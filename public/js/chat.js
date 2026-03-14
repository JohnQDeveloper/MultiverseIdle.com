/* Floating chat widget — vanilla JS, no dependencies */
(function () {
    'use strict';

    const POLL_INTERVAL_MS  = 3000;
    const MAX_DISPLAY_MSGS  = 150;
    const STORAGE_KEY       = 'chat_open';

    const widget    = document.getElementById('chat-widget');
    if (!widget) return;

    const csrf      = widget.dataset.csrf;
    const userId    = parseInt(widget.dataset.userId, 10) || 0;
    const isMod     = widget.dataset.isMod === '1';
    const isGuest   = widget.dataset.isGuest === '1';

    const toggleBtn  = document.getElementById('chat-toggle');
    const body       = document.getElementById('chat-widget-body');
    const msgArea    = document.getElementById('chat-messages');
    const form       = document.getElementById('chat-form');
    const input      = document.getElementById('chat-input');
    const muteBanner = document.getElementById('chat-muted-banner');
    const muteReason = document.getElementById('chat-muted-reason');
    const unreadBadge = document.getElementById('chat-unread');
    const modForm    = document.getElementById('mod-form');
    const modResult  = document.getElementById('mod-result');

    let currentChannel = 'global';
    let lastId         = 0;
    let pollTimer      = null;
    let isMuted        = false;
    let unreadCount    = 0;
    let isOpen         = sessionStorage.getItem(STORAGE_KEY) === '1';

    // -----------------------------------------------------------------------
    // Open / close
    // -----------------------------------------------------------------------
    function openWidget() {
        isOpen = true;
        body.hidden = false;
        toggleBtn.setAttribute('aria-expanded', 'true');
        toggleBtn.querySelector('.chat-toggle-arrow').textContent = '\u25BC'; // ▼
        sessionStorage.setItem(STORAGE_KEY, '1');
        clearUnread();
        if (!pollTimer) {
            fetchMessages();
            startPolling();
        }
    }

    function closeWidget() {
        isOpen = false;
        body.hidden = true;
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.querySelector('.chat-toggle-arrow').textContent = '\u25B2'; // ▲
        sessionStorage.setItem(STORAGE_KEY, '0');
        stopPolling();
    }

    toggleBtn.addEventListener('click', function () {
        if (isOpen) { closeWidget(); } else { openWidget(); }
    });

    // Restore previous open state
    if (isOpen) { openWidget(); }

    // -----------------------------------------------------------------------
    // Unread badge
    // -----------------------------------------------------------------------
    function addUnread(count) {
        if (isOpen) return;
        unreadCount += count;
        if (unreadBadge) {
            unreadBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            unreadBadge.style.display = '';
        }
    }

    function clearUnread() {
        unreadCount = 0;
        if (unreadBadge) unreadBadge.style.display = 'none';
    }

    // -----------------------------------------------------------------------
    // Channel tabs
    // -----------------------------------------------------------------------
    document.querySelectorAll('.chat-tab:not([disabled])').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.chat-tab').forEach(function (t) {
                t.classList.remove('active');
            });
            tab.classList.add('active');
            currentChannel = tab.dataset.channel;
            lastId = 0;
            msgArea.innerHTML = '<div class="chat-loading">Loading\u2026</div>';
            stopPolling();
            fetchMessages();
            startPolling();
        });
    });

    // -----------------------------------------------------------------------
    // Render a single message row
    // -----------------------------------------------------------------------
    function renderMessage(msg) {
        const div = document.createElement('div');
        div.className = 'chat-msg';
        div.dataset.userId = msg.user_id;

        const time  = new Date(msg.timestamp * 1000)
                        .toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const badge = msg.is_mod
            ? '<span class="chat-badge chat-badge--mod">[Staff]</span>'
            : '';
        const modBtn = (isMod && msg.user_id !== userId && msg.user_id > 0)
            ? '<button class="chat-mod-quick" data-uid="' + msg.user_id + '" title="Moderate">\u22EE</button>'
            : '';

        div.innerHTML =
            '<span class="chat-time">' + escHtml(time) + '</span> ' +
            badge + (badge ? ' ' : '') +
            '<span class="chat-username">' + escHtml(msg.username) + '</span>: ' +
            '<span class="chat-text">' + escHtml(msg.message) + '</span>' +
            modBtn;

        return div;
    }

    function appendMessages(messages) {
        if (!messages || messages.length === 0) return;

        const atBottom = msgArea.scrollHeight - msgArea.scrollTop - msgArea.clientHeight < 40;
        const frag = document.createDocumentFragment();
        messages.forEach(function (msg) { frag.appendChild(renderMessage(msg)); });
        msgArea.appendChild(frag);

        while (msgArea.children.length > MAX_DISPLAY_MSGS) {
            msgArea.removeChild(msgArea.firstChild);
        }

        if (atBottom || !isOpen) {
            msgArea.scrollTop = msgArea.scrollHeight;
        }

        if (!isOpen) addUnread(messages.length);
    }

    // -----------------------------------------------------------------------
    // Fetch
    // -----------------------------------------------------------------------
    function fetchMessages() {
        const url = '/api/chat_fetch?channel=' + encodeURIComponent(currentChannel) +
                    '&since_id=' + lastId + '&limit=50';

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                if (lastId === 0) {
                    msgArea.innerHTML = '';
                }

                if (data.messages && data.messages.length > 0) {
                    appendMessages(data.messages);
                    lastId = data.last_id;
                } else if (lastId === 0) {
                    msgArea.innerHTML = '<div class="chat-empty">No messages yet.</div>';
                }

                // Mute status
                if (data.muted) {
                    isMuted = true;
                    const until = data.muted.until;
                    let label = until === -1 ? '(permanent)' :
                        'until ' + new Date(until * 1000).toLocaleString();
                    if (data.muted.reason) label += ' \u2014 ' + escHtml(data.muted.reason);
                    if (muteReason) muteReason.innerHTML = label;
                    if (muteBanner) muteBanner.style.display = '';
                    if (input) input.disabled = true;
                } else {
                    isMuted = false;
                    if (muteBanner) muteBanner.style.display = 'none';
                    if (input && !isGuest) input.disabled = false;
                }
            })
            .catch(function () { /* silent — network blip */ });
    }

    // -----------------------------------------------------------------------
    // Send
    // -----------------------------------------------------------------------
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!input || !input.value.trim() || isMuted || isGuest) return;

            const text = input.value.trim();
            input.value = '';
            input.disabled = true;

            const body = new URLSearchParams({
                csrf_token: csrf,
                channel:    currentChannel,
                message:    text,
            });

            fetch('/api/chat_send', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:        body.toString(),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    input.disabled = false;
                    input.focus();
                    if (data.success && data.message) {
                        const placeholder = msgArea.querySelector('.chat-empty, .chat-loading');
                        if (lastId === 0 && placeholder) msgArea.innerHTML = '';
                        appendMessages([data.message]);
                        lastId = data.message.id;
                    } else if (!data.success) {
                        showError(data.error || 'Failed to send');
                    }
                })
                .catch(function () {
                    input.disabled = false;
                    showError('Network error.');
                });
        });
    }

    // -----------------------------------------------------------------------
    // Mod quick-action (delegated click)
    // -----------------------------------------------------------------------
    msgArea.addEventListener('click', function (e) {
        const btn = e.target.closest('.chat-mod-quick');
        if (!btn) return;
        const uid = btn.dataset.uid;
        const uidInput = document.getElementById('mod-user-id');
        if (uidInput) {
            uidInput.value = uid;
            // Open the details element if closed
            const details = document.querySelector('#chat-mod-panel details');
            if (details) details.open = true;
        }
    });

    // -----------------------------------------------------------------------
    // Mod form
    // -----------------------------------------------------------------------
    if (modForm) {
        const durationSel = document.getElementById('mod-duration');
        const actionSel   = document.getElementById('mod-action');

        function toggleDuration() {
            if (durationSel && actionSel) {
                durationSel.style.display = actionSel.value === 'mute' ? '' : 'none';
            }
        }
        if (actionSel) {
            actionSel.addEventListener('change', toggleDuration);
            toggleDuration();
        }

        modForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const targetId = (document.getElementById('mod-user-id') || {}).value || '';
            const action   = (document.getElementById('mod-action') || {}).value || '';
            const duration = (document.getElementById('mod-duration') || {}).value || '3600';
            const reason   = ((document.getElementById('mod-reason') || {}).value || '').trim();

            if (!targetId) return;

            const payload = new URLSearchParams({
                csrf_token: csrf,
                action,
                user_id:  targetId,
                duration,
                reason,
            });

            fetch('/api/chat_moderate', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:        payload.toString(),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!modResult) return;
                    modResult.className = 'mod-result ' + (data.success ? 'mod-result--ok' : 'mod-result--err');
                    modResult.textContent = data.message || data.error || '';
                    modResult.style.display = '';
                    setTimeout(function () { modResult.style.display = 'none'; }, 4000);
                })
                .catch(function () {
                    if (modResult) {
                        modResult.className = 'mod-result mod-result--err';
                        modResult.textContent = 'Network error';
                        modResult.style.display = '';
                    }
                });
        });
    }

    // -----------------------------------------------------------------------
    // Polling helpers
    // -----------------------------------------------------------------------
    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(fetchMessages, POLL_INTERVAL_MS);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopPolling();
        } else if (isOpen) {
            fetchMessages();
            startPolling();
        }
    });

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showError(msg) {
        const el = document.createElement('div');
        el.className = 'chat-error';
        el.textContent = msg;
        msgArea.appendChild(el);
        msgArea.scrollTop = msgArea.scrollHeight;
        setTimeout(function () { el.remove(); }, 5000);
    }
}());
