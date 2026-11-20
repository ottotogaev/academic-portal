Security Requirements
1. General
Security is mandatory for all public and administrative functionality.

Follow secure defaults.

Do not expose internal application files.
2. Environment Configuration
- Store .env outside the public web root.
- Never commit real credentials.
- Provide .env.example with placeholders.
- Do not print secrets in logs.
- Do not expose configuration through public routes.
3. SQL Injection
- Use PDO prepared statements.
- Bind user-provided values.
- Never concatenate raw user input into SQL.
- Validate dynamic identifiers against explicit allowlists.
4. Cross-Site Scripting
- Escape output by default.
- Use context-appropriate escaping.
- Do not render user-provided HTML without sanitization.
- Validate rich text separately if rich text editing is introduced.
5. CSRF
All state-changing requests must validate CSRF tokens.

This includes:
- Admin forms.
- Comment submission.
- Feedback submission.
- Upload requests.
- Delete and update operations.
6. Authentication
- Use password_hash.
- Use password_verify.
- Never store plain-text passwords.
- Regenerate session IDs after successful authentication.
- Invalidate sessions on logout.
- Implement login throttling.
- Avoid revealing whether a username exists.
7. Authorization
Every admin route must require authentication.

Do not rely only on hiding admin links in the frontend.

Verify authorization on the server for every protected operation.
8. Session Security
Where supported:
- HttpOnly.
- Secure when HTTPS is enabled.
- Appropriate SameSite policy.
- Strict session mode.
- Session ID regeneration after login.
Set session lifetime according to the security requirements.
9. File Upload Security
- Allowlist extensions.
- Validate MIME using Fileinfo.
- Enforce maximum file size.
- Generate random server-side filenames.
- Store files outside public web root.
- Reject executable files.
- Do not trust browser-provided MIME types.
- Do not use original filenames as storage paths.
- Serve downloads through a controlled endpoint.
10. Comments and Feedback
- Validate all fields.
- Apply rate limiting.
- Add anti-spam controls.
- Escape user-generated content.
- Moderate comments before publication.
- Keep feedback private.
11. HTTP and HTTPS
Production must use HTTPS.

Redirect HTTP to HTTPS when hosting configuration supports it.

Set appropriate security headers where compatible.
12. Error Handling
Production must not expose:
- Stack traces.
- SQL errors.
- File paths.
- Credentials.
- Environment values.
- Internal application details.
Log errors privately.
13. Database Permissions
Use a dedicated database user for Academic Portal.

Grant only required privileges on its own database.

Never reuse credentials from the existing website.
14. Backup
Define:
- Database backup schedule.
- Uploaded-file backup schedule.
- Backup retention.
- Restore procedure.
A backup is not considered reliable until restoration has been tested.
15. Deployment Safety
- No public installer.
- No default production password.
- No debug mode in production.
- No exposed .env.
- No exposed storage directory.
- No destructive deployment scripts without confirmation.
- No changes to the existing website.
