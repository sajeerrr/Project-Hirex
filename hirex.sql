-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 11:25 PM
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
-- Database: `hirex`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Admin', 'admin@hirex.com', '$2y$10$7vDXKwBfCMezFqU0p8t/OOpfS4oF.H.tZth183a4DlxJfSa.ppM62', 'admin', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `booking_alerts` tinyint(1) DEFAULT 1,
  `worker_approvals` tinyint(1) DEFAULT 1,
  `complaint_alerts` tinyint(1) DEFAULT 1,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `allow_registration` tinyint(1) DEFAULT 1,
  `max_booking_days` int(11) DEFAULT 30,
  `default_currency` varchar(10) DEFAULT 'INR',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `admin_id`, `email_notifications`, `booking_alerts`, `worker_approvals`, `complaint_alerts`, `maintenance_mode`, `allow_registration`, `max_booking_days`, `default_currency`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 0, 1, 30, 'INR', '2026-04-25 19:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `booking_date` datetime DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `booking_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `worker_id`, `booking_date`, `duration_hours`, `address`, `notes`, `total_amount`, `status`, `created_at`, `booking_time`) VALUES
(1, 1, 2, '2026-04-12 14:00:00', 3, 'trivandrum', 'worker needed', 1050, 'pending', '2026-04-12 15:57:33', NULL),
(2, 1, 11, '2026-04-25 03:00:00', 2, 'trivandrum', '', 800, 'completed', '2026-04-25 20:58:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `user_id`, `worker_id`, `subject`, `message`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Late Arrival', 'Worker arrived 2 hours late', 'pending', 'high', '2026-04-25 17:54:24', '2026-04-25 17:54:24'),
(2, 1, NULL, 'App Issue', 'Unable to book worker from dashboard', 'pending', 'medium', '2026-04-25 17:54:24', '2026-04-25 17:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earnings`
--

CREATE TABLE `earnings` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_type` enum('user','worker') DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `receiver_type` enum('user','worker') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`, `message`, `is_read`, `created_at`) VALUES
(2, 1, 'user', 4, 'worker', 'hi', 0, '2026-04-12 17:58:57'),
(3, 1, 'user', 4, 'worker', 'hello', 0, '2026-04-12 17:59:03'),
(4, 1, 'user', 5, 'worker', 'hi', 0, '2026-04-12 18:19:39'),
(5, 1, 'user', 5, 'worker', 'hello', 0, '2026-04-12 18:19:43'),
(6, 1, 'user', 5, 'worker', 'hey', 0, '2026-04-12 18:27:36'),
(7, 1, 'user', 2, 'worker', 'hi', 0, '2026-04-12 18:31:59'),
(8, 1, 'user', 1, 'worker', 'hi', 0, '2026-04-12 18:32:22'),
(9, 1, 'user', 5, 'worker', 'he', 0, '2026-04-13 04:55:48'),
(10, 1, 'user', 11, 'worker', 'hi', 1, '2026-04-25 20:58:01'),
(11, 11, 'worker', 1, 'user', 'hello', 1, '2026-04-25 20:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `worker_id`, `amount`, `status`, `created_at`) VALUES
(1, 1, 1, 800.00, 'completed', '2026-03-15 09:54:53'),
(2, 2, 2, 450.00, 'pending', '2026-03-15 09:54:53'),
(3, 1, 3, 1200.00, 'completed', '2026-03-15 09:54:53'),
(4, 4, 4, 600.00, 'confirmed', '2026-03-15 09:54:53'),
(5, 3, 5, 900.00, 'pending', '2026-03-15 09:54:53'),
(6, 1, 6, 700.00, 'completed', '2026-03-15 09:54:53'),
(7, 2, 7, 500.00, 'pending', '2026-03-15 09:54:53'),
(8, 4, 8, 1000.00, 'completed', '2026-03-15 09:54:53');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `worker_id`, `rating`, `comment`, `created_at`, `reply`, `replied_at`) VALUES
(1, 1, 11, 5, 'Professional painter with clean finishing, timely work, and reasonable pricing', '2026-04-25 21:17:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `saved_workers`
--

CREATE TABLE `saved_workers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_workers`
--

INSERT INTO `saved_workers` (`id`, `user_id`, `worker_id`, `created_at`) VALUES
(1, 1, 11, '2026-04-25 21:13:55'),
(2, 1, 1, '2026-04-25 21:14:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `location`, `created_at`, `role`, `phone`, `bio`, `photo`, `status`) VALUES
(1, 'Alex', 'alex@gmail.com', '$2y$10$Pqk6xiBx7QGfQ.UCJ7/4UeP6.HWc0vt/2HeikVo2PbtGaC4LCkXUK', 'trivandrum', '2026-04-12 10:24:44', 'user', '1234567890', 'Life is easy when we find workers', 'user_1_1775990176.jpg', 'active'),
(2, 'Rahul', 'rahul@gmail.com', '$2y$10$EuqN7ZtKZ0YFUZBMWdDTF.IP5QS0JP//ICkOpxph3s.qxVl3Wtkpm', 'Kochi', '2026-03-15 09:58:44', 'user', NULL, NULL, NULL, 'active'),
(3, 'Anjali', 'anjali@gmail.com', '$2y$10$EuqN7ZtKZ0YFUZBMWdDTF.IP5QS0JP//ICkOpxph3s.qxVl3Wtkpm', 'Trivandrum', '2026-03-15 09:58:44', 'user', NULL, NULL, NULL, 'active'),
(6, 'john', 'john@gmail.com', '$2y$10$uIBrfkNKETa8B4ITnL8bhebeeWJVt4cKj7xQZY8Bgw/PbvAD5T3ZS', 'trivandrum', '2026-04-25 06:57:29', 'user', '1234567890', '', '', 'active'),
(7, 'Test User', 'testuser@example.com', '$2y$10$0gR4n/H58PEVZc/vWELcX.MnmsHkt4yM16ZECwHAMw.KfCUD9l8iy', 'Test City', '2026-04-25 20:23:54', 'user', '1234567890', '', '', 'active'),
(8, 'Test User', 'test@example.com', '$2y$10$x6mFXL8XWneCf6lgNYM5r.U0VdRG/3UhjCcn8BLY5EEiJ/xUYjPfW', 'Test City', '2026-04-25 20:41:42', 'user', '1234567890', '', '', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `booking_alerts` tinyint(1) DEFAULT 1,
  `message_alerts` tinyint(1) DEFAULT 1,
  `dark_mode` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `theme` varchar(20) DEFAULT 'light',
  `default_location` varchar(100) DEFAULT NULL,
  `preferred_category` varchar(100) DEFAULT NULL,
  `show_profile` tinyint(1) DEFAULT 1,
  `hide_contact` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `email_notifications`, `booking_alerts`, `message_alerts`, `dark_mode`, `created_at`, `updated_at`, `theme`, `default_location`, `preferred_category`, `show_profile`, `hide_contact`) VALUES
(1, 1, 1, 1, 1, 0, '2026-04-25 06:45:13', '2026-04-25 06:45:13', 'light', NULL, NULL, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `method` enum('bank','upi','wallet') DEFAULT 'bank',
  `account_details` text DEFAULT NULL,
  `status` enum('requested','processing','completed','rejected') DEFAULT 'requested',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `reviews` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `experience` varchar(20) DEFAULT NULL,
  `jobs` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `city` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `name`, `role`, `rating`, `price`, `reviews`, `available`, `photo`, `experience`, `jobs`, `location`, `email`, `password`, `phone`, `status`, `created_at`, `city`, `bio`) VALUES
(1, 'Anoop Nair', 'Electrician', 4.7, 450, 120, 1, 'https://randomuser.me/api/portraits/men/11.jpg', '8 yrs', 320, 'Kozhikode', 'anoop@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(2, 'Sajith Kumar', 'Plumber', 4.5, 350, 95, 1, 'https://randomuser.me/api/portraits/men/21.jpg', '6 yrs', 240, 'Kochi', 'sajith@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(3, 'Pradeep Menon', 'Carpenter', 4.8, 500, 210, 0, 'https://randomuser.me/api/portraits/men/31.jpg', '12 yrs', 500, 'Thrissur', 'pradeep@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(4, 'Biju Thomas', 'Painter', 4.3, 300, 140, 1, 'https://randomuser.me/api/portraits/men/41.jpg', '10 yrs', 410, 'Kannur', 'biju@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(5, 'Ramesh Varghese', 'AC Technician', 4.9, 550, 260, 1, 'https://randomuser.me/api/portraits/men/51.jpg', '9 yrs', 380, 'Ernakulam', 'ramesh@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(6, 'Faizal Rahman', 'Mechanic', 4.2, 400, 150, 0, 'https://randomuser.me/api/portraits/men/61.jpg', '7 yrs', 290, 'Malappuram', 'faizal@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(7, 'Hari Krishnan', 'Electrician', 4.6, 420, 180, 1, 'https://randomuser.me/api/portraits/men/71.jpg', '11 yrs', 460, 'Palakkad', 'hari@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(8, 'Shibu Joseph', 'Plumber', 4.1, 320, 100, 1, 'https://randomuser.me/api/portraits/men/81.jpg', '5 yrs', 210, 'Alappuzha', 'shibu@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(9, 'Manoj Pillai', 'Carpenter', 4.4, 480, 160, 1, 'https://randomuser.me/api/portraits/men/91.jpg', '8 yrs', 350, 'Kollam', 'manoj@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(10, 'Vinod Raj', 'Painter', 4.7, 330, 190, 1, 'https://randomuser.me/api/portraits/men/14.jpg', '13 yrs', 520, 'Thiruvananthapuram', 'vinod@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', NULL, 'active', '2026-04-25 18:05:44', NULL, NULL),
(11, 'Bob', 'Painter', 5, 400, 1, 1, 'worker_11_1777147822.jpg', '2 yrs', 0, 'panachamoodu', 'bob@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S', '1234567890', 'active', '2026-04-25 18:05:44', 'trivandrum', 'loyal worker'),
(12, 'hello', 'Cleaner', 0, 0, 0, 1, 'default.png', '0 yrs', 0, 'trivandrum', 'hello@gmail.com', '$2y$10$lF.K04yrWwDeRrB3Zh2z.ekM7LacLIecUWmvjxzIxrG8adv4eiXfG', '1234567890', 'rejected', '2026-04-25 18:05:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `worker_availability`
--

CREATE TABLE `worker_availability` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `start_time` time DEFAULT '09:00:00',
  `end_time` time DEFAULT '17:00:00',
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_services`
--

CREATE TABLE `worker_services` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_hours` decimal(5,2) DEFAULT 1.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_complaint_user` (`user_id`),
  ADD KEY `fk_complaint_worker` (`worker_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `earnings`
--
ALTER TABLE `earnings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saved_workers`
--
ALTER TABLE `saved_workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `worker_availability`
--
ALTER TABLE `worker_availability`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `worker_services`
--
ALTER TABLE `worker_services`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `earnings`
--
ALTER TABLE `earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `saved_workers`
--
ALTER TABLE `saved_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `worker_availability`
--
ALTER TABLE `worker_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `worker_services`
--
ALTER TABLE `worker_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaint_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_complaint_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
