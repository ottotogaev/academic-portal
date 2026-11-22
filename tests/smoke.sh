#!/usr/bin/env bash
# HTTP smoke test against the PHP built-in server: bash tests/smoke.sh
# Starts its own server on a free port with a throw-away environment (no .env needed).
set -u
cd "$(dirname "$0")/.."

CONTACT="${FEATURE_CONTACT:-false}"
PORT="${PORT:-8765}"
BASE="http://127.0.0.1:${PORT}"
LOGDIR="$(mktemp -d)"
APP_ENV=local APP_DEBUG=false SESSION_SECURE=false FEATURE_CONTACT="${FEATURE_CONTACT:-false}" APP_URL="$BASE" php -S "127.0.0.1:${PORT}" -t public >"$LOGDIR/server.log" 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null; rm -rf "$LOGDIR"' EXIT
for _ in $(seq 1 30); do curl -s -o /dev/null "$BASE/health" && break; sleep 0.2; done

fail=0
pass=0
ok()   { pass=$((pass+1)); echo "  ok   $1"; }
bad()  { fail=$((fail+1)); echo "  FAIL $1"; }

status() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
body()   { curl -s "$@"; }

expect_status() { # name expected curl-args...
  local name=$1 want=$2; shift 2
  local got; got=$(status "$@")
  [ "$got" = "$want" ] && ok "$name ($got)" || bad "$name: expected $want, got $got"
}
expect_body() { # name needle curl-args...
  local name=$1 needle=$2; shift 2
  body "$@" | grep -qF -- "$needle" && ok "$name" || bad "$name: missing '$needle'"
}
expect_no_body() {
  local name=$1 needle=$2; shift 2
  body "$@" | grep -qF -- "$needle" && bad "$name: found '$needle'" || ok "$name"
}

echo "Routing"
expect_status "health" 200 "$BASE/health"
[ "$(body "$BASE/health")" = "ok" ] && ok "health body is exactly 'ok'" || bad "health body"
expect_status "root redirects" 302 "$BASE/"
[ "$(curl -s -o /dev/null -w '%{redirect_url}' "$BASE/")" = "$BASE/uz/" ] && ok "root redirects to /uz/" || bad "root redirect target"
for p in "" about topics news assignments practical-lessons laboratory-lessons materials; do
  expect_status "GET /uz/$p" 200 "$BASE/uz/$p"
done
expect_status "trailing slash /uz/about/" 200 "$BASE/uz/about/"
expect_status "HEAD /uz/" 200 -I "$BASE/uz/"
expect_status "unknown page" 404 "$BASE/uz/nope"
expect_status "unknown locale" 404 "$BASE/de/"
expect_status "POST to page" 405 -X POST "$BASE/uz/"
expect_status "malformed encoding" 400 "$BASE/uz/%FF"
expect_status "NUL byte" 400 "$BASE/uz/%00"
expect_status "traversal is not served" 404 --path-as-is "$BASE/uz/../../.env"
expect_status "hidden .env not reachable" 404 "$BASE/.env"
expect_status "app source not reachable" 404 "$BASE/app/Core/App.php"
expect_status "config not reachable" 404 "$BASE/config/app.php"

echo "Content (uz)"
expect_body "home headline" "Neft va gazni" "$BASE/uz/"
expect_body "home has professor name" "Samatov San&#039;at Baxtiyorovich" "$BASE/uz/"
expect_body "home has contact CTA" "Xabar yuborish" "$BASE/uz/"
expect_body "menu has Aloqa" '>Aloqa<' "$BASE/uz/"
expect_no_body "no 'Asosiy fanlar' section" "Asosiy fanlar" "$BASE/uz/"
expect_no_body "no 'Fanlar' menu item" ">Fanlar<" "$BASE/uz/"
expect_no_body "no 'Kafedra' footer block" "Kafedra</p>" "$BASE/uz/"
expect_no_body "news page has no footer CTA button" 'class="btn" href="/uz/contact"' "$BASE/uz/news"
expect_body "news empty state" "Hozircha e‘lon qilingan yangiliklar yo‘q." "$BASE/uz/news"
expect_body "nav marks current page" 'href="/uz/news" aria-current="page"' "$BASE/uz/news"
expect_body "canonical URL" "<link rel=\"canonical\" href=\"$BASE/uz/news\">" "$BASE/uz/news"
expect_body "html lang" '<html lang="uz">' "$BASE/uz/"
expect_body "404 page rendered in layout" "Sahifa topilmadi" "$BASE/uz/nope"
expect_body "404 is noindex" 'name="robots" content="noindex"' "$BASE/uz/nope"
expect_status "contact page" 200 "$BASE/uz/contact"
expect_no_body "no placeholder markers" "[kiritiladi]" "$BASE/uz/contact"
expect_body "contact form posts to the locale URL" '<form class="form" method="post" action="/uz/contact"' "$BASE/uz/contact"
if [ "$CONTACT" = true ]; then
  expect_body "contact form has csrf token" 'name="_token"' "$BASE/uz/contact"
  expect_body "contact form has honeypot" 'name="website"' "$BASE/uz/contact"
  expect_no_body "contact form is not disabled" "disabled" "$BASE/uz/contact"
  curl -s -D - -o /dev/null "$BASE/uz/contact" | grep -qi '^Cache-Control: no-store' && ok "contact page is no-store" || bad "contact page cacheable"
  expect_status "contact POST without token rejected" 400 -X POST -d 'name=Ali&email=a@b.uz&message=salom dunyo hammaga' "$BASE/uz/contact"
  expect_status "contact POST to another locale also needs the token" 400 -X POST -d 'name=x' "$BASE/ru/contact"
else
  expect_body "contact fields are disabled while the feature is off" '<fieldset class="form__fields" disabled>' "$BASE/uz/contact"
  expect_body "submit button is disabled" 'type="submit" disabled' "$BASE/uz/contact"
  expect_body "unavailable note shown" "Xabar yuborish hozircha ishlamaydi" "$BASE/uz/contact"
  expect_no_body "no csrf token while off" 'name="_token"' "$BASE/uz/contact"
  expect_status "contact POST has no route while the feature is off" 405 -X POST -d 'x=1' "$BASE/uz/contact"
fi
expect_status "css served" 200 "$BASE/assets/css/site.css"
expect_status "professor photo served" 200 "$BASE/assets/images/professor.jpg"

echo "Russian and English"
for L in ru en; do expect_status "GET /$L/ pages" 200 "$BASE/$L/"; done
for p in about topics news assignments practical-lessons laboratory-lessons materials; do
  expect_status "GET /ru/$p" 200 "$BASE/ru/$p"
  expect_status "GET /en/$p" 200 "$BASE/en/$p"
done
expect_body "ru html lang" '<html lang="ru">' "$BASE/ru/"
expect_body "en html lang" '<html lang="en">' "$BASE/en/"
expect_body "ru headline" "Процессы и аппараты" "$BASE/ru/"
expect_body "en headline" "Processes and equipment for" "$BASE/en/"
expect_body "ru name in Cyrillic" "Саматов Санъат Бахтиёрович" "$BASE/ru/"
expect_body "en university" "University of Economics and Pedagogy" "$BASE/en/"
expect_body "ru menu translated" ">Новости<" "$BASE/ru/"
expect_body "en menu translated" ">News<" "$BASE/en/"
expect_body "ru empty state" "Опубликованных новостей пока нет." "$BASE/ru/news"
expect_body "en empty state" "No news has been published yet." "$BASE/en/news"
expect_no_body "ru page has no Uzbek headline" "Neft va gazni" "$BASE/ru/"
expect_no_body "en page has no Uzbek headline" "Neft va gazni" "$BASE/en/about"
expect_no_body "ru menu has no Uzbek labels" "Yangiliklar" "$BASE/ru/"
expect_body "ru 404 translated" "Страница не найдена" "$BASE/ru/nope"
expect_body "en 404 translated" "Page not found" "$BASE/en/nope"
expect_no_body "ru is indexable" 'name="robots" content="noindex"' "$BASE/ru/"
expect_body "language switcher keeps the page" 'href="/en/news"' "$BASE/ru/news"
expect_body "hreflang alternates" '<link rel="alternate" hreflang="ru" href="' "$BASE/uz/news"
expect_body "hreflang x-default" 'hreflang="x-default"' "$BASE/en/"
expect_status "unknown locale still 404" 404 "$BASE/de/"

echo "Admin (no database needed)"
expect_status "admin root redirects" 302 "$BASE/admin"
expect_status "dashboard requires login" 302 "$BASE/admin/dashboard"
[ "$(curl -s -o /dev/null -w '%{redirect_url}' "$BASE/admin/dashboard")" = "$BASE/admin/login" ] && ok "dashboard redirects to login" || bad "dashboard redirect target"
expect_status "password page requires login" 302 "$BASE/admin/password"
expect_status "logout without session/token" 302 -X POST "$BASE/admin/logout"
for p in dashboard password profile topics topics/new topics/1/edit news news/new news/1/edit assignments practical laboratory categories materials materials/new materials/1/edit; do
  expect_status "GET /admin/$p needs login" 302 "$BASE/admin/$p"
done
for p in password profile topics topics/1 topics/1/delete news news/1 materials materials/1/delete logout; do
  expect_status "POST /admin/$p needs login" 302 -X POST -d 'x=1' "$BASE/admin/$p"
done
if [ "$CONTACT" = true ]; then
  for p in feedback feedback/1; do expect_status "GET /admin/$p needs login" 302 "$BASE/admin/$p"; done
  for p in feedback/1/status feedback/1/delete; do expect_status "POST /admin/$p needs login" 302 -X POST -d 'x=1' "$BASE/admin/$p"; done
fi
expect_status "login page" 200 "$BASE/admin/login"
expect_body "login form has csrf field" 'name="_token"' "$BASE/admin/login"
expect_no_body "login page is not a public page (no site nav)" 'aria-current="page"' "$BASE/admin/login"
alh=$(curl -s -D - -o /dev/null "$BASE/admin/login")
echo "$alh" | grep -qi '^Cache-Control: no-store' && ok "login is no-store" || bad "login not no-store"
echo "$alh" | grep -qi '^X-Robots-Tag: noindex' && ok "login is noindex" || bad "login not noindex"
echo "$alh" | grep -qi '^Set-Cookie: ap_sid=' && ok "session cookie set on login page" || bad "no session cookie"
echo "$alh" | grep -i '^Set-Cookie: ap_sid=' | grep -qi 'HttpOnly' && ok "session cookie is HttpOnly" || bad "cookie not HttpOnly"
echo "$alh" | grep -i '^Set-Cookie: ap_sid=' | grep -qi 'SameSite=Lax' && ok "session cookie is SameSite=Lax" || bad "cookie SameSite"
expect_status "login POST without csrf token is rejected" 400 -X POST -d 'username=a&password=b' "$BASE/admin/login"
expect_status "login POST with wrong csrf token is rejected" 400 -X POST -d 'username=a&password=b&_token=nope' "$BASE/admin/login"
expect_no_body "public pages set no session cookie" "ap_sid" "$BASE/uz/"
curl -s -D - -o /dev/null "$BASE/uz/" | grep -qi '^Set-Cookie' && bad "public page sets a cookie" || ok "public page sets no cookie"
expect_status "session files not reachable" 404 "$BASE/storage/sessions/.gitkeep"

echo "Headers"
hdrs=$(curl -s -D - -o /dev/null "$BASE/uz/")
for h in "Content-Security-Policy: default-src 'self'" "X-Content-Type-Options: nosniff" "X-Frame-Options: SAMEORIGIN" "Referrer-Policy: strict-origin-when-cross-origin" "Permissions-Policy:"; do
  echo "$hdrs" | grep -qiF "$h" && ok "header $h" || bad "missing header $h"
done
echo "$hdrs" | grep -qi '^X-Powered-By' && bad "X-Powered-By exposed" || ok "no X-Powered-By"
allow=$(curl -s -D - -o /dev/null -X POST "$BASE/uz/" | grep -i '^Allow:' | tr -d '\r')
[ -n "$allow" ] && ok "405 sends Allow ($allow)" || bad "405 missing Allow"

hdrs_html=$(curl -s "$BASE/uz/" "$BASE/admin/login")
echo "$hdrs_html" | grep -qE 'fonts\.googleapis|fonts\.gstatic' && bad "page still loads Google Fonts" || ok "no external font requests"
echo "$hdrs_html" | grep -qE '<script[^>]*>[^<]' && bad "inline script present (CSP would block it)" || ok "no inline scripts"
echo "$hdrs_html" | grep -qE ' on[a-z]+="' && bad "inline event handler present" || ok "no inline event handlers"
echo "$hdrs" | grep -qi '^Strict-Transport-Security' && bad "HSTS sent over plain http" || ok "no HSTS on an http site"
for f in manrope-latin.woff2 manrope-latin-ext.woff2 manrope-cyrillic.woff2 ibm-plex-mono-latin-500.woff2; do expect_status "font $f served" 200 "$BASE/assets/fonts/$f"; done
expect_status "admin.js served" 200 "$BASE/assets/js/admin.js"

echo "Escaping"
expect_no_body "query string never reflected" "<script>alert(1)</script>" "$BASE/uz/?q=<script>alert(1)</script>"
expect_no_body "path never reflected on 404" "<script>" "$BASE/uz/%3Cscript%3E"

echo
echo "$pass passed, $fail failed"
[ "$fail" -eq 0 ]
