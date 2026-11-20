#!/usr/bin/env bash
# HTTP smoke test against the PHP built-in server: bash tests/smoke.sh
# Starts its own server on a free port with a throw-away environment (no .env needed).
set -u
cd "$(dirname "$0")/.."

PORT="${PORT:-8765}"
BASE="http://127.0.0.1:${PORT}"
LOGDIR="$(mktemp -d)"
APP_ENV=local APP_DEBUG=false APP_URL="$BASE" php -S "127.0.0.1:${PORT}" -t public >"$LOGDIR/server.log" 2>&1 &
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
for p in "" about topics news assignments practical-lessons laboratory-lessons materials contact; do
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
expect_no_body "no 'Asosiy fanlar' section" "Asosiy fanlar" "$BASE/uz/"
expect_no_body "no 'Fanlar' menu item" ">Fanlar<" "$BASE/uz/"
expect_no_body "no 'Kafedra' footer block" "Kafedra</p>" "$BASE/uz/"
expect_no_body "no placeholder markers" "[kiritiladi]" "$BASE/uz/contact"
expect_no_body "contact page has no footer CTA button" 'class="btn" href="/uz/contact"' "$BASE/uz/contact"
expect_no_body "news page has no footer CTA button" 'class="btn" href="/uz/contact"' "$BASE/uz/news"
expect_body "news empty state" "Hozircha e‘lon qilingan yangiliklar yo‘q." "$BASE/uz/news"
expect_body "nav marks current page" 'href="/uz/news" aria-current="page"' "$BASE/uz/news"
expect_body "canonical URL" "<link rel=\"canonical\" href=\"$BASE/uz/news\">" "$BASE/uz/news"
expect_body "html lang" '<html lang="uz">' "$BASE/uz/"
expect_body "404 page rendered in layout" "Sahifa topilmadi" "$BASE/uz/nope"
expect_body "404 is noindex" 'name="robots" content="noindex"' "$BASE/uz/nope"
expect_body "contact form disabled" "<fieldset class=\"form__fields\" disabled>" "$BASE/uz/contact"
expect_status "css served" 200 "$BASE/assets/css/site.css"
expect_status "professor photo served" 200 "$BASE/assets/images/professor.jpg"

echo "Unavailable translations"
expect_status "ru home" 200 "$BASE/ru/"
expect_body "ru shows notice" "Перевод на этот язык пока недоступен." "$BASE/ru/"
expect_no_body "ru does not reuse Uzbek content" "Neft va gazni" "$BASE/ru/"
expect_body "en shows notice" "This language is not available yet." "$BASE/en/about"
expect_body "ru is noindex" 'name="robots" content="noindex"' "$BASE/ru/"

echo "Headers"
hdrs=$(curl -s -D - -o /dev/null "$BASE/uz/")
for h in "X-Content-Type-Options: nosniff" "X-Frame-Options: SAMEORIGIN" "Referrer-Policy: strict-origin-when-cross-origin" "Permissions-Policy:"; do
  echo "$hdrs" | grep -qiF "$h" && ok "header $h" || bad "missing header $h"
done
echo "$hdrs" | grep -qi '^X-Powered-By' && bad "X-Powered-By exposed" || ok "no X-Powered-By"
allow=$(curl -s -D - -o /dev/null -X POST "$BASE/uz/" | grep -i '^Allow:' | tr -d '\r')
[ -n "$allow" ] && ok "405 sends Allow ($allow)" || bad "405 missing Allow"

echo "Escaping"
expect_no_body "query string never reflected" "<script>alert(1)</script>" "$BASE/uz/?q=<script>alert(1)</script>"
expect_no_body "path never reflected on 404" "<script>" "$BASE/uz/%3Cscript%3E"

echo
echo "$pass passed, $fail failed"
[ "$fail" -eq 0 ]
