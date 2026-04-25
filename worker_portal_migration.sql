-- ============================================
-- HireX Worker Portal Migration
-- Run this ONLY if tables are missing.
-- The updated hirex.sql already includes these.
-- ============================================

-- 1. Worker availability table
CREATE TABLE IF NOT EXISTS `worker_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `start_time` time DEFAULT '09:00:00',
  `end_time` time DEFAULT '17:00:00',
  `is_available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_day` (`worker_id`,`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Worker services table
CREATE TABLE IF NOT EXISTS `worker_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_hours` decimal(5,2) DEFAULT 1.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `worker_id` (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Add reply/replied_at to reviews (if missing)
ALTER TABLE `reviews`
  ADD COLUMN IF NOT EXISTS `reply` text DEFAULT NULL AFTER `comment`,
  ADD COLUMN IF NOT EXISTS `replied_at` timestamp NULL DEFAULT NULL AFTER `reply`;

-- 4. Add booking_time to bookings (if missing)
ALTER TABLE `bookings`
  ADD COLUMN IF NOT EXISTS `booking_time` time DEFAULT NULL AFTER `booking_date`;

-- 5. Add worker columns (if missing)
ALTER TABLE `workers`
  ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `status` varchar(20) DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  ADD COLUMN IF NOT EXISTS `city` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `bio` text DEFAULT NULL;

-- Done! The worker portal is now fully set up.
