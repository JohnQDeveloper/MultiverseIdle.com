<?php

declare(strict_types=1);

class Season
{
    private object $DAL;

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    public function GetActiveSeason(): ?array
    {
        $result = $this->DAL->r(
            "SELECT * FROM seasons WHERE status = 'active' ORDER BY start_date DESC LIMIT 1"
        );
        return (!empty($result)) ? $result[0] : null;
    }

    public function GetSeasonById(int $id): ?array
    {
        $result = $this->DAL->r(
            "SELECT * FROM seasons WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
        return (!empty($result)) ? $result[0] : null;
    }

    public function UserHasSeasonCharacter(int $user_id, int $season_id): bool
    {
        $result = $this->DAL->r(
            "SELECT COUNT(*) as count FROM characters WHERE user_id = :user_id AND season_id = :season_id",
            ['user_id' => $user_id, 'season_id' => $season_id]
        );
        return !empty($result) && (int)$result[0]['count'] > 0;
    }
}
