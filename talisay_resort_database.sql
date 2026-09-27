-- ============================================================
-- Talisay Beach Resort Smart Tourism System
-- Full MySQL Database Dump (Updated Schema & Seed Data)
-- Ready for import into phpMyAdmin / MySQL
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Create Database
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `talisay_resort`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `talisay_resort`;

-- ============================================================
-- TABLE: migrations
-- ============================================================
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('0001_01_01_000003_create_packages_table', 1),
('0001_01_01_000004_create_bookings_table', 1),
('0001_01_01_000005_create_payments_emergencies_table', 1),
('0001_01_01_000006_create_chatbot_tables', 1),
('0001_01_01_000007_create_reviews_memory_tour_tables', 1),
('0001_01_01_000008_create_notifications_audit_settings_tables', 1);

-- ============================================================
-- TABLE: users
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','tourist') NOT NULL DEFAULT 'tourist',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `fcm_token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password for ALL accounts below is: password
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `role`, `avatar`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@talisayresort.com', '+63-917-000-0001', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'admin', NULL, 1, NOW(), NOW()),
(2, 'Maria Santos', 'staff1@talisayresort.com', '+63-917-000-0002', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'staff', NULL, 1, NOW(), NOW()),
(3, 'Juan Dela Cruz', 'staff2@talisayresort.com', '+63-917-000-0003', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'staff', NULL, 1, NOW(), NOW()),
(4, 'Ana Reyes', 'ana.reyes@email.com', '+63-918-111-0001', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'tourist', NULL, 1, NOW(), NOW()),
(5, 'Carlos Garcia', 'carlos.garcia@email.com', '+63-918-111-0002', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'tourist', NULL, 1, NOW(), NOW()),
(6, 'Sofia Lim', 'sofia.lim@email.com', '+63-918-111-0003', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'tourist', NULL, 1, NOW(), NOW()),
(7, 'Diego Ramos', 'diego.ramos@email.com', '+63-918-111-0004', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'tourist', NULL, 1, NOW(), NOW()),
(8, 'Isabella Torres', 'isabella.torres@email.com', '+63-918-111-0005', NOW(), '$2y$12$T9mE/5oS.6bBHyLXXE0IkuEg3t4asBVjXi3Kze0k4/rU1sAfpuBCm', 'tourist', NULL, 1, NOW(), NOW());

-- ============================================================
-- TABLE: password_reset_tokens
-- ============================================================
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: sessions
-- ============================================================
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: cache & cache_locks
-- ============================================================
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: jobs, job_batches, failed_jobs
-- ============================================================
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: packages
-- ============================================================
DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_capacity` int(11) NOT NULL,
  `images` json DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `seasonal_pricing` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `max_capacity`, `images`, `is_visible`, `seasonal_pricing`, `created_at`, `updated_at`) VALUES
(1, 'Day Tour Package', 'Enjoy a full day of sun, sand, and sea at Talisay Beach. Includes access to all beach facilities, cottage use, and a complimentary welcome drink.', 500.00, 50, '["packages/day-tour-1.jpg", "packages/day-tour-2.jpg"]', 1, '[{"season": "Peak (March-May)", "markup_percent": 20}]', NOW(), NOW()),
(2, 'Overnight Cottage Stay', 'Stay overnight in our cozy beachfront cottages with a stunning view of the Camotes Sea. Includes dinner and breakfast for two.', 1500.00, 20, '["packages/overnight-1.jpg", "packages/overnight-2.jpg"]', 1, '[{"season": "Peak (March-May)", "markup_percent": 25}]', NOW(), NOW()),
(3, 'Beachfront Cabin Suite', 'Indulge in our premium beachfront cabin with air conditioning, private veranda, and direct beach access. Includes full-board meals.', 3500.00, 10, '["packages/cabin-1.jpg"]', 1, '[{"season": "Peak (March-May)", "markup_percent": 30}]', NOW(), NOW()),
(4, 'Group Adventure Package', 'Designed for groups of 5-15 people. Includes beach games, island hopping, snorkeling adventure, and grilled lunch by the shore.', 800.00, 30, '["packages/group-1.jpg", "packages/group-2.jpg"]', 1, '[{"season": "Peak (March-May)", "markup_percent": 15}]', NOW(), NOW()),
(5, 'Snorkeling Adventure Add-on', 'Add this to any package for a guided snorkeling experience at the Talisay coral reef. Includes mask, snorkel, fins, and guide.', 350.00, 15, '["packages/snorkel-1.jpg"]', 1, NULL, NOW(), NOW()),
(6, 'Kayak Rental', 'Explore the calm waters of Talisay Bay on a single or double kayak. Life jackets and brief orientation included.', 250.00, 20, '["packages/kayak-1.jpg"]', 1, NULL, NOW(), NOW());

-- ============================================================
-- TABLE: package_schedules
-- ============================================================
DROP TABLE IF EXISTS `package_schedules`;
CREATE TABLE `package_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL DEFAULT '08:00:00',
  `end_time` time NOT NULL DEFAULT '17:00:00',
  `available_slots` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_schedules_package_id_foreign` (`package_id`),
  CONSTRAINT `package_schedules_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `package_schedules` (`package_id`, `day_of_week`, `start_time`, `end_time`, `available_slots`, `created_at`, `updated_at`) VALUES
(1, 'monday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(1, 'tuesday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(1, 'wednesday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(1, 'thursday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(1, 'friday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(1, 'saturday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(1, 'sunday', '08:00:00', '17:00:00', 50, NOW(), NOW()),
(2, 'thursday', '14:00:00', '11:00:00', 10, NOW(), NOW()),
(2, 'friday', '14:00:00', '11:00:00', 10, NOW(), NOW()),
(2, 'saturday', '14:00:00', '11:00:00', 10, NOW(), NOW()),
(2, 'sunday', '14:00:00', '11:00:00', 10, NOW(), NOW());

-- ============================================================
-- TABLE: capacity_schedules
-- ============================================================
DROP TABLE IF EXISTS `capacity_schedules`;
CREATE TABLE `capacity_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 100,
  `current_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `capacity_schedules_date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `capacity_schedules` (`date`, `max_capacity`, `current_count`, `created_at`, `updated_at`) VALUES
(CURDATE(), 100, 35, NOW(), NOW()),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 100, 42, NOW(), NOW()),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), 100, 18, NOW(), NOW()),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY), 100, 50, NOW(), NOW()),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 100, 60, NOW(), NOW()),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 100, 75, NOW(), NOW());

-- ============================================================
-- TABLE: bookings
-- ============================================================
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(255) DEFAULT NULL,
  `guests_count` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','paid','checked_in','checked_out','cancelled','completed') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_reference_no_unique` (`reference_no`),
  KEY `bookings_booking_date_status_index` (`booking_date`,`status`),
  KEY `bookings_user_id_index` (`user_id`),
  KEY `bookings_package_id_index` (`package_id`),
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bookings` (`id`, `reference_no`, `user_id`, `package_id`, `booking_date`, `time_slot`, `guests_count`, `status`, `special_requests`, `cancelled_at`, `cancellation_reason`, `total_amount`, `created_at`, `updated_at`) VALUES
(1, 'TBR-2026-1001', 4, 1, CURDATE(), '08:00 AM - 05:00 PM', 2, 'checked_in', 'Please assign cottage near water.', NULL, NULL, 1000.00, NOW(), NOW()),
(2, 'TBR-2026-1002', 5, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '02:00 PM - 11:00 AM', 2, 'paid', 'Late check-in requested around 4PM.', NULL, NULL, 3000.00, NOW(), NOW()),
(3, 'TBR-2026-1003', 6, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '02:00 PM - 12:00 PM', 2, 'pending', NULL, NULL, NULL, 7000.00, NOW(), NOW()),
(4, 'TBR-2026-1004', 7, 4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00 AM - 04:00 PM', 8, 'completed', 'Group lunch at 12:30 PM.', NULL, NULL, 6400.00, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'TBR-2026-1005', 8, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00 AM - 05:00 PM', 2, 'cancelled', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Change of travel plans', 1000.00, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ============================================================
-- TABLE: payments
-- ============================================================
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` varchar(255) NOT NULL DEFAULT 'stripe',
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  `proof_path` varchar(255) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_booking_id_index` (`booking_id`),
  KEY `payments_transaction_id_index` (`transaction_id`),
  CONSTRAINT `payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`booking_id`, `amount`, `gateway`, `transaction_id`, `status`, `proof_path`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1000.00, 'stripe', 'TXN-A183940129', 'success', NULL, '{"currency": "PHP"}', NOW(), NOW()),
(2, 3000.00, 'gcash',  'TXN-GC78492019', 'success', 'payments/proof-2.jpg', '{"phone": "+63-918-111-0002"}', NOW(), NOW()),
(3, 7000.00, 'stripe',  NULL,             'pending', NULL, NULL, NOW(), NOW()),
(4, 6400.00, 'stripe', 'TXN-A992019384', 'success', NULL, '{"currency": "PHP"}', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 1000.00, 'stripe', 'TXN-R774839201', 'refunded', NULL, '{"refund_reason": "Cancellation"}', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ============================================================
-- TABLE: emergencies
-- ============================================================
DROP TABLE IF EXISTS `emergencies`;
CREATE TABLE `emergencies` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category` enum('medical','security','lost_item','other') NOT NULL,
  `description` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('pending','acknowledged','responding','resolved') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(255) NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `responder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emergencies_tracking_number_unique` (`tracking_number`),
  KEY `emergencies_status_index` (`status`),
  KEY `emergencies_user_id_index` (`user_id`),
  KEY `emergencies_responder_id_foreign` (`responder_id`),
  CONSTRAINT `emergencies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emergencies_responder_id_foreign` FOREIGN KEY (`responder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `emergencies` (`user_id`, `category`, `description`, `photo_path`, `latitude`, `longitude`, `status`, `tracking_number`, `resolved_at`, `responder_id`, `response_notes`, `created_at`, `updated_at`) VALUES
(4, 'medical', 'Guest experienced heat exhaustion near the beachfront. Feeling dizzy.', NULL, 10.6837000, 124.7970000, 'resolved', 'EMG-2026-88192', NOW(), 2, 'First aid administered. Rested in shade with hydration.', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(5, 'lost_item', 'Lost a black wallet near the cottage area. Contains IDs.', NULL, 10.6835000, 124.7972000, 'responding', 'EMG-2026-77382', NULL, 2, 'Staff actively searching cottage grounds.', NOW(), NOW());

-- ============================================================
-- TABLE: chatbot_intents & chatbot_logs
-- ============================================================
DROP TABLE IF EXISTS `chatbot_intents`;
CREATE TABLE `chatbot_intents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL,
  `response` text NOT NULL,
  `category` enum('rates','hours','policies','directions','facilities','booking_help','general') NOT NULL DEFAULT 'general',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chatbot_intents` (`keyword`, `response`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
('rates', 'Our rates start at PHP 250 for kayak rental up to PHP 3,500 for our Beachfront Cabin Suite. Day Tour is PHP 500 per person. Visit our Packages page for details!', 'rates', 1, NOW(), NOW()),
('hours', 'Talisay Beach Resort is open daily from 8:00 AM to 5:00 PM for day tours. Overnight stays check in at 2:00 PM.', 'hours', 1, NOW(), NOW()),
('cancel', 'Cancellations made at least 48 hours before the booking date receive a full refund. Cancellations within 24-48 hours receive a 50% refund.', 'policies', 1, NOW(), NOW()),
('directions', 'Talisay Beach Resort is located in Barangay Maslug, Baybay City, Leyte. From Tacloban, take the coastal road south.', 'directions', 1, NOW(), NOW());

DROP TABLE IF EXISTS `chatbot_logs`;
CREATE TABLE `chatbot_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `intent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chatbot_logs_session_id_index` (`session_id`),
  KEY `chatbot_logs_user_id_index` (`user_id`),
  CONSTRAINT `chatbot_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: reviews, memory_timeline_items, memory_timelines, tour_assets
-- ============================================================
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_user_id_index` (`user_id`),
  KEY `reviews_booking_id_index` (`booking_id`),
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reviews` (`user_id`, `booking_id`, `rating`, `comment`, `is_approved`, `created_at`, `updated_at`) VALUES
(7, 4, 5, 'Great group experience! Snorkeling and beach volleyball were top notch.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

DROP TABLE IF EXISTS `memory_timeline_items`;
CREATE TABLE `memory_timeline_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('photo','video','note') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `memory_timeline_items_user_id_index` (`user_id`),
  KEY `memory_timeline_items_booking_id_index` (`booking_id`),
  CONSTRAINT `memory_timeline_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `memory_timeline_items_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `memory_timelines`;
CREATE TABLE `memory_timelines` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `generated_pdf_path` varchar(255) DEFAULT NULL,
  `is_generated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `memory_timelines_user_id_index` (`user_id`),
  KEY `memory_timelines_booking_id_index` (`booking_id`),
  CONSTRAINT `memory_timelines_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `memory_timelines_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tour_assets`;
CREATE TABLE `tour_assets` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `panorama_path` varchar(255) NOT NULL,
  `type` enum('image','video') NOT NULL DEFAULT 'image',
  `hotspots` json DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tour_assets` (`id`, `title`, `description`, `panorama_path`, `type`, `hotspots`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Beach Entrance', 'Welcome to Talisay Beach Resort! Panoramic view of our main entrance and shoreline.', 'tour-assets/panorama-1.jpg', 'image', '[]', 1, 1, NOW(), NOW()),
(2, 'Beachfront Cottage Area', 'Our cozy beachfront cottages line the shore with direct beach access.', 'tour-assets/panorama-2.jpg', 'image', '[]', 2, 1, NOW(), NOW());

-- ============================================================
-- TABLE: notifications_table, audit_logs, system_settings
-- ============================================================
DROP TABLE IF EXISTS `notifications_table`;
CREATE TABLE `notifications_table` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_table_user_id_index` (`user_id`),
  KEY `notifications_table_read_at_index` (`read_at`),
  CONSTRAINT `notifications_table_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
('daily_visitor_cap', '100', NOW(), NOW()),
('cancellation_window_hours', '48', NOW(), NOW()),
('resort_contact_email', 'info@talisayresort.com', NOW(), NOW()),
('resort_address', 'Barangay Maslug, Baybay City, Leyte', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE! Complete updated database schema & seed data ready.
-- 
-- Login credentials (all use password: "password"):
--   Admin   : admin@talisayresort.com
--   Staff   : staff1@talisayresort.com
--   Tourist : ana.reyes@email.com
-- ============================================================
