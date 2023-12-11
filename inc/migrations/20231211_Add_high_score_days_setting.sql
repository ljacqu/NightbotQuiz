ALTER TABLE nq_settings
ADD COLUMN high_score_days INT;

UPDATE nq_settings
SET high_score_days = 30;

ALTER TABLE nq_settings
MODIFY COLUMN high_score_days INT NOT NULL;
