-- Run this once on an existing database before deploying the secured attendance flow.
-- It is additive: existing students, teachers, and attendance records are preserved.

CREATE TABLE IF NOT EXISTS `app_config` (
  `k` varchar(64) NOT NULL,
  `v` varchar(255) NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identity` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempt_window` (`created_at`),
  KEY `idx_login_attempt_identity` (`identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject_code` int NOT NULL,
  `slot` int NOT NULL,
  `batch` varchar(100) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `semester` int NOT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attendance_session_open` (`teacher_id`, `subject_code`, `slot`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_qr_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint unsigned NOT NULL,
  `token_hash` binary(32) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_qr_token` (`token_hash`),
  KEY `idx_attendance_qr_expiry` (`expires_at`),
  CONSTRAINT `fk_attendance_qr_session` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint unsigned NOT NULL,
  `enrollment_no` int NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy_m` decimal(10,2) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) NOT NULL,
  `risk_score` tinyint unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_claim_per_student` (`attendance_session_id`, `enrollment_no`),
  CONSTRAINT `fk_attendance_claim_session` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chỉ lưu vector đặc trưng 128 chiều, không lưu ảnh selfie gốc.
-- Bảng này tách riêng khỏi students để có thể quản lý quyền truy cập dữ liệu
-- sinh trắc học độc lập với thông tin hồ sơ thông thường.
CREATE TABLE IF NOT EXISTS `student_face_profiles` (
  `enrollment_no` int NOT NULL,
  `descriptor` MEDIUMTEXT NOT NULL,
  `consented_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`enrollment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lưu kết quả của lần xác thực thành công để có thể kiểm tra lại điểm danh;
-- không lưu ảnh hoặc vector selfie của từng lần điểm danh.
CREATE TABLE IF NOT EXISTS `attendance_face_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint unsigned NOT NULL,
  `enrollment_no` int NOT NULL,
  `face_distance` decimal(6,4) NOT NULL,
  `liveness_challenge` varchar(16) NOT NULL,
  `verified_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_face_per_student` (`attendance_session_id`, `enrollment_no`),
  CONSTRAINT `fk_attendance_face_session` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bước idempotent thêm cột attendance_session_id vào attendance nếu chưa có
SET @col_att_sess = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'attendance'
      AND COLUMN_NAME = 'attendance_session_id'
);

SET @sql_att_sess = IF(
    @col_att_sess = 0,
    "ALTER TABLE `attendance` ADD COLUMN `attendance_session_id` bigint unsigned NULL AFTER `ip_address`",
    'SELECT 1'
);

PREPARE stmt_att_sess FROM @sql_att_sess;
EXECUTE stmt_att_sess;
DEALLOCATE PREPARE stmt_att_sess;

-- Bước idempotent thêm index idx_attendance_session_student vào attendance nếu chưa có
SET @idx_att_sess = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'attendance'
      AND INDEX_NAME = 'idx_attendance_session_student'
);

SET @sql_idx_att_sess = IF(
    @idx_att_sess = 0,
    "ALTER TABLE `attendance` ADD KEY `idx_attendance_session_student` (`attendance_session_id`, `enrollment_no`)",
    'SELECT 1'
);

PREPARE stmt_idx_att_sess FROM @sql_idx_att_sess;
EXECUTE stmt_idx_att_sess;
DEALLOCATE PREPARE stmt_idx_att_sess;

-- Bước idempotent đặt PRIMARY KEY cho settings nếu chưa có (cần MODIFY id NOT NULL trước)
UPDATE `settings` SET `id` = 1 WHERE `id` IS NULL LIMIT 1;

SET @has_settings_pk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'settings'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);

SET @sql_settings_modify = IF(
    @has_settings_pk = 0,
    "ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL",
    'SELECT 1'
);
PREPARE stmt_settings_modify FROM @sql_settings_modify;
EXECUTE stmt_settings_modify;
DEALLOCATE PREPARE stmt_settings_modify;

SET @sql_settings_pk = IF(
    @has_settings_pk = 0,
    "ALTER TABLE `settings` ADD PRIMARY KEY (`id`)",
    'SELECT 1'
);
PREPARE stmt_settings_pk FROM @sql_settings_pk;
EXECUTE stmt_settings_pk;
DEALLOCATE PREPARE stmt_settings_pk;
