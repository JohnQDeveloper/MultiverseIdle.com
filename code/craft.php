<?php

    # Affix definitions with their scaling per level
    $affix_definitions = [
        'strength' => ['name' => 'Strength', 'per_level' => 20, 'type' => 'flat'],
        'health' => ['name' => 'Health', 'per_level' => 20, 'type' => 'flat'],
        'dexterity' => ['name' => 'Dexterity', 'per_level' => 20, 'type' => 'flat'],
        'wisdom' => ['name' => 'Wisdom', 'per_level' => 20, 'type' => 'flat'],
        'cold_damage' => ['name' => 'Increased Cold Damage', 'per_level' => 2, 'type' => 'percent'],
        'fire_damage' => ['name' => 'Increased Fire Damage', 'per_level' => 2, 'type' => 'percent'],
        'physical_damage' => ['name' => 'Increased Physical Damage', 'per_level' => 1, 'type' => 'percent'],
        'physical_resistance' => ['name' => 'Physical Resistance', 'per_level' => 1, 'type' => 'percent'],
        'cold_resistance' => ['name' => 'Cold Resistance', 'per_level' => 3, 'type' => 'percent'],
        'fire_resistance' => ['name' => 'Fire Resistance', 'per_level' => 3, 'type' => 'percent'],
    ];

    # Item type definitions with their base bonuses
    $item_type_definitions = [
        'weapon' => ['name' => 'Weapon', 'slot' => 'weapon', 'bonuses' => ['strength' => 15, 'health' => 15]],
        'wand' => ['name' => 'Wand', 'slot' => 'weapon', 'bonuses' => ['wisdom' => 30]],
        'plate' => ['name' => 'Plate', 'slot' => 'armor', 'bonuses' => ['health' => 15, 'resistances' => 15]],
        'robe' => ['name' => 'Robe', 'slot' => 'armor', 'bonuses' => ['dexterity' => 15, 'wisdom' => 15]],
    ];

    # Valid affix keys for validation
    $valid_affixes = array_keys($affix_definitions);
    $valid_item_types = array_keys($item_type_definitions);

    # Potion affix definitions (from Potion class)
    $potion_prefix_definitions = Potion::getPrefixDefinitions();
    $potion_suffix_definitions = Potion::getSuffixDefinitions();

    $valid_potion_prefixes = array_keys($potion_prefix_definitions);
    $valid_potion_suffixes = array_keys($potion_suffix_definitions);

    # Craft Item
    if (isset($_POST['craft_item'])) {
        $item_type = $_POST['item_type'] ?? '';
        $affix_1 = $_POST['affix_1'] ?? '';
        $affix_2 = $_POST['affix_2'] ?? '';

        # Validate inputs
        if (!in_array($item_type, $valid_item_types)) {
            $alert_danger = 'Invalid item type selected.';
        } elseif (!in_array($affix_1, $valid_affixes)) {
            $alert_danger = 'Invalid first affix selected.';
        } elseif (!in_array($affix_2, $valid_affixes)) {
            $alert_danger = 'Invalid second affix selected.';
        } else {
            # Calculate potential based on party level
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $potential = floor($party_level * 1);

            # Initialize affix levels
            $affix_1_level = 0;
            $affix_2_level = 0;
            $current_affix = 1; # Start with first affix

            # Consume potential by alternating between affixes
            while ($potential > 0) {
                # Random potential consumed per upgrade (1-5)
                $consumed = rand(1, 5);
                $consumed = min($consumed, $potential); # Don't consume more than available

                if ($current_affix === 1) {
                    $affix_1_level++;
                    $current_affix = 2;
                } else {
                    $affix_2_level++;
                    $current_affix = 1;
                }

                $potential -= $consumed;
                $Character->Data['iron'] -= 100; # Deduct iron cost per upgrade
            }

            # Calculate final affix values
            $affix_1_value = $affix_1_level * $affix_definitions[$affix_1]['per_level'];
            $affix_2_value = $affix_2_level * $affix_definitions[$affix_2]['per_level'];

            # Generate random item name: PREFIX MATERIAL ITEM_TYPE SUFFIX
            $random_prefix = GEARNAMES_PREFIX[array_rand(GEARNAMES_PREFIX)];
            $random_material = GEARNAMES_MATERIAL[array_rand(GEARNAMES_MATERIAL)];
            $random_suffix = GEARNAMES_SUFFIX[array_rand(GEARNAMES_SUFFIX)];
            $item_name = $random_prefix . ' ' . $random_material . ' ' . $item_type_definitions[$item_type]['name'] . ' ' . $random_suffix;

            # Create the item array
            $crafted_item = [
                'type' => $item_type,
                'name' => $item_name,
                'slot' => $item_type_definitions[$item_type]['slot'],
                'base_bonuses' => $item_type_definitions[$item_type]['bonuses'],
                'affixes' => [
                    [
                        'key' => $affix_1,
                        'name' => $affix_definitions[$affix_1]['name'],
                        'level' => $affix_1_level,
                        'value' => $affix_1_value,
                        'type' => $affix_definitions[$affix_1]['type'],
                    ],
                    [
                        'key' => $affix_2,
                        'name' => $affix_definitions[$affix_2]['name'],
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

            $alert_success = 'Crafted ' . htmlspecialchars($crafted_item['name']) . ' with ' .
                '+' . $affix_1_value . $affix_1_suffix . ' ' . htmlspecialchars($affix_definitions[$affix_1]['name']) .
                ' (Lv.' . $affix_1_level . ') and ' .
                '+' . $affix_2_value . $affix_2_suffix . ' ' . htmlspecialchars($affix_definitions[$affix_2]['name']) .
                ' (Lv.' . $affix_2_level . ')';

            # Save the crafted item
            $gear = new Gear();
            $new_gear_id = $gear->CreateItem($crafted_item['name'], $crafted_item);
        }
    }

    # Craft Potion
    if (isset($_POST['craft_potion'])) {
        $prefix_affix = $_POST['prefix_affix'] ?? '';
        $suffix_affix = $_POST['suffix_affix'] ?? '';

        # Validate inputs
        if (!in_array($prefix_affix, $valid_potion_prefixes)) {
            $alert_danger = 'Invalid prefix affix selected.';
        } elseif (!in_array($suffix_affix, $valid_potion_suffixes)) {
            $alert_danger = 'Invalid suffix affix selected.';
        } else {
            # Get party level - this is the potion level
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $potion_level = $party_level;

            # Calculate affix values based on level
            $prefix_value = $potion_level * $potion_prefix_definitions[$prefix_affix]['per_level'];
            $suffix_value = $potion_level * $potion_suffix_definitions[$suffix_affix]['per_level'];

            # Generate potion name
            $potion_name = $potion_prefix_definitions[$prefix_affix]['name'] . ' and ' . $potion_suffix_definitions[$suffix_affix]['name'] . ' Potion';

            # Deduct crafting cost
            $Character->Data['herbs'] -= ($potion_level * 100);

            # Format success message
            $alert_success = 'Crafted Level ' . $potion_level . ' ' . htmlspecialchars($potion_name) . ' with ' .
                '+' . $prefix_value . '% ' . htmlspecialchars($potion_prefix_definitions[$prefix_affix]['name']) .
                ' and +' . $suffix_value . '% ' . htmlspecialchars($potion_suffix_definitions[$suffix_affix]['name']);

            # Save the crafted potion
            $potion = new Potion();
            $new_potion_id = $potion->CreatePotion($potion_name, $prefix_affix, $suffix_affix, $potion_level);
        }
    }
