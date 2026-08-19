-- Leave One Light On — Phase 1 private two-way messaging prototype
-- MySQL 8 / MariaDB-compatible schema.
-- Do not place production credentials in this repository.

CREATE TABLE conversations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  secret_hash CHAR(64) NOT NULL,
  public_reference VARCHAR(32) NOT NULL,
  topic VARCHAR(40) NOT NULL,
  nickname VARCHAR(120) NULL,
  response_method VARCHAR(20) NOT NULL,
  contact_ciphertext TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_conversations_secret_hash (secret_hash),
  UNIQUE KEY uq_conversations_public_reference (public_reference),
  KEY idx_conversations_status_activity (status, last_activity_at),
  KEY idx_conversations_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender VARCHAR(20) NOT NULL,
  message_body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_conversation_created (conversation_id, created_at, id),
  CONSTRAINT fk_messages_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_conversation_created (conversation_id, created_at),
  CONSTRAINT fk_audit_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores only a keyed one-way digest of the requesting IP address.
-- Rows should be purged frequently; they are not part of message history.
CREATE TABLE rate_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rate_key CHAR(64) NOT NULL,
  event_type VARCHAR(30) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rate_events_key_type_time (rate_key, event_type, created_at),
  KEY idx_rate_events_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
