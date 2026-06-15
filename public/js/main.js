document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#_=_') {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }
    initToastContainer();
    initThemeToggle();
    initFlashModal();
    initCustomSelects();
    initCategoryIconPickers();
    initRegisterForm();
    initReadingProgress();
    initHashScroll();
    initSmoothScroll();
    initScrollReveal();
    initPostLike();
    initShareButtons();
    initBackToTop();
    initScrollEffects();
    initFollowButton();
    initCommentActions();
    initCommentReplies();
    initPostBookmark();
    initNotificationDropdown();
    initNavDropdowns();
    initAdminToolbarNav();
    initAdminConfirmModal();
    initPasswordToggles();
    registerServiceWorker();
    initWebPush();
    initRealtimeBadges();
});

function updateNavNotifyBadge(count) {
    var badge = document.getElementById('nav-notify-badge');
    count = parseInt(count, 10) || 0;
    if (count > 0) {
        if (!badge) {
            var btn = document.querySelector('.nav-notify-btn');
            if (btn) {
                badge = document.createElement('span');
                badge.id = 'nav-notify-badge';
                badge.className = 'nav-notify-badge';
                btn.appendChild(badge);
            }
        }
        if (badge) {
            badge.textContent = count;
            badge.hidden = false;
        }
    } else if (badge) {
        badge.remove();
    }
}

function updateAdminBadge(key, count) {
    var badges = document.querySelectorAll('[data-admin-badge="' + key + '"]');
    count = parseInt(count, 10) || 0;
    for (var i = 0; i < badges.length; i++) {
        if (count > 0) {
            badges[i].textContent = count;
            badges[i].hidden = false;
        } else {
            badges[i].textContent = '0';
            badges[i].hidden = true;
        }
    }

    var tab = document.querySelector('[data-admin-tab="' + key + '"]');
    if (tab) {
        tab.classList.toggle('has-unread', count > 0);
    }
}

function updateNotifyUnreadLabel(count) {
    var label = document.getElementById('nav-notify-unread-label');
    var markAll = document.getElementById('nav-notify-mark-all');
    count = parseInt(count, 10) || 0;
    if (label) {
        if (count > 0) {
            label.textContent = count + ' unread';
            label.hidden = false;
        } else {
            label.textContent = '';
            label.hidden = true;
        }
    }
    if (markAll) {
        markAll.hidden = count <= 0;
    }
}

function initRealtimeBadges() {
    var adminNav = document.getElementById('dashToolbarNav');
    var hasNotify = document.querySelector('.nav-notify-btn');
    if (!adminNav && !hasNotify) {
        return;
    }

    function pollCounts() {
        fetch(appUrl('api/admin-counts.php'), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success) {
                    return;
                }
                if (adminNav) {
                    updateAdminBadge('messages', data.messages);
                    updateAdminBadge('reports', data.reports);
                    updateAdminBadge('pending_posts', data.pending_posts);
                    updateAdminBadge('pending_comments', data.pending_comments);
                    updateAdminBadge('notifications', data.notifications);
                }
                if (hasNotify) {
                    updateNavNotifyBadge(data.notifications);
                    updateNotifyUnreadLabel(data.notifications);
                }
            })
            .catch(function() {});
    }

    pollCounts();
    window.setInterval(pollCounts, 60000);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            pollCounts();
            if (typeof window.refreshNavNotifications === 'function') {
                window.refreshNavNotifications(true);
            }
        }
    });
}

function initPasswordToggles() {
    var wraps = document.querySelectorAll('.password-input-wrap');
    wraps.forEach(function(wrap) {
        var input = wrap.querySelector('input[type="password"]');
        if (!input || wrap.querySelector('.password-toggle-btn')) {
            return;
        }

        wrap.classList.add('has-toggle');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle-btn';
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';

        btn.addEventListener('click', function() {
            var icon = btn.querySelector('i');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            if (icon) {
                icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            }
        });

        wrap.appendChild(btn);
    });
}

function appUrl(path) {
    if (!path) {
        if (window.APP_PRETTY && window.APP_ROUTE_MAP && window.APP_ROUTE_MAP['index.php']) {
            return window.APP_ROUTE_MAP['index.php'];
        }
        return (window.APP_BASE || '') + 'index.php';
    }
    if (/^https?:\/\//i.test(path)) {
        return path;
    }
    if (path.charAt(0) === '/') {
        return path;
    }
    var clean = String(path).replace(/^\//, '');
    if (window.APP_PRETTY && window.APP_ROUTE_MAP && window.APP_ROUTE_MAP[clean]) {
        return window.APP_ROUTE_MAP[clean];
    }
    return (window.APP_BASE || '') + clean;
}

var pendingConfirmForm = null;
var pendingConfirmCallback = null;

function showConfirmDialog(message, onConfirm, options) {
    var modalEl = document.getElementById('confirmActionModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        if (window.confirm(message || 'Are you sure?')) {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        }
        return;
    }

    var opts = options || {};
    var titleEl = document.getElementById('confirmActionTitle');
    var msgEl = document.getElementById('confirmActionMessage');
    var confirmBtn = document.getElementById('confirmActionBtn');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    if (titleEl) {
        titleEl.textContent = opts.title || 'Confirm';
    }
    if (msgEl) {
        msgEl.textContent = message || 'Are you sure?';
    }
    if (confirmBtn) {
        confirmBtn.textContent = opts.confirmLabel || 'Confirm';
        confirmBtn.className = opts.danger ? 'btn btn-danger px-4' : 'btn btn-gradient px-4';
    }

    pendingConfirmForm = null;
    pendingConfirmCallback = onConfirm;
    modal.show();
}

function initAdminConfirmModal() {
    var modalEl = document.getElementById('confirmActionModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var titleEl = document.getElementById('confirmActionTitle');
    var msgEl = document.getElementById('confirmActionMessage');
    var confirmBtn = document.getElementById('confirmActionBtn');
    var forms = document.querySelectorAll('form.admin-action-form[data-confirm]');

    for (var i = 0; i < forms.length; i++) {
        forms[i].addEventListener('submit', function(e) {
            e.preventDefault();
            pendingConfirmForm = this;
            pendingConfirmCallback = null;

            if (titleEl) {
                titleEl.textContent = this.getAttribute('data-confirm-title') || 'Confirm';
            }
            if (msgEl) {
                msgEl.textContent = this.getAttribute('data-confirm') || 'Are you sure?';
            }
            if (confirmBtn) {
                confirmBtn.textContent = this.getAttribute('data-confirm-danger') === '1' ? 'Delete' : 'Confirm';
                if (this.getAttribute('data-confirm-danger') === '1') {
                    confirmBtn.className = 'btn btn-danger px-4';
                } else {
                    confirmBtn.className = 'btn btn-gradient px-4';
                }
            }

            modal.show();
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            var form = pendingConfirmForm;
            var callback = pendingConfirmCallback;
            pendingConfirmForm = null;
            pendingConfirmCallback = null;
            modal.hide();

            if (form) {
                form.submit();
            } else if (typeof callback === 'function') {
                callback();
            }
        });
    }

    modalEl.addEventListener('hidden.bs.modal', function() {
        pendingConfirmForm = null;
        pendingConfirmCallback = null;
    });
}

function initToastContainer() {
    if (!document.getElementById('toast-container')) {
        var c = document.createElement('div');
        c.id = 'toast-container';
        c.style.cssText = 'position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:0.75rem;';
        document.body.appendChild(c);
    }
}

function showToast(title, message, type) {
    if (!type) type = 'success';
    initToastContainer();
    var colors = { success: 'var(--success)', error: 'var(--danger)', warning: 'var(--warning)', info: 'var(--info)' };
    var toast = document.createElement('div');
    toast.className = 'glass-panel glass-panel-sm';
    toast.style.cssText = 'padding:1rem 1.25rem;min-width:260px;opacity:0;transform:translateY(16px);transition:all 0.35s ease;border-left:3px solid ' + (colors[type] || colors.info) + ';';
    toast.innerHTML = '<strong style="color:var(--heading-color);display:block;margin-bottom:0.2rem;">' + title + '</strong><span style="color:var(--text-secondary);font-size:0.85rem;">' + message + '</span>';
    document.getElementById('toast-container').appendChild(toast);
    requestAnimationFrame(function() {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3500);
}

function initFlashModal() {
    var modalEl = document.getElementById('flashModal');
    if (!modalEl || typeof bootstrap == 'undefined') {
        return;
    }

    var modal = new bootstrap.Modal(modalEl, {
        backdrop: true,
        keyboard: true
    });
    modal.show();

    modalEl.addEventListener('hidden.bs.modal', function() {
        modalEl.remove();
    });

    setTimeout(function() {
        if (modalEl.classList.contains('show')) {
            modal.hide();
        }
    }, 4500);
}

function initThemeToggle() {
    var btn = document.getElementById('theme-toggle');
    if (!btn) return;

    btn.addEventListener('click', function() {
        var html = document.documentElement;
        var current = html.getAttribute('data-theme');
        var next = 'light';
        if (current == 'light') {
            next = 'dark';
        }
        html.setAttribute('data-theme', next);
        localStorage.setItem('cms-theme', next);
    });
}

function initCustomSelects() {
    var selects = document.querySelectorAll('select.form-select.form-control-custom');
    for (var i = 0; i < selects.length; i++) {
        buildCustomSelect(selects[i]);
    }

    document.addEventListener('click', function(e) {
        var openSelects = document.querySelectorAll('.custom-select.is-open');
        for (var j = 0; j < openSelects.length; j++) {
            if (!openSelects[j].contains(e.target)) {
                closeCustomSelect(openSelects[j]);
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key == 'Escape') {
            var openSelects = document.querySelectorAll('.custom-select.is-open');
            for (var k = 0; k < openSelects.length; k++) {
                closeCustomSelect(openSelects[k]);
            }
        }
    });
}

function buildCustomSelect(select) {
    if (select.dataset.customSelect == '1') {
        return;
    }
    select.dataset.customSelect = '1';

    var wrap = document.createElement('div');
    wrap.className = 'custom-select';
    var spacingClasses = ['mb-1', 'mb-2', 'mb-3', 'mb-4', 'mb-5'];
    for (var s = 0; s < spacingClasses.length; s++) {
        if (select.classList.contains(spacingClasses[s])) {
            wrap.classList.add(spacingClasses[s]);
            select.classList.remove(spacingClasses[s]);
        }
    }
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    select.classList.add('custom-select-native');
    select.setAttribute('tabindex', '-1');

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');

    var label = document.createElement('span');
    label.className = 'custom-select-label';

    var icon = document.createElement('span');
    icon.className = 'custom-select-chevron';
    icon.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';

    trigger.appendChild(label);
    trigger.appendChild(icon);

    var menu = document.createElement('div');
    menu.className = 'custom-select-menu';
    menu.setAttribute('role', 'listbox');

    wrap.appendChild(trigger);
    wrap.appendChild(menu);

    wrap.customSelect = {
        select: select,
        trigger: trigger,
        label: label,
        menu: menu
    };

    renderCustomSelectOptions(wrap);
    updateCustomSelectLabel(wrap);

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        if (select.disabled) {
            return;
        }
        if (wrap.classList.contains('is-open')) {
            closeCustomSelect(wrap);
        } else {
            openCustomSelect(wrap);
        }
    });

    select.addEventListener('change', function() {
        updateCustomSelectLabel(wrap);
        renderCustomSelectOptions(wrap);
    });
}

function renderCustomSelectOptions(wrap) {
    var select = wrap.customSelect.select;
    var menu = wrap.customSelect.menu;
    menu.innerHTML = '';

    for (var i = 0; i < select.options.length; i++) {
        var opt = select.options[i];
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'custom-select-option';
        item.setAttribute('role', 'option');
        item.dataset.value = opt.value;
        item.textContent = opt.textContent;

        if (opt.value == select.value) {
            item.classList.add('is-selected');
            item.setAttribute('aria-selected', 'true');
        } else {
            item.setAttribute('aria-selected', 'false');
        }

        if (opt.disabled) {
            item.disabled = true;
            item.classList.add('is-disabled');
        }

        item.addEventListener('click', function(e) {
            e.stopPropagation();
            if (this.disabled) {
                return;
            }
            select.value = this.dataset.value;
            var event = new Event('change', { bubbles: true });
            select.dispatchEvent(event);
            closeCustomSelect(wrap);
        });

        menu.appendChild(item);
    }
}

function updateCustomSelectLabel(wrap) {
    var select = wrap.customSelect.select;
    var label = wrap.customSelect.label;
    var text = '— Select —';
    var selectedIndex = select.selectedIndex;

    if (selectedIndex >= 0 && select.options[selectedIndex]) {
        text = select.options[selectedIndex].textContent;
    }

    label.textContent = text;

    if (select.disabled) {
        wrap.classList.add('is-disabled');
        wrap.customSelect.trigger.disabled = true;
    } else {
        wrap.classList.remove('is-disabled');
        wrap.customSelect.trigger.disabled = false;
    }
}

function openCustomSelect(wrap) {
    var all = document.querySelectorAll('.custom-select.is-open');
    for (var i = 0; i < all.length; i++) {
        if (all[i] != wrap) {
            closeCustomSelect(all[i]);
        }
    }
    wrap.classList.add('is-open');
    wrap.customSelect.trigger.setAttribute('aria-expanded', 'true');
}

function closeCustomSelect(wrap) {
    wrap.classList.remove('is-open');
    wrap.customSelect.trigger.setAttribute('aria-expanded', 'false');
}

function initReadingProgress() {
    if (!document.querySelector('.article-body')) return;

    if (!document.getElementById('reading-progress')) {
        var bar = document.createElement('div');
        bar.id = 'reading-progress';
        document.body.prepend(bar);
    }
    document.body.classList.add('has-reading-progress');
}

function updateReadingProgress() {
    var bar = document.getElementById('reading-progress');
    var article = document.querySelector('.article-body');
    if (!bar || !article) return;

    var rect = article.getBoundingClientRect();
    var winHeight = window.innerHeight;
    var articleHeight = article.offsetHeight;
    var scrolled = winHeight - rect.top;
    var total = articleHeight + winHeight * 0.3;
    var progress = 0;

    if (scrolled > 0 && total > 0) {
        progress = (scrolled / total) * 100;
    }
    if (progress > 100) progress = 100;
    if (progress < 0) progress = 0;

    bar.style.width = progress + '%';
}

function getNavScrollOffset() {
    var navbar = document.getElementById('main-navbar');
    return navbar ? navbar.offsetHeight + 16 : 80;
}

function revealElement(el) {
    if (el && el.classList && el.classList.contains('reveal')) {
        el.classList.add('visible');
    }
}

function scrollToHashTarget(hash, behavior) {
    if (!hash || hash === '#' || hash === '#_=_') {
        return false;
    }
    var target = document.querySelector(hash);
    if (!target) {
        return false;
    }
    revealElement(target);
    var top = target.getBoundingClientRect().top + window.scrollY - getNavScrollOffset();
    window.scrollTo({ top: Math.max(0, top), behavior: behavior || 'smooth' });
    return true;
}

function initHashScroll() {
    function applyHashScroll(smooth) {
        if (!window.location.hash) {
            return;
        }
        scrollToHashTarget(window.location.hash, smooth ? 'smooth' : 'auto');
    }

    applyHashScroll(false);
    window.setTimeout(function() {
        applyHashScroll(true);
    }, 150);
    window.addEventListener('load', function() {
        applyHashScroll(false);
    });
    window.addEventListener('hashchange', function() {
        applyHashScroll(true);
    });
}

function initSmoothScroll() {
    var links = document.querySelectorAll('a[href^="#"]');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href == '#') return;
            if (scrollToHashTarget(href, true)) {
                e.preventDefault();
                if (history.pushState) {
                    history.pushState(null, '', href);
                }
            }
        });
    }
}

function initScrollReveal() {
    var els = document.querySelectorAll('.reveal');
    if (els.length == 0) return;

    function showElement(el) {
        el.classList.add('visible');
    }

    function isInView(el) {
        var rect = el.getBoundingClientRect();
        return rect.top < window.innerHeight - 40 && rect.bottom > 0;
    }

    if (!('IntersectionObserver' in window)) {
        for (var i = 0; i < els.length; i++) showElement(els[i]);
        return;
    }

    var io = new IntersectionObserver(function(entries) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i].isIntersecting) {
                showElement(entries[i].target);
                io.unobserve(entries[i].target);
            }
        }
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    for (var j = 0; j < els.length; j++) {
        if (isInView(els[j])) {
            showElement(els[j]);
        } else {
            io.observe(els[j]);
        }
    }
}

function initPostLike() {
    var btn = document.getElementById('btn-like');
    var article = document.getElementById('post-article');
    if (!btn || !article) return;

    var postId = article.dataset.postId;
    var countEl = document.getElementById('like-count');

    function setLikeState(liked, likes) {
        btn.dataset.liked = liked ? '1' : '0';
        btn.classList.toggle('liked', liked);
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-solid', liked);
            icon.classList.toggle('fa-regular', !liked);
        }
        if (countEl) countEl.textContent = likes;
    }

    btn.addEventListener('click', function() {
        if (article.getAttribute('data-require-login') === '1') {
            var loginMsg = (window.APP_I18N && window.APP_I18N.like_login) ? window.APP_I18N.like_login : 'Please sign in to like this post';
            showToast('Sign in', loginMsg, 'warning');
            window.location.href = appUrl('login.php');
            return;
        }

        btn.disabled = true;
        var fd = new FormData();
        fd.append('post_id', postId);
        fd.append('toggle', '1');
        fd.append('csrf_token', getCsrfToken());

        fetch(appUrl('api/like.php'), { method: 'POST', body: fd })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    setLikeState(data.liked, data.likes);
                    var thanksTitle = (window.APP_I18N && window.APP_I18N.like_thanks) ? window.APP_I18N.like_thanks : 'Thanks!';
                    var removedTitle = (window.APP_I18N && window.APP_I18N.like_removed) ? window.APP_I18N.like_removed : 'Like removed';
                    if (data.liked) {
                        showToast(thanksTitle, '', 'success');
                    } else {
                        showToast('Updated', removedTitle, 'info');
                    }
                } else {
                    var failMsg = (window.APP_I18N && window.APP_I18N.like_failed) ? window.APP_I18N.like_failed : 'Please try again';
                    showToast('Failed', data.message || failMsg, 'error');
                }
                btn.disabled = false;
            })
            .catch(function() {
                showToast('Error', 'Could not connect to server', 'error');
                btn.disabled = false;
            });
    });
}

function initShareButtons() {
    var buttons = document.querySelectorAll('[data-share="copy"]');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function() {
            var url = this.dataset.url || window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    showToast('Copied', 'Link copied to clipboard', 'success');
                }).catch(function() {
                    prompt('Copy this link:', url);
                });
            } else {
                prompt('Copy this link:', url);
            }
        });
    }
}

function initBackToTop() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function initScrollEffects() {
    var navbar = document.getElementById('main-navbar');
    var backBtn = document.getElementById('back-to-top');
    var hasReadingBar = !!document.getElementById('reading-progress');
    var ticking = false;

    function handleScroll() {
        var scrollY = window.scrollY || window.pageYOffset;

        if (navbar) {
            if (scrollY > 60) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        }

        if (backBtn) {
            if (scrollY > 400) {
                backBtn.classList.add('visible');
            } else {
                backBtn.classList.remove('visible');
            }
        }

        if (hasReadingBar) {
            updateReadingProgress();
        }
        ticking = false;
    }

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(handleScroll);
            ticking = true;
        }
    }, { passive: true });

    handleScroll();
}

function initRegisterForm() {
    var agree = document.getElementById('agree_terms');
    var btn = document.getElementById('register_btn');
    if (!agree || !btn) {
        return;
    }

    function updateRegisterBtn() {
        btn.disabled = !agree.checked;
    }

    agree.addEventListener('change', updateRegisterBtn);
    updateRegisterBtn();
}

function initCategoryIconPickers() {
    var pickers = document.querySelectorAll('.category-icon-picker');
    for (var p = 0; p < pickers.length; p++) {
        setupCategoryIconPicker(pickers[p]);
    }
}

function setupCategoryIconPicker(root) {
    var hiddenInput = root.querySelector('.category-icon-input');
    var previewValue = root.querySelector('.icon-picker-preview-value');
    if (!hiddenInput || !previewValue) {
        return;
    }

    var tabs = root.querySelectorAll('.icon-picker-tab');
    var panels = root.querySelectorAll('.icon-picker-panel');
    var faButtons = root.querySelectorAll('.icon-pick-btn');
    var emojiButtons = root.querySelectorAll('.emoji-pick-btn');
    var emojiInput = root.querySelector('.emoji-custom-input');

    function setTab(tabName) {
        for (var i = 0; i < tabs.length; i++) {
            if (tabs[i].dataset.tab == tabName) {
                tabs[i].classList.add('active');
            } else {
                tabs[i].classList.remove('active');
            }
        }
        for (var j = 0; j < panels.length; j++) {
            if (panels[j].dataset.panel == tabName) {
                panels[j].classList.add('is-active');
            } else {
                panels[j].classList.remove('is-active');
            }
        }
    }

    function clearFaSelection() {
        for (var i = 0; i < faButtons.length; i++) {
            faButtons[i].classList.remove('is-selected');
        }
    }

    function clearEmojiSelection() {
        for (var i = 0; i < emojiButtons.length; i++) {
            emojiButtons[i].classList.remove('is-selected');
        }
    }

    function updatePreview(value, labelText) {
        hiddenInput.value = value;
        var glyphHtml = '';
        if (value.indexOf('emoji:') === 0) {
            var emoji = value.substring(6);
            glyphHtml = '<span class="cat-emoji-icon icon-preview-glyph" aria-hidden="true">' + emoji + '</span>';
        } else {
            glyphHtml = '<i class="fa-solid ' + value + ' icon-preview-glyph"></i>';
        }
        previewValue.innerHTML = glyphHtml + '<span class="icon-picker-preview-label">' + labelText + '</span>';
    }

    function selectFa(value, labelText) {
        clearFaSelection();
        clearEmojiSelection();
        if (emojiInput) {
            emojiInput.value = '';
        }
        for (var i = 0; i < faButtons.length; i++) {
            if (faButtons[i].dataset.value == value) {
                faButtons[i].classList.add('is-selected');
                if (!labelText && faButtons[i].title) {
                    labelText = faButtons[i].title;
                }
            }
        }
        if (!labelText) {
            labelText = value;
        }
        updatePreview(value, labelText);
        setTab('fa');
    }

    function selectEmoji(emoji) {
        if (!emoji) {
            return;
        }
        clearFaSelection();
        clearEmojiSelection();
        if (emojiInput) {
            emojiInput.value = emoji;
        }
        for (var i = 0; i < emojiButtons.length; i++) {
            if (emojiButtons[i].dataset.emoji == emoji) {
                emojiButtons[i].classList.add('is-selected');
            }
        }
        updatePreview('emoji:' + emoji, 'Emoji ' + emoji);
        setTab('emoji');
    }

    for (var t = 0; t < tabs.length; t++) {
        tabs[t].addEventListener('click', function() {
            setTab(this.dataset.tab);
        });
    }

    for (var f = 0; f < faButtons.length; f++) {
        faButtons[f].addEventListener('click', function() {
            selectFa(this.dataset.value, this.title || '');
        });
    }

    for (var e = 0; e < emojiButtons.length; e++) {
        emojiButtons[e].addEventListener('click', function() {
            selectEmoji(this.dataset.emoji);
        });
    }

    if (emojiInput) {
        emojiInput.addEventListener('input', function() {
            var val = emojiInput.value.trim();
            if (val == '') {
                return;
            }
            selectEmoji(val);
        });
    }

    var current = hiddenInput.value;
    if (current.indexOf('emoji:') === 0) {
        selectEmoji(current.substring(6));
    } else if (current != '') {
        selectFa(current, '');
    }
}

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        return meta.getAttribute('content');
    }
    var input = document.querySelector('input[name="csrf_token"]');
    if (input) {
        return input.value;
    }
    return '';
}

function initFollowButton() {
    var btn = document.getElementById('follow-btn');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function() {
        var userId = btn.dataset.userId;
        var following = btn.dataset.following == '1';
        var action = following ? 'unfollow' : 'follow';
        btn.disabled = true;

        var fd = new FormData();
        fd.append('action', action);
        fd.append('user_id', userId);
        fd.append('csrf_token', getCsrfToken());

        fetch(appUrl('api/follow.php'), { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                if (!data.success) {
                    showToast('Error', data.message || 'Could not update follow status', 'error');
                    return;
                }
                btn.dataset.following = data.following ? '1' : '0';
                if (data.following) {
                    btn.className = 'btn btn-sm btn-outline-custom';
                    btn.innerHTML = '<i class="fa-solid fa-user-minus"></i> Unfollow';
                } else {
                    btn.className = 'btn btn-sm btn-gradient';
                    btn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Follow';
                }
                showToast('Success', data.message, 'success');
            })
            .catch(function() {
                btn.disabled = false;
                showToast('Error', 'Network error', 'error');
            });
    });
}

var activeCommentEdit = null;

function cancelCommentEdit() {
    if (!activeCommentEdit) {
        return;
    }

    var form = activeCommentEdit.item.querySelector('.comment-edit-form');
    if (form) {
        form.remove();
    }
    activeCommentEdit.contentEl.hidden = false;
    activeCommentEdit.editBtn.disabled = false;
    activeCommentEdit = null;
}

function finishCommentEdit(item, editBtn, contentEl, nextText, status) {
    contentEl.textContent = nextText;
    contentEl.hidden = false;
    editBtn.dataset.content = nextText;
    editBtn.disabled = false;

    var form = item.querySelector('.comment-edit-form');
    if (form) {
        form.remove();
    }

    var meta = item.querySelector('.comment-meta');
    if (meta && status) {
        var badge = meta.querySelector('.comment-status-badge');
        if (status === 'pending') {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge bg-warning text-dark comment-status-badge';
                badge.textContent = 'Pending';
                meta.insertBefore(badge, meta.querySelector('.comment-actions'));
            }
        } else if (badge) {
            badge.remove();
        }
    }

    activeCommentEdit = null;
}

function saveCommentEdit(editBtn, item, contentEl, form, textarea) {
    var id = editBtn.dataset.id;
    var next = textarea.value.trim();
    if (next.length < 2) {
        showToast('Error', 'Comment must be at least 2 characters.', 'error');
        textarea.focus();
        return;
    }
    if (next.length > 1000) {
        showToast('Error', 'Comment is too long (max 1000 characters).', 'error');
        textarea.focus();
        return;
    }

    var saveBtn = form.querySelector('.comment-save-btn');
    saveBtn.disabled = true;

    var fd = new FormData();
    fd.append('action', 'edit');
    fd.append('comment_id', id);
    fd.append('content', next);
    fd.append('csrf_token', getCsrfToken());

    fetch(appUrl('api/comment.php'), { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            saveBtn.disabled = false;
            if (!data.success) {
                showToast('Error', data.message || 'Could not update comment', 'error');
                return;
            }
            finishCommentEdit(item, editBtn, contentEl, next, data.status || '');
            showToast('Success', data.message, 'success');
        })
        .catch(function() {
            saveBtn.disabled = false;
            showToast('Error', 'Network error', 'error');
        });
}

function startCommentEdit(editBtn) {
    if (activeCommentEdit && activeCommentEdit.editBtn === editBtn) {
        return;
    }
    cancelCommentEdit();

    var item = document.getElementById('comment-' + editBtn.dataset.id);
    if (!item) {
        return;
    }

    var contentEl = item.querySelector('.comment-content');
    if (!contentEl) {
        return;
    }

    var current = editBtn.dataset.content || contentEl.textContent || '';
    var form = document.createElement('div');
    form.className = 'comment-edit-form';

    var textarea = document.createElement('textarea');
    textarea.className = 'form-control form-control-custom comment-edit-input';
    textarea.rows = 3;
    textarea.value = current;
    textarea.setAttribute('maxlength', '1000');

    var actions = document.createElement('div');
    actions.className = 'comment-edit-actions';

    var saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'btn btn-sm btn-gradient comment-save-btn';
    saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save';

    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-sm btn-outline-custom comment-cancel-btn';
    cancelBtn.innerHTML = 'Cancel';

    actions.appendChild(saveBtn);
    actions.appendChild(cancelBtn);
    form.appendChild(textarea);
    form.appendChild(actions);

    contentEl.hidden = true;
    item.appendChild(form);
    editBtn.disabled = true;
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);

    activeCommentEdit = {
        item: item,
        contentEl: contentEl,
        editBtn: editBtn
    };

    cancelBtn.addEventListener('click', cancelCommentEdit);
    saveBtn.addEventListener('click', function() {
        saveCommentEdit(editBtn, item, contentEl, form, textarea);
    });
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            cancelCommentEdit();
        }
    });
}

function initCommentActions() {
    var editButtons = document.querySelectorAll('.comment-edit-btn');
    for (var i = 0; i < editButtons.length; i++) {
        editButtons[i].addEventListener('click', function() {
            startCommentEdit(this);
        });
    }

    var deleteButtons = document.querySelectorAll('.comment-delete-btn');
    for (var j = 0; j < deleteButtons.length; j++) {
        deleteButtons[j].addEventListener('click', function() {
            var btn = this;
            var id = btn.dataset.id;
            showConfirmDialog('Delete this comment?', function() {
                var fd = new FormData();
                fd.append('action', 'delete');
                fd.append('comment_id', id);
                fd.append('csrf_token', getCsrfToken());

                fetch(appUrl('api/comment.php'), { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.success) {
                            showToast('Error', data.message || 'Could not delete comment', 'error');
                            return;
                        }
                        var item = document.getElementById('comment-' + id);
                        if (item) {
                            item.remove();
                        }
                        showToast('Success', data.message, 'success');
                    });
            }, { title: 'Delete?', danger: true, confirmLabel: 'Delete' });
        });
    }
}

function initNotificationDropdown() {
    var list = document.getElementById('nav-notify-list');
    var dropdown = document.querySelector('.nav-notify-dropdown');
    var markAllBtn = document.getElementById('nav-notify-mark-all');
    if (!list || !dropdown) {
        return;
    }

    var loading = false;

    function applyNotificationData(data) {
        if (!data || !data.success) {
            return;
        }
        renderItems(data.items);
        updateNavNotifyBadge(data.count);
        updateNotifyUnreadLabel(data.count);
        updateAdminBadge('notifications', data.count);
    }

    function bindNotificationItems() {
        var items = list.querySelectorAll('.nav-notify-item[data-notify-id]');
        for (var n = 0; n < items.length; n++) {
            items[n].addEventListener('click', function(e) {
                var id = this.getAttribute('data-notify-id');
                var link = this.getAttribute('href');
                if (!id || !this.classList.contains('is-unread')) {
                    return;
                }
                e.preventDefault();
                var self = this;
                var fd = new FormData();
                fd.append('id', id);
                fd.append('csrf_token', getCsrfToken());
                fetch(appUrl('api/notifications.php?action=mark_read'), { method: 'POST', body: fd, keepalive: true })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            self.classList.remove('is-unread');
                            updateNavNotifyBadge(data.count);
                            updateNotifyUnreadLabel(data.count);
                            updateAdminBadge('notifications', data.count);
                        }
                        window.location.href = link;
                    })
                    .catch(function() {
                        window.location.href = link;
                    });
            });
        }
    }

    function renderItems(items) {
        if (!items || items.length === 0) {
            list.innerHTML = '<div class="nav-notify-empty text-secondary small">No notifications yet.</div>';
            return;
        }
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var unreadClass = item.is_read ? '' : ' is-unread';
            var supportClass = item.is_support ? ' is-support' : '';
            var link = appUrl(item.link || 'notifications.php');
            html += '<a href="' + link + '" class="nav-notify-item' + unreadClass + supportClass + '" data-notify-id="' + item.id + '">';
            html += '<span class="nav-notify-item-icon"><i class="fa-solid ' + item.icon + '"></i></span>';
            html += '<span class="nav-notify-item-body">';
            if (item.type_label) {
                html += '<span class="nav-notify-type">' + item.type_label + '</span>';
            }
            html += '<strong>' + item.title + '</strong>';
            html += '<span>' + item.message + '</span>';
            html += '<small>' + item.time + '</small>';
            html += '</span></a>';
        }
        list.innerHTML = html;
        bindNotificationItems();
    }

    function loadNotifications(silent) {
        if (loading) {
            return;
        }
        loading = true;
        if (!silent) {
            list.innerHTML = '<div class="nav-notify-empty text-secondary small">Loading...</div>';
        }

        fetch(appUrl('api/notifications.php'), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loading = false;
                if (!data.success) {
                    if (!silent) {
                        list.innerHTML = '<div class="nav-notify-empty text-secondary small">Could not load notifications.</div>';
                    }
                    return;
                }
                applyNotificationData(data);
            })
            .catch(function() {
                loading = false;
                if (!silent) {
                    list.innerHTML = '<div class="nav-notify-empty text-secondary small">Could not load notifications.</div>';
                }
            });
    }

    window.refreshNavNotifications = loadNotifications;

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var fd = new FormData();
            fd.append('csrf_token', getCsrfToken());
            fetch(appUrl('api/notifications.php?action=mark_all'), { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        loadNotifications(true);
                    }
                });
        });
    }

    dropdown.addEventListener('show.bs.dropdown', function() {
        loadNotifications(false);
    });

    loadNotifications(true);
}

function initNavDropdowns() {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    var toggles = document.querySelectorAll('.nav-menu-pills [data-bs-toggle="dropdown"]');
    for (var i = 0; i < toggles.length; i++) {
        toggles[i].addEventListener('click', function(e) {
            e.preventDefault();
        });

        bootstrap.Dropdown.getOrCreateInstance(toggles[i], {
            autoClose: true,
            boundary: 'viewport',
            popperConfig: function(defaultBsPopperConfig) {
                defaultBsPopperConfig.strategy = 'fixed';
                defaultBsPopperConfig.modifiers = defaultBsPopperConfig.modifiers || [];
                defaultBsPopperConfig.modifiers.push({
                    name: 'preventOverflow',
                    options: { boundary: 'viewport' }
                });
                return defaultBsPopperConfig;
            }
        });
    }
}

function initAdminToolbarNav() {
    var wrap = document.getElementById('dashToolbarNavWrap');
    var nav = document.getElementById('dashToolbarNav');
    if (!wrap || !nav) {
        return;
    }

    function updateScrollState() {
        var maxScroll = nav.scrollWidth - nav.clientWidth;
        var left = nav.scrollLeft;
        wrap.classList.toggle('can-scroll-left', left > 4);
        wrap.classList.toggle('can-scroll-right', maxScroll - left > 4);
    }

    nav.addEventListener('scroll', updateScrollState, { passive: true });
    window.addEventListener('resize', updateScrollState);

    nav.addEventListener('wheel', function(e) {
        if (nav.scrollWidth <= nav.clientWidth) {
            return;
        }
        if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) {
            return;
        }
        e.preventDefault();
        nav.scrollLeft += e.deltaY;
    }, { passive: false });

    updateScrollState();
}

function initCommentReplies() {
    var form = document.getElementById('comment-form');
    var parentInput = document.getElementById('comment-parent-id');
    var banner = document.getElementById('comment-reply-banner');
    var authorEl = document.getElementById('comment-reply-author');
    var cancelBtn = document.getElementById('comment-reply-cancel');
    var contentInput = document.getElementById('comment-content');
    if (!form || !parentInput) {
        return;
    }

    function clearReply() {
        parentInput.value = '0';
        if (banner) {
            banner.hidden = true;
        }
    }

    document.querySelectorAll('.comment-reply-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            parentInput.value = btn.getAttribute('data-parent') || '0';
            if (banner && authorEl) {
                authorEl.textContent = btn.getAttribute('data-author') || '';
                banner.hidden = false;
            }
            if (contentInput) {
                contentInput.focus();
            }
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', clearReply);
    }
}

function initPostBookmark() {
    var btn = document.getElementById('btn-bookmark');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function() {
        if (btn.getAttribute('data-require-login') === '1') {
            var msg = (window.APP_I18N && window.APP_I18N.bookmark_login) ? window.APP_I18N.bookmark_login : 'Please sign in to save posts';
            showToast('Sign in', msg, 'warning');
            window.location.href = appUrl('login.php');
            return;
        }

        var postId = btn.getAttribute('data-post-id');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('post_id', postId);
        fd.append('csrf_token', getCsrfToken());

        fetch(appUrl('api/bookmark.php'), { method: 'POST', body: fd })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.success) {
                    showToast('Failed', data.message || 'Could not update bookmark', 'error');
                    btn.disabled = false;
                    return;
                }

                var saved = !!data.bookmarked;
                btn.dataset.bookmarked = saved ? '1' : '0';
                btn.classList.toggle('is-bookmarked', saved);
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-solid', saved);
                    icon.classList.toggle('fa-regular', !saved);
                }
                var savedLabel = (window.APP_I18N && window.APP_I18N.bookmark_saved) ? window.APP_I18N.bookmark_saved : 'Saved';
                var removedLabel = (window.APP_I18N && window.APP_I18N.bookmark_removed) ? window.APP_I18N.bookmark_removed : 'Removed';
                showToast(saved ? savedLabel : 'Updated', saved ? '' : removedLabel, saved ? 'success' : 'info');
                btn.disabled = false;
            })
            .catch(function() {
                showToast('Failed', 'Network error', 'error');
                btn.disabled = false;
            });
    });
}

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', function() {
        navigator.serviceWorker.register(appUrl('sw.js')).catch(function() {
            /* ignore registration errors in unsupported contexts */
        });
    });
}

function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

function initWebPush() {
    var btn = document.getElementById('btn-push-enable');
    if (!btn) {
        return;
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        btn.disabled = true;
        btn.textContent = btn.getAttribute('data-unsupported') || 'Not supported';
        return;
    }

    function setBtnState(enabled) {
        if (enabled) {
            btn.classList.remove('btn-gradient');
            btn.classList.add('btn-outline-custom');
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i> ' + (btn.getAttribute('data-disable-label') || 'Disable push');
            btn.setAttribute('data-enabled', '1');
        } else {
            btn.classList.add('btn-gradient');
            btn.classList.remove('btn-outline-custom');
            btn.innerHTML = '<i class="fa-solid fa-bell"></i> ' + (btn.getAttribute('data-enable-label') || 'Enable push notifications');
            btn.setAttribute('data-enabled', '0');
        }
    }

    function fetchVapidKey() {
        return fetch(appUrl('api/push-vapid.php'), { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.ok || !data.publicKey) {
                    throw new Error('not_configured');
                }
                return data.publicKey;
            });
    }

    function getSubscription() {
        return navigator.serviceWorker.ready.then(function(reg) {
            return reg.pushManager.getSubscription();
        });
    }

    btn.addEventListener('click', function() {
        btn.disabled = true;
        var enabled = btn.getAttribute('data-enabled') === '1';

        if (enabled) {
            getSubscription().then(function(sub) {
                if (!sub) {
                    setBtnState(false);
                    btn.disabled = false;
                    return null;
                }
                var endpoint = sub.endpoint;
                return sub.unsubscribe().then(function() {
                    var fd = new FormData();
                    fd.append('action', 'unsubscribe');
                    fd.append('endpoint', endpoint);
                    fd.append('csrf_token', getCsrfToken());
                    return fetch(appUrl('api/push-subscribe.php'), { method: 'POST', body: fd });
                });
            }).then(function() {
                setBtnState(false);
                showToast('Push', btn.getAttribute('data-disabled-msg') || 'Push notifications disabled', 'info');
                btn.disabled = false;
            }).catch(function() {
                btn.disabled = false;
            });
            return;
        }

        Notification.requestPermission().then(function(perm) {
            if (perm !== 'granted') {
                showToast('Push', btn.getAttribute('data-denied-msg') || 'Permission denied', 'warning');
                btn.disabled = false;
                return null;
            }
            return fetchVapidKey();
        }).then(function(publicKey) {
            if (!publicKey) {
                return null;
            }
            return navigator.serviceWorker.ready.then(function(reg) {
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey)
                });
            });
        }).then(function(sub) {
            if (!sub) {
                return null;
            }
            var json = sub.toJSON();
            var fd = new FormData();
            fd.append('action', 'subscribe');
            fd.append('endpoint', json.endpoint);
            fd.append('p256dh', json.keys.p256dh);
            fd.append('auth', json.keys.auth);
            fd.append('csrf_token', getCsrfToken());
            return fetch(appUrl('api/push-subscribe.php'), { method: 'POST', body: fd });
        }).then(function(res) {
            if (!res) {
                return;
            }
            return res.json();
        }).then(function(data) {
            if (data && data.ok) {
                setBtnState(true);
                showToast('Push', btn.getAttribute('data-enabled-msg') || 'Push notifications enabled', 'success');
            } else if (data && data.error === 'push_not_configured') {
                showToast('Push', btn.getAttribute('data-not-configured') || 'Push not configured on server', 'warning');
            }
            btn.disabled = false;
        }).catch(function() {
            showToast('Push', 'Could not enable push notifications', 'error');
            btn.disabled = false;
        });
    });

    getSubscription().then(function(sub) {
        setBtnState(!!sub);
    }).catch(function() {
        setBtnState(false);
    });
}
