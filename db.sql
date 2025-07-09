-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 10:15 AM
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
-- Database: `eoffice`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrator','Support Staff','User','SubUser') NOT NULL,
  `Dept_name` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `password`, `role`, `Dept_name`, `contact_number`, `designation`) VALUES
('ADM', 'Admin', '$2y$10$1bZSi/Juy8tJRvIKFiJI0O114S2Qn5/tlJ0QEk47gW2Z6sUy3.Xx2', 'Administrator', 'ADM', '1234', NULL),
('adm_ashok_kumar', 'Ashok Kumar', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'ADM', '1234', NULL),
('adm_jyoti_chawla', 'Jyoti Chawla', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'ADM', '5678', NULL),
('adm_suresh_sharma', 'Suresh Sharma', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'ADM', '1234', NULL),
('CENTANB', 'CENTANB', '$2y$10$3H0CyXbEzpGKsnDSz2dMLuoExHhji6pmFMeue27065e2Ya8O4NOsi', 'Administrator', 'CENTANB', '5678', NULL),
('cet_naresh_kumar', 'Naresh Kumar', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CENTANB', '5678', NULL),
('cet_ravindra_sharma', 'Ravindra Sharma', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CENTAB', '1234', NULL),
('CMHCS', 'CMHCS', '$2y$10$SKiCa8BtIv3SpxsCx3zPhePnR3yepZPK4kk8ImtLW2avKhoL759hm', 'Administrator', 'CMHCS', '1234', NULL),
('cmh_bharti_negi', 'Bharti Negi', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CMHCS', '5678', NULL),
('cmh_gaurav_dixit', 'Gaurav Dixit', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CMHCS', '1234', NULL),
('cmh_rakesh_agnihotri', 'Rakesh Agnihotri', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CMHCS', '5678', NULL),
('COURSES', 'Courses', '$2y$10$3H0CyXbEzpGKsnDSz2dMLuoExHhji6pmFMeue27065e2Ya8O4NOsi', 'Administrator', 'COURSES', '5678', NULL),
('cou_pankaj_marwah', 'Pankaj Marwah', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'COURSES', '1234', NULL),
('cou_rajesh_pandey', 'Rajesh Pandey', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'COURSES', '5678', NULL),
('cou_tej_ram', 'Tej Ram', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'COURSES', '1234', NULL),
('CS3', 'CS3', '$2y$10$3H0CyXbEzpGKsnDSz2dMLuoExHhji6pmFMeue27065e2Ya8O4NOsi', 'Administrator', 'CS3', '1234', NULL),
('cs3_aparna_roy', 'Aparna Roy', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CS3', '5678', NULL),
('cs3_hemant_choudhary', 'Hemant Choudhary', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CS3', '1234', NULL),
('cs3_manish_gupta', 'Manish Gupta', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'CS3', '5678', NULL),
('DG_SECTT', 'DG Sectt.', '$2y$10$3H0CyXbEzpGKsnDSz2dMLuoExHhji6pmFMeue27065e2Ya8O4NOsi', 'Administrator', 'DG_SECTT', '5678', NULL),
('EDITORIAL', 'Editorial', '$2y$10$3H0CyXbEzpGKsnDSz2dMLuoExHhji6pmFMeue27065e2Ya8O4NOsi', 'Administrator', 'EDITORIAL', '1234', NULL),
('edi_laxman_singh', 'Laxman Singh', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'EDITORIAL', '1234', NULL),
('edi_neelotpal_mishra', 'Neelotpal Mishra', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'EDITORIAL', '5678', NULL),
('edi_savita_saluja', 'Savita Saluja', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'EDITORIAL', '1234', NULL),
('IT', 'IT', '$2y$10$7MT8qreu4bZZJSLckyO1EeU/kafktpbIN6puhSt0alX3O03mzAo1q', 'Administrator', 'IT', '1234', NULL),
('lib_anita_midha', 'Anita Midha', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'LIBRARY', '5678', NULL),
('lib_pankaj_sharma', 'Pankaj Sharma', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'LIBRARY', '1234', NULL),
('lib_rajesh_kumar', 'Rajesh Kumar', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'LIBRARY', '5678', NULL),
('lib_santosh_vijay', 'Santosh Vijay', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'LIBRARY', '1234', NULL),
('lib_urmil_rajor', 'Urmil Rajor', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'LIBRARY', '5678', NULL),
('lib_vijendra_sharma', 'Vijendra Sharma', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'LIBRARY', '1234', NULL),
('UN', 'UN Cell', '$2y$10$3H0CyXbEzpGKsnDSz2dMLuoExHhji6pmFMeue27065e2Ya8O4NOsi', 'Administrator', 'UN', '5678', NULL),
('un_denial_j', 'Denial J', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'UN', '5678', NULL),
('user_adm', 'Administration User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'ADM', '5678', NULL),
('user_cet', 'CETNAB User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'CENTANB', '1234', 'user'),
('user_cmh', 'CMHCS User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'CMHCS', '5678', NULL),
('user_cou', 'Courses User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'COURSES', '1234', NULL),
('user_cs3', 'CS3 User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'CS3', '5678', NULL),
('user_dg', 'DG Office User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'DG_SECTT', '1234', NULL),
('user_edi', 'Editorial User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'EDITORIAL', '5678', NULL),
('user_lib', 'Library User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'IT', '1234', NULL),
('user_un', 'UN Cell User', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'UN', '5678', NULL),
('USI002', 'Admin Two', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Administrator', 'USI', '5678', NULL),
('USI003', 'Ayush', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'USI', '1234', NULL),
('USI004', 'Luv', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'USI', '5678', NULL),
('USI005', 'Kaushal', 'a67d22cef2f6639d71b8901b5b2bbee4a2400d92c70e60c179c0fd76d72d6c23', 'Support Staff', 'USI', '1234', NULL),
('USI006', 'Editorial', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'USI', '1234', NULL),
('USI007', 'ANBI', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'USI', '5678', NULL),
('USI008', 'CS3', 'e606e38b0d8c19b24cf0ee3808183162ea7cd63ff7912dbb22b5e803286b4446', 'User', 'USI', '1234', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);
ALTER TABLE `users` ADD FULLTEXT KEY `Dept_name` (`Dept_name`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
