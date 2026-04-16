-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 06:34 AM
-- Server version: 8.0.45
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `civictrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `created_at`) VALUES
(2, 'admin', '$2y$10$8KsK7b1SatPukDRAMaG/O.RReKE95LrXfk4/OI.wqfcQkNDk8R4h6', 'System Administrator', '2026-04-15 22:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `citizens`
--

CREATE TABLE `citizens` (
  `id` int NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `citizens`
--

INSERT INTO `citizens` (`id`, `full_name`, `email`, `phone`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 'Ramesh Kumar', 'ramesh@example.com', '9876543210', '$2y$10$TKh8H1.PfQ0A32WY.fXUIOqNKGge069oNlo61MwGiH1MR3T2PQdyC', 1, '2026-04-10 00:08:56'),
(2, 'Priya Sharma', 'priya@example.com', '9123456789', '$2y$10$TKh8H1.PfQ0A32WY.fXUIOqNKGge069oNlo61MwGiH1MR3T2PQdyC', 1, '2026-04-10 00:08:56'),
(3, 'Ankit Patel', 'ankit@example.com', '9988776655', '$2y$10$TKh8H1.PfQ0A32WY.fXUIOqNKGge069oNlo61MwGiH1MR3T2PQdyC', 1, '2026-04-10 00:08:56'),
(4, 'Sunita Devi', 'sunita@example.com', '9700123456', '$2y$10$TKh8H1.PfQ0A32WY.fXUIOqNKGge069oNlo61MwGiH1MR3T2PQdyC', 1, '2026-04-10 00:08:56'),
(5, 'Vinod Reddy', 'vinod@example.com', '8801234567', '$2y$10$TKh8H1.PfQ0A32WY.fXUIOqNKGge069oNlo61MwGiH1MR3T2PQdyC', 1, '2026-04-10 00:08:56'),
(6, 'Nishath.Mohammad', 'nishathmohammad2407@gmail.com', '6301342885', '$2y$10$Web3GJA1M4QT2aLwm3p.W.bT3LFBRSuC993TE8hvXHY3hdMkc0L3a', 1, '2026-04-14 00:40:10'),
(7, 'hothra', 'mahinsayyad04@gmail.com', '9392625161', '$2y$10$ePYWw785wIAcGtvi3BYsyuRLyTu5HmdtFHkj3bkIoQdXV1/zh8nCO', 1, '2026-04-14 00:45:46');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int NOT NULL,
  `complaint_id` varchar(20) NOT NULL,
  `type` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `ward` varchar(255) DEFAULT NULL,
  `address` varchar(300) NOT NULL,
  `landmark` varchar(180) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `priority` enum('High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `status` enum('Pending','In Progress','Resolved','Escalated') NOT NULL DEFAULT 'Pending',
  `citizen_name` varchar(120) NOT NULL,
  `citizen_phone` varchar(15) NOT NULL,
  `citizen_id` int DEFAULT NULL,
  `image_path` varchar(350) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `complaint_id`, `type`, `description`, `ward`, `address`, `landmark`, `lat`, `lng`, `priority`, `status`, `citizen_name`, `citizen_phone`, `citizen_id`, `image_path`, `submitted_at`, `updated_at`) VALUES
(1, 'CMP-001', 'Pothole', 'Road damaged', 'Ward 1', 'MG Road', 'Bus Stop', 17.3850000, 78.4867000, 'High', 'In Progress', 'Ramesh Kumar', '9876543210', 1, NULL, '2026-04-10 00:08:56', '2026-04-15 23:18:31'),
(2, 'CMP-002', 'Garbage', 'Not collected', 'Ward 4', 'Nehru Nagar', 'Market', 17.3615000, 78.4747000, 'High', 'Pending', 'Priya Sharma', '9123456789', 2, NULL, '2026-04-10 00:08:56', '2026-04-10 00:08:56'),
(3, 'CMP-003', '🕳️ Pothole / Road Damage', 'this is the major reason for the major accidents happening during rainy season', NULL, 'miyapur', 'nizampet', NULL, NULL, 'High', 'Resolved', 'Nishath.Mohammad', '6301342885', 6, 'uploads/IMG_69dfdc314708f5.15620259.png', '2026-04-16 00:12:57', '2026-04-16 09:21:11');

-- --------------------------------------------------------

--
-- Table structure for table `complaint_timeline`
--

CREATE TABLE `complaint_timeline` (
  `id` int NOT NULL,
  `complaint_id` varchar(20) NOT NULL,
  `label` varchar(250) NOT NULL,
  `event_date` date NOT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `complaint_timeline`
--

INSERT INTO `complaint_timeline` (`id`, `complaint_id`, `label`, `event_date`, `is_done`, `created_at`) VALUES
(1, 'CMP-003', 'Complaint Submitted', '2026-04-16', 1, '2026-04-16 00:12:57'),
(2, 'CMP-003', 'Citizen provided feedback. Case marked as Resolved.', '2026-04-16', 1, '2026-04-16 09:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `escalation_log`
--

CREATE TABLE `escalation_log` (
  `id` int NOT NULL,
  `complaint_id` varchar(20) NOT NULL,
  `reason` varchar(250) NOT NULL DEFAULT 'SLA breach',
  `escalated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `complaint_id` varchar(20) NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `complaint_id`, `rating`, `comment`, `submitted_at`, `created_at`) VALUES
(1, 'CMP-003', 5, 'the work is resolved within a short period of time', '2026-04-16 09:25:46', '2026-04-16 09:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int NOT NULL,
  `phone` varchar(15) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `citizens`
--
ALTER TABLE `citizens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `complaint_id` (`complaint_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_ward` (`ward`(50)),
  ADD KEY `idx_submitted` (`submitted_at`),
  ADD KEY `idx_citizen` (`citizen_id`);

--
-- Indexes for table `complaint_timeline`
--
ALTER TABLE `complaint_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cmp` (`complaint_id`);

--
-- Indexes for table `escalation_log`
--
ALTER TABLE `escalation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaint_id` (`complaint_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `complaint_id` (`complaint_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `citizens`
--
ALTER TABLE `citizens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `complaint_timeline`
--
ALTER TABLE `complaint_timeline`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `escalation_log`
--
ALTER TABLE `escalation_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaint_citizen` FOREIGN KEY (`citizen_id`) REFERENCES `citizens` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `complaint_timeline`
--
ALTER TABLE `complaint_timeline`
  ADD CONSTRAINT `complaint_timeline_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE;

--
-- Constraints for table `escalation_log`
--
ALTER TABLE `escalation_log`
  ADD CONSTRAINT `escalation_log_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
