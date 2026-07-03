<?php

secure_json_api(array(
    'methods'    => array('POST'),
    'login'      => true,
    'csrf'       => true,
    'rate_limit' => array('action' => 'poll_vote', 'id' => client_rate_limit_id(), 'max' => 30, 'window' => 60),
));

$action  = isset($_POST['action']) ? trim($_POST['action']) : 'vote';
$user_id = (int) $_SESSION['user_id'];

if ($action === 'vote') {
    $poll_id   = isset($_POST['poll_id']) ? (int) $_POST['poll_id'] : 0;
    $option_id = isset($_POST['option_id']) ? (int) $_POST['option_id'] : 0;

    if ($poll_id <= 0 || $option_id <= 0) {
        echo json_encode(array('ok' => false, 'error' => 'Invalid poll or option.'));
        exit;
    }

    // Validate poll exists and option belongs to poll
    try {
        $poll_stmt = $pdo->prepare('SELECT * FROM polls WHERE id = :id');
        $poll_stmt->execute(array('id' => $poll_id));
        $poll = $poll_stmt->fetch();
        if (!$poll) {
            echo json_encode(array('ok' => false, 'error' => 'Poll not found.'));
            exit;
        }

        // Check if poll has ended
        if (!empty($poll['ends_at']) && strtotime($poll['ends_at']) < time()) {
            echo json_encode(array('ok' => false, 'error' => 'This poll has ended.'));
            exit;
        }

        $opt_stmt = $pdo->prepare('SELECT * FROM poll_options WHERE id = :id AND poll_id = :pid');
        $opt_stmt->execute(array('id' => $option_id, 'pid' => $poll_id));
        if (!$opt_stmt->fetch()) {
            echo json_encode(array('ok' => false, 'error' => 'Invalid option.'));
            exit;
        }

        // If not multiple choice, remove previous vote first
        if (!$poll['is_multiple']) {
            $pdo->prepare('DELETE FROM poll_votes WHERE poll_id = :pid AND user_id = :uid')
                ->execute(array('pid' => $poll_id, 'uid' => $user_id));
        }

        // Check if already voted on this specific option
        $check = $pdo->prepare('SELECT id FROM poll_votes WHERE poll_id = :pid AND option_id = :oid AND user_id = :uid');
        $check->execute(array('pid' => $poll_id, 'oid' => $option_id, 'uid' => $user_id));
        if ($check->fetch()) {
            // Toggle off
            $pdo->prepare('DELETE FROM poll_votes WHERE poll_id = :pid AND option_id = :oid AND user_id = :uid')
                ->execute(array('pid' => $poll_id, 'oid' => $option_id, 'uid' => $user_id));
        } else {
            $pdo->prepare('INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (:pid, :oid, :uid) ON CONFLICT DO NOTHING')
                ->execute(array('pid' => $poll_id, 'oid' => $option_id, 'uid' => $user_id));
        }

        // Return updated counts
        $counts_stmt = $pdo->prepare('SELECT option_id, COUNT(*) AS votes FROM poll_votes WHERE poll_id = :pid GROUP BY option_id');
        $counts_stmt->execute(array('pid' => $poll_id));
        $counts_raw = $counts_stmt->fetchAll();
        $counts = array();
        foreach ($counts_raw as $row) {
            $counts[(int) $row['option_id']] = (int) $row['votes'];
        }

        $my_votes_stmt = $pdo->prepare('SELECT option_id FROM poll_votes WHERE poll_id = :pid AND user_id = :uid');
        $my_votes_stmt->execute(array('pid' => $poll_id, 'uid' => $user_id));
        $my_votes = array_column($my_votes_stmt->fetchAll(), 'option_id');

        echo json_encode(array('ok' => true, 'counts' => $counts, 'my_votes' => $my_votes));
    } catch (PDOException $e) {
        echo json_encode(array('ok' => false, 'error' => 'Vote failed.'));
    }
    exit;
}

echo json_encode(array('ok' => false, 'error' => 'Invalid action.'));
