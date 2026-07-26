-- MASUMX TRADE Database Schema
-- Production Ready & Optimized for MySQL 8+

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `advertisements`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `activity_log`;
DROP TABLE IF EXISTS `bookmarks`;
DROP TABLE IF EXISTS `videos`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `status` ENUM('active', 'suspended') DEFAULT 'active',
  `is_verified` TINYINT(1) DEFAULT 0,
  `verification_token` VARCHAR(100) NULL,
  `reset_token` VARCHAR(100) NULL,
  `reset_expiry` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (`email`),
  INDEX idx_role (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Videos Table
CREATE TABLE `videos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `video_type` ENUM('youtube', 'vimeo', 'mp4') NOT NULL DEFAULT 'youtube',
  `video_url` VARCHAR(255) NOT NULL,
  `thumbnail_url` VARCHAR(255) NOT NULL,
  `duration` VARCHAR(20) DEFAULT '00:00',
  `views` INT DEFAULT 0,
  `instructor` VARCHAR(100) DEFAULT 'Admin',
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_trending` TINYINT(1) DEFAULT 0,
  `is_premium` TINYINT(1) DEFAULT 0,
  `seo_title` VARCHAR(150) NULL,
  `seo_description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX idx_category (`category_id`),
  INDEX idx_featured (`is_featured`),
  INDEX idx_trending (`is_trending`),
  INDEX idx_premium (`is_premium`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookmarks Table
CREATE TABLE `bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_user_video` (`user_id`, `video_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key_name` VARCHAR(100) NOT NULL UNIQUE,
  `val_value` TEXT NULL,
  INDEX idx_key (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advertisements Table
CREATE TABLE `advertisements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_position` VARCHAR(50) NOT NULL UNIQUE, -- banner, sidebar, popup
  `ad_code` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('announcement', 'popup') NOT NULL,
  `message` TEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs Table
CREATE TABLE `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Database with Premium Default configurations
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `is_verified`) VALUES
('Admin', 'admin@masumxtrade.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1); -- password: password

INSERT INTO `categories` (`name`, `slug`, `sort_order`) VALUES
('Newest', 'newest', 1),
('Popular', 'popular', 2),
('Beginner', 'beginner', 3),
('Advanced', 'advanced', 4);

INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('website_name', 'MASUMX TRADE'),
('primary_color', '#ffbd00'),
('secondary_color', '#00ff66'),
('logo_url', '/assets/images/logo.png'),
('favicon_url', '/assets/images/favicon.ico'),
('telegram_url', 'https://t.me/masumxtrade'),
('maintenance_mode', '0'),
('hero_title', 'Learn Trading Through Professional Video Tutorials'),
('hero_subtitle', 'Master Trading with Premium Educational Videos.'),
('smtp_host', 'smtp.mailtrap.io'),
('smtp_port', '2525'),
('smtp_user', 'username'),
('smtp_pass', 'password'),
('smtp_encryption', 'tls'),
('timezone', 'UTC'),
('analytics_id', 'UA-XXXXX-Y'),
('google_tag_manager', 'GTM-XXXXXX'),
('meta_pixel', 'FB-PIXEL-ID'),
('seo_title', 'MASUMX TRADE | Premium Educational Trading Platform'),
('seo_description', 'Learn Binary Options, Forex, Price Action, and Advanced Candlestick patterns with professional video tutorials.'),
('seo_keywords', 'trading, binary options, forex, learning, charts, financial education'),
('contact_email', 'support@masumxtrade.com'),
('contact_phone', '+880123456789'),
('contact_address', 'Dhaka, Bangladesh');

INSERT INTO `advertisements` (`ad_position`, `ad_code`, `is_active`) VALUES
('banner', '<div class="premium-ad-banner"><small>Advertisement</small><p>Invest in your skills with VIP Signals! <a href="#" class="btn-ad">Join VIP</a></p></div>', 1),
('sidebar', '<div class="premium-ad-sidebar"><small>Sponsored</small><h4>Broker of the Year</h4><p>Get up to 100% deposit bonus on PocketOption!</p><a href="#" class="btn-ad">Claim Now</a></div>', 1),
('popup', '<div class="premium-ad-popup"><h3>Special Trading Masterclass</h3><p>Get lifetime premium videos access today for only $49.</p><a href="/pricing.html" class="btn-ad">Access Now</a></div>', 0);

INSERT INTO `notifications` (`type`, `message`, `is_active`) VALUES
('announcement', 'Welcome to MASUMX TRADE! Join our Telegram Channel for live premium signals and free trading courses!', 1),
('popup', 'Maintenance Notice: The platform will be updated tonight at 12:00 AM UTC. Thank you for your cooperation!', 0);

INSERT INTO `videos` (`category_id`, `title`, `slug`, `description`, `video_type`, `video_url`, `thumbnail_url`, `duration`, `views`, `instructor`, `is_featured`, `is_trending`, `is_premium`, `seo_title`, `seo_description`) VALUES
(3, 'Binary Trading Basics - Complete Masterclass 2026', 'binary-trading-basics', 'Master the fundamentals of Binary Options, learning supports, resistance, and market structures.', 'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ', '/assets/images/thumb-binary.jpg', '45:32', 1250, 'Masum X', 1, 1, 0, 'Learn Binary Options Basics', 'Master Binary Options Trading easily with this beginner friendly guide.'),
(4, 'Advanced Candlestick Patterns & Price Action Secrets', 'advanced-candlestick-patterns', 'Learn how to read candlestick psychology and trade rejection levels with highest probability setups.', 'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ', '/assets/images/thumb-candlesticks.jpg', '55:15', 820, 'Masum X', 1, 0, 1, 'Advanced Candlestick & Price Action Course', 'Uncover secret price action strategies used by professional retail traders.');
