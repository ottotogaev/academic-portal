AGENTS.md — Codex Development Rules
1. Before Starting
Before changing code, read:
- PROJECT_SPEC.md
- TECHNICAL_ARCHITECTURE.md
- DATABASE_SCHEMA.md
- SECURITY.md
- DEPLOYMENT.md
Inspect the existing repository before making assumptions.

If a required document is missing, report it. Do not invent requirements.
2. General Engineering Rules
- Target PHP 8.1.
- Use strict and readable PHP.
- Follow PSR-12 where practical.
- Use PDO for database access.
- Use prepared SQL statements.
- Keep controllers thin.
- Keep business logic in services.
- Keep database access in repositories.
- Avoid unnecessary abstractions.
- Avoid unnecessary dependencies.
- Do not introduce frameworks without explicit approval.
- Do not refactor unrelated code.
3. Architecture
Use a lightweight MVC architecture.

Expected layers:
- Routing.
- Middleware.
- Controllers.
- Services.
- Repositories.
- Models or domain objects.
- Views.
- Configuration.
Use a single public entry point: public/index.php.

Do not expose application internals to the web.
4. PHP Compatibility
All code must work on PHP 8.1.

Do not use syntax or APIs introduced after PHP 8.1.

Check compatibility of third-party dependencies before installation.
5. Database
- Use MySQL.
- Use PDO.
- Use prepared statements.
- Use foreign keys where appropriate.
- Use transactions for multi-step writes.
- Add indexes for frequent filtering and lookup.
- Use migrations.
- Never assume production database state.
- Never modify the existing website database.
6. Multilingual Content
Supported locales:
- uz
- ru
- en
Default locale: uz.

All public content must support translations.

Use translation tables for translatable entities.

Do not duplicate entire entities per locale.

Never invent translations.
7. Security
Follow SECURITY.md.

At minimum:
- Escape output.
- Validate all inputs.
- Protect state-changing requests with CSRF.
- Hash passwords using password_hash.
- Verify passwords using password_verify.
- Protect admin routes.
- Use secure session settings.
- Validate file uploads.
- Rate-limit public forms.
- Do not expose stack traces in production.
8. Frontend
- Follow the Premium Academic design direction.
- Build responsive layouts.
- Use semantic HTML.
- Ensure keyboard accessibility.
- Avoid generic Bootstrap templates.
- Keep JavaScript minimal.
- Do not introduce unnecessary frontend frameworks.
9. Content Integrity
Never fabricate information about the professor.

Never invent academic publications, degrees, experience, courses, laboratory procedures, or contact details.

Unknown data must remain empty or editable.
10. File Handling
- Store uploads outside the public directory.
- Validate file extensions and MIME types.
- Generate safe server-side filenames.
- Limit file size.
- Do not trust the original filename.
- Serve files through a controlled download endpoint.
11. Testing
For each feature:
- Test normal operation.
- Test invalid input.
- Test unauthorized access.
- Test missing records.
- Test relevant database constraints.
- Test multilingual behavior.
Do not claim tests passed unless they were actually executed.
12. Workflow
For every task:
1. Read relevant project documentation.
2. Inspect existing code.
3. Identify the smallest necessary change.
4. Explain the planned implementation briefly.
5. Implement the change.
6. Run relevant checks and tests.
7. Report changed files.
8. Report executed tests.
9. Report unresolved issues.
Do not implement future phases without authorization.
13. Deployment Safety
Never:
- Modify another website's files.
- Reuse another website's database.
- Overwrite existing environment files.
- Run destructive SQL without explicit approval.
- Delete unknown files.
- Expose secrets in logs or source control.
Academic Portal must remain isolated from the existing website.
