-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 30, 2026 at 04:19 AM
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
  `admissions_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงภายในระบบ',
  `admissions_an` varchar(20) NOT NULL COMMENT 'รหัส AN (Admission Number) เลขประจำตัวผู้ป่วยในรอบนี้',
  `patients_id` int(11) NOT NULL COMMENT 'เชื่อมโยงกับตารางผู้ป่วย (FK)',
  `health_insurance_id` int(11) DEFAULT NULL COMMENT 'สิทธิการรักษา (FK)',
  `admit_datetime` datetime NOT NULL COMMENT 'วันเวลาที่เริ่มแอดมิท',
  `discharge_datetime` datetime DEFAULT NULL COMMENT 'วันเวลาที่จำหน่ายออก (NULL = ยังนอนรักษาอยู่)',
  `ward_id` int(11) DEFAULT NULL COMMENT 'เชื่อมโยงตารางแผนก/หอผู้ป่วย (FK)',
  `bed_number` varchar(10) DEFAULT NULL COMMENT 'เลขเตียง (ระบุเป็นตัวอักษรได้)',
  `doctor_id` int(11) DEFAULT NULL COMMENT 'แพทย์เจ้าของไข้ในรอบนี้ (FK)',
  `status` varchar(50) DEFAULT 'Admitted' COMMENT 'สถานะปัจจุบัน (เช่น Admitted, Discharged, Transferred)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันเวลาที่สร้าง record นี้'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางเก็บข้อมูลการเข้ารักษาตัว (IPD Admissions)';

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`admissions_id`, `admissions_an`, `patients_id`, `health_insurance_id`, `admit_datetime`, `discharge_datetime`, `ward_id`, `bed_number`, `doctor_id`, `status`, `created_at`) VALUES
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
  `disease_id` int(11) NOT NULL COMMENT 'รหัสโรค',
  `disease_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ชื่อโรค',
  `disease_type` enum('โรคที่มีความรุนแรงน้อยถึงปานกลาง','โรคที่มีความรุนแรงมาก') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ประเภทความรุนแรงของโรค',
  `disease_score` int(11) NOT NULL COMMENT 'คะแนนประเมินโรค'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `disease`
--

INSERT INTO `disease` (`disease_id`, `disease_name`, `disease_type`, `disease_score`) VALUES
(1, 'DM (เบาหวาน)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(2, 'CKD-ESRD (ไตเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(3, 'Septicemia (ติดเชื้อในกระแสเลือด)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(4, 'Solid cancer (มะเร็งทั่วไป)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(5, 'Chronic heart failure (หัวใจล้มเหลวเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(6, 'Hip fracture (ข้อสะโพกหัก)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(7, 'COPD (ปอดอุดกั้นเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(8, 'Severe head injury (บาดเจ็บที่ศีรษะรุนแรง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(9, '>= 2 of burn (แผลไฟไหม้ระดับ 2 ขึ้นไป)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(10, 'CLD/Cirrhosis/Hepati cencaph (ตับเรื้อรัง)', 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3),
(11, 'Severe pneumonia (ปอดบวมขั้นรุนแรง)', 'โรคที่มีความรุนแรงมาก', 6),
(12, 'Critically ill (ผู้ป่วยวิกฤต)', 'โรคที่มีความรุนแรงมาก', 6),
(13, 'Multiple fracture (กระดูกหักหลายตำแหน่ง)', 'โรคที่มีความรุนแรงมาก', 6),
(14, 'Stroke/CVA (อัมพาต)', 'โรคที่มีความรุนแรงมาก', 6),
(15, 'Malignant hematologic disease/Bone marrow transplant (มะเร็งเม็ดเลือด/ปลูกถ่ายไขกระดูก)', 'โรคที่มีความรุนแรงมาก', 6);

-- --------------------------------------------------------

--
-- Table structure for table `disease_saved`
--

CREATE TABLE `disease_saved` (
  `disease_saved_id` int(11) NOT NULL COMMENT 'Primary Key',
  `nutrition_assessment_id` int(11) NOT NULL COMMENT 'เชื่อมโยงกับตารางแบบประเมิน ',
  `disease_id` int(11) DEFAULT NULL COMMENT 'รหัสโรค',
  `disease_other_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อโรคกรณีระบุเอง',
  `disease_type` varchar(50) DEFAULT NULL COMMENT 'ประเภทของโรค',
  `disease_score` int(11) DEFAULT 0 COMMENT 'คะแนน'
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
(8, 7, 2, NULL, 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', 3);

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctor_id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `doctor_specialty` varchar(100) DEFAULT NULL
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
  `food_access_id` int(11) NOT NULL COMMENT 'รหัสการเข้าถึงอาหาร',
  `food_access_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ชื่อหรือประเภทของการเข้าถึงอาหาร',
  `food_access_score` int(11) DEFAULT NULL COMMENT 'คะแนนประเมินการเข้าถึงอาหาร'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `food_access`
--

INSERT INTO `food_access` (`food_access_id`, `food_access_label`, `food_access_score`) VALUES
(1, 'นอนติดเตียง', 2),
(2, 'ต้องมีผู้ช่วยบ้าง', 1),
(3, 'นั่งๆ นอนๆ', 0),
(4, 'ปกติ', 0);

-- --------------------------------------------------------

--
-- Table structure for table `food_amount`
--

CREATE TABLE `food_amount` (
  `food_amount_id` int(11) NOT NULL COMMENT 'รหัสปริมาณอาหาร',
  `food_amount_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ชื่อหรือคำอธิบายปริมาณอาหาร',
  `food_amount_score` int(11) DEFAULT NULL COMMENT 'คะแนนประเมินปริมาณอาหาร'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `food_amount`
--

INSERT INTO `food_amount` (`food_amount_id`, `food_amount_label`, `food_amount_score`) VALUES
(1, 'กินน้อยมาก', 2),
(2, 'กินน้อยลง', 1),
(3, 'กินมากขึ้น', 0),
(4, 'กินเท่าปกติ', 0);

-- --------------------------------------------------------

--
-- Table structure for table `food_type`
--

CREATE TABLE `food_type` (
  `food_type_id` int(11) NOT NULL COMMENT 'รหัสประเภทอาหาร',
  `food_type_label` varchar(255) DEFAULT NULL COMMENT 'ชื่อหรือคำอธิบายประเภทอาหาร',
  `food_type_score` int(11) DEFAULT NULL COMMENT 'คะแนนประเมินประเภทอาหาร'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_type`
--

INSERT INTO `food_type` (`food_type_id`, `food_type_label`, `food_type_score`) VALUES
(1, 'อาหารน้ำๆ', 2),
(2, 'อาหารเหลวๆ', 2),
(3, 'อาหารนุ่มกว่าปกติ', 1),
(4, 'อาหารเหมือนปกติ', 0);

-- --------------------------------------------------------

--
-- Table structure for table `health_insurance`
--

CREATE TABLE `health_insurance` (
  `health_insurance_id` int(11) NOT NULL,
  `health_insurance_name` varchar(100) NOT NULL
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
-- Table structure for table `nutritionists`
--

CREATE TABLE `nutritionists` (
  `nut_id` int(11) NOT NULL COMMENT 'รหัสลำดับ (PK)',
  `nut_code` varchar(50) DEFAULT NULL COMMENT 'เลขใบประกอบวิชาชีพ',
  `nut_fullname` varchar(255) NOT NULL COMMENT 'ชื่อ-นามสกุล (เช่น นักโภชนาการ ใจดี)',
  `nut_gender` enum('ชาย','หญิง') NOT NULL DEFAULT 'ชาย',
  `nut_position` varchar(100) DEFAULT 'นักโภชนาการ' COMMENT 'ตำแหน่ง',
  `nut_username` varchar(100) NOT NULL COMMENT 'ชื่อผู้ใช้สำหรับ Login',
  `nut_password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน',
  `nut_email` varchar(255) DEFAULT NULL COMMENT 'อีเมล (เผื่อลืมรหัสผ่าน)',
  `nut_phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์ติดต่อ',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'สถานะ: 1=ใช้งาน, 0=ลาออก/ระงับ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutritionists`
--

INSERT INTO `nutritionists` (`nut_id`, `nut_code`, `nut_fullname`, `nut_gender`, `nut_position`, `nut_username`, `nut_password`, `nut_email`, `nut_phone`, `is_active`, `created_at`) VALUES
(1, 'DT-66099', 'เพชรลดา เชยเพ็ชร', 'หญิง', 'นักโภชนาการชำนาญการ', 'phetrada', 'phetrada2616', 'phetrada.2646@gmail.com', '0970579246', 1, '2026-01-27 03:50:53'),
(2, 'DT-66088', 'เพทาย ทดสอบระบบ', 'ชาย', 'นักโภชนาการ', 'pheythay', 'pheythay0026', 'pheythay@gmail.com', '095222345', 1, '2026-01-28 02:33:23'),
(4, 'DT-66088', 'พร้อมพร้อม ทดสอบระบบ', 'ชาย', 'นักโภชนาการ', 'porpor', 'porpor2626', 'porpor26@gmail.com', '098456879', 1, '2026-01-28 02:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_assessment`
--

CREATE TABLE `nutrition_assessment` (
  `nutrition_assessment_id` int(11) NOT NULL COMMENT 'รหัสอ้างอิงภายในระบบ',
  `doc_no` varchar(20) NOT NULL COMMENT 'เลขที่ใบประเมิน (ห้ามซ้ำ)',
  `naf_seq` int(11) DEFAULT 1 COMMENT 'ลำดับครั้งที่ประเมิน (1, 2, ...)',
  `admissions_an` varchar(20) NOT NULL COMMENT 'รหัส AN (สำคัญ: ระบุว่าประเมินรอบป่วยไหน)',
  `patients_hn` varchar(20) NOT NULL COMMENT 'รหัส HN (ระบุตัวคนไข้)',
  `nut_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ประเมิน (อ้างอิงตาราง nutritionists)',
  `assessment_datetime` datetime NOT NULL COMMENT 'วันเวลาที่ทำการประเมิน',
  `initial_diagnosis` text DEFAULT NULL COMMENT 'การวินิจฉัยโรคเบื้องต้น',
  `info_source` varchar(50) DEFAULT NULL COMMENT 'แหล่งที่มาข้อมูล (ผู้ป่วย/ญาติ/แฟ้ม)',
  `other_source` varchar(100) DEFAULT NULL COMMENT 'ระบุเพิ่มกรณีเลือกอื่นๆ',
  `height_measure` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูงที่วัดได้จริง (ซม.)',
  `body_length` decimal(5,2) DEFAULT NULL COMMENT 'ความยาวตัว (กรณีวัดส่วนสูงไม่ได้)',
  `arm_span` decimal(5,2) DEFAULT NULL COMMENT 'ความยาวช่วงแขน (Arm Span)',
  `height_relative` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูงที่คำนวณได้ (Relative Height)',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนักตัว (กก.)',
  `bmi` decimal(5,2) DEFAULT NULL COMMENT 'ค่า BMI',
  `bmi_score` int(11) DEFAULT NULL COMMENT 'คะแนนจากค่า BMI',
  `is_no_weight` tinyint(1) DEFAULT 0 COMMENT 'ชั่งน้ำหนักไม่ได้ใช่หรือไม่? (1=ใช่, 0=ไม่ใช่)',
  `lab_method` varchar(50) DEFAULT NULL COMMENT 'วิธีดูผลแล็บ (Albumin หรือ TLC)',
  `albumin_val` decimal(4,2) DEFAULT NULL COMMENT 'ค่า Albumin',
  `tlc_val` decimal(10,2) DEFAULT NULL COMMENT 'ค่า TLC (Total Lymphocyte Count)',
  `lab_score` int(11) DEFAULT NULL COMMENT 'คะแนนจากผลแล็บ',
  `weight_option_id` int(11) DEFAULT NULL COMMENT 'วิธีหาน้ำหนัก (FK)',
  `patient_shape_id` int(11) DEFAULT NULL COMMENT 'รูปร่างผู้ป่วย (FK)',
  `weight_change_4_weeks_id` int(11) DEFAULT NULL COMMENT 'การเปลี่ยนแปลงน้ำหนักใน 4 สัปดาห์ (FK)',
  `food_type_id` int(11) DEFAULT NULL COMMENT 'ลักษณะอาหารที่กิน (FK)',
  `food_amount_id` int(11) DEFAULT NULL COMMENT 'ปริมาณอาหารที่กินได้ (FK)',
  `food_access_id` int(11) DEFAULT NULL COMMENT 'การเข้าถึงอาหาร (FK)',
  `total_score` int(11) DEFAULT NULL COMMENT 'คะแนนรวมทั้งหมด (Total BNT Score)',
  `naf_level` varchar(50) DEFAULT NULL COMMENT 'ระดับความเสี่ยง (Level of Risk / NAF)',
  `ref_screening_doc_no` varchar(20) DEFAULT NULL COMMENT 'เลขที่เอกสาร SPENT ที่อ้างอิง',
  `nutrition_screening_id` int(11) DEFAULT NULL COMMENT 'ID อ้างอิงตาราง nutrition_screening',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'เวลาบันทึกลงฐานข้อมูล'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางบันทึกการประเมินภาวะโภชนาการ';

--
-- Dumping data for table `nutrition_assessment`
--

INSERT INTO `nutrition_assessment` (`nutrition_assessment_id`, `doc_no`, `naf_seq`, `admissions_an`, `patients_hn`, `nut_id`, `assessment_datetime`, `initial_diagnosis`, `info_source`, `other_source`, `height_measure`, `body_length`, `arm_span`, `height_relative`, `weight`, `bmi`, `bmi_score`, `is_no_weight`, `lab_method`, `albumin_val`, `tlc_val`, `lab_score`, `weight_option_id`, `patient_shape_id`, `weight_change_4_weeks_id`, `food_type_id`, `food_amount_id`, `food_access_id`, `total_score`, `naf_level`, `ref_screening_doc_no`, `nutrition_screening_id`, `created_at`) VALUES
(1, 'NAF-6710001-001', 1, '6701001', '6710001', 1, '2026-01-22 08:39:08', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 165.00, NULL, NULL, NULL, 45.00, 16.53, 2, 0, NULL, NULL, NULL, 0, 1, 2, 1, 3, 2, 2, 19, 'NAF C', 'SPENT-6710001-001', 1, '2026-01-22 08:39:08'),
(2, 'NAF-6780002-001', 1, '6708002', '6780002', 2, '2026-01-22 13:05:02', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 178.00, NULL, NULL, NULL, 50.00, 15.78, 2, 0, NULL, NULL, NULL, 0, 2, 2, 1, 3, 1, 1, 26, 'NAF C', 'SPENT-6780002-001', 5, '2026-01-22 13:05:02'),
(3, 'NAF-6710003-001', 1, '6701003', '6710003', 4, '2026-01-22 15:12:08', 'มะเร็งลำไส้ใหญ่', 'ผู้ป่วย', NULL, 167.00, NULL, NULL, NULL, 45.00, 16.14, 0, 0, NULL, NULL, NULL, 0, 1, 1, 1, 1, 2, 2, 16, 'NAF C', 'SPENT-6710003-001', 3, '2026-01-22 15:12:08'),
(4, 'NAF-6710004-001', 1, '6701004', '6710004', 2, '2026-01-22 15:13:27', 'ความดันโลหิตสูง', 'ผู้ป่วย', NULL, 168.00, NULL, NULL, NULL, 50.00, 17.72, 0, 0, NULL, NULL, NULL, 0, 2, 2, 1, 4, 2, 3, 6, 'NAF B', 'SPENT-6710004-001', 6, '2026-01-22 15:13:27'),
(5, 'NAF-6760001-001', 1, '6706001', '6760001', 1, '2026-01-22 15:14:43', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 158.00, NULL, NULL, NULL, 45.00, 18.03, 0, 0, NULL, NULL, NULL, 0, 2, 2, 1, 4, 4, 4, 5, 'NAF A', 'SPENT-6760001-001', 7, '2026-01-22 15:14:43'),
(6, 'NAF-6770001-001', 1, '6707001', '6770001', 1, '2026-01-28 09:01:29', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 168.00, NULL, NULL, NULL, 45.00, 15.94, 0, 0, NULL, NULL, NULL, 0, 2, 2, 1, 1, 2, 1, 17, 'NAF C', 'SPENT-6770001-001', 9, '2026-01-28 09:01:29'),
(7, 'NAF-6710005-001', 1, '6701005', '6710005', 2, '2026-01-28 10:04:34', 'อ่อนเพลีย น้ำหนักลด', 'ผู้ป่วย', NULL, 170.00, NULL, NULL, NULL, 40.00, 13.84, 0, 0, NULL, NULL, NULL, 0, 2, 1, 2, 1, 2, 1, 15, 'NAF C', 'SPENT-6710005-001', 10, '2026-01-28 10:04:34');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_screening`
--

CREATE TABLE `nutrition_screening` (
  `nutrition_screening_id` int(11) NOT NULL COMMENT 'รหัสลำดับการคัดกรอง',
  `doc_no` varchar(20) NOT NULL COMMENT 'เลขที่เอกสารการคัดกรอง',
  `admissions_an` varchar(20) NOT NULL COMMENT 'รหัส AN (เชื่อมโยงการแอดมิท)',
  `patients_hn` varchar(20) NOT NULL COMMENT 'รหัส HN (เชื่อมโยงผู้ป่วย)',
  `nut_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ประเมิน (อ้างอิงตาราง nutritionists)',
  `screening_datetime` datetime NOT NULL COMMENT 'วันเวลาที่ทำการคัดกรอง',
  `screening_seq` int(11) DEFAULT 1 COMMENT 'ครั้งที่คัดกรอง (เช่น ครั้งที่ 1, 2, 3...)',
  `initial_diagnosis` varchar(255) DEFAULT NULL COMMENT 'การวินิจฉัยโรคเบื้องต้น',
  `present_weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนักปัจจุบัน (กก.)',
  `normal_weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนักปกติ (กก.)',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูง (ซม.)',
  `bmi` decimal(5,2) DEFAULT NULL COMMENT 'ค่าดัชนีมวลกาย',
  `weight_method` varchar(100) DEFAULT NULL COMMENT 'วิธีการชั่งน้ำหนัก (ชั่งจริง/ซักถาม/กะประมาณ)',
  `q1_weight_loss` int(11) DEFAULT NULL COMMENT 'คะแนน Q1: น้ำหนักลดลงหรือไม่',
  `q2_eat_less` int(11) DEFAULT NULL COMMENT 'คะแนน Q2: กินได้น้อยลงหรือไม่',
  `q3_bmi_abnormal` int(11) DEFAULT NULL COMMENT 'คะแนน Q3: BMI ผิดปกติหรือไม่',
  `q4_critical` int(11) DEFAULT NULL COMMENT 'คะแนน Q4: มีภาวะวิกฤตหรือไม่',
  `screening_result` varchar(50) DEFAULT NULL COMMENT 'ผลการคัดกรอง (เช่น ปกติ, มีความเเสี่ยง)',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุเพิ่มเติม',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'เวลาที่บันทึกลงระบบ',
  `screening_status` varchar(50) DEFAULT NULL COMMENT 'สถานะ (ปกติ, มีความเสี่ยง, รอทำแบบประเมิน, ประเมินต่อแล้ว)',
  `has_assessment` tinyint(1) DEFAULT 0 COMMENT 'มีการประเมิน NAF หรือไม่ (0=ไม่มี, 1=มี)',
  `assessment_doc_no` varchar(20) DEFAULT NULL COMMENT 'เลขที่เอกสาร NAF ที่เชื่อมโยง (Update ภายหลัง)',
  `assessment_datetime` datetime DEFAULT NULL COMMENT 'วันเวลาที่ทำการประเมิน NAF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางประวัติการคัดกรองภาวะโภชนาการ (Screening)';

--
-- Dumping data for table `nutrition_screening`
--

INSERT INTO `nutrition_screening` (`nutrition_screening_id`, `doc_no`, `admissions_an`, `patients_hn`, `nut_id`, `screening_datetime`, `screening_seq`, `initial_diagnosis`, `present_weight`, `normal_weight`, `height`, `bmi`, `weight_method`, `q1_weight_loss`, `q2_eat_less`, `q3_bmi_abnormal`, `q4_critical`, `screening_result`, `notes`, `created_at`, `screening_status`, `has_assessment`, `assessment_doc_no`, `assessment_datetime`) VALUES
(1, 'SPENT-6710001-001', '6701001', '6710001', 1, '2026-01-22 08:37:59', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 165.00, 16.53, 'ซักถาม', 0, 1, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 08:37:59', 'ประเมินต่อแล้ว', 1, 'NAF-6710001-001', NULL),
(2, 'SPENT-6710002-001', '6701002', '6710002', 1, '2026-01-22 08:59:14', 1, 'ความดันโลหิตสูง', 50.00, 55.00, 158.00, 20.03, 'ชั่งจริง', 0, 0, 0, 0, 'ปกติ', '', '2026-01-22 08:59:14', 'ปกติ', 0, NULL, NULL),
(3, 'SPENT-6710003-001', '6701003', '6710003', 2, '2026-01-22 08:59:44', 1, 'มะเร็งลำไส้ใหญ่', 45.00, 50.00, 167.00, 16.14, 'กะประมาณ', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 08:59:44', 'ประเมินต่อแล้ว', 1, 'NAF-6710003-001', NULL),
(4, 'SPENT-6780001-001', '6708001', '6780001', 2, '2026-01-22 13:02:42', 1, 'Osteoarthritis, knee (ข้อเข่าเสื่อม)', 45.00, 56.00, 170.00, 15.57, 'ชั่งจริง', 1, 1, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 13:02:42', 'รอทำแบบประเมิน', 0, NULL, NULL),
(5, 'SPENT-6780002-001', '6708002', '6780002', 1, '2026-01-22 13:04:36', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 178.00, 15.78, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 13:04:36', 'ประเมินต่อแล้ว', 1, 'NAF-6780002-001', NULL),
(6, 'SPENT-6710004-001', '6701004', '6710004', 1, '2026-01-22 15:12:56', 1, 'ความดันโลหิตสูง', 50.00, 55.00, 168.00, 17.72, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 15:12:56', 'ประเมินต่อแล้ว', 1, 'NAF-6710004-001', NULL),
(7, 'SPENT-6760001-001', '6706001', '6760001', 4, '2026-01-22 15:14:15', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 55.00, 158.00, 18.03, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-22 15:14:15', 'ประเมินต่อแล้ว', 1, 'NAF-6760001-001', NULL),
(8, 'SPENT-6770002-001', '6707002', '6770002', 2, '2026-01-26 10:52:21', 1, 'อ่อนเพลีย น้ำหนักลด', 50.00, 55.00, 157.00, 20.28, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-01-26 10:52:21', 'ปกติ', 0, NULL, NULL),
(9, 'SPENT-6770001-001', '6707001', '6770001', 1, '2026-01-28 09:01:11', 1, 'อ่อนเพลีย น้ำหนักลด', 45.00, 50.00, 168.00, 15.94, 'ชั่งจริง', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-28 09:01:11', 'ประเมินต่อแล้ว', 1, 'NAF-6770001-001', NULL),
(10, 'SPENT-6710005-001', '6701005', '6710005', 2, '2026-01-28 09:41:50', 1, 'อ่อนเพลีย น้ำหนักลด', 40.00, 55.00, 170.00, 13.84, 'ซักถาม', 1, 0, 1, 0, 'มีความเสี่ยง', '', '2026-01-28 09:41:50', 'ประเมินต่อแล้ว', 1, 'NAF-6710005-001', NULL),
(11, 'SPENT-6730001-001', '6703001', '6730001', 2, '2026-01-28 10:19:05', 1, 'ความดันโลหิตสูง', 50.00, 55.00, 158.00, 20.03, 'ชั่งจริง', 1, 0, 0, 0, 'ปกติ', '', '2026-01-28 10:19:05', 'ปกติ', 0, NULL, NULL),
(12, 'SPENT-6740001-001', '6704001', '6740001', 2, '2026-01-28 10:19:24', 1, 'ความดันโลหิตสูง', 45.00, 50.00, 165.00, 16.53, 'ซักถาม', 0, 0, 1, 0, 'ปกติ', '', '2026-01-28 10:19:24', 'ปกติ', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patients_id` int(11) NOT NULL,
  `patients_hn` varchar(20) NOT NULL,
  `patients_id_card` varchar(13) DEFAULT NULL,
  `patients_firstname` varchar(100) NOT NULL,
  `patients_lastname` varchar(100) NOT NULL,
  `patients_gender` enum('ชาย','หญิง') DEFAULT NULL,
  `patients_dob` date DEFAULT NULL,
  `patients_phone` varchar(20) DEFAULT NULL,
  `patients_drug_allergy` text DEFAULT NULL,
  `patients_congenital_disease` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patients_id`, `patients_hn`, `patients_id_card`, `patients_firstname`, `patients_lastname`, `patients_gender`, `patients_dob`, `patients_phone`, `patients_drug_allergy`, `patients_congenital_disease`) VALUES
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
  `patient_shape_id` int(11) NOT NULL,
  `patient_shape_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `patient_shape_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `patient_shape`
--

INSERT INTO `patient_shape` (`patient_shape_id`, `patient_shape_label`, `patient_shape_score`) VALUES
(1, 'ผอมมาก', 2),
(2, 'ผอม', 1),
(3, 'อ้วนมาก', 1),
(4, 'ปกติ-อ้วนปานกลาง', 0);

-- --------------------------------------------------------

--
-- Table structure for table `symptom_problem`
--

CREATE TABLE `symptom_problem` (
  `symptom_problem_id` int(11) NOT NULL,
  `symptom_problem_name` varchar(255) DEFAULT NULL,
  `symptom_problem_type` varchar(255) DEFAULT NULL,
  `symptom_problem_score` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptom_problem`
--

INSERT INTO `symptom_problem` (`symptom_problem_id`, `symptom_problem_name`, `symptom_problem_type`, `symptom_problem_score`) VALUES
(1, 'สำลัก', 'ปัญหาทางการเคี้ยว/กลืนอาหาร', 2),
(2, 'เคี้ยว/กลืนลำบาก/ได้อาหารทางสายยาง', 'ปัญหาทางการเคี้ยว/กลืนอาหาร', 2),
(3, 'กลืนได้ปกติ', 'ปัญหาทางการเคี้ยว/กลืนอาหาร', 0),
(4, 'ท้องเสีย', 'ปัญหาระบบทางเดินอาหาร', 2),
(5, 'ปวดท้อง', 'ปัญหาระบบทางเดินอาหาร', 2),
(6, 'ปกติ', 'ปัญหาระบบทางเดินอาหาร', 0),
(7, 'อาเจียน', 'ปัญหาระหว่างกินอาหาร', 2),
(8, 'คลื่นไส้', 'ปัญหาระหว่างกินอาหาร', 2),
(9, 'ปกติ', 'ปัญหาระหว่างกินอาหาร', 0);

-- --------------------------------------------------------

--
-- Table structure for table `symptom_problem_saved`
--

CREATE TABLE `symptom_problem_saved` (
  `symptom_problem_saved_id` int(11) NOT NULL COMMENT 'Primary Key',
  `nutrition_assessment_id` int(11) NOT NULL COMMENT 'เชื่อมโยงกับตารางแบบประเมิน',
  `symptom_problem_id` int(11) NOT NULL COMMENT 'รหัสอาการ',
  `symptom_problem_score` int(11) DEFAULT 0 COMMENT 'คะแนน'
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
(21, 7, 7, 2);

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `ward_id` int(11) NOT NULL,
  `ward_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`ward_id`, `ward_name`) VALUES
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
  `weight_change_4_weeks_id` int(11) NOT NULL,
  `weight_change_4_weeks_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight_change_4_weeks_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `weight_change_4_weeks`
--

INSERT INTO `weight_change_4_weeks` (`weight_change_4_weeks_id`, `weight_change_4_weeks_label`, `weight_change_4_weeks_score`) VALUES
(1, 'ลดลง / ผอมลง', 2),
(2, 'เพิ่มขึ้น / อ้วนขึ้น', 1),
(3, 'ไม่ทราบ', 0),
(4, 'คงเดิม', 0);

-- --------------------------------------------------------

--
-- Table structure for table `weight_option`
--

CREATE TABLE `weight_option` (
  `weight_option_id` int(11) NOT NULL,
  `weight_option_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight_option_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `weight_option`
--

INSERT INTO `weight_option` (`weight_option_id`, `weight_option_label`, `weight_option_score`) VALUES
(1, 'ชั่งในท่านอน', 1),
(2, 'ชั่งในท่ายืน', 0),
(3, 'ชั่งไม่ได้', 0),
(4, 'ญาติบอก', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`admissions_id`),
  ADD UNIQUE KEY `admissions_an` (`admissions_an`),
  ADD KEY `patients_id` (`patients_id`),
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
  ADD KEY `idx_nutrition_assessment_id` (`nutrition_assessment_id`);

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
-- Indexes for table `nutritionists`
--
ALTER TABLE `nutritionists`
  ADD PRIMARY KEY (`nut_id`),
  ADD UNIQUE KEY `unique_username` (`nut_username`);

--
-- Indexes for table `nutrition_assessment`
--
ALTER TABLE `nutrition_assessment`
  ADD PRIMARY KEY (`nutrition_assessment_id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `admissions_an` (`admissions_an`),
  ADD KEY `patients_hn` (`patients_hn`),
  ADD KEY `fk_na_screening` (`nutrition_screening_id`),
  ADD KEY `fk_na_weight_opt` (`weight_option_id`),
  ADD KEY `fk_na_weight_chg` (`weight_change_4_weeks_id`),
  ADD KEY `fk_na_food_amt` (`food_amount_id`),
  ADD KEY `fk_na_shape` (`patient_shape_id`),
  ADD KEY `fk_na_food_type` (`food_type_id`),
  ADD KEY `fk_na_food_acc` (`food_access_id`),
  ADD KEY `fk_assessment_nutritionist` (`nut_id`);

--
-- Indexes for table `nutrition_screening`
--
ALTER TABLE `nutrition_screening`
  ADD PRIMARY KEY (`nutrition_screening_id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `admissions_an` (`admissions_an`),
  ADD KEY `patients_hn` (`patients_hn`),
  ADD KEY `fk_screening_nutritionist` (`nut_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patients_id`),
  ADD UNIQUE KEY `patients_hn` (`patients_hn`);

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
  ADD KEY `idx_nutrition_assessment_id` (`nutrition_assessment_id`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
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
  MODIFY `admissions_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสอ้างอิงภายในระบบ', AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `disease`
--
ALTER TABLE `disease`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสโรค', AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `disease_saved`
--
ALTER TABLE `disease_saved`
  MODIFY `disease_saved_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key', AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `food_access`
--
ALTER TABLE `food_access`
  MODIFY `food_access_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสการเข้าถึงอาหาร', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `food_amount`
--
ALTER TABLE `food_amount`
  MODIFY `food_amount_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสปริมาณอาหาร', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `food_type`
--
ALTER TABLE `food_type`
  MODIFY `food_type_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสประเภทอาหาร', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `health_insurance`
--
ALTER TABLE `health_insurance`
  MODIFY `health_insurance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `nutritionists`
--
ALTER TABLE `nutritionists`
  MODIFY `nut_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับ (PK)', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `nutrition_assessment`
--
ALTER TABLE `nutrition_assessment`
  MODIFY `nutrition_assessment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสอ้างอิงภายในระบบ', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `nutrition_screening`
--
ALTER TABLE `nutrition_screening`
  MODIFY `nutrition_screening_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับการคัดกรอง', AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patients_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `patient_shape`
--
ALTER TABLE `patient_shape`
  MODIFY `patient_shape_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `symptom_problem`
--
ALTER TABLE `symptom_problem`
  MODIFY `symptom_problem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `symptom_problem_saved`
--
ALTER TABLE `symptom_problem_saved`
  MODIFY `symptom_problem_saved_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key', AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `weight_change_4_weeks`
--
ALTER TABLE `weight_change_4_weeks`
  MODIFY `weight_change_4_weeks_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `weight_option`
--
ALTER TABLE `weight_option`
  MODIFY `weight_option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patients_id`) REFERENCES `patients` (`patients_id`),
  ADD CONSTRAINT `admissions_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`ward_id`),
  ADD CONSTRAINT `admissions_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_admissions_insurance` FOREIGN KEY (`health_insurance_id`) REFERENCES `health_insurance` (`health_insurance_id`);

--
-- Constraints for table `disease_saved`
--
ALTER TABLE `disease_saved`
  ADD CONSTRAINT `fk_disease_assessment` FOREIGN KEY (`nutrition_assessment_id`) REFERENCES `nutrition_assessment` (`nutrition_assessment_id`) ON DELETE CASCADE;

--
-- Constraints for table `nutrition_assessment`
--
ALTER TABLE `nutrition_assessment`
  ADD CONSTRAINT `fk_assessment_nutritionist` FOREIGN KEY (`nut_id`) REFERENCES `nutritionists` (`nut_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_na_food_acc` FOREIGN KEY (`food_access_id`) REFERENCES `food_access` (`food_access_id`),
  ADD CONSTRAINT `fk_na_food_amt` FOREIGN KEY (`food_amount_id`) REFERENCES `food_amount` (`food_amount_id`),
  ADD CONSTRAINT `fk_na_food_type` FOREIGN KEY (`food_type_id`) REFERENCES `food_type` (`food_type_id`),
  ADD CONSTRAINT `fk_na_screening` FOREIGN KEY (`nutrition_screening_id`) REFERENCES `nutrition_screening` (`nutrition_screening_id`),
  ADD CONSTRAINT `fk_na_shape` FOREIGN KEY (`patient_shape_id`) REFERENCES `patient_shape` (`patient_shape_id`),
  ADD CONSTRAINT `fk_na_weight_chg` FOREIGN KEY (`weight_change_4_weeks_id`) REFERENCES `weight_change_4_weeks` (`weight_change_4_weeks_id`),
  ADD CONSTRAINT `fk_na_weight_opt` FOREIGN KEY (`weight_option_id`) REFERENCES `weight_option` (`weight_option_id`),
  ADD CONSTRAINT `nutrition_assessment_ibfk_1` FOREIGN KEY (`admissions_an`) REFERENCES `admissions` (`admissions_an`),
  ADD CONSTRAINT `nutrition_assessment_ibfk_2` FOREIGN KEY (`patients_hn`) REFERENCES `patients` (`patients_hn`);

--
-- Constraints for table `nutrition_screening`
--
ALTER TABLE `nutrition_screening`
  ADD CONSTRAINT `fk_screening_nutritionist` FOREIGN KEY (`nut_id`) REFERENCES `nutritionists` (`nut_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `nutrition_screening_ibfk_1` FOREIGN KEY (`admissions_an`) REFERENCES `admissions` (`admissions_an`) ON UPDATE CASCADE,
  ADD CONSTRAINT `nutrition_screening_ibfk_2` FOREIGN KEY (`patients_hn`) REFERENCES `patients` (`patients_hn`) ON UPDATE CASCADE;

--
-- Constraints for table `symptom_problem_saved`
--
ALTER TABLE `symptom_problem_saved`
  ADD CONSTRAINT `fk_symptom_assessment` FOREIGN KEY (`nutrition_assessment_id`) REFERENCES `nutrition_assessment` (`nutrition_assessment_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
