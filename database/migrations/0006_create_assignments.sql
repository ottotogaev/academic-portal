-- Assignments. A topic is optional; a topic that still has assignments cannot be deleted (RESTRICT).
CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    topic_id INT UNSIGNED NULL,
    deadline DATETIME NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_assignments_status_deadline (status, deadline),
    KEY idx_assignments_topic (topic_id),
    CONSTRAINT fk_assignments_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assignment_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description MEDIUMTEXT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assignment_translations (assignment_id, locale),
    CONSTRAINT fk_assignment_translations_assignment FOREIGN KEY (assignment_id) REFERENCES assignments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
