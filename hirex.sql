-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 08:54 AM
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
  `role` varchar(20) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'admin@hirex.com', '$2y$10$7vDXKwBfCMezFqU0p8t/OOpfS4oF.H.tZth183a4DlxJfSa.ppM62', 'admin');

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
(1, 1, 2, '2026-04-12 14:00:00', 3, 'trivandrum', 'worker needed', 1050, 'pending', '2026-04-12 15:57:33', NULL);

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
(9, 1, 'user', 5, 'worker', 'he', 0, '2026-04-13 04:55:48');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `location`, `created_at`, `role`, `phone`, `bio`, `photo`) VALUES
(1, 'Alex', 'alex@gmail.com', '$2y$10$Pqk6xiBx7QGfQ.UCJ7/4UeP6.HWc0vt/2HeikVo2PbtGaC4LCkXUK', 'trivandrum', '2026-04-12 10:24:44', 'user', '1234567890', 'Life is easy when we find workers', 'user_1_1775990176.jpg'),
(2, 'Rahul', 'rahul@gmail.com', '$2y$10$EuqN7ZtKZ0YFUZBMWdDTF.IP5QS0JP//ICkOpxph3s.qxVl3Wtkpm', 'Kochi', '2026-03-15 09:58:44', 'user', NULL, NULL, NULL),
(3, 'Anjali', 'anjali@gmail.com', '$2y$10$EuqN7ZtKZ0YFUZBMWdDTF.IP5QS0JP//ICkOpxph3s.qxVl3Wtkpm', 'Trivandrum', '2026-03-15 09:58:44', 'user', NULL, NULL, NULL);

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
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `name`, `role`, `rating`, `price`, `reviews`, `available`, `photo`, `experience`, `jobs`, `location`, `email`, `password`) VALUES
(1, 'Anoop Nair', 'Electrician', 4.7, 450, 120, 1, 'https://randomuser.me/api/portraits/men/11.jpg', '8 yrs', 320, 'Kozhikode', 'anoop@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(2, 'Sajith Kumar', 'Plumber', 4.5, 350, 95, 1, 'https://randomuser.me/api/portraits/men/21.jpg', '6 yrs', 240, 'Kochi', 'sajith@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(3, 'Pradeep Menon', 'Carpenter', 4.8, 500, 210, 0, 'https://randomuser.me/api/portraits/men/31.jpg', '12 yrs', 500, 'Thrissur', 'pradeep@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(4, 'Biju Thomas', 'Painter', 4.3, 300, 140, 1, 'https://randomuser.me/api/portraits/men/41.jpg', '10 yrs', 410, 'Kannur', 'biju@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(5, 'Ramesh Varghese', 'AC Technician', 4.9, 550, 260, 1, 'https://randomuser.me/api/portraits/men/51.jpg', '9 yrs', 380, 'Ernakulam', 'ramesh@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(6, 'Faizal Rahman', 'Mechanic', 4.2, 400, 150, 0, 'https://randomuser.me/api/portraits/men/61.jpg', '7 yrs', 290, 'Malappuram', 'faizal@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(7, 'Hari Krishnan', 'Electrician', 4.6, 420, 180, 1, 'https://randomuser.me/api/portraits/men/71.jpg', '11 yrs', 460, 'Palakkad', 'hari@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(8, 'Shibu Joseph', 'Plumber', 4.1, 320, 100, 1, 'https://randomuser.me/api/portraits/men/81.jpg', '5 yrs', 210, 'Alappuzha', 'shibu@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(9, 'Manoj Pillai', 'Carpenter', 4.4, 480, 160, 1, 'https://randomuser.me/api/portraits/men/91.jpg', '8 yrs', 350, 'Kollam', 'manoj@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(10, 'Vinod Raj', 'Painter', 4.7, 330, 190, 1, 'https://randomuser.me/api/portraits/men/14.jpg', '13 yrs', 520, 'Thiruvananthapuram', 'vinod@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S'),
(11, 'Bob', 'Painter', 0, 0, 0, 1, 'user-image.jpg', '0 yrs', 0, 'trivandrum', 'bob@gmail.com', '$2y$10$aC6889dRTnEWpk1o1pfHiej7ACcQJF1gLcDFdxSr5P2qiJoG.Z23S');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
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
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_workers`
--
ALTER TABLE `saved_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
