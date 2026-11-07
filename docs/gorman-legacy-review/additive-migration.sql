-- Only for an EXISTING light_stories table lacking these columns. Back up first.
-- New installations use docs/light-board-schema.sql instead; never run both.
ALTER TABLE light_stories
 ADD attribution_mode VARCHAR(20) NOT NULL DEFAULT 'anonymous',
 ADD contributor_ciphertext TEXT NULL,
 ADD public_attribution VARCHAR(100) NULL;
