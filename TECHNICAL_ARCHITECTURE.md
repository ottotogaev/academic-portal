Technical Architecture
1. Overview
Academic Portal uses a lightweight PHP MVC architecture suitable for PHP 8.1 shared hosting.

The application must not require a continuously running Node.js process or external infrastructure services.
2. Proposed Directory Structure
academic-portal/
├── app/
│   ├── Controllers/
│   │   ├── Public/
│   │   └── Admin/
│   ├── Middleware/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Validators/
│   └── Views/
│       ├── layouts/
│       ├── components/
│       ├── public/
│       └── admin/
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── index.php
│   └── .htaccess
├── routes/
│   ├── web.php
│   └── admin.php
├── storage/
│   ├── logs/
│   ├── uploads/
│   └── cache/
├── tests/
├── vendor/
├── .env
├── .env.example
├── .gitignore
├── AGENTS.md
├── PROJECT_SPEC.md
├── TECHNICAL_ARCHITECTURE.md
├── DATABASE_SCHEMA.md
├── SECURITY.md
├── DEPLOYMENT.md
└── CODEX_START_PROMPT.md
The exact structure can be adjusted after repository inspection.
3. Request Lifecycle
1. Request reaches Apache.
2. Apache redirects application routes to public/index.php.
3. Bootstrap loads configuration and dependencies.
4. Router resolves the route.
5. Middleware validates locale, authentication, and authorization.
6. Controller handles the request.
7. Service executes business logic.
8. Repository performs database operations.
9. View renders the response.
10. Response is returned to the client.
4. Front Controller
public/index.php must be the only public PHP entry point.

Application files, configuration, logs, database migrations, and uploaded documents must not be directly accessible.
5. Routing
Use locale-prefixed public routes:
/uz/
/ru/
/en/

/uz/about
/uz/courses
/uz/courses/{slug}
/uz/news
/uz/assignments
/uz/practical-lessons
/uz/laboratory-lessons
/uz/materials
/uz/contact
Admin routes:
/admin/login
/admin/logout
/admin/dashboard
/admin/courses
/admin/news
/admin/assignments
/admin/materials
/admin/comments
/admin/feedback
/admin/settings
Admin interface language can be Uzbek initially. Its translation support may be added if required.
6. Database Access
Use one PDO connection managed through application bootstrap.

Repositories own SQL queries.

Services own business rules and transaction boundaries.

Controllers must not contain complex SQL.

Use parameter binding for all user-provided values.
7. Multilingual Architecture
Use base entity tables and translation tables.

Example:
courses
  id
  slug
  status
  created_at
  updated_at

course_translations
  id
  course_id
  locale
  title
  description
Enforce:
UNIQUE(course_id, locale)
Use the same strategy for other translatable entities.

Locale resolution:
1. Validate requested locale.
2. Use requested translation when available.
3. Apply documented fallback to Uzbek where appropriate.
4. Never fabricate translated content.
8. Main Relationships
- Course has many topics.
- Course has many assignments.
- Course has many practical lessons.
- Course has many laboratory lessons.
- Course has many materials.
- Topic belongs to a course.
- Topic may have related materials or lessons.
- News may optionally belong to a course.
- Comments belong to an explicitly supported content type.
- Feedback is private and independent of public content.
The final relationship design must be validated against DATABASE_SCHEMA.md.
9. Authentication
Version 1 supports one administrator.

Use PHP sessions.

Authentication must include:
- Password hashing.
- Login throttling.
- Session ID regeneration after login.
- Logout session invalidation.
- Protected admin routes.
- Authorization checks on every admin action.
No public account registration.
10. File Management
Upload workflow:
1. Validate request and CSRF token.
2. Validate file size.
3. Validate extension.
4. Inspect MIME type using Fileinfo.
5. Generate a random server-side filename.
6. Store outside the public directory.
7. Save metadata in the database.
8. Serve through a controlled download route.
Never use the supplied filename as a filesystem path.
11. Search
Implement SQL-based search with:
- Parameterized queries.
- Pagination.
- Appropriate indexes.
- Filters by discipline and content type.
- Locale-aware content lookup.
Avoid fetching all content into memory.

Full-text search may be considered after checking the MySQL version and actual requirements.
12. Comments and Feedback
Comments:
- Pending by default.
- Public only after approval.
- Admin moderation required.
- Spam protection required.
Feedback:
- Private.
- Visible only in the admin panel.
- Statuses: new, in progress, processed.
13. Performance
- Use database indexes.
- Paginate result lists.
- Avoid N+1 database queries.
- Optimize uploaded images.
- Avoid unnecessary external assets.
- Use caching only where it is useful and compatible with shared hosting.
14. Error Handling
- Centralized exception handling.
- Production-safe error responses.
- Detailed logs outside public root.
- No stack traces or credentials in production responses.
15. Architecture Decision Rule
Do not implement speculative infrastructure.

Prefer the simplest design that meets the requirements and works reliably on PHP 8.1 shared hosting.
