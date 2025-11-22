-- Splash360 Tour - Complete Database Schema
-- Multi-tenant SaaS platform for 360° virtual tours
-- Compatible with MySQL 5.7+

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `hotspots`;
DROP TABLE IF EXISTS `scenes`;
DROP TABLE IF EXISTS `tours`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `tenant_subscriptions`;
DROP TABLE IF EXISTS `plans`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `tenants`;
DROP TABLE IF EXISTS `settings`;
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- TENANTS TABLE
-- =====================================================
CREATE TABLE `tenants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `logo` VARCHAR(500) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `website` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('active', 'suspended', 'canceled') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (`status`),
  INDEX idx_slug (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('platform_admin', 'tenant_admin', 'agent') NOT NULL DEFAULT 'agent',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (`email`),
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_role (`role`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUBSCRIPTION PLANS TABLE
-- =====================================================
CREATE TABLE `plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `price_monthly` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `price_yearly` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `max_properties` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_active_tours` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_scenes` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_users` INT UNSIGNED NOT NULL DEFAULT 0,
  `features` TEXT DEFAULT NULL COMMENT 'JSON array of features',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slug (`slug`),
  INDEX idx_active (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TENANT SUBSCRIPTIONS TABLE
-- =====================================================
CREATE TABLE `tenant_subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('trialing', 'active', 'past_due', 'canceled', 'expired') DEFAULT 'trialing',
  `billing_cycle` ENUM('monthly', 'yearly') DEFAULT 'monthly',
  `trial_end` TIMESTAMP NULL DEFAULT NULL,
  `current_period_start` TIMESTAMP NULL DEFAULT NULL,
  `current_period_end` TIMESTAMP NULL DEFAULT NULL,
  `canceled_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_plan_id (`plan_id`),
  INDEX idx_status (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INVOICES TABLE
-- =====================================================
CREATE TABLE `invoices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `subscription_id` INT UNSIGNED DEFAULT NULL,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `amount` DECIMAL(10, 2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `status` ENUM('draft', 'pending', 'paid', 'overdue', 'canceled') DEFAULT 'pending',
  `due_date` DATE NOT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_subscription_id (`subscription_id`),
  INDEX idx_invoice_number (`invoice_number`),
  INDEX idx_status (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_id`) REFERENCES `tenant_subscriptions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `method` VARCHAR(50) DEFAULT 'offline' COMMENT 'offline, credit_card, paypal, etc.',
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
  `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_invoice_id (`invoice_id`),
  INDEX idx_status (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PROPERTIES TABLE
-- =====================================================
CREATE TABLE `properties` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `reference_code` VARCHAR(100) DEFAULT NULL,
  `type` ENUM('apartment', 'villa', 'office', 'land', 'penthouse', 'townhouse', 'warehouse', 'shop', 'other') DEFAULT 'apartment',
  `status` ENUM('for_sale', 'for_rent', 'sold', 'rented', 'off_market') DEFAULT 'for_sale',
  `price` DECIMAL(12, 2) DEFAULT NULL,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `city` VARCHAR(100) DEFAULT NULL,
  `area` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `bedrooms` INT DEFAULT NULL,
  `bathrooms` INT DEFAULT NULL,
  `size` DECIMAL(10, 2) DEFAULT NULL COMMENT 'in square meters',
  `description` TEXT DEFAULT NULL,
  `main_image` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_reference_code (`reference_code`),
  INDEX idx_type (`type`),
  INDEX idx_status (`status`),
  INDEX idx_city (`city`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TOURS TABLE
-- =====================================================
CREATE TABLE `tours` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `property_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('draft', 'published') DEFAULT 'draft',
  `is_public` TINYINT(1) DEFAULT 1,
  `is_featured` TINYINT(1) DEFAULT 0,
  `views_count` INT UNSIGNED DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_slug_per_tenant (`tenant_id`, `slug`),
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_property_id (`property_id`),
  INDEX idx_slug (`slug`),
  INDEX idx_status (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SCENES TABLE
-- =====================================================
CREATE TABLE `scenes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `tour_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(500) NOT NULL COMMENT 'Path to 360 panoramic image',
  `thumbnail_path` VARCHAR(500) DEFAULT NULL,
  `initial_yaw` DECIMAL(6, 2) DEFAULT 0.00 COMMENT 'Initial horizontal view angle',
  `initial_pitch` DECIMAL(6, 2) DEFAULT 0.00 COMMENT 'Initial vertical view angle',
  `order_index` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_tour_id (`tour_id`),
  INDEX idx_order (`order_index`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tour_id`) REFERENCES `tours`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- HOTSPOTS TABLE
-- =====================================================
CREATE TABLE `hotspots` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `scene_id` INT UNSIGNED NOT NULL,
  `type` ENUM('navigation', 'info', 'link') NOT NULL DEFAULT 'navigation',
  `target_scene_id` INT UNSIGNED DEFAULT NULL COMMENT 'For navigation hotspots',
  `yaw` DECIMAL(6, 2) NOT NULL COMMENT 'Horizontal position angle',
  `pitch` DECIMAL(6, 2) NOT NULL COMMENT 'Vertical position angle',
  `label` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `external_url` VARCHAR(500) DEFAULT NULL COMMENT 'For link hotspots',
  `icon_type` VARCHAR(50) DEFAULT 'arrow' COMMENT 'arrow, info, link, etc.',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_id (`tenant_id`),
  INDEX idx_scene_id (`scene_id`),
  INDEX idx_type (`type`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`scene_id`) REFERENCES `scenes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_scene_id`) REFERENCES `scenes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASSWORD RESETS TABLE
-- =====================================================
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (`email`),
  INDEX idx_token (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SETTINGS TABLE (for platform-wide settings)
-- =====================================================
CREATE TABLE `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT DEFAULT NULL,
  `type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA - Default Plans
-- =====================================================
INSERT INTO `plans` (`name`, `slug`, `description`, `price_monthly`, `price_yearly`, `max_properties`, `max_active_tours`, `max_scenes`, `max_users`, `features`, `is_active`, `sort_order`) VALUES
('Starter', 'starter', 'Perfect for small agencies getting started', 29.00, 290.00, 10, 5, 50, 2, '["Up to 10 properties", "5 active tours", "50 scenes total", "2 users", "Email support"]', 1, 1),
('Professional', 'professional', 'Great for growing real estate businesses', 79.00, 790.00, 50, 25, 250, 5, '["Up to 50 properties", "25 active tours", "250 scenes total", "5 users", "Priority email support", "Custom branding"]', 1, 2),
('Enterprise', 'enterprise', 'For large agencies with high volume needs', 199.00, 1990.00, 9999, 9999, 9999, 20, '["Unlimited properties", "Unlimited tours", "Unlimited scenes", "20 users", "Priority support", "Custom branding", "API access", "White label option"]', 1, 3);

-- =====================================================
-- SEED DATA - Platform Admin User
-- Password: admin123 (change this after first login!)
-- =====================================================
INSERT INTO `users` (`tenant_id`, `email`, `password`, `first_name`, `last_name`, `role`, `status`) VALUES
(NULL, 'admin@splash360tour.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform', 'Admin', 'platform_admin', 'active');

-- =====================================================
-- SEED DATA - Default Settings
-- =====================================================
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('site_name', 'Splash360 Tour', 'string', 'Platform name'),
('support_email', 'support@splash360tour.com', 'string', 'Support contact email'),
('trial_days', '14', 'integer', 'Default trial period in days'),
('default_currency', 'USD', 'string', 'Default currency code');
