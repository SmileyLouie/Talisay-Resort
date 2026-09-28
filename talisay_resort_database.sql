-- ============================================================
-- Talisay Beach Resort Smart Tourism System
-- Complete MySQL Database Dump (Updated Schema & Seed Data)
-- Exported on: 2026-09-27 17:27:42 (PHT)
-- Ready for import into phpMyAdmin / MySQL 5.7+ / 8.0+
-- ============================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Create Database
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `talisay_resort`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `talisay_resort`;

-- ============================================================
-- TABLE: `migrations`
-- ============================================================
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `migrations` (16 records)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '0001_01_01_000003_create_accommodations_table', 1),
(5, '0001_01_01_000004_create_bookings_table', 1),
(6, '0001_01_01_000005_create_payments_emergencies_table', 1),
(7, '0001_01_01_000006_create_chatbot_tables', 1),
(8, '0001_01_01_000007_create_reviews_memory_tour_tables', 1),
(9, '0001_01_01_000008_create_notifications_audit_settings_tables', 1),
(10, '2026_09_05_000009_drop_emergencies_table', 1),
(11, '2026_09_06_000010_add_booking_source_to_bookings', 1),
(12, '2026_09_09_000011_add_comment_blocking_to_reviews_table', 1),
(13, '2026_09_23_000012_add_staff_fields_to_users_table', 1),
(14, '2026_09_24_000014_create_staff_rbac_and_permissions_tables', 1),
(15, '2026_09_25_000015_create_chatbot_configs_table', 1),
(16, '2026_09_27_000018_create_personal_access_tokens_table', 2);

-- ============================================================
-- TABLE: `users`
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','tourist') NOT NULL DEFAULT 'tourist',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `account_status` enum('active','inactive','on_leave','suspended') NOT NULL DEFAULT 'active',
  `department` varchar(100) DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_staff_id_unique` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users` (8 records)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `role`, `avatar`, `is_active`, `fcm_token`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `position`, `department`, `duty_status`, `duty_notes`, `staff_id`, `account_status`) VALUES
(1, 'Admin User', 'admin@talisayresort.com', '+63-917-000-0001', '2026-09-25 02:20:51', '$2y$12$fb8zmrbMWH72Qm3GffVXjeyTGlCsV7mHiUmKA.UJzSBK/Z3G6DJZS', 'admin', NULL, 1, NULL, NULL, '2026-09-25 02:20:52', '2026-09-25 02:20:52', NULL, NULL, NULL, 'available', NULL, NULL, 'active'),
(2, 'Maria Santos', 'staff1@talisayresort.com', '+63-917-000-0002', '2026-09-25 02:20:52', '$2y$12$hmsaFz6ElN.Ci1xixE.iI.0iznV4KSh664WNGVtwsOpgGXkTI4pbe', 'staff', NULL, 1, NULL, NULL, '2026-09-25 02:20:52', '2026-09-25 02:20:52', NULL, 'Front Desk Supervisor', 'Front Office', 'busy', NULL, 'EMP-001', 'active'),
(3, 'Juan Dela Cruz', 'staff2@talisayresort.com', '+63-917-000-0003', '2026-09-25 02:20:52', '$2y$12$alEwdNG.SsCf5saypl..Jell6Ujbybmmpd8KWpf2Rrnu06lL/UWYS', 'staff', NULL, 1, NULL, NULL, '2026-09-25 02:20:52', '2026-09-25 02:20:52', NULL, 'Operations Lead', 'Resort Operations', 'available', NULL, 'EMP-002', 'active'),
(4, 'Ana Reyes', 'ana.reyes@email.com', '+63-918-111-0001', '2026-09-25 02:20:52', '$2y$12$rbJaRCJ/KrmaQyK3zeoS0OHdaBRH3/eVH0IjvsmRAlRL7C/qUfEBK', 'tourist', NULL, 1, NULL, NULL, '2026-09-25 02:20:52', '2026-09-25 02:20:52', NULL, NULL, NULL, 'available', NULL, NULL, 'active'),
(5, 'Carlos Garcia', 'carlos.garcia@email.com', '+63-918-111-0002', '2026-09-25 02:20:53', '$2y$12$BoHUx8EqOrXM.hfoPKYI9ehl1mekcFf1.WAcPRSKHpY0t7fA9/5gq', 'tourist', NULL, 1, NULL, NULL, '2026-09-25 02:20:53', '2026-09-25 02:20:53', NULL, NULL, NULL, 'available', NULL, NULL, 'active'),
(6, 'Sofia Lim', 'sofia.lim@email.com', '+63-918-111-0003', '2026-09-25 02:20:56', '$2y$12$mNqmFoUrLVC2oJjCNef0Te1s1Vks.sq8FPw/vsru4jytI.Ik9An36', 'tourist', NULL, 1, NULL, NULL, '2026-09-25 02:20:56', '2026-09-25 02:20:56', NULL, NULL, NULL, 'available', NULL, NULL, 'active'),
(7, 'Diego Ramos', 'diego.ramos@email.com', '+63-918-111-0004', '2026-09-25 02:20:59', '$2y$12$PMA3untS3GHfpg8tve8Q7ucZt4Csb3ZttZvV4qixWqU3B6v0/mBlq', 'tourist', NULL, 1, NULL, NULL, '2026-09-25 02:20:59', '2026-09-25 02:20:59', NULL, NULL, NULL, 'available', NULL, NULL, 'active'),
(8, 'Isabella Torres', 'isabella.torres@email.com', '+63-918-111-0005', '2026-09-25 02:21:03', '$2y$12$vN72ouzoCzQ0rpTohcsnEuBwZr8rnO9nnWgxdakerghY2ce7OxHBu', 'tourist', NULL, 1, NULL, NULL, '2026-09-25 02:21:03', '2026-09-25 02:21:03', NULL, NULL, NULL, 'available', NULL, NULL, 'active');

-- ============================================================
-- TABLE: `accommodation_units`
-- ============================================================
DROP TABLE IF EXISTS `accommodation_units`;
CREATE TABLE `accommodation_units` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_number` varchar(255) NOT NULL,
  `unit_type` enum('room','cottage') NOT NULL,
  `variant` enum('normal','premium') NOT NULL,
  `floor_area_sqm` decimal(6,1) DEFAULT NULL,
  `bed_configuration` varchar(255) DEFAULT NULL,
  `max_occupancy` int(11) NOT NULL DEFAULT 2,
  `amenities` json DEFAULT NULL,
  `description` text DEFAULT NULL,
  `images` json DEFAULT NULL,
  `tour_video_path` varchar(255) DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accommodation_units_unit_type_index` (`unit_type`),
  KEY `accommodation_units_variant_index` (`variant`),
  KEY `accommodation_units_is_available_index` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `accommodation_units` (20 records)
INSERT INTO `accommodation_units` (`id`, `unit_number`, `unit_type`, `variant`, `floor_area_sqm`, `bed_configuration`, `max_occupancy`, `amenities`, `description`, `images`, `tour_video_path`, `price_per_night`, `is_available`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Room 01', 'room', 'normal', 22, '1 Queen Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal room (Room 01) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 1500, 1, 1, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(2, 'Room 02', 'room', 'normal', 22, '1 Queen Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal room (Room 02) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 1500, 1, 2, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(3, 'Room 03', 'room', 'normal', 22, '1 Queen Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal room (Room 03) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 1500, 1, 3, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(4, 'Room 04', 'room', 'normal', 22, '1 Queen Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal room (Room 04) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 1500, 1, 4, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(5, 'Room 05', 'room', 'normal', 22, '1 Queen Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal room (Room 05) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 1500, 1, 5, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(6, 'Room 06', 'room', 'premium', 30, '1 King Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium room (Room 06) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 2500, 1, 6, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(7, 'Room 07', 'room', 'premium', 30, '1 King Bed', 4, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium room (Room 07) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 2500, 1, 7, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(8, 'Room 08', 'room', 'premium', 30, '1 King Bed + 1 Sofa Bed', 5, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium room (Room 08) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 2800, 1, 8, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(9, 'Room 09', 'room', 'premium', 30, '1 King Bed + 1 Sofa Bed', 5, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium room (Room 09) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 2800, 1, 9, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(10, 'Room 10', 'room', 'premium', 35, '2 Queen Beds', 6, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium room (Room 10) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 3200, 1, 10, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(11, 'Cottage 01', 'cottage', 'normal', 40, '2 Queen Beds', 8, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal cottage (Cottage 01) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 2000, 1, 11, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(12, 'Cottage 02', 'cottage', 'normal', 40, '2 Queen Beds', 8, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal cottage (Cottage 02) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 2000, 1, 12, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(13, 'Cottage 03', 'cottage', 'normal', 40, '2 Queen Beds', 8, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal cottage (Cottage 03) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 2000, 1, 13, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(14, 'Cottage 04', 'cottage', 'normal', 40, '2 Queen Beds + Bunks', 10, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal cottage (Cottage 04) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 2200, 1, 14, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(15, 'Cottage 05', 'cottage', 'normal', 40, '2 Queen Beds + Bunks', 10, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access"]', 'Comfortable normal cottage (Cottage 05) with all essential amenities and direct beach access. Great value for groups and families.', '[]', NULL, 2200, 1, 15, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(16, 'Cottage 06', 'cottage', 'premium', 55, '1 King + 2 Queens', 12, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium cottage (Cottage 06) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 3500, 1, 16, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(17, 'Cottage 07', 'cottage', 'premium', 55, '1 King + 2 Queens', 12, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium cottage (Cottage 07) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 3500, 1, 17, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(18, 'Cottage 08', 'cottage', 'premium', 60, '2 Kings + Loft', 14, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium cottage (Cottage 08) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 4000, 1, 18, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(19, 'Cottage 09', 'cottage', 'premium', 60, '2 Kings + Loft', 14, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium cottage (Cottage 09) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 4000, 1, 19, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(20, 'Cottage 10', 'cottage', 'premium', 65, '3 Queens + Sala Set', 16, '["Free Wi-Fi","Air Conditioning","Private Bathroom","Hot & Cold Shower","Beach Access","Mini Fridge","Smart TV","Private Veranda","In-room Safe"]', 'Spacious premium cottage (Cottage 10) with premium finishes, private veranda, and stunning Camotes Sea views. Ideal for families and couples seeking a premium beach escape.', '[]', NULL, 4500, 1, 20, '2026-09-25 02:21:04', '2026-09-25 02:21:04');

-- ============================================================
-- TABLE: `capacity_schedules`
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

-- Dumping data for table `capacity_schedules` (14 records)
INSERT INTO `capacity_schedules` (`id`, `date`, `max_capacity`, `current_count`, `created_at`, `updated_at`) VALUES
(1, '2026-09-25', 100, 61, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(2, '2026-09-26', 100, 18, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(3, '2026-09-27', 100, 34, '2026-09-25 02:21:04', '2026-09-25 02:21:04'),
(4, '2026-09-28', 100, 23, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, '2026-09-29', 100, 12, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(6, '2026-09-30', 100, 59, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(7, '2026-10-01', 100, 61, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(8, '2026-10-02', 100, 42, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(9, '2026-10-03', 100, 51, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(10, '2026-10-04', 100, 51, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(11, '2026-10-05', 100, 11, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(12, '2026-10-06', 100, 35, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(13, '2026-10-07', 100, 57, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(14, '2026-10-08', 100, 33, '2026-09-25 02:21:05', '2026-09-25 02:21:05');

-- ============================================================
-- TABLE: `bookings`
-- ============================================================
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(255) NOT NULL,
  `booking_type` enum('regular','special_resort') NOT NULL DEFAULT 'regular',
  `booking_source` varchar(50) NOT NULL DEFAULT 'online',
  `guest_name_manual` varchar(255) DEFAULT NULL,
  `guest_contact_manual` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `accommodation_unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `original_accommodation_unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_date` date NOT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `nights_count` int(11) NOT NULL DEFAULT 1,
  `time_slot` varchar(255) DEFAULT NULL,
  `guests_count` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','paid','checked_in','checked_out','cancelled','completed') NOT NULL DEFAULT 'pending',
  `admin_approval_status` enum('not_required','pending','approved','rejected') NOT NULL DEFAULT 'not_required',
  `admin_approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `modified_at` timestamp NULL DEFAULT NULL,
  `modification_notes` text DEFAULT NULL,
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
  KEY `bookings_accommodation_unit_id_index` (`accommodation_unit_id`),
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_accommodation_unit_id_foreign` FOREIGN KEY (`accommodation_unit_id`) REFERENCES `accommodation_units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_original_accommodation_unit_id_foreign` FOREIGN KEY (`original_accommodation_unit_id`) REFERENCES `accommodation_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_admin_approved_by_foreign` FOREIGN KEY (`admin_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `bookings` (22 records)
INSERT INTO `bookings` (`id`, `reference_no`, `booking_type`, `user_id`, `accommodation_unit_id`, `original_accommodation_unit_id`, `booking_date`, `check_in_date`, `check_out_date`, `nights_count`, `time_slot`, `guests_count`, `status`, `admin_approval_status`, `admin_approved_by`, `special_requests`, `modified_at`, `modification_notes`, `cancelled_at`, `cancellation_reason`, `total_amount`, `created_at`, `updated_at`, `deleted_at`, `booking_source`, `guest_name_manual`, `guest_contact_manual`) VALUES
(1, 'TBR-BAA04AE8', 'regular', 4, 1, NULL, '2026-09-11 00:00:00', '2026-09-11 00:00:00', '2026-09-13 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'completed', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 3000, '2026-09-06 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(2, 'TBR-40743718', 'regular', 5, 11, NULL, '2026-09-13 00:00:00', '2026-09-13 00:00:00', '2026-09-16 00:00:00', 3, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'completed', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 6000, '2026-09-09 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(3, 'TBR-2D2EC8B0', 'regular', 6, 2, NULL, '2026-09-15 00:00:00', '2026-09-15 00:00:00', '2026-09-16 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'completed', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-14 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(4, 'TBR-670F2E9D', 'regular', 7, 6, NULL, '2026-09-17 00:00:00', '2026-09-17 00:00:00', '2026-09-19 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'completed', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 5000, '2026-09-16 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(5, 'TBR-A542030E', 'regular', 8, 12, NULL, '2026-09-18 00:00:00', '2026-09-18 00:00:00', '2026-09-20 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 8, 'completed', 'not_required', NULL, 'Please prepare extra beach chairs and a designated cottage area for our group.', NULL, NULL, NULL, NULL, 4000, '2026-09-14 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(6, 'TBR-6A7CF711', 'regular', 4, 7, NULL, '2026-09-18 00:00:00', '2026-09-18 00:00:00', '2026-09-21 00:00:00', 3, '2:00 PM Check-in · 11:00 AM Check-out', 6, 'completed', 'not_required', NULL, 'Please prepare extra beach chairs and a designated cottage area for our group.', NULL, NULL, NULL, NULL, 7500, '2026-09-15 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(7, 'TBR-26D0A76C', 'regular', 5, 3, NULL, '2026-09-22 00:00:00', '2026-09-22 00:00:00', '2026-09-23 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'completed', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-18 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(8, 'TBR-786A673B', 'regular', 6, 8, NULL, '2026-09-23 00:00:00', '2026-09-23 00:00:00', '2026-09-25 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'completed', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 5600, '2026-09-19 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(9, 'TBR-92D08F43', 'regular', 7, 1, NULL, '2026-09-20 00:00:00', '2026-09-20 00:00:00', '2026-09-21 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'cancelled', 'not_required', NULL, NULL, NULL, NULL, '2026-09-21 02:21:05', 'Change of plans', 1500, '2026-09-15 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(10, 'TBR-A4537036', 'regular', 8, 11, NULL, '2026-09-21 00:00:00', '2026-09-21 00:00:00', '2026-09-23 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'cancelled', 'not_required', NULL, NULL, NULL, NULL, '2026-09-22 02:21:05', 'Change of plans', 4000, '2026-09-18 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(11, 'TBR-859966A8', 'regular', 4, 4, NULL, '2026-09-25 00:00:00', '2026-09-25 00:00:00', '2026-09-26 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 3, 'checked_in', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-21 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(12, 'TBR-2D4E18F2', 'regular', 5, 6, NULL, '2026-09-25 00:00:00', '2026-09-25 00:00:00', '2026-09-27 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'paid', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 5000, '2026-09-21 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(13, 'TBR-9372C5FB', 'regular', 6, 5, NULL, '2026-09-26 00:00:00', '2026-09-26 00:00:00', '2026-09-27 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'paid', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-19 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(14, 'TBR-8305F013', 'regular', 7, 12, NULL, '2026-09-27 00:00:00', '2026-09-27 00:00:00', '2026-09-29 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 6, 'paid', 'not_required', NULL, 'Please prepare extra beach chairs and a designated cottage area for our group.', NULL, NULL, NULL, NULL, 4000, '2026-09-22 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(15, 'TBR-25B0ACB8', 'regular', 8, 1, NULL, '2026-09-28 00:00:00', '2026-09-28 00:00:00', '2026-09-29 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'pending', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-18 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(16, 'TBR-654F285C', 'regular', 4, 9, NULL, '2026-09-30 00:00:00', '2026-09-30 00:00:00', '2026-10-03 00:00:00', 3, '2:00 PM Check-in · 11:00 AM Check-out', 8, 'pending', 'not_required', NULL, 'Please prepare extra beach chairs and a designated cottage area for our group.', NULL, NULL, NULL, NULL, 8400, '2026-09-19 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(17, 'TBR-0CDC4EC0', 'regular', 5, 7, NULL, '2026-09-30 00:00:00', '2026-09-30 00:00:00', '2026-10-02 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'paid', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 5000, '2026-09-16 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(18, 'TBR-35014713', 'regular', 6, 6, NULL, '2026-10-02 00:00:00', '2026-10-02 00:00:00', '2026-10-04 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 4, 'pending', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 5000, '2026-09-15 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(19, 'TBR-79F52FAA', 'regular', 7, 13, NULL, '2026-10-02 00:00:00', '2026-10-02 00:00:00', '2026-10-04 00:00:00', 2, '2:00 PM Check-in · 11:00 AM Check-out', 6, 'pending', 'not_required', NULL, 'Please prepare extra beach chairs and a designated cottage area for our group.', NULL, NULL, NULL, NULL, 4000, '2026-09-13 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(20, 'TBR-509A9BF5', 'regular', 8, 2, NULL, '2026-10-05 00:00:00', '2026-10-05 00:00:00', '2026-10-06 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'pending', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-12 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(21, 'TBR-20913A8F', 'regular', 4, 11, NULL, '2026-10-07 00:00:00', '2026-10-07 00:00:00', '2026-10-10 00:00:00', 3, '2:00 PM Check-in · 11:00 AM Check-out', 8, 'paid', 'not_required', NULL, 'Please prepare extra beach chairs and a designated cottage area for our group.', NULL, NULL, NULL, NULL, 6000, '2026-09-10 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL),
(22, 'TBR-1066B7A4', 'regular', 5, 3, NULL, '2026-10-09 00:00:00', '2026-10-09 00:00:00', '2026-10-10 00:00:00', 1, '2:00 PM Check-in · 11:00 AM Check-out', 2, 'pending', 'not_required', NULL, NULL, NULL, NULL, NULL, NULL, 1500, '2026-09-07 02:21:05', '2026-09-25 02:21:05', NULL, 'online', NULL, NULL);

-- ============================================================
-- TABLE: `payments`
-- ============================================================
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` varchar(255) NOT NULL DEFAULT 'gcash',
  `payment_channel` enum('gcash','paypal','card','cash') NOT NULL DEFAULT 'gcash',
  `transaction_id` varchar(255) DEFAULT NULL,
  `paypal_order_id` varchar(255) DEFAULT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `is_cash_on_arrival` tinyint(1) NOT NULL DEFAULT 0,
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

-- Dumping data for table `payments` (22 records)
INSERT INTO `payments` (`id`, `booking_id`, `amount`, `gateway`, `payment_channel`, `transaction_id`, `paypal_order_id`, `card_last_four`, `is_cash_on_arrival`, `status`, `proof_path`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 3000, 'gcash', 'gcash', 'TXN-3E46583D10', NULL, NULL, 0, 'success', 'payments/sample-receipt.jpg', '{"currency":"PHP","paid_at":"2026-09-06T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 2, 6000, 'paypal', 'paypal', 'TXN-7C83101EA8', NULL, NULL, 0, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-09T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 3, 1500, 'card', 'card', 'TXN-C3F11A0484', NULL, '4242', 0, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-14T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 4, 5000, 'cash', 'cash', NULL, NULL, NULL, 1, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-16T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, 5, 4000, 'gcash', 'gcash', 'TXN-802E467DEB', NULL, NULL, 0, 'success', 'payments/sample-receipt.jpg', '{"currency":"PHP","paid_at":"2026-09-14T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(6, 6, 7500, 'paypal', 'paypal', 'TXN-C208B21EFE', NULL, NULL, 0, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-15T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(7, 7, 1500, 'card', 'card', 'TXN-CFCF72C997', NULL, '4242', 0, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-18T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(8, 8, 5600, 'cash', 'cash', NULL, NULL, NULL, 1, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-19T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(9, 9, 1500, 'gcash', 'gcash', 'TXN-5DAEA6AA7E', NULL, NULL, 0, 'refunded', NULL, '{"refunded_at":"2026-09-25T02:21:05+08:00","reason":"Cancellation within 24h window"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(10, 10, 4000, 'paypal', 'paypal', 'TXN-302686A91C', NULL, NULL, 0, 'refunded', NULL, '{"refunded_at":"2026-09-25T02:21:05+08:00","reason":"Cancellation within 24h window"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(11, 11, 1500, 'card', 'card', 'TXN-DF9338A5A7', NULL, '4242', 0, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-21T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(12, 12, 5000, 'cash', 'cash', NULL, NULL, NULL, 1, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-21T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(13, 13, 1500, 'gcash', 'gcash', 'TXN-9C24454C47', NULL, NULL, 0, 'success', 'payments/sample-receipt.jpg', '{"currency":"PHP","paid_at":"2026-09-19T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(14, 14, 4000, 'paypal', 'paypal', 'TXN-5242E36D42', NULL, NULL, 0, 'success', NULL, '{"currency":"PHP","paid_at":"2026-09-22T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(15, 15, 1500, 'card', 'card', NULL, NULL, NULL, 0, 'pending', NULL, NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(16, 16, 8400, 'cash', 'cash', NULL, NULL, NULL, 1, 'pending', NULL, NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(17, 17, 5000, 'gcash', 'gcash', 'TXN-45C25183F2', NULL, NULL, 0, 'success', 'payments/sample-receipt.jpg', '{"currency":"PHP","paid_at":"2026-09-16T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(18, 18, 5000, 'paypal', 'paypal', NULL, NULL, NULL, 0, 'pending', NULL, NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(19, 19, 4000, 'card', 'card', NULL, NULL, NULL, 0, 'pending', NULL, NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(20, 20, 1500, 'cash', 'cash', NULL, NULL, NULL, 1, 'pending', NULL, NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(21, 21, 6000, 'gcash', 'gcash', 'TXN-E3D2413FB8', NULL, NULL, 0, 'success', 'payments/sample-receipt.jpg', '{"currency":"PHP","paid_at":"2026-09-10T04:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(22, 22, 1500, 'paypal', 'paypal', NULL, NULL, NULL, 0, 'pending', NULL, NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05');

-- ============================================================
-- TABLE: `reviews`
-- ============================================================
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_comment_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `block_reason` varchar(255) DEFAULT NULL,
  `comment_blocked_at` timestamp NULL DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_user_id_index` (`user_id`),
  KEY `reviews_booking_id_index` (`booking_id`),
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `reviews` (7 records)
INSERT INTO `reviews` (`id`, `user_id`, `booking_id`, `rating`, `comment`, `is_approved`, `created_at`, `updated_at`, `is_comment_blocked`, `block_reason`, `comment_blocked_at`) VALUES
(1, 4, 1, 5, 'Amazing beach experience! The sand was pristine and the water was crystal clear. Will definitely come back with family.', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL),
(2, 5, 2, 5, 'The overnight cottage was cozy and the bonfire experience was magical. Staff were very accommodating.', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL),
(3, 6, 3, 5, 'Great value for money. The day tour package had everything we needed. The welcome drink was a nice touch!', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL),
(4, 7, 4, 5, 'The beachfront cabin exceeded our expectations. Waking up to the sound of waves was unforgettable. Highly recommended!', 0, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL),
(5, 8, 5, 5, 'Our group of 8 had an absolute blast! The island hopping and snorkeling were the highlights. The grilled lunch was delicious.', 0, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL),
(6, 7, 3, 3, 'The beach was nice but the cottage was a bit dated. Could use some renovation. Food was average.', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL),
(7, 8, 1, 2, 'Water supply was interrupted during our stay. Staff could have been more responsive. The beach itself is beautiful though.', 0, '2026-09-25 02:21:05', '2026-09-25 02:21:05', 0, NULL, NULL);

-- ============================================================
-- TABLE: `tour_assets`
-- ============================================================
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

-- Dumping data for table `tour_assets` (5 records)
INSERT INTO `tour_assets` (`id`, `title`, `description`, `panorama_path`, `type`, `hotspots`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Beach Entrance', 'Welcome to Talisay Beach Resort! This panoramic view shows our main entrance, the reception area, and the stunning beachfront beyond.', 'tour-assets/panorama-1.jpg', 'image', '[{"pitch":10,"yaw":-30,"text":"Reception Hall","type":"info"},{"pitch":-5,"yaw":45,"text":"Beachfront Cottages","type":"scene","sceneId":"cottage-area"}]', 1, 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 'Beachfront Cottage Area', 'Our cozy beachfront cottages line the shore, offering direct access to the sand and sea. Each cottage has a private veranda with sea views.', 'tour-assets/panorama-2.jpg', 'image', '[{"pitch":5,"yaw":60,"text":"Cottage A1","type":"info"},{"pitch":-10,"yaw":-45,"text":"Restaurant & Bar","type":"scene","sceneId":"restaurant"}]', 2, 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 'Restaurant and Bar', 'Our beachfront restaurant serves fresh seafood and local Filipino dishes. Enjoy your meal with a panoramic view of the Camotes Sea.', 'tour-assets/panorama-3.jpg', 'image', '[{"pitch":0,"yaw":0,"text":"Outdoor Dining","type":"info"},{"pitch":-5,"yaw":90,"text":"Beachfront View","type":"scene","sceneId":"main-entrance"}]', 3, 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 'Premium Cabin Suite', 'Step inside our premium Beachfront Cabin Suite. Fully air-conditioned with a private veranda, king-size bed, and direct beach access.', 'tour-assets/panorama-4.jpg', 'image', '[{"pitch":10,"yaw":-20,"text":"Private Veranda","type":"info"},{"pitch":0,"yaw":60,"text":"Sea View Balcony","type":"info"}]', 4, 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, 'Snorkeling and Water Sports Area', 'The coral reef is just 50 meters from shore! Our water sports area offers snorkeling gear, kayaks, and guided reef tours.', 'tour-assets/panorama-5.jpg', 'image', '[{"pitch":-15,"yaw":0,"text":"Coral Reef Zone","type":"info"},{"pitch":5,"yaw":-90,"text":"Kayak Launch","type":"info"},{"pitch":10,"yaw":120,"text":"Back to Beach","type":"scene","sceneId":"main-entrance"}]', 5, 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05');

-- ============================================================
-- TABLE: `chatbot_intents`
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

-- Dumping data for table `chatbot_intents` (33 records)
INSERT INTO `chatbot_intents` (`id`, `keyword`, `response`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'rates', 'Our rates start at PHP 250 for kayak rental up to PHP 3,500 for our Beachfront Cabin Suite. Day Tour is PHP 500 per person. Visit our Packages page for full pricing details!', 'rates', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 'price', 'Here are our current rates: Day Tour - PHP 500/pax, Overnight Cottage - PHP 1,500/couple, Beachfront Cabin - PHP 3,500/couple, Group Package - PHP 800/pax. Add-ons like Snorkeling (PHP 350) and Kayak (PHP 250) are also available.', 'rates', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 'cost', 'Our packages range from PHP 250 (Kayak Rental) to PHP 3,500 (Beachfront Cabin Suite). Day Tour Package is our most popular at PHP 500 per person. Peak season surcharges may apply from March to May.', 'rates', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 'hours', 'Talisay Beach Resort is open daily from 8:00 AM to 5:00 PM for day tours. Overnight stays check in at 2:00 PM and check out at 11:00 AM (cottage) or 12:00 PM (cabin).', 'hours', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, 'open', 'We are open daily! Day tours run from 8:00 AM to 5:00 PM. Overnight cottages are available Thursday through Sunday with check-in at 2:00 PM.', 'hours', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(6, 'time', 'Day Tour hours: 8:00 AM - 5:00 PM daily. Overnight Cottage: Check-in 2:00 PM, Check-out 11:00 AM (Thu-Sun). Beachfront Cabin: Check-in 2:00 PM, Check-out 12:00 PM daily.', 'hours', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(7, 'cancel', 'Cancellations made at least 48 hours before the booking date receive a full refund. Cancellations within 24-48 hours receive a 50% refund. Same-day cancellations are non-refundable. Please contact our staff for assistance.', 'policies', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(8, 'refund', 'Refunds are processed within 5-7 business days. Full refund for cancellations 48+ hours before booking. 50% refund for 24-48 hour cancellations. No refunds for same-day cancellations.', 'policies', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(9, 'policy', 'Our policies: Cancellations 48+ hours ahead get full refund. Children under 5 stay free. Pets are not allowed. Outside food is permitted in designated picnic areas only. Smoking is restricted to outdoor areas.', 'policies', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(10, 'directions', 'Talisay Beach Resort is located in Barangay Maslug, Baybay City, Leyte. From Tacloban, take the coastal road south (approx. 2.5 hours). From Baybay City proper, head northwest for about 20 minutes. Look for our signage along the coastal road.', 'directions', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(11, 'location', 'We are in Barangay Maslug, Baybay City, Leyte, Philippines. The resort is along the coastal road with clear signage. From Baybay City center, it is a 20-minute drive northwest.', 'directions', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(12, 'how to get there', 'To reach Talisay Beach Resort: From Tacloban City, take the south-bound coastal highway (2.5 hrs). From Ormoc, take the south road via Baybay (2 hrs). From Baybay City proper, drive 20 mins northwest along the coast. Public transport (jeepney) is available from Baybay terminal.', 'directions', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(13, 'facilities', 'Our facilities include: beachfront cottages, premium cabins with AC, shower and changing rooms, restaurant and bar, bonfire area, snorkeling and kayak rental, beach volleyball court, children play area, and free parking.', 'facilities', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(14, 'amenities', 'Talisay Beach Resort amenities: Beachfront cottages, Air-conditioned cabins, Restaurant & bar, Shower/changing rooms, Bonfire area, Water sports equipment rental, Beach volleyball, Children\'s play area, Free parking, WiFi at the pavilion.', 'facilities', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(15, 'wifi', 'Free WiFi is available at the main pavilion and restaurant area. Connection speed is suitable for browsing and social media. For video streaming, we recommend enjoying the beach instead!', 'facilities', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(16, 'food', 'Our beachfront restaurant serves fresh seafood, local Filipino dishes, and refreshing beverages. Operating hours: 7:00 AM - 9:00 PM. Guests can also arrange for grilled beachside meals for group packages.', 'facilities', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(17, 'restaurant', 'The Talisay Beach Restaurant is open daily from 7:00 AM to 9:00 PM. We serve fresh catch seafood, Filipino cuisine, and tropical drinks. Cottage and cabin packages include selected meals.', 'facilities', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(18, 'booking', 'You can book through our mobile app or website. Select your preferred package, choose a date, specify the number of guests, and proceed to payment. You\'ll receive a confirmation with your booking reference number.', 'booking_help', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(19, 'reserve', 'To make a reservation: 1) Choose a package, 2) Select your preferred date, 3) Enter the number of guests, 4) Complete payment. You\'ll receive a booking reference number via email and app notification.', 'booking_help', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(20, 'availability', 'You can check real-time availability on our booking page. Select your desired date and package to see available slots. During peak season (March-May), we recommend booking at least 2 weeks in advance.', 'booking_help', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(21, 'payment', 'We accept payments via credit/debit card (Visa, Mastercard) through our secure online payment system, or via bank transfer/manual payment at the resort. A payment link will be provided after booking confirmation.', 'booking_help', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(22, 'snorkeling', 'Our Snorkeling Adventure Add-on is PHP 350 per person, available daily from 9:00 AM to 4:00 PM. Includes mask, snorkel, fins, and a certified guide. The Talisay coral reef is just 50 meters from shore!', 'rates', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(23, 'kayak', 'Kayak Rental is PHP 250 per session. Single and double kayaks are available. Life jackets and a brief orientation are included. Available daily from 9:00 AM to 4:00 PM.', 'rates', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(24, 'hello', 'Welcome to Talisay Beach Resort! How can I help you today? You can ask me about our rates, facilities, booking process, directions, or resort policies.', 'general', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(25, 'hi', 'Hello! Welcome to Talisay Beach Resort! I can help you with information about rates, hours, booking, directions, and more. What would you like to know?', 'general', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(26, 'help', 'I can help you with: Rates & Pricing, Operating Hours, Booking & Reservations, Directions & Location, Facilities & Amenities, 360° Virtual Tour, and Resort Policies. Just ask away!', 'general', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(27, 'emergency', 'For assistance or medical needs on-site, please alert any resort staff member or visit the Front Desk immediately. Our team is available 24/7. For national emergencies, dial 911 or Baybay PNP at (053) 563-0166.', 'general', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(28, 'contact', 'You can reach us at: Email: info@talisayresort.com, Phone: +63-53-XXX-XXXX, or visit us at Barangay Maslug, Baybay City, Leyte. Our staff is also available on-site during operating hours.', 'general', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(29, 'parking', 'Free parking is available for all guests. Our parking area can accommodate up to 50 vehicles. For oversized vehicles, please inform us in advance so we can reserve an appropriate spot.', 'facilities', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(30, 'children', 'Children are welcome at Talisay Beach Resort! Kids under 5 stay free with a paying adult. We have a children\'s play area and shallow swimming zones. Parental supervision is required at all times near the water.', 'policies', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(31, 'pet', 'Unfortunately, pets are not allowed within the resort premises to ensure the comfort and safety of all guests. Service animals are permitted with proper documentation.', 'policies', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(32, 'weather', 'Baybay City enjoys a tropical climate. Dry season is typically March to May (best beach weather). Rainy season is June to October. Always check the forecast before your visit. We post weather updates on our social media.', 'general', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(33, 'group', 'Our Group Adventure Package is perfect for groups of 5-15 people at PHP 800 per person. It includes beach games, island hopping, snorkeling, grilled lunch, and a group photo session. Book at least 3 days in advance!', 'rates', 1, '2026-09-25 02:21:05', '2026-09-25 02:21:05');

-- ============================================================
-- TABLE: `chatbot_logs`
-- ============================================================
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

-- Dumping data for table `chatbot_logs` (8 records)
INSERT INTO `chatbot_logs` (`id`, `user_id`, `session_id`, `message`, `response`, `intent`, `created_at`, `updated_at`) VALUES
(1, 4, 'session-4-20260920', 'What are your rates?', 'Our rates start at PHP 250 for kayak rental up to PHP 3,500 for our Beachfront Cabin Suite. Day Tour is PHP 500 per person.', 'rates', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 4, 'session-4-20260922', 'What time do you open?', 'Talisay Beach Resort is open daily from 8:00 AM to 5:00 PM for day tours.', 'hours', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 5, 'session-5-20260921', 'How do I cancel my booking?', 'Cancellations made at least 48 hours before the booking date receive a full refund.', 'cancel', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 6, 'session-6-20260920', 'Is there WiFi?', 'Free WiFi is available at the main pavilion and restaurant area.', 'wifi', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, 7, 'session-7-20260921', 'How to get there?', 'Talisay Beach Resort is located in Barangay Maslug, Baybay City, Leyte. From Tacloban, take the coastal road south.', 'directions', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(6, 8, 'session-8-20260924', 'Can I bring my dog?', 'Unfortunately, pets are not allowed within the resort premises to ensure the comfort and safety of all guests.', 'pet', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(7, 1, 'web-1790274715685', 'what is love', 'At **Talisay Beach Resort**, love is warm ocean breezes, stunning Baybay City sunsets, and creating unforgettable memories with family and friends by the sea! 🌅🌴 *(Or, as the classic song goes: "Baby don\'t hurt me, no more!" 😉)*\n\nOn the operations side, love is also ensuring top-tier Filipino hospitality, clean facilities, and seamless guest experiences for all our visitors.\n\nHow can I assist you with our resort data, bookings, or guest management today, Admin?', 'ai_response', '2026-09-25 02:32:15', '2026-09-25 02:32:15'),
(8, NULL, 'landing_tizhrqhm_1790274850112', 'what is love', 'Warm greetings! While love can mean many things, here at Talisay Beach Resort, we believe love is spending quality time creating unforgettable oceanfront memories with your family, friends, and special someone. \n\nWhether you\'re planning a romantic getaway, a wedding, or a relaxing weekend by the sea, we would love to welcome you to Baybay City, Leyte! How may I assist you with our room rates, cottages, or resort facilities today?', 'ai_response', '2026-09-25 02:34:28', '2026-09-25 02:34:28');

-- ============================================================
-- TABLE: `chatbot_configs`
-- ============================================================
DROP TABLE IF EXISTS `chatbot_configs`;
CREATE TABLE `chatbot_configs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL DEFAULT 'gemini',
  `model` varchar(255) NOT NULL DEFAULT 'gemini-1.5-flash',
  `api_key` text DEFAULT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `system_prompt` text DEFAULT NULL,
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.70,
  `max_tokens` int(11) NOT NULL DEFAULT 600,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `response_language` varchar(255) NOT NULL DEFAULT 'en',
  `personality` varchar(255) NOT NULL DEFAULT 'friendly',
  `welcome_message` text DEFAULT NULL,
  `fallback_message` text DEFAULT NULL,
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `last_test_status` varchar(255) DEFAULT NULL,
  `last_test_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `chatbot_configs` (1 records)
INSERT INTO `chatbot_configs` (`id`, `provider`, `model`, `api_key`, `api_endpoint`, `system_prompt`, `temperature`, `max_tokens`, `is_enabled`, `response_language`, `personality`, `welcome_message`, `fallback_message`, `last_tested_at`, `last_test_status`, `last_test_message`, `created_at`, `updated_at`) VALUES
(1, 'gemini', 'gemini-3.6-flash', 'eyJpdiI6ImZhajE4bEhiOG0yWFJua25QU2w0OXc9PSIsInZhbHVlIjoiZ00wV2E5a29MbFVDdkh3NXhia3Zwaksxck5NM1VSNGR5ckFhSDJwTUhncVNXMEF2Vi80ZXZzcXpWTmFIVW9QZGFPT0UzMU9XdWxHUDNlenhkNzNramc9PSIsIm1hYyI6Ijg1NTY3ZDY3NzQ4N2U5NjU4ZTYzNGI4MTNiM2M3ZDRhOTBiMmMwN2U1YzI0MTk2MDczZTE2NWExMWU0NWJjMWQiLCJ0YWciOiIifQ==', NULL, 'You are the official Talisay Beach Resort AI tourism assistant in Baybay City, Leyte, Philippines. Help visitors, tourists, and staff with resort information, room rates, cottage availability, facilities, policies, and booking inquiries. Be courteous, concise, and helpful. Never invent information not available in the system.', 0.7, 600, 1, 'en', 'friendly', 'Welcome to Talisay Beach Resort! How can I help you today?', 'I\'m not sure about that. Would you like to speak with our resort front desk staff?', '2026-09-26 01:23:41', 'success', 'Connection successful! Model \'gemini-3.6-flash\' responded in 10429ms.', '2026-09-25 02:20:52', '2026-09-26 01:23:41');

-- ============================================================
-- TABLE: `staff_permissions`
-- ============================================================
DROP TABLE IF EXISTS `staff_permissions`;
CREATE TABLE `staff_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(50) NOT NULL,
  `actions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_permissions_user_id_module_unique` (`user_id`,`module`),
  KEY `staff_permissions_user_id_module_index` (`user_id`,`module`),
  CONSTRAINT `staff_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `staff_permissions` (10 records)
INSERT INTO `staff_permissions` (`id`, `user_id`, `module`, `actions`, `created_at`, `updated_at`) VALUES
(1, 2, 'bookings', '["view","create","edit","confirm","check_in_out"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(2, 2, 'payments', '["view","verify"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(3, 2, 'accommodations', '["view","edit","toggle_availability"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(4, 2, 'reviews', '["view","approve","reject"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(5, 2, 'guests', '["view"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(6, 3, 'bookings', '["view","create","edit","confirm","check_in_out"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(7, 3, 'accommodations', '["view","edit","toggle_availability"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(8, 3, 'housekeeping', '["view","update_status"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(9, 3, 'maintenance', '["view","create_request","update_status"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52'),
(10, 3, 'reviews', '["view","approve","reject"]', '2026-09-25 02:20:52', '2026-09-25 02:20:52');

-- ============================================================
-- TABLE: `notifications_table`
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

-- Dumping data for table `notifications_table` (4 records)
INSERT INTO `notifications_table` (`id`, `user_id`, `type`, `title`, `message`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'booking', 'New Booking Received', 'A new booking (TBR-DEMO001) has been placed by Ana Reyes for the Day Tour Package.', '{"booking_id":1}', '2026-09-24 23:21:05', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 1, 'payment', 'Payment Received', 'Payment of PHP 1,500.00 has been received for booking TBR-DEMO003.', '{"payment_id":1}', NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 2, 'booking', 'New Booking Assigned', 'A new group booking has been placed for this Saturday. 10 guests for the Group Adventure Package.', '{"booking_id":16}', NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 4, 'booking', 'Booking Confirmed', 'Your Day Tour booking for today has been confirmed. We look forward to seeing you!', '{"booking_id":11}', NULL, '2026-09-25 02:21:05', '2026-09-25 02:21:05');

-- ============================================================
-- TABLE: `audit_logs`
-- ============================================================
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

-- Dumping data for table `audit_logs` (29 records)
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `model_type`, `model_id`, `old_values`, `new_values`, `created_at`, `updated_at`) VALUES
(1, 1, 'user_login', 'App\\Models\\User', 1, NULL, '{"ip":"127.0.0.1","login_at":"2026-09-24T21:21:05+08:00"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 1, 'accommodation_unit_created', 'App\\Models\\AccommodationUnit', 1, NULL, '{"unit_number":"Room 01","price_per_night":1500}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 1, 'payment_approved', 'App\\Models\\Payment', 1, '{"status":"pending"}', '{"status":"success"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 1, 'review_approved', 'App\\Models\\Review', 1, '{"is_approved":false}', '{"is_approved":true}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, 1, 'settings_updated', 'App\\Models\\SystemSetting', 1, '{"key":"daily_visitor_cap","value":"50"}', '{"key":"daily_visitor_cap","value":"100"}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(6, 1, 'user_logout', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:33:35', '2026-09-25 02:33:35'),
(7, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:36:07', '2026-09-25 02:36:07'),
(8, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:36:07', '2026-09-25 02:36:07'),
(9, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:36:08', '2026-09-25 02:36:08'),
(10, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:36:08', '2026-09-25 02:36:08'),
(11, 4, 'user_login', 'App\\Models\\User', 4, NULL, NULL, '2026-09-25 02:36:09', '2026-09-25 02:36:09'),
(12, 4, 'user_login', 'App\\Models\\User', 4, NULL, NULL, '2026-09-25 02:36:09', '2026-09-25 02:36:09'),
(13, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:37:30', '2026-09-25 02:37:30'),
(14, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:37:31', '2026-09-25 02:37:31'),
(15, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:37:31', '2026-09-25 02:37:31'),
(16, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:37:31', '2026-09-25 02:37:31'),
(17, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:37:31', '2026-09-25 02:37:31'),
(18, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:37:32', '2026-09-25 02:37:32'),
(19, 4, 'user_login', 'App\\Models\\User', 4, NULL, NULL, '2026-09-25 02:37:32', '2026-09-25 02:37:32'),
(20, 4, 'user_login', 'App\\Models\\User', 4, NULL, NULL, '2026-09-25 02:37:32', '2026-09-25 02:37:32'),
(21, 4, 'user_login', 'App\\Models\\User', 4, NULL, NULL, '2026-09-25 02:37:32', '2026-09-25 02:37:32'),
(22, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:43:57', '2026-09-25 02:43:57'),
(23, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:43:57', '2026-09-25 02:43:57'),
(24, 2, 'user_login', 'App\\Models\\User', 2, NULL, NULL, '2026-09-25 02:43:58', '2026-09-25 02:43:58'),
(25, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-25 02:54:48', '2026-09-25 02:54:48'),
(26, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-26 01:07:57', '2026-09-26 01:07:57'),
(27, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-26 13:02:27', '2026-09-26 13:02:27'),
(28, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-27 16:14:38', '2026-09-27 16:14:38'),
(29, 1, 'user_login', 'App\\Models\\User', 1, NULL, NULL, '2026-09-27 16:15:14', '2026-09-27 16:15:14');

-- ============================================================
-- TABLE: `system_settings`
-- ============================================================
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

-- Dumping data for table `system_settings` (19 records)
INSERT INTO `system_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'daily_visitor_cap', '100', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(2, 'cancellation_window_hours', '48', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(3, 'late_cancellation_refund_percent', '50', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(4, 'same_day_cancellation_refund', '0', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(5, 'check_in_time', '14:00', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(6, 'check_out_time_cottage', '11:00', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(7, 'check_out_time_cabin', '12:00', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(8, 'max_booking_advance_days', '90', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(9, 'min_booking_advance_hours', '6', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(10, 'max_guests_per_booking', '15', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(11, 'resort_contact_email', 'info@talisayresort.com', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(12, 'resort_contact_phone', '+63-53-XXX-XXXX', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(13, 'resort_address', 'Barangay Maslug, Baybay City, Leyte', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(14, 'notification_booking_created', 'New booking created: {reference_no}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(15, 'notification_booking_confirmed', 'Your booking {reference_no} has been confirmed!', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(16, 'notification_booking_cancelled', 'Booking {reference_no} has been cancelled.', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(17, 'notification_payment_received', 'Payment of PHP {amount} received for booking {reference_no}', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(18, 'stripe_mode', 'test', '2026-09-25 02:21:05', '2026-09-25 02:21:05'),
(19, 'timezone', 'Asia/Manila', '2026-09-25 02:21:05', '2026-09-25 02:21:05');

-- ============================================================
-- TABLE: `cache`
-- ============================================================
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `cache_locks`
-- ============================================================
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `jobs`
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

-- ============================================================
-- TABLE: `job_batches`
-- ============================================================
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

-- ============================================================
-- TABLE: `failed_jobs`
-- ============================================================
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `sessions`
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
-- TABLE: `password_reset_tokens`
-- ============================================================
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `personal_access_tokens`
-- Phone sign-in tokens. The guest app sends one of these on every request.
-- ============================================================
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- End of SQL Dump
-- ============================================================