#!/usr/bin/env bash
# End-to-end contact form, inbox and profile editing on a DISPOSABLE database (takes ~25 s: the form has a 2 s time trap).
# Needs DB_* env vars and migrations applied: bash tests/e2e-contact.sh
set -u
cd "$(dirname "$0")/.."
: "${DB_DATABASE:?set DB_* variables for a disposable test database}"
export APP_ENV=local APP_DEBUG=false SESSION_SECURE=false
PORT="${PORT:-8769}"; BASE="http://127.0.0.1:${PORT}"; export APP_URL="$BASE"
ADM="$(mktemp)"; VIS="$(mktemp)"; LOGDIR="$(mktemp -d)"
php -r '$basePath=getcwd(); $a=require "bootstrap/app.php"; $p=$a->database()->pdo();
foreach(["feedback_messages","teacher_profile_translations","teacher_profiles","login_attempts","admins"] as $t){$p->exec("DELETE FROM $t");}'
ADMIN_PASSWORD='contact test password' php bin/create-admin.php prof >/dev/null || exit 1
php -S "127.0.0.1:${PORT}" -t public >"$LOGDIR/server.log" 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf "$LOGDIR" "$ADM" "$VIS"' EXIT
for _ in $(seq 1 30); do curl -s -o /dev/null "$BASE/health" && break; sleep 0.2; done
pass=0; fail=0
ok(){ pass=$((pass+1)); echo "  ok   $1"; }; bad(){ fail=$((fail+1)); echo "  FAIL $1"; }
acode(){ curl -s -o /dev/null -w '%{http_code}' -b "$ADM" -c "$ADM" "$@"; }
aget(){ curl -s -b "$ADM" -c "$ADM" "$@"; }
atok(){ aget "$1" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
vtok(){ curl -s -b "$VIS" -c "$VIS" "$BASE/uz/contact" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
vcode(){ curl -s -o /dev/null -w '%{http_code}' -b "$VIS" -c "$VIS" "$@"; }
dbcount(){ php -r '$basePath=getcwd(); $a=require "bootstrap/app.php"; echo $a->database()->pdo()->query("SELECT COUNT(*) FROM feedback_messages")->fetchColumn();'; }
# send FIELD=VALUE... : load form, wait out the time trap, post
send(){ local T; T=$(vtok); sleep 3; vcode -X POST --data-urlencode "_token=$T" "$@" "$BASE/uz/contact"; }

T=$(curl -s -b "$ADM" -c "$ADM" "$BASE/admin/login" | grep -o 'name="_token" value="[a-f0-9]*"' | sed 's/.*value="//;s/"//')
acode -X POST --data-urlencode username=prof --data-urlencode 'password=contact test password' --data-urlencode "_token=$T" "$BASE/admin/login" >/dev/null

echo "Spam traps and validation"
T=$(vtok)
[ "$(vcode -X POST --data-urlencode name=Ali --data-urlencode email=a@b.uz --data-urlencode 'message=Assalomu alaykum, savol bor' "$BASE/uz/contact")" = 400 ] && ok "no CSRF token -> 400" || bad "csrf"
[ "$(vcode -X POST --data-urlencode "_token=$T" --data-urlencode name=Ali --data-urlencode email=a@b.uz --data-urlencode 'message=Assalomu alaykum, savol bor' "$BASE/uz/contact")" = 429 ] && ok "instant submit (time trap) refused" || bad "time trap"
[ "$(dbcount)" = 0 ] && ok "nothing stored by the time trap" || bad "stored by trap"
T=$(vtok); sleep 3
[ "$(vcode -X POST --data-urlencode "_token=$T" --data-urlencode name=Bot --data-urlencode email=bot@b.uz --data-urlencode 'message=Buy cheap pills now please' --data-urlencode website=http://spam.example "$BASE/uz/contact")" = 303 ] && ok "honeypot looks successful to the bot" || bad "honeypot response"
[ "$(dbcount)" = 0 ] && ok "honeypot message is not stored" || bad "honeypot stored"
T=$(vtok); sleep 3
[ "$(vcode -X POST --data-urlencode "_token=$T" --data-urlencode name=Ali --data-urlencode email=bad --data-urlencode 'message=qisqa' "$BASE/uz/contact")" = 422 ] && ok "invalid input -> 422" || bad "validation status"
curl -s -b "$VIS" -c "$VIS" -X POST --data-urlencode "_token=$T" --data-urlencode name=Ali --data-urlencode email=bad --data-urlencode 'message=qisqa' "$BASE/uz/contact" | grep -q 'value="Ali"' && ok "typed values kept on error" || bad "values lost"
[ "$(vcode -X POST --data-urlencode "_token=$T" --data-urlencode name=Ali "$BASE/ru/contact")" = 404 ] && ok "POST to an unavailable locale is 404" || bad "ru post"

echo "Sending"
CODE=$(send --data-urlencode "name=Ali <b>Valiyev</b>" --data-urlencode email=ali@example.uz --data-urlencode 'subject=<script>alert(1)</script>' --data-urlencode $'message=Birinchi qator <img src=x onerror=alert(1)>\n\nIkkinchi abzats')
[ "$CODE" = 303 ] && ok "valid message accepted (303 redirect)" || bad "valid message ($CODE)"
[ "$(dbcount)" = 1 ] && ok "stored once" || bad "stored count $(dbcount)"
curl -s -b "$VIS" -c "$VIS" "$BASE/uz/contact" | grep -q 'Xabaringiz yuborildi' && ok "thank-you message shown after redirect" || bad "no thanks"
curl -s -b "$VIS" -c "$VIS" "$BASE/uz/contact" | grep -q 'Xabaringiz yuborildi' && bad "thanks repeats on reload" || ok "thanks shown only once"

echo "Inbox"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/admin/feedback")" = 302 ] && ok "inbox requires login" || bad "inbox open"
aget "$BASE/admin/feedback" | grep -q 'ali@example.uz' && ok "message listed" || bad "not listed"
aget "$BASE/admin/feedback" | grep -q '<script>alert(1)</script>' && bad "script in list unescaped" || ok "subject escaped in the list"
MID=$(aget "$BASE/admin/feedback" | grep -oE 'feedback/[0-9]+' | head -1 | cut -d/ -f2)
aget "$BASE/admin/feedback/$MID" > /tmp/ap_msg.html
grep -q '<img src=x' /tmp/ap_msg.html && bad "HTML in message is live" || ok "HTML in message is escaped"
grep -q '&lt;img src=x onerror=alert(1)&gt;' /tmp/ap_msg.html && ok "message shown as text" || bad "message text missing"
grep -q '<p>Ikkinchi abzats</p>' /tmp/ap_msg.html && ok "paragraphs rendered" || bad "no paragraphs"
rm -f /tmp/ap_msg.html
aget "$BASE/admin/dashboard" | grep -q 'Yangi xabarlar' && ok "dashboard shows new messages" || bad "dashboard tile"
aget "$BASE/admin/dashboard" | grep -q 'class="badge">1' && ok "nav badge counts the new message" || bad "badge"
AT=$(atok "$BASE/admin/feedback/$MID")
[ "$(acode -X POST --data-urlencode "_token=$AT" --data-urlencode status=processed "$BASE/admin/feedback/$MID/status")" = 302 ] && ok "status changed" || bad "status change"
[ "$(acode -X POST --data-urlencode "_token=$AT" --data-urlencode status=hacked "$BASE/admin/feedback/$MID/status")" = 400 ] && ok "unknown status refused" || bad "bad status accepted"
aget "$BASE/admin/dashboard" | grep -q 'class="badge"' && bad "badge remains after processing" || ok "badge disappears once processed"
[ "$(acode -X POST --data-urlencode status=new "$BASE/admin/feedback/$MID/status")" = 400 ] && ok "status change without CSRF refused" || bad "status csrf"

echo "Rate limit (3 per client per hour)"
send --data-urlencode name=Ali --data-urlencode email=a@b.uz --data-urlencode 'message=Ikkinchi xabar uchun matn' >/dev/null
send --data-urlencode name=Ali --data-urlencode email=a@b.uz --data-urlencode 'message=Uchinchi xabar uchun matn' >/dev/null
[ "$(dbcount)" = 3 ] && ok "three messages accepted" || bad "count $(dbcount)"
[ "$(send --data-urlencode name=Ali --data-urlencode email=a@b.uz --data-urlencode 'message=To‘rtinchi xabar uchun matn')" = 429 ] && ok "fourth message refused (429)" || bad "no rate limit"
[ "$(dbcount)" = 3 ] && ok "refused message not stored" || bad "stored despite limit"

echo "Delete"
AT=$(atok "$BASE/admin/feedback/$MID")
[ "$(acode -X POST --data-urlencode "_token=$AT" "$BASE/admin/feedback/$MID/delete")" = 302 ] && [ "$(dbcount)" = 2 ] && ok "message deleted" || bad "delete"
[ "$(acode "$BASE/admin/feedback/$MID")" = 404 ] && ok "deleted message is 404" || bad "still reachable"
[ "$(acode "$BASE/admin/feedback/abc")" = 404 ] && ok "non-numeric id 404" || bad "bad id"

echo "Profile"
curl -s "$BASE/uz/contact" | grep -q 'mailto:' && bad "email shown before it is set" || ok "no email or phone shown while unknown"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/admin/profile")" = 302 ] && ok "profile requires login" || bad "profile open"
aget "$BASE/admin/profile" | grep -q 'value="Samatov San&#039;at Baxtiyorovich"' && ok "form is pre-filled with the confirmed data" || bad "no prefill"
PT=$(atok "$BASE/admin/profile")
pp(){ acode -X POST --data-urlencode "_token=$PT" "$@" "$BASE/admin/profile"; }
[ "$(pp --data-urlencode uz__full_name= )" = 422 ] && ok "empty name refused" || bad "empty name"
[ "$(pp --data-urlencode uz__full_name=Test --data-urlencode email=bad)" = 422 ] && ok "bad email refused" || bad "bad email"
[ "$(pp --data-urlencode "uz__full_name=Samatov San'at Baxtiyorovich" --data-urlencode 'uz__position=Professor' --data-urlencode 'uz__academic_degree=PhD' --data-urlencode 'uz__specialization=' --data-urlencode $'uz__biography=Tarjimai hol matni.\n\nIkkinchi abzats.' --data-urlencode email=prof@example.uz --data-urlencode 'phone=+998 90 123-45-67')" = 302 ] && ok "profile saved" || bad "profile save"
curl -s "$BASE/uz/about" > /tmp/ap_about.html
grep -q 'Tarjimai hol matni.' /tmp/ap_about.html && ok "biography appears on the About page" || bad "no biography"
grep -q 'Mutaxassislik' /tmp/ap_about.html && bad "emptied field still shown" || ok "emptied specialization is hidden"
grep -q '<dd>Professor</dd>' /tmp/ap_about.html && ok "edited position shown" || bad "position"
rm -f /tmp/ap_about.html
curl -s "$BASE/uz/" | grep -q 'class="hero__role">Professor' && ok "home page uses the edited profile" || bad "home not updated"
curl -s "$BASE/uz/contact" | grep -q 'href="mailto:prof%40example.uz"' && ok "email link on the Contact page" || bad "email link"
curl -s "$BASE/uz/contact" | grep -q 'href="tel:+998901234567"' && ok "phone link on the Contact page" || bad "phone link"

echo; echo "$pass passed, $fail failed"; [ "$fail" -eq 0 ]
