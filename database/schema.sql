-- PMRatnam CMS — MySQL schema (InnoDB, utf8mb4)
-- Written in Phase-block A. Do not recreate this file in later phases; ALTER/extend it.
-- Do not run against production until the installer (Phase B) is reviewed.
--
-- Engine/version target: MySQL 8.0+ / MariaDB 10.6+ (utf8mb4, JSON, CURRENT_TIMESTAMP on DATETIME).
-- Character set: utf8mb4 so Indian-language and emoji copy is safe.
--
-- Hostinger: create the database in hPanel first. The installer skips CREATE DATABASE
-- and USE because shared-hosting users cannot create schemas and the real name is
-- prefixed (e.g. u123456789_pmratnam). Statements below still include CREATE DATABASE
-- for local/dev convenience; production installs ignore those two statements.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `pmratnam`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pmratnam`;

-- ---------------------------------------------------------------------------
-- admins
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `menus`;
DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `redirects`;
DROP TABLE IF EXISTS `seo_settings`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `post_media`;
DROP TABLE IF EXISTS `page_media`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `media`;
DROP TABLE IF EXISTS `admins`;

CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'admin', 'editor', 'author') NOT NULL DEFAULT 'admin',
  `status` ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(45) NULL DEFAULT NULL,
  `failed_login_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_email` (`email`),
  KEY `idx_admins_status` (`status`),
  KEY `idx_admins_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed/successful login rows for throttling (Phase B: lock after 5 failures / 15 minutes).
CREATE TABLE `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email_time` (`email`, `attempted_at`),
  KEY `idx_login_attempts_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- media
-- ---------------------------------------------------------------------------
CREATE TABLE `media` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `disk_path` VARCHAR(512) NOT NULL COMMENT 'Relative path under /uploads',
  `filename` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(127) NOT NULL,
  `extension` VARCHAR(16) NOT NULL,
  `byte_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NULL DEFAULT NULL,
  `height` INT UNSIGNED NULL DEFAULT NULL,
  `alt_text` VARCHAR(255) NULL DEFAULT NULL,
  `title` VARCHAR(255) NULL DEFAULT NULL,
  `uploaded_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_created_at` (`created_at`),
  KEY `idx_media_mime_type` (`mime_type`),
  KEY `idx_media_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- categories (posts)
-- ---------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `status` ENUM('active', 'archived') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_status` (`status`),
  KEY `idx_categories_parent_id` (`parent_id`),
  KEY `idx_categories_created_at` (`created_at`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- pages
-- slug: unique. Homepage uses slug = '' and is_homepage = 1 (from "index page.html").
-- source_filename: original .html name for the importer; never delete those files in CMS code.
-- ---------------------------------------------------------------------------
CREATE TABLE `pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(191) NOT NULL DEFAULT '',
  `content` LONGTEXT NULL,
  `excerpt` TEXT NULL,
  `featured_image_id` INT UNSIGNED NULL DEFAULT NULL,
  `template` VARCHAR(64) NOT NULL DEFAULT 'page',
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `is_homepage` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL DEFAULT NULL,
  `meta_title` VARCHAR(255) NULL DEFAULT NULL,
  `meta_description` VARCHAR(512) NULL DEFAULT NULL,
  `canonical_url` VARCHAR(512) NULL DEFAULT NULL,
  `og_title` VARCHAR(255) NULL DEFAULT NULL,
  `og_description` VARCHAR(512) NULL DEFAULT NULL,
  `og_image_id` INT UNSIGNED NULL DEFAULT NULL,
  `schema_markup` MEDIUMTEXT NULL COMMENT 'JSON-LD or raw script payload',
  `source_filename` VARCHAR(255) NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `homepage_guard` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_homepage` = 1 THEN 1 ELSE NULL END) STORED COMMENT 'Enforces a single homepage row',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug` (`slug`),
  UNIQUE KEY `uq_pages_homepage` (`homepage_guard`),
  KEY `idx_pages_status` (`status`),
  KEY `idx_pages_created_at` (`created_at`),
  KEY `idx_pages_published_at` (`published_at`),
  KEY `idx_pages_featured_image_id` (`featured_image_id`),
  KEY `idx_pages_created_by` (`created_by`),
  CONSTRAINT `fk_pages_featured_image` FOREIGN KEY (`featured_image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pages_og_image` FOREIGN KEY (`og_image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pages_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pages_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- posts (blog)
-- ---------------------------------------------------------------------------
CREATE TABLE `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `content` LONGTEXT NULL,
  `excerpt` TEXT NULL,
  `category_id` INT UNSIGNED NULL DEFAULT NULL,
  `featured_image_id` INT UNSIGNED NULL DEFAULT NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `meta_title` VARCHAR(255) NULL DEFAULT NULL,
  `meta_description` VARCHAR(512) NULL DEFAULT NULL,
  `canonical_url` VARCHAR(512) NULL DEFAULT NULL,
  `og_title` VARCHAR(255) NULL DEFAULT NULL,
  `og_description` VARCHAR(512) NULL DEFAULT NULL,
  `og_image_id` INT UNSIGNED NULL DEFAULT NULL,
  `schema_markup` MEDIUMTEXT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_status` (`status`),
  KEY `idx_posts_category_id` (`category_id`),
  KEY `idx_posts_created_at` (`created_at`),
  KEY `idx_posts_published_at` (`published_at`),
  KEY `idx_posts_created_by` (`created_by`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_featured_image` FOREIGN KEY (`featured_image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_og_image` FOREIGN KEY (`og_image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional attachments beyond featured image (Phase G can use these).
CREATE TABLE `page_media` (
  `page_id` INT UNSIGNED NOT NULL,
  `media_id` INT UNSIGNED NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`page_id`, `media_id`),
  KEY `idx_page_media_media_id` (`media_id`),
  CONSTRAINT `fk_page_media_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_page_media_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_media` (
  `post_id` INT UNSIGNED NOT NULL,
  `media_id` INT UNSIGNED NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`post_id`, `media_id`),
  KEY `idx_post_media_media_id` (`media_id`),
  CONSTRAINT `fk_post_media_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_media_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- menus
-- ---------------------------------------------------------------------------
CREATE TABLE `menus` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `location` VARCHAR(64) NOT NULL COMMENT 'header, footer, mobile, etc.',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menus_location` (`location`),
  KEY `idx_menus_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `label` VARCHAR(191) NOT NULL,
  `url` VARCHAR(512) NULL DEFAULT NULL COMMENT 'External or explicit path; ignored if page_id set',
  `page_id` INT UNSIGNED NULL DEFAULT NULL,
  `target` ENUM('_self', '_blank') NOT NULL DEFAULT '_self',
  `css_class` VARCHAR(191) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_items_menu_id` (`menu_id`),
  KEY `idx_menu_items_parent_id` (`parent_id`),
  KEY `idx_menu_items_page_id` (`page_id`),
  KEY `idx_menu_items_sort` (`menu_id`, `sort_order`),
  CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_menu_items_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- seo_settings (site-wide singleton; row id = 1 created by installer)
-- Per-page SEO lives on pages/posts columns.
-- ---------------------------------------------------------------------------
CREATE TABLE `seo_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `site_name` VARCHAR(191) NOT NULL DEFAULT 'PMRatnam',
  `default_meta_title` VARCHAR(255) NULL DEFAULT NULL,
  `default_meta_description` VARCHAR(512) NULL DEFAULT NULL,
  `default_og_image_id` INT UNSIGNED NULL DEFAULT NULL,
  `robots_txt` MEDIUMTEXT NULL,
  `separator` VARCHAR(8) NOT NULL DEFAULT '|',
  `google_site_verification` VARCHAR(255) NULL DEFAULT NULL,
  `bing_site_verification` VARCHAR(255) NULL DEFAULT NULL,
  `organization_jsonld` MEDIUMTEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_seo_settings_og_image` FOREIGN KEY (`default_og_image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- leads (contact / service quote forms)
-- ---------------------------------------------------------------------------
CREATE TABLE `leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_name` VARCHAR(64) NOT NULL DEFAULT 'contact',
  `first_name` VARCHAR(191) NULL DEFAULT NULL,
  `last_name` VARCHAR(191) NULL DEFAULT NULL,
  `name` VARCHAR(191) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `phone` VARCHAR(64) NULL DEFAULT NULL,
  `company` VARCHAR(191) NULL DEFAULT NULL,
  `website` VARCHAR(512) NULL DEFAULT NULL,
  `message` TEXT NULL,
  `page_id` INT UNSIGNED NULL DEFAULT NULL,
  `source_url` VARCHAR(512) NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('new', 'read', 'archived', 'spam') NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leads_email` (`email`),
  KEY `idx_leads_status` (`status`),
  KEY `idx_leads_created_at` (`created_at`),
  KEY `idx_leads_page_id` (`page_id`),
  CONSTRAINT `fk_leads_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- settings (key/value; groups: general, social, seo, contact, analytics, install)
-- install.complete = 1 is the installer lock flag (Phase B).
-- ---------------------------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_group` VARCHAR(64) NOT NULL DEFAULT 'general',
  `setting_key` VARCHAR(191) NOT NULL,
  `setting_value` MEDIUMTEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- redirects
-- from_path stored as a site-relative path beginning with /, no host.
-- Checked before 404 in the front controller (Phase D).
-- ---------------------------------------------------------------------------
CREATE TABLE `redirects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_path` VARCHAR(512) NOT NULL,
  `to_path` VARCHAR(512) NOT NULL,
  `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` VARCHAR(255) NULL DEFAULT NULL,
  `hit_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redirects_from_path` (`from_path`(191)),
  KEY `idx_redirects_active` (`is_active`),
  KEY `idx_redirects_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- audit_logs
-- ---------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(64) NULL DEFAULT NULL,
  `entity_id` INT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `metadata` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_admin_id` (`admin_id`),
  KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  KEY `idx_audit_logs_action` (`action`),
  CONSTRAINT `fk_audit_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Installer (Phase B) should INSERT seo_settings id=1 and settings.install.complete after
-- creating the first admin. No seed content or hardcoded credentials here.
