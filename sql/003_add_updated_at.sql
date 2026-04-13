-- Migration 003: Add updated_at timestamp to students table
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Also add other_names if not exists (field in form but missing from original schema migration)
ALTER TABLE students
    MODIFY COLUMN other_names VARCHAR(150) NULL DEFAULT NULL;
