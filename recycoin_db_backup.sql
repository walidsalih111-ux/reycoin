/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: recycoin_db
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `deposits`
--

DROP TABLE IF EXISTS `deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `bottle_size` varchar(100) DEFAULT NULL,
  `points_earned` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `deposits` WRITE;
/*!40000 ALTER TABLE `deposits` DISABLE KEYS */;
INSERT INTO `deposits` VALUES
(1,1,'Bulk Deposit (34 bot',102.00,'2026-05-11 18:08:02'),
(2,2,'Bulk Deposit (34 bot',102.00,'2026-05-11 18:23:48'),
(3,2,'Bulk Deposit (29 bot',87.00,'2026-05-11 18:57:38'),
(4,2,'Bulk Deposit (1 bott',1.00,'2026-05-11 19:10:41'),
(5,1,'Bulk Deposit (35 bot',103.00,'2026-05-12 00:53:21'),
(6,1,'Bulk Deposit (1 bott',1.00,'2026-05-12 01:21:21'),
(7,1,'Bulk Deposit (34 bot',102.00,'2026-05-12 06:50:34'),
(8,7,'Bulk Deposit (2 bottles)',2.00,'2026-05-14 10:40:04'),
(9,7,'Bulk Deposit (3 bottles)',3.00,'2026-05-14 10:40:07'),
(10,7,'Bulk Deposit (3 bottles)',3.00,'2026-05-14 10:56:38'),
(11,7,'Hardware Detect (3)',8.00,'2026-05-14 11:29:41'),
(12,7,'Hardware Detect (6)',8.50,'2026-05-14 11:31:50'),
(13,7,'Hardware Detect (10)',17.00,'2026-05-14 11:33:50'),
(14,10,'Hardware Detect (4)',6.00,'2026-05-14 11:36:05'),
(15,10,'Hardware Detect (9)',10.00,'2026-05-14 11:42:14'),
(16,10,'Hardware Detect (3)',7.00,'2026-05-14 11:49:09'),
(17,10,'Hardware Detect (7)',11.50,'2026-05-14 12:07:33'),
(18,10,'Hardware Detect (16)',25.50,'2026-05-14 12:10:42'),
(19,11,'Hardware Detect (2)',5.00,'2026-05-14 12:44:38'),
(20,11,'Hardware Detect (3)',4.50,'2026-05-14 12:45:30'),
(21,10,'Hardware Detect (5)',7.00,'2026-05-14 15:43:20'),
(22,10,'Hardware Detect (2)',4.00,'2026-05-14 16:46:28'),
(23,10,'Hardware Detect (1)',3.00,'2026-05-14 16:52:06'),
(24,10,'Hardware Detect (1)',2.00,'2026-05-14 16:53:17'),
(25,12,'Hardware Detect (1)',2.00,'2026-05-15 01:59:53'),
(26,12,'Hardware Detect (1)',0.50,'2026-05-15 02:02:59');
/*!40000 ALTER TABLE `deposits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `personnel`
--

DROP TABLE IF EXISTS `personnel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `personnel` WRITE;
/*!40000 ALTER TABLE `personnel` DISABLE KEYS */;
INSERT INTO `personnel` VALUES
(1,'recycoin','recycoin','2026-05-11 17:46:30');
/*!40000 ALTER TABLE `personnel` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `redemptions`
--

DROP TABLE IF EXISTS `redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redemptions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `redemptions` WRITE;
/*!40000 ALTER TABLE `redemptions` DISABLE KEYS */;
INSERT INTO `redemptions` VALUES
(1,2,1,'1kg Rice',50.00,'2026-05-11 18:24:27'),
(2,2,1,'Canned Goods',30.00,'2026-05-11 18:24:32'),
(3,2,1,'100 Points Reward',100.00,'2026-05-11 18:57:56'),
(4,1,1,'100 Points Reward',100.00,'2026-05-12 00:48:45'),
(5,1,1,'100 Points Reward',100.00,'2026-05-12 01:35:21'),
(6,1,1,'100 Points Reward',100.00,'2026-05-12 06:50:46'),
(7,7,1,'100 Points Reward',100.00,'2026-05-14 10:26:27');
/*!40000 ALTER TABLE `redemptions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_lock`
--

DROP TABLE IF EXISTS `system_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_lock` (
  `id` int(11) NOT NULL,
  `user_qr` varchar(50) DEFAULT NULL,
  `expires_at` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_lock`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_lock` WRITE;
/*!40000 ALTER TABLE `system_lock` DISABLE KEYS */;
INSERT INTO `system_lock` VALUES
(1,NULL,0);
/*!40000 ALTER TABLE `system_lock` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `qr_code` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT 'Local Resident',
  `total_points` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  UNIQUE KEY `qr_code` (`qr_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'::1','RC-2026-324992','Al-Raji Theng',8.00,'2026-05-11 17:47:22','2026-05-15 01:57:59'),
(2,'10.167.202.29','RC-2026-0F33A9','Walid B. Salih',10.00,'2026-05-11 17:48:05','2026-05-15 01:57:59'),
(3,'10.167.202.70','RC-2026-F31490','TAE',0.00,'2026-05-12 06:46:34','2026-05-15 01:57:59'),
(4,'10.167.202.251','RC-2026-DCA297','Resident (10.167.202.251)',0.00,'2026-05-12 06:59:55','2026-05-15 01:57:59'),
(5,'192.168.254.107','RC-2026-20AA99','Mohammad Salih M. Musa',3.00,'2026-05-13 07:26:27','2026-05-15 01:57:59'),
(6,'192.168.254.101','RC-2026-D999DF','Al-Saud Theng',99.00,'2026-05-13 10:23:26','2026-05-15 01:57:59'),
(7,'10.235.39.82','RC-2026-2E047F','Al-Raji J I don\'t know what',49.50,'2026-05-14 10:13:29','2026-05-15 01:57:59'),
(8,'10.235.39.161','RC-2026-648ABB','Resident (10.235.39.161)',0.00,'2026-05-14 10:47:37','2026-05-15 01:57:59'),
(9,'127.0.0.1','RC-2026-F2E8B2','Resident (127.0.0.1)',0.00,'2026-05-14 10:49:38','2026-05-15 01:57:59'),
(10,'10.235.39.89','RC-2026-EB9842','Resident (10.235.39.89)',76.00,'2026-05-14 11:35:03','2026-05-15 01:57:59'),
(11,'10.0.0.144','RC-2026-2F2B40','Resident (10.0.0.144)',9.50,'2026-05-14 12:41:56','2026-05-15 01:57:59'),
(12,'10.201.122.89','RC-2026-541D56','Juan Dela Cruz',2.50,'2026-05-15 01:39:12','2026-05-15 02:02:59');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-05-15 10:24:39
