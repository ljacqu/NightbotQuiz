-- Run init.php first so that nq_owner_stats is created

    ALTER TABLE nq_owner
    ADD COLUMN stats_id INT;

    INSERT INTO nq_owner_stats (id)
    SELECT id FROM nq_owner;

    UPDATE nq_owner
    SET stats_id = id;

    ALTER TABLE nq_owner
    MODIFY COLUMN stats_id INT NOT NULL;

    ALTER TABLE nq_owner
    ADD CONSTRAINT FK_nq_owner_stats
    FOREIGN KEY (stats_id) REFERENCES nq_owner_stats(id);


-- Add debug_mode column

    ALTER TABLE nq_settings
    ADD COLUMN debug_mode INT;

    UPDATE nq_settings
    SET debug_mode = 0;

    ALTER TABLE nq_settings
    MODIFY COLUMN debug_mode INT NOT NULL;

-- Add unique constraint to owner name

    ALTER TABLE nq_owner
    ADD UNIQUE KEY nq_owner_name (name) USING BTREE;
