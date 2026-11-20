#!/usr/bin/env bash
# End-to-end file uploads and downloads on a DISPOSABLE database and storage/uploads/materials.
# Needs DB_* env vars and migrations applied: bash tests/e2e-materials.sh
set -u
cd "$(dirname "$0")/.."
: "${DB_DATABASE:?set DB_* variables for a disposable test database}"
export APP_ENV=local APP_DEBUG=false SESSION_SECURE=false UPLOAD_MAX_SIZE=20000
PORT="${PORT:-8768}"; BASE="http://127.0.0.1:${PORT}"; export APP_URL="$BASE"
UP=storage/uploads/materials
JAR="$(mktemp)"; LOGDIR="$(mktemp -d)"; W="$(mktemp -d)"
php -r '$basePath=getcwd(); $a=require "bootstrap/app.php"; $p=$a->database()->pdo();
foreach(["material_translations","materials","material_category_translations","material_categories","topic_translations","topics","login_attempts","admins"] as $t){$p->exec("DELETE FROM $t");}'
rm -rf "$UP"
ADMIN_PASSWORD='upload test password' php bin/create-admin.php prof >/dev/null || exit 1
php -S "127.0.0.1:${PORT}" -t public >"$LOGDIR/server.log" 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf "$LOGDIR" "$JAR" "$W"' EXIT
for _ in $(seq 1 30); do curl -s -o /dev/null "$BASE/health" && break; sleep 0.2; done
pass=0; fail=0
ok(){ pass=$((pass+1)); echo "  ok   $1"; }; bad(){ fail=$((fail+1)); echo "  FAIL $1"; }
code(){ curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$@"; }
tok(){ curl -s -b "$JAR" -c "$JAR" "$1" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
count(){ ls "$UP" 2>/dev/null | grep -vc '^$' ; }

printf '%%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF\n' > "$W/dars.pdf"
printf '%%PDF-1.4\n2 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF\n' > "$W/yangi.pdf"
printf '<?php system($_GET["c"]); ?>' > "$W/shell.pdf"
cp "$W/dars.pdf" "$W/shell.php"
head -c 30000 /dev/zero | tr '\0' 'a' > "$W/big.txt"
printf '<html><script>alert(1)</script></html>' > "$W/page.png"

T=$(tok "$BASE/admin/login")
code -X POST --data-urlencode username=prof --data-urlencode 'password=upload test password' --data-urlencode "_token=$T" "$BASE/admin/login" >/dev/null
T=$(tok "$BASE/admin/materials/new")
up(){ # upload FILE [name] [extra -F ...]
  local f=$1 n=$2; shift 2
  code -X POST -F "_token=$T" -F "uz__title=Ma‘ruza matni" -F "uz__description=Tavsif" -F status=published -F "file=@$f;filename=$n" "$@" "$BASE/admin/materials"
}
loc(){ curl -s -o /dev/null -w '%{redirect_url}' -b "$JAR" -c "$JAR" -X POST -F "_token=$T" -F "uz__title=Ma‘ruza matni" -F "uz__description=Tavsif" -F status=published -F "file=@$1;filename=$2" "${@:3}" "$BASE/admin/materials"; }

echo "Rejected uploads"
[ "$(code -X POST -F "_token=$T" -F uz__title=x -F status=draft "$BASE/admin/materials")" = 422 ] && ok "file is required on create" || bad "file not required"
[ "$(up "$W/shell.pdf" a.pdf)" = 422 ] && ok "PHP code disguised as .pdf refused" || bad "disguised php accepted"
[ "$(up "$W/shell.php" shell.php)" = 422 ] && ok ".php refused" || bad ".php accepted"
[ "$(up "$W/page.png" a.png)" = 422 ] && ok "HTML disguised as .png refused" || bad "html as png accepted"
[ "$(up "$W/big.txt" big.txt)" = 422 ] && ok "oversize file refused (UPLOAD_MAX_SIZE)" || bad "oversize accepted"
[ "$(count)" = 0 ] && ok "nothing stored after refused uploads" || bad "files stored: $(count)"
curl -s -b "$JAR" -X POST -F "_token=$T" -F uz__title=x -F status=draft -F "file=@$W/shell.php;filename=s.php" "$BASE/admin/materials" | grep -q 'ruxsat etilmagan' && ok "clear error message shown" || bad "no error message"
[ "$(code -X POST -F uz__title=x -F status=draft -F "file=@$W/dars.pdf" "$BASE/admin/materials")" = 400 ] && ok "upload without CSRF token rejected" || bad "csrf on upload"

echo "Accepted upload"
L=$(loc "$W/dars.pdf" "Dars matni.pdf")
echo "$L" | grep -qE '/admin/materials/[0-9]+/edit$' && ok "upload accepted" || bad "upload failed ($L)"
MID=$(echo "$L" | grep -oE '[0-9]+/edit' | cut -d/ -f1)
[ "$(count)" = 1 ] && ok "one file stored" || bad "stored count $(count)"
F=$(ls "$UP"); echo "$F" | grep -qE '^[a-f0-9]{32}\.pdf$' && ok "stored under a random name (not the original)" || bad "stored name $F"
[ "$(stat -c %a "$UP/$F")" = 640 ] && ok "file mode 0640" || bad "file mode $(stat -c %a "$UP/$F")"
curl -s -b "$JAR" "$BASE/admin/materials/$MID/edit" | grep -q 'Dars matni.pdf' && ok "edit page shows the current file name" || bad "no current file"
curl -s "$BASE/uz/materials" | grep -q 'Ma‘ruza matni' && ok "published material listed publicly" || bad "not listed"
curl -s "$BASE/uz/materials" | grep -q "$F" && bad "stored name leaked into HTML" || ok "stored name never appears in HTML"
curl -s "$BASE/uz/materials" | grep -q 'Dars matni.pdf' && bad "original file name leaked in list" || ok "list shows no file name"

echo "Download"
H=$(curl -s -D - -o "$W/got.pdf" "$BASE/uz/materials/$MID/download")
echo "$H" | head -1 | grep -q 200 && ok "download 200" || bad "download status"
cmp -s "$W/got.pdf" "$W/dars.pdf" && ok "downloaded bytes are identical" || bad "bytes differ"
echo "$H" | grep -qi '^Content-Type: application/pdf' && ok "content type from the stored (safe) mime" || bad "content type"
echo "$H" | grep -qi '^Content-Disposition: attachment; filename="Dars_matni.pdf"; filename\*=UTF-8' && ok "served as attachment with the original name" || bad "disposition: $(echo "$H" | grep -i disposition)"
echo "$H" | grep -qi '^X-Content-Type-Options: nosniff' && ok "nosniff" || bad "nosniff"
[ "$(curl -s -o /dev/null -w '%{http_code}' -I "$BASE/uz/materials/$MID/download")" = 200 ] && ok "HEAD works" || bad "HEAD"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/ru/materials/$MID/download")" = 200 ] && ok "download works under any locale prefix" || bad "locale download"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/uz/materials/abc/download")" = 404 ] && ok "non-numeric id 404" || bad "bad id"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/uz/materials/999999/download")" = 404 ] && ok "unknown id 404" || bad "unknown id"
[ "$(curl -s -o /dev/null -w '%{http_code}' --path-as-is "$BASE/storage/uploads/materials/$F")" = 404 ] && ok "storage is not directly reachable" || bad "direct storage access"

echo "Draft / archive / replace / delete"
T=$(tok "$BASE/admin/materials/$MID/edit")
code -X POST -F "_token=$T" -F "uz__title=Ma‘ruza matni" -F status=draft "$BASE/admin/materials/$MID" >/dev/null
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/uz/materials/$MID/download")" = 404 ] && ok "draft is not downloadable" || bad "draft downloadable"
curl -s "$BASE/uz/materials" | grep -q 'Ma‘ruza matni' && bad "draft listed" || ok "draft not listed"
[ "$(count)" = 1 ] && ok "edit without a file keeps the stored file" || bad "file lost on edit"
code -X POST -F "_token=$T" -F "uz__title=Ma‘ruza matni" -F status=published -F "file=@$W/yangi.pdf;filename=Yangi.pdf" "$BASE/admin/materials/$MID" >/dev/null
[ "$(count)" = 1 ] && [ "$(ls "$UP")" != "$F" ] && ok "replacing removes the old file and stores the new one" || bad "replace: $(count) files"
curl -s "$BASE/uz/materials/$MID/download" | cmp -s - "$W/yangi.pdf" && ok "download serves the new file" || bad "old content served"
[ "$(code -X POST -F "_token=$T" -F "uz__title=x" -F status=published -F "file=@$W/shell.php;filename=s.php" "$BASE/admin/materials/$MID")" = 422 ] && [ "$(count)" = 1 ] && ok "refused replacement keeps the current file" || bad "bad replace broke state"
code -X POST --data-urlencode "_token=$T" "$BASE/admin/materials/$MID/delete" >/dev/null
[ "$(count)" = 0 ] && ok "deleting removes the stored file" || bad "orphan after delete"

echo "Categories"
CL=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$JAR" -c "$JAR" -X POST --data-urlencode "_token=$T" --data-urlencode "uz__name=Ma‘ruzalar" --data-urlencode status=published "$BASE/admin/categories")
CID=$(echo "$CL" | grep -oE '[0-9]+/edit' | cut -d/ -f1)
[ -n "$CID" ] && ok "category created" || bad "category create"
loc "$W/dars.pdf" a.pdf -F category_id=$CID >/dev/null
curl -s "$BASE/uz/materials" | grep -q 'group-title">Ma‘ruzalar' && ok "public page groups by category" || bad "no grouping"
[ "$(code -X POST -F "_token=$T" -F uz__title=x -F status=draft -F category_id=99999 -F "file=@$W/dars.pdf;filename=a.pdf" "$BASE/admin/materials")" = 422 ] && ok "unknown category rejected" || bad "unknown category accepted"
code -X POST --data-urlencode "_token=$T" "$BASE/admin/categories/$CID/delete" >/dev/null
curl -s -b "$JAR" "$BASE/admin/categories" | grep -q 'ishlatilmoqda' && ok "category in use cannot be deleted" || bad "category deleted while used"

echo; echo "$pass passed, $fail failed"; [ "$fail" -eq 0 ]
