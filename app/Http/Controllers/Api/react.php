<?php

secure_json_api(array(
    'methods'    => array('POST'),
    'login'      => true,
    'csrf'       => true,
    'rate_limit' => array('action' => 'react_api', 'id' => client_rate_limit_id(), 'max' => 60, 'window' => 60),
));

$allowed_reactions = array('like', 'love', 'haha', 'sad', 'angry', 'pray');

$post_id  = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$reaction = isset($_POST['reaction']) ? trim($_POST['reaction']) : 'like';
$user_id  = (int) $_SESSION['user_id'];

if ($post_id <= 0) {
    echo json_encode(array('ok' => false, 'error' => 'Invalid post.'));
    exit;
}

if (!in_array($reaction, $allowed_reactions, true)) {
    echo json_encode(array('ok' => false, 'error' => 'Invalid reaction.'));
    exit;
}

try {
    // Check existing reaction
    $check = $pdo->prepare('SELECT id, reaction FROM post_reactions WHERE post_id = :pid AND user_id = :uid');
    $check->execute(array('pid' => $post_id, 'uid' => $user_id));
    $existing = $check->fetch();

    if ($existing) {
        if ($existing['reaction'] === $reaction) {
            // Same reaction → remove (toggle off)
            $pdo->prepare('DELETE FROM post_reactions WHERE id = :id')->execute(array('id' => (int) $existing['id']));
            $my_reaction = null;
        } else {
            // Different reaction → update
            $pdo->prepare('UPDATE post_reactions SET reaction = :r WHERE id = :id')
                ->execute(array('r' => $reaction, 'id' => (int) $existing['id']));
            $my_reaction = $reaction;
        }
    } else {
        $pdo->prepare('INSERT INTO post_reactions (post_id, user_id, reaction) VALUES (:pid, :uid, :r)')
            ->execute(array('pid' => $post_id, 'uid' => $user_id, 'r' => $reaction));
        $my_reaction = $reaction;
    }

    // Return updated reaction counts
    $counts_stmt = $pdo->prepare('SELECT reaction, COUNT(*) AS total FROM post_reactions WHERE post_id = :pid GROUP BY reaction');
    $counts_stmt->execute(array('pid' => $post_id));
    $counts = array();
    foreach ($counts_stmt->fetchAll() as $row) {
        $counts[$row['reaction']] = (int) $row['total'];
    }

    echo json_encode(array('ok' => true, 'my_reaction' => $my_reaction, 'counts' => $counts));
} catch (PDOException $e) {
    echo json_encode(array('ok' => false, 'error' => 'Reaction failed.'));
}
