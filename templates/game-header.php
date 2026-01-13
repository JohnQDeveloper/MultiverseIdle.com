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
              <li><a href="#"><img src="./img/logo.png" height="64"></a></li>
              <li class="dropdown">
                <a href="#">Actions</a>
                <ul class="dropdown-content">
                  <li><a href="/arena">2v2 Arena</a></li>
                  <li><a href="/rift-delves">Rift Delves</a></li>
                  <li><a href="/world-boss">World Boss</a></li>
                </ul>
              </li>
              <li><a href="/craft">Craft</a></li>
              <li><a href="/market">Market</a></li>
              <li><a href="/party">Party</a></li>
              <li><a href="/workers">Workers</a></li>
              <li><a href="/logout">Logout</a></li>
              <li> ::: </li>
              <li><a href="https://discord.gg/KrD7hGuDyb">Discord</a></li>
              <li><a href="https://github.com/JohnQDeveloper/MultiverseIdle.com/issues">Feedback</a></li>
              <li><a href="/log">Game Log</a></li>
              <li><a href="/settings">Settings</a></li>
            </ul>
          </nav>

        </div>
      </div>

<main class="container">
    <div class="wrapper">
    <div class="resources">
        <div class="row">
            Level: <?php echo htmlspecialchars($Character->Data['level']); ?> <BR />
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
            Rift: Queued <BR />
            Boss: Queued <BR />
        </div>
        </div>
    </div>
    <?php
    # generic alerts for top of page
      if(isset($alert_success) && $alert_success != '') {
          echo '<div class="alert alert-success">' . htmlspecialchars($alert_success) . '</div>';
      }
      if(isset($alert_danger) && $alert_danger != '') {
          echo '<div class="alert alert-danger">' . htmlspecialchars($alert_danger) . '</div>';
      }

    ?>
    <BR />
