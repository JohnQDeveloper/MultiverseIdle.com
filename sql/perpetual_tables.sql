CREATE TABLE
  `perpetual_characters` (
    `user_id` int unsigned NOT NULL,
    `json_save` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `last_save` datetime DEFAULT CURRENT_TIMESTAMP,
    `energy` int DEFAULT '120',
    `max_energy` int DEFAULT '120',
    `nerve` int DEFAULT '120',
    `max_nerve` int DEFAULT '120',
    `life` int DEFAULT '120',
    `max_life` int DEFAULT '120',
    `toxicity` int DEFAULT '120',
    `money` int DEFAULT NULL,
    `premium_end_date` datetime DEFAULT NULL,
    `last_job` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`user_id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci

CREATE TABLE
  `economy_stats` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `type` varchar(255) DEFAULT NULL,
    `amt` bigint DEFAULT '0',
    PRIMARY KEY (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci
