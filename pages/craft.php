<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Craft</h1>
        <p>Craft powerful gear and potions to enhance your party's abilities.</p>

        <?php
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $active_tab = $_GET['tab'] ?? 'gear';
        ?>

        <!-- Tab Navigation -->
        <div style="margin-bottom: 20px; border-bottom: 2px solid #ccc;">
            <a href="/craft?tab=gear" style="display: inline-block; padding: 10px 20px; margin-right: 5px; text-decoration: none; <?php echo $active_tab === 'gear' ? 'border-bottom: 3px solid #007bff; font-weight: bold;' : ''; ?>">Gear Crafting</a>
            <a href="/craft?tab=potions" style="display: inline-block; padding: 10px 20px; text-decoration: none; <?php echo $active_tab === 'potions' ? 'border-bottom: 3px solid #007bff; font-weight: bold;' : ''; ?>">Potion Crafting</a>
        </div>

        <?php if ($active_tab === 'gear'): ?>
        <!-- Gear Crafting Tab -->
        <h2>Gear Crafting</h2>
        <p>Craft powerful gear to enhance your party's abilities. Each item type provides different stat bonuses.</p>

        <form method="POST" action="/craft?tab=gear">
            <b>Select Item to Craft:</b><br />
            <select name="item_type">
                <optgroup label="Weapons">
                    <option value="weapon">Weapon (+15% Strength, +15% Health)</option>
                    <option value="wand">Wand (+30% Wisdom)</option>
                </optgroup>
                <optgroup label="Armor">
                    <option value="plate">Plate (+15% Health, +15% Resistances)</option>
                    <option value="robe">Robe (+15% Dexterity, +15% Wisdom)</option>
                </optgroup>
            </select>
            <br /><br />

            <b>Select First Affix:</b><br />
            <select name="affix_1">
                <optgroup label="Stats">
                    <option value="strength">Strength (+20 per gear level)</option>
                    <option value="health">Health (+20 per gear level)</option>
                    <option value="dexterity">Dexterity (+20 per gear level)</option>
                    <option value="wisdom">Wisdom (+20 per gear level)</option>
                </optgroup>
                <optgroup label="Damage">
                    <option value="cold_damage">Increased Cold Damage (+2% per level)</option>
                    <option value="fire_damage">Increased Fire Damage (+2% per level)</option>
                    <option value="physical_damage">Increased Physical Damage (+1% per level)</option>
                </optgroup>
                <optgroup label="Resistances">
                    <option value="physical_resistance">Physical Resistance (+1% per level)</option>
                    <option value="cold_resistance">Cold Resistance (+3% per level)</option>
                    <option value="fire_resistance">Fire Resistance (+3% per level)</option>
                </optgroup>
            </select>
            <br /><br />

            <b>Select Second Affix:</b><br />
            <select name="affix_2">
                <optgroup label="Stats">
                    <option value="strength">Strength (+20 per gear level)</option>
                    <option value="health">Health (+20 per gear level)</option>
                    <option value="dexterity">Dexterity (+20 per gear level)</option>
                    <option value="wisdom">Wisdom (+20 per gear level)</option>
                </optgroup>
                <optgroup label="Damage">
                    <option value="cold_damage">Increased Cold Damage (+2% per level)</option>
                    <option value="fire_damage">Increased Fire Damage (+2% per level)</option>
                    <option value="physical_damage">Increased Physical Damage (+1% per level)</option>
                </optgroup>
                <optgroup label="Resistances">
                    <option value="physical_resistance">Physical Resistance (+1% per level)</option>
                    <option value="cold_resistance">Cold Resistance (+3% per level)</option>
                    <option value="fire_resistance">Fire Resistance (+3% per level)</option>
                </optgroup>
            </select>
            <br /><br />

            <p>
                <b>Potential:</b> <?php echo $party_level; ?> (100% of Party Level <?php echo $party_level; ?>)<br />
                <small>Each affix level consumes 1-5 potential and 100 Iron. Upgrades alternate between affixes until potential is exhausted.</small>
            </p>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_item" value="Craft Item">
        </form>

        <?php elseif ($active_tab === 'potions'): ?>
        <!-- Potion Crafting Tab -->
        <h2>Potion Crafting</h2>
        <p>Brew potions to boost your resource gains and experience. Potions have a prefix and suffix affix.</p>

        <form method="POST" action="/craft?tab=potions">
            <b>Select Prefix Affix:</b><br />
            <select name="prefix_affix">
                <optgroup label="Worker Yields">
                    <option value="herb_worker_yield">Herb Worker Yield (+1% per level)</option>
                    <option value="gold_worker_yield">Gold Worker Yield (+1% per level)</option>
                    <option value="iron_worker_yield">Iron Worker Yield (+1% per level)</option>
                    <option value="gems_worker_yield">Gems Worker Yield (+1% per level)</option>
                </optgroup>
                <optgroup label="Resource Drops">
                    <option value="arena_resource_drops">Arena Resource Drops (+1% per level)</option>
                    <option value="rift_drops">Rift Drops (+1% per level)</option>
                </optgroup>
            </select>
            <br /><br />

            <b>Select Suffix Affix:</b><br />
            <select name="suffix_affix">
                <optgroup label="Experience Gains">
                    <option value="arena_xp">Arena XP (+1% per level)</option>
                    <option value="rift_xp">Rift XP (+1% per level)</option>
                    <option value="world_boss_xp">World Boss XP (+100% per level)</option>
                </optgroup>
                <optgroup label="Stat Gains">
                    <option value="arena_stat_gains">Arena Stat Gains (+1% per level)</option>
                    <option value="rift_stat_gains">Rift Stat Gains (+1% per level)</option>
                </optgroup>
            </select>
            <br /><br />

            <p>
                <b>Potion Level:</b> <?php echo $party_level; ?> (Equal to Party Level <?php echo $party_level; ?>)<br />
                <small>Crafting cost: <?php echo ($party_level * 100); ?> Herbs. Potion level is fixed at your current party level.</small>
            </p>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_potion" value="Craft Potion">
        </form>

        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
