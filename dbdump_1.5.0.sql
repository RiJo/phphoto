-- phpMyAdmin SQL Dump
-- version 3.2.0-beta1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2010 at 09:34 AM
-- Server version: 5.0.27
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `phphoto`
--

CREATE DATABASE `phphoto` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `phphoto`;

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE IF NOT EXISTS `cameras` (
  `model` varchar(255) collate utf8_general_ci NOT NULL,
  `crop_factor` float NOT NULL default '1',
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `galleries`
--

CREATE TABLE IF NOT EXISTS `galleries` (
  `id` int(11) NOT NULL auto_increment,
  `thumbnail` blob,
  `title` varchar(255) collate utf8_general_ci NOT NULL,
  `description` text collate utf8_general_ci,
  `views` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UNIQUE` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT AUTO_INCREMENT=6 ;

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
  `filename` varchar(255) collate utf8_general_ci NOT NULL,
  `exif` text collate utf8_general_ci,
  `title` varchar(255) collate utf8_general_ci default NULL,
  `description` text collate utf8_general_ci,
  `views` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UNIQUE` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT AUTO_INCREMENT=18 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE IF NOT EXISTS `languages` (
  `id` varchar(3) collate utf8_general_ci NOT NULL,
  `name` varchar(255) collate utf8_general_ci NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `changed`, `created`) VALUES
('en', 'English', NOW(), NOW()),
('se', 'Svenska', NOW(), NOW());

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_general_ci NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `texts`
--

CREATE TABLE IF NOT EXISTS `texts` (
  `language_id` varchar(3) collate utf8_general_ci NOT NULL,
  `category` varchar(255) collate utf8_general_ci NOT NULL,
  `name` varchar(255) collate utf8_general_ci NOT NULL,
  `text` text collate utf8_general_ci NOT NULL,
  `parameters` int(11) NOT NULL,
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` datetime NOT NULL,
  UNIQUE KEY `UNIQUE` (`language_id`,`category`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `texts`
--

INSERT INTO `texts` (`language_id`, `category`, `name`, `text`, `parameters`, `changed`, `created`) VALUES
('en', 'button', 'add', 'Add', 0, NOW(), NOW()),
('en', 'button', 'create', 'Create', 0, NOW(), NOW()),
('en', 'button', 'start', 'Start', 0, NOW(), NOW()),
('en', 'button', 'update', 'update', 0, NOW(), NOW()),
('en', 'button', 'upload', 'Upload', 0, NOW(), NOW()),
('en', 'common', 'page_number', '%d (of %d)', 2, NOW(), NOW()),
('en', 'footer', 'images', '%d images', 1, NOW(), NOW()),
('en', 'footer', 'updated', 'updated %s', 1, NOW(), NOW()),
('en', 'footer', 'views', '%d views', 1, NOW(), NOW()),
('en', 'gallery', 'create', 'Create gallery', 0, NOW(), NOW()),
('en', 'gallery', 'created', 'Gallery ''%s'' has has been created', 1, NOW(), NOW()),
('en', 'gallery', 'deleted', 'Gallery has been deleted', 0, NOW(), NOW()),
('en', 'gallery', 'delete_error', 'Could not delete gallery', 0, NOW(), NOW()),
('en', 'gallery', 'edit', 'Edit gallery', 0, NOW(), NOW()),
('en', 'gallery', 'exists', 'Gallery ''%s'' already exists', 1, NOW(), NOW()),
('en', 'gallery', 'images_in', 'Images in gallery', 0, NOW(), NOW()),
('en', 'gallery', 'images_not_in', 'Images not in gallery', 0, NOW(), NOW()),
('en', 'gallery', 'image_added', 'Image has been added', 0, NOW(), NOW()),
('en', 'gallery', 'image_removed', 'Image has been removed', 0, NOW(), NOW()),
('en', 'gallery', 'note_long_time', 'Note: this may take a while depending on the number of images in the gallery', 0, NOW(), NOW()),
('en', 'gallery', 'regenerate_thumb', 'Regenerate thumbnail', 0, NOW(), NOW()),
('en', 'gallery', 'store_error', 'Could not create gallery ''%s''', 1, NOW(), NOW()),
('en', 'gallery', 'thumb_regenerated', 'Gallery thumbnail has been regenerated', 0, NOW(), NOW()),
('en', 'gallery', 'unknown', 'Could not find the gallery requested', 0, NOW(), NOW()),
('en', 'gallery', 'updated', 'Gallery has been updated', 0, NOW(), NOW()),
('en', 'header', 'camera', 'Camera', 0, NOW(), NOW()),
('en', 'header', 'changed', 'Changed', 0, NOW(), NOW()),
('en', 'header', 'created', 'Created', 0, NOW(), NOW()),
('en', 'header', 'crop_factor', 'Crop factor', 0, NOW(), NOW()),
('en', 'header', 'description', 'Description', 0, NOW(), NOW()),
('en', 'header', 'filename', 'Filename', 0, NOW(), NOW()),
('en', 'header', 'filesize', 'Filesize', 0, NOW(), NOW()),
('en', 'header', 'format', 'Format', 0, NOW(), NOW()),
('en', 'header', 'galleries', 'Galleries', 0, NOW(), NOW()),
('en', 'header', 'images', 'Images', 0, NOW(), NOW()),
('en', 'header', 'model', 'Model', 0, NOW(), NOW()),
('en', 'header', 'name', 'Name', 0, NOW(), NOW()),
('en', 'header', 'resolution', 'Resolution', 0, NOW(), NOW()),
('en', 'header', 'settings', 'Settings', 0, NOW(), NOW()),
('en', 'header', 'tags', 'Tags', 0, NOW(), NOW()),
('en', 'header', 'thumbnail', 'Thumbnail', 0, NOW(), NOW()),
('en', 'header', 'title', 'Title', 0, NOW(), NOW()),
('en', 'header', 'views', 'Views', 0, NOW(), NOW()),
('en', 'image', 'allowed_extensions', 'Allowed extensions: %s', 1, NOW(), NOW()),
('en', 'image', 'deleted', 'Image has been deleted', 0, NOW(), NOW()),
('en', 'image', 'delete_error', 'Could not delete image', 0, NOW(), NOW()),
('en', 'image', 'edit', 'Edit image', 0, NOW(), NOW()),
('en', 'image', 'exists', 'Image ''%s'' already exists', 1, NOW(), NOW()),
('en', 'image', 'invalid_filesize', 'Not a valid filesize: %s', 1, NOW(), NOW()),
('en', 'image', 'invalid_filetype', 'Not a valid filetype: %s', 1, NOW(), NOW()),
('en', 'image', 'invalid_temp_file', 'Could not find uploaded temp file', 0, NOW(), NOW()),
('en', 'image', 'maximum_filesize', 'Maximum filesize: %s', 1, NOW(), NOW()),
('en', 'image', 'note_long_time', 'Note: this may take a while depending on the number of images stored', 0, NOW(), NOW()),
('en', 'image', 'regenerate_thumbs', 'Regenerate thumbnails', 0, NOW(), NOW()),
('en', 'image', 'replace_existing', 'Replace existing', 0, NOW(), NOW()),
('en', 'image', 'store_error', 'The image could not be stored in the database', 0, NOW(), NOW()),
('en', 'image', 'thumbs_regenerated', '%d thumbnails have been regenerated', 1, NOW(), NOW()),
('en', 'image', 'unknown', 'Could not find the image requested', 0, NOW(), NOW()),
('en', 'image', 'updated', 'Image has been updated', 0, NOW(), NOW()),
('en', 'image', 'upload', 'Upload image', 0, NOW(), NOW()),
('en', 'image', 'uploaded_normal', 'Image ''%s'' uploaded successfully', 1, NOW(), NOW()),
('en', 'image', 'uploaded_replace', 'Image ''%s'' uploaded successfully (replace existing)', 1, NOW(), NOW()),
('en', 'info', 'developers', 'Developers: %s', 1, NOW(), NOW()),
('en', 'info', 'last_updated', 'Updated: %s', 1, NOW(), NOW()),
('en', 'info', 'version', 'Version: %s', 1, NOW(), NOW()),
('en', 'section', 'admin', 'Admin', 0, NOW(), NOW()),
('en', 'section', 'cameras', 'Cameras', 0, NOW(), NOW()),
('en', 'section', 'galleries', 'Galleries', 0, NOW(), NOW()),
('en', 'section', 'images', 'Images', 0, NOW(), NOW()),
('en', 'section', 'index', 'First page', 0, NOW(), NOW()),
('en', 'section', 'tags', 'Tags', 0, NOW(), NOW()),
('en', 'tag', 'create', 'Create tag', 0, NOW(), NOW()),
('en', 'tag', 'created', 'Tag ''%s'' has been created', 1, NOW(), NOW()),
('en', 'tag', 'deleted', 'Tag has been deleted', 0, NOW(), NOW()),
('en', 'tag', 'delete_error', 'Could not delete tag', 0, NOW(), NOW()),
('en', 'tag', 'edit', 'Edit tag', 0, NOW(), NOW()),
('en', 'tag', 'exists', 'Tag ''%s'' already exists', 1, NOW(), NOW()),
('en', 'tag', 'image_added', 'Image has been added', 0, NOW(), NOW()),
('en', 'tag', 'image_removed', 'Image has been removed', 0, NOW(), NOW()),
('en', 'tag', 'not_tagged_images', 'Not tagged images', 0, NOW(), NOW()),
('en', 'tag', 'store_error', 'Could not create tag ''%s''', 1, NOW(), NOW()),
('en', 'tag', 'tagged_images', 'Tageed images', 0, NOW(), NOW()),
('en', 'tag', 'unknown', 'Could not find the tag requested', 0, NOW(), NOW()),
('en', 'tag', 'updated', 'Tag has been updated', 0, NOW(), NOW()),

('se', 'button', 'add', 'Lägg till', 0, NOW(), NOW()),
('se', 'button', 'create', 'Skapa', 0, NOW(), NOW()),
('se', 'button', 'start', 'Start', 0, NOW(), NOW()),
('se', 'button', 'update', 'Uppdatera', 0, NOW(), NOW()),
('se', 'button', 'upload', 'Ladda upp', 0, NOW(), NOW()),
('se', 'common', 'page_number', '%d (av %d)', 2, NOW(), NOW()),
('se', 'footer', 'images', '%d bilder', 1, NOW(), NOW()),
('se', 'footer', 'updated', 'uppdaterad %s', 1, NOW(), NOW()),
('se', 'footer', 'views', '%d visningar', 1, NOW(), NOW()),
('se', 'gallery', 'create', 'Skapa galleri', 0, NOW(), NOW()),
('se', 'gallery', 'created', 'Galleriet ''%s'' har skapats', 1, NOW(), NOW()),
('se', 'gallery', 'deleted', 'Galleriet har raderats', 0, NOW(), NOW()),
('se', 'gallery', 'delete_error', 'Kunde inte radera galleri', 0, NOW(), NOW()),
('se', 'gallery', 'edit', 'Ändra galleri', 0, NOW(), NOW()),
('se', 'gallery', 'exists', 'Galleriet ''%s'' finns redan', 1, NOW(), NOW()),
('se', 'gallery', 'images_in', 'Bilder i galleriet', 0, NOW(), NOW()),
('se', 'gallery', 'images_not_in', 'Bilder inte i galleriet', 0, NOW(), NOW()),
('se', 'gallery', 'image_added', 'Bilden har lagts till', 0, NOW(), NOW()),
('se', 'gallery', 'image_removed', 'Bilden har tagits bort', 0, NOW(), NOW()),
('se', 'gallery', 'note_long_time', 'Notera: detta kan ta ett tag beroende på hur många bilder det finns i galleriet', 0, NOW(), NOW()),
('se', 'gallery', 'regenerate_thumb', 'Regenerera miniatyr', 0, NOW(), NOW()),
('se', 'gallery', 'store_error', 'Kunde inte skapa galleriet ''%s''', 1, NOW(), NOW()),
('se', 'gallery', 'thumb_regenerated', 'Galleriets miniatyr har regenererats', 0, NOW(), NOW()),
('se', 'gallery', 'unknown', 'Kunde inte hitta det begärda galleriet', 0, NOW(), NOW()),
('se', 'gallery', 'updated', 'Galleriet har uppdaterats', 0, NOW(), NOW()),
('se', 'header', 'camera', 'Kamera', 0, NOW(), NOW()),
('se', 'header', 'changed', 'Ändrad', 0, NOW(), NOW()),
('se', 'header', 'created', 'Skapad', 0, NOW(), NOW()),
('se', 'header', 'crop_factor', 'Beskärings faktor', 0, NOW(), NOW()),
('se', 'header', 'description', 'Beskrivning', 0, NOW(), NOW()),
('se', 'header', 'filename', 'Filnamn', 0, NOW(), NOW()),
('se', 'header', 'filesize', 'Filstorlek', 0, NOW(), NOW()),
('se', 'header', 'format', 'Format', 0, NOW(), NOW()),
('se', 'header', 'galleries', 'Gallerier', 0, NOW(), NOW()),
('se', 'header', 'images', 'Bilder', 0, NOW(), NOW()),
('se', 'header', 'model', 'Modell', 0, NOW(), NOW()),
('se', 'header', 'name', 'Namn', 0, NOW(), NOW()),
('se', 'header', 'resolution', 'Upplösning', 0, NOW(), NOW()),
('se', 'header', 'settings', 'Inställningar', 0, NOW(), NOW()),
('se', 'header', 'tags', 'Taggar', 0, NOW(), NOW()),
('se', 'header', 'thumbnail', 'Miniatyr', 0, NOW(), NOW()),
('se', 'header', 'title', 'Titel', 0, NOW(), NOW()),
('se', 'header', 'views', 'Visningar', 0, NOW(), NOW()),
('se', 'image', 'allowed_extensions', 'Tillåtna filändelser: %s', 1, NOW(), NOW()),
('se', 'image', 'deleted', 'Bilden har raderats', 0, NOW(), NOW()),
('se', 'image', 'delete_error', 'Kunde inte radera bilden', 0, NOW(), NOW()),
('se', 'image', 'edit', 'Ändra bild', 0, NOW(), NOW()),
('se', 'image', 'exists', 'Bilden ''%s'' finns redan', 1, NOW(), NOW()),
('se', 'image', 'invalid_filesize', 'Ingen giltlig filstorlek: %s', 1, NOW(), NOW()),
('se', 'image', 'invalid_filetype', 'Ingen giltlig filändelse: %s', 1, NOW(), NOW()),
('se', 'image', 'invalid_temp_file', 'Kunde inte hitta den uppladdade temp filen', 0, NOW(), NOW()),
('se', 'image', 'maximum_filesize', 'Största tillåtna filstorlek: %s', 1, NOW(), NOW()),
('se', 'image', 'note_long_time', 'Notera: detta kan ta ett tag beroende på hur många bilder som är lagrade', 0, NOW(), NOW()),
('se', 'image', 'regenerate_thumbs', 'Regenerera miniatyr', 0, NOW(), NOW()),
('se', 'image', 'replace_existing', 'Ersätt existerande', 0, NOW(), NOW()),
('se', 'image', 'store_error', 'Kunde inte spara bilden', 0, NOW(), NOW()),
('se', 'image', 'thumbs_regenerated', '%d miniatyrer har regenererats', 1, NOW(), NOW()),
('se', 'image', 'unknown', 'Kunde inte hitta den begärda bilden', 0, NOW(), NOW()),
('se', 'image', 'updated', 'Bilden har uppdaterats', 0, NOW(), NOW()),
('se', 'image', 'upload', 'Ladda upp bild', 0, NOW(), NOW()),
('se', 'image', 'uploaded_normal', 'Bilden ''%s'' har laddats upp', 1, NOW(), NOW()),
('se', 'image', 'uploaded_replace', 'Bilden ''%s'' har laddats upp (ersätt existerande)', 1, NOW(), NOW()),
('se', 'info', 'developers', 'Utvecklare: %s', 1, NOW(), NOW()),
('se', 'info', 'last_updated', 'Uppdaterad: %s', 1, NOW(), NOW()),
('se', 'info', 'version', 'Version: %s', 1, NOW(), NOW()),
('se', 'section', 'admin', 'Admin', 0, NOW(), NOW()),
('se', 'section', 'cameras', 'Kameror', 0, NOW(), NOW()),
('se', 'section', 'galleries', 'Gallerier', 0, NOW(), NOW()),
('se', 'section', 'images', 'Bilder', 0, NOW(), NOW()),
('se', 'section', 'index', 'Första sidan', 0, NOW(), NOW()),
('se', 'section', 'tags', 'Taggar', 0, NOW(), NOW()),
('se', 'tag', 'create', 'Skapa tagg', 0, NOW(), NOW()),
('se', 'tag', 'created', 'Taggen ''%s'' har skapats', 1, NOW(), NOW()),
('se', 'tag', 'deleted', 'Taggen har raderats', 0, NOW(), NOW()),
('se', 'tag', 'delete_error', 'Kunde inte radera taggen', 0, NOW(), NOW()),
('se', 'tag', 'edit', 'Ändra tagg', 0, NOW(), NOW()),
('se', 'tag', 'exists', 'Taggen ''%s'' finns redan', 1, NOW(), NOW()),
('se', 'tag', 'image_added', 'Bilden har lagts till', 0, NOW(), NOW()),
('se', 'tag', 'image_removed', 'Bilden har tagits bort', 0, NOW(), NOW()),
('se', 'tag', 'not_tagged_images', 'Bilder som inte är taggade', 0, NOW(), NOW()),
('se', 'tag', 'store_error', 'Kunde inte skapa taggen ''%s''', 1, NOW(), NOW()),
('se', 'tag', 'tagged_images', 'Bilder som är taggade', 0, NOW(), NOW()),
('se', 'tag', 'unknown', 'Kunde inte hitta den begärda taggen', 0, NOW(), NOW()),
('se', 'tag', 'updated', 'Taggen har uppdaterats', 0, NOW(), NOW());

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
