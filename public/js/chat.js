/* Floating chat widget — vanilla JS, no dependencies */
(function () {
    'use strict';

    const POLL_INTERVAL_MS  = 3000;
    const MAX_DISPLAY_MSGS  = 150;
    const STORAGE_KEY       = 'chat_open';

    const SLASH_COMMANDS = [
        {
            cmd:    '/wire',
            syntax: '/wire <username> <amount> <commodity>',
            desc:   'Wire resources to another player  (commodities: gold, iron, herbs, gems)',
        },
    ];

    const widget    = document.getElementById('chat-widget');
    if (!widget) return;

    const csrf      = widget.dataset.csrf;
    const userId    = parseInt(widget.dataset.userId, 10) || 0;
    const isMod     = widget.dataset.isMod === '1';
    const isGuest   = widget.dataset.isGuest === '1';
    const isSeason  = widget.dataset.isSeason === '1';

    const toggleBtn      = document.getElementById('chat-toggle');
    const body           = document.getElementById('chat-widget-body');
    const msgArea        = document.getElementById('chat-messages');
    const form           = document.getElementById('chat-form');
    const input          = document.getElementById('chat-input');
    const muteBanner     = document.getElementById('chat-muted-banner');
    const muteReason     = document.getElementById('chat-muted-reason');
    const unreadBadge    = document.getElementById('chat-unread');
    const modForm        = document.getElementById('mod-form');
    const modResult      = document.getElementById('mod-result');
    const slashHint      = document.getElementById('chat-slash-hint');

    // DM elements (null-safe — only present for logged-in non-guests)
    const dmConversations = document.getElementById('dm-conversations');
    const dmConvList      = document.getElementById('dm-conversation-list');
    const dmNewUsername   = document.getElementById('dm-new-username');
    const dmNewBtn        = document.getElementById('dm-new-btn');
    const dmChatHeader    = document.getElementById('dm-chat-header');
    const dmBackBtn       = document.getElementById('dm-back-btn');
    const dmPartnerNameEl = document.getElementById('dm-partner-name');

    let currentChannel    = 'global';
    let lastId            = 0;
    let pollTimer         = null;
    let isMuted           = false;
    let unreadCount       = 0;
    let isOpen            = sessionStorage.getItem(STORAGE_KEY) === '1';
    let slashSelectIndex  = -1;


    // -----------------------------------------------------------------------
    // Open / close
    // -----------------------------------------------------------------------
    function openWidget() {
        isOpen = true;
        body.hidden = false;
        toggleBtn.setAttribute('aria-expanded', 'true');
        toggleBtn.querySelector('.chat-toggle-arrow').textContent = '▼';
        sessionStorage.setItem(STORAGE_KEY, '1');
        clearUnread();
        if (!pollTimer && currentChannel !== 'dm') {
            fetchMessages();
            startPolling();
        }
    }

    function closeWidget() {
        isOpen = false;
        body.hidden = true;
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.querySelector('.chat-toggle-arrow').textContent = '▲';
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
    // Channel / DM view switching helpers
    // -----------------------------------------------------------------------
    function showChannelView() {
        if (dmConversations) dmConversations.style.display = 'none';
        if (dmChatHeader)    dmChatHeader.style.display    = 'none';
        msgArea.style.display = '';
        if (form) form.style.display = '';
    }

    function showDMListView() {
        stopPolling();
        currentChannel = 'dm';

        if (dmConversations) dmConversations.style.display = 'flex';
        if (dmChatHeader)    dmChatHeader.style.display    = 'none';
        msgArea.style.display = 'none';
        if (form) form.style.display = 'none';
        if (muteBanner) muteBanner.style.display = 'none';
    }

    function openDMChat(partnerId, partnerName) {
        const lo = Math.min(userId, partnerId);
        const hi = Math.max(userId, partnerId);
        currentChannel = 'dm:' + lo + ':' + hi;
        lastId = 0;

        if (dmConversations) dmConversations.style.display = 'none';
        if (dmChatHeader) {
            dmChatHeader.style.display = '';
            if (dmPartnerNameEl) dmPartnerNameEl.textContent = partnerName;
        }
        msgArea.style.display = '';
        if (form) form.style.display = '';

        msgArea.innerHTML = '<div class="chat-loading">Loading…</div>';
        stopPolling();
        fetchMessages();
        startPolling();
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

            const channel = tab.dataset.channel;

            if (channel === 'dm') {
                showDMListView();
                fetchDMConversations();
            } else {
                showChannelView();
                currentChannel = channel;
                lastId = 0;
                msgArea.innerHTML = '<div class="chat-loading">Loading…</div>';
                stopPolling();
                fetchMessages();
                startPolling();
            }
        });
    });

    // -----------------------------------------------------------------------
    // DM: back button
    // -----------------------------------------------------------------------
    if (dmBackBtn) {
        dmBackBtn.addEventListener('click', function () {
            stopPolling();
            showDMListView();
            fetchDMConversations();

            // Keep the DM tab highlighted
            document.querySelectorAll('.chat-tab').forEach(function (t) { t.classList.remove('active'); });
            const dmTab = document.querySelector('.chat-tab[data-channel="dm"]');
            if (dmTab) dmTab.classList.add('active');
        });
    }

    // -----------------------------------------------------------------------
    // DM: fetch conversations list
    // -----------------------------------------------------------------------
    function fetchDMConversations() {
        if (dmConvList) {
            dmConvList.innerHTML = '<div class="chat-loading">Loading…</div>';
        }

        fetch('/api/chat_dm_conversations', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!dmConvList) return;
                if (!data.success) {
                    dmConvList.innerHTML = '<div class="chat-error">Could not load conversations.</div>';
                    return;
                }
                if (!data.conversations || data.conversations.length === 0) {
                    dmConvList.innerHTML = '<div class="chat-empty">No DMs yet. Start one below!</div>';
                    return;
                }

                dmConvList.innerHTML = '';
                data.conversations.forEach(function (conv) {
                    const item = document.createElement('div');
                    item.className = 'dm-conv-item';
                    item.dataset.uid      = conv.user_id;
                    item.dataset.username = conv.username;

                    const d = new Date(conv.last_ts * 1000);
                    const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    item.innerHTML =
                        '<span class="dm-conv-username">' + escHtml(conv.username) + '</span>' +
                        '<span class="dm-conv-time">'     + escHtml(timeStr)       + '</span>';

                    item.addEventListener('click', function () {
                        openDMChat(conv.user_id, conv.username);
                        document.querySelectorAll('.chat-tab').forEach(function (t) { t.classList.remove('active'); });
                        const dmTab = document.querySelector('.chat-tab[data-channel="dm"]');
                        if (dmTab) dmTab.classList.add('active');
                    });

                    dmConvList.appendChild(item);
                });
            })
            .catch(function () {
                if (dmConvList) {
                    dmConvList.innerHTML = '<div class="chat-error">Network error.</div>';
                }
            });
    }

    // -----------------------------------------------------------------------
    // DM: start new conversation by username
    // -----------------------------------------------------------------------
    function startNewDM() {
        if (!dmNewUsername) return;
        const username = dmNewUsername.value.trim();
        if (!username) return;

        if (dmNewBtn) dmNewBtn.disabled = true;

        fetch('/api/chat_dm_user?username=' + encodeURIComponent(username), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (dmNewBtn) dmNewBtn.disabled = false;
                if (data.success && data.user) {
                    dmNewUsername.value = '';
                    openDMChat(data.user.id, data.user.username);
                    document.querySelectorAll('.chat-tab').forEach(function (t) { t.classList.remove('active'); });
                    const dmTab = document.querySelector('.chat-tab[data-channel="dm"]');
                    if (dmTab) dmTab.classList.add('active');
                } else {
                    if (dmConvList) {
                        const err = document.createElement('div');
                        err.className = 'chat-error';
                        err.textContent = data.error || 'User not found';
                        dmConvList.appendChild(err);
                        setTimeout(function () { err.remove(); }, 4000);
                    }
                }
            })
            .catch(function () {
                if (dmNewBtn) dmNewBtn.disabled = false;
                if (dmConvList) {
                    const err = document.createElement('div');
                    err.className = 'chat-error';
                    err.textContent = 'Network error.';
                    dmConvList.appendChild(err);
                    setTimeout(function () { err.remove(); }, 4000);
                }
            });
    }

    if (dmNewBtn) {
        dmNewBtn.addEventListener('click', startNewDM);
    }

    if (dmNewUsername) {
        dmNewUsername.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); startNewDM(); }
        });
    }

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
            ? '<button class="chat-mod-quick" data-uid="' + msg.user_id + '" title="Moderate">⋮</button>'
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
        if (currentChannel === 'dm') return; // DM list view — nothing to fetch
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

                // Mute status (not returned for DM channels)
                if (data.muted) {
                    isMuted = true;
                    const until = data.muted.until;
                    let label = until === -1 ? '(permanent)' :
                        'until ' + new Date(until * 1000).toLocaleString();
                    if (data.muted.reason) label += ' — ' + escHtml(data.muted.reason);
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
    // Slash command hint
    // -----------------------------------------------------------------------
    function slashHintMatches(val) {
        const lower = val.toLowerCase();
        return SLASH_COMMANDS.filter(function (sc) {
            return sc.cmd.startsWith(lower) || lower.startsWith(sc.cmd + ' ') || lower === sc.cmd;
        });
    }

    function updateSlashHint() {
        if (!slashHint || !input) return;
        const val = input.value;
        if (!val.startsWith('/')) {
            slashHint.style.display = 'none';
            slashSelectIndex = -1;
            return;
        }
        const matches = slashHintMatches(val);
        if (matches.length === 0) {
            slashHint.style.display = 'none';
            slashSelectIndex = -1;
            return;
        }
        slashHint.innerHTML = '';
        matches.forEach(function (sc, idx) {
            const item = document.createElement('div');
            item.className = 'chat-slash-item' + (idx === slashSelectIndex ? ' selected' : '');
            item.setAttribute('role', 'option');
            item.innerHTML =
                '<span class="chat-slash-item-syntax">' + escHtml(sc.syntax) + '</span>' +
                '<span class="chat-slash-item-desc">'   + escHtml(sc.desc)   + '</span>';
            item.addEventListener('mousedown', function (e) {
                e.preventDefault(); // keep focus on input
                if (!input.value.toLowerCase().startsWith(sc.cmd + ' ') && input.value.toLowerCase() !== sc.cmd) {
                    input.value = sc.cmd + ' ';
                }
                slashHint.style.display = 'none';
                slashSelectIndex = -1;
                input.focus();
            });
            slashHint.appendChild(item);
        });
        slashHint.style.display = '';
    }

    function hideSlashHint() {
        if (slashHint) slashHint.style.display = 'none';
        slashSelectIndex = -1;
    }

    if (input) {
        input.addEventListener('input', updateSlashHint);

        input.addEventListener('blur', hideSlashHint);

        input.addEventListener('keydown', function (e) {
            if (!slashHint || slashHint.style.display === 'none') return;
            const items = slashHint.querySelectorAll('.chat-slash-item');
            if (items.length === 0) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                slashSelectIndex = Math.min(slashSelectIndex + 1, items.length - 1);
                items.forEach(function (el, i) { el.classList.toggle('selected', i === slashSelectIndex); });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                slashSelectIndex = Math.max(slashSelectIndex - 1, -1);
                items.forEach(function (el, i) { el.classList.toggle('selected', i === slashSelectIndex); });
            } else if ((e.key === 'Tab' || e.key === 'Enter') && slashSelectIndex >= 0) {
                e.preventDefault();
                const matches = slashHintMatches(input.value);
                const sc = matches[slashSelectIndex];
                if (sc) {
                    if (!input.value.toLowerCase().startsWith(sc.cmd + ' ') && input.value.toLowerCase() !== sc.cmd) {
                        input.value = sc.cmd + ' ';
                    }
                    hideSlashHint();
                    input.focus();
                }
            } else if (e.key === 'Escape') {
                hideSlashHint();
            }
        });
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
            hideSlashHint();

            // /wire <username> <amount> <commodity>
            if (text.toLowerCase().startsWith('/wire ') && isSeason) {
                showError('Wires are only available to Perpetual characters');
                input.disabled = false;
                input.focus();
                return;
            }
            if (text.toLowerCase().startsWith('/wire ')) {
                const parts = text.slice(6).trim().split(/\s+/);
                if (parts.length < 3) {
                    showError('Usage: /wire <username> <amount> <commodity>');
                    input.disabled = false;
                    input.focus();
                    return;
                }
                const wireRecipient  = parts[0];
                const wireAmount     = parseInt(parts[1], 10);
                const wireCommodity  = parts[2].toLowerCase();

                if (isNaN(wireAmount) || wireAmount <= 0) {
                    showError('Wire amount must be a positive whole number');
                    input.disabled = false;
                    input.focus();
                    return;
                }

                fetch('/api/wire', {
                    method:      'POST',
                    credentials: 'same-origin',
                    headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:        new URLSearchParams({
                        csrf_token: csrf,
                        recipient:  wireRecipient,
                        amount:     wireAmount,
                        commodity:  wireCommodity,
                    }).toString(),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        input.disabled = false;
                        input.focus();
                        if (data.success) {
                            showError('Wired ' + wireAmount.toLocaleString() + ' ' + wireCommodity + ' to ' + wireRecipient + '. Check your log.');
                        } else {
                            showError(data.error || 'Wire failed');
                        }
                    })
                    .catch(function () {
                        input.disabled = false;
                        showError('Network error.');
                    });
                return;
            }

            const payload = new URLSearchParams({
                csrf_token: csrf,
                channel:    currentChannel,
                message:    text,
            });

            fetch('/api/chat_send', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:        payload.toString(),
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

            const modPayload = new URLSearchParams({
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
                body:        modPayload.toString(),
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
        } else if (isOpen && currentChannel !== 'dm') {
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
        setTimeout(function () { el.remove(); }, 10000);
    }
}());
