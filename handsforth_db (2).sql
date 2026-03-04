-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 02:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

DROP DATABASE IF EXISTS `handsforth_db`;
CREATE DATABASE `handsforth_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `handsforth_db`;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `handsforth_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `action_type` varchar(20) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `beneficiary_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `contact_info` varchar(200) DEFAULT NULL,
  `community_name` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `distributions`
--

CREATE TABLE `distributions` (
  `distribution_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `beneficiary_id` int(11) DEFAULT NULL,
  `distributed_amount` decimal(10,2) DEFAULT NULL,
  `distributed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `donation_type` enum('funds','goods') NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_received` date NOT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_schedule` varchar(50) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `txn_number` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `donation_type`, `amount`, `payment_type`, `description`, `date_received`, `status`, `is_recurring`, `recurring_schedule`, `receipt_file`, `donor_id`, `staff_id`, `txn_number`, `created_at`, `updated_at`) VALUES
(7, 'goods', NULL, NULL, NULL, '2026-02-18', 'completed', 0, NULL, NULL, 1, 1, 'TXN-2026-F03756A7', '2026-03-04 21:18:10', '2026-03-04 21:18:10');

-- --------------------------------------------------------

--
-- Table structure for table `donation_items`
--

CREATE TABLE `donation_items` (
  `item_id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `item_condition` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `estimated_value` decimal(10,2) DEFAULT NULL,
  `value_source` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `donor_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `engagement_status` varchar(20) DEFAULT 'active',
  `communication_log` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donors`
--

INSERT INTO `donors` (`donor_id`, `name`, `email`, `phone`, `engagement_status`, `communication_log`, `created_at`, `updated_at`) VALUES
(1, 'Jericho Bellen', 'jerichopbellen@gmail.com', '09716256372671', 'active', NULL, '2026-03-04 21:18:10', '2026-03-04 21:18:10');

-- --------------------------------------------------------

--
-- Table structure for table `monetary_details`
--

CREATE TABLE `monetary_details` (
  `id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `check_number` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `recurring` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('planned','ongoing','completed','cancelled') DEFAULT 'planned',
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `title`, `description`, `date`, `location`, `status`, `created_by`) VALUES
(6, 'test project', 'fake desc', '2026-02-20', 'Taguig', 'ongoing', 1);

-- --------------------------------------------------------

--
-- Table structure for table `project_beneficiaries`
--

CREATE TABLE `project_beneficiaries` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `service_received` text DEFAULT NULL,
  `date_served` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_volunteers`
--

CREATE TABLE `project_volunteers` (
  `assignment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `role_in_project` enum('leader','member','support') DEFAULT 'member',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `img_path` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role_id`, `first_name`, `last_name`, `email`, `img_path`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'jerichobellen', 'cbfdac6008f9cab4083784cbd1874f76618d2a97', NULL, 'Jericho', 'Bellen', 'jerichobellen@gmail.com', NULL, '587458', '2026-01-28 15:24:09', '2026-02-14 09:51:06'),
(3, 'testuser', 'hashedpass', NULL, 'Tests', 'User', 'test@example.com', '', '12345678910', '2026-01-28 16:07:15', '2026-02-14 10:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_details`
--

CREATE TABLE `volunteer_details` (
  `volunteer_id` int(11) NOT NULL,
  `skills` text DEFAULT NULL,
  `availability` enum('weekdays','weekends','anytime') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `idx_attendance_project_volunteer` (`project_id`,`volunteer_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_audit_performed_by` (`performed_by`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`beneficiary_id`);

--
-- Indexes for table `distributions`
--
ALTER TABLE `distributions`
  ADD PRIMARY KEY (`distribution_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `donation_id` (`donation_id`),
  ADD KEY `fk_dist_beneficiary` (`beneficiary_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD UNIQUE KEY `idx_duplicate_check` (`donor_id`,`amount`,`date_received`);

--
-- Indexes for table `donation_items`
--
ALTER TABLE `donation_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Indexes for table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`donor_id`),
  ADD UNIQUE KEY `unique_donor_email` (`email`);

--
-- Indexes for table `monetary_details`
--
ALTER TABLE `monetary_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_projects_status` (`status`);

--
-- Indexes for table `project_beneficiaries`
--
ALTER TABLE `project_beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `beneficiary_id` (`beneficiary_id`),
  ADD KEY `idx_project_beneficiaries` (`project_id`,`beneficiary_id`);

--
-- Indexes for table `project_volunteers`
--
ALTER TABLE `project_volunteers`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `idx_project_volunteers` (`project_id`,`volunteer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- Indexes for table `volunteer_details`
--
ALTER TABLE `volunteer_details`
  ADD PRIMARY KEY (`volunteer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `beneficiary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `distributions`
--
ALTER TABLE `distributions`
  MODIFY `distribution_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `donation_items`
--
ALTER TABLE `donation_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `donors`
--
ALTER TABLE `donors`
  MODIFY `donor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `monetary_details`
--
ALTER TABLE `monetary_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project_beneficiaries`
--
ALTER TABLE `project_beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_volunteers`
--
ALTER TABLE `project_volunteers`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `distributions`
--
ALTER TABLE `distributions`
  ADD CONSTRAINT `distributions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `distributions_ibfk_2` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dist_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`beneficiary_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dist_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`) ON DELETE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `fk_donation_donor` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`donor_id`) ON DELETE CASCADE;

--
-- Constraints for table `donation_items`
--
ALTER TABLE `donation_items`
  ADD CONSTRAINT `donation_items_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`) ON DELETE CASCADE;

--
-- Constraints for table `monetary_details`
--
ALTER TABLE `monetary_details`
  ADD CONSTRAINT `monetary_details_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`);

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `project_beneficiaries`
--
ALTER TABLE `project_beneficiaries`
  ADD CONSTRAINT `project_beneficiaries_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_beneficiaries_ibfk_2` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`beneficiary_id`) ON DELETE CASCADE;

--
-- Constraints for table `project_volunteers`
--
ALTER TABLE `project_volunteers`
  ADD CONSTRAINT `project_volunteers_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_volunteers_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL;

--
-- Constraints for table `volunteer_details`
--
ALTER TABLE `volunteer_details`
  ADD CONSTRAINT `volunteer_details_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
