-- Downloadable materials. Files live outside the web root (storage/uploads) under random server-side names.
-- A category or topic that still has materials cannot be deleted (RESTRICT).
CREATE TABLE IF NOT EXISTS material_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_material_categories_status_order (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS material_category_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_material_category_translations (category_id, locale),
    CONSTRAINT fk_material_category_translations_category FOREIGN KEY (category_id) REFERENCES material_categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS materials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    topic_id INT UNSIGNED NULL,
    stored_name VARCHAR(64) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_materials_stored_name (stored_name),
    KEY idx_materials_status_created (status, created_at),
    KEY idx_materials_category (category_id),
    KEY idx_materials_topic (topic_id),
    CONSTRAINT fk_materials_category FOREIGN KEY (category_id) REFERENCES material_categories (id) ON DELETE RESTRICT,
    CONSTRAINT fk_materials_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS material_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    material_id INT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_material_translations (material_id, locale),
    CONSTRAINT fk_material_translations_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
