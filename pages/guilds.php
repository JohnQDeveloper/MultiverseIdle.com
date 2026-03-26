<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('guilds.title'); ?></h1>

        <?php if (!empty($pending_invites)): ?>
            <!-- Pending Invites Section -->
            <div class="info-box" style="background-color: rgba(142, 250, 213, 0.1); border-color: #8EFAD5;">
                <h2 class="heading--no-top-margin"><?php echo t('guilds.pending_invites', ['count' => count($pending_invites)]); ?></h2>
                <?php foreach ($pending_invites as $invite): ?>
                    <div class="card" style="margin-bottom: 15px;">
                        <h3 class="heading--no-top-margin"><?php echo htmlspecialchars($invite['guild_name']); ?></h3>
                        <?php if (!empty($invite['description'])): ?>
                            <p><?php echo htmlspecialchars($invite['description']); ?></p>
                        <?php endif; ?>
                        <p><small><?php echo t('guilds.invited_by', ['name' => htmlspecialchars($invite['inviter_name'] ?? t('common.n_a'))]); ?></small></p>
                        <p><small><?php echo t('guilds.invited_at', ['date' => date('M j, Y g:i A', strtotime($invite['created_at']))]); ?></small></p>

                        <form method="post" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="invite_id" value="<?php echo $invite['invite_id']; ?>">
                            <button type="submit" name="accept_invite" class="button button--success"><?php echo t('guilds.accept'); ?></button>
                        </form>
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="invite_id" value="<?php echo $invite['invite_id']; ?>">
                            <button type="submit" name="decline_invite" class="button button--danger"><?php echo t('guilds.decline'); ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($user_guild_id === null): ?>
            <!-- No Guild - Create Guild Section -->
            <div class="info-box">
                <h3 class="heading--no-top-margin"><?php echo t('guilds.not_in_guild'); ?></h3>
                <p><?php echo t('guilds.not_in_desc'); ?></p>
            </div>

            <h2><?php echo t('guilds.create_title'); ?></h2>
            <form method="post" class="card">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                <div style="margin-bottom: 15px;">
                    <label for="guild_name"><b><?php echo t('guilds.guild_name'); ?></b> <?php echo t('guilds.guild_name_hint'); ?></label>
                    <input type="text" id="guild_name" name="guild_name" required minlength="3" maxlength="50" style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="guild_description"><b><?php echo t('guilds.description'); ?></b> <?php echo t('guilds.description_hint'); ?></label>
                    <textarea id="guild_description" name="guild_description" rows="3" maxlength="500" style="width: 100%; padding: 8px; margin-top: 5px;"></textarea>
                </div>

                <button type="submit" name="create_guild" class="button button--primary"><?php echo t('guilds.create_submit'); ?></button>
            </form>

        <?php else: ?>
            <!-- In a Guild - Guild Management Section -->
            <div class="card card--active">
                <h2 class="heading--no-top-margin">
                    <?php echo htmlspecialchars($guild_data['name']); ?>
                    <span class="badge" style="margin-left: 10px;">
                        <?php
                        $role_display = [
                            'guild_master' => t('guilds.role.guild_master'),
                            'officer' => t('guilds.role.officer'),
                            'member' => t('guilds.role.member')
                        ];
                        echo $role_display[$user_role] ?? t('guilds.role.member');
                        ?>
                    </span>
                </h2>

                <?php if (!empty($guild_data['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($guild_data['description'])); ?></p>
                <?php endif; ?>

                <p><small><?php echo t('guilds.created', ['date' => date('M j, Y', strtotime($guild_data['created_at']))]); ?></small></p>
            </div>

            <!-- Guild Members Section -->
            <h2><?php echo t('guilds.members_title', ['count' => count($guild_members)]); ?></h2>

            <div style="margin-bottom: 20px;">
                <?php foreach ($guild_members as $member): ?>
                    <?php
                    $is_current_user = $member['user_id'] == $_SESSION['auth_user_id'];
                    $member_role = $member['role'];
                    ?>
                    <div class="card" style="margin-bottom: 10px;">
                        <div class="grid" style="align-items: center;">
                            <div>
                                <h3 class="heading--no-top-margin" style="margin-bottom: 5px;">
                                    <?php echo htmlspecialchars($member['character_name'] ?? 'Unknown'); ?>
                                    <?php if ($is_current_user): ?>
                                        <span style="color: #8EFAD5;"><?php echo t('guilds.you'); ?></span>
                                    <?php endif; ?>
                                </h3>
                                <p style="margin: 0;">
                                    <span class="badge">
                                        <?php echo $role_display[$member_role] ?? t('guilds.role.member'); ?>
                                    </span>
                                    <span style="margin-left: 10px;"><?php echo t('common.level'); ?> <?php echo $member['level'] ?? 1; ?></span>
                                </p>
                                <p style="margin: 5px 0 0 0;"><small><?php echo t('guilds.joined', ['date' => date('M j, Y', strtotime($member['joined_at']))]); ?></small></p>
                            </div>

                            <?php if (!$is_current_user && ($user_role === 'guild_master' || ($user_role === 'officer' && $member_role === 'member'))): ?>
                                <div style="text-align: right;">
                                    <!-- Guild Master Actions -->
                                    <?php if ($user_role === 'guild_master'): ?>
                                        <?php if ($member_role === 'member'): ?>
                                            <form method="post" style="display: inline-block; margin-left: 5px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                                <input type="hidden" name="target_user_id" value="<?php echo $member['user_id']; ?>">
                                                <button type="submit" name="promote_officer" class="button button--small"><?php echo t('guilds.promote_officer'); ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($member_role === 'officer'): ?>
                                            <form method="post" style="display: inline-block; margin-left: 5px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                                <input type="hidden" name="target_user_id" value="<?php echo $member['user_id']; ?>">
                                                <button type="submit" name="demote_member" class="button button--small"><?php echo t('guilds.demote_member'); ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" style="display: inline-block; margin-left: 5px;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                            <input type="hidden" name="new_master_user_id" value="<?php echo $member['user_id']; ?>">
                                            <button type="submit" name="transfer_master" class="button button--small button--warning" onclick="return confirm('<?php echo t('confirm.transfer_master'); ?>');"><?php echo t('guilds.transfer_master'); ?></button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Kick Member (Guild Master or Officer) -->
                                    <form method="post" style="display: inline-block; margin-left: 5px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                        <input type="hidden" name="target_user_id" value="<?php echo $member['user_id']; ?>">
                                        <button type="submit" name="kick_member" class="button button--small button--danger" onclick="return confirm('<?php echo t('confirm.kick_member'); ?>');"><?php echo t('guilds.kick'); ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Invite Members Section (Guild Master or Officer) -->
            <?php if ($user_role === 'guild_master' || $user_role === 'officer'): ?>
                <h2><?php echo t('guilds.invite_title'); ?></h2>
                <div class="card">
                    <form method="get" style="margin-bottom: 15px;">
                        <label for="search"><b><?php echo t('guilds.search_label'); ?></b></label>
                        <div style="display: flex; gap: 10px; margin-top: 5px;">
                            <input type="text" id="search" name="search" placeholder="<?php echo t('guilds.search_ph'); ?>" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex: 1; padding: 8px;">
                        </div>
                        <div>
                            <button type="submit" class="button"><?php echo t('guilds.search_submit'); ?></button>
                        </div>
                    </form>

                    <?php if (!empty($search_results)): ?>
                        <p><b><?php echo t('guilds.search_results'); ?></b></p>
                        <?php foreach ($search_results as $result): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #444; margin-bottom: 5px;">
                                <div>
                                    <b><?php echo htmlspecialchars($result['character_name']); ?></b>
                                    <span style="margin-left: 10px; color: #999;"><?php echo t('common.level'); ?> <?php echo $result['level'] ?? 1; ?></span>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="invitee_user_id" value="<?php echo $result['user_id']; ?>">
                                    <button type="submit" name="send_invite" class="button button--small button--primary"><?php echo t('guilds.send_invite'); ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <p><em><?php echo t('guilds.no_results'); ?></em></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Leave/Disband Guild Section -->
            <h2><?php echo t('guilds.actions_title'); ?></h2>
            <div class="card">
                <?php if ($user_role !== 'guild_master'): ?>
                    <form method="post" style="margin-bottom: 10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <button type="submit" name="leave_guild" class="button button--danger" onclick="return confirm('<?php echo t('confirm.leave_guild'); ?>');"><?php echo t('guilds.leave'); ?></button>
                    </form>
                <?php else: ?>
                    <p><b><?php echo t('guilds.disband_title'); ?></b></p>
                    <p><?php echo t('guilds.disband_desc'); ?></p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <div style="margin-bottom: 10px;">
                            <label for="confirm_disband"><?php echo t('guilds.disband_confirm'); ?></label>
                            <input type="text" id="confirm_disband" name="confirm_disband" required style="padding: 8px; margin-top: 5px;">
                        </div>
                        <button type="submit" name="disband_guild" class="button button--danger"><?php echo t('guilds.disband_submit'); ?></button>
                    </form>

                    <p style="margin-top: 20px;"><small><?php echo t('guilds.disband_leave_note'); ?></small></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
