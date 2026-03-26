<!-- Header Navigation -->
      <div class="navbar">
        <button class="hamburger" aria-label="<?php echo t('nav.actions'); ?>">
          <span></span>
          <span></span>
          <span></span>
        </button>

        <div class="container">
          <nav>
            <ul class="nav-menu">
              <li><a href="#"><img src="/img/logo.png" height="64"></a></li>
              <li class="dropdown">
                <a href="#"><?php echo t('nav.actions'); ?></a>
                <ul class="dropdown-content">
                  <li><a href="/arena"><?php echo t('nav.arena'); ?></a></li>
                  <li><a href="/rifts"><?php echo t('nav.rifts'); ?></a></li>
                  <li><a href="/world-boss"><?php echo t('nav.world_boss'); ?></a></li>
                  <li><a href="/workers"><?php echo t('nav.workers'); ?></a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#"><?php echo t('nav.items'); ?></a>
                <ul class="dropdown-content">
                  <li><a href="/craft"><?php echo t('nav.craft'); ?></a></li>
                  <li><a href="/inventory"><?php echo t('nav.inventory'); ?></a></li>
                  <li><a href="/party"><?php echo t('nav.party'); ?></a></li>
                </ul>
              </li>
              <?php if (!isset($_SESSION['guest_mode']) || $_SESSION['guest_mode'] !== true): ?>
              <li><a href="/market"><?php echo t('nav.market'); ?></a></li>
              <?php endif; ?>
              <li class="dropdown">
                <a href="/guilds"><?php echo t('nav.guilds'); ?></a>
                <ul class="dropdown-content">
                  <li><a href="/guilds"><?php echo t('nav.guild'); ?></a></li>
                  <li><a href="/guild-bank"><?php echo t('nav.guild_bank'); ?></a></li>
                  <li><a href="/guild-buildings"><?php echo t('nav.guild_buildings'); ?></a></li>
                  <li><a href="/guild-quests"><?php echo t('nav.guild_quests'); ?></a></li>
                </ul>
              </li>
              <?php if (!isset($_SESSION['guest_mode']) || $_SESSION['guest_mode'] !== true): ?>
              <li><a href="/store"><?php echo t('nav.store'); ?></a></li>
              <?php endif; ?>
              <li><a href="/pvp"><?php echo t('nav.pvp'); ?></a></li>

              <li class="dropdown">
                <a href="#">Misc</a>
                <ul class="dropdown-content">
                    <li><a href="/leaderboard"><?php echo t('nav.leaderboard'); ?></a></li>
                    <li><a href="/season-select">League Swap</a></li>
                    <li><a href="/log"><?php echo t('nav.log'); ?></a></li>
                </ul>
            </li>


              <li><a href="/logout"><?php echo t('nav.logout'); ?></a></li>
              <li> ::: </li>
              <li><a href="https://discord.gg/KrD7hGuDyb"><?php echo t('nav.discord'); ?></a></li>
              <li><a href="https://github.com/JohnQDeveloper/MultiverseIdle.com/issues"><?php echo t('nav.feedback'); ?></a></li>
              <li><a href="/settings"><?php echo t('nav.settings'); ?></a></li>
            </ul>
          </nav>

        </div>
      </div>

<?php
// Compute chat widget data (Redis-first, minimal overhead)
$_chatUserId   = (int)($_SESSION['auth_user_id'] ?? 0);
$_chatIsGuest  = isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;
$_chatIsLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1 && $_chatUserId > 0;
$_chatIsMod    = false;
$_chatGuildId  = '';
if ($_chatIsLoggedIn) {
    $_ChatObj    = new Chat();
    $_chatIsMod  = $_ChatObj->isModerator($_chatUserId);
    $_GuildObj   = new Guild();
    $_GuildObj->setSeasonId(isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null);
    $_guildIdInt = $_GuildObj->GetUserGuildId($_chatUserId);
    $_chatGuildId = $_guildIdInt !== null ? (string)$_guildIdInt : '';
}
?>

<!-- Floating Chat Widget -->
<div id="chat-widget"
     class="chat-widget"
     data-csrf="<?php echo htmlspecialchars($_SESSION['csrf-token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
     data-user-id="<?php echo $_chatUserId; ?>"
     data-is-mod="<?php echo $_chatIsMod ? '1' : '0'; ?>"
     data-is-guest="<?php echo $_chatIsGuest ? '1' : '0'; ?>"
     data-is-season="<?php echo isset($_SESSION['active_season_id']) ? '1' : '0'; ?>"
     data-guild-id="<?php echo htmlspecialchars($_chatGuildId, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Always-visible toggle bar -->
    <button id="chat-toggle" class="chat-toggle" aria-expanded="false" aria-controls="chat-widget-body">
        <span class="chat-toggle-label">&#128172; <?php echo t('chat.toggle'); ?></span>
        <span id="chat-unread" class="chat-unread" style="display:none"></span>
        <span class="chat-toggle-arrow">&#9650;</span>
    </button>

    <!-- Collapsible body -->
    <div id="chat-widget-body" class="chat-widget-body" hidden>

        <!-- Channel tabs -->
        <div class="chat-tabs" role="tablist">
            <button class="chat-tab active" data-channel="global" role="tab"><?php echo t('chat.channel.global'); ?></button>
            <button class="chat-tab" data-channel="help" role="tab"><?php echo t('chat.channel.help'); ?></button>
            <?php if ($_chatGuildId !== ''): ?>
            <button class="chat-tab" data-channel="guild:<?php echo htmlspecialchars($_chatGuildId, ENT_QUOTES, 'UTF-8'); ?>" role="tab"><?php echo t('chat.channel.guild'); ?></button>
            <?php else: ?>
            <button class="chat-tab chat-tab--disabled" disabled title="<?php echo t('chat.guild_join_title'); ?>"><?php echo t('chat.channel.guild'); ?></button>
            <?php endif; ?>
            <?php if ($_chatIsLoggedIn && !$_chatIsGuest): ?>
            <button class="chat-tab" data-channel="dm" role="tab"><?php echo t('chat.channel.dm'); ?></button>
            <?php endif; ?>
        </div>

        <!-- DM: conversation list (shown when DM tab is active, no conversation open) -->
        <?php if ($_chatIsLoggedIn && !$_chatIsGuest): ?>
        <div id="dm-conversations" class="dm-conversations" style="display:none;">
            <div id="dm-conversation-list" class="dm-conversation-list">
                <div class="chat-loading"><?php echo t('chat.loading'); ?></div>
            </div>
            <div class="dm-new-row">
                <input type="text" id="dm-new-username" class="chat-input"
                       placeholder="<?php echo t('chat.dm_placeholder'); ?>" maxlength="50" autocomplete="off" aria-label="<?php echo t('chat.dm_placeholder'); ?>">
                <button type="button" id="dm-new-btn" class="chat-send-btn"><?php echo t('chat.dm_open'); ?></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- DM: chat header with back button (shown when a DM conversation is open) -->
        <div id="dm-chat-header" class="dm-chat-header" style="display:none;">
            <button type="button" id="dm-back-btn" class="dm-back-btn"><?php echo t('chat.back'); ?></button>
            <span id="dm-partner-name" class="dm-partner-name"></span>
        </div>

        <!-- Messages -->
        <div id="chat-messages" class="chat-messages" aria-live="polite">
            <div class="chat-loading"><?php echo t('chat.loading'); ?></div>
        </div>

        <!-- Mute notice -->
        <div id="chat-muted-banner" class="chat-muted-banner" style="display:none;">
            <?php echo t('chat.muted'); ?> <span id="chat-muted-reason"></span>
        </div>

        <!-- Slash command hint -->
        <?php if (!$_chatIsGuest): ?>
        <div id="chat-slash-hint" class="chat-slash-hint" style="display:none;" role="listbox" aria-label="<?php echo t('chat.msg_placeholder'); ?>"></div>
        <?php endif; ?>

        <!-- Input -->
        <?php if (!$_chatIsGuest): ?>
        <form id="chat-form" class="chat-input-row" autocomplete="off">
            <input type="text" id="chat-input" class="chat-input"
                   placeholder="<?php echo t('chat.msg_placeholder'); ?>" maxlength="500" aria-label="<?php echo t('chat.msg_placeholder'); ?>">
            <button type="submit" class="chat-send-btn"><?php echo t('chat.send'); ?></button>
        </form>
        <?php else: ?>
        <p class="chat-guest-notice"><?php echo t('chat.guest_notice', [
            'register_link' => '<a href="/register">' . t('chat.register') . '</a>',
            'login_link' => '<a href="/login">' . t('chat.login') . '</a>',
        ]); ?></p>
        <?php endif; ?>

        <!-- Mod panel -->
        <?php if ($_chatIsMod): ?>
        <div id="chat-mod-panel" class="chat-mod-panel">
            <details>
                <summary class="chat-mod-summary"><?php echo t('chat.mod.title'); ?></summary>
                <form id="mod-form" class="chat-mod-form">
                    <input type="text" id="mod-user-id" placeholder="<?php echo t('chat.mod.user_id'); ?>" class="mod-input" style="width:80px;" required>
                    <select id="mod-action" class="mod-input">
                        <option value="promote"><?php echo t('chat.mod.promote'); ?></option>
                        <option value="demote"><?php echo t('chat.mod.demote'); ?></option>
                        <option value="mute"><?php echo t('chat.mod.mute'); ?></option>
                        <option value="unmute"><?php echo t('chat.mod.unmute'); ?></option>
                    </select>
                    <select id="mod-duration" class="mod-input">
                        <option value="3600"><?php echo t('chat.mod.1h'); ?></option>
                        <option value="86400"><?php echo t('chat.mod.24h'); ?></option>
                        <option value="604800"><?php echo t('chat.mod.7d'); ?></option>
                        <option value="-1"><?php echo t('chat.mod.permanent'); ?></option>
                    </select>
                    <input type="text" id="mod-reason" placeholder="<?php echo t('chat.mod.reason'); ?>" class="mod-input" style="width:120px;">
                    <button type="submit" class="chat-send-btn chat-send-btn--danger"><?php echo t('chat.mod.apply'); ?></button>
                </form>
                <div id="mod-result" class="mod-result" style="display:none;"></div>
            </details>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.MI_LANG = <?php echo t_json(); ?>;
</script>
<script src="/js/chat.js"></script>

<main class="container">
    <div class="wrapper">
    <div class="resources">
        <div class="row">
            <?php echo t('res.level'); ?>: <?php echo htmlspecialchars($Character->Data['party_json']['members']['frontline']['level']); ?> <BR />
            <!-- XP: <?php echo htmlspecialchars($Character->Data['party_json']['members']['frontline']['xp']); ?> <BR /> -->
            <?php echo t('res.arena'); ?>: <?php echo htmlspecialchars($Character->Data['arena_floor']); ?> <BR />
        </div>
        <div class="row">
            <?php echo t('res.gold'); ?>: <?php echo human_num($Character->Data['gold']); ?> <BR />
            <?php echo t('res.iron'); ?>: <?php echo human_num($Character->Data['iron']); ?> <BR />
        </div>
        <div class="row">
            <?php echo t('res.herbs'); ?>: <?php echo human_num($Character->Data['herbs']); ?> <BR />
            <?php echo t('res.gems'); ?>: <?php echo human_num($Character->Data['gems']); ?> <BR />
        </div>
        <div class="row">
            <?php echo t('res.credits'); ?>: <?php echo human_num($Character->Data['credits'] ?? 0); ?> <BR />
            <?php echo t('res.qol_sub'); ?>: <?php echo (!empty($Character->Data['subscription_expires']) && strtotime($Character->Data['subscription_expires']) > time()) ? t('res.active') : t('res.none'); ?> <BR />
        </div>
        <div class="row">
            <?php echo t('res.rift'); ?>: <?php echo t('res.queued'); ?> <BR />
            <?php echo t('res.boss'); ?>: <?php echo t('res.queued'); ?> <BR />
        </div>
        </div>
    </div>
    <?php
    # Guest mode banner
    if (isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true) {
        echo '<div class="alert alert-warning">' . t('banner.guest', [
            'register_link' => '<a href="/register"><strong>' . t('banner.guest.register') . '</strong></a>',
            'login_link' => '<a href="/login">' . t('banner.guest.login') . '</a>',
        ]) . '</div>';
    }

    # Season / Perpetual mode banner
    if (isset($_SESSION['active_season_id'])) {
        $SeasonBanner = new Season();
        $SeasonBannerData = $SeasonBanner->GetSeasonById((int)$_SESSION['active_season_id']);
        if ($SeasonBannerData) {
            $days_left = max(0, (int)ceil((strtotime($SeasonBannerData['end_date']) - time()) / 86400));
            echo '<div class="alert alert-warning">' . t('banner.season', [
                'name' => htmlspecialchars($SeasonBannerData['name']),
                'days' => $days_left,
                'switch_link' => '<a href="/season-select">' . t('banner.switch_mode') . '</a>',
            ]) . '</div>';
        }
    } else {
        echo '<div class="alert alert-info">' . t('banner.perpetual', [
            'switch_link' => '<a href="/season-select">' . t('banner.switch_mode') . '</a>',
        ]) . '</div>';
    }

    # generic alerts for top of page
      if(isset($alert_success) && $alert_success != '') {
          echo '<div class="alert alert-success">' . htmlspecialchars($alert_success) . '</div>';
      }
      if(isset($alert_danger) && $alert_danger != '') {
          echo '<div class="alert alert-danger">' . htmlspecialchars($alert_danger) . '</div>';
      }

    ?>
    <BR />
