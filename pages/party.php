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
                <form>
                <br />
                <select name="class">
                    <option value="<?php echo $Character->Data['party_json']['members']['frontline']['class']; ?>">
                        <?php echo t('party.class.' . $Character->Data['party_json']['members']['frontline']['class']); ?>
                    </option>
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
                <form>
                <br />
                <select name="class">
                    <option value="<?php echo $Character->Data['party_json']['members']['backline']['class']; ?>">
                        <?php echo t('party.class.' . $Character->Data['party_json']['members']['backline']['class']); ?>
                    </option>
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
            </div>
        </div>

    </article>
    </div>
    </div>
</main>
