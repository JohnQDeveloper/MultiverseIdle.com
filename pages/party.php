<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Party Management</h1>

        <h3>Frontline Character</h3>
        <div class="grid">
            <div>
                Strength: 365 <br />
                Dexterity: 365 <br />
                Health: 365 <br />
                Wisdom: 365 <br />
                <form>
                <br />
                <select name="class">
                    <option value="Tank">Tank</option>
                </select>

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
                <select name="gear_slot_1">
                    <option value="Sword of Testing">Sword of Testing</option>
                    <option value="Shield of Testing">Shield of Testing</option>
                    <option value="Helmet of Testing">Helmet of Testing</option>
                </select>

                 <input type="submit" value="Update Gear" />
                </form>
            </div>
            <div>
                <form>
                1st Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_1"); ?>

                2nd Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_2"); ?>

                 <input type="submit" value="Update Skills" />
                </form>
            </div>
        </div>

        <h3>Backline Character</h3>
        <div class="grid">
<div>
                Strength: 365 <br />
                Dexterity: 365 <br />
                Health: 365 <br />
                Wisdom: 365 <br />
                <form>
                <br />
                <select name="class">
                    <option value="Ranger">Ranger</option>
                </select>

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
                <select name="gear_slot_1">
                    <option value="Sword of Testing">Sword of Testing</option>
                    <option value="Shield of Testing">Shield of Testing</option>
                    <option value="Helmet of Testing">Helmet of Testing</option>
                </select>

                 <input type="submit" value="Update Gear" />
                </form>
            </div>
            <div>
                <form>
                1st Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_1"); ?>

                2nd Skill Gem:
                <?php echo Controls::SkillGemSelectBox("skill_gem_2"); ?>

                 <input type="submit" value="Update Skills" />
                </form>
            </div>
        </div>

    </article>
    </div>
    </div>
</main>
