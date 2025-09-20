-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2025 at 05:41 PM
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
-- Database: `ihrrs_dbase`
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
(1, 4, 'Paracetamol 500mg', 10, 5, '2025-02-04 16:00:00'),
(2, 12, 'Ibuprofen 400mg', 10, 5, '2025-03-14 16:00:00'),
(3, 20, 'Paracetamol 500mg', 10, 5, '2025-04-24 16:00:00'),
(4, 28, 'Ambroxol 30mg', 10, 5, '2025-02-01 16:00:00'),
(5, 33, 'Paracetamol 500mg', 10, 5, '2025-02-28 16:00:00'),
(6, 36, 'Ibuprofen 400mg', 10, 5, '2025-03-17 16:00:00'),
(7, 42, 'Paracetamol 500mg', 10, 5, '2025-04-18 16:00:00'),
(8, 47, 'Ambroxol 30mg', 10, 5, '2025-05-15 16:00:00'),
(9, 50, 'Paracetamol 500mg', 10, 5, '2025-05-31 16:00:00'),
(10, 55, 'Paracetamol 500mg', 10, 5, '2025-06-25 16:00:00'),
(11, 58, 'Ambroxol 30mg', 10, 5, '2025-07-12 16:00:00'),
(12, 63, 'Paracetamol 500mg', 10, 5, '2025-08-08 16:00:00'),
(13, 66, 'Paracetamol 500mg', 10, 5, '2025-08-26 16:00:00'),
(14, 71, 'Ambroxol 30mg', 10, 5, '2025-08-10 16:00:00'),
(15, 74, 'Paracetamol 500mg', 10, 5, '2025-05-04 16:00:00'),
(16, 77, 'Ambroxol 30mg', 10, 5, '2025-05-07 16:00:00'),
(17, 195, 'Paracetamol 500mg', 10, 5, '2025-01-31 16:00:00'),
(18, 218, 'Paracetamol 500mg', 10, 6, '2025-01-14 16:00:00'),
(19, 219, 'Ambroxol 30mg', 10, 6, '2025-01-19 16:00:00'),
(20, 222, 'Paracetamol 500mg', 10, 6, '2025-02-05 16:00:00'),
(21, 223, 'Paracetamol 500mg', 10, 6, '2025-02-10 16:00:00'),
(22, 226, 'Ambroxol 30mg', 10, 6, '2025-02-28 16:00:00'),
(23, 228, 'Paracetamol 500mg', 10, 6, '2025-03-10 16:00:00'),
(24, 229, 'Paracetamol 500mg', 10, 6, '2025-03-15 16:00:00'),
(25, 232, 'Paracetamol 500mg', 10, 6, '2025-04-05 16:00:00'),
(26, 234, 'Paracetamol 500mg', 10, 6, '2025-04-15 16:00:00'),
(27, 574, 'Paracetamol', 2, 3, '2025-09-12 06:15:33');

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
(97, 'diagnosis', 'Mild stroke'),
(98, 'bhs_remarks', 'Patient needs urgent care'),
(99, 'diagnosis', 'Tuberculosis'),
(100, 'rhu_remarks', 'Patient needs more advanced care'),
(101, 'rhu_remarks', 'Patient has severe allergy to antibiotics'),
(102, 'Barangay 1', 'Purok 3 - Camambugan Daet Camarines Norte'),
(103, 'treatment', 'Consultation'),
(104, 'diagnosis', 'Hypertension'),
(105, 'diagnosis', 'Diabetes Mellitus'),
(106, 'diagnosis', 'Upper Respiratory Tract Infection'),
(107, 'diagnosis', 'Pneumonia'),
(108, 'diagnosis', 'Asthma'),
(109, 'diagnosis', 'Dengue Fever'),
(110, 'diagnosis', 'Chikungunya'),
(111, 'diagnosis', 'Typhoid Fever'),
(112, 'diagnosis', 'Diarrhea'),
(113, 'diagnosis', 'Gastroenteritis'),
(114, 'diagnosis', 'Urinary Tract Infection'),
(115, 'diagnosis', 'Leptospirosis'),
(116, 'diagnosis', 'Measles'),
(117, 'diagnosis', 'Chickenpox'),
(118, 'diagnosis', 'Influenza'),
(119, 'diagnosis', 'Otitis Media'),
(120, 'diagnosis', 'Skin Abscess'),
(121, 'diagnosis', 'Scabies'),
(122, 'diagnosis', 'Allergic Rhinitis'),
(123, 'diagnosis', 'COVID-19'),
(124, 'diagnosis', 'Malaria'),
(125, 'diagnosis', 'Hepatitis B'),
(126, 'diagnosis', 'Anemia'),
(127, 'diagnosis', 'Malnutrition'),
(128, 'diagnosis', 'Gastritis'),
(129, 'diagnosis', 'Peptic Ulcer Disease'),
(130, 'diagnosis', 'Myalgia'),
(131, 'diagnosis', 'Arthritis'),
(132, 'diagnosis', 'Fever'),
(133, 'diagnosis', 'Cough'),
(134, 'diagnosis', 'Colds'),
(135, 'diagnosis', 'Headache'),
(136, 'diagnosis', 'Toothache'),
(137, 'diagnosis', 'Sore Throat'),
(138, 'diagnosis', 'Stomach Pain'),
(139, 'diagnosis', 'Body Malaise'),
(140, 'diagnosis', 'Dizziness');

-- --------------------------------------------------------

--
-- Table structure for table `follow_ups`
--

CREATE TABLE `follow_ups` (
  `followup_id` int(11) NOT NULL,
  `consultation_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `set_by` int(11) DEFAULT NULL,
  `followup_status` enum('Pending','Completed','Missed') NOT NULL DEFAULT 'Pending',
  `patient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follow_ups`
--

INSERT INTO `follow_ups` (`followup_id`, `consultation_id`, `date`, `set_by`, `followup_status`, `patient_id`) VALUES
(1, 77, '2025-09-16', 2, 'Pending', 444),
(2, 78, '2025-09-18', 2, 'Pending', 444),
(3, 79, '2025-09-30', 2, 'Completed', 444),
(4, 80, '2025-09-16', 2, 'Pending', 445);

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
(1, NULL, 'Successful Login', '2025-09-11 01:23:54', 5, NULL, 0),
(2, NULL, 'Successful Login', '2025-09-11 01:30:59', 2, NULL, 0),
(3, NULL, 'Successful Login', '2025-09-11 01:31:58', 3, NULL, 0),
(4, NULL, 'Successful Login', '2025-09-11 12:05:45', 6, NULL, 0),
(5, NULL, 'Successful Login', '2025-09-11 12:26:37', 2, NULL, 0),
(6, NULL, 'Successful Login', '2025-09-11 12:27:22', 3, NULL, 0),
(7, NULL, 'User Logged Out', '2025-09-11 13:43:27', 6, NULL, 0),
(8, NULL, 'Successful Login', '2025-09-11 13:51:37', 6, NULL, 0),
(9, NULL, 'Successful Login', '2025-09-12 04:53:35', 6, NULL, 0),
(10, NULL, 'Successful Login', '2025-09-12 04:56:53', 3, NULL, 0),
(11, NULL, 'Successful Login', '2025-09-15 03:26:11', 5, NULL, 0),
(12, NULL, 'Successful Login', '2025-09-15 03:27:44', 3, NULL, 0),
(13, NULL, 'Successful Login', '2025-09-15 04:34:43', 2, NULL, 0),
(14, NULL, 'Failed Login Attempt', '2025-09-15 07:11:46', 12, NULL, 0),
(15, NULL, 'Successful Login', '2025-09-15 07:12:36', 12, NULL, 0),
(16, NULL, 'Successful Login', '2025-09-15 07:14:58', 1, NULL, 0),
(17, NULL, 'Successful Login', '2025-09-17 02:24:25', 5, NULL, 0),
(18, NULL, 'Successful Login', '2025-09-17 02:26:53', 2, NULL, 0);

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
(1, 'Juan', 'Dela', 'Cruz', 'Jr.', '1990-05-14', 35, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09171234567', 1001, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Farmer', 'Roman Catholic', 'PH1234567890', 'No', 'NHTS'),
(2, 'Maria', 'Santos', 'Reyes', '', '1988-11-02', 36, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09182345678', 1002, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 3, 'Teacher', 'Aglipayan', 'PH1234567891', 'Yes', 'LGU'),
(3, 'Jose', 'Garcia', 'Lopez', 'Sr.', '1975-07-22', 50, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09193456789', 1003, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 4, 'Driver', 'Islam', 'PH1234567892', 'No', 'Private'),
(4, 'Ana', 'Torres', 'Mendoza', '', '1995-03-18', 30, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09204567890', 1004, 'Single', 'Mercedes, Camarines Norte', 'Postgraduate', 3, 'Nurse', 'Iglesia ni Cristo', 'PH1234567893', 'No', 'Self-Employed'),
(5, 'Pedro', 'Villanueva', 'Cortez', 'III', '1982-12-09', 42, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09215678901', 1005, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Carpenter', 'Assemblies of God', 'PH1234567894', 'Yes', 'NHTS'),
(6, 'Rosa', 'Cruz', 'Bautista', '', '1998-06-25', 27, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09226789012', 1006, 'Single', 'Basud, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567895', 'No', 'LGU'),
(7, 'Andres', 'Domingo', 'Marquez', 'II', '1985-01-15', 40, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09237890123', 1007, 'Married', 'Daet, Camarines Norte', 'Primary Education', 3, 'Fisherman', 'United Methodist Church', 'PH1234567896', 'No', 'Private'),
(8, 'Elena', 'Ramos', 'Castro', '', '1979-08-03', 46, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09248901234', 1008, 'Widowed', 'Talisay, Camarines Norte', 'Secondary Education', 4, 'Vendor', 'Roman Catholic', 'PH1234567897', 'Yes', 'NHTS'),
(9, 'Miguel', 'Santiago', 'Jimenez', 'IV', '1993-02-19', 32, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09259012345', 1009, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Engineer', 'Islam', 'PH1234567898', 'No', 'Self-Employed'),
(10, 'Luz', 'Flores', 'Delos Santos', '', '1991-09-11', 34, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09260123456', 1010, 'Married', 'Labo, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Aglipayan', 'PH1234567899', 'Yes', 'LGU'),
(11, 'Ricardo', 'Navarro', 'Aguilar', 'V', '1987-04-05', 38, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09271234567', 1011, 'Married', 'Paracale, Camarines Norte', 'Tertiary Education', 3, 'Mechanic', 'Roman Catholic', 'PH1234567900', 'No', 'Private'),
(12, 'Carmen', 'Velasco', 'Gutierrez', '', '1973-10-28', 51, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09282345678', 1012, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 4, 'Housewife', 'Assemblies of God', 'PH1234567901', 'No', 'NHTS'),
(13, 'Francisco', 'Aquino', 'Morales', 'Sr.', '1969-06-13', 56, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09293456789', 1013, 'Married', 'Jose Panganiban, Camarines Norte', 'Secondary Education', 3, 'Farmer', 'Iglesia ni Cristo', 'PH1234567902', 'Yes', 'LGU'),
(14, 'Teresa', 'Diaz', 'Ferrer', '', '1996-07-21', 29, 'Female', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09304567890', 1014, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567903', 'No', 'Private'),
(15, 'Carlos', 'Manalo', 'Rivera', 'Jr.', '1984-01-07', 41, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09315678901', 1015, 'Married', 'Mercedes, Camarines Norte', 'Tertiary Education', 3, 'Teacher', 'United Methodist Church', 'PH1234567904', 'No', 'Self-Employed'),
(16, 'Isabel', 'Perez', 'Alvarez', '', '1992-03-29', 33, 'Female', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09326789012', 1016, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Nurse', 'Roman Catholic', 'PH1234567905', 'Yes', 'NHTS'),
(17, 'Eduardo', 'Santos', 'Domingo', 'II', '1981-11-16', 43, 'Male', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09337890123', 1017, 'Married', 'Basud, Camarines Norte', 'Postgraduate', 3, 'Engineer', 'Aglipayan', 'PH1234567906', 'No', 'LGU'),
(18, 'Sofia', 'Mendoza', 'Jimenez', '', '1999-05-24', 26, 'Female', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09348901234', 1018, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567907', 'No', 'Private'),
(19, 'Antonio', 'Ramos', 'Castillo', 'III', '1978-12-30', 46, 'Male', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09359012345', 1019, 'Married', 'Labo, Camarines Norte', 'Primary Education', 4, 'Carpenter', 'Islam', 'PH1234567908', 'Yes', 'Self-Employed'),
(20, 'Patricia', 'Lopez', 'Villanueva', '', '1994-02-14', 31, 'Female', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09360123456', 1020, 'Single', 'Talisay, Camarines Norte', 'Tertiary Education', 3, 'Vendor', 'Roman Catholic', 'PH1234567909', 'No', 'LGU'),
(21, 'Jorge', 'Del Rosario', 'Salazar', 'IV', '1989-09-03', 36, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09371234567', 1021, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Driver', 'Iglesia ni Cristo', 'PH1234567910', 'No', 'NHTS'),
(22, 'Monica', 'Reyes', 'Ortiz', '', '1976-06-08', 49, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09382345678', 1022, 'Widowed', 'Paracale, Camarines Norte', 'Secondary Education', 4, 'Housewife', 'Roman Catholic', 'PH1234567911', 'Yes', 'Private'),
(23, 'Luis', 'Cruz', 'Del Rosario', 'V', '1983-10-01', 41, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09393456789', 1023, 'Married', 'Daet, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Assemblies of God', 'PH1234567912', 'No', 'LGU'),
(24, 'Alfredo', 'Marquez', 'Santos', '', '1980-03-11', 45, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09401234567', 1024, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Farmer', 'Roman Catholic', 'PH1234567913', 'No', 'NHTS'),
(25, 'Beatriz', 'Gutierrez', 'Torres', 'Sr.', '1997-12-05', 27, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09412345678', 1025, 'Single', 'Labo, Camarines Norte', 'Tertiary Education', 3, 'Nurse', 'Aglipayan', 'PH1234567914', 'Yes', 'LGU'),
(26, 'Cesar', 'Aguilar', 'Perez', '', '1972-06-23', 53, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09423456789', 1026, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 3, 'Fisherman', 'Islam', 'PH1234567915', 'No', 'Private'),
(27, 'Dolores', 'Ferrer', 'Reyes', 'II', '1990-01-17', 35, 'Female', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09434567890', 1027, 'Married', 'Mercedes, Camarines Norte', 'Postgraduate', 3, 'Teacher', 'Iglesia ni Cristo', 'PH1234567916', 'No', 'Self-Employed'),
(28, 'Enrique', 'Morales', 'Delgado', '', '1988-08-14', 37, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09445678901', 1028, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 4, 'Driver', 'Assemblies of God', 'PH1234567917', 'Yes', 'NHTS'),
(29, 'Felisa', 'Castro', 'Aquino', 'III', '1993-04-29', 32, 'Female', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09456789012', 1029, 'Married', 'Basud, Camarines Norte', 'Secondary Education', 3, 'Vendor', 'Roman Catholic', 'PH1234567918', 'No', 'LGU'),
(30, 'Gregorio', 'Mendoza', 'Lopez', '', '1977-07-07', 48, 'Male', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09467890123', 1030, 'Separated', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Carpenter', 'United Methodist Church', 'PH1234567919', 'No', 'Private'),
(31, 'Helena', 'Jimenez', 'Cruz', '', '1984-02-18', 41, 'Female', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09478901234', 1031, 'Married', 'Jose Panganiban, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Roman Catholic', 'PH1234567920', 'Yes', 'LGU'),
(32, 'Ignacio', 'Salazar', 'Bautista', 'IV', '1999-11-26', 25, 'Male', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09489012345', 1032, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Islam', 'PH1234567921', 'No', 'Self-Employed'),
(33, 'Juliana', 'Ortiz', 'Villanueva', '', '1986-09-13', 39, 'Female', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09490123456', 1033, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 3, 'Housewife', 'Aglipayan', 'PH1234567922', 'Yes', 'NHTS'),
(34, 'Karl', 'Rivera', 'Cortez', 'V', '1982-01-25', 43, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09501234567', 1034, 'Married', 'Daet, Camarines Norte', 'Primary Education', 3, 'Fisherman', 'Roman Catholic', 'PH1234567923', 'No', 'Private'),
(35, 'Leticia', 'Delos Santos', 'Navarro', '', '1970-05-06', 55, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09512345678', 1035, 'Widowed', 'Mercedes, Camarines Norte', 'Primary Education', 4, 'Vendor', 'Assemblies of God', 'PH1234567924', 'No', 'LGU'),
(36, 'Manuel', 'Agbayani', 'Velasco', 'Jr.', '1991-02-20', 34, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09523456789', 1036, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Engineer', 'Roman Catholic', 'PH1234567925', 'Yes', 'Self-Employed'),
(37, 'Nora', 'Cortez', 'Diaz', '', '1979-10-09', 45, 'Female', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09534567890', 1037, 'Married', 'Basud, Camarines Norte', 'Secondary Education', 3, 'Teacher', 'Iglesia ni Cristo', 'PH1234567926', 'No', 'NHTS'),
(38, 'Oscar', 'Domingo', 'Ramos', '', '1994-06-15', 31, 'Male', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09545678901', 1038, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567927', 'No', 'LGU'),
(39, 'Patria', 'Jimenez', 'Garcia', 'Sr.', '1987-12-22', 37, 'Female', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09556789012', 1039, 'Separated', 'Labo, Camarines Norte', 'Primary Education', 4, 'Vendor', 'Aglipayan', 'PH1234567928', 'Yes', 'Private'),
(40, 'Quirino', 'Santiago', 'Aquino', '', '1968-08-01', 57, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09567890123', 1040, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Farmer', 'Islam', 'PH1234567929', 'No', 'NHTS'),
(41, 'Rosalinda', 'Villanueva', 'Del Rosario', '', '1992-03-08', 33, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09578901234', 1041, 'Single', 'Jose Panganiban, Camarines Norte', 'Tertiary Education', 3, 'Nurse', 'Roman Catholic', 'PH1234567930', 'No', 'LGU'),
(42, 'Salvador', 'Torres', 'Manalo', 'II', '1975-07-19', 50, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09589012345', 1042, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Carpenter', 'Assemblies of God', 'PH1234567931', 'No', 'Self-Employed'),
(43, 'Teresa', 'Aquino', 'Velasco', '', '1996-11-28', 28, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09590123456', 1043, 'Married', 'Talisay, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Roman Catholic', 'PH1234567932', 'Yes', 'NHTS'),
(44, 'Urbano', 'Garcia', 'Aguilar', '', '1983-04-03', 42, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09601234567', 1044, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Driver', 'United Methodist Church', 'PH1234567933', 'No', 'Private'),
(45, 'Veronica', 'Cruz', 'Santos', 'III', '1998-09-27', 26, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09612345678', 1045, 'Single', 'Basud, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567934', 'No', 'LGU'),
(46, 'Walter', 'Navarro', 'Reyes', '', '1971-12-16', 53, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09623456789', 1046, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 4, 'Fisherman', 'Islam', 'PH1234567935', 'Yes', 'NHTS'),
(47, 'Xenia', 'Morales', 'Diaz', '', '1989-05-12', 36, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09634567890', 1047, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 3, 'Housewife', 'Roman Catholic', 'PH1234567936', 'No', 'Private'),
(48, 'Jared', 'Cruz', 'Reyes', '', '2019-06-18', 6, 'Male', 'Purok 2 - Barangay 7 Daet, Camarines Norte', '09173216547', 276, 'Single', 'Daet', 'Primary Education', 3200, '', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(49, 'Alexa', 'Santos', 'Torres', '', '2017-09-12', 8, 'Female', 'Purok 4 - Barangay 8 Daet, Camarines Norte', '09283451672', 277, 'Single', 'Daet', 'Primary Education', 3000, 'Student', 'Iglesia ni Cristo', NULL, 'No', 'LGU'),
(50, 'Patrick', 'Domingo', 'Gomez', 'Jr.', '2008-01-23', 17, 'Male', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09395671248', 278, 'Single', 'Daet', 'Secondary Education', 3400, 'Student', 'Roman Catholic', NULL, 'Yes', 'Private'),
(51, 'Diana', 'Lopez', 'Cabrera', '', '2010-11-09', 14, 'Female', 'Purok 2 - Barangay San Isidro Daet, Camarines Norte', '09687321459', 279, 'Single', 'Daet', 'Secondary Education', 3300, 'Student', 'Aglipayan', NULL, 'No', 'LGU'),
(52, 'Evan', 'Mendoza', 'Aquino', '', '2006-04-02', 19, 'Male', 'Purok 7 - Barangay Cobangbang Daet, Camarines Norte', '09978236451', 280, 'Single', 'Daet', 'Secondary Education', 3700, 'Student', 'Roman Catholic', '32-345678912-7', 'No', 'LGU'),
(53, 'Samantha', 'Villanueva', 'Delos Santos', '', '2020-02-15', 5, 'Female', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09183456217', 281, 'Single', 'Daet', 'Primary Education', 3100, '', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(54, 'Kevin', 'Garcia', 'Ramos', '', '2014-05-26', 11, 'Male', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte', '09274351628', 282, 'Single', 'Labo', 'Primary Education', 3300, 'Student', 'United Methodist Church', NULL, 'No', 'LGU'),
(55, 'Lianne', 'Hernandez', 'Pascual', '', '2005-08-03', 20, 'Female', 'Purok 1 - Barangay Bagasbas Daet, Camarines Norte', '09564237819', 283, 'Single', 'Daet', 'Secondary Education', 3500, 'Apprentice', 'Roman Catholic', '51-876543219-4', 'No', 'Private'),
(56, 'Noel', 'Cortez', 'Santiago', '', '2012-10-20', 12, 'Male', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09782346512', 284, 'Single', 'Daet', 'Primary Education', 3200, 'Student', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(57, 'Chloe', 'Aguilar', 'Lim', '', '2007-03-14', 18, 'Female', 'Purok 2 - Barangay San Isidro Daet, Camarines Norte', '09923654817', 285, 'Single', 'Daet', 'Secondary Education', 3500, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(58, 'Joshua', 'Ramos', 'Fabian', '', '2004-12-01', 20, 'Male', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09172634851', 286, 'Single', 'Daet', 'Tertiary Education', 3700, 'Student', 'Assemblies of God', '44-998877665-1', 'No', 'LGU'),
(59, 'Monica', 'Cruz', 'Ortega', '', '2016-07-07', 9, 'Female', 'Purok 8 - Barangay Gubat Daet, Camarines Norte', '09283645197', 287, 'Single', 'Daet', 'Primary Education', 3100, 'Student', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(60, 'Andre', 'Lopez', 'Padilla', 'II', '2003-09-18', 21, 'Male', 'Purok 3 - Barangay Mambalite Daet, Camarines Norte', '09563427815', 288, 'Single', 'Daet', 'Tertiary Education', 3800, 'Student', 'Roman Catholic', '67-223344556-8', 'No', 'Private'),
(61, 'Mikaela', 'Santos', 'Tolentino', '', '2021-01-05', 4, 'Female', 'Purok 7 - Barangay 7 Daet, Camarines Norte', '09183476529', 289, 'Single', 'Daet', 'Primary Education', 3000, '', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(62, 'Victor', 'Reyes', 'Navarro', '', '2009-06-11', 16, 'Male', 'Purok 9 - Barangay 8 Daet, Camarines Norte', '09321457896', 290, 'Single', 'Daet', 'Secondary Education', 3400, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(63, 'Elena', 'Gutierrez', 'Morales', '', '2013-02-27', 12, 'Female', 'Purok 1 - Barangay Gubat Daet, Camarines Norte', '09786431259', 291, 'Single', 'Daet', 'Primary Education', 3200, 'Student', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(64, 'Damian', 'Diaz', 'Vergara', '', '2015-12-19', 9, 'Male', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte', '09673421589', 292, 'Single', 'Daet', 'Primary Education', 3300, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(65, 'Grace', 'Castillo', 'Jimenez', '', '2002-07-09', 23, 'Female', 'Purok 4 - Barangay San Isidro Daet, Camarines Norte', '09283645120', 293, 'Single', 'Daet', 'Tertiary Education', 3700, 'Student', 'Iglesia ni Cristo', '21-445566778-3', 'No', 'Private'),
(66, 'Lucas', 'Mendoza', 'Ortega', '', '2018-05-30', 7, 'Male', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte', '09172634825', 294, 'Single', 'Daet', 'Primary Education', 3100, '', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(67, 'Janine', 'Roxas', 'Cortez', '', '2001-04-14', 24, 'Female', 'Purok 6 - Barangay Mambalite Daet, Camarines Norte', '09912345671', 295, 'Single', 'Labo', 'Tertiary Education', 3900, 'Apprentice', 'Roman Catholic', '56-334455667-9', 'No', 'LGU'),
(68, 'Kyle', 'Santiago', 'Reyes', '', '2011-11-25', 13, 'Male', 'Purok 7 - Barangay Bagasbas Daet, Camarines Norte', '09456123897', 296, 'Single', 'Daet', 'Secondary Education', 3300, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(69, 'Aria', 'Lim', 'Serrano', '', '2022-03-21', 3, 'Female', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09127346582', 297, 'Single', 'Daet', 'Primary Education', 3000, '', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(70, 'Martin', 'Aguilar', 'Villanueva', '', '2000-08-28', 25, 'Male', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte', '09283416572', 298, 'Single', 'Daet', 'Tertiary Education', 3800, 'Student', 'Roman Catholic', '89-998877665-4', 'No', 'Private'),
(71, 'Patricia', 'Cruz', 'Bautista', '', '2010-01-02', 15, 'Female', 'Purok 1 - Barangay San Isidro Daet, Camarines Norte', '09784561239', 299, 'Single', 'Daet', 'Secondary Education', 3400, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(72, 'Sean', 'Torres', 'Gutierrez', '', '2016-09-17', 8, 'Male', 'Purok 8 - Barangay Bagasbas Daet, Camarines Norte', '09127364589', 300, 'Single', 'Daet', 'Primary Education', 3100, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(73, 'Gabriel', 'Torres', 'Mendez', '', '1986-05-12', 39, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09401234567', 1024, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Mechanic', 'Roman Catholic', 'PH1234567913', 'No', 'Private'),
(74, 'Angela', 'Dela Cruz', 'Navarro', 'II', '1997-09-22', 27, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09412345678', 1025, 'Single', 'Labo, Camarines Norte', 'Postgraduate', 3, 'Nurse', 'Iglesia ni Cristo', 'PH1234567914', 'No', 'LGU'),
(75, 'Roberto', 'Reyes', 'Aquino', '', '1974-03-18', 51, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09423456789', 1026, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 3, 'Driver', 'Islam', 'PH1234567915', 'Yes', 'NHTS'),
(76, 'Clara', 'Santos', 'Velasco', '', '1982-12-01', 42, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09434567890', 1027, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Teacher', 'Roman Catholic', 'PH1234567916', 'No', 'Private'),
(77, 'Hector', 'Mendoza', 'Lopez', 'Sr.', '1990-07-07', 35, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09445678901', 1028, 'Single', 'Basud, Camarines Norte', 'Primary Education', 3, 'Fisherman', 'Assemblies of God', 'PH1234567917', 'No', 'LGU'),
(78, 'Cynthia', 'Villanueva', 'Santiago', '', '1979-02-26', 46, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09456789012', 1029, 'Widowed', 'Talisay, Camarines Norte', 'Secondary Education', 4, 'Vendor', 'Roman Catholic', 'PH1234567918', 'Yes', 'NHTS'),
(79, 'Daniel', 'Aquino', 'Ramos', 'III', '1995-06-15', 30, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09467890123', 1030, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Engineer', 'United Methodist Church', 'PH1234567919', 'No', 'Self-Employed'),
(80, 'Veronica', 'Garcia', 'Morales', '', '1988-11-19', 36, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09478901234', 1031, 'Married', 'Labo, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Aglipayan', 'PH1234567920', 'Yes', 'Private'),
(81, 'Luis', 'Perez', 'Domingo', 'IV', '1977-01-23', 48, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09489012345', 1032, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Carpenter', 'Roman Catholic', 'PH1234567921', 'No', 'NHTS'),
(82, 'Teresa', 'Cortez', 'Bautista', '', '1993-08-30', 32, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09490123456', 1033, 'Single', 'Mercedes, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567922', 'No', 'LGU'),
(83, 'Marco', 'Diaz', 'Ferrer', 'V', '1985-04-12', 40, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09501234567', 1034, 'Married', 'Daet, Camarines Norte', 'Primary Education', 4, 'Farmer', 'Iglesia ni Cristo', 'PH1234567923', 'Yes', 'Self-Employed'),
(84, 'Adela', 'Ramos', 'Salazar', '', '1999-12-14', 25, 'Female', 'Purok 6 - Barangay 6 Daet, Camarines Norte', '09512345678', 1035, 'Single', 'Labo, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567924', 'No', 'Private'),
(85, 'Ramon', 'Lopez', 'Castillo', 'Jr.', '1983-10-08', 41, 'Male', 'Purok 7 - Barangay 6 Daet, Camarines Norte', '09523456789', 1036, 'Married', 'Paracale, Camarines Norte', 'Postgraduate', 3, 'Teacher', 'Assemblies of God', 'PH1234567925', 'No', 'LGU'),
(86, 'Isabel', 'Delos Santos', 'Manalo', '', '1991-03-02', 34, 'Female', 'Purok 8 - Barangay 6 Daet, Camarines Norte', '09534567890', 1037, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Nurse', 'Roman Catholic', 'PH1234567926', 'Yes', 'NHTS'),
(87, 'Diego', 'Jimenez', 'Velasco', 'II', '1976-06-17', 49, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09545678901', 1038, 'Married', 'Mercedes, Camarines Norte', 'Tertiary Education', 3, 'Mechanic', 'Islam', 'PH1234567927', 'No', 'Private'),
(88, 'Elena', 'Gutierrez', 'Alvarez', '', '1989-07-29', 36, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09556789012', 1039, 'Single', 'Daet, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Roman Catholic', 'PH1234567928', 'No', 'LGU'),
(89, 'Felipe', 'Aguilar', 'Rivera', 'Sr.', '1972-09-11', 53, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09567890123', 1040, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 4, 'Fisherman', 'Roman Catholic', 'PH1234567929', 'Yes', 'NHTS'),
(90, 'Dolores', 'Santiago', 'Ortega', '', '1980-01-27', 45, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09578901234', 1041, 'Married', 'Daet, Camarines Norte', 'Primary Education', 3, 'Housewife', 'Aglipayan', 'PH1234567930', 'No', 'Private'),
(91, 'Mateo', 'Villanueva', 'Reyes', 'III', '1994-05-16', 31, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09589012345', 1042, 'Single', 'Jose Panganiban, Camarines Norte', 'Tertiary Education', 3, 'Engineer', 'Roman Catholic', 'PH1234567931', 'No', 'LGU'),
(92, 'Rosa', 'Cruz', 'Fernandez', '', '1996-02-03', 29, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09590123456', 1043, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Student', 'Roman Catholic', 'PH1234567932', 'No', 'Self-Employed'),
(93, 'Ignacio', 'Martinez', 'Domingo', 'IV', '1987-11-21', 37, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09601234567', 1044, 'Married', 'Labo, Camarines Norte', 'Primary Education', 4, 'Farmer', 'United Methodist Church', 'PH1234567933', 'Yes', 'NHTS'),
(94, 'Cristina', 'Bautista', 'Santos', '', '1990-09-05', 35, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09612345678', 1045, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Teacher', 'Roman Catholic', 'PH1234567934', 'No', 'Private'),
(95, 'Vicente', 'Aquino', 'Torres', '', '1984-12-30', 40, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09623456789', 1046, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 3, 'Carpenter', 'Assemblies of God', 'PH1234567935', 'No', 'LGU'),
(96, 'Marina', 'Del Rosario', 'Flores', '', '1978-04-18', 47, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09634567890', 1047, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 4, 'Vendor', 'Roman Catholic', 'PH1234567936', 'Yes', 'NHTS'),
(97, 'Oscar', 'Morales', 'Castro', 'V', '1981-07-09', 44, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09645678901', 1048, 'Married', 'Paracale, Camarines Norte', 'Tertiary Education', 3, 'Driver', 'Roman Catholic', 'PH1234567937', 'No', 'Self-Employed'),
(98, 'Gabriel', 'Santos', 'Lopez', '', '1990-01-12', 35, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09401234567', 1126, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Teacher', 'Roman Catholic', 'PH1234567933', 'No', 'NHTS'),
(99, 'Clara', 'Reyes', 'Mendoza', 'Jr.', '1987-05-22', 38, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09412345678', 1127, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 3, 'Vendor', 'Aglipayan', 'PH1234567934', 'Yes', 'LGU'),
(100, 'Ramon', 'Dela', 'Cruz', '', '1979-09-18', 45, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09423456789', 1128, 'Married', 'Labo, Camarines Norte', 'Primary Education', 3, 'Farmer', 'Islam', 'PH1234567935', 'No', 'Private'),
(101, 'Angela', 'Domingo', 'Villanueva', '', '1995-12-04', 29, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09434567890', 1129, 'Single', 'Basud, Camarines Norte', 'Postgraduate', 3, 'Nurse', 'Roman Catholic', 'PH1234567936', 'No', 'Self-Employed'),
(102, 'Oscar', 'Garcia', 'Navarro', 'Sr.', '1982-04-28', 43, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09445678901', 1130, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Driver', 'Iglesia ni Cristo', 'PH1234567937', 'Yes', 'NHTS'),
(103, 'Beatriz', 'Cruz', 'Aguilar', '', '1993-08-15', 32, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09456789012', 1131, 'Single', 'Talisay, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567938', 'No', 'LGU'),
(104, 'Hector', 'Torres', 'Castro', 'II', '1986-07-03', 39, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09467890123', 1132, 'Married', 'Daet, Camarines Norte', 'Primary Education', 4, 'Carpenter', 'Assemblies of God', 'PH1234567939', 'No', 'Private'),
(105, 'Juliana', 'Ramos', 'Morales', '', '1998-11-19', 26, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09478901234', 1133, 'Single', 'Paracale, Camarines Norte', 'Secondary Education', 3, 'Student', 'United Methodist Church', 'PH1234567940', 'Yes', 'NHTS'),
(106, 'Fernando', 'Velasco', 'Jimenez', 'III', '1984-03-10', 41, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09489012345', 1134, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 4, 'Mechanic', 'Roman Catholic', 'PH1234567941', 'No', 'LGU'),
(107, 'Marina', 'Aquino', 'Gutierrez', '', '1997-06-25', 28, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09490123456', 1135, 'Single', 'Basud, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Aglipayan', 'PH1234567942', 'No', 'Private'),
(108, 'Ignacio', 'Diaz', 'Ferrer', 'IV', '1980-02-14', 45, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09501234567', 1136, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Fisherman', 'Roman Catholic', 'PH1234567943', 'Yes', 'Self-Employed'),
(109, 'Camila', 'Manalo', 'Rivera', '', '1991-10-08', 33, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09512345678', 1137, 'Married', 'Labo, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'Islam', 'PH1234567944', 'No', 'LGU'),
(110, 'Alberto', 'Perez', 'Alvarez', 'V', '1976-12-30', 48, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09523456789', 1138, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Engineer', 'Roman Catholic', 'PH1234567945', 'No', 'Private'),
(111, 'Cecilia', 'Santos', 'Domingo', '', '1994-07-17', 31, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09534567890', 1139, 'Single', 'Mercedes, Camarines Norte', 'Secondary Education', 3, 'Nurse', 'Assemblies of God', 'PH1234567946', 'Yes', 'NHTS'),
(112, 'Diego', 'Mendoza', 'Jimenez', '', '1988-11-29', 36, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09545678901', 1140, 'Married', 'Daet, Camarines Norte', 'Primary Education', 4, 'Farmer', 'Roman Catholic', 'PH1234567947', 'No', 'LGU'),
(113, 'Adela', 'Ramos', 'Castillo', '', '1999-05-05', 26, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09556789012', 1141, 'Single', 'Basud, Camarines Norte', 'Tertiary Education', 3, 'Student', 'United Methodist Church', 'PH1234567948', 'No', 'Private'),
(114, 'Mauricio', 'Lopez', 'Villanueva', 'Sr.', '1977-08-21', 48, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09567890123', 1142, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 3, 'Driver', 'Iglesia ni Cristo', 'PH1234567949', 'Yes', 'NHTS'),
(115, 'Paula', 'Del Rosario', 'Salazar', '', '1992-01-02', 33, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09578901234', 1143, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Teacher', 'Roman Catholic', 'PH1234567950', 'No', 'LGU'),
(116, 'Vicente', 'Reyes', 'Ortiz', 'II', '1985-09-11', 40, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09589012345', 1144, 'Married', 'Jose Panganiban, Camarines Norte', 'Primary Education', 4, 'Carpenter', 'Aglipayan', 'PH1234567951', 'No', 'Private'),
(117, 'Lucia', 'Cruz', 'Del Rosario', '', '1996-03-23', 29, 'Female', 'Purok 6 - Barangay 6 Daet, Camarines Norte', '09590123456', 1145, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Student', 'Roman Catholic', 'PH1234567952', 'Yes', 'Self-Employed'),
(118, 'Ernesto', 'Navarro', 'Rivera', 'III', '1974-07-08', 51, 'Male', 'Purok 7 - Barangay 6 Daet, Camarines Norte', '09601234567', 1146, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Fisherman', 'Islam', 'PH1234567953', 'No', 'LGU'),
(119, 'Helena', 'Velasco', 'Gutierrez', '', '1993-10-16', 31, 'Female', 'Purok 8 - Barangay 6 Daet, Camarines Norte', '09612345678', 1147, 'Widowed', 'Labo, Camarines Norte', 'Postgraduate', 3, 'Nurse', 'Roman Catholic', 'PH1234567954', 'Yes', 'Private'),
(120, 'Salvador', 'Aquino', 'Aguilar', 'Jr.', '1981-12-27', 43, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09623456789', 1148, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Mechanic', 'Assemblies of God', 'PH1234567955', 'No', 'NHTS'),
(121, 'Nora', 'Diaz', 'Morales', '', '1997-02-09', 28, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09634567890', 1149, 'Single', 'Mercedes, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH1234567956', 'No', 'LGU'),
(122, 'Alfonso', 'Manalo', 'Domingo', '', '1983-05-20', 42, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09645678901', 1150, 'Married', 'Daet, Camarines Norte', 'Primary Education', 4, 'Farmer', 'United Methodist Church', 'PH1234567957', 'Yes', 'Private'),
(123, 'Arnel', 'Reyes', 'Domingo', 'Jr.', '1987-03-12', 38, 'Male', 'Purok 2 - Barangay 7 Daet, Camarines Norte', '09171230001', 1101, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Tricycle Driver', 'Roman Catholic', 'PH2234567801', 'No', 'LGU'),
(124, 'Clarissa', 'Villanueva', 'Morales', '', '1994-08-25', 31, 'Female', 'Purok 5 - Barangay 8 Daet, Camarines Norte', '09182340002', 1102, 'Single', 'Labo, Camarines Norte', 'Tertiary Education', 3, 'Teacher', 'Iglesia ni Cristo', 'PH2234567802', 'Yes', 'NHTS'),
(125, 'Gilbert', 'Santos', 'Navarro', 'Sr.', '1979-01-14', 46, 'Male', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09193450003', 1103, 'Married', 'Daet, Camarines Norte', 'Primary Education', 3, 'Farmer', 'United Methodist Church', 'PH2234567803', 'No', 'Private'),
(126, 'Rowena', 'Torres', 'Garcia', '', '1985-09-07', 40, 'Female', 'Purok 6 - Barangay San Isidro Daet, Camarines Norte', '09204560004', 1104, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 3, 'Vendor', 'Roman Catholic', 'PH2234567804', 'No', 'LGU'),
(127, 'Marlon', 'Cruz', 'Velasco', 'II', '1991-12-18', 33, 'Male', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte', '09215670005', 1105, 'Single', 'Basud, Camarines Norte', 'Tertiary Education', 3, 'Mechanic', 'Assemblies of God', 'PH2234567805', 'Yes', 'Self-Employed'),
(128, 'Janice', 'Ramos', 'Delos Reyes', '', '1993-06-02', 32, 'Female', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte', '09226780006', 1106, 'Married', 'Daet, Camarines Norte', 'Postgraduate', 3, 'Nurse', 'Roman Catholic', 'PH2234567806', 'No', 'Private'),
(129, 'Ronald', 'Flores', 'Castillo', 'III', '1982-11-20', 42, 'Male', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte', '09237890007', 1107, 'Married', 'Jose Panganiban, Camarines Norte', 'Secondary Education', 3, 'Carpenter', 'Islam', 'PH2234567807', 'Yes', 'LGU'),
(130, 'Liza', 'Aquino', 'Jimenez', '', '1990-05-05', 35, 'Female', 'Purok 7 - Barangay 7 Daet, Camarines Norte', '09248900008', 1108, 'Single', 'Talisay, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH2234567808', 'No', 'NHTS'),
(131, 'Victor', 'Manalo', 'Gutierrez', 'Sr.', '1978-07-13', 47, 'Male', 'Purok 8 - Barangay 8 Daet, Camarines Norte', '09259010009', 1109, 'Married', 'Daet, Camarines Norte', 'Primary Education', 4, 'Fisherman', 'Iglesia ni Cristo', 'PH2234567809', 'No', 'Private'),
(132, 'Rachelle', 'Del Rosario', 'Alvarez', '', '1997-10-29', 27, 'Female', 'Purok 4 - Barangay Gubat Daet, Camarines Norte', '09260120010', 1110, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Aglipayan', 'PH2234567810', 'No', 'LGU'),
(133, 'Henry', 'Perez', 'Salazar', 'Jr.', '1983-02-17', 42, 'Male', 'Purok 1 - Barangay San Isidro Daet, Camarines Norte', '09271230011', 1111, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 3, 'Driver', 'Roman Catholic', 'PH2234567811', 'Yes', 'Self-Employed'),
(134, 'Fiona', 'Garcia', 'Rivera', '', '1986-04-09', 39, 'Female', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09282340012', 1112, 'Married', 'Daet, Camarines Norte', 'Postgraduate', 3, 'Doctor', 'United Methodist Church', 'PH2234567812', 'No', 'LGU'),
(135, 'Patrick', 'Lopez', 'Ortega', 'II', '1992-09-21', 32, 'Male', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09293450013', 1113, 'Single', 'Mercedes, Camarines Norte', 'Tertiary Education', 3, 'Engineer', 'Roman Catholic', 'PH2234567813', 'No', 'Private'),
(136, 'Angela', 'Santiago', 'Marquez', '', '1995-01-30', 30, 'Female', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte', '09304560014', 1114, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Assemblies of God', 'PH2234567814', 'Yes', 'NHTS'),
(137, 'Dennis', 'Castro', 'Villanueva', 'Sr.', '1980-06-15', 45, 'Male', 'Purok 1 - Barangay 8 Daet, Camarines Norte', '09315670015', 1115, 'Married', 'Basud, Camarines Norte', 'Secondary Education', 4, 'Farmer', 'Roman Catholic', 'PH2234567815', 'No', 'LGU'),
(138, 'Beatriz', 'Aguilar', 'Santos', '', '1996-12-08', 28, 'Female', 'Purok 7 - Barangay Gubat Daet, Camarines Norte', '09326780016', 1116, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Iglesia ni Cristo', 'PH2234567816', 'No', 'Private'),
(139, 'Oscar', 'Domingo', 'Morales', 'III', '1977-03-28', 48, 'Male', 'Purok 4 - Barangay San Isidro Daet, Camarines Norte', '09337890017', 1117, 'Married', 'Jose Panganiban, Camarines Norte', 'Primary Education', 3, 'Laborer', 'Roman Catholic', 'PH2234567817', 'Yes', 'NHTS'),
(140, 'Marites', 'Bautista', 'Cruz', '', '1988-11-19', 36, 'Female', 'Purok 8 - Barangay Cobangbang Daet, Camarines Norte', '09348900018', 1118, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 3, 'Vendor', 'Aglipayan', 'PH2234567818', 'No', 'Self-Employed'),
(141, 'Joel', 'Rivera', 'Aquino', 'IV', '1990-05-14', 35, 'Male', 'Purok 7 - Barangay Bagasbas Daet, Camarines Norte', '09359010019', 1119, 'Single', 'Labo, Camarines Norte', 'Tertiary Education', 3, 'Technician', 'Islam', 'PH2234567819', 'No', 'LGU'),
(142, 'Michelle', 'Ferrer', 'Torres', '', '1998-08-03', 27, 'Female', 'Purok 5 - Barangay Mambalite Daet, Camarines Norte', '09360120020', 1120, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH2234567820', 'No', 'Private'),
(143, 'Ernesto', 'Gutierrez', 'Delos Santos', 'V', '1981-07-22', 44, 'Male', 'Purok 6 - Barangay 7 Daet, Camarines Norte', '09371230021', 1121, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 4, 'Driver', 'Assemblies of God', 'PH2234567821', 'Yes', 'LGU'),
(144, 'Janelle', 'Morales', 'Ramos', '', '1999-02-18', 26, 'Female', 'Purok 9 - Barangay 8 Daet, Camarines Norte', '09382340022', 1122, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 3, 'Student', 'Roman Catholic', 'PH2234567822', 'No', 'NHTS'),
(145, 'Manuel', 'Jimenez', 'Del Rosario', 'II', '1984-12-05', 40, 'Male', 'Purok 2 - Barangay Gubat Daet, Camarines Norte', '09393450023', 1123, 'Married', 'Basud, Camarines Norte', 'Secondary Education', 3, 'Fisherman', 'United Methodist Church', 'PH2234567823', 'No', 'Private'),
(146, 'Sylvia', 'Ortega', 'Castro', '', '1992-06-10', 33, 'Female', 'Purok 3 - Barangay San Isidro Daet, Camarines Norte', '09404560024', 1124, 'Single', 'Daet, Camarines Norte', 'Postgraduate', 3, 'Nurse', 'Roman Catholic', 'PH2234567824', 'No', 'LGU'),
(147, 'Alfredo', 'Delos Reyes', 'Villanueva', 'Sr.', '1976-04-01', 49, 'Male', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09415670025', 1125, 'Married', 'Daet, Camarines Norte', 'Primary Education', 4, 'Farmer', 'Iglesia ni Cristo', 'PH2234567825', 'Yes', 'NHTS'),
(148, 'Aiden', 'Cruz', 'Ramos', '', '2017-05-12', 8, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09171234567', 3301, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(149, 'Mia', 'Villanueva', 'Santos', '', '2018-09-03', 7, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09281234567', 3302, 'Single', 'Labo, Camarines Norte', 'Primary Education', 0, 'Student', 'Iglesia ni Cristo', NULL, 'No', 'Private'),
(150, 'Liam', 'Reyes', 'Fernandez', 'Jr.', '2019-11-25', 5, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09381234567', 3303, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(151, 'Sophia', 'Gomez', 'Dela Cruz', '', '2020-02-15', 5, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09481234567', 3304, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'United Methodist Church', NULL, 'No', 'LGU'),
(152, 'Noah', 'Delos Santos', 'Garcia', '', '2016-07-28', 9, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09581234567', 3305, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(153, 'Isabella', 'Martinez', 'Lopez', '', '2021-01-19', 4, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09182345670', 3306, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Islam', NULL, 'No', 'NHTS'),
(154, 'Lucas', 'Torres', 'Mendoza', '', '2015-04-07', 10, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09283456771', 3307, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(155, 'Amelia', 'Flores', 'Castillo', '', '2022-06-14', 3, 'Female', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09384561234', 3308, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'Private'),
(156, 'Ethan', 'Rivera', 'Cabrera', '', '2013-10-21', 11, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09485671234', 3309, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(157, 'Olivia', 'Jimenez', 'Domingo', '', '2019-03-10', 6, 'Female', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09586712345', 3310, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Aglipayan', NULL, 'No', 'LGU'),
(158, 'Benjamin', 'Santiago', 'Villanueva', '', '2014-12-30', 10, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09187654321', 3311, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(159, 'Emma', 'Aguilar', 'Reyes', '', '2017-08-09', 8, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09288765432', 3312, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(160, 'Jacob', 'Navarro', 'Gonzales', '', '2015-06-01', 10, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09389876543', 3313, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Assemblies of God', NULL, 'No', 'LGU'),
(161, 'Sofia', 'Castro', 'Ramos', '', '2018-11-17', 6, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09480987654', 3314, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(162, 'Michael', 'Bautista', 'Perez', '', '2012-04-04', 13, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09582098765', 3315, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(163, 'Chloe', 'Padilla', 'Hernandez', '', '2020-09-12', 5, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09183012345', 3316, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Islam', NULL, 'No', 'LGU'),
(164, 'James', 'Serrano', 'Gutierrez', '', '2016-01-23', 9, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09284123456', 3317, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(165, 'Charlotte', 'Ortiz', 'Flores', '', '2021-05-05', 4, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09385234567', 3318, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'NHTS'),
(166, 'Alexander', 'Domingo', 'Torres', '', '2019-12-14', 5, 'Male', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09486345678', 3319, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Iglesia ni Cristo', NULL, 'No', 'LGU'),
(167, 'Aria', 'Lopez', 'Cruz', '', '2014-07-29', 11, 'Female', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09587456789', 3320, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(168, 'Daniel', 'Fernandez', 'Santos', '', '2013-02-02', 12, 'Male', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09188567890', 3321, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'United Methodist Church', NULL, 'No', 'NHTS'),
(169, 'Grace', 'Gonzales', 'Reyes', '', '2022-10-18', 2, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09289654321', 3322, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'LGU'),
(170, 'Matthew', 'Cruz', 'Garcia', '', '2017-03-09', 8, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09380765432', 3323, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(171, 'Ella', 'Villanueva', 'Martinez', '', '2015-09-22', 9, 'Female', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09481876543', 3324, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(172, 'David', 'Ramos', 'Castillo', '', '2018-12-11', 6, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09582987654', 3325, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Aglipayan', NULL, 'No', 'LGU'),
(173, 'Nathan', 'Santos', 'Rivera', '', '2016-02-14', 9, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09181234567', 3326, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(174, 'Zoe', 'Reyes', 'Cortez', '', '2020-08-05', 5, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09282345678', 3327, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'Private'),
(175, 'Caleb', 'Torres', 'Domingo', '', '2014-11-27', 10, 'Male', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09383456789', 3328, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'United Methodist Church', NULL, 'No', 'NHTS'),
(176, 'Leah', 'Villanueva', 'Mendoza', '', '2019-05-16', 6, 'Female', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09484567890', 3329, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(177, 'Samuel', 'Flores', 'Aguilar', '', '2017-01-08', 8, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09585678901', 3330, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Islam', NULL, 'No', 'Private'),
(178, 'Hannah', 'Delos Santos', 'Castillo', '', '2015-06-30', 10, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09186789012', 3331, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(179, 'Elijah', 'Ramos', 'Jimenez', '', '2018-03-11', 7, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09287890123', 3332, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Aglipayan', NULL, 'No', 'NHTS'),
(180, 'Victoria', 'Cruz', 'Lopez', '', '2021-07-22', 4, 'Female', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09388901234', 3333, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'Private'),
(181, 'Gabriel', 'Perez', 'Reyes', '', '2013-09-14', 12, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09489012345', 3334, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(182, 'Layla', 'Navarro', 'Cabrera', '', '2020-12-01', 4, 'Female', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09580123456', 3335, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'NHTS'),
(183, 'Isaac', 'Domingo', 'Gutierrez', '', '2017-04-23', 8, 'Male', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09181234568', 3336, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Assemblies of God', NULL, 'No', 'Private'),
(184, 'Scarlett', 'Aquino', 'Morales', '', '2016-10-06', 8, 'Female', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09282345679', 3337, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(185, 'Ethan', 'Bautista', 'Salazar', '', '2018-08-19', 7, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09383456780', 3338, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Islam', NULL, 'No', 'NHTS'),
(186, 'Camila', 'Mendoza', 'Delos Santos', '', '2014-02-25', 11, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09484567891', 3339, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(187, 'Julian', 'Torres', 'Rivera', '', '2022-01-13', 3, 'Male', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09585678902', 3340, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'United Methodist Church', NULL, 'No', 'Private'),
(188, 'Avery', 'Reyes', 'Ortiz', '', '2015-07-29', 10, 'Female', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09186789013', 3341, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(189, 'Mateo', 'Cruz', 'Fernandez', '', '2019-10-04', 5, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09287890124', 3342, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(190, 'Stella', 'Garcia', 'Ramos', '', '2013-05-20', 12, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09388901235', 3343, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(191, 'Logan', 'Villanueva', 'Aguilar', '', '2017-09-11', 8, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09489012346', 3344, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Iglesia ni Cristo', NULL, 'No', 'NHTS'),
(192, 'Aurora', 'Ramos', 'Jimenez', '', '2021-11-28', 3, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09580123457', 3345, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, '', 'Roman Catholic', NULL, 'No', 'LGU'),
(193, 'Henry', 'Delos Santos', 'Castro', '', '2016-06-17', 9, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09181234569', 3346, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'Private'),
(194, 'Clara', 'Navarro', 'Lopez', '', '2018-01-30', 7, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09282345680', 3347, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS'),
(195, 'Sebastian', 'Domingo', 'Villanueva', '', '2014-03-07', 11, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09383456781', 3348, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Assemblies of God', NULL, 'No', 'LGU'),
(196, 'Maya', 'Flores', 'Santos', '', '2019-12-24', 5, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09484567892', 3349, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'Private'),
(197, 'Christopher', 'Aquino', 'Reyes', '', '2013-08-02', 12, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09585678903', 3350, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'NHTS');
INSERT INTO `patients` (`patient_id`, `first_name`, `middle_name`, `last_name`, `extension`, `date_of_birth`, `age`, `sex`, `address`, `contact_number`, `family_serial_no`, `civil_status`, `birthplace`, `educational_attainment`, `birth_weight`, `occupation`, `religion`, `philhealth_member_no`, `fourps_status`, `category`) VALUES
(198, 'Pedro', 'D', 'Manalo', '', '1950-07-11', 75, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09123458701', 201, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH201111', 'No', 'LGU'),
(199, 'Maria', 'G', 'Santiago', '', '1965-04-22', 60, 'Female', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09234567902', 202, 'Widowed', 'Labo, Camarines Norte', 'Primary Education', 0, 'Vendor', 'Iglesia ni Cristo', 'PH201112', 'No', 'NHTS'),
(200, 'Vicente', 'R', 'Aquino', 'Sr.', '1949-09-05', 76, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09198765433', 203, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Retired', 'Roman Catholic', 'PH201113', 'No', 'Private'),
(201, 'Elena', 'M', 'Reyes', '', '1955-12-19', 69, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09187654322', 204, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Aglipayan', 'PH201114', 'No', 'LGU'),
(202, 'Josefina', 'C', 'Dela Cruz', '', '1960-06-08', 65, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09234567891', 205, 'Widowed', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Self-Employed', 'Roman Catholic', 'PH201115', 'No', 'NHTS'),
(203, 'Rodolfo', 'B', 'Garcia', 'Jr.', '1948-11-27', 76, 'Male', 'Purok 7 - Barangay 6 Daet, Camarines Norte', '09345678912', 206, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Carpenter', 'Roman Catholic', 'PH201116', 'No', 'LGU'),
(204, 'Carmen', 'F', 'Lopez', '', '1958-08-14', 67, 'Female', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09122233445', 207, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'United Methodist Church', 'PH201117', 'No', 'Private'),
(205, 'Antonio', 'Q', 'Fernandez', '', '1953-01-30', 72, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09211122334', 208, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH201118', 'No', 'LGU'),
(206, 'Amelia', 'T', 'Rivera', '', '1962-02-11', 63, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09155566778', 209, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH201119', 'No', 'NHTS'),
(207, 'Teodoro', 'S', 'Castro', '', '1947-10-03', 77, 'Male', 'Purok 8 - Barangay 6 Daet, Camarines Norte', '09388899900', 210, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH201120', 'No', 'LGU'),
(208, 'Consuelo', 'V', 'Diaz', '', '1956-06-25', 69, 'Female', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09100011223', 211, 'Widowed', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH201121', 'No', 'NHTS'),
(209, 'Gregorio', 'M', 'Bautista', '', '1952-05-07', 73, 'Male', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09211133445', 212, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Farmer', 'Iglesia ni Cristo', 'PH201122', 'No', 'LGU'),
(210, 'Isabel', 'A', 'Mendoza', '', '1964-11-02', 60, 'Female', 'Purok 6 - Barangay 6 Daet, Camarines Norte', '09344455667', 213, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH201123', 'No', 'Private'),
(211, 'Ernesto', 'J', 'Flores', 'Sr.', '1946-09-18', 78, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09166677889', 214, 'Married', 'Paracale, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH201124', 'No', 'LGU'),
(212, 'Aurora', 'N', 'Domingo', '', '1961-12-27', 63, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09299911122', 215, 'Separated', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Self-Employed', 'Assemblies of God', 'PH201125', 'No', 'NHTS'),
(213, 'Felipe', 'R', 'Santos', '', '1951-03-10', 74, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09177788990', 216, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH201126', 'No', 'LGU'),
(214, 'Dolores', 'E', 'Jimenez', '', '1954-07-20', 71, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09233344556', 217, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH201127', 'No', 'Private'),
(215, 'Leandro', 'O', 'Torres', '', '1949-01-29', 76, 'Male', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09311122233', 218, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH201128', 'No', 'LGU'),
(216, 'Patricia', 'K', 'Vargas', '', '1963-05-15', 62, 'Female', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09133344455', 219, 'Married', 'Paracale, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Roman Catholic', 'PH201129', 'No', 'LGU'),
(217, 'Hector', 'L', 'Morales', '', '1957-09-09', 68, 'Male', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09244455566', 220, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Roman Catholic', 'PH201130', 'No', 'NHTS'),
(218, 'Mercedes', 'S', 'Salazar', '', '1960-10-12', 64, 'Female', 'Purok 7 - Barangay 6 Daet, Camarines Norte', '09155566677', 221, 'Widowed', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH201131', 'No', 'LGU'),
(219, 'Crisanto', 'P', 'Guerrero', '', '1948-02-17', 77, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09322233344', 222, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Iglesia ni Cristo', 'PH201132', 'No', 'Private'),
(220, 'Salvacion', 'U', 'Aguilar', '', '1959-06-21', 66, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09277788899', 223, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH201133', 'No', 'LGU'),
(221, 'Roberto', 'G', 'Francisco', '', '1952-11-30', 72, 'Male', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09188899900', 224, 'Separated', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Self-Employed', 'Roman Catholic', 'PH201134', 'No', 'NHTS'),
(222, 'Pedro', 'R', 'Alvarez', '', '1950-02-15', 75, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09123450001', 301, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Farmer', 'Roman Catholic', 'PH301001', 'No', 'LGU'),
(223, 'Maria', 'S', 'Villanueva', '', '1962-06-20', 63, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09123450002', 302, 'Widowed', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Iglesia ni Cristo', 'PH301002', 'No', 'NHTS'),
(224, 'Juan', 'D', 'Santos', 'Sr.', '1948-11-05', 76, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09123450003', 303, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Retired', 'Roman Catholic', 'PH301003', 'No', 'Private'),
(225, 'Elena', 'M', 'Torres', '', '1957-08-12', 68, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09123450004', 304, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Aglipayan', 'PH301004', 'No', 'LGU'),
(226, 'Rogelio', 'C', 'Dela Cruz', '', '1953-01-25', 72, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09123450005', 305, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Carpenter', 'Roman Catholic', 'PH301005', 'No', 'NHTS'),
(227, 'Consuelo', 'V', 'Bautista', '', '1960-04-18', 65, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09123450006', 306, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301006', 'No', 'LGU'),
(228, 'Ernesto', 'L', 'Reyes', 'Jr.', '1949-07-02', 76, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09123450007', 307, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Retired', 'United Methodist Church', 'PH301007', 'No', 'Private'),
(229, 'Carmen', 'P', 'Navarro', '', '1954-03-28', 71, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09123450008', 308, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH301008', 'No', 'LGU'),
(230, 'Antonio', 'F', 'Morales', '', '1956-12-09', 68, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09123450009', 309, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Iglesia ni Cristo', 'PH301009', 'No', 'NHTS'),
(231, 'Josefina', 'T', 'Jimenez', '', '1963-05-14', 62, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09123450010', 310, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301010', 'No', 'Private'),
(232, 'Leandro', 'R', 'Castro', '', '1952-10-23', 72, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09123450011', 311, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH301011', 'No', 'LGU'),
(233, 'Dolores', 'E', 'Gutierrez', '', '1955-09-08', 70, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09123450012', 312, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Aglipayan', 'PH301012', 'No', 'NHTS'),
(234, 'Hector', 'G', 'Aguilar', '', '1947-06-30', 78, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09123450013', 313, 'Married', 'Paracale, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH301013', 'No', 'Private'),
(235, 'Patricia', 'N', 'Salazar', '', '1961-01-05', 64, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09123450014', 314, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301014', 'No', 'LGU'),
(236, 'Ramon', 'O', 'Domingo', 'Sr.', '1946-03-12', 79, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09123450015', 315, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Farmer', 'Roman Catholic', 'PH301015', 'No', 'NHTS'),
(237, 'Aurora', 'Q', 'Mendoza', '', '1964-07-19', 61, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09123450016', 316, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH301016', 'No', 'LGU'),
(238, 'Felipe', 'U', 'Santiago', '', '1951-11-27', 73, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09123450017', 317, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Roman Catholic', 'PH301017', 'No', 'Private'),
(239, 'Isabel', 'Y', 'Francisco', '', '1958-02-09', 67, 'Female', 'Purok 2 - Barangay 1 Daet, Camarines Norte', '09123450018', 318, 'Widowed', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301018', 'No', 'NHTS'),
(240, 'Teodoro', 'A', 'Ramos', '', '1949-04-16', 76, 'Male', 'Purok 3 - Barangay 1 Daet, Camarines Norte', '09123450019', 319, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH301019', 'No', 'LGU'),
(241, 'Aurora', 'H', 'Velasco', '', '1960-09-25', 64, 'Female', 'Purok 4 - Barangay 1 Daet, Camarines Norte', '09123450020', 320, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Assemblies of God', 'PH301020', 'No', 'Private'),
(242, 'Gregorio', 'M', 'Cortez', '', '1952-12-01', 72, 'Male', 'Purok 5 - Barangay 1 Daet, Camarines Norte', '09123450021', 321, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Farmer', 'Roman Catholic', 'PH301021', 'No', 'LGU'),
(243, 'Celia', 'J', 'Delos Santos', '', '1963-05-07', 62, 'Female', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '09123450022', 322, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301022', 'No', 'NHTS'),
(244, 'Roberto', 'K', 'Aquino', '', '1948-10-10', 76, 'Male', 'Purok 7 - Barangay 1 Daet, Camarines Norte', '09123450023', 323, 'Married', 'Paracale, Camarines Norte', 'Primary Education', 0, 'Retired', 'Iglesia ni Cristo', 'PH301023', 'No', 'Private'),
(245, 'Mercedes', 'D', 'Guerrero', '', '1959-01-22', 66, 'Female', 'Purok 8 - Barangay 1 Daet, Camarines Norte', '09123450024', 324, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH301024', 'No', 'LGU'),
(246, 'Crisanto', 'Z', 'Fernandez', '', '1954-06-11', 71, 'Male', 'Purok 1 - Barangay 1 Daet, Camarines Norte', '09123450025', 325, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Self-Employed', 'Roman Catholic', 'PH301025', 'No', 'NHTS'),
(247, 'Miguel', 'D', 'Cruz', '', '2018-03-12', 7, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09987654321', 301, 'Single', 'Daet, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', 'PH301111', 'Yes', 'NHTS'),
(248, 'Angelica', 'M', 'Torres', '', '2015-09-05', 10, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09123456789', 302, 'Single', 'Daet, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', 'PH301112', 'Yes', 'LGU'),
(249, 'Joshua', 'R', 'Santos', '', '2012-11-18', 12, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09223344556', 303, 'Single', 'Labo, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', 'PH301113', 'No', 'Private'),
(250, 'Andrea', 'L', 'Reyes', '', '2010-07-21', 15, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09334455667', 304, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', 'PH301114', 'No', 'LGU'),
(251, 'Kevin', 'S', 'Garcia', '', '2008-01-03', 17, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09445566778', 305, 'Single', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Student', 'Iglesia ni Cristo', 'PH301115', 'No', 'NHTS'),
(252, 'Monica', 'G', 'Lopez', '', '2005-05-30', 20, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09556677889', 306, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', 'PH301116', 'Yes', 'Private'),
(253, 'Jerome', 'C', 'Bautista', '', '2002-10-09', 22, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09667788990', 307, 'Single', 'Labo, Camarines Norte', 'Tertiary Education', 0, 'Clerk', 'Roman Catholic', 'PH301117', 'No', 'LGU'),
(254, 'Alyssa', 'F', 'Domingo', '', '1999-12-14', 25, 'Female', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09778899001', 308, 'Single', 'Mercedes, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'Roman Catholic', 'PH301118', 'No', 'Private'),
(255, 'Carlo', 'V', 'Aquino', '', '1996-04-23', 29, 'Male', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09889900112', 309, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Engineer', 'Roman Catholic', 'PH301119', 'No', 'LGU'),
(256, 'Joanna', 'T', 'Rivera', '', '1993-09-11', 32, 'Female', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09990011223', 310, 'Married', 'Paracale, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Roman Catholic', 'PH301120', 'No', 'Private'),
(257, 'Rolando', 'P', 'Mendoza', '', '1989-07-04', 36, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09112233445', 311, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH301121', 'No', 'NHTS'),
(258, 'Cecilia', 'A', 'Villanueva', '', '1985-05-16', 40, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09223344557', 312, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301122', 'No', 'LGU'),
(259, 'Danilo', 'B', 'Santiago', '', '1980-08-19', 45, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09334455668', 313, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Roman Catholic', 'PH301123', 'No', 'Private'),
(260, 'Marites', 'N', 'Ramos', '', '1977-03-25', 48, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09445566779', 314, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH301124', 'Yes', 'NHTS'),
(261, 'Renato', 'H', 'Fernandez', 'Jr.', '1974-11-02', 50, 'Male', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09556677880', 315, 'Married', 'Labo, Camarines Norte', 'Tertiary Education', 0, 'Driver', 'Roman Catholic', 'PH301125', 'No', 'LGU'),
(262, 'Evelyn', 'C', 'Jimenez', '', '1970-06-18', 55, 'Female', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09667788991', 316, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Self-Employed', 'Roman Catholic', 'PH301126', 'No', 'Private'),
(263, 'Manuel', 'J', 'Castro', 'Sr.', '1968-01-07', 57, 'Male', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09778899002', 317, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH301127', 'No', 'LGU'),
(264, 'Lourdes', 'R', 'Diaz', '', '1965-02-15', 60, 'Female', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09889900113', 318, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH301128', 'No', 'NHTS'),
(265, 'Eduardo', 'E', 'Morales', '', '1962-09-29', 62, 'Male', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09990011224', 319, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Retired', 'Roman Catholic', 'PH301129', 'No', 'Private'),
(266, 'Gloria', 'U', 'Aguilar', '', '1958-12-05', 66, 'Female', 'Purok 5 - Barangay 6 Daet, Camarines Norte', '09112233446', 320, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH301130', 'No', 'LGU'),
(267, 'Teodoro', 'K', 'Guerrero', '', '1954-04-22', 71, 'Male', 'Purok 1 - Barangay 6 Daet, Camarines Norte', '09223344558', 321, 'Married', 'Labo, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH301131', 'No', 'NHTS'),
(268, 'Rosario', 'L', 'Salazar', '', '1950-10-19', 74, 'Female', 'Purok 2 - Barangay 6 Daet, Camarines Norte', '09334455669', 322, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH301132', 'No', 'Private'),
(269, 'Francisco', 'M', 'Torres', '', '1947-08-01', 78, 'Male', 'Purok 3 - Barangay 6 Daet, Camarines Norte', '09445566770', 323, 'Widowed', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH301133', 'No', 'LGU'),
(270, 'Virginia', 'O', 'Flores', '', '1944-05-09', 81, 'Female', 'Purok 4 - Barangay 6 Daet, Camarines Norte', '09556677881', 324, 'Widowed', 'Paracale, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH301134', 'No', 'Private'),
(271, 'Alvin', 'D', 'Santos', '', '2022-05-18', 3, 'Male', 'Purok 1 - Barangay 7 Daet, Camarines Norte', '09123456001', 401, 'Single', 'Daet, Camarines Norte', 'N/A', 3, NULL, 'Roman Catholic', 'PH401111', 'Yes', 'NHTS'),
(272, 'Bea', 'G', 'Reyes', '', '2019-11-07', 5, 'Female', 'Purok 2 - Barangay 7 Daet, Camarines Norte', '09123456002', 402, 'Single', 'Daet, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', 'PH401112', 'Yes', 'LGU'),
(273, 'Charles', 'A', 'Lopez', '', '2016-02-14', 9, 'Male', 'Purok 3 - Barangay 7 Daet, Camarines Norte', '09123456003', 403, 'Single', 'Labo, Camarines Norte', 'Elementary', 0, NULL, 'Iglesia ni Cristo', 'PH401113', 'No', 'Private'),
(274, 'Diana', 'E', 'Torres', '', '2013-06-10', 12, 'Female', 'Purok 4 - Barangay 7 Daet, Camarines Norte', '09123456004', 404, 'Single', 'Daet, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', 'PH401114', 'No', 'LGU'),
(275, 'Earl', 'M', 'Rivera', '', '2010-09-28', 14, 'Male', 'Purok 5 - Barangay 7 Daet, Camarines Norte', '09123456005', 405, 'Single', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', 'PH401115', 'Yes', 'NHTS'),
(276, 'Fiona', 'P', 'Aquino', '', '2007-03-22', 18, 'Female', 'Purok 6 - Barangay 7 Daet, Camarines Norte', '09123456006', 406, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', 'PH401116', 'No', 'Private'),
(277, 'Gabriel', 'R', 'Domingo', '', '2003-08-01', 22, 'Male', 'Purok 7 - Barangay 7 Daet, Camarines Norte', '09123456007', 407, 'Single', 'Paracale, Camarines Norte', 'Tertiary Education', 0, 'Call Center Agent', 'Roman Catholic', 'PH401117', 'No', 'LGU'),
(278, 'Hazel', 'S', 'Garcia', '', '1999-12-16', 25, 'Female', 'Purok 1 - Barangay 7 Daet, Camarines Norte', '09123456008', 408, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'Roman Catholic', 'PH401118', 'No', 'Private'),
(279, 'Ian', 'L', 'Castro', '', '1995-04-05', 30, 'Male', 'Purok 2 - Barangay 7 Daet, Camarines Norte', '09123456009', 409, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Roman Catholic', 'PH401119', 'No', 'LGU'),
(280, 'Jenny', 'T', 'Vargas', '', '1990-01-20', 35, 'Female', 'Purok 3 - Barangay 7 Daet, Camarines Norte', '09123456010', 410, 'Married', 'Mercedes, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Roman Catholic', 'PH401120', 'No', 'NHTS'),
(281, 'Kenneth', 'B', 'Santiago', '', '1987-09-30', 37, 'Male', 'Purok 4 - Barangay 7 Daet, Camarines Norte', '09123456011', 411, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Driver', 'Roman Catholic', 'PH401121', 'No', 'Private'),
(282, 'Lourdes', 'C', 'Jimenez', '', '1983-06-25', 42, 'Female', 'Purok 5 - Barangay 7 Daet, Camarines Norte', '09123456012', 412, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH401122', 'No', 'LGU'),
(283, 'Marvin', 'O', 'Diaz', '', '1980-02-12', 45, 'Male', 'Purok 6 - Barangay 7 Daet, Camarines Norte', '09123456013', 413, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH401123', 'No', 'Private'),
(284, 'Nina', 'H', 'Morales', '', '1977-07-09', 48, 'Female', 'Purok 7 - Barangay 7 Daet, Camarines Norte', '09123456014', 414, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH401124', 'Yes', 'LGU'),
(285, 'Oscar', 'I', 'Aguilar', '', '1973-03-15', 52, 'Male', 'Purok 1 - Barangay 7 Daet, Camarines Norte', '09123456015', 415, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Fisherman', 'Roman Catholic', 'PH401125', 'No', 'Private'),
(286, 'Pamela', 'J', 'Guerrero', '', '1970-05-27', 55, 'Female', 'Purok 2 - Barangay 7 Daet, Camarines Norte', '09123456016', 416, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Self-Employed', 'Roman Catholic', 'PH401126', 'No', 'NHTS'),
(287, 'Rodolfo', 'K', 'Villanueva', 'Jr.', '1967-01-18', 58, 'Male', 'Purok 3 - Barangay 7 Daet, Camarines Norte', '09123456017', 417, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Retired Driver', 'Roman Catholic', 'PH401127', 'No', 'LGU'),
(288, 'Sofia', 'N', 'Salazar', '', '1963-09-06', 62, 'Female', 'Purok 4 - Barangay 7 Daet, Camarines Norte', '09123456018', 418, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH401128', 'No', 'Private'),
(289, 'Tomas', 'E', 'Mendoza', '', '1959-12-22', 65, 'Male', 'Purok 5 - Barangay 7 Daet, Camarines Norte', '09123456019', 419, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Farmer', 'Roman Catholic', 'PH401129', 'No', 'LGU'),
(290, 'Ursula', 'Q', 'Francisco', '', '1956-08-13', 69, 'Female', 'Purok 6 - Barangay 7 Daet, Camarines Norte', '09123456020', 420, 'Married', 'Labo, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH401130', 'No', 'NHTS'),
(291, 'Victor', 'R', 'Domingo', '', '1952-04-17', 73, 'Male', 'Purok 7 - Barangay 7 Daet, Camarines Norte', '09123456021', 421, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH401131', 'No', 'Private'),
(292, 'Wilma', 'F', 'Santos', '', '1949-01-04', 76, 'Female', 'Purok 1 - Barangay 7 Daet, Camarines Norte', '09123456022', 422, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH401132', 'No', 'LGU'),
(293, 'Xavier', 'G', 'Cruz', '', '1946-10-20', 78, 'Male', 'Purok 2 - Barangay 7 Daet, Camarines Norte', '09123456023', 423, 'Married', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Retired Farmer', 'Roman Catholic', 'PH401133', 'No', 'NHTS'),
(294, 'Yolanda', 'L', 'Flores', '', '1942-07-29', 83, 'Female', 'Purok 3 - Barangay 7 Daet, Camarines Norte', '09123456024', 424, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH401134', 'No', 'Private'),
(295, 'Rogelio', 'Aquino', 'Delos Santos', '', '1950-03-12', 75, 'Male', 'Purok 1 - Barangay 8 Daet, Camarines Norte', '09191234501', 801, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Retired Farmer', 'Roman Catholic', 'PH8012345', 'No', 'LGU'),
(296, 'Marites', 'Villanueva', 'Reyes', '', '1985-07-08', 40, 'Female', 'Purok 2 - Barangay 8 Daet, Camarines Norte', '09191234502', 802, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Iglesia ni Cristo', 'PH8022345', 'No', 'Private'),
(297, 'Jerome', 'Santos', 'Garcia', 'Jr.', '2015-05-16', 10, 'Male', 'Purok 3 - Barangay 8 Daet, Camarines Norte', '09191234503', 803, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(298, 'Criselda', 'Martinez', 'Lopez', '', '1962-09-21', 62, 'Female', 'Purok 4 - Barangay 8 Daet, Camarines Norte', '09191234504', 804, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Aglipayan', 'PH8046789', 'No', 'Self-Employed'),
(299, 'Jun', 'Bautista', 'Fernandez', '', '2001-11-30', 23, 'Male', 'Purok 5 - Barangay 8 Daet, Camarines Norte', '09191234505', 805, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Service Crew', 'Roman Catholic', 'PH8054567', 'No', 'Private'),
(300, 'Evelyn', 'Gomez', 'Ramos', '', '1978-02-15', 47, 'Female', 'Purok 6 - Barangay 8 Daet, Camarines Norte', '09191234506', 806, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'United Methodist Church', 'PH8066789', 'Yes', 'LGU'),
(301, 'Dominic', 'Flores', 'Torres', '', '2018-08-03', 7, 'Male', 'Purok 7 - Barangay 8 Daet, Camarines Norte', '09191234507', 807, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(302, 'Lourdes', 'Rivera', 'Cruz', '', '1945-06-22', 80, 'Female', 'Purok 8 - Barangay 8 Daet, Camarines Norte', '09191234508', 808, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH8083456', 'No', 'LGU'),
(303, 'Jayson', 'Domingo', 'Mendoza', '', '1993-01-14', 32, 'Male', 'Purok 9 - Barangay 8 Daet, Camarines Norte', '09191234509', 809, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Technician', 'Islam', 'PH8091122', 'No', 'Private'),
(304, 'Clarissa', 'Peralta', 'Ramos', '', '2007-04-10', 18, 'Female', 'Purok 10 - Barangay 8 Daet, Camarines Norte', '09191234510', 810, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(305, 'Ramon', 'Santiago', 'Velasco', 'Sr.', '1955-10-05', 69, 'Male', 'Purok 1 - Barangay 8 Daet, Camarines Norte', '09191234511', 811, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH8115566', 'No', 'LGU'),
(306, 'Angelica', 'Mendoza', 'De Guzman', '', '1989-03-19', 36, 'Female', 'Purok 2 - Barangay 8 Daet, Camarines Norte', '09191234512', 812, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'Assemblies of God', 'PH8125566', 'No', 'Private'),
(307, 'Leo', 'Castillo', 'Aguilar', '', '2010-12-01', 14, 'Male', 'Purok 3 - Barangay 8 Daet, Camarines Norte', '09191234513', 813, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(308, 'Minda', 'Salazar', 'Francisco', '', '1972-11-09', 52, 'Female', 'Purok 4 - Barangay 8 Daet, Camarines Norte', '09191234514', 814, 'Separated', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH8149876', 'No', 'Self-Employed'),
(309, 'Emmanuel', 'Cruz', 'Panganiban', '', '1999-09-29', 25, 'Male', 'Purok 5 - Barangay 8 Daet, Camarines Norte', '09191234515', 815, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Driver', 'Roman Catholic', 'PH8153322', 'No', 'Private'),
(310, 'Arlene', 'Navarro', 'Villanueva', '', '1980-05-04', 45, 'Female', 'Purok 6 - Barangay 8 Daet, Camarines Norte', '09191234516', 816, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Office Clerk', 'Iglesia ni Cristo', 'PH8164455', 'Yes', 'LGU'),
(311, 'Daniel', 'Soriano', 'Morales', 'III', '2016-07-23', 9, 'Male', 'Purok 7 - Barangay 8 Daet, Camarines Norte', '09191234517', 817, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(312, 'Rosalinda', 'Jimenez', 'Santos', '', '1948-08-30', 77, 'Female', 'Purok 8 - Barangay 8 Daet, Camarines Norte', '09191234518', 818, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH8188765', 'No', 'LGU'),
(313, 'Victor', 'Reyes', 'Castro', '', '1968-01-26', 57, 'Male', 'Purok 9 - Barangay 8 Daet, Camarines Norte', '09191234519', 819, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Roman Catholic', 'PH8199988', 'No', 'Self-Employed'),
(314, 'Jessica', 'Flores', 'Diaz', '', '2005-03-11', 20, 'Female', 'Purok 10 - Barangay 8 Daet, Camarines Norte', '09191234520', 820, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'Private'),
(315, 'Rafael', 'Gutierrez', 'Lim', '', '1975-12-25', 49, 'Male', 'Purok 1 - Barangay 8 Daet, Camarines Norte', '09191234521', 821, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Fisherman', 'Roman Catholic', 'PH8215566', 'Yes', 'LGU'),
(316, 'Melissa', 'Torres', 'Cabrera', '', '1990-02-18', 35, 'Female', 'Purok 2 - Barangay 8 Daet, Camarines Norte', '09191234522', 822, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Cashier', 'Roman Catholic', 'PH8223344', 'No', 'Private'),
(317, 'Alvin', 'Hernandez', 'Santiago', '', '2009-09-05', 16, 'Male', 'Purok 3 - Barangay 8 Daet, Camarines Norte', '09191234523', 823, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(318, 'Nora', 'Delos Reyes', 'Padilla', '', '1960-04-20', 65, 'Female', 'Purok 4 - Barangay 8 Daet, Camarines Norte', '09191234524', 824, 'Widowed', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Aglipayan', 'PH8246677', 'No', 'Self-Employed'),
(319, 'Dennis', 'Aguilar', 'Martinez', '', '1996-10-01', 28, 'Male', 'Purok 5 - Barangay 8 Daet, Camarines Norte', '09191234525', 825, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Salesman', 'Roman Catholic', 'PH8257788', 'No', 'Private'),
(320, 'Alberto', 'Santos', 'De Leon', 'Sr.', '1952-01-15', 73, 'Male', 'Purok 1 - Barangay Gubat Daet, Camarines Norte', '09201234501', 901, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Retired Farmer', 'Roman Catholic', 'PH9012345', 'No', 'LGU'),
(321, 'Lucia', 'Villanueva', 'Cruz', '', '1983-06-10', 42, 'Female', 'Purok 1 - Barangay Gubat Daet, Camarines Norte', '09201234502', 902, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Iglesia ni Cristo', 'PH9022345', 'No', 'Private'),
(322, 'Ronaldo', 'Aquino', 'Reyes', '', '2006-09-25', 18, 'Male', 'Purok 1 - Barangay Gubat Daet, Camarines Norte', '09201234503', 903, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(323, 'Consuelo', 'Garcia', 'Lopez', '', '1968-03-02', 57, 'Female', 'Purok 1 - Barangay Gubat Daet, Camarines Norte', '09201234504', 904, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Aglipayan', 'PH9046789', 'No', 'Self-Employed'),
(324, 'Emmanuel', 'Martinez', 'Torres', '', '2017-08-19', 8, 'Male', 'Purok 1 - Barangay Gubat Daet, Camarines Norte', '09201234505', 905, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(325, 'Veronica', 'Ramos', 'Del Rosario', '', '1975-12-11', 49, 'Female', 'Purok 2 - Barangay Gubat Daet, Camarines Norte', '09201234506', 906, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', 'PH9065566', 'Yes', 'LGU'),
(326, 'Pedro', 'Navarro', 'Domingo', '', '1998-05-05', 27, 'Male', 'Purok 2 - Barangay Gubat Daet, Camarines Norte', '09201234507', 907, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Technician', 'Islam', 'PH9071122', 'No', 'Private'),
(327, 'Maria', 'Jimenez', 'Velasco', '', '1947-04-28', 78, 'Female', 'Purok 2 - Barangay Gubat Daet, Camarines Norte', '09201234508', 908, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH9083344', 'No', 'LGU'),
(328, 'Anthony', 'Gutierrez', 'Aguilar', 'Jr.', '2003-11-14', 21, 'Male', 'Purok 2 - Barangay Gubat Daet, Camarines Norte', '09201234509', 909, 'Single', '', 'Tertiary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'Private'),
(329, 'Cynthia', 'Santiago', 'Francisco', '', '1987-09-09', 38, 'Female', 'Purok 2 - Barangay Gubat Daet, Camarines Norte', '09201234510', 910, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Cashier', 'Assemblies of God', 'PH9104455', 'No', 'LGU'),
(330, 'Edgardo', 'Hernandez', 'Castro', '', '1960-10-20', 64, 'Male', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09201234511', 911, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Roman Catholic', 'PH9116677', 'No', 'Self-Employed'),
(331, 'Jocelyn', 'Panganiban', 'Mendoza', '', '1992-03-16', 33, 'Female', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09201234512', 912, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'United Methodist Church', 'PH9127788', 'No', 'Private'),
(332, 'Raymond', 'Peralta', 'Morales', '', '2008-07-27', 17, 'Male', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09201234513', 913, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(333, 'Dolores', 'Cabrera', 'Villanueva', '', '1970-02-07', 55, 'Female', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09201234514', 914, 'Separated', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH9149988', 'No', 'Self-Employed'),
(334, 'Ismael', 'Soriano', 'Lim', '', '2012-06-30', 13, 'Male', 'Purok 3 - Barangay Gubat Daet, Camarines Norte', '09201234515', 915, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(335, 'Erlinda', 'Delos Reyes', 'Padilla', '', '1942-05-19', 83, 'Female', 'Purok 4 - Barangay Gubat Daet, Camarines Norte', '09201234516', 916, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH9165566', 'No', 'LGU'),
(336, 'Benjamin', 'Aguilar', 'Martinez', '', '1979-11-03', 45, 'Male', 'Purok 4 - Barangay Gubat Daet, Camarines Norte', '09201234517', 917, 'Married', '', 'Secondary Education', 0, 'Fisherman', 'Roman Catholic', 'PH9172233', 'Yes', 'LGU'),
(337, 'Rowena', 'Domingo', 'Diaz', '', '1995-09-12', 30, 'Female', 'Purok 4 - Barangay 8 Gubat Daet, Camarines Norte', '09201234518', 918, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Office Clerk', 'Roman Catholic', 'PH9188899', 'No', 'Private'),
(338, 'Leandro', 'Santos', 'Fernandez', '', '2000-01-22', 25, 'Male', 'Purok 4 - Barangay 8 Gubat Daet, Camarines Norte', '09201234519', 919, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Service Crew', 'Roman Catholic', 'PH9191122', 'No', 'Private'),
(339, 'Nerissa', 'Gomez', 'Ramos', '', '2019-02-14', 6, 'Female', 'Purok 4 - Barangay 8 Gubat Daet, Camarines Norte', '09201234520', 920, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Child', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(340, 'Manuel', 'Castillo', 'Santiago', 'Jr.', '1965-04-04', 60, 'Male', 'Purok 5 - Barangay 8 Gubat Daet, Camarines Norte', '09201234521', 921, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH9218899', 'No', 'LGU'),
(341, 'Helena', 'Salazar', 'Gutierrez', '', '1988-10-18', 36, 'Female', 'Purok 5 - Barangay 8 Gubat Daet, Camarines Norte', '09201234522', 922, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Cashier', 'Assemblies of God', 'PH9222233', 'No', 'Private'),
(342, 'Francisco', 'Lim', 'Soriano', '', '2009-03-08', 16, 'Male', 'Purok 5 - Barangay 8 Gubat Daet, Camarines Norte', '09201234523', 923, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(343, 'Cecilia', 'Villanueva', 'Mendoza', '', '1973-07-25', 52, 'Female', 'Purok 5 - Barangay 8 Gubat Daet, Camarines Norte', '09201234524', 924, 'Separated', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH9246677', 'No', 'Self-Employed'),
(344, 'Armando', 'Francisco', 'Delos Santos', '', '2014-12-29', 10, 'Male', 'Purok 5 - Barangay 8 Gubat Daet, Camarines Norte', '09201234525', 925, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(345, 'Andrei', 'C', 'Villanueva', '', '2016-03-12', 9, 'Male', 'Purok 1 - Barangay San Isidro', '09173456012', 601, 'Single', 'San Isidro', 'Elementary', 0, NULL, 'Roman Catholic', NULL, 'No', 'NHTS'),
(346, 'Bianca', 'Marquez', 'Reyes', '', '2018-07-20', 7, 'Female', 'Purok 2 - Barangay San Isidro', '09184561203', 602, 'Single', 'San Isidro', 'Kinder', 0, NULL, 'Roman Catholic', NULL, 'Yes', 'LGU'),
(347, 'Carlo', 'Santos', 'Rivera', '', '2010-11-05', 14, 'Male', 'Purok 3 - Barangay San Isidro', '09203456178', 603, 'Single', 'Daet', 'High School', 0, NULL, 'Christian', NULL, 'No', 'Private'),
(348, 'Diana', 'Flores', 'Castro', '', '2008-04-18', 17, 'Female', 'Purok 4 - Barangay San Isidro', '09195672034', 604, 'Single', 'San Isidro', 'High School', 0, NULL, 'Roman Catholic', NULL, 'Yes', 'LGU'),
(349, 'Ethan', 'Gomez', 'Pascual', '', '2003-09-27', 21, 'Male', 'Purok 5 - Barangay San Isidro', '09216784523', 605, 'Single', 'San Isidro', 'College', 0, 'Student', 'Iglesia ni Cristo', NULL, 'No', 'NHTS'),
(350, 'Fatima', 'Lopez', 'Cabrera', '', '2001-01-14', 24, 'Female', 'Purok 6 - Barangay San Isidro', '09184573214', 606, 'Single', 'San Isidro', 'College', 0, 'Vendor', 'Roman Catholic', NULL, 'Yes', 'Private'),
(351, 'Gerald', 'Navarro', 'Domingo', '', '1997-05-09', 28, 'Male', 'Purok 1 - Barangay San Isidro', '09196784512', 607, 'Married', 'San Vicente', 'College', 0, 'Technician', 'Roman Catholic', 'PMN123456', 'No', 'LGU'),
(352, 'Hannah', 'Ramos', 'Soriano', '', '1992-12-30', 32, 'Female', 'Purok 2 - Barangay San Isidro', '', 608, 'Married', 'Daet', 'College', 0, 'Teacher', 'Roman Catholic', 'PMN987654', 'No', 'Private'),
(353, 'Ian', 'Perez', 'Aguilar', '', '1989-06-15', 36, 'Male', 'Purok 3 - Barangay San Isidro', '09174563209', 609, 'Married', 'San Isidro', 'High School', 0, 'Farmer', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(354, 'Joyce', 'Villarin', 'Salvador', '', '1985-10-22', 39, 'Female', 'Purok 4 - Barangay San Isidro', '09185673290', 610, 'Married', 'Labo', 'College', 0, 'Nurse', 'Roman Catholic', 'PMN456321', 'No', 'LGU'),
(355, 'Kevin', 'Martinez', 'Torres', '', '1982-08-11', 43, 'Male', 'Purok 5 - Barangay San Isidro', '09197653281', 611, 'Married', 'Daet', 'College', 0, 'Driver', 'Christian', NULL, 'No', 'Private'),
(356, 'Leah', 'Castillo', 'Padilla', '', '1978-03-07', 47, 'Female', 'Purok 6 - Barangay San Isidro', '09217654382', 612, 'Married', 'San Isidro', 'College', 0, 'Businesswoman', 'Roman Catholic', NULL, 'No', 'Self-Employed'),
(357, 'Marco', 'Reyes', 'Valdez', '', '1975-12-01', 49, 'Male', 'Purok 1 - Barangay San Isidro', '09197654381', 613, 'Married', 'Labo', 'High School', 0, 'Carpenter', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(358, 'Nina', 'Delos Santos', 'Aquino', '', '1970-05-16', 55, 'Female', 'Purok 2 - Barangay San Isidro', '09184569321', 614, 'Married', 'San Isidro', 'Elementary', 0, 'Vendor', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(359, 'Oscar', 'Gutierrez', 'Fernandez', '', '1968-09-24', 56, 'Male', 'Purok 3 - Barangay San Isidro', '09173456219', 615, 'Married', 'Daet', 'High School', 0, 'Fisherman', 'Roman Catholic', NULL, 'No', 'Private'),
(360, 'Paula', 'Garcia', 'De Leon', '', '1965-11-30', 59, 'Female', 'Purok 4 - Barangay San Isidro', '09216784567', 616, '', 'San Vicente', 'High School', 0, 'Housewife', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(361, 'Ramon', 'Bautista', 'Mendoza', '', '1960-02-10', 65, 'Male', 'Purok 5 - Barangay San Isidro', '09184567213', 617, 'Married', 'San Isidro', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'LGU'),
(362, 'Sara', 'Morales', 'Lorenzo', '', '1958-07-08', 67, 'Female', 'Purok 6 - Barangay San Isidro', '09184567823', 618, 'Married', 'Daet', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'NHTS'),
(363, 'Tony', 'Villanueva', 'Cruz', '', '1955-04-21', 70, 'Male', 'Purok 1 - Barangay San Isidro', '09196782345', 619, 'Married', 'San Isidro', 'Elementary', 0, 'Retired Farmer', 'Roman Catholic', NULL, 'No', 'LGU'),
(364, 'Ursula', 'Santos', 'Jimenez', '', '1950-10-12', 74, 'Female', 'Purok 2 - Barangay San Isidro', '09183457201', 620, 'Widowed', 'San Isidro', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(365, 'Victor', 'Lopez', 'Ortega', '', '1948-05-03', 77, 'Male', 'Purok 3 - Barangay San Isidro', '09197653210', 621, 'Married', 'Labo', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'LGU'),
(366, 'Wilma', 'Marquez', 'Domingo', '', '1945-12-19', 79, 'Female', 'Purok 4 - Barangay San Isidro', '09213456789', 622, 'Widowed', 'San Isidro', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'Private'),
(367, 'Xavier', 'Torres', 'Ramos', '', '1942-07-25', 83, 'Male', 'Purok 5 - Barangay San Isidro', '09184569322', 623, 'Married', 'Daet', 'Elementary', 0, 'Retired', 'Christian', NULL, 'No', 'LGU'),
(368, 'Yolanda', 'Aguilar', 'Pineda', '', '1938-11-14', 86, 'Female', 'Purok 6 - Barangay San Isidro', '09197654329', 624, 'Widowed', '', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(369, 'Zenaida', 'P', 'Peralta', '', '1998-04-02', 27, 'Female', 'Purok 6 - Barangay San Isidro', '09173459876', 625, 'Single', 'San Isidro', 'Tertiary Education', 0, 'Cashier', 'Roman Catholic', 'PMN556677', 'No', 'Private'),
(370, 'Althea', 'G', 'Reyes', '', '2015-06-14', 10, 'Female', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09123456001', 801, 'Single', 'Daet, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(371, 'Bryan', 'R', 'Santos', '', '2013-09-03', 12, 'Male', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte', '09123456002', 802, 'Single', 'Daet, Camarines Norte', 'Elementary', 0, NULL, 'Roman Catholic', NULL, 'Yes', 'LGU'),
(372, 'Celine', 'T', 'Dela Cruz', '', '2010-01-25', 15, 'Female', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte', '09123456003', 803, 'Single', 'Daet, Camarines Norte', 'High School', 0, NULL, 'Iglesia ni Cristo', NULL, 'No', 'Private'),
(373, 'Darren', 'M', 'Villanueva', '', '2008-05-19', 17, 'Male', 'Purok 4 - Barangay Cobangbang Daet, Camarines Norte', '09123456004', 804, 'Single', 'Labo, Camarines Norte', 'High School', 0, NULL, 'Roman Catholic', NULL, 'No', 'LGU'),
(374, 'Ella', 'J', 'Garcia', '', '2004-02-12', 21, 'Female', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09123456005', 805, 'Single', 'Daet, Camarines Norte', 'College', 0, 'Student', 'Aglipayan', NULL, 'No', 'NHTS'),
(375, 'Francis', 'B', 'Domingo', '', '2000-07-30', 25, 'Male', 'Purok 6 - Barangay Cobangbang Daet, Camarines Norte', '09123456006', 806, 'Single', 'Mercedes, Camarines Norte', 'College', 0, 'Technician', 'Roman Catholic', 'PH8001', 'No', 'LGU'),
(376, 'Grace', 'L', 'Aquino', '', '1997-11-09', 27, 'Female', 'Purok 7 - Barangay Cobangbang Daet, Camarines Norte', '09123456007', 807, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'Roman Catholic', 'PH8002', 'No', 'Private'),
(377, 'Harold', 'C', 'Castillo', '', '1994-03-23', 31, 'Male', 'Purok 8 - Barangay Cobangbang Daet, Camarines Norte', '09123456008', 808, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Driver', 'Christian', NULL, 'No', 'LGU'),
(378, 'Ivy', 'S', 'Morales', '', '1990-12-05', 34, 'Female', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09123456009', 809, 'Married', 'Labo, Camarines Norte', 'College', 0, 'Businesswoman', 'Roman Catholic', NULL, 'No', 'Self-Employed'),
(379, 'Jasper', 'N', 'Torres', '', '1987-09-18', 37, 'Male', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte', '09123456010', 810, 'Married', 'Daet, Camarines Norte', 'College', 0, 'Farmer', 'Roman Catholic', 'PH8003', 'No', 'LGU'),
(380, 'Kristine', 'E', 'Lopez', '', '1984-06-11', 41, 'Female', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte', '09123456011', 811, 'Married', 'Paracale, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Roman Catholic', 'PH8004', 'No', 'Private'),
(381, 'Leo', 'A', 'Fernandez', '', '1981-01-26', 44, 'Male', 'Purok 4 - Barangay Cobangbang Daet, Camarines Norte', '09123456012', 812, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Fisherman', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(382, 'Melissa', 'D', 'Ramos', '', '1978-04-03', 47, 'Female', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09123456013', 813, 'Married', 'Labo, Camarines Norte', 'High School', 0, 'Housewife', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(383, 'Nathan', 'O', 'Gutierrez', '', '1975-10-20', 49, 'Male', 'Purok 6 - Barangay Cobangbang Daet, Camarines Norte', '09123456014', 814, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Carpenter', 'Iglesia ni Cristo', NULL, 'No', 'Private'),
(384, 'Olivia', 'P', 'Salvador', '', '1971-08-29', 54, 'Female', 'Purok 7 - Barangay Cobangbang Daet, Camarines Norte', '09123456015', 815, 'Married', 'Mercedes, Camarines Norte', 'College', 0, 'Vendor', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(385, 'Patrick', 'V', 'Mendoza', '', '1968-02-14', 57, 'Male', 'Purok 8 - Barangay Cobangbang Daet, Camarines Norte', '09123456016', 816, 'Married', 'Daet, Camarines Norte', 'Elementary', 0, 'Fisherman', 'Roman Catholic', NULL, 'No', 'LGU'),
(386, 'Queenie', 'H', 'Aguilar', '', '1965-07-07', 60, 'Female', 'Purok 1 - Barangay Cobangbang Daet, Camarines Norte', '09123456017', 817, 'Married', 'Labo, Camarines Norte', 'High School', 0, 'Housewife', 'Roman Catholic', NULL, 'No', 'Private'),
(387, 'Ramon', 'I', 'Bautista', '', '1961-03-01', 64, 'Male', 'Purok 2 - Barangay Cobangbang Daet, Camarines Norte', '09123456018', 818, 'Married', 'Daet, Camarines Norte', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'LGU'),
(388, 'Sofia', 'K', 'Jimenez', '', '1958-09-25', 66, 'Female', 'Purok 3 - Barangay Cobangbang Daet, Camarines Norte', '09123456019', 819, 'Widowed', 'Daet, Camarines Norte', 'Elementary', 0, 'Housewife', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(389, 'Tomas', 'Q', 'Ortega', 'Sr.', '1955-11-17', 69, 'Male', 'Purok 4 - Barangay Cobangbang Daet, Camarines Norte', '09123456020', 820, 'Married', 'Paracale, Camarines Norte', 'Elementary', 0, 'Retired Farmer', 'Roman Catholic', NULL, 'No', 'LGU'),
(390, 'Ursula', 'W', 'Diaz', '', '1951-05-05', 74, 'Female', 'Purok 5 - Barangay Cobangbang Daet, Camarines Norte', '09123456021', 821, 'Widowed', 'Daet, Camarines Norte', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'Private'),
(391, 'Vicente', 'Y', 'Padilla', '', '1948-08-19', 77, 'Male', 'Purok 6 - Barangay Cobangbang Daet, Camarines Norte', '09123456022', 822, 'Married', 'Mercedes, Camarines Norte', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'Yes', 'LGU'),
(392, 'Wilma', 'Z', 'Cabrera', '', '1945-12-10', 79, 'Female', 'Purok 7 - Barangay Cobangbang Daet, Camarines Norte', '09123456023', 823, 'Widowed', 'Daet, Camarines Norte', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(393, 'Xavier', 'L', 'Villarin', '', '1942-04-22', 83, 'Male', 'Purok 8 - Barangay Cobangbang Daet, Camarines Norte', '09123456024', 824, 'Married', 'Daet, Camarines Norte', 'Elementary', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'LGU'),
(394, 'Miguel', 'R', 'Santiago', '', '2017-03-12', 8, 'Male', 'Purok 1 - Barangay Bagasbas Daet, Camarines Norte', '09123456001', 901, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, NULL, 'Roman Catholic', 'PH300201', 'No', 'LGU'),
(395, 'Althea', 'C', 'Villanueva', '', '2019-06-05', 6, 'Female', 'Purok 1 - Barangay Bagasbas Daet, Camarines Norte', '09123456002', 902, 'Single', 'Daet, Camarines Norte', 'Primary Education', 3, NULL, 'Roman Catholic', 'PH300202', 'No', 'Private'),
(396, 'Jasper', 'L', 'Reyes', '', '2012-08-21', 13, 'Male', 'Purok 1 - Barangay Bagasbas Daet, Camarines Norte', '09123456003', 903, 'Single', 'Labo, Camarines Norte', 'Primary Education', 0, 'Student', 'Iglesia ni Cristo', 'PH300203', 'Yes', 'NHTS'),
(397, 'Sofia', 'T', 'Cruz', '', '2008-01-09', 17, 'Female', 'Purok 2 - Barangay Bagasbas Daet, Camarines Norte', '09123456004', 904, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', 'PH300204', 'No', 'LGU'),
(398, 'Gabriel', 'M', 'Torres', '', '2005-11-30', 19, 'Male', 'Purok 2 - Barangay Bagasbas Daet, Camarines Norte', '09123456005', 905, 'Single', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', 'PH300205', 'No', 'Private'),
(399, 'Janelle', 'C', 'Aquino', '', '2002-05-14', 23, 'Female', 'Purok 2 - Barangay Bagasbas Daet, Camarines Norte', '09123456006', 906, 'Single', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'United Methodist Church', 'PH300206', 'Yes', 'NHTS'),
(400, 'Patrick', 'G', 'Domingo', '', '1998-07-02', 27, 'Male', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte', '09123456007', 907, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Clerk', 'Roman Catholic', 'PH300207', 'No', 'LGU');
INSERT INTO `patients` (`patient_id`, `first_name`, `middle_name`, `last_name`, `extension`, `date_of_birth`, `age`, `sex`, `address`, `contact_number`, `family_serial_no`, `civil_status`, `birthplace`, `educational_attainment`, `birth_weight`, `occupation`, `religion`, `philhealth_member_no`, `fourps_status`, `category`) VALUES
(401, 'Clarisse', 'R', 'Bautista', '', '1994-02-27', 31, 'Female', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte', '09123456008', 908, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Roman Catholic', 'PH300208', 'No', 'Private'),
(402, 'Jericho', 'D', 'Navarro', 'Jr.', '1989-10-18', 35, 'Male', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte', '09123456009', 909, 'Married', 'Labo, Camarines Norte', 'Tertiary Education', 0, 'Driver', 'Islam', 'PH300209', 'No', 'LGU'),
(403, 'Alyssa', 'M', 'Mendoza', '', '1986-06-03', 39, 'Female', 'Purok 3 - Barangay Bagasbas Daet, Camarines Norte', '09123456010', 910, 'Married', 'Mercedes, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'Roman Catholic', 'PH300210', 'No', 'Private'),
(404, 'Ramon', 'F', 'Villareal', 'Sr.', '1982-04-15', 43, 'Male', 'Purok 4 - Barangay Bagasbas Daet, Camarines Norte', '09123456011', 911, 'Married', 'Daet, Camarines Norte', 'Postgraduate', 0, 'Engineer', 'Roman Catholic', 'PH300211', 'No', 'LGU'),
(405, 'Elena', 'S', 'Serrano', '', '1979-09-28', 45, 'Female', 'Purok 4 - Barangay Bagasbas Daet, Camarines Norte', '09123456012', 912, 'Married', 'Paracale, Camarines Norte', 'Tertiary Education', 0, 'Housewife', 'Aglipayan', 'PH300212', 'No', 'NHTS'),
(406, 'Carlos', 'J', 'Garcia', 'II', '1975-01-22', 50, 'Male', 'Purok 4 - Barangay Bagasbas Daet, Camarines Norte', '09123456013', 913, 'Married', 'Daet, Camarines Norte', 'Postgraduate', 0, 'Manager', 'Roman Catholic', 'PH300213', 'No', 'Private'),
(407, 'Maria', 'P', 'Lorenzo', '', '1971-03-16', 54, 'Female', 'Purok 4 - Barangay Bagasbas Daet, Camarines Norte', '09123456014', 914, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', 'PH300214', 'No', 'LGU'),
(408, 'Pedro', 'R', 'Santos', 'III', '1967-08-10', 58, 'Male', 'Purok 5 - Barangay Bagasbas Daet, Camarines Norte', '09123456015', 915, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Farmer', 'Roman Catholic', 'PH300215', 'No', 'NHTS'),
(409, 'Rosa', 'D', 'Dela Cruz', '', '1963-02-04', 62, 'Female', 'Purok 5 - Barangay Bagasbas Daet, Camarines Norte', '09123456016', 916, 'Widowed', 'Labo, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH300216', 'No', 'Private'),
(410, 'Francisco', 'T', 'Aquino', '', '1958-07-29', 67, 'Male', 'Purok 5 - Barangay Bagasbas Daet, Camarines Norte', '09123456017', 917, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Farmer', 'Roman Catholic', 'PH300217', 'No', 'LGU'),
(411, 'Teresa', 'M', 'Jimenez', '', '1954-12-17', 70, 'Female', 'Purok 5 - Barangay Bagasbas Daet, Camarines Norte', '09123456018', 918, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Assemblies of God', 'PH300218', 'No', 'NHTS'),
(412, 'Antonio', 'C', 'Reyes', 'Sr.', '1949-09-02', 76, 'Male', 'Purok 5 - Barangay Bagasbas Daet, Camarines Norte', '09123456019', 919, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH300219', 'No', 'LGU'),
(413, 'Josefa', 'L', 'Domingo', '', '1946-11-12', 78, 'Female', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09123456020', 920, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH300220', 'No', 'Private'),
(414, 'Manuel', 'R', 'Gonzales', '', '1941-04-25', 84, 'Male', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09123456021', 921, 'Widowed', 'Labo, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH300221', 'No', 'NHTS'),
(415, 'Consuelo', 'A', 'Torres', '', '1938-05-06', 87, 'Female', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09123456022', 922, 'Widowed', 'Mercedes, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH300222', 'No', 'LGU'),
(416, 'Andres', 'G', 'Cortez', 'Sr.', '1935-02-19', 90, 'Male', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09123456023', 923, 'Married', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Aglipayan', 'PH300223', 'No', 'Private'),
(417, 'Lourdes', 'S', 'Villanueva', '', '1931-08-08', 94, 'Female', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09123456024', 924, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Housewife', 'Roman Catholic', 'PH300224', 'No', 'NHTS'),
(418, 'Fernando', 'M', 'Santiago', '', '1928-01-13', 97, 'Male', 'Purok 6 - Barangay Bagasbas Daet, Camarines Norte', '09123456025', 925, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH300225', 'No', 'LGU'),
(419, 'Althea', 'Domingo', 'Reyes', '', '2020-03-15', 5, 'Female', 'Purok 1 - Barangay Mambalite Daet, Camarines Norte', '09123456001', 9501, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, NULL, 'Roman Catholic', NULL, 'Yes', 'NHTS'),
(420, 'Joshua', 'Santos', 'Garcia', '', '2018-07-21', 7, 'Male', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte', '09123456002', 9502, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, NULL, 'Roman Catholic', NULL, 'Yes', 'LGU'),
(421, 'Marianne', 'Torres', 'Cruz', '', '2015-11-05', 9, 'Female', 'Purok 3 - Barangay Mambalite Daet, Camarines Norte', '09123456003', 9503, 'Single', 'Labo, Camarines Norte', 'Primary Education', 0, NULL, 'Iglesia ni Cristo', 'PH300203', 'No', 'Private'),
(422, 'Daniel', 'Villanueva', 'Santos', '', '2013-09-12', 12, 'Male', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte', '09123456004', 9504, 'Single', 'Daet, Camarines Norte', 'Primary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(423, 'Sophia', 'Flores', 'Mendoza', '', '2010-01-29', 15, 'Female', 'Purok 5 - Barangay Mambalite Daet, Camarines Norte', '09123456005', 9505, 'Single', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Student', 'Aglipayan', NULL, 'No', 'NHTS'),
(424, 'John', 'Castro', 'Lopez', '', '2008-06-18', 17, 'Male', 'Purok 6 - Barangay Mambalite Daet, Camarines Norte', '09123456006', 9506, 'Single', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Student', 'Roman Catholic', NULL, 'No', 'LGU'),
(425, 'Ella', 'Navarro', 'Villanueva', '', '2005-04-02', 20, 'Female', 'Purok 7 - Barangay Mambalite Daet, Camarines Norte', '09123456007', 9507, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Student', 'United Methodist Church', NULL, 'No', 'Private'),
(426, 'Kevin', 'Jimenez', 'Domingo', '', '2002-10-23', 22, 'Male', 'Purok 1 - Barangay Mambalite Daet, Camarines Norte', '09123456008', 9508, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Clerk', 'Roman Catholic', 'PH300208', 'No', 'LGU'),
(427, 'Grace', 'Fernandez', 'Torres', '', '2000-08-17', 25, 'Female', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte', '09123456009', 9509, 'Single', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Office Assistant', 'Roman Catholic', NULL, 'No', 'NHTS'),
(428, 'Mark', 'Morales', 'Ramos', '', '1995-07-13', 30, 'Male', 'Purok 3 - Barangay Mambalite Daet, Camarines Norte', '09123456010', 9510, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Engineer', 'Iglesia ni Cristo', 'PH300210', 'No', 'Private'),
(429, 'Claudine', 'Aguilar', 'Navarro', '', '1992-04-20', 33, 'Female', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte', '09123456011', 9511, 'Married', 'Mercedes, Camarines Norte', 'Tertiary Education', 0, 'Teacher', 'Roman Catholic', 'PH300211', 'No', 'LGU'),
(430, 'Leo', 'Bautista', 'Aguilar', '', '1989-09-09', 36, 'Male', 'Purok 5 - Barangay Mambalite Daet, Camarines Norte', '09123456012', 9512, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Mechanic', 'Roman Catholic', NULL, 'No', 'Private'),
(431, 'Michelle', 'Flores', 'Castro', '', '1986-11-25', 38, 'Female', 'Purok 6 - Barangay Mambalite Daet, Camarines Norte', '09123456013', 9513, 'Married', 'Labo, Camarines Norte', 'Tertiary Education', 0, 'Nurse', 'Aglipayan', 'PH300213', 'Yes', 'NHTS'),
(432, 'Raymond', 'Domingo', 'Bautista', '', '1983-05-14', 42, 'Male', 'Purok 7 - Barangay Mambalite Daet, Camarines Norte', '09123456014', 9514, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Driver', 'Roman Catholic', NULL, 'No', 'LGU'),
(433, 'Carolina', 'Reyes', 'Flores', '', '1979-08-19', 46, 'Female', 'Purok 1 - Barangay Mambalite Daet, Camarines Norte', '09123456015', 9515, 'Married', 'Paracale, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', NULL, 'Yes', 'Private'),
(434, 'Victor', 'Mendoza', 'Martinez', '', '1975-02-06', 50, 'Male', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte', '09123456016', 9516, 'Married', 'Daet, Camarines Norte', 'Tertiary Education', 0, 'Business Owner', 'Iglesia ni Cristo', 'PH300216', 'No', 'LGU'),
(435, 'Angela', 'Ramos', 'Morales', '', '1971-10-27', 53, 'Female', 'Purok 3 - Barangay Mambalite Daet, Camarines Norte', '09123456017', 9517, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Housewife', 'Roman Catholic', NULL, 'No', 'NHTS'),
(436, 'Roberto', 'Santos', 'Torralba', '', '1968-03-03', 57, 'Male', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte', '09123456018', 9518, 'Married', 'Labo, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', 'PH300218', 'No', 'LGU'),
(437, 'Elvira', 'Garcia', 'Jimenez', '', '1964-12-17', 60, 'Female', 'Purok 5 - Barangay Mambalite Daet, Camarines Norte', '09123456019', 9519, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'Private'),
(438, 'Armando', 'Villanueva', 'Delos Santos', '', '1961-06-08', 64, 'Male', 'Purok 6 - Barangay Mambalite Daet, Camarines Norte', '09123456020', 9520, 'Married', 'Mercedes, Camarines Norte', 'Secondary Education', 0, 'Fisherman', 'Roman Catholic', 'PH300220', 'No', 'NHTS'),
(439, 'Lourdes', 'Cruz', 'Fernandez', '', '1958-02-19', 67, 'Female', 'Purok 7 - Barangay Mambalite Daet, Camarines Norte', '09123456021', 9521, 'Married', 'Daet, Camarines Norte', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', NULL, 'No', 'LGU'),
(440, 'Felipe', 'Aguilar', 'Santiago', '', '1954-12-01', 70, 'Male', 'Purok 1 - Barangay Mambalite Daet, Camarines Norte', '09123456022', 9522, 'Married', 'Paracale, Camarines Norte', 'Primary Education', 0, 'Retired Farmer', 'Roman Catholic', NULL, 'No', 'Private'),
(441, 'Natividad', 'Castro', 'Velasco', '', '1950-08-14', 75, 'Female', 'Purok 2 - Barangay Mambalite Daet, Camarines Norte', '09123456023', 9523, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Iglesia ni Cristo', NULL, 'No', 'NHTS'),
(442, 'Eduardo', 'Ramos', 'Francisco', '', '1946-04-22', 79, 'Male', 'Purok 3 - Barangay Mambalite Daet, Camarines Norte', '09123456024', 9524, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', NULL, 'No', 'LGU'),
(443, 'Dolores', 'Domingo', 'Salazar', '', '1938-05-06', 87, 'Female', 'Purok 4 - Barangay Mambalite Daet, Camarines Norte', '09123456025', 9525, 'Widowed', 'Daet, Camarines Norte', 'Primary Education', 0, 'Retired', 'Roman Catholic', NULL, 'Yes', 'Private'),
(444, 'Jose', 'Santos', 'Madrigal', '', '1960-09-08', 65, 'Male', 'Purok 6 - Barangay 1 Daet, Camarines Norte', '', 0, 'Married', '222', '', 0, '', '', '', '', ''),
(445, 'Oliver', 'Reyes', 'Santos', '', '2025-02-18', 1, 'Male', 'Purok 3 - Camambugan Daet Camarines Norte', '09765677889', 0, 'Single', 'Labo, CN', 'Secondary Education', 0, 'Vendor', 'Roman Catholic', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `patient_assessment`
--

CREATE TABLE `patient_assessment` (
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `recorded_by` int(11) NOT NULL,
  `visit_date` date DEFAULT current_timestamp(),
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `chief_complaints` text DEFAULT NULL,
  `referred_to_rhu` tinyint(1) DEFAULT 0,
  `bmi` decimal(5,1) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `chest_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `patient_alert` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `treatment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_assessment`
--

INSERT INTO `patient_assessment` (`visit_id`, `patient_id`, `recorded_by`, `visit_date`, `blood_pressure`, `temperature`, `chief_complaints`, `referred_to_rhu`, `bmi`, `weight`, `height`, `chest_rate`, `respiratory_rate`, `patient_alert`, `remarks`, `treatment`) VALUES
(1, 1, 5, '2025-01-10', '120/80', 36.8, 'Headache and dizziness', 0, 22.5, 60.00, 1.63, 80, 18, NULL, 'Normal check-up, no referral needed', 'BP only'),
(2, 2, 5, '2025-01-15', '130/85', 37.2, 'Lagnat at ubo', 1, 20.8, 55.00, 1.63, 85, 20, NULL, 'Referred to RHU for further evaluation', NULL),
(3, 3, 5, '2025-02-01', '110/70', 36.5, 'Routine weighing', 0, 19.6, 50.00, 1.60, 78, 17, NULL, 'Weighing only, normal results', 'Weighing only'),
(4, 4, 5, '2025-02-05', '125/82', 37.0, 'Cough and sore throat', 0, 23.1, 65.00, 1.68, 82, 19, NULL, 'Advised to rest and hydrate', 'Other'),
(5, 5, 5, '2025-02-10', '140/90', 38.0, 'Mataas ang BP, hilo', 1, 24.0, 70.00, 1.70, 90, 22, NULL, 'Referred due to hypertension', NULL),
(6, 6, 5, '2025-02-15', '118/76', 36.6, 'Normal check-up', 0, 21.2, 58.00, 1.65, 76, 16, NULL, 'Healthy, no issues', 'BP only'),
(7, 7, 5, '2025-02-20', '122/80', 37.1, 'Fever and cough', 1, 22.9, 62.00, 1.65, 84, 21, NULL, 'Possible infection, referred', NULL),
(8, 8, 5, '2025-02-25', '115/75', 36.7, 'Weighing and vaccination', 0, 20.4, 54.00, 1.63, 79, 18, NULL, 'Received immunization', 'Immunization'),
(9, 14, 5, '2025-03-01', '124/82', 36.9, 'Headache', 0, 23.5, 66.00, 1.67, 81, 19, NULL, 'Advised to monitor BP', 'BP only'),
(10, 15, 5, '2025-03-05', '135/88', 37.3, 'Lagnat, ubo, sipon', 1, 25.1, 70.00, 1.67, 86, 22, NULL, 'Referred to RHU for pneumonia check', NULL),
(11, 16, 5, '2025-03-10', '119/78', 36.5, 'Routine weighing', 0, 19.5, 52.00, 1.63, 77, 17, NULL, 'Stable and healthy', 'Weighing only'),
(12, 17, 5, '2025-03-15', '126/80', 37.0, 'Back pain', 0, 24.8, 68.00, 1.65, 83, 20, NULL, 'Given advice on posture', 'Other'),
(13, 18, 5, '2025-03-20', '138/92', 38.1, 'Mataas na presyon at lagnat', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Hypertension with fever, referred', NULL),
(14, 19, 5, '2025-03-25', '112/72', 36.4, 'Normal check-up', 0, 20.9, 56.00, 1.64, 78, 16, NULL, 'Healthy, no complaints', 'BP only'),
(15, 20, 5, '2025-04-01', '121/79', 36.6, 'Weighing only', 0, 19.8, 53.00, 1.64, 79, 17, NULL, 'All normal', 'Weighing only'),
(16, 21, 5, '2025-04-05', '134/86', 37.5, 'Fever and cough', 1, 22.6, 60.00, 1.63, 87, 21, NULL, 'Referred for possible flu', NULL),
(17, 27, 5, '2025-04-10', '128/84', 36.8, 'BP check-up', 0, 21.7, 59.00, 1.65, 80, 18, NULL, 'Normal BP', 'BP only'),
(18, 28, 5, '2025-04-15', '137/89', 37.2, 'Ubo at sipon', 1, 23.7, 64.00, 1.64, 85, 20, NULL, 'Referred for chest exam', NULL),
(19, 29, 5, '2025-04-20', '118/76', 36.7, 'Weighing and immunization', 0, 19.9, 52.00, 1.62, 78, 17, NULL, 'Child immunized', 'Immunization'),
(20, 30, 5, '2025-04-25', '124/82', 37.0, 'Mild headache', 0, 22.2, 60.00, 1.65, 82, 19, NULL, 'Advised hydration', 'Other'),
(21, 31, 5, '2025-05-01', '145/95', 38.3, 'Matinding hilo at mataas BP', 1, 29.0, 80.00, 1.66, 95, 24, NULL, 'Severe hypertension, referred', NULL),
(22, 32, 5, '2025-05-05', '110/70', 36.4, 'Weighing only', 0, 18.9, 50.00, 1.63, 76, 16, NULL, 'No issues', 'Weighing only'),
(23, 33, 5, '2025-05-10', '125/82', 36.8, 'Cough and fever', 1, 23.0, 62.00, 1.64, 84, 21, NULL, 'Referred for further check-up', NULL),
(24, 34, 5, '2025-05-15', '120/80', 36.9, 'Normal visit', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'All normal', 'BP only'),
(25, 40, 5, '2025-01-12', '122/80', 36.8, 'Routine BP check', 0, 21.9, 59.00, 1.64, 80, 18, NULL, 'Stable vitals', 'BP only'),
(26, 41, 5, '2025-01-18', '135/88', 37.4, 'Ubo at lagnat', 1, 23.5, 64.00, 1.65, 86, 21, NULL, 'Referred to RHU for further tests', NULL),
(27, 42, 5, '2025-01-25', '118/76', 36.5, 'Weighing only', 0, 20.1, 54.00, 1.64, 78, 17, NULL, 'Normal results', 'Weighing only'),
(28, 43, 5, '2025-02-02', '128/84', 37.0, 'Cough and sore throat', 0, 22.8, 62.00, 1.65, 83, 20, NULL, 'Advised rest and fluids', 'Other'),
(29, 44, 5, '2025-02-06', '140/92', 38.2, 'Matinding hilo at mataas BP', 1, 27.2, 75.00, 1.66, 92, 23, NULL, 'Hypertensive, referred', NULL),
(30, 45, 5, '2025-02-11', '120/80', 36.9, 'Normal check-up', 0, 21.3, 58.00, 1.65, 79, 18, NULL, 'Healthy, no issues', 'BP only'),
(31, 46, 5, '2025-02-16', '133/87', 37.2, 'Fever and cough', 1, 24.4, 67.00, 1.66, 88, 20, NULL, 'Referred to RHU', NULL),
(32, 47, 5, '2025-02-22', '115/75', 36.7, 'Weighing and immunization', 0, 20.9, 55.00, 1.62, 77, 18, NULL, 'Child immunized', 'Immunization'),
(33, 76, 5, '2025-03-01', '125/82', 36.9, 'Headache', 0, 23.1, 64.00, 1.66, 81, 19, NULL, 'Advised hydration and rest', 'Other'),
(34, 77, 5, '2025-03-07', '138/90', 37.5, 'Lagnat at ubo', 1, 25.3, 70.00, 1.66, 90, 22, NULL, 'Referred to RHU due to high fever', NULL),
(35, 78, 5, '2025-03-12', '119/78', 36.4, 'Weighing only', 0, 19.7, 52.00, 1.63, 76, 17, NULL, 'No issues', 'Weighing only'),
(36, 81, 5, '2025-03-18', '126/82', 37.0, 'Back pain', 0, 24.5, 68.00, 1.66, 82, 20, NULL, 'Advised posture correction', 'Other'),
(37, 82, 5, '2025-03-24', '142/95', 38.3, 'Mataas BP at lagnat', 1, 28.1, 78.00, 1.67, 95, 24, NULL, 'Referred for hypertension management', NULL),
(38, 83, 5, '2025-03-30', '112/72', 36.6, 'Routine check-up', 0, 20.7, 56.00, 1.64, 78, 16, NULL, 'Healthy status', 'BP only'),
(39, 87, 5, '2025-04-04', '121/79', 36.5, 'Weighing and vaccination', 0, 19.9, 53.00, 1.63, 77, 17, NULL, 'Vaccinated successfully', 'Immunization'),
(40, 88, 5, '2025-04-09', '136/88', 37.2, 'Ubo at sipon', 1, 24.0, 66.00, 1.66, 86, 21, NULL, 'Referred to RHU', NULL),
(41, 92, 5, '2025-04-14', '118/76', 36.7, 'Weighing only', 0, 19.8, 52.00, 1.63, 77, 17, NULL, 'Stable condition', 'Weighing only'),
(42, 93, 5, '2025-04-19', '127/83', 37.0, 'Headache and dizziness', 0, 22.5, 60.00, 1.63, 82, 19, NULL, 'Mild headache, advised rest', 'Other'),
(43, 94, 5, '2025-04-25', '145/96', 38.4, 'Severe hypertension', 1, 29.5, 82.00, 1.67, 96, 25, NULL, 'Immediate referral to RHU', NULL),
(44, 97, 5, '2025-05-01', '124/82', 36.8, 'Normal check-up', 0, 21.7, 59.00, 1.65, 80, 18, NULL, 'Vitals within normal range', 'BP only'),
(45, 101, 5, '2025-05-06', '131/85', 37.1, 'Lagnat at ubo', 1, 23.9, 65.00, 1.65, 85, 20, NULL, 'Referred to RHU for assessment', NULL),
(46, 102, 5, '2025-05-11', '117/75', 36.6, 'Weighing only', 0, 20.0, 53.00, 1.63, 76, 17, NULL, 'Normal growth', 'Weighing only'),
(47, 103, 5, '2025-05-16', '129/84', 37.0, 'Cough and colds', 0, 23.2, 63.00, 1.64, 82, 19, NULL, 'Prescribed rest and fluids', 'Other'),
(48, 106, 5, '2025-05-21', '141/93', 38.2, 'Mataas na presyon', 1, 26.5, 74.00, 1.67, 92, 23, NULL, 'Hypertension case referred', NULL),
(49, 107, 5, '2025-05-26', '115/75', 36.5, 'Weighing and immunization', 0, 19.6, 52.00, 1.63, 77, 18, NULL, 'Immunized and stable', 'Immunization'),
(50, 108, 5, '2025-06-01', '125/82', 36.9, 'Headache', 0, 22.1, 60.00, 1.65, 81, 19, NULL, 'Advised to hydrate', 'Other'),
(51, 109, 5, '2025-06-06', '137/89', 37.3, 'Fever and cough', 1, 24.7, 68.00, 1.66, 87, 21, NULL, 'Referred due to infection', NULL),
(52, 110, 5, '2025-06-11', '120/80', 36.7, 'Normal check-up', 0, 21.0, 57.00, 1.65, 79, 18, NULL, 'Stable', 'BP only'),
(53, 111, 5, '2025-06-16', '133/87', 37.1, 'Ubo at sipon', 1, 23.5, 64.00, 1.65, 85, 20, NULL, 'Referred to RHU', NULL),
(54, 120, 5, '2025-06-21', '119/77', 36.5, 'Weighing only', 0, 19.4, 51.00, 1.63, 76, 16, NULL, 'Healthy weight', 'Weighing only'),
(55, 121, 5, '2025-06-26', '126/82', 36.8, 'Mild dizziness', 0, 22.3, 60.00, 1.64, 80, 18, NULL, 'Observation advised', 'Other'),
(56, 122, 5, '2025-07-01', '144/94', 38.1, 'High BP and fever', 1, 27.0, 75.00, 1.67, 93, 23, NULL, 'Referred for hypertension', NULL),
(57, 148, 5, '2025-07-07', '118/76', 36.6, 'Weighing only', 0, 20.1, 54.00, 1.64, 78, 17, NULL, 'Stable growth', 'Weighing only'),
(58, 149, 5, '2025-07-13', '130/85', 37.0, 'Cough and colds', 0, 23.6, 64.00, 1.65, 82, 19, NULL, 'Fluids and rest recommended', 'Other'),
(59, 151, 5, '2025-07-18', '139/90', 37.6, 'Ubo at lagnat', 1, 25.5, 70.00, 1.66, 89, 22, NULL, 'Referred for pneumonia check', NULL),
(60, 153, 5, '2025-07-24', '121/80', 36.9, 'Routine check-up', 0, 21.8, 59.00, 1.65, 80, 18, NULL, 'No issues detected', 'BP only'),
(61, 155, 5, '2025-07-29', '135/88', 37.3, 'Fever with cough', 1, 24.8, 68.00, 1.66, 87, 21, NULL, 'Referred to RHU', NULL),
(62, 156, 5, '2025-08-04', '116/75', 36.4, 'Weighing only', 0, 19.6, 52.00, 1.63, 76, 17, NULL, 'All normal', 'Weighing only'),
(63, 158, 5, '2025-08-09', '128/84', 37.0, 'Mild headache', 0, 22.7, 61.00, 1.64, 82, 19, NULL, 'Advised hydration', 'Other'),
(64, 160, 5, '2025-08-15', '141/92', 38.2, 'Matinding presyon', 1, 27.4, 76.00, 1.67, 93, 23, NULL, 'Referred due to hypertension', NULL),
(65, 161, 5, '2025-08-21', '114/74', 36.6, 'Normal check-up', 0, 20.3, 54.00, 1.64, 77, 17, NULL, 'Stable vitals', 'BP only'),
(66, 164, 5, '2025-08-27', '132/86', 37.2, 'Cough and sore throat', 0, 23.3, 63.00, 1.65, 84, 20, NULL, 'Given rest advice', 'Other'),
(67, 166, 5, '2025-09-02', '138/90', 37.4, 'Fever and cough', 1, 25.0, 69.00, 1.66, 89, 22, NULL, 'Referred to RHU', NULL),
(68, 168, 5, '2025-09-07', '120/80', 36.8, 'Normal check-up', 0, 21.1, 57.00, 1.65, 79, 18, NULL, 'Healthy', 'BP only'),
(69, 170, 5, '2025-01-07', '136/89', 37.3, 'Lagnat at ubo', 1, 24.5, 66.00, 1.65, 88, 21, NULL, 'Referred for further check', NULL),
(70, 171, 5, '2025-09-08', '118/76', 36.5, 'Weighing only', 0, 20.2, 54.00, 1.64, 77, 17, NULL, 'Normal', 'Weighing only'),
(71, 173, 5, '2025-08-11', '125/82', 37.0, 'Mild cough', 0, 22.6, 61.00, 1.65, 82, 19, NULL, 'Advised home care', 'Other'),
(72, 175, 5, '2025-04-22', '143/95', 38.2, 'High BP with dizziness', 1, 27.9, 77.00, 1.67, 94, 24, NULL, 'Referred for hypertension', NULL),
(73, 177, 5, '2025-05-23', '116/74', 36.6, 'Weighing and vaccination', 0, 19.7, 52.00, 1.63, 76, 17, NULL, 'Immunized and stable', 'Immunization'),
(74, 178, 5, '2025-05-05', '129/84', 37.0, 'Headache', 0, 22.9, 62.00, 1.65, 82, 19, NULL, 'Advised hydration', 'Other'),
(75, 179, 5, '2025-07-13', '137/90', 37.5, 'Lagnat, ubo, sipon', 1, 25.2, 70.00, 1.66, 89, 22, NULL, 'Referred to RHU', NULL),
(76, 182, 5, '2025-04-02', '120/80', 36.8, 'Normal visit', 0, 21.2, 58.00, 1.65, 80, 18, NULL, 'No issues', 'BP only'),
(77, 184, 5, '2025-05-08', '134/87', 37.2, 'Cough and colds', 0, 23.4, 64.00, 1.65, 84, 20, NULL, 'Rest advised', 'Other'),
(193, 185, 5, '2025-04-22', '139/91', 37.6, 'High fever and cough', 1, 26.1, 72.00, 1.66, 91, 23, NULL, 'Referred for pneumonia check', NULL),
(194, 187, 5, '2025-06-23', '117/75', 36.5, 'Weighing only', 0, 19.8, 53.00, 1.63, 76, 17, NULL, 'Normal results', 'Weighing only'),
(195, 189, 5, '2025-02-01', '128/84', 37.0, 'Headache and mild fever', 0, 22.4, 60.00, 1.64, 82, 19, NULL, 'Monitored, no referral', 'Other'),
(196, 190, 5, '2025-05-21', '142/94', 38.2, 'High blood pressure with dizziness', 1, 27.5, 76.00, 1.67, 94, 23, NULL, 'Referred to RHU for hypertension management', NULL),
(197, 192, 5, '2025-04-15', '135/89', 37.6, 'Ubo at lagnat', 1, 24.8, 68.00, 1.66, 88, 21, NULL, 'Referred due to persistent cough and fever', NULL),
(198, 193, 5, '2025-04-10', '146/96', 38.4, 'Matinding hilo at mataas na BP', 1, 28.2, 79.00, 1.67, 96, 24, NULL, 'Severe hypertension, immediate referral', NULL),
(199, 194, 5, '2025-07-05', '139/91', 37.8, 'Chest pain and shortness of breath', 1, 26.0, 72.00, 1.66, 91, 22, NULL, 'Referred for cardiac evaluation', NULL),
(200, 195, 5, '2025-05-06', '133/87', 37.2, 'Lagnat at ubo', 1, 25.0, 70.00, 1.67, 87, 21, NULL, 'Possible pneumonia, referred to RHU', NULL),
(201, 199, 5, '2025-03-10', '145/95', 38.3, 'Severe hypertension', 1, 29.1, 82.00, 1.68, 95, 24, NULL, 'Referred for further hypertension workup', NULL),
(202, 201, 5, '2025-08-07', '138/90', 37.5, 'Persistent cough and fever', 1, 23.8, 65.00, 1.65, 89, 22, NULL, 'Referred due to possible infection', NULL),
(203, 202, 5, '2025-05-11', '140/92', 38.0, 'Mataas na BP at pagkahilo', 1, 27.3, 76.00, 1.67, 92, 23, NULL, 'Referred to RHU for further management', NULL),
(204, 204, 5, '2025-09-05', '136/88', 37.4, 'Fever, cough, and chest pain', 1, 24.6, 68.00, 1.67, 88, 21, NULL, 'Referred for pneumonia rule-out', NULL),
(205, 206, 5, '2025-04-02', '143/94', 38.1, 'High blood pressure and dizziness', 1, 28.0, 78.00, 1.67, 94, 23, NULL, 'Hypertensive case, referred', NULL),
(206, 208, 5, '2025-08-09', '132/86', 37.0, 'Matinding ubo at lagnat', 1, 25.5, 70.00, 1.66, 86, 20, NULL, 'Referred due to respiratory symptoms', NULL),
(207, 211, 5, '2025-03-15', '147/97', 38.5, 'Severe hypertension with headache', 1, 29.3, 83.00, 1.68, 97, 25, NULL, 'Emergency referral to RHU', NULL),
(208, 213, 5, '2025-07-26', '134/88', 37.3, 'Ubo at hirap sa paghinga', 1, 24.9, 69.00, 1.67, 88, 21, NULL, 'Referred for respiratory evaluation', NULL),
(209, 215, 5, '2025-02-27', '141/92', 37.9, 'High BP with chest pain', 1, 27.8, 77.00, 1.67, 92, 23, NULL, 'Referred to RHU for cardiac concerns', NULL),
(210, 217, 5, '2025-06-21', '139/90', 37.7, 'Lagnat at matinding ubo', 1, 25.2, 71.00, 1.67, 90, 22, NULL, 'Referred for possible pneumonia', NULL),
(211, 219, 5, '2025-07-02', '144/95', 38.2, 'Hypertension with dizziness', 1, 28.5, 79.00, 1.67, 95, 24, NULL, 'Referred due to uncontrolled BP', NULL),
(212, 220, 5, '2025-05-25', '137/89', 37.4, 'Fever and cough', 1, 24.3, 67.00, 1.66, 89, 21, NULL, 'Referred for infection workup', NULL),
(213, 221, 5, '2025-06-23', '142/94', 38.0, 'Headache and shortness of breath', 1, 27.0, 75.00, 1.67, 94, 23, NULL, 'Referred for further diagnostics', NULL),
(214, 222, 5, '2025-07-05', '135/88', 37.5, 'Lagnat, ubo, sipon', 1, 24.7, 69.00, 1.67, 88, 21, NULL, 'Referred for further management', NULL),
(215, 223, 5, '2025-06-03', '146/96', 38.3, 'Matinding hilo at mataas BP', 1, 28.9, 80.00, 1.67, 96, 24, NULL, 'Hypertensive urgency, referred', NULL),
(216, 9, 6, '2025-01-05', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Lahat ay normal', 'BP only'),
(217, 10, 6, '2025-01-10', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay tama', 'Weighing only'),
(218, 11, 6, '2025-01-15', '122/80', 36.9, 'Headache and mild fever', 0, 22.0, 60.00, 1.64, 81, 18, NULL, 'Pinayuhan uminom ng maraming tubig', 'Other'),
(219, 12, 6, '2025-01-20', '125/82', 37.0, 'Cough', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Ubo lang, walang ibang sintomas', 'Other'),
(220, 13, 6, '2025-01-25', '115/75', 36.7, 'Weighing and immunization', 0, 20.5, 55.00, 1.63, 77, 17, NULL, 'Na-immunize na ang bata', 'Immunization'),
(221, 22, 6, '2025-02-01', '119/78', 36.5, 'Routine check-up', 0, 21.2, 57.00, 1.64, 79, 18, NULL, 'Walang abnormal na findings', 'BP only'),
(222, 23, 6, '2025-02-06', '124/82', 36.8, 'Headache', 0, 22.5, 61.00, 1.65, 82, 19, NULL, 'Pinayuhan magpahinga', 'Other'),
(223, 24, 6, '2025-02-11', '130/85', 37.2, 'Mild fever', 0, 23.8, 65.00, 1.66, 85, 20, NULL, 'Lagnat lamang, hindi malala', 'Other'),
(224, 25, 6, '2025-02-16', '118/76', 36.6, 'Weighing only', 0, 20.9, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(225, 26, 6, '2025-02-21', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'BP ay within normal range', 'BP only'),
(226, 35, 6, '2025-03-01', '125/82', 37.0, 'Cough and mild fever', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Ubo ay manageable', 'Other'),
(227, 36, 6, '2025-03-06', '115/75', 36.5, 'Weighing and immunization', 0, 20.6, 55.00, 1.63, 77, 17, NULL, 'Bakuna na natanggap', 'Immunization'),
(228, 37, 6, '2025-03-11', '120/80', 36.8, 'Headache', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'Pinayuhan uminom ng gamot kung kailangan', 'Other'),
(229, 38, 6, '2025-03-16', '130/85', 37.1, 'Mild fever', 0, 23.5, 64.00, 1.65, 85, 20, NULL, 'Magpahinga lamang', 'Other'),
(230, 39, 6, '2025-03-21', '119/77', 36.6, 'Routine weighing', 0, 21.0, 56.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(231, 73, 6, '2025-04-01', '122/80', 36.9, 'BP check-up', 0, 22.0, 60.00, 1.64, 81, 18, NULL, 'BP ay stable', 'BP only'),
(232, 74, 6, '2025-04-06', '125/82', 37.0, 'Headache', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Uminom ng gamot kung kinakailangan', 'Other'),
(233, 75, 6, '2025-04-11', '118/76', 36.5, 'Weighing only', 0, 20.8, 54.00, 1.63, 77, 17, NULL, 'Walang abnormalidad', 'Weighing only'),
(234, 79, 6, '2025-04-16', '130/85', 37.2, 'Mild fever', 0, 23.5, 65.00, 1.66, 85, 20, NULL, 'Lagnat ay hindi malala', 'Other'),
(235, 80, 6, '2025-04-21', '121/79', 36.7, 'Routine check-up', 0, 21.7, 59.00, 1.64, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(236, 84, 6, '2025-05-01', '140/92', 38.0, 'Mataas na presyon at pagkahilo', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU para sa karagdagang pagsusuri', NULL),
(237, 85, 6, '2025-05-03', '135/88', 37.6, 'Fever and cough', 1, 24.5, 68.00, 1.66, 88, 21, NULL, 'Referred due to persistent symptoms', NULL),
(238, 86, 6, '2025-05-05', '145/95', 38.3, 'Severe hypertension', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral sa RHU', NULL),
(239, 89, 6, '2025-05-07', '138/90', 37.8, 'Ubo at lagnat', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred for infection check', NULL),
(240, 90, 6, '2025-05-09', '142/94', 38.1, 'Matinding ulo at mataas BP', 1, 27.5, 76.00, 1.67, 93, 23, NULL, 'Referral required', NULL),
(241, 91, 6, '2025-05-11', '137/89', 37.5, 'Fever and sore throat', 1, 24.8, 69.00, 1.66, 88, 21, NULL, 'Referred for further assessment', NULL),
(242, 95, 6, '2025-05-13', '146/96', 38.4, 'Mataas BP at pagkapagod', 1, 28.9, 82.00, 1.67, 96, 24, NULL, 'Immediate referral to RHU', NULL),
(243, 96, 6, '2025-05-15', '140/92', 37.9, 'Shortness of breath', 1, 26.5, 74.00, 1.66, 92, 23, NULL, 'Referred for respiratory evaluation', NULL),
(244, 98, 6, '2025-05-17', '133/87', 37.3, 'Persistent cough', 1, 25.0, 70.00, 1.66, 87, 21, NULL, 'Referred to RHU', NULL),
(245, 99, 6, '2025-05-19', '141/93', 38.2, 'Severe headache', 1, 27.8, 77.00, 1.67, 92, 23, NULL, 'Referral recommended', NULL),
(246, 100, 6, '2025-05-21', '139/91', 37.7, 'Fever and malaise', 1, 25.2, 71.00, 1.66, 89, 22, NULL, 'Referred due to persistent fever', NULL),
(247, 104, 6, '2025-05-23', '144/95', 38.3, 'High BP with dizziness', 1, 28.5, 79.00, 1.67, 95, 24, NULL, 'Referred to RHU', NULL),
(248, 105, 6, '2025-05-25', '136/88', 37.4, 'Lagnat at ubo', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for further evaluation', NULL),
(249, 112, 6, '2025-05-27', '147/97', 38.5, 'Severe hypertension', 1, 29.3, 83.00, 1.68, 97, 25, NULL, 'Agad na referral', NULL),
(250, 113, 6, '2025-05-29', '134/88', 37.3, 'Ubo at sipon', 1, 24.9, 69.00, 1.67, 88, 21, NULL, 'Referred sa RHU', NULL),
(251, 114, 6, '2025-05-31', '141/92', 37.9, 'High BP with chest pain', 1, 27.8, 77.00, 1.67, 92, 23, NULL, 'Referred for cardiac evaluation', NULL),
(252, 115, 6, '2025-06-02', '139/90', 37.7, 'Fever and cough', 1, 25.2, 71.00, 1.66, 89, 22, NULL, 'Referred to RHU for assessment', NULL),
(253, 203, 6, '2025-08-01', '140/92', 38.0, 'Fever at ubo', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU para sa follow-up', NULL),
(254, 205, 6, '2025-08-03', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(255, 207, 6, '2025-08-05', '145/95', 38.3, 'High BP and dizziness', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(256, 209, 6, '2025-08-07', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang normal', 'Weighing only'),
(257, 210, 6, '2025-08-09', '138/90', 37.7, 'Ubo at mild fever', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(258, 212, 6, '2025-08-11', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'BP ay normal', 'BP only'),
(259, 214, 6, '2025-08-13', '136/88', 37.4, 'Fever and cough', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for further evaluation', NULL),
(260, 216, 6, '2025-08-15', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(261, 218, 6, '2025-08-17', '145/95', 38.3, 'Severe hypertension', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(262, 247, 6, '2025-08-19', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(263, 248, 6, '2025-08-21', '140/92', 38.0, 'Headache and dizziness', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred to RHU', NULL),
(264, 249, 6, '2025-08-23', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(265, 250, 6, '2025-08-25', '138/90', 37.7, 'Ubo at lagnat', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(266, 251, 6, '2025-08-27', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(267, 252, 6, '2025-08-29', '145/95', 38.3, 'High BP and headache', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(268, 253, 6, '2025-08-31', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(269, 254, 6, '2025-09-01', '138/90', 37.7, 'Ubo at lagnat for 3 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU para sa further evaluation', NULL),
(270, 255, 6, '2025-09-03', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(271, 256, 6, '2025-09-05', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(272, 257, 6, '2025-09-07', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang normal', 'Weighing only'),
(273, 258, 6, '2025-09-09', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(274, 259, 6, '2025-07-28', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'BP ay normal', 'BP only'),
(275, 260, 6, '2025-03-23', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for further evaluation', NULL),
(276, 261, 6, '2025-02-02', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(277, 262, 6, '2025-06-21', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(278, 263, 6, '2025-01-02', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(279, 264, 6, '2025-09-06', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred to RHU', NULL),
(280, 265, 6, '2025-08-19', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(281, 266, 6, '2025-06-04', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(282, 267, 6, '2025-03-22', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(283, 268, 6, '2025-07-13', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(284, 269, 6, '2025-08-09', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(285, 270, 6, '2025-01-17', '140/92', 38.0, 'Fever and cough for 1 week', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(286, 48, 7, '2025-06-27', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(287, 61, 7, '2025-03-22', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(288, 123, 7, '2025-05-04', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(289, 130, 7, '2025-05-05', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(290, 143, 7, '2025-01-02', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(291, 271, 7, '2025-05-15', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(292, 272, 7, '2025-06-16', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(293, 273, 7, '2025-06-25', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(294, 274, 7, '2025-05-07', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(295, 275, 7, '2025-04-18', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(296, 276, 7, '2025-06-05', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang normal', 'Weighing only'),
(297, 277, 7, '2025-07-22', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(298, 278, 7, '2025-02-13', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(299, 279, 7, '2025-04-26', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(300, 280, 7, '2025-07-13', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(301, 281, 7, '2025-04-28', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(302, 282, 7, '2025-01-05', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(303, 283, 7, '2025-06-26', '140/92', 38.0, 'Fever and cough for 1 week', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(304, 284, 7, '2025-04-24', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(305, 285, 7, '2025-02-08', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(306, 286, 7, '2025-04-14', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(307, 287, 7, '2025-05-29', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(308, 288, 7, '2025-06-28', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(309, 289, 7, '2025-07-18', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(310, 290, 7, '2025-07-23', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(311, 291, 7, '2025-06-20', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(312, 292, 7, '2025-08-29', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(313, 293, 7, '2025-07-09', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(314, 294, 7, '2025-08-11', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(315, 49, 8, '2025-02-13', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(316, 62, 8, '2025-02-22', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(317, 124, 8, '2025-05-13', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(318, 131, 8, '2025-01-02', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(319, 137, 8, '2025-04-26', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(320, 144, 8, '2025-03-09', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(321, 295, 8, '2025-09-01', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(322, 296, 8, '2025-01-07', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(323, 297, 8, '2025-02-26', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(324, 298, 8, '2025-01-14', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(325, 299, 8, '2025-05-31', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(326, 300, 8, '2025-07-25', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(327, 301, 8, '2025-03-15', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(328, 302, 8, '2025-01-02', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(329, 303, 8, '2025-02-10', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(330, 304, 8, '2025-07-16', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(331, 305, 8, '2025-01-02', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(332, 306, 8, '2025-01-04', '140/92', 38.0, 'Fever and cough for 1 week', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(333, 307, 8, '2025-01-06', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(334, 308, 8, '2025-01-08', '138/90', 37.7, 'Fever and sore throat for 2 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(335, 309, 8, '2025-01-10', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(336, 310, 8, '2025-01-12', '136/88', 37.4, 'Cough and mild fever for 3 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(337, 311, 8, '2025-01-14', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(338, 312, 8, '2025-01-16', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(339, 313, 8, '2025-01-18', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(340, 314, 8, '2025-01-20', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(341, 315, 8, '2025-01-22', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(342, 316, 8, '2025-01-24', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(343, 317, 8, '2025-01-26', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(344, 318, 8, '2025-01-28', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(345, 319, 8, '2025-01-30', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(346, 320, 8, '2025-02-01', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(347, 321, 8, '2025-02-03', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(348, 322, 8, '2025-02-05', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(349, 323, 8, '2025-02-07', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(350, 324, 8, '2025-02-09', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(351, 325, 8, '2025-02-11', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(352, 326, 8, '2025-02-13', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(353, 327, 8, '2025-02-15', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(354, 328, 8, '2025-02-17', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(355, 329, 8, '2025-02-19', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(356, 330, 8, '2025-02-21', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(357, 331, 8, '2025-02-23', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(358, 332, 8, '2025-02-25', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(359, 333, 8, '2025-02-27', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(360, 334, 8, '2025-03-01', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(361, 335, 8, '2025-03-03', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(362, 336, 8, '2025-03-05', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(363, 337, 8, '2025-03-07', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(364, 338, 8, '2025-03-09', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(365, 339, 8, '2025-03-11', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(366, 340, 8, '2025-03-13', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(367, 341, 8, '2025-03-15', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(368, 342, 8, '2025-03-17', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(369, 343, 8, '2025-03-19', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(370, 344, 8, '2025-03-21', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(371, 329, 8, '2025-02-19', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(372, 330, 8, '2025-02-21', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(373, 331, 8, '2025-02-23', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(374, 332, 8, '2025-02-25', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(375, 333, 8, '2025-02-27', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(376, 334, 8, '2025-03-01', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(377, 335, 8, '2025-03-03', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(378, 336, 8, '2025-03-05', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(379, 337, 8, '2025-03-07', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(380, 338, 8, '2025-03-09', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(381, 339, 8, '2025-03-11', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(382, 340, 8, '2025-03-13', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(383, 341, 8, '2025-03-15', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(384, 342, 8, '2025-03-17', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(385, 343, 8, '2025-03-19', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(386, 344, 8, '2025-03-21', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(387, 49, 9, '2025-03-23', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(388, 62, 9, '2025-03-25', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(389, 124, 9, '2025-03-27', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(390, 131, 9, '2025-03-29', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(391, 137, 9, '2025-03-31', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(392, 144, 9, '2025-04-02', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(393, 295, 9, '2025-04-04', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(394, 296, 9, '2025-04-06', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(395, 297, 9, '2025-04-08', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(396, 298, 9, '2025-04-10', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(397, 299, 9, '2025-04-12', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(398, 300, 9, '2025-04-14', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(399, 301, 9, '2025-04-16', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(400, 302, 9, '2025-04-18', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(401, 303, 9, '2025-04-20', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(402, 304, 9, '2025-04-22', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(403, 305, 9, '2025-04-24', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(404, 306, 9, '2025-04-26', '140/92', 38.0, 'Fever and cough for 1 week', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(405, 307, 9, '2025-04-28', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(406, 308, 9, '2025-04-30', '138/90', 37.7, 'Fever and sore throat for 2 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(407, 309, 9, '2025-05-02', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(408, 310, 9, '2025-05-04', '136/88', 37.4, 'Cough and mild fever for 3 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(409, 311, 9, '2025-05-06', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(410, 312, 9, '2025-05-08', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(411, 313, 9, '2025-05-10', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(412, 314, 9, '2025-05-12', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(413, 315, 9, '2025-05-14', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(414, 316, 9, '2025-05-16', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(415, 317, 9, '2025-05-18', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(416, 318, 9, '2025-05-20', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(417, 319, 9, '2025-05-22', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(418, 320, 9, '2025-05-24', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(419, 321, 9, '2025-05-26', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(420, 322, 9, '2025-05-28', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(421, 323, 9, '2025-05-30', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(422, 324, 9, '2025-06-01', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(423, 325, 9, '2025-06-03', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(424, 326, 9, '2025-06-05', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(425, 327, 9, '2025-06-07', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(426, 328, 9, '2025-06-09', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(427, 329, 9, '2025-06-11', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(428, 330, 9, '2025-06-13', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(429, 331, 9, '2025-06-15', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(430, 332, 9, '2025-06-17', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(431, 333, 9, '2025-06-19', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(432, 334, 9, '2025-06-21', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(433, 335, 9, '2025-06-23', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(434, 336, 9, '2025-06-25', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(435, 337, 9, '2025-06-27', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(436, 338, 9, '2025-06-29', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(437, 339, 9, '2025-07-01', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(438, 340, 9, '2025-07-03', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(439, 341, 9, '2025-07-05', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(440, 342, 9, '2025-07-07', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(441, 343, 9, '2025-07-09', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(442, 344, 9, '2025-07-11', '140/92', 38.0, 'Fever and cough for 1 week', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(443, 51, 10, '2025-07-13', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(444, 57, 10, '2025-07-15', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(445, 65, 10, '2025-07-17', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(446, 71, 10, '2025-07-19', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(447, 126, 10, '2025-07-21', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(448, 133, 10, '2025-07-23', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(449, 139, 10, '2025-07-25', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(450, 146, 10, '2025-07-27', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(451, 345, 10, '2025-07-29', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(452, 346, 10, '2025-07-31', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(453, 347, 10, '2025-08-02', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(454, 348, 10, '2025-08-04', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(455, 349, 10, '2025-08-06', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(456, 350, 10, '2025-08-08', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(457, 351, 10, '2025-08-10', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(458, 352, 10, '2025-08-12', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(459, 353, 10, '2025-08-14', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(460, 354, 10, '2025-08-16', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(461, 355, 10, '2025-08-18', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(462, 356, 10, '2025-08-20', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(463, 357, 10, '2025-08-22', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(464, 358, 10, '2025-08-24', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(465, 359, 10, '2025-08-26', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(466, 360, 10, '2025-08-28', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(467, 361, 10, '2025-08-30', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(468, 362, 10, '2025-09-01', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(469, 363, 10, '2025-09-03', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(470, 364, 10, '2025-09-05', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(471, 365, 10, '2025-09-07', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(472, 366, 10, '2025-09-09', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(473, 367, 10, '2025-04-16', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(474, 368, 10, '2025-07-12', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(475, 369, 10, '2025-05-19', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(476, 52, 11, '2025-04-28', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(477, 56, 11, '2025-06-21', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(478, 64, 11, '2025-01-02', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(479, 69, 11, '2025-09-06', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(480, 127, 11, '2025-08-20', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(481, 134, 11, '2025-06-07', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(482, 140, 11, '2025-04-03', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(483, 147, 11, '2025-09-02', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(484, 370, 11, '2025-07-09', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only');
INSERT INTO `patient_assessment` (`visit_id`, `patient_id`, `recorded_by`, `visit_date`, `blood_pressure`, `temperature`, `chief_complaints`, `referred_to_rhu`, `bmi`, `weight`, `height`, `chest_rate`, `respiratory_rate`, `patient_alert`, `remarks`, `treatment`) VALUES
(485, 371, 11, '2025-08-04', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(486, 372, 11, '2025-01-02', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(487, 373, 11, '2025-04-28', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(488, 374, 11, '2025-03-22', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(489, 375, 11, '2025-02-20', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(490, 376, 11, '2025-01-09', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(491, 377, 11, '2025-05-23', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(492, 378, 11, '2025-07-03', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(493, 379, 11, '2025-08-25', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(494, 380, 11, '2025-05-11', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(495, 381, 11, '2025-07-14', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(496, 382, 11, '2025-03-17', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(497, 383, 11, '2025-02-18', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(498, 384, 11, '2025-01-15', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(499, 385, 11, '2025-06-30', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(500, 386, 11, '2025-04-12', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(501, 387, 11, '2025-08-08', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(502, 388, 11, '2025-02-07', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(503, 389, 11, '2025-02-01', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(504, 390, 11, '2025-02-15', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(505, 391, 11, '2025-05-12', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(506, 392, 11, '2025-01-19', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(507, 393, 11, '2025-07-19', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(508, 53, 12, '2025-03-12', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(509, 55, 12, '2025-04-05', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(510, 58, 12, '2025-02-20', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(511, 66, 12, '2025-05-18', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(512, 68, 12, '2025-01-28', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(513, 72, 12, '2025-03-30', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(514, 128, 12, '2025-06-12', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(515, 135, 12, '2025-07-05', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(516, 141, 12, '2025-08-01', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(517, 394, 12, '2025-02-14', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(518, 395, 12, '2025-03-22', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(519, 396, 12, '2025-04-28', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(520, 397, 12, '2025-05-15', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(521, 398, 12, '2025-06-09', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(522, 399, 12, '2025-07-18', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(523, 400, 12, '2025-08-23', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(524, 401, 12, '2025-01-10', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(525, 402, 12, '2025-02-05', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(526, 403, 12, '2025-02-18', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(527, 404, 12, '2025-03-01', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(528, 405, 12, '2025-03-15', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(529, 406, 12, '2025-03-28', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(530, 407, 12, '2025-04-05', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(531, 408, 12, '2025-04-20', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(532, 409, 12, '2025-05-02', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(533, 410, 12, '2025-05-18', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(534, 411, 12, '2025-06-01', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(535, 412, 12, '2025-06-15', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(536, 413, 12, '2025-07-02', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(537, 414, 12, '2025-07-18', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(538, 415, 12, '2025-08-01', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(539, 416, 12, '2025-08-16', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(540, 417, 12, '2025-09-05', '130/85', 37.2, 'Routine check-up', 0, 22.5, 60.00, 1.65, 82, 19, NULL, 'Normal ang lahat', 'BP only'),
(541, 418, 12, '2025-09-20', '142/90', 37.8, 'Fever for 2 days', 1, 26.5, 72.00, 1.67, 90, 22, NULL, 'Referred sa RHU', NULL),
(542, 54, 13, '2025-01-18', '122/80', 36.7, 'Routine check-up', 0, 21.6, 58.50, 1.65, 81, 18, NULL, 'Normal ang lahat', 'BP only'),
(543, 60, 13, '2025-02-12', '138/88', 37.5, 'Fever for 2 days', 1, 25.2, 68.00, 1.66, 89, 21, NULL, 'Referred sa RHU', NULL),
(544, 67, 13, '2025-03-01', '120/78', 36.6, 'Weighing only', 0, 20.9, 54.50, 1.63, 79, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(545, 70, 13, '2025-03-18', '142/90', 37.8, 'High BP and headache for 1 day', 1, 27.8, 75.00, 1.67, 94, 23, NULL, 'Agad na referral', NULL),
(546, 129, 13, '2025-04-05', '125/82', 37.0, 'BP check-up', 0, 23.1, 63.50, 1.65, 83, 19, NULL, 'All normal', 'BP only'),
(547, 136, 13, '2025-04-22', '136/88', 37.4, 'Cough and mild fever for 3 days', 1, 24.7, 68.50, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(548, 142, 13, '2025-05-08', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(549, 419, 13, '2025-05-25', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.3, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(550, 420, 13, '2025-06-10', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(551, 421, 13, '2025-06-28', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(552, 422, 13, '2025-07-12', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(553, 423, 13, '2025-07-28', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(554, 424, 13, '2025-08-03', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(555, 425, 13, '2025-08-19', '145/95', 38.3, 'High BP and headache for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Referral required', NULL),
(556, 426, 13, '2025-09-05', '130/85', 37.2, 'Routine check-up', 0, 22.5, 60.00, 1.65, 82, 19, NULL, 'Normal ang lahat', 'BP only'),
(557, 427, 13, '2025-09-20', '142/90', 37.8, 'Fever for 2 days', 1, 26.5, 72.00, 1.67, 90, 22, NULL, 'Referred sa RHU', NULL),
(558, 428, 13, '2025-10-02', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(559, 429, 13, '2025-01-20', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.65, 80, 18, NULL, 'Normal ang lahat', 'BP only'),
(560, 430, 13, '2025-02-10', '140/92', 38.0, 'Fever and cough for 3 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU for follow-up', NULL),
(561, 431, 13, '2025-02-23', '125/82', 37.0, 'BP check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'BP ay normal', 'BP only'),
(562, 432, 13, '2025-03-04', '145/95', 38.3, 'High BP and dizziness for 2 days', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(563, 433, 13, '2025-03-19', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(564, 434, 13, '2025-03-31', '138/90', 37.7, 'Fever and sore throat for 4 days', 1, 25.0, 70.00, 1.66, 90, 22, NULL, 'Referred sa RHU', NULL),
(565, 435, 13, '2025-04-07', '121/79', 36.7, 'BP check-up', 0, 21.8, 59.00, 1.64, 80, 18, NULL, 'Normal', 'BP only'),
(566, 436, 13, '2025-04-22', '136/88', 37.4, 'Cough and mild fever for 5 days', 1, 24.6, 68.00, 1.66, 88, 21, NULL, 'Referred for evaluation', NULL),
(567, 437, 13, '2025-05-04', '120/80', 36.8, 'Routine check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'All normal', 'BP only'),
(568, 438, 13, '2025-05-20', '145/95', 38.3, 'Severe hypertension for 1 day', 1, 28.2, 80.00, 1.67, 95, 24, NULL, 'Agad na referral', NULL),
(569, 439, 13, '2025-06-03', '118/76', 36.6, 'Weighing only', 0, 20.8, 54.00, 1.63, 78, 17, NULL, 'Timbang ay normal', 'Weighing only'),
(570, 440, 13, '2025-06-17', '140/92', 38.0, 'Headache and dizziness for 2 days', 1, 27.0, 75.00, 1.67, 92, 23, NULL, 'Referred sa RHU', NULL),
(571, 441, 13, '2025-07-04', '125/82', 37.0, 'Routine check-up', 0, 23.0, 63.00, 1.65, 83, 19, NULL, 'Lahat ay normal', 'BP only'),
(572, 442, 13, '2025-07-20', '138/90', 37.7, 'Fever and cough for 3 days', 1, 25.5, 70.00, 1.66, 90, 22, NULL, 'Referred for infection', NULL),
(573, 443, 13, '2025-08-05', '120/80', 36.8, 'BP check-up', 0, 21.5, 58.00, 1.64, 80, 18, NULL, 'BP normal', 'BP only'),
(574, 2, 3, '2025-09-12', '12', 12.0, '222', 0, 833.3, 12.00, 12.00, NULL, NULL, '', '222', 'Immunization'),
(575, 444, 5, '2025-09-15', '120/80', 37.0, 'headache for 10 days', 0, 20.1, 49.00, 156.00, 0, 0, '', '', ''),
(576, 445, 5, '2025-09-15', '120/80', 37.0, 'Masakit ulo ko. 10 days na.', 0, 27.1, 66.00, 156.00, 0, 0, '', 'Patient needs urgent care', '');

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
(1, 2, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-09-12 14:15:33', 3, NULL, 574),
(2, 444, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-09-15 12:33:16', 5, NULL, 575),
(3, 445, 'COLLECTION AND USE OF PERSONAL HEALTH INFORMATION', 'verbal', '2025-09-15 15:34:56', 5, NULL, 576);

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
(1, 2, 2, 5, 'Completed', '2025-01-15'),
(2, 5, 5, 5, 'Uncompleted', '2025-02-10'),
(3, 7, 7, 5, 'Canceled', '2025-02-20'),
(4, 15, 10, 5, 'Completed', '2025-03-05'),
(5, 18, 13, 5, 'Uncompleted', '2025-03-20'),
(6, 21, 16, 5, 'Completed', '2025-04-05'),
(7, 28, 18, 5, 'Canceled', '2025-04-15'),
(8, 31, 21, 5, 'Completed', '2025-05-01'),
(9, 33, 23, 5, 'Uncompleted', '2025-05-10'),
(10, 41, 26, 5, 'Completed', '2025-01-18'),
(11, 44, 29, 5, 'Canceled', '2025-02-06'),
(12, 46, 31, 5, 'Completed', '2025-02-16'),
(13, 77, 34, 5, 'Uncompleted', '2025-03-07'),
(14, 82, 37, 5, 'Completed', '2025-03-24'),
(15, 88, 40, 5, 'Canceled', '2025-04-09'),
(16, 94, 43, 5, 'Completed', '2025-04-25'),
(17, 101, 45, 5, 'Uncompleted', '2025-05-06'),
(18, 106, 48, 5, 'Completed', '2025-05-21'),
(19, 109, 51, 5, 'Canceled', '2025-06-06'),
(20, 111, 53, 5, 'Completed', '2025-06-16'),
(21, 122, 56, 5, 'Uncompleted', '2025-07-01'),
(22, 151, 59, 5, 'Completed', '2025-07-18'),
(23, 155, 61, 5, 'Canceled', '2025-07-29'),
(24, 160, 64, 5, 'Completed', '2025-08-15'),
(25, 166, 67, 5, 'Completed', '2025-09-02'),
(26, 170, 69, 5, 'Uncompleted', '2025-01-07'),
(27, 175, 72, 5, 'Completed', '2025-04-22'),
(28, 179, 75, 5, 'Completed', '2025-07-13'),
(29, 185, 193, 5, 'Canceled', '2025-04-22'),
(30, 190, 196, 5, 'Completed', '2025-05-21'),
(31, 192, 197, 5, 'Completed', '2025-04-15'),
(32, 193, 198, 5, 'Completed', '2025-04-10'),
(33, 194, 199, 5, 'Completed', '2025-07-05'),
(34, 195, 200, 5, 'Uncompleted', '2025-05-06'),
(35, 199, 201, 5, 'Completed', '2025-03-10'),
(36, 201, 202, 5, 'Completed', '2025-08-07'),
(37, 202, 203, 5, 'Completed', '2025-05-11'),
(38, 204, 204, 5, 'Canceled', '2025-09-05'),
(39, 206, 205, 5, 'Completed', '2025-04-02'),
(40, 208, 206, 5, 'Completed', '2025-08-09'),
(41, 211, 207, 5, 'Completed', '2025-03-15'),
(42, 213, 208, 5, 'Completed', '2025-07-26'),
(43, 215, 209, 5, 'Uncompleted', '2025-02-27'),
(44, 217, 210, 5, 'Completed', '2025-06-21'),
(45, 219, 211, 5, 'Completed', '2025-07-02'),
(46, 220, 212, 5, 'Completed', '2025-05-25'),
(47, 221, 213, 5, 'Completed', '2025-06-23'),
(48, 222, 214, 5, 'Uncompleted', '2025-07-05'),
(49, 223, 215, 5, 'Completed', '2025-06-03'),
(50, 84, 236, 6, 'Completed', '2025-05-01'),
(51, 85, 237, 6, 'Completed', '2025-05-03'),
(52, 86, 238, 6, 'Canceled', '2025-05-05'),
(53, 89, 239, 6, 'Completed', '2025-05-07'),
(54, 90, 240, 6, 'Completed', '2025-05-09'),
(55, 91, 241, 6, 'Completed', '2025-05-11'),
(56, 95, 242, 6, 'Uncompleted', '2025-05-13'),
(57, 96, 243, 6, 'Completed', '2025-05-15'),
(58, 98, 244, 6, 'Completed', '2025-05-17'),
(59, 99, 245, 6, 'Completed', '2025-05-19'),
(60, 100, 246, 6, 'Completed', '2025-05-21'),
(61, 104, 247, 6, 'Canceled', '2025-05-23'),
(62, 105, 248, 6, 'Completed', '2025-05-25'),
(63, 112, 249, 6, 'Completed', '2025-05-27'),
(64, 113, 250, 6, 'Completed', '2025-05-29'),
(65, 114, 251, 6, 'Uncompleted', '2025-05-31'),
(66, 115, 252, 6, 'Completed', '2025-06-02'),
(67, 203, 253, 6, 'Completed', '2025-08-01'),
(68, 207, 255, 6, 'Completed', '2025-08-05'),
(69, 210, 257, 6, 'Completed', '2025-08-09'),
(70, 214, 259, 6, 'Completed', '2025-08-13'),
(71, 218, 261, 6, 'Completed', '2025-08-17'),
(72, 248, 263, 6, 'Completed', '2025-08-21'),
(73, 250, 265, 6, 'Completed', '2025-08-25'),
(74, 252, 267, 6, 'Completed', '2025-08-29'),
(75, 254, 269, 6, 'Completed', '2025-09-01'),
(76, 256, 271, 6, 'Completed', '2025-09-05'),
(77, 258, 273, 6, 'Completed', '2025-09-09'),
(78, 260, 275, 6, 'Completed', '2025-03-23'),
(79, 262, 277, 6, 'Completed', '2025-06-21'),
(80, 264, 279, 6, 'Completed', '2025-09-06'),
(81, 266, 281, 6, 'Completed', '2025-06-04'),
(82, 268, 283, 6, 'Completed', '2025-07-13'),
(83, 270, 285, 6, 'Uncompleted', '2025-01-17'),
(84, 61, 287, 7, 'Canceled', '2025-03-22'),
(85, 130, 289, 7, 'Completed', '2025-05-05'),
(86, 271, 291, 7, 'Completed', '2025-05-15'),
(87, 273, 293, 7, 'Completed', '2025-06-25'),
(88, 275, 295, 7, 'Completed', '2025-04-18'),
(89, 277, 297, 7, 'Completed', '2025-07-22'),
(90, 279, 299, 7, 'Completed', '2025-04-26'),
(91, 281, 301, 7, 'Completed', '2025-04-28'),
(92, 283, 303, 7, 'Completed', '2025-06-26'),
(93, 285, 305, 7, 'Completed', '2025-02-08'),
(94, 287, 307, 7, 'Completed', '2025-05-29'),
(95, 289, 309, 7, 'Completed', '2025-07-18'),
(96, 291, 311, 7, 'Completed', '2025-06-20'),
(97, 293, 313, 7, 'Completed', '2025-07-09'),
(98, 62, 316, 8, 'Completed', '2025-02-22'),
(99, 131, 318, 8, 'Completed', '2025-01-02'),
(100, 144, 320, 8, 'Completed', '2025-03-09'),
(101, 2, 2, 3, 'Forwarded to Physician', '2025-09-12'),
(102, 444, 575, 5, 'Completed', '2025-09-15'),
(103, 445, 576, 5, 'Completed', '2025-09-15');

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
(1, 2, 2, '2025-01-15', 'Fever', 'Uminom ng paracetamol, take plenty of fluids, at magpahinga ng sapat', '2025-01-22', 2, NULL, 'Ongoing'),
(2, 15, 2, '2025-03-05', 'Flu', 'Take paracetamol 3x a day, dagdagan ng tubig, iwasan ang malamig', NULL, 10, NULL, 'Treated'),
(3, 21, 2, '2025-04-05', 'Cough', 'Continue antibiotics, uminom ng maraming fluids, at magpahinga', '2025-04-19', 16, NULL, 'Ongoing'),
(4, 31, 2, '2025-05-01', 'Hypertension', 'Monitor blood pressure araw-araw, bawasan ang maalat na pagkain', '2025-05-08', 21, NULL, 'Ongoing'),
(5, 41, 2, '2025-01-18', 'Cough', 'Magpahinga, uminom ng gamot sa ubo, at iwasan ang malamig na inumin', NULL, 26, NULL, 'Treated'),
(6, 46, 2, '2025-02-16', 'Fever', 'Take cough syrup, paracetamol kung may lagnat, at magpahinga', '2025-02-26', 31, NULL, 'Ongoing'),
(7, 82, 2, '2025-03-24', 'Hypertension', 'Check BP daily, inumin ang gamot sa alta presyon, at magpahinga', '2025-03-31', 37, NULL, 'Ongoing'),
(8, 94, 2, '2025-04-25', 'Hypertension', 'Immediate referral to hospital, bantayan ang sintomas', NULL, 43, NULL, 'Reffered'),
(9, 106, 2, '2025-05-21', 'Hypertension', 'Regular BP check, sundin ang maintenance medicine, avoid salty food', '2025-05-28', 48, NULL, 'Ongoing'),
(10, 111, 2, '2025-06-16', 'Cough', 'Take cough syrup, vitamin C, and uminom ng maraming tubig', NULL, 53, NULL, 'Treated'),
(11, 151, 2, '2025-07-18', 'Fever', 'Take paracetamol kung may lagnat, uminom ng salabat or warm water', '2025-07-25', 59, NULL, 'Ongoing'),
(12, 160, 2, '2025-08-15', 'Hypertension', 'Strict BP monitoring, bawasan ang maalat, mag-exercise kung kaya', '2025-08-22', 64, NULL, 'Ongoing'),
(13, 166, 2, '2025-09-02', 'Cough', 'Paracetamol for fever, cough syrup, at magpahinga ng sapat', '2025-09-09', 67, NULL, 'Ongoing'),
(14, 175, 2, '2025-04-22', 'Hypertension', 'Monitor BP daily, iwasan ang maalat, at bumalik for follow-up', '2025-04-29', 72, NULL, 'Ongoing'),
(15, 179, 2, '2025-07-13', 'Flu', 'Take paracetamol, uminom ng tubig, magpahinga, at avoid stress', NULL, 75, NULL, 'Treated'),
(16, 190, 2, '2025-08-05', 'Hypertension', 'Take prescribed antihypertensive medicine and monitor BP daily.', '2025-08-12', 196, NULL, 'Ongoing'),
(17, 192, 2, '2025-08-09', 'Flu', 'Uminom ng maraming tubig at magpahinga. Take paracetamol for fever.', '2025-08-16', 197, NULL, 'Ongoing'),
(18, 193, 2, '2025-08-13', 'Hypertension', 'Take medicine regularly and avoid salty foods.', '2025-08-20', 198, NULL, 'Ongoing'),
(19, 194, 2, '2025-08-17', 'Angina', 'Avoid heavy activity, take prescribed cardiac meds.', '2025-08-24', 199, NULL, 'Ongoing'),
(20, 199, 2, '2025-08-21', 'Hypertension', 'Continue antihypertensive therapy, follow low-salt diet.', '2025-08-28', 201, NULL, 'Ongoing'),
(21, 201, 2, '2025-08-25', 'Flu', 'Take fluids, rest, and prescribed fever medicine.', '2025-09-01', 202, NULL, 'Ongoing'),
(22, 202, 2, '2025-08-29', 'Hypertension', 'Monitor blood pressure daily and continue medication.', '2025-09-05', 203, NULL, 'Ongoing'),
(23, 206, 2, '2025-09-01', 'Hypertension', 'Take medicine on time, avoid stress and salty foods.', '2025-09-08', 205, NULL, 'Ongoing'),
(24, 208, 2, '2025-09-05', 'Flu', 'Uminom ng maraming tubig, take paracetamol for fever.', '2025-09-12', 206, NULL, 'Ongoing'),
(25, 211, 2, '2025-09-09', 'Hypertension', 'Continue BP monitoring, follow doctor’s prescription.', '2025-09-16', 207, NULL, 'Ongoing'),
(26, 213, 2, '2025-03-23', 'Pneumonia', 'Take antibiotics as prescribed, rest and hydrate.', '2025-03-30', 208, NULL, 'Ongoing'),
(27, 217, 2, '2025-06-21', 'Flu', 'Magpahinga, take medicine for cough and fever.', '2025-06-28', 210, NULL, 'Ongoing'),
(28, 219, 2, '2025-09-06', 'Hypertension', 'Continue regular checkup and medication.', '2025-09-13', 211, NULL, 'Ongoing'),
(29, 220, 2, '2025-06-04', 'Flu', 'Take plenty of fluids and rest well.', '2025-06-11', 212, NULL, 'Ongoing'),
(30, 221, 2, '2025-07-13', 'Migraine', 'Take prescribed pain reliever, avoid stress.', '2025-07-20', 213, NULL, 'Ongoing'),
(31, 223, 2, '2025-01-17', 'Hypertension', 'Maintain low-sodium diet and take medicine regularly.', '2025-01-24', 215, NULL, 'Ongoing'),
(32, 84, 2, '2025-03-22', 'Hypertension', 'Uminom ng gamot araw-araw at iwasan ang maalat na pagkain.', '2025-03-29', 236, NULL, 'Ongoing'),
(33, 85, 2, '2025-05-05', 'Flu', 'Take rest and drink fluids.', '2025-05-12', 237, NULL, 'Ongoing'),
(34, 89, 2, '2025-05-15', 'Flu', 'Take paracetamol for fever and plenty of fluids.', '2025-05-22', 239, NULL, 'Ongoing'),
(35, 90, 2, '2025-05-09', 'Hypertension', 'Take antihypertensive meds, avoid stress and salty food.', '2025-05-16', 240, NULL, 'Ongoing'),
(36, 91, 2, '2025-05-11', 'Tonsillitis', 'Take antibiotics as prescribed, gargle warm salt water.', '2025-05-18', 241, NULL, 'Ongoing'),
(37, 96, 2, '2025-05-15', 'Pneumonia', 'Take antibiotics, rest, and hydrate well.', '2025-05-22', 243, NULL, 'Ongoing'),
(38, 98, 2, '2025-05-17', 'Tuberculosis', 'Take TB regimen daily, avoid close contact until cleared.', '2025-05-24', 244, NULL, 'Ongoing'),
(39, 99, 2, '2025-05-19', 'Migraine', 'Take prescribed pain reliever, avoid stress triggers.', '2025-05-26', 245, NULL, 'Ongoing'),
(40, 100, 2, '2025-05-21', 'Influenza', 'Take fluids, rest, and paracetamol for fever.', '2025-05-28', 246, NULL, 'Ongoing'),
(41, 105, 2, '2025-05-25', 'Bronchitis', 'Take prescribed cough medicine, avoid smoke and cold drinks.', '2025-06-01', 248, NULL, 'Ongoing'),
(42, 112, 2, '2025-05-27', 'Hypertension', 'Continue daily BP meds, monitor BP at home.', '2025-06-03', 249, NULL, 'Ongoing'),
(43, 113, 2, '2025-05-29', 'Flu', 'Take paracetamol, rest, and increase fluid intake.', '2025-06-05', 250, NULL, 'Ongoing'),
(44, 115, 2, '2025-06-02', 'Influenza', 'Rest, drink warm fluids, take prescribed fever medicine.', '2025-06-09', 252, NULL, 'Ongoing'),
(45, 203, 2, '2025-08-01', 'Bronchitis', 'Take antibiotics and cough syrup as prescribed.', '2025-08-08', 253, NULL, 'Ongoing'),
(46, 207, 2, '2025-08-05', 'Hypertension', 'Take antihypertensive daily and avoid salty food.', '2025-08-12', 255, NULL, 'Ongoing'),
(47, 210, 2, '2025-08-09', 'Flu', 'Take rest, fluids, and paracetamol for fever.', '2025-08-16', 257, NULL, 'Ongoing'),
(48, 214, 2, '2025-08-13', 'Influenza', 'Stay hydrated, take medicine for fever and cough.', '2025-08-20', 259, NULL, 'Ongoing'),
(49, 218, 2, '2025-08-17', 'Hypertension', 'Continue medication, regular BP checkup.', '2025-08-24', 261, NULL, 'Ongoing'),
(50, 248, 2, '2025-08-21', 'Migraine', 'Take pain relievers, rest in a dark quiet room.', '2025-08-28', 263, NULL, 'Ongoing'),
(51, 250, 2, '2025-08-25', 'Influenza', 'Rest, take prescribed medicine, and hydrate well.', '2025-09-01', 265, NULL, 'Ongoing'),
(52, 252, 2, '2025-08-29', 'Hypertension', 'Maintain low-salt diet and daily BP monitoring.', '2025-09-05', 267, NULL, 'Ongoing'),
(53, 254, 2, '2025-09-01', 'Pneumonia', 'Take antibiotics, hydrate, and rest properly.', '2025-09-08', 269, NULL, 'Ongoing'),
(54, 256, 2, '2025-09-05', 'Hypertension', 'Take daily antihypertensives and avoid stress.', '2025-09-12', 271, NULL, 'Ongoing'),
(55, 258, 2, '2025-09-09', 'Tonsillitis', 'Take antibiotics, gargle warm water with salt.', '2025-09-16', 273, NULL, 'Ongoing'),
(56, 260, 2, '2025-03-23', 'Bronchitis', 'Take antibiotics and cough medicine as prescribed.', '2025-03-30', 275, NULL, 'Ongoing'),
(57, 262, 2, '2025-06-21', 'Hypertension', 'Continue BP medications and follow up regularly.', '2025-06-28', 277, NULL, 'Ongoing'),
(58, 264, 2, '2025-07-13', 'Migraine', 'Take pain relievers and avoid triggers like stress.', '2025-07-20', 279, NULL, 'Ongoing'),
(59, 266, 2, '2025-06-04', 'Influenza', 'Take paracetamol for fever, drink plenty of fluids, and rest.', '2025-06-11', 281, NULL, 'Ongoing'),
(60, 268, 2, '2025-07-13', 'Hypertension', 'Continue BP meds daily, avoid salty food and stress.', '2025-07-20', 283, NULL, 'Ongoing'),
(61, 130, 2, '2025-05-05', 'Hypertension', 'Take antihypertensive medication and monitor BP regularly.', '2025-05-12', 289, NULL, 'Ongoing'),
(62, 271, 2, '2025-05-15', 'Tonsillitis', 'Take antibiotics as prescribed, gargle warm salt water.', '2025-05-22', 291, NULL, 'Ongoing'),
(63, 273, 2, '2025-06-25', 'Bronchitis', 'Take cough syrup and antibiotics, avoid cold drinks.', '2025-07-02', 293, NULL, 'Ongoing'),
(64, 275, 2, '2025-04-18', 'Hypertension', 'Strict medication compliance, low-salt diet, avoid stress.', '2025-04-25', 295, NULL, 'Ongoing'),
(65, 277, 2, '2025-07-22', 'Migraine', 'Take pain reliever, rest in a dark quiet room.', '2025-07-29', 297, NULL, 'Ongoing'),
(66, 279, 2, '2025-04-26', 'Influenza', 'Take paracetamol, drink warm fluids, and rest well.', '2025-05-03', 299, NULL, 'Ongoing'),
(67, 281, 2, '2025-04-28', 'Hypertension', 'Continue prescribed BP meds, check BP daily.', '2025-05-05', 301, NULL, 'Ongoing'),
(68, 283, 2, '2025-06-26', 'Tuberculosis', 'Follow TB regimen strictly, avoid close contact with others.', '2025-07-03', 303, NULL, 'Ongoing'),
(69, 285, 2, '2025-02-08', 'Influenza', 'Take fever medicine, hydrate, and get enough rest.', '2025-02-15', 305, NULL, 'Ongoing'),
(70, 287, 2, '2025-05-29', 'Hypertension', 'Take daily antihypertensives, avoid stress and salty food.', '2025-06-05', 307, NULL, 'Ongoing'),
(71, 289, 2, '2025-07-18', 'Tonsillitis', 'Antibiotics as prescribed, rest, and gargle salt water.', '2025-07-25', 309, NULL, 'Ongoing'),
(72, 291, 2, '2025-06-20', 'Bronchitis', 'Take antibiotics and cough syrup, hydrate well.', '2025-06-27', 311, NULL, 'Ongoing'),
(73, 293, 2, '2025-07-09', 'Hypertension', 'Continue medication, monitor BP twice daily.', '2025-07-16', 313, NULL, 'Ongoing'),
(74, 62, 2, '2025-02-22', 'Influenza', 'Take prescribed fever medicine, drink warm fluids.', '2025-03-01', 316, NULL, 'Ongoing'),
(75, 131, 2, '2025-01-02', 'Hypertension', 'Maintain BP medication routine, reduce stress.', '2025-01-09', 318, NULL, 'Ongoing'),
(76, 144, 2, '2025-03-09', 'Tonsillitis', 'Take antibiotics, avoid cold drinks, gargle warm water.', '2025-03-16', 320, NULL, 'Ongoing'),
(77, 444, 2, '2025-09-15', 'Stroke', '', '2025-09-16', 575, 'uploads/1757910985_RHU 1 Letter.pdf', 'Ongoing'),
(78, 444, 2, '2025-09-15', 'Colon Cancer', '', '2025-09-18', 575, NULL, 'Ongoing'),
(79, 444, 2, '2025-09-15', 'Colon Cancer', '', '2025-09-30', 575, NULL, 'Ongoing'),
(80, 445, 2, '2025-09-15', 'Colon Cancer', '', '2025-09-16', 576, 'uploads/1757922700_RHU 1 Letter.pdf', 'Ongoing');

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
(1, 1, 'Paracetamol 500mg', 10, 2, '2025-01-14 16:00:00'),
(2, 2, 'Paracetamol 500mg', 15, 2, '2025-03-04 16:00:00'),
(3, 3, 'Amoxicillin 500mg', 14, 2, '2025-04-04 16:00:00'),
(4, 4, 'Losartan 50mg', 30, 2, '2025-04-30 16:00:00'),
(5, 5, 'Ambroxol Syrup 60ml', 1, 2, '2025-01-17 16:00:00'),
(6, 6, 'Paracetamol 500mg', 12, 2, '2025-02-15 16:00:00'),
(7, 7, 'Amlodipine 5mg', 30, 2, '2025-03-23 16:00:00'),
(8, 8, 'Captopril 25mg', 20, 2, '2025-04-24 16:00:00'),
(9, 9, 'Losartan 50mg', 30, 2, '2025-05-20 16:00:00'),
(10, 10, 'Carbocisteine Syrup 60ml', 1, 2, '2025-06-15 16:00:00'),
(11, 11, 'Paracetamol 500mg', 10, 2, '2025-07-17 16:00:00'),
(12, 12, 'Amlodipine 10mg', 30, 2, '2025-08-14 16:00:00'),
(13, 13, 'Ambroxol Syrup 60ml', 1, 2, '2025-09-01 16:00:00'),
(14, 14, 'Losartan 50mg', 30, 2, '2025-04-21 16:00:00'),
(15, 15, 'Paracetamol 500mg', 15, 2, '2025-07-12 16:00:00'),
(16, 16, 'Losartan 50mg', 14, 2, '2025-08-04 16:00:00'),
(17, 17, 'Paracetamol 500mg', 10, 2, '2025-08-08 16:00:00'),
(18, 18, 'Amlodipine 5mg', 14, 2, '2025-08-12 16:00:00'),
(19, 19, 'Isosorbide dinitrate 10mg', 10, 2, '2025-08-16 16:00:00'),
(20, 20, 'Captopril 25mg', 14, 2, '2025-08-20 16:00:00'),
(21, 21, 'Paracetamol 500mg', 10, 2, '2025-08-24 16:00:00'),
(22, 22, 'Losartan 50mg', 14, 2, '2025-08-28 16:00:00'),
(23, 23, 'Amlodipine 5mg', 14, 2, '2025-08-31 16:00:00'),
(24, 24, 'Paracetamol 500mg', 10, 2, '2025-09-04 16:00:00'),
(25, 25, 'Captopril 25mg', 14, 2, '2025-09-08 16:00:00'),
(26, 26, 'Amoxicillin 500mg', 21, 2, '2025-03-22 16:00:00'),
(27, 27, 'Paracetamol 500mg', 10, 2, '2025-06-20 16:00:00'),
(28, 28, 'Losartan 50mg', 14, 2, '2025-09-05 16:00:00'),
(29, 29, 'Paracetamol 500mg', 10, 2, '2025-06-03 16:00:00'),
(30, 30, 'Ibuprofen 400mg', 7, 2, '2025-07-12 16:00:00'),
(31, 31, 'Amlodipine 5mg', 14, 2, '2025-01-16 16:00:00'),
(32, 32, 'Losartan 50mg', 14, 2, '2025-03-21 16:00:00'),
(33, 33, 'Paracetamol 500mg', 10, 2, '2025-05-04 16:00:00'),
(34, 34, 'Paracetamol 500mg', 10, 2, '2025-05-14 16:00:00'),
(35, 35, 'Amlodipine 5mg', 30, 2, '2025-05-08 16:00:00'),
(36, 36, 'Amoxicillin 500mg', 21, 2, '2025-05-10 16:00:00'),
(37, 37, 'Cefuroxime 500mg', 14, 2, '2025-05-14 16:00:00'),
(38, 38, 'Rifampicin 300mg', 30, 2, '2025-05-16 16:00:00'),
(39, 39, 'Ibuprofen 400mg', 10, 2, '2025-05-18 16:00:00'),
(40, 40, 'Paracetamol 500mg', 10, 2, '2025-05-20 16:00:00'),
(41, 41, 'Salbutamol Syrup 2mg/5ml', 1, 2, '2025-05-24 16:00:00'),
(42, 42, 'Losartan 50mg', 30, 2, '2025-05-26 16:00:00'),
(43, 43, 'Paracetamol 500mg', 10, 2, '2025-05-28 16:00:00'),
(44, 44, 'Paracetamol 500mg', 10, 2, '2025-06-01 16:00:00'),
(45, 45, 'Amoxicillin 500mg', 21, 2, '2025-07-31 16:00:00'),
(46, 46, 'Amlodipine 5mg', 30, 2, '2025-08-04 16:00:00'),
(47, 47, 'Paracetamol 500mg', 10, 2, '2025-08-08 16:00:00'),
(48, 48, 'Paracetamol 500mg', 10, 2, '2025-08-12 16:00:00'),
(49, 49, 'Amlodipine 5mg', 30, 2, '2025-08-16 16:00:00'),
(50, 50, 'Ibuprofen 400mg', 10, 2, '2025-08-20 16:00:00'),
(51, 51, 'Paracetamol 500mg', 10, 2, '2025-08-24 16:00:00'),
(52, 52, 'Losartan 50mg', 30, 2, '2025-08-28 16:00:00'),
(53, 53, 'Cefuroxime 500mg', 14, 2, '2025-08-31 16:00:00'),
(54, 54, 'Amlodipine 5mg', 30, 2, '2025-09-04 16:00:00'),
(55, 55, 'Amoxicillin 500mg', 21, 2, '2025-09-08 16:00:00'),
(56, 56, 'Salbutamol Syrup 2mg/5ml', 1, 2, '2025-03-22 16:00:00'),
(57, 57, 'Losartan 50mg', 30, 2, '2025-06-20 16:00:00'),
(58, 58, 'Ibuprofen 400mg', 10, 2, '2025-07-12 16:00:00'),
(59, 59, 'Paracetamol 500mg', 10, 2, '2025-06-03 16:00:00'),
(60, 60, 'Amlodipine 5mg', 30, 2, '2025-07-12 16:00:00'),
(61, 61, 'Losartan 50mg', 30, 2, '2025-05-04 16:00:00'),
(62, 62, 'Amoxicillin 500mg', 21, 2, '2025-05-14 16:00:00'),
(63, 63, 'Salbutamol Syrup 2mg/5ml', 1, 2, '2025-06-24 16:00:00'),
(64, 64, 'Amlodipine 5mg', 30, 2, '2025-04-17 16:00:00'),
(65, 65, 'Ibuprofen 400mg', 10, 2, '2025-07-21 16:00:00'),
(66, 66, 'Paracetamol 500mg', 10, 2, '2025-04-25 16:00:00'),
(67, 67, 'Losartan 50mg', 30, 2, '2025-04-27 16:00:00'),
(68, 68, 'Rifampicin 300mg', 30, 2, '2025-06-25 16:00:00'),
(69, 69, 'Paracetamol 500mg', 10, 2, '2025-02-07 16:00:00'),
(70, 70, 'Amlodipine 5mg', 30, 2, '2025-05-28 16:00:00'),
(71, 71, 'Amoxicillin 500mg', 21, 2, '2025-07-17 16:00:00'),
(72, 72, 'Cefuroxime 500mg', 14, 2, '2025-06-19 16:00:00'),
(73, 73, 'Losartan 50mg', 30, 2, '2025-07-08 16:00:00'),
(74, 74, 'Paracetamol 500mg', 10, 2, '2025-02-21 16:00:00'),
(75, 75, 'Amlodipine 5mg', 30, 2, '2025-01-01 16:00:00'),
(76, 76, 'Amoxicillin 500mg', 21, 2, '2025-03-08 16:00:00'),
(77, 77, 'Paracetamol 500mg', 4, 2, '2025-09-15 04:36:25'),
(78, 78, 'Paracetamol 500mg', 2, 2, '2025-09-15 04:43:15'),
(79, 80, 'Paracetamol 500mg', 3, 2, '2025-09-15 07:51:40'),
(80, 80, 'Cotrimoxazole 800mg', 3, 2, '2025-09-15 07:51:40');

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
  `barangay` enum('Barangay 1','Barangay 6','Barangay 7','Barangay 8','Barangay Gubat','Barangay San Isidro','Barangay Cobangbang','Barangay Bagasbas','Barangay Mambalite') NOT NULL,
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
(1, 'Francis Ramos', 'francis_ramos', '$2y$10$B1Hsn8bADZuvxV.46y0FdueWIdzFt5gPnfTR/AQ4In6m6qiEI.BfK', 'admin', 'approved', '', 'Daet CAMARINES NORTE', 26, '09632884241', 'active', '2025-09-10 07:57:54', '', NULL),
(2, 'Teresa Madrigal', 'teresa_madrigal', '$2y$10$MAA4VOKA/xe5TipaZIsL3.Rxa6mgpzZZVWn9IvgcJXVIJ.hiOBHI6', 'doctor', 'approved', '', 'Daet CAMARINES NORTE', 34, '09632884241', 'active', '2025-09-10 08:00:26', 'RHU II', NULL),
(3, 'Rianne De Mesa', 'rianne_demesa', '$2y$10$.a3gHp/DyH726Hp5n7If8enpOPQwzTT/F4c.ARH5EGp.eAOmRIKuS', 'nursing_attendant', 'approved', '', 'Daet CAMARINES NORTE', 28, '09632884241', 'active', '2025-09-10 08:03:42', '', NULL),
(4, 'Andre Bernardo', 'andre_bernardo', '$2y$10$YFXtBG3QnmWhOyQFI4wyzeQR5WZqmMZ1x9Rr9S2qfHSPYIe4nTtS6', 'nursing_attendant', 'approved', '', 'Daet CAMARINES NORTE', 30, '09632884241', 'active', '2025-09-10 08:06:22', '', NULL),
(5, 'Joanne Del Barrio', 'joanne_delbario', '$2y$10$mNMFbs32IewiWJF6VX2WguhdsI12UwD6ENDBMubpnQwiA82br/HZ.', 'bhw', 'approved', 'Barangay 1', 'Daet CAMARINES NORTE', 34, '09632884241', 'active', '2025-09-10 08:11:49', '', NULL),
(6, 'Maria Santos', 'maria_santos', '$2y$10$K88jkLpY9qChE25ptAXvPOooN2DdEGFdhRvjRuYEaL3/axy/r9Qfu', 'bhw', 'approved', 'Barangay 6', 'Daet CAMARINES NORTE', 40, '09632884241', 'active', '2025-09-10 08:12:50', '', NULL),
(7, 'Jose Reyes', 'jose_reyes', '$2y$10$OMhHdz6qFAqPFWFUey37E.oBi0i1ayrnyP3Ei3Zy90zLdJU.pT.kW', 'bhw', 'approved', 'Barangay 7', 'Daet CAMARINES NORTE', 29, '09632884241', 'active', '2025-09-10 08:15:22', '', NULL),
(8, 'Ana Mendoza', 'ana_mendoza', '$2y$10$CVhDp/Qeu0osKgvEf2.Yreq2rzNUJbRn2xM2GqPq/31PM4tWTorBe', 'bhw', 'approved', 'Barangay 8', 'Daet CAMARINES NORTE', 39, '09632884241', 'active', '2025-09-10 08:17:35', '', NULL),
(9, 'Carla Villanueva', 'carla_villanueva', '$2y$10$mivxx4KiI8QoBZBFUYM9iuKUrWugWM1tSSoyXD8FAPjyoAiWppvtG', 'bhw', 'approved', 'Barangay Gubat', 'Daet CAMARINES NORTE', 48, '09632884241', 'active', '2025-09-10 08:22:00', '', NULL),
(10, 'Lisa Bautista', 'lisa_bautista', '$2y$10$B0BVYMkS3lO9Sc2iWa5en.ELG9DRyK.U489B9v8MmgnPizotMPgcS', 'bhw', 'approved', 'Barangay San Isidro', 'Daet CAMARINES NORTE', 46, '09632884241', 'active', '2025-09-10 08:24:08', '', NULL),
(11, 'Miguel Narido', 'miguel_narido', '$2y$10$r3VYshKhWuFKat/CuGbznuzRdFhk86BYCVakaBFGXpy3oNUeStclC', 'bhw', 'approved', 'Barangay Cobangbang', 'Daet CAMARINES NORTE', 50, '09632884241', 'active', '2025-09-10 08:26:01', '', NULL),
(12, 'Kristine Aloc', 'kristine_aloc', '$2y$10$jWQ25TyxyR.ecDhbbCa5b.5sww1oc65HDEZH/sPVMYtLC42Vd7eES', 'bhw', 'approved', 'Barangay Bagasbas', 'Daet CAMARINES NORTE', 47, '09632884241', 'active', '2025-09-10 08:28:06', '', NULL),
(13, 'Roberto Flores', 'roberto_flores', '$2y$10$d1RW6zXIVxzE9Pb6GdiUtuYQ.9NJZLoDURCDyeYcImLZjA//JL8hO', 'bhw', 'approved', 'Barangay Mambalite', 'Daet CAMARINES NORTE', 49, '09632884241', 'active', '2025-09-10 08:30:02', '', NULL);

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
(1, 5, 'login', '2025-09-11 09:23:54'),
(2, 2, 'login', '2025-09-11 09:30:59'),
(3, 3, 'login', '2025-09-11 09:31:58'),
(4, 6, 'login', '2025-09-11 20:05:46'),
(5, 2, 'login', '2025-09-11 20:26:37'),
(6, 3, 'login', '2025-09-11 20:27:22'),
(7, 6, 'logout', '2025-09-11 21:43:27'),
(8, 6, 'login', '2025-09-11 21:51:37'),
(9, 6, 'login', '2025-09-12 12:53:35'),
(10, 3, 'login', '2025-09-12 12:56:53'),
(11, 5, 'login', '2025-09-15 11:26:11'),
(12, 3, 'login', '2025-09-15 11:27:44'),
(13, 2, 'login', '2025-09-15 12:34:43'),
(14, 12, 'login', '2025-09-15 15:12:36'),
(15, 5, 'login', '2025-09-17 10:24:25'),
(16, 2, 'login', '2025-09-17 10:26:53');

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
-- Indexes for table `patient_assessment`
--
ALTER TABLE `patient_assessment`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `bhw_id` (`recorded_by`);

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
  MODIFY `dispensed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `custom_options`
--
ALTER TABLE `custom_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `follow_ups`
--
ALTER TABLE `follow_ups`
  MODIFY `followup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `forgot_password_requests`
--
ALTER TABLE `forgot_password_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=446;

--
-- AUTO_INCREMENT for table `patient_assessment`
--
ALTER TABLE `patient_assessment`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=577;

--
-- AUTO_INCREMENT for table `patient_consents`
--
ALTER TABLE `patient_consents`
  MODIFY `consent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `rhu_consultations`
--
ALTER TABLE `rhu_consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `rhu_medicine_dispensed`
--
ALTER TABLE `rhu_medicine_dispensed`
  MODIFY `dispensed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
-- Constraints for table `patient_assessment`
--
ALTER TABLE `patient_assessment`
  ADD CONSTRAINT `patient_assessment_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_assessment_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `update_patient_ages` ON SCHEDULE EVERY 1 DAY STARTS '2025-09-15 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    UPDATE patients
    SET age = TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE());
END$$

CREATE DEFINER=`root`@`localhost` EVENT `mark_old_pending_referrals_uncompleted` ON SCHEDULE EVERY 1 DAY STARTS '2025-09-15 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    UPDATE referrals
    SET referral_status = 'Uncompleted'
    WHERE referral_status = 'Pending'
    AND referral_date < CURRENT_DATE;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
