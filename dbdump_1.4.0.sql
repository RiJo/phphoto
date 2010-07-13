-- phpMyAdmin SQL Dump
-- version 3.2.0-beta1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2010 at 11:18 AM
-- Server version: 5.0.27
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `phphoto`
--

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE IF NOT EXISTS `languages` (
  `id` varchar(3) collate latin1_general_ci NOT NULL,
  `name` varchar(255) collate latin1_general_ci NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `changed`, `created`) VALUES
('en', 'English', '2010-07-13 05:04:07', '2010-07-13 05:03:34'),
('se', 'Svenska', '2010-07-13 05:03:34', '2010-07-13 05:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `texts`
--

CREATE TABLE IF NOT EXISTS `texts` (
  `id` int(11) NOT NULL auto_increment,
  `language_id` varchar(3) collate latin1_general_ci NOT NULL,
  `category` varchar(255) collate latin1_general_ci NOT NULL,
  `name` varchar(255) collate latin1_general_ci NOT NULL,
  `text` text collate latin1_general_ci NOT NULL,
  `parameters` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UNIQUE` (`language_id`,`category`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=4 ;

--
-- Dumping data for table `texts`
--

INSERT INTO `texts` (`id`, `language_id`, `category`, `name`, `text`, `parameters`, `changed`, `created`) VALUES
(1, 'en', 'information', 'version', 'Version: %s', 1, '2010-07-13 05:12:37', '2010-07-13 05:12:37'),
(2, 'en', 'information', 'last_updated', 'Updated: %s', 1, '2010-07-13 05:12:37', '2010-07-13 05:12:37'),
(3, 'en', 'information', 'developers', 'Developers: %s', 1, '2010-07-13 05:13:17', '2010-07-13 05:13:17');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `texts`
--
ALTER TABLE `texts`
  ADD CONSTRAINT `texts_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`);
