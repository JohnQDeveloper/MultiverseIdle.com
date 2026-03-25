<!-- Header Navigation -->
      <div class="navbar">
        <button class="hamburger" aria-label="Toggle menu">
          <span></span>
          <span></span>
          <span></span>
        </button>

        <div class="container">
          <nav>
            <ul class="nav-menu">
              <li><a href="#"><img src="/img/logo.png" height="64"></a></li>
              <li class="dropdown">
                <a href="#">Actions</a>
                <ul class="dropdown-content">
                  <li><a href="/arena">2v2 Arena</a></li>
                  <li><a href="/rifts">Rifts</a></li>
                  <li><a href="/world-boss">World Boss</a></li>
                  <li><a href="/workers">Workers</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#">Items</a>
                <ul class="dropdown-content">
                  <li><a href="/craft">Craft</a></li>
                  <li><a href="/inventory">Inventory</a></li>
                  <li><a href="/party">Party</a></li>
                </ul>
              </li>
              <?php if (!isset($_SESSION['guest_mode']) || $_SESSION['guest_mode'] !== true): ?>
              <li><a href="/market">Market</a></li>
              <?php endif; ?>
              <li class="dropdown">
                <a href="/guilds">Guilds</a>
                <ul class="dropdown-content">
                  <li><a href="/guilds">Guild</a></li>
                  <li><a href="/guild-bank">Guild Bank</a></li>
                  <li><a href="/guild-buildings">Guild Buildings</a></li>
                </ul>
              </li>
              <?php if (!isset($_SESSION['guest_mode']) || $_SESSION['guest_mode'] !== true): ?>
              <li><a href="/store">Store</a></li>
              <?php endif; ?>
              <li><a href="/leaderboard">Leaderboard</a></li>
              <!--<li><a href="/season-select"><?php echo (isset($_SESSION['active_season_id'])) ? 'Season' : 'Perpetual'; ?></a></li>-->
              <li><a href="/logout">Logout</a></li>
              <li> ::: </li>
              <li><a href="https://discord.gg/KrD7hGuDyb">Discord</a></li>
              <li><a href="https://github.com/JohnQDeveloper/MultiverseIdle.com/issues">Feedback</a></li>
              <li><a href="/log">Log</a></li>
              <li><a href="/settings">Settings</a></li>
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
        <span class="chat-toggle-label">&#128172; Chat</span>
        <span id="chat-unread" class="chat-unread" style="display:none"></span>
        <span class="chat-toggle-arrow">&#9650;</span>
    </button>

    <!-- Collapsible body -->
    <div id="chat-widget-body" class="chat-widget-body" hidden>

        <!-- Channel tabs -->
        <div class="chat-tabs" role="tablist">
            <button class="chat-tab active" data-channel="global" role="tab">Global</button>
            <button class="chat-tab" data-channel="help" role="tab">Help</button>
            <?php if ($_chatGuildId !== ''): ?>
            <button class="chat-tab" data-channel="guild:<?php echo htmlspecialchars($_chatGuildId, ENT_QUOTES, 'UTF-8'); ?>" role="tab">Guild</button>
            <?php else: ?>
            <button class="chat-tab chat-tab--disabled" disabled title="Join a guild to use guild chat">Guild</button>
            <?php endif; ?>
            <?php if ($_chatIsLoggedIn && !$_chatIsGuest): ?>
            <button class="chat-tab" data-channel="dm" role="tab">DM</button>
            <?php endif; ?>
        </div>

        <!-- DM: conversation list (shown when DM tab is active, no conversation open) -->
        <?php if ($_chatIsLoggedIn && !$_chatIsGuest): ?>
        <div id="dm-conversations" class="dm-conversations" style="display:none;">
            <div id="dm-conversation-list" class="dm-conversation-list">
                <div class="chat-loading">Loading&hellip;</div>
            </div>
            <div class="dm-new-row">
                <input type="text" id="dm-new-username" class="chat-input"
                       placeholder="Start new DM&hellip;" maxlength="50" autocomplete="off" aria-label="Username to DM">
                <button type="button" id="dm-new-btn" class="chat-send-btn">Open</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- DM: chat header with back button (shown when a DM conversation is open) -->
        <div id="dm-chat-header" class="dm-chat-header" style="display:none;">
            <button type="button" id="dm-back-btn" class="dm-back-btn">&#8592; Back</button>
            <span id="dm-partner-name" class="dm-partner-name"></span>
        </div>

        <!-- Messages -->
        <div id="chat-messages" class="chat-messages" aria-live="polite">
            <div class="chat-loading">Loading&hellip;</div>
        </div>

        <!-- Mute notice -->
        <div id="chat-muted-banner" class="chat-muted-banner" style="display:none;">
            Muted: <span id="chat-muted-reason"></span>
        </div>

        <!-- Slash command hint -->
        <?php if (!$_chatIsGuest): ?>
        <div id="chat-slash-hint" class="chat-slash-hint" style="display:none;" role="listbox" aria-label="Slash command suggestions"></div>
        <?php endif; ?>

        <!-- Input -->
        <?php if (!$_chatIsGuest): ?>
        <form id="chat-form" class="chat-input-row" autocomplete="off">
            <input type="text" id="chat-input" class="chat-input"
                   placeholder="Message&hellip;" maxlength="500" aria-label="Chat message">
            <button type="submit" class="chat-send-btn">Send</button>
        </form>
        <?php else: ?>
        <p class="chat-guest-notice"><a href="/register">Register</a> or <a href="/login">login</a> to chat.</p>
        <?php endif; ?>

        <!-- Mod panel -->
        <?php if ($_chatIsMod): ?>
        <div id="chat-mod-panel" class="chat-mod-panel">
            <details>
                <summary class="chat-mod-summary">Moderation</summary>
                <form id="mod-form" class="chat-mod-form">
                    <input type="text" id="mod-user-id" placeholder="User ID" class="mod-input" style="width:80px;" required>
                    <select id="mod-action" class="mod-input">
                        <option value="promote">Promote</option>
                        <option value="demote">Demote</option>
                        <option value="mute">Mute</option>
                        <option value="unmute">Unmute</option>
                    </select>
                    <select id="mod-duration" class="mod-input">
                        <option value="3600">1 hour</option>
                        <option value="86400">24 hours</option>
                        <option value="604800">7 days</option>
                        <option value="-1">Permanent</option>
                    </select>
                    <input type="text" id="mod-reason" placeholder="Reason" class="mod-input" style="width:120px;">
                    <button type="submit" class="chat-send-btn chat-send-btn--danger">Apply</button>
                </form>
                <div id="mod-result" class="mod-result" style="display:none;"></div>
            </details>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="/js/chat.js"></script>

<main class="container">
    <div class="wrapper">
    <div class="resources">
        <div class="row">
            Level: <?php echo htmlspecialchars($Character->Data['party_json']['members']['frontline']['level']); ?> <BR />
            <!-- XP: <?php echo htmlspecialchars($Character->Data['party_json']['members']['frontline']['xp']); ?> <BR /> -->
            Arena: <?php echo htmlspecialchars($Character->Data['arena_floor']); ?> <BR />
        </div>
        <div class="row">
            Gold: <?php echo human_num($Character->Data['gold']); ?> <BR />
            Iron: <?php echo human_num($Character->Data['iron']); ?> <BR />
        </div>
        <div class="row">
            Herbs: <?php echo human_num($Character->Data['herbs']); ?> <BR />
            Gems: <?php echo human_num($Character->Data['gems']); ?> <BR />
        </div>
        <div class="row">
            Credits: <?php echo human_num($Character->Data['credits'] ?? 0); ?> <BR />
            QoL Sub: <?php echo (!empty($Character->Data['subscription_expires']) && strtotime($Character->Data['subscription_expires']) > time()) ? 'Active' : 'None'; ?> <BR />
        </div>
        <div class="row">
            Rift: Queued <BR />
            Boss: Queued <BR />
        </div>
        </div>
    </div>
    <?php
    # Guest mode banner
    if (isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true) {
        echo '<div class="alert alert-warning">You are playing as a guest. Your progress is only saved in this browser session. <a href="/register"><strong>Register now</strong></a> to keep your progress permanently, or <a href="/login">login</a> to an existing account.</div>';
    }

    # Season / Perpetual mode banner
    if (isset($_SESSION['active_season_id'])) {
        $SeasonBanner = new Season();
        $SeasonBannerData = $SeasonBanner->GetSeasonById((int)$_SESSION['active_season_id']);
        if ($SeasonBannerData) {
            $days_left = max(0, (int)ceil((strtotime($SeasonBannerData['end_date']) - time()) / 86400));
            echo '<div class="alert alert-warning">&#9733; Season mode: <strong>' . htmlspecialchars($SeasonBannerData['name']) . '</strong> &mdash; ' . $days_left . ' days remaining. <a href="/season-select">Switch mode</a></div>';
        }
    } else {
        echo '<div class="alert alert-info">&#9670; Perpetual mode &mdash; progress never resets. <a href="/season-select">Switch mode</a></div>';
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
