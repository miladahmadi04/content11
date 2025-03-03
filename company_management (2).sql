-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2025 at 02:09 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `company_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'miladahmadi04', '$2y$10$o5o4zmdoIWiJ/CH7C4zCNOAr2mbmHkV0Aj03r4fgEoeCcXBh.qifW', '2025-03-01 06:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'فیلمسازی', '2025-03-01 06:14:35'),
(2, 'عکاسی', '2025-03-01 06:14:40'),
(3, 'طراحی وب', '2025-03-01 06:14:47'),
(4, 'طراحی صفحه محصول', '2025-03-01 06:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `coach_reports`
--

CREATE TABLE `coach_reports` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `general_comments` text DEFAULT NULL,
  `coach_comment` text DEFAULT NULL,
  `coach_score` decimal(3,1) DEFAULT NULL,
  `statistics_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`statistics_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `coach_reports`
--

INSERT INTO `coach_reports` (`id`, `coach_id`, `personnel_id`, `receiver_id`, `company_id`, `report_date`, `date_from`, `date_to`, `team_name`, `general_comments`, `coach_comment`, `coach_score`, `statistics_json`, `created_at`) VALUES
(1, 1, 2, 0, 0, '2025-03-02', '2025-01-04', '2025-03-17', 'تست', NULL, NULL, NULL, NULL, '2025-03-02 08:00:10'),
(2, 1, 1, 0, 0, '2025-03-02', '2025-01-04', '2025-03-17', 'تست', NULL, NULL, NULL, NULL, '2025-03-02 08:00:10'),
(3, 1, 2, 0, 0, '2025-03-02', '2025-01-04', '2025-03-17', 'تست', NULL, NULL, NULL, NULL, '2025-03-02 08:02:02'),
(4, 1, 1, 0, 0, '2025-03-02', '2025-01-04', '2025-03-17', 'تست', NULL, NULL, NULL, NULL, '2025-03-02 08:02:02'),
(5, 1, 2, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', 'تست', NULL, NULL, NULL, NULL, '2025-03-02 08:03:04'),
(6, 1, 1, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', 'تست', NULL, NULL, NULL, NULL, '2025-03-02 08:03:04'),
(7, 1, 2, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', '', NULL, NULL, NULL, NULL, '2025-03-02 08:04:11'),
(8, 1, 2, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', '', NULL, NULL, NULL, NULL, '2025-03-02 08:09:13'),
(9, 1, 2, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', 'دیجیتال', NULL, NULL, NULL, NULL, '2025-03-02 08:12:52'),
(10, 1, 1, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', 'دیجیتال', NULL, NULL, NULL, NULL, '2025-03-02 08:12:52'),
(11, 1, 2, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', '', NULL, NULL, NULL, NULL, '2025-03-02 08:16:36'),
(12, 1, 1, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', '', NULL, NULL, NULL, NULL, '2025-03-02 08:16:36'),
(13, 1, 2, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', '', NULL, 'قفقفقف', '1.0', NULL, '2025-03-02 08:21:30'),
(14, 1, 1, 0, 0, '2025-03-02', '2025-01-31', '2025-03-02', '', NULL, 'قفقفقف', '2.0', NULL, '2025-03-02 08:21:30'),
(30, 1, 2, 1, 1, '2025-03-03', '2025-02-01', '2025-03-03', NULL, 'nmn', NULL, NULL, NULL, '2025-03-03 12:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `coach_report_access`
--

CREATE TABLE `coach_report_access` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `coach_report_personnel`
--

CREATE TABLE `coach_report_personnel` (
  `id` int(11) NOT NULL,
  `coach_report_id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `coach_comment` text DEFAULT NULL,
  `coach_score` decimal(3,1) DEFAULT NULL,
  `statistics_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`statistics_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `coach_report_personnel`
--

INSERT INTO `coach_report_personnel` (`id`, `coach_report_id`, `personnel_id`, `coach_comment`, `coach_score`, `statistics_json`, `created_at`) VALUES
(1, 1, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(2, 2, 1, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(3, 3, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(4, 4, 1, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(5, 5, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(6, 6, 1, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(7, 7, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(8, 8, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(9, 9, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(10, 10, 1, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(11, 11, 2, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(12, 12, 1, NULL, NULL, NULL, '2025-03-03 12:48:05'),
(13, 13, 2, 'قفقفقف', '1.0', NULL, '2025-03-03 12:48:05'),
(14, 14, 1, 'قفقفقف', '2.0', NULL, '2025-03-03 12:48:05'),
(34, 30, 2, 'cdfdfd dfd', '2.0', '{\"report_count\":\"1\",\"categories\":[\"\\u0639\\u06a9\\u0627\\u0633\\u06cc\"],\"top_categories\":[{\"name\":\"\\u0639\\u06a9\\u0627\\u0633\\u06cc\",\"count\":\"1\"}]}', '2025-03-03 12:57:54'),
(35, 30, 5, 'dfdf dfd', '3.0', '{\"report_count\":\"0\",\"categories\":[],\"top_categories\":[]}', '2025-03-03 12:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `coach_report_social_reports`
--

CREATE TABLE `coach_report_social_reports` (
  `coach_report_id` int(11) NOT NULL,
  `social_report_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `coach_report_social_reports`
--

INSERT INTO `coach_report_social_reports` (`coach_report_id`, `social_report_id`) VALUES
(13, 2),
(13, 3),
(14, 2),
(30, 2);

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `is_active`, `created_at`) VALUES
(1, 'پیروز پک', 1, '2025-03-01 06:13:11'),
(2, 'گروه میلاد احمدی', 1, '2025-03-02 05:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `contents`
--

CREATE TABLE `contents` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `publish_date` date NOT NULL,
  `publish_time` time NOT NULL,
  `scenario` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `production_status_id` int(11) NOT NULL,
  `publish_status_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_audience_relations`
--

CREATE TABLE `content_audience_relations` (
  `content_id` int(11) NOT NULL,
  `audience_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_calendar_settings`
--

CREATE TABLE `content_calendar_settings` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `is_visible` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `content_calendar_settings`
--

INSERT INTO `content_calendar_settings` (`id`, `company_id`, `field_name`, `is_visible`, `display_order`, `created_at`) VALUES
(1, 1, 'title', 1, 1, '2025-03-03 13:07:17'),
(2, 1, 'publish_date', 1, 2, '2025-03-03 13:07:17'),
(3, 1, 'publish_time', 1, 3, '2025-03-03 13:07:17'),
(4, 1, 'production_status', 1, 4, '2025-03-03 13:07:17'),
(5, 1, 'publish_status', 1, 5, '2025-03-03 13:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `content_formats`
--

CREATE TABLE `content_formats` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `content_formats`
--

INSERT INTO `content_formats` (`id`, `company_id`, `name`, `is_default`, `can_delete`, `created_at`) VALUES
(1, 1, 'خلاصه', 1, 0, '2025-03-03 13:07:17'),
(2, 1, 'کامل', 1, 0, '2025-03-03 13:07:17'),
(3, 1, 'تیزر', 1, 0, '2025-03-03 13:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `content_platforms`
--

CREATE TABLE `content_platforms` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_platform_relations`
--

CREATE TABLE `content_platform_relations` (
  `content_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_post_publish_processes`
--

CREATE TABLE `content_post_publish_processes` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `format_id` int(11) NOT NULL,
  `days_after` int(11) NOT NULL DEFAULT 0,
  `publish_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_production_statuses`
--

CREATE TABLE `content_production_statuses` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `content_production_statuses`
--

INSERT INTO `content_production_statuses` (`id`, `company_id`, `name`, `is_default`, `can_delete`, `created_at`) VALUES
(1, 1, 'محتوا تولید نشده', 1, 0, '2025-03-03 13:07:17'),
(2, 1, 'محتوا در حال تولید است', 1, 0, '2025-03-03 13:07:17'),
(3, 1, 'محتوا تولید شده', 1, 0, '2025-03-03 13:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `content_publish_statuses`
--

CREATE TABLE `content_publish_statuses` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `content_publish_statuses`
--

INSERT INTO `content_publish_statuses` (`id`, `company_id`, `name`, `is_default`, `can_delete`, `created_at`) VALUES
(1, 1, 'منتشر شده', 1, 0, '2025-03-03 13:07:17'),
(2, 1, 'منتشر نشده', 1, 0, '2025-03-03 13:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `content_target_audiences`
--

CREATE TABLE `content_target_audiences` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_tasks`
--

CREATE TABLE `content_tasks` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `content_tasks`
--

INSERT INTO `content_tasks` (`id`, `company_id`, `name`, `is_default`, `can_delete`, `created_at`) VALUES
(1, 1, 'وظیفه اصلی', 1, 0, '2025-03-03 13:07:17'),
(2, 1, 'فرآیند پس از انتشار', 1, 0, '2025-03-03 13:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `content_task_assignments`
--

CREATE TABLE `content_task_assignments` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_templates`
--

CREATE TABLE `content_templates` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `scenario` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_topics`
--

CREATE TABLE `content_topics` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_topic_relations`
--

CREATE TABLE `content_topic_relations` (
  `content_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_types`
--

CREATE TABLE `content_types` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_type_relations`
--

CREATE TABLE `content_type_relations` (
  `content_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_models`
--

CREATE TABLE `kpi_models` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `model_type` enum('growth_over_time','percentage_of_field') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kpi_models`
--

INSERT INTO `kpi_models` (`id`, `name`, `description`, `model_type`, `created_at`) VALUES
(1, 'رشد زمانی', 'انتظار دارم فیلد X هر Y روز به مقدار N رشد کند', 'growth_over_time', '2025-03-01 06:11:46'),
(2, 'درصد از فیلد دیگر', 'انتظار دارم فیلد X به مقدار N درصد از فیلد دیگر باشد', 'percentage_of_field', '2025-03-01 06:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `scheduled_time` datetime DEFAULT NULL,
  `is_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_reports`
--

CREATE TABLE `monthly_reports` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `monthly_reports`
--

INSERT INTO `monthly_reports` (`id`, `page_id`, `creator_id`, `report_date`, `created_at`) VALUES
(2, 1, NULL, '2025-02-01', '2025-03-01 06:41:36'),
(3, 1, NULL, '2025-03-01', '2025-03-01 06:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_report_values`
--

CREATE TABLE `monthly_report_values` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `monthly_report_values`
--

INSERT INTO `monthly_report_values` (`id`, `report_id`, `field_id`, `field_value`, `created_at`) VALUES
(7, 2, 1, 'http://localhost/report2/social_pages.php', '2025-03-01 06:41:36'),
(8, 2, 2, '500', '2025-03-01 06:41:36'),
(9, 2, 3, '20', '2025-03-01 06:41:36'),
(10, 2, 4, '50', '2025-03-01 06:41:36'),
(11, 2, 5, '20', '2025-03-01 06:41:36'),
(12, 2, 6, '55', '2025-03-01 06:41:36'),
(13, 3, 1, 'http://localhost/report2/social_pages.php', '2025-03-01 06:43:34'),
(14, 3, 2, '560', '2025-03-01 06:43:34'),
(15, 3, 3, '5', '2025-03-01 06:43:34'),
(16, 3, 4, '66', '2025-03-01 06:43:34'),
(17, 3, 5, '20', '2025-03-01 06:43:34'),
(18, 3, 6, '500', '2025-03-01 06:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'پیام جدید از miladahmadi04: تست', 'view_message.php?id=0', 0, '2025-03-01 17:11:13'),
(2, 1, 'پیام جدید از عرفان عباسپور: RE: تست', 'view_message.php?id=0', 0, '2025-03-01 17:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `page_kpis`
--

CREATE TABLE `page_kpis` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `kpi_model_id` int(11) NOT NULL,
  `related_field_id` int(11) DEFAULT NULL,
  `growth_value` decimal(10,2) DEFAULT NULL,
  `growth_period_days` int(11) DEFAULT NULL,
  `percentage_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `page_kpis`
--

INSERT INTO `page_kpis` (`id`, `page_id`, `field_id`, `kpi_model_id`, `related_field_id`, `growth_value`, `growth_period_days`, `percentage_value`, `created_at`) VALUES
(1, 1, 2, 1, NULL, '10.00', 30, NULL, '2025-03-01 06:36:18'),
(2, 1, 3, 1, NULL, '10.00', 30, NULL, '2025-03-01 06:36:32'),
(3, 1, 4, 1, NULL, '10.00', 30, NULL, '2025-03-01 06:37:54'),
(4, 1, 5, 2, 2, NULL, NULL, '10.00', '2025-03-01 06:38:23'),
(5, 1, 6, 2, 5, NULL, NULL, '10.00', '2025-03-01 06:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `code`, `description`, `created_at`) VALUES
(1, 'مشاهده داشبورد', 'view_dashboard', 'دسترسی به مشاهده داشبورد', '2025-03-01 16:04:28'),
(2, 'مشاهده شرکت‌ها', 'view_companies', 'دسترسی به مشاهده لیست شرکت‌ها', '2025-03-01 16:04:28'),
(3, 'افزودن شرکت', 'add_company', 'دسترسی به افزودن شرکت جدید', '2025-03-01 16:04:28'),
(4, 'ویرایش شرکت', 'edit_company', 'دسترسی به ویرایش اطلاعات شرکت', '2025-03-01 16:04:28'),
(5, 'غیرفعال کردن شرکت', 'toggle_company', 'دسترسی به فعال/غیرفعال کردن شرکت', '2025-03-01 16:04:28'),
(6, 'مشاهده پرسنل', 'view_personnel', 'دسترسی به مشاهده لیست پرسنل', '2025-03-01 16:04:28'),
(7, 'افزودن پرسنل', 'add_personnel', 'دسترسی به افزودن پرسنل جدید', '2025-03-01 16:04:28'),
(8, 'ویرایش پرسنل', 'edit_personnel', 'دسترسی به ویرایش اطلاعات پرسنل', '2025-03-01 16:04:28'),
(9, 'غیرفعال کردن پرسنل', 'toggle_personnel', 'دسترسی به فعال/غیرفعال کردن پرسنل', '2025-03-01 16:04:28'),
(10, 'بازنشانی رمز عبور', 'reset_password', 'دسترسی به بازنشانی رمز عبور پرسنل', '2025-03-01 16:04:28'),
(11, 'مشاهده نقش‌ها', 'view_roles', 'دسترسی به مشاهده لیست نقش‌ها', '2025-03-01 16:04:28'),
(12, 'افزودن نقش', 'add_role', 'دسترسی به افزودن نقش جدید', '2025-03-01 16:04:28'),
(13, 'ویرایش نقش', 'edit_role', 'دسترسی به ویرایش نقش', '2025-03-01 16:04:28'),
(14, 'حذف نقش', 'delete_role', 'دسترسی به حذف نقش', '2025-03-01 16:04:28'),
(15, 'مدیریت دسترسی‌ها', 'manage_permissions', 'دسترسی به مدیریت دسترسی‌های هر نقش', '2025-03-01 16:04:28'),
(16, 'مشاهده دسته‌بندی‌ها', 'view_categories', 'دسترسی به مشاهده لیست دسته‌بندی‌ها', '2025-03-01 16:04:28'),
(17, 'افزودن دسته‌بندی', 'add_category', 'دسترسی به افزودن دسته‌بندی جدید', '2025-03-01 16:04:28'),
(18, 'ویرایش دسته‌بندی', 'edit_category', 'دسترسی به ویرایش دسته‌بندی', '2025-03-01 16:04:28'),
(19, 'حذف دسته‌بندی', 'delete_category', 'دسترسی به حذف دسته‌بندی', '2025-03-01 16:04:28'),
(20, 'ثبت گزارش روزانه', 'add_daily_report', 'دسترسی به ثبت گزارش روزانه', '2025-03-01 16:04:28'),
(21, 'مشاهده گزارش‌های روزانه', 'view_daily_reports', 'دسترسی به مشاهده گزارش‌های روزانه', '2025-03-01 16:04:28'),
(22, 'ویرایش گزارش روزانه', 'edit_daily_report', 'دسترسی به ویرایش گزارش روزانه', '2025-03-01 16:04:28'),
(23, 'حذف گزارش روزانه', 'delete_daily_report', 'دسترسی به حذف گزارش روزانه', '2025-03-01 16:04:28'),
(24, 'مشاهده شبکه‌های اجتماعی', 'view_social_networks', 'دسترسی به مشاهده لیست شبکه‌های اجتماعی', '2025-03-01 16:04:28'),
(25, 'افزودن شبکه اجتماعی', 'add_social_network', 'دسترسی به افزودن شبکه اجتماعی جدید', '2025-03-01 16:04:28'),
(26, 'ویرایش شبکه اجتماعی', 'edit_social_network', 'دسترسی به ویرایش شبکه اجتماعی', '2025-03-01 16:04:28'),
(27, 'حذف شبکه اجتماعی', 'delete_social_network', 'دسترسی به حذف شبکه اجتماعی', '2025-03-01 16:04:28'),
(28, 'مشاهده فیلدهای شبکه‌ها', 'view_social_fields', 'دسترسی به مشاهده فیلدهای شبکه‌های اجتماعی', '2025-03-01 16:04:28'),
(29, 'افزودن فیلد شبکه', 'add_social_field', 'دسترسی به افزودن فیلد شبکه', '2025-03-01 16:04:28'),
(30, 'ویرایش فیلد شبکه', 'edit_social_field', 'دسترسی به ویرایش فیلد شبکه', '2025-03-01 16:04:28'),
(31, 'حذف فیلد شبکه', 'delete_social_field', 'دسترسی به حذف فیلد شبکه', '2025-03-01 16:04:28'),
(32, 'مشاهده مدل‌های KPI', 'view_kpi_models', 'دسترسی به مشاهده مدل‌های KPI', '2025-03-01 16:04:28'),
(33, 'افزودن مدل KPI', 'add_kpi_model', 'دسترسی به افزودن مدل KPI', '2025-03-01 16:04:28'),
(34, 'ویرایش مدل KPI', 'edit_kpi_model', 'دسترسی به ویرایش مدل KPI', '2025-03-01 16:04:28'),
(35, 'حذف مدل KPI', 'delete_kpi_model', 'دسترسی به حذف مدل KPI', '2025-03-01 16:04:28'),
(36, 'مشاهده صفحات اجتماعی', 'view_social_pages', 'دسترسی به مشاهده صفحات اجتماعی', '2025-03-01 16:04:28'),
(37, 'افزودن صفحه اجتماعی', 'add_social_page', 'دسترسی به افزودن صفحه اجتماعی', '2025-03-01 16:04:28'),
(38, 'ویرایش صفحه اجتماعی', 'edit_social_page', 'دسترسی به ویرایش صفحه اجتماعی', '2025-03-01 16:04:28'),
(39, 'حذف صفحه اجتماعی', 'delete_social_page', 'دسترسی به حذف صفحه اجتماعی', '2025-03-01 16:04:28'),
(40, 'مشاهده KPI های صفحه', 'view_page_kpis', 'دسترسی به مشاهده KPI های صفحه', '2025-03-01 16:04:28'),
(41, 'افزودن KPI صفحه', 'add_page_kpi', 'دسترسی به افزودن KPI به صفحه', '2025-03-01 16:04:28'),
(42, 'ویرایش KPI صفحه', 'edit_page_kpi', 'دسترسی به ویرایش KPI صفحه', '2025-03-01 16:04:28'),
(43, 'حذف KPI صفحه', 'delete_page_kpi', 'دسترسی به حذف KPI صفحه', '2025-03-01 16:04:28'),
(44, 'مشاهده گزارش‌های اجتماعی', 'view_social_reports', 'دسترسی به مشاهده گزارش‌های شبکه‌های اجتماعی', '2025-03-01 16:04:28'),
(45, 'افزودن گزارش اجتماعی', 'add_social_report', 'دسترسی به افزودن گزارش شبکه‌های اجتماعی', '2025-03-01 16:04:28'),
(46, 'ویرایش گزارش اجتماعی', 'edit_social_report', 'دسترسی به ویرایش گزارش شبکه‌های اجتماعی', '2025-03-01 16:04:28'),
(47, 'حذف گزارش اجتماعی', 'delete_social_report', 'دسترسی به حذف گزارش شبکه‌های اجتماعی', '2025-03-01 16:04:28'),
(48, 'مشاهده عملکرد مورد انتظار', 'view_expected_performance', 'دسترسی به مشاهده عملکرد مورد انتظار', '2025-03-01 16:04:28'),
(49, 'مشاهده صندوق پیام', 'view_messages', 'دسترسی به مشاهده صندوق پیام', '2025-03-01 17:08:51'),
(50, 'ارسال پیام', 'send_message', 'دسترسی به ارسال پیام', '2025-03-01 17:08:51'),
(51, 'ارسال پیام زمانبندی شده', 'schedule_message', 'دسترسی به ارسال پیام زمانبندی شده', '2025-03-01 17:08:51'),
(52, 'مدیریت پیام‌های ارسالی', 'manage_sent_messages', 'دسترسی به مدیریت پیام‌های ارسال شده', '2025-03-01 17:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `can_receive_reports` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `company_id`, `role_id`, `full_name`, `gender`, `email`, `mobile`, `is_active`, `username`, `password`, `created_at`, `can_receive_reports`) VALUES
(1, 1, 1, 'علیرضا ترابی', 'male', 'ss@j.com', '0933004', 1, '386', '$2y$10$ioPp1URXDWlDkdzHpXYe8.UcPbp/p7rRxeyNuEUVbhyuIPqjZDGvu', '2025-03-01 06:13:55', 1),
(2, 1, 2, 'عرفان عباسپور', 'male', 'ceo@milad-ahmadi.com', '555', 1, '465', '$2y$10$RdsPTFMkVHrcDkt1nvaR8uCrK/HK9ftcZTe1VxhUbKPazU3UTi9r2', '2025-03-01 06:14:16', 0),
(3, 2, 2, 'سید علی سادات', 'male', 'alisadatakbarosadat@gmail.com', '09918859261', 1, '584', '$2y$10$/F9/vzBfXvmpevvG0GDfM.aZ/jLspRWRGnC9tb5xWCVLTYMU7FAri', '2025-03-02 05:07:14', 0),
(4, 2, 2, 'آتنا سادات', 'female', 'test@gmail.com', '09302116969', 1, '508', '$2y$10$s2jdZxQ6e4kUKIvy0ewusOP4giuZ9VL6GT3J375r/3n05QeDdk722', '2025-03-02 05:08:09', 0),
(5, 1, 2, 'مسائلی', 'female', 'dsfdf@gffg.vom', '0545', 1, '930', '$2y$10$RBHc7iaTvE06n9RhIv3Lsu8nD/59L8DoKTVjpy7zakqnyRSgyfzbG', '2025-03-03 07:57:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_publish_platform_relations`
--

CREATE TABLE `post_publish_platform_relations` (
  `process_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `personnel_id`, `report_date`, `created_at`) VALUES
(1, 2, '2025-03-01', '2025-03-01 06:48:01');

-- --------------------------------------------------------

--
-- Table structure for table `report_items`
--

CREATE TABLE `report_items` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `report_items`
--

INSERT INTO `report_items` (`id`, `report_id`, `content`, `created_at`) VALUES
(1, 1, 'طراحی پوستر', '2025-03-01 06:48:01');

-- --------------------------------------------------------

--
-- Table structure for table `report_item_categories`
--

CREATE TABLE `report_item_categories` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `report_item_categories`
--

INSERT INTO `report_item_categories` (`item_id`, `category_id`) VALUES
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `report_scores`
--

CREATE TABLE `report_scores` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `expected_value` decimal(10,2) NOT NULL,
  `actual_value` decimal(10,2) NOT NULL,
  `score` decimal(3,1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `report_scores`
--

INSERT INTO `report_scores` (`id`, `report_id`, `field_id`, `expected_value`, `actual_value`, `score`, `created_at`) VALUES
(1, 2, 2, '546.52', '500.00', '6.4', '2025-03-01 06:41:36'),
(2, 2, 3, '21.86', '20.00', '6.4', '2025-03-01 06:41:36'),
(3, 2, 4, '54.65', '50.00', '6.4', '2025-03-01 06:41:36'),
(4, 2, 5, '50.00', '20.00', '2.8', '2025-03-01 06:41:36'),
(5, 2, 6, '2.00', '55.00', '7.0', '2025-03-01 06:41:36'),
(6, 3, 2, '500.00', '560.00', '7.0', '2025-03-01 06:43:34'),
(7, 3, 3, '20.00', '5.00', '1.8', '2025-03-01 06:43:34'),
(8, 3, 4, '50.00', '66.00', '7.0', '2025-03-01 06:43:34'),
(9, 3, 5, '50.00', '20.00', '2.8', '2025-03-01 06:43:34'),
(10, 3, 6, '2.00', '500.00', '7.0', '2025-03-01 06:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_ceo` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `is_ceo`, `created_at`) VALUES
(1, 'مدیر عامل', 1, '2025-03-01 06:11:46'),
(2, 'کارمند', 0, '2025-03-01 06:13:29'),
(3, 'مدیر2', 1, '2025-03-03 07:00:08');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39),
(1, 40),
(1, 41),
(1, 42),
(1, 43),
(1, 44),
(1, 45),
(1, 46),
(1, 47),
(1, 48);

-- --------------------------------------------------------

--
-- Table structure for table `social_networks`
--

CREATE TABLE `social_networks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `social_networks`
--

INSERT INTO `social_networks` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'Instagram', 'fab fa-instagram', '2025-03-01 06:11:46'),
(2, 'Twitter', 'fab fa-twitter', '2025-03-01 06:11:46'),
(3, 'Facebook', 'fab fa-facebook', '2025-03-01 06:11:46'),
(4, 'LinkedIn', 'fab fa-linkedin', '2025-03-01 06:11:46'),
(5, 'YouTube', 'fab fa-youtube', '2025-03-01 06:11:46'),
(6, 'TikTok', 'fab fa-tiktok', '2025-03-01 06:11:46'),
(7, 'Pinterest', 'fab fa-pinterest', '2025-03-01 06:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `social_network_fields`
--

CREATE TABLE `social_network_fields` (
  `id` int(11) NOT NULL,
  `social_network_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `field_type` enum('text','number','date','url') NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `is_kpi` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `social_network_fields`
--

INSERT INTO `social_network_fields` (`id`, `social_network_id`, `field_name`, `field_label`, `field_type`, `is_required`, `is_kpi`, `sort_order`, `created_at`) VALUES
(1, 1, 'instagram_url', 'آدرس اینستاگرام', 'text', 1, 0, 1, '2025-03-01 06:11:46'),
(2, 1, 'followers', 'تعداد فالوور', 'number', 1, 1, 2, '2025-03-01 06:11:46'),
(3, 1, 'engagement', 'تعداد تعامل', 'number', 1, 1, 3, '2025-03-01 06:11:46'),
(4, 1, 'views', 'تعداد بازدید', 'number', 1, 1, 4, '2025-03-01 06:11:46'),
(5, 1, 'leads', 'تعداد لید', 'number', 0, 1, 5, '2025-03-01 06:11:46'),
(6, 1, 'customers', 'تعداد مشتری', 'number', 0, 1, 6, '2025-03-01 06:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `social_pages`
--

CREATE TABLE `social_pages` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `social_network_id` int(11) NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `social_pages`
--

INSERT INTO `social_pages` (`id`, `company_id`, `social_network_id`, `page_name`, `page_url`, `start_date`, `created_at`) VALUES
(1, 1, 1, 'اینستاگرام پیروز پک', 'http://localhost/report2/social_pages.php', '2025-03-01', '2025-03-01 06:33:47');

-- --------------------------------------------------------

--
-- Table structure for table `social_page_fields`
--

CREATE TABLE `social_page_fields` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `social_page_fields`
--

INSERT INTO `social_page_fields` (`id`, `page_id`, `field_id`, `field_value`, `created_at`) VALUES
(1, 1, 1, 'http://localhost/report2/social_pages.php', '2025-03-01 06:33:47'),
(2, 1, 2, '500', '2025-03-01 06:33:47'),
(3, 1, 3, '20', '2025-03-01 06:33:47'),
(4, 1, 4, '50', '2025-03-01 06:33:47'),
(5, 1, 5, '20', '2025-03-01 06:33:47'),
(6, 1, 6, '55', '2025-03-01 06:33:47');

-- --------------------------------------------------------

--
-- Table structure for table `template_audience_relations`
--

CREATE TABLE `template_audience_relations` (
  `template_id` int(11) NOT NULL,
  `audience_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `template_platform_relations`
--

CREATE TABLE `template_platform_relations` (
  `template_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `template_task_relations`
--

CREATE TABLE `template_task_relations` (
  `template_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `template_topic_relations`
--

CREATE TABLE `template_topic_relations` (
  `template_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `template_type_relations`
--

CREATE TABLE `template_type_relations` (
  `template_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coach_reports`
--
ALTER TABLE `coach_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_id` (`coach_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `coach_report_access`
--
ALTER TABLE `coach_report_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `coach_report_personnel`
--
ALTER TABLE `coach_report_personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coach_report_id` (`coach_report_id`,`personnel_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `coach_report_social_reports`
--
ALTER TABLE `coach_report_social_reports`
  ADD PRIMARY KEY (`coach_report_id`,`social_report_id`),
  ADD KEY `social_report_id` (`social_report_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contents`
--
ALTER TABLE `contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `production_status_id` (`production_status_id`),
  ADD KEY `publish_status_id` (`publish_status_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `content_audience_relations`
--
ALTER TABLE `content_audience_relations`
  ADD PRIMARY KEY (`content_id`,`audience_id`),
  ADD KEY `audience_id` (`audience_id`);

--
-- Indexes for table `content_calendar_settings`
--
ALTER TABLE `content_calendar_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`field_name`);

--
-- Indexes for table `content_formats`
--
ALTER TABLE `content_formats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_platforms`
--
ALTER TABLE `content_platforms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_platform_relations`
--
ALTER TABLE `content_platform_relations`
  ADD PRIMARY KEY (`content_id`,`platform_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Indexes for table `content_post_publish_processes`
--
ALTER TABLE `content_post_publish_processes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `format_id` (`format_id`);

--
-- Indexes for table `content_production_statuses`
--
ALTER TABLE `content_production_statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_publish_statuses`
--
ALTER TABLE `content_publish_statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_target_audiences`
--
ALTER TABLE `content_target_audiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_tasks`
--
ALTER TABLE `content_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_task_assignments`
--
ALTER TABLE `content_task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `content_templates`
--
ALTER TABLE `content_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `content_topics`
--
ALTER TABLE `content_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_topic_relations`
--
ALTER TABLE `content_topic_relations`
  ADD PRIMARY KEY (`content_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `content_types`
--
ALTER TABLE `content_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `content_type_relations`
--
ALTER TABLE `content_type_relations`
  ADD PRIMARY KEY (`content_id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `kpi_models`
--
ALTER TABLE `kpi_models`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `scheduled_time` (`scheduled_time`),
  ADD KEY `is_sent` (`is_sent`);

--
-- Indexes for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `monthly_report_values`
--
ALTER TABLE `monthly_report_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `page_kpis`
--
ALTER TABLE `page_kpis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `kpi_model_id` (`kpi_model_id`),
  ADD KEY `related_field_id` (`related_field_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `post_publish_platform_relations`
--
ALTER TABLE `post_publish_platform_relations`
  ADD PRIMARY KEY (`process_id`,`platform_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `report_items`
--
ALTER TABLE `report_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `report_item_categories`
--
ALTER TABLE `report_item_categories`
  ADD PRIMARY KEY (`item_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `report_scores`
--
ALTER TABLE `report_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `social_networks`
--
ALTER TABLE `social_networks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `social_network_fields`
--
ALTER TABLE `social_network_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `social_network_id` (`social_network_id`);

--
-- Indexes for table `social_pages`
--
ALTER TABLE `social_pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `social_network_id` (`social_network_id`);

--
-- Indexes for table `social_page_fields`
--
ALTER TABLE `social_page_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `template_audience_relations`
--
ALTER TABLE `template_audience_relations`
  ADD PRIMARY KEY (`template_id`,`audience_id`),
  ADD KEY `audience_id` (`audience_id`);

--
-- Indexes for table `template_platform_relations`
--
ALTER TABLE `template_platform_relations`
  ADD PRIMARY KEY (`template_id`,`platform_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Indexes for table `template_task_relations`
--
ALTER TABLE `template_task_relations`
  ADD PRIMARY KEY (`template_id`,`task_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `template_topic_relations`
--
ALTER TABLE `template_topic_relations`
  ADD PRIMARY KEY (`template_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `template_type_relations`
--
ALTER TABLE `template_type_relations`
  ADD PRIMARY KEY (`template_id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coach_reports`
--
ALTER TABLE `coach_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `coach_report_access`
--
ALTER TABLE `coach_report_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coach_report_personnel`
--
ALTER TABLE `coach_report_personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contents`
--
ALTER TABLE `contents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_calendar_settings`
--
ALTER TABLE `content_calendar_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `content_formats`
--
ALTER TABLE `content_formats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `content_platforms`
--
ALTER TABLE `content_platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_post_publish_processes`
--
ALTER TABLE `content_post_publish_processes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_production_statuses`
--
ALTER TABLE `content_production_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `content_publish_statuses`
--
ALTER TABLE `content_publish_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `content_target_audiences`
--
ALTER TABLE `content_target_audiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_tasks`
--
ALTER TABLE `content_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `content_task_assignments`
--
ALTER TABLE `content_task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_templates`
--
ALTER TABLE `content_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_topics`
--
ALTER TABLE `content_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_types`
--
ALTER TABLE `content_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_models`
--
ALTER TABLE `kpi_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `monthly_report_values`
--
ALTER TABLE `monthly_report_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `page_kpis`
--
ALTER TABLE `page_kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_items`
--
ALTER TABLE `report_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_scores`
--
ALTER TABLE `report_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `social_networks`
--
ALTER TABLE `social_networks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `social_network_fields`
--
ALTER TABLE `social_network_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `social_pages`
--
ALTER TABLE `social_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `social_page_fields`
--
ALTER TABLE `social_page_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coach_reports`
--
ALTER TABLE `coach_reports`
  ADD CONSTRAINT `coach_reports_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coach_reports_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_report_access`
--
ALTER TABLE `coach_report_access`
  ADD CONSTRAINT `coach_report_access_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coach_report_access_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_report_personnel`
--
ALTER TABLE `coach_report_personnel`
  ADD CONSTRAINT `coach_report_personnel_ibfk_1` FOREIGN KEY (`coach_report_id`) REFERENCES `coach_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coach_report_personnel_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_report_social_reports`
--
ALTER TABLE `coach_report_social_reports`
  ADD CONSTRAINT `coach_report_social_reports_ibfk_1` FOREIGN KEY (`coach_report_id`) REFERENCES `coach_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coach_report_social_reports_ibfk_2` FOREIGN KEY (`social_report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contents`
--
ALTER TABLE `contents`
  ADD CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contents_ibfk_2` FOREIGN KEY (`production_status_id`) REFERENCES `content_production_statuses` (`id`),
  ADD CONSTRAINT `contents_ibfk_3` FOREIGN KEY (`publish_status_id`) REFERENCES `content_publish_statuses` (`id`),
  ADD CONSTRAINT `contents_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_audience_relations`
--
ALTER TABLE `content_audience_relations`
  ADD CONSTRAINT `content_audience_relations_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_audience_relations_ibfk_2` FOREIGN KEY (`audience_id`) REFERENCES `content_target_audiences` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_calendar_settings`
--
ALTER TABLE `content_calendar_settings`
  ADD CONSTRAINT `content_calendar_settings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_formats`
--
ALTER TABLE `content_formats`
  ADD CONSTRAINT `content_formats_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_platforms`
--
ALTER TABLE `content_platforms`
  ADD CONSTRAINT `content_platforms_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_platform_relations`
--
ALTER TABLE `content_platform_relations`
  ADD CONSTRAINT `content_platform_relations_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_platform_relations_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `content_platforms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_post_publish_processes`
--
ALTER TABLE `content_post_publish_processes`
  ADD CONSTRAINT `content_post_publish_processes_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_post_publish_processes_ibfk_2` FOREIGN KEY (`format_id`) REFERENCES `content_formats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_production_statuses`
--
ALTER TABLE `content_production_statuses`
  ADD CONSTRAINT `content_production_statuses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_publish_statuses`
--
ALTER TABLE `content_publish_statuses`
  ADD CONSTRAINT `content_publish_statuses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_target_audiences`
--
ALTER TABLE `content_target_audiences`
  ADD CONSTRAINT `content_target_audiences_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_tasks`
--
ALTER TABLE `content_tasks`
  ADD CONSTRAINT `content_tasks_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_task_assignments`
--
ALTER TABLE `content_task_assignments`
  ADD CONSTRAINT `content_task_assignments_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_task_assignments_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `content_tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_task_assignments_ibfk_3` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_templates`
--
ALTER TABLE `content_templates`
  ADD CONSTRAINT `content_templates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_templates_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_topics`
--
ALTER TABLE `content_topics`
  ADD CONSTRAINT `content_topics_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_topic_relations`
--
ALTER TABLE `content_topic_relations`
  ADD CONSTRAINT `content_topic_relations_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_topic_relations_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `content_topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_types`
--
ALTER TABLE `content_types`
  ADD CONSTRAINT `content_types_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_type_relations`
--
ALTER TABLE `content_type_relations`
  ADD CONSTRAINT `content_type_relations_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_type_relations_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD CONSTRAINT `monthly_reports_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `social_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_report_values`
--
ALTER TABLE `monthly_report_values`
  ADD CONSTRAINT `monthly_report_values_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_report_values_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `social_network_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_kpis`
--
ALTER TABLE `page_kpis`
  ADD CONSTRAINT `page_kpis_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `social_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_kpis_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `social_network_fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_kpis_ibfk_3` FOREIGN KEY (`kpi_model_id`) REFERENCES `kpi_models` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_kpis_ibfk_4` FOREIGN KEY (`related_field_id`) REFERENCES `social_network_fields` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `personnel_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `post_publish_platform_relations`
--
ALTER TABLE `post_publish_platform_relations`
  ADD CONSTRAINT `post_publish_platform_relations_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `content_post_publish_processes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_publish_platform_relations_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `content_platforms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_items`
--
ALTER TABLE `report_items`
  ADD CONSTRAINT `report_items_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_item_categories`
--
ALTER TABLE `report_item_categories`
  ADD CONSTRAINT `report_item_categories_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `report_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_item_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_scores`
--
ALTER TABLE `report_scores`
  ADD CONSTRAINT `report_scores_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_scores_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `social_network_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_network_fields`
--
ALTER TABLE `social_network_fields`
  ADD CONSTRAINT `social_network_fields_ibfk_1` FOREIGN KEY (`social_network_id`) REFERENCES `social_networks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_pages`
--
ALTER TABLE `social_pages`
  ADD CONSTRAINT `social_pages_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `social_pages_ibfk_2` FOREIGN KEY (`social_network_id`) REFERENCES `social_networks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_page_fields`
--
ALTER TABLE `social_page_fields`
  ADD CONSTRAINT `social_page_fields_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `social_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `social_page_fields_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `social_network_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_audience_relations`
--
ALTER TABLE `template_audience_relations`
  ADD CONSTRAINT `template_audience_relations_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `content_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_audience_relations_ibfk_2` FOREIGN KEY (`audience_id`) REFERENCES `content_target_audiences` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_platform_relations`
--
ALTER TABLE `template_platform_relations`
  ADD CONSTRAINT `template_platform_relations_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `content_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_platform_relations_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `content_platforms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_task_relations`
--
ALTER TABLE `template_task_relations`
  ADD CONSTRAINT `template_task_relations_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `content_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_task_relations_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `content_tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_topic_relations`
--
ALTER TABLE `template_topic_relations`
  ADD CONSTRAINT `template_topic_relations_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `content_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_topic_relations_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `content_topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_type_relations`
--
ALTER TABLE `template_type_relations`
  ADD CONSTRAINT `template_type_relations_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `content_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_type_relations_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
