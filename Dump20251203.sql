-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: affiliates_db
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `affiliates`
--

DROP TABLE IF EXISTS `affiliates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `affiliates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `affiliate_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` enum('GS','TV') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GS',
  `national_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_clearance` tinyint(1) DEFAULT '0',
  `authentication_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_clearance_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('ecocash','bank','none') COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `ecocash_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_link` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('affiliate','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'affiliate',
  `status` enum('active','suspended','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `affiliate_id` (`affiliate_id`),
  UNIQUE KEY `phone_number` (`phone_number`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affiliates`
--

LOCK TABLES `affiliates` WRITE;
/*!40000 ALTER TABLE `affiliates` DISABLE KEYS */;
INSERT INTO `affiliates` VALUES (1,'AFF000','System Admin','0000000000','admin@example.com','$2y$10$tfj8KL9WgSE92Q7I.sQQI.vor35HzNhs0hZ4eanHT.1UjVOCF2jB2',NULL,'GS',NULL,0,NULL,NULL,'none',NULL,NULL,NULL,NULL,NULL,'admin','active','2025-11-27 16:06:31','2025-11-27 16:11:15'),(2,'AFF001','aubrey zhuwao','0774164508','azaways@gmail.com','$2y$10$9.HTrVhe4f7kISuKwrezh.6YSp87i1sbzPl4VqnmNQm6hx575C2fq','harare','TV','632429740e63',0,'',NULL,'ecocash','0774164508','','','','https://wa.me/263771234567?text=I%27ve+been+referred+by+Affiliate+AFF001','affiliate','active','2025-11-27 17:12:01','2025-12-02 21:02:38'),(3,'AFF002','John Doe','263771789456','test@domain.com','$2y$10$2E//FXpLiSIMBa/sUPaJOuXWp5UdK.X/WW.XQZSnhF591zMUOgOzC','harare','TV','632429740e63',0,'',NULL,'ecocash','0774164508','','','',NULL,'affiliate','active','2025-12-03 12:48:01','2025-12-03 12:48:01');
/*!40000 ALTER TABLE `affiliates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commissions`
--

DROP TABLE IF EXISTS `commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quotation_id` int NOT NULL,
  `affiliate_id` int NOT NULL,
  `gross_commission` decimal(12,2) NOT NULL,
  `withholding_tax` decimal(12,2) DEFAULT '0.00',
  `net_commission` decimal(12,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `payment_status` enum('pending','paid') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comm_quotation` (`quotation_id`),
  KEY `fk_comm_affiliate` (`affiliate_id`),
  CONSTRAINT `fk_comm_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comm_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commissions`
--

LOCK TABLES `commissions` WRITE;
/*!40000 ALTER TABLE `commissions` DISABLE KEYS */;
INSERT INTO `commissions` VALUES (1,1,2,97.65,14.65,83.00,7.00,'paid','2025-12-02 20:50:04','Cash','02/12/25','paid on 2 dec 2025 by Tanaka',NULL,'2025-12-02 18:46:44');
/*!40000 ALTER TABLE `commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_settings`
--

DROP TABLE IF EXISTS `program_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program` enum('GS','TV') COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_settings`
--

LOCK TABLES `program_settings` WRITE;
/*!40000 ALTER TABLE `program_settings` DISABLE KEYS */;
INSERT INTO `program_settings` VALUES (1,'GS','263771234567','2025-12-02 19:18:41'),(2,'TV','263784567890','2025-12-02 19:18:41');
/*!40000 ALTER TABLE `program_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `affiliate_id` int NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `estimated_value` decimal(12,2) DEFAULT NULL,
  `quoted_amount` decimal(12,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(12,2) DEFAULT NULL,
  `withholding_tax` decimal(12,2) DEFAULT NULL,
  `net_commission` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','approved','declined','converted') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_quotation_affiliate` (`affiliate_id`),
  CONSTRAINT `fk_quotation_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES (1,2,'jane doe','0770123456','test quote',1450.00,1395.00,7.00,97.65,14.65,83.00,'converted','2025-11-29 14:41:19','2025-12-02 18:46:44');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-03 14:56:25
