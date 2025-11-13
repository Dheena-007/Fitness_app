-- Create the main database if it doesn't already exist
CREATE DATABASE IF NOT EXISTS `fitness_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Switch to the newly created database
USE `fitness_db`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- Stores user registration and login information.
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_metrics`
-- Stores a historical log of each user's physical metrics.
--
CREATE TABLE `user_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `height_cm` decimal(5,1) NOT NULL,
  `weight_kg` decimal(5,1) NOT NULL,
  `bmi` decimal(4,2) NOT NULL,
  `activity_level` enum('sedentary','light','moderate','active','very_active') NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_metrics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `daily_food_log`
-- Stores daily food entries for the calorie tracker.
--
CREATE TABLE `daily_food_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `food_name` varchar(100) NOT NULL,
  `calories` int(11) NOT NULL,
  `log_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `daily_food_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `daily_water_log`
-- Stores daily water intake, one record per user per day.
--
CREATE TABLE `daily_water_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `glasses` int(11) NOT NULL DEFAULT 0,
  `log_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_day_unique` (`user_id`,`log_date`),
  CONSTRAINT `daily_water_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
-- Stores recipe information managed by the admin panel.
--
CREATE TABLE `recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_name` varchar(255) NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `calories_per_serving` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;