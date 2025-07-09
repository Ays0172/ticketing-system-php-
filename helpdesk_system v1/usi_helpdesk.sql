-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 11:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `usi_helpdesk`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` varchar(10) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `task_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 3, 'USI003', 'Test 1', '2025-05-01 05:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `short_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `short_code`) VALUES
(1, 'Editorial', 'EDT'),
(2, 'Website', 'WEB'),
(3, 'Social Media', 'SOC'),
(4, 'IT', 'IT'),
(5, 'Video Editing', 'VID');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `user_id` varchar(10) DEFAULT NULL,
  `raised_by_name` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `support_staff_id` varchar(10) DEFAULT NULL,
  `reference_no` varchar(20) DEFAULT NULL,
  `status` enum('New','Opened','Pending','Completed') DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `department_id`, `user_id`, `raised_by_name`, `designation`, `support_staff_id`, `reference_no`, `status`, `created_at`, `file_path`) VALUES
(2, 'test', 'test', 1, 'USI008', NULL, NULL, 'USI005', 'EDT-001', 'Completed', '2025-04-30 18:47:47', NULL),
(3, 'DG', 'testing', 1, 'USI008', 'ABC', 'DG', 'USI003', 'EDT-002', 'Opened', '2025-05-01 05:18:45', NULL),
(4, 'ASD', 'Test Attachment 1', 2, 'USI006', 'ASD', 'ASD', NULL, 'WEB-001', 'New', '2025-05-01 05:23:40', 'uploads/file_6813055c84010.jpg'),
(5, 'AB', 'AB', 3, 'USI008', 'PDF test', 'AB', NULL, 'SOC-001', 'New', '2025-05-01 05:27:48', 'uploads/file_6813065486484.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `task_logs`
--

CREATE TABLE `task_logs` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `actor_user_id` varchar(10) NOT NULL,
  `role` enum('Administrator','Support Staff','User') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_logs`
--

INSERT INTO `task_logs` (`id`, `task_id`, `action`, `actor_user_id`, `role`, `created_at`) VALUES
(1, 2, 'Status updated to \'Completed\'', 'USI003', 'Support Staff', '2025-04-30 19:18:18'),
(2, 2, 'Status updated to \'Completed\'', 'USI003', 'Support Staff', '2025-04-30 19:43:02'),
(3, 2, 'Reassigned ticket to USI005', 'USI003', 'Support Staff', '2025-04-30 20:14:11'),
(4, 3, 'Assigned task to USI003', 'USI001', 'Administrator', '2025-05-01 05:20:24'),
(5, 3, 'Status updated to \'Pending\'', 'USI003', 'Support Staff', '2025-05-01 05:20:50'),
(6, 3, 'Reassigned ticket to USI004', 'USI003', 'Support Staff', '2025-05-01 05:21:04'),
(7, 3, 'Status updated to \'Completed\'', 'USI004', 'Support Staff', '2025-05-01 05:21:53'),
(8, 3, 'Assigned task to USI003', 'USI002', 'Administrator', '2025-05-01 05:27:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(10) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `role` enum('Administrator','Support Staff','User') DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `role`, `password`) VALUES
('USI001', 'Admin One', 'Administrator', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9'),
('USI002', 'Admin Two', 'Administrator', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9'),
('USI003', 'Ayus', 'Support Staff', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23'),
('USI004', 'Luv', 'Support Staff', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23'),
('USI005', 'Kaushal', 'Support Staff', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23'),
('USI006', 'Editorial', 'User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446'),
('USI007', 'ANBI', 'User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446'),
('USI008', 'CS3', 'User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `support_staff_id` (`support_staff_id`);

--
-- Indexes for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `actor_user_id` (`actor_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`support_staff_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD CONSTRAINT `task_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `task_logs_ibfk_2` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
