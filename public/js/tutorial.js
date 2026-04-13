/**
 * MultiverseIdle Tutorial System
 * Guides new players through each page on their first visit.
 * Progress is stored in localStorage keyed by user ID.
 */
(function () {
    'use strict';

    // ----------------------------------------------------------------
    // Step definitions per page
    // target: CSS selector to spotlight, or null for a centered modal
    // position: preferred tooltip placement relative to target
    // ----------------------------------------------------------------
    var PAGES = {
        welcome: [
            {
                target: null,
                title: 'Welcome to MultiverseIdle!',
                body: 'Your party automatically fights monsters every minute — even when you\'re offline! This quick tour covers the main features. Click <b>Next</b> to step through, or <b>Skip</b> to dismiss.',
                position: 'center'
            },
            {
                target: '.navbar',
                title: 'Navigation',
                body: '<b>Actions</b> leads to combat pages (Arena, Rifts, World Boss, Workers). <b>Items</b> covers gear management. Use the hamburger menu on mobile.',
                position: 'bottom'
            },
            {
                target: '.resources',
                title: 'Your Resources',
                body: 'Track your party <b>Level</b>, current <b>Arena Floor</b>, and four resources: Gold, Iron, Herbs, and Gems. Credits are the premium currency.',
                position: 'bottom'
            },
            {
                target: '#chat-toggle',
                title: 'Community Chat',
                body: 'Click to open the live chat. Join <b>Global</b>, ask questions in <b>Help</b>, or talk to your guild. Chat is read-only for guests.',
                position: 'top'
            }
        ],
        arena: [
            {
                target: 'article.main h1',
                title: 'The Arena',
                body: 'Your party automatically battles monsters on your set floor <b>every minute</b>. Win fights to earn XP, Gold, and other resources.',
                position: 'bottom'
            },
            {
                target: 'input[name="new_floor"]',
                title: 'Arena Floor',
                body: 'Enter the floor you want to fight on. Higher floors have stronger enemies — and much better loot! Raise the floor as your party gets stronger.',
                position: 'bottom'
            },
            {
                target: 'input[name="update_floor"]',
                title: 'Update Floor',
                body: 'Click here to save your new floor choice. Your party will start battling there on the next tick.',
                position: 'bottom'
            },
            {
                target: '.grid > div:last-child',
                title: 'Battle Log',
                body: 'Check here after battles to see what happened — wins, losses, and resource drops from your most recent fight.',
                position: 'top'
            }
        ],
        workers: [
            {
                target: 'article.main h1',
                title: 'Workers',
                body: 'Workers passively harvest resources every minute, even while you\'re away. The more you upgrade them, the more they produce.',
                position: 'bottom'
            },
            {
                target: 'select[name="resource"]',
                title: 'Choose a Resource',
                body: 'Pick which resource your workers harvest: <b>Gold, Iron, Herbs,</b> or <b>Gems</b>. You can change this any time.',
                position: 'bottom'
            },
            {
                target: 'input[name="hire_workers"]',
                title: 'Hire Workers',
                body: 'Spend Gold to hire more workers. More workers = higher yield per tick. This is one of the best early investments.',
                position: 'top'
            },
            {
                target: 'input[name="upgrade_speed"]',
                title: 'Speed Upgrades',
                body: 'Upgrade worker speed to increase how many harvests occur per tick. Costs Gold.',
                position: 'top'
            },
            {
                target: 'input[name="upgrade_intelligence"]',
                title: 'Intelligence Upgrades',
                body: 'Boost worker intelligence to accelerate their skill experience, unlocking higher yields over time.',
                position: 'top'
            }
        ],
        party: [
            {
                target: 'article.main h1',
                title: 'Your Party',
                body: 'Your party has two members: a <b>Frontline</b> fighter who faces enemies head-on, and a <b>Backline</b> supporter. Both level up through combat.',
                position: 'bottom'
            },
            {
                target: 'article.main h3:first-of-type',
                title: 'Frontline Member',
                body: 'Your primary combat unit. Stats grow every level based on their class. Keep their gear and skill gems updated!',
                position: 'bottom'
            },
            {
                target: 'select[name="class"]',
                title: 'Class Selection',
                body: 'Choose a class for your party member. Each class distributes stat points differently across <b>Strength</b>, <b>Dexterity</b>, <b>Health</b>, and <b>Wisdom</b> as they level up.',
                position: 'right'
            },
            {
                target: 'form[action="/party?update=frontline_gear"]',
                title: 'Equip Gear',
                body: 'Assign crafted weapons and armor to your party. Head to <b>Items → Craft</b> to make gear first, then favorite the pieces you want to equip here.',
                position: 'right'
            },
            {
                target: 'form[action="/party?update=frontline_skills"]',
                title: 'Skill Gems',
                body: 'Socket skill gems to grant your frontline special combat abilities. Gems are crafted under <b>Items → Craft</b>.',
                position: 'right'
            }
        ],
        craft: [
            {
                target: 'article.main h1',
                title: 'Crafting',
                body: 'Turn your gathered resources into powerful gear, useful potions, and rift stones. Crafted item power scales with your party level.',
                position: 'bottom'
            },
            {
                target: '.tab-nav',
                title: 'Crafting Tabs',
                body: 'Switch between three crafting categories: <b>Gear</b> for equipment, <b>Potions</b> for temporary buffs, and <b>Rift Stones</b> to unlock special dungeons.',
                position: 'bottom'
            },
            {
                target: '.tab-nav-item:nth-child(1)',
                title: 'Gear Tab',
                body: 'Craft weapons and armor. Pick an item type and two affixes — the gear\'s stat values scale with your current party level.',
                position: 'bottom'
            },
            {
                target: '.tab-nav-item:nth-child(2)',
                title: 'Potions Tab',
                body: 'Brew potions for temporary bonuses: boosted resource yields, extra XP, or faster stat gains. Only one potion can be active at a time.',
                position: 'bottom'
            },
            {
                target: '.tab-nav-item:nth-child(3)',
                title: 'Rift Stones Tab',
                body: 'Craft rift stones to queue up Rift delves — challenging multi-battle dungeons with big rewards if you survive.',
                position: 'bottom'
            }
        ],
        rifts: [
            {
                target: 'article.main h1',
                title: 'Rifts',
                body: 'Rifts are special dungeons that offer far better rewards than normal arena battles — but they\'re much harder. Prepare before diving in!',
                position: 'bottom'
            },
            {
                target: '.info-box',
                title: 'How Rifts Work',
                body: 'Your party runs 10 battles in sequence with full heals between each. Win enough to claim rewards — but it\'s <b>all-or-nothing</b> if you fail the run.',
                position: 'bottom'
            },
            {
                target: 'h2:first-of-type',
                title: 'Rift Queue',
                body: 'Queued rift stones are processed automatically by the hourly cron. You can queue multiple stones up to your slot limit.',
                position: 'bottom'
            },
            {
                target: 'h2:nth-of-type(2)',
                title: 'Available Rift Stones',
                body: 'Stones you\'ve crafted and are ready to queue. Head to <b>Items → Craft → Rift Stones</b> to make more.',
                position: 'bottom'
            }
        ],
        pvp: [
            {
                target: 'article.main h1',
                title: 'PvP — Player vs. Player',
                body: 'Challenge other players\' parties. Place wagers and fight for their resources. Your party fights automatically using current stats and gear.',
                position: 'bottom'
            }
        ],
        'world-boss': [
            {
                target: 'article.main h1',
                title: 'World Boss',
                body: 'Join server-wide raid battles against a massive boss. Contribute damage, earn rare rewards, and compete for top spots on the damage leaderboard.',
                position: 'bottom'
            }
        ],
        inventory: [
            {
                target: 'article.main h1',
                title: 'Inventory',
                body: 'View all your crafted gear here. <b>Favorite</b> items to make them available in the Party gear selection. Sell or discard what you no longer need.',
                position: 'bottom'
            }
        ],
        market: [
            {
                target: 'article.main h1',
                title: 'The Market',
                body: 'Buy and sell resources with other players. Post buy or sell orders and the market will match them automatically.',
                position: 'bottom'
            }
        ]
    };

    // ----------------------------------------------------------------
    // Storage helpers
    // ----------------------------------------------------------------
    function storageKey(userId) {
        return 'mi_tutorial_v1_' + userId;
    }

    function getState(userId) {
        try {
            var raw = localStorage.getItem(storageKey(userId));
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function saveState(userId, state) {
        try {
            localStorage.setItem(storageKey(userId), JSON.stringify(state));
        } catch (e) {}
    }

    function isCompleted(userId, page) {
        return getState(userId)[page] === true;
    }

    function markComplete(userId, page) {
        var state = getState(userId);
        state[page] = true;
        saveState(userId, state);
    }

    function skipAll(userId) {
        var state = {};
        Object.keys(PAGES).forEach(function (p) { state[p] = true; });
        saveState(userId, state);
    }

    // ----------------------------------------------------------------
    // Tutorial engine state
    // ----------------------------------------------------------------
    var domBuilt = false;
    var overlay, tooltip, titleEl, progressEl, bodyEl, nextBtn, skipBtn;
    var currentUserId, currentPage, currentSteps, currentStep;
    var pendingPage = null;
    var currentSpotlight = null;

    // ----------------------------------------------------------------
    // Build DOM (once)
    // ----------------------------------------------------------------
    function buildDOM() {
        if (domBuilt) return;
        domBuilt = true;

        overlay = document.createElement('div');
        overlay.id = 'tutorial-overlay';
        document.body.appendChild(overlay);

        tooltip = document.createElement('div');
        tooltip.id = 'tutorial-tooltip';
        tooltip.setAttribute('role', 'dialog');
        tooltip.setAttribute('aria-modal', 'true');
        tooltip.setAttribute('aria-labelledby', 'tutorial-title');
        tooltip.innerHTML = [
            '<div class="tutorial-tooltip__header">',
            '  <span class="tutorial-tooltip__title" id="tutorial-title"></span>',
            '  <span class="tutorial-tooltip__progress"></span>',
            '</div>',
            '<div class="tutorial-tooltip__body"></div>',
            '<div class="tutorial-tooltip__actions">',
            '  <button class="tutorial-btn tutorial-btn--skip" id="tutorial-skip">Skip Tutorial</button>',
            '  <button class="tutorial-btn tutorial-btn--next" id="tutorial-next">Next &#8250;</button>',
            '</div>'
        ].join('');
        document.body.appendChild(tooltip);

        titleEl    = tooltip.querySelector('.tutorial-tooltip__title');
        progressEl = tooltip.querySelector('.tutorial-tooltip__progress');
        bodyEl     = tooltip.querySelector('.tutorial-tooltip__body');
        nextBtn    = document.getElementById('tutorial-next');
        skipBtn    = document.getElementById('tutorial-skip');

        nextBtn.addEventListener('click', onNext);
        skipBtn.addEventListener('click', onSkip);

        // Allow Escape to skip
        document.addEventListener('keydown', function (e) {
            if (tooltip.style.display !== 'none' && tooltip.style.display !== '' &&
                (e.key === 'Escape' || e.keyCode === 27)) {
                onSkip();
            }
        });
    }

    // ----------------------------------------------------------------
    // Show a single step
    // ----------------------------------------------------------------
    function showStep(index) {
        currentStep = index;
        var step = currentSteps[index];
        var isLast = (index === currentSteps.length - 1);

        // Remove previous spotlight
        removeSpotlight();

        // Populate tooltip content
        titleEl.textContent = step.title;
        bodyEl.innerHTML    = step.body;
        progressEl.textContent = (index + 1) + ' / ' + currentSteps.length;
        nextBtn.innerHTML = isLast ? 'Done &#10003;' : 'Next &#8250;';

        // Show overlay + tooltip
        overlay.style.display = 'block';
        tooltip.style.display = 'block';

        if (!step.target) {
            // Centered modal — dim the overlay itself
            overlay.classList.add('tutorial-overlay--dim');
            positionCenter();
            nextBtn.focus();
            return;
        }

        overlay.classList.remove('tutorial-overlay--dim');

        var el = document.querySelector(step.target);
        if (!el) {
            // Element not on page — skip to next
            if (!isLast) {
                showStep(index + 1);
            } else {
                onComplete();
            }
            return;
        }

        // Apply spotlight
        currentSpotlight = el;
        el.classList.add('tutorial-spotlight');
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Position tooltip after scroll settles
        setTimeout(function () { positionNear(el, step.position); }, 380);
        nextBtn.focus();
    }

    function removeSpotlight() {
        if (currentSpotlight) {
            currentSpotlight.classList.remove('tutorial-spotlight');
            currentSpotlight = null;
        }
    }

    // ----------------------------------------------------------------
    // Tooltip positioning
    // ----------------------------------------------------------------
    function positionCenter() {
        tooltip.style.position  = 'fixed';
        tooltip.style.top       = '50%';
        tooltip.style.left      = '50%';
        tooltip.style.transform = 'translate(-50%, -50%)';
        tooltip.style.bottom    = 'auto';
        tooltip.style.right     = 'auto';
        tooltip.style.width     = Math.min(400, window.innerWidth * 0.9) + 'px';
    }

    function positionNear(el, preferred) {
        // On small screens always anchor to bottom of viewport
        if (window.innerWidth < 520) {
            positionMobile();
            return;
        }

        var rect = el.getBoundingClientRect();
        var vw   = window.innerWidth;
        var vh   = window.innerHeight;
        var GAP  = 14;
        var W    = Math.min(340, vw - 32);
        var H    = tooltip.offsetHeight || 170;

        tooltip.style.width     = W + 'px';
        tooltip.style.transform = 'none';
        tooltip.style.position  = 'fixed';

        var top, left;

        // Try preferred position first, fall back gracefully
        var posOrder = [preferred, 'bottom', 'top', 'right', 'left'];
        var placed = false;

        for (var i = 0; i < posOrder.length && !placed; i++) {
            var pos = posOrder[i];
            if (!pos) continue;
            if (pos === 'bottom' && rect.bottom + GAP + H <= vh) {
                top   = rect.bottom + GAP;
                left  = clamp(rect.left, 8, vw - W - 8);
                placed = true;
            } else if (pos === 'top' && rect.top - GAP - H >= 0) {
                top   = rect.top - GAP - H;
                left  = clamp(rect.left, 8, vw - W - 8);
                placed = true;
            } else if (pos === 'right' && rect.right + GAP + W <= vw) {
                top   = clamp(rect.top, 8, vh - H - 8);
                left  = rect.right + GAP;
                placed = true;
            } else if (pos === 'left' && rect.left - GAP - W >= 0) {
                top   = clamp(rect.top, 8, vh - H - 8);
                left  = rect.left - GAP - W;
                placed = true;
            }
        }

        if (!placed) {
            // Last resort: centre vertically
            top  = clamp((vh - H) / 2, 8, vh - H - 8);
            left = clamp(rect.left, 8, vw - W - 8);
        }

        tooltip.style.top    = top + 'px';
        tooltip.style.left   = left + 'px';
        tooltip.style.bottom = 'auto';
        tooltip.style.right  = 'auto';
    }

    function positionMobile() {
        // Fixed to bottom, above chat widget (~40px)
        tooltip.style.position  = 'fixed';
        tooltip.style.bottom    = '50px';
        tooltip.style.top       = 'auto';
        tooltip.style.left      = '12px';
        tooltip.style.right     = '12px';
        tooltip.style.width     = 'auto';
        tooltip.style.transform = 'none';
    }

    function clamp(val, min, max) {
        return Math.max(min, Math.min(max, val));
    }

    // ----------------------------------------------------------------
    // Event handlers
    // ----------------------------------------------------------------
    function onNext() {
        if (currentStep >= currentSteps.length - 1) {
            onComplete();
        } else {
            showStep(currentStep + 1);
        }
    }

    function onSkip() {
        skipAll(currentUserId);
        teardown();
    }

    function onComplete() {
        markComplete(currentUserId, currentPage);
        teardown();

        // Chain: after Welcome, run the page-specific tutorial if pending
        if (currentPage === 'welcome' && pendingPage) {
            var pp = pendingPage;
            pendingPage = null;
            if (PAGES[pp] && !isCompleted(currentUserId, pp)) {
                setTimeout(function () { runPage(pp); }, 400);
            }
        }
    }

    function teardown() {
        removeSpotlight();
        overlay.style.display  = 'none';
        tooltip.style.display  = 'none';
    }

    // ----------------------------------------------------------------
    // Start a page tutorial
    // ----------------------------------------------------------------
    function runPage(page) {
        currentPage  = page;
        currentSteps = PAGES[page];
        showStep(0);
    }

    // ----------------------------------------------------------------
    // Public API — called from game-header.php
    // ----------------------------------------------------------------
    window.MITutorial = {
        init: function (userId, pageName) {
            currentUserId = String(userId);

            buildDOM();

            var welcomeDone = isCompleted(currentUserId, 'welcome');
            var pageDone    = !pageName || !PAGES[pageName] || isCompleted(currentUserId, pageName);

            if (!welcomeDone) {
                // Store the page to chain after welcome
                pendingPage = (!pageDone && pageName) ? pageName : null;
                runPage('welcome');
            } else if (!pageDone) {
                runPage(pageName);
            }
        },

        // Expose for the "Restart Tutorial" settings link
        reset: function (userId) {
            try { localStorage.removeItem(storageKey(String(userId))); } catch (e) {}
        }
    };
}());
