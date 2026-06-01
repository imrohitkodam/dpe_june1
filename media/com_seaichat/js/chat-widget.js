/**
 * SE AI Chatbot — Chat Widget
 */
(function() {
    'use strict';
    if (window.SE_CHAT_LOADED) return;
    window.SE_CHAT_LOADED = true;
    var config = window.SE_CHAT_CONFIG || {};
    if (!config.enabled) return;

    var defaults = {
        status_online: 'Online',
        chat_with_ai: 'Chat with AI',
        new_conversation: 'New conversation',
        close: 'Close',
        send: 'Send',
        gdpr_title: 'Data Privacy Notice',
        gdpr_accept: 'Accept',
        gdpr_decline: 'Decline',
        gdpr_unavailable_title: 'Chat Unavailable',
        gdpr_unavailable_msg: 'You have declined the data privacy notice. The chat is not available without your consent. You can refresh the chat above to try again.',
        error_prefix: 'Sorry:',
        error_generic: 'Sorry, something went wrong.',
        sources_label: 'Read more'
    };

    var SE = {
        key: null, count: 0, open: false, loading: false, config: config,
        started: false, isInline: !!config.inline,

        str: function(key) {
            return (this.config.strings && this.config.strings[key]) || defaults[key] || key;
        },

        avatarHtml: function() {
            if (this.config.avatar_url) {
                return '<img src="' + this.escA(this.config.avatar_url) + '" alt="Avatar" class="se-chat-avatar-img" style="width:100%;height:100%;object-fit:cover;border-radius:50%" />';
            }
            return '<i class="fa-solid fa-robot"></i>';
        },

        init: function() {
            this.key = sessionStorage.getItem('se_cs') || this.genKey();
            sessionStorage.setItem('se_cs', this.key);
            if (this.isInline) {
                this.bindInline();
                this.showWelcome();
            } else {
                this.render();
            }
        },

        genKey: function() {
            var a = new Uint8Array(16); crypto.getRandomValues(a);
            return Array.from(a, function(b) { return b.toString(16).padStart(2,'0'); }).join('');
        },

        render: function() {
            var w = document.createElement('div');
            w.id = 'se-chat-widget';
            if ((this.config.position || 'bottom-right') === 'bottom-left') w.classList.add('se-chat-left');
            w.innerHTML =
                '<div id="se-chat-bubble" title="' + this.esc(this.str('chat_with_ai')) + '"><i class="fa-solid fa-comments"></i></div>' +
                '<div id="se-chat-panel" style="display:none">' +
                    '<div id="se-chat-header">' +
                        '<div class="se-chat-header-info"><div class="se-chat-avatar">' + this.avatarHtml() + '</div>' +
                        '<div><div class="se-chat-header-title">' + this.esc(this.config.header_title || 'AI Assistant') + '</div>' +
                        '<div class="se-chat-header-status"><span class="se-chat-status-dot"></span> ' + this.esc(this.str('status_online')) + '</div></div></div>' +
                        '<div style="display:flex;gap:6px">' +
                        '<button id="se-chat-reset" title="' + this.esc(this.str('new_conversation')) + '" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:background 0.2s"><i class="fa-solid fa-rotate-right"></i></button>' +
                        '<button id="se-chat-close" title="' + this.esc(this.str('close')) + '"><i class="fa-solid fa-xmark"></i></button>' +
                        '</div>' +
                    '</div>' +
                    '<div id="se-chat-messages"></div>' +
                    '<div id="se-chat-input-area" style="display:none">' +
                        '<div class="se-chat-input-wrap">' +
                            '<textarea id="se-chat-input" placeholder="' + this.esc(this.config.placeholder_text || 'Type your question...') + '" rows="1"></textarea>' +
                            '<button id="se-chat-send" title="' + this.esc(this.str('send')) + '"><i class="fa-solid fa-paper-plane"></i></button>' +
                        '</div>' +
                        (this.config.contact_url ? '<div class="se-chat-footer"><a href="' + this.esc(this.config.contact_url) + '" target="' + (this.config.contact_target || '_blank') + '" class="se-chat-footer-link"><i class="fa-solid fa-headset"></i> ' + this.esc(this.config.contact_text || 'Contact Support') + '</a></div>' : '') +
                    '</div>' +
                '</div>';
            document.body.appendChild(w);
            this.bind();
        },

        bind: function() {
            var s = this;
            document.getElementById('se-chat-bubble').onclick = function() { s.toggle(); };
            document.getElementById('se-chat-close').onclick = function() { s.toggle(); };
            document.getElementById('se-chat-send').onclick = function() { s.send(); };
            document.getElementById('se-chat-reset').onclick = function() { s.resetChat(); };
            var inp = document.getElementById('se-chat-input');
            inp.onkeydown = function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); s.send(); } };
            inp.oninput = function() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight,100)+'px'; };
        },

        bindInline: function() {
            var s = this;
            document.getElementById('se-chat-send').onclick = function() { s.send(); };
            var resetBtn = document.getElementById('se-chat-reset');
            if (resetBtn) resetBtn.onclick = function() { s.resetChat(); };
            var inp = document.getElementById('se-chat-input');
            inp.onkeydown = function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); s.send(); } };
            inp.oninput = function() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight,100)+'px'; };
        },

        toggle: function() {
            var p = document.getElementById('se-chat-panel'), b = document.getElementById('se-chat-bubble');
            this.open = !this.open;
            if (this.open) {
                p.style.display = 'flex'; b.classList.add('se-chat-bubble-hidden');
                setTimeout(function() { p.classList.add('se-chat-panel-open'); }, 10);
                if (!this.started) this.showWelcome();
            } else {
                p.classList.remove('se-chat-panel-open');
                setTimeout(function() { p.style.display = 'none'; }, 250);
                b.classList.remove('se-chat-bubble-hidden');
            }
        },

        showWelcome: function() {
            this.started = true;
            var gdpr = this.config.gdpr_enabled;
            if (gdpr) {
                if (sessionStorage.getItem('se_gdpr_declined')) {
                    // Previously declined — show unavailable message, no greeting, no input
                    this.showDeclinedState();
                    return;
                }
                if (!sessionStorage.getItem('se_gdpr_accepted')) {
                    this.showGdprConsent();
                    return;
                }
            }
            // GDPR not enabled, or already accepted
            this.msg('assistant', this.config.welcome_message || 'Hi! How can I help you today?');
            var inputArea = document.getElementById('se-chat-input-area');
            inputArea.style.display = 'block';
            inputArea.classList.add('se-gdpr-passed');
            document.getElementById('se-chat-input').focus();
        },

        showDeclinedState: function() {
            var box = document.getElementById('se-chat-messages');
            box.innerHTML = '';
            var inputArea = document.getElementById('se-chat-input-area');
            inputArea.style.display = 'none';
            inputArea.classList.remove('se-gdpr-passed');
            var declineMsg = document.createElement('div');
            declineMsg.style.cssText = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px 20px;text-align:center;margin:auto 0;';
            declineMsg.innerHTML = '<div style="width:48px;height:48px;border-radius:50%;background:#dc2626;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 14px"><i class="fa-solid fa-ban"></i></div>' +
                '<div style="font-size:15px;font-weight:700;color:#1a1d26;margin-bottom:10px">' + this.esc(this.str('gdpr_unavailable_title')) + '</div>' +
                '<div style="font-size:13px;line-height:1.6;color:#4b5563">' + this.esc(this.str('gdpr_unavailable_msg')) + '</div>';
            box.appendChild(declineMsg);
        },

        showGdprConsent: function() {
            var box = document.getElementById('se-chat-messages');
            var d = document.createElement('div');
            d.id = 'se-gdpr-consent';
            d.style.cssText = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px 20px;text-align:center;margin:auto 0;font-family:inherit;';
            var text = this.config.gdpr_text || '';
            var privacyUrl = this.config.gdpr_privacy_url || '';
            var displayText = this.esc(text);
            if (privacyUrl) {
                displayText = displayText.replace(/privacy policy/gi, '<a href="' + this.escA(privacyUrl) + '" target="_blank" style="color:var(--se-chat-color,#2E486B);text-decoration:underline;font-weight:500">privacy policy</a>');
            }
            var color = this.config.color || '#2E486B';
            d.innerHTML =
                '<div style="width:48px;height:48px;border-radius:50%;background:' + color + ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 14px"><i class="fa-solid fa-shield-halved"></i></div>' +
                '<div style="font-size:15px;font-weight:700;color:#1a1d26;margin-bottom:10px">' + this.esc(this.str('gdpr_title')) + '</div>' +
                '<div style="font-size:13px;line-height:1.6;color:#4b5563;margin-bottom:18px">' + displayText + '</div>' +
                '<div style="display:flex;gap:10px;justify-content:center">' +
                    '<button type="button" id="se-gdpr-accept" style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border:none;border-radius:10px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;background:' + color + ';color:#fff"><i class="fa-solid fa-check"></i> ' + this.esc(this.str('gdpr_accept')) + '</button>' +
                    '<button type="button" id="se-gdpr-decline" style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border:1px solid #e5e7eb;border-radius:10px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;background:#f3f4f6;color:#6b7280"><i class="fa-solid fa-xmark"></i> ' + this.esc(this.str('gdpr_decline')) + '</button>' +
                '</div>';
            box.appendChild(d);
            box.scrollTop = box.scrollHeight;

            var s = this;
            document.getElementById('se-gdpr-accept').onclick = function() {
                sessionStorage.setItem('se_gdpr_accepted', '1');
                sessionStorage.removeItem('se_gdpr_declined');
                d.remove();
                s.msg('assistant', s.config.welcome_message || 'Hi! How can I help you today?');
                var inputArea = document.getElementById('se-chat-input-area');
                inputArea.style.display = 'block';
                inputArea.classList.add('se-gdpr-passed');
                document.getElementById('se-chat-input').focus();
            };
            document.getElementById('se-gdpr-decline').onclick = function() {
                sessionStorage.setItem('se_gdpr_declined', '1');
                d.remove();
                s.showDeclinedState();
            };
        },

        send: function() {
            if (this.loading) return;
            if (this.config.gdpr_enabled && !sessionStorage.getItem('se_gdpr_accepted')) return;
            var inp = document.getElementById('se-chat-input'), m = inp.value.trim();
            if (!m) return;
            inp.value = ''; inp.style.height = 'auto';
            this.msg('user', m); this.count++; this.typing(true); this.loading = true;

            var s = this, fd = new FormData();
            fd.append('message', m); fd.append('session_key', this.key); fd.append(this.config.token, '1');

            fetch(this.config.base_url + 'index.php?option=com_seaichat&task=chat.send&format=raw', {
                method:'POST', body:fd, credentials:'same-origin'
            }).then(function(r){return r.json()}).then(function(d) {
                s.typing(false); s.loading = false;
                if (d.error) s.msg('assistant', s.str('error_prefix') + ' ' + d.error);
                else { s.msg('assistant', d.message, d.actions, d.sources, d.cta_style); }
            }).catch(function(){s.typing(false);s.loading=false;s.msg('assistant', s.str('error_generic'));});
        },

        resetChat: function() {
            var s = this;

            // Clear any GDPR declined/accepted state so showWelcome re-presents the consent
            if (this.config.gdpr_enabled) {
                sessionStorage.removeItem('se_gdpr_declined');
                sessionStorage.removeItem('se_gdpr_accepted');
            }

            // Clear the UI
            var box = document.getElementById('se-chat-messages');
            box.innerHTML = '';
            var inputArea = document.getElementById('se-chat-input-area');
            inputArea.style.display = 'none';
            inputArea.classList.remove('se-gdpr-passed');
            this.count = 0;

            // Reset session on the server
            var fd = new FormData();
            fd.append('session_key', this.key); fd.append(this.config.token, '1');

            fetch(this.config.base_url + 'index.php?option=com_seaichat&task=chat.reset&format=raw', {
                method:'POST', body:fd, credentials:'same-origin'
            }).then(function(r){return r.json()}).then(function(d) {
                if (d.success) {
                    s.key = d.new_session_key || s.genKey();
                    sessionStorage.setItem('se_cs', s.key);
                }
            }).catch(function(){});

            // Route through showWelcome — this will show GDPR consent if enabled,
            // or the welcome message if GDPR is off or already accepted
            this.showWelcome();
        },

        msg: function(role, text, actions, sources, ctaStyle) {
            var box = document.getElementById('se-chat-messages'), d = document.createElement('div');
            d.className = 'se-chat-msg se-chat-msg-'+role;
            var f = this.fmt(text);
            var extraHtml = '';
            if (role === 'assistant') {
                // Source documentation links
                if (sources && sources.length) {
                    extraHtml += '<div class="se-chat-sources">';
                    extraHtml += '<div class="se-chat-sources-label"><i class="fa-solid fa-book-open"></i> ' + this.esc(this.str('sources_label')) + '</div>';
                    for (var i = 0; i < sources.length; i++) {
                        var src = sources[i];
                        extraHtml += '<a href="' + this.escA(src.url) + '" class="se-chat-source-link" target="_blank">';
                        extraHtml += '<i class="fa-solid fa-file-lines"></i> ' + this.esc(src.title) + '</a>';
                    }
                    extraHtml += '</div>';
                }
                // CTA action buttons
                if (actions && actions.length) {
                    var btnStyle = '';
                    if (ctaStyle) {
                        if (ctaStyle.bg) btnStyle += 'background:' + ctaStyle.bg + ';';
                        if (ctaStyle.text) btnStyle += 'color:' + ctaStyle.text + ';';
                    }
                    extraHtml += '<div class="se-chat-actions">';
                    for (var j = 0; j < actions.length; j++) {
                        var a = actions[j];
                        extraHtml += '<a href="' + this.escA(a.url) + '" class="se-chat-action-btn"' + (btnStyle ? ' style="' + btnStyle + '"' : '') + ' target="' + this.escA(a.target || '_self') + '">';
                        if (a.icon) extraHtml += '<i class="fa-solid ' + this.esc(a.icon) + '"></i> ';
                        extraHtml += this.esc(a.label) + '</a>';
                    }
                    extraHtml += '</div>';
                }
                d.innerHTML = '<div class="se-chat-msg-avatar">' + this.avatarHtml() + '</div><div class="se-chat-msg-content">'+f+extraHtml+'</div>';
            } else {
                d.innerHTML = '<div class="se-chat-msg-content">'+f+'</div>';
            }
            box.appendChild(d); box.scrollTop = box.scrollHeight;
        },

        fmt: function(t) {
            var e = this.esc(t);
            return e.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/`(.*?)`/g,'<code>$1</code>').replace(/\n/g,'<br>');
        },

        typing: function(on) {
            var el = document.getElementById('se-chat-typing');
            if (!on) { if (el) el.remove(); return; }
            var box = document.getElementById('se-chat-messages'), d = document.createElement('div');
            d.id = 'se-chat-typing'; d.className = 'se-chat-msg se-chat-msg-assistant';
            d.innerHTML = '<div class="se-chat-msg-avatar">' + this.avatarHtml() + '</div><div class="se-chat-msg-content"><div class="se-chat-typing-dots"><span></span><span></span><span></span></div></div>';
            box.appendChild(d); box.scrollTop = box.scrollHeight;
        },

        esc: function(t) { var d=document.createElement('div'); d.textContent=t; return d.innerHTML; },
        escA: function(t) { return this.esc(t).replace(/"/g,'&quot;'); }
    };

    if (document.readyState==='loading') document.addEventListener('DOMContentLoaded',function(){SE.init();});
    else SE.init();
})();
