-- Advanced donation administration migration.
-- Back up dmssystem first, then run this file once in phpMyAdmin.
-- It preserves existing donations and adds traceability; it does not delete data.

START TRANSACTION;

CREATE TABLE donors (
    donor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    address VARCHAR(500) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(254) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (donor_id),
    UNIQUE KEY uq_donors_phone (phone),
    KEY idx_donors_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE donations
    ADD COLUMN donor_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN reference_code VARCHAR(32) NULL AFTER donor_id,
    ADD COLUMN verified_at DATETIME NULL AFTER payment_status,
    ADD COLUMN verified_by VARCHAR(100) NULL AFTER verified_at,
    ADD COLUMN status_note VARCHAR(500) NULL AFTER verified_by,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD UNIQUE KEY uq_donations_reference_code (reference_code),
    ADD KEY idx_donations_donor_created (donor_id, created_at),
    ADD KEY idx_donations_created_at (created_at);

-- Create reusable donors from existing donation records. Phone is the stable matching key.
INSERT INTO donors (full_name, address, phone)
SELECT MAX(donor_name), MAX(address), phone
FROM donations
WHERE phone IS NOT NULL AND phone <> ''
GROUP BY phone;

UPDATE donations d
JOIN donors donor ON donor.phone = d.phone
SET d.donor_id = donor.donor_id,
    d.reference_code = CONCAT('DON-', LPAD(d.id, 8, '0'))
WHERE d.donor_id IS NULL;

CREATE TABLE donation_status_history (
    history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    donation_id INT NOT NULL,
    previous_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NOT NULL,
    note VARCHAR(500) NULL,
    changed_by VARCHAR(100) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (history_id),
    KEY idx_status_history_donation_time (donation_id, changed_at),
    CONSTRAINT fk_status_history_donation FOREIGN KEY (donation_id)
        REFERENCES donations(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_audit_logs (
    audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_username VARCHAR(100) NOT NULL,
    action_type VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(64) NOT NULL,
    details VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    KEY idx_audit_entity_time (entity_type, entity_id, created_at),
    KEY idx_audit_admin_time (admin_username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE donations
    ADD CONSTRAINT fk_donations_donor FOREIGN KEY (donor_id)
        REFERENCES donors(donor_id) ON DELETE RESTRICT ON UPDATE CASCADE;

COMMIT;
