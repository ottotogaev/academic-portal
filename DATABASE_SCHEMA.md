# Database Schema (implemented)

This replaces the original proposal. Source of truth: `database/migrations/*.sql`, applied with `php bin/migrate.php`. Behaviour is verified by `tests/schema-test.php` on a disposable database.

## Rules

- MySQL / MariaDB, InnoDB, `utf8mb4` + `utf8mb4_unicode_ci`.
- Migrations are versioned, idempotent (`CREATE TABLE IF NOT EXISTS`) and never destructive. Back up before running them in production (`--yes` is required there).
- Portable SQL: no JSON, CHECK constraints or `DEFAULT CURRENT_TIMESTAMP` in tables from `0002` on. `created_at` / `updated_at` are `DATETIME` in UTC, written by PHP. (`0001` uses `DEFAULT CURRENT_TIMESTAMP`, which needs MySQL 5.6.5+.)
- Statuses and kinds are short `VARCHAR`s checked against allowlists in PHP (no ENUM, so values can change without a schema change).
- Translations: each translatable entity has a `<entity>_translations` table with `UNIQUE (<entity>_id, locale)`. Only locales that have real content get rows; nothing is machine-filled. Deleting an entity removes its translation rows (`ON DELETE CASCADE`).

## Differences from the original proposal (the site has one subject)

- No `courses` table and no `course_id` anywhere.
- Practical and laboratory lessons share one `lessons` table (`kind = practical | laboratory`). `safety_information` exists only for laboratory lessons and must contain verified content only.
- `news.slug` is language-neutral and unique, so the language switcher can link the same article (the proposal stored it per translation).
- `materials` stores `stored_name` (random server-side file name, files live in `storage/uploads`, never public) instead of `file_path`.
- Comments are **not** created yet. When commenting is approved, use explicit nullable foreign keys (for example `news_id`) rather than an unchecked `content_type` + `content_id`.

## Tables

| Migration | Tables | Notes |
|---|---|---|
| 0001 | `site_settings` | key/value, unique `setting_key`; no secrets |
| 0002 | `admins` | exactly one administrator, bcrypt `password_hash`, `last_login_at`; no default account |
| 0003 | `login_attempts` | failed-login throttling; stores a SHA-256 of the client address |
| 0004 | `topics`, `topic_translations` | `sort_order`, `status` (draft / published / archived) |
| 0005 | `news`, `news_translations` | `slug`, `cover_image_path`, `status`, `published_at`; translation: `title`, `summary`, `body` |
| 0006 | `assignments`, `assignment_translations` | optional `topic_id`, optional `deadline` |
| 0007 | `lessons`, `lesson_translations` | `kind`, optional `topic_id`, `sort_order`; translation adds `safety_information` |
| 0008 | `material_categories`(+`_translations`), `materials`(+`_translations`) | file metadata: `stored_name`, `original_filename`, `mime_type`, `file_size`; optional category and topic |
| 0009 | `teacher_profiles`, `teacher_profile_translations` | single row in v1; NULL means unknown and is not shown publicly |
| 0010 | `feedback_messages` | status new / in_progress / processed; `client_hash` for rate limiting; private |

## Deletion policy

- `assignments.topic_id`, `lessons.topic_id`, `materials.topic_id`, `materials.category_id`: `ON DELETE RESTRICT`. A topic or category that is still in use cannot be deleted; archive it instead (`status = archived`) or move its items first.
- Translation tables cascade from their parent only.
- Published content is removed by an explicit admin action, never as a side effect of another deletion.

## Indexes

Listing queries filter by status and order by `sort_order`, `published_at`, `deadline` or `created_at`; each has a matching composite index. Foreign keys are indexed. Feedback has indexes on status and on `(client_hash, created_at)` for rate limiting.
