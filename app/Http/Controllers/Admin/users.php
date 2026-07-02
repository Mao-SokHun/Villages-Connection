<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$admin_post = admin_post_action();
if ($admin_post) {
    app_cache_forget('admin_users_list_default');
    invalidate_admin_unread_counts_cache();
    if ($admin_post['action'] == 'role' && $admin_post['id'] > 0 && $admin_post['value'] != '') {
        if ($admin_post['value'] == 'admin') {
            $new_role = 'admin';
        } else {
            $new_role = 'author';
        }

        if ($admin_post['id'] != (int) $_SESSION['user_id']) {
            try {
                $sql = 'UPDATE users SET role = :role WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array('role' => $new_role, 'id' => $admin_post['id']));
                setFlashMessage('success', 'User role updated.');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Could not update role.');
            }
        } else {
            setFlashMessage('warning', 'You cannot change your own role.');
        }
        header('Location: users.php');
        exit;
    }
    if ($admin_post['action'] == 'ban' && $admin_post['id'] > 0) {
        if ($admin_post['id'] == (int) $_SESSION['user_id']) {
            setFlashMessage('warning', 'You cannot ban yourself.');
        } else {
            $reason = 'Violation of community rules';
            if ($admin_post['value'] != '') {
                $reason = $admin_post['value'];
            }
            ban_user($pdo, $admin_post['id'], $reason);
            setFlashMessage('success', 'User has been banned.');
        }
        header('Location: users.php');
        exit;
    }
    if ($admin_post['action'] == 'unban' && $admin_post['id'] > 0) {
        unban_user($pdo, $admin_post['id']);
        setFlashMessage('success', 'User ban removed.');
        header('Location: users.php');
        exit;
    }
    if ($admin_post['action'] == 'reset_password' && $admin_post['id'] > 0) {
        $temp_password = 'VC' . strtoupper(bin2hex(random_bytes(3)));
        $hash = password_hash($temp_password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password = :password WHERE id = :id')->execute(array('password' => $hash, 'id' => $admin_post['id']));
        log_activity($pdo, 'user.password_reset', 'User #' . $admin_post['id']);
        setFlashMessage('success', 'Temporary password for user #' . $admin_post['id'] . ': ' . $temp_password);
        header('Location: users.php');
        exit;
    }
    if ($admin_post['action'] == 'activate' && $admin_post['id'] > 0) {
        if (activate_user_account($pdo, $admin_post['id'])) {
            setFlashMessage('success', 'User account reactivated.');
        } else {
            setFlashMessage('warning', 'This account is not closed or could not be reactivated.');
        }
        header('Location: users.php');
        exit;
    }
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        if ($admin_post['id'] == (int) $_SESSION['user_id']) {
            setFlashMessage('warning', 'You cannot delete your own account.');
        } else {
            try {
                $sql = 'SELECT name FROM users WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array('id' => $admin_post['id']));
                $u = $stmt->fetch();
                if ($u && !user_is_deleted($u)) {
                    $sql = "UPDATE users SET account_status = 'deleted', deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array('id' => $admin_post['id']));
                    log_activity($pdo, 'user.deleted', 'User #' . $admin_post['id']);
                    setFlashMessage('success', "User '" . $u['name'] . "' closed.");
                }
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Could not delete user.');
            }
        }
        header('Location: users.php');
        exit;
    }
}

$list_search = '';
if (isset($_GET['search'])) {
    $list_search = trim($_GET['search']);
}

$list_role = '';
if (isset($_GET['role'])) {
    $list_role = trim($_GET['role']);
}

$list_sort = 'newest';
if (isset($_GET['sort'])) {
    $list_sort = trim($_GET['sort']);
}

$list_where = ' WHERE 1=1';
$list_params = array();

if ($list_search != '') {
    $list_where .= ' AND (u.name ILIKE :search OR u.email ILIKE :search)';
    $list_params['search'] = '%' . $list_search . '%';
}
if ($list_role == 'admin' || $list_role == 'author') {
    $list_where .= ' AND u.role = :role';
    $list_params['role'] = $list_role;
}

$list_order = ' ORDER BY u.id DESC';
if ($list_sort == 'oldest') {
    $list_order = ' ORDER BY u.id ASC';
} elseif ($list_sort == 'name_az') {
    $list_order = ' ORDER BY u.name ASC';
} elseif ($list_sort == 'name_za') {
    $list_order = ' ORDER BY u.name DESC';
} elseif ($list_sort == 'posts') {
    $list_order = ' ORDER BY post_count DESC, u.name ASC';
}

$list_has_filters = ($list_search != '' || $list_role != '' || $list_sort != 'newest');

$users = null;
if (!$list_has_filters) {
    $cached_users = app_cache_get('admin_users_list_default', 60);
    if (is_array($cached_users) && isset($cached_users['rows'])) {
        $users = $cached_users['rows'];
    }
}

if (!is_array($users)) {
    try {
        $sql = "SELECT u.id, u.name, u.email, u.role, u.is_banned, u.banned_reason, u.account_status, u.deleted_at, u.created_at,
                COUNT(p.id) FILTER (WHERE p.status != 'Deleted') as post_count
                FROM users u
                LEFT JOIN posts p ON p.user_id = u.id" . $list_where . "
                GROUP BY u.id" . $list_order;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($list_params);
        $users = $stmt->fetchAll();
        if (!$list_has_filters) {
            app_cache_put('admin_users_list_default', array('rows' => $users));
        }
    } catch (PDOException $e) {
        app_log_error('Admin users query failed: ' . $e->getMessage());
        die(app_public_error_message('Query error.'));
    }
}

$page_title = 'Manage Users';
$admin_active = 'users';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-white mb-0"><i class="fa-solid fa-users text-indigo me-2"></i>Staff & Authors</h3>
    </div>

    <form method="GET" action="users.php" class="admin-list-toolbar mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label form-label-custom small mb-1">Search</label>
                <input type="search" name="search" class="form-control form-control-custom" placeholder="Name or email..." value="<?php echo htmlspecialchars($list_search); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-custom small mb-1">Role</label>
                <select name="role" class="form-select form-control-custom">
                    <option value="">All roles</option>
                    <option value="admin" <?php if ($list_role == 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="author" <?php if ($list_role == 'author') echo 'selected'; ?>>Author</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-custom small mb-1">Sort by</label>
                <select name="sort" class="form-select form-control-custom">
                    <option value="newest" <?php if ($list_sort == 'newest') echo 'selected'; ?>>Newest first</option>
                    <option value="oldest" <?php if ($list_sort == 'oldest') echo 'selected'; ?>>Oldest first</option>
                    <option value="name_az" <?php if ($list_sort == 'name_az') echo 'selected'; ?>>Name A–Z</option>
                    <option value="name_za" <?php if ($list_sort == 'name_za') echo 'selected'; ?>>Name Z–A</option>
                    <option value="posts" <?php if ($list_sort == 'posts') echo 'selected'; ?>>Most posts</option>
                </select>
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <?php if ($list_has_filters): ?>
                <a href="users.php" class="btn btn-outline-custom btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="admin-list-results mb-0 mt-2"><i class="fa-solid fa-list-ul me-1"></i><?php echo count($users); ?> user<?php if (count($users) != 1) echo 's'; ?> found</p>
    </form>

    <?php if (count($users) == 0): ?>
        <?php if ($list_has_filters): ?>
        <p class="text-secondary mb-0">No users match your filters — <a href="users.php">clear filters</a></p>
        <?php else: ?>
        <p class="text-secondary mb-0">No users found.</p>
        <?php endif; ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th class="text-center">Role</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Posts</th>
                        <th>Joined</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><code>#<?php echo $u['id']; ?></code></td>
                            <td><strong class="table-cell-title"><?php echo htmlspecialchars($u['name']); ?></strong></td>
                            <td class="table-cell-muted small"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="text-center">
                                <?php if ($u['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Author</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (user_is_deleted($u)): ?>
                                    <span class="badge bg-secondary">Closed</span>
                                <?php elseif (user_is_banned($u)): ?>
                                    <span class="badge bg-danger" title="<?php echo htmlspecialchars($u['banned_reason']); ?>">Banned</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center table-cell-strong"><?php echo $u['post_count']; ?></td>
                            <td class="table-cell-muted small"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <a href="posts.php?search=<?php echo urlencode($u['name']); ?>" class="btn btn-sm btn-outline-custom py-1 px-2" title="View posts"><i class="fa-solid fa-images"></i></a>
                                    <?php render_admin_action_button('users.php', 'reset_password', $u['id'], array('class' => 'btn btn-sm btn-outline-custom py-1 px-2', 'icon' => 'fa-solid fa-key', 'title' => 'Reset password', 'confirm' => 'Generate a temporary password for this user?')); ?>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <?php if (user_is_banned($u)): ?>
                                            <?php render_admin_action_button('users.php', 'unban', $u['id'], array('class' => 'btn btn-sm btn-outline-custom text-success py-1 px-2', 'label' => 'Unban', 'title' => 'Unban')); ?>
                                        <?php else: ?>
                                            <?php render_admin_action_button('users.php', 'ban', $u['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger py-1 px-2', 'icon' => 'fa-solid fa-ban', 'title' => 'Ban user', 'confirm' => 'Ban this user?')); ?>
                                        <?php endif; ?>
                                        <?php if ($u['role'] != 'admin'): ?>
                                            <?php render_admin_action_button('users.php', 'role', $u['id'], array('class' => 'btn btn-sm btn-outline-custom text-warning py-1 px-2', 'label' => 'Admin', 'title' => 'Make admin', 'value' => 'admin')); ?>
                                        <?php else: ?>
                                            <?php render_admin_action_button('users.php', 'role', $u['id'], array('class' => 'btn btn-sm btn-outline-custom text-info py-1 px-2', 'label' => 'Author', 'title' => 'Make author', 'value' => 'author')); ?>
                                        <?php endif; ?>
                                        <?php if (user_is_deleted($u)): ?>
                                            <?php render_admin_action_button('users.php', 'activate', $u['id'], array('class' => 'btn btn-sm btn-outline-custom text-success py-1 px-2', 'label' => 'Activate', 'icon' => 'fa-solid fa-circle-check', 'title' => 'Reactivate account', 'confirm' => 'Reactivate this user account? They will be able to sign in again.')); ?>
                                        <?php else: ?>
                                        <?php render_admin_action_button('users.php', 'delete', $u['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger py-1 px-2', 'icon' => 'fa-solid fa-trash-can', 'title' => 'Close account', 'confirm' => 'Close this user account? Their data will be kept but hidden.')); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-secondary small">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
