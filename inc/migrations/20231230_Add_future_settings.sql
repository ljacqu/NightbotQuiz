-- Add new settings column

ALTER TABLE nq_settings
ADD COLUMN repeat_unanswered_question INT;

UPDATE nq_settings
SET repeat_unanswered_question = 0;

ALTER TABLE nq_settings
MODIFY COLUMN repeat_unanswered_question INT NOT NULL;


-- New stats column

ALTER TABLE nq_owner_stats
    ADD COLUMN times_question_queried int,
    ADD COLUMN public_page_url varchar(200);
