-- Migration Script for College Club Management System Missing Requirements
-- Apply this file to update existing databases

ALTER TABLE `clubs` 
  ADD COLUMN `logo` VARCHAR(255) DEFAULT NULL AFTER `club_head_id`,
  ADD COLUMN `email_subject` VARCHAR(255) DEFAULT NULL AFTER `logo`,
  ADD COLUMN `email_body` TEXT DEFAULT NULL AFTER `email_subject`;

ALTER TABLE `memberships`
  DROP INDEX `unique_active_user_club`,
  MODIFY COLUMN `status` ENUM('pending', 'active', 'inactive', 'rejected') NOT NULL DEFAULT 'pending',
  ADD COLUMN `leave_status` ENUM('none', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'none' AFTER `status`,
  ADD COLUMN `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `leave_status`,
  MODIFY COLUMN `joined_at` TIMESTAMP NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `announcement_reads` (
  `announcement_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`, `user_id`),
  FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
