USE asterisk;
ALTER TABLE call_history ADD COLUMN IF NOT EXISTS cisco_directory TINYINT(1) NOT NULL DEFAULT 0 AFTER sentiment;
