-- setting the users role_mask > 1 gives them chat moderator priviledges

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
ALTER TABLE characters ADD COLUMN highest_rift_level INT DEFAULT 0;

ALTER TABLE `characters`
ADD COLUMN `inventory_json` JSON DEFAULT NULL AFTER `worker_json`;

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

ALTER TABLE potions ADD COLUMN market_price BIGINT UNSIGNED DEFAULT NULL;

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

ALTER TABLE guilds ADD COLUMN season_id INT NULL DEFAULT NULL;


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

-- Guild buildings table
CREATE TABLE guild_buildings (
  guild_id INT UNSIGNED NOT NULL,
  farm INT UNSIGNED NOT NULL DEFAULT 0,
  iron_mine INT UNSIGNED NOT NULL DEFAULT 0,
  gem_mine INT UNSIGNED NOT NULL DEFAULT 0,
  market INT UNSIGNED NOT NULL DEFAULT 0,
  gym INT UNSIGNED NOT NULL DEFAULT 0,
  tavern INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (guild_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Seed rows for any existing guilds
INSERT IGNORE INTO guild_buildings (guild_id) SELECT id FROM guilds;

-- Market Orders Table
CREATE TABLE `market_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `character_id` int unsigned NOT NULL,
  `order_type` enum('buy','sell') NOT NULL,
  `resource` enum('herbs','iron','gems','credits') NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `amount_remaining` bigint unsigned NOT NULL,
  `price_per_unit` bigint unsigned NOT NULL,
  `status` enum('open','filled','cancelled') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`),
  KEY `resource_status` (`resource`, `status`, `price_per_unit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE market_orders ADD COLUMN season_id INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE market_orders ADD INDEX idx_season_resource_status (season_id, resource, status);


-- Season Changes
CREATE TABLE seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,           -- e.g. "Season 1: The Iron Age"
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,           -- 6 months after start
    status ENUM('upcoming','active','ended') DEFAULT 'upcoming',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Characters get a season_id column
-- NULL = perpetual, integer = belongs to that season
ALTER TABLE characters ADD COLUMN season_id INT UNSIGNED NULL DEFAULT NULL REFERENCES seasons(id);
ALTER TABLE characters ADD INDEX idx_season (season_id);

-- Season isolation for item market listings
ALTER TABLE gear     ADD COLUMN season_id INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE rifts    ADD COLUMN season_id INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE potions  ADD COLUMN season_id INT UNSIGNED NULL DEFAULT NULL;

-- Season isolation for resource orders (+ performance index)
ALTER TABLE market_orders ADD COLUMN season_id INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE market_orders ADD INDEX idx_season_resource_status (season_id, resource, status);

-- Manually adding a season for testing purposes
-- INSERT INTO seasons (name, start_date, end_date, status)
-- VALUES ('Season 1', NOW(), DATE_ADD(NOW(), INTERVAL 4 MONTH), 'active');

-- Wire Log Table to track resource transfers between players (e.g. arena rewards, rift rewards, gifting)
CREATE TABLE `wire_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sender_user_id` int NOT NULL,
  `recipient_user_id` int NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `commodity` enum('gold','iron','herbs','gems') NOT NULL,
  `season_id` int unsigned NULL DEFAULT NULL,
  KEY `sender` (`sender_user_id`),
  KEY `recipient` (`recipient_user_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Referral Codes Table
CREATE TABLE `referral_codes` (
    `user_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(16) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `referral_uses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `referrer_user_id` INT UNSIGNED NOT NULL,
    `referred_user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `referred_user_id` (`referred_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guild Bank Setup
ALTER TABLE guilds ADD COLUMN tax_rate TINYINT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE guild_bank (
  guild_id INT UNSIGNED NOT NULL,
  gold BIGINT UNSIGNED NOT NULL DEFAULT 0,
  iron BIGINT UNSIGNED NOT NULL DEFAULT 0,
  herbs BIGINT UNSIGNED NOT NULL DEFAULT 0,
  gems BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (guild_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--- treasure_chests
CREATE TABLE treasure_chests (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    owner_id      INT NOT NULL,
    chest_size    ENUM('small', 'medium', 'large') NOT NULL,
    queue_position INT NOT NULL,
    season_id     INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY owner_queue (owner_id, queue_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE characters ADD COLUMN last_pvp_log  TEXT DEFAULT NULL;
ALTER TABLE characters ADD COLUMN last_pvp_time DATETIME DEFAULT NULL;


--- guild quests
CREATE TABLE `guild_quests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guild_id` INT UNSIGNED NOT NULL,
  `season_id` INT UNSIGNED NULL DEFAULT NULL,
  `grade` ENUM('easy', 'normal', 'hard', 'legendary') NOT NULL,
  `quest_type` ENUM('pvp_wins', 'arena_wins', 'world_boss_top50') NOT NULL,
  `resource_type` ENUM('gold', 'iron', 'herbs', 'gems') NOT NULL,
  `target_amount` INT UNSIGNED NOT NULL,
  `current_amount` INT UNSIGNED NOT NULL DEFAULT 0,
  `reward_amount` BIGINT UNSIGNED NOT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`guild_id`) REFERENCES `guilds`(`id`) ON DELETE CASCADE,
  INDEX `idx_guild_quests_active` (`guild_id`, `is_completed`, `season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chat_moderators` (
  `user_id` INT UNSIGNED NOT NULL,
  `promoted_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `idx_chat_moderators_promoted_by` (`promoted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `chat_mutes` (
  `user_id` INT UNSIGNED NOT NULL,
  `muted_by` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(200) NOT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `idx_chat_mutes_expires_at` (`expires_at`),
  KEY `idx_chat_mutes_muted_by` (`muted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `chat_ignores` (
  `user_id` int NOT NULL,
  `ignored_user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `ignored_user_id`),
  KEY `idx_chat_ignores_ignored_user_id` (`ignored_user_id`)
);

-- skill gems table
CREATE TABLE `skill_gems` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `created_at` DATETIME DEFAULT NOW(),
    `owner_id` INT,
    `skill_name` VARCHAR(100),
    `details` JSON,
    `name` VARCHAR(255),
    `favorite` TINYINT(1) DEFAULT 0,
    `season_id` INT DEFAULT NULL,
    `market_price` INT DEFAULT 0
);
