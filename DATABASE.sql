-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2025 at 02:50 PM
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
-- Database: `ihrrs_database`
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
(1, 1, 'Paracetamol 500mg', 4, 8, '2025-05-19 16:08:22'),
(2, 3, 'Mefenamic Acid 500mg', 4, 8, '2025-05-20 01:21:16'),
(3, 4, 'Mefenamic Acid 500mg', 8, 8, '2025-05-20 01:24:50'),
(4, 5, 'Amoxicillin 500mg', 4, 8, '2025-05-20 01:32:36'),
(5, 6, 'Mefenamic Acid 500mg', 6, 8, '2025-05-20 01:41:28'),
(6, 8, 'Amlodipine 10mg', 1, 8, '2025-05-20 03:39:36'),
(7, 8, 'Simvastatin 20mg', 1, 8, '2025-05-20 03:39:37'),
(8, 9, 'Amoxicillin 500mg', 6, 8, '2025-07-04 06:33:07'),
(9, 10, 'Metoprolol 50mg', 4, 8, '2025-07-04 13:12:31'),
(10, 11, 'Metoprolol 50mg', 4, 8, '2025-07-04 13:25:59'),
(11, 12, 'Ibuprofen', 3, 8, '2025-07-05 02:30:37'),
(12, 13, 'Salbutamol Tablet 2mg', 3, 8, '2025-07-14 07:56:47'),
(13, 17, 'Amoxicillin 500mg', 3, 8, '2025-07-16 06:15:33'),
(14, 18, 'Amoxicillin', 2, 8, '2025-07-16 06:22:19'),
(15, 19, 'Gliclazide 30mg', 2, 8, '2025-07-16 06:28:02');

-- --------------------------------------------------------

--
-- Table structure for table `patient_assessment`
--

CREATE TABLE `patient_assessment` (
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `recorded_by` int(11) NOT NULL,
  `visit_date` date NOT NULL DEFAULT current_timestamp(),
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
-- Dumping data for table `patient_assessment`
--

INSERT INTO `patient_assessment` (`visit_id`, `patient_id`, `recorded_by`, `visit_date`, `blood_pressure`, `temperature`, `chief_complaints`, `referred_to_rhu`, `bmi`, `weight`, `height`, `chest_rate`, `respiratory_rate`, `patient_alert`, `remarks`, `treatment`) VALUES
(1, 1, 8, '2025-05-20', '120/80', 35.0, 'Diarrhea for 10 days. Severe Headache', 0, 17.6, 45.00, 160.00, 0, 0, '', 'Patient has allergy to seafoods', ''),
(2, 2, 8, '2025-05-20', '120/80', 35.0, 'severe stomach pain for 4 days', 0, 17.6, 45.00, 160.00, 0, 0, '', 'drink 3 x a day', ''),
(3, 3, 8, '2025-05-20', '1230/80', 39.0, 'headache for 10 days', 0, 17.2, 45.00, 162.00, 0, 0, '', 'Patient has severe allergy to antibiotics', ''),
(4, 4, 8, '2025-05-20', '120/80', 39.4, 'Muscle pain, tightness in chest, difficulty in breathing', 0, 20.0, 45.00, 150.00, 0, 0, '', 'patient shows hypertension symptoms', ''),
(5, 5, 8, '2025-05-20', '120/80', 30.0, 'Severe headache, dizziness', 0, 27.0, 78.00, 170.00, 0, 0, '', 'patient has hearing problem', ''),
(6, 6, 8, '2025-05-20', '120/80', 39.0, 'severe stomachache, nausea for 10 days', 0, 21.0, 65.00, 176.00, 0, 0, '', 'Patient has burry vision', ''),
(7, 7, 8, '2025-05-20', '120/80', 37.0, 'Severe headache for 10 days', 0, 23.0, 45.00, 140.00, 0, 0, '', '', ''),
(8, 5, 8, '2025-05-20', '1230/80', 120.0, 'asad', 0, 12.2, 34.00, 167.00, NULL, NULL, 'Allergic Reactions', '', 'Weighing only'),
(9, 8, 8, '2025-07-04', '88', 444.0, 'Fever', 0, 15.2, 48.00, 178.00, 0, 0, '', 'eee', 'Weighing only'),
(10, 9, 8, '2025-07-04', '120/80', 80.0, 'fever', 0, 12.1, 35.00, 170.00, 0, 0, '', 'Need urgent care', 'BP only'),
(11, 10, 8, '2025-07-04', '120/820', 802.0, 'feverd', 0, 1.2, 352.00, 999.99, 0, 0, '', 'Need urgent care', 'BP only'),
(12, 5, 8, '2025-07-05', '1120', 32.0, 'hay fever', 0, 22.5, 34.00, 123.00, NULL, NULL, '', 'aaaa', 'Prenatal'),
(13, 5, 8, '2025-07-14', '120/34', 43.0, 'Fever for 15 days', 0, 28.0, 43.00, 124.00, NULL, NULL, '', 'none', 'Weighing only'),
(14, 5, 8, '2025-07-15', '44', 44.0, '', 0, 831.8, 44.00, 23.00, NULL, NULL, '', '', ''),
(15, 5, 8, '2025-07-16', '88', 36.0, '', 0, 36.6, 89.00, 156.00, NULL, NULL, '', 'hh', ''),
(16, 5, 8, '2025-07-16', '45', 43.0, 'test', 0, 14.5, 45.00, 176.00, NULL, NULL, '', 'test', ''),
(17, 5, 8, '2025-07-16', '11', 11.0, '111', 0, 909.1, 11.00, 11.00, NULL, NULL, '', '111', ''),
(18, 5, 8, '2025-07-16', '12', 12.0, 'qqqq', 0, 833.3, 12.00, 12.00, NULL, NULL, '', '1111', ''),
(19, 5, 8, '2025-07-16', '11', 11.0, '111', 0, 909.1, 11.00, 11.00, NULL, NULL, '', '1111', ''),
(20, 5, 8, '2025-07-16', '55', 55.0, '', 0, 181.8, 55.00, 55.00, NULL, NULL, '', '555', ''),
(21, 5, 8, '2025-07-16', '55', 55.0, 'ccccccccccc', 0, 175.4, 55.00, 56.00, NULL, NULL, '', 'try', ''),
(22, 5, 8, '2025-07-16', '55', 55.0, 'ccccccccccc', 0, 175.4, 55.00, 56.00, NULL, NULL, '', 'test', ''),
(23, 5, 8, '2025-07-16', '55', 55.0, 'ccccccccccc', 0, 175.4, 55.00, 56.00, NULL, NULL, '', 'testing', ''),
(24, 5, 8, '2025-07-16', '55', 55.0, 'ccccccccccc', 0, 175.4, 55.00, 56.00, NULL, NULL, '', 'testing again', '');

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
(4, 'patient_alert', 'Deaf'),
(6, 'diagnosis', 'Stroke'),
(7, 'diagnosis', 'Colon Cancer'),
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
(78, 'treatment', 'Prenatal'),
(82, 'extension', 'VII'),
(83, 'medicine', 'Paracetamol 500mg'),
(84, 'medicine', 'Amoxicillin 500mg'),
(85, 'medicine', 'Cotrimoxazole 800mg'),
(86, 'medicine', 'Metronidazole 500mg'),
(87, 'medicine', 'Cefalexin 500mg'),
(88, 'medicine', 'Mefenamic Acid 500mg'),
(89, 'medicine', 'Salbutamol Tablet 2mg'),
(90, 'medicine', 'Amlodipine 10mg'),
(91, 'medicine', 'Losartan 50mg'),
(92, 'medicine', 'Metoprolol 50mg'),
(93, 'medicine', 'Simvastatin 20mg'),
(94, 'medicine', 'Metformin 500mg'),
(95, 'medicine', 'Gliclazide 30mg'),
(96, 'religion', 'Muslim'),
(97, 'diagnosis', 'Mild stroke');

-- --------------------------------------------------------

--
-- Table structure for table `follow_ups`
--

CREATE TABLE `follow_ups` (
  `followup_id` int(11) NOT NULL,
  `consultation_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `set_by` int(11) DEFAULT NULL,
  `followup_status` enum('Pending','Completed','Missed') NOT NULL DEFAULT 'Pending',
  `patient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follow_ups`
--

INSERT INTO `follow_ups` (`followup_id`, `consultation_id`, `date`, `set_by`, `followup_status`, `patient_id`) VALUES
(1, 8, '2025-07-31', 8, 'Completed', 9),
(2, 3, '2025-07-31', 11, 'Completed', 2),
(5, 8, '2025-07-17', 1, 'Completed', 4),
(6, 17, '2025-07-17', 7, 'Completed', 1);

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
(1, NULL, 'User Logged Out', '2025-05-19 16:00:49', 10, NULL, 0),
(2, NULL, 'Successful Login', '2025-05-19 16:01:01', 10, NULL, 0),
(3, NULL, 'User Logged Out', '2025-05-19 16:01:19', 10, NULL, 0),
(4, NULL, 'Successful Login', '2025-05-19 16:01:37', 8, NULL, 0),
(5, NULL, 'User Logged Out', '2025-05-19 16:40:16', 8, NULL, 0),
(6, NULL, 'Successful Login', '2025-05-19 16:40:35', 14, NULL, 0),
(7, NULL, 'User Logged Out', '2025-05-19 16:45:41', 14, NULL, 0),
(8, NULL, 'Successful Login', '2025-05-19 16:45:59', 8, NULL, 0),
(9, NULL, 'User Logged Out', '2025-05-19 16:47:27', 8, NULL, 0),
(10, NULL, 'Successful Login', '2025-05-19 16:47:49', 14, NULL, 0),
(11, NULL, 'Successful Login', '2025-05-20 01:17:56', 8, NULL, 0),
(12, NULL, 'Failed Login (Unauthorized Role)', '2025-05-20 01:42:26', 14, NULL, 0),
(13, NULL, 'Successful Login', '2025-05-20 01:42:47', 14, NULL, 0),
(14, NULL, 'Successful Login', '2025-05-20 01:44:04', 10, NULL, 0),
(15, NULL, 'Successful Login', '2025-05-20 01:45:04', 16, NULL, 0),
(16, NULL, 'User Logged Out', '2025-05-20 01:51:03', 8, NULL, 0),
(17, NULL, 'User Logged Out', '2025-05-20 01:51:32', 14, NULL, 0),
(18, NULL, 'User Logged Out', '2025-05-20 01:51:45', 10, NULL, 0),
(19, NULL, 'User Logged Out', '2025-05-20 01:51:58', 16, NULL, 0),
(20, NULL, 'Successful Login', '2025-05-20 03:25:31', 8, NULL, 0),
(21, NULL, 'Failed Login Attempt', '2025-05-20 03:31:11', 14, NULL, 0),
(22, NULL, 'Successful Login', '2025-05-20 03:31:34', 14, NULL, 0),
(23, NULL, 'Successful Login', '2025-05-20 03:32:55', 10, NULL, 0),
(24, NULL, 'Successful Login', '2025-05-20 03:53:18', 14, NULL, 0),
(25, NULL, 'Failed Login Attempt', '2025-07-04 06:23:18', 8, NULL, 0),
(26, NULL, 'Failed Login Attempt', '2025-07-04 06:23:31', 8, NULL, 0),
(27, NULL, 'Failed Login Attempt', '2025-07-04 06:23:40', 8, NULL, 0),
(28, NULL, 'Successful Login', '2025-07-04 06:24:12', 8, NULL, 0),
(29, NULL, 'User Logged Out', '2025-07-04 06:25:59', 8, NULL, 0),
(30, NULL, 'Failed Login (Incorrect Password)', '2025-07-04 06:26:13', 10, NULL, 0),
(31, NULL, 'Successful Login', '2025-07-04 06:26:40', 10, NULL, 0),
(32, NULL, 'Successful Login', '2025-07-04 06:29:20', 8, NULL, 0),
(33, NULL, 'Failed Login Attempt', '2025-07-05 02:24:47', 8, NULL, 0),
(34, NULL, 'Failed Login Attempt', '2025-07-05 02:25:05', 8, NULL, 0),
(35, NULL, 'Failed Login Attempt', '2025-07-05 02:25:12', 8, NULL, 0),
(36, NULL, 'Failed Login Attempt', '2025-07-05 02:25:28', 8, NULL, 0),
(37, NULL, 'Failed Login Attempt', '2025-07-05 02:25:38', 8, NULL, 0),
(38, NULL, 'Failed Login Attempt', '2025-07-05 02:25:47', 8, NULL, 0),
(39, NULL, 'Failed Login Attempt', '2025-07-05 02:28:14', 8, NULL, 0),
(40, NULL, 'Successful Login', '2025-07-05 02:28:23', 8, NULL, 0),
(41, NULL, 'Successful Login', '2025-07-05 02:31:02', 10, NULL, 0),
(42, NULL, 'Failed Login Attempt', '2025-07-14 07:53:02', 8, NULL, 0),
(43, NULL, 'Successful Login', '2025-07-14 07:53:13', 8, NULL, 0);

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
(1, 'John', 'Gabo', 'Santos', 'Jr.', '2002-02-20', 23, 'Male', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09302040378', 1111, 'Single', 'Daet CN', '', 0, 'Tricycle driver', 'Muslim', '1111', '', ''),
(2, 'Lee', 'Ollica', 'Almadrones', '', '2002-02-20', 23, 'Male', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09302040378', 0, 'Single', 'Daet CN', '', 1212, 'Tricycle driver', '', '', '', ''),
(3, 'Trisha', 'Santos', 'Palomares', '', '2025-05-21', -1, 'Male', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte', '', 0, 'Single', 'Labo CN', '', 0, '', '', '', '', ''),
(4, 'Lea', 'Madrigal', 'Liop', '', '2010-01-06', 15, 'Male', 'Purok 7 - Barangay Cobangbang Daet, Camarines Norte', '09093634252', 0, 'Widowed', 'Labo CN', '', 0, '', '', '', '', ''),
(5, 'Cezar', 'Ibusag', 'Bernandino', '', '2009-02-03', 16, 'Male', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte', '', 0, 'Single', 'Daet CN', '', 0, '', '', '', '', ''),
(6, 'John', 'Madrigal', 'Dela Cruz', '', '2000-07-06', 24, 'Male', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte', '', 0, 'Married', 'Daet CN', '', 0, 'Vendor', 'Roman Catholic', '', '', ''),
(7, 'Jp', 'I', 'Gabo', '', '2020-01-31', 5, 'Male', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09123456789', 0, 'Single', 'Labo CN', 'Primary Education', 0, 'Vendor', 'Roman Catholic', '', '', ''),
(8, 'Harry', 'x', 'Styles', '', '2025-01-28', 1, 'Male', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09304071594', 33, 'Married', 'Tondo, Manila', 'Tertiary Education', 0, 'Driver', 'Aglipayan', '21', 'Yes', 'LGU'),
(9, 'Digong', 'x', 'Berdugo', '', '2025-07-01', 0, 'Male', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '', 0, 'Single', 'Tondo, Manila', '', 0, '', '', '', '', ''),
(10, 'Digongd', 'xd', 'Berdugong', '', '2025-05-21', 0, 'Male', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte', '', 0, 'Married', 'Tondo, Manilad', '', 0, '', '', '', '', '');

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
(1, 1, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 00:08:22', 8, NULL, 1),
(2, 2, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 00:47:18', 8, NULL, 2),
(3, 3, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 09:21:16', 8, NULL, 3),
(4, 4, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 09:24:50', 8, NULL, 4),
(5, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 09:32:36', 8, NULL, 5),
(6, 6, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 09:41:28', 8, NULL, 6),
(7, 7, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 11:29:00', 8, NULL, 7),
(8, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-05-20 11:39:36', 8, NULL, 8),
(9, 8, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-04 14:33:07', 8, NULL, 9),
(10, 9, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-04 21:12:31', 8, NULL, 10),
(11, 10, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-04 21:25:59', 8, NULL, 11),
(12, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-05 10:30:37', 8, NULL, 12),
(13, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-14 15:56:47', 8, NULL, 13),
(14, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-15 21:39:56', 8, NULL, 14),
(15, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 13:49:09', 8, NULL, 15),
(16, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 14:10:58', 8, NULL, 16),
(17, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 14:15:33', 8, NULL, 17),
(18, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 14:22:19', 8, NULL, 18),
(19, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 14:28:02', 8, NULL, 19),
(20, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 14:36:11', 8, NULL, 20),
(21, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 19:34:51', 8, NULL, 21),
(22, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 19:35:01', 8, NULL, 22),
(23, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 19:42:36', 8, NULL, 23),
(24, 5, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-07-16 19:43:27', 8, NULL, 24);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visit_id` int(11) NOT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `referral_status` enum('Pending','Completed','Uncompleted','Canceled','Forwarded to Physician') DEFAULT 'Pending',
  `referral_date` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `patient_id`, `visit_id`, `referred_by`, `referral_status`, `referral_date`) VALUES
(1, 1, 1, 8, 'Completed', '2025-05-20'),
(2, 2, 2, 8, 'Completed', '2025-05-20'),
(3, 3, 3, 8, 'Uncompleted', '2025-05-20'),
(4, 4, 4, 8, 'Uncompleted', '2025-05-20'),
(5, 5, 5, 8, 'Uncompleted', '2025-05-20'),
(6, 6, 6, 8, 'Uncompleted', '2025-05-20'),
(7, 7, 7, 8, 'Completed', '2025-05-20'),
(8, 8, 9, 8, 'Completed', '2025-07-04'),
(9, 9, 10, 8, 'Completed', '2025-07-04'),
(10, 10, 11, 8, 'Completed', '2025-07-04'),
(11, 5, 12, 8, 'Completed', '2025-07-05'),
(12, 5, 12, 8, 'Completed', '2025-07-14'),
(13, 5, 13, 8, 'Completed', '2025-07-15'),
(14, 5, 14, 8, 'Completed', '2025-07-16'),
(15, 5, 15, 8, 'Completed', '2025-07-16'),
(16, 5, 16, 8, 'Completed', '2025-07-16'),
(17, 5, 17, 8, 'Completed', '2025-07-16'),
(18, 5, 18, 8, 'Completed', '2025-07-16'),
(19, 5, 19, 8, 'Completed', '2025-07-16'),
(20, 5, 19, 8, 'Completed', '2025-07-16'),
(21, 5, 20, 8, 'Completed', '2025-07-16'),
(22, 5, 20, 8, 'Completed', '2025-07-16'),
(23, 5, 22, 8, 'Completed', '2025-07-16'),
(24, 5, 22, 8, 'Completed', '2025-07-16'),
(25, 5, 23, 8, 'Completed', '2025-07-16');

-- --------------------------------------------------------

--
-- Table structure for table `rhu_consultations`
--

CREATE TABLE `rhu_consultations` (
  `consultation_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `consultation_date` date NOT NULL DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `instruction_prescription` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `lab_result_path` varchar(255) DEFAULT NULL,
  `diagnosis_status` enum('Ongoing','Treated','Reffered','Deceased') NOT NULL DEFAULT 'Ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rhu_consultations`
--

INSERT INTO `rhu_consultations` (`consultation_id`, `patient_id`, `doctor_id`, `consultation_date`, `diagnosis`, `instruction_prescription`, `follow_up_date`, `visit_id`, `lab_result_path`, `diagnosis_status`) VALUES
(1, 1, 1, '2025-05-19', 'Stroke', 'Drink 1 x a day', NULL, 1, NULL, 'Treated'),
(2, 2, 1, '2025-05-19', 'Stroke', 'Take after meal', NULL, 2, NULL, 'Treated'),
(3, 7, 1, '2025-05-20', 'Mild stroke', 'as', NULL, 7, NULL, 'Treated'),
(4, 8, 1, '2025-07-04', 'Stroke', 'cscsc', NULL, 9, NULL, 'Treated'),
(5, 9, 1, '2025-07-04', 'Mild stroke', 'vvvv', NULL, 10, NULL, 'Treated'),
(6, 10, 1, '2025-07-04', 'Stroke', 'fff', NULL, 11, NULL, 'Treated'),
(7, 5, 1, '2025-07-05', 'Colon Cancer', 'vvv', NULL, 12, 'uploads/1751683989_pfp.jpg', 'Treated'),
(8, 5, 1, '2025-07-14', 'Colon Cancer', 'aaa', NULL, 12, NULL, 'Treated'),
(9, 5, 1, '2025-07-16', 'Stroke', 'x', NULL, 13, NULL, 'Ongoing'),
(10, 5, 1, '2025-07-16', 'Stroke', 'sssstrokeeee', NULL, 14, NULL, ''),
(11, 5, 1, '2025-07-16', 'Stroke', 'test only', NULL, 15, NULL, 'Ongoing'),
(12, 5, 1, '2025-07-16', 'Colon Cancer', '1111', NULL, 16, NULL, 'Ongoing'),
(13, 5, 1, '2025-07-16', 'Mild stroke', '122221 test', NULL, 17, NULL, 'Ongoing'),
(14, 5, 1, '2025-07-16', 'Stroke', 'test 22 33', '0000-00-00', 18, NULL, 'Ongoing'),
(15, 5, 1, '2025-07-16', 'Colon Cancer', 'test 33333333', '2025-07-31', 19, 'uploads/1752647551_pfp.jpg', ''),
(16, 5, 1, '2025-07-16', 'Stroke', 'sss', '2025-07-30', 19, NULL, 'Deceased'),
(17, 5, 1, '2025-07-16', 'Colon Cancer', 'scscscs', '2025-07-31', 20, NULL, 'Treated'),
(18, 5, 1, '2025-07-16', 'Mild stroke', 'test number 1', '2025-07-31', 22, NULL, 'Ongoing'),
(19, 5, 1, '2025-07-16', 'Colon Cancer', 'test num 1 2', '2025-07-31', 20, NULL, 'Ongoing'),
(20, 5, 1, '2025-07-16', 'Stroke', 'vbvbv', '2025-07-31', 22, NULL, 'Ongoing'),
(21, 5, 1, '2025-07-16', 'Mild stroke', '12121212 testiiiiiinggg', '0000-00-00', 23, NULL, '');

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
(1, 2, 'Simvastatin 20mg', 2, 1, '2025-05-19 16:49:01'),
(2, 2, 'Amlodipine 10mg', 2, 1, '2025-05-19 16:49:01'),
(3, 3, 'Metronidazole 500mg', 2, 1, '2025-05-20 03:36:12'),
(4, 3, 'Amlodipine 10mg', 2, 1, '2025-05-20 03:36:12'),
(5, 3, 'Metoprolol 50mg', 1, 1, '2025-05-20 03:36:12'),
(6, 4, 'Amoxicillin 500mg', 2, 1, '2025-07-04 13:09:13'),
(7, 5, 'Amoxicillin 500mg', 3, 1, '2025-07-04 13:22:09'),
(8, 6, 'Amoxicillin 500mg', 3, 1, '2025-07-04 13:26:29'),
(9, 7, 'Metronidazole 500mg', 3, 1, '2025-07-05 02:53:09'),
(10, 8, 'Amoxicillin 500mg', 4, 1, '2025-07-14 09:04:03'),
(11, 10, 'Metronidazole 500mg', 2, 1, '2025-07-16 06:00:06'),
(12, 11, 'Amoxicillin 500mg', 3, 1, '2025-07-16 06:13:00'),
(13, 12, 'Metronidazole 500mg', 2, 1, '2025-07-16 06:16:03'),
(14, 13, 'Metronidazole 500mg', 3, 1, '2025-07-16 06:22:57'),
(15, 14, 'Amoxicillin 500mg', 2, 1, '2025-07-16 06:29:00'),
(16, 15, 'Amoxicillin 500mg', 3, 1, '2025-07-16 06:32:31'),
(17, 16, 'Cotrimoxazole 800mg', 2, 1, '2025-07-16 06:37:44'),
(18, 17, 'Cefalexin 500mg', 1, 1, '2025-07-16 11:33:47'),
(19, 18, 'Cefalexin 500mg', 4, 1, '2025-07-16 11:35:50'),
(20, 20, 'Cefalexin 500mg', 1, 1, '2025-07-16 11:43:02'),
(21, 21, 'Cotrimoxazole 800mg', 2, 1, '2025-07-16 11:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','bhw','midwife','doctor','nursing_attendant') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `barangay` enum('Barangay 1','Barangay 6','Barangay 7','Barangay 8','Barangay Gubat','Barangay San Isidro','Barangay Cobangbang','Barangay Bagasbas','Barangay Manbalite') NOT NULL,
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
(8, 'Cezar Bernandino', 'CezarBernandino', '$2y$10$B/57uMMGyeUCuSuUDTBgjOxj.cieq0EG/PdC8sOLV9tkBZ2d/I7.2', 'bhw', 'approved', 'Barangay Cobangbang', 'Purok 2- Barangay 1 Daet, Camarines Norte', 20, '09304071594', 'active', '2025-04-14 07:37:59', NULL, NULL),
(9, 'bxfbc', 'gdgfrdf', '$2y$10$RAzGSZ2H5zKCtDppfihB9O.fgtAc1VIc8MkuOYON7sre91Qf9dbgu', 'bhw', 'pending', '', 'bxcbxbx', 45, '343434333454', 'active', '2025-04-15 03:41:06', NULL, NULL),
(10, 'Cezar Ibusag', 'CezarIbusag', '$2y$10$DthxUCuGD0P8C2846uXrvuC17WLwLawtlTdoUnawqqkbZq3vnFTrG', 'doctor', 'approved', '', 'P-3 Camambugn, Labo, Camarines Norte', 23, '098383838383', 'active', '2025-04-22 06:51:48', NULL, NULL),
(11, 'John Paulino Gallebo', 'jp123', '$2y$10$2icv9Qz8aFCJfsJvL.FSfesii1ufGtn0P8CsGzG/H0vE8G3PQ.CWW', '', 'approved', '', 'P-3 Camambugn, Labo, Camarines Norte', 23, '930407234', 'active', '2025-05-14 07:46:52', '', NULL),
(12, 'Ivan', 'ivanivan', '$2y$10$8LDS6HkAIwaKKnSbyrzA8.CLaVqtzGCEqHpHGO2jfv.PVSaUrMx4a', 'admin', 'approved', '', 'sdsdsds', 23, '23232323232', 'active', '2025-05-14 07:52:45', '', NULL),
(13, 'cezar', 'cezarcezar', '$2y$10$3zsyYMHLGmh7KZ/suKY2n.b6uusMj6rTFKyxwsuNGrL27XqC2gIba', '', 'approved', '', 'wewewe', 34, '12132323232', 'active', '2025-05-14 07:54:18', '', NULL),
(14, 'Lee Ivan Almadrones', 'lee', '$2y$10$sxkCOhHadyNalCh.OzmGWOeuqcboO7n0e7ogJKziuW6fBNSgetSn.', 'nursing_attendant', 'approved', '', 'p-3 Lugui', 24, '09302040378', 'active', '2025-05-19 14:58:32', '', NULL),
(15, 'Bernandino Cezar', 'BernandinoCezar', 'Bernandino1234', 'doctor', 'pending', '', '', 0, '', 'active', '2025-05-19 15:08:06', NULL, NULL),
(16, 'Lee Ivan Almadrones', 'LeeIvan1234', '$2y$10$gBEXB4u0Fh4Ji9NgBttwF.GhfgLLiXM.tK7v06NTSLDnHKx5DhF86', 'admin', 'pending', 'Barangay 1', '', 0, '09123456789', 'active', '2025-05-19 09:21:01', NULL, NULL);

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
(1, 10, 'logout', '2025-05-20 00:00:49'),
(2, 10, 'login', '2025-05-20 00:01:01'),
(3, 10, 'logout', '2025-05-20 00:01:19'),
(4, 8, 'login', '2025-05-20 00:01:37'),
(5, 8, 'logout', '2025-05-20 00:40:16'),
(6, 14, 'login', '2025-05-20 00:40:35'),
(7, 14, 'logout', '2025-05-20 00:45:41'),
(8, 8, 'login', '2025-05-20 00:45:59'),
(9, 8, 'logout', '2025-05-20 00:47:27'),
(10, 14, 'login', '2025-05-20 00:47:49'),
(11, 8, 'login', '2025-05-20 09:17:56'),
(12, 14, 'login', '2025-05-20 09:42:47'),
(13, 10, 'login', '2025-05-20 09:44:04'),
(14, 8, 'logout', '2025-05-20 09:51:03'),
(15, 14, 'logout', '2025-05-20 09:51:32'),
(16, 10, 'logout', '2025-05-20 09:51:45'),
(17, 16, 'logout', '2025-05-20 09:51:58'),
(18, 8, 'login', '2025-05-20 11:25:31'),
(19, 14, 'login', '2025-05-20 11:31:34'),
(20, 10, 'login', '2025-05-20 11:32:55'),
(21, 14, 'login', '2025-05-20 11:53:18'),
(22, 8, 'login', '2025-07-04 14:24:12'),
(23, 8, 'logout', '2025-07-04 14:25:59'),
(24, 10, 'login', '2025-07-04 14:26:40'),
(25, 8, 'login', '2025-07-04 14:29:20'),
(26, 8, 'login', '2025-07-05 10:28:23'),
(27, 10, 'login', '2025-07-05 10:31:02'),
(28, 8, 'login', '2025-07-14 15:53:13');

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
-- Indexes for table `patient_assessment`
--
ALTER TABLE `patient_assessment`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `custom_options`
--
ALTER TABLE `custom_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `follow_ups`
--
ALTER TABLE `follow_ups`
  ADD PRIMARY KEY (`followup_id`),
  ADD KEY `consultation_id` (`consultation_id`),
  ADD KEY `set_by` (`set_by`),
  ADD KEY `fk_followups_patient` (`patient_id`);

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
  MODIFY `dispensed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `patient_assessment`
--
ALTER TABLE `patient_assessment`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `custom_options`
--
ALTER TABLE `custom_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `follow_ups`
--
ALTER TABLE `follow_ups`
  MODIFY `followup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `forgot_password_requests`
--
ALTER TABLE `forgot_password_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `patient_consents`
--
ALTER TABLE `patient_consents`
  MODIFY `consent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `rhu_consultations`
--
ALTER TABLE `rhu_consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `rhu_medicine_dispensed`
--
ALTER TABLE `rhu_medicine_dispensed`
  MODIFY `dispensed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  ADD CONSTRAINT `bhs_medicine_dispensed_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `patient_assessment` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bhs_medicine_dispensed_ibfk_2` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_assessment`
--
ALTER TABLE `patient_assessment`
  ADD CONSTRAINT `patient_assessment_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_assessment_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `follow_ups`
--
ALTER TABLE `follow_ups`
  ADD CONSTRAINT `fk_followups_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `follow_ups_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `rhu_consultations` (`consultation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `follow_ups_ibfk_2` FOREIGN KEY (`set_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_visit_id` FOREIGN KEY (`visit_id`) REFERENCES `patient_assessment` (`visit_id`),
  ADD CONSTRAINT `patient_consents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `patient_consents_ibfk_2` FOREIGN KEY (`received_by_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_fk_visit` FOREIGN KEY (`visit_id`) REFERENCES `patient_assessment` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rhu_consultations`
--
ALTER TABLE `rhu_consultations`
  ADD CONSTRAINT `fk_consultations_visit` FOREIGN KEY (`visit_id`) REFERENCES `patient_assessment` (`visit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
