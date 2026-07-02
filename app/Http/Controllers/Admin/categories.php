<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$id = 0;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$errors = array();

$admin_post = admin_post_action();
if ($admin_post) {
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        try {
            $sql = 'SELECT name FROM categories WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array('id' => $admin_post['id']));
            $cat = $stmt->fetch();

            if ($cat) {
                $sql = 'DELETE FROM categories WHERE id = :id';
                $pdo->prepare($sql)->execute(array('id' => $admin_post['id']));
                clear_nav_category_cache();
                setFlashMessage('success', "Category '" . $cat['name'] . "' deleted successfully.");
            } else {
                setFlashMessage('danger', 'Category not found.');
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Could not delete category.');
        }
        header('Location: categories.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['admin_action'])) {
    $name = '';
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
    }

    $description = '';
    if (isset($_POST['description'])) {
        $description = trim($_POST['description']);
    }

    $icon = 'fa-newspaper';
    if (isset($_POST['icon'])) {
        $icon = trim($_POST['icon']);
    }

    if ($name == '') {
        $errors[] = 'Category name is required.';
    }
    if ($description == '') {
        $errors[] = 'Description is required.';
    }
    $icon = normalize_category_icon($icon);

    if (count($errors) == 0) {
        $slug = slugify($name);

        $db_action = '';
        if (isset($_POST['db_action'])) {
            $db_action = $_POST['db_action'];
        }

        try {
            if ($db_action == 'add') {
                $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array('slug' => $slug));
                if ($stmt->fetchColumn() > 0) {
                    $slug = $slug . '-' . time();
                }

                $created_by = (int) $_SESSION['user_id'];
                $sql = 'INSERT INTO categories (name, slug, description, icon, created_by) VALUES (:name, :slug, :description, :icon, :created_by)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array(
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'icon' => $icon,
                    'created_by' => $created_by,
                ));
                clear_nav_category_cache();
                setFlashMessage('success', "Category '" . $name . "' created successfully.");
            } elseif ($db_action == 'edit' && $id > 0) {
                $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array('slug' => $slug, 'id' => $id));
                if ($stmt->fetchColumn() > 0) {
                    $slug = $slug . '-' . time();
                }

                $sql = 'UPDATE categories SET name = :name, slug = :slug, description = :description, icon = :icon WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array(
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'icon' => $icon,
                    'id' => $id,
                ));
                clear_nav_category_cache();
                setFlashMessage('success', "Category '" . $name . "' updated successfully.");
            }

            header('Location: categories.php');
            exit;
        } catch (PDOException $e) {
            app_log_error('Category save failed: ' . $e->getMessage());
            $errors[] = app_public_error_message('Database error.');
        }
    }
}

$category = null;
if ($action == 'edit' && $id > 0) {
    $sql = 'SELECT * FROM categories WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => $id));
    $category = $stmt->fetch();
    if (!$category) {
        setFlashMessage('danger', 'Category not found.');
        header('Location: categories.php');
        exit;
    }
}

if ($action == 'add' || $action == 'edit') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($errors) > 0) {
        $category = array(
            'name' => '',
            'description' => '',
            'icon' => 'fa-newspaper',
        );
        if (isset($_POST['name'])) {
            $category['name'] = trim($_POST['name']);
        }
        if (isset($_POST['description'])) {
            $category['description'] = trim($_POST['description']);
        }
        if (isset($_POST['icon'])) {
            $category['icon'] = trim($_POST['icon']);
        }
    }
}

$page_title = 'Manage Categories';
$admin_active = 'categories';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<?php if ($action == 'add' || $action == 'edit'): ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-panel p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white mb-0">
                    <i class="fa-solid fa-tags me-2 text-warning"></i>
                    <?php if ($action == 'add'): ?>
                        Create Category
                    <?php else: ?>
                        Edit Category
                    <?php endif; ?>
                </h3>
                <a href="categories.php" class="btn btn-outline-custom btn-sm">Back</a>
            </div>

            <?php if (count($errors) > 0): ?>
            <?php render_user_alerts($errors, 'danger'); ?>
            <?php endif; ?>

            <form method="POST" action="categories.php?action=<?php echo $action; ?><?php if ($id > 0) echo '&id=' . $id; ?>">
                <input type="hidden" name="db_action" value="<?php echo $action; ?>">

                <div class="mb-3">
                    <label class="form-label form-label-custom">Category Name *</label>
                    <input type="text" name="name" class="form-control form-control-custom" required
                        value="<?php if ($category) echo htmlspecialchars($category['name']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label form-label-custom">Icon *</label>
                    <?php
                    $icon_field_name = 'icon';
                    $icon_selected = 'fa-tag';
                    if ($category && isset($category['icon'])) {
                        $icon_selected = $category['icon'];
                    }
                    require ROOT_PATH . '/app/Views/partials/category-icon-picker.php';
                    ?>
                </div>

                <div class="mb-4">
                    <label class="form-label form-label-custom">Description *</label>
                    <textarea name="description" rows="4" class="form-control form-control-custom" required><?php if ($category) echo htmlspecialchars($category['description']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-gradient w-100 py-3">
                    <i class="fa-solid fa-save"></i> Save Category
                </button>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="text-white mb-0"><i class="fa-solid fa-tags text-warning me-2"></i>All Categories</h3>
        <a href="categories.php?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> Create New</a>
    </div>

    <?php
    $list_search = '';
    if (isset($_GET['search'])) {
        $list_search = trim($_GET['search']);
    }

    $list_sort = 'name_asc';
    if (isset($_GET['sort'])) {
        $list_sort = trim($_GET['sort']);
    }

    $list_where = ' WHERE 1=1';
    $list_params = array();

    if ($list_search != '') {
        $list_where .= ' AND (c.name ILIKE :search OR c.slug ILIKE :search OR c.description ILIKE :search OR u.name ILIKE :search)';
        $list_params['search'] = '%' . $list_search . '%';
    }

    $list_order = ' ORDER BY c.name ASC';
    if ($list_sort == 'name_desc') {
        $list_order = ' ORDER BY c.name DESC';
    } elseif ($list_sort == 'posts') {
        $list_order = ' ORDER BY post_count DESC, c.name ASC';
    } elseif ($list_sort == 'newest') {
        $list_order = ' ORDER BY c.id DESC';
    } elseif ($list_sort == 'oldest') {
        $list_order = ' ORDER BY c.id ASC';
    }

    $sql = "SELECT c.*, COUNT(p.id) as post_count, u.name as creator_name
        FROM categories c
        LEFT JOIN posts p ON p.category_id = c.id
        LEFT JOIN users u ON c.created_by = u.id" . $list_where . "
        GROUP BY c.id, u.name" . $list_order;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($list_params);
    $categories = $stmt->fetchAll();

    $list_has_filters = ($list_search != '' || $list_sort != 'name_asc');
    ?>
    <form method="GET" action="categories.php" class="admin-list-toolbar mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label class="form-label form-label-custom small mb-1">Search</label>
                <input type="search" name="search" class="form-control form-control-custom" placeholder="Name, slug, description..." value="<?php echo htmlspecialchars($list_search); ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-custom small mb-1">Sort by</label>
                <select name="sort" class="form-select form-control-custom">
                    <option value="name_asc" <?php if ($list_sort == 'name_asc') echo 'selected'; ?>>Name A–Z</option>
                    <option value="name_desc" <?php if ($list_sort == 'name_desc') echo 'selected'; ?>>Name Z–A</option>
                    <option value="posts" <?php if ($list_sort == 'posts') echo 'selected'; ?>>Most posts</option>
                    <option value="newest" <?php if ($list_sort == 'newest') echo 'selected'; ?>>Newest first</option>
                    <option value="oldest" <?php if ($list_sort == 'oldest') echo 'selected'; ?>>Oldest first</option>
                </select>
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <?php if ($list_has_filters): ?>
                <a href="categories.php" class="btn btn-outline-custom btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="admin-list-results mb-0 mt-2"><i class="fa-solid fa-list-ul me-1"></i><?php echo count($categories); ?> categor<?php if (count($categories) == 1) echo 'y'; else echo 'ies'; ?> found</p>
    </form>

    <?php if (count($categories) == 0): ?>
        <?php if ($list_has_filters): ?>
        <p class="text-secondary text-center py-5">No categories match your search — <a href="categories.php">clear filters</a> or <a href="categories.php?action=add">create one</a></p>
        <?php else: ?>
        <p class="text-secondary text-center py-5">No categories yet — <a href="categories.php?action=add">create one</a></p>
        <?php endif; ?>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Created By</th>
                    <th class="text-center">Posts</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><code>#<?php echo (int) $cat['id']; ?></code></td>
                    <td class="table-cell-title">
                        <?php echo render_category_icon($cat['icon'], 'text-warning me-1'); ?>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </td>
                    <td class="table-cell-muted small"><?php echo htmlspecialchars($cat['slug']); ?></td>
                    <td class="text-secondary small"><?php echo htmlspecialchars($cat['description']); ?></td>
                    <td class="text-secondary small">
                        <?php
                        if (isset($cat['creator_name']) && $cat['creator_name'] != '') {
                            echo htmlspecialchars($cat['creator_name']);
                        } else {
                            echo 'System';
                        }
                        ?>
                    </td>
                    <td class="text-center table-cell-strong"><?php echo (int) $cat['post_count']; ?></td>
                    <td class="text-center text-nowrap">
                        <a href="categories.php?action=edit&id=<?php echo (int) $cat['id']; ?>" class="btn btn-sm btn-outline-custom text-info">
                            <i class="fa-solid fa-edit"></i>
                        </a>
                        <?php render_admin_action_button('categories.php', 'delete', $cat['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete this category? Posts in this category will become uncategorized.')); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
