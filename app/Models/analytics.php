<?php

function analytics_days_allowed($days)
{
    $allowed = array(7, 14, 30, 90);
    $days = (int) $days;
    if (!in_array($days, $allowed, true)) {
        return 30;
    }
    return $days;
}

function analytics_overview($pdo, $days = 30)
{
    $days = analytics_days_allowed($days);
    $interval = (int) $days;

    $stats = array(
        'users' => 0,
        'users_new' => 0,
        'posts' => 0,
        'posts_new' => 0,
        'published' => 0,
        'pending' => 0,
        'views' => 0,
        'likes' => 0,
        'comments' => 0,
        'comments_new' => 0,
        'bookmarks' => 0,
        'reports_open' => 0,
        'messages_new' => 0,
        'followers' => 0,
        'engagement_rate' => 0,
    );

    try {
        $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['posts'] = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        $stats['published'] = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Published'")->fetchColumn();
        $stats['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Pending'")->fetchColumn();
        $stats['views'] = (int) $pdo->query('SELECT COALESCE(SUM(views),0) FROM posts')->fetchColumn();
        $stats['likes'] = (int) $pdo->query('SELECT COALESCE(SUM(likes),0) FROM posts')->fetchColumn();
        $stats['comments'] = (int) $pdo->query("SELECT COUNT(*) FROM post_comments WHERE status NOT IN ('deleted','rejected')")->fetchColumn();
        $stats['bookmarks'] = (int) $pdo->query('SELECT COUNT(*) FROM post_bookmarks')->fetchColumn();
        $stats['reports_open'] = (int) $pdo->query("SELECT COUNT(*) FROM content_reports WHERE status = 'open'")->fetchColumn();
        $stats['followers'] = (int) $pdo->query('SELECT COUNT(*) FROM user_follows')->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= (CURRENT_DATE - (:days || ' days')::interval)");
        $stmt->execute(array('days' => $interval));
        $stats['users_new'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE created_at >= (CURRENT_DATE - (:days || ' days')::interval)");
        $stmt->execute(array('days' => $interval));
        $stats['posts_new'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_comments WHERE created_at >= (CURRENT_DATE - (:days || ' days')::interval) AND status NOT IN ('deleted','rejected')");
        $stmt->execute(array('days' => $interval));
        $stats['comments_new'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE status = 'new' AND created_at >= (CURRENT_DATE - (:days || ' days')::interval)");
        $stmt->execute(array('days' => $interval));
        $stats['messages_new'] = (int) $stmt->fetchColumn();

        if ($stats['views'] > 0) {
            $stats['engagement_rate'] = round(($stats['likes'] / $stats['views']) * 100, 1);
        }
    } catch (PDOException $e) {
        // keep defaults
    }

    return $stats;
}

function analytics_daily_series($pdo, $days = 30)
{
    $days = analytics_days_allowed($days);

    $sql = "SELECT d.day::date AS day,
        COALESCE(u.total, 0) AS users,
        COALESCE(p.total, 0) AS posts,
        COALESCE(c.total, 0) AS comments,
        COALESCE(l.total, 0) AS likes
        FROM generate_series((CURRENT_DATE - :days::int), CURRENT_DATE, '1 day'::interval) AS d(day)
        LEFT JOIN (
            SELECT DATE(created_at) AS day, COUNT(*)::int AS total FROM users
            WHERE created_at >= (CURRENT_DATE - :days2::int)
            GROUP BY DATE(created_at)
        ) u ON u.day = d.day::date
        LEFT JOIN (
            SELECT DATE(created_at) AS day, COUNT(*)::int AS total FROM posts
            WHERE created_at >= (CURRENT_DATE - :days3::int)
            GROUP BY DATE(created_at)
        ) p ON p.day = d.day::date
        LEFT JOIN (
            SELECT DATE(created_at) AS day, COUNT(*)::int AS total FROM post_comments
            WHERE created_at >= (CURRENT_DATE - :days4::int) AND status NOT IN ('deleted','rejected')
            GROUP BY DATE(created_at)
        ) c ON c.day = d.day::date
        LEFT JOIN (
            SELECT DATE(created_at) AS day, COUNT(*)::int AS total FROM post_likes
            WHERE created_at >= (CURRENT_DATE - :days5::int)
            GROUP BY DATE(created_at)
        ) l ON l.day = d.day::date
        ORDER BY d.day ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'days' => $days,
        'days2' => $days,
        'days3' => $days,
        'days4' => $days,
        'days5' => $days,
    ));

    return $stmt->fetchAll();
}

function analytics_top_authors($pdo, $limit = 8)
{
    $sql = "SELECT u.id, u.name, COUNT(p.id)::int AS posts,
        COALESCE(SUM(p.views),0)::int AS views,
        COALESCE(SUM(p.likes),0)::int AS likes
        FROM users u
        INNER JOIN posts p ON p.user_id = u.id AND p.status = 'Published'
        GROUP BY u.id, u.name
        ORDER BY views DESC
        LIMIT " . (int) $limit;
    return $pdo->query($sql)->fetchAll();
}

function analytics_top_categories($pdo, $limit = 8)
{
    $sql = "SELECT COALESCE(c.name, 'Uncategorized') AS name,
        COUNT(p.id)::int AS posts,
        COALESCE(SUM(p.views),0)::int AS views
        FROM posts p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'Published'
        GROUP BY COALESCE(c.name, 'Uncategorized')
        ORDER BY views DESC
        LIMIT " . (int) $limit;
    return $pdo->query($sql)->fetchAll();
}

function analytics_export_reports($pdo, $status = 'all')
{
    $where = '';
    $params = array();
    if ($status == 'open' || $status == 'resolved') {
        $where = ' WHERE status = :status';
        $params['status'] = $status;
    }

    $stmt = $pdo->prepare('SELECT * FROM content_reports' . $where . ' ORDER BY created_at DESC');
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $data = array();
    foreach ($rows as $r) {
        $data[] = array(
            $r['id'],
            $r['reporter_name'],
            $r['reporter_email'],
            $r['reason'],
            $r['post_url'],
            $r['status'],
            $r['admin_notes'],
            $r['created_at'],
            $r['resolved_at'],
        );
    }

    admin_export_csv('content-reports-export.csv', array(
        'ID', 'Reporter Name', 'Reporter Email', 'Reason', 'Post URL', 'Status', 'Admin Notes', 'Created', 'Resolved'
    ), $data);
}

function analytics_export_messages($pdo)
{
    $rows = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    $data = array();
    foreach ($rows as $r) {
        $data[] = array(
            $r['id'],
            $r['name'],
            $r['email'],
            $r['subject'],
            $r['status'],
            $r['created_at'],
            $r['read_at'],
        );
    }
    admin_export_csv('contact-messages-export.csv', array(
        'ID', 'Name', 'Email', 'Subject', 'Status', 'Created', 'Read At'
    ), $data);
}

function analytics_export_comments($pdo)
{
    $sql = "SELECT c.id, c.author_name, c.content, c.status, p.title, c.created_at
        FROM post_comments c
        LEFT JOIN posts p ON p.id = c.post_id
        ORDER BY c.created_at DESC";
    $rows = $pdo->query($sql)->fetchAll();
    $data = array();
    foreach ($rows as $r) {
        $data[] = array(
            $r['id'],
            $r['author_name'],
            excerpt($r['content'], 200),
            $r['status'],
            $r['title'],
            $r['created_at'],
        );
    }
    admin_export_csv('comments-export.csv', array(
        'ID', 'Author', 'Content', 'Status', 'Post', 'Created'
    ), $data);
}

function analytics_export_activity($pdo, $limit = 1000)
{
    $sql = 'SELECT l.id, u.name, l.action, l.details, l.ip_address, l.created_at
        FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        ORDER BY l.created_at DESC
        LIMIT ' . (int) $limit;
    $rows = $pdo->query($sql)->fetchAll();
    $data = array();
    foreach ($rows as $r) {
        $data[] = array(
            $r['id'],
            $r['name'],
            $r['action'],
            $r['details'],
            $r['ip_address'],
            $r['created_at'],
        );
    }
    admin_export_csv('activity-logs-export.csv', array(
        'ID', 'User', 'Action', 'Details', 'IP', 'Created'
    ), $data);
}
