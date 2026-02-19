-- =============================================
-- iRescue v2.0 - Competition-Level Database
-- =============================================

CREATE DATABASE IF NOT EXISTS `irescue_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `irescue_db`;

-- =============================================
-- USERS
-- =============================================
CREATE TABLE `users` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  `username`          VARCHAR(50) NOT NULL,
  `email`             VARCHAR(100) NOT NULL,
  `password`          VARCHAR(255) NOT NULL,
  `full_name`         VARCHAR(100) DEFAULT NULL,
  `role`              ENUM('user','responder','admin') NOT NULL DEFAULT 'user',
  `is_verified`       TINYINT(1) NOT NULL DEFAULT 0,
  `verify_token`      VARCHAR(64) DEFAULT NULL,
  `reset_token`       VARCHAR(64) DEFAULT NULL,
  `reset_token_expiry` DATETIME DEFAULT NULL,
  `avatar_url`        VARCHAR(255) DEFAULT NULL,
  `last_login`        DATETIME DEFAULT NULL,
  `login_attempts`    INT(3) NOT NULL DEFAULT 0,
  `locked_until`      DATETIME DEFAULT NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin (password: Admin@123)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_verified`) VALUES
(1, 'admin', 'admin@irescue.local', '$2y$10$2BP0MaSLm/JDrDd3bWXDQOR7k4j5uRS3Wd/zStEn75/u7Rn1ufiuW', 'System Administrator', 'admin', 1),
(2, 'responder1', 'responder@irescue.local', '$2y$12$TK.VPi6.FXoThDMMVSo4M.1grwBq8CzlZJkTjt1/wvEXi5Q6HYvDy', 'Field Responder', 'responder', 1),
(3, 'jdoe', 'jdoe@irescue.local', '$2y$12$TK.VPi6.FXoThDMMVSo4M.1grwBq8CzlZJkTjt1/wvEXi5Q6HYvDy', 'John Doe', 'user', 1);

-- =============================================
-- REPORTS / INCIDENTS
-- =============================================
CREATE TABLE `reports` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11) NOT NULL,
  `category`      ENUM('Fire','Flood','Medical','Crime','Accident','Other') NOT NULL,
  `severity`      ENUM('Low','Moderate','High','Critical') NOT NULL DEFAULT 'Moderate',
  `title`         VARCHAR(255) NOT NULL DEFAULT '',
  `details`       TEXT NOT NULL,
  `location_lat`  DECIMAL(10,8) DEFAULT NULL,
  `location_lng`  DECIMAL(11,8) DEFAULT NULL,
  `address`       VARCHAR(255) DEFAULT NULL,
  `photo_path`    VARCHAR(255) DEFAULT NULL,
  `zone`          VARCHAR(50) NOT NULL DEFAULT 'Unassigned',
  `status`        ENUM('Pending','Responding','Resolved','Closed') NOT NULL DEFAULT 'Pending',
  `assigned_to`   INT(11) DEFAULT NULL,
  `resolved_at`   DATETIME DEFAULT NULL,
  `timestamp`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reports` (`id`, `user_id`, `category`, `severity`, `title`, `details`, `location_lat`, `location_lng`, `address`, `zone`, `status`, `assigned_to`, `timestamp`) VALUES
(1, 3, 'Flood', 'High', 'Flooding on Main Street', 'Main street is impassable due to rising floodwaters near the old bridge. Water level approximately 1.5 meters.', 14.59950000, 120.98420000, 'Main St, near Old Bridge', 'Zone 4', 'Responding', 2, NOW() - INTERVAL 1 HOUR),
(2, 3, 'Fire', 'Critical', 'Warehouse Fire on Industrial Ave', 'Smoke and flames seen coming from the abandoned warehouse on Industrial Ave. Possible chemical hazard.', 14.60100000, 120.98550000, 'Industrial Ave, Warehouse District', 'Zone 2', 'Pending', NULL, NOW() - INTERVAL 2 HOUR),
(3, 3, 'Medical', 'Moderate', 'Injured Person at Central Park', 'Person found unconscious near the fountain. Possible cardiac event. CPR in progress.', 14.59780000, 120.97900000, 'Central Park, Main Entrance', 'Zone 1', 'Resolved', 2, NOW() - INTERVAL 5 HOUR),
(4, 3, 'Crime', 'High', 'Armed Robbery at Convenience Store', 'Two suspects reported at QuickMart on Commerce Rd. Store clerk has been threatened.', 14.60250000, 120.98700000, '12 Commerce Rd, QuickMart', 'Zone 3', 'Pending', NULL, NOW() - INTERVAL 30 MINUTE);

-- =============================================
-- ALERTS / ANNOUNCEMENTS
-- =============================================
CREATE TABLE `alerts` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `level`     ENUM('Info','Warning','Critical') NOT NULL,
  `title`     VARCHAR(255) NOT NULL,
  `details`   TEXT NOT NULL,
  `posted_by` INT(11) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `alerts` (`id`, `level`, `title`, `details`, `posted_by`, `timestamp`) VALUES
(1, 'Warning', 'Typhoon Signal #2 Raised', 'All residents are advised to secure their homes and stay indoors. Pre-emptive evacuations are underway in low-lying coastal areas. Stay tuned for official updates from NDRRMC.', 1, NOW() - INTERVAL 3 HOUR),
(2, 'Info', 'Vaccination Drive: Barangay Health Center', 'Free COVID-19 booster shots available at all health centers from 8am–5pm this week. Bring your vaccination card.', 1, NOW() - INTERVAL 1 DAY),
(3, 'Critical', 'Bridge Closure: Northbound Lane', 'Due to structural assessment, the northbound lane of Riverside Bridge is closed until further notice. Use alternate routes via Southgate Ave.', 1, NOW() - INTERVAL 2 HOUR);

-- =============================================
-- HOTLINES
-- =============================================
CREATE TABLE `hotlines` (
  `id`       INT(11) NOT NULL AUTO_INCREMENT,
  `service`  VARCHAR(100) NOT NULL,
  `number`   VARCHAR(50) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'General',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `hotlines` (`id`, `service`, `number`, `category`) VALUES
(1, 'National Emergency Hotline', '911', 'Emergency'),
(2, 'Police Department', '117', 'Law Enforcement'),
(3, 'Fire Department', '160', 'Fire'),
(4, 'Ambulance / Medical Services', '161', 'Medical'),
(5, 'Disaster Response Unit', '132', 'Disaster'),
(6, 'Red Cross Philippines', '143', 'Disaster'),
(7, 'Coast Guard Hotline', '5100', 'Maritime'),
(8, 'Mental Health Crisis Line', '1553', 'Medical');

-- =============================================
-- LOCATIONS
-- =============================================
CREATE TABLE `locations` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(255) NOT NULL,
  `address`   VARCHAR(255) NOT NULL,
  `type`      ENUM('Evacuation Center','Hospital','Police Station','Fire Station','Coast Guard') NOT NULL,
  `capacity`  INT(11) DEFAULT NULL,
  `contact`   VARCHAR(50) DEFAULT NULL,
  `lat`       DECIMAL(10,8) DEFAULT NULL,
  `lng`       DECIMAL(11,8) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `locations` (`id`, `name`, `address`, `type`, `capacity`, `contact`) VALUES
(1, 'Central City Evacuation Hall', '123 Main St, Central City', 'Evacuation Center', 500, '02-8123-4567'),
(2, 'General Hospital', '456 Health Ave, Central City', 'Hospital', NULL, '02-8234-5678'),
(3, 'Community Sports Complex', '789 Victory Rd, Central City', 'Evacuation Center', 1200, '02-8345-6789'),
(4, 'Police Precinct 1', '101 Justice Way, Central City', 'Police Station', NULL, '02-8456-7890'),
(5, 'Fire Station Bravo', '212 Blaze Blvd, Central City', 'Fire Station', NULL, '02-8567-8901');

-- =============================================
-- ACTIVITY LOGS
-- =============================================
CREATE TABLE `activity_logs` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`   INT(11) DEFAULT NULL,
  `username`  VARCHAR(50) NOT NULL,
  `action`    VARCHAR(100) NOT NULL,
  `details`   TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- INCIDENT HISTORY (status change trail)
-- =============================================
CREATE TABLE `incident_history` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `report_id`  INT(11) NOT NULL,
  `changed_by` INT(11) NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `note`       TEXT DEFAULT NULL,
  `timestamp`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `history_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- LOGIN RATE LIMITING
-- =============================================
CREATE TABLE `login_attempts` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `username`   VARCHAR(50) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;