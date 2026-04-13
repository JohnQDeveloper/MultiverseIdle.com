/**
 * MultiverseIdle Tutorial System
 * Guides new players through each page on their first visit.
 * Progress is stored in localStorage keyed by user ID.
 */
(function () {
    'use strict';

    function lang(key) {
        if (window.MI_LANG && window.MI_LANG[key]) {
            return window.MI_LANG[key];
        }

        return key;
    }

    // ----------------------------------------------------------------
    // Step definitions per page
    // target: CSS selector to spotlight, or null for a centered modal
    // position: preferred tooltip placement relative to target
    // ----------------------------------------------------------------
    var PAGES = {
        welcome: [
            {
                target: null,
                title: lang('tutorial.welcome.1.title'),
                body: lang('tutorial.welcome.1.body'),
                position: 'center'
            },
            {
                target: '.navbar',
                title: lang('tutorial.welcome.2.title'),
                body: lang('tutorial.welcome.2.body'),
                position: 'bottom'
            },
            {
                target: '.resources',
                title: lang('tutorial.welcome.3.title'),
                body: lang('tutorial.welcome.3.body'),
                position: 'bottom'
            },
            {
                target: '#chat-toggle',
                title: lang('tutorial.welcome.4.title'),
                body: lang('tutorial.welcome.4.body'),
                position: 'top'
            }
        ],
        arena: [
            {
                target: 'article.main h1',
                title: lang('tutorial.arena.1.title'),
                body: lang('tutorial.arena.1.body'),
                position: 'bottom'
            },
            {
                target: 'input[name="new_floor"]',
                title: lang('tutorial.arena.2.title'),
                body: lang('tutorial.arena.2.body'),
                position: 'bottom'
            },
            {
                target: 'input[name="update_floor"]',
                title: lang('tutorial.arena.3.title'),
                body: lang('tutorial.arena.3.body'),
                position: 'bottom'
            },
            {
                target: '.grid > div:last-child',
                title: lang('tutorial.arena.4.title'),
                body: lang('tutorial.arena.4.body'),
                position: 'top'
            }
        ],
        workers: [
            {
                target: 'article.main h1',
                title: lang('tutorial.workers.1.title'),
                body: lang('tutorial.workers.1.body'),
                position: 'bottom'
            },
            {
                target: 'select[name="resource"]',
                title: lang('tutorial.workers.2.title'),
                body: lang('tutorial.workers.2.body'),
                position: 'bottom'
            },
            {
                target: 'input[name="hire_workers"]',
                title: lang('tutorial.workers.3.title'),
                body: lang('tutorial.workers.3.body'),
                position: 'top'
            },
            {
                target: 'input[name="upgrade_speed"]',
                title: lang('tutorial.workers.4.title'),
                body: lang('tutorial.workers.4.body'),
                position: 'top'
            },
            {
                target: 'input[name="upgrade_intelligence"]',
                title: lang('tutorial.workers.5.title'),
                body: lang('tutorial.workers.5.body'),
                position: 'top'
            }
        ],
        party: [
            {
                target: 'article.main h1',
                title: lang('tutorial.party.1.title'),
                body: lang('tutorial.party.1.body'),
                position: 'bottom'
            },
            {
                target: 'article.main h3:first-of-type',
                title: lang('tutorial.party.2.title'),
                body: lang('tutorial.party.2.body'),
                position: 'bottom'
            },
            {
                target: 'select[name="class"]',
                title: lang('tutorial.party.3.title'),
                body: lang('tutorial.party.3.body'),
                position: 'right'
            },
            {
                target: 'form[action="/party?update=frontline_gear"]',
                title: lang('tutorial.party.4.title'),
                body: lang('tutorial.party.4.body'),
                position: 'right'
            },
            {
                target: 'form[action="/party?update=frontline_skills"]',
                title: lang('tutorial.party.5.title'),
                body: lang('tutorial.party.5.body'),
                position: 'right'
            }
        ],
        craft: [
            {
                target: 'article.main h1',
                title: lang('tutorial.craft.1.title'),
                body: lang('tutorial.craft.1.body'),
                position: 'bottom'
            },
            {
                target: '.tab-nav',
                title: lang('tutorial.craft.2.title'),
                body: lang('tutorial.craft.2.body'),
                position: 'bottom'
            },
            {
                target: '.tab-nav-item:nth-child(1)',
                title: lang('tutorial.craft.3.title'),
                body: lang('tutorial.craft.3.body'),
                position: 'bottom'
            },
            {
                target: '.tab-nav-item:nth-child(2)',
                title: lang('tutorial.craft.4.title'),
                body: lang('tutorial.craft.4.body'),
                position: 'bottom'
            },
            {
                target: '.tab-nav-item:nth-child(3)',
                title: lang('tutorial.craft.5.title'),
                body: lang('tutorial.craft.5.body'),
                position: 'bottom'
            }
        ],
        rifts: [
            {
                target: 'article.main h1',
                title: lang('tutorial.rifts.1.title'),
                body: lang('tutorial.rifts.1.body'),
                position: 'bottom'
            },
            {
                target: '.info-box',
                title: lang('tutorial.rifts.2.title'),
                body: lang('tutorial.rifts.2.body'),
                position: 'bottom'
            },
            {
                target: 'h2:first-of-type',
                title: lang('tutorial.rifts.3.title'),
                body: lang('tutorial.rifts.3.body'),
                position: 'bottom'
            },
            {
                target: 'h2:nth-of-type(2)',
                title: lang('tutorial.rifts.4.title'),
                body: lang('tutorial.rifts.4.body'),
                position: 'bottom'
            }
        ],
        pvp: [
            {
                target: 'article.main h1',
                title: lang('tutorial.pvp.1.title'),
                body: lang('tutorial.pvp.1.body'),
                position: 'bottom'
            }
        ],
        'world-boss': [
            {
                target: 'article.main h1',
                title: lang('tutorial.world_boss.1.title'),
                body: lang('tutorial.world_boss.1.body'),
                position: 'bottom'
            }
        ],
        inventory: [
            {
                target: 'article.main h1',
                title: lang('tutorial.inventory.1.title'),
                body: lang('tutorial.inventory.1.body'),
                position: 'bottom'
            }
        ],
        market: [
            {
                target: 'article.main h1',
                title: lang('tutorial.market.1.title'),
                body: lang('tutorial.market.1.body'),
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
            '  <button class="tutorial-btn tutorial-btn--skip" id="tutorial-skip">' + lang('tutorial.button.skip') + '</button>',
            '  <button class="tutorial-btn tutorial-btn--next" id="tutorial-next">' + lang('tutorial.button.next') + ' &#8250;</button>',
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
        nextBtn.innerHTML = isLast
            ? lang('tutorial.button.done') + ' &#10003;'
            : lang('tutorial.button.next') + ' &#8250;';

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
