<?php

declare(strict_types=1);

class Guild
{
    private const MAX_GUILD_MEMBERS = 20;
    private const INVITE_EXPIRY_HOURS = 72; // 3 days

    private object $DAL;
    private ?int $seasonId = null;

    /**
     * @var array<string, mixed> Guild data
     */
    public array $Data = [];

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
     * Get user ID from parameter or session
     */
    private function getUserId(int $user_id = 0): int
    {
        if ($user_id === 0 && isset($_SESSION['auth_user_id'])) {
            return (int)$_SESSION['auth_user_id'];
        }
        return $user_id;
    }

    /**
     * Create a new guild
     */
    public function CreateGuild(string $name, string $description, int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        // Check if user is already in a guild
        if ($this->GetUserGuildId($user_id) !== null) {
            return false;
        }

        // Validate guild name
        $name = trim($name);
        if (strlen($name) < 3 || strlen($name) > 50) {
            return false;
        }

        // Create the guild
        $query = "INSERT INTO guilds (name, description, guild_master_user_id, season_id)
                  VALUES (:name, :description, :guild_master_user_id, :season_id)";
        $params = [
            ':name'               => $name,
            ':description'        => $description,
            ':guild_master_user_id' => $user_id,
            ':season_id'          => $this->seasonId,
        ];

        if (!$this->DAL->w($query, $params)) {
            return false;
        }

        $guild_id = (int)$this->DAL->last_insert_id();

        // Initialise the guild bank row
        $this->DAL->w(
            "INSERT INTO guild_bank (guild_id) VALUES (:guild_id)",
            [':guild_id' => $guild_id]
        );

        // Initialise the guild buildings row
        $this->DAL->w(
            "INSERT IGNORE INTO guild_buildings (guild_id) VALUES (:guild_id)",
            [':guild_id' => $guild_id]
        );

        // Add creator as guild master
        $member_query = "INSERT INTO guild_members (guild_id, user_id, role)
                        VALUES (:guild_id, :user_id, 'guild_master')";
        $member_params = [
            ':guild_id' => $guild_id,
            ':user_id' => $user_id
        ];

        return $this->DAL->w($member_query, $member_params);
    }

    /**
     * Get guild ID for a user (returns null if not in a guild)
     */
    public function GetUserGuildId(int $user_id = 0): ?int
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return null;
        }

        $query = "SELECT gm.guild_id FROM guild_members gm
                  JOIN guilds g ON g.id = gm.guild_id
                  WHERE gm.user_id = :user_id AND g.season_id <=> :season_id";
        $result = $this->DAL->r($query, [':user_id' => $user_id, ':season_id' => $this->seasonId]);

        if (!$result || empty($result)) {
            return null;
        }

        return (int)$result[0]['guild_id'];
    }

    /**
     * Get user's role in their guild
     */
    public function GetUserRole(int $user_id = 0): ?string
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return null;
        }

        $query = "SELECT gm.role FROM guild_members gm
                  JOIN guilds g ON g.id = gm.guild_id
                  WHERE gm.user_id = :user_id AND g.season_id <=> :season_id";
        $result = $this->DAL->r($query, [':user_id' => $user_id, ':season_id' => $this->seasonId]);

        if (!$result || empty($result)) {
            return null;
        }

        return $result[0]['role'];
    }

    /**
     * Load guild by ID
     */
    public function LoadGuildById(int $guild_id): bool
    {
        $query = "SELECT * FROM guilds WHERE id = :guild_id";
        $result = $this->DAL->r($query, [':guild_id' => $guild_id]);

        if (!$result || empty($result)) {
            return false;
        }

        $this->Data = $result[0];
        return true;
    }

    /**
     * Load guild for a user
     */
    public function LoadUserGuild(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);
        $guild_id = $this->GetUserGuildId($user_id);

        if ($guild_id === null) {
            return false;
        }

        return $this->LoadGuildById($guild_id);
    }

    /**
     * Get all members of a guild
     *
     * @return array<int, array<string, mixed>>
     */
    public function GetGuildMembers(int $guild_id): array
    {
        $query = "SELECT gm.user_id, gm.role, gm.joined_at, c.name as character_name, c.level
                  FROM guild_members gm
                  LEFT JOIN characters c ON c.user_id = gm.user_id AND c.season_id <=> :season_id
                  WHERE gm.guild_id = :guild_id
                  ORDER BY
                    CASE gm.role
                      WHEN 'guild_master' THEN 1
                      WHEN 'officer' THEN 2
                      WHEN 'member' THEN 3
                    END,
                    gm.joined_at ASC";

        $result = $this->DAL->r($query, [':guild_id' => $guild_id, ':season_id' => $this->seasonId]);

        return $result ?: [];
    }

    /**
     * Check if user can invite members (guild_master or officer)
     */
    public function CanInviteMembers(int $user_id = 0): bool
    {
        $role = $this->GetUserRole($user_id);
        return in_array($role, ['guild_master', 'officer'], true);
    }

    /**
     * Check if user can kick members (guild_master or officer)
     */
    public function CanKickMembers(int $user_id = 0): bool
    {
        $role = $this->GetUserRole($user_id);
        return in_array($role, ['guild_master', 'officer'], true);
    }

    /**
     * Check if user is guild master
     */
    public function IsGuildMaster(int $user_id = 0): bool
    {
        $role = $this->GetUserRole($user_id);
        return $role === 'guild_master';
    }

    /**
     * Send guild invite to a user
     */
    public function SendInvite(int $invitee_user_id, int $inviter_user_id = 0): bool
    {
        $inviter_user_id = $this->getUserId($inviter_user_id);

        if ($inviter_user_id <= 0) {
            return false;
        }

        // Check if inviter can invite
        if (!$this->CanInviteMembers($inviter_user_id)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($inviter_user_id);
        if ($guild_id === null) {
            return false;
        }

        // Check if invitee is already in a guild
        if ($this->GetUserGuildId($invitee_user_id) !== null) {
            return false;
        }

        // Check member count
        $current_members = $this->GetGuildMembers($guild_id);
        if (count($current_members) >= self::MAX_GUILD_MEMBERS) {
            return false;
        }

        // Check if invite already exists
        $check_query = "SELECT id FROM guild_invites
                       WHERE guild_id = :guild_id
                       AND invitee_user_id = :invitee_user_id
                       AND expires_at > NOW()";
        $existing = $this->DAL->r($check_query, [
            ':guild_id' => $guild_id,
            ':invitee_user_id' => $invitee_user_id
        ]);

        if ($existing && !empty($existing)) {
            return false; // Invite already exists
        }

        // Create invite
        $query = "INSERT INTO guild_invites (guild_id, inviter_user_id, invitee_user_id, expires_at)
                  VALUES (:guild_id, :inviter_user_id, :invitee_user_id,
                          DATE_ADD(NOW(), INTERVAL :hours HOUR))";
        $params = [
            ':guild_id' => $guild_id,
            ':inviter_user_id' => $inviter_user_id,
            ':invitee_user_id' => $invitee_user_id,
            ':hours' => self::INVITE_EXPIRY_HOURS
        ];

        return $this->DAL->w($query, $params);
    }

    /**
     * Get pending invites for a user
     *
     * @return array<int, array<string, mixed>>
     */
    public function GetUserInvites(int $user_id = 0): array
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return [];
        }

        $query = "SELECT gi.id as invite_id, gi.guild_id, gi.created_at,
                         g.name as guild_name, g.description,
                         c.name as inviter_name
                  FROM guild_invites gi
                  JOIN guilds g ON g.id = gi.guild_id AND g.season_id <=> :season_id
                  LEFT JOIN characters c ON c.user_id = gi.inviter_user_id AND c.season_id <=> :season_id2
                  WHERE gi.invitee_user_id = :user_id
                  AND gi.expires_at > NOW()
                  ORDER BY gi.created_at DESC";

        $result = $this->DAL->r($query, [
            ':user_id'   => $user_id,
            ':season_id' => $this->seasonId,
            ':season_id2' => $this->seasonId,
        ]);

        return $result ?: [];
    }

    /**
     * Accept guild invite
     */
    public function AcceptInvite(int $invite_id, int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        // Check if user is already in a guild
        if ($this->GetUserGuildId($user_id) !== null) {
            return false;
        }

        // Get invite details
        $query = "SELECT guild_id FROM guild_invites
                  WHERE id = :invite_id
                  AND invitee_user_id = :user_id
                  AND expires_at > NOW()";
        $result = $this->DAL->r($query, [
            ':invite_id' => $invite_id,
            ':user_id' => $user_id
        ]);

        if (!$result || empty($result)) {
            return false;
        }

        $guild_id = (int)$result[0]['guild_id'];

        // Check member count
        $current_members = $this->GetGuildMembers($guild_id);
        if (count($current_members) >= self::MAX_GUILD_MEMBERS) {
            return false;
        }

        // Add user to guild as member
        $add_query = "INSERT INTO guild_members (guild_id, user_id, role)
                     VALUES (:guild_id, :user_id, 'member')";
        if (!$this->DAL->w($add_query, [
            ':guild_id' => $guild_id,
            ':user_id' => $user_id
        ])) {
            return false;
        }

        // Delete all invites for this user (they've joined a guild)
        $delete_query = "DELETE FROM guild_invites WHERE invitee_user_id = :user_id";
        $this->DAL->w($delete_query, [':user_id' => $user_id]);

        return true;
    }

    /**
     * Decline guild invite
     */
    public function DeclineInvite(int $invite_id, int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $query = "DELETE FROM guild_invites
                  WHERE id = :invite_id
                  AND invitee_user_id = :user_id";

        return $this->DAL->w($query, [
            ':invite_id' => $invite_id,
            ':user_id' => $user_id
        ]);
    }

    /**
     * Kick member from guild
     */
    public function KickMember(int $target_user_id, int $kicker_user_id = 0): bool
    {
        $kicker_user_id = $this->getUserId($kicker_user_id);

        if ($kicker_user_id <= 0) {
            return false;
        }

        // Check if kicker can kick
        if (!$this->CanKickMembers($kicker_user_id)) {
            return false;
        }

        // Can't kick yourself
        if ($target_user_id === $kicker_user_id) {
            return false;
        }

        // Get guild IDs
        $kicker_guild_id = $this->GetUserGuildId($kicker_user_id);
        $target_guild_id = $this->GetUserGuildId($target_user_id);

        // Must be in same guild
        if ($kicker_guild_id === null || $kicker_guild_id !== $target_guild_id) {
            return false;
        }

        // Get roles
        $kicker_role = $this->GetUserRole($kicker_user_id);
        $target_role = $this->GetUserRole($target_user_id);

        // Can't kick guild master
        if ($target_role === 'guild_master') {
            return false;
        }

        // Officers can't kick other officers, only guild master can
        if ($kicker_role === 'officer' && $target_role === 'officer') {
            return false;
        }

        // Remove member
        $query = "DELETE FROM guild_members
                  WHERE guild_id = :guild_id
                  AND user_id = :user_id";

        return $this->DAL->w($query, [
            ':guild_id' => $target_guild_id,
            ':user_id' => $target_user_id
        ]);
    }

    /**
     * Leave guild
     */
    public function LeaveGuild(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($user_id);
        if ($guild_id === null) {
            return false;
        }

        // Guild masters can't leave - they must transfer or disband
        if ($this->IsGuildMaster($user_id)) {
            return false;
        }

        $query = "DELETE FROM guild_members
                  WHERE guild_id = :guild_id
                  AND user_id = :user_id";

        return $this->DAL->w($query, [
            ':guild_id' => $guild_id,
            ':user_id' => $user_id
        ]);
    }

    /**
     * Transfer guild master role
     */
    public function TransferGuildMaster(int $new_master_user_id, int $current_master_user_id = 0): bool
    {
        $current_master_user_id = $this->getUserId($current_master_user_id);

        if ($current_master_user_id <= 0) {
            return false;
        }

        // Check if current user is guild master
        if (!$this->IsGuildMaster($current_master_user_id)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($current_master_user_id);
        if ($guild_id === null) {
            return false;
        }

        // Check if new master is in the same guild
        if ($this->GetUserGuildId($new_master_user_id) !== $guild_id) {
            return false;
        }

        // Update guild table
        $update_guild = "UPDATE guilds SET guild_master_user_id = :new_master
                        WHERE id = :guild_id";
        if (!$this->DAL->w($update_guild, [
            ':new_master' => $new_master_user_id,
            ':guild_id' => $guild_id
        ])) {
            return false;
        }

        // Update old master to officer
        $demote_query = "UPDATE guild_members SET role = 'officer'
                        WHERE guild_id = :guild_id AND user_id = :user_id";
        $this->DAL->w($demote_query, [
            ':guild_id' => $guild_id,
            ':user_id' => $current_master_user_id
        ]);

        // Update new master role
        $promote_query = "UPDATE guild_members SET role = 'guild_master'
                         WHERE guild_id = :guild_id AND user_id = :user_id";

        return $this->DAL->w($promote_query, [
            ':guild_id' => $guild_id,
            ':user_id' => $new_master_user_id
        ]);
    }

    /**
     * Promote member to officer
     */
    public function PromoteToOfficer(int $target_user_id, int $promoter_user_id = 0): bool
    {
        $promoter_user_id = $this->getUserId($promoter_user_id);

        if ($promoter_user_id <= 0) {
            return false;
        }

        // Only guild master can promote
        if (!$this->IsGuildMaster($promoter_user_id)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($promoter_user_id);
        if ($guild_id === null) {
            return false;
        }

        // Check if target is in same guild
        if ($this->GetUserGuildId($target_user_id) !== $guild_id) {
            return false;
        }

        // Check if target is already officer or guild master
        $target_role = $this->GetUserRole($target_user_id);
        if ($target_role !== 'member') {
            return false;
        }

        $query = "UPDATE guild_members SET role = 'officer'
                  WHERE guild_id = :guild_id AND user_id = :user_id";

        return $this->DAL->w($query, [
            ':guild_id' => $guild_id,
            ':user_id' => $target_user_id
        ]);
    }

    /**
     * Demote officer to member
     */
    public function DemoteToMember(int $target_user_id, int $demoter_user_id = 0): bool
    {
        $demoter_user_id = $this->getUserId($demoter_user_id);

        if ($demoter_user_id <= 0) {
            return false;
        }

        // Only guild master can demote
        if (!$this->IsGuildMaster($demoter_user_id)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($demoter_user_id);
        if ($guild_id === null) {
            return false;
        }

        // Check if target is in same guild
        if ($this->GetUserGuildId($target_user_id) !== $guild_id) {
            return false;
        }

        // Check if target is officer
        $target_role = $this->GetUserRole($target_user_id);
        if ($target_role !== 'officer') {
            return false;
        }

        $query = "UPDATE guild_members SET role = 'member'
                  WHERE guild_id = :guild_id AND user_id = :user_id";

        return $this->DAL->w($query, [
            ':guild_id' => $guild_id,
            ':user_id' => $target_user_id
        ]);
    }

    /**
     * Disband guild (guild master only)
     */
    public function DisbandGuild(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        // Only guild master can disband
        if (!$this->IsGuildMaster($user_id)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($user_id);
        if ($guild_id === null) {
            return false;
        }

        // Delete all members
        $delete_members = "DELETE FROM guild_members WHERE guild_id = :guild_id";
        $this->DAL->w($delete_members, [':guild_id' => $guild_id]);

        // Delete all invites
        $delete_invites = "DELETE FROM guild_invites WHERE guild_id = :guild_id";
        $this->DAL->w($delete_invites, [':guild_id' => $guild_id]);

        // Delete guild
        $delete_guild = "DELETE FROM guilds WHERE id = :guild_id";

        return $this->DAL->w($delete_guild, [':guild_id' => $guild_id]);
    }

    /**
     * Get guild bank balances
     *
     * @return array<string, int>
     */
    public function GetBankBalances(int $guild_id): array
    {
        $result = $this->DAL->r(
            "SELECT gold, iron, herbs, gems FROM guild_bank WHERE guild_id = :guild_id",
            [':guild_id' => $guild_id]
        );

        if (!$result || empty($result)) {
            return ['gold' => 0, 'iron' => 0, 'herbs' => 0, 'gems' => 0];
        }

        return [
            'gold'  => (int)$result[0]['gold'],
            'iron'  => (int)$result[0]['iron'],
            'herbs' => (int)$result[0]['herbs'],
            'gems'  => (int)$result[0]['gems'],
        ];
    }

    /**
     * Get the current tax rate for a guild (0–20)
     */
    public function GetTaxRate(int $guild_id): int
    {
        $result = $this->DAL->r(
            "SELECT tax_rate FROM guilds WHERE id = :guild_id",
            [':guild_id' => $guild_id]
        );

        if (!$result || empty($result)) {
            return 0;
        }

        return (int)$result[0]['tax_rate'];
    }

    /**
     * Set the tax rate for the caller's guild (guild master or officer only, 0–20 %)
     */
    public function SetTaxRate(int $rate, int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $role = $this->GetUserRole($user_id);
        if (!in_array($role, ['guild_master', 'officer'], true)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($user_id);
        if ($guild_id === null) {
            return false;
        }

        $rate = max(0, min(20, $rate));

        return $this->DAL->w(
            "UPDATE guilds SET tax_rate = :rate WHERE id = :guild_id",
            [':rate' => $rate, ':guild_id' => $guild_id]
        );
    }

    /**
     * Get member count for a guild
     */
    public function GetMemberCount(int $guild_id): int
    {
        $result = $this->DAL->r(
            "SELECT COUNT(*) AS cnt FROM guild_members WHERE guild_id = :guild_id",
            [':guild_id' => $guild_id]
        );

        if (!$result || empty($result)) {
            return 0;
        }

        return (int)$result[0]['cnt'];
    }

    /**
     * Get all building levels for a guild
     *
     * @return array<string, int>
     */
    public function GetBuildingLevels(int $guild_id): array
    {
        $defaults = ['farm' => 0, 'iron_mine' => 0, 'gem_mine' => 0, 'market' => 0, 'gym' => 0, 'tavern' => 0];

        $result = $this->DAL->r(
            "SELECT farm, iron_mine, gem_mine, market, gym, tavern
             FROM guild_buildings WHERE guild_id = :guild_id",
            [':guild_id' => $guild_id]
        );

        if (!$result || empty($result)) {
            return $defaults;
        }

        return [
            'farm'      => (int)$result[0]['farm'],
            'iron_mine' => (int)$result[0]['iron_mine'],
            'gem_mine'  => (int)$result[0]['gem_mine'],
            'market'    => (int)$result[0]['market'],
            'gym'       => (int)$result[0]['gym'],
            'tavern'    => (int)$result[0]['tavern'],
        ];
    }

    /**
     * Upgrade a guild building, paying the cost from the guild bank in the specified resource.
     * Only guild_master and officers may upgrade.
     * Cost = 10000 * member_count * (current_level + 1)
     */
    public function UpgradeBuilding(string $building, string $resource, int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        $valid_buildings = ['farm', 'iron_mine', 'gem_mine', 'market', 'gym', 'tavern'];
        $valid_resources = ['gold', 'iron', 'herbs', 'gems'];
        if (!in_array($building, $valid_buildings, true) || !in_array($resource, $valid_resources, true)) {
            return false;
        }

        $role = $this->GetUserRole($user_id);
        if (!in_array($role, ['guild_master', 'officer'], true)) {
            return false;
        }

        $guild_id = $this->GetUserGuildId($user_id);
        if ($guild_id === null) {
            return false;
        }

        $levels       = $this->GetBuildingLevels($guild_id);
        $current      = $levels[$building];
        $member_count = $this->GetMemberCount($guild_id);
        $cost         = 10000 * $member_count * ($current + 1);

        // Check guild bank has enough of the chosen resource
        $bank = $this->GetBankBalances($guild_id);
        if ($bank[$resource] < $cost) {
            return false;
        }

        // Deduct from guild bank
        $deducted = $this->DAL->w(
            "UPDATE guild_bank SET `{$resource}` = `{$resource}` - :cost
             WHERE guild_id = :guild_id AND `{$resource}` >= :cost2",
            [':cost' => $cost, ':guild_id' => $guild_id, ':cost2' => $cost]
        );

        if (!$deducted || $this->DAL->rows_affected() === 0) {
            return false;
        }

        // Increment building level (upsert in case row was missing)
        return $this->DAL->w(
            "INSERT INTO guild_buildings (guild_id, `{$building}`) VALUES (:guild_id, 1)
             ON DUPLICATE KEY UPDATE `{$building}` = `{$building}` + 1",
            [':guild_id' => $guild_id]
        );
    }

    /**
     * Calculate the cost for the next upgrade of a building
     */
    public function GetUpgradeCost(string $building, int $guild_id): int
    {
        $levels       = $this->GetBuildingLevels($guild_id);
        $current      = $levels[$building] ?? 0;
        $member_count = $this->GetMemberCount($guild_id);

        return 10000 * $member_count * ($current + 1);
    }

    /**
     * Search for users by character name (for inviting)
     *
     * @return array<int, array<string, mixed>>
     */
    public function SearchUsers(string $search_term): array
    {
        $search_term = trim($search_term);
        if (strlen($search_term) < 2) {
            return [];
        }

        $query = "SELECT c.user_id, c.name as character_name, c.level
                  FROM characters c
                  LEFT JOIN guild_members gm ON gm.user_id = c.user_id
                  LEFT JOIN guilds g ON g.id = gm.guild_id AND g.season_id <=> :season_id
                  WHERE c.name LIKE :search_term
                  AND c.season_id <=> :season_id2
                  AND g.id IS NULL
                  LIMIT 20";

        $result = $this->DAL->r($query, [
            ':search_term' => '%' . $search_term . '%',
            ':season_id'   => $this->seasonId,
            ':season_id2'  => $this->seasonId,
        ]);

        return $result ?: [];
    }
}
