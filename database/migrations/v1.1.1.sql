-- One-time upgrade for installations created before v1.1.1.
-- Back up the database first. Resolve duplicate admin/donor names before running.
USE dmssystem;

ALTER TABLE admin
    ADD UNIQUE KEY uq_admin_name (adname);

ALTER TABLE donors
    ADD UNIQUE KEY uq_donors_full_name (full_name);

ALTER TABLE donations
    MODIFY amount DECIMAL(12,2) NOT NULL,
    MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE foundations
    MODIFY create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE news
    MODIFY create_at DATE NOT NULL DEFAULT CURRENT_TIMESTAMP;
