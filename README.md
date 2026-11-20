# Academic Portal

Multilingual (uz / ru / en) academic website for a university professor. PHP 8.1, MySQL, PDO, lightweight MVC, Apache shared hosting. No Composer dependencies and no frontend build step.

Current state: **Phase 1 (infrastructure) with the approved public design, Phase 2 (administrator login) and Phase 3 (content database schema).** Content pages show empty states until the content phase; the admin panel has login, logout, dashboard and password change only.

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

- **Locales:** `/uz/`, `/ru/`, `/en/`. Only Uzbek has strings. `/ru/` and `/en/` show a short "translation unavailable" notice instead of reusing Uzbek content.
- **Single subject:** the site is about one subject (neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari). There is no list of disciplines ("Fanlar"), so the content tables have no `course_id`. See `DATABASE_SCHEMA.md` for the implemented schema and how it differs from the original proposal.
- **Contact call to action** ("Xabar yuborish") appears only on the home page and the contact page. The contact form is rendered disabled until feedback handling exists (Phase 5).
- **Unknown data stays hidden:** no placeholder text for email, phone, biography, etc. is shown publicly.
- **Errors:** production responses never include messages, paths or traces; details go to `storage/logs/`. `APP_DEBUG` is ignored when `APP_ENV=production`.
- **Admin (`/admin`, Uzbek only):** `/admin/login`, `/admin/dashboard`, `/admin/password`, `POST /admin/logout`. Every admin route except login requires a session on the server; every POST requires a CSRF token. Sessions start only on admin routes (public pages set no cookie), use an HttpOnly, SameSite=Lax cookie (`__Host-` prefixed and Secure in production), live in `storage/sessions/`, expire after 2 h idle / 12 h total, get a new ID on login and password change, and are destroyed on logout. Failed logins are throttled (5 per client and 30 overall per 15 min, HTTP 429); only a hash of the client address is stored. Wrong user and wrong password give the same message and take the same time. Admin pages are `no-store` and `noindex`.
- **SQL portability:** the new tables use no `DEFAULT CURRENT_TIMESTAMP`, JSON or CHECK constraints; timestamps (UTC) are written by PHP, so they work on old MySQL and MariaDB alike. (`0001` still uses `DEFAULT CURRENT_TIMESTAMP`, which needs MySQL 5.6.5+; check the Eskiz version before deploying.)
- **Fonts** (Manrope, IBM Plex Mono) are loaded from Google Fonts for now; self-hosting is a Phase 6 option.

## Deployment notes

Do not put `.env`, uploads, or logs under the public document root. On Eskiz (no separate document root) keep the application in a sibling directory and change the single `$basePath` line in the domain's `index.php`, as described in `DEPLOYMENT.md`. Set `SESSION_SECURE=true` once HTTPS is active and enable the HTTPS redirect in `public/.htaccess`.
