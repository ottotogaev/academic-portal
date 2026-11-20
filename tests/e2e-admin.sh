#!/usr/bin/env bash
# End-to-end admin flow against a real MySQL/MariaDB test database (NEVER point this at production data).
# Required env: DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD. Runs migrations, creates a throw-away admin,
# then exercises login, throttling, password change and logout over HTTP: bash tests/e2e-admin.sh
set -u
cd "$(dirname "$0")/.."
: "${DB_DATABASE:?set DB_* variables for a disposable test database}"
export APP_ENV=local APP_DEBUG=false SESSION_SECURE=false
PORT="${PORT:-8766}"; BASE="http://127.0.0.1:${PORT}"; export APP_URL="$BASE"
JAR="$(mktemp)"; LOGDIR="$(mktemp -d)"
php bin/migrate.php >/dev/null || { echo "migration failed"; exit 1; }
php -r 'require "bootstrap/app.php";' >/dev/null
php -r '$a=require "bootstrap/app.php"; $p=$a->database()->pdo(); $p->exec("DELETE FROM admins"); $p->exec("DELETE FROM login_attempts");'
rm -rf storage/sessions/sess_* 
ADMIN_PASSWORD='first long password' php bin/create-admin.php prof >/dev/null || { echo "create-admin failed"; exit 1; }
php -S "127.0.0.1:${PORT}" -t public >"$LOGDIR/server.log" 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf "$LOGDIR" "$JAR"' EXIT
for _ in $(seq 1 30); do curl -s -o /dev/null "$BASE/health" && break; sleep 0.2; done
pass=0; fail=0
ok(){ pass=$((pass+1)); echo "  ok   $1"; }; bad(){ fail=$((fail+1)); echo "  FAIL $1"; }
tok(){ curl -s -b "$JAR" -c "$JAR" "$1" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
code(){ curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$@"; }

T=$(tok "$BASE/admin/login")
[ -n "$T" ] && ok "csrf token issued" || bad "no csrf token"
[ "$(code -X POST --data-urlencode username=prof --data-urlencode 'password=wrong password!' --data-urlencode "_token=$T" "$BASE/admin/login")" = 422 ] && ok "wrong password -> 422" || bad "wrong password status"
curl -s -b "$JAR" -c "$JAR" -X POST --data-urlencode username=nobody --data-urlencode 'password=whatever long' --data-urlencode "_token=$T" "$BASE/admin/login" | grep -q "noto‘g‘ri" && ok "generic error message" || bad "error message"
[ "$(code "$BASE/admin/dashboard")" = 302 ] && ok "still locked out" || bad "dashboard open without login"
OLD=$(grep -o 'ap_sid[[:space:]]*[a-z0-9]*' "$JAR" | awk '{print $2}')
[ "$(code -X POST --data-urlencode username=prof --data-urlencode 'password=first long password' --data-urlencode "_token=$T" "$BASE/admin/login")" = 302 ] && ok "correct password -> 302" || bad "login failed"
NEW=$(grep -o 'ap_sid[[:space:]]*[a-z0-9]*' "$JAR" | awk '{print $2}')
[ -n "$NEW" ] && [ "$OLD" != "$NEW" ] && ok "session id regenerated on login" || bad "session id not regenerated"
[ "$(code "$BASE/admin/dashboard")" = 200 ] && ok "dashboard reachable when logged in" || bad "dashboard blocked"
curl -s -b "$JAR" "$BASE/admin/dashboard" | grep -q 'Xush kelibsiz, prof' && ok "dashboard greets by name" || bad "dashboard content"
[ "$(code "$BASE/admin/login")" = 302 ] && ok "login page redirects when logged in" || bad "login page when logged in"
[ "$(code -X POST --data-urlencode current_password='first long password' --data-urlencode new_password='second long password' --data-urlencode new_password_confirm='second long password' "$BASE/admin/password")" = 400 ] && ok "password change without csrf rejected" || bad "password csrf"
PT=$(tok "$BASE/admin/password")
[ "$(code -X POST --data-urlencode current_password='bad current pass' --data-urlencode new_password='second long password' --data-urlencode new_password_confirm='second long password' --data-urlencode "_token=$PT" "$BASE/admin/password")" = 422 ] && ok "wrong current password rejected" || bad "wrong current"
[ "$(code -X POST --data-urlencode current_password='first long password' --data-urlencode new_password='short' --data-urlencode new_password_confirm='short' --data-urlencode "_token=$PT" "$BASE/admin/password")" = 422 ] && ok "short new password rejected" || bad "short new"
[ "$(code -X POST --data-urlencode current_password='first long password' --data-urlencode new_password='second long password' --data-urlencode new_password_confirm='second long password' --data-urlencode "_token=$PT" "$BASE/admin/password")" = 200 ] && ok "password changed" || bad "password change"
LT=$(tok "$BASE/admin/dashboard")
[ "$(code -X POST --data-urlencode "_token=$LT" "$BASE/admin/logout")" = 302 ] && ok "logout -> 302" || bad "logout"
[ "$(code "$BASE/admin/dashboard")" = 302 ] && ok "dashboard locked after logout" || bad "dashboard open after logout"
# the old (pre-logout) session cookie must be dead on the server
J2="$(mktemp)"; printf '127.0.0.1\tFALSE\t/\tFALSE\t0\tap_sid\t%s\n' "$NEW" > "$J2"
[ "$(curl -s -o /dev/null -w '%{http_code}' -b "$J2" "$BASE/admin/dashboard")" = 302 ] && ok "old session id is invalid after logout" || bad "session replay after logout"
rm -f "$J2"
J3="$(mktemp)"; T3=$(curl -s -b "$J3" -c "$J3" "$BASE/admin/login" | grep -o 'name="_token" value="[a-f0-9]*"' | sed 's/.*value="//;s/"//')
[ "$(curl -s -o /dev/null -w '%{http_code}' -b "$J3" -c "$J3" -X POST --data-urlencode username=prof --data-urlencode 'password=first long password' --data-urlencode "_token=$T3" "$BASE/admin/login")" = 422 ] && ok "old password rejected after change" || bad "old password still works"
for i in 1 2 3 4; do curl -s -o /dev/null -b "$J3" -c "$J3" -X POST --data-urlencode username=prof --data-urlencode "password=guess number $i xx" --data-urlencode "_token=$T3" "$BASE/admin/login"; done
R=$(curl -s -o /dev/null -w '%{http_code}' -b "$J3" -c "$J3" -X POST --data-urlencode username=prof --data-urlencode 'password=second long password' --data-urlencode "_token=$T3" "$BASE/admin/login")
[ "$R" = 429 ] && ok "throttled after repeated failures, even with the right password (429)" || bad "no throttling (got $R)"
curl -s -D - -o /dev/null -b "$J3" -c "$J3" -X POST --data-urlencode username=prof --data-urlencode 'password=x' --data-urlencode "_token=$T3" "$BASE/admin/login" | grep -qi '^Retry-After:' && ok "Retry-After sent" || bad "no Retry-After"
rm -f "$J3"
php -r '$a=require "bootstrap/app.php"; $p=$a->database()->pdo(); $p->exec("DELETE FROM login_attempts");'
grep -rqiE 'first long password|second long password|guess number' storage/logs 2>/dev/null && bad "password found in logs" || ok "no passwords in logs"
echo; echo "$pass passed, $fail failed"; [ "$fail" -eq 0 ]
