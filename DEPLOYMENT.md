# Deployment Guide: Eskiz shared hosting (cPanel)

Target: `sanatsamatov.uz`, PHP 8.1, MySQL, no separate document root. The hosting account may hold other sites: never touch them, and give this application its own database and database user.

## 1. What you need before starting

| Item | Where to find it |
|---|---|
| cPanel login | Eskiz |
| Account folder (for example `/home/ACCOUNT`) | cPanel, right column "Home Directory" |
| The folder that serves `sanatsamatov.uz` (the document root) | cPanel, "Domains" |
| PHP 8.1 with `pdo_mysql`, `mbstring`, `fileinfo` | cPanel, "Select PHP Version" |
| An empty MySQL database and its own user | cPanel, "MySQL Databases" (section 3) |
| A free SSL certificate | cPanel, "SSL/TLS Status" (AutoSSL / Let's Encrypt) |

## 2. Layout

The application lives **outside** the public folder. Only the contents of `public/` are web accessible.

```
/home/ACCOUNT/
├── academic-portal/          <- application (private): app/ bootstrap/ config/ database/ routes/ storage/ bin/ .env
└── public_html/              <- the document root of sanatsamatov.uz (yours may be a subfolder)
    ├── index.php             <- from public.zip, one line edited (section 5)
    ├── .htaccess
    └── assets/
```

If the document root of the domain is a different folder, use that folder instead of `public_html`. Do not put `.env`, `storage/` or `app/` inside it.

## 3. Database (cPanel, "MySQL Databases")

1. Create a database, for example `ACCOUNT_portal`.
2. Create a user with a long random password (use the generator, store it in a password manager).
3. Add the user to the database with **all privileges on that database only**.
4. Note the three names. cPanel prefixes them with your account name.
5. Check the MySQL version (phpMyAdmin front page). MySQL 5.6.5+ or any MariaDB works.

## 4. Build the upload files (on a computer or in the project session)

```
bash bin/build-release.sh
```

This writes `release/app.zip` (private part) and `release/public.zip` (public part). They contain no tests, no `.env`, no uploads and no logs.

## 5. First deployment

1. **File Manager**: create `/home/ACCOUNT/academic-portal/`, upload `app.zip` there and extract it. Delete the zip afterwards.
2. Upload `public.zip` into the domain's document root and extract it. Delete the zip afterwards.
3. Edit `index.php` in the document root: change the single line

   ```php
   $basePath = dirname(__DIR__);
   ```
   to the absolute path of the private folder, for example
   ```php
   $basePath = '/home/ACCOUNT/academic-portal';
   ```
4. Create `/home/ACCOUNT/academic-portal/.env` from `.env.example` (File Manager, "Edit"):

   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://sanatsamatov.uz
   APP_DEFAULT_LOCALE=uz
   APP_TIMEZONE=Asia/Tashkent

   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=ACCOUNT_portal
   DB_USERNAME=ACCOUNT_portal_user
   DB_PASSWORD=the-long-random-password

   SESSION_SECURE=true
   UPLOAD_MAX_SIZE=10485760
   ```
   Set the file permission to `600` (File Manager, "Permissions"). Never put real values in `.env.example` and never commit `.env`.
5. Permissions: folders `750` (or `755` if the host requires it), files `640` (or `644`). `storage/` and everything below it must be writable by PHP.
6. **PHP** (cPanel, "Select PHP Version"): choose 8.1, enable `pdo_mysql`, `mbstring`, `fileinfo`. Under "Options" set `upload_max_filesize` and `post_max_size` to at least `12M` if 10 MB uploads are wanted (the app uses the smaller of its own limit and PHP's).
7. **SSL**: in "SSL/TLS Status" run AutoSSL for `sanatsamatov.uz` (and `www` if used). Wait until `https://sanatsamatov.uz` opens without a warning.
8. **Force HTTPS**: in the document root's `.htaccess` remove the `#` in front of the three lines marked "Enable after the SSL certificate is active". Reload the site over `http://`: it must redirect to `https://`.

### Database tables and the administrator: with a terminal

If cPanel has "Terminal" (or you have SSH):

```
cd ~/academic-portal
php bin/migrate.php --yes
php bin/create-admin.php YOUR_LOGIN        # asks for the password (min 12 characters), nothing is shown
php bin/check.php
```

### Database tables and the administrator: without a terminal (cPanel "Cron Jobs")

Cron can run PHP commands once. In "Cron Jobs" add a job set to run every minute (use the "Once Per Minute" preset) with this command, **one job at a time**, and delete each job after the e-mailed output (or `~/cron.log`) shows it worked:

1. Tables:
   ```
   cd /home/ACCOUNT/academic-portal && php bin/migrate.php --yes > ~/cron.log 2>&1
   ```
2. Administrator (temporary password, you change it in the panel right away):
   ```
   cd /home/ACCOUNT/academic-portal && ADMIN_PASSWORD='Temporary-Long-Pass-123' php bin/create-admin.php YOUR_LOGIN >> ~/cron.log 2>&1
   ```
3. Self-check:
   ```
   cd /home/ACCOUNT/academic-portal && php bin/check.php >> ~/cron.log 2>&1
   ```
4. Delete all three jobs and `~/cron.log` (File Manager). The temporary password appears in the cron settings, so change it first: sign in at `https://sanatsamatov.uz/admin`, open "Parolni o‘zgartirish".

If the cron `php` is not 8.1, use the full path shown in "Select PHP Version", for example `/opt/cpanel/ea-php81/root/usr/bin/php`.

## 6. Verification checklist

Run `php bin/check.php` (terminal or cron). It must end with `Ready`. Then in a browser:

- [ ] `https://sanatsamatov.uz/` opens, the padlock is valid, `http://` redirects to `https://`.
- [ ] `https://sanatsamatov.uz/health` shows exactly `ok`.
- [ ] `/.env`, `/app/Core/App.php`, `/config/app.php`, `/storage/` and `/database/` all give 404 or 403, never content.
- [ ] A made-up address such as `/uz/nope` shows the site's own "Sahifa topilmadi" page, with no technical text.
- [ ] `/admin` redirects to `/admin/login`; wrong passwords show one generic message; the right one opens the panel.
- [ ] Add a test news item, publish it, see it on `/uz/news`, then delete it.
- [ ] Upload a small PDF as a material, download it from `/uz/materials`, delete it.
- [ ] Send a message from `/uz/contact` (wait a few seconds after the page opens), read it in the admin inbox, delete it.
- [ ] Open the site on a phone.
- [ ] Fill in the profile (`/admin/profile`) including e-mail and phone if they should be public.

## 7. Updating later

1. Back up the database first (cPanel "Backup" or phpMyAdmin "Export") and the `storage/uploads/` folder.
2. Build new archives (section 4). Upload `app.zip` over the private folder and extract with overwrite. **Do not overwrite `.env` or `storage/uploads/`**; the archive contains neither.
3. Upload `public.zip` over the document root. Keep your edited `index.php` (extract everything except `index.php`, or edit the line again) and your `.htaccess` HTTPS lines.
4. Apply new tables: `php bin/migrate.php --yes` (or the cron way) and run `php bin/check.php`.

## 8. Backups

- Database: weekly export plus one before every update.
- `storage/uploads/materials/`: copy it whenever materials are added, since the database only holds their names.
- Keep at least one copy off the hosting account.

## 9. Troubleshooting

| Symptom | Likely cause |
|---|---|
| Blank page or the generic "Xatolik yuz berdi" page | Read the newest file in `storage/logs/` (File Manager). Most often a wrong `$basePath` line or wrong `.env` values |
| Every page except `/` is 404 | `.htaccess` was not uploaded (hidden file): enable "Show Hidden Files" in File Manager |
| Cannot sign in, the page just reloads | `SESSION_SECURE=true` on a site that is still opened over `http://`; finish step 7 first |
| "Fayl juda katta" for small files | PHP `upload_max_filesize` / `post_max_size` too low (step 6) |
| Uploads fail to save | `storage/uploads/` not writable (step 5) |
| Fonts look different | Check that `assets/fonts/` was uploaded with `public.zip` |

## 10. Rules this deployment must keep

- Never edit or reuse anything belonging to another site on the account.
- `.env`, `storage/`, uploads and logs stay outside the public folder.
- `APP_DEBUG=false` in production; the app ignores `true` there, but keep it off.
- Do not leave temporary cron jobs, test accounts or test content behind.
