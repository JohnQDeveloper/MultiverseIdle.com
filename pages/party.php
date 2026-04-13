<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <?php
        // Get all currently equipped item IDs to prevent double-equipping
        $frontline_weapon = $Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0;
        $frontline_armor = $Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0;
        $backline_weapon = $Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0;
        $backline_armor = $Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0;
    ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('party.title'); ?></h1>

        <h3><?php echo t('party.frontline'); ?></h3>
        <div class="grid">
            <div>
                <?php echo t('common.level'); ?>: <?php echo $Character->Data['party_json']['members']['frontline']['level']; ?> <br />
                <?php echo t('party.strength'); ?>: <?php echo $Character->Data['party_json']['members']['frontline']['strength']; ?> <br />
                <?php echo t('party.dexterity'); ?>: <?php echo $Character->Data['party_json']['members']['frontline']['dexterity']; ?> <br />
                <?php echo t('party.health'); ?>: <?php echo $Character->Data['party_json']['members']['frontline']['health']; ?> <br />
                <?php echo t('party.wisdom'); ?>: <?php echo $Character->Data['party_json']['members']['frontline']['wisdom']; ?> <br />
                <form method="POST" action="/party?update=frontline_class">
                <br />
                <select name="class">
                    <?php foreach (['strength', 'dexterity', 'health', 'wisdom'] as $party_class): ?>
                    <option
                        value="<?php echo $party_class; ?>"
                        <?php echo ($Character->Data['party_json']['members']['frontline']['class'] === $party_class) ? 'selected' : ''; ?>
                    >
                        <?php echo t('party.class.' . $party_class); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="<?php echo t('party.update_class'); ?>" />
                </form>
            </div>
            <div>
                <form method="POST" action="/party?update=frontline_gear">
                <?php echo t('party.weapon_slot'); ?>
                <select name="weapon_slot">
                    <option value="0"><?php echo t('party.none'); ?></option>
                    <?php
                    foreach ($favorite_weapons as $weapon):
                        // Skip if equipped by backline
                        if ($weapon['id'] == $backline_weapon) continue;
                        $selected = ($weapon['id'] == $frontline_weapon) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $weapon['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($weapon['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <?php echo t('party.armor_slot'); ?>
                <select name="armor_slot">
                    <option value="0"><?php echo t('party.none'); ?></option>
                    <?php
                    foreach ($favorite_armors as $armor):
                        // Skip if equipped by backline
                        if ($armor['id'] == $backline_armor) continue;
                        $selected = ($armor['id'] == $frontline_armor) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $armor['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($armor['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                 <input type="submit" value="<?php echo t('party.update_gear'); ?>" />
                </form>
                <small><a href="/inventory"><?php echo t('party.favorite_hint'); ?></a></small>
            </div>
            <div>
                <form method="POST" action="/party?update=frontline_skills">
                <?php echo t('party.skill_gem_1'); ?>
                <?php echo Controls::SkillGemSelectBox("skill_gem_1",
                $Character->Data['party_json']['members']['frontline']['skills'][0]); ?>

                <?php echo t('party.skill_gem_2'); ?>
                <?php echo Controls::SkillGemSelectBox("skill_gem_2",
                $Character->Data['party_json']['members']['frontline']['skills'][1]); ?>

                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="<?php echo t('party.update_skills'); ?>" />
                </form>

                <form method="POST" action="/party?update=frontline_skill_gems">
                <?php
                    $fl_skills = $Character->Data['party_json']['members']['frontline']['skills'] ?? [];
                    $fl_equipped_gems = $Character->Data['party_json']['members']['frontline']['equipped_skill_gems'] ?? [0, 0];
                    foreach ([0, 1] as $slot_idx):
                        $slot_skill = $fl_skills[$slot_idx] ?? '';
                        $slot_gem_id = $fl_equipped_gems[$slot_idx] ?? 0;
                ?>
                <?php echo t('party.skill_gem_slot_1', ['skill' => htmlspecialchars($slot_skill)]); ?><br />
                <select name="skill_gem_slot_<?php echo $slot_idx; ?>">
                    <option value="0"><?php echo t('party.no_gem'); ?></option>
                    <?php foreach ($favorite_skill_gems as $fav_gem):
                        if ($fav_gem['skill_name'] !== $slot_skill) continue;
                        $selected = ($fav_gem['id'] == $slot_gem_id) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $fav_gem['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($fav_gem['name']); ?> (Tier <?php echo $fav_gem['tier']; ?>)</option>
                    <?php endforeach; ?>
                </select><br />
                <?php endforeach; ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                <input type="submit" value="<?php echo t('party.update_skill_gems'); ?>" />
                </form>
                <small><a href="/inventory?tab=skill_gems"><?php echo t('party.skill_gem_hint'); ?></a></small>
            </div>
        </div>

        <h3><?php echo t('party.backline'); ?></h3>
        <div class="grid">
<div>
                <?php echo t('common.level'); ?>: <?php echo $Character->Data['party_json']['members']['backline']['level']; ?> <br />
                <?php echo t('party.strength'); ?>: <?php echo $Character->Data['party_json']['members']['backline']['strength']; ?> <br />
                <?php echo t('party.dexterity'); ?>: <?php echo $Character->Data['party_json']['members']['backline']['dexterity']; ?> <br />
                <?php echo t('party.health'); ?>: <?php echo $Character->Data['party_json']['members']['backline']['health']; ?> <br />
                <?php echo t('party.wisdom'); ?>: <?php echo $Character->Data['party_json']['members']['backline']['wisdom']; ?> <br />
                <form method="POST" action="/party?update=backline_class">
                <br />
                <select name="class">
                    <?php foreach (['strength', 'dexterity', 'health', 'wisdom'] as $party_class): ?>
                    <option
                        value="<?php echo $party_class; ?>"
                        <?php echo ($Character->Data['party_json']['members']['backline']['class'] === $party_class) ? 'selected' : ''; ?>
                    >
                        <?php echo t('party.class.' . $party_class); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="<?php echo t('party.update_class'); ?>" />
                </form>
            </div>
            <div>
                <form method="POST" action="/party?update=backline_gear">
                <?php echo t('party.weapon_slot'); ?>
                <select name="weapon_slot">
                    <option value="0"><?php echo t('party.none'); ?></option>
                    <?php
                    foreach ($favorite_weapons as $weapon):
                        // Skip if equipped by frontline
                        if ($weapon['id'] == $frontline_weapon) continue;
                        $selected = ($weapon['id'] == $backline_weapon) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $weapon['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($weapon['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <?php echo t('party.armor_slot'); ?>
                <select name="armor_slot">
                    <option value="0"><?php echo t('party.none'); ?></option>
                    <?php
                    foreach ($favorite_armors as $armor):
                        // Skip if equipped by frontline
                        if ($armor['id'] == $frontline_armor) continue;
                        $selected = ($armor['id'] == $backline_armor) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $armor['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($armor['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="<?php echo t('party.update_gear'); ?>" />
                </form>
                <small><a href="/inventory"><?php echo t('party.favorite_hint'); ?></a></small>
            </div>
            <div>
                <form method="POST" action="/party?update=backline_skills">
                <?php echo t('party.skill_gem_1'); ?>
                <?php echo Controls::SkillGemSelectBox("skill_gem_1",
                $Character->Data['party_json']['members']['backline']['skills'][0]); ?>

                <?php echo t('party.skill_gem_2'); ?>
                <?php echo Controls::SkillGemSelectBox("skill_gem_2",
                $Character->Data['party_json']['members']['backline']['skills'][1]); ?>

                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="<?php echo t('party.update_skills'); ?>" />
                </form>

                <form method="POST" action="/party?update=backline_skill_gems">
                <?php
                    $bl_skills = $Character->Data['party_json']['members']['backline']['skills'] ?? [];
                    $bl_equipped_gems = $Character->Data['party_json']['members']['backline']['equipped_skill_gems'] ?? [0, 0];
                    foreach ([0, 1] as $slot_idx):
                        $slot_skill = $bl_skills[$slot_idx] ?? '';
                        $slot_gem_id = $bl_equipped_gems[$slot_idx] ?? 0;
                ?>
                <?php echo t('party.skill_gem_slot_1', ['skill' => htmlspecialchars($slot_skill)]); ?><br />
                <select name="skill_gem_slot_<?php echo $slot_idx; ?>">
                    <option value="0"><?php echo t('party.no_gem'); ?></option>
                    <?php foreach ($favorite_skill_gems as $fav_gem):
                        if ($fav_gem['skill_name'] !== $slot_skill) continue;
                        $selected = ($fav_gem['id'] == $slot_gem_id) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $fav_gem['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($fav_gem['name']); ?> (Tier <?php echo $fav_gem['tier']; ?>)</option>
                    <?php endforeach; ?>
                </select><br />
                <?php endforeach; ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                <input type="submit" value="<?php echo t('party.update_skill_gems'); ?>" />
                </form>
                <small><a href="/inventory?tab=skill_gems"><?php echo t('party.skill_gem_hint'); ?></a></small>
            </div>
        </div>

    </article>
    </div>
    </div>
</main>
