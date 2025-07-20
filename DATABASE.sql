-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 03:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ihrrs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `approval_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `approved_by` int(11) NOT NULL,
  `approval_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bhs_medicine_dispensed`
--

CREATE TABLE `bhs_medicine_dispensed` (
  `dispensed_id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `quantity_dispensed` int(11) NOT NULL,
  `dispensed_by` int(11) NOT NULL,
  `dispensed_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bhs_medicine_dispensed`
--

INSERT INTO `bhs_medicine_dispensed` (`dispensed_id`, `visit_id`, `medicine_name`, `quantity_dispensed`, `dispensed_by`, `dispensed_date`) VALUES
(1, 5, 'Paracetamol', 10, 1, '2025-03-19 03:05:00'),
(2, 5, 'Ibuprofen', 10, 1, '2025-03-19 03:05:00'),
(3, 6, 'Paracetamol', 10, 1, '2025-03-19 03:05:29'),
(4, 6, 'Ibuprofen', 10, 1, '2025-03-19 03:05:29'),
(5, 7, 'Paracetamol', 10, 1, '2025-03-19 03:06:30'),
(6, 7, 'Ibuprofen', 10, 1, '2025-03-19 03:06:30'),
(7, 11, 'Amoxicillin', 100, 1, '2025-03-19 03:21:33'),
(8, 12, 'Amoxicillin', 100, 1, '2025-03-19 03:21:48'),
(9, 13, 'Amoxicillin', 2000, 1, '2025-03-19 03:23:50'),
(10, 13, 'Paracetamol', 2000, 1, '2025-03-19 03:23:50'),
(11, 21, 'Paracetamol', 2, 1, '2025-03-19 13:58:50'),
(12, 21, 'Ibuprofen', 2, 1, '2025-03-19 13:58:50'),
(13, 21, 'Amoxicillin', 2, 1, '2025-03-19 13:58:50'),
(14, 34, 'Paracetamol', 2, 1, '2025-03-27 00:50:47'),
(15, 34, 'Ibuprofen', 1, 1, '2025-03-27 00:50:47'),
(16, 34, 'Ibuprofen', 1, 1, '2025-03-27 00:50:47'),
(17, 35, 'Paracetamol', 2, 1, '2025-03-27 00:51:58'),
(18, 35, 'Ibuprofen', 1, 1, '2025-03-27 00:51:58'),
(19, 35, 'Ibuprofen', 1, 1, '2025-03-27 00:51:58'),
(20, 36, 'Paracetamol', 2, 1, '2025-03-27 00:57:52'),
(21, 36, 'Ibuprofen', 1, 1, '2025-03-27 00:57:52'),
(22, 36, 'Ibuprofen', 1, 1, '2025-03-27 00:57:52'),
(23, 37, 'Paracetamol', 5, 1, '2025-03-27 01:56:41'),
(24, 38, 'Paracetamol', 5, 1, '2025-03-27 01:57:06'),
(25, 40, 'Paracetamol', 2, 1, '2025-04-01 02:56:00'),
(26, 41, 'Paracetamol', 1, 1, '2025-04-08 12:36:28'),
(27, 41, 'Ibuprofen', 1, 1, '2025-04-08 12:36:28'),
(28, 42, 'Paracetamol', 1, 1, '2025-04-08 12:45:39'),
(29, 42, 'Ibuprofen', 1, 1, '2025-04-08 12:45:39'),
(30, 43, 'Paracetamol', 1, 1, '2025-04-08 12:47:27'),
(31, 44, 'Ibuprofen', 2, 1, '2025-04-12 13:13:58'),
(32, 45, 'Ibuprofen', 2, 1, '2025-04-12 13:59:53'),
(33, 46, 'Paracetamol', 3, 1, '2025-04-12 14:04:57'),
(34, 47, 'Ibuprofen', 3, 8, '2025-04-14 08:16:40'),
(35, 48, 'Ibuprofen', 3, 8, '2025-04-14 08:19:38'),
(36, 49, 'Paracetamol', 50, 8, '2025-04-14 08:23:38'),
(37, 50, 'Paracetamol', 3, 8, '2025-04-15 01:01:43'),
(38, 51, 'Ibuprofen', 5, 8, '2025-04-15 01:59:41'),
(39, 52, 'Ibuprofen', 5, 8, '2025-04-15 01:59:49'),
(40, 53, 'Ibuprofen', 1, 8, '2025-04-15 02:01:22'),
(41, 54, 'Ibuprofen', 66, 8, '2025-04-15 02:05:39'),
(42, 55, 'Paracetamol', 3, 8, '2025-04-15 03:11:11'),
(43, 56, 'Paracetamol', 5, 8, '2025-04-17 01:24:45'),
(44, 57, 'Paracetamol', 5, 8, '2025-04-18 06:44:54'),
(45, 58, 'Paracetamol', 4, 8, '2025-04-18 06:53:54'),
(46, 59, 'Paracetamol', 3, 8, '2025-04-19 06:25:31'),
(47, 60, 'Paracetamol', 3, 8, '2025-04-19 06:30:19'),
(48, 61, 'Ibuprofen', 3, 8, '2025-04-19 06:32:27'),
(49, 62, 'Ibuprofen', 4, 8, '2025-04-24 10:24:33'),
(50, 63, 'Paracetamol', 2, 8, '2025-04-25 00:24:42'),
(51, 66, 'Ibuprofen', 1, 10, '2025-04-27 14:40:34'),
(52, 67, 'Ibuprofen', 2, 8, '2025-04-29 02:09:30'),
(53, 68, 'Paracetamol', 2, 8, '2025-04-29 06:30:39'),
(54, 69, 'Paracetamol', 2, 8, '2025-04-29 06:33:09'),
(55, 70, 'Amoxicillin', 3, 8, '2025-04-29 06:50:59'),
(56, 71, 'Ibuprofen', 1, 8, '2025-04-29 06:59:11'),
(57, 72, 'Ibuprofen', 1, 8, '2025-04-29 07:00:17'),
(58, 73, 'Paracetamol', 3, 8, '2025-05-01 07:00:49'),
(59, 74, 'Antibiotic (60mg)', 7, 8, '2025-05-01 07:18:07'),
(60, 75, 'Antibiotic (60mg)', 7, 8, '2025-05-01 07:26:58'),
(61, 76, 'Amoxicillin', 30, 8, '2025-05-01 07:28:38'),
(62, 77, 'Cezar', 5, 8, '2025-05-03 05:04:07'),
(63, 78, 'Paracetamol', 4, 8, '2025-05-03 08:12:54'),
(64, 80, 'Ibuprofen', 4, 8, '2025-05-03 09:58:28');

-- --------------------------------------------------------

--
-- Table structure for table `bhs_visits`
--

CREATE TABLE `bhs_visits` (
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `bhw_id` int(11) NOT NULL,
  `visit_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `chief_complaints` text DEFAULT NULL,
  `referred_to_rhu` tinyint(1) DEFAULT 0,
  `bmi` decimal(5,1) NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `chest_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `patient_alert` text NOT NULL,
  `remarks` text DEFAULT NULL,
  `treatment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bhs_visits`
--

INSERT INTO `bhs_visits` (`visit_id`, `patient_id`, `bhw_id`, `visit_date`, `blood_pressure`, `temperature`, `chief_complaints`, `referred_to_rhu`, `bmi`, `weight`, `height`, `chest_rate`, `respiratory_rate`, `patient_alert`, `remarks`, `treatment`) VALUES
(1, 1, 1, '2025-03-19 03:00:40', '170', 36.0, 'cezar', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(2, 1, 1, '2025-03-19 03:01:44', '170', 36.0, 'cezar', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(3, 2, 1, '2025-03-19 03:02:17', '170', 36.0, 'cezar', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(4, 3, 1, '2025-03-19 03:03:29', '170', 36.0, 'cezar', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(5, 4, 1, '2025-03-19 03:05:00', '1', 1.0, 'cezar3', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(6, 4, 1, '2025-03-19 03:05:29', '1', 1.0, 'cezar3', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(7, 5, 1, '2025-03-19 03:06:30', '1', 1.0, 'cezar3', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(8, 4, 1, '2025-03-19 03:15:32', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(9, 4, 1, '2025-03-19 03:16:11', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(10, 6, 1, '2025-03-19 03:16:35', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(11, 7, 1, '2025-03-19 03:21:33', '1', 1.0, 'cezar5', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(12, 8, 1, '2025-03-19 03:21:48', '1', 1.0, 'cezar5', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(13, 9, 1, '2025-03-19 03:23:50', '1', 1.0, 'cezar7', 0, 0.0, 56.00, 1.00, 122, 11, '', 'cezar', ''),
(14, 10, 1, '2025-03-19 03:25:40', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(15, 11, 1, '2025-03-19 03:26:27', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(16, 12, 1, '2025-03-19 03:40:45', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(17, 13, 1, '2025-03-19 03:41:34', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(18, 14, 1, '2025-03-19 03:42:04', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(19, 13, 1, '2025-03-19 03:42:28', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(20, 9, 1, '2025-03-19 09:47:57', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(21, 16, 1, '2025-03-19 13:58:50', '1', 1.0, '', 0, 0.0, 89.00, 89.00, 11, 11, '', '', ''),
(22, 18, 1, '2025-03-20 09:09:29', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(23, 19, 1, '2025-03-20 09:10:36', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(24, 18, 1, '2025-03-20 09:12:50', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(25, 18, 1, '2025-03-20 09:13:27', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(26, 18, 1, '2025-03-20 09:13:45', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(27, 20, 1, '2025-03-20 09:15:05', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(28, 20, 1, '2025-03-20 09:15:47', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(29, 21, 1, '2025-03-20 09:16:05', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(30, 22, 1, '2025-03-20 09:17:07', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(31, 22, 1, '2025-03-20 09:17:26', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(32, 23, 1, '2025-03-20 09:18:02', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(33, 25, 1, '2025-03-22 04:39:10', '1', 1.0, '', 0, 0.0, 56.00, 1.00, 122, 11, '', '', ''),
(34, 26, 1, '2025-03-27 00:50:47', '1', 1.0, 'headache, fever', 0, 0.0, 56.00, 170.00, 122, 11, '', 'done', ''),
(35, 27, 1, '2025-03-27 00:51:58', '1', 1.0, 'headache, fever', 0, 0.0, 56.00, 170.00, 122, 11, '', 'done', ''),
(36, 27, 1, '2025-03-27 00:57:52', '1', 1.0, 'headache, fever', 0, 0.0, 56.00, 170.00, 122, 11, '', 'done', ''),
(37, 29, 1, '2025-03-27 01:56:41', '1', 1.0, '', 0, 0.0, 11.00, 170.00, 11, 11, '', '', ''),
(38, 29, 1, '2025-03-27 01:57:06', '1', 1.0, '', 0, 0.0, 11.00, 170.00, 11, 11, '', '', ''),
(39, 30, 1, '2025-03-27 02:06:21', '1', 1.0, 'Headache, fever', 0, 0.0, 56.00, 176.00, 11, 1, '', '', ''),
(40, 31, 1, '2025-02-23 16:00:00', '11', 10.0, 'april36', 0, 0.0, 1.00, 10.00, 10, 10, 'aaaa', '1111', ''),
(41, 1, 1, '2025-04-05 16:00:00', '1', 1.0, '11', 0, 0.0, 1.00, 1.00, 1, 1, 'N/A', '11', ''),
(42, 1, 1, '2025-04-08 12:45:39', '1', 1.0, '1', 0, 0.0, 1.00, 1.00, 1, 1, '', '111', ''),
(43, 1, 1, '2025-04-06 16:00:00', '1', 1.0, '1', 0, 0.0, 1.00, 1.00, 0, 1, '', '111', ''),
(44, 2, 1, '2025-04-12 13:13:58', '1', 11.0, '1111', 0, 0.0, 11.00, 111.00, 11, 11, '', '111', ''),
(45, 4, 1, '2025-04-12 13:59:53', '1', 1.0, '2222', 0, 0.0, 22.00, 22.00, 22, 222, '', '22', ''),
(46, 4, 1, '2025-04-12 14:04:57', '333', 33.0, '333', 0, 0.0, 333.00, 333.00, 33, 33, '', '33333', ''),
(47, 32, 8, '2025-04-14 08:16:40', '1', 1.0, 'april25555555', 0, 0.0, 1.00, 10.00, 10, 10, '', 'qqqqqqqqqqq', ''),
(48, 32, 8, '2025-04-14 08:19:38', '1', 1.0, 'april25555555', 0, 0.0, 1.00, 10.00, 10, 10, '', 'qqqqqqqqqqq', ''),
(49, 33, 8, '2025-04-14 08:23:38', '1', 1.0, 'aprilllllllllllllllllllllllllllllllll', 0, 0.0, 1.00, 10.00, 10, 10, '', '11111111111111111', ''),
(50, 34, 8, '2025-04-15 01:01:43', '1', 1.0, 'TESTTTT', 0, 0.0, 1.00, 10.00, 10, 10, '', '1111qq', ''),
(51, 35, 8, '2025-04-15 01:59:41', '1', 1.0, 'sssss', 0, 0.0, 11.00, 11.00, 11, 11, '', '1111', ''),
(52, 35, 8, '2025-04-15 01:59:49', '1', 1.0, 'sssss', 0, 0.0, 11.00, 11.00, 11, 11, '', '1111', ''),
(53, 36, 8, '2025-04-15 02:01:22', '1', 1.0, 'ggggggggggggggg', 0, 0.0, 999.99, 999.99, 11, 222, '', '1111', ''),
(54, 12, 8, '2025-04-15 02:05:39', '1', 1.0, 'referral testing', 0, 0.0, 66.00, 66.00, 66, 66, '', '66', ''),
(55, 37, 8, '2025-04-15 03:11:11', '1', 1.0, 'headache', 0, 0.0, 47.00, 156.00, 67, 11, '', 'FAFAFAFAFFAFA', ''),
(56, 32, 8, '2025-04-17 01:24:45', '1', 1.0, 'Testingg', 0, 0.0, 111.00, 11.00, 11, 1, '', '1111111', ''),
(57, 2, 8, '2025-04-18 06:44:54', '12', 12.0, 'testing', 0, 0.0, 22.00, 22.00, 22, 22, '', '222', ''),
(58, 10, 8, '2025-04-18 06:53:54', '1', 1.0, 'qqqqq', 0, 0.0, 1.00, 1.00, 1, 1, '', '1111', ''),
(59, 38, 8, '2025-04-19 06:25:31', '1', 1.0, 'qqqqq', 0, 0.0, 111.00, 11.00, 11, 1, 'Allergic Reactions', '111', ''),
(60, 39, 8, '2025-04-19 06:30:19', '1', 1.0, 'qqqqq', 0, 0.0, 111.00, 11.00, 11, 1, 'Allergic Reactions', '111', ''),
(61, 40, 8, '2025-04-16 16:00:00', '111', 1.0, 'testing', 0, 0.0, 111.00, 11.00, 11, 1, 'Allergic Reactions', '222', ''),
(62, 40, 8, '2025-04-24 10:24:33', '1', 1.0, 'headaceee chilllssss', 0, 0.0, 12.00, 12.00, 12, 12, '', '111111test111111', ''),
(63, 1, 8, '2025-04-25 00:24:42', '1', 1.0, 'qqq', 0, 0.0, 11.00, 11.00, 1, 11, '', '11', ''),
(64, 41, 8, '2025-04-25 02:57:24', '120', 1.0, 'Headache, nagtatae 5 days', 0, 0.0, 45.00, 155.00, 11, 1, 'Allergic Reactions', 'aaaaaa', ''),
(65, 43, 8, '2025-04-27 01:17:36', '120/80', 39.3, 'nagtatae for 10 days, headache, chills, fever', 0, 23.4, 60.00, 160.00, 0, 12, 'Impairment', 'Patient has hearing problem', ''),
(66, 44, 10, '2025-04-27 14:40:34', '120', 39.3, 'headaceee chilllssss', 0, 18.7, 45.00, 155.00, 0, 12, 'Deaf', '111111test111111', ''),
(67, 2, 8, '2025-04-29 02:09:30', '1', 1.0, '1111', 0, 0.0, 11.00, 11.00, 1, 1, '', '111', ''),
(68, 45, 8, '2025-04-29 06:30:39', '120', 39.3, 'qqqq', 0, 18.7, 45.00, 155.00, 0, 12, 'Allergic Reactions', 'qqq', ''),
(69, 46, 8, '2025-04-29 06:33:09', '4', 4.0, 'Santos', 0, 271.6, 55.00, 45.00, 0, 0, 'Santos', '2222', ''),
(70, 47, 8, '2025-04-29 06:50:59', '1', 1.0, '111', 0, 16.7, 35.00, 145.00, 0, 0, '', '111', ''),
(71, 48, 8, '2025-04-29 06:59:11', '11', 11.0, 'headaceee chilllssss', 0, 82.6, 1.00, 11.00, 0, 0, '', '111111test111111', ''),
(72, 50, 8, '2025-04-29 07:00:17', '11', 11.0, 'headaceee chilllssss', 0, 82.6, 1.00, 11.00, 0, 0, '', '111111test111111', ''),
(73, 51, 8, '2025-05-01 07:00:49', '1', 1.0, 'dana', 0, 9999.9, 11.00, 1.00, 0, 0, '', 'dana', 'BP only'),
(74, 52, 8, '2025-05-01 07:18:07', '1', 1.0, '1111', 0, 9999.9, 11.00, 1.00, 0, 1, 'Impairment', '111', 'Immunization'),
(75, 54, 8, '2025-05-01 07:26:58', '1', 1.0, '1111a', 0, 909.1, 11.00, 11.00, 0, 1, 'Impairment', '111', 'Immunization'),
(76, 55, 8, '2025-05-01 07:28:38', '1', 1.0, '2222', 0, 909.1, 11.00, 11.00, 0, 1, '', '111', 'Prenatal'),
(77, 1, 8, '2025-04-30 16:00:00', '23', 32.0, 'test', 0, 14.5, 22.00, 123.00, 0, 0, 'Cezar', 'Cezarwwww', 'Cezar'),
(78, 19, 8, '2025-05-03 08:12:54', '11', 12.0, 'bvbvbvbv', 0, 368.0, 23.00, 25.00, NULL, NULL, '', 'mmmmmmm', 'Prenatal'),
(79, 21, 8, '2025-05-03 08:14:06', '22', 22.0, 'tesst2', 0, 1.3, 2.00, 123.00, NULL, NULL, 'Impairment', '2222', 'Immunization'),
(80, 56, 8, '2025-05-02 16:00:00', '1', 12.0, 'qqqq', 0, 8.1, 12.00, 122.00, 0, 0, 'Santos', '1111', ''),
(81, 57, 8, '2025-05-02 16:00:00', '1', 1.0, 'dana', 0, 9999.9, 11.00, 1.00, 0, 1, '', 'dana', ''),
(82, 58, 8, '2025-05-02 16:00:00', '1', 1.0, 'dana', 0, 9999.9, 11.00, 1.00, 0, 1, '', 'dana', ''),
(83, 59, 8, '2025-05-03 12:00:26', '23', 32.0, '1111a', 0, 1818.2, 22.00, 11.00, 0, 1, '', '111', ''),
(84, 60, 8, '2025-05-03 12:03:15', '23', 32.0, '2222', 0, 1818.2, 22.00, 11.00, 0, 1, '', '111', ''),
(85, 1, 8, '2025-05-02 16:00:00', '1', 1.0, 'assssssss', 0, 909.1, 11.00, 11.00, NULL, NULL, '', '1', ''),
(86, 61, 8, '2025-05-02 16:00:00', '23', 32.0, 'test', 0, 352.0, 22.00, 25.00, 0, 1, '', 'Cezar', ''),
(87, 62, 8, '2025-04-06 16:00:00', '23', 32.0, 'testing the referral update number 333', 0, 352.0, 22.00, 25.00, 0, 1, '', 'Cezarzzzzz1111112222334444555566667788999100111234566666', '');

-- --------------------------------------------------------

--
-- Table structure for table `custom_options`
--

CREATE TABLE `custom_options` (
  `id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_options`
--

INSERT INTO `custom_options` (`id`, `category`, `value`) VALUES
(1, 'religion', 'Born Again'),
(2, 'address', 'testtttt'),
(4, 'patient_alert', 'Deaf'),
(5, 'religion', 'Testt'),
(6, 'diagnosis', 'Stroke'),
(7, 'diagnosis', 'Colon Cancer'),
(8, 'medicine', 'Antibiotic (60mg)'),
(9, 'Barangay 1', 'Purok 1 - Barangay 1 Daet, Camarines Norte'),
(10, 'Barangay 1', 'Purok 2 - Barangay 1 Daet, Camarines Norte'),
(11, 'Barangay 1', 'Purok 3 - Barangay 1 Daet, Camarines Norte'),
(12, 'Barangay 1', 'Purok 4 - Barangay 1 Daet, Camarines Norte'),
(13, 'Barangay 1', 'Purok 5 - Barangay 1 Daet, Camarines Norte'),
(14, 'Barangay 1', 'Purok 6 - Barangay 1 Daet, Camarines Norte'),
(15, 'Barangay 1', 'Purok 7 - Barangay 1 Daet, Camarines Norte'),
(16, 'Barangay 1', 'Purok 8 - Barangay 1 Daet, Camarines Norte'),
(17, 'Barangay 6', 'Purok 1 - Barangay 6 Daet, Camarines Norte'),
(18, 'Barangay 6', 'Purok 2 - Barangay 6 Daet, Camarines Norte'),
(19, 'Barangay 6', 'Purok 3 - Barangay 6 Daet, Camarines Norte'),
(20, 'Barangay 6', 'Purok 4 - Barangay 6 Daet, Camarines Norte'),
(21, 'Barangay 6', 'Purok 5 - Barangay 6 Daet, Camarines Norte'),
(22, 'Barangay 7', 'Purok 1 - Barangay 7 Daet, Camarines Norte'),
(23, 'Barangay 7', 'Purok 2 - Barangay 7 Daet, Camarines Norte'),
(24, 'Barangay 7', 'Purok 3 - Barangay 7 Daet, Camarines Norte'),
(25, 'Barangay 7', 'Purok 4 - Barangay 7 Daet, Camarines Norte'),
(26, 'Barangay 7', 'Purok 5 - Barangay 7 Daet, Camarines Norte'),
(27, 'Barangay 7', 'Purok 6 - Barangay 7 Daet, Camarines Norte'),
(28, 'Barangay 7', 'Purok 7 - Barangay 7 Daet, Camarines Norte'),
(29, 'Barangay 8', 'Purok 1 - Barangay 8 Daet, Camarines Norte'),
(30, 'Barangay 8', 'Purok 2 - Barangay 8 Daet, Camarines Norte'),
(31, 'Barangay 8', 'Purok 3 - Barangay 8 Daet, Camarines Norte'),
(32, 'Barangay 8', 'Purok 4 - Barangay 8 Daet, Camarines Norte'),
(33, 'Barangay 8', 'Purok 5 - Barangay 8 Daet, Camarines Norte'),
(34, 'Barangay 8', 'Purok 6 - Barangay 8 Daet, Camarines Norte'),
(35, 'Barangay 8', 'Purok 7 - Barangay 8 Daet, Camarines Norte'),
(36, 'Barangay 8', 'Purok 8 - Barangay 8 Daet, Camarines Norte'),
(37, 'Barangay 8', 'Purok 9 - Barangay 8 Daet, Camarines Norte'),
(38, 'Barangay 8', 'Purok 10 - Barangay 8 Daet, Camarines Norte'),
(39, 'Barangay Gubat', 'Purok 1 - Barangay Gubat Daet, Camarines Norte'),
(40, 'Barangay Gubat', 'Purok 2 - Barangay Gubat Daet, Camarines Norte'),
(41, 'Barangay Gubat', 'Purok 3 - Barangay Gubat Daet, Camarines Norte'),
(42, 'Barangay Gubat', 'Purok 4 - Barangay Gubat Daet, Camarines Norte'),
(43, 'Barangay Gubat', 'Purok 5 - Barangay Gubat Daet, Camarines Norte'),
(44, 'Barangay San Isidro', 'Purok 1 - Barangay San Isidro Daet, Camarines Norte'),
(45, 'Barangay San Isidro', 'Purok 2 - Barangay San Isidro Daet, Camarines Norte'),
(46, 'Barangay San Isidro', 'Purok 3 - Barangay San Isidro Daet, Camarines Norte'),
(47, 'Barangay San Isidro', 'Purok 4 - Barangay San Isidro Daet, Camarines Norte'),
(48, 'Barangay San Isidro', 'Purok 5 - Barangay San Isidro Daet, Camarines Norte'),
(49, 'Barangay San Isidro', 'Purok 6 - Barangay San Isidro Daet, Camarines Norte'),
(50, 'Barangay Cobangbang', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte'),
(51, 'Barangay Cobangbang', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte'),
(52, 'Barangay Cobangbang', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte'),
(53, 'Barangay Cobangbang', 'Purok 4 - Barangay Cobangbang Daet, Camarines Norte'),
(54, 'Barangay Cobangbang', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte'),
(55, 'Barangay Cobangbang', 'Purok 6 - Barangay Cobangbang Daet, Camarines Norte'),
(56, 'Barangay Cobangbang', 'Purok 7 - Barangay Cobangbang Daet, Camarines Norte'),
(57, 'Barangay Cobangbang', 'Purok 8 - Barangay Cobangbang Daet, Camarines Norte'),
(58, 'Barangay Bagasbas', 'Purok 1 - Barangay Bagasbas Daet, Camarines Norte'),
(59, 'Barangay Bagasbas', 'Purok 2 - Barangay Bagasbas Daet, Camarines Norte'),
(60, 'Barangay Bagasbas', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte'),
(61, 'Barangay Bagasbas', 'Purok 4 - Barangay Bagasbas Daet, Camarines Norte'),
(62, 'Barangay Bagasbas', 'Purok 5 - Barangay Bagasbas Daet, Camarines Norte'),
(63, 'Barangay Bagasbas', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte'),
(64, 'Barangay Mambalite', 'Purok 1 - Barangay Mambalite Daet, Camarines Norte'),
(65, 'Barangay Mambalite', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte'),
(66, 'Barangay Mambalite', 'Purok 3 - Barangay Mambalite Daet, Camarines Norte'),
(67, 'Barangay Mambalite', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte'),
(68, 'Barangay Mambalite', 'Purok 5 - Barangay Mambalite Daet, Camarines Norte'),
(69, 'Barangay Mambalite', 'Purok 6 - Barangay Mambalite Daet, Camarines Norte'),
(70, 'Barangay Mambalite', 'Purok 7 - Barangay Mambalite Daet, Camarines Norte'),
(72, 'religion', 'Santos'),
(73, 'patient_alert', 'Santos'),
(74, 'Barangay 1', 'INC'),
(75, 'Barangay 1', 'P3 Mambalite Dact CN'),
(76, 'religion', 'TEST ONLY'),
(77, 'Barangay 1', 'TESTT'),
(78, 'treatment', 'Prenatal'),
(79, 'patient_alert', 'Cezar'),
(80, 'treatment', 'Cezar'),
(81, 'medicine', 'Cezar'),
(82, 'extension', 'VII'),
(83, 'Barangay 1', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `forgot_password_requests`
--

CREATE TABLE `forgot_password_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending',
  `request_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `performed_by` int(11) DEFAULT NULL,
  `user_affected` int(11) DEFAULT NULL,
  `suspicious_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `timestamp`, `performed_by`, `user_affected`, `suspicious_flag`) VALUES
(1, 2, 'login_failed', '2025-03-15 05:23:39', NULL, NULL, 0),
(2, 2, 'login_failed', '2025-03-15 05:24:17', NULL, NULL, 0),
(3, 3, 'login_success', '2025-03-15 05:25:45', 10, NULL, 0),
(4, 4, 'login_failed', '2025-03-15 05:27:42', NULL, NULL, 0),
(5, 2, 'login_success', '2025-03-15 05:27:49', 8, NULL, 0),
(6, 2, 'login_success', '2025-03-15 07:02:16', 7, NULL, 0),
(7, 3, 'login_success', '2025-03-15 07:06:06', 7, NULL, 0),
(8, 4, 'login_failed', '2025-03-16 00:00:26', NULL, NULL, 0),
(9, 5, 'login_success', '2025-03-16 00:00:39', 7, NULL, 0),
(10, 5, 'login_failed', '2025-03-16 00:20:32', NULL, NULL, 0),
(11, 7, 'login_success', '2025-03-16 00:20:38', 7, NULL, 0),
(12, 7, 'login_success', '2025-03-16 00:24:28', 7, NULL, 0),
(13, 6, 'login_failed', '2025-03-16 00:25:20', NULL, NULL, 0),
(14, 7, 'login_success', '2025-03-16 00:25:26', 7, NULL, 0),
(15, 6, 'Deleted user: Administrator', '2025-03-16 00:32:46', 7, 6, 0),
(16, 6, 'Reset password for Admin User', '2025-03-16 00:36:21', 7, 7, 0),
(17, NULL, 'Successful Login', '2025-04-14 07:38:25', 8, NULL, 0),
(18, NULL, 'Successful Login', '2025-04-14 08:15:46', 8, NULL, 0),
(19, NULL, 'Successful Login', '2025-04-15 01:54:34', 8, NULL, 0),
(20, NULL, 'User Logged Out', '2025-04-15 02:13:34', 8, NULL, 0),
(21, NULL, 'Successful Login', '2025-04-15 02:13:47', 8, NULL, 0),
(22, NULL, 'Successful Login', '2025-04-15 03:03:11', 8, NULL, 0),
(23, NULL, 'Successful Login', '2025-04-18 06:44:07', 8, NULL, 0),
(24, NULL, 'Successful Login', '2025-04-21 14:19:05', 8, NULL, 0),
(25, NULL, 'Successful Login', '2025-04-22 06:46:04', 8, NULL, 0),
(26, NULL, 'Successful Login', '2025-04-24 10:23:42', 8, NULL, 0),
(27, NULL, 'Successful Login', '2025-04-25 02:27:21', 8, NULL, 0),
(28, NULL, 'Failed Login (Unauthorized Role)', '2025-04-25 02:58:57', 8, NULL, 0),
(29, NULL, 'Successful Login', '2025-04-25 02:59:04', 10, NULL, 0),
(30, NULL, 'Successful Login', '2025-04-25 13:16:54', 8, NULL, 0),
(31, NULL, 'Successful Login', '2025-04-27 01:12:16', 8, NULL, 0),
(32, NULL, 'Successful Login', '2025-04-27 01:20:30', 10, NULL, 0),
(33, NULL, 'Successful Login', '2025-04-27 14:49:06', 8, NULL, 0),
(34, NULL, 'Successful Login', '2025-04-27 15:16:26', 10, NULL, 0),
(35, NULL, 'User Logged Out', '2025-04-27 15:16:41', 10, NULL, 0),
(36, NULL, 'Successful Login', '2025-04-27 15:24:59', 10, NULL, 0),
(37, NULL, 'Successful Login', '2025-04-28 10:49:36', 10, NULL, 0),
(38, NULL, 'Successful Login', '2025-04-28 10:50:02', 8, NULL, 0),
(39, NULL, 'Successful Login', '2025-04-29 04:16:25', 8, NULL, 0),
(40, NULL, 'Successful Login', '2025-04-29 06:20:52', 8, NULL, 0),
(41, NULL, 'Successful Login', '2025-05-01 06:45:59', 8, NULL, 0),
(42, NULL, 'User Logged Out', '2025-05-02 12:17:45', 8, NULL, 0),
(43, NULL, 'Successful Login', '2025-05-03 03:04:26', 8, NULL, 0),
(44, NULL, 'Successful Login', '2025-05-03 05:55:20', 8, NULL, 0),
(45, NULL, 'Successful Login', '2025-05-03 08:48:49', 8, NULL, 0),
(46, NULL, 'Successful Login', '2025-05-04 04:00:18', 8, NULL, 0),
(47, NULL, 'User Logged Out', '2025-05-04 06:40:06', 8, NULL, 0),
(48, NULL, 'User Logged Out', '2025-05-04 06:41:06', 8, NULL, 0),
(49, NULL, 'User Logged Out', '2025-05-04 06:41:08', 8, NULL, 0),
(50, NULL, 'User Logged Out', '2025-05-05 03:36:03', 8, NULL, 0),
(51, NULL, 'Failed Login (Unauthorized Role)', '2025-05-05 03:37:09', 8, NULL, 0),
(52, NULL, 'Successful Login', '2025-05-05 03:37:20', 8, NULL, 0),
(53, NULL, 'Successful Login', '2025-05-05 03:37:38', 8, NULL, 0),
(54, NULL, 'Successful Login', '2025-05-05 03:37:42', 8, NULL, 0),
(55, NULL, 'Successful Login', '2025-05-05 03:42:14', 8, NULL, 0),
(56, NULL, 'Successful Login', '2025-05-05 03:43:24', 8, NULL, 0),
(57, NULL, 'Successful Login', '2025-05-05 04:18:31', 8, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `extension` text NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(3) NOT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `address` text NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `family_serial_no` int(10) NOT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `birthplace` varchar(255) DEFAULT NULL,
  `educational_attainment` varchar(255) DEFAULT NULL,
  `birth_weight` decimal(10,0) NOT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `philhealth_member_no` varchar(50) DEFAULT NULL,
  `fourps_status` enum('Yes','No') DEFAULT NULL,
  `category` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `first_name`, `middle_name`, `last_name`, `extension`, `date_of_birth`, `age`, `sex`, `address`, `contact_number`, `family_serial_no`, `civil_status`, `birthplace`, `educational_attainment`, `birth_weight`, `occupation`, `religion`, `philhealth_member_no`, `fourps_status`, `category`) VALUES
(1, 'Testing', 'Testing test', 'Cezar', 'Jr.', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Islam', '1111', '', ''),
(2, 'Testing', 'Test', 'Cezar2', '', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Islam', '1111', '', ''),
(3, 'Test', 'Test', 'Cezar2', '', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Islam', '1111', '', ''),
(4, 'Test', 'Test', 'Cezar3', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(5, 'Test', 'Test', 'Cezar3', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(6, 'Test', 'Test', 'Cezar4', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(7, 'Test', 'Test', 'cezar5', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(8, 'Test', 'Test', 'cezar6', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(9, 'Test', 'Test', 'cezar7', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(10, 'Test', 'Test', 'cezar8', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(11, 'Test', 'Test', 'cezar9', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(12, 'Testing', 'Test', 'cezar10', '', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(13, 'Test', 'Test', 'cezar11', '', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(14, 'Test', 'Test', 'cezar11', '', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(16, 'Test', 'Test', 'Santos', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(18, 'Test', 'Test', '2cezar', '', '2017-01-19', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(19, 'Test', 'Test', '2cezar', '', '2017-01-19', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(20, 'Test', 'Test', '3cezar', '', '2017-01-19', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(21, 'Test', 'Test', '3cezar', '', '2017-01-19', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(22, 'Test', 'Test', '4cezar', '', '2017-01-19', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(23, 'Test', 'Test', '4cezar', '', '2017-01-19', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(25, 'Test', 'Test', 'Referral', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(26, 'Test', 'Test', 'Cezar', '', '2017-01-30', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', 'No', ''),
(27, 'Test', 'Test', 'Cezar2', '', '2017-01-30', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', 'No', ''),
(29, 'Test', 'Test', 'Cezar20', '', '2017-02-05', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', '', ''),
(30, 'Test', 'Test', 'Hillary', '', '2017-02-07', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', '', ''),
(31, 'April Mae', 'Test', 'April1', '', '2017-01-17', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Married', 'mmm', 'Primary Education', 0, 'test', 'Roman Catholic', '1111', '', ''),
(32, 'Test', 'Test', 'CEZARRR', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Married', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', 'Yes', ''),
(33, 'Test', 'Test', 'Razecc', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', 'Yes', ''),
(34, 'Test', 'Test', 'Razecccccc', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 0, 'test', 'Aglipayan', '1111', '', ''),
(35, 'Test', 'Test', 'qwertqwertssssss', '', '2017-02-08', 0, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Married', 'mmm', '', 0, 'test', 'Aglipayan', '1111', '', ''),
(36, 'Test', 'Test', 'Trisha', '', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Married', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', '', ''),
(37, 'Test', 'Test', 'Palomares', '', '2017-01-30', 8, 'Female', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111111111111111', 1111, 'Single', 'mmm', '', 0, 'test', 'Roman Catholic', '1111', 'Yes', ''),
(38, 'Test', 'Test', 'CCezar', 'Jr.', '2017-02-01', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 111, 'test', '', '1111', '', ''),
(39, 'Test', 'Test', 'CCCezar', 'Jr.', '2017-02-01', 8, 'Male', 'Purok 1- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', '', 111, 'test', '', '1111', '', ''),
(40, 'Test', 'Test', 'CCCCezar', 'Jr.', '2017-02-08', 0, 'Male', 'Purok 2- Barangay 1 Daet, Camarines Norte', '111', 1111, 'Single', 'mmm', 'Secondary Education', 111, 'test', 'Aglipayan', '1111', '', 'NHTS'),
(41, 'Trisha', 'Santos', 'Palomares', '', '2017-02-08', 0, 'Female', 'Purok 1- Barangay 1 Daet, Camarines Norte', '', 1111, 'Single', 'mmm', '', 0, 'test', '', '1111', '', ''),
(43, 'John', 'Paulino', 'Gallebo', 'Jr.', '2000-02-07', 25, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '0930407234', 38369210, 'Single', 'Daet, Camarines Norte', 'Primary Education', 45, 'Construction Worker', 'Iglesia ni Cristo', '', 'Yes', 'NHTS'),
(44, 'Sean', 'Del Rio', 'Melendez', 'V', '2009-03-08', 16, 'Male', 'Purok 7, Daet Camarines Norte', '09304072341', 1111, 'Single', 'Daet, Camarines Norte', 'Primary Education', 45, 'Construction Worker', 'Testt', '1111', '', 'Self-Employed'),
(45, 'Sara', 'Romero', 'Santos', 'Jr.', '2017-02-02', 8, 'Male', 'P3 Barangay Mambalite', '09304072341', 1111, 'Married', '11', 'Primary Education', 45, 'Construction Worker', 'Santos', '1111', '', ''),
(46, 'aa', 'a', 'aaasssssaaaa', '', '2017-01-04', 8, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09304072341', 1111, 'Married', 'aaa', '', 0, '', '', '1111', '', ''),
(47, 'Lorde', 'Lorde', 'Lorde', '', '2017-01-31', 8, 'Male', 'P3 Mambalite Dact CN', '', 1111, 'Widowed', 'Lorde', '', 0, '', 'INC', '1111', '', ''),
(48, 'Sarah', 'Del Rio', 'Melendez', '', '2017-01-11', 8, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Married', 'Daet, Camarines Norte', '', 0, '', 'TEST ONLY', '1111', '', ''),
(50, 'Sarah', 'Del Rio', 'Mar', '', '2017-01-11', 0, 'Male', 'TESTT', '', 1111, 'Married', 'Daet, Camarines Norte', '', 1, '', '', '1111', '', ''),
(51, 'dana', 'dana', 'dana', '', '2017-02-08', 0, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Married', 'dana', '', 0, '', '', '1111', '', ''),
(52, 'dana', 'dana', 'danaaa', '', '2017-01-06', 8, 'Male', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Married', 'dana', '', 1, '', '', '1111', '', ''),
(54, 'danaa', 'danaa', 'daaaanaaa', '', '2017-01-06', 0, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Separated', 'dana', '', 1, '', '', '1111', '', ''),
(55, 'danaa', 'danaa', 'daaaanaaa222', '', '2017-02-08', 0, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Widowed', 'dana', '', 1, '', '', '1111', '', ''),
(56, 'CCCCC', 'CCCCC', 'CCCCC', 'V', '2025-01-27', 0, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09090090991', 1111, 'Single', 'CCCCC', 'Tertiary Education', 0, 'Construction Worker', 'Assemblies of God', '1111', '', ''),
(57, 'dana', 'dana', 'dana', '', '2025-05-01', 0, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Married', 'dana', '', 1, '', '', '1111', '', ''),
(58, 'dana', 'dana', 'Mdana', '', '2025-03-31', 0, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '', 1111, 'Married', 'dana', '', 1, '', '', '1111', '', ''),
(59, 'xxxx', 'aaaa', 'xxxx', '', '2025-03-13', 0, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09304072341', 1111, 'Married', 'qqqqq', '', 1, 'Construction Worker', '', '1111', '', ''),
(60, 'xxxx', 'aaaa', 'xxxx', '', '2022-02-01', 3, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09304072341', 1111, 'Married', 'mmm', '', 1, 'Construction Worker', '', '1111', '', ''),
(61, 'Joel', 'CCCCC', 'Billie', '', '2025-02-05', 0, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09090090991', 1111, 'Married', 'CCCCC', '', 1, 'Construction Worker', '', '1111', '', ''),
(62, 'CCCCC22', 'CCCCC22', 'CCCCC222', 'Jr.', '2025-02-12', 0, 'Male', 'test', '09090090991', 1111, 'Married', '', '', 1, 'Construction Worker', '', '1111', '', 'NHTS');

-- --------------------------------------------------------

--
-- Table structure for table `patient_consents`
--

CREATE TABLE `patient_consents` (
  `consent_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `consent_given` varchar(50) NOT NULL,
  `consent_method` text NOT NULL,
  `consent_date` datetime DEFAULT current_timestamp(),
  `received_by_user_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `visit_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_consents`
--

INSERT INTO `patient_consents` (`consent_id`, `patient_id`, `consent_given`, `consent_method`, `consent_date`, `received_by_user_id`, `remarks`, `visit_id`) VALUES
(1, 52, '', '', '2025-05-01 15:18:07', 8, NULL, 74),
(2, 54, '', '', '2025-05-01 15:26:58', 8, NULL, 75),
(3, 55, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-01 15:28:38', 8, NULL, 76),
(4, 1, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 13:04:07', 8, NULL, 77),
(5, 19, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 16:12:54', 8, NULL, 78),
(6, 21, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 16:14:06', 8, NULL, 79),
(7, 56, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 17:58:28', 8, NULL, 80),
(8, 57, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 17:59:21', 8, NULL, 81),
(9, 58, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 18:00:25', 8, NULL, 82),
(10, 59, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 20:00:26', 8, NULL, 83),
(11, 60, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 20:03:15', 8, NULL, 84),
(12, 1, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 20:05:58', 8, NULL, 85),
(13, 61, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 20:19:31', 8, NULL, 86),
(14, 62, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-03 20:23:11', 8, NULL, 87);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visit_id` int(11) NOT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `referral_status` enum('Pending','Completed','Uncompleted','Canceled') DEFAULT 'Pending',
  `referral_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `patient_id`, `visit_id`, `referred_by`, `referral_status`, `referral_date`) VALUES
(25, 19, 23, 1, 'Completed', '2025-03-20 09:10:41'),
(26, 18, 24, 1, 'Uncompleted', '2025-03-20 09:13:00'),
(27, 18, 25, 1, 'Uncompleted', '2025-03-20 09:13:30'),
(28, 20, 27, 1, 'Uncompleted', '2025-03-20 09:15:07'),
(29, 20, 28, 1, 'Uncompleted', '2025-03-20 09:15:49'),
(30, 21, 29, 1, 'Uncompleted', '2025-03-20 09:16:08'),
(31, 25, 33, 1, 'Uncompleted', '2025-03-22 04:39:11'),
(32, 29, 38, 1, 'Uncompleted', '2025-03-27 01:57:35'),
(33, 30, 39, 1, 'Completed', '2025-03-27 02:06:22'),
(34, 31, 40, 1, 'Canceled', '2025-04-01 02:56:02'),
(40, 31, 40, NULL, 'Uncompleted', '2025-04-07 08:26:14'),
(41, 31, 40, NULL, 'Uncompleted', '2025-04-07 13:05:33'),
(42, 1, 41, 1, 'Uncompleted', '2025-04-08 12:45:39'),
(43, 1, 41, NULL, 'Uncompleted', '2025-04-09 03:26:27'),
(44, 1, 41, NULL, 'Uncompleted', '2025-04-12 03:30:24'),
(45, 2, 3, 1, 'Completed', '2025-04-12 13:13:58'),
(46, 4, 9, 1, 'Uncompleted', '2025-04-12 13:59:53'),
(47, 4, 45, 1, 'Uncompleted', '2025-04-12 14:04:57'),
(48, 32, 47, 1, 'Uncompleted', '2025-04-14 08:16:42'),
(49, 33, 49, 8, 'Uncompleted', '2025-04-14 08:23:39'),
(50, 34, 50, 8, 'Completed', '2025-04-15 01:01:45'),
(51, 35, 52, 8, 'Uncompleted', '2025-04-15 01:59:53'),
(52, 36, 53, 8, 'Completed', '2025-04-15 02:01:24'),
(53, 12, 16, 8, 'Completed', '2025-04-15 02:05:39'),
(54, 37, 55, 8, 'Completed', '2025-04-15 03:11:13'),
(55, 32, 48, 8, 'Uncompleted', '2025-04-17 01:24:45'),
(56, 2, 57, 8, 'Uncompleted', '2025-04-18 06:44:54'),
(57, 38, 59, 8, 'Uncompleted', '2025-04-19 06:25:31'),
(58, 39, 60, 8, 'Uncompleted', '2025-04-19 06:30:20'),
(59, 40, 61, 8, 'Uncompleted', '2025-04-19 06:32:28'),
(60, 40, 61, 8, 'Uncompleted', '2025-04-24 10:24:33'),
(61, 40, 61, NULL, 'Uncompleted', '2025-04-24 10:25:26'),
(62, 40, 61, NULL, 'Uncompleted', '2025-04-24 10:36:34'),
(63, 1, 43, 8, 'Uncompleted', '2025-04-25 00:24:42'),
(64, 41, 64, 8, 'Completed', '2025-04-25 02:57:25'),
(65, 43, 65, 8, 'Completed', '2025-04-27 01:17:38'),
(66, 44, 66, 10, 'Uncompleted', '2025-04-27 14:40:35'),
(67, 2, 67, 8, 'Completed', '2025-04-29 02:09:31'),
(68, 46, 69, 8, 'Uncompleted', '2025-04-29 06:33:10'),
(69, 47, 70, 8, 'Uncompleted', '2025-04-29 06:51:01'),
(70, 48, 71, 8, 'Uncompleted', '2025-04-29 06:59:12'),
(71, 50, 72, 8, 'Uncompleted', '2025-04-29 07:00:18'),
(72, 51, 73, 8, 'Uncompleted', '2025-05-01 07:00:50'),
(73, 52, 74, 8, 'Uncompleted', '2025-05-01 07:18:08'),
(74, 54, 75, 8, 'Uncompleted', '2025-05-01 07:26:59'),
(75, 55, 76, 8, 'Uncompleted', '2025-05-01 07:28:39'),
(76, 1, 63, 8, 'Completed', '2025-05-03 03:04:53'),
(77, 1, 63, 8, 'Completed', '2025-05-03 05:04:07'),
(78, 19, 23, 8, 'Completed', '2025-05-03 08:12:54'),
(79, 21, 29, 8, 'Uncompleted', '2025-05-03 08:14:06'),
(80, 1, 77, 8, 'Uncompleted', '2025-05-03 12:05:58'),
(81, 62, 87, 8, 'Uncompleted', '2025-05-03 12:23:12'),
(82, 62, 87, NULL, 'Uncompleted', '2025-05-03 13:49:43'),
(83, 62, 87, NULL, 'Uncompleted', '2025-05-03 13:58:34'),
(84, 62, 87, NULL, 'Uncompleted', '2025-05-03 14:00:15'),
(85, 62, 87, NULL, 'Uncompleted', '2025-05-03 14:01:11'),
(86, 62, 87, NULL, 'Uncompleted', '2025-05-03 14:03:33'),
(87, 62, 87, NULL, 'Uncompleted', '2025-05-03 15:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `rhu_consultations`
--

CREATE TABLE `rhu_consultations` (
  `consultation_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `consultation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `instruction_prescription` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rhu_consultations`
--

INSERT INTO `rhu_consultations` (`consultation_id`, `patient_id`, `doctor_id`, `consultation_date`, `diagnosis`, `instruction_prescription`, `follow_up_date`, `visit_id`) VALUES
(2, 9, 1, '2025-03-20 16:00:00', 'Asthma', 'sss', NULL, 20),
(3, 9, 1, '2025-03-20 16:00:00', 'Asthma', 'sss', NULL, 20),
(4, 19, 1, '2025-03-20 16:00:00', 'Tuberculosis', 'tbtbtbtbtb', NULL, 23),
(5, 19, 1, '2025-03-20 16:00:00', 'Asthma', 'zxx', NULL, 23),
(6, 19, 1, '2025-03-20 16:00:00', 'Asthma', 'zxx', NULL, 23),
(7, 19, 1, '2025-03-20 16:00:00', 'Tuberculosis', 'sssss', NULL, 23),
(8, 19, 1, '2025-03-20 16:00:00', 'Tuberculosis', 'qqqqq', NULL, 23),
(9, 19, 1, '2025-03-20 16:00:00', 'Tuberculosis', 'qqqqqqqqqqqqqqqqqqqqqq', NULL, 23),
(10, 19, 1, '2025-03-20 16:00:00', 'Asthma', 'xxxxx', NULL, 23),
(11, 19, 1, '2025-03-20 16:00:00', 'Tuberculosis', 'ssssssssss', NULL, 23),
(12, 30, 1, '2025-03-26 16:00:00', 'Asthma', 'EAWRESTDRFYTGUHJK', NULL, 39),
(13, 2, 1, '2025-04-11 16:00:00', 'Asthma', 'patient has severe abdominal pain. make sure to drink 3X a day', NULL, 3),
(14, 34, 1, '2025-04-14 16:00:00', 'Tuberculosis', 'qqq', NULL, 50),
(15, 12, 1, '2025-04-14 16:00:00', 'Tuberculosis', 'referral testing', NULL, 16),
(16, 37, 1, '2025-04-14 16:00:00', 'Tuberculosis', 'SSSSSSSSSSSSSSSSSSSSSSS', NULL, 55),
(17, 36, 1, '2025-04-14 16:00:00', 'Asthma', 'bbbb', NULL, 53),
(18, 41, 1, '2025-04-24 16:00:00', 'Tuberculosis', 'aa', NULL, 64),
(19, 43, 1, '2025-04-27 16:00:00', 'Dengue Fever', 'vv', NULL, 65),
(20, 2, 1, '2025-04-28 16:00:00', 'arfrf', 'asas', NULL, 67),
(21, 2, 1, '2025-04-28 16:00:00', 'Colon Cancer', 'Antibiotic 500mg', NULL, 67),
(22, 2, 1, '2025-04-28 16:00:00', 'Colon Cancer', 'colon cancer', NULL, 67),
(23, 2, 1, '2025-04-28 16:00:00', 'Colon Cancer', 'patient has colon cancer and needs immediate care.', NULL, 67),
(24, 1, 1, '2025-05-02 16:00:00', 'Asthma', 'zzzz', NULL, 63),
(25, 19, 1, '2025-05-02 16:00:00', 'Bronchitis', '111', NULL, 23);

-- --------------------------------------------------------

--
-- Table structure for table `rhu_medicine_dispensed`
--

CREATE TABLE `rhu_medicine_dispensed` (
  `dispensed_id` int(11) NOT NULL,
  `consultation_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `quantity_dispensed` int(11) NOT NULL,
  `dispensed_by` int(11) NOT NULL,
  `dispensed_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rhu_medicine_dispensed`
--

INSERT INTO `rhu_medicine_dispensed` (`dispensed_id`, `consultation_id`, `medicine_name`, `quantity_dispensed`, `dispensed_by`, `dispensed_date`) VALUES
(1, 2, 'Paracetamol', 2, 1, '2025-03-21 05:46:53'),
(2, 2, 'Paracetamol', 2, 1, '2025-03-21 05:46:53'),
(3, 3, 'Paracetamol', 2, 1, '2025-03-21 05:47:07'),
(4, 3, 'Paracetamol', 2, 1, '2025-03-21 05:47:07'),
(5, 4, 'Paracetamol', 2, 1, '2025-03-21 07:09:31'),
(6, 5, 'Paracetamol', 1, 1, '2025-03-21 09:32:38'),
(7, 6, 'Paracetamol', 1, 1, '2025-03-21 09:40:31'),
(8, 7, 'Paracetamol', 2, 1, '2025-03-21 09:40:46'),
(9, 8, 'Paracetamol', 2, 1, '2025-03-21 09:42:54'),
(10, 9, 'Ibuprofen', 1, 1, '2025-03-21 09:47:35'),
(11, 10, 'Ibuprofen', 30, 1, '2025-03-21 10:09:41'),
(12, 11, 'Paracetamol', 50, 1, '2025-03-21 10:11:11'),
(13, 12, 'Amoxicillin', 5, 1, '2025-03-27 02:08:29'),
(14, 12, 'Paracetamol', 2, 1, '2025-03-27 02:08:29'),
(15, 13, 'Ibuprofen', 2, 1, '2025-04-12 13:16:52'),
(16, 13, 'Ibuprofen', 2, 1, '2025-04-12 13:16:52'),
(17, 14, 'Paracetamol', 2, 1, '2025-04-15 01:04:04'),
(18, 15, 'Paracetamol', 1, 1, '2025-04-15 02:07:15'),
(19, 16, 'Amoxicillin', 3, 1, '2025-04-15 03:17:10'),
(20, 17, 'Ibuprofen', 1, 1, '2025-04-15 03:18:37'),
(21, 18, 'Ibuprofen', 3, 1, '2025-04-25 02:59:59'),
(22, 20, 'Amlodipine', 4, 1, '2025-04-29 02:11:17'),
(23, 21, 'Antibiotic 500mg', 5, 1, '2025-04-29 03:33:32'),
(24, 22, 'Antibiotic (50mg)', 6, 1, '2025-04-29 03:37:41'),
(25, 24, 'Paracetamol', 2, 1, '2025-05-03 08:08:52'),
(26, 25, 'Amoxicillin', 3, 1, '2025-05-03 08:14:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','bhw','midwife','doctor') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `barangay` enum('Barangay 1','Barangay 6','Barangay 7','Barangay 8','Gubat','San Isidro','Cobangbang','Bagasbas','Manbalite') NOT NULL,
  `address` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `account_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `rhu` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `password_hash`, `role`, `status`, `barangay`, `address`, `age`, `contact_number`, `account_status`, `registration_date`, `rhu`, `profile_image`) VALUES
(1, 'Cezar', 'Bernandino', 'cezarcezar', 'bhw', 'rejected', 'Barangay 6', 'Barangay 6', 19, '0920292022', 'active', '2025-03-27 00:55:10', NULL, NULL),
(2, 'Admin User', 'admin2', '$2y$10$e/INcdCqDAYkf002FQxyhuh2u0gJmFmb5A3/.AAw/Pvw5RQmSN0W.', 'admin', 'approved', 'Barangay 1', '', 0, '', 'active', '2025-03-27 00:55:10', NULL, NULL),
(3, 'Admin User', 'admin3', '$2y$10$.C2h.TuOxxLZuLblaSDRjuI2vUbacciwJ/s55FpT7I6j0MYngteRS', 'admin', 'approved', 'Barangay 1', '', 0, '', 'active', '2025-03-27 00:55:10', NULL, NULL),
(4, 'Ivan Almadrones', 'ivan', '$2y$10$7ETD/9Aw.oF36mSSW9ooAevgBTNz62Zu/GrghgV6DCi3mYQlzaDNq', 'bhw', 'approved', 'Barangay 1', '', 0, '', 'active', '2025-03-27 00:55:10', NULL, NULL),
(5, 'admin4', 'admin4', '$2y$10$kIJkse268oXUw7.dnvKD6.0o3fLRxs2qSBsJRoqsKpWMbSVIyr07m', 'bhw', 'approved', 'Barangay 1', '', 0, '', 'active', '2025-03-27 00:55:10', NULL, NULL),
(6, 'Lee Ivan Almadrones', 'bhw1', '$2y$10$xU4ati9dY7/G/9wM4sTtvO/8IgYTSAzjElI6a5sV0n91eXnvEQSSO', 'bhw', 'approved', 'Barangay 1', '', 0, '', 'active', '2025-03-27 00:55:10', NULL, NULL),
(7, 'BHW2', 'bhw2', '$2y$10$0iq7B2MvTg4EZfgL85JSx.odmQM5J7pb0hX6PHg7oXux7RSbl1DXi', 'bhw', 'rejected', 'Barangay 1', 'Mambalite', 26, '09108987920', 'active', '2025-03-27 00:55:10', NULL, NULL),
(8, 'Cezar Bernandino', 'CezarBernandino', '$2y$10$B/57uMMGyeUCuSuUDTBgjOxj.cieq0EG/PdC8sOLV9tkBZ2d/I7.2', 'bhw', 'approved', 'Barangay 1', 'Purok 2- Barangay 1 Daet, Camarines Norte', 20, '09304071594', 'active', '2025-04-14 07:37:59', NULL, NULL),
(9, 'bxfbc', 'gdgfrdf', '$2y$10$RAzGSZ2H5zKCtDppfihB9O.fgtAc1VIc8MkuOYON7sre91Qf9dbgu', 'bhw', 'pending', 'San Isidro', 'bxcbxbx', 45, '343434333454', 'active', '2025-04-15 03:41:06', NULL, NULL),
(10, 'Cezar Ibusag', 'CezarIbusag', '$2y$10$DthxUCuGD0P8C2846uXrvuC17WLwLawtlTdoUnawqqkbZq3vnFTrG', 'doctor', 'approved', '', 'P-3 Camambugn, Labo, Camarines Norte', 23, '098383838383', 'active', '2025-04-22 06:51:48', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `log_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `action`, `log_time`) VALUES
(1, 8, 'login', '2025-05-05 11:42:14'),
(2, 8, 'login', '2025-05-05 11:43:24'),
(3, 8, 'login', '2025-05-05 12:18:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `bhs_medicine_dispensed`
--
ALTER TABLE `bhs_medicine_dispensed`
  ADD PRIMARY KEY (`dispensed_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `dispensed_by` (`dispensed_by`);

--
-- Indexes for table `bhs_visits`
--
ALTER TABLE `bhs_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `bhw_id` (`bhw_id`);

--
-- Indexes for table `custom_options`
--
ALTER TABLE `custom_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forgot_password_requests`
--
ALTER TABLE `forgot_password_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `patient_consents`
--
ALTER TABLE `patient_consents`
  ADD PRIMARY KEY (`consent_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `received_by_user_id` (`received_by_user_id`),
  ADD KEY `fk_visit_id` (`visit_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `referred_by` (`referred_by`),
  ADD KEY `referrals_fk_visit` (`visit_id`);

--
-- Indexes for table `rhu_consultations`
--
ALTER TABLE `rhu_consultations`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `fk_consultations_visit` (`visit_id`);

--
-- Indexes for table `rhu_medicine_dispensed`
--
ALTER TABLE `rhu_medicine_dispensed`
  ADD PRIMARY KEY (`dispensed_id`),
  ADD KEY `consultation_id` (`consultation_id`),
  ADD KEY `dispensed_by` (`dispensed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bhs_medicine_dispensed`
--
ALTER TABLE `bhs_medicine_dispensed`
  MODIFY `dispensed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `bhs_visits`
--
ALTER TABLE `bhs_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `custom_options`
--
ALTER TABLE `custom_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `forgot_password_requests`
--
ALTER TABLE `forgot_password_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `patient_consents`
--
ALTER TABLE `patient_consents`
  MODIFY `consent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `rhu_consultations`
--
ALTER TABLE `rhu_consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `rhu_medicine_dispensed`
--
ALTER TABLE `rhu_medicine_dispensed`
  MODIFY `dispensed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `approvals_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `bhs_medicine_dispensed`
--
ALTER TABLE `bhs_medicine_dispensed`
  ADD CONSTRAINT `bhs_medicine_dispensed_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `bhs_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bhs_medicine_dispensed_ibfk_2` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `bhs_visits`
--
ALTER TABLE `bhs_visits`
  ADD CONSTRAINT `bhs_visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bhs_visits_ibfk_2` FOREIGN KEY (`bhw_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `forgot_password_requests`
--
ALTER TABLE `forgot_password_requests`
  ADD CONSTRAINT `forgot_password_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_consents`
--
ALTER TABLE `patient_consents`
  ADD CONSTRAINT `fk_visit_id` FOREIGN KEY (`visit_id`) REFERENCES `bhs_visits` (`visit_id`),
  ADD CONSTRAINT `patient_consents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `patient_consents_ibfk_2` FOREIGN KEY (`received_by_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_fk_visit` FOREIGN KEY (`visit_id`) REFERENCES `bhs_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rhu_consultations`
--
ALTER TABLE `rhu_consultations`
  ADD CONSTRAINT `fk_consultations_visit` FOREIGN KEY (`visit_id`) REFERENCES `bhs_visits` (`visit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rhu_consultations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rhu_consultations_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rhu_medicine_dispensed`
--
ALTER TABLE `rhu_medicine_dispensed`
  ADD CONSTRAINT `rhu_medicine_dispensed_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `rhu_consultations` (`consultation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rhu_medicine_dispensed_ibfk_2` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
