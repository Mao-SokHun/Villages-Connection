<?php

$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page = max(1, (int) ($_GET['page'] ?? 1));

$page_title = 'Community Events';
$page_description = 'Upcoming and past community events, meetings, and gatherings.';

$upcoming = array();
$past     = array();
$selected_event = null;
$rsvp_result = null;

// Load events
$upcoming = get_upcoming_events($pdo, 20);
try {
    $past_stmt = $pdo->query("SELECT e.*, u.name AS organizer_name,
        (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id AND r.status = 'going') AS attendee_count
        FROM events e LEFT JOIN users u ON u.id = e.created_by
        WHERE e.status != 'cancelled' AND e.event_date < CURRENT_DATE
        ORDER BY e.event_date DESC LIMIT 8");
    $past = $past_stmt->fetchAll();
} catch (PDOException $e) {
    $past = array();
}

if ($event_id > 0) {
    $selected_event = get_event_by_id($pdo, $event_id);
}

$user_rsvp = null;
if ($selected_event && isLoggedIn()) {
    $user_rsvp = get_user_event_rsvp($pdo, $event_id, (int) $_SESSION['user_id']);
}

$attendees = array();
if ($selected_event) {
    $attendees = get_event_rsvps($pdo, $event_id, 'going', 20);
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="glass-panel p-4 p-md-5 mb-4 reveal">
    <h1 class="text-white mb-2"><i class="fa-solid fa-calendar-star text-warning me-2"></i> Community Events</h1>
    <p class="text-secondary mb-0">Discover and RSVP to local events, meetings, and gatherings.</p>
</div>

<div class="row g-4">
    <!-- Events list -->
    <div class="col-lg-7">
        <?php if (count($upcoming) === 0): ?>
        <div class="glass-panel p-4 reveal">
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark fa-2x text-secondary mb-3"></i>
                <p class="text-secondary">No upcoming events right now. Check back soon!</p>
            </div>
        </div>
        <?php else: ?>
        <div class="d-flex flex-column gap-3 reveal">
            <?php foreach ($upcoming as $ev): ?>
            <?php $is_sel = $selected_event && (int) $selected_event['id'] === (int) $ev['id']; ?>
            <a href="<?php echo app_url('events.php?id=' . (int) $ev['id']); ?>" class="glass-panel p-3 d-block text-decoration-none event-card <?php if ($is_sel) echo 'border border-warning'; ?>">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <span class="badge bg-success mb-1"><?php echo htmlspecialchars(ucfirst($ev['status'])); ?></span>
                        <h5 class="text-white mb-1"><?php echo htmlspecialchars($ev['title']); ?></h5>
                        <?php if (!empty($ev['location'])): ?>
                        <span class="text-secondary small"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($ev['location']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="text-warning fw-bold"><?php echo date('M j', strtotime($ev['event_date'])); ?></div>
                        <?php if (!empty($ev['event_time'])): ?>
                        <div class="text-secondary small"><?php echo date('g:i A', strtotime($ev['event_time'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-2 text-secondary small">
                    <i class="fa-solid fa-user-check me-1"></i><?php echo (int) $ev['attendee_count']; ?> going
                    <?php if ($ev['max_attendees']): ?> / <?php echo (int) $ev['max_attendees']; ?> max<?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (count($past) > 0): ?>
        <h5 class="text-white mt-5 mb-3"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i> Past Events</h5>
        <div class="d-flex flex-column gap-2 reveal">
            <?php foreach ($past as $ev): ?>
            <div class="glass-panel-sm p-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white fw-semibold"><?php echo htmlspecialchars($ev['title']); ?></span>
                    <?php if (!empty($ev['location'])): ?><span class="text-secondary small ms-2"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($ev['location']); ?></span><?php endif; ?>
                </div>
                <span class="text-secondary small"><?php echo date('M j, Y', strtotime($ev['event_date'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Event detail / RSVP -->
    <div class="col-lg-5">
        <?php if ($selected_event): ?>
        <div class="glass-panel p-4 sticky-sidebar reveal">
            <h4 class="text-white mb-1"><?php echo htmlspecialchars($selected_event['title']); ?></h4>
            <div class="text-secondary small mb-3">
                <i class="fa-regular fa-calendar me-1"></i>
                <?php echo date('l, F j, Y', strtotime($selected_event['event_date'])); ?>
                <?php if (!empty($selected_event['event_time'])): ?>
                · <?php echo date('g:i A', strtotime($selected_event['event_time'])); ?>
                <?php endif; ?>
                <?php if (!empty($selected_event['end_date'])): ?>
                – <?php echo date('M j', strtotime($selected_event['end_date'])); ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($selected_event['location'])): ?>
            <div class="text-secondary small mb-3"><i class="fa-solid fa-location-dot text-warning me-1"></i><?php echo htmlspecialchars($selected_event['location']); ?></div>
            <?php endif; ?>
            <?php if (!empty($selected_event['description'])): ?>
            <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($selected_event['description'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($selected_event['organizer_name'])): ?>
            <div class="text-secondary small mb-3"><i class="fa-solid fa-user me-1"></i>Organized by <?php echo htmlspecialchars($selected_event['organizer_name']); ?></div>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="text-secondary small"><i class="fa-solid fa-user-check me-1"></i><?php echo count($attendees); ?> going</span>
                <?php if ($selected_event['max_attendees']): ?>
                <span class="text-secondary small">/ <?php echo (int) $selected_event['max_attendees']; ?> max</span>
                <?php endif; ?>
            </div>

            <?php if (isLoggedIn() && $selected_event['status'] === 'upcoming' && $selected_event['event_date'] >= date('Y-m-d')): ?>
            <button type="button" class="btn <?php echo $user_rsvp ? 'btn-success' : 'btn-gradient'; ?> w-100 mb-3 btn-rsvp"
                id="btn-event-rsvp"
                data-event-id="<?php echo (int) $selected_event['id']; ?>"
                data-going="<?php echo $user_rsvp ? '1' : '0'; ?>">
                <i class="fa-solid fa-<?php echo $user_rsvp ? 'circle-check' : 'calendar-plus'; ?> me-1"></i>
                <?php echo $user_rsvp ? 'Going ✓' : 'RSVP — I\'m Going'; ?>
            </button>
            <?php elseif (!isLoggedIn()): ?>
            <a href="<?php echo app_url('login.php'); ?>" class="btn btn-gradient w-100 mb-3">Sign in to RSVP</a>
            <?php endif; ?>

            <?php if (count($attendees) > 0): ?>
            <div class="mt-2">
                <div class="text-secondary small mb-2 fw-semibold">ATTENDEES</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach (array_slice($attendees, 0, 12) as $att): ?>
                    <?php echo render_user_avatar($att['name'], $att['avatar'] ?? '', '', $att['email'] ?? ''); ?>
                    <?php endforeach; ?>
                    <?php if (count($attendees) > 12): ?>
                    <span class="text-secondary small align-self-center">+<?php echo count($attendees) - 12; ?> more</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="glass-panel p-4 text-center reveal">
            <i class="fa-solid fa-calendar-star fa-2x text-warning mb-3"></i>
            <p class="text-secondary">Click an event to see details and RSVP.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var rsvpBtn = document.getElementById('btn-event-rsvp');
    if (!rsvpBtn) return;
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    rsvpBtn.addEventListener('click', function() {
        var going = rsvpBtn.dataset.going === '1';
        var fd = new FormData();
        fd.append('action', 'rsvp');
        fd.append('event_id', rsvpBtn.dataset.eventId);
        fd.append('csrf_token', csrf);
        fetch(window.APP_BASE + 'api/event-rsvp.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.ok) {
                    var nowGoing = d.going;
                    rsvpBtn.dataset.going = nowGoing ? '1' : '0';
                    rsvpBtn.className = 'btn ' + (nowGoing ? 'btn-success' : 'btn-gradient') + ' w-100 mb-3 btn-rsvp';
                    rsvpBtn.innerHTML = '<i class="fa-solid fa-' + (nowGoing ? 'circle-check' : 'calendar-plus') + ' me-1"></i>' + (nowGoing ? 'Going ✓' : "RSVP — I'm Going");
                } else {
                    alert(d.error || 'Could not update RSVP.');
                }
            });
    });
})();
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
