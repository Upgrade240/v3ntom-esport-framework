-- V3NTOM E-Sport Management System Database Schema
-- Version: 1.0.0 - Clean Installation
-- Date: 2024-09-13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for table `roles`
-- Hierarchical permission system similar to TeamSpeak

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `hierarchy_level` int(11) DEFAULT 0,
  `color` varchar(7) DEFAULT '#5865f2',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `assignable_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `is_system_role` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `hierarchy_level` (`hierarchy_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles with hierarchical structure
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `hierarchy_level`, `color`, `permissions`, `assignable_roles`, `is_system_role`) VALUES
(1, 'super_admin', 'Super Administrator', 'Full system access with all permissions', 1000, '#ff0000', '{"*": true}', '[2,3,4,5,6,7]', 1),
(2, 'admin', 'Administrator', 'System administration with most permissions', 900, '#ff6600', '{"admin.*": true, "members.*": true, "teams.*": true, "discord.*": true, "teamspeak.*": true}', '[3,4,5,6,7]', 1),
(3, 'manager', 'Community Manager', 'Manage community and moderate users', 800, '#ffcc00', '{"members.view": true, "members.warn": true, "members.kick": true, "teams.view": true}', '[4,5,6]', 0),
(4, 'team_leader', 'Team Leader', 'Lead and manage specific teams', 700, '#00ff00', '{"teams.view": true, "teams.manage_own": true, "members.view": true}', '[5,6]', 0),
(5, 'moderator', 'Moderator', 'Basic moderation permissions', 600, '#0099ff', '{"members.view": true, "members.warn": true, "teams.view": true}', '[6]', 0),
(6, 'member', 'Member', 'Standard community member', 500, '#5865f2', '{"dashboard.view": true, "profile.edit_own": true, "teams.apply": true}', '[]', 0),
(7, 'trial', 'Trial Member', 'New member on trial period', 100, '#999999', '{"dashboard.view": true, "profile.view_own": true}', '[]', 0);

-- --------------------------------------------------------

-- Table structure for table `users`
-- Core user management with Discord integration

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `discriminator` varchar(4) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT 7,
  `additional_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `biography` text DEFAULT NULL,
  `voice_time` int(11) DEFAULT 0,
  `message_count` int(11) DEFAULT 0,
  `join_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','banned','trial') DEFAULT 'trial',
  `teamspeak_uid` varchar(100) DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `discord_id` (`discord_id`),
  KEY `role_id` (`role_id`),
  KEY `status` (`status`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `teams`
-- E-Sport team management

CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `game` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#5865f2',
  `leader_id` int(11) DEFAULT NULL,
  `co_leaders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `max_members` int(11) DEFAULT 10,
  `recruitment_open` tinyint(1) DEFAULT 1,
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `discord_role_id` varchar(20) DEFAULT NULL,
  `teamspeak_group_id` int(11) DEFAULT NULL,
  `statistics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `achievements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `leader_id` (`leader_id`),
  KEY `game` (`game`),
  KEY `recruitment_open` (`recruitment_open`),
  CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `team_members`
-- Team membership relationships

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','co_leader','leader') DEFAULT 'member',
  `position` varchar(50) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `performance_stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_user_unique` (`team_id`, `user_id`),
  KEY `team_id` (`team_id`),
  KEY `user_id` (`user_id`),
  KEY `role` (`role`),
  CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `team_applications`
-- Team application system with approval workflow

CREATE TABLE `team_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `motivation` text DEFAULT NULL,
  `availability` varchar(100) DEFAULT NULL,
  `previous_teams` text DEFAULT NULL,
  `additional_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `status` enum('pending','approved','rejected','withdrawn') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `user_id` (`user_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `team_applications_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_applications_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `events`
-- Event and calendar management

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `type` enum('training','match','meeting','tournament','community','scrim') DEFAULT 'community',
  `team_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `discord_event_id` varchar(20) DEFAULT NULL,
  `opponent` varchar(100) DEFAULT NULL,
  `map_pool` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `prize_pool` decimal(10,2) DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `status` enum('planned','ongoing','completed','cancelled') DEFAULT 'planned',
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `created_by` (`created_by`),
  KEY `start_date` (`start_date`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `event_participants`
-- Event participation tracking

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('attending','maybe','declined','backup') DEFAULT 'attending',
  `role` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_user_unique` (`event_id`, `user_id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `moderation_logs`
-- Complete moderation and audit trail

CREATE TABLE `moderation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('warn','kick','ban','unban','role_change','note','timeout','voice_kick','voice_ban') NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `moderator_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `platform` enum('discord','teamspeak','panel','website') DEFAULT 'panel',
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in seconds for temporary actions',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `automatic` tinyint(1) DEFAULT 0 COMMENT 'Was this action automatic?',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When temporary action expires',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `target_user_id` (`target_user_id`),
  KEY `moderator_id` (`moderator_id`),
  KEY `action_type` (`action_type`),
  KEY `platform` (`platform`),
  KEY `created_at` (`created_at`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `moderation_logs_ibfk_1` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moderation_logs_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `announcements`
-- System announcements and news

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `type` enum('info','warning','success','danger','maintenance') DEFAULT 'info',
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `target_teams` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `created_by` int(11) NOT NULL,
  `pinned` tinyint(1) DEFAULT 0,
  `send_notification` tinyint(1) DEFAULT 0,
  `notification_sent` tinyint(1) DEFAULT 0,
  `published` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `reactions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `created_at` (`created_at`),
  KEY `type` (`type`),
  KEY `published` (`published`),
  KEY `pinned` (`pinned`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_activities`
-- User activity and statistics tracking

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `platform` enum('discord','teamspeak','panel','website') DEFAULT 'panel',
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in seconds',
  `channel_name` varchar(100) DEFAULT NULL,
  `server_name` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `date` date NOT NULL,
  `hour` tinyint(4) DEFAULT NULL COMMENT 'Hour of the day (0-23)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `activity_type` (`activity_type`),
  KEY `platform` (`platform`),
  KEY `date` (`date`),
  KEY `date_hour` (`date`, `hour`),
  CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `support_tickets`
-- Support ticket system

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category` enum('technical','application','complaint','suggestion','ban_appeal','other') DEFAULT 'other',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_user','waiting_admin','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `first_response_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `satisfaction_rating` tinyint(4) DEFAULT NULL COMMENT 'Rating 1-5',
  `satisfaction_comment` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `internal_notes` text DEFAULT NULL,
  `estimated_resolution` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `user_id` (`user_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`),
  KEY `category` (`category`),
  KEY `priority` (`priority`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `ticket_responses`
-- Support ticket conversation history

CREATE TABLE `ticket_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_staff_response` tinyint(1) DEFAULT 0,
  `is_internal` tinyint(1) DEFAULT 0 COMMENT 'Internal staff note',
  `response_time` int(11) DEFAULT NULL COMMENT 'Response time in seconds',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `edited` tinyint(1) DEFAULT 0,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  KEY `is_staff_response` (`is_staff_response`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `ticket_responses_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `system_settings`
-- Dynamic system configuration

CREATE TABLE `system_settings` (
  `key` varchar(100) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `type` enum('string','integer','boolean','json','float') DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Can be accessed via public API',
  `requires_restart` tinyint(1) DEFAULT 0 COMMENT 'Requires system restart to take effect',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`key`),
  KEY `category` (`category`),
  KEY `is_public` (`is_public`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT INTO `system_settings` (`key`, `value`, `type`, `category`, `description`, `is_public`) VALUES
('site_name', 'V3NTOM eSport', 'string', 'general', 'Website name displayed in title and navigation', 1),
('site_description', 'E-Sport & Community Management System', 'string', 'general', 'Website description for SEO and about pages', 1),
('site_keywords', 'esport,gaming,community,management,discord,teamspeak', 'string', 'general', 'SEO keywords', 1),
('discord_invite', '', 'string', 'discord', 'Discord server invite link', 1),
('teamspeak_address', '', 'string', 'teamspeak', 'TeamSpeak server address for users', 1),
('registration_open', 'true', 'boolean', 'general', 'Allow new user registrations', 0),
('maintenance_mode', 'false', 'boolean', 'general', 'Enable maintenance mode', 0),
('max_team_applications', '3', 'integer', 'teams', 'Maximum simultaneous team applications per user', 0),
('auto_accept_members', 'false', 'boolean', 'teams', 'Automatically accept trial members as full members after time period', 0),
('trial_period_days', '7', 'integer', 'general', 'Trial period duration in days', 0),
('default_theme', 'dark', 'string', 'appearance', 'Default theme for new users', 1),
('enable_statistics', 'true', 'boolean', 'features', 'Enable statistics tracking', 0),
('enable_voice_tracking', 'true', 'boolean', 'features', 'Enable voice channel time tracking', 0),
('enable_message_tracking', 'true', 'boolean', 'features', 'Enable message count tracking', 0),
('backup_retention_days', '30', 'integer', 'system', 'How long to keep backup files', 0),
('log_retention_days', '90', 'integer', 'system', 'How long to keep log files', 0),
('session_timeout', '7200', 'integer', 'security', 'Session timeout in seconds', 0),
('max_login_attempts', '5', 'integer', 'security', 'Maximum login attempts before lockout', 0),
('lockout_duration', '300', 'integer', 'security', 'Account lockout duration in seconds', 0),
('enable_2fa', 'false', 'boolean', 'security', 'Enable two-factor authentication', 0),
('timezone', 'Europe/Berlin', 'string', 'general', 'Default timezone', 1),
('date_format', 'd.m.Y H:i', 'string', 'general', 'Default date format', 1),
('currency', 'EUR', 'string', 'general', 'Default currency for tournaments', 1),
('enable_tournaments', 'true', 'boolean', 'features', 'Enable tournament system', 1),
('enable_team_applications', 'true', 'boolean', 'features', 'Enable team application system', 1),
('enable_support_tickets', 'true', 'boolean', 'features', 'Enable support ticket system', 1);

-- --------------------------------------------------------

-- Table structure for table `notifications`
-- User notification system

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('team_application','event_reminder','role_change','announcement','ticket_update','team_invite','warning','ban','system') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `read_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `delivery_method` enum('web','email','discord','push') DEFAULT 'web',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `read_at` (`read_at`),
  KEY `priority` (`priority`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_sessions`
-- User session management

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `audit_logs`
-- System audit trail for compliance and security

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `endpoint` varchar(200) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `execution_time` decimal(8,3) DEFAULT NULL COMMENT 'Execution time in milliseconds',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `table_name` (`table_name`),
  KEY `created_at` (`created_at`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `file_uploads`
-- File upload management and security

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_hash` varchar(64) NOT NULL,
  `category` enum('avatar','team_logo','banner','document','image','other') DEFAULT 'other',
  `associated_type` varchar(50) DEFAULT NULL COMMENT 'teams, users, tickets, etc.',
  `associated_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `virus_scan_status` enum('pending','clean','infected','error') DEFAULT 'pending',
  `virus_scan_result` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_hash` (`file_hash`),
  KEY `user_id` (`user_id`),
  KEY `category` (`category`),
  KEY `associated_type` (`associated_type`, `associated_id`),
  KEY `virus_scan_status` (`virus_scan_status`),
  CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `api_keys`
-- API access management

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `key_hash` varchar(255) NOT NULL,
  `key_prefix` varchar(10) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `rate_limit` int(11) DEFAULT 1000 COMMENT 'Requests per hour',
  `allowed_ips` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(45) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`),
  UNIQUE KEY `key_prefix` (`key_prefix`),
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `scheduled_tasks`
-- Background task scheduling

CREATE TABLE `scheduled_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('backup','cleanup','sync','notification','report','maintenance') NOT NULL,
  `command` text NOT NULL,
  `schedule` varchar(100) NOT NULL COMMENT 'Cron expression',
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `is_active` tinyint(1) DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `last_run_status` enum('success','error','timeout','skipped') DEFAULT NULL,
  `last_run_output` text DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `run_count` int(11) DEFAULT 0,
  `error_count` int(11) DEFAULT 0,
  `max_execution_time` int(11) DEFAULT 300 COMMENT 'Maximum execution time in seconds',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `task_type` (`task_type`),
  KEY `is_active` (`is_active`),
  KEY `next_run_at` (`next_run_at`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `scheduled_tasks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default scheduled tasks
INSERT INTO `scheduled_tasks` (`name`, `description`, `task_type`, `command`, `schedule`, `parameters`, `is_active`) VALUES
('cleanup_expired_sessions', 'Remove expired user sessions', 'cleanup', 'DELETE FROM user_sessions WHERE expires_at < NOW()', '0 2 * * *', '{}', 1),
('cleanup_old_logs', 'Remove old audit logs based on retention policy', 'cleanup', 'DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)', '0 3 * * 0', '{}', 1),
('sync_discord_members', 'Synchronize Discord member status', 'sync', 'php /path/to/sync_discord.php', '*/15 * * * *', '{}', 1),
('backup_database', 'Create daily database backup', 'backup', 'mysqldump v3ntom_esport > /backups/daily_$(date +%Y%m%d).sql', '0 4 * * *', '{}', 1),
('send_pending_notifications', 'Send queued notifications', 'notification', 'php /path/to/send_notifications.php', '*/5 * * * *', '{}', 1);

-- --------------------------------------------------------

-- Table structure for table `webhooks`
-- Webhook management for integrations

CREATE TABLE `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]',
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `is_active` tinyint(1) DEFAULT 1,
  `retry_count` int(11) DEFAULT 3,
  `timeout` int(11) DEFAULT 30,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_status_code` int(11) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `success_count` int(11) DEFAULT 0,
  `error_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `webhooks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Create indexes for better performance

-- User activity indexes for analytics
CREATE INDEX idx_user_activities_date_user ON user_activities(date, user_id);
CREATE INDEX idx_user_activities_type_date ON user_activities(activity_type, date);

-- Moderation logs for efficient searching
CREATE INDEX idx_moderation_logs_date ON moderation_logs(created_at);
CREATE INDEX idx_moderation_logs_user_date ON moderation_logs(target_user_id, created_at);

-- Team applications for dashboard widgets
CREATE INDEX idx_team_applications_status_date ON team_applications(status, created_at);

-- Events for calendar views
CREATE INDEX idx_events_date_range ON events(start_date, end_date);
CREATE INDEX idx_events_team_date ON events(team_id, start_date);

-- Notifications for user dashboard
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, read_at);
CREATE INDEX idx_notifications_user_created ON notifications(user_id, created_at);

-- Support tickets for admin dashboard
CREATE INDEX idx_tickets_status_priority ON support_tickets(status, priority);
CREATE INDEX idx_tickets_assigned_status ON support_tickets(assigned_to, status);

-- --------------------------------------------------------

-- Sample data will be created after first user registration

-- --------------------------------------------------------

-- Final optimizations

-- Ensure referential integrity
SET FOREIGN_KEY_CHECKS = 1;

-- Optimize table statistics
ANALYZE TABLE roles, users, teams, team_members, team_applications, events, moderation_logs, announcements, user_activities, support_tickets, notifications;

-- --------------------------------------------------------

COMMIT;

-- --------------------------------------------------------
-- Installation Complete
-- --------------------------------------------------------

-- V3NTOM E-Sport Management System Database Schema Installation Complete
-- 
-- Features installed:
-- ✅ Hierarchical role system with TeamSpeak-style permissions
-- ✅ User management with Discord integration
-- ✅ Team management with applications workflow
-- ✅ Event and calendar system
-- ✅ Complete moderation and audit trail
-- ✅ Support ticket system
-- ✅ Notification system
-- ✅ File upload management
-- ✅ API key management
-- ✅ Background task scheduling
-- ✅ Webhook integrations
-- ✅ Performance optimized indexes
-- ✅ Security and compliance features
--
-- Next steps:
-- 1. Configure Discord Bot credentials
-- 2. Set up TeamSpeak Query (optional)
-- 3. Configure system settings via admin panel
-- 4. Create your first teams and events
-- 5. Test all integrations
--
-- Documentation: /admin/documentation.php
-- Admin Panel: /admin/
--
-- Have fun with your new E-Sport Management System!





