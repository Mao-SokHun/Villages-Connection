<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$query = '';
if (isset($_GET['q'])) {
    $query = trim($_GET['q']);
}

$type = 'all';
if (isset($_GET['type'])) {
    $type = trim($_GET['type']);
}

$sort = 'relevance';
if (isset($_GET['sort'])) {
    $sort = trim($_GET['sort']);
}

$category = '';
if (isset($_GET['cat'])) {
    $category = trim($_GET['cat']);
}

$page = 1;
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];
}

$search_categories = $pdo->query('SELECT slug, name, icon FROM categories ORDER BY name')->fetchAll();

$page_title = __('search.title');
$results = array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1);
$authors = array();

if ($query != '') {
    if ($type == 'authors') {
        $authors = search_authors($pdo, $query, 20);
    } else {
        $search_options = array(
            'page' => $page,
            'sort' => $sort,
        );
        if ($category != '') {
            $search_options['category'] = $category;
        }
        $results = search_posts($pdo, $query, $search_options);
    }
}

function search_page_url($params)
{
    $parts = array();
    if (!empty($params['q'])) {
        $parts[] = 'q=' . urlencode($params['q']);
    }
    if (!empty($params['type']) && $params['type'] != 'all') {
        $parts[] = 'type=' . urlencode($params['type']);
    }
    if (!empty($params['sort']) && $params['sort'] != 'relevance') {
        $parts[] = 'sort=' . urlencode($params['sort']);
    }
    if (!empty($params['cat'])) {
        $parts[] = 'cat=' . urlencode($params['cat']);
    }
    if (!empty($params['page']) && (int) $params['page'] > 1) {
        $parts[] = 'page=' . (int) $params['page'];
    }

    $query_string = implode('&', $parts);
    if ($query_string == '') {
        return app_url('search.php');
    }

    return app_url('search.php?' . $query_string);
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-10">
        <div class="glass-panel p-4 reveal">
            <h1 class="text-white h3 mb-3"><i class="fa-solid fa-magnifying-glass text-warning me-2"></i><?php echo __('search.title'); ?></h1>
            <form method="GET" action="<?php echo app_url('search.php'); ?>" class="search-page-form mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label form-label-custom" for="search-q"><?php echo __('search.query'); ?></label>
                        <input type="search" name="q" id="search-q" class="form-control form-control-custom" value="<?php echo htmlspecialchars($query); ?>" placeholder="<?php echo htmlspecialchars(__('nav.search')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-custom" for="search-type"><?php echo __('search.type'); ?></label>
                        <select name="type" id="search-type" class="form-select form-control-custom">
                            <option value="all" <?php if ($type == 'all') echo 'selected'; ?>><?php echo __('search.type_all'); ?></option>
                            <option value="authors" <?php if ($type == 'authors') echo 'selected'; ?>><?php echo __('search.type_authors'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-custom" for="search-cat"><?php echo __('search.category'); ?></label>
                        <select name="cat" id="search-cat" class="form-select form-control-custom" <?php if ($type == 'authors') echo 'disabled'; ?>>
                            <option value=""><?php echo __('search.all_categories'); ?></option>
                            <?php foreach ($search_categories as $cat_row): ?>
                            <option value="<?php echo htmlspecialchars($cat_row['slug']); ?>" <?php if ($category === $cat_row['slug']) echo 'selected'; ?>><?php echo htmlspecialchars($cat_row['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gradient w-100"><i class="fa-solid fa-search"></i> <?php echo __('search.submit'); ?></button>
                    </div>
                </div>
                <?php if ($type != 'authors'): ?>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="<?php echo search_page_url(array('q' => $query, 'cat' => $category, 'sort' => 'relevance')); ?>" class="filter-chip <?php if ($sort == 'relevance') echo 'active'; ?>"><?php echo __('search.sort_relevance'); ?></a>
                    <a href="<?php echo search_page_url(array('q' => $query, 'cat' => $category, 'sort' => 'latest')); ?>" class="filter-chip <?php if ($sort == 'latest') echo 'active'; ?>"><?php echo __('nav.latest'); ?></a>
                    <a href="<?php echo search_page_url(array('q' => $query, 'cat' => $category, 'sort' => 'popular')); ?>" class="filter-chip <?php if ($sort == 'popular') echo 'active'; ?>"><?php echo __('nav.popular'); ?></a>
                </div>
                <?php endif; ?>
            </form>

            <?php if ($query == ''): ?>
            <div class="empty-state py-4">
                <i class="fa-solid fa-search fa-2x text-secondary mb-3"></i>
                <p class="text-secondary mb-0"><?php echo __('search.prompt'); ?></p>
            </div>
            <?php elseif ($type == 'authors'): ?>
                <?php if (count($authors) == 0): ?>
                <p class="text-secondary mb-0"><?php echo __('search.no_authors'); ?></p>
                <?php else: ?>
                <div class="search-author-list">
                    <?php foreach ($authors as $author): ?>
                    <a href="<?php echo profile_url((int) $author['id']); ?>" class="search-author-card glass-panel p-3 mb-3 d-flex align-items-center gap-3 text-decoration-none">
                        <?php echo render_user_avatar($author['name'], isset($author['avatar']) ? $author['avatar'] : '', 'user-avatar-md', isset($author['email']) ? $author['email'] : ''); ?>
                        <div>
                            <div class="text-white fw-semibold"><?php echo highlight_search_term($author['name'], $query); ?></div>
                            <?php if (!empty($author['bio'])): ?>
                            <div class="text-secondary small"><?php echo highlight_search_term(excerpt($author['bio'], 80), $query); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-secondary small mb-3"><?php echo __('search.results_count', array('count' => (int) $results['total'])); ?></p>
                <?php if (count($results['items']) == 0): ?>
                <p class="text-secondary mb-0"><?php echo __('search.no_posts'); ?></p>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($results['items'] as $art): ?>
                    <div class="col-md-6 col-lg-4">
                        <?php include ROOT_PATH . '/app/Views/partials/news-card.php'; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($results['pages'] > 1): ?>
                <nav class="mt-4" aria-label="Search pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $results['pages']; $i++): ?>
                        <li class="page-item <?php if ($i == $results['page']) echo 'active'; ?>">
                            <a class="page-link" href="<?php echo search_page_url(array('q' => $query, 'cat' => $category, 'sort' => $sort, 'page' => $i)); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('search-type');
    var catSelect = document.getElementById('search-cat');
    if (typeSelect && catSelect) {
        typeSelect.addEventListener('change', function() {
            catSelect.disabled = typeSelect.value === 'authors';
            if (catSelect.disabled) {
                catSelect.value = '';
            }
        });
    }
});
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
