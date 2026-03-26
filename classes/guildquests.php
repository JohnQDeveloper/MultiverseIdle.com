<?php

declare(strict_types=1);

class GuildQuests
{
    public const MAX_ACTIVE_QUESTS = 4;

    private const GRADE_MULTIPLIERS = [
        'easy'      => 5,
        'normal'    => 10,
        'hard'      => 15,
        'legendary' => 20,
    ];

    private const QUEST_REWARD_MULTIPLIERS = [
        'pvp_wins'          => 1000,
        'arena_wins'        => 500,
        'world_boss_top50'  => 500,
    ];

    private const VALID_GRADES     = ['easy', 'normal', 'hard', 'legendary'];
    private const VALID_RESOURCES  = ['gold', 'iron', 'herbs', 'gems'];
    private const VALID_QUEST_TYPES = ['pvp_wins', 'arena_wins', 'world_boss_top50'];

    private object $DAL;
    private ?int $seasonId = null;

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    public function setSeasonId(?int $season_id): void
    {
        $this->seasonId = $season_id;
    }

    /**
     * Calculate X (the quest target) for a given grade, member count, and completed quest count.
     * Easy:      X = 5  * (members + quests_completed / 50)
     * Normal:    X = 10 * (members + quests_completed / 50)
     * Hard:      X = 15 * (members + quests_completed / 50)
     * Legendary: X = 20 * (members + quests_completed / 50)
     */
    public function calculateX(string $grade, int $member_count, int $quests_completed): int
    {
        $multiplier = self::GRADE_MULTIPLIERS[$grade] ?? 5;
        $x = (int) round($multiplier * ($member_count + $quests_completed / 50));
        return max(1, $x);
    }

    /**
     * Get all active (not yet completed) quests for a guild.
     *
     * @return array<int, array<string, mixed>>
     */
    public function GetActiveQuests(int $guild_id): array
    {
        $result = $this->DAL->r(
            "SELECT * FROM guild_quests
             WHERE guild_id = :guild_id AND is_completed = 0 AND season_id <=> :season_id
             ORDER BY created_at ASC",
            [':guild_id' => $guild_id, ':season_id' => $this->seasonId]
        );
        return $result ?: [];
    }

    /**
     * Get the total number of completed quests for this guild (used in X formula).
     */
    public function GetCompletedQuestCount(int $guild_id): int
    {
        $result = $this->DAL->r(
            "SELECT COUNT(*) AS cnt FROM guild_quests
             WHERE guild_id = :guild_id AND is_completed = 1 AND season_id <=> :season_id",
            [':guild_id' => $guild_id, ':season_id' => $this->seasonId]
        );
        return (int) ($result[0]['cnt'] ?? 0);
    }

    /**
     * Get recently completed quests (last 10) for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function GetRecentCompletedQuests(int $guild_id): array
    {
        $result = $this->DAL->r(
            "SELECT * FROM guild_quests
             WHERE guild_id = :guild_id AND is_completed = 1 AND season_id <=> :season_id
             ORDER BY completed_at DESC
             LIMIT 10",
            [':guild_id' => $guild_id, ':season_id' => $this->seasonId]
        );
        return $result ?: [];
    }

    /**
     * Add a new quest of the given grade to the guild queue.
     * Returns false if already at MAX_ACTIVE_QUESTS or grade is invalid.
     */
    public function AddQuest(int $guild_id, string $grade, int $member_count): bool
    {
        if (!in_array($grade, self::VALID_GRADES, true)) {
            return false;
        }

        $active = $this->GetActiveQuests($guild_id);
        if (count($active) >= self::MAX_ACTIVE_QUESTS) {
            return false;
        }

        $quests_completed = $this->GetCompletedQuestCount($guild_id);
        $x = $this->calculateX($grade, $member_count, $quests_completed);

        $quest_types   = self::VALID_QUEST_TYPES;
        $resources     = self::VALID_RESOURCES;
        $quest_type    = $quest_types[array_rand($quest_types)];
        $resource_type = $resources[array_rand($resources)];
        $reward_amount = $x * self::QUEST_REWARD_MULTIPLIERS[$quest_type];

        return $this->DAL->w(
            "INSERT INTO guild_quests
                (guild_id, season_id, grade, quest_type, resource_type, target_amount, reward_amount)
             VALUES
                (:guild_id, :season_id, :grade, :quest_type, :resource_type, :target_amount, :reward_amount)",
            [
                ':guild_id'      => $guild_id,
                ':season_id'     => $this->seasonId,
                ':grade'         => $grade,
                ':quest_type'    => $quest_type,
                ':resource_type' => $resource_type,
                ':target_amount' => $x,
                ':reward_amount' => $reward_amount,
            ]
        );
    }

    /**
     * Increment progress on all active quests of the given type for a guild.
     * Completes quests that reach their target and deposits the reward into guild_bank.
     */
    public function IncrementProgress(int $guild_id, string $quest_type, int $amount = 1): void
    {
        if (!in_array($quest_type, self::VALID_QUEST_TYPES, true) || $amount <= 0) {
            return;
        }

        $quests = $this->DAL->r(
            "SELECT id, current_amount, target_amount, resource_type, reward_amount
             FROM guild_quests
             WHERE guild_id = :guild_id
               AND quest_type = :quest_type
               AND is_completed = 0
               AND season_id <=> :season_id",
            [
                ':guild_id'   => $guild_id,
                ':quest_type' => $quest_type,
                ':season_id'  => $this->seasonId,
            ]
        );

        if (empty($quests)) {
            return;
        }

        foreach ($quests as $quest) {
            $quest_id    = (int) $quest['id'];
            $new_amount  = (int) $quest['current_amount'] + $amount;
            $target      = (int) $quest['target_amount'];
            $resource    = $quest['resource_type'];
            $reward      = (int) $quest['reward_amount'];

            if ($new_amount >= $target) {
                $this->DAL->w(
                    "UPDATE guild_quests
                     SET current_amount = :target, is_completed = 1, completed_at = NOW()
                     WHERE id = :id",
                    [':target' => $target, ':id' => $quest_id]
                );

                // Deposit reward into guild bank
                $this->DAL->w(
                    "INSERT INTO guild_bank (guild_id, `{$resource}`)
                     VALUES (:guild_id, :amount)
                     ON DUPLICATE KEY UPDATE `{$resource}` = `{$resource}` + :amount2",
                    [':guild_id' => $guild_id, ':amount' => $reward, ':amount2' => $reward]
                );
            } else {
                $this->DAL->w(
                    "UPDATE guild_quests SET current_amount = :amount WHERE id = :id",
                    [':amount' => $new_amount, ':id' => $quest_id]
                );
            }
        }
    }
}
