<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Craft</h1>
        <p>Craft powerful gear to enhance your party's abilities. Each item type provides different stat bonuses.</p>

        <?php
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
        ?>

        <form method="POST" action="/craft">
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
                    <option value="strength">Strength (+5 per gear level)</option>
                    <option value="health">Health (+5 per gear level)</option>
                    <option value="dexterity">Dexterity (+5 per gear level)</option>
                    <option value="wisdom">Wisdom (+5 per gear level)</option>
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
                    <option value="strength">Strength (+5 per gear level)</option>
                    <option value="health">Health (+5 per gear level)</option>
                    <option value="dexterity">Dexterity (+5 per gear level)</option>
                    <option value="wisdom">Wisdom (+5 per gear level)</option>
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

    </article>
    </div>
    </div>
</main>
