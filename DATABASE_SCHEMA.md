Database Schema Proposal
1. General Rules
Database: MySQL.

Use InnoDB and utf8mb4.

Use foreign keys where appropriate.

Use migrations for schema changes.

All tables should have appropriate timestamps.

The following is a proposed schema, not an already implemented database.

Validate the final design against actual application requirements and the hosting MySQL version.
2. Administrators
admins
- id
- username
- password_hash
- created_at
- updated_at
- last_login_at
Version 1 supports exactly one administrator account.

Do not create a public registration workflow.
3. Teacher Profile
teacher_profiles
- id
- email (nullable)
- phone (nullable)
- photo_path (nullable)
- created_at
- updated_at
teacher_profile_translations
- id
- teacher_profile_id
- locale
- full_name
- position
- academic_degree
- specialization
- biography (nullable)
- education (nullable)
- experience (nullable)
Unique constraint:
- teacher_profile_id + locale
The confirmed teacher information must be entered accurately. Unknown information remains empty.
4. Courses
courses
- id
- slug
- status
- sort_order
- created_at
- updated_at
course_translations
- id
- course_id
- locale
- title
- description (nullable)
Unique constraints:
- course_id + locale
- locale + slug, if slug is stored per translation
Choose one consistent slug strategy before implementing routing.
5. Course Topics
course_topics
- id
- course_id
- sort_order
- status
- created_at
- updated_at
course_topic_translations
- id
- topic_id
- locale
- title
- description (nullable)
6. News
news
- id
- cover_image_path (nullable)
- course_id (nullable)
- status
- published_at (nullable)
- created_at
- updated_at
news_translations
- id
- news_id
- locale
- title
- summary (nullable)
- body
- slug
Unique constraints:
- news_id + locale
- locale + slug
7. Assignments
assignments
- id
- course_id
- topic_id (nullable)
- deadline (nullable)
- status
- created_at
- updated_at
assignment_translations
- id
- assignment_id
- locale
- title
- description
8. Practical Lessons
practical_lessons
- id
- course_id
- topic_id (nullable)
- sort_order
- status
- created_at
- updated_at
practical_lesson_translations
- id
- practical_lesson_id
- locale
- title
- description
9. Laboratory Lessons
laboratory_lessons
- id
- course_id
- topic_id (nullable)
- sort_order
- status
- created_at
- updated_at
laboratory_lesson_translations
- id
- laboratory_lesson_id
- locale
- title
- description
- safety_information (nullable)
Safety information must only contain verified content.
10. Materials
materials
- id
- course_id (nullable)
- topic_id (nullable)
- category_id (nullable)
- file_path
- original_filename
- mime_type
- file_size
- status
- created_at
- updated_at
material_translations
- id
- material_id
- locale
- title
- description (nullable)
11. Material Categories
material_categories
- id
- sort_order
- status
- created_at
- updated_at
material_category_translations
- id
- category_id
- locale
- name
12. Comments
comments
- id
- content_type
- content_id
- author_name
- author_email (nullable)
- body
- status
- created_at
- updated_at
Suggested statuses:
- pending
- approved
- rejected
Important:

A polymorphic content_type + content_id relationship cannot enforce a normal foreign key to multiple tables.

Before implementation, decide between:
1. Explicit nullable foreign keys for supported content types.
2. A validated polymorphic relationship with strict allowlists and existence checks.
Do not implement an unchecked polymorphic relationship.
13. Feedback
feedback_messages
- id
- sender_name
- sender_email
- subject (nullable)
- message
- status
- created_at
- updated_at
Suggested statuses:
- new
- in_progress
- processed
Feedback is private.
14. Site Settings
site_settings
- id
- setting_key
- setting_value
- created_at
- updated_at
Unique constraint:
- setting_key
Do not store secrets or passwords as ordinary settings.
15. Optional Media Metadata
media_files
Use only if a separate media library is justified.

Possible fields:
- id
- storage_path
- original_filename
- mime_type
- file_size
- created_at
Avoid duplicating file metadata between media_files and materials without a clear ownership model.
16. Indexes
Consider indexes for:
- Foreign keys.
- Entity status.
- Publication date.
- Course associations.
- Topic associations.
- Locale and slug.
- Comment status.
- Feedback status.
- Material category.
- Searchable columns.
Create composite indexes based on actual query patterns.
17. Deletion Policy
Define deletion behavior for every foreign key.

Prefer restrictive deletion or explicit application-level deletion workflows when educational content has dependent records.

Avoid cascading deletion of important published content without explicit requirements.
18. Migration Requirements
- Version every migration.
- Keep migrations reproducible.
- Never run destructive migrations automatically in production.
- Back up before production schema changes.
- Test migrations on a separate database first.
