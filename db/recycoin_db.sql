-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 08:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `recycoin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bottle_size` varchar(20) DEFAULT NULL,
  `points_earned` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`id`, `user_id`, `bottle_size`, `points_earned`, `created_at`) VALUES
(1, 1, 'Large', 2.50, '2026-05-10 17:42:03'),
(2, 1, 'Large', 2.50, '2026-05-10 17:42:05'),
(3, 1, 'Large', 2.50, '2026-05-10 17:42:06'),
(4, 1, 'Large', 2.50, '2026-05-10 17:42:07'),
(5, 1, 'Large', 2.50, '2026-05-10 17:42:08'),
(6, 1, 'Large', 2.50, '2026-05-10 17:42:09'),
(7, 1, 'Large', 2.50, '2026-05-10 17:42:52'),
(8, 1, 'Large', 2.50, '2026-05-10 17:42:53'),
(9, 1, 'Large', 2.50, '2026-05-10 17:42:54'),
(10, 1, 'Large', 2.50, '2026-05-10 17:43:51'),
(11, 1, 'Large', 2.50, '2026-05-10 17:43:52'),
(12, 1, 'Large', 2.50, '2026-05-10 17:43:53'),
(13, 1, 'Large', 2.50, '2026-05-10 17:43:53'),
(14, 1, 'Large', 2.50, '2026-05-10 17:43:54'),
(15, 1, 'Large', 2.50, '2026-05-10 17:43:55'),
(16, 1, 'Large', 2.50, '2026-05-10 17:43:55'),
(17, 1, 'Large', 2.50, '2026-05-10 17:43:56'),
(18, 1, 'Large', 2.50, '2026-05-10 17:43:56'),
(19, 1, 'Large', 2.50, '2026-05-10 17:43:57'),
(20, 1, 'Large', 2.50, '2026-05-10 17:43:57'),
(21, 1, 'Large', 2.50, '2026-05-10 17:43:58'),
(22, 1, 'Large', 2.50, '2026-05-10 17:43:59'),
(23, 1, 'Large', 2.50, '2026-05-10 17:43:59'),
(24, 1, 'Large', 2.50, '2026-05-10 17:44:00'),
(25, 1, 'Large', 2.50, '2026-05-10 17:44:00'),
(26, 1, 'Large', 2.50, '2026-05-10 17:44:01'),
(27, 1, 'Large', 2.50, '2026-05-10 17:56:11'),
(28, 1, 'Large', 2.50, '2026-05-10 17:56:12'),
(29, 1, 'Large', 2.50, '2026-05-10 17:56:13'),
(30, 1, 'Large', 2.50, '2026-05-10 17:56:14'),
(31, 1, 'Large', 2.50, '2026-05-10 17:58:41'),
(32, 1, 'Large', 2.50, '2026-05-10 17:58:42');

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `username`, `password`, `full_name`) VALUES
(1, 'admin', 'admin123', 'Al-Raji Theng');

-- --------------------------------------------------------

--
-- Table structure for table `redemptions`
--

CREATE TABLE `redemptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `reward_item` varchar(100) DEFAULT NULL,
  `points_deducted` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `redemptions`
--

INSERT INTO `redemptions` (`id`, `user_id`, `personnel_id`, `reward_item`, `points_deducted`, `created_at`) VALUES
(1, 1, 1, '1kg Rice', 50.00, '2026-05-10 17:42:32'),
(2, 1, 1, 'Canned Goods', 30.00, '2026-05-10 17:44:14'),
(3, 1, 1, '1kg Rice', 50.00, '2026-05-10 17:44:24'),
(4, 1, 1, 'Canned Goods', 30.00, '2026-05-10 17:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `qr_code` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `total_points` decimal(10,2) DEFAULT 0.00,
  `total_bottles` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `qr_code`, `full_name`, `total_points`, `total_bottles`, `created_at`) VALUES
(1, 'RC-2026-00142', 'Juan Dela Cruz', 5.50, 74, '2026-05-10 17:34:13'),
(2, 'RC-2026-00139', 'Maria Clara', 120.00, 60, '2026-05-10 17:34:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `redemptions`
--
ALTER TABLE `redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deposits`
--
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD CONSTRAINT `redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `redemptions_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
