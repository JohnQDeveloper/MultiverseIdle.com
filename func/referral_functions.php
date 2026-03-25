<?php

declare(strict_types=1);

/**
 * Retrieve the referral code for a user, generating one if it doesn't exist yet.
 */
function get_or_create_referral_code(int $user_id): string
{
    global $DAL;

    $result = $DAL->r(
        "SELECT `code` FROM `referral_codes` WHERE `user_id` = :uid",
        [':uid' => $user_id]
    );

    if (!empty($result)) {
        return $result[0]['code'];
    }

    // Generate a new unique 8-character uppercase hex code
    do {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $exists = $DAL->r("SELECT `user_id` FROM `referral_codes` WHERE `code` = :code", [':code' => $code]);
    } while (!empty($exists));

    $DAL->w(
        "INSERT INTO `referral_codes` (`user_id`, `code`) VALUES (:uid, :code)",
        [':uid' => $user_id, ':code' => $code]
    );

    return $code;
}

/**
 * Count the number of successful referrals for a user.
 */
function count_referrals(int $user_id): int
{
    global $DAL;

    $result = $DAL->r(
        "SELECT COUNT(*) AS `count` FROM `referral_uses` WHERE `referrer_user_id` = :uid",
        [':uid' => $user_id]
    );

    return (int)($result[0]['count'] ?? 0);
}

/**
 * Process a referral during registration.
 *
 * Awards the new user 100,000 gold and the referrer 7 days worth of gold production.
 * The new user's character is created if it doesn't already exist (guest migration
 * will have created it beforehand if applicable).
 *
 * @param int    $new_user_id  The newly registered user's ID.
 * @param string $username     The new user's username (used only if creating the character).
 * @param string $code         The referral code entered at registration.
 */
function process_referral(int $new_user_id, string $username, string $code): bool
{
    global $DAL;

    $code = strtoupper(trim($code));
    if ($code === '') {
        return false;
    }

    // Look up the referrer
    $referrer_row = $DAL->r(
        "SELECT `user_id` FROM `referral_codes` WHERE `code` = :code",
        [':code' => $code]
    );
    if (empty($referrer_row)) {
        return false;
    }
    $referrer_user_id = (int)$referrer_row[0]['user_id'];

    // Prevent self-referral
    if ($referrer_user_id === $new_user_id) {
        return false;
    }

    // Prevent double-use (each new user can only be referred once)
    $already_used = $DAL->r(
        "SELECT `id` FROM `referral_uses` WHERE `referred_user_id` = :rid",
        [':rid' => $new_user_id]
    );
    if (!empty($already_used)) {
        return false;
    }

    // Load or create the new user's character
    $NewChar = new Character();
    if ($NewChar->CharacterExists($new_user_id)) {
        $NewChar->LoadByUserId($new_user_id);
    } else {
        $NewChar->CreateCharacter($new_user_id, $username);
    }

    if (empty($NewChar->Data['id'])) {
        return false;
    }

    // Award new user 100,000 gold
    $NewChar->Data['gold'] += 100000;
    $NewChar->SaveByUserId($new_user_id);

    // Load the referrer's character and calculate 7 days of gold production
    $ReferrerChar = new Character();
    if (!$ReferrerChar->LoadByUserId($referrer_user_id)) {
        return false;
    }

    $gold_skill     = (int)($ReferrerChar->Data['worker_json']['skills']['gold'] ?? 1);
    $speed_upgrades = (int)($ReferrerChar->Data['worker_json']['speed_upgrades'] ?? 0);
    $num_workers    = (int)($ReferrerChar->Data['worker_json']['workers'] ?? 1);
    $tick_yield     = (int)worker_yield(10, $speed_upgrades, $gold_skill, $num_workers, 0);
    $seven_day_gold = $tick_yield * 7 * 24 * 60; // 10,080 ticks (1 tick = 1 minute)

    $ReferrerChar->Data['gold'] += $seven_day_gold;
    $ReferrerChar->SaveByUserId($referrer_user_id);

    // Record the referral
    $DAL->w(
        "INSERT INTO `referral_uses` (`referrer_user_id`, `referred_user_id`) VALUES (:ref, :new)",
        [':ref' => $referrer_user_id, ':new' => $new_user_id]
    );

    return true;
}
