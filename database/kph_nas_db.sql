-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 07:56 AM
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
-- Database: `kph_nas_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `admissions_id` int(11) NOT NULL COMMENT 'รหัสลำดับการเข้ารับการรักษาในระบบ',
  `admissions_an` varchar(20) NOT NULL COMMENT 'เลขที่ผู้ป่วยใน',
  `patient_id` int(11) NOT NULL COMMENT 'รหัสผู้ป่วย',
  `health_insurance_id` int(11) NOT NULL COMMENT 'สิทธิการรักษาพยาบาล',
  `admit_datetime` datetime NOT NULL COMMENT 'วันและเวลาที่รับผู้ป่วยเข้าพักรักษาตัว',
  `discharge_datetime` datetime DEFAULT NULL COMMENT 'วันและเวลาที่จำหน่ายผู้ป่วยออกจากโรงพยาบาล',
  `ward_id` int(11) NOT NULL COMMENT 'รหัสหอผู้ป่วยที่เข้ารักษา',
  `bed_number` varchar(10) DEFAULT NULL COMMENT 'หมายเลขเตียงของผู้ป่วย',
  `doctor_id` int(11) DEFAULT NULL COMMENT 'รหัสแพทย์เจ้าของไข้ผู้รับผิดชอบ',
  `status` varchar(50) NOT NULL DEFAULT 'Admitted' COMMENT 'สถานะผู้ป่วย (Admitted = กำลังรักษาตัว, Discharged = จำหน่ายออกแล้ว)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันและเวลาที่สร้าง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางเก็บข้อมูลการเข้ารักษาตัว (IPD Admissions)';

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`admissions_id`, `admissions_an`, `patient_id`, `health_insurance_id`, `admit_datetime`, `discharge_datetime`, `ward_id`, `bed_number`, `doctor_id`, `status`, `created_at`) VALUES
(1, '6701001', 1, 1, '2026-01-10 08:00:00', NULL, 1, 'A-01', 1, 'Admitted', '2026-01-21 14:48:14'),
(2, '6701002', 2, 7, '2026-01-10 09:30:00', NULL, 1, 'A-02', 3, 'Admitted', '2026-01-21 14:48:14'),
(3, '6701003', 3, 9, '2026-01-11 14:15:00', NULL, 1, 'A-03', 2, 'Admitted', '2026-01-21 14:48:14'),
(4, '6701004', 4, 2, '2026-01-12 10:20:00', NULL, 1, 'A-04', 4, 'Admitted', '2026-01-21 14:48:14'),
(5, '6701005', 5, 1, '2026-01-13 16:45:00', NULL, 1, 'A-05', 1, 'Admitted', '2026-01-21 14:48:14'),
(6, '6701006', 6, 4, '2026-01-14 07:10:00', NULL, 1, 'A-06', 1, 'Admitted', '2026-01-21 14:48:14'),
(7, '6701007', 7, 1, '2026-01-15 11:30:00', NULL, 1, 'A-07', 2, 'Admitted', '2026-01-21 14:48:14'),
(8, '6701008', 8, 5, '2026-01-16 20:00:00', NULL, 1, 'A-08', 3, 'Admitted', '2026-01-21 14:48:14'),
(9, '6701009', 9, 1, '2026-01-17 13:20:00', NULL, 1, 'A-09', 1, 'Admitted', '2026-01-21 14:48:14'),
(10, '6701010', 10, 3, '2026-01-18 06:50:00', NULL, 1, 'A-10', 3, 'Admitted', '2026-01-21 14:48:14'),
(11, '6702001', 11, 4, '2026-01-09 15:00:00', NULL, 2, 'B-01', 1, 'Admitted', '2026-01-21 14:48:14'),
(12, '6702002', 12, 1, '2026-01-11 08:30:00', NULL, 2, 'B-02', 3, 'Admitted', '2026-01-21 14:48:14'),
(13, '6702003', 13, 7, '2026-01-12 11:00:00', NULL, 2, 'B-03', 2, 'Admitted', '2026-01-21 14:48:14'),
(14, '6702004', 14, 1, '2026-01-13 19:15:00', NULL, 2, 'B-04', 3, 'Admitted', '2026-01-21 14:48:14'),
(15, '6702005', 15, 2, '2026-01-14 09:40:00', NULL, 2, 'B-05', 4, 'Admitted', '2026-01-21 14:48:14'),
(16, '6702006', 16, 1, '2026-01-15 14:20:00', NULL, 2, 'B-06', 1, 'Admitted', '2026-01-21 14:48:14'),
(17, '6702007', 17, 9, '2026-01-16 10:50:00', NULL, 2, 'B-07', 1, 'Admitted', '2026-01-21 14:48:14'),
(18, '6702008', 18, 1, '2026-01-17 18:00:00', NULL, 2, 'B-08', 1, 'Admitted', '2026-01-21 14:48:14'),
(19, '6702009', 19, 7, '2026-01-18 08:15:00', NULL, 2, 'B-09', 2, 'Admitted', '2026-01-21 14:48:14'),
(20, '6702010', 20, 3, '2026-01-19 13:30:00', NULL, 2, 'B-10', 4, 'Admitted', '2026-01-21 14:48:14'),
(21, '6703001', 21, 1, '2026-01-08 09:00:00', NULL, 3, 'C-01', 1, 'Admitted', '2026-01-21 14:48:14'),
(22, '6703002', 22, 7, '2026-01-10 16:20:00', NULL, 3, 'C-02', 4, 'Admitted', '2026-01-21 14:48:14'),
(23, '6703003', 23, 1, '2026-01-11 12:45:00', NULL, 3, 'C-03', 1, 'Admitted', '2026-01-21 14:48:14'),
(24, '6703004', 24, 2, '2026-01-13 08:30:00', NULL, 3, 'C-04', 1, 'Admitted', '2026-01-21 14:48:14'),
(25, '6703005', 25, 1, '2026-01-14 20:10:00', NULL, 3, 'C-05', 2, 'Admitted', '2026-01-21 14:48:14'),
(26, '6703006', 26, 4, '2026-01-15 11:00:00', NULL, 3, 'C-06', 1, 'Admitted', '2026-01-21 14:48:14'),
(27, '6703007', 27, 1, '2026-01-16 15:15:00', NULL, 3, 'C-07', 1, 'Admitted', '2026-01-21 14:48:14'),
(28, '6703008', 28, 5, '2026-01-17 09:40:00', NULL, 3, 'C-08', 1, 'Admitted', '2026-01-21 14:48:14'),
(29, '6703009', 29, 9, '2026-01-18 17:50:00', NULL, 3, 'C-09', 4, 'Admitted', '2026-01-21 14:48:14'),
(30, '6703010', 30, 1, '2026-01-19 13:00:00', NULL, 3, 'C-10', 3, 'Admitted', '2026-01-21 14:48:14'),
(31, '6704001', 31, 1, '2026-01-09 10:15:00', NULL, 4, 'D-01', 3, 'Admitted', '2026-01-21 14:48:14'),
(32, '6704002', 32, 3, '2026-01-11 14:30:00', NULL, 4, 'D-02', 1, 'Admitted', '2026-01-21 14:48:14'),
(33, '6704003', 33, 1, '2026-01-12 08:45:00', NULL, 4, 'D-03', 1, 'Admitted', '2026-01-21 14:48:14'),
(34, '6704004', 34, 7, '2026-01-14 19:00:00', NULL, 4, 'D-04', 1, 'Admitted', '2026-01-21 14:48:14'),
(35, '6704005', 35, 1, '2026-01-15 12:20:00', NULL, 4, 'D-05', 1, 'Admitted', '2026-01-21 14:48:14'),
(36, '6704006', 36, 9, '2026-01-16 16:10:00', NULL, 4, 'D-06', 2, 'Admitted', '2026-01-21 14:48:14'),
(37, '6704007', 37, 1, '2026-01-17 07:30:00', NULL, 4, 'D-07', 4, 'Admitted', '2026-01-21 14:48:14'),
(38, '6704008', 38, 4, '2026-01-18 15:45:00', NULL, 4, 'D-08', 4, 'Admitted', '2026-01-21 14:48:14'),
(39, '6704009', 39, 1, '2026-01-19 11:00:00', NULL, 4, 'D-09', 4, 'Admitted', '2026-01-21 14:48:14'),
(40, '6704010', 40, 7, '2026-01-20 09:25:00', NULL, 4, 'D-10', 3, 'Admitted', '2026-01-21 14:48:14'),
(41, '6706001', 41, 1, '2026-01-10 08:20:00', NULL, 6, 'E-01', 5, 'Admitted', '2026-01-21 14:48:14'),
(42, '6706002', 42, 7, '2026-01-11 13:40:00', NULL, 6, 'E-02', 5, 'Admitted', '2026-01-21 14:48:14'),
(43, '6706003', 43, 1, '2026-01-12 17:15:00', NULL, 6, 'E-03', 5, 'Admitted', '2026-01-21 14:48:14'),
(44, '6706004', 44, 2, '2026-01-13 11:00:00', NULL, 6, 'E-04', 5, 'Admitted', '2026-01-21 14:48:14'),
(45, '6706005', 45, 5, '2026-01-14 09:30:00', NULL, 6, 'E-05', 5, 'Admitted', '2026-01-21 14:48:14'),
(46, '6706006', 46, 1, '2026-01-15 20:00:00', NULL, 6, 'E-06', 6, 'Admitted', '2026-01-21 14:48:14'),
(47, '6706007', 47, 9, '2026-01-16 15:50:00', NULL, 6, 'E-07', 5, 'Admitted', '2026-01-21 14:48:14'),
(48, '6706008', 48, 1, '2026-01-17 12:10:00', NULL, 6, 'E-08', 6, 'Admitted', '2026-01-21 14:48:14'),
(49, '6706009', 49, 3, '2026-01-18 08:00:00', NULL, 6, 'E-09', 5, 'Admitted', '2026-01-21 14:48:14'),
(50, '6706010', 50, 4, '2026-01-19 14:25:00', NULL, 6, 'E-10', 5, 'Admitted', '2026-01-21 14:48:14'),
(51, '6707001', 51, 4, '2026-01-09 09:50:00', NULL, 7, 'F-01', 5, 'Admitted', '2026-01-21 14:48:14'),
(52, '6707002', 52, 1, '2026-01-11 16:30:00', NULL, 7, 'F-02', 5, 'Admitted', '2026-01-21 14:48:14'),
(53, '6707003', 53, 7, '2026-01-12 10:45:00', NULL, 7, 'F-03', 5, 'Admitted', '2026-01-21 14:48:14'),
(54, '6707004', 54, 1, '2026-01-13 13:20:00', NULL, 7, 'F-04', 5, 'Admitted', '2026-01-21 14:48:14'),
(55, '6707005', 55, 9, '2026-01-15 08:15:00', NULL, 7, 'F-05', 5, 'Admitted', '2026-01-21 14:48:14'),
(56, '6707006', 56, 1, '2026-01-16 11:55:00', NULL, 7, 'F-06', 5, 'Admitted', '2026-01-21 14:48:14'),
(57, '6707007', 57, 2, '2026-01-17 19:40:00', NULL, 7, 'F-07', 5, 'Admitted', '2026-01-21 14:48:14'),
(58, '6707008', 58, 1, '2026-01-18 14:10:00', NULL, 7, 'F-08', 5, 'Admitted', '2026-01-21 14:48:14'),
(59, '6707009', 59, 7, '2026-01-19 10:00:00', NULL, 7, 'F-09', 5, 'Admitted', '2026-01-21 14:48:14'),
(60, '6707010', 60, 5, '2026-01-20 07:30:00', NULL, 7, 'F-10', 5, 'Admitted', '2026-01-21 14:48:14'),
(61, '6708001', 61, 1, '2026-01-08 11:00:00', NULL, 8, 'G-01', 8, 'Admitted', '2026-01-21 14:48:14'),
(62, '6708002', 62, 3, '2026-01-10 15:30:00', NULL, 8, 'G-02', 9, 'Admitted', '2026-01-21 14:48:14'),
(63, '6708003', 63, 1, '2026-01-12 09:15:00', NULL, 8, 'G-03', 9, 'Admitted', '2026-01-21 14:48:14'),
(64, '6708004', 64, 7, '2026-01-13 18:00:00', NULL, 8, 'G-04', 8, 'Admitted', '2026-01-21 14:48:14'),
(65, '6708005', 65, 1, '2026-01-14 12:25:00', NULL, 8, 'G-05', 8, 'Admitted', '2026-01-21 14:48:14'),
(66, '6708006', 66, 12, '2026-01-16 08:40:00', NULL, 8, 'G-06', 8, 'Admitted', '2026-01-21 14:48:14'),
(67, '6708007', 67, 1, '2026-01-17 16:50:00', NULL, 8, 'G-07', 8, 'Admitted', '2026-01-21 14:48:14'),
(68, '6708008', 68, 4, '2026-01-18 10:35:00', NULL, 8, 'G-08', 8, 'Admitted', '2026-01-21 14:48:14'),
(69, '6708009', 69, 12, '2026-01-19 19:20:00', NULL, 8, 'G-09', 8, 'Admitted', '2026-01-21 14:48:14'),
(70, '6708010', 70, 9, '2026-01-20 13:10:00', NULL, 8, 'G-10', 9, 'Admitted', '2026-01-21 14:48:14');

-- --------------------------------------------------------

--
-- Table structure for table `disease`
--

CREATE TABLE `disease` (
  `disease_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงของโรค',
  `disease_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ชื่อโรคหรือภาวะเจ็บป่วยทางการแพทย์',
  `disease_type` enum('โรคที่มีความรุนแรงน้อยถึงปานกลาง','โรคที่มีความรุนแรงมาก') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'กลุ่มระดับความรุนแรงของโรค',
  `disease_score` int(11) NOT NULL COMMENT 'คะแนนความเสี่ยงทางโภชนาการที่ได้จากโรคนี้',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้เลือกใช้งาน (1 = เปิดใช้งาน, 0 = ปิดการใช้งาน/ซ่อนข้อมูล)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `disease`
--

INSERT INTO `disease` (`disease_id`, `disease_name`, `disease_type`, `disease_score`, `is_active`) VALUES
(1, 'DM (เบาหวาน)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(2, 'CKD-ESRD (ไตเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(3, 'Septicemia (ติดเชื้อในกระแสเลือด)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(4, 'Solid cancer (มะเร็งทั่วไป)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(5, 'Chronic heart failure (หัวใจล้มเหลวเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(6, 'Hip fracture (ข้อสะโพกหัก)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(7, 'COPD (ปอดอุดกั้นเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(8, 'Severe head injury (บาดเจ็บที่ศีรษะรุนแรง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(9, '>= 2 of burn (แผลไฟไหม้ระดับ 2 ขึ้นไป)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(10, 'CLD/Cirrhosis/Hepati cencaph (ตับเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3, 1),
(11, 'Severe pneumonia (ปอดบวมขั้นรุนแรง)', 'โรคที่มีความรุนแรงมาก', 6, 1),
(12, 'Critically ill (ผู้ป่วยวิกฤต)', 'โรคที่มีความรุนแรงมาก', 6, 1),
(13, 'Multiple fracture (กระดูกหักหลายตำแหน่ง)', 'โรคที่มีความรุนแรงมาก', 6, 1),
(14, 'Stroke/CVA (อัมพาต)', 'โรคที่มีความรุนแรงมาก', 6, 1),
(15, 'Malignant hematologic disease/Bone marrow transplant (มะเร็งเม็ดเลือด/ปลูกถ่ายไขกระดูก)', 'โรคที่มีความรุนแรงมาก', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `disease_saved`
--

CREATE TABLE `disease_saved` (
  `disease_saved_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงข้อมูลโรคที่ผู้ป่วยเป็น',
  `nutrition_assessment_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงใบประเมินภาวะโภชนาการ',
  `disease_id` int(11) DEFAULT NULL COMMENT 'รหัสโรคที่เลือกจากระบบ',
  `disease_other_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อโรคหรืออาการอื่นๆ',
  `disease_type` varchar(50) DEFAULT NULL COMMENT 'กลุ่มระดับความรุนแรงของโรคที่บันทึก',
  `disease_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเสี่ยงทางโภชนาการที่ได้จากโรคนี้'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางบันทึกโรคที่ผู้ป่วยเป็น (1:Many)';

--
-- Dumping data for table `disease_saved`
--

INSERT INTO `disease_saved` (`disease_saved_id`, `nutrition_assessment_id`, `disease_id`, `disease_other_name`, `disease_type`, `disease_score`) VALUES
(1, 1, 1, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(2, 1, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(3, 2, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(4, 2, 3, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(5, 2, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(6, 3, 4, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(7, 6, 1, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(8, 7, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(9, 8, 5, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(10, 8, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(11, 9, 3, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(12, 9, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(13, 10, 13, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(14, 11, 4, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(15, 12, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(16, 12, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(17, 13, 1, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(18, 14, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(19, 14, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(20, 15, 1, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(21, 15, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(22, 17, 7, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(23, 18, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(24, 19, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(25, 20, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(26, 21, 10, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(27, 23, NULL, 'ไทรอยด์เป็นพิษขั้นรุนแรง', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(28, 29, 1, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(29, 29, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(30, 29, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(31, 33, NULL, 'Leptospirosis (โรคฉี่หนู)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(32, 34, NULL, 'เลือดออกทางเดินอาหาร', 'โรคที่มีความรุนแรงมาก', 6),
(33, 36, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6),
(34, 37, 12, NULL, 'โรคที่มีความรุนแรงมาก', 6);

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctor_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงข้อมูลแพทย์',
  `doctor_name` varchar(100) NOT NULL COMMENT 'ชื่อ-นามสกุลของแพทย์',
  `doctor_specialty` varchar(100) DEFAULT NULL COMMENT 'ความเชี่ยวชาญเฉพาะทางหรือแผนกที่สังกัด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doctor_id`, `doctor_name`, `doctor_specialty`) VALUES
(1, 'นพ. สมศักดิ์ รักษาดี', 'อายุรกรรมทั่วไป (General Medicine)'),
(2, 'พญ. วรารัตน์ ใจมั่น', 'อายุรกรรมโรคหัวใจ (Cardiology)'),
(3, 'นพ. ปิติ พึ่งพาได้', 'อายุรกรรมโรคไต (Nephrology)'),
(4, 'พญ. สุภาวดี มีเมตตา', 'อายุรกรรมระบบประสาท (Neurology)'),
(5, 'นพ. เกรียงไกร มือหนึ่ง', 'ศัลยกรรมทั่วไป (General Surgery)'),
(6, 'นพ. ชัยชนะ ผ่าตัดเก่ง', 'ศัลยกรรมอุบัติเหตุ (Trauma)'),
(7, 'นพ. วิศิษฐ์ ประสาทศัลย์', 'ศัลยกรรมระบบประสาท (Neurosurgery)'),
(8, 'นพ. อธิป กระดูกเหล็ก', 'ศัลยกรรมกระดูกและข้อ'),
(9, 'นพ. ธีระ ข้อเข่าดี', 'ศัลยกรรมกระดูกและข้อ (Sport Medicine)'),
(10, 'พญ. อริสรา รักเด็ก', 'กุมารเวชกรรมทั่วไป'),
(11, 'พญ. นันทิดา ดูแลบุตร', 'กุมารเวชกรรมทารกแรกเกิด (Neonatal)'),
(12, 'พญ. กานดา มารดาประชารักษ์', 'สูติ-นรีเวชกรรม'),
(13, 'นพ. สุรชัย ทำคลอดปลอดภัย', 'สูติ-นรีเวชกรรม'),
(14, 'พญ. เนตรนภา ตาใส', 'จักษุวิทยา (Ophthalmology)'),
(15, 'นพ. ก้องเกียรติ ฟังชัด', 'โสต ศอ นาสิก (หู คอ จมูก)'),
(16, 'นพ. กล้าหาญ ชาญชัย', 'เวชศาสตร์ฉุกเฉิน (Emergency Medicine)'),
(17, 'พญ. จิตตรา สบายใจ', 'จิตเวชศาสตร์');

-- --------------------------------------------------------

--
-- Table structure for table `food_access`
--

CREATE TABLE `food_access` (
  `food_access_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงความสามารถในการเข้าถึงอาหาร',
  `food_access_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ระดับความสามารถหรือข้อจำกัดในการเข้าถึง/รับประทานอาหารของผู้ป่วย',
  `food_access_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเสี่ยงทางโภชนาการที่ประเมินได้จากระดับการเข้าถึงอาหารนี้',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `food_access`
--

INSERT INTO `food_access` (`food_access_id`, `food_access_label`, `food_access_score`, `is_active`) VALUES
(1, 'นอนติดเตียง', 2, 1),
(2, 'ต้องมีผู้ช่วยบ้าง', 1, 1),
(3, 'นั่งๆ นอนๆ', 0, 1),
(4, 'ปกติ', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `food_amount`
--

CREATE TABLE `food_amount` (
  `food_amount_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงปริมาณอาหาร',
  `food_amount_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ระดับปริมาณอาหารที่ผู้ป่วยรับประทานได้เมื่อเทียบกับช่วงปกติ',
  `food_amount_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเสี่ยงทางโภชนาการที่ประเมินได้จากปริมาณอาหารที่รับประทาน',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `food_amount`
--

INSERT INTO `food_amount` (`food_amount_id`, `food_amount_label`, `food_amount_score`, `is_active`) VALUES
(1, 'กินน้อยมาก', 2, 1),
(2, 'กินน้อยลง', 1, 1),
(3, 'กินมากขึ้น', 0, 1),
(4, 'กินเท่าปกติ', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `food_type`
--

CREATE TABLE `food_type` (
  `food_type_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงลักษณะประเภทอาหาร',
  `food_type_label` varchar(255) DEFAULT NULL COMMENT 'ลักษณะอาหารที่ผู้ป่วยรับประทานได้ในช่วงที่ผ่านมา',
  `food_type_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเสี่ยงทางโภชนาการที่ประเมินได้จากลักษณะอาหารประเภทนี้',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_type`
--

INSERT INTO `food_type` (`food_type_id`, `food_type_label`, `food_type_score`, `is_active`) VALUES
(1, 'อาหารน้ำๆ', 2, 1),
(2, 'อาหารเหลวๆ', 2, 1),
(3, 'อาหารนุ่มกว่าปกติ', 1, 1),
(4, 'อาหารเหมือนปกติ', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `health_insurance`
--

CREATE TABLE `health_insurance` (
  `health_insurance_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงสิทธิการรักษาพยาบาล',
  `health_insurance_name` varchar(100) NOT NULL COMMENT 'ชื่อสิทธิการรักษาพยาบาลของผู้ป่วย'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_insurance`
--

INSERT INTO `health_insurance` (`health_insurance_id`, `health_insurance_name`) VALUES
(1, 'บัตรทอง/UC'),
(2, 'สิทธิ์ผู้พิการ'),
(3, 'สิทธิ์ผู้สูงอายุ'),
(4, 'สิทธิข้าราชการ (จ่ายตรงกรมบัญชีกลาง)'),
(5, 'สิทธิข้าราชการ (องค์กรปกครองส่วนท้องถิ่น - อปท.)'),
(6, 'สิทธิรัฐวิสาหกิจ'),
(7, 'สิทธิประกันสังคม (โรงพยาบาลตามสิทธิ)'),
(8, 'สิทธิประกันสังคม (ส่งต่อ/ฉุกเฉิน)'),
(9, 'ชำระเงินเอง'),
(10, 'สิทธิประกันสุขภาพแรงงานต่างด้าว (MOU)'),
(11, 'สิทธิบุคคลที่มีปัญหาสถานะและสิทธิ (บัตรเลข 0)'),
(12, 'สิทธิ พ.ร.บ. คุ้มครองผู้ประสบภัยจากรถ');

-- --------------------------------------------------------

--
-- Table structure for table `nutritionist`
--

CREATE TABLE `nutritionist` (
  `nutritionist_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงข้อมูลนักโภชนาการ',
  `nutritionist_code` varchar(50) DEFAULT NULL COMMENT 'เลขที่ใบประกอบวิชาชีพ',
  `nutritionist_fullname` varchar(255) NOT NULL COMMENT 'ชื่อ-นามสกุลของนักโภชนาการ',
  `nutritionist_gender` enum('ชาย','หญิง') NOT NULL DEFAULT 'ชาย' COMMENT 'เพศของนักโภชนาการ',
  `nutritionist_position` varchar(100) DEFAULT 'นักโภชนาการ' COMMENT 'ตำแหน่งงาน',
  `nutritionist_username` varchar(100) NOT NULL COMMENT 'ชื่อผู้ใช้งาน',
  `nutritionist_password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน',
  `nutritionist_email` varchar(100) DEFAULT NULL COMMENT 'อีเมล',
  `nutritionist_phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะบัญชีผู้ใช้งาน (1 = เปิดใช้งาน/ล็อกอินได้, 0 = ระงับบัญชี/ล็อกอินไม่ได้)',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'สิทธิ์ผู้ดูแลระบบ (1 = แอดมินจัดการระบบได้, 0 = ผู้ใช้งาน/นักโภชนาการทั่วไป)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันและเวลาที่สร้างบัญชีผู้ใช้งานนี้ในระบบ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutritionist`
--

INSERT INTO `nutritionist` (`nutritionist_id`, `nutritionist_code`, `nutritionist_fullname`, `nutritionist_gender`, `nutritionist_position`, `nutritionist_username`, `nutritionist_password`, `nutritionist_email`, `nutritionist_phone`, `is_active`, `is_admin`, `created_at`) VALUES
(1, 'DT-66099', 'เพชรลดา เชยเพ็ชร', 'หญิง', 'นักโภชนาการ', 'phetrada', 'phetrada2616', 'phetrada.2646@gmail.com', '0970579246', 1, 0, '2026-01-27 03:50:53'),
(2, 'DT-66088', 'นักโภชนาการ คนที่ 2', 'ชาย', 'นักโภชนาการ', 'pheythay', 'pheythay0026', 'pheythay@gmail.com', '095222345', 1, 0, '2026-01-28 02:33:23'),
(4, 'DT-66066', 'นักโภชนาการ คนที่ 3', 'ชาย', 'นักโภชนาการ', 'porpor', 'porpor2626', 'porpor26@gmail.com', '098456879', 1, 0, '2026-01-28 02:33:54'),
(5, 'DT-66077', 'แอดมิน ทดสอบระบบ', 'ชาย', 'นักโภชนาการ', 'admin', 'admin2616', 'admin@gmail.com', '088777555', 1, 1, '2026-02-04 06:53:53');

-- --------------------------------------------------------

--
-- Table structure for table `nutritionist_signature`
--

CREATE TABLE `nutritionist_signature` (
  `nutritionist_signature_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงข้อมูลลายเซ็น',
  `nutritionist_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงนักโภชนาการเจ้าของลายเซ็น',
  `nutritionist_signature_type` enum('canvas','upload') NOT NULL DEFAULT 'canvas' COMMENT 'รูปแบบการได้มาของลายเซ็น (canvas = วาดเซ็นสดบนหน้าเว็บ, upload = อัปโหลดไฟล์รูปภาพ)',
  `nutritionist_signature_data` longtext NOT NULL COMMENT 'ข้อมูลไฟล์รูปภาพลายเซ็นในรูปแบบสตริง Base64',
  `nutritionist_signature_datetime` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'วันและเวลาที่ทำการบันทึกหรืออัปเดตลายเซ็นนี้ล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `nutritionist_signature`
--

INSERT INTO `nutritionist_signature` (`nutritionist_signature_id`, `nutritionist_id`, `nutritionist_signature_type`, `nutritionist_signature_data`, `nutritionist_signature_datetime`) VALUES
(1, 1, 'canvas', 'iVBORw0KGgoAAAANSUhEUgAAAfQAAAC0CAYAAABi+d5SAAAQAElEQVR4AeydaawtTVWGjzgzyCQCAoYIGFAhGCQg/EGMohAwnz+UKAoJIioEZ9BIZFAiSgKiJoKSYIhB8AeCogacEpWoiCIgyEwIMsj0gSg4az2Hs+73nr7de/dQVd3V+73pulVdXbVqraeGVd279z7XO/M/EzABEzABEzCB5gnYoTffhTbABEzABEzABM7Oyjp0EzYBEzABEzABE6hCwA69CmY3YgImYAImYAJlCbTs0MuSsXQTMAETMAETaIiAHXpDnWVVTcAETMAETGCIgB36EBnnm4AJmIAJmEBDBOzQG+osq2oCJmACJmACQwTs0IfIlM23dBMwARMwARPISsAOPStOCzMBEzABEzCBdQjYoa/DvWyrlm4CJmACJnByBOzQT67LbbAJmIAJmMAeCdih77FXy9pk6SZgAiZgAhskYIe+wU6xSiZgAiZgAiYwlYAd+lRiLl+WgKWbgAmYgAnMImCHPgubK5mACZiACZjAtgjYoW+rP6xNWQKWbgImYAK7JWCHvtuutWEmYAImYAKnRMAO/ZR627aWJWDpJmACJrAiATv0FeG7aRMwARMwARPIRcAOPRdJyzGBsgQs3QRMwAQOErBDP4jHF03ABEzABEygDQJ26G30k7U0gbIELN0ETKB5AnbozXehDTABEzABEzCBszM7dI8CEzCB0gQs3wRMoAIBO/QKkN2ECZiACZiACZQmYIdemrDlm4AJlCVg6SZgAucE7NDPMfg/EzABEzABE2ibgB162/1n7U3ABMoSsHQTaIaAHXozXWVFTcAETMAETGCYgB36MBtfMQETMIGyBCzdBDISsEPPCNOiTMAETMAETGAtAnboa5F3uyZgAiZQloClnxgBO/QT63CbawImYAImsE8Cduj77FdbZQImYAJlCVj65gjYoW+uS6yQCZiACZiACUwnYIc+nZlrmIAJmIAJlCVg6TMI2KHPgOYqJmACJmACJrA1AnboW+sR62MCJmACJlCWwE6l26HvtGNtlgmYgAmYwGkRsEM/rf62tSZgAiZgAmUJrCbdDn019G7YBEzABEzABPIRsEPPx9KSTMAETMAETKAsgQPS7dAPwPElEzABEzABE2iFgB16Kz1lPU3ABEzABEzgAIEMDv2AdF8yARMwARMwAROo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', '2026-02-16 13:41:51'),
(2, 2, 'canvas', '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', '2026-02-16 13:56:12');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_assessment`
--

CREATE TABLE `nutrition_assessment` (
  `nutrition_assessment_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงแบบประเมินภาวะโภชนาการ',
  `doc_no` varchar(20) NOT NULL COMMENT 'เลขที่เอกสารใบประเมิน',
  `naf_seq` int(11) DEFAULT 1 COMMENT 'ลำดับครั้งที่ประเมินของผู้ป่วยในรอบการรักษานี้',
  `admissions_an` varchar(20) NOT NULL COMMENT 'รหัส AN',
  `patient_hn` varchar(20) NOT NULL COMMENT 'รหัส HN',
  `nutritionist_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ทำการประเมิน',
  `nutrition_assessment_datetime` datetime NOT NULL COMMENT 'วันและเวลาที่บันทึกทำการประเมินภาวะโภชนาการ',
  `initial_diagnosis` text DEFAULT NULL COMMENT 'ข้อมูลการวินิจฉัยโรคเบื้องต้น',
  `info_source` varchar(50) DEFAULT NULL COMMENT 'แหล่งที่มาของการให้ข้อมูล',
  `other_source` varchar(100) DEFAULT NULL COMMENT 'ระบุแหล่งที่มาเพิ่มเติม',
  `height_measure` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูงที่วัดได้จริงจากการยืน (เซนติเมตร)',
  `body_length` decimal(5,2) DEFAULT NULL COMMENT 'ความยาวลำตัว',
  `arm_span` decimal(5,2) DEFAULT NULL COMMENT 'ความยาวช่วงแขนกาง',
  `height_relative` decimal(5,2) DEFAULT NULL COMMENT 'ญาติบอก',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนักตัวผู้ป่วย (กิโลกรัม)',
  `bmi` decimal(5,2) DEFAULT NULL COMMENT 'ค่าดัชนีมวลกาย',
  `bmi_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเสี่ยงที่แปรผลมาจากค่า BMI',
  `is_no_weight` tinyint(1) DEFAULT 0 COMMENT 'สถานะการชั่งน้ำหนัก (1 = ชั่งน้ำหนักไม่ได้ต้องใช้ผล Lab ประเมินแทน, 0 = ชั่งน้ำหนักได้ปกติ)',
  `lab_method` varchar(50) DEFAULT NULL COMMENT 'วิธีตรวจทางห้องปฏิบัติการที่ใช้ประเมินแทนน้ำหนัก (Albumin หรือ TLC)',
  `albumin_val` decimal(4,2) DEFAULT NULL COMMENT 'ค่าผลตรวจ Albumin (g/dL)',
  `tlc_val` decimal(10,2) DEFAULT NULL COMMENT 'ค่าผลตรวจ TLC (Total Lymphocyte Count)',
  `lab_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเสี่ยงที่แปรผลมาจากค่า Lab',
  `weight_option_id` int(11) DEFAULT NULL COMMENT 'วิธีการได้มาของน้ำหนักตัว',
  `patient_shape_id` int(11) DEFAULT NULL COMMENT 'ลักษณะรูปร่างของผู้ป่วย',
  `weight_change_4_weeks_id` int(11) DEFAULT NULL COMMENT 'การเปลี่ยนแปลงน้ำหนักใน 4 สัปดาห์',
  `food_type_id` int(11) DEFAULT NULL COMMENT 'ลักษณะอาหารที่ผู้ป่วยรับประทานได้',
  `food_amount_id` int(11) DEFAULT NULL COMMENT 'ปริมาณอาหารที่ผู้ป่วยรับประทานได้',
  `food_access_id` int(11) DEFAULT NULL COMMENT 'ความสามารถหรือข้อจำกัดในการเข้าถึงอาหาร',
  `total_score` int(11) DEFAULT NULL COMMENT 'คะแนนรวมความเสี่ยงทางโภชนาการทั้งหมดที่คำนวณได้',
  `naf_level` varchar(50) DEFAULT NULL COMMENT 'ผลลัพธ์ระดับความเสี่ยงทางโภชนาการ (เช่น NAF A, NAF B, NAF C)',
  `ref_screening_doc_no` varchar(20) DEFAULT NULL COMMENT 'เลขที่เอกสารแบบคัดกรองที่ใช้เป็นข้อมูลอ้างอิงเบื้องต้น',
  `nutrition_screening_id` int(11) DEFAULT NULL COMMENT 'รหัสอ้างอิงใบแบบคัดกรองโภชนาการที่เกี่ยวข้อง',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันและเวลาที่บันทึกลงฐานข้อมูล'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ID อ้างอิงตาราง nutrition_screening (FK)';

--
-- Dumping data for table `nutrition_assessment`
--

INSERT INTO `nutrition_assessment` (`nutrition_assessment_id`, `doc_no`, `naf_seq`, `admissions_an`, `patient_hn`, `nutritionist_id`, `nutrition_assessment_datetime`, `initial_diagnosis`, `info_source`, `other_source`, `height_measure`, `body_length`, `arm_span`, `height_relative`, `weight`, `bmi`, `bmi_score`, `is_no_weight`, `lab_method`, `albumin_val`, `tlc_val`, `lab_score`, `weight_option_id`, `patient_shape_id`, `weight_change_4_weeks_id`, `food_type_id`, `food_amount_id`, `food_access_id`, `total_score`, `naf_level`, `ref_screening_doc_no`, `nutrition_screening_id`, `created_at`) VALUES
(1, 'NAF-6710001-001', 1, '6701001', '6710001', 1, '2026-01-22 08:39:08', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 165.00, NULL, NULL, NULL, 45.00, 16.53, 2, 0, NULL, NULL, NULL, 0, 1, 2, 1, 3, 2, 2, 19, 'NAF C', 'SPENT-6710001-001', 1, '2026-01-22 08:39:08'),
(2, 'NAF-6780002-001', 1, '6708002', '6780002', 2, '2026-01-22 13:05:02', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 178.00, NULL, NULL, NULL, 50.00, 15.78, 2, 0, NULL, NULL, NULL, 0, 2, 2, 1, 3, 1, 1, 26, 'NAF C', 'SPENT-6780002-001', NULL, '2026-01-22 13:05:02'),
(3, 'NAF-6710003-001', 1, '6701003', '6710003', 4, '2026-01-22 15:12:08', 'มะเร็งลำไส้ใหญ่', 'ผู้ป่วย', NULL, 167.00, NULL, NULL, NULL, 45.00, 16.14, 0, 0, NULL, NULL, NULL, 0, 1, 1, 1, 1, 2, 2, 16, 'NAF C', 'SPENT-6710003-001', NULL, '2026-01-22 15:12:08'),
(4, 'NAF-6710004-001', 1, '6701004', '6710004', 2, '2026-01-22 15:13:27', 'ความดันโลหิตสูง', 'ผู้ป่วย', NULL, 168.00, NULL, NULL, NULL, 50.00, 17.72, 0, 0, NULL, NULL, NULL, 0, 2, 2, 1, 4, 2, 3, 6, 'NAF B', 'SPENT-6710004-001', NULL, '2026-01-22 15:13:27'),
(5, 'NAF-6760001-001', 1, '6706001', '6760001', 1, '2026-01-22 15:14:43', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 158.00, NULL, NULL, NULL, 45.00, 18.03, 0, 0, NULL, NULL, NULL, 0, 2, 2, 1, 4, 4, 4, 5, 'NAF A', 'SPENT-6760001-001', NULL, '2026-01-22 15:14:43'),
(6, 'NAF-6770001-001', 1, '6707001', '6770001', 1, '2026-01-28 09:01:29', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 168.00, NULL, NULL, NULL, 45.00, 15.94, 0, 0, NULL, NULL, NULL, 0, 2, 2, 1, 1, 2, 1, 17, 'NAF C', 'SPENT-6770001-001', NULL, '2026-01-28 09:01:29'),
(7, 'NAF-6710005-001', 1, '6701005', '6710005', 2, '2026-01-28 10:04:34', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 170.00, NULL, NULL, NULL, 40.00, 13.84, 0, 0, NULL, NULL, NULL, 0, 2, 1, 2, 1, 2, 1, 15, 'NAF C', 'SPENT-6710005-001', NULL, '2026-01-28 10:04:34'),
(8, 'NAF-6710007-001', 1, '6701007', '6710007', 2, '2026-02-02 15:16:06', 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 'ผู้ป่วย', '', 164.00, NULL, NULL, NULL, 40.00, 14.87, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 2, 1, 2, 17, 'NAF C', 'SPENT-6710007-001', NULL, '2026-02-02 15:16:06'),
(9, 'NAF-6710008-001', 1, '6701008', '6710008', 1, '2026-02-03 22:08:08', 'กินอาหารได้น้อยลง ช่วยเหลือตัวเองไม่ได้', 'ญาติ', '', 165.00, NULL, NULL, NULL, NULL, NULL, 0, 1, 'Albumin', 2.40, NULL, 3, NULL, 2, 1, 3, 2, NULL, 17, 'NAF C', '', NULL, '2026-02-03 22:08:08'),
(10, 'NAF-6780001-001', 1, '6708001', '6780001', 1, '2026-02-04 08:35:00', 'Osteoarthritis, knee (ข้อเข่าเสื่อม)', 'ผู้ป่วย', '', 170.00, NULL, NULL, NULL, 45.00, 15.57, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 3, 1, 2, 13, 'NAF C', 'SPENT-6780001-001', NULL, '2026-02-04 08:35:00'),
(11, 'NAF-6710009-001', 1, '6701009', '6710009', 1, '2026-02-03 10:10:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 180.00, NULL, NULL, NULL, 48.00, 14.81, 2, 0, '', NULL, NULL, 0, 1, 2, 1, 2, 1, 1, 15, 'NAF C', 'SPENT-6710009-001', NULL, '2026-02-04 09:16:20'),
(12, 'NAF-6710010-001', 1, '6701010', '6710010', 1, '2026-02-03 09:25:00', 'ความดันโลหิตสูง', 'ญาติ', '', 167.00, NULL, NULL, NULL, NULL, NULL, 0, 1, 'TLC', NULL, 500.00, 3, NULL, 2, 1, 2, 2, 1, 20, 'NAF C', '', NULL, '2026-02-04 09:21:49'),
(13, 'NAF-6720001-001', 1, '6702001', '6720001', 1, '2026-02-04 09:51:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 162.00, NULL, NULL, NULL, 45.00, 17.15, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 4, 2, 3, 7, 'NAF B', 'SPENT-6720001-001', NULL, '2026-02-04 09:52:51'),
(14, 'NAF-6720004-001', 1, '6702004', '6720004', 2, '2026-02-05 12:59:00', 'ผู้ป่วยมีประวัติรับประทานอาหารได้น้อยลงในช่วงระยะเวลาที่ผ่านมา ร่วมกับน้ำหนักตัวลดลง ตรวจพบความดันโลหิตต่ำกว่าปกติ อาจสัมพันธ์กับภาวะโภชนาการไม่เพียงพอ', 'ผู้ป่วย', '', 165.00, NULL, NULL, NULL, 45.00, 16.53, 0, 0, '', NULL, NULL, 0, 1, 2, 1, 2, 1, 2, 18, 'NAF C', 'SPENT-6720004-001', NULL, '2026-02-05 13:02:29'),
(15, 'NAF-6720004-002', 2, '6702004', '6720004', 2, '2026-02-05 13:10:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 158.00, NULL, NULL, NULL, 45.00, 18.03, 0, 0, '', NULL, NULL, 0, 1, 1, 1, 2, 1, 2, 16, 'NAF C', 'SPENT-6720004-002', NULL, '2026-02-05 13:12:42'),
(16, 'NAF-6710006-001', 1, '6701006', '6710006', 2, '2026-02-02 08:46:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 165.00, NULL, NULL, NULL, 45.00, 16.53, 0, 0, '', NULL, NULL, 0, 2, 1, 1, 2, 1, 1, 10, 'NAF B', 'SPENT-6710006-001', NULL, '2026-02-06 09:11:20'),
(17, 'NAF-6710001-002', 2, '6701001', '6710001', 1, '2026-02-02 14:53:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 165.00, NULL, NULL, NULL, 45.00, 16.53, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 3, 2, 1, 10, 'NAF B', 'SPENT-6710001-002', NULL, '2026-02-10 08:41:43'),
(18, 'NAF-6710002-001', 1, '6701002', '6710002', 1, '2026-02-10 08:50:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 175.00, NULL, NULL, NULL, 50.00, 16.33, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 3, 2, 3, 8, 'NAF B', 'SPENT-6710002-002', NULL, '2026-02-10 08:51:21'),
(19, 'NAF-6720007-001', 1, '6702007', '6720007', 1, '2026-02-09 10:45:00', 'ความดันโลหิตสูง', 'ผู้ป่วย', '', 158.00, NULL, NULL, NULL, 50.00, 20.03, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 2, 1, 4, 10, 'NAF B', 'SPENT-6720007-001', NULL, '2026-02-10 08:52:45'),
(20, 'NAF-6720008-001', 1, '6702008', '6720008', 1, '2026-02-10 13:39:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 156.00, NULL, NULL, NULL, 45.00, 18.49, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 2, 1, 2, 11, 'NAF B', 'SPENT-6720008-001', NULL, '2026-02-10 13:41:12'),
(21, 'NAF-6730002-001', 1, '6703002', '6730002', 1, '2026-02-16 15:14:00', 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 'ญาติ', '', 175.00, NULL, NULL, NULL, NULL, NULL, 0, 1, 'Albumin', 3.20, NULL, 1, NULL, 2, 1, 3, 2, 1, 11, 'NAF B', '', NULL, '2026-02-16 15:15:04'),
(22, 'NAF-6730003-001', 1, '6703003', '6730003', 1, '2026-02-19 01:52:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 165.00, NULL, NULL, NULL, 50.00, 18.37, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 4, 2, 1, 6, 'NAF B', 'SPENT-6730003-001', NULL, '2026-02-19 01:53:16'),
(23, 'NAF-6720009-001', 1, '6702009', '6720009', 1, '2026-02-16 14:00:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 168.00, NULL, NULL, NULL, 45.00, 15.94, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 4, 2, 4, 7, 'NAF B', 'SPENT-6720009-001', NULL, '2026-02-19 01:57:46'),
(24, 'NAF-6730010-001', 1, '6703010', '6730010', 1, '2026-02-22 14:19:00', 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 'ญาติ', '', 158.00, NULL, NULL, NULL, NULL, NULL, 0, 1, 'Albumin', 2.20, NULL, 3, NULL, 2, 1, 3, 2, 2, 9, 'NAF B', '', NULL, '2026-02-22 14:22:07'),
(25, 'NAF-6720006-001', 1, '6702006', '6720006', 1, '2026-02-22 14:22:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 156.00, NULL, NULL, NULL, 40.00, 16.44, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 2, 1, 2, 8, 'NAF B', 'SPENT-6720006-001', 25, '2026-02-22 14:22:51'),
(26, 'NAF-6730005-001', 1, '6703005', '6730005', 1, '2026-02-22 14:24:00', 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 'ญาติ', '', 168.00, NULL, NULL, NULL, 45.00, 15.94, 2, 0, '', NULL, NULL, 0, 1, 2, 1, NULL, NULL, NULL, 6, 'NAF B', '', NULL, '2026-02-22 14:25:17'),
(27, 'NAF-6730005-002', 2, '6703005', '6730005', 1, '2026-02-22 14:25:00', 'อ่อนเพลีย น้ำหนักลด', 'ญาติ', '', 168.00, NULL, NULL, NULL, 55.00, 19.49, 0, 0, '', NULL, NULL, 0, 1, 1, 1, 1, 2, NULL, 8, 'NAF B', '', NULL, '2026-02-22 14:25:53'),
(28, 'NAF-6730005-003', 3, '6703005', '6730005', 1, '2026-02-22 14:25:00', 'อ่อนเพลีย น้ำหนักลด', 'ญาติ', '', 168.00, NULL, NULL, NULL, 55.00, 19.49, 0, 0, '', NULL, NULL, 0, 1, 1, 1, 2, 2, NULL, 8, 'NAF B', '', NULL, '2026-02-22 14:26:20'),
(29, 'NAF-6730005-004', 4, '6703005', '6730005', 1, '2026-02-22 14:26:00', 'อ่อนเพลีย น้ำหนักลด', 'ญาติ', '', 168.00, NULL, NULL, NULL, 55.00, 19.49, 0, 0, '', NULL, NULL, 0, 1, 1, 2, 1, 2, 1, 21, 'NAF C', '', NULL, '2026-02-22 14:29:01'),
(30, 'NAF-6730005-005', 5, '6703005', '6730005', 1, '2026-02-22 14:34:00', 'อ่อนเพลีย น้ำหนักลด', 'ญาติ', '', 168.00, NULL, NULL, NULL, 55.00, 19.49, 0, 0, '', NULL, NULL, 0, 4, 4, 4, 3, 3, 3, 1, 'NAF A', '', NULL, '2026-02-22 14:34:38'),
(31, 'NAF-6730005-006', 6, '6703005', '6730005', 1, '2026-02-22 14:41:00', 'ความดันโลหิตสูง', 'ญาติ', '', 168.00, NULL, NULL, NULL, 55.00, 19.49, 0, 0, '', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 10, 'NAF A', '', NULL, '2026-02-22 14:41:24'),
(32, 'NAF-6730005-007', 7, '6703005', '6730005', 1, '2026-02-22 14:41:00', 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 'ญาติ', '', 168.00, NULL, NULL, NULL, NULL, NULL, 0, 1, 'Albumin', 2.50, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'NAF A', '', NULL, '2026-02-22 14:42:07'),
(33, 'NAF-6730006-001', 1, '6703006', '6730006', 1, '2026-02-23 10:05:00', 'อ่อนเพลีย น้ำหนักลด', 'ญาติ', '', 167.00, NULL, NULL, NULL, 50.00, 17.93, 1, 0, '', NULL, NULL, 0, 2, 4, 2, 4, 3, 4, 5, 'NAF A', '', NULL, '2026-02-23 10:06:16'),
(34, 'NAF-6730007-001', 1, '6703007', '6730007', 1, '2026-02-24 11:22:00', 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 'ผู้ป่วย', '', 185.00, NULL, NULL, NULL, 45.00, 13.15, 0, 0, '', NULL, NULL, 0, 2, 1, 1, 3, 2, 4, 14, 'NAF C', 'SPENT-6730007-001', 33, '2026-02-24 11:22:49'),
(35, 'NAF-6780002-002', 2, '6708002', '6780002', 1, '2026-02-24 16:57:00', 'ความดันโลหิตสูง', 'ญาติ', '', 158.00, NULL, NULL, NULL, 50.00, 20.03, 0, 0, '', NULL, NULL, 0, 2, 2, 2, 3, 3, 4, 3, 'NAF A', '', NULL, '2026-02-24 16:58:08'),
(36, 'NAF-6730004-001', 1, '6703004', '6730004', 1, '2026-02-25 01:27:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 165.00, NULL, NULL, NULL, 50.00, 18.37, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 1, 2, 2, 17, 'NAF C', 'SPENT-6730004-002', 34, '2026-02-25 01:28:07'),
(37, 'NAF-6740002-001', 1, '6704002', '6740002', 1, '2026-02-25 02:12:00', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', '', 158.00, NULL, NULL, NULL, 45.00, 18.03, 0, 0, '', NULL, NULL, 0, 2, 2, 1, 3, 2, 2, 16, 'NAF C', 'SPENT-6740002-001', 37, '2026-02-25 02:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_screening`
--

CREATE TABLE `nutrition_screening` (
  `nutrition_screening_id` int(11) NOT NULL COMMENT 'รหัสลำดับการคัดกรอง',
  `doc_no` varchar(20) NOT NULL COMMENT 'เลขที่เอกสารการคัดกรอง',
  `admissions_an` varchar(20) NOT NULL COMMENT 'รหัส AN',
  `patient_hn` varchar(20) NOT NULL COMMENT 'รหัส HN',
  `nutritionist_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ทำการคัดกรอง',
  `nutrition_screening_datetime` datetime NOT NULL COMMENT 'วันและเวลาที่ทำการคัดกรองภาวะโภชนาการ',
  `nutrition_screening_seq` int(11) DEFAULT 1 COMMENT 'ลำดับครั้งที่คัดกรองของการเข้ารับการรักษารอบนี้ ',
  `initial_diagnosis` varchar(255) DEFAULT NULL COMMENT 'การวินิจฉัยโรคเบื้องต้น',
  `present_weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนักตัวปัจจุบัน (กิโลกรัม)',
  `normal_weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนักตัวปกติ (กิโลกรัม)',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูงของผู้ป่วย (เซนติเมตร)',
  `bmi` decimal(5,2) DEFAULT NULL COMMENT 'ค่าดัชนีมวลกาย (BMI) ที่คำนวณได้',
  `weight_method` varchar(100) DEFAULT NULL COMMENT 'วิธีการที่ได้น้ำหนักมา',
  `q1_weight_loss` int(11) DEFAULT NULL COMMENT 'คะแนนคำถาม Q1: น้ำหนักลดลงโดยไม่ได้ตั้งใจ (1 = ใช่, 0 = ไม่ใช่)',
  `q2_eat_less` int(11) DEFAULT NULL COMMENT 'คะแนนคำถาม Q2: รับประทานอาหารได้น้อยลง (1 = ใช่, 0 = ไม่ใช่)',
  `q3_bmi_abnormal` int(11) DEFAULT NULL COMMENT 'คะแนนคำถาม Q3: BMI ต่ำกว่าเกณฑ์มาตรฐาน (1 = ใช่, 0 = ไม่ใช่)',
  `q4_critical` int(11) DEFAULT NULL COMMENT 'คะแนนคำถาม Q4: ผู้ป่วยมีภาวะวิกฤต (1 = ใช่, 0 = ไม่ใช่)',
  `nutrition_screening_result` varchar(50) DEFAULT NULL COMMENT 'ผลการคัดกรองรวม',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุหรือข้อมูลเพิ่มเติมจากการคัดกรอง',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันและเวลาที่ข้อมูลคัดกรองถูกบันทึกลงระบบ',
  `screening_status` varchar(50) DEFAULT NULL COMMENT 'สถานะความคืบหน้า (เช่น ปกติ, มีความเสี่ยง, รอทำแบบประเมิน, ประเมินต่อแล้ว)',
  `has_assessment` tinyint(1) DEFAULT 0 COMMENT 'สถานะการทำแบบประเมิน NAF ต่อเนื่อง (1 = มีการทำ NAF แล้ว, 0 = ยังไม่ทำ)',
  `assessment_doc_no` varchar(20) DEFAULT NULL COMMENT 'เลขที่เอกสารแบบประเมิน NAF ที่เชื่อมโยงกับใบนี้'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางประวัติการคัดกรองภาวะโภชนาการ (Screening)';

--
-- Dumping data for table `nutrition_screening`
--

INSERT INTO `nutrition_screening` (`nutrition_screening_id`, `doc_no`, `admissions_an`, `patient_hn`, `nutritionist_id`, `nutrition_screening_datetime`, `nutrition_screening_seq`, `initial_diagnosis`, `present_weight`, `normal_weight`, `height`, `bmi`, `weight_method`, `q1_weight_loss`, `q2_eat_less`, `q3_bmi_abnormal`, `q4_critical`, `nutrition_screening_result`, `notes`, `created_at`, `screening_status`, `has_assessment`, `assessment_doc_no`) VALUES
(1, 'SPENT-6710001-001', '6701001', '6710001', 1, '2026-01-22 08:37:59', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 165.00, 16.53, 'ซักถาม', 0, 1, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 08:37:59', 'ประเมินต่อแล้ว', 1, 'NAF-6710001-001'),
(2, 'SPENT-6710002-001', '6701002', '6710002', 1, '2026-01-22 08:59:14', 1, 'ความดันโลหิตสูง', 50.00, 55.00, 158.00, 20.03, 'ชั่งจริง', 0, 0, 0, 0, 'ปกติ', '', '2026-01-22 08:59:14', 'ปกติ', 0, NULL),
(3, 'SPENT-6710003-001', '6701003', '6710003', 2, '2026-01-22 08:59:44', 1, 'มะเร็งลำไส้ใหญ่', 45.00, 50.00, 167.00, 16.14, 'กะประมาณ', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 08:59:44', 'ประเมินต่อแล้ว', 1, 'NAF-6710003-001'),
(4, 'SPENT-6780001-001', '6708001', '6780001', 2, '2026-01-22 13:02:42', 1, 'Osteoarthritis, knee (ข้อเข่าเสื่อม)', 45.00, 56.00, 170.00, 15.57, 'ชั่งจริง', 1, 1, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 13:02:42', 'ประเมินต่อแล้ว', 1, 'NAF-6780001-001'),
(5, 'SPENT-6780002-001', '6708002', '6780002', 1, '2026-01-22 13:04:36', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 178.00, 15.78, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 13:04:36', 'ประเมินต่อแล้ว', 1, 'NAF-6780002-001'),
(6, 'SPENT-6710004-001', '6701004', '6710004', 1, '2026-01-22 15:12:56', 1, 'ความดันโลหิตสูง', 50.00, 55.00, 168.00, 17.72, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 15:12:56', 'ประเมินต่อแล้ว', 1, 'NAF-6710004-001'),
(7, 'SPENT-6760001-001', '6706001', '6760001', 4, '2026-01-22 15:14:15', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 55.00, 158.00, 18.03, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 15:14:15', 'ประเมินต่อแล้ว', 1, 'NAF-6760001-001'),
(8, 'SPENT-6770002-001', '6707002', '6770002', 2, '2026-01-26 10:52:21', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 157.00, 20.28, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-01-26 10:52:21', 'ปกติ', 0, NULL),
(9, 'SPENT-6770001-001', '6707001', '6770001', 1, '2026-01-28 09:01:11', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 168.00, 15.94, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-28 09:01:11', 'ประเมินต่อแล้ว', 1, 'NAF-6770001-001'),
(10, 'SPENT-6710005-001', '6701005', '6710005', 2, '2026-01-28 09:41:50', 1, 'อ่อนเพลีย น้ำหนักลด', 40.00, 55.00, 170.00, 13.84, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-28 09:41:50', 'ประเมินต่อแล้ว', 1, 'NAF-6710005-001'),
(11, 'SPENT-6730001-001', '6703001', '6730001', 2, '2026-01-28 10:19:05', 1, 'ความดันโลหิตสูง', 50.00, 55.00, 158.00, 20.03, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-01-28 10:19:05', 'ปกติ', 0, NULL),
(12, 'SPENT-6740001-001', '6704001', '6740001', 2, '2026-01-28 10:19:24', 1, 'ความดันโลหิตสูง', 45.00, 50.00, 165.00, 16.53, 'ซักถาม', 0, 0, 1, 0, 'ปกติ', '', '2026-01-28 10:19:24', 'ปกติ', 0, NULL),
(13, 'SPENT-6710006-001', '6701006', '6710006', 1, '2026-02-02 08:46:07', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 165.00, 16.53, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-02 08:46:07', 'ประเมินต่อแล้ว', 1, 'NAF-6710006-001'),
(14, 'SPENT-6710001-002', '6701001', '6710001', 1, '2026-02-02 14:53:34', 2, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 165.00, 16.53, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-02 14:53:34', 'ประเมินต่อแล้ว', 1, 'NAF-6710001-002'),
(15, 'SPENT-6710007-001', '6701007', '6710007', 2, '2026-02-02 15:10:02', 1, 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 40.00, 50.00, 164.00, 14.87, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-02 15:10:02', 'ประเมินต่อแล้ว', 1, 'NAF-6710007-001'),
(16, 'SPENT-6760002-001', '6706002', '6760002', 2, '2026-02-02 16:03:11', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 160.00, 19.53, 'กะประมาณ', 1, 0, 0, 0, 'ปกติ', '', '2026-02-02 16:03:11', 'ปกติ', 0, NULL),
(17, 'SPENT-6710009-001', '6701009', '6710009', 1, '2026-02-02 11:04:00', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 60.00, 180.00, 15.43, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-04 09:04:42', 'ประเมินต่อแล้ว', 1, 'NAF-6710009-001'),
(18, 'SPENT-6710010-001', '6701010', '6710010', 1, '2026-02-04 09:51:00', 1, 'อ่อนเพลีย น้ำหนักลด', 40.00, 60.00, 167.00, 14.34, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-04 09:51:21', 'รอทำแบบประเมิน', 0, NULL),
(19, 'SPENT-6720001-001', '6702001', '6720001', 1, '2026-02-04 09:51:00', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 55.00, 162.00, 17.15, 'กะประมาณ', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-04 09:52:00', 'ประเมินต่อแล้ว', 1, 'NAF-6720001-001'),
(20, 'SPENT-6720002-001', '6702002', '6720002', 1, '2026-02-04 10:45:00', 1, 'อ่อนเพลีย น้ำหนักลด', 55.00, 60.00, 169.00, 19.26, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-02-04 10:45:52', 'ปกติ', 0, NULL),
(21, 'SPENT-6720003-001', '6702003', '6720003', 2, '2026-02-05 12:58:00', 1, 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 50.00, 65.00, 153.00, 21.36, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-02-05 12:59:16', 'ปกติ', 0, NULL),
(22, 'SPENT-6720004-001', '6702004', '6720004', 2, '2026-02-05 12:59:00', 1, 'ผู้ป่วยมีประวัติรับประทานอาหารได้น้อยลงในช่วงระยะเวลาที่ผ่านมา ร่วมกับน้ำหนักตัวลดลง ตรวจพบความดันโลหิตต่ำกว่าปกติ อาจสัมพันธ์กับภาวะโภชนาการไม่เพียงพอ', 45.00, 55.00, 165.00, 16.53, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-05 13:02:02', 'ประเมินต่อแล้ว', 1, 'NAF-6720004-001'),
(23, 'SPENT-6720004-002', '6702004', '6720004', 2, '2026-02-05 13:10:00', 2, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 158.00, 18.03, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-05 13:12:02', 'ประเมินต่อแล้ว', 1, 'NAF-6720004-002'),
(24, 'SPENT-6720005-001', '6702005', '6720005', 1, '2026-02-09 20:51:00', 1, 'อ่อนเพลีย และมีอาการเบื่ออาหาร', 50.00, 60.00, 157.00, 20.28, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-02-09 20:51:47', 'ปกติ', 0, NULL),
(25, 'SPENT-6720006-001', '6702006', '6720006', 1, '2026-02-09 20:53:00', 1, 'อ่อนเพลีย น้ำหนักลด', 40.00, 45.00, 156.00, 16.44, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-09 20:53:45', 'ประเมินต่อแล้ว', 1, 'NAF-6720006-001'),
(26, 'SPENT-6710002-002', '6701002', '6710002', 1, '2026-02-10 08:50:00', 2, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 175.00, 16.33, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-10 08:50:34', 'ประเมินต่อแล้ว', 1, 'NAF-6710002-001'),
(27, 'SPENT-6720007-001', '6702007', '6720007', 1, '2026-02-08 09:45:00', 1, 'ความดันโลหิตสูง', 45.00, 50.00, 158.00, 18.03, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-10 08:52:02', 'ประเมินต่อแล้ว', 1, 'NAF-6720007-001'),
(28, 'SPENT-6720008-001', '6702008', '6720008', 1, '2026-02-10 13:39:00', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 156.00, 18.49, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-10 13:40:12', 'ประเมินต่อแล้ว', 1, 'NAF-6720008-001'),
(29, 'SPENT-6720009-001', '6702009', '6720009', 1, '2026-02-15 21:45:00', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 168.00, 15.94, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-15 21:45:31', 'ประเมินต่อแล้ว', 1, 'NAF-6720009-001'),
(30, 'SPENT-6720010-001', '6702010', '6720010', 1, '2026-02-16 15:16:00', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 155.00, 20.81, 'ซักถาม', 1, 0, 0, 0, 'ปกติ', '', '2026-02-16 15:16:35', 'ปกติ', 0, NULL),
(31, 'SPENT-6730003-001', '6703003', '6730003', 1, '2026-02-19 01:41:00', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 165.00, 18.37, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-19 01:43:45', 'ประเมินต่อแล้ว', 1, 'NAF-6730003-001'),
(32, 'SPENT-6730004-001', '6703004', '6730004', 1, '2026-02-12 10:50:00', 1, 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 45.00, 50.00, 169.00, 15.76, 'ชั่งจริง', 0, 0, 1, 0, 'ปกติ', '', '2026-02-19 01:50:31', 'ปกติ', 0, NULL),
(33, 'SPENT-6730007-001', '6703007', '6730007', 1, '2026-02-24 11:22:00', 1, 'ความดันตก หายใจไม่ค่อยสะดวก มีอาการเบื่ออาหาร และกินอาหารได้น้อยลง', 45.00, 50.00, 185.00, 13.15, 'กะประมาณ', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-24 11:22:14', 'ประเมินต่อแล้ว', 1, 'NAF-6730007-001'),
(34, 'SPENT-6730004-002', '6703004', '6730004', 1, '2026-02-25 00:37:00', 2, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 165.00, 18.37, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-25 00:37:59', 'ประเมินต่อแล้ว', 1, 'NAF-6730004-001'),
(35, 'SPENT-6730008-001', '6703008', '6730008', 1, '2026-02-25 00:59:00', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 165.00, 16.53, 'ชั่งจริง', 0, 0, 1, 0, 'ปกติ', '', '2026-02-25 00:59:48', 'ปกติ', 0, NULL),
(36, 'SPENT-6760003-001', '6706003', '6760003', 1, '2026-02-25 01:33:00', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 185.00, 13.15, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-25 01:33:30', 'รอทำแบบประเมิน', 0, NULL),
(37, 'SPENT-6740002-001', '6704002', '6740002', 1, '2026-02-25 01:50:00', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 158.00, 18.03, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-25 02:12:49', 'ประเมินต่อแล้ว', 1, 'NAF-6740002-001'),
(38, 'SPENT-6740003-001', '6704003', '6740003', 2, '2026-02-25 02:14:00', 1, 'อ่อนเพลีย เบื่ออาหาร ทานได้น้อยลง และมีอาการบวมที่ขาทั้งสองข้าง มา 2 สัปดาห์', 50.00, 55.00, 165.00, 18.37, 'ชั่งจริง', 0, 0, 1, 0, 'ปกติ', '', '2026-02-25 02:25:12', 'ปกติ', 0, NULL),
(39, 'SPENT-6740004-001', '6704004', '6740004', 2, '2026-02-25 03:05:00', 1, 'อ่อนเพลีย เบื่ออาหาร ทานได้น้อยลง และมีอาการบวมที่ขาทั้งสองข้าง มา 2 สัปดาห์', 45.00, 55.00, 157.00, 18.26, 'กะประมาณ', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-02-25 03:06:41', 'รอทำแบบประเมิน', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `patient_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงข้อมูลผู้ป่วย',
  `patient_hn` varchar(20) NOT NULL COMMENT 'รหัสประจำตัวผู้ป่วย',
  `patient_id_card` varchar(13) DEFAULT NULL COMMENT 'เลขประจำตัวประชาชน 13 หลัก',
  `patient_firstname` varchar(100) NOT NULL COMMENT 'ชื่อจริงผู้ป่วย',
  `patient_lastname` varchar(100) NOT NULL COMMENT 'นามสกุลผู้ป่วย',
  `patient_gender` enum('ชาย','หญิง') DEFAULT NULL COMMENT 'เพศของผู้ป่วย',
  `patient_dob` date DEFAULT NULL COMMENT 'วันเดือนปีเกิดของผู้ป่วย',
  `patient_phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์สำหรับติดต่อผู้ป่วยหรือญาติ',
  `patient_drug_allergy` text DEFAULT NULL COMMENT 'ประวัติการแพ้ยา',
  `patient_congenital_disease` text DEFAULT NULL COMMENT 'โรคประจำตัว'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`patient_id`, `patient_hn`, `patient_id_card`, `patient_firstname`, `patient_lastname`, `patient_gender`, `patient_dob`, `patient_phone`, `patient_drug_allergy`, `patient_congenital_disease`) VALUES
(1, '6710001', '3100100111001', 'นายสมชาย', 'ใจดี', 'ชาย', '1960-01-01', '081-111-0001', 'ไม่แพ้', 'COPD (ปอดอุดกั้น)'),
(2, '6710002', '3100100111002', 'นายวีระ', 'กล้าหาญ', 'ชาย', '1975-05-05', '081-111-0002', 'Penicillin', 'CKD Stage 4 (โรคไต)'),
(3, '6710003', '3100100111003', 'นายปิติ', 'ยินดี', 'ชาย', '1980-10-10', '081-111-0003', 'ไม่แพ้', 'CHF (หัวใจล้มเหลว)'),
(4, '6710004', '3100100111004', 'นายมานะ', 'อดทน', 'ชาย', '1955-12-12', '081-111-0004', 'Sulfa', 'Stroke (หลอดเลือดสมอง)'),
(5, '6710005', '3100100111005', 'นายชูใจ', 'รักสงบ', 'ชาย', '1990-03-03', '081-111-0005', 'ไม่แพ้', 'Pneumonia (ปอดอักเสบ)'),
(6, '6710006', '3100100111006', 'นายกิตติ', 'มีทรัพย์', 'ชาย', '1968-08-20', '081-111-0006', 'ไม่แพ้', 'Dengue Fever (ไข้เลือดออก)'),
(7, '6710007', '3100100111007', 'นายอำนาจ', 'วาสนา', 'ชาย', '1972-02-14', '081-111-0007', 'Aspirin', 'MI (กล้ามเนื้อหัวใจตาย)'),
(8, '6710008', '3100100111008', 'นายวายุ', 'พัดพา', 'ชาย', '1985-11-25', '081-111-0008', 'ไม่แพ้', 'Septicemia (ติดเชื้อในกระแสเลือด)'),
(9, '6710009', '3100100111009', 'นายศิลา', 'แกร่ง', 'ชาย', '1950-06-06', '081-111-0009', 'Cephalosporin', 'CA Lung (มะเร็งปอด)'),
(10, '6710010', '3100100111010', 'นายธารา', 'ไหลเย็น', 'ชาย', '1995-09-09', '081-111-0010', 'ไม่แพ้', 'Acute Kidney Injury (ไตวายเฉียบพลัน)'),
(11, '6720001', '3200200222001', 'นางสมศรี', 'มีสุข', 'หญิง', '1965-04-15', '082-222-0001', 'ไม่แพ้', 'DM, HT (เบาหวาน ความดัน)'),
(12, '6720002', '3200200222002', 'นางวันดี', 'ดีใจ', 'หญิง', '1978-07-20', '082-222-0002', 'ไม่แพ้', 'SLE (แพ้ภูมิตัวเอง)'),
(13, '6720003', '3200200222003', 'นางสายใจ', 'รักแท้', 'หญิง', '1958-02-28', '082-222-0003', 'NSAIDS', 'Heart Failure (น้ำท่วมปอด)'),
(14, '6720004', '3200200222004', 'นางมาลี', 'สีสวย', 'หญิง', '1982-11-11', '082-222-0004', 'ไม่แพ้', 'Pyelonephritis (กรวยไตอักเสบ)'),
(15, '6720005', '3200200222005', 'นางกานดา', 'พาเพลิน', 'หญิง', '1960-01-30', '082-222-0005', 'Penicillin', 'Stroke (อัมพาตครึ่งซีก)'),
(16, '6720006', '3200200222006', 'นางจินตนา', 'น่ารัก', 'หญิง', '1992-05-05', '082-222-0006', 'ไม่แพ้', 'Scrub Typhus (ไข้รากสาดใหญ่)'),
(17, '6720007', '3200200222007', 'นางพิมพา', 'ผ่องใส', 'หญิง', '1970-09-09', '082-222-0007', 'Sulfa', 'Anemia (โลหิตจางรุนแรง)'),
(18, '6720008', '3200200222008', 'นางดวงใจ', 'ใสสะอาด', 'หญิง', '1955-12-12', '082-222-0008', 'ไม่แพ้', 'Cirrhosis (ตับแข็ง)'),
(19, '6720009', '3200200222009', 'นางอรัญญา', 'งามตา', 'หญิง', '1988-03-20', '082-222-0009', 'ไม่แพ้', 'Hyperthyroid (ไทรอยด์เป็นพิษ)'),
(20, '6720010', '3200200222010', 'นางประภา', 'กล้าหาญ', 'หญิง', '1975-06-15', '082-222-0010', 'Tramadol', 'Vertigo (บ้านหมุน)'),
(21, '6730001', '3300300333001', 'นายบุญมี', 'มีบุญ', 'ชาย', '1952-08-08', '083-333-0001', 'ไม่แพ้', 'TB Lung (วัณโรคปอด)'),
(22, '6730002', '3300300333002', 'นายสมบัติ', 'รักษา', 'ชาย', '1966-10-10', '083-333-0002', 'ไม่แพ้', 'Alcohol Withdrawal'),
(23, '6730003', '3300300333003', 'นายปรีชา', 'สามารถ', 'ชาย', '1985-01-25', '083-333-0003', 'Aspirin', 'Dengue Hemorrhagic Fever'),
(24, '6730004', '3300300333004', 'นายวิชัย', 'เกรียงไกร', 'ชาย', '1972-04-12', '083-333-0004', 'ไม่แพ้', 'Liver Abscess (ฝีในตับ)'),
(25, '6730005', '3300300333005', 'นายสุชาติ', 'ชาติชาย', 'ชาย', '1959-07-07', '083-333-0005', 'ไม่แพ้', 'AF with RVR (หัวใจเต้นผิดจังหวะ)'),
(26, '6730006', '3300300333006', 'นายอุดม', 'สมบูรณ์', 'ชาย', '1994-12-01', '083-333-0006', 'Penicillin', 'Leptospirosis (โรคฉี่หนู)'),
(27, '6730007', '3300300333007', 'นายไพโรจน์', 'โชติช่วง', 'ชาย', '1963-03-30', '083-333-0007', 'ไม่แพ้', 'UGIB (เลือดออกในทางเดินอาหาร)'),
(28, '6730008', '3300300333008', 'นายสง่า', 'งามตา', 'ชาย', '1980-06-20', '083-333-0008', 'ไม่แพ้', 'Cellulitis Foot'),
(29, '6730009', '3300300333009', 'นายมนตรี', 'ศรีสุข', 'ชาย', '1950-11-15', '083-333-0009', 'Sulfa', 'Parkinson'),
(30, '6730010', '3300300333010', 'นายสนั่น', 'หวั่นไหว', 'ชาย', '1976-02-02', '083-333-0010', 'ไม่แพ้', 'Electrolyte Imbalance'),
(31, '6740001', '3400400444001', 'นางสุภาพ', 'เรียบร้อย', 'หญิง', '1961-05-20', '084-444-0001', 'ไม่แพ้', 'Hyponatremia'),
(32, '6740002', '3400400444002', 'นางนภา', 'ฟ้าใส', 'หญิง', '1988-09-09', '084-444-0002', 'ไม่แพ้', 'Viral Hepatitis (ตับอักเสบ)'),
(33, '6740003', '3400400444003', 'นางวิไล', 'วรรณ', 'หญิง', '1954-12-25', '084-444-0003', 'Cephalosporin', 'Pneumonia in Bedridden'),
(34, '6740004', '3400400444004', 'นางมยุรี', 'สีสด', 'หญิง', '1973-03-15', '084-444-0004', 'ไม่แพ้', 'DM Foot (แผลเบาหวาน)'),
(35, '6740005', '3400400444005', 'นางรัตนา', 'มณี', 'หญิง', '1996-08-08', '084-444-0005', 'ไม่แพ้', 'Influenza A (ไข้หวัดใหญ่)'),
(36, '6740006', '3400400444006', 'นางสมพร', 'สอนง่าย', 'หญิง', '1966-01-10', '084-444-0006', 'Aspirin', 'AF (หัวใจพริ้ว)'),
(37, '6740007', '3400400444007', 'นางอุไร', 'วรรณ', 'หญิง', '1980-04-22', '084-444-0007', 'ไม่แพ้', 'Seizure (ลมชัก)'),
(38, '6740008', '3400400444008', 'นางศิริพร', 'อำไพ', 'หญิง', '1957-07-07', '084-444-0008', 'ไม่แพ้', 'Alzheimer'),
(39, '6740009', '3400400444009', 'นางบัวลอย', 'อร่อยดี', 'หญิง', '1991-11-30', '084-444-0009', 'Penicillin', 'Meningitis (เยื่อหุ้มสมองอักเสบ)'),
(40, '6740010', '3400400444010', 'นางทองสุข', 'มั่งมี', 'หญิง', '1962-02-18', '084-444-0010', 'ไม่แพ้', 'CKD Stage 5'),
(41, '6760001', '3600600666001', 'นายกล้าณรงค์', 'พลัง', 'ชาย', '1985-01-01', '086-666-0001', 'ไม่แพ้', 'Acute Appendicitis'),
(42, '6760002', '3600600666002', 'นายขุนศึก', 'นึกสนุก', 'ชาย', '1960-05-05', '086-666-0002', 'ไม่แพ้', 'Inguinal Hernia'),
(43, '6760003', '3600600666003', 'นายคมสัน', 'มั่นใจ', 'ชาย', '1975-09-09', '086-666-0003', 'Aspirin', 'Hemorrhoids'),
(44, '6760004', '3600600666004', 'นายงามวงศ์', 'พงศ์', 'ชาย', '1992-02-14', '086-666-0004', 'ไม่แพ้', 'Gallstone'),
(45, '6760005', '3600600666005', 'นายจอมพล', 'คนเก่ง', 'ชาย', '1950-11-20', '086-666-0005', 'Sulfa', 'Bowel Obstruction'),
(46, '6760006', '3600600666006', 'นายฉัตรชัย', 'ไวพจน์', 'ชาย', '1968-07-07', '086-666-0006', 'ไม่แพ้', 'Head Injury (Mild)'),
(47, '6760007', '3600600666007', 'นายชลทิศ', 'ทิศทาง', 'ชาย', '1980-04-30', '086-666-0007', 'ไม่แพ้', 'Abdominal Pain'),
(48, '6760008', '3600600666008', 'นายณเดชน์', 'เขตเมือง', 'ชาย', '1995-12-12', '086-666-0008', 'Penicillin', 'Laceration Wound'),
(49, '6760009', '3600600666009', 'นายเดชา', 'มานะ', 'ชาย', '1958-10-10', '086-666-0009', 'ไม่แพ้', 'CA Colon'),
(50, '6760010', '3600600666010', 'นายทรงพล', 'คนดี', 'ชาย', '1970-03-25', '086-666-0010', 'ไม่แพ้', 'Gastric Perforation'),
(51, '6770001', '3700700777001', 'นางกิ่งแก้ว', 'แววไว', 'หญิง', '1978-01-01', '087-777-0001', 'ไม่แพ้', 'Breast Mass'),
(52, '6770002', '3700700777002', 'นางขวัญตา', 'พารวย', 'หญิง', '1985-06-06', '087-777-0002', 'ไม่แพ้', 'Thyroid Nodule'),
(53, '6770003', '3700700777003', 'นางงามพิศ', 'ชิดใกล้', 'หญิง', '1965-09-09', '087-777-0003', 'Aspirin', 'Acute Cholecystitis'),
(54, '6770004', '3700700777004', 'นางจันทร์จิรา', 'มาเลิศ', 'หญิง', '1990-02-14', '087-777-0004', 'ไม่แพ้', 'Acute Appendicitis'),
(55, '6770005', '3700700777005', 'นางฉันทนา', 'พารัก', 'หญิง', '1955-05-20', '087-777-0005', 'Sulfa', 'CA Breast'),
(56, '6770006', '3700700777006', 'นางชลดา', 'น่ามอง', 'หญิง', '1995-11-11', '087-777-0006', 'ไม่แพ้', 'Anal Fissure'),
(57, '6770007', '3700700777007', 'นางญาณี', 'มีสุข', 'หญิง', '1972-04-15', '087-777-0007', 'ไม่แพ้', 'Diabetic Foot'),
(58, '6770008', '3700700777008', 'นางฐาปนีย์', 'ศรีใส', 'หญิง', '1960-08-30', '087-777-0008', 'Penicillin', 'Hernia'),
(59, '6770009', '3700700777009', 'นางณิชา', 'พารุ่ง', 'หญิง', '1982-12-25', '087-777-0009', 'ไม่แพ้', 'Abscess'),
(60, '6770010', '3700700777010', 'นางดวงกมล', 'คนงาม', 'หญิง', '1950-03-03', '087-777-0010', 'ไม่แพ้', 'CA Rectum'),
(61, '6780001', '3800800888001', 'นายแข็ง', 'แกร่ง', 'ชาย', '1998-01-10', '088-888-0001', 'ไม่แพ้', 'Fracture Femur (ขาหัก)'),
(62, '6780002', '3800800888002', 'นางเข่า', 'ดี', 'หญิง', '1955-05-20', '088-888-0002', 'NSAIDS', 'OA Knee (ข้อเข่าเสื่อม)'),
(63, '6780003', '3800800888003', 'นายคงกระพัน', 'ชาตรี', 'ชาย', '1985-09-15', '088-888-0003', 'ไม่แพ้', 'ACL Tear (เอ็นเข่าขาด)'),
(64, '6780004', '3800800888004', 'นางงา', 'ช้าง', 'หญิง', '1960-12-05', '088-888-0004', 'ไม่แพ้', 'Spinal Stenosis (กระดูกทับเส้น)'),
(65, '6780005', '3800800888005', 'นายจอม', 'พลัง', 'ชาย', '1995-03-30', '088-888-0005', 'Penicillin', 'Fracture Tibia'),
(66, '6780006', '3800800888006', 'นางฉัตร', 'ทอง', 'หญิง', '1950-08-08', '088-888-0006', 'ไม่แพ้', 'Fracture Hip (สะโพกหัก)'),
(67, '6780007', '3800800888007', 'นายช้าง', 'ศึก', 'ชาย', '1980-11-20', '088-888-0007', 'Sulfa', 'HNP (หมอนรองกระดูก)'),
(68, '6780008', '3800800888008', 'นางซาร่า', 'น่ารัก', 'หญิง', '1975-02-14', '088-888-0008', 'ไม่แพ้', 'CTS (พังผืดทับเส้นประสาท)'),
(69, '6780009', '3800800888009', 'นายณรงค์', 'เดช', 'ชาย', '2000-06-25', '088-888-0009', 'ไม่แพ้', 'Fracture Clavicle (ไหปลาร้าหัก)'),
(70, '6780010', '3800800888010', 'นางดรุณี', 'มีทรัพย์', 'หญิง', '1965-10-10', '088-888-0010', 'Cephalosporin', 'Septic Arthritis (ข้ออักเสบ)');

-- --------------------------------------------------------

--
-- Table structure for table `patient_shape`
--

CREATE TABLE `patient_shape` (
  `patient_shape_id` int(11) NOT NULL COMMENT 'รหัสลำดับลักษณะรูปร่าง',
  `patient_shape_label` varchar(255) DEFAULT NULL COMMENT 'คำอธิบายลักษณะรูปร่าง',
  `patient_shape_score` int(11) DEFAULT NULL COMMENT 'คะแนนประเมินความเสี่ยงตามลักษณะรูปร่าง',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `patient_shape`
--

INSERT INTO `patient_shape` (`patient_shape_id`, `patient_shape_label`, `patient_shape_score`, `is_active`) VALUES
(1, 'ผอมมาก', 2, 1),
(2, 'ผอม', 1, 1),
(3, 'อ้วนมาก', 1, 1),
(4, 'ปกติ-อ้วนปานกลาง', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `symptom_problem`
--

CREATE TABLE `symptom_problem` (
  `symptom_problem_id` int(11) NOT NULL COMMENT 'รหัสลำดับอาการ/ปัญหา',
  `symptom_problem_name` varchar(255) DEFAULT NULL COMMENT 'ชื่ออาการหรือปัญหา',
  `symptom_problem_type` varchar(255) DEFAULT NULL COMMENT 'หมวดหมู่ของอาการ',
  `symptom_problem_score` int(11) DEFAULT 0 COMMENT 'คะแนนประเมินความเสี่ยงที่ได้รับจากอาการนี้',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)	'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptom_problem`
--

INSERT INTO `symptom_problem` (`symptom_problem_id`, `symptom_problem_name`, `symptom_problem_type`, `symptom_problem_score`, `is_active`) VALUES
(1, 'สำลัก', 'ปัญหาทางการเคี้ยว/กลืนอาหาร', 2, 1),
(2, 'เคี้ยว/กลืนลำบาก/ได้อาหารทางสายยาง', 'ปัญหาทางการเคี้ยว/กลืนอาหาร', 2, 1),
(3, 'กลืนได้ปกติ', 'ปัญหาทางการเคี้ยว/กลืนอาหาร', 0, 1),
(4, 'ท้องเสีย', 'ปัญหาระบบทางเดินอาหาร', 2, 1),
(5, 'ปวดท้อง', 'ปัญหาระบบทางเดินอาหาร', 2, 1),
(6, 'ปกติ', 'ปัญหาระบบทางเดินอาหาร', 0, 1),
(7, 'อาเจียน', 'ปัญหาระหว่างกินอาหาร', 2, 1),
(8, 'คลื่นไส้', 'ปัญหาระหว่างกินอาหาร', 2, 1),
(9, 'ปกติ', 'ปัญหาระหว่างกินอาหาร', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `symptom_problem_saved`
--

CREATE TABLE `symptom_problem_saved` (
  `symptom_problem_saved_id` int(11) NOT NULL COMMENT 'รหัสลำดับการบันทึกอาการ',
  `nutrition_assessment_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงใบประเมินภาวะโภชนาการ',
  `symptom_problem_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงอาการ/ปัญหา (Foreign Key เชื่อมตาราง symptom_problem)',
  `symptom_problem_score` int(11) DEFAULT 0 COMMENT 'คะแนนของอาการที่บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางบันทึกอาการที่เป็น';

--
-- Dumping data for table `symptom_problem_saved`
--

INSERT INTO `symptom_problem_saved` (`symptom_problem_saved_id`, `nutrition_assessment_id`, `symptom_problem_id`, `symptom_problem_score`) VALUES
(1, 1, 1, 2),
(2, 1, 6, 0),
(3, 1, 7, 2),
(4, 2, 1, 2),
(5, 2, 6, 0),
(6, 2, 7, 2),
(7, 3, 3, 0),
(8, 3, 4, 2),
(9, 3, 8, 2),
(10, 4, 3, 0),
(11, 4, 5, 2),
(12, 4, 9, 0),
(13, 5, 3, 0),
(14, 5, 4, 2),
(15, 5, 9, 0),
(16, 6, 1, 2),
(17, 6, 5, 2),
(18, 6, 7, 2),
(19, 7, 1, 2),
(20, 7, 6, 0),
(21, 7, 7, 2),
(22, 8, 3, 0),
(23, 8, 4, 2),
(24, 8, 9, 0),
(25, 9, 1, 2),
(26, 9, 6, 0),
(27, 9, 7, 2),
(28, 10, 1, 2),
(29, 10, 6, 0),
(30, 10, 8, 2),
(31, 11, 1, 2),
(32, 11, 6, 0),
(33, 11, 7, 2),
(34, 12, 3, 0),
(35, 12, 4, 2),
(36, 12, 9, 0),
(37, 13, 1, 2),
(38, 13, 4, 2),
(39, 13, 9, 0),
(40, 14, 3, 0),
(41, 14, 4, 2),
(42, 14, 5, 2),
(43, 14, 9, 0),
(44, 15, 1, 2),
(45, 15, 5, 2),
(46, 15, 7, 2),
(47, 16, 1, 2),
(48, 16, 5, 2),
(49, 16, 7, 2),
(50, 17, 3, 0),
(51, 17, 5, 2),
(52, 17, 7, 2),
(53, 18, 3, 0),
(54, 18, 4, 2),
(55, 18, 8, 2),
(56, 19, 1, 2),
(57, 19, 5, 2),
(58, 19, 9, 0),
(59, 20, 1, 2),
(60, 20, 6, 0),
(61, 20, 7, 2),
(62, 21, 3, 0),
(63, 21, 4, 2),
(64, 21, 9, 0),
(65, 22, 1, 2),
(66, 22, 6, 0),
(67, 22, 9, 0),
(68, 23, 3, 0),
(69, 23, 4, 2),
(70, 23, 9, 0),
(71, 24, 2, 2),
(72, 24, 6, 0),
(73, 24, 8, 2),
(74, 25, 2, 2),
(75, 25, 4, 2),
(76, 25, 9, 0),
(77, 28, 1, 2),
(78, 28, 5, 2),
(79, 28, 8, 2),
(80, 30, 1, 2),
(81, 30, 2, 2),
(82, 30, 5, 2),
(83, 30, 7, 2),
(84, 30, 8, 2),
(85, 31, 1, 2),
(86, 31, 2, 2),
(87, 31, 4, 2),
(88, 31, 5, 2),
(89, 31, 7, 2),
(90, 33, 3, 0),
(91, 33, 6, 0),
(92, 33, 9, 0),
(93, 34, 3, 0),
(94, 34, 4, 2),
(95, 34, 9, 0),
(96, 35, 3, 0),
(97, 35, 6, 0),
(98, 35, 9, 0),
(99, 36, 3, 0),
(100, 36, 4, 2),
(101, 36, 8, 2),
(102, 37, 1, 2),
(103, 37, 6, 0),
(104, 37, 7, 2);

-- --------------------------------------------------------

--
-- Table structure for table `ward`
--

CREATE TABLE `ward` (
  `ward_id` int(11) NOT NULL COMMENT 'รหัสลำดับอ้างอิงหอผู้ป่วย',
  `ward_name` varchar(100) NOT NULL COMMENT 'ชื่อหอผู้ป่วย'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ward`
--

INSERT INTO `ward` (`ward_id`, `ward_name`) VALUES
(1, 'อายุรกรรมชาย 1'),
(2, 'อายุรกรรมหญิง 1'),
(3, 'อายุรกรรมชาย 2'),
(4, 'อายุรกรรมหญิง 2'),
(5, 'อายุรกรรมโรคหลอดเลือดสมอง'),
(6, 'ศัลยกรรมชาย'),
(7, 'ศัลยกรรมหญิง'),
(8, 'ศัลยกรรมกระดูกและข้อ'),
(9, 'ศัลยกรรมระบบประสาท'),
(10, 'หอผู้ป่วยอุบัติเหตุ'),
(11, 'สูติ-นรีเวชกรรม (ตึกกาญจนาภิเษก)'),
(12, 'กุมารเวชกรรม'),
(13, 'ทารกแรกเกิดวิกฤต'),
(14, 'ห้องคลอด (LR)'),
(15, 'ICU อายุรกรรม'),
(16, 'ICU ศัลยกรรม'),
(17, 'ICU อุบัติเหตุ'),
(18, 'CCU (โรคหัวใจ)'),
(19, 'หอผู้ป่วยสงฆ์อาพาธ'),
(20, 'หอผู้ป่วยจักษุ โสต ศอ นาสิก'),
(21, 'หอผู้ป่วยหนักโควิด'),
(22, 'หอผู้ป่วยจิตเวช'),
(23, 'พิเศษอายุรกรรม'),
(24, 'พิเศษศัลยกรรม'),
(25, 'พิเศษสงฆ์');

-- --------------------------------------------------------

--
-- Table structure for table `weight_change_4_weeks`
--

CREATE TABLE `weight_change_4_weeks` (
  `weight_change_4_weeks_id` int(11) NOT NULL COMMENT 'รหัสลำดับการเปลี่ยนแปลงน้ำหนัก',
  `weight_change_4_weeks_label` varchar(255) DEFAULT NULL COMMENT 'คำอธิบายการเปลี่ยนแปลงน้ำหนัก',
  `weight_change_4_weeks_score` int(11) DEFAULT NULL COMMENT 'คะแนนประเมินความเสี่ยงจากการเปลี่ยนแปลงน้ำหนัก',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)	'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `weight_change_4_weeks`
--

INSERT INTO `weight_change_4_weeks` (`weight_change_4_weeks_id`, `weight_change_4_weeks_label`, `weight_change_4_weeks_score`, `is_active`) VALUES
(1, 'ลดลง / ผอมลง', 2, 1),
(2, 'เพิ่มขึ้น / อ้วนขึ้น', 1, 1),
(3, 'ไม่ทราบ', 0, 1),
(4, 'คงเดิม', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `weight_option`
--

CREATE TABLE `weight_option` (
  `weight_option_id` int(11) NOT NULL COMMENT 'รหัสลำดับรูปแบบการชั่งน้ำหนัก',
  `weight_option_label` varchar(255) DEFAULT NULL COMMENT 'คำอธิบายรูปแบบการชั่ง',
  `weight_option_score` int(11) DEFAULT NULL COMMENT 'คะแนนประเมินความเสี่ยงจากรูปแบบการชั่ง',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผลให้หน้าเว็บดึงไปใช้งาน (1 = เปิดใช้งาน, 0 = ปิด/ซ่อน)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `weight_option`
--

INSERT INTO `weight_option` (`weight_option_id`, `weight_option_label`, `weight_option_score`, `is_active`) VALUES
(1, 'ชั่งในท่านอน', 1, 1),
(2, 'ชั่งในท่ายืน', 0, 1),
(3, 'ชั่งไม่ได้', 0, 1),
(4, 'ญาติบอก', 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`admissions_id`),
  ADD UNIQUE KEY `admissions_an` (`admissions_an`),
  ADD KEY `patients_id` (`patient_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `health_insurance_id` (`health_insurance_id`);

--
-- Indexes for table `disease`
--
ALTER TABLE `disease`
  ADD PRIMARY KEY (`disease_id`);

--
-- Indexes for table `disease_saved`
--
ALTER TABLE `disease_saved`
  ADD PRIMARY KEY (`disease_saved_id`),
  ADD KEY `idx_nutrition_assessment_id` (`nutrition_assessment_id`),
  ADD KEY `fk_disease_saved_id` (`disease_id`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doctor_id`);

--
-- Indexes for table `food_access`
--
ALTER TABLE `food_access`
  ADD PRIMARY KEY (`food_access_id`);

--
-- Indexes for table `food_amount`
--
ALTER TABLE `food_amount`
  ADD PRIMARY KEY (`food_amount_id`);

--
-- Indexes for table `food_type`
--
ALTER TABLE `food_type`
  ADD PRIMARY KEY (`food_type_id`);

--
-- Indexes for table `health_insurance`
--
ALTER TABLE `health_insurance`
  ADD PRIMARY KEY (`health_insurance_id`);

--
-- Indexes for table `nutritionist`
--
ALTER TABLE `nutritionist`
  ADD PRIMARY KEY (`nutritionist_id`),
  ADD UNIQUE KEY `unique_username` (`nutritionist_username`);

--
-- Indexes for table `nutritionist_signature`
--
ALTER TABLE `nutritionist_signature`
  ADD PRIMARY KEY (`nutritionist_signature_id`),
  ADD UNIQUE KEY `unique_user_sig` (`nutritionist_id`) COMMENT '1 คนมี 1 ลายเซ็นล่าสุดเท่านั้น';

--
-- Indexes for table `nutrition_assessment`
--
ALTER TABLE `nutrition_assessment`
  ADD PRIMARY KEY (`nutrition_assessment_id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `patients_hn` (`patient_hn`),
  ADD KEY `fk_food_acc` (`food_access_id`),
  ADD KEY `fk_food_amt` (`food_amount_id`),
  ADD KEY `fk_food_type` (`food_type_id`),
  ADD KEY `fk_shape` (`patient_shape_id`),
  ADD KEY `fk_weight_changes` (`weight_change_4_weeks_id`),
  ADD KEY `fk_weight_options` (`weight_option_id`),
  ADD KEY `fk_admissions_an` (`admissions_an`),
  ADD KEY `fk_assessment_nutritionist` (`nutritionist_id`) USING BTREE,
  ADD KEY `fk_na_screening_idx` (`nutrition_screening_id`);

--
-- Indexes for table `nutrition_screening`
--
ALTER TABLE `nutrition_screening`
  ADD PRIMARY KEY (`nutrition_screening_id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `fk_screening_nutritionist` (`nutritionist_id`),
  ADD KEY `patients_hn` (`patient_hn`),
  ADD KEY `fk_nutrition_screening` (`admissions_an`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `patients_hn` (`patient_hn`);

--
-- Indexes for table `patient_shape`
--
ALTER TABLE `patient_shape`
  ADD PRIMARY KEY (`patient_shape_id`);

--
-- Indexes for table `symptom_problem`
--
ALTER TABLE `symptom_problem`
  ADD PRIMARY KEY (`symptom_problem_id`);

--
-- Indexes for table `symptom_problem_saved`
--
ALTER TABLE `symptom_problem_saved`
  ADD PRIMARY KEY (`symptom_problem_saved_id`),
  ADD KEY `idx_nutrition_assessment_id` (`nutrition_assessment_id`),
  ADD KEY `fk_symptom_saved_id` (`symptom_problem_id`);

--
-- Indexes for table `ward`
--
ALTER TABLE `ward`
  ADD PRIMARY KEY (`ward_id`);

--
-- Indexes for table `weight_change_4_weeks`
--
ALTER TABLE `weight_change_4_weeks`
  ADD PRIMARY KEY (`weight_change_4_weeks_id`);

--
-- Indexes for table `weight_option`
--
ALTER TABLE `weight_option`
  ADD PRIMARY KEY (`weight_option_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `admissions_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับการเข้ารับการรักษาในระบบ', AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `disease`
--
ALTER TABLE `disease`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงของโรค', AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `disease_saved`
--
ALTER TABLE `disease_saved`
  MODIFY `disease_saved_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสอ้างอิงข้อมูลโรคที่ผู้ป่วยเป็น', AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงข้อมูลแพทย์', AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `food_access`
--
ALTER TABLE `food_access`
  MODIFY `food_access_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงความสามารถในการเข้าถึงอาหาร', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `food_amount`
--
ALTER TABLE `food_amount`
  MODIFY `food_amount_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงปริมาณอาหาร', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `food_type`
--
ALTER TABLE `food_type`
  MODIFY `food_type_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงลักษณะประเภทอาหาร', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `health_insurance`
--
ALTER TABLE `health_insurance`
  MODIFY `health_insurance_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงสิทธิการรักษาพยาบาล', AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `nutritionist`
--
ALTER TABLE `nutritionist`
  MODIFY `nutritionist_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงข้อมูลนักโภชนาการ', AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `nutritionist_signature`
--
ALTER TABLE `nutritionist_signature`
  MODIFY `nutritionist_signature_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงข้อมูลลายเซ็น', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `nutrition_assessment`
--
ALTER TABLE `nutrition_assessment`
  MODIFY `nutrition_assessment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงแบบประเมินภาวะโภชนาการ', AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `nutrition_screening`
--
ALTER TABLE `nutrition_screening`
  MODIFY `nutrition_screening_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับการคัดกรอง', AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงข้อมูลผู้ป่วย', AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `patient_shape`
--
ALTER TABLE `patient_shape`
  MODIFY `patient_shape_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับลักษณะรูปร่าง', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `symptom_problem`
--
ALTER TABLE `symptom_problem`
  MODIFY `symptom_problem_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอาการ/ปัญหา', AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `symptom_problem_saved`
--
ALTER TABLE `symptom_problem_saved`
  MODIFY `symptom_problem_saved_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับการบันทึกอาการ', AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `ward`
--
ALTER TABLE `ward`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับอ้างอิงหอผู้ป่วย', AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `weight_change_4_weeks`
--
ALTER TABLE `weight_change_4_weeks`
  MODIFY `weight_change_4_weeks_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับการเปลี่ยนแปลงน้ำหนัก', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `weight_option`
--
ALTER TABLE `weight_option`
  MODIFY `weight_option_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับรูปแบบการชั่งน้ำหนัก', AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`patient_id`),
  ADD CONSTRAINT `admissions_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `ward` (`ward_id`),
  ADD CONSTRAINT `admissions_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_admissions_insurance` FOREIGN KEY (`health_insurance_id`) REFERENCES `health_insurance` (`health_insurance_id`);

--
-- Constraints for table `disease_saved`
--
ALTER TABLE `disease_saved`
  ADD CONSTRAINT `fk_disease_assessment` FOREIGN KEY (`nutrition_assessment_id`) REFERENCES `nutrition_assessment` (`nutrition_assessment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_disease_saved_id` FOREIGN KEY (`disease_id`) REFERENCES `disease` (`disease_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nutritionist_signature`
--
ALTER TABLE `nutritionist_signature`
  ADD CONSTRAINT `fk_signature_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `nutritionist` (`nutritionist_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nutrition_assessment`
--
ALTER TABLE `nutrition_assessment`
  ADD CONSTRAINT `fk_admissions_an` FOREIGN KEY (`admissions_an`) REFERENCES `admissions` (`admissions_an`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assessment_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `nutritionist` (`nutritionist_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_food_acc` FOREIGN KEY (`food_access_id`) REFERENCES `food_access` (`food_access_id`),
  ADD CONSTRAINT `fk_food_amt` FOREIGN KEY (`food_amount_id`) REFERENCES `food_amount` (`food_amount_id`),
  ADD CONSTRAINT `fk_food_type` FOREIGN KEY (`food_type_id`) REFERENCES `food_type` (`food_type_id`),
  ADD CONSTRAINT `fk_nutrition_assessment_screening` FOREIGN KEY (`nutrition_screening_id`) REFERENCES `nutrition_screening` (`nutrition_screening_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shape` FOREIGN KEY (`patient_shape_id`) REFERENCES `patient_shape` (`patient_shape_id`),
  ADD CONSTRAINT `fk_weight_changes` FOREIGN KEY (`weight_change_4_weeks_id`) REFERENCES `weight_change_4_weeks` (`weight_change_4_weeks_id`),
  ADD CONSTRAINT `fk_weight_options` FOREIGN KEY (`weight_option_id`) REFERENCES `weight_option` (`weight_option_id`);

--
-- Constraints for table `nutrition_screening`
--
ALTER TABLE `nutrition_screening`
  ADD CONSTRAINT `fk_nutrition_screening` FOREIGN KEY (`admissions_an`) REFERENCES `admissions` (`admissions_an`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_screening_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `nutritionist` (`nutritionist_id`) ON UPDATE CASCADE;

--
-- Constraints for table `symptom_problem_saved`
--
ALTER TABLE `symptom_problem_saved`
  ADD CONSTRAINT `fk_symptom_assessment` FOREIGN KEY (`nutrition_assessment_id`) REFERENCES `nutrition_assessment` (`nutrition_assessment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_symptom_saved_id` FOREIGN KEY (`symptom_problem_id`) REFERENCES `symptom_problem` (`symptom_problem_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
