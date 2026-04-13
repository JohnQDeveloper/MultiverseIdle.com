<?php

declare(strict_types=1);

    $lucky_wyrdstone = (int)($Character->Data['inventory_json']['special_resources']['lucky_wyrdstone'] ?? 0);

    # Skill gem bonus type definitions
    $skill_gem_bonus_definitions = SkillGem::getBonusTypeDefinitions();
    $valid_skill_gem_bonus_types  = array_keys($skill_gem_bonus_definitions);

    # Gear affix and item type definitions (from Gear class)
    $affix_definitions = Gear::getAffixDefinitions();
    $item_type_definitions = Gear::getItemTypeDefinitions();

    # Valid affix keys for validation
    $valid_affixes = array_keys($affix_definitions);
    $valid_item_types = array_keys($item_type_definitions);

    # Potion affix definitions (from Potion class)
    $potion_prefix_definitions = Potion::getPrefixDefinitions();
    $potion_suffix_definitions = Potion::getSuffixDefinitions();

    $valid_potion_prefixes = array_keys($potion_prefix_definitions);
    $valid_potion_suffixes = array_keys($potion_suffix_definitions);

    # Rift stone definitions (from RiftStone class)
    $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
    $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();

    $valid_rift_stone_implicits = array_keys($rift_stone_implicit_definitions);
    $valid_rift_stone_affixes = array_keys($rift_stone_affix_definitions);

    # Craft Item
    if (isset($_POST['craft_item'])) {
        $item_type = $_POST['item_type'] ?? '';
        $affix_1 = $_POST['affix_1'] ?? '';
        $affix_2 = $_POST['affix_2'] ?? '';

        # Validate inputs
        if (!in_array($item_type, $valid_item_types)) {
            $alert_danger = t('craft.alert.invalid_type');
        } elseif (!in_array($affix_1, $valid_affixes)) {
            $alert_danger = t('craft.alert.invalid_affix1');
        } elseif (!in_array($affix_2, $valid_affixes)) {
            $alert_danger = t('craft.alert.invalid_affix2');
        } else {
            # Calculate potential based on party level
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $potential = floor($party_level * 1);

            $use_lucky_wyrdstone = isset($_POST['use_lucky_wyrdstone']);
            if ($use_lucky_wyrdstone && $lucky_wyrdstone <= 0) {
                $alert_danger = t('craft.alert.no_lucky_wyrdstone');
            } else {
                # Initialize affix levels
                $affix_1_level = 0;
                $affix_2_level = 0;
                $current_affix = 1; # Start with first affix

                # Consume potential by alternating between affixes
                while ($potential > 0) {
                    if ($Character->Data['iron'] < 2500) {
                        break;
                    }

                    # Lucky wyrdstone rolls twice and keeps the more favorable potential cost.
                    $consumed = rand(1, 5);
                    if ($use_lucky_wyrdstone) {
                        $consumed = min($consumed, rand(1, 5));
                    }
                    $consumed = min($consumed, $potential); # Don't consume more than available

                    if ($current_affix === 1) {
                        $affix_1_level++;
                        $current_affix = 2;
                    } else {
                        $affix_2_level++;
                        $current_affix = 1;
                    }

                    $potential -= $consumed;
                    $Character->Data['iron'] -= 2500; # Deduct iron cost per upgrade
                }

                if ($use_lucky_wyrdstone) {
                    $Character->Data['inventory_json']['special_resources']['lucky_wyrdstone'] = $lucky_wyrdstone - 1;
                    $lucky_wyrdstone--;
                }

                # Calculate final affix values
                $affix_1_value = $affix_1_level * $affix_definitions[$affix_1]['per_level'];
                $affix_2_value = $affix_2_level * $affix_definitions[$affix_2]['per_level'];

                # Generate random item name: PREFIX MATERIAL ITEM_TYPE SUFFIX
                $random_prefix = GEARNAMES_PREFIX[array_rand(GEARNAMES_PREFIX)];
                $random_material = GEARNAMES_MATERIAL[array_rand(GEARNAMES_MATERIAL)];
                $random_suffix = GEARNAMES_SUFFIX[array_rand(GEARNAMES_SUFFIX)];
                $item_name = $random_prefix . ' ' . $random_material . ' ' . t('gear.item.' . $item_type) . ' ' . $random_suffix;

                # Create the item array
                $crafted_item = [
                    'type' => $item_type,
                    'name' => $item_name,
                    'slot' => $item_type_definitions[$item_type]['slot'],
                    'base_bonuses' => $item_type_definitions[$item_type]['bonuses'],
                    'affixes' => [
                        [
                            'key' => $affix_1,
                            'name' => t('gear.affix.' . $affix_1),
                            'level' => $affix_1_level,
                            'value' => $affix_1_value,
                            'type' => $affix_definitions[$affix_1]['type'],
                        ],
                        [
                            'key' => $affix_2,
                            'name' => t('gear.affix.' . $affix_2),
                            'level' => $affix_2_level,
                            'value' => $affix_2_value,
                            'type' => $affix_definitions[$affix_2]['type'],
                        ],
                    ],
                    'party_level_at_craft' => $party_level,
                ];

                # Format success message
                $affix_1_suffix = $affix_definitions[$affix_1]['type'] === 'percent' ? '%' : '';
                $affix_2_suffix = $affix_definitions[$affix_2]['type'] === 'percent' ? '%' : '';

                $affix_1_text = '+' . $affix_1_value . $affix_1_suffix . ' ' . htmlspecialchars(t('gear.affix.' . $affix_1)) .
                    ' ' . t('craft.level_short', ['level' => $affix_1_level]);
                $affix_2_text = '+' . $affix_2_value . $affix_2_suffix . ' ' . htmlspecialchars(t('gear.affix.' . $affix_2)) .
                    ' ' . t('craft.level_short', ['level' => $affix_2_level]);

                $alert_success = t('craft.alert.crafted_gear', ['name' => htmlspecialchars($crafted_item['name']), 'affixes' =>
                    $affix_1_text . ' ' . t('craft.and') . ' ' . $affix_2_text]);

                if ($use_lucky_wyrdstone) {
                    $alert_success .= ' ' . t('craft.alert.used_lucky_wyrdstone');
                }

                # Save the crafted item
                $gear = new Gear();
                $new_gear_id = $gear->CreateItem($crafted_item['name'], $crafted_item);
            }
        }
    }

    # Craft Potion
    if (isset($_POST['craft_potion'])) {
        $prefix_affix = $_POST['prefix_affix'] ?? '';
        $suffix_affix = $_POST['suffix_affix'] ?? '';

        # Validate inputs
        if (!in_array($prefix_affix, $valid_potion_prefixes)) {
            $alert_danger = t('craft.alert.invalid_prefix');
        } elseif (!in_array($suffix_affix, $valid_potion_suffixes)) {
            $alert_danger = t('craft.alert.invalid_suffix');
        } else {
            # Get party level - this is the potion level
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $potion_level = $party_level;
            $herb_cost = $potion_level * 1500;

            if ($Character->Data['herbs'] < $herb_cost) {
                $alert_danger = t('craft.alert.no_herbs', ['cost' => number_format($herb_cost)]);
            } else {
                # Calculate affix values based on level
                $prefix_value = $potion_level * $potion_prefix_definitions[$prefix_affix]['per_level'];
                $suffix_value = $potion_level * $potion_suffix_definitions[$suffix_affix]['per_level'];

                # Generate potion name
                $prefix_name = t('potion.affix.' . $prefix_affix);
                $suffix_name = t('potion.affix.' . $suffix_affix);
                $potion_name = t('craft.potion.generated_name', [
                    'prefix' => $prefix_name,
                    'suffix' => $suffix_name,
                ]);

                # Deduct crafting cost
                $Character->Data['herbs'] -= $herb_cost;

                # Format success message
                $alert_success = t('craft.alert.crafted_potion', [
                    'level' => $potion_level,
                    'name' => htmlspecialchars($potion_name),
                    'prefix' => '+' . $prefix_value . '% ' . htmlspecialchars($prefix_name),
                    'suffix' => '+' . $suffix_value . '% ' . htmlspecialchars($suffix_name),
                ]);

                # Save the crafted potion
                $potion = new Potion();
                $new_potion_id = $potion->CreatePotion($potion_name, $prefix_affix, $suffix_affix, $potion_level);
            }
        }
    }

    # Craft Rift Stone
    if (isset($_POST['craft_rift_stone'])) {
        $implicit = $_POST['implicit'] ?? '';
        $rift_level = intval($_POST['rift_level'] ?? 0);

        # Get daily highest floor completed
        $owner_id = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
        if ($owner_id > 0) {
            $daily_floor_key = 'daily_floor:' . $owner_id . ':' . date('Y-m-d');
            $max_rift_level = (int)($redis->get($daily_floor_key) ?? 0);
        } else {
            # Guest: use session tracking
            $guest_daily_date = $_SESSION['guest_daily_floor_date'] ?? '';
            $max_rift_level = ($guest_daily_date === date('Y-m-d'))
                ? (int)($_SESSION['guest_daily_floor_value'] ?? 0)
                : 0;
        }
        $min_rift_level = (int)floor($max_rift_level * 0.8);

        # Validate inputs
        if (!in_array($implicit, $valid_rift_stone_implicits)) {
            $alert_danger = t('craft.alert.invalid_implicit');
        } elseif ($max_rift_level <= 0) {
            $alert_danger = t('craft.alert.no_arena_floor');
        } elseif ($rift_level < $min_rift_level || $rift_level > $max_rift_level) {
            $alert_danger = t('craft.alert.invalid_rift_lvl', ['min' => $min_rift_level, 'max' => $max_rift_level]);
        } else {
            $crafting_cost = $max_rift_level * 15;

            if ($Character->Data['gems'] < $crafting_cost) {
                $alert_danger = t('craft.alert.no_gems', ['cost' => number_format($crafting_cost)]);
            } else {
                # Roll 3 random affixes (can repeat)
                $affixes = [];
                for ($i = 0; $i < 3; $i++) {
                    $affixes[] = array_rand($rift_stone_affix_definitions);
                }

                # Generate rift stone name
                $implicit_name = t('riftstone.implicit.' . $implicit . '.name');
                $rift_stone_name = t('craft.rift.generated_name', [
                    'level' => $rift_level,
                    'implicit' => $implicit_name,
                ]);

                # Deduct crafting cost (gems based on daily highest floor)
                $Character->Data['gems'] -= $crafting_cost;

                # Create the rift stone details array
                $rift_stone_details = [
                    'name' => $rift_stone_name,
                    'implicit' => $implicit,
                    'affixes' => $affixes,
                    'level' => $rift_level,
                    'daily_floor_at_craft' => $max_rift_level,
                ];

                # Format success message with affixes
                $affix_names = [];
                foreach ($affixes as $affix_key) {
                    $affix_names[] = t('riftstone.affix.' . $affix_key . '.name');
                }

                $alert_success = t('craft.alert.crafted_rift', [
                    'name' => htmlspecialchars($rift_stone_name),
                    'affixes' => htmlspecialchars(implode(', ', $affix_names)),
                ]);

                # Save the crafted rift stone
                $rift_stone = new RiftStone();
                $new_rift_stone_id = $rift_stone->CreateRiftStone($rift_stone_details);
            }
        }
    }

    # Craft Skill Gem
    if (isset($_POST['craft_skill_gem'])) {
        $skill_name = $_POST['skill_name'] ?? '';
        $bonus_type = $_POST['bonus_type'] ?? '';

        # Validate inputs
        if (!in_array($skill_name, SKILL_GEM_NAMES, true)) {
            $alert_danger = t('craft.alert.invalid_skill');
        } elseif (!in_array($bonus_type, $valid_skill_gem_bonus_types, true)) {
            $alert_danger = t('craft.alert.invalid_bonus_type');
        } else {
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $potential = floor($party_level * 1);

            $use_lucky_wyrdstone = isset($_POST['use_lucky_wyrdstone']);
            if ($use_lucky_wyrdstone && $lucky_wyrdstone <= 0) {
                $alert_danger = t('craft.alert.no_lucky_wyrdstone');
            } else {
                $tier = 0;

                # Consume potential, each iteration increases tier by 1
                while ($potential > 0) {
                    if ($Character->Data['iron'] < 2500) {
                        break;
                    }

                    $consumed = rand(1, 5);
                    if ($use_lucky_wyrdstone) {
                        $consumed = min($consumed, rand(1, 5));
                    }
                    $consumed = min($consumed, $potential);

                    $tier++;
                    $potential -= $consumed;
                    $Character->Data['iron'] -= 2500;
                }

                if ($use_lucky_wyrdstone) {
                    $Character->Data['inventory_json']['special_resources']['lucky_wyrdstone'] = $lucky_wyrdstone - 1;
                    $lucky_wyrdstone--;
                }

                # Generate gem name: "[prefix] [skill] Gem"
                $bonus_prefix = $skill_gem_bonus_definitions[$bonus_type]['gem_prefix'];
                $gem_name = $bonus_prefix . ' ' . $skill_name . ' Gem';

                # Build details
                $gem_details = [
                    'skill_name'          => $skill_name,
                    'bonus_type'          => $bonus_type,
                    'tier'                => $tier,
                    'party_level_at_craft' => $party_level,
                ];

                # Calculate bonus value for display
                $per_tier   = $skill_gem_bonus_definitions[$bonus_type]['per_tier'];
                $bonus_unit = $skill_gem_bonus_definitions[$bonus_type]['unit'];
                $bonus_value = $tier * $per_tier;
                $bonus_name  = $skill_gem_bonus_definitions[$bonus_type]['name'];

                $alert_success = t('craft.alert.crafted_skill_gem', [
                    'name'  => htmlspecialchars($gem_name),
                    'tier'  => $tier,
                    'bonus' => '+' . $bonus_value . $bonus_unit . ' ' . htmlspecialchars($bonus_name),
                ]);

                if ($use_lucky_wyrdstone) {
                    $alert_success .= ' ' . t('craft.alert.used_lucky_wyrdstone');
                }

                # Save the crafted gem
                $skill_gem = new SkillGem();
                $skill_gem->CreateItem($gem_name, $gem_details);
            }
        }
    }
