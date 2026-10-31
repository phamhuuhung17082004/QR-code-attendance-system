-- QRCAS Database Migration: WebAuthn Passkey (student_passkeys)
-- An toàn khi chạy lại nhiều lần (idempotent)

CREATE TABLE IF NOT EXISTS `student_passkeys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enrollment_no` int(11) NOT NULL,
  `credential_id` varbinary(255) NOT NULL,
  `public_key` mediumtext NOT NULL,
  `sign_count` int(10) unsigned NOT NULL DEFAULT 0,
  `label` varchar(100) NOT NULL,
  `status` enum('pending','approved','revoked') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_credential_id` (`credential_id`),
  KEY `idx_enrollment_no` (`enrollment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_config` (`k`, `v`) VALUES ('passkey_mode', 'always')
ON DUPLICATE KEY UPDATE `k` = `k`;

