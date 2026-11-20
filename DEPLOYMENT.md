Deployment Guide — Eskiz Shared Hosting
1. Target Environment
Hosting provider: Eskiz.

Control panel: cPanel.

Runtime: PHP 8.1.

Confirmed PHP extensions:
- pdo_mysql
- fileinfo
- mbstring
The exact hosting environment must be inspected before deployment.
2. Isolation Requirement
The hosting account already contains another working website.

Academic Portal must be isolated.

Required separate resources:
- Domain or subdomain.
- Document root.
- MySQL database.
- MySQL user.
- Application environment file.
- Upload storage.
- Logs.
Never modify the existing website.
3. Document Root
Recommended layout:
/home/ACCOUNT/
├── academic-portal/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
└── public_html/
    └── academic.example.uz/
        ├── index.php
        ├── .htaccess
        └── assets/
The exact directory structure depends on the hosting account.

The domain document root must point to the application's public/ directory whenever the hosting configuration allows it.

Do not expose the project root.

If the provider does not allow a separate document root, design a secure alternative before deployment. Do not place sensitive files in a publicly accessible directory.
4. Environment Variables
Example .env.example:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.uz
APP_DEFAULT_LOCALE=uz

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=academic_portal
DB_USERNAME=academic_portal_user
DB_PASSWORD=replace_with_secure_password

SESSION_SECURE=true
UPLOAD_MAX_SIZE=10485760
Use actual values only in the private .env file.

Never commit .env.
5. Database Setup
1. Create a new MySQL database.
2. Create a new database user.
3. Generate a strong password.
4. Grant the user privileges only on the new database.
5. Configure .env.
6. Run migrations against the new database.
7. Verify database connectivity.
Do not reuse the existing website's database or credentials.
6. PHP Configuration
Verify:
- PHP 8.1 is selected.
- PDO MySQL is enabled.
- Fileinfo is enabled.
- Mbstring is enabled.
- Upload size limits are appropriate.
- Maximum execution time is appropriate.
- Memory limit is sufficient.
Do not assume the hosting environment matches local development.
7. Apache Rewrite
If Apache rewrite is available, configure the public directory to route application requests through index.php.

Protect private directories from direct web access.

Test:
- Homepage.
- Locale-prefixed routes.
- Static assets.
- 404 handling.
- Admin login.
8. File Permissions
Use the minimum permissions needed.

Do not make all project directories writable.

Only required storage directories should be writable by the application.

Never use broad permissions such as 777 as a routine workaround.
9. Admin Initialization
Create the initial administrator securely.

Requirements:
- No public registration.
- No hardcoded default production password.
- Use a secure password hash.
- Remove or disable any temporary initialization mechanism after use.
10. HTTPS
Enable the hosting provider's SSL certificate.

Verify:
- HTTPS loads correctly.
- HTTP redirects to HTTPS.
- Secure session cookies work.
- Public assets use HTTPS.
11. Production Verification
Before launch:
- Verify database connection.
- Verify migrations.
- Verify admin authentication.
- Verify all public routes.
- Verify all locales.
- Verify uploads and downloads.
- Verify comment moderation.
- Verify feedback privacy.
- Verify CSRF protection.
- Verify access restrictions.
- Verify production error handling.
- Verify backups.
12. Rollback
Before deployment:
1. Back up the new application's database.
2. Back up the new application's files.
3. Record the deployed version.
4. Prepare a rollback procedure.
Rollback must not affect the existing website.
13. Unknown Hosting Details
Do not assume:
- SSH is enabled.
- Composer is available.
- MySQL version.
- Apache rewrite configuration.
- Custom document root support.
- Exact filesystem paths.
Inspect and verify each requirement before relying on it.
