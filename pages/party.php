<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Party Management</h1>

        <h3>Frontline Character</h3>
        <div class="grid">
            <div>
                Level: <?php echo $Character->Data['party_json']['members']['frontline']['level']; ?> <br />
                Strength: <?php echo $Character->Data['party_json']['members']['frontline']['strength']; ?> <br />
                Dexterity: <?php echo $Character->Data['party_json']['members']['frontline']['dexterity']; ?> <br />
                Health: <?php echo $Character->Data['party_json']['members']['frontline']['health']; ?> <br />
                Wisdom: <?php echo $Character->Data['party_json']['members']['frontline']['wisdom']; ?> <br />
                <form>
                <br />
                <select name="class">
                    <option value="<?php echo $Character->Data['party_json']['members']['frontline']['class']; ?>">
                        <?php echo $Character->Data['party_json']['members']['frontline']['class']; ?>
                    </option>
                </select>
                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="Update Class" />
                </form>
            </div>
            <div>
                <form>
                1st Gear Slot:
                <select name="gear_slot_1">
                    <option value="Sword of Testing">Sword of Testing</option>
                    <option value="Shield of Testing">Shield of Testing</option>
                    <option value="Helmet of Testing">Helmet of Testing</option>
                </select>

                2nd Gear Slot:
                <select name="gear_slot_2">
                    <option value="Sword of Testing">Sword of Testing</option>
                    <option value="Shield of Testing">Shield of Testing</option>
                    <option value="Helmet of Testing">Helmet of Testing</option>
                </select>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                 <input type="submit" value="Update Gear" />
                </form>
            </div>
            <div>
                <form method="POST" action="/party?update=frontline_skills">
                1st Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_1",
                $Character->Data['party_json']['members']['frontline']['skills'][0]); ?>

                2nd Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_2",
                $Character->Data['party_json']['members']['frontline']['skills'][1]); ?>

                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="Update Skills" />
                </form>
            </div>
        </div>

        <h3>Backline Character</h3>
        <div class="grid">
<div>
                Level: <?php echo $Character->Data['party_json']['members']['backline']['level']; ?> <br />
                Strength: <?php echo $Character->Data['party_json']['members']['backline']['strength']; ?> <br />
                Dexterity: <?php echo $Character->Data['party_json']['members']['backline']['dexterity']; ?> <br />
                Health: <?php echo $Character->Data['party_json']['members']['backline']['health']; ?> <br />
                Wisdom: <?php echo $Character->Data['party_json']['members']['backline']['wisdom']; ?> <br />
                <form>
                <br />
                <select name="class">
                    <option value="<?php echo $Character->Data['party_json']['members']['backline']['class']; ?>">
                        <?php echo $Character->Data['party_json']['members']['backline']['class']; ?>
                    </option>
                </select>

                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="Update Class" />
                </form>
            </div>
            <div>
                <form>
                1st Gear Slot:
                <select name="gear_slot_1">
                    <option value="Sword of Testing">Sword of Testing</option>
                    <option value="Shield of Testing">Shield of Testing</option>
                    <option value="Helmet of Testing">Helmet of Testing</option>
                </select>

                2nd Gear Slot:
                <select name="gear_slot_2">
                    <option value="Sword of Testing">Sword of Testing</option>
                    <option value="Shield of Testing">Shield of Testing</option>
                    <option value="Helmet of Testing">Helmet of Testing</option>
                </select>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="Update Gear" />
                </form>
            </div>
            <div>
                <form method="POST" action="/party?update=backline_skills">
                1st Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_1",
                $Character->Data['party_json']['members']['backline']['skills'][0]); ?>

                2nd Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_2",
                $Character->Data['party_json']['members']['backline']['skills'][1]); ?>

                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                 <input type="submit" value="Update Skills" />
                </form>
            </div>
        </div>

    </article>
    </div>
    </div>
</main>
