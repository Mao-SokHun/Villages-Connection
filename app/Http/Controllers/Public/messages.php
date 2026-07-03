<?php
requireLogin();

$user_id  = (int) $_SESSION['user_id'];
$conv_id  = isset($_GET['conv']) ? (int) $_GET['conv'] : 0;
$new_to   = isset($_GET['to']) ? (int) $_GET['to'] : 0;

// If starting a new conversation with a user
$active_conv = null;
$other_user  = null;

if ($new_to > 0 && $new_to !== $user_id) {
    // Get or create conversation
    $to_stmt = $pdo->prepare("SELECT id, name, avatar, email FROM users WHERE id = :id AND COALESCE(is_banned, FALSE) = FALSE AND COALESCE(account_status, 'active') != 'deleted'");
    $to_stmt->execute(array('id' => $new_to));
    $other_user = $to_stmt->fetch();
    if ($other_user) {
        $active_conv = dm_get_or_create_conversation($pdo, $user_id, (int) $other_user['id']);
        if ($active_conv) {
            $conv_id = (int) $active_conv['id'];
        }
    }
}

$conversations = dm_get_user_conversations($pdo, $user_id);

// Open specific conversation
if ($conv_id > 0 && !$active_conv) {
    foreach ($conversations as $c) {
        if ((int) $c['id'] === $conv_id) {
            $active_conv = $c;
            // Load full other_user info
            $ou_stmt = $pdo->prepare('SELECT id, name, avatar, email FROM users WHERE id = :id');
            $ou_stmt->execute(array('id' => (int) $c['other_user_id']));
            $other_user = $ou_stmt->fetch();
            break;
        }
    }
}

if ($active_conv && $conv_id > 0 && dm_user_can_access_conversation($pdo, $conv_id, $user_id)) {
    dm_mark_read($pdo, $conv_id, $user_id);
    $messages = dm_get_messages($pdo, $conv_id, 80);
} else {
    $messages = array();
}

$page_title = 'Messages';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<style>
.dm-layout { display: flex; gap: 1rem; height: 72vh; min-height: 420px; }
.dm-sidebar { width: 280px; flex-shrink: 0; display: flex; flex-direction: column; }
.dm-sidebar-list { overflow-y: auto; flex: 1; }
.dm-conv-item { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,.05); text-decoration: none; transition: background .15s; }
.dm-conv-item:hover, .dm-conv-item.active { background: rgba(255,255,255,.06); }
.dm-conv-info { flex: 1; min-width: 0; }
.dm-conv-name { color: var(--text-primary); font-weight: 600; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dm-conv-preview { color: var(--text-secondary); font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dm-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); flex-shrink: 0; }
.dm-chat-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.dm-messages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .5rem; }
.dm-msg { max-width: 72%; }
.dm-msg.mine { align-self: flex-end; }
.dm-msg.theirs { align-self: flex-start; }
.dm-msg-bubble { padding: .5rem .85rem; border-radius: 1.2rem; font-size: .9rem; line-height: 1.45; word-break: break-word; }
.dm-msg.mine .dm-msg-bubble { background: var(--primary); color: #fff; border-bottom-right-radius: .3rem; }
.dm-msg.theirs .dm-msg-bubble { background: rgba(255,255,255,.1); color: var(--text-primary); border-bottom-left-radius: .3rem; }
.dm-msg-time { font-size: .7rem; color: var(--text-secondary); margin-top: .2rem; }
.dm-msg.mine .dm-msg-time { text-align: right; }
.dm-input-bar { display: flex; gap: .5rem; padding: .75rem 1rem; border-top: 1px solid rgba(255,255,255,.1); }
.dm-input-bar textarea { flex: 1; resize: none; min-height: 42px; max-height: 120px; }
.dm-empty { display: flex; align-items: center; justify-content: center; flex: 1; color: var(--text-secondary); font-size: .95rem; text-align: center; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-white mb-0"><i class="fa-solid fa-comments text-warning me-2"></i> Messages</h3>
    <a href="<?php echo app_url('messages.php?new=1'); ?>" class="btn btn-gradient btn-sm" id="btn-new-msg">
        <i class="fa-solid fa-pen-to-square"></i> New Message
    </a>
</div>

<!-- New message modal -->
<div class="modal fade" id="newMsgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">New Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="dm-user-search" class="form-control form-control-custom" placeholder="Search member name…" autocomplete="off">
                <div id="dm-user-results" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

<div class="glass-panel p-0 overflow-hidden reveal">
    <div class="dm-layout">
        <!-- Sidebar: conversation list -->
        <div class="dm-sidebar border-end border-secondary border-opacity-25">
            <div class="p-3 border-bottom border-secondary border-opacity-25">
                <span class="small text-secondary fw-semibold">CONVERSATIONS</span>
            </div>
            <div class="dm-sidebar-list">
                <?php if (count($conversations) === 0): ?>
                <div class="p-4 text-secondary small">No conversations yet. Start one above.</div>
                <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                <?php $is_active = $active_conv && (int) $active_conv['id'] === (int) $c['id']; ?>
                <a href="<?php echo app_url('messages.php?conv=' . (int) $c['id']); ?>" class="dm-conv-item <?php if ($is_active) echo 'active'; ?>">
                    <?php echo render_user_avatar($c['other_name'], $c['other_avatar'] ?? '', '', $c['other_email'] ?? ''); ?>
                    <div class="dm-conv-info">
                        <div class="dm-conv-name"><?php echo htmlspecialchars($c['other_name']); ?></div>
                        <div class="dm-conv-preview"><?php echo htmlspecialchars(isset($c['last_body']) ? excerpt($c['last_body'], 36) : ''); ?></div>
                    </div>
                    <?php if ((int) $c['unread_count'] > 0): ?>
                    <span class="dm-unread-dot" title="<?php echo (int) $c['unread_count']; ?> unread"></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat area -->
        <div class="dm-chat-area">
            <?php if ($active_conv && $other_user): ?>
            <div class="px-3 py-2 border-bottom border-secondary border-opacity-25 d-flex align-items-center gap-2">
                <?php echo render_user_avatar($other_user['name'], $other_user['avatar'] ?? '', '', $other_user['email'] ?? ''); ?>
                <a href="<?php echo app_url('profile.php?id=' . (int) $other_user['id']); ?>" class="text-white fw-semibold text-decoration-none">
                    <?php echo htmlspecialchars($other_user['name']); ?>
                </a>
            </div>

            <div class="dm-messages" id="dm-messages-list">
                <?php foreach ($messages as $msg): ?>
                <?php $mine = (int) $msg['sender_id'] === $user_id; ?>
                <div class="dm-msg <?php echo $mine ? 'mine' : 'theirs'; ?>" data-msg-id="<?php echo (int) $msg['id']; ?>">
                    <div class="dm-msg-bubble"><?php echo nl2br(htmlspecialchars($msg['body'])); ?></div>
                    <div class="dm-msg-time"><?php echo date('M j, H:i', strtotime($msg['created_at'])); ?><?php if ($mine): ?> <a href="#" class="text-danger small dm-delete-msg" data-id="<?php echo (int) $msg['id']; ?>">×</a><?php endif; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <form id="dm-send-form" class="dm-input-bar" data-conv-id="<?php echo (int) $conv_id; ?>" data-to-user-id="<?php echo (int) $other_user['id']; ?>">
                <textarea id="dm-body" class="form-control form-control-custom" rows="1" placeholder="Type a message…" required maxlength="2000"></textarea>
                <button type="submit" class="btn btn-gradient px-3"><i class="fa-solid fa-paper-plane"></i></button>
            </form>

            <?php else: ?>
            <div class="dm-empty">
                <div>
                    <i class="fa-regular fa-comments fa-3x mb-3 d-block text-center text-secondary"></i>
                    Select a conversation or <a href="#" id="start-new-link">start a new one</a>.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Scroll messages to bottom
    var msgList = document.getElementById('dm-messages-list');
    if (msgList) { msgList.scrollTop = msgList.scrollHeight; }

    // Send form
    var sendForm = document.getElementById('dm-send-form');
    if (sendForm) {
        var bodyEl = document.getElementById('dm-body');
        sendForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var body = bodyEl.value.trim();
            if (!body) return;
            var convId = sendForm.dataset.convId;
            var toUserId = sendForm.dataset.toUserId;
            var fd = new FormData();
            fd.append('action', 'send');
            fd.append('to_user_id', toUserId);
            fd.append('body', body);
            fd.append('csrf_token', csrfToken);
            bodyEl.value = '';
            fetch(window.APP_BASE + 'api/message.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.ok) {
                        var div = document.createElement('div');
                        div.className = 'dm-msg mine';
                        div.innerHTML = '<div class="dm-msg-bubble">' + escHtml(body) + '</div><div class="dm-msg-time">Just now <a href="#" class="text-danger small dm-delete-msg" data-id="' + d.message_id + '">×</a></div>';
                        msgList.appendChild(div);
                        msgList.scrollTop = msgList.scrollHeight;
                        bindDeleteBtns();
                    }
                });
        });

        // Auto-grow textarea
        bodyEl.addEventListener('input', function() {
            bodyEl.style.height = 'auto';
            bodyEl.style.height = Math.min(bodyEl.scrollHeight, 120) + 'px';
        });
        // Send on Enter (Shift+Enter = newline)
        bodyEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendForm.dispatchEvent(new Event('submit')); }
        });
    }

    function bindDeleteBtns() {
        document.querySelectorAll('.dm-delete-msg').forEach(function(btn) {
            btn.onclick = function(e) {
                e.preventDefault();
                if (!confirm('Delete this message?')) return;
                var msgId = btn.dataset.id;
                var fd = new FormData();
                fd.append('action', 'delete');
                fd.append('message_id', msgId);
                fd.append('csrf_token', csrfToken);
                fetch(window.APP_BASE + 'api/message.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { if (d.ok) btn.closest('.dm-msg').remove(); });
            };
        });
    }
    bindDeleteBtns();

    // New message modal
    var newMsgBtn = document.getElementById('btn-new-msg');
    var startNewLink = document.getElementById('start-new-link');
    function openNewMsgModal(e) {
        if (e) e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('newMsgModal'));
        modal.show();
    }
    if (newMsgBtn) newMsgBtn.addEventListener('click', openNewMsgModal);
    if (startNewLink) startNewLink.addEventListener('click', openNewMsgModal);

    // User search for new conversation
    var searchEl = document.getElementById('dm-user-search');
    var resultsEl = document.getElementById('dm-user-results');
    if (searchEl) {
        var timer;
        searchEl.addEventListener('input', function() {
            clearTimeout(timer);
            var q = searchEl.value.trim();
            if (q.length < 2) { resultsEl.innerHTML = ''; return; }
            timer = setTimeout(function() {
                fetch(window.APP_BASE + 'api/notifications.php?action=search_users&q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        resultsEl.innerHTML = '';
                        if (!d.users || d.users.length === 0) { resultsEl.innerHTML = '<p class="text-secondary small mt-2">No members found.</p>'; return; }
                        d.users.forEach(function(u) {
                            var a = document.createElement('a');
                            a.href = window.location.pathname + '?to=' + u.id;
                            a.className = 'dm-conv-item text-decoration-none rounded';
                            a.innerHTML = '<span class="text-white">' + escHtml(u.name) + '</span>';
                            resultsEl.appendChild(a);
                        });
                    });
            }, 300);
        });
    }

    function escHtml(t) {
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
