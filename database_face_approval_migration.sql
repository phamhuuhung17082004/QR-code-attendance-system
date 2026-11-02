-- QRCAS Database Migration: Face Profile Approval (status column)
-- Tuong thich ca MySQL 8.0+ va MariaDB, an toan khi chay lai nhieu lan (idempotent)

SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'student_face_profiles'
      AND COLUMN_NAME = 'status'
);

SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE `student_face_profiles` ADD COLUMN `status` ENUM('pending','approved') NOT NULL DEFAULT 'approved'",
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
