<?php

$page_title = __('challenges.title');
$page_description = __('challenges.desc');

$active = array();
$recent_winners = array();
$leaderboards = array();
try {
    $active = $pdo->query("SELECT * FROM community_challenges
        WHERE status = 'active' AND end_date >= CURRENT_DATE
        ORDER BY end_date ASC, id DESC")->fetchAll();
    $recent_winners = $pdo->query("SELECT * FROM community_challenges
        WHERE status IN ('completed', 'closed')
        ORDER BY end_date DESC, id DESC
        LIMIT 6")->fetchAll();
    foreach ($active as $challenge) {
        $lb_stmt = $pdo->prepare("SELECT u.id AS user_id, u.name AS user_name, u.avatar AS user_avatar, COUNT(p.id) AS post_count
            FROM posts p
            INNER JOIN users u ON u.id = p.user_id
            WHERE p.challenge_id = :cid
              AND p.status = 'Published'
              AND (p.expires_at IS NULL OR p.expires_at > CURRENT_TIMESTAMP)
            GROUP BY u.id, u.name, u.avatar
            ORDER BY post_count DESC, u.id ASC
            LIMIT 5");
        $lb_stmt->execute(array('cid' => (int) $challenge['id']));
        $leaderboards[(int) $challenge['id']] = $lb_stmt->fetchAll();
    }
} catch (Exception $e) {
    $active = array();
    $recent_winners = array();
    $leaderboards = array();
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="glass-panel p-4 p-md-5 mb-4">
    <h1 class="text-white mb-2"><i class="fa-solid fa-trophy text-warning me-2"></i><?php echo __('challenges.title'); ?></h1>
    <p class="text-secondary mb-4"><?php echo __('challenges.desc'); ?></p>
    <a href="<?php echo create_post_url(); ?>" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> <?php echo __('challenges.create_post'); ?></a>
</div>

<?php if (count($active) > 0): ?>
<div class="row g-4 mb-4">
    <?php foreach ($active as $challenge): ?>
    <div class="col-lg-6">
        <div class="glass-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h4 class="text-white mb-0"><?php echo htmlspecialchars($challenge['title']); ?></h4>
                <span class="badge bg-success"><?php echo __('challenges.active'); ?></span>
            </div>
            <p class="text-secondary small mb-2"><?php echo nl2br(htmlspecialchars($challenge['description'])); ?></p>
            <div class="text-secondary small mb-3">
                <span class="me-3"><i class="fa-regular fa-calendar me-1"></i><?php echo htmlspecialchars($challenge['start_date']); ?> <?php echo __('challenges.to'); ?> <?php echo htmlspecialchars($challenge['end_date']); ?></span>
                <span><i class="fa-solid fa-bullseye me-1"></i><?php echo __('challenges.goal', array('target' => $challenge['goal_target'], 'type' => $challenge['goal_type'])); ?></span>
            </div>
            <?php $lb_rows = isset($leaderboards[(int) $challenge['id']]) ? $leaderboards[(int) $challenge['id']] : array(); ?>
            <?php if (count($lb_rows) > 0): ?>
            <div class="mt-3">
                <div class="text-white small mb-2"><i class="fa-solid fa-ranking-star me-1 text-warning"></i><?php echo __('challenges.leaderboard'); ?></div>
                <?php foreach ($lb_rows as $idx => $lb): ?>
                <div class="d-flex justify-content-between text-secondary small mb-1">
                    <span><?php echo ($idx + 1) . '. ' . htmlspecialchars($lb['user_name']); ?></span>
                    <span><?php echo __('challenges.posts_count', array('count' => (int) $lb['post_count'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($challenge['reward_text'])): ?>
            <div class="alert alert-info py-2 px-3 mb-0 small"><i class="fa-solid fa-award me-1"></i><?php echo htmlspecialchars($challenge['reward_text']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="glass-panel p-4 mb-4">
    <p class="text-secondary mb-0"><?php echo __('challenges.none_active'); ?></p>
</div>
<?php endif; ?>

<?php if (count($recent_winners) > 0): ?>
<div class="glass-panel p-4">
    <h5 class="text-white mb-3"><i class="fa-solid fa-medal text-info me-2"></i><?php echo __('challenges.recent'); ?></h5>
    <div class="row g-3">
        <?php foreach ($recent_winners as $challenge): ?>
        <div class="col-md-6 col-lg-4">
            <div class="glass-panel-sm p-3 h-100">
                <strong class="text-white d-block"><?php echo htmlspecialchars($challenge['title']); ?></strong>
                <span class="text-secondary small d-block mt-1"><?php echo htmlspecialchars($challenge['end_date']); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
