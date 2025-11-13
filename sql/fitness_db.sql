-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 07:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fitness_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_food_log`
--

CREATE TABLE `daily_food_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_name` varchar(100) NOT NULL,
  `calories` int(11) NOT NULL,
  `log_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_food_log`
--

INSERT INTO `daily_food_log` (`id`, `user_id`, `food_name`, `calories`, `log_date`) VALUES
(1, 1, 'Apple', 2, '2025-10-14'),
(2, 1, 'Orange ', 5, '2025-10-14'),
(3, 6, 'Apple', 2, '2025-10-14'),
(4, 6, 'Orange ', 5, '2025-10-14'),
(5, 1, 'Orange ', 4, '2025-10-15'),
(6, 1, 'Apple', 123, '2025-10-16'),
(7, 1, 'Apple', 12, '2025-10-16'),
(8, 9, 'apple', 45, '2025-11-12'),
(9, 9, 'apple', 67, '2025-11-12'),
(10, 9, 'Apple', 123, '2025-11-13'),
(11, 9, 'Apple', 12, '2025-11-13'),
(12, 9, 'Apple', 27, '2025-11-13'),
(13, 9, 'apple', 12, '2025-11-13'),
(14, 9, 'Orange ', 12, '2025-11-13'),
(15, 9, 'Apple', 89, '2025-11-13'),
(16, 9, 'appke', 12, '2025-11-13'),
(17, 1, 'apple', 100, '2025-11-13');

-- --------------------------------------------------------

--
-- Table structure for table `daily_water_log`
--

CREATE TABLE `daily_water_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `glasses` int(11) NOT NULL DEFAULT 0,
  `log_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_water_log`
--

INSERT INTO `daily_water_log` (`id`, `user_id`, `glasses`, `log_date`) VALUES
(1, 1, 65, '2025-10-14'),
(64, 6, 6, '2025-10-14'),
(72, 7, 2, '2025-10-14'),
(74, 9, 17, '2025-11-12'),
(91, 9, 34, '2025-11-13'),
(125, 1, 16, '2025-11-13');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `recipe_name` varchar(255) NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `calories_per_serving` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `goal` enum('lose_weight','gain_muscle','maintain') NOT NULL DEFAULT 'maintain'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `age`, `gender`, `created_at`, `goal`) VALUES
(1, 'Boopathi', 'boopathi@gmail.com', '$2y$10$lgXo9TbF0eL2hVJ.zPcUxu/7MsT/ynusvXbqI4uuz/QQh8VR.r78W', NULL, NULL, '2025-10-13 08:37:26', 'maintain'),
(3, 'vijay', 'vijay@gmail.com', '$2y$10$1BiNK8kVhGZYFhiGw2tj6OY.T8t.ZgznE6QJutwidUgdhkH88hUrK', NULL, NULL, '2025-10-13 08:46:24', 'maintain'),
(4, 'paramasivam', 'siva@gmail.com', '$2y$10$H0Rr0HLcBH2sSD4Sfjv8ZOHnxbFcsSr5HM803w0AdVcZggc/613xi', NULL, NULL, '2025-10-13 09:01:26', 'maintain'),
(5, 'Dheena', 'dheena@gmail.com', '$2y$10$D1IOcf6pHwn1TKVGCAM3fO7nxWPqWW8kxXRT9CXZzHWqZJOCi/Xfy', NULL, NULL, '2025-10-14 04:24:49', 'maintain'),
(6, 'Mano', 'mano@gmail.com', '$2y$10$D3Up98685/z8N/VB8seHrOPHRN0PFpjalUP3Lb69dztQeBgVdNmrm', NULL, NULL, '2025-10-14 04:41:30', 'maintain'),
(7, 'appu', 'appu@gmail.com', '$2y$10$chOrnRMw879OVMmyNW015.rKISw6wXd.3iSgvyvjTrTN4FHvhdSoO', NULL, NULL, '2025-10-14 09:58:33', 'maintain'),
(8, 'Aswini ', 'asswini@gmail.com', '$2y$10$MpH7j4h3sYGR8CmQdrZCAO3kuF0ssjIVg9iCsKfqakudtUWNzdzaK', NULL, NULL, '2025-10-14 10:23:05', 'maintain'),
(9, 'Dheena', 'sdheenadayalan896@gmail.com', '$2y$10$JY.93SFewcDM.tYBHkH6hOFcVPBmOXNSb7ESPDhXADlvxuQM8kqHq', 24, 'male', '2025-11-12 06:03:08', 'maintain');

-- --------------------------------------------------------

--
-- Table structure for table `user_metrics`
--

CREATE TABLE `user_metrics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `height_cm` decimal(5,1) NOT NULL,
  `weight_kg` decimal(5,1) NOT NULL,
  `bmi` decimal(4,2) NOT NULL,
  `activity_level` enum('sedentary','light','moderate','active','very_active') NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_metrics`
--

INSERT INTO `user_metrics` (`id`, `user_id`, `height_cm`, `weight_kg`, `bmi`, `activity_level`, `recorded_at`) VALUES
(1, 9, 170.0, 70.0, 24.22, 'sedentary', '2025-11-13 17:23:40'),
(2, 9, 170.0, 80.0, 27.68, 'sedentary', '2025-11-13 17:32:32'),
(3, 9, 170.0, 890.0, 99.99, 'sedentary', '2025-11-13 17:32:52'),
(4, 9, 170.0, 90.0, 31.14, 'sedentary', '2025-11-13 17:32:56'),
(5, 9, 180.0, 60.0, 18.52, 'moderate', '2025-11-13 17:42:03'),
(6, 9, 150.0, 70.0, 31.11, 'sedentary', '2025-11-13 17:47:43'),
(7, 9, 180.0, 79.9, 24.66, 'sedentary', '2025-11-13 18:02:23'),
(8, 9, 180.0, 90.0, 27.78, 'sedentary', '2025-11-13 18:03:40'),
(9, 9, 179.0, 94.8, 29.59, 'sedentary', '2025-11-13 18:13:29'),
(10, 1, 180.0, 80.0, 24.69, 'very_active', '2025-11-13 18:29:43'),
(11, 1, 170.0, 68.0, 23.53, 'sedentary', '2025-11-13 18:29:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_food_log`
--
ALTER TABLE `daily_food_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `daily_water_log`
--
ALTER TABLE `daily_water_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_day_unique` (`user_id`,`log_date`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_metrics`
--
ALTER TABLE `user_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_food_log`
--
ALTER TABLE `daily_food_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `daily_water_log`
--
ALTER TABLE `daily_water_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_metrics`
--
ALTER TABLE `user_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_food_log`
--
ALTER TABLE `daily_food_log`
  ADD CONSTRAINT `daily_food_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_water_log`
--
ALTER TABLE `daily_water_log`
  ADD CONSTRAINT `daily_water_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_metrics`
--
ALTER TABLE `user_metrics`
  ADD CONSTRAINT `user_metrics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
