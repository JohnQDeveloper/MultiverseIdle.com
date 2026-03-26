<?php

$gear = new Gear();
$party_classes = ['strength', 'dexterity', 'health', 'wisdom'];

// Handle frontline class updates
if (isset($_GET['update']) && $_GET['update'] == 'frontline_class') {
    $selected_class = $_POST['class'] ?? '';
    if (in_array($selected_class, $party_classes, true)) {
        $Character->Data['party_json']['members']['frontline']['class'] = $selected_class;
    }
}

// Handle backline class updates
if (isset($_GET['update']) && $_GET['update'] == 'backline_class') {
    $selected_class = $_POST['class'] ?? '';
    if (in_array($selected_class, $party_classes, true)) {
        $Character->Data['party_json']['members']['backline']['class'] = $selected_class;
    }
}

// Handle frontline skill updates
if(isset($_GET['update']) && $_GET['update'] == 'frontline_skills') {
    if(isset($_POST['skill_gem_1']) && isset($_POST['skill_gem_2'])) {
        $Character->Data['party_json']['members']['frontline']['skills'][0] = $_POST['skill_gem_1'];
        $Character->Data['party_json']['members']['frontline']['skills'][1] = $_POST['skill_gem_2'];
    }
}

// Handle backline skill updates
if(isset($_GET['update']) && $_GET['update'] == 'backline_skills') {
    if(isset($_POST['skill_gem_1']) && isset($_POST['skill_gem_2'])) {
        $Character->Data['party_json']['members']['backline']['skills'][0] = $_POST['skill_gem_1'];
        $Character->Data['party_json']['members']['backline']['skills'][1] = $_POST['skill_gem_2'];
    }
}

// Handle frontline gear updates
if(isset($_GET['update']) && $_GET['update'] == 'frontline_gear') {
    $weapon_id = intval($_POST['weapon_slot'] ?? 0);
    $armor_id = intval($_POST['armor_slot'] ?? 0);
    $owner_id = $_SESSION['auth_user_id'];
    $party_level = (int)($Character->Data['party_json']['members']['frontline']['level'] ?? 1);

    // Get backline equipped items to check for duplicates
    $backline_weapon = $Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0;
    $backline_armor = $Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0;

    // Verify ownership before equipping
    if ($weapon_id > 0 && !$gear->VerifyOwnership($weapon_id, $owner_id)) {
        $alert_danger = t('party.alert.no_weapon');
    } elseif ($armor_id > 0 && !$gear->VerifyOwnership($armor_id, $owner_id)) {
        $alert_danger = t('party.alert.no_armor');
    } elseif ($weapon_id > 0 && $gear->GetItemLevelByID($weapon_id) > $party_level) {
        $alert_danger = t('party.alert.level_too_low_wpn');
    } elseif ($armor_id > 0 && $gear->GetItemLevelByID($armor_id) > $party_level) {
        $alert_danger = t('party.alert.level_too_low_arm');
    } elseif ($weapon_id > 0 && $weapon_id == $backline_weapon) {
        $alert_danger = t('party.alert.wpn_backline');
    } elseif ($armor_id > 0 && $armor_id == $backline_armor) {
        $alert_danger = t('party.alert.arm_backline');
    } else {
        $Character->Data['party_json']['members']['frontline']['equipped_weapon'] = $weapon_id;
        $Character->Data['party_json']['members']['frontline']['equipped_armor'] = $armor_id;
        $alert_success = t('party.alert.frontline_updated');
    }
}

// Handle backline gear updates
if(isset($_GET['update']) && $_GET['update'] == 'backline_gear') {
    $weapon_id = intval($_POST['weapon_slot'] ?? 0);
    $armor_id = intval($_POST['armor_slot'] ?? 0);
    $owner_id = $_SESSION['auth_user_id'];
    $party_level = (int)($Character->Data['party_json']['members']['frontline']['level'] ?? 1);

    // Get frontline equipped items to check for duplicates
    $frontline_weapon = $Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0;
    $frontline_armor = $Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0;

    // Verify ownership before equipping
    if ($weapon_id > 0 && !$gear->VerifyOwnership($weapon_id, $owner_id)) {
        $alert_danger = t('party.alert.no_weapon');
    } elseif ($armor_id > 0 && !$gear->VerifyOwnership($armor_id, $owner_id)) {
        $alert_danger = t('party.alert.no_armor');
    } elseif ($weapon_id > 0 && $gear->GetItemLevelByID($weapon_id) > $party_level) {
        $alert_danger = t('party.alert.level_too_low_wpn');
    } elseif ($armor_id > 0 && $gear->GetItemLevelByID($armor_id) > $party_level) {
        $alert_danger = t('party.alert.level_too_low_arm');
    } elseif ($weapon_id > 0 && $weapon_id == $frontline_weapon) {
        $alert_danger = t('party.alert.wpn_frontline');
    } elseif ($armor_id > 0 && $armor_id == $frontline_armor) {
        $alert_danger = t('party.alert.arm_frontline');
    } else {
        $Character->Data['party_json']['members']['backline']['equipped_weapon'] = $weapon_id;
        $Character->Data['party_json']['members']['backline']['equipped_armor'] = $armor_id;
        $alert_success = t('party.alert.backline_updated');
    }
}

// Load favorite gear for dropdowns
$favorite_weapons = $gear->GetFavoriteItemsByOwnerAndSlot($_SESSION['auth_user_id'], 'weapon');
$favorite_armors = $gear->GetFavoriteItemsByOwnerAndSlot($_SESSION['auth_user_id'], 'armor');
