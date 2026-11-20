-- Profile shown on the About page and in the footer. Empty/NULL means "unknown": nothing is shown publicly.
-- Version 1 holds a single row; the application always reads the first one.
CREATE TABLE IF NOT EXISTS teacher_profiles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    photo_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS teacher_profile_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_profile_id INT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NULL,
    academic_degree VARCHAR(255) NULL,
    specialization VARCHAR(255) NULL,
    biography MEDIUMTEXT NULL,
    education MEDIUMTEXT NULL,
    experience MEDIUMTEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teacher_profile_translations (teacher_profile_id, locale),
    CONSTRAINT fk_teacher_profile_translations_profile FOREIGN KEY (teacher_profile_id) REFERENCES teacher_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
