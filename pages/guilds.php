<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Guilds</h1>

        <?php if (!empty($pending_invites)): ?>
            <!-- Pending Invites Section -->
            <div class="info-box" style="background-color: rgba(142, 250, 213, 0.1); border-color: #8EFAD5;">
                <h2 class="heading--no-top-margin">Pending Guild Invites (<?php echo count($pending_invites); ?>)</h2>
                <?php foreach ($pending_invites as $invite): ?>
                    <div class="card" style="margin-bottom: 15px;">
                        <h3 class="heading--no-top-margin"><?php echo htmlspecialchars($invite['guild_name']); ?></h3>
                        <?php if (!empty($invite['description'])): ?>
                            <p><?php echo htmlspecialchars($invite['description']); ?></p>
                        <?php endif; ?>
                        <p><small>Invited by: <?php echo htmlspecialchars($invite['inviter_name'] ?? 'Unknown'); ?></small></p>
                        <p><small>Invited: <?php echo date('M j, Y g:i A', strtotime($invite['created_at'])); ?></small></p>

                        <form method="post" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="invite_id" value="<?php echo $invite['invite_id']; ?>">
                            <button type="submit" name="accept_invite" class="button button--success">Accept</button>
                        </form>
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="invite_id" value="<?php echo $invite['invite_id']; ?>">
                            <button type="submit" name="decline_invite" class="button button--danger">Decline</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($user_guild_id === null): ?>
            <!-- No Guild - Create Guild Section -->
            <div class="info-box">
                <h3 class="heading--no-top-margin">You are not in a guild</h3>
                <p>Create a guild to team up with other players, or wait for an invite to join an existing guild.</p>
            </div>

            <h2>Create a Guild</h2>
            <form method="post" class="card">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                <div style="margin-bottom: 15px;">
                    <label for="guild_name"><b>Guild Name</b> (3-50 characters)</label>
                    <input type="text" id="guild_name" name="guild_name" required minlength="3" maxlength="50" style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="guild_description"><b>Description</b> (optional)</label>
                    <textarea id="guild_description" name="guild_description" rows="3" maxlength="500" style="width: 100%; padding: 8px; margin-top: 5px;"></textarea>
                </div>

                <button type="submit" name="create_guild" class="button button--primary">Create Guild</button>
            </form>

        <?php else: ?>
            <!-- In a Guild - Guild Management Section -->
            <div class="card card--active">
                <h2 class="heading--no-top-margin">
                    <?php echo htmlspecialchars($guild_data['name']); ?>
                    <span class="badge" style="margin-left: 10px;">
                        <?php
                        $role_display = [
                            'guild_master' => 'Guild Master',
                            'officer' => 'Officer',
                            'member' => 'Member'
                        ];
                        echo $role_display[$user_role] ?? 'Member';
                        ?>
                    </span>
                </h2>

                <?php if (!empty($guild_data['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($guild_data['description'])); ?></p>
                <?php endif; ?>

                <p><small>Created: <?php echo date('M j, Y', strtotime($guild_data['created_at'])); ?></small></p>
            </div>

            <!-- Guild Members Section -->
            <h2>Guild Members (<?php echo count($guild_members); ?>/20)</h2>

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
                                        <span style="color: #8EFAD5;">(You)</span>
                                    <?php endif; ?>
                                </h3>
                                <p style="margin: 0;">
                                    <span class="badge">
                                        <?php echo $role_display[$member_role] ?? 'Member'; ?>
                                    </span>
                                    <span style="margin-left: 10px;">Level <?php echo $member['level'] ?? 1; ?></span>
                                </p>
                                <p style="margin: 5px 0 0 0;"><small>Joined: <?php echo date('M j, Y', strtotime($member['joined_at'])); ?></small></p>
                            </div>

                            <?php if (!$is_current_user && ($user_role === 'guild_master' || ($user_role === 'officer' && $member_role === 'member'))): ?>
                                <div style="text-align: right;">
                                    <!-- Guild Master Actions -->
                                    <?php if ($user_role === 'guild_master'): ?>
                                        <?php if ($member_role === 'member'): ?>
                                            <form method="post" style="display: inline-block; margin-left: 5px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                                <input type="hidden" name="target_user_id" value="<?php echo $member['user_id']; ?>">
                                                <button type="submit" name="promote_officer" class="button button--small">Promote to Officer</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($member_role === 'officer'): ?>
                                            <form method="post" style="display: inline-block; margin-left: 5px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                                <input type="hidden" name="target_user_id" value="<?php echo $member['user_id']; ?>">
                                                <button type="submit" name="demote_member" class="button button--small">Demote to Member</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" style="display: inline-block; margin-left: 5px;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                            <input type="hidden" name="new_master_user_id" value="<?php echo $member['user_id']; ?>">
                                            <button type="submit" name="transfer_master" class="button button--small button--warning" onclick="return confirm('Are you sure you want to transfer guild master to this member? You will become an officer.');">Transfer Master</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Kick Member (Guild Master or Officer) -->
                                    <form method="post" style="display: inline-block; margin-left: 5px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                        <input type="hidden" name="target_user_id" value="<?php echo $member['user_id']; ?>">
                                        <button type="submit" name="kick_member" class="button button--small button--danger" onclick="return confirm('Are you sure you want to kick this member?');">Kick</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Guild Bank Section -->
            <h2>Guild Bank</h2>
            <div class="card">
                <p style="margin-top: 0;"><b>Current Tax Rate:</b> <?php echo $guild_tax_rate; ?>%
                    <small style="color: #999;"> — flat % taken from each member's arena and worker income</small>
                </p>
                <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 10px;">
                    <?php foreach (['gold' => 'Gold', 'iron' => 'Iron', 'herbs' => 'Herbs', 'gems' => 'Gems'] as $key => $label): ?>
                        <div style="text-align: center; padding: 10px; border: 1px solid #444;">
                            <div style="font-size: 0.85em; color: #999;"><?php echo $label; ?></div>
                            <div style="font-size: 1.2em; font-weight: bold;"><?php echo number_format($guild_bank[$key]); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($user_role === 'guild_master' || $user_role === 'officer'): ?>
                    <form method="post" style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <label for="tax_rate"><b>Set Tax Rate:</b></label>
                        <input type="number" id="tax_rate" name="tax_rate" min="0" max="20" value="<?php echo $guild_tax_rate; ?>" style="width: 70px; padding: 6px; text-align: center;">
                        <span style="color: #999;">% (0–20)</span>
                        <button type="submit" name="set_tax_rate" class="button button--small button--primary">Save</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Invite Members Section (Guild Master or Officer) -->
            <?php if ($user_role === 'guild_master' || $user_role === 'officer'): ?>
                <h2>Invite Members</h2>
                <div class="card">
                    <form method="get" style="margin-bottom: 15px;">
                        <label for="search"><b>Search for players by character name:</b></label>
                        <div style="display: flex; gap: 10px; margin-top: 5px;">
                            <input type="text" id="search" name="search" placeholder="Enter character name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex: 1; padding: 8px;">
                        </div>
                        <div>
                            <button type="submit" class="button">Search</button>
                        </div>
                    </form>

                    <?php if (!empty($search_results)): ?>
                        <p><b>Search Results:</b></p>
                        <?php foreach ($search_results as $result): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #444; margin-bottom: 5px;">
                                <div>
                                    <b><?php echo htmlspecialchars($result['character_name']); ?></b>
                                    <span style="margin-left: 10px; color: #999;">Level <?php echo $result['level'] ?? 1; ?></span>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="invitee_user_id" value="<?php echo $result['user_id']; ?>">
                                    <button type="submit" name="send_invite" class="button button--small button--primary">Send Invite</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <p><em>No players found. They may already be in a guild.</em></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Leave/Disband Guild Section -->
            <h2>Guild Actions</h2>
            <div class="card">
                <?php if ($user_role !== 'guild_master'): ?>
                    <form method="post" style="margin-bottom: 10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <button type="submit" name="leave_guild" class="button button--danger" onclick="return confirm('Are you sure you want to leave this guild?');">Leave Guild</button>
                    </form>
                <?php else: ?>
                    <p><b>Disband Guild</b></p>
                    <p>As the guild master, you can disband the guild. This will remove all members and permanently delete the guild.</p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <div style="margin-bottom: 10px;">
                            <label for="confirm_disband">Type <b>DISBAND</b> to confirm:</label>
                            <input type="text" id="confirm_disband" name="confirm_disband" required style="padding: 8px; margin-top: 5px;">
                        </div>
                        <button type="submit" name="disband_guild" class="button button--danger">Disband Guild</button>
                    </form>

                    <p style="margin-top: 20px;"><small>To leave the guild, transfer guild master to another member first.</small></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
