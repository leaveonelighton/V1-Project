-- Additive migration. Use the existing private-message database. No production secrets here.
CREATE TABLE light_stories (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 public_id CHAR(24) NOT NULL UNIQUE,
 receipt_hash CHAR(64) NOT NULL UNIQUE,
 perspective VARCHAR(20) NOT NULL,
 story_ciphertext TEXT NOT NULL,
 reflection_ciphertext TEXT NULL,
 website_consent TINYINT NOT NULL,
 reuse_consent TINYINT NOT NULL DEFAULT 0,
 consent_version VARCHAR(20) NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'pending',
 review_flags VARCHAR(255) NOT NULL DEFAULT '',
 review_note TEXT NULL,
 public_text TEXT NULL,
 public_reflection TEXT NULL,
 revision INT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 published_at DATETIME NULL,
 KEY idx_lights_public (state, published_at, public_id),
 KEY idx_lights_review (state, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE light_story_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 story_id BIGINT UNSIGNED NOT NULL,
 event_type VARCHAR(40) NOT NULL,
 created_at DATETIME NOT NULL,
 KEY idx_light_events (story_id, created_at),
 CONSTRAINT fk_light_events FOREIGN KEY (story_id) REFERENCES light_stories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
