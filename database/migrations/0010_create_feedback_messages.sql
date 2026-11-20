-- Private messages from the contact form. status: new | in_progress | processed (validated in PHP).
-- client_hash (SHA-256 of the sender address) supports rate limiting without storing the address.
CREATE TABLE IF NOT EXISTS feedback_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_name VARCHAR(120) NOT NULL,
    sender_email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NULL,
    message TEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'new',
    client_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_feedback_status_created (status, created_at),
    KEY idx_feedback_client_created (client_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
