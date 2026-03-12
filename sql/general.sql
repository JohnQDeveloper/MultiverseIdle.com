/* MAIN CHARACTERS TABLE */
CREATE TABLE
  `characters` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_save` timestamp NULL DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    `level` bigint unsigned DEFAULT NULL,
    `arena_floor` bigint unsigned DEFAULT NULL,
    `gold` bigint unsigned DEFAULT NULL,
    `iron` bigint unsigned DEFAULT NULL,
    `herbs` bigint unsigned DEFAULT NULL,
    `gems` bigint unsigned DEFAULT NULL,
    `party_json` json DEFAULT NULL,
    `worker_json` json DEFAULT NULL,
    `rift_queued` bigint unsigned DEFAULT NULL,
    `world_boss_queued` int DEFAULT NULL,
    `user_id` int DEFAULT NULL,
    `last_arena_log` text,
    `last_arena_time` datetime DEFAULT NULL,
    `active_potion_id` int DEFAULT NULL,
    `potion_expire_time` datetime DEFAULT NULL,
    `last_seen` datetime DEFAULT NULL,
    `world_boss_log` text,
    PRIMARY KEY (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci

ALTER TABLE characters
ADD COLUMN last_rift_time DATETIME DEFAULT NULL,
ADD COLUMN last_rift_log TEXT DEFAULT NULL;

ALTER TABLE characters ADD COLUMN credits BIGINT UNSIGNED DEFAULT 0;
ALTER TABLE characters ADD COLUMN subscription_expires DATETIME DEFAULT NULL;

ALTER TABLE characters ADD COLUMN last_free_credits_claim DATETIME DEFAULT NULL;


/* RIFT QUEUE TABLE */
CREATE TABLE
  `rifts` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `owner_id` int DEFAULT NULL,
    `details` json DEFAULT NULL,
    `queue_position` int DEFAULT NULL,
    `market_price` bigint unsigned DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci

/* GEAR TABLE */
CREATE TABLE
  `gear` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `details` json DEFAULT NULL,
    `market_price` bigint unsigned DEFAULT NULL,
    `owner_id` bigint unsigned DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci

ALTER TABLE gear ADD COLUMN favorite TINYINT(1) DEFAULT 0;

/* POTIONS TABLE */
CREATE TABLE
  `potions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `level` int DEFAULT NULL,
    `prefix` varchar(255) DEFAULT NULL,
    `suffix` varchar(255) DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    `owner_id` int DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci

/* ADJ */
ALTER TABLE characters ADD COLUMN highest_rift_level INT DEFAULT 0;

-- Guilds table
CREATE TABLE `guilds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `guild_master_user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Guild members table
CREATE TABLE `guild_members` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `guild_id` int unsigned NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('guild_master','officer','member') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guild_user` (`guild_id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Guild invites table
CREATE TABLE `guild_invites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `guild_id` int unsigned NOT NULL,
  `inviter_user_id` int NOT NULL,
  `invitee_user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guild_invitee` (`guild_id`, `invitee_user_id`),
  KEY `invitee_user_id` (`invitee_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
