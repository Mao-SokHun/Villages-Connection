<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request'));
    exit;
}

require_valid_csrf();

$post_id = 0;
if (isset($_POST['post_id'])) {
    $post_id = (int) $_POST['post_id'];
}

$toggle = isset($_POST['toggle']) && $_POST['toggle'] == '1';

if ($post_id <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid post'));
    exit;
}

$key = visitor_key();

$sql = 'SELECT id, likes FROM posts WHERE id = :id AND status = :status';
$stmt = $pdo->prepare($sql);
$stmt->execute(array('id' => $post_id, 'status' => 'Published'));
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(array('success' => false, 'message' => 'Post not found'));
    exit;
}

$sql = 'SELECT id FROM post_likes WHERE post_id = :pid AND visitor_key = :vk';
$check = $pdo->prepare($sql);
$check->execute(array('pid' => $post_id, 'vk' => $key));
$existing = $check->fetch();

if ($existing) {
    if (!$toggle) {
        echo json_encode(array(
            'success' => true,
            'liked' => true,
            'likes' => (int) $post['likes'],
            'message' => 'You already liked this post',
        ));
        exit;
    }

    $pdo->prepare('DELETE FROM post_likes WHERE id = :id')->execute(array('id' => (int) $existing['id']));
    $pdo->prepare('UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE id = :id')->execute(array('id' => $post_id));
    $likes_stmt = $pdo->prepare('SELECT likes FROM posts WHERE id = :id');
    $likes_stmt->execute(array('id' => $post_id));
    $likes = (int) $likes_stmt->fetchColumn();

    echo json_encode(array('success' => true, 'liked' => false, 'likes' => $likes, 'message' => 'Like removed'));
    exit;
}

$sql = 'INSERT INTO post_likes (post_id, visitor_key) VALUES (:pid, :vk)';
$insert = $pdo->prepare($sql);
$insert->execute(array('pid' => $post_id, 'vk' => $key));

$sql = 'UPDATE posts SET likes = likes + 1 WHERE id = :id';
$update = $pdo->prepare($sql);
$update->execute(array('id' => $post_id));

$sql = 'SELECT likes FROM posts WHERE id = :id';
$likes_stmt = $pdo->prepare($sql);
$likes_stmt->execute(array('id' => $post_id));
$likes = (int) $likes_stmt->fetchColumn();

echo json_encode(array('success' => true, 'liked' => true, 'likes' => $likes, 'message' => 'Post liked'));
