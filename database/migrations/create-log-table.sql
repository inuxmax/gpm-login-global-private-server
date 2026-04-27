CREATE TABLE IF NOT EXISTS `logs` (
    `id` CHAR(36) NOT NULL,
    `time` DATETIME NOT NULL,
    `target_id` CHAR(36) NULL DEFAULT NULL COMMENT 'profile_id, group_id',
    `target_type` VARCHAR(32) NULL DEFAULT NULL COMMENT 'group, profile, proxy, ...',
    `user_id` CHAR(36) NULL DEFAULT NULL,
    `type` VARCHAR(16) NOT NULL COMMENT 'info, warn, error',
    `message` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `logs_time_index` (`time`),
    KEY `logs_target_id_index` (`target_id`),
    KEY `logs_target_type_index` (`target_type`),
    KEY `logs_user_id_index` (`user_id`),
    KEY `logs_type_index` (`type`),
    CONSTRAINT `logs_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
