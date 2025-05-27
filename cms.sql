-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2025 at 08:04 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_decisions`
--

CREATE TABLE `admin_decisions` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `status_decision` enum('Approved','Rejected') NOT NULL,
  `decision_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_decisions`
--

INSERT INTO `admin_decisions` (`id`, `project_id`, `admin_id`, `status_decision`, `decision_date`) VALUES
(1, 23, 1, '', '2025-05-07 14:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `comment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `report_id`, `user_id`, `comment_text`, `comment_date`) VALUES
(1, 15, 1, 'Ok, continue your job', '2025-04-16 16:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `completed_projects`
--

CREATE TABLE `completed_projects` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `completion_date` date NOT NULL,
  `completion_notes` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `submission_date` datetime NOT NULL,
  `review_date` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `completed_projects`
--

INSERT INTO `completed_projects` (`id`, `project_id`, `manager_id`, `admin_id`, `completion_date`, `completion_notes`, `attachment_path`, `submission_date`, `review_date`, `admin_notes`, `status`) VALUES
(1, 24, 2, 1, '2025-05-31', '...', NULL, '2025-05-22 23:17:46', '2025-05-22 23:44:07', 'lack of quality', 'Rejected'),
(2, 14, 2, NULL, '2025-05-10', '....', NULL, '2025-05-22 23:20:16', NULL, NULL, 'Pending'),
(3, 23, 2, 1, '2025-05-30', '...........', 'uploads/project_completions/project_23_1747946589_@Group 3 (3).docx', '2025-05-22 23:43:09', '2025-05-22 23:46:41', 'I accept it. it is good', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `daily_labor`
--

CREATE TABLE `daily_labor` (
  `labor_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `hours_worked` decimal(4,2) NOT NULL,
  `tasks_performed` text DEFAULT NULL,
  `site_engineer_id` int(11) NOT NULL,
  `laborer_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_labor`
--

INSERT INTO `daily_labor` (`labor_id`, `project_id`, `user_id`, `date`, `hours_worked`, `tasks_performed`, `site_engineer_id`, `laborer_name`) VALUES
(1, 14, 62, '2025-05-23', 0.50, 'Material transportation', 62, 'Girma');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `username`, `ip_address`, `attempt_time`, `success`) VALUES
(1, 'man', '::1', '2025-05-05 21:19:58', 0),
(2, 'man', '::1', '2025-05-05 21:20:24', 0),
(3, 'man', '::1', '2025-05-05 21:20:54', 0),
(4, 'man', '::1', '2025-05-05 21:31:37', 0),
(5, 'man', '::1', '2025-05-05 21:31:53', 0),
(6, 'man', '::1', '2025-05-10 21:48:11', 0),
(7, 'admin', '::1', '2025-05-10 21:48:29', 1),
(8, 'man', '::1', '2025-05-10 21:49:37', 1),
(9, 'admin', '::1', '2025-05-10 21:49:56', 1),
(10, 'man', '::1', '2025-05-10 21:50:20', 1),
(11, 'admin', '::1', '2025-05-10 21:52:11', 1),
(12, 'admin', '::1', '2025-05-11 09:32:00', 1),
(13, 'k.l', '::1', '2025-05-11 09:41:21', 1),
(14, 'man', '::1', '2025-05-11 09:49:42', 1),
(15, 'man', '::1', '2025-05-11 09:51:20', 0),
(16, 'man', '::1', '2025-05-11 09:51:29', 1),
(17, 'emp', '::1', '2025-05-11 10:05:07', 1),
(18, 'man', '::1', '2025-05-11 10:12:36', 1),
(19, 'cont', '::1', '2025-05-11 10:29:36', 1),
(20, 'cons', '::1', '2025-05-11 10:42:04', 0),
(21, 'cons', '::1', '2025-05-11 10:42:13', 1),
(22, 'eng', '::1', '2025-05-11 10:42:47', 0),
(23, 'Yosan', '::1', '2025-05-11 10:42:59', 1),
(24, 'emp', '::1', '2025-05-11 10:53:35', 1),
(25, 'admin', '::1', '2025-05-11 13:41:33', 1),
(26, 'man', '::1', '2025-05-11 13:57:22', 1),
(27, 'admin', '::1', '2025-05-11 14:08:00', 1),
(28, 'admin', '::1', '2025-05-11 14:42:49', 1),
(29, 'man', '::1', '2025-05-11 14:50:38', 1),
(30, 'admin', '::1', '2025-05-11 14:51:56', 1),
(31, 'man', '::1', '2025-05-11 15:51:32', 1),
(32, 'man', '::1', '2025-05-11 15:55:45', 1),
(33, 'man', '::1', '2025-05-11 17:09:24', 1),
(34, 'admin', '::1', '2025-05-11 17:19:36', 1),
(35, 'cont', '::1', '2025-05-11 17:20:14', 1),
(36, 'man', '::1', '2025-05-11 18:34:00', 1),
(37, 'cons', '::1', '2025-05-11 18:47:11', 1),
(38, 'cont', '::1', '2025-05-11 19:31:51', 1),
(39, 'cons', '::1', '2025-05-11 19:32:31', 1),
(40, 'admin', '::1', '2025-05-11 19:33:47', 1),
(41, 'man', '::1', '2025-05-11 19:34:31', 0),
(42, 'man', '::1', '2025-05-11 19:34:48', 0),
(43, 'man', '::1', '2025-05-11 19:35:17', 1),
(44, 'cont', '::1', '2025-05-11 19:37:53', 1),
(45, 'cons', '::1', '2025-05-11 19:38:11', 1),
(46, 'cons', '::1', '2025-05-11 19:41:55', 1),
(47, 'cons', '::1', '2025-05-11 19:45:55', 1),
(48, 'cons', '::1', '2025-05-11 19:54:10', 1),
(49, 'admin', '::1', '2025-05-11 19:57:13', 1),
(50, 'Yosan', '::1', '2025-05-11 20:02:04', 1),
(51, 'emp', '::1', '2025-05-11 20:12:11', 1),
(52, 'Yosan', '::1', '2025-05-11 20:13:32', 1),
(53, 'man', '::1', '2025-05-11 20:21:06', 1),
(54, 'cont', '::1', '2025-05-11 20:21:58', 1),
(55, 'admin', '::1', '2025-05-11 20:32:36', 1),
(56, 'sol.tufa', '::1', '2025-05-11 21:42:33', 1),
(57, 'man', '::1', '2025-05-11 21:47:09', 1),
(58, 'admin', '::1', '2025-05-12 08:34:41', 1),
(59, 'man', '::1', '2025-05-12 08:35:55', 1),
(60, 'admin', '::1', '2025-05-12 08:41:02', 0),
(61, 'admin', '::1', '2025-05-12 08:41:13', 0),
(62, 'admin', '::1', '2025-05-12 08:41:26', 1),
(63, 'man', '::1', '2025-05-12 08:42:10', 1),
(64, 'admin', '::1', '2025-05-12 08:42:32', 0),
(65, 'admin', '::1', '2025-05-12 08:42:46', 1),
(66, 'cont', '::1', '2025-05-12 08:43:16', 1),
(67, 'cont', '::1', '2025-05-12 08:43:44', 1),
(68, 'cons', '::1', '2025-05-12 08:44:07', 1),
(69, 'eng', '::1', '2025-05-12 08:44:27', 0),
(70, 'eng', '::1', '2025-05-12 08:44:35', 0),
(71, 'Yosan', '::1', '2025-05-12 08:44:49', 1),
(72, 'emp', '::1', '2025-05-12 08:45:17', 1),
(73, 'man', '::1', '2025-05-12 08:46:14', 1),
(74, 'man', '::1', '2025-05-12 08:52:49', 1),
(75, 'man', '::1', '2025-05-12 08:54:32', 0),
(76, 'man', '::1', '2025-05-12 08:54:45', 1),
(77, 'admin', '::1', '2025-05-12 08:56:29', 1),
(78, 'admin', '::1', '2025-05-12 09:01:58', 1),
(79, 'admin', '::1', '2025-05-12 22:32:42', 1),
(80, 'cont', '::1', '2025-05-12 22:33:31', 1),
(81, 'man', '::1', '2025-05-12 22:38:05', 0),
(82, 'man', '::1', '2025-05-12 22:38:18', 0),
(83, 'Man', '::1', '2025-05-12 22:38:31', 1),
(84, 'man', '::1', '2025-05-12 22:47:06', 1),
(85, 'admin', '::1', '2025-05-12 22:47:34', 1),
(86, 'admin', '::1', '2025-05-12 22:51:55', 1),
(87, 'man', '::1', '2025-05-12 23:03:26', 1),
(88, 'cont', '::1', '2025-05-12 23:14:16', 1),
(89, 'man', '::1', '2025-05-12 23:18:02', 1),
(90, 'cont', '::1', '2025-05-12 23:18:21', 1),
(91, 'Babi', '::1', '2025-05-12 23:49:41', 1),
(92, 'cons', '::1', '2025-05-12 23:55:11', 1),
(93, 'man', '::1', '2025-05-12 23:56:56', 1),
(94, 'admin', '::1', '2025-05-12 23:58:04', 1),
(95, 'bikiltu.tesfa', '::1', '2025-05-13 00:00:18', 1),
(96, 'admin', '::1', '2025-05-13 00:02:34', 1),
(97, 'man', '::1', '2025-05-13 08:54:34', 0),
(98, 'man', '::1', '2025-05-13 08:54:46', 1),
(99, 'man', '::1', '2025-05-13 08:59:58', 1),
(100, 'admin', '::1', '2025-05-13 09:00:50', 1),
(101, 'man', '::1', '2025-05-13 09:21:10', 1),
(102, 'man', '::1', '2025-05-13 09:23:00', 0),
(103, 'man', '::1', '2025-05-13 09:23:12', 1),
(104, 'lll.bb', '::1', '2025-05-13 09:55:59', 1),
(105, 'lll.bb', '::1', '2025-05-13 09:56:47', 1),
(106, 'admin', '::1', '2025-05-13 09:57:12', 0),
(107, 'lll.bb', '::1', '2025-05-13 09:57:29', 1),
(108, 'chala.tola', '::1', '2025-05-13 10:00:03', 1),
(109, 'lll.bb', '::1', '2025-05-13 10:11:07', 1),
(110, 'lll.bb', '::1', '2025-05-13 10:12:44', 1),
(111, 'admin', '::1', '2025-05-13 10:25:26', 1),
(112, 'admin', '::1', '2025-05-13 10:38:26', 1),
(113, 'tame.mulata', '::1', '2025-05-13 10:41:20', 1),
(114, 'admin', '::1', '2025-05-13 10:41:46', 1),
(115, 'admin', '::1', '2025-05-13 10:43:10', 1),
(116, 'man', '::1', '2025-05-13 10:46:54', 1),
(117, 'admin', '::1', '2025-05-13 10:47:14', 1),
(118, 'kasish.gosh', '::1', '2025-05-13 10:48:05', 1),
(119, 'Yosan', '::1', '2025-05-13 10:56:50', 1),
(120, 'admin', '::1', '2025-05-13 11:01:44', 1),
(121, 'man', '::1', '2025-05-13 11:05:22', 1),
(122, 'man', '::1', '2025-05-13 11:08:23', 1),
(123, 'man', '::1', '2025-05-13 11:09:55', 0),
(124, 'man', '::1', '2025-05-13 11:10:20', 1),
(125, 'cont', '::1', '2025-05-13 11:10:39', 1),
(126, 'cons', '::1', '2025-05-13 11:11:53', 1),
(127, 'admin', '::1', '2025-05-13 11:21:40', 1),
(128, 'admin', '::1', '2025-05-18 06:29:20', 1),
(129, 'cont', '::1', '2025-05-18 06:29:42', 1),
(130, 'cons', '::1', '2025-05-18 06:31:06', 1),
(131, 'Yosan', '::1', '2025-05-18 06:32:05', 1),
(132, 'EFA', '::1', '2025-05-18 06:33:40', 1),
(133, 'cons', '::1', '2025-05-18 06:35:01', 1),
(134, 'cont', '::1', '2025-05-18 06:35:19', 1),
(135, 'admin', '::1', '2025-05-18 06:39:28', 1),
(136, 'admin', '::1', '2025-05-18 18:57:44', 1),
(137, 'cons', '::1', '2025-05-18 18:59:38', 1),
(138, 'man', '::1', '2025-05-18 19:00:43', 0),
(139, 'man', '::1', '2025-05-18 19:00:57', 0),
(140, 'man', '::1', '2025-05-18 19:01:12', 0),
(141, 'man', '::1', '2025-05-18 19:01:28', 0),
(142, 'man', '::1', '2025-05-18 19:01:48', 0),
(143, 'man', '::1', '2025-05-18 19:02:16', 0),
(144, 'man', '::1', '2025-05-18 19:02:39', 0),
(145, 'man', '::1', '2025-05-18 19:03:02', 0),
(146, 'man', '::1', '2025-05-18 19:06:04', 1),
(147, 'admin', '::1', '2025-05-18 19:09:38', 1),
(148, 'admin', '::1', '2025-05-18 19:12:04', 0),
(149, 'admin', '::1', '2025-05-18 19:12:14', 1),
(150, 'oli.fituma', '::1', '2025-05-18 19:13:48', 1),
(151, 'man', '::1', '2025-05-18 19:14:53', 1),
(152, 'man', '::1', '2025-05-18 19:15:57', 1),
(153, 'man', '::1', '2025-05-18 19:18:16', 1),
(154, 'Oli.fituma', '::1', '2025-05-18 19:20:57', 1),
(155, 'admin', '::1', '2025-05-18 20:42:52', 1),
(156, 'abe.kal', '::1', '2025-05-18 20:43:56', 1),
(157, 'admin', '::1', '2025-05-21 19:10:28', 1),
(158, 'man', '::1', '2025-05-21 19:12:47', 1),
(159, 'admin', '::1', '2025-05-21 19:13:29', 1),
(160, 'admin', '::1', '2025-05-21 19:16:26', 1),
(161, 'cont', '::1', '2025-05-21 19:19:27', 1),
(162, 'rtesfa_614', '::1', '2025-05-21 19:23:08', 1),
(163, 'admin', '::1', '2025-05-21 19:30:01', 1),
(164, 'man', '::1', '2025-05-21 19:34:04', 0),
(165, 'man', '::1', '2025-05-21 19:34:18', 1),
(166, 'cons', '::1', '2025-05-21 19:35:08', 1),
(167, 'man', '::1', '2025-05-21 19:40:02', 1),
(168, 'ab.c', '::1', '2025-05-21 19:41:47', 1),
(169, 'admin', '::1', '2025-05-21 19:53:53', 1),
(170, 'bila.bire', '::1', '2025-05-21 19:59:27', 1),
(171, 'Yosan', '::1', '2025-05-21 20:05:12', 1),
(172, 'Yosan', '::1', '2025-05-21 20:12:14', 1),
(173, 'admin', '::1', '2025-05-22 08:43:28', 1),
(174, 'admin', '::1', '2025-05-22 08:43:46', 1),
(175, 'man', '::1', '2025-05-22 08:54:52', 1),
(176, 'admin', '::1', '2025-05-22 19:45:49', 1),
(177, 'admin', '::1', '2025-05-22 20:14:54', 1),
(178, 'man', '::1', '2025-05-22 21:09:03', 1),
(179, 'cont', '::1', '2025-05-22 21:23:24', 1),
(180, 'man', '::1', '2025-05-22 21:42:27', 1),
(181, 'dame.dego1', '::1', '2025-05-22 21:44:14', 0),
(182, 'dame.dego1', '::1', '2025-05-22 21:44:34', 1),
(183, 'cont', '::1', '2025-05-22 22:58:09', 1),
(184, 'man', '::1', '2025-05-22 23:00:10', 1),
(185, 'Yosan', '::1', '2025-05-22 23:00:56', 1),
(186, 'man', '::1', '2025-05-22 23:04:08', 1),
(187, 'man', '::1', '2025-05-22 23:10:49', 1),
(188, 'zed.kuma', '::1', '2025-05-23 00:11:33', 1),
(189, 'man', '::1', '2025-05-23 00:56:28', 1),
(190, 'admin', '::1', '2025-05-23 01:47:31', 1),
(191, 'admin', '::1', '2025-05-23 02:16:06', 1),
(192, 'cont', '::1', '2025-05-23 02:17:58', 1),
(193, 'cons', '::1', '2025-05-23 02:19:41', 1),
(194, 'cont', '::1', '2025-05-23 02:20:10', 1),
(195, 'Yosan', '::1', '2025-05-23 02:21:09', 0),
(196, 'Yosan', '::1', '2025-05-23 02:21:24', 1),
(197, 'admin', '::1', '2025-05-23 08:12:55', 1),
(198, 'man', '::1', '2025-05-23 08:14:07', 1),
(199, 'Yosan', '::1', '2025-05-23 08:15:42', 1),
(200, 'Yosan', '::1', '2025-05-23 08:29:59', 1),
(201, 'EFA', '::1', '2025-05-23 08:40:25', 1),
(202, 'cont', '::1', '2025-05-23 08:53:51', 1),
(203, 'man', '::1', '2025-05-23 10:05:02', 1),
(204, 'admin', '::1', '2025-05-23 10:42:23', 1),
(205, 'manjus.abebe', '::1', '2025-05-23 10:45:42', 1),
(206, 'fita.feyo', '::1', '2025-05-23 10:50:41', 1),
(207, 'kebe.buze', '::1', '2025-05-23 11:27:52', 1),
(208, 'engu.dure', '::1', '2025-05-23 11:45:04', 1),
(209, 'galme.rebo', '::1', '2025-05-23 12:03:48', 1),
(210, 'mule.tame', '::1', '2025-05-23 12:07:20', 1),
(211, 'fita.feyo', '::1', '2025-05-23 12:42:01', 1),
(212, 'bnego_255', '::1', '2025-05-23 12:49:21', 1),
(213, 'cont', '::1', '2025-05-23 13:02:01', 1),
(214, 'cont', '::1', '2025-05-23 13:07:33', 1),
(215, 'admin', '::1', '2025-05-23 15:27:06', 1),
(216, 'man', '::1', '2025-05-23 15:28:34', 1),
(217, 'manjus.abebe', '::1', '2025-05-23 15:30:31', 1),
(218, 'fita.feyo', '::1', '2025-05-23 15:34:46', 1),
(219, 'admin', '::1', '2025-05-23 15:44:24', 1),
(220, 'admin', '::1', '2025-05-23 16:28:10', 1),
(221, 'admin', '::1', '2025-05-27 11:01:58', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:32:08'),
(2, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:32:09'),
(3, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:32:20'),
(4, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:32:22'),
(5, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:35:06'),
(6, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:35:08'),
(7, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:36:46'),
(8, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:36:47'),
(9, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:36:48'),
(10, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:45:47'),
(11, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:45:48'),
(12, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:45:54'),
(13, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:46:01'),
(14, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:46:03'),
(15, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:55:59'),
(16, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:56:00'),
(17, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:56:17'),
(18, 103, 'You have been assigned a new project: do it', 'assignment', 1, '2025-05-18 16:56:19'),
(19, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 16:57:34'),
(20, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 16:57:43'),
(21, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 16:57:46'),
(22, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 16:57:48'),
(23, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:38'),
(24, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:43'),
(25, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:46'),
(26, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:48'),
(27, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:50'),
(28, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:52'),
(29, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:08:54'),
(30, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:13:52'),
(31, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:15:21'),
(32, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:15:23'),
(33, 103, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:40:08'),
(34, 104, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:44:25'),
(35, 104, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:44:49'),
(36, 104, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:45:09'),
(37, 104, 'You have been assigned a new project: do', 'assignment', 1, '2025-05-18 17:46:32'),
(38, 104, 'You have been assigned a new project: do', 'assignment', 0, '2025-05-18 18:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `manager_id` int(11) NOT NULL,
  `status` enum('Planning','In Progress','On Hold','Completed','Cancelled') DEFAULT 'Planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `site_engineer_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `consultant_id` int(11) DEFAULT NULL,
  `resources` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `admin_comment` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `decision_status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_name`, `description`, `start_date`, `end_date`, `budget`, `manager_id`, `status`, `created_at`, `site_engineer_id`, `employee_id`, `contractor_id`, `consultant_id`, `resources`, `file_path`, `admin_comment`, `attachment`, `decision_status`) VALUES
(1, 'Admin Block', 'This project focuses on the construction of a modern, fully-equipped university campus aimed at providing an innovative learning environment for students from various disciplines.', '2025-04-18', '2025-04-16', NULL, 2, '', '2025-04-14 11:05:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Admin Block', 'This project focuses on the construction of a modern, fully-equipped university campus aimed at providing an innovative learning environment for students from various disciplines.', '2025-04-17', '2025-05-26', NULL, 2, '', '2025-04-14 11:10:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Admin Block', 'This project focuses on the construction of a modern, fully-equipped university campus aimed at providing an innovative learning environment for students from various disciplines.', '2025-04-17', '2025-05-26', NULL, 2, '', '2025-04-14 11:16:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Admin Block', 'This project focuses on the construction of a modern, fully-equipped university campus aimed at providing an innovative learning environment for students from various disciplines.', '2025-04-17', '2025-05-26', NULL, 2, '', '2025-04-14 11:16:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Admin Block', 'This project focuses on the construction of a modern, fully-equipped university campus aimed at providing an innovative learning environment for students from various disciplines.', '2025-04-17', '2025-05-26', NULL, 2, '', '2025-04-14 11:16:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Admin Block', 'hhhhhh', '2025-04-26', '2025-04-30', NULL, 2, '', '2025-04-14 11:17:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Admin Block', 'hhhhhh', '2025-04-26', '2025-04-30', NULL, 2, '', '2025-04-14 11:19:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Admin Block', 'hhhhhh', '2025-04-26', '2025-04-30', NULL, 2, '', '2025-04-14 11:20:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Admin Block', 'hhhhhh', '2025-04-26', '2025-04-30', NULL, 2, '', '2025-04-14 11:24:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Admin Block', 'It is mandatory to build one block', '2025-04-24', '2025-05-24', NULL, 2, '', '2025-04-14 11:27:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Admin Block', 'HEY', '2025-04-14', '2025-05-14', NULL, 2, '', '2025-04-14 12:24:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Library construction', 'Shall we continue?', '2025-04-22', '2025-05-09', 200000.00, 2, '', '2025-04-22 06:57:31', NULL, NULL, NULL, NULL, NULL, NULL, 'hold it', NULL, 'Rejected'),
(13, 'Laboratory', 'do it', '2025-04-28', '2025-05-08', NULL, 2, '', '2025-04-28 13:17:52', NULL, NULL, NULL, NULL, NULL, NULL, 'stop it', NULL, 'Rejected'),
(14, 'New Library for Female Students', 'Finish it within given time', '2025-04-29', '2025-05-10', NULL, 2, 'Completed', '2025-04-29 16:37:19', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 'Approved'),
(15, 'A', '...', '2025-04-29', '2025-05-02', NULL, 2, 'Completed', '2025-04-29 16:44:53', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 'Rejected'),
(16, 'abc', '...', '2025-04-29', '2025-05-09', NULL, 2, 'In Progress', '2025-04-29 17:01:13', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 'Rejected'),
(17, 'xyz', '...', '2025-04-29', '2025-05-10', NULL, 2, '', '2025-04-29 17:05:04', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 'Approved'),
(18, 'ggg', '...', '2025-04-29', '2025-05-10', NULL, 2, '', '2025-04-29 17:08:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved'),
(19, 'ABCD', '...', '2025-04-29', '2025-05-10', NULL, 2, '', '2025-04-29 17:15:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rejected'),
(20, 'bridge', 'We try to finish it within 2 months', '2025-04-30', '2025-06-30', 100000.00, 2, '', '2025-04-29 18:28:11', NULL, NULL, NULL, NULL, 'Cement, Steel, Sand, Timber', '../uploads/2025-01-01.png', NULL, NULL, 'Approved'),
(21, 'toilet', 'do it', '2025-05-05', '2025-05-31', 6000.00, 2, '', '2025-05-05 17:04:28', NULL, NULL, NULL, NULL, 'Cement', NULL, NULL, NULL, 'Approved'),
(22, 'NEW BUILDING', '...', '2025-05-07', '2025-05-29', 100000.00, 2, '', '2025-05-07 06:22:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved'),
(23, 'aaa', 'jjj', '2025-05-07', '2025-05-30', 1000.00, 2, 'Completed', '2025-05-07 07:16:13', NULL, NULL, NULL, NULL, 'Cement', NULL, NULL, NULL, 'Approved'),
(24, 'tttttttt', NULL, '2025-05-11', '2025-05-12', NULL, 2, 'Completed', '2025-05-11 07:14:37', NULL, NULL, NULL, NULL, '', '../uploads/NLP assignment.pdf', NULL, NULL, NULL),
(25, 'BRIDGE', 'SHALL WE DO IT?', '2025-05-11', '2025-05-24', 98.00, 2, 'In Progress', '2025-05-11 18:43:11', NULL, NULL, NULL, NULL, 'Cement', NULL, '', NULL, 'Approved'),
(26, 'new building', NULL, '2025-06-13', '2025-12-11', NULL, 2, 'In Progress', '2025-05-13 07:19:08', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL),
(27, 'Admin bldg', NULL, '2025-06-21', '2025-12-27', NULL, 2, 'In Progress', '2025-05-13 07:48:38', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 'Approved'),
(28, 'New building', NULL, '2025-06-28', '2026-01-03', NULL, 113, 'In Progress', '2025-05-22 18:45:22', NULL, NULL, NULL, NULL, '', NULL, 'CONTINUE', 'uploads/project_attachments/project_1747939522_682f70c2e2571.pdf', 'Approved'),
(29, 'NEW TOILET', NULL, '2025-07-11', '2026-01-13', NULL, 113, 'In Progress', '2025-05-22 19:16:14', NULL, NULL, NULL, NULL, '', NULL, 'DO', 'uploads/project_attachments/project_1747941374_682f77fe98067.pdf', 'Approved'),
(30, 'bridge', NULL, '2025-06-30', '2025-12-27', NULL, 113, 'In Progress', '2025-05-22 19:31:23', NULL, NULL, NULL, NULL, '', NULL, 'stop', NULL, 'Rejected'),
(31, 'new', '...', '2025-06-28', '2026-01-08', NULL, 2, 'Planning', '2025-05-22 21:13:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/project_attachments/project_1747948384_682f9360a3fe8.docx', NULL),
(32, 'new project', NULL, '2025-06-25', '2026-01-07', NULL, 122, 'In Progress', '2025-05-23 07:51:59', NULL, NULL, NULL, NULL, '', NULL, 'continue', 'uploads/project_attachments/project_1747986719_6830291f1ee77.pdf', 'Approved'),
(33, 'abbde', '...', '2025-07-05', '2026-02-03', NULL, 122, 'Planning', '2025-05-23 07:54:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 'Lounge', NULL, '2025-06-27', '2027-08-24', NULL, 122, 'In Progress', '2025-05-23 08:32:20', NULL, NULL, NULL, NULL, '', NULL, '', 'uploads/project_attachments/project_1747989140_6830329428987.pdf', 'Rejected'),
(35, 'New lounge', NULL, '2025-06-28', '2026-01-03', NULL, 122, 'In Progress', '2025-05-23 08:37:07', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `assignment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `role_in_project` varchar(50) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `completion_notes` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Assigned',
  `consultant_id` int(11) NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`assignment_id`, `project_id`, `user_id`, `assigned_date`, `role_in_project`, `contractor_id`, `description`, `completion_notes`, `start_date`, `end_date`, `status`, `consultant_id`, `attachment`, `attachment_path`, `created_at`) VALUES
(10, 4, 2, '2025-04-16 18:15:11', 'Assigned Contractor', 3, 'Hello', NULL, '2025-04-16', '2025-04-26', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(11, 2, 2, '2025-04-16 18:15:39', 'Assigned Contractor', 3, 'HI Tufa', NULL, '2025-04-16', '2025-04-25', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(12, 6, 2, '2025-04-19 16:56:56', 'Assigned Contractor', 44, 'Hi, how are you?', NULL, '2025-04-19', '2025-04-26', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(13, 3, 2, '2025-04-19 17:09:32', 'Assigned Contractor', 22, 'Do it', NULL, '2025-04-19', '2025-04-26', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(14, 5, 2, '2025-04-19 17:11:44', 'Assigned Contractor', 44, 'Do', NULL, '2025-04-19', '2025-05-03', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(15, 4, 2, '2025-04-19 17:21:57', 'Assigned Contractor', 44, 'Do it Ok!', NULL, '2025-04-19', '2025-04-26', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(16, 4, 2, '2025-04-19 17:26:07', 'Assigned Contractor', 44, 'Do it Ok!', NULL, '2025-04-19', '2025-04-26', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(18, 7, 2, '2025-04-19 17:27:47', 'Assigned Contractor', 44, 'DO', NULL, '2025-04-19', '2025-05-03', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(19, 7, 2, '2025-04-22 06:53:10', 'Assigned Consultant', 40, 'Do this project', NULL, '2025-04-22', '2025-05-03', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(20, 12, 2, '2025-04-22 06:58:20', 'Assigned Contractor', 44, 'Do it', NULL, '2025-04-22', '2025-04-26', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(21, 6, 57, '2025-04-26 17:19:00', 'Assigned Site Engineer', NULL, 'Burte do it', NULL, '2025-04-26', '2025-05-10', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(22, 13, 2, '2025-04-28 13:18:46', 'Assigned Contractor', 44, 'do it', NULL, '2025-04-28', '2025-05-10', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(23, 22, 2, '2025-05-08 06:48:10', 'Assigned Contractor', 3, 'FINISH  IT IN TWO WEEKS', NULL, '2025-05-08', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(24, 22, 2, '2025-05-08 10:13:17', 'Assigned Contractor', 3, 'do it', NULL, '2025-05-08', '2025-05-29', 'Assigned', 0, NULL, '', '2025-05-18 16:32:08'),
(25, 20, 2, '2025-05-08 10:15:02', 'Assigned Contractor', 3, 'DO IT, CONTRO TUFA', NULL, '2025-05-08', '2025-06-05', 'Assigned', 0, NULL, '../uploads/assignments/1746699302_2025-01-01.png', '2025-05-18 16:32:08'),
(26, 12, 2, '2025-05-08 16:27:10', 'Assigned Consultant', 61, 'Do it Lemi', NULL, '2025-05-31', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(27, 18, 2, '2025-05-08 16:45:27', 'Assigned Consultant', 61, 'do it', NULL, '2025-05-31', '2025-06-07', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(28, 18, 2, '2025-05-08 16:45:27', 'Assigned Consultant', 61, 'do it', NULL, '2025-05-31', '2025-06-07', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(29, 18, 2, '2025-05-08 16:51:15', 'Assigned Consultant', 61, 'do it', NULL, '2025-05-31', '2025-06-07', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(30, 18, 2, '2025-05-08 16:51:15', 'Assigned Consultant', 61, 'do it', NULL, '2025-05-31', '2025-06-07', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(31, 21, 62, '2025-05-09 06:34:25', 'Assigned Site Engineer', NULL, 'Do it', NULL, '2025-05-09', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(32, 16, 2, '2025-05-13 06:55:10', 'Assigned Consultant', 95, 'do it', NULL, '2025-05-13', '2025-05-30', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(33, 16, 2, '2025-05-13 06:55:10', 'Assigned Consultant', 95, 'do it', NULL, '2025-05-13', '2025-05-30', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(34, 13, 2, '2025-05-18 16:21:25', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(35, 13, 2, '2025-05-18 16:21:25', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(36, 13, 2, '2025-05-18 16:21:39', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(37, 13, 2, '2025-05-18 16:21:39', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(38, 13, 2, '2025-05-18 16:21:41', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(39, 13, 2, '2025-05-18 16:21:41', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(40, 13, 2, '2025-05-18 16:22:55', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(41, 13, 2, '2025-05-18 16:22:55', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(42, 13, 2, '2025-05-18 16:22:56', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(43, 13, 2, '2025-05-18 16:22:56', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(44, 13, 2, '2025-05-18 16:22:58', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(45, 13, 2, '2025-05-18 16:22:58', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(46, 13, 2, '2025-05-18 16:32:08', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(47, 13, 2, '2025-05-18 16:32:08', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:08'),
(48, 13, 2, '2025-05-18 16:32:09', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:09'),
(49, 13, 2, '2025-05-18 16:32:09', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:09'),
(50, 13, 2, '2025-05-18 16:32:20', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:20'),
(51, 13, 2, '2025-05-18 16:32:20', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:20'),
(52, 13, 2, '2025-05-18 16:32:22', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:22'),
(53, 13, 2, '2025-05-18 16:32:22', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:32:22'),
(54, 13, 2, '2025-05-18 16:35:06', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:35:06'),
(55, 13, 2, '2025-05-18 16:35:06', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:35:06'),
(56, 13, 2, '2025-05-18 16:35:08', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:35:08'),
(57, 13, 2, '2025-05-18 16:35:08', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:35:08'),
(58, 13, 2, '2025-05-18 16:36:46', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:36:46'),
(59, 13, 2, '2025-05-18 16:36:46', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:36:46'),
(60, 13, 2, '2025-05-18 16:36:47', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:36:47'),
(61, 13, 2, '2025-05-18 16:36:47', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:36:47'),
(62, 13, 2, '2025-05-18 16:36:48', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:36:48'),
(63, 13, 2, '2025-05-18 16:36:48', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:36:48'),
(64, 13, 2, '2025-05-18 16:45:47', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:45:47'),
(65, 13, 2, '2025-05-18 16:45:47', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:45:47'),
(66, 13, 2, '2025-05-18 16:45:48', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:45:48'),
(67, 13, 2, '2025-05-18 16:45:48', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:45:48'),
(68, 13, 2, '2025-05-18 16:45:54', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:45:54'),
(69, 13, 2, '2025-05-18 16:45:54', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:45:54'),
(70, 13, 2, '2025-05-18 16:46:01', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:46:01'),
(71, 13, 2, '2025-05-18 16:46:01', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:46:01'),
(72, 13, 2, '2025-05-18 16:46:03', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:46:03'),
(73, 13, 2, '2025-05-18 16:46:03', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:46:03'),
(74, 13, 2, '2025-05-18 16:55:59', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:55:59'),
(75, 13, 2, '2025-05-18 16:55:59', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:55:59'),
(76, 13, 2, '2025-05-18 16:56:00', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:56:00'),
(77, 13, 2, '2025-05-18 16:56:00', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:56:00'),
(78, 13, 2, '2025-05-18 16:56:17', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:56:17'),
(79, 13, 2, '2025-05-18 16:56:17', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:56:17'),
(80, 13, 2, '2025-05-18 16:56:19', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:56:19'),
(81, 13, 2, '2025-05-18 16:56:19', 'Assigned Consultant', 103, 'do it', NULL, '2025-05-18', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 16:56:19'),
(82, 20, 2, '2025-05-18 16:57:34', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:34'),
(83, 20, 2, '2025-05-18 16:57:34', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:34'),
(84, 20, 2, '2025-05-18 16:57:43', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:43'),
(85, 20, 2, '2025-05-18 16:57:43', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:43'),
(86, 20, 2, '2025-05-18 16:57:46', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:46'),
(87, 20, 2, '2025-05-18 16:57:46', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:46'),
(88, 20, 2, '2025-05-18 16:57:48', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:48'),
(89, 20, 2, '2025-05-18 16:57:48', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 16:57:48'),
(90, 20, 2, '2025-05-18 17:08:38', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:38'),
(91, 20, 2, '2025-05-18 17:08:38', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:38'),
(92, 20, 2, '2025-05-18 17:08:43', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:43'),
(93, 20, 2, '2025-05-18 17:08:43', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:43'),
(94, 20, 2, '2025-05-18 17:08:46', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:46'),
(95, 20, 2, '2025-05-18 17:08:46', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:46'),
(96, 20, 2, '2025-05-18 17:08:48', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:48'),
(97, 20, 2, '2025-05-18 17:08:48', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:48'),
(98, 20, 2, '2025-05-18 17:08:50', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:50'),
(99, 20, 2, '2025-05-18 17:08:50', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:50'),
(100, 20, 2, '2025-05-18 17:08:52', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:52'),
(101, 20, 2, '2025-05-18 17:08:52', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:52'),
(102, 20, 2, '2025-05-18 17:08:54', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:54'),
(103, 20, 2, '2025-05-18 17:08:54', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:08:54'),
(104, 20, 2, '2025-05-18 17:13:52', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:13:52'),
(105, 20, 2, '2025-05-18 17:13:52', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:13:52'),
(106, 20, 2, '2025-05-18 17:15:21', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:15:21'),
(107, 20, 2, '2025-05-18 17:15:21', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:15:21'),
(108, 20, 2, '2025-05-18 17:15:23', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:15:23'),
(109, 20, 2, '2025-05-18 17:15:23', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:15:23'),
(110, 20, 2, '2025-05-18 17:40:08', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:40:08'),
(111, 20, 2, '2025-05-18 17:40:08', 'Assigned Consultant', 103, 'do', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, NULL, '2025-05-18 17:40:08'),
(112, 4, 2, '2025-05-18 17:44:25', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:44:25'),
(113, 4, 2, '2025-05-18 17:44:25', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:44:25'),
(114, 4, 2, '2025-05-18 17:44:49', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:44:49'),
(115, 4, 2, '2025-05-18 17:44:49', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:44:49'),
(116, 4, 2, '2025-05-18 17:45:09', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:45:09'),
(117, 4, 2, '2025-05-18 17:45:09', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:45:09'),
(118, 4, 2, '2025-05-18 17:46:32', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:46:32'),
(119, 4, 2, '2025-05-18 17:46:32', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 17:46:32'),
(120, 4, 2, '2025-05-18 18:02:46', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 18:02:46'),
(121, 4, 2, '2025-05-18 18:02:46', 'Assigned Consultant', 104, 'do', NULL, '2025-05-29', '2025-06-06', 'Assigned', 0, NULL, NULL, '2025-05-18 18:02:46'),
(122, 10, 2, '2025-05-21 16:41:10', 'Assigned Consultant', 106, '.....', NULL, '2025-05-21', '2025-05-31', 'Assigned', 0, NULL, '', '2025-05-21 16:41:10'),
(123, 14, 2, '2025-05-21 17:00:29', 'Assigned Consultant', 107, 'do it', NULL, '2025-05-30', '2025-05-31', 'Assigned', 0, NULL, '', '2025-05-21 17:00:29'),
(124, 14, 2, '2025-05-21 17:04:50', 'Assigned Site Engineer', 62, '.........', NULL, '2025-05-31', '2025-06-06', 'Assigned', 0, NULL, '../uploads/assignments/1747847090_NLP assignment.pdf', '2025-05-21 17:04:50'),
(125, 19, 2, '2025-05-21 19:02:13', 'Assigned Contractor', 33, 'do it', NULL, '0000-00-00', '0000-00-00', 'Assigned', 0, NULL, '', '2025-05-21 19:02:13'),
(126, 6, 2, '2025-05-21 19:13:38', 'Assigned Consultant', 94, 'do', NULL, '2025-04-26', '2025-04-30', 'Assigned', 0, NULL, '', '2025-05-21 19:13:38'),
(127, 23, 2, '2025-05-21 19:19:41', 'Assigned Site Engineer', 63, 'do it', NULL, '2025-05-07', '2025-05-30', 'Assigned', 0, NULL, '', '2025-05-21 19:19:41'),
(128, 32, 122, '2025-05-23 08:23:45', 'Assigned Contractor', 123, 'Do it Ok!', NULL, '2025-06-25', '2026-01-07', 'Assigned', 0, NULL, '', '2025-05-23 08:23:45'),
(129, 32, 122, '2025-05-23 08:43:46', 'Assigned Consultant', 124, 'do it Kebe', NULL, '2025-06-25', '2026-01-07', 'Assigned', 0, NULL, '', '2025-05-23 08:43:46'),
(131, 32, 122, '2025-05-23 08:53:17', 'Assigned Site Engineer', 126, '......', NULL, '2025-06-25', '2026-01-07', 'Assigned', 0, NULL, '', '2025-05-23 08:53:17');

-- --------------------------------------------------------

--
-- Table structure for table `project_comments`
--

CREATE TABLE `project_comments` (
  `comment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `comment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_comments`
--

INSERT INTO `project_comments` (`comment_id`, `project_id`, `user_id`, `comment_text`, `comment_date`) VALUES
(2, 21, 1, 'TRY TO FINISH IT ON TIME', '2025-05-07 18:32:41'),
(3, 21, 1, 'ok do it', '2025-05-07 18:44:50'),
(4, 20, 1, 'try to finish it on time', '2025-05-07 18:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `project_schedules`
--

CREATE TABLE `project_schedules` (
  `schedule_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `dependencies` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `report_type` enum('Progress','Financial','Issue') NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_content` text NOT NULL,
  `budget_status` varchar(50) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `project_id` int(11) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `file_attachment` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `created_by`, `report_type`, `title`, `content`, `created_at`, `report_content`, `budget_status`, `submitted_by`, `submitted_at`, `project_id`, `attachment`, `file_attachment`) VALUES
(2, 2, 'Progress', '', NULL, '2025-04-14 18:28:22', 'It is on track Ok', 'On Track', 0, '2025-04-14 18:28:22', NULL, NULL, NULL),
(3, 2, 'Progress', '', NULL, '2025-04-14 18:32:46', 'we will send it as soon as we finish', 'Over Budget', 0, '2025-04-14 18:32:46', NULL, NULL, NULL),
(4, 2, 'Progress', '', NULL, '2025-04-14 18:34:45', 'we will send it as soon as we finish', 'Over Budget', 0, '2025-04-14 18:34:45', NULL, NULL, NULL),
(11, 2, 'Progress', 'GOOD', 'GOOD', '2025-04-14 18:02:31', 'GOOD', 'GOOD', 2, '2025-04-14 18:02:31', NULL, NULL, NULL),
(12, 2, 'Progress', 'Mid-term Progress', 'Hi', '2025-04-16 15:26:44', '', '', 0, '2025-04-16 15:26:44', 5, NULL, NULL),
(13, 2, 'Progress', 'Mid-term Progress', 'Hi', '2025-04-16 15:30:16', '', '', 0, '2025-04-16 15:30:16', 5, NULL, NULL),
(14, 2, 'Progress', 'Telling the progress', 'it is on good status', '2025-04-16 15:30:54', '', '', 0, '2025-04-16 15:30:54', 2, NULL, NULL),
(15, 2, 'Progress', 'STATUS', 'it is good', '2025-04-16 15:33:04', '', '', 0, '2025-04-16 15:33:04', 2, NULL, NULL),
(16, 2, '', 'Finished Budget', 'please add some Dollar😎', '2025-04-16 18:30:57', '', '', 0, '2025-04-16 18:30:57', 8, NULL, NULL),
(17, 3, 'Progress', 'Status', 'it is on good status', '2025-04-16 18:38:11', '', '', 0, '2025-04-16 18:38:11', 4, NULL, NULL),
(18, 3, 'Issue', 'Fire damage', 'our materials were completely damaged', '2025-04-16 19:00:38', '', '', 0, '2025-04-16 19:00:38', 2, NULL, NULL),
(19, 2, '', 'Budget', 'We want additional budget', '2025-04-16 20:28:12', '', '', 0, '2025-04-16 20:28:12', 2, '../uploads/reports/1744835292_2025-02-13.png', NULL),
(20, 2, '', 'Budget', 'send some additional money', '2025-04-17 05:43:10', '', '', 0, '2025-04-17 05:43:10', 4, '../uploads/reports/1744868590_2025-02-13.png', NULL),
(21, 40, 'Issue', 'deficiency of budget', '...', '2025-04-22 07:32:11', '', '', 0, '2025-04-22 07:32:11', 7, NULL, NULL),
(22, 57, '', 'not completed', 'ok', '2025-04-26 17:34:49', '', '', 0, '2025-04-26 17:34:49', 6, NULL, NULL),
(23, 57, 'Issue', 'C', 'C', '2025-04-26 17:38:38', '', '', 0, '2025-04-26 17:38:38', 6, NULL, NULL),
(24, 57, '', 'bbb', 'vvvv', '2025-04-26 17:41:30', '', '', 0, '2025-04-26 17:41:30', 6, NULL, NULL),
(25, 3, '', 'Completed', 'it is already completed', '2025-05-08 11:17:44', '', '', 0, '2025-05-08 11:17:44', 22, NULL, NULL),
(26, 61, '', 'Completed', 'it is already completed', '2025-05-08 16:51:04', '', '', 0, '2025-05-08 16:51:04', 18, NULL, NULL),
(27, 62, '', 'Completed', 'we do all thing', '2025-05-09 07:31:32', '', '', 0, '2025-05-09 07:31:32', 21, '', NULL),
(28, 2, '', 'hh', '........', '2025-05-11 07:00:23', '', '', 0, '2025-05-11 07:00:23', 19, '../uploads/reports/1746946823_NLP_2018_Highlights.pdf', NULL),
(29, 106, 'Progress', 'abc', '........', '2025-05-21 16:42:21', '', '', 0, '2025-05-21 16:42:21', 10, NULL, NULL),
(30, 62, '', 'FINISHED', 'DO IT', '2025-05-21 17:45:05', '', '', 0, '2025-05-21 17:45:05', 21, '', NULL),
(33, 3, '', 'completed', '.......', '2025-05-22 18:59:08', '', '', 0, '2025-05-22 19:59:08', 22, NULL, 'uploads/contractor_reports/1747943948_3_NLP assignment.pdf'),
(34, 62, '', 'finished', '...', '2025-05-22 20:01:34', '', '', 0, '2025-05-22 20:01:34', 21, '../uploads/reports/site_engineer_report_62_1747944094.pdf', NULL),
(35, 62, '', 'finished', '.....', '2025-05-22 19:02:48', '', '', 0, '2025-05-22 20:02:48', 14, NULL, 'uploads/site_engineer_reports/1747944168_62_NLP assignment.pdf'),
(36, 2, '', 'Budget deficiency', '....', '2025-05-22 20:48:49', '', '', 0, '2025-05-22 20:48:49', 21, '../uploads/reports/1747946929_@Group 3 (2).docx', NULL),
(37, 123, 'Progress', 'Good Status', '....', '2025-05-23 08:45:49', '', '', 0, '2025-05-23 09:45:49', 32, NULL, 'uploads/contractor_reports/1747993549_123_@Project title(1).pdf');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(3, 'Admin'),
(15, 'Consultant'),
(1, 'Contractor'),
(17, 'Employee'),
(2, 'Project Manager'),
(16, 'Site Engineer');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','Blocked') DEFAULT 'Not Started',
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `project_id`, `task_name`, `description`, `assigned_to`, `assigned_by`, `start_date`, `end_date`, `status`, `employee_id`) VALUES
(3, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(4, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(5, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(6, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(7, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(8, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(9, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(10, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(11, 22, 'Debris Cleanup', 'do it', 60, 3, NULL, NULL, 'Not Started', NULL),
(12, 22, 'Material Handling', 'Transporting and organizing materials on the construction site.', 91, 3, NULL, NULL, 'Not Started', NULL),
(13, 32, 'Material Handling', 'Transporting and organizing materials on the construction site.', 128, 123, NULL, NULL, 'Not Started', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `task_assignment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assignment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `contractor_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `status` enum('Assigned','In Progress','Completed') DEFAULT 'Assigned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`task_assignment_id`, `task_id`, `user_id`, `assignment_date`, `contractor_id`, `start_date`, `end_date`, `project_id`, `task_name`, `task_description`, `status`, `created_at`, `employee_id`) VALUES
(15, 5, 60, '2025-05-08 14:59:33', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 14:59:33', 0),
(16, 6, 60, '2025-05-08 15:05:10', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 15:05:10', 0),
(17, 7, 60, '2025-05-08 15:18:17', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 15:18:17', 0),
(18, 8, 60, '2025-05-08 15:18:34', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 15:18:34', 0),
(19, 9, 60, '2025-05-08 15:18:37', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 15:18:37', 0),
(20, 10, 60, '2025-05-08 15:18:39', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 15:18:39', 0),
(21, 11, 60, '2025-05-08 15:25:50', NULL, '0000-00-00', '0000-00-00', 22, '', 'do it', 'Assigned', '2025-05-08 15:25:50', 0),
(22, 12, 91, '2025-05-12 20:50:20', NULL, '0000-00-00', '0000-00-00', 22, '', 'Transporting and organizing materials on the construction site.', 'In Progress', '2025-05-12 20:50:20', 0),
(23, 13, 128, '2025-05-23 09:48:54', NULL, '0000-00-00', '0000-00-00', 32, '', 'Transporting and organizing materials on the construction site.', 'In Progress', '2025-05-23 09:48:54', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Admin','Project Manager','Contractor','Consultant','Site Engineer','Employee') NOT NULL,
  `RegistrationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Username` varchar(50) NOT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `managed_by_contractor_id` int(11) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Email`, `Password`, `Role`, `RegistrationDate`, `Username`, `LastLogin`, `role_id`, `managed_by_contractor_id`, `Phone`, `created_by`, `is_active`) VALUES
(1, 'Girma', 'Bacha', 'admin@gmail.com', '$2y$10$RlB6yceoaSzW78L1BOaY8uAqgbBnowQC8HhqaQ29PUp8U2N5539By', 'Admin', '2025-03-25 17:22:44', 'admin', '2025-05-27 11:01:58', 2, NULL, '+251798786356', NULL, 1),
(2, 'Manager', 'Tola', 'manager@gmail.com', '$2y$10$VEcHkNds/RSvxCWbwyR9Tu2sH/kO3LyjoIWG/VeDEn8Tcssntmdtu', 'Project Manager', '2025-03-25 17:22:44', 'man', '2025-05-23 15:28:34', NULL, NULL, '+251767562980', NULL, 1),
(3, 'Tufa', 'Muna', 'tufa@gmail.com', '$2y$10$aEqjwmeR81LJo2Z6hBtvDu.gnaPslG9oMYTRUY6Go5IRRDK4KyePa', 'Contractor', '2025-03-25 17:22:44', 'cont', '2025-05-23 13:07:33', 1, NULL, '+251953738738', NULL, 1),
(4, 'Consultant', 'Tariku', 'consultant@gmail.com', '$2y$10$BLecYgv0C8ImonzqcurL0eXzpdTqwqmBdXfUVmSRV4y0a6ROloIKy', 'Consultant', '2025-03-25 17:22:44', 'cons', '2025-05-23 02:19:41', NULL, NULL, NULL, NULL, 1),
(6, 'Ukash', 'Kedir', 'Ukash@gmail.com', '$2y$10$reQOZjd9iD5dr2U6Yg2mzOtSsGc97TfGTYADu8FahFLA0m.O9tRQG', 'Employee', '2025-03-25 17:22:44', 'emp', '2025-05-12 08:45:17', NULL, NULL, NULL, NULL, 1),
(22, 'Girma', 'Tulu', 'girmatulu@gmail.com', '$2y$10$xJ5FQJl.v3EES96VxgalKu.1XDB4NBtNP.1BSHEVgb4S8ta71/cV6', 'Contractor', '2025-04-15 12:16:39', 'girma.tulu', NULL, 1, NULL, NULL, NULL, 1),
(23, 'Alex', 'Teka', 'ale@gmail.com', '$2y$10$6NOcDFRLzzL7FUiVhKo6BulyOA4z34qRjeH2fVpqMJeDx6CVJ.gMi', 'Consultant', '2025-04-15 12:22:39', 'alex.teka', NULL, NULL, NULL, NULL, NULL, 0),
(24, 'Zeru', 'Balcha', 'zaru@gmail.com', '$2y$10$bb/3S/uQ53DTtu2Nid1FS.Jz6Wef5DBxBxRWvfpM0lNxmLmFi30z.', 'Employee', '2025-04-15 12:24:58', 'zeru.balcha', NULL, NULL, NULL, NULL, NULL, 1),
(25, 'Dima', 'Abiy', 'dima@gmail.com', '$2y$10$gjt.SnFy8Qsy149xJ8p5Vu1z6/J79E1.ufmInBskP2csbT8lLDWWC', 'Employee', '2025-04-15 12:25:27', 'dima.abiy', NULL, NULL, NULL, NULL, NULL, 1),
(31, 'wako', 'Tulu', 'wake@gmail.com', '$2y$10$tkvC4aE3KpA7mO0.LR6K9.1nHxE7tnh0eBq3iNc13RCIjW6WtEOcW', 'Project Manager', '2025-04-18 17:13:20', 'wako', NULL, NULL, NULL, '0941344994', NULL, 1),
(33, 'Balcha', 'Ifa', 'ifa@gmail.com', '$2y$10$bYlXwrhdaDnbGqMDyan7d.mN1M2uOXUHnNRF04Yvx1ACiEGSTv3ru', 'Contractor', '2025-04-18 18:44:36', 'balcha', NULL, NULL, NULL, '0945345276', NULL, 1),
(34, 'bacha', 'Toal', 'bacha@gmail.com', '$2y$10$9yN3OfsOVo.7R/Dd7Hx8tunTl1N3L8lFt83qMdIKrCTgICdUsPpTO', 'Consultant', '2025-04-18 18:54:35', 'bacha', NULL, NULL, NULL, '0987654321', NULL, 1),
(35, 'Tariku', 'Hirko', 'tul@gmail.com', '$2y$10$99CHPF56/PIPRb5KqIBV9ujmWl0lR1L3Slz/o.dqTyWvSxZlan9BS', 'Contractor', '2025-04-18 18:55:24', 'tulu', NULL, NULL, NULL, '0987654350', NULL, 1),
(36, 'Chaltu', 'Belay', 'chaltu@gmail.com', '$2y$10$aCi/BNyMPXxatUgVN2TReOKP208.IfVY4MxIeGZkxOhh1LbDIZteS', 'Project Manager', '2025-04-18 18:57:59', 'chaltu', NULL, NULL, NULL, '0943765489', NULL, 1),
(38, 'Aman', 'Tufa', 'aman@gmail.com', '$2y$10$9oUJciqHyDXvuM/1lm.ScOWKZEhHmrWpwM5AFrjNYcOX9ydqbhhKm', 'Consultant', '2025-04-19 16:08:22', 'aman', NULL, NULL, NULL, '0987654312', NULL, 1),
(39, 'Andu', 'Teka', 'andu@gmail.com', '$2y$10$z5TufXxpBn.yKC8R.YQlSu01OXOyem9xEN8rmV.p4jJQoO2/lNR/y', 'Consultant', '2025-04-19 16:16:47', 'andu', NULL, NULL, NULL, '0987654786', NULL, 1),
(40, 'Merera', 'Fikadu', 'marara@gmail.com', '$2y$10$zESX3f7/ZRbNcNf2Q/wYi.i8zt00AgIOlHPpE/NDR6tXrRItBsfoW', 'Consultant', '2025-04-19 16:28:20', 'marara', NULL, 15, 2, '0987677545', NULL, 1),
(44, 'Tulu', 'Dimtu', 'tulu@gmail.com', '$2y$10$adEUcsM5MYOCJktSJezzVuz7fQ1XXCTPBqG4foOQb1cArxL8afZhi', 'Contractor', '2025-04-19 16:39:30', 'tuluD', NULL, 1, 2, '0999887766', NULL, 1),
(45, 'Abel', 'Girma', 'abel@gmail.com', '$2y$10$UWhpZAs07k22lCIagNG9zezu50gR192VR5mgfsW13nKD1mUtAL7G.', 'Contractor', '2025-04-19 17:30:09', 'abel', NULL, NULL, NULL, '0990876775', NULL, 0),
(46, 'Bilisa', 'Gurmu', 'bila@gmail.com', '$2y$10$cMESI/SUuK2AMVIvYyk9DuM5MLmYs5oGqsz5uBZrJw2o2ZExdfxrO', 'Consultant', '2025-04-22 07:44:10', 'bila', NULL, 15, 2, '0909877898', NULL, 1),
(47, 'Teke', 'Kuma', 'teke@gmail.com', '$2y$10$0TrUY82Cl4zCyqJy2UhBKOBE14PAKUu7hrWKWYAQFZrN1uy0pq3xa', 'Project Manager', '2025-04-25 06:40:12', 'teke', NULL, NULL, NULL, '941344994', NULL, 0),
(48, 'Degu', 'Bekele', 'degu@gmail.com', '$2y$10$a6a0o70WMedjv57ACaVJnO3/6UHJwvhYHe7chA/K/Rck/559KObWC', 'Employee', '2025-04-25 07:01:57', 'degu', NULL, NULL, NULL, '0990099009', NULL, 1),
(49, 'Ebisa', 'Dima', 'ebo@gmail.com', '$2y$10$WaNuVkeM0wJxe/gp3ZtFLO2ptY0Wit8.HuQgkeMdrhQsp6qH15b26', 'Employee', '2025-04-25 07:08:24', 'ebo', NULL, NULL, 70, '0941364994', NULL, 1),
(51, 'Marara', 'Fikadu', 'mer@gmail.com', '$2y$10$wUJpNUNkje0Y8VC3wy3GjuP8TqvQZOXVt.b/MYpTftqk70meDHuRC', 'Employee', '2025-04-25 08:08:50', 'mar', NULL, NULL, 3, '0917543989', NULL, 1),
(57, 'Burte', 'Balcha', 'burte@gmail.com', '$2y$10$ZO9joAGOHd5Vh9bdkyhBX.i6hDUM2XYKpzHerc4u8ha78oSssO9e6', 'Site Engineer', '2025-04-26 17:16:12', 'Burte', NULL, NULL, NULL, '0789986765', 2, 1),
(58, 'Bire', 'Balcha', 'bire@gmail.com', '$2y$10$waDPR1o45dn0dwxOsc8k2uTXP3Ad/CIUwchdTO1wV1rFJ3/9.LjrG', 'Site Engineer', '2025-04-27 09:58:42', 'Bire', NULL, NULL, NULL, '0712213445', 2, 1),
(60, 'Ephraim', 'Yared', 'efa@gmail.com', '$2y$10$UBjD9hImHYFnhAH2fWPg.OQXZE7mqEuPncUekrPyXFsayz1OKy8z.', 'Employee', '2025-05-08 11:51:37', 'EFA', '2025-05-23 08:40:25', NULL, 3, '+251965453789', NULL, 1),
(61, 'Lemi', 'Bekele', 'lemi@gmail.com', '$2y$10$CIeyjn8PEQVBrajt9ky5LO8PW84SrH4RY7UicZyyEKlAlQU9aKaly', 'Consultant', '2025-05-08 16:25:34', 'Lemi', NULL, 15, 2, '+251904567624', NULL, 1),
(62, 'Yosan', 'Bifan', 'yosan@gmail.com', '$2y$10$hIwHFJniyLxFmwYXga5aRuuSeVoMXtXg/aYFIICI5lRyExlnXHZS.', 'Site Engineer', '2025-05-08 17:00:34', 'Yosan', '2025-05-23 08:29:59', NULL, NULL, '+251790765645', 2, 1),
(63, 'Ayansa', 'T', 'ayu@gamil.com', '$2y$10$diPMypHlB1gnnKutrXYSzuRNYfcJVxYgazub2AIYxv1UmqFMQt9We', 'Site Engineer', '2025-05-09 13:25:22', 'Ayu', NULL, NULL, NULL, '+251999999999', 2, 1),
(69, 'Obsa', 'Teka', 'obsa@gmail.com', '$2y$10$EzIfWv8f5Xk46y.ezRyMieoUBMhYA2FOr2dIxaRux29cTTP76nc6S', 'Contractor', '2025-05-09 18:03:18', 'obsa.teka', NULL, NULL, NULL, '+251 989998876', NULL, 1),
(70, 'Adisu', 'Gurmu', 'adis@gmail.com', '$2y$10$q1oWAPcnJ/ck.qEk.lp2I.81e6zLxfFn1EGNIbssyLwMLXC/IMVUy', 'Contractor', '2025-05-09 18:05:48', 'adisu.gurmu', NULL, NULL, NULL, '+251 999767567', NULL, 1),
(71, 'Teklu', 'Gurmu', 'teku@gmail.com', '$2y$10$z9e20/yxpa.CtH1lGaxjo.oL6WAFK4NVdwjI7vKGUJDfVL9Cz55tG', 'Project Manager', '2025-05-09 18:17:23', 'teklu.gurmu', NULL, NULL, NULL, '+251 977662345', NULL, 1),
(72, 'Eyob', 'Tariku', 'eyob@gmail.com', '$2y$10$XBeenE0ORgXlg94BVFtqZumfWSImg1XeWupAGV.Yr4bgYmmIFwMIq', 'Contractor', '2025-05-09 18:20:35', 'eyob.tariku', NULL, NULL, NULL, '+251 909056756', NULL, 1),
(73, 'Mohe', 'Tawil', 'moh@gmail.com', '$2y$10$b7Nr3elJ5X2o1lO/sHhTse7AERxdbVqPWjo9iF0fGBU6VES/DQp6i', 'Consultant', '2025-05-09 18:26:34', 'mohe.tawil', NULL, NULL, NULL, '+251 945633782', NULL, 1),
(74, 'Dare', 'Kasu', 'dare@gmail.com', '$2y$10$85ju9/zSiwTjiqRyQQUeKuillaN2pPU74Mrg1QqwCOekxsus5HZUy', '', '2025-05-09 18:32:31', 'dare.kasu', NULL, NULL, NULL, '+251 987665444', NULL, 1),
(78, 'Darex', 'Kasu', 'darex@gmail.com', '$2y$10$NKTh0P9gzav77SulsvJejOdfYxOKr.IOV52b5VizK8/2dJHgT6Jsa', '', '2025-05-09 18:58:34', 'darex.kasu', NULL, NULL, NULL, '+251 987665444', NULL, 1),
(80, 'Yared', 'Kasu', 'yared@gmail.com', '$2y$10$7I.6F7Fv1MKoNAkU7kFo4OTRIDusNVrGmIMjtOgYuaHSefn3zprRC', '', '2025-05-09 19:04:59', 'yared.kasu', NULL, NULL, NULL, '+251 987665444', NULL, 1),
(81, 'Yare', 'Kasu', 'yare@gmail.com', '$2y$10$lpBxxttHkGT3MmmXBXISiuqMrNm1nCvrc2iH3ZQLztdXSsU8TJ8tO', 'Site Engineer', '2025-05-09 19:13:19', 'yare.kasu', NULL, NULL, NULL, '+251 987665444', 1, 0),
(83, 'Lalo', 'Juda', 'lal@gmamil.com', '$2y$10$jG8Ohh8OhlQHljnfTm1qLOT3tzln2nHAuTDF1GxQ8ilgWVZ81h.oq', 'Contractor', '2025-05-10 05:57:37', 'lalo.juda', NULL, NULL, NULL, '+251 977654333', NULL, 1),
(84, 'LEM', 'TUL', 'LEM@GMAIL.COM', '$2y$10$j5GzPL4zmyzYNjSMSe8PBeE1PyRCALnl.Y90xtP3nISSGzluPL6bm', 'Consultant', '2025-05-10 05:59:52', 'lem.tul', NULL, NULL, NULL, '+251 988888666', NULL, 1),
(85, 'Wal', 'Lol', 'wal@gmail.com', '$2y$10$DDDMSx4HIa9lPz1rMY4w6ebGPMkqwDY6JDamOovzxZgwuE5OoPjSm', 'Site Engineer', '2025-05-10 06:01:00', 'wal.lol', NULL, NULL, NULL, '+251 748489390', 1, 1),
(87, 'Dagm', 'Sam', 'dagm@gmail.com', '$2y$10$DEmuZ2/vmbFSp5qS82kz3.xqu0T9STeVJp6wcAQG9nqq6aPU1s1ma', 'Project Manager', '2025-05-11 06:36:06', 'dagm.sam', NULL, NULL, NULL, '+251 932323222', NULL, 1),
(88, 'Dagm', 'Las', 'dagms@gmail.com', '$2y$10$NyaWWnSsG051bRJr8UMyLO1/3Yq6gg5oN1rBlb/wUHdA9IGTqQr16', 'Project Manager', '2025-05-11 06:37:58', 'dagm.las', NULL, NULL, NULL, '+251 973941296', NULL, 1),
(91, 'Babi', 'Gude', 'babi@gmail.com', '$2y$10$zrsUKdGo4lj8Tpso63MqYeHLjVcKGeWEsAP06Tnq2sJlBuM691ej2', 'Employee', '2025-05-12 20:48:30', 'Babi', '2025-05-12 23:49:41', NULL, 3, '+251725399903', NULL, 1),
(92, 'Bikiltu', 'Tesfa', 'bik@gmail.com', '$2y$10$LOyo5BoVxqKSFR5kKVHXbu9BSORP6TJFCoAiwyr4xboQCLWM4dgua', 'Consultant', '2025-05-12 20:58:52', 'bikiltu.tesfa', '2025-05-13 00:00:18', NULL, NULL, '+251 977634333', NULL, 1),
(93, 'Lal', 'TULU', 'xyz@gmail.com', '$2y$10$vCYegZ.41t4LtkQDrHwASO.W5TGrxK069gviVXwXkrrtk4WLbT2.O', 'Consultant', '2025-05-13 06:01:45', 'lal.tulu', NULL, NULL, NULL, '+251 973654333', NULL, 1),
(94, 'KK', 'Lu', 'kk@gmail.com', '$2y$10$3pL1P07/RTbF69MlBn8sn.bv7Gwd8OiY2KfXxVd/H9kt7XiWqLRWC', 'Consultant', '2025-05-13 06:16:39', 'kk.lu', NULL, 15, NULL, '+251965453789', NULL, 1),
(95, 'LLL', 'BB', 'wa@gmail.com', '$2y$10$kxN7apd//nGhFUao7iJkLuQO9CGrT6HLQblp4eo.B3H77kiwcizTC', 'Consultant', '2025-05-13 06:50:25', 'lll.bb', '2025-05-13 10:12:44', NULL, 2, '+251 988755555', NULL, 1),
(96, 'Chala', 'Tola', 'ct@gmail.com', '$2y$10$.hRAwzMxRlzbXS/jxDVGxe//CAVScGB8S0K9an2HJ08fjeCEruwC.', 'Project Manager', '2025-05-13 06:59:38', 'chala.tola', '2025-05-13 10:00:03', NULL, NULL, '+251 778568666', NULL, 1),
(98, 'Tame', 'Mulata', 'tame@gmail.com', '$2y$10$6xV7KQENPDN8zUlwPM5UJuI2r3QxpNlXM1hvyaAoyRcp00S9ihDI6', 'Project Manager', '2025-05-13 07:40:34', 'tame.mulata', '2025-05-13 10:41:20', NULL, NULL, '+251 966644445', NULL, 1),
(102, 'Abe', 'Hes', 'abe@gmail.com', '$2y$10$a4mYm8b2Lsb9p/JEy.NTueQFz/WDxEWMXg8fhmExAGFWl3yBrtLP.', 'Admin', '2025-05-18 15:58:32', 'abe.hes', NULL, NULL, NULL, '+251987656564', NULL, 1),
(103, 'Oli', 'Fituma', 'oli@gmail.com', '$2y$10$KxFbQb8hUT1p3sX9p5XHnO2eSYEOFU4A8UaeOOcaOWLsm9yLAxZVi', 'Consultant', '2025-05-18 16:12:54', 'oli.fituma', '2025-05-18 19:20:57', NULL, 2, '+251 974868482', NULL, 1),
(104, 'Abe', 'Kal', 'ab@gmail.com', '$2y$10$n2c.//e/Rn2e.RtlsiDu6.1uPhQcwPY887f9BDpIuhbGNktQCbYMK', 'Consultant', '2025-05-18 17:43:29', 'abe.kal', '2025-05-18 20:43:56', NULL, 2, '+251 938690766', NULL, 1),
(105, 'Rebira', 'Tesfaye', 'rebo@gmail.com', '$2y$10$48.G/GzQymlHlmK.nWG7EORDazaMPDzkuyWq8DKMZB4fiWgMmSXS.', 'Employee', '2025-05-21 16:22:35', 'rtesfa_614', '2025-05-21 19:23:08', NULL, 3, '+251974367427', NULL, 1),
(106, 'Ab', 'C', 'abcs@gmail.com', '$2y$10$5VnsCP.4fahvdIbKsP3exelawzp0lfAsXKpN7Uy0V1VqSWxGfccvG', 'Consultant', '2025-05-21 16:39:40', 'ab.c', '2025-05-21 19:41:47', NULL, NULL, '+251977777778', NULL, 1),
(107, 'Bila', 'Bire', 'bilac@gmail.com', '$2y$10$O1LF1PkVwRBpyJnOoJ6nXu55mg0MSSwveJYRGnqDfAj9V0TFoMNzW', 'Consultant', '2025-05-21 16:58:14', 'bila.bire', '2025-05-21 19:59:27', NULL, 2, '+251 748460982', NULL, 1),
(108, 'Adem', 'Ahmed', 'adem@gmail.com', '$2y$10$VpdNm8y2UEu.ZkPSK.ZRH./SozH0CoikHFd4y7KSiJKnRPUkpxIZu', 'Contractor', '2025-05-22 17:43:58', 'adem.ahmed', NULL, NULL, 87, '+251 977777777', NULL, 1),
(109, 'Gosaye', 'Tesfu', 'gos@gmail.com', '$2y$10$yQnPTnYDvf1WWqI2JowvveQLGl/c9UAOrbCu0rCLtLmpFsco5LMOi', 'Employee', '2025-05-22 18:24:23', 'gtesfu@941', NULL, NULL, 3, '+251783743865', NULL, 1),
(111, 'Andu', 'Gosa', 'andulem@gmail.com', '$2y$10$4UYBU23soGVqC38nSomepeU10EClHikbFzd4DYkqDiI9b7JrP/wLK', 'Employee', '2025-05-22 18:31:43', 'agosa_276', NULL, NULL, 3, '+251746383992', NULL, 1),
(113, 'Dame', 'Dego', 'demex@gmail.com', '$2y$10$RYBUae2tPKeR8XS.OpzAGuUnPp1gjC22MglYIQhykGqyHWjyLHw5q', 'Project Manager', '2025-05-22 18:43:41', 'dame.dego1', '2025-05-22 21:44:34', NULL, NULL, '+251 903036519', NULL, 1),
(115, 'Ana', 'Gabi', 'ana@gmail.com', '$2y$10$lkIZMwgKuZToU9sVHzcF5OXaOiUo9N5LpSrwBiZ.T7l0xH./UpIqa', 'Contractor', '2025-05-22 23:16:53', 'ana.gabi', NULL, NULL, 114, '+251 988888888', NULL, 1),
(116, 'Ayu', 'Angasu', 'ayu@gmail.com', '$2y$10$BNWphl57zFFGLvgw5aHh5ekAX0pY5W7lF29Lq7WlHUf6tHQyroyI.', 'Employee', '2025-05-23 06:31:15', 'aangas_490', NULL, NULL, 3, '+251947637267', NULL, 1),
(117, 'Mara', 'Fike', 'mer@gail.com', '$2y$10$YepFgHn13pMsXXjWWnDU.eRRL6iqBiIPJBu2.VxxqOZxlNe8xK0MG', 'Contractor', '2025-05-23 06:34:41', 'mara.fike', NULL, NULL, 11, '+251 737378749', NULL, 1),
(118, 'Mara', 'Fike', 'maur@gail.com', '$2y$10$9moV6Q7LQ/pRxTOOhor1fOduGBXgGBncaP37I7qNf777WHsCqjJuK', 'Contractor', '2025-05-23 06:39:11', 'mara.fike1', NULL, NULL, 11, '+251 737378749', NULL, 1),
(119, 'Mara', 'Fike', 'maur@gmail.com', '$2y$10$Rq85fG5S1/aH47RmwyeQYuAGhcPtIdEFSwyWj2uu0KY60asefn.zy', 'Contractor', '2025-05-23 06:41:06', 'mara.fike2', NULL, NULL, 11, '+251 737378749', NULL, 1),
(120, 'Abs', 'Abd', 'abd@gail.com', '$2y$10$7r8EFlwBOuQQg8DLdZ3nju0Qs6Qs87o4lYEb3NK/GNUflAOjfYQya', 'Project Manager', '2025-05-23 06:43:40', 'abs.abd', NULL, NULL, NULL, '+251 964846299', NULL, 1),
(121, 'Mehdi', 'Buseri', '1@gmail.com', '$2y$10$X6c.v56SH1GDfAmTpEqBkev21KQJiyrZ7LHz0hlFfXSRpBsEy8r8i', 'Site Engineer', '2025-05-23 06:48:22', 'mehdi.buseri', NULL, NULL, 100, '251', 1, 1),
(122, 'Manjus', 'Abebe', 'manju@gmail.com', '$2y$10$qZ.5D1CikYIqsRv04c.ZouE9yE9sWQM/B1zSUDwKyqV4jsKF194K6', 'Project Manager', '2025-05-23 07:44:29', 'manjus.abebe', '2025-05-23 15:30:31', NULL, NULL, '+251 738972982', NULL, 1),
(123, 'Fita', 'Feyo', 'fita@gmail.com', '$2y$10$cVSSbOELObTaHolazqrLs.6gtQ3PReRJXLV9ScMCue9KzHBIgkop6', 'Contractor', '2025-05-23 07:49:15', 'fita.feyo', '2025-05-23 15:34:46', NULL, 122, '+251 754335678', NULL, 1),
(124, 'Kebe', 'Buze', 'kebe@gmail.com', '$2y$10$RyOmS8MHoC6it.RV.fZ7FenG5.1f5k/JdZ.ShKCJw0.I77imTogGW', 'Consultant', '2025-05-23 08:25:25', 'kebe.buze', '2025-05-23 11:27:52', NULL, 122, '+251 737637386', NULL, 1),
(125, 'Engu', 'Dure', 'engu@gmail.com', '$2y$10$YaNLDWjMaIm4.29Bx5mxFuHcp/aa1q4B4377lrO/fN.CllEPFXmVm', 'Site Engineer', '2025-05-23 08:41:54', 'engu.dure', '2025-05-23 11:45:04', NULL, 122, '+251941344993', 1, 1),
(126, 'Galme', 'Rebo', 'galme@gmail.com', '$2y$10$kMDt.GicMWLuVtCrdp.hQO7ELw5Oka3aYRnHG/Z76ZPkZnSmcfV4G', 'Site Engineer', '2025-05-23 08:52:07', 'galme.rebo', '2025-05-23 12:03:48', NULL, 122, '251', 1, 1),
(127, 'Mule', 'Tame', 'mule@gmail.com', '$2y$10$CtAnP7qM6c1lwR5KdzjkQ.k4G4n.CwoOI3REde1rUzRCdDw/uQm7i', 'Site Engineer', '2025-05-23 09:06:55', 'mule.tame', '2025-05-23 12:07:20', NULL, 122, '+251 736376828', 1, 1),
(128, 'Biruk', 'Nego', 'biruk@gmail.com', '$2y$10$DWma6fUqKDbPp73Wyn.x5.wtiM9c0WZEI.rf6Wnhz4B2k5mrgFTGy', 'Employee', '2025-05-23 09:47:49', 'bnego_255', '2025-05-23 12:49:21', NULL, 123, '+251984848484', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_requests`
--

CREATE TABLE `user_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('Access','Resource','Support','Permission','Other') NOT NULL,
  `request_title` varchar(100) NOT NULL,
  `request_details` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','In Progress') NOT NULL DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_security_questions`
--

CREATE TABLE `user_security_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_1` varchar(255) NOT NULL,
  `answer_1` varchar(255) NOT NULL,
  `question_2` varchar(255) NOT NULL,
  `answer_2` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_security_questions`
--

INSERT INTO `user_security_questions` (`id`, `user_id`, `question_1`, `answer_1`, `question_2`, `answer_2`) VALUES
(1, 2, 'birth_city', 'komando', 'roleModel', 'hachalu'),
(2, 3, 'nickname', 'boche', 'birthPlace', '2000 G.C.'),
(3, 4, 'nickname', 'abc', 'birthPlace', '2000 G.C.'),
(4, 62, 'nickname', 'kemer', 'birthPlace', '2002 G.C.'),
(5, 105, 'birth_city', 'gindo', 'roleModel', 'abiy'),
(6, 123, 'birth_city', 'fitche', 'birthPlace', '1995 G.C.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_decisions`
--
ALTER TABLE `admin_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `completed_projects`
--
ALTER TABLE `completed_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `manager_id` (`manager_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `daily_labor`
--
ALTER TABLE `daily_labor`
  ADD PRIMARY KEY (`labor_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `fk_manager` (`manager_id`),
  ADD KEY `fk_employee_id` (`employee_id`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `project_schedules`
--
ALTER TABLE `project_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_project_report` (`project_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`task_assignment_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `user_requests`
--
ALTER TABLE `user_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_decisions`
--
ALTER TABLE `admin_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `completed_projects`
--
ALTER TABLE `completed_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_labor`
--
ALTER TABLE `daily_labor`
  MODIFY `labor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
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
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `project_comments`
--
ALTER TABLE `project_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `project_schedules`
--
ALTER TABLE `project_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `task_assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `user_requests`
--
ALTER TABLE `user_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_decisions`
--
ALTER TABLE `admin_decisions`
  ADD CONSTRAINT `admin_decisions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `admin_decisions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `completed_projects`
--
ALTER TABLE `completed_projects`
  ADD CONSTRAINT `completed_projects_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `completed_projects_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `completed_projects_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `daily_labor`
--
ALTER TABLE `daily_labor`
  ADD CONSTRAINT `daily_labor_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `daily_labor_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `fk_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `project_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `project_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `project_schedules`
--
ALTER TABLE `project_schedules`
  ADD CONSTRAINT `project_schedules_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `fk_project_report` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `fk_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`),
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `task_assignments_ibfk_4` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `user_requests`
--
ALTER TABLE `user_requests`
  ADD CONSTRAINT `user_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  ADD CONSTRAINT `fk_user_security` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
