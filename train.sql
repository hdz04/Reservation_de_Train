-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 31, 2025 at 04:41 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `train`
--

-- --------------------------------------------------------

--
-- Table structure for table `billets`
--

DROP TABLE IF EXISTS `billets`;
CREATE TABLE IF NOT EXISTS `billets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `trajet_id` int NOT NULL,
  `code_billet` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `classe` enum('economique','premiere') COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_emission` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_billet` (`code_billet`),
  KEY `reservation_id` (`reservation_id`),
  KEY `trajet_id` (`trajet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `billets`
--

INSERT INTO `billets` (`id`, `reservation_id`, `trajet_id`, `code_billet`, `prix`, `classe`, `date_emission`) VALUES
(2, 7, 1, 'TRN20250530131355702', 500.00, 'economique', '2025-05-30 14:13:55'),
(4, 8, 1, 'TRN20250530184942802', 625.00, 'economique', '2025-05-30 19:49:42'),
(7, 10, 1, 'TRN202505301950571001', 625.00, 'economique', '2025-05-30 20:50:57'),
(8, 10, 1, 'TRN202505301950571002', 625.00, 'economique', '2025-05-30 20:50:57'),
(9, 11, 1, 'TRN202505302208561101', 625.00, 'economique', '2025-05-30 23:08:56'),
(10, 11, 1, 'TRN202505302208561102', 625.00, 'economique', '2025-05-30 23:08:56'),
(11, 12, 1, 'TRN202505302304501201', 625.00, 'economique', '2025-05-31 00:04:50'),
(12, 12, 1, 'TRN202505302304501202', 625.00, 'economique', '2025-05-31 00:04:50'),
(13, 13, 1, 'TRN202505310825461301', 625.00, 'economique', '2025-05-31 09:25:46'),
(14, 13, 1, 'TRN202505310825461302', 625.00, 'economique', '2025-05-31 09:25:46'),
(15, 14, 1, 'TRN202505310832401401', 625.00, 'premiere', '2025-05-31 09:32:40'),
(16, 14, 1, 'TRN202505310832401402', 625.00, 'premiere', '2025-05-31 09:32:40'),
(17, 15, 1, 'TRN202505311438061501', 625.00, 'economique', '2025-05-31 15:38:06'),
(18, 15, 1, 'TRN202505311438061502', 625.00, 'economique', '2025-05-31 15:38:06'),
(19, 16, 1, 'TRN202505311447381601', 625.00, 'economique', '2025-05-31 15:47:38'),
(20, 16, 1, 'TRN202505311447381602', 625.00, 'economique', '2025-05-31 15:47:38'),
(21, 17, 1, 'TRN202505311448551701', 625.00, 'premiere', '2025-05-31 15:48:55'),
(22, 17, 1, 'TRN202505311448551702', 625.00, 'premiere', '2025-05-31 15:48:55'),
(23, 18, 1, 'TRN202505311518351801', 625.00, 'economique', '2025-05-31 16:18:35'),
(24, 18, 1, 'TRN202505311518351802', 625.00, 'economique', '2025-05-31 16:18:35'),
(25, 19, 1, 'TRN202505311520451901', 625.00, 'economique', '2025-05-31 16:20:45'),
(26, 19, 1, 'TRN202505311520451902', 625.00, 'economique', '2025-05-31 16:20:45'),
(27, 20, 1, 'TRN202505311525092001', 625.00, 'premiere', '2025-05-31 16:25:09'),
(28, 20, 1, 'TRN202505311525092002', 625.00, 'premiere', '2025-05-31 16:25:09'),
(29, 21, 1, 'TRN202505311615172101', 625.00, 'economique', '2025-05-31 17:15:17'),
(30, 21, 1, 'TRN202505311615172102', 625.00, 'economique', '2025-05-31 17:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `demandes_remboursement`
--

DROP TABLE IF EXISTS `demandes_remboursement`;
CREATE TABLE IF NOT EXISTS `demandes_remboursement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `date_demande` datetime NOT NULL,
  `date_traitement` datetime DEFAULT NULL,
  `statut` enum('pending','approved','rejected') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'pending',
  `montant_rembourse` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `demandes_remboursement`
--

INSERT INTO `demandes_remboursement` (`id`, `reservation_id`, `utilisateur_id`, `date_demande`, `date_traitement`, `statut`, `montant_rembourse`) VALUES
(1, 7, 3, '2025-05-30 14:18:18', '2025-05-30 14:19:11', 'rejected', 0.00),
(2, 20, 3, '2025-05-31 16:39:59', NULL, 'pending', NULL),
(3, 18, 4, '2025-05-31 16:42:37', NULL, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gares`
--

DROP TABLE IF EXISTS `gares`;
CREATE TABLE IF NOT EXISTS `gares` (
  `id_gare` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `ville` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id_gare`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `gares`
--

INSERT INTO `gares` (`id_gare`, `nom`, `ville`) VALUES
(1, 'Gare d’Annaba', 'Annaba'),
(2, 'Gare d’Alger', 'Alger'),
(3, 'Gare de Constantine', 'Constantine'),
(4, 'Gare de Sétif', 'Sétif'),
(5, 'Gare d’Oran', 'Oran'),
(6, 'Gare de Bejaia', 'Bejaia'),
(7, 'Gare de Tlemcen', 'Tlemcen'),
(8, 'Gare de Batna', 'Batna'),
(9, 'Gare de Skikda', 'Skikda'),
(10, 'Gare de Blida', 'Blida'),
(11, 'Gare de Biskra', 'Biskra'),
(12, 'Gare de Tizi Ouzou', 'Tizi Ouzou'),
(13, 'Gare de El Eulma', 'El Eulma');

-- --------------------------------------------------------

--
-- Table structure for table `messages_contact`
--

DROP TABLE IF EXISTS `messages_contact`;
CREATE TABLE IF NOT EXISTS `messages_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `messages_contact`
--

INSERT INTO `messages_contact` (`id`, `nom`, `email`, `message`, `date_envoi`, `utilisateur_id`) VALUES
(1, 'Houda Zouaoui', 'houdazouaoui@gmail.com', 'hii', '2025-05-30 16:34:38', 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int DEFAULT NULL,
  `content` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `content`, `date_creation`, `is_read`) VALUES
(1, 3, 'Votre réservation #7 a été confirmée avec succès. Montant payé: 1 000 DA', '2025-05-30 14:13:55', 1),
(2, 3, 'Votre demande d\'annulation a été refusée.', '2025-05-30 14:19:11', 1),
(3, 3, 'Votre réservation #8 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-30 19:49:42', 1),
(4, 3, 'Votre réservation #9 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-30 20:10:35', 1),
(5, 3, 'Votre réservation #10 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-30 20:50:57', 1),
(6, 3, 'Votre réservation #11 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-30 23:08:56', 1),
(7, 3, 'Votre réservation #12 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 00:04:50', 1),
(8, 3, 'Votre réservation #13 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 09:25:46', 1),
(9, 3, 'Votre réservation #14 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 09:32:40', 1),
(10, 4, 'Votre réservation #15 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 15:38:06', 1),
(11, 4, 'Votre réservation #16 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 15:47:38', 1),
(12, 4, 'Votre réservation #17 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 15:48:55', 1),
(13, 4, 'Votre réservation #18 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 16:18:35', 1),
(14, 3, 'Votre réservation #19 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 16:20:45', 1),
(15, 3, 'Votre réservation #20 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 16:25:09', 1),
(16, 3, 'Votre réservation #21 a été confirmée avec succès. Montant payé: 1 250 DA', '2025-05-31 17:15:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `methode` enum('carte','Edahabia') COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_paiement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `paiements`
--

INSERT INTO `paiements` (`id`, `reservation_id`, `utilisateur_id`, `montant`, `methode`, `date_paiement`) VALUES
(3, 7, 3, 1000.00, 'carte', '2025-05-30 14:13:55'),
(4, 8, 3, 1250.00, 'carte', '2025-05-30 19:49:42'),
(5, 9, 3, 1250.00, 'Edahabia', '2025-05-30 20:10:35'),
(6, 10, 3, 1250.00, 'carte', '2025-05-30 20:50:57'),
(7, 11, 3, 1250.00, 'carte', '2025-05-30 23:08:56'),
(8, 12, 3, 1250.00, 'carte', '2025-05-31 00:04:50'),
(9, 13, 3, 1250.00, 'carte', '2025-05-31 09:25:46'),
(10, 14, 3, 1250.00, 'carte', '2025-05-31 09:32:40'),
(11, 15, 4, 1250.00, 'Edahabia', '2025-05-31 15:38:06'),
(12, 16, 4, 1250.00, 'carte', '2025-05-31 15:47:38'),
(13, 17, 4, 1250.00, 'carte', '2025-05-31 15:48:55'),
(14, 18, 4, 1250.00, 'carte', '2025-05-31 16:18:35'),
(15, 19, 3, 1250.00, 'carte', '2025-05-31 16:20:45'),
(16, 20, 3, 1250.00, 'Edahabia', '2025-05-31 16:25:09'),
(17, 21, 3, 1250.00, 'Edahabia', '2025-05-31 17:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `trajet_id` int NOT NULL,
  `date_reservation` datetime DEFAULT CURRENT_TIMESTAMP,
  `nb_passagers` int NOT NULL DEFAULT '1',
  `classe` enum('economique','premiere') COLLATE utf8mb3_unicode_ci DEFAULT 'economique',
  `prix_total` decimal(10,2) NOT NULL,
  `statut` enum('confirmee','annulee','terminee') COLLATE utf8mb3_unicode_ci DEFAULT 'confirmee',
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `trajet_id` (`trajet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `utilisateur_id`, `trajet_id`, `date_reservation`, `nb_passagers`, `classe`, `prix_total`, `statut`) VALUES
(7, 3, 1, '2025-05-30 14:13:55', 2, '', 1000.00, ''),
(8, 3, 1, '2025-05-30 19:49:42', 2, '', 1250.00, 'confirmee'),
(9, 3, 1, '2025-05-30 20:10:35', 2, '', 1250.00, 'confirmee'),
(10, 3, 1, '2025-05-30 20:50:57', 2, '', 1250.00, 'confirmee'),
(11, 3, 1, '2025-05-30 23:08:56', 2, '', 1250.00, 'confirmee'),
(12, 3, 1, '2025-05-31 00:04:50', 2, '', 1250.00, 'confirmee'),
(13, 3, 1, '2025-05-31 09:25:46', 2, '', 1250.00, 'confirmee'),
(14, 3, 1, '2025-05-31 09:32:40', 2, '', 1250.00, 'confirmee'),
(15, 4, 1, '2025-05-31 15:38:06', 2, '', 1250.00, 'confirmee'),
(16, 4, 1, '2025-05-31 15:47:38', 2, '', 1250.00, 'confirmee'),
(17, 4, 1, '2025-05-31 15:48:55', 2, '', 1250.00, 'confirmee'),
(18, 4, 1, '2025-05-31 16:18:35', 2, '', 1250.00, ''),
(19, 3, 1, '2025-05-31 16:20:45', 2, '', 1250.00, 'confirmee'),
(20, 3, 1, '2025-05-31 16:25:09', 2, '', 1250.00, ''),
(21, 3, 1, '2025-05-31 17:15:17', 2, '', 1250.00, 'confirmee');

-- --------------------------------------------------------

--
-- Table structure for table `reservations_trajets`
--

DROP TABLE IF EXISTS `reservations_trajets`;
CREATE TABLE IF NOT EXISTS `reservations_trajets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `trajet_id` int NOT NULL,
  `type` enum('aller','retour') COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `trajet_id` (`trajet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trains`
--

DROP TABLE IF EXISTS `trains`;
CREATE TABLE IF NOT EXISTS `trains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `capacite_economique` int NOT NULL DEFAULT '0',
  `capacite_premiere` int NOT NULL DEFAULT '0',
  `statut` enum('active','retired') COLLATE utf8mb3_unicode_ci DEFAULT 'active',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `trains`
--

INSERT INTO `trains` (`id`, `numero`, `nom`, `capacite_economique`, `capacite_premiere`, `statut`, `date_creation`) VALUES
(1, '1', 'train de Annaba', 90, 10, 'active', '2025-05-27 15:59:28'),
(2, '2', 'Train de Skikda', 90, 10, 'retired', '2025-05-27 16:11:26');

-- --------------------------------------------------------

--
-- Table structure for table `trajets`
--

DROP TABLE IF EXISTS `trajets`;
CREATE TABLE IF NOT EXISTS `trajets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `train_id` int DEFAULT NULL,
  `id_gare_depart` int DEFAULT NULL,
  `id_gare_arrivee` int DEFAULT NULL,
  `date_heure_depart` datetime NOT NULL,
  `date_heure_arrivee` datetime NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `economique` int NOT NULL DEFAULT '0',
  `premiere_classe` int NOT NULL DEFAULT '0',
  `statut` enum('active','annulee','terminee') COLLATE utf8mb3_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `train_id` (`train_id`),
  KEY `id_gare_depart` (`id_gare_depart`),
  KEY `id_gare_arrivee` (`id_gare_arrivee`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `trajets`
--

INSERT INTO `trajets` (`id`, `train_id`, `id_gare_depart`, `id_gare_arrivee`, `date_heure_depart`, `date_heure_arrivee`, `prix`, `economique`, `premiere_classe`, `statut`) VALUES
(1, 1, 1, 9, '2025-06-05 12:00:00', '2025-06-05 13:30:00', 500.00, 90, 10, 'active'),
(2, 1, 1, 3, '2025-06-10 15:00:00', '2025-06-10 17:30:00', 1000.00, 90, 10, 'active'),
(3, 1, 1, 2, '2025-06-10 08:00:00', '2025-06-10 20:00:00', 2000.00, 90, 10, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `role` enum('admin','client') COLLATE utf8mb3_unicode_ci DEFAULT 'client',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `telephone`, `mot_de_passe`, `role`, `date_inscription`) VALUES
(2, 'Sys', 'Admin', 'admin@gmail.com', '0555000000', '$2y$10$6.ZYdTHxutvmbuEtcEMsJOx3ey2xd6XWZ3WpxFjUWSHGZHV4ljAzS', 'admin', '2025-05-27 14:55:16'),
(3, 'Zouaoui', 'Houda', 'houdazouaoui@gmail.com', '0567327867', '$2y$10$a.4GCamOB3ZC0onKWA.UHeb0dtkEQlGSctQVfZ9cVdvekAIu0rh8K', 'client', '2025-05-27 16:05:39'),
(4, 'Mehdaoui', 'Rahma', 'mehdaouirahma@gmail.com', '0743895678', '$2y$10$J1lfYGqBAMRI2mf7vG8Pf.06IgoA0oj4gllVIRgM92N96c27wTD5u', 'client', '2025-05-31 12:29:50');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billets`
--
ALTER TABLE `billets`
  ADD CONSTRAINT `billets_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billets_ibfk_2` FOREIGN KEY (`trajet_id`) REFERENCES `trajets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `demandes_remboursement`
--
ALTER TABLE `demandes_remboursement`
  ADD CONSTRAINT `demandes_remboursement_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  ADD CONSTRAINT `demandes_remboursement_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Constraints for table `messages_contact`
--
ALTER TABLE `messages_contact`
  ADD CONSTRAINT `messages_contact_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`trajet_id`) REFERENCES `trajets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations_trajets`
--
ALTER TABLE `reservations_trajets`
  ADD CONSTRAINT `reservations_trajets_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_trajets_ibfk_2` FOREIGN KEY (`trajet_id`) REFERENCES `trajets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trajets`
--
ALTER TABLE `trajets`
  ADD CONSTRAINT `trajets_ibfk_1` FOREIGN KEY (`train_id`) REFERENCES `trains` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trajets_ibfk_2` FOREIGN KEY (`id_gare_depart`) REFERENCES `gares` (`id_gare`) ON DELETE SET NULL,
  ADD CONSTRAINT `trajets_ibfk_3` FOREIGN KEY (`id_gare_arrivee`) REFERENCES `gares` (`id_gare`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
