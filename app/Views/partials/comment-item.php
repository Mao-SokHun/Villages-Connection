<?php
if (!isset($comment) || !is_array($comment)) {
    return;
}

$depth = 0;
if (isset($comment_depth)) {
    $depth = (int) $comment_depth;
}

$post_slug = '';
if (isset($comment_post_slug)) {
    $post_slug = $comment_post_slug;
}

$is_post_owner = false;
if (isset($comment_is_post_owner)) {
    $is_post_owner = $comment_is_post_owner;
}

$can_manage_comment = can_manage_comment($comment);
$can_moderate_comment = false;
if ($is_post_owner || isAdmin()) {
    if ($comment['status'] == 'pending' && isLoggedIn() && (int) $comment['user_id'] != (int) $_SESSION['user_id']) {
        $can_moderate_comment = true;
    }
}

$reply_count = 0;
if (isset($comment['replies']) && is_array($comment['replies'])) {
    $reply_count = count($comment['replies']);
}
?>
<div class="comment-item<?php if ($depth > 0) echo ' comment-item-reply'; ?>" id="comment-<?php echo (int) $comment['id']; ?>" data-comment-id="<?php echo (int) $comment['id']; ?>" style="--comment-depth: <?php echo (int) $depth; ?>">
    <div class="comment-meta">
        <strong class="text-white"><?php echo htmlspecialchars($comment['author_name']); ?></strong>
        <span class="text-secondary small"><?php echo format_display_date($comment['created_at']); ?></span>
        <?php if ($comment['status'] == 'pending'): ?>
        <span class="badge bg-warning text-dark comment-status-badge"><?php echo __('comments.pending'); ?></span>
        <?php endif; ?>
        <?php if ($can_moderate_comment): ?>
        <span class="comment-actions ms-auto">
            <form method="POST" action="<?php echo htmlspecialchars(post_url($post_slug, '/') . '#comment-' . (int) $comment['id']); ?>" class="d-inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                <button type="submit" name="moderate_comment" value="approve" class="btn btn-sm btn-success py-0 px-2"><i class="fa-solid fa-check"></i></button>
                <button type="submit" name="moderate_comment" value="reject" class="btn btn-sm btn-outline-custom text-danger py-0 px-2"><i class="fa-solid fa-xmark"></i></button>
            </form>
        </span>
        <?php elseif ($can_manage_comment): ?>
        <span class="comment-actions ms-auto">
            <button type="button" class="btn btn-sm btn-outline-custom py-0 px-2 comment-edit-btn" data-id="<?php echo (int) $comment['id']; ?>" data-content="<?php echo htmlspecialchars($comment['content']); ?>"><i class="fa-solid fa-pen"></i></button>
            <button type="button" class="btn btn-sm btn-outline-custom text-danger py-0 px-2 comment-delete-btn" data-id="<?php echo (int) $comment['id']; ?>"><i class="fa-solid fa-trash"></i></button>
        </span>
        <?php endif; ?>
    </div>
    <p class="text-secondary mb-0 comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>

    <?php if (isLoggedIn() && $depth < 1): ?>
    <button type="button" class="btn btn-link btn-sm px-0 comment-reply-btn" data-parent="<?php echo (int) $comment['id']; ?>" data-author="<?php echo htmlspecialchars($comment['author_name']); ?>">
        <i class="fa-solid fa-reply me-1"></i><?php echo __('comments.reply'); ?>
    </button>
    <?php endif; ?>

    <?php if (isset($comment['replies']) && count($comment['replies']) > 0): ?>
    <div class="comment-replies">
        <?php foreach ($comment['replies'] as $reply):
            $comment = $reply;
            $comment_depth = $depth + 1;
            include __DIR__ . '/comment-item.php';
        endforeach; ?>
    </div>
    <?php endif; ?>
</div>
