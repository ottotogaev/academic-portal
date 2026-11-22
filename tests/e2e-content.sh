#!/usr/bin/env bash
# End-to-end content management on a DISPOSABLE database (it empties the content and admin tables).
# Needs DB_* env vars and migrations applied: bash tests/e2e-content.sh
set -u
cd "$(dirname "$0")/.."
: "${DB_DATABASE:?set DB_* variables for a disposable test database}"
export APP_ENV=local APP_DEBUG=false SESSION_SECURE=false APP_TIMEZONE=Asia/Tashkent
PORT="${PORT:-8767}"; BASE="http://127.0.0.1:${PORT}"; export APP_URL="$BASE"
JAR="$(mktemp)"; LOGDIR="$(mktemp -d)"
php -r '$basePath=getcwd(); $a=require "bootstrap/app.php"; $p=$a->database()->pdo();
foreach(["assignment_translations","assignments","lesson_translations","lessons","news_translations","news","topic_translations","topics","login_attempts","admins"] as $t){$p->exec("DELETE FROM $t");}'
ADMIN_PASSWORD='content test password' php bin/create-admin.php prof >/dev/null || exit 1
php -S "127.0.0.1:${PORT}" -t public >"$LOGDIR/server.log" 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf "$LOGDIR" "$JAR"' EXIT
for _ in $(seq 1 30); do curl -s -o /dev/null "$BASE/health" && break; sleep 0.2; done
pass=0; fail=0
ok(){ pass=$((pass+1)); echo "  ok   $1"; }; bad(){ fail=$((fail+1)); echo "  FAIL $1"; }
code(){ curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$@"; }
get(){ curl -s -b "$JAR" -c "$JAR" "$@"; }
tok(){ get "$1" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
pub(){ curl -s "$@"; }
has(){ local n=$1 needle=$2; shift 2; "$@" | grep -qF -- "$needle" && ok "$n" || bad "$n (missing: $needle)"; }
hasnt(){ local n=$1 needle=$2; shift 2; "$@" | grep -qF -- "$needle" && bad "$n (found: $needle)" || ok "$n"; }
# post TYPE-PATH TOKEN fields... (curl --data-urlencode pairs)
post(){ local path=$1 t=$2; shift 2; code -X POST --data-urlencode "_token=$t" "$@" "$BASE$path"; }
locationof(){ curl -s -o /dev/null -w '%{redirect_url}' -b "$JAR" -c "$JAR" -X POST --data-urlencode "_token=$1" "${@:3}" "$BASE$2"; }

[ "$(code "$BASE/admin/news")" = 302 ] && ok "content list requires login" || bad "list open without login"
[ "$(code "$BASE/admin/news/new")" = 302 ] && ok "content form requires login" || bad "form open without login"
[ "$(code -X POST --data-urlencode uz__title=x "$BASE/admin/news")" = 302 ] && ok "anonymous POST redirected to login" || bad "anonymous POST"
T=$(tok "$BASE/admin/login")
[ "$(post /admin/login "$T" --data-urlencode username=prof --data-urlencode 'password=content test password')" = 302 ] && ok "logged in" || bad "login"
[ "$(code -X POST --data-urlencode uz__title=x --data-urlencode uz__body=y "$BASE/admin/news")" = 400 ] && ok "content POST without CSRF token rejected" || bad "csrf on content POST"
for p in topics news assignments practical laboratory; do [ "$(code "$BASE/admin/$p")" = 200 ] && ok "admin list $p" || bad "admin list $p"; done
[ "$(code "$BASE/admin/nope")" = 404 ] && ok "unknown content type 404" || bad "unknown type"

echo "Validation"
T=$(tok "$BASE/admin/news/new")
[ "$(post /admin/news "$T" --data-urlencode uz__title= --data-urlencode uz__body=b --data-urlencode status=draft)" = 422 ] && ok "empty title -> 422" || bad "empty title"
[ "$(post /admin/news "$T" --data-urlencode uz__title=t --data-urlencode uz__body=b --data-urlencode status=hacked)" = 422 ] && ok "bad status -> 422" || bad "bad status"
[ "$(post /admin/news "$T" --data-urlencode uz__title=t --data-urlencode uz__body=b --data-urlencode 'slug=Bad Slug!' --data-urlencode status=draft)" = 422 ] && ok "bad slug -> 422" || bad "bad slug"
[ "$(post /admin/news "$T" --data-urlencode uz__title=t --data-urlencode uz__body=b --data-urlencode published_at=not-a-date --data-urlencode status=draft)" = 422 ] && ok "bad date -> 422" || bad "bad date"
get -X POST --data-urlencode "_token=$T" --data-urlencode uz__title= --data-urlencode uz__body=keepme "$BASE/admin/news" | grep -q 'keepme' && ok "form keeps typed values on error" || bad "values lost on error"

echo "News lifecycle"
LOC=$(locationof "$T" /admin/news --data-urlencode "uz__title=Yangi o‘quv yili boshlandi" --data-urlencode 'uz__summary=Qisqa mazmun' --data-urlencode $'uz__body=Birinchi abzats.\n\nIkkinchi abzats <b>teg emas</b>.' --data-urlencode status=draft)
echo "$LOC" | grep -qE '/admin/news/[0-9]+/edit$' && ok "create redirects to edit page" || bad "create redirect ($LOC)"
NID=$(echo "$LOC" | grep -oE '[0-9]+/edit' | cut -d/ -f1)
hasnt "draft is not in the public list" "Yangi o‘quv yili" pub "$BASE/uz/news"
[ "$(code "$BASE/uz/news/yangi-oquv-yili-boshlandi")" = 404 ] && ok "draft detail is 404 (slug generated from title)" || bad "draft visible or slug wrong"
get "$BASE/admin/news/$NID/edit" | grep -q 'Saqlandi' && ok "flash message shown after save" || bad "no flash"
T=$(tok "$BASE/admin/news/$NID/edit")
[ "$(post /admin/news/$NID "$T" --data-urlencode "uz__title=Yangi o‘quv yili boshlandi" --data-urlencode 'uz__summary=Qisqa mazmun' --data-urlencode $'uz__body=Birinchi abzats.\n\nIkkinchi abzats <b>teg emas</b>.' --data-urlencode slug=yangi-oquv-yili-boshlandi --data-urlencode status=published)" = 302 ] && ok "publish" || bad "publish"
has "published item in public list" "Yangi o‘quv yili boshlandi" pub "$BASE/uz/news"
has "list links to the slug page" 'href="/uz/news/yangi-oquv-yili-boshlandi"' pub "$BASE/uz/news"
[ "$(code "$BASE/uz/news/yangi-oquv-yili-boshlandi")" = 200 ] && ok "detail page 200" || bad "detail"
has "paragraphs rendered" '<p>Ikkinchi abzats &lt;b&gt;teg emas&lt;/b&gt;.</p>' pub "$BASE/uz/news/yangi-oquv-yili-boshlandi"
hasnt "HTML in body is escaped, never executed" '<b>teg emas</b>' pub "$BASE/uz/news/yangi-oquv-yili-boshlandi"
has "detail has own title" '<title>Yangi o‘quv yili boshlandi · ' pub "$BASE/uz/news/yangi-oquv-yili-boshlandi"
[ "$(code "$BASE/ru/news/yangi-oquv-yili-boshlandi")" = 404 ] && ok "news without a ru translation is 404 in ru" || bad "ru detail without translation"
[ "$(code "$BASE/uz/news/Bad_Slug")" = 404 ] && ok "malformed slug is 404" || bad "malformed slug"
[ "$(post /admin/news "$T" --data-urlencode uz__title=Boshqa --data-urlencode uz__body=b --data-urlencode slug=yangi-oquv-yili-boshlandi --data-urlencode status=draft)" = 422 ] && ok "duplicate slug rejected" || bad "duplicate slug"
# same title again gets a unique generated slug
LOC2=$(locationof "$T" /admin/news --data-urlencode "uz__title=Yangi o‘quv yili boshlandi" --data-urlencode uz__body=b --data-urlencode status=published)
[ "$(code "$BASE/uz/news/yangi-oquv-yili-boshlandi-2")" = 200 ] && ok "duplicate title gets slug -2" || bad "slug uniqueness"
# scheduled in the future stays hidden
LOC3=$(locationof "$T" /admin/news --data-urlencode "uz__title=Kelajak yangiligi" --data-urlencode uz__body=b --data-urlencode status=published --data-urlencode published_at=2099-01-01T10:00)
hasnt "future-dated news is hidden" "Kelajak yangiligi" pub "$BASE/uz/news"
# XSS in title
locationof "$T" /admin/news --data-urlencode 'uz__title=<script>alert(1)</script>' --data-urlencode uz__body=b --data-urlencode status=published >/dev/null
hasnt "script in title is escaped on the public list" '<script>alert(1)</script>' pub "$BASE/uz/news"
has "…as text" '&lt;script&gt;alert(1)&lt;/script&gt;' pub "$BASE/uz/news"
# archive hides
T=$(tok "$BASE/admin/news/$NID/edit")
post /admin/news/$NID "$T" --data-urlencode "uz__title=Yangi o‘quv yili boshlandi" --data-urlencode uz__body=b --data-urlencode slug=yangi-oquv-yili-boshlandi --data-urlencode status=archived >/dev/null
[ "$(code "$BASE/uz/news/yangi-oquv-yili-boshlandi")" = 404 ] && ok "archived item is hidden" || bad "archived visible"

echo "Topics, assignments, lessons"
TT=$(tok "$BASE/admin/topics/new")
TLOC=$(locationof "$TT" /admin/topics --data-urlencode "uz__title=Neftni birlamchi qayta ishlash" --data-urlencode 'uz__description=Haydash jarayoni' --data-urlencode sort_order=1 --data-urlencode status=published)
TID=$(echo "$TLOC" | grep -oE '[0-9]+/edit' | cut -d/ -f1)
has "topic shown on the public topics page" "Neftni birlamchi qayta ishlash" pub "$BASE/uz/topics"
ALOC=$(locationof "$TT" /admin/assignments --data-urlencode "uz__title=Birinchi topshiriq" --data-urlencode uz__description=Tavsif --data-urlencode topic_id=$TID --data-urlencode deadline=2026-12-01T10:30 --data-urlencode status=published)
AID=$(echo "$ALOC" | grep -oE '[0-9]+/edit' | cut -d/ -f1)
has "assignment deadline shown in local time" "01.12.2026 10:30" pub "$BASE/uz/assignments"
get "$BASE/admin/assignments/$AID/edit" | grep -q 'value="2026-12-01T10:30"' && ok "deadline round-trips through UTC" || bad "deadline round trip"
[ "$(post /admin/assignments "$TT" --data-urlencode uz__title=X --data-urlencode uz__description=Y --data-urlencode topic_id=99999 --data-urlencode status=draft)" = 422 ] && ok "unknown topic rejected" || bad "unknown topic accepted"
locationof "$TT" /admin/practical --data-urlencode "uz__title=Amaliy bir" --data-urlencode uz__description=D --data-urlencode status=published >/dev/null
locationof "$TT" /admin/laboratory --data-urlencode "uz__title=Laboratoriya bir" --data-urlencode uz__description=D --data-urlencode "uz__safety_information=Himoya ko‘zoynagi taqing" --data-urlencode status=published >/dev/null
has "practical page lists practical lessons" "Amaliy bir" pub "$BASE/uz/practical-lessons"
hasnt "practical page does not list laboratory lessons" "Laboratoriya bir" pub "$BASE/uz/practical-lessons"
has "laboratory page lists lab lessons with safety note" "Himoya ko‘zoynagi taqing" pub "$BASE/uz/laboratory-lessons"
hasnt "laboratory page does not list practical lessons" "Amaliy bir" pub "$BASE/uz/laboratory-lessons"

echo "Deleting"
[ "$(post /admin/topics/$TID/delete "$TT")" = 302 ] && ok "delete request handled" || bad "delete topic request"
get "$BASE/admin/topics" | grep -q 'ishlatilmoqda' && ok "topic in use cannot be deleted (explained)" || bad "in-use message"
[ "$(code "$BASE/admin/topics/$TID/edit")" = 200 ] && ok "topic still exists" || bad "topic was deleted"
post /admin/assignments/$AID/delete "$TT" >/dev/null
[ "$(code "$BASE/admin/assignments/$AID/edit")" = 404 ] && ok "assignment deleted" || bad "assignment not deleted"
[ "$(post /admin/topics/$TID/delete "$TT")" = 302 ] && [ "$(code "$BASE/admin/topics/$TID/edit")" = 404 ] && ok "topic deletable once unused" || bad "topic delete after cleanup"
[ "$(post /admin/news/999999/delete "$TT")" = 404 ] && ok "deleting a missing item is 404" || bad "missing delete"
[ "$(code "$BASE/admin/news/abc/edit")" = 404 ] && ok "non-numeric id is 404" || bad "bad id"
get "$BASE/admin/dashboard" | grep -q 'admin-tile' && ok "dashboard shows content counts" || bad "dashboard tiles"

echo "Translations (ru, en) and the home page"
T=$(tok "$BASE/admin/news/new")
get "$BASE/admin/news/new" | grep -q 'name="ru__title"' && get "$BASE/admin/news/new" | grep -q 'name="en__title"' && ok "admin form has Russian and English sections" || bad "no ru/en sections"
LOC=$(locationof "$T" /admin/news --data-urlencode "uz__title=Uch tilli yangilik" --data-urlencode "uz__body=O‘zbekcha matn" --data-urlencode "ru__title=Новость на трёх языках" --data-urlencode "ru__body=Русский текст" --data-urlencode "en__title=Three language news" --data-urlencode "en__body=English text" --data-urlencode status=published)
NID3=$(echo "$LOC" | grep -oE '[0-9]+/edit' | cut -d/ -f1)
SL=uch-tilli-yangilik
has "uz list shows the Uzbek title" "Uch tilli yangilik" pub "$BASE/uz/news"
has "ru list shows the Russian title" "Новость на трёх языках" pub "$BASE/ru/news"
has "en list shows the English title" "Three language news" pub "$BASE/en/news"
hasnt "ru list does not show the Uzbek title" "Uch tilli yangilik" pub "$BASE/ru/news"
has "ru detail page in Russian" "Русский текст" pub "$BASE/ru/news/$SL"
has "en detail page in English" "English text" pub "$BASE/en/news/$SL"
has "ru detail has translated back link" "Все новости" pub "$BASE/ru/news/$SL"
has "same slug in every language (switcher works)" 'href="/en/news/'$SL'"' pub "$BASE/ru/news/$SL"
# an item with only an Uzbek translation is hidden in the other languages
locationof "$T" /admin/news --data-urlencode "uz__title=Faqat o‘zbekcha" --data-urlencode "uz__body=Matn" --data-urlencode status=published >/dev/null
hasnt "item without a ru translation is not listed in ru" "Faqat o‘zbekcha" pub "$BASE/ru/news"
hasnt "item without an en translation is not listed in en" "Faqat o‘zbekcha" pub "$BASE/en/news"
[ "$(code "$BASE/ru/news/faqat-ozbekcha")" = 404 ] && ok "its ru detail page is 404" || bad "ru detail without translation"
# partial translation: a title without a body is refused
T=$(tok "$BASE/admin/news/$NID3/edit")
[ "$(post /admin/news/$NID3 "$T" --data-urlencode "uz__title=Uch tilli yangilik" --data-urlencode "uz__body=x" --data-urlencode "ru__title=Только заголовок" --data-urlencode "ru__body=" --data-urlencode slug=$SL --data-urlencode status=published)" = 422 ] && ok "half-filled Russian translation refused" || bad "partial translation accepted"
# removing the Russian title deletes the Russian translation
post /admin/news/$NID3 "$T" --data-urlencode "uz__title=Uch tilli yangilik" --data-urlencode "uz__body=x" --data-urlencode "ru__title=" --data-urlencode "ru__body=" --data-urlencode "en__title=Three language news" --data-urlencode "en__body=English text" --data-urlencode slug=$SL --data-urlencode status=published >/dev/null
hasnt "emptied Russian translation disappears" "Новость на трёх языках" pub "$BASE/ru/news"
has "English translation untouched" "Three language news" pub "$BASE/en/news"
# the home page lists the latest published news
has "home shows the latest news (uz)" "Uch tilli yangilik" pub "$BASE/uz/"
has "home shows English news in English" "Three language news" pub "$BASE/en/"
hasnt "home hides drafts" "Kelajak yangiligi" pub "$BASE/uz/"

echo; echo "$pass passed, $fail failed"; [ "$fail" -eq 0 ]
