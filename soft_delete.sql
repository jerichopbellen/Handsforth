-- Add is_deleted column to main tables for soft delete
ALTER TABLE beneficiaries ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE projects ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE donations ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE project_volunteers ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;

-- Example: Mark a record as deleted (soft delete)
-- UPDATE beneficiaries SET is_deleted = 1 WHERE beneficiary_id = ?;
-- UPDATE projects SET is_deleted = 1 WHERE project_id = ?;
-- UPDATE donations SET is_deleted = 1 WHERE donation_id = ?;
-- UPDATE users SET is_deleted = 1 WHERE user_id = ?;
-- UPDATE project_volunteers SET is_deleted = 1 WHERE assignment_id = ?;

-- Example: Select only non-deleted records
-- SELECT * FROM beneficiaries WHERE is_deleted = 0;
-- SELECT * FROM projects WHERE is_deleted = 0;
-- SELECT * FROM donations WHERE is_deleted = 0;
-- SELECT * FROM users WHERE is_deleted = 0;
-- SELECT * FROM project_volunteers WHERE is_deleted = 0;
