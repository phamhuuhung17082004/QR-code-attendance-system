-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Server version: 10.4.34-MariaDB-cll-lve
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qrattendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `enrollment_no` int(255) NOT NULL,
  `date` date NOT NULL,
  `day` text NOT NULL,
  `subject_code` int(11) NOT NULL,
  `slot` int(11) NOT NULL,
  `batch` text NOT NULL,
  `branch` text NOT NULL,
  `semester` int(11) DEFAULT NULL,
  `time` text NOT NULL,
  `ip_address` longtext DEFAULT NULL,
  `attendance_session_id` bigint(20) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `enrollment_no`, `date`, `day`, `subject_code`, `slot`, `batch`, `branch`, `semester`, `time`, `ip_address`, `attendance_session_id`) VALUES
(49, 147501, '2024-08-08', 'Thursday', 1, 1, 'A1', 'Computer Engineering', 1, '03:55:54 pm', '152.57.104.48', NULL),
(50, 147502, '2024-08-08', 'Thursday', 1, 1, 'A1', 'Computer Engineering', 1, '03:56:45 pm', '59.184.29.71', NULL),
(51, 147501, '2024-08-08', 'Thursday', 2, 2, 'A1', 'Computer Engineering', 1, '03:57:54 pm', '152.57.117.119', NULL),
(52, 147502, '2024-08-08', 'Thursday', 2, 2, 'A1', 'Computer Engineering', 1, '06:37:39 pm', '152.57.126.77', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_noti`
--

CREATE TABLE `attendance_noti` (
  `id` int(11) NOT NULL,
  `enrollment_no` int(255) NOT NULL,
  `date` text NOT NULL,
  `day` text NOT NULL,
  `subject_code` int(11) NOT NULL,
  `slot` int(11) NOT NULL,
  `batch` text NOT NULL,
  `branch` text NOT NULL,
  `semester` int(11) DEFAULT NULL,
  `time` text NOT NULL,
  `ip_address` longtext DEFAULT NULL,
  `is_read` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `location` text DEFAULT NULL,
  `lat` text DEFAULT NULL,
  `lon` text DEFAULT NULL,
  `covarage` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `location`, `lat`, `lon`, `covarage`) VALUES
(1, 'NH753BB, Sindkhede 425406, Maharashtra Sindkhede Maharashtra India', '21.338150080341443', '74.55262184727246', '0.5');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `enrollment_no` int(11) NOT NULL,
  `name` text NOT NULL,
  `semester` int(11) NOT NULL,
  `branch` text NOT NULL,
  `roll_no` int(11) NOT NULL,
  `batch` text NOT NULL,
  `password` text NOT NULL,
  `pic` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`enrollment_no`, `name`, `semester`, `branch`, `roll_no`, `batch`, `password`, `pic`) VALUES
(147501, 'Jayesh Gavale', 1, 'Computer Engineering', 1, 'A1', '123', NULL),
(147502, 'Tejas Chaudhari', 1, 'Computer Engineering', 2, 'A1', '123', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_code` int(11) NOT NULL,
  `name` text NOT NULL,
  `abbreviation` text NOT NULL,
  `semester` int(11) NOT NULL,
  `branch` text NOT NULL,
  `teacher_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_code`, `name`, `abbreviation`, `semester`, `branch`, `teacher_id`) VALUES
(1, 'DBMS', 'DBMS', 1, 'Computer Engineering', 3),
(2, 'Java', 'JAVA', 1, 'Computer Engineering', 3);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `education` text NOT NULL,
  `designation` text NOT NULL,
  `branch` text NOT NULL,
  `password` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `education`, `designation`, `branch`, `password`) VALUES
(3, 'Sandip Vende', 'M.E/M.Tech', 'Professor', 'Computer Engineering', '123');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `batch` text NOT NULL,
  `branch` text DEFAULT NULL,
  `day` text NOT NULL,
  `slot` int(11) NOT NULL,
  `slotlabel` text DEFAULT NULL,
  `subject_code` int(11) NOT NULL,
  `academic_year` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `semester`, `batch`, `branch`, `day`, `slot`, `slotlabel`, `subject_code`, `academic_year`) VALUES
(163, 1, 'A1', 'Computer Engineering', 'Monday', 1, '11:00 AM To 12:00 PM', 2, '2024-25'),
(164, 1, 'A1', 'Computer Engineering', 'Tuesday', 1, '11:00 AM To 12:00 PM', 1, '2024-25'),
(165, 1, 'A1', 'Computer Engineering', 'Wednesday', 1, '11:00 AM To 12:00 PM', 1, '2024-25'),
(166, 1, 'A1', 'Computer Engineering', 'Thursday', 1, '11:00 AM To 12:00 PM', 1, '2024-25'),
(167, 1, 'A1', 'Computer Engineering', 'Friday', 1, '11:00 AM To 12:00 PM', 1, '2024-25'),
(168, 1, 'A1', 'Computer Engineering', 'Saturday', 1, '11:00 AM To 12:00 PM', 2, '2024-25'),
(169, 1, 'A1', 'Computer Engineering', 'Monday', 2, '1:00 PM To 2:00 PM', 1, '2024-25'),
(170, 1, 'A1', 'Computer Engineering', 'Tuesday', 2, '1:00 PM To 2:00 PM', 2, '2024-25'),
(171, 1, 'A1', 'Computer Engineering', 'Wednesday', 2, '1:00 PM To 2:00 PM', 2, '2024-25'),
(172, 1, 'A1', 'Computer Engineering', 'Thursday', 2, '1:00 PM To 2:00 PM', 2, '2024-25'),
(173, 1, 'A1', 'Computer Engineering', 'Friday', 2, '1:00 PM To 2:00 PM', 2, '2024-25'),
(174, 1, 'A1', 'Computer Engineering', 'Saturday', 2, '1:00 PM To 2:00 PM', 1, '2024-25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `user_type` text NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `user_type`, `status`) VALUES
(1, 'admin', '$2y$10$m62rGFcy.z6I0xvDBp4qSu88SQatgUSTGr6UpuD9wxrOefLoMcRQ.', 'ADMIN', 1);

-- --------------------------------------------------------

--
-- Table structure for table `app_config`
--

CREATE TABLE `app_config` (
  `k` varchar(64) NOT NULL,
  `v` varchar(255) NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_config`
--

INSERT INTO `app_config` (`k`, `v`) VALUES ('passkey_mode', 'always')
ON DUPLICATE KEY UPDATE `k` = `k`;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identity` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempt_window` (`created_at`),
  KEY `idx_login_attempt_identity` (`identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `subject_code` int(11) NOT NULL,
  `slot` int(11) NOT NULL,
  `batch` varchar(100) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `semester` int(11) NOT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attendance_session_open` (`teacher_id`, `subject_code`, `slot`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_qr_tokens`
--

CREATE TABLE `attendance_qr_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint(20) unsigned NOT NULL,
  `token_hash` binary(32) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_qr_token` (`token_hash`),
  KEY `idx_attendance_qr_expiry` (`expires_at`),
  CONSTRAINT `fk_attendance_qr_session` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_claims`
--

CREATE TABLE `attendance_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint(20) unsigned NOT NULL,
  `enrollment_no` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy_m` decimal(10,2) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) NOT NULL,
  `risk_score` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_claim_per_student` (`attendance_session_id`, `enrollment_no`),
  CONSTRAINT `fk_attendance_claim_session` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_face_profiles`
--

CREATE TABLE `student_face_profiles` (
  `enrollment_no` int(11) NOT NULL,
  `descriptor` mediumtext NOT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'approved',
  `consented_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`enrollment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_face_verifications`
--

CREATE TABLE `attendance_face_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint(20) unsigned NOT NULL,
  `enrollment_no` int(11) NOT NULL,
  `face_distance` decimal(6,4) NOT NULL,
  `liveness_challenge` varchar(16) NOT NULL,
  `verified_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_face_per_student` (`attendance_session_id`, `enrollment_no`),
  CONSTRAINT `fk_attendance_face_session` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_passkeys`
--

CREATE TABLE `student_passkeys` (
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attendance_slot_student` (`date`, `enrollment_no`, `subject_code`, `slot`, `batch`(50)),
  ADD KEY `idx_attendance_session_student` (`attendance_session_id`, `enrollment_no`);

--
-- Indexes for table `attendance_noti`
--
ALTER TABLE `attendance_noti`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`enrollment_no`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `attendance_noti`
--
ALTER TABLE `attendance_noti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_code` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=469;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
