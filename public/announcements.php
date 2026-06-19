<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$selected_id = 0;
if (isset($_GET['id'])) {
    $selected_id = (int) $_GET['id'];
}

$announcements = array();
try {
    $sql = "SELECT * FROM announcements
            WHERE is_active = TRUE
            AND (starts_at IS NULL OR starts_at <= CURRENT_TIMESTAMP)
            AND (ends_at IS NULL OR ends_at >= CURRENT_TIMESTAMP)
            ORDER BY id DESC";
    $announcements = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $announcements = array();
}

$selected_announcement = null;
if ($selected_id > 0) {
    foreach ($announcements as $item) {
        if ((int) $item['id'] === $selected_id) {
            $selected_announcement = $item;
            break;
        }
    }
}
if ($selected_announcement === null && count($announcements) > 0) {
    $selected_announcement = $announcements[0];
}

$page_title = 'Announcements';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass-panel p-4 reveal">
            <h3 class="text-white mb-3"><i class="fa-solid fa-bullhorn text-info me-2"></i>Announcements</h3>

            <?php if ($selected_announcement): ?>
            <h4 class="mb-2"><?php echo htmlspecialchars($selected_announcement['title']); ?></h4>
            <p class="text-secondary small mb-3"><?php echo date('M j, Y H:i', strtotime($selected_announcement['created_at'])); ?></p>
            <div class="mb-3"><?php echo nl2br(htmlspecialchars($selected_announcement['message'])); ?></div>

            <?php if (!empty($selected_announcement['link_url'])): ?>
            <?php $announcement_link = safe_http_href($selected_announcement['link_url']); ?>
            <?php if ($announcement_link !== ''): ?>
            <a href="<?php echo htmlspecialchars($announcement_link); ?>" class="btn btn-outline-custom btn-sm" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open related link
            </a>
            <?php endif; ?>
            <?php endif; ?>
            <?php else: ?>
            <p class="text-secondary mb-0">No active announcements right now.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="glass-panel p-4 reveal">
            <h5 class="text-white mb-3">Recent</h5>
            <?php if (count($announcements) === 0): ?>
            <p class="text-secondary mb-0">No announcements yet.</p>
            <?php else: ?>
            <div class="d-grid gap-2">
                <?php foreach ($announcements as $item): ?>
                <?php $is_active_item = $selected_announcement && (int) $selected_announcement['id'] === (int) $item['id']; ?>
                <a href="<?php echo app_url('announcements.php?id=' . (int) $item['id']); ?>" class="btn btn-sm <?php echo $is_active_item ? 'btn-gradient' : 'btn-outline-custom'; ?> text-start">
                    <div class="fw-semibold"><?php echo htmlspecialchars(excerpt($item['title'], 44)); ?></div>
                    <div class="small <?php echo $is_active_item ? 'text-white-50' : 'text-secondary'; ?>">
                        <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
