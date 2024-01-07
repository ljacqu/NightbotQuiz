CREATE TABLE nq_draw_stats (
     id int NOT NULL AUTO_INCREMENT,
     draw_id int NOT NULL,
     last_question datetime,
     last_answer datetime,
     times_question_queried int,
     last_question_repeat datetime,
     PRIMARY KEY (id),
     FOREIGN KEY (draw_id) REFERENCES nq_draw(id),
     UNIQUE KEY nq_draw_stats_draw_id_uq (draw_id)
) ENGINE = InnoDB;

INSERT INTO nq_draw_stats (draw_id, last_question, last_answer, times_question_queried)
SELECT last_draw_id, last_question, last_answer, times_question_queried
FROM nq_owner_stats;

ALTER TABLE nq_owner_stats
  DROP COLUMN last_draw_id,
  DROP COLUMN last_question,
  DROP COLUMN last_answer,
  DROP COLUMN times_question_queried;

