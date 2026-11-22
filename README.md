# Academic Portal

Multilingual (uz / ru / en) academic website for a university professor. PHP 8.1, MySQL, PDO, lightweight MVC, Apache shared hosting. No Composer dependencies and no frontend build step.

Current state: **Phase 1 (infrastructure) with the approved public design, Phase 2 (administrator login) and Phase 3 (content database schema) and Phase 4 (content management for topics, news, assignments, practical and laboratory lessons) Phase 5a (materials with file uploads) and Phase 5b (contact form, admin inbox, profile editing) and Phase 6 (self-hosted fonts, CSP, deployment tooling), full Uzbek / Russian / English interface, and a home page that shows the latest news and materials. The contact page is switched off (`FEATURE_CONTACT=false`) until the end of the project. Not built yet: e-mail notification of new messages, profile photo upload.

## Local setup

1. Copy `.env.example` to `.env`. For local work set `APP_ENV=local`, `APP_URL=http://127.0.0.1:8000`, and a dedicated local database and user.
2. Serve the public directory: `php -S 127.0.0.1:8000 -t public`.
3. Apply migrations explicitly after the database is configured: `php bin/migrate.php` (`--status` lists them; in production `--yes` is required after a backup).

4. Create the single administrator (no default account exists): `php bin/create-admin.php <username>` (hidden password prompt, min. 12 characters). For local HTTP set `SESSION_SECURE=false`; keep it `true` in production (HTTPS).
5. Open `/admin/login`.

The public pages render without a database. `GET /health` returns only `ok` and does not touch the database or expose configuration.

## Tests

- `php tests/run.php` unit tests (no dependencies).
- `bash tests/smoke.sh` HTTP smoke test; starts its own PHP server, needs no `.env` or database.
- `php tests/schema-test.php` checks constraints, cascades and restrictions on a **disposable** database after `php bin/migrate.php`.
- `bash tests/e2e-contact.sh` contact form spam traps and rate limit, inbox, profile editing (disposable database; takes about 25 s).
- `bash tests/e2e-materials.sh` uploads, downloads, replaces and deletes files, including hostile uploads (disposable database).
- `bash tests/e2e-content.sh` creates, edits, publishes, archives and deletes content through the admin and checks the public pages (disposable database, `DB_*` exported).
- `bash tests/e2e-admin.sh` full admin flow (login, throttling, password change, logout) against a **disposable** MySQL/MariaDB database; export `DB_*` first. It deletes rows in `admins` and `login_attempts`, so never run it on real data.

## Structure

```
public/            only web-accessible folder (index.php, .htaccess, assets/)
bootstrap/app.php  autoloader, env, config, error handling, App
app/Core/          App, Router, Session, Csrf, Auth, Request, Response, View, Translator, Database, ErrorHandler, Logger, Env, Config
app/Controllers/   thin controllers (Public/, Admin/ later)
app/Middleware/    LocaleMiddleware, AdminAuthMiddleware, CsrfMiddleware
app/Views/         layouts/, components/, public/, errors/
app/Lang/uz.php    Uzbek UI strings (ru.php and en.php do not exist yet)
config/            app, database, navigation (navigation.php drives routes and the menu)
routes/web.php     /health, / -> /uz/, locale-prefixed pages
database/migrations/  versioned .sql files applied by bin/migrate.php
storage/           logs, cache, uploads (never public)
```

## Behaviour worth knowing

- **Locales:** `/uz/`, `/ru/`, `/en/`, each with its own interface strings (`app/Lang/{uz,ru,en}.php`; `tests/run.php` checks that all three have identical keys and placeholders and that nothing is copied from Uzbek). A locale without a language file would show a short "translation unavailable" notice instead of reusing another language. Content (news, topics, ...) is per language: an item appears in a language only if it has a translation in it, and the admin forms show a section for every language. Pages carry `hreflang` alternates.
- **Contact feature flag:** `FEATURE_CONTACT` (default false). While off there is no contact page (404), no menu item, no call-to-action button, no inbox and no POST route. Set it to true as the last step (see `DEPLOYMENT.md` section 11). The e2e contact test sets it itself.
- **Single subject:** the site is about one subject (neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari). There is no list of disciplines ("Fanlar"), so the content tables have no `course_id`. See `DATABASE_SCHEMA.md` for the implemented schema and how it differs from the original proposal.
- **Contact call to action** ("Xabar yuborish") appears only on the home page and the contact page, and only when `FEATURE_CONTACT=true`.
- **Unknown data stays hidden:** no placeholder text for email, phone, biography, etc. is shown publicly.
- **Errors:** production responses never include messages, paths or traces; details go to `storage/logs/`. `APP_DEBUG` is ignored when `APP_ENV=production`.
- **Admin (`/admin`, Uzbek only):** `/admin/login`, `/admin/dashboard`, `/admin/password`, `POST /admin/logout`. Every admin route except login requires a session on the server; every POST requires a CSRF token. Sessions start only on admin routes (public pages set no cookie), use an HttpOnly, SameSite=Lax cookie (`__Host-` prefixed and Secure in production), live in `storage/sessions/`, expire after 2 h idle / 12 h total, get a new ID on login and password change, and are destroyed on logout. Failed logins are throttled (5 per client and 30 overall per 15 min, HTTP 429); only a hash of the client address is stored. Wrong user and wrong password give the same message and take the same time. Admin pages are `no-store` and `noindex`.
- **Content management:** `/admin/{topics,news,assignments,practical,laboratory}` (list, create, edit, delete). All five are driven by `config/content.php`, so adding a field or type is a config change plus a translation string. Each item has a status: draft (hidden), published, archived (hidden). News with a future date stays hidden until then. Dates are typed and shown in `APP_TIMEZONE` (default Asia/Tashkent) and stored in UTC. Text is plain: blank lines make paragraphs, HTML is shown as text, never executed. The admin form shows a section for every language that has UI strings (today only Uzbek); a topic that is still used by assignments or lessons cannot be deleted. If the database is unreachable the public pages show their empty state and the error goes to `storage/logs/`.
- **Materials and uploads:** `/admin/materials` and `/admin/categories`. Allowed types are listed in `config/uploads.php` (PDF, Word, Excel, PowerPoint, TXT, PNG, JPG); the extension must be allowed **and** the real file content (libmagic) must match it, so a script renamed `.pdf` is refused. Files are stored in `storage/uploads/materials/` (never public; `.htaccess` denies direct access as a second layer) under a random 32-hex name, mode 0640. The visitor downloads through `/uz/materials/{id}/download`, which only serves published items, always as an attachment with `nosniff` and a content type taken from the allowlist, never from the client. Replacing or deleting a material removes the old file; a failed save removes the new one. The usable size limit is the smaller of `UPLOAD_MAX_SIZE` and PHP's `upload_max_filesize` / `post_max_size` (the form shows the real figure); on Eskiz raise the PHP limits in cPanel (Select PHP Version, Options) if 10 MB files are needed.
- **Contact form and inbox:** `POST /uz/contact` needs the session CSRF token (the page is `no-store`). Traps: a hidden honeypot field (a bot sees a normal success and nothing is stored), a 2-second minimum fill time, and a limit of 3 messages per client per hour and 100 per day overall (HTTP 429). Input is validated (name, e-mail, message 10-5000 characters, no control characters) and stored as plain text; only a SHA-256 of the sender address is kept. Messages are read in `/admin/feedback` (status new / in progress / processed, delete); a badge in the admin menu counts new ones. No e-mail is sent: the inbox is the only channel.
- **Teacher profile:** `/admin/profile` edits name, position, degree, specialization, biography, education, experience, e-mail and phone. Until the first save, the confirmed data in `app/Lang/uz.php` is shown; after it, the database is the source and an empty field is hidden on the public pages. E-mail and phone appear on the Contact page only when set. The photo is still the static file `public/assets/images/professor.jpg`.
- **SQL portability:** the new tables use no `DEFAULT CURRENT_TIMESTAMP`, JSON or CHECK constraints; timestamps (UTC) are written by PHP, so they work on old MySQL and MariaDB alike. (`0001` still uses `DEFAULT CURRENT_TIMESTAMP`, which needs MySQL 5.6.5+; check the Eskiz version before deploying.)
- **Fonts** (Manrope, IBM Plex Mono; latin, latin-ext, cyrillic) are self-hosted in `public/assets/fonts/` under the SIL OFL; the site makes no third-party requests. A Content-Security-Policy locks scripts, styles, fonts and images to the site's own origin; HSTS is sent only when `APP_URL` is https.
- **Deployment tooling:** `bash bin/build-release.sh` builds `release/app.zip` + `release/public.zip`; `php bin/check.php` verifies PHP, `.env`, folders, database, migrations and the administrator (see `DEPLOYMENT.md`).

## Deployment notes

Do not put `.env`, uploads, or logs under the public document root. On Eskiz (no separate document root) keep the application in a sibling directory and change the single `$basePath` line in the domain's `index.php`, as described in `DEPLOYMENT.md`. Set `SESSION_SECURE=true` once HTTPS is active and enable the HTTPS redirect in `public/.htaccess`.
