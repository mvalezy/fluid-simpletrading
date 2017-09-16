-- phpMyAdmin SQL Dump
-- version 4.1.14.8
-- http://www.phpmyadmin.net
--
-- Client :  db510.1and1.fr
-- Généré le :  Mar 12 Septembre 2017 à 22:39
-- Version du serveur :  5.5.57-0+deb7u1-log
-- Version de PHP :  5.4.45-0+deb7u11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `db202171217`
--

-- --------------------------------------------------------

--
-- Structure de la table `trade_alert`
--

CREATE TABLE IF NOT EXISTS `trade_alert` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `exchange` varchar(20) NOT NULL,
  `pair` varchar(10) NOT NULL,
  `ledgerid` int(11) unsigned DEFAULT NULL,
  `operator` enum('more','less','even') NOT NULL,
  `price` decimal(10,4) NOT NULL,
  `status` enum('new','sent') NOT NULL DEFAULT 'new',
  `addDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closeDate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `remaining` int(6) DEFAULT NULL,
  `resettimer` int(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ledgerid` (`ledgerid`),
  KEY `addDate` (`addDate`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Structure de la table `trade_history`
--

CREATE TABLE IF NOT EXISTS `trade_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `echange` varchar(20) NOT NULL,
  `pair` varchar(10) NOT NULL,
  `price` decimal(10,4) NOT NULL,
  `addDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `AddDate` (`addDate`),
  KEY `Price` (`price`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3654 ;

-- --------------------------------------------------------

--
-- Structure de la table `trade_ledger`
--

CREATE TABLE IF NOT EXISTS `trade_ledger` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `exchange` varchar(20) NOT NULL,
  `pair` varchar(10) NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `action` enum('buy','sell') NOT NULL DEFAULT 'buy',
  `volume` decimal(10,6) NOT NULL,
  `price` decimal(10,4) NOT NULL,
  `total` decimal(10,4) NOT NULL,
  `takeProfit` decimal(10,4) DEFAULT NULL,
  `stopLoss` decimal(10,4) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `addDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closeDate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
