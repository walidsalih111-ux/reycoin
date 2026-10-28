-- RECYCOIN database schema (structure only, no resident data)
--
-- Fresh install: create an empty database and import this file, for example
--   mysql -u root -e "CREATE DATABASE recycoin_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
--   mysql -u root recycoin_db < db/recycoin_db.sql
-- (or use phpMyAdmin's Import tab). Existing databases don't need this file:
-- db/schema.php upgrades them on the first request.
--
-- When you change the schema, add a migration to db/schema.php and update this file to match.
--
-- Default personnel login: recycoin / recycoin. Change it right after the first login
-- (Personnel page > Edit Credentials).

SET NAMES utf8mb4;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) DEFAULT NULL,
  `qr_code` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT 'Local Resident',
  `total_points` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `device_token` char(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code` (`qr_code`),
  UNIQUE KEY `device_token` (`device_token`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `bottle_size` varchar(100) DEFAULT NULL,
  `points_earned` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_id` char(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `reward_item` varchar(100) DEFAULT NULL,
  `points_deducted` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `personnel_id` (`personnel_id`),
  CONSTRAINT `redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `redemptions_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `system_lock` (
  `id` int(11) NOT NULL,
  `user_qr` varchar(50) DEFAULT NULL,
  `expires_at` int(11) DEFAULT 0,
  `started_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `schema_migrations` (
  `version` int(11) NOT NULL,
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_lock` (`id`, `user_qr`, `expires_at`, `started_at`) VALUES (1, NULL, 0, 0);

-- recycoin / recycoin (bcrypt hash)
INSERT INTO `personnel` (`id`, `username`, `password_hash`) VALUES
(1, 'recycoin', '$2y$10$6S0Yyghb/U.CNH6ykPqcHO99v2opgdUbA7QHc5LNl9nsYIW0OAnu2');

-- This file already includes every migration in db/schema.php up to version 2
INSERT INTO `schema_migrations` (`version`) VALUES (1), (2);
