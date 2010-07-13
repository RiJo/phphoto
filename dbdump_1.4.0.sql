-- phpMyAdmin SQL Dump
-- version 3.2.0-beta1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2010 at 12:00 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `phphoto`
--

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE IF NOT EXISTS `cameras` (
  `model` varchar(255) collate latin1_general_ci NOT NULL,
  `crop_factor` float NOT NULL default '1',
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`model`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `galleries`
--

CREATE TABLE IF NOT EXISTS `galleries` (
  `id` int(11) NOT NULL auto_increment,
  `thumbnail` blob,
  `title` varchar(255) collate latin1_general_ci NOT NULL,
  `description` text collate latin1_general_ci,
  `views` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UNIQUE` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ROW_FORMAT=COMPACT AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `id` int(11) NOT NULL auto_increment,
  `data` mediumblob NOT NULL,
  `thumbnail` blob NOT NULL,
  `type` int(2) NOT NULL,
  `width` int(5) NOT NULL,
  `height` int(5) NOT NULL,
  `filesize` bigint(20) NOT NULL,
  `filename` varchar(255) collate latin1_general_ci NOT NULL,
  `exif` text collate latin1_general_ci,
  `title` varchar(255) collate latin1_general_ci default NULL,
  `description` text collate latin1_general_ci,
  `views` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UNIQUE` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ROW_FORMAT=COMPACT AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_to_gallery`
--

CREATE TABLE IF NOT EXISTS `image_to_gallery` (
  `gallery_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`gallery_id`,`image_id`),
  KEY `image_id` (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `image_to_tag`
--

CREATE TABLE IF NOT EXISTS `image_to_tag` (
  `tag_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`tag_id`,`image_id`),
  KEY `image_id` (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

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
-- Table structure for table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate latin1_general_ci NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=4 ;

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
-- Constraints for dumped tables
--

--
-- Constraints for table `image_to_gallery`
--
ALTER TABLE `image_to_gallery`
  ADD CONSTRAINT `image_to_gallery_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `image_to_gallery_ibfk_1` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `image_to_tag`
--
ALTER TABLE `image_to_tag`
  ADD CONSTRAINT `image_to_tag_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `image_to_tag_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `texts`
--
ALTER TABLE `texts`
  ADD CONSTRAINT `texts_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`);

--
-- Dumping data for table `texts`
--

INSERT INTO `texts` (`id`, `language_id`, `category`, `name`, `text`, `parameters`, `changed`, `created`) VALUES
(1, 'en', 'information', 'version', 'Version: %s', 1, '2010-07-13 05:12:37', '2010-07-13 05:12:37'),
(2, 'en', 'information', 'last_updated', 'Updated: %s', 1, '2010-07-13 05:12:37', '2010-07-13 05:12:37'),
(3, 'en', 'information', 'developers', 'Developers: %s', 1, '2010-07-13 05:13:17', '2010-07-13 05:13:17');
