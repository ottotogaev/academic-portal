-- Practical and laboratory lessons share one table; kind = practical | laboratory (validated in PHP).
-- safety_information is only for laboratory lessons and must contain verified content only.
CREATE TABLE IF NOT EXISTS lessons (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind VARCHAR(16) NOT NULL,
    topic_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lessons_kind_status_order (kind, status, sort_order),
    KEY idx_lessons_topic (topic_id),
    CONSTRAINT fk_lessons_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS lesson_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    lesson_id INT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description MEDIUMTEXT NOT NULL,
    safety_information TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lesson_translations (lesson_id, locale),
    CONSTRAINT fk_lesson_translations_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
