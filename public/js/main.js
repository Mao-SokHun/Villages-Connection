document.addEventListener('DOMContentLoaded', function() {
    initToastContainer();
    initThemeToggle();
    initFlashModal();
    initCustomSelects();
    initCategoryIconPickers();
    initRegisterForm();
    initReadingProgress();
    initSmoothScroll();
    initScrollReveal();
    initPostLike();
    initShareButtons();
    initBackToTop();
    initScrollEffects();
    initFollowButton();
    initCommentActions();
    initNotificationDropdown();
    initNavDropdowns();
    initAdminToolbarNav();
});

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

function initSmoothScroll() {
    var links = document.querySelectorAll('a[href^="#"]');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href == '#') return;
            var target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                var navbar = document.getElementById('main-navbar');
                var offset = navbar ? navbar.offsetHeight + 16 : 80;
                var top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top: top, behavior: 'smooth' });
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
        btn.disabled = true;
        var fd = new FormData();
        fd.append('post_id', postId);
        fd.append('toggle', '1');
        fd.append('csrf_token', getCsrfToken());

        fetch('api/like.php', { method: 'POST', body: fd })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    setLikeState(data.liked, data.likes);
                    if (data.liked) {
                        showToast('Thanks!', 'You liked this post', 'success');
                    } else {
                        showToast('Updated', 'Like removed', 'info');
                    }
                } else {
                    showToast('Failed', data.message || 'Please try again', 'error');
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

        updateReadingProgress();
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

        fetch('api/follow.php', { method: 'POST', body: fd })
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

function initCommentActions() {
    var editButtons = document.querySelectorAll('.comment-edit-btn');
    for (var i = 0; i < editButtons.length; i++) {
        editButtons[i].addEventListener('click', function() {
            var id = this.dataset.id;
            var current = this.dataset.content || '';
            var next = window.prompt('Edit your comment:', current);
            if (next == null) {
                return;
            }

            var fd = new FormData();
            fd.append('action', 'edit');
            fd.append('comment_id', id);
            fd.append('content', next);
            fd.append('csrf_token', getCsrfToken());

            fetch('api/comment.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) {
                        showToast('Error', data.message || 'Could not update comment', 'error');
                        return;
                    }
                    var item = document.getElementById('comment-' + id);
                    if (item) {
                        var contentEl = item.querySelector('.comment-content');
                        if (contentEl) {
                            contentEl.textContent = next;
                        }
                    }
                    showToast('Success', data.message, 'success');
                });
        });
    }

    var deleteButtons = document.querySelectorAll('.comment-delete-btn');
    for (var j = 0; j < deleteButtons.length; j++) {
        deleteButtons[j].addEventListener('click', function() {
            if (!window.confirm('Delete this comment?')) {
                return;
            }
            var id = this.dataset.id;
            var fd = new FormData();
            fd.append('action', 'delete');
            fd.append('comment_id', id);
            fd.append('csrf_token', getCsrfToken());

            fetch('api/comment.php', { method: 'POST', body: fd })
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
        });
    }
}

function initNotificationDropdown() {
    var list = document.getElementById('nav-notify-list');
    if (!list) {
        return;
    }

    function renderItems(items) {
        if (!items || items.length == 0) {
            list.innerHTML = '<div class="nav-notify-empty text-secondary small">No notifications yet.</div>';
            return;
        }
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var unreadClass = item.is_read ? '' : ' is-unread';
            html += '<a href="' + (item.link || 'notifications.php') + '" class="nav-notify-item' + unreadClass + '">';
            html += '<span class="nav-notify-item-icon"><i class="fa-solid ' + item.icon + '"></i></span>';
            html += '<span class="nav-notify-item-body">';
            html += '<strong>' + item.title + '</strong>';
            html += '<span>' + item.message + '</span>';
            html += '<small>' + item.time + '</small>';
            html += '</span></a>';
        }
        list.innerHTML = html;
    }

    function refreshBadge(count) {
        var badge = document.getElementById('nav-notify-badge');
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
            }
        } else if (badge) {
            badge.remove();
        }
    }

    fetch('api/notifications.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                list.innerHTML = '<div class="nav-notify-empty text-secondary small">Could not load notifications.</div>';
                return;
            }
            renderItems(data.items);
            refreshBadge(data.count);
        });
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
