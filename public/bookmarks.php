<?php
require_once dirname(__DIR__) . '/bootstrap.php';
requireLogin();

$page = 1;
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];
}

$data = get_user_bookmarks($pdo, (int) $_SESSION['user_id'], $page, 12);
$page_title = __('bookmarks.title');

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-10">
        <div class="glass-panel p-4 reveal">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h1 class="text-white h3 mb-0"><i class="fa-solid fa-bookmark text-warning me-2"></i><?php echo __('bookmarks.title'); ?></h1>
                <span class="text-secondary small"><?php echo __('bookmarks.count', array('count' => (int) $data['total'])); ?></span>
            </div>

            <?php if (count($data['items']) == 0): ?>
            <div class="empty-state py-4">
                <i class="fa-regular fa-bookmark fa-2x text-secondary mb-3"></i>
                <p class="text-secondary mb-3"><?php echo __('bookmarks.empty'); ?></p>
                <a href="index.php" class="btn btn-gradient btn-sm"><?php echo __('nav.feed'); ?></a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($data['items'] as $art): ?>
                <div class="col-md-6 col-lg-4">
                    <?php include ROOT_PATH . '/app/Views/partials/news-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($data['pages'] > 1): ?>
            <nav class="mt-4" aria-label="Bookmarks pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                    <li class="page-item <?php if ($i == $data['page']) echo 'active'; ?>">
                        <a class="page-link" href="bookmarks.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
