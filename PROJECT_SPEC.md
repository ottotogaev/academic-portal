Academic Portal — Project Specification
1. Project Overview
Project name: Academic Portal

Academic Portal is a multilingual personal academic website for a university professor. It provides structured access to professional information, academic disciplines, educational materials, assignments, practical lessons, laboratory lessons, and academic news.

This is a personal academic platform, not a full Learning Management System (LMS).

The platform must be manageable by one administrator: the professor.
2. Teacher Information
The following information is confirmed:
- Full name: Samatov San'at Baxtiyorovich
- University: Iqtisodiyot va pedagogika universiteti
- Position: Neft va gaz ishi kafedrasi v.b. professori
- Academic degree: Kimyo fanlari falsafa doktori (PhD)
- Specialization: Neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari
Do not invent additional biographical or academic information.

Unknown information must remain empty or editable until verified.
3. Product Goals
- Present the professor's academic and professional profile.
- Publish multiple academic disciplines.
- Organize educational content by discipline and topic.
- Publish assignments and practical/laboratory lessons.
- Provide downloadable educational materials.
- Publish academic news and announcements.
- Support Uzbek, Russian, and English.
- Provide a secure administration panel.
- Offer search, filtering, comments, and feedback.
4. Target Audience
- University students.
- Academic colleagues.
- Researchers.
- Visitors interested in the professor's academic work.
5. Languages
Supported locales:
- uz — Uzbek (default).
- ru — Russian.
- en — English.
Public URLs must use locale prefixes:
- /uz/
- /ru/
- /en/
All public content must support translations.

Do not invent missing translations. Implement a documented fallback strategy or show an unavailable translation state.
6. Public Website Navigation
The public navigation should include:
1. Bosh sahifa
2. O‘qituvchi haqida
3. Fanlar
4. Fan mavzulari
5. Yangiliklar
6. Topshiriqlar
7. Amaliy mashg‘ulotlar
8. Laboratoriya mashg‘ulotlari
9. O‘quv materiallari
10. Aloqa
The interface must be responsive.
7. Core Modules
7.1 Home
- Academic identity and introduction.
- Featured disciplines.
- Latest news.
- Recently published materials.
- Quick navigation.
7.2 Teacher Profile
- Personal and professional information.
- Academic degree and position.
- Biography.
- Education and professional experience.
- Publications and research activities.
Only verified information may be published.
7.3 Disciplines
- Multiple disciplines.
- Multilingual titles and descriptions.
- Discipline-specific topics.
- Related assignments, lessons, and materials.
- Publication status.
7.4 Topics
- Topics belong to disciplines.
- Multilingual titles and descriptions.
- Optional ordering.
- Related educational materials.
7.5 News
- Title, summary, body, cover image.
- Publication date.
- Locale-specific content.
- Draft and published statuses.
- Optional discipline association.
7.6 Assignments
- Assignment title and description.
- Associated discipline.
- Optional topic association.
- Deadline.
- Attachments.
- Publication status.
7.7 Practical Lessons
- Lesson title and description.
- Associated discipline and optional topic.
- Instructions and attachments.
- Ordering and publication status.
7.8 Laboratory Lessons
- Laboratory lesson title and description.
- Associated discipline and optional topic.
- Required equipment and safety information, when supplied.
- Instructions and attachments.
- Publication status.
Never generate real laboratory procedures or safety instructions without verified source material.
7.9 Educational Materials
- PDFs and office documents.
- Images.
- Material categories.
- Discipline and topic associations.
- Download functionality.
- Multilingual metadata.
7.10 Contact and Feedback
- Contact information.
- Private feedback form.
- Spam protection.
- Admin inbox.
Feedback must not be publicly visible.
8. Comments
- Visitors can submit comments where enabled.
- Comments require moderation.
- Only approved comments are public.
- Administrator can approve, reject, or delete comments.
- Rate limiting and spam protection are required.
9. Administration Panel
Exactly one administrator in version 1.

Administrator capabilities:
- Secure login/logout.
- Dashboard.
- Manage teacher profile.
- Manage disciplines.
- Manage topics.
- Manage news.
- Manage assignments.
- Manage practical lessons.
- Manage laboratory lessons.
- Manage educational materials.
- Manage translations.
- Moderate comments.
- Review feedback.
- Manage site settings.
- Change password.
No public registration.
10. Search and Filtering
Search across:
- Disciplines.
- Topics.
- News.
- Assignments.
- Practical lessons.
- Laboratory lessons.
- Educational materials.
Filters should include relevant fields such as discipline, category, date, academic year, and content type.

Use database pagination. Do not load all records into application memory.
11. Design Direction
Design style: Premium Academic.

Visual requirements:
- Professional university-oriented identity.
- Refined typography.
- Clear information hierarchy.
- Consistent spacing.
- Restrained color palette.
- High-quality responsive layouts.
- Accessible contrast.
- Carefully designed cards and content pages.
- Custom components rather than a generic Bootstrap template.
Use Bootstrap 5 only as optional layout primitives. The final design must be custom.
12. Technical Stack
- PHP 8.1.
- MySQL.
- PDO.
- HTML5.
- CSS3.
- JavaScript.
- PHP Sessions.
- Apache / cPanel hosting.
No mandatory Node.js runtime, Docker, VPS, Redis, message queues, or microservices.

Avoid unnecessary dependencies.
13. Hosting Constraints
Target hosting: Eskiz shared hosting with cPanel.

Confirmed:
- PHP 8.1.
- pdo_mysql enabled.
- fileinfo enabled.
- mbstring enabled.
The following must be verified before implementation or deployment:
- MySQL version.
- Apache rewrite support.
- PHP upload limits.
- Composer availability.
- SSH availability.
- Filesystem permissions.
- Ability to configure a separate document root.
14. Isolation Requirement
An existing website is already running on the hosting account.

Academic Portal must be completely isolated.

Use:
- Separate domain or subdomain.
- Separate document root.
- Separate MySQL database.
- Separate MySQL user.
- Separate environment configuration.
- Separate storage directory.
Do not modify the existing website, database, configuration, or files.
15. File Management
Supported file types should be explicitly allowlisted.

Initial allowed types:
- PDF.
- DOC / DOCX.
- PPT / PPTX.
- JPG / JPEG.
- PNG.
- WEBP.
Store uploaded files outside the public web root.

Downloads must pass through a controlled endpoint with authorization and validation.
16. SEO
Implement:
- Locale-specific page titles.
- Meta descriptions.
- Canonical URLs.
- hreflang.
- Sitemap.
- Robots.txt.
- Open Graph metadata.
- Appropriate structured data.
17. Development Phases
Phase 1 — Infrastructure
- Project structure.
- Configuration.
- Database connection.
- Router.
- Request lifecycle.
- Error handling.
- Environment loading.
- Basic layout.
Phase 2 — Authentication and Admin
- Admin authentication.
- Session management.
- Authorization.
- Admin dashboard.
Phase 3 — Academic Content
- Profile.
- Disciplines.
- Topics.
- News.
- Assignments.
- Practical lessons.
- Laboratory lessons.
- Materials.
Phase 4 — Public Website
- Public pages.
- Multilingual routing.
- Search and filtering.
- Responsive design.
Phase 5 — Interactions
- Comments.
- Moderation.
- Feedback.
- File downloads.
Phase 6 — Production Readiness
- Security review.
- Performance review.
- SEO.
- Deployment.
- Backup and restore testing.
18. Missing Information Policy
Do not fabricate:
- Biography.
- Education.
- Employment history.
- Academic publications.
- Research projects.
- Awards.
- Contact information.
- Course descriptions.
- Assignment content.
- Laboratory procedures.
- Academic references.
Create editable fields and leave them empty until real information is provided.
19. Definition of Done
A feature is complete only when:
- Its requirements are implemented.
- Input validation is implemented.
- Authorization is verified where applicable.
- Database operations use prepared statements.
- Output is escaped.
- Relevant tests pass.
- Errors are handled appropriately.
- Existing unrelated functionality remains unaffected.
