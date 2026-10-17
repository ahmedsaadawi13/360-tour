-- Migration 001: Phase 2 (branding, leads, analytics, login throttling,
-- cancel-at-period-end subscriptions, globally unique tour slugs).
-- Apply with: php bin/migrate.php
--
-- NOTE: tour slugs become globally unique. If two tenants share a slug the
-- unique index fails; rename one of the duplicates first:
--   SELECT slug, COUNT(*) FROM tours GROUP BY slug HAVING COUNT(*) > 1;

ALTER TABLE `tenants`
  ADD COLUMN `brand_color` VARCHAR(7) DEFAULT NULL COMMENT 'Hex color used in public tour viewer' AFTER `website`,
  ADD COLUMN `webhook_url` VARCHAR(500) DEFAULT NULL COMMENT 'Receives lead/tour events as JSON (n8n, CRM, Zapier...)' AFTER `brand_color`;

ALTER TABLE `tenant_subscriptions`
  ADD COLUMN `cancel_at_period_end` TINYINT(1) NOT NULL DEFAULT 0 AFTER `canceled_at`;

ALTER TABLE `tours`
  ADD COLUMN `auto_rotate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN `lead_form_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `auto_rotate`,
  DROP INDEX `unique_slug_per_tenant`,
  DROP INDEX `idx_slug`,
  ADD UNIQUE KEY `unique_slug` (`slug`);

-- Reset tokens are now stored hashed; old plaintext tokens are invalid
DELETE FROM `password_resets`;
ALTER TABLE `password_resets`
  ADD COLUMN `expires_at` TIMESTAMP NULL DEFAULT NULL AFTER `token`;

CREATE TABLE `leads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `tour_id` INT UNSIGNED DEFAULT NULL,
  `property_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('new', 'contacted', 'qualified', 'closed', 'lost') NOT NULL DEFAULT 'new',
  `notes` TEXT DEFAULT NULL,
  `source` VARCHAR(50) DEFAULT 'tour',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_status (`tenant_id`, `status`),
  INDEX idx_tour_id (`tour_id`),
  INDEX idx_created (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tour_id`) REFERENCES `tours`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tour_views` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `tour_id` INT UNSIGNED NOT NULL,
  `visitor_hash` CHAR(64) NOT NULL,
  `referrer_host` VARCHAR(255) DEFAULT NULL,
  `is_embed` TINYINT(1) NOT NULL DEFAULT 0,
  `viewed_on` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tour_day (`tour_id`, `viewed_on`),
  INDEX idx_tenant_day (`tenant_id`, `viewed_on`),
  INDEX idx_visitor (`tour_id`, `visitor_hash`, `viewed_on`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tour_id`) REFERENCES `tours`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (`email`, `attempted_at`),
  INDEX idx_ip_time (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

