-- Optional: run this manually in phpMyAdmin after backing up dmssystem.
-- It does not recreate tables or delete data. Verify the column names first,
-- especially news.create_at and admin.last_failed_login in older databases.

-- Prevent duplicate administrator usernames.
ALTER TABLE admin ADD UNIQUE INDEX uq_admin_adname (adname);

-- Improve the queries used by dashboard and donation-status pages.
ALTER TABLE donations ADD INDEX idx_donations_status_created (payment_status, created_at);
ALTER TABLE donations ADD INDEX idx_donations_foundation (foundation_id);

-- Only add this foreign key when there are no orphaned donation records.
-- It prevents accidental deletion of a foundation that still has donations.
-- ALTER TABLE donations
--   ADD CONSTRAINT fk_donations_foundation
--   FOREIGN KEY (foundation_id) REFERENCES foundations(fid)
--   ON DELETE RESTRICT ON UPDATE CASCADE;
