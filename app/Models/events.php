<?php

/**
 * Events model functions.
 */

function get_upcoming_events($pdo, $limit = 10, $include_past = false)
{
    try {
        $where = $include_past ? "status != 'cancelled'" : "status != 'cancelled' AND event_date >= CURRENT_DATE";
        $sql = "SELECT e.*, u.name AS organizer_name,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id AND r.status = 'going') AS attendee_count
            FROM events e
            LEFT JOIN users u ON u.id = e.created_by
            WHERE $where
            ORDER BY e.event_date ASC, e.event_time ASC
            LIMIT " . (int) $limit;
        return $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function get_event_by_id($pdo, $event_id)
{
    try {
        $stmt = $pdo->prepare("SELECT e.*, u.name AS organizer_name, u.avatar AS organizer_avatar
            FROM events e
            LEFT JOIN users u ON u.id = e.created_by
            WHERE e.id = :id");
        $stmt->execute(array('id' => (int) $event_id));
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function get_event_rsvps($pdo, $event_id, $status = 'going', $limit = 50)
{
    try {
        $sql = "SELECT r.*, u.name, u.avatar, u.email
            FROM event_rsvps r
            JOIN users u ON u.id = r.user_id
            WHERE r.event_id = :eid AND r.status = :status
            ORDER BY r.created_at ASC
            LIMIT " . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('eid' => (int) $event_id, 'status' => $status));
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function get_user_event_rsvp($pdo, $event_id, $user_id)
{
    try {
        $stmt = $pdo->prepare('SELECT * FROM event_rsvps WHERE event_id = :eid AND user_id = :uid');
        $stmt->execute(array('eid' => (int) $event_id, 'uid' => (int) $user_id));
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function toggle_event_rsvp($pdo, $event_id, $user_id)
{
    $event_id = (int) $event_id;
    $user_id = (int) $user_id;

    try {
        $existing = get_user_event_rsvp($pdo, $event_id, $user_id);
        if ($existing) {
            $pdo->prepare('DELETE FROM event_rsvps WHERE event_id = :eid AND user_id = :uid')
                ->execute(array('eid' => $event_id, 'uid' => $user_id));
            return array('ok' => true, 'going' => false, 'message' => 'RSVP removed.');
        }

        // Check capacity
        $event = get_event_by_id($pdo, $event_id);
        if ($event && $event['max_attendees'] > 0) {
            $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM event_rsvps WHERE event_id = :eid AND status = :s');
            $count_stmt->execute(array('eid' => $event_id, 's' => 'going'));
            $count = (int) $count_stmt->fetchColumn();
            if ($count >= (int) $event['max_attendees']) {
                return array('ok' => false, 'error' => 'This event is full.');
            }
        }

        $pdo->prepare('INSERT INTO event_rsvps (event_id, user_id, status) VALUES (:eid, :uid, :s) ON CONFLICT (event_id, user_id) DO UPDATE SET status = :s2')
            ->execute(array('eid' => $event_id, 'uid' => $user_id, 's' => 'going', 's2' => 'going'));

        return array('ok' => true, 'going' => true, 'message' => 'You are going!');
    } catch (PDOException $e) {
        return array('ok' => false, 'error' => 'Could not update RSVP.');
    }
}

function create_event($pdo, $data)
{
    try {
        $sql = "INSERT INTO events (title, description, location, event_date, event_time, end_date, end_time, status, created_by, max_attendees)
            VALUES (:title, :desc, :loc, :edate, :etime, :enddate, :endtime, :status, :created_by, :max_att)
            RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'title'      => trim((string) $data['title']),
            'desc'       => trim((string) ($data['description'] ?? '')),
            'loc'        => trim((string) ($data['location'] ?? '')),
            'edate'      => $data['event_date'],
            'etime'      => !empty($data['event_time']) ? $data['event_time'] : null,
            'enddate'    => !empty($data['end_date']) ? $data['end_date'] : null,
            'endtime'    => !empty($data['end_time']) ? $data['end_time'] : null,
            'status'     => 'upcoming',
            'created_by' => (int) ($data['created_by'] ?? 0),
            'max_att'    => !empty($data['max_attendees']) ? (int) $data['max_attendees'] : null,
        ));
        return array('ok' => true, 'id' => (int) $stmt->fetchColumn());
    } catch (PDOException $e) {
        return array('ok' => false, 'error' => $e->getMessage());
    }
}

function update_event($pdo, $event_id, $data)
{
    try {
        $sql = "UPDATE events SET title = :title, description = :desc, location = :loc,
            event_date = :edate, event_time = :etime, end_date = :enddate, end_time = :endtime,
            status = :status, max_attendees = :max_att, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'title'   => trim((string) $data['title']),
            'desc'    => trim((string) ($data['description'] ?? '')),
            'loc'     => trim((string) ($data['location'] ?? '')),
            'edate'   => $data['event_date'],
            'etime'   => !empty($data['event_time']) ? $data['event_time'] : null,
            'enddate' => !empty($data['end_date']) ? $data['end_date'] : null,
            'endtime' => !empty($data['end_time']) ? $data['end_time'] : null,
            'status'  => $data['status'] ?? 'upcoming',
            'max_att' => !empty($data['max_attendees']) ? (int) $data['max_attendees'] : null,
            'id'      => (int) $event_id,
        ));
        return array('ok' => true);
    } catch (PDOException $e) {
        return array('ok' => false, 'error' => $e->getMessage());
    }
}

function delete_event($pdo, $event_id)
{
    try {
        $pdo->prepare('DELETE FROM events WHERE id = :id')->execute(array('id' => (int) $event_id));
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function list_events_admin($pdo, $page = 1, $per_page = 20)
{
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $per_page;
    try {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
        $sql = "SELECT e.*, u.name AS organizer_name,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id AND r.status = 'going') AS attendee_count
            FROM events e LEFT JOIN users u ON u.id = e.created_by
            ORDER BY e.event_date DESC, e.id DESC
            LIMIT " . (int) $per_page . " OFFSET " . (int) $offset;
        $items = $pdo->query($sql)->fetchAll();
        return array(
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => $total > 0 ? (int) ceil($total / $per_page) : 1,
        );
    } catch (PDOException $e) {
        return array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1);
    }
}
