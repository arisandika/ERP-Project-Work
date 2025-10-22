-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 30, 2025 at 02:53 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_erp_nexicon`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint UNSIGNED DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(1, 'Resource', 'Department Created by Admin', 'App\\Models\\HR\\Department', 'Created', 2, 'App\\Models\\User', 1, '{\"id\": 2, \"code\": \"IT\", \"name\": \"Information Technology\", \"created_at\": \"2025-09-28 13:24:40\", \"updated_at\": \"2025-09-28 13:24:40\"}', NULL, '2025-09-28 06:24:40', '2025-09-28 06:24:40'),
(2, 'Resource', 'Employee Created by Admin', 'App\\Models\\HR\\Employee', 'Created', 1, 'App\\Models\\User', 1, '{\"id\": 1, \"email\": \"ari@nexicon.id\", \"photo\": \"employees/photos/01K688FT7TE69Y02307X22CK7R.jpg\", \"status\": \"active\", \"address\": \"Quia natus facilis reprehenderit nesciunt sint distinctio\", \"user_id\": 3, \"position\": \"senior\", \"full_name\": \"Harlan Riley\", \"created_at\": \"2025-09-28 13:34:10\", \"updated_at\": \"2025-09-28 13:34:10\", \"phone_number\": \"+1 (997) 762-9475\", \"contract_type\": \"intern\", \"department_id\": \"2\", \"face_landmarks\": null, \"face_embeddings\": null, \"face_embedding_path\": null}', NULL, '2025-09-28 06:34:10', '2025-09-28 06:34:10'),
(3, 'Resource', 'Employee Updated by Admin', 'App\\Models\\HR\\Employee', 'Updated', 1, 'App\\Models\\User', 1, '{\"address\": \"Tangerang\", \"position\": \"lead\", \"full_name\": \"Ari Sandika\", \"updated_at\": \"2025-09-28 13:35:25\", \"phone_number\": \"088822223333\", \"contract_type\": \"permanent\"}', NULL, '2025-09-28 06:35:25', '2025-09-28 06:35:25'),
(4, 'Access', 'Harlan Riley logged in', NULL, 'Login', NULL, 'App\\Models\\User', 3, '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}', NULL, '2025-09-28 06:50:26', '2025-09-28 06:50:26'),
(5, 'Resource', 'Employee Updated by Harlan Riley', 'App\\Models\\HR\\Employee', 'Updated', 1, 'App\\Models\\User', 3, '{\"full_name\": \"Ari Sandika 123\", \"updated_at\": \"2025-09-28 13:57:49\"}', NULL, '2025-09-28 06:57:49', '2025-09-28 06:57:49'),
(6, 'Access', 'Ari Sandika 123 logged in', NULL, 'Login', NULL, 'App\\Models\\User', 3, '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}', NULL, '2025-09-28 06:58:12', '2025-09-28 06:58:12'),
(7, 'Resource', 'Employee Updated by Ari Sandika 123', 'App\\Models\\HR\\Employee', 'Updated', 1, 'App\\Models\\User', 3, '{\"full_name\": \"Ari Sandika\", \"updated_at\": \"2025-09-28 13:58:41\"}', NULL, '2025-09-28 06:58:41', '2025-09-28 06:58:41'),
(8, 'Resource', 'Shift Created by Ari Sandika', 'App\\Models\\HR\\Shift', 'Created', 1, 'App\\Models\\User', 3, '{\"id\": 1, \"name\": \"Normal Daily\", \"end_time\": \"17:00:00\", \"created_at\": \"2025-09-28 14:13:18\", \"start_time\": \"09:00:00\", \"updated_at\": \"2025-09-28 14:13:18\", \"tolerance_minutes\": \"30\"}', NULL, '2025-09-28 07:13:18', '2025-09-28 07:13:18'),
(9, 'Resource', 'Office Created by Ari Sandika', 'App\\Models\\HR\\Office', 'Created', 1, 'App\\Models\\User', 3, '{\"id\": 1, \"name\": \"Headquarter\", \"created_at\": \"2025-09-28 16:05:10\", \"updated_at\": \"2025-09-28 16:05:10\", \"radius_meters\": 100}', NULL, '2025-09-28 09:05:10', '2025-09-28 09:05:10'),
(10, 'Resource', 'Office Updated by Ari Sandika', 'App\\Models\\HR\\Office', 'Updated', 1, 'App\\Models\\User', 3, '{\"latitude\": -6.157162256125016, \"longitude\": 106.58316801363162, \"updated_at\": \"2025-09-28 16:14:45\"}', NULL, '2025-09-28 09:14:45', '2025-09-28 09:14:45'),
(11, 'Access', 'Ari Sandika logged in', NULL, 'Login', NULL, 'App\\Models\\User', 3, '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}', NULL, '2025-09-29 08:35:07', '2025-09-29 08:35:07'),
(12, 'Access', 'Ari Sandika logged in', NULL, 'Login', NULL, 'App\\Models\\User', 3, '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}', NULL, '2025-09-30 06:55:05', '2025-09-30 06:55:05'),
(13, 'Access', 'Ari Sandika logged in', NULL, 'Login', NULL, 'App\\Models\\User', 3, '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}', NULL, '2025-09-30 07:01:06', '2025-09-30 07:01:06'),
(14, 'Resource', 'Role Created by Ari Sandika', 'Spatie\\Permission\\Models\\Role', 'Created', 2, 'App\\Models\\User', 3, '{\"id\": 2, \"name\": \"HR Employees\", \"created_at\": \"2025-09-30 14:08:57\", \"guard_name\": \"web\", \"updated_at\": \"2025-09-30 14:08:57\"}', NULL, '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(15, 'Resource', 'Department Created by Ari Sandika', 'App\\Models\\HR\\Department', 'Created', 3, 'App\\Models\\User', 3, '{\"id\": 3, \"code\": \"HR\", \"name\": \"Human Resource\", \"created_at\": \"2025-09-30 14:11:00\", \"updated_at\": \"2025-09-30 14:11:00\"}', NULL, '2025-09-30 07:11:00', '2025-09-30 07:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `shift_id` bigint UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `latitude_in` decimal(10,7) DEFAULT NULL,
  `longitude_in` decimal(10,7) DEFAULT NULL,
  `latitude_out` decimal(10,7) DEFAULT NULL,
  `longitude_out` decimal(10,7) DEFAULT NULL,
  `face_snapshot_in` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_snapshot_out` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_verified_in` tinyint(1) NOT NULL DEFAULT '0',
  `face_verified_out` tinyint(1) NOT NULL DEFAULT '0',
  `face_similarity_in` double DEFAULT NULL,
  `face_similarity_out` double DEFAULT NULL,
  `status` enum('present','late','absent','leave') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `breezy_sessions`
--

CREATE TABLE `breezy_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `authenticatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authenticatable_id` bigint UNSIGNED NOT NULL,
  `panel_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1759066482),
('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1759066482;', 1759066482),
('laravel-cache-77de68daecd823babbb58edb1c8e14d7106e83bb', 'i:1;', 1759241496),
('laravel-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer', 'i:1759241496;', 1759241496),
('laravel-cache-livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3', 'i:1;', 1759240925),
('laravel-cache-livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer', 'i:1759240924;', 1759240924),
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:48:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:21:\"view_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:25:\"view_any_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:23:\"create_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:23:\"update_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:24:\"restore_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:28:\"restore_any_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:26:\"replicate_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"reorder_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:23:\"delete_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:27:\"delete_any_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:29:\"force_delete_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:33:\"force_delete_any_h::r::department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:19:\"view_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:23:\"view_any_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:21:\"create_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:21:\"update_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:22:\"restore_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:26:\"restore_any_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:24:\"replicate_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:22:\"reorder_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:21:\"delete_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:25:\"delete_any_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:27:\"force_delete_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:31:\"force_delete_any_h::r::employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:17:\"view_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:21:\"view_any_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:19:\"create_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:19:\"update_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:20:\"restore_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:24:\"restore_any_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:22:\"replicate_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:20:\"reorder_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:19:\"delete_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:23:\"delete_any_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:25:\"force_delete_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:29:\"force_delete_any_h::r::office\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:16:\"view_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:20:\"view_any_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:18:\"create_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:18:\"update_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:19:\"restore_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:23:\"restore_any_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:21:\"replicate_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:19:\"reorder_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:18:\"delete_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:22:\"delete_any_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:24:\"force_delete_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:28:\"force_delete_any_h::r::shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}}s:5:\"roles\";a:1:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"HR Employees\";s:1:\"c\";s:3:\"web\";}}}', 1759327743);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_09_28_101808_create_departments_table', 1),
(5, '2025_09_28_102902_create_employees_table', 1),
(6, '2025_09_28_103517_create_shifts_table', 1),
(7, '2025_09_28_103557_create_attendances_table', 1),
(8, '2025_09_28_103652_create_leaves_table', 1),
(9, '2025_09_28_104557_create_offices_table', 1),
(10, '2025_09_28_114830_create_permission_tables', 2),
(11, '2025_09_28_115235_create_breezy_sessions_table', 3),
(12, '2025_09_28_115421_create_activity_log_table', 4),
(13, '2025_09_28_115422_add_event_column_to_activity_log_table', 4),
(14, '2025_09_28_115423_add_batch_uuid_column_to_activity_log_table', 4);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\HR\\Employee', 1),
(1, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `nx_departments`
--

CREATE TABLE `nx_departments` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nx_departments`
--

INSERT INTO `nx_departments` (`id`, `name`, `code`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Finance', 'FNC', NULL, '2025-09-28 05:59:24', '2025-09-28 05:59:24'),
(2, 'Information Technology', 'IT', NULL, '2025-09-28 06:24:40', '2025-09-28 06:24:40'),
(3, 'Human Resource', 'HR', NULL, '2025-09-30 07:11:00', '2025-09-30 07:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `nx_employees`
--

CREATE TABLE `nx_employees` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_type` enum('permanent','contract','intern') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contract',
  `status` enum('active','resigned','terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `face_embeddings` json DEFAULT NULL,
  `face_embedding_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_landmarks` json DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nx_employees`
--

INSERT INTO `nx_employees` (`id`, `user_id`, `department_id`, `full_name`, `email`, `phone_number`, `photo`, `address`, `position`, `contract_type`, `status`, `face_embeddings`, `face_embedding_path`, `face_landmarks`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 3, 2, 'Ari Sandika', 'ari@nexicon.id', '088822223333', 'employees/photos/01K688FT7TE69Y02307X22CK7R.jpg', 'Tangerang', 'lead', 'permanent', 'active', NULL, NULL, NULL, NULL, '2025-09-28 06:34:10', '2025-09-28 06:58:41');

-- --------------------------------------------------------

--
-- Table structure for table `nx_leaves`
--

CREATE TABLE `nx_leaves` (
  `id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `leave_type` enum('sick','annual','permission') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nx_offices`
--

CREATE TABLE `nx_offices` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `radius_meters` int NOT NULL DEFAULT '100',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nx_offices`
--

INSERT INTO `nx_offices` (`id`, `name`, `latitude`, `longitude`, `radius_meters`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Headquarter', -6.157162256125, 106.58316801363, 100, NULL, '2025-09-28 09:05:10', '2025-09-28 09:14:45');

-- --------------------------------------------------------

--
-- Table structure for table `nx_shifts`
--

CREATE TABLE `nx_shifts` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `tolerance_minutes` int NOT NULL DEFAULT '10',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nx_shifts`
--

INSERT INTO `nx_shifts` (`id`, `name`, `start_time`, `end_time`, `tolerance_minutes`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Normal Daily', '09:00:00', '17:00:00', 30, NULL, '2025-09-28 07:13:18', '2025-09-28 07:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(2, 'view_any_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(3, 'create_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(4, 'update_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(5, 'restore_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(6, 'restore_any_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(7, 'replicate_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(8, 'reorder_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(9, 'delete_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(10, 'delete_any_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(11, 'force_delete_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(12, 'force_delete_any_h::r::department', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(13, 'view_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(14, 'view_any_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(15, 'create_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(16, 'update_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(17, 'restore_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(18, 'restore_any_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(19, 'replicate_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(20, 'reorder_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(21, 'delete_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(22, 'delete_any_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(23, 'force_delete_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(24, 'force_delete_any_h::r::employee', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(25, 'view_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(26, 'view_any_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(27, 'create_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(28, 'update_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(29, 'restore_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(30, 'restore_any_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(31, 'replicate_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(32, 'reorder_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(33, 'delete_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(34, 'delete_any_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(35, 'force_delete_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(36, 'force_delete_any_h::r::office', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(37, 'view_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(38, 'view_any_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(39, 'create_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(40, 'update_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(41, 'restore_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(42, 'restore_any_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(43, 'replicate_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(44, 'reorder_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(45, 'delete_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(46, 'delete_any_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(47, 'force_delete_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58'),
(48, 'force_delete_any_h::r::shift', 'web', '2025-09-30 07:08:58', '2025-09-30 07:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'web', '2025-09-28 04:51:45', '2025-09-28 04:51:45'),
(2, 'HR Employees', 'web', '2025-09-30 07:08:57', '2025-09-30 07:08:57');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2),
(16, 2),
(17, 2),
(18, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(23, 2),
(24, 2),
(25, 2),
(26, 2),
(27, 2),
(28, 2),
(29, 2),
(30, 2),
(31, 2),
(32, 2),
(33, 2),
(34, 2),
(35, 2),
(36, 2),
(37, 2),
(38, 2),
(39, 2),
(40, 2),
(41, 2),
(42, 2),
(43, 2),
(44, 2),
(45, 2),
(46, 2),
(47, 2),
(48, 2);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('9iaHOd02Wj0g7uB9fn0iURY2UHIDnWXM77a1UpAG', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT3Y4Z1dPcG04a0VGblFLQ1QydXRIb0lKOURocVQ4YVRrdjNxV0FaVSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMToiaHR0cDovL2xvY2FsaG9zdDo4MDAwIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1759146226),
('DGdSPkaQPifjYFCSWnvsE0uexYAvharAly1hh4lP', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo3OntzOjM6InVybCI7YTowOnt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9oLXIvb2ZmaWNlcy8xL2VkaXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjY6Il90b2tlbiI7czo0MDoiU3F2QkZoeUMxVU9KQ292RkxpT1U5MGNMRTZ0dnV4STVvQzEydHhoRCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MztzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJGhjUnE1RGNRaWVra1dwaVY3MVhZZ3VmajkuanhUakdnUzFTTmZOakFvdlVLUmw0bG9XVzBpIjtzOjg6ImZpbGFtZW50IjthOjA6e319', 1759076138),
('Et5wtWRhEdijRqOiq8ZPHDeklWGHEv81vq5h9gMy', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaEY5aWJ6WHBCaHRpU2M2Z0NDdnNGRGJYQXViRHA5QldDWDRDRzlPQiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiRoY1JxNURjUWlla2tXcGlWNzFYWWd1Zmo5Lmp4VGpHZ1MxU05mTmpBb3ZVS1JsNGxvV1cwaSI7fQ==', 1759160142),
('FyrvXbhWoFkTYB3yzviFJrmiseoAaR3BS0a0I66C', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiS2dFRDJTcTMzcFY5S2FzMzg3NTlOZlZLbDN4RFp4cVpqd0RlSk14QiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ3OiJodHRwOi8vbG9jYWxob3N0OjgwMDAvaHItbWFuYWdlbWVudC9lbXBsb3llZXMvMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiRoY1JxNURjUWlla2tXcGlWNzFYWWd1Zmo5Lmp4VGpHZ1MxU05mTmpBb3ZVS1JsNGxvV1cwaSI7czo4OiJmaWxhbWVudCI7YTowOnt9fQ==', 1759242157),
('p7cTJzfU6zDnzBZrUyYnWIIc6q3ZLCsvTj1JqVBZ', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiSDhUVW9TeWpoeWJHcUNlMk02VFNBSmc0U1oyRGFncExrME9ZcHJldCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMzOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvaC1yL29mZmljZXMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkaGNScTVEY1FpZWtrV3BpVjcxWFlndWZqOS5qeFRqR2dTMVNOZk5qQW92VUtSbDRsb1dXMGkiO30=', 1759240660);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@nexicon.id', NULL, '$2y$12$TMqZZ.yBeoZ3YkNT3ToAn.9dh4qZEJZLW9oDQ3ZsV5V88j9AN6XZq', NULL, '2025-09-28 04:07:14', '2025-09-28 04:07:14'),
(3, 'Ari Sandika', 'ari@nexicon.id', NULL, '$2y$12$hcRq5DcQiekkWpiV71XYgufj9.jxTjGgS1SNfNjAovUKRl4loWW0i', NULL, '2025-09-28 06:34:10', '2025-09-28 06:58:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_employee_id_foreign` (`employee_id`),
  ADD KEY `attendances_shift_id_foreign` (`shift_id`);

--
-- Indexes for table `breezy_sessions`
--
ALTER TABLE `breezy_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `breezy_sessions_authenticatable_type_authenticatable_id_index` (`authenticatable_type`,`authenticatable_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `nx_departments`
--
ALTER TABLE `nx_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nx_departments_code_unique` (`code`);

--
-- Indexes for table `nx_employees`
--
ALTER TABLE `nx_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nx_employees_email_unique` (`email`),
  ADD UNIQUE KEY `nx_employees_phone_number_unique` (`phone_number`),
  ADD KEY `nx_employees_user_id_foreign` (`user_id`),
  ADD KEY `nx_employees_department_id_foreign` (`department_id`);

--
-- Indexes for table `nx_leaves`
--
ALTER TABLE `nx_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nx_leaves_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `nx_offices`
--
ALTER TABLE `nx_offices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nx_shifts`
--
ALTER TABLE `nx_shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `breezy_sessions`
--
ALTER TABLE `breezy_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `nx_departments`
--
ALTER TABLE `nx_departments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nx_employees`
--
ALTER TABLE `nx_employees`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nx_leaves`
--
ALTER TABLE `nx_leaves`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nx_offices`
--
ALTER TABLE `nx_offices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nx_shifts`
--
ALTER TABLE `nx_shifts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `nx_employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `nx_shifts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nx_employees`
--
ALTER TABLE `nx_employees`
  ADD CONSTRAINT `nx_employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `nx_departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nx_employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nx_leaves`
--
ALTER TABLE `nx_leaves`
  ADD CONSTRAINT `nx_leaves_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `nx_employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
