-- Uhura (ConnectHub) MySQL Schema and Seed Data
-- Target: GoDaddy Shared Hosting (MySQL 5.7+/8.0)
-- Database: uhura_dbase
-- Notes:
--  - If your GoDaddy MySQL does not permit CREATE DATABASE from SQL, create the DB in cPanel, then run from the USE statement down.
--  - JSON type requires MySQL 5.7+. If on older versions, change JSON columns to TEXT.
--  - All tables use InnoDB and utf8mb4 for full Unicode support.

CREATE DATABASE IF NOT EXISTS `uhura_dbase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uhura_dbase`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- USERS
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `bio` TEXT NULL,
  `profile_image` VARCHAR(255) NULL,
  `role` ENUM('member','organizer','admin','super_admin') DEFAULT 'member',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=active,0=inactive,2=pending,3=suspended',
  `membership_expires_at` DATETIME NULL,
  `membership_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `points` INT NOT NULL DEFAULT 0,
  `last_login` DATETIME NULL,
  `login_attempts` INT NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(255) NULL,
  `password_reset_token` VARCHAR(255) NULL,
  `password_reset_expires` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_username` (`username`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CATEGORIES
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(50) NULL,
  `color` VARCHAR(7) NULL DEFAULT '#007bff',
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GROUPS
CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `privacy` ENUM('public','private','secret') NOT NULL DEFAULT 'public',
  `category_id` INT UNSIGNED NULL,
  `location_city` VARCHAR(100) NULL,
  `location_state` VARCHAR(100) NULL,
  `location_country` VARCHAR(100) NULL,
  `latitude` DECIMAL(10,8) NULL,
  `longitude` DECIMAL(11,8) NULL,
  `member_count` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_groups_slug` (`slug`),
  KEY `idx_groups_created_by` (`created_by`),
  CONSTRAINT `fk_groups_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GROUP MEMBERSHIPS (aligned with app: owner/co_host/moderator/member)
CREATE TABLE IF NOT EXISTS `group_memberships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `role` ENUM('owner','co_host','moderator','member') NOT NULL DEFAULT 'member',
  `status` ENUM('active','pending','banned','inactive') NOT NULL DEFAULT 'active',
  `joined_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_group_user` (`group_id`,`user_id`),
  KEY `idx_group_memberships_user` (`user_id`),
  CONSTRAINT `fk_group_memberships_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_group_memberships_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENTS (aligned with Event model)
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NULL,
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `location_type` ENUM('in_person','online','hybrid') NOT NULL DEFAULT 'in_person',
  `venue_name` VARCHAR(255) NULL,
  `venue_address` VARCHAR(500) NULL,
  `online_link` VARCHAR(500) NULL,
  `max_attendees` INT NULL,
  `registration_deadline` DATETIME NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `cover_image` VARCHAR(255) NULL,
  `tags` JSON NULL,
  `requirements` TEXT NULL,
  `status` ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_slug` (`slug`),
  KEY `idx_events_group` (`group_id`),
  KEY `idx_events_date_time` (`event_date`,`start_time`),
  CONSTRAINT `fk_events_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENT ATTENDEES (aligned with RSVP logic in app)
CREATE TABLE IF NOT EXISTS `event_attendees` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('going','maybe','not_going','waitlist') NOT NULL DEFAULT 'going',
  `notes` TEXT NULL,
  `registered_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_user` (`event_id`,`user_id`),
  KEY `idx_event_attendees_user` (`user_id`),
  CONSTRAINT `fk_event_attendees_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_attendees_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENT COMMENTS
CREATE TABLE IF NOT EXISTS `event_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `comment` TEXT NOT NULL,
  `status` ENUM('active','hidden','deleted') NOT NULL DEFAULT 'active',
  `likes_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_comments_event` (`event_id`),
  KEY `idx_event_comments_user` (`user_id`),
  KEY `idx_event_comments_parent` (`parent_id`),
  CONSTRAINT `fk_event_comments_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `event_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENT MEDIA
CREATE TABLE IF NOT EXISTS `event_media` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `file_size` INT NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `status` ENUM('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_media_event` (`event_id`),
  KEY `idx_event_media_user` (`user_id`),
  KEY `idx_event_media_type` (`file_type`),
  CONSTRAINT `fk_event_media_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_media_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COMMENT LIKES
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `reaction_type` ENUM('like','love','laugh','angry','sad') NOT NULL DEFAULT 'like',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comment_user` (`comment_id`,`user_id`),
  KEY `idx_comment_likes_user` (`user_id`),
  CONSTRAINT `fk_comment_likes_comment` FOREIGN KEY (`comment_id`) REFERENCES `event_comments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAYMENTS
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `type` ENUM('membership','event','other') NOT NULL,
  `status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL,
  `transaction_id` VARCHAR(255) NULL,
  `stripe_payment_intent_id` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_user` (`user_id`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- POINTS TRANSACTIONS
CREATE TABLE IF NOT EXISTS `points_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `points` INT NOT NULL,
  `type` ENUM('earned','redeemed','bonus','penalty') NOT NULL,
  `description` TEXT NULL,
  `event_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_points_user` (`user_id`),
  CONSTRAINT `fk_points_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_points_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTIFICATIONS
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  `read_status` TINYINT(1) NOT NULL DEFAULT 0,
  `action_url` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LOGIN ATTEMPTS (MySQL port)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(255) NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `user_agent` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip_time` (`ip_address`,`attempted_at`),
  KEY `idx_login_attempts_success_time` (`success`,`attempted_at`),
  KEY `idx_login_attempts_email_time` (`email`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================
-- SEED DATA
-- =====================

-- Categories
INSERT INTO `categories` (`name`,`slug`,`description`,`icon`,`color`) VALUES
('Technology','technology','Tech meetups, programming, and software development','fas fa-laptop-code','#007bff'),
('Business','business','Networking, entrepreneurship, and professional development','fas fa-briefcase','#28a745'),
('Arts & Culture','arts-culture','Creative arts, museums, and cultural events','fas fa-palette','#dc3545'),
('Sports & Fitness','sports-fitness','Sports activities, fitness groups, and outdoor adventures','fas fa-dumbbell','#fd7e14'),
('Food & Drink','food-drink','Culinary experiences, wine tasting, and food lovers','fas fa-utensils','#e83e8c'),
('Education','education','Learning groups, workshops, and skill development','fas fa-graduation-cap','#6f42c1'),
('Music','music','Concerts, music appreciation, and musical performances','fas fa-music','#20c997'),
('Health & Wellness','health-wellness','Mental health, wellness practices, and healthy living','fas fa-heart','#17a2b8'),
('Photography','photography','Photo walks, photography techniques, and visual arts','fas fa-camera','#ffc107'),
('Travel','travel','Travel groups, cultural exchange, and adventure trips','fas fa-plane','#6c757d')
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Admin and sample users (password for all below: password123)
-- Hash corresponds to bcrypt of 'password123'
INSERT INTO `users` (`username`,`email`,`password_hash`,`first_name`,`last_name`,`role`,`status`,`email_verified`,`membership_paid`,`membership_expires_at`) VALUES
('admin','admin@connecthub.local','$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG','Admin','User','super_admin',1,1,1,DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('organizer','organizer@connecthub.local','$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG','John','Organizer','organizer',1,1,1,DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('member','member@connecthub.local','$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG','Jane','Member','member',1,1,1,DATE_ADD(NOW(), INTERVAL 1 YEAR))
ON DUPLICATE KEY UPDATE `first_name`=VALUES(`first_name`);

-- Sample groups (owned by organizer id=2)
INSERT INTO `groups` (`name`,`slug`,`description`,`privacy`,`category_id`,`location_city`,`location_state`,`location_country`,`created_by`) VALUES
('Tech Innovators Meetup','tech-innovators-meetup','A community for technology enthusiasts and innovators to share ideas and network.','public',1,'San Francisco','CA','USA',2),
('Business Leaders Network','business-leaders-network','Professional networking group for business leaders and entrepreneurs.','public',2,'New York','NY','USA',2),
('Photography Enthusiasts','photography-enthusiasts','Group for amateur and professional photographers to share techniques and organize photo walks.','public',9,'Los Angeles','CA','USA',2)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Auto-add owner membership for organizer
INSERT INTO `group_memberships` (`group_id`,`user_id`,`role`,`status`)
SELECT g.id, 2, 'owner', 'active' FROM `groups` g
LEFT JOIN `group_memberships` gm ON gm.group_id = g.id AND gm.user_id = 2
WHERE gm.id IS NULL;

-- Optional: sample event (for first group)
INSERT INTO `events` (`title`,`slug`,`description`,`group_id`,`created_by`,`event_date`,`start_time`,`end_time`,`timezone`,`location_type`,`venue_name`,`venue_address`,`max_attendees`,`price`,`currency`,`status`)
VALUES ('Welcome Mixer','welcome-mixer','Kickoff event for our community', (SELECT id FROM `groups` WHERE slug='tech-innovators-meetup'), 2,
        DATE_ADD(CURDATE(), INTERVAL 14 DAY), '18:00:00','20:00:00','America/Phoenix','in_person','Main Hall','123 Main St, SF, CA',100,0.00,'USD','published')
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Optional: organizer RSVP 'going' to the sample event
INSERT INTO `event_attendees` (`event_id`,`user_id`,`status`)
SELECT e.id, 2, 'going' FROM `events` e WHERE e.slug='welcome-mixer'
ON DUPLICATE KEY UPDATE `status`='going';

-- Done
