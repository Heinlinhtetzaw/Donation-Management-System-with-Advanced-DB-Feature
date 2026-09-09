-- Donation Management System clean-install schema (v1.1.1)
-- MySQL 8.0+ or a compatible MariaDB release.

CREATE DATABASE IF NOT EXISTS dmssystem
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE dmssystem;

CREATE TABLE admin (
    admin_id INT NOT NULL AUTO_INCREMENT,
    adname VARCHAR(50) NOT NULL,
    adpassword VARCHAR(255) NOT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    last_failed_login DATETIME NULL,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admin_name (adname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donors (
    donor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    address VARCHAR(500) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(254) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (donor_id),
    UNIQUE KEY uq_donors_full_name (full_name),
    UNIQUE KEY uq_donors_phone (phone),
    KEY idx_donors_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE foundations (
    fid INT NOT NULL AUTO_INCREMENT,
    created_by_admin_id INT NULL,
    image_path VARCHAR(255) NOT NULL,
    fname VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    intro TEXT NOT NULL,
    create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fid),
    KEY fk_foundations_created_admin (created_by_admin_id),
    CONSTRAINT fk_foundations_created_admin
        FOREIGN KEY (created_by_admin_id) REFERENCES admin (admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
    nid INT NOT NULL AUTO_INCREMENT,
    created_by_admin_id INT NULL,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    create_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (nid),
    KEY fk_news_created_admin (created_by_admin_id),
    CONSTRAINT fk_news_created_admin
        FOREIGN KEY (created_by_admin_id) REFERENCES admin (admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donations (
    id INT NOT NULL AUTO_INCREMENT,
    donor_id BIGINT UNSIGNED NULL,
    reference_code VARCHAR(32) NULL,
    donor_name VARCHAR(100) NOT NULL,
    address VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    foundation_id INT NOT NULL,
    payment_method ENUM('Cash', 'Wavepay', 'Kpay') NOT NULL,
    payment_status ENUM('Pending', 'Complete') NOT NULL DEFAULT 'Pending',
    verified_at DATETIME NULL,
    verified_by VARCHAR(100) NULL,
    verified_by_admin_id INT NULL,
    status_note VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_donations_reference_code (reference_code),
    KEY idx_donations_donor_created (donor_id, created_at),
    KEY idx_donations_created_at (created_at),
    KEY fk_donations_foundation (foundation_id),
    KEY fk_donations_verified_admin (verified_by_admin_id),
    CONSTRAINT fk_donations_donor
        FOREIGN KEY (donor_id) REFERENCES donors (donor_id),
    CONSTRAINT fk_donations_foundation
        FOREIGN KEY (foundation_id) REFERENCES foundations (fid),
    CONSTRAINT fk_donations_verified_admin
        FOREIGN KEY (verified_by_admin_id) REFERENCES admin (admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donation_status_history (
    history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    donation_id INT NOT NULL,
    previous_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NOT NULL,
    note VARCHAR(500) NULL,
    changed_by VARCHAR(100) NOT NULL,
    changed_by_admin_id INT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (history_id),
    KEY idx_status_history_donation_time (donation_id, changed_at),
    KEY fk_history_changed_admin (changed_by_admin_id),
    CONSTRAINT fk_status_history_donation
        FOREIGN KEY (donation_id) REFERENCES donations (id),
    CONSTRAINT fk_history_changed_admin
        FOREIGN KEY (changed_by_admin_id) REFERENCES admin (admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_audit_logs (
    audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT NULL,
    admin_username VARCHAR(100) NOT NULL,
    action_type VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(64) NOT NULL,
    details VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    KEY idx_audit_entity_time (entity_type, entity_id, created_at),
    KEY idx_audit_admin_time (admin_username, created_at),
    KEY fk_audit_admin (admin_id),
    CONSTRAINT fk_audit_admin
        FOREIGN KEY (admin_id) REFERENCES admin (admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
