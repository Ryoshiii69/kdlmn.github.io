-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 09:33 AM
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
-- Database: `kadiliman`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `totp_secret` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `firstname`, `surname`, `password`, `totp_secret`, `created_at`, `last_login`) VALUES
(1, 'adminTan', 'adrianojonathan.official@gmail.com', 'JONATHAN', 'ADRIANO', '$2y$10$dsPccskTvAMQ5HiAxnikTeMxKd/O5nXJQhv/UBY5XRl9MU06Uz8V.', NULL, '2025-05-05 00:23:01', '2025-05-05 09:42:12'),
(3, 'adminMigs', 'ryoshi.codm321@gmail.com', 'migs', 'becha', '$2y$10$NfK6U4X092kfueTz6W4//.YeCypjv1THn2dH/A80a1bRU.cTQfYh2', NULL, '2025-05-05 13:24:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_tokens`
--

CREATE TABLE `admin_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_by` varchar(255) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_tokens`
--

INSERT INTO `admin_tokens` (`id`, `token`, `created_by`, `created_at`, `expiry_date`, `is_used`, `used_by`, `used_at`) VALUES
(1, 'h8qr9kky', 'initial_setup', '2025-05-05 03:25:31', '2025-05-06 05:25:31', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `balance_transactions`
--

CREATE TABLE `balance_transactions` (
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `transaction_type` enum('purchase','conversion','usage','refund') NOT NULL,
  `standard_change` decimal(10,2) DEFAULT 0.00,
  `premium_change` decimal(10,2) DEFAULT 0.00,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `balance_transactions`
--

INSERT INTO `balance_transactions` (`transaction_id`, `user_id`, `username`, `transaction_type`, `standard_change`, `premium_change`, `transaction_date`, `description`) VALUES
(28, 57, 'migss', 'purchase', 0.00, 0.00, '2025-04-24 13:26:32', 'Initial standard time balance'),
(29, 57, 'migss', 'purchase', 0.00, 0.00, '2025-04-24 13:26:32', 'Initial premium time balance'),
(30, 59, 'ryoshi', 'purchase', 0.00, 0.00, '2025-05-04 12:13:46', 'Initial standard time balance'),
(31, 59, 'ryoshi', 'purchase', 0.00, 0.00, '2025-05-04 12:13:46', 'Initial premium time balance'),
(32, 59, '', '', 1.00, 0.00, '2025-05-04 14:24:51', 'Top-up of 1 hours to standard PC'),
(33, 57, '', '', 1.00, 0.00, '2025-05-04 15:47:24', 'Top-up of 1 hours to standard PC');

-- --------------------------------------------------------

--
-- Table structure for table `pc_sessions`
--

CREATE TABLE `pc_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `pc_type` enum('standard','premium') NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `minutes_used` int(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `pc_number` int(10) UNSIGNED NOT NULL,
  `pc_type` enum('S','P') NOT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration_hours` int(2) UNSIGNED NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','canceled') NOT NULL DEFAULT 'pending',
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `refusal_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `username`, `pc_number`, `pc_type`, `reservation_date`, `start_time`, `duration_hours`, `end_time`, `status`, `price`, `created_at`, `refusal_reason`) VALUES
(35, 57, 'migss', 2, 'S', '2025-05-05', '08:00:00', 3, '11:00:00', 'canceled', 54.00, '2025-05-04 15:52:19', 'joke lang');

-- --------------------------------------------------------

--
-- Table structure for table `token_history`
--

CREATE TABLE `token_history` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  `firstname` varchar(30) NOT NULL,
  `surname` varchar(30) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_active` tinyint(1) DEFAULT 1,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `password_changes` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to identify admin users (1=admin, 0=regular user)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `firstname`, `surname`, `branch`, `password`, `account_active`, `reg_date`, `login_alerts`, `password_changes`, `is_admin`) VALUES
(57, 'migss', 'bechaves78@gmail.com', 'Miguel', 'Bechaves', 'Taguig', '$2y$10$3TmHgLp67oPg/9.pWUgSxeg8RCZlOc1NzmhM0TSS5wn9MDa0corTW', 1, '2025-05-04 14:27:06', 0, 0, 0),
(58, 'migul', 'bechavesarnel@gmail.com', 'Miguel', 'Bechaves', 'Taguig', '$2y$10$K4QO54NHBavOGlYpYk1HCOqihsE52BXNvK78/SNEv0Ve/yhHLvDqC', 1, '2025-04-24 16:20:33', 1, 1, 0),
(59, 'ryoshi', 'adrianojonathan.official@gmail.com', 'JONATHAN', 'ADRIANO', 'Makati', '$2y$10$XViuguxjDliCuOAOJ7KBOe.yOr4L2zASLqR0aPECqtnx67fWFP.1m', 1, '2025-05-04 14:23:40', 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_balance`
--

CREATE TABLE `user_balance` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `standard_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `premium_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `conversions_used` int(1) NOT NULL DEFAULT 0,
  `conversion_reset_time` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_balance`
--

INSERT INTO `user_balance` (`id`, `user_id`, `username`, `standard_balance`, `premium_balance`, `conversions_used`, `conversion_reset_time`, `last_updated`) VALUES
(8, 57, 'migss', 1.00, 0.00, 0, '2025-05-05 09:47:12', '2025-05-04 15:47:24'),
(9, 59, 'ryoshi', 6.00, 6.50, 0, '2025-05-05 06:13:46', '2025-05-04 14:26:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admin_totp` (`totp_secret`);

--
-- Indexes for table `admin_tokens`
--
ALTER TABLE `admin_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `balance_transactions`
--
ALTER TABLE `balance_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pc_sessions`
--
ALTER TABLE `pc_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `reservations_user_id_fk` (`user_id`);

--
-- Indexes for table `token_history`
--
ALTER TABLE `token_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_history_admin` (`admin_id`),
  ADD KEY `idx_token_history_token` (`token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_balance`
--
ALTER TABLE `user_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_tokens`
--
ALTER TABLE `admin_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `balance_transactions`
--
ALTER TABLE `balance_transactions`
  MODIFY `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `pc_sessions`
--
ALTER TABLE `pc_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `token_history`
--
ALTER TABLE `token_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `user_balance`
--
ALTER TABLE `user_balance`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `balance_transactions`
--
ALTER TABLE `balance_transactions`
  ADD CONSTRAINT `balance_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pc_sessions`
--
ALTER TABLE `pc_sessions`
  ADD CONSTRAINT `pc_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `token_history`
--
ALTER TABLE `token_history`
  ADD CONSTRAINT `token_history_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `user_balance`
--
ALTER TABLE `user_balance`
  ADD CONSTRAINT `user_balance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
