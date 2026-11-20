# Academic Portal

Multilingual (uz / ru / en) academic website for a university professor. PHP 8.1, MySQL, PDO, lightweight MVC, Apache shared hosting. No Composer dependencies and no frontend build step.

Current state: **Phase 1 (infrastructure) with the approved public design applied.** Content pages show empty states until the content phase; there is no admin yet.

## Local setup

1. Copy `.env.example` to `.env`. For local work set `APP_ENV=local`, `APP_URL=http://127.0.0.1:8000`, and a dedicated local database and user.
2. Serve the public directory: `php -S 127.0.0.1:8000 -t public`.
3. Apply migrations explicitly after the database is configured: `php bin/migrate.php` (`--status` lists them; in production `--yes` is required after a backup).

The pages render without a database. `GET /health` returns only `ok` and does not touch the database or expose configuration.

## Tests

- `php tests/run.php` unit tests (no dependencies).
- `bash tests/smoke.sh` HTTP smoke test; starts its own PHP server, needs no `.env` or database.

## Structure

```
public/            only web-accessible folder (index.php, .htaccess, assets/)
bootstrap/app.php  autoloader, env, config, error handling, App
app/Core/          App, Router, Request, Response, View, Translator, Database, ErrorHandler, Logger, Env, Config
app/Controllers/   thin controllers (Public/, Admin/ later)
app/Middleware/    LocaleMiddleware validates the {locale} URL segment
app/Views/         layouts/, components/, public/, errors/
app/Lang/uz.php    Uzbek UI strings (ru.php and en.php do not exist yet)
config/            app, database, navigation (navigation.php drives routes and the menu)
routes/web.php     /health, / -> /uz/, locale-prefixed pages
database/migrations/  versioned .sql files applied by bin/migrate.php
storage/           logs, cache, uploads (never public)
```

## Behaviour worth knowing

- **Locales:** `/uz/`, `/ru/`, `/en/`. Only Uzbek has strings. `/ru/` and `/en/` show a short "translation unavailable" notice instead of reusing Uzbek content.
- **Single subject:** the site is about one subject (neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari). There is no list of disciplines ("Fanlar"), so the content tables will not need a `course_id`. This deliberately differs from the proposal in `DATABASE_SCHEMA.md`; update that document when the content schema is designed (Phase 3).
- **Contact call to action** ("Xabar yuborish") appears only on the home page and the contact page. The contact form is rendered disabled until feedback handling exists (Phase 5).
- **Unknown data stays hidden:** no placeholder text for email, phone, biography, etc. is shown publicly.
- **Errors:** production responses never include messages, paths or traces; details go to `storage/logs/`. `APP_DEBUG` is ignored when `APP_ENV=production`.
- **Fonts** (Manrope, IBM Plex Mono) are loaded from Google Fonts for now; self-hosting is a Phase 6 option.

## Deployment notes

Do not put `.env`, uploads, or logs under the public document root. On Eskiz (no separate document root) keep the application in a sibling directory and change the single `$basePath` line in the domain's `index.php`, as described in `DEPLOYMENT.md`. Set `SESSION_SECURE=true` once HTTPS is active and enable the HTTPS redirect in `public/.htaccess`.
