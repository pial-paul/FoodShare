-- Run once on the original three-table schema. See docs/operations.md first.
ALTER TABLE requests
    ADD completed_at DATETIME NULL,
    ADD received_kg DECIMAL(8,2) NULL,
    ADD assignment_slot TINYINT GENERATED ALWAYS AS (
        CASE WHEN status IN ('pending','approved','completed') THEN 1 ELSE NULL END
    ) STORED,
    ADD UNIQUE INDEX one_assignment (donation_id, assignment_slot),
    ADD INDEX receiver_history (receiver_id, id),
    ADD INDEX confirmed_period (status, completed_at);
ALTER TABLE donations
    MODIFY status ENUM('available','requested','picked_up','expired','withdrawn') DEFAULT 'available',
    ADD pickup_details VARCHAR(500) NULL,
    ADD handling_notes VARCHAR(500) NULL,
    ADD INDEX browse_expiry (status, expiry_date, id),
    ADD INDEX donor_history (donor_id, id);
ALTER TABLE users ADD disabled_at DATETIME NULL;
CREATE TABLE login_limits (
    bucket CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL,
    resets_at DATETIME NOT NULL,
    INDEX limit_expiry (resets_at)
) ENGINE=InnoDB;
