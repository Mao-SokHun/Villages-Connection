<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$page_title = __('incident.title');
$page_description = __('incident.meta');
$page_breadcrumbs = array(
    array('label' => __('common.home'), 'url' => 'index.php'),
    array('label' => __('incident.breadcrumb'), 'url' => '')
);

$incident_types = array(
    'road_flood' => __('incident.type_road_flood'),
    'fire' => __('incident.type_fire'),
    'lost_item' => __('incident.type_lost_item'),
    'medical' => __('incident.type_medical'),
    'emergency' => __('incident.type_emergency'),
    'other' => __('incident.type_other'),
);
$priority_options = array(
    'low' => __('incident.priority_low'),
    'medium' => __('incident.priority_medium'),
    'high' => __('incident.priority_high'),
    'critical' => __('incident.priority_critical'),
);

$name = isLoggedIn() ? $_SESSION['user_name'] : '';
$email = isLoggedIn() ? $_SESSION['user_email'] : '';
$incident_type = 'other';
$priority = 'medium';
$title = '';
$details = '';
$village_name = '';
$location_text = '';
$latitude = '';
$longitude = '';
$errors = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();
    $limit_id = client_rate_limit_id();
    if (!rate_limit_hit('incident_report', $limit_id, 8, 3600)) {
        $errors[] = rate_limit_blocked_response('incident_report', $limit_id, 3600, false);
    }

    $name = isset($_POST['name']) ? sanitize_plain_text_field($_POST['name'], 100) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $incident_type = isset($_POST['incident_type']) ? trim($_POST['incident_type']) : 'other';
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'medium';
    $title = isset($_POST['title']) ? sanitize_plain_text_field($_POST['title'], 180) : '';
    $details = isset($_POST['details']) ? trim($_POST['details']) : '';
    $village_name = isset($_POST['village_name']) ? sanitize_plain_text_field($_POST['village_name'], 150) : '';
    $location_text = isset($_POST['location_text']) ? sanitize_plain_text_field($_POST['location_text'], 255) : '';
    $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
    $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';

    if ($name == '') {
        $errors[] = __('validation.name_required');
    }
    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('validation.email_invalid');
    }
    if (!isset($incident_types[$incident_type])) {
        $errors[] = __('validation.incident_type');
    }
    if (!isset($priority_options[$priority])) {
        $errors[] = __('validation.priority');
    }
    if ($title == '' || strlen($title) < 6) {
        $errors[] = __('validation.title_min');
    }
    if (strlen($details) < 12) {
        $errors[] = __('validation.incident_details_min');
    }
    if ($latitude !== '' && !is_numeric($latitude)) {
        $errors[] = __('validation.latitude');
    }
    if ($longitude !== '' && !is_numeric($longitude)) {
        $errors[] = __('validation.longitude');
    }

    if (count($errors) == 0) {
        $incident_id = save_incident_report($pdo, array(
            'user_id' => isLoggedIn() ? (int) $_SESSION['user_id'] : 0,
            'reporter_name' => $name,
            'reporter_email' => $email,
            'incident_type' => $incident_type,
            'priority' => $priority,
            'title' => $title,
            'details' => $details,
            'village_name' => $village_name,
            'location_text' => $location_text,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ));
        setFlashMessage('success', __('incident.success', array('id' => $incident_id)));
        redirect_to('incident-report.php');
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
render_breadcrumb($page_breadcrumbs, $base_path);
?>
<div class="row justify-content-center g-4">
    <div class="col-lg-8">
        <div class="glass-panel p-4">
            <h3 class="text-white mb-2"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i><?php echo __('incident.heading'); ?></h3>
            <p class="text-secondary small mb-4"><?php echo __('incident.desc'); ?></p>

            <?php if (count($errors) > 0): ?>
            <?php render_user_alerts($errors, 'danger'); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo app_url('incident-report.php'); ?>">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.your_name'); ?></label>
                        <input type="text" name="name" class="form-control form-control-custom" required value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.email'); ?></label>
                        <input type="email" name="email" class="form-control form-control-custom" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.type'); ?></label>
                        <select name="incident_type" class="form-select form-control-custom">
                            <?php foreach ($incident_types as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php if ($incident_type === $key) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.priority_label'); ?></label>
                        <select name="priority" class="form-select form-control-custom">
                            <?php foreach ($priority_options as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php if ($priority === $key) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-custom"><?php echo __('incident.title_label'); ?></label>
                        <input type="text" name="title" class="form-control form-control-custom" required value="<?php echo htmlspecialchars($title); ?>" placeholder="<?php echo htmlspecialchars(__('incident.title_placeholder')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-custom"><?php echo __('incident.details'); ?></label>
                        <textarea name="details" rows="4" class="form-control form-control-custom" required placeholder="<?php echo htmlspecialchars(__('incident.details_placeholder')); ?>"><?php echo htmlspecialchars($details); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.village'); ?></label>
                        <input type="text" name="village_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($village_name); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.location'); ?></label>
                        <input type="text" name="location_text" class="form-control form-control-custom" value="<?php echo htmlspecialchars($location_text); ?>" placeholder="<?php echo htmlspecialchars(__('incident.location_placeholder')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.latitude'); ?></label>
                        <input type="text" name="latitude" class="form-control form-control-custom" value="<?php echo htmlspecialchars($latitude); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom"><?php echo __('incident.longitude'); ?></label>
                        <input type="text" name="longitude" class="form-control form-control-custom" value="<?php echo htmlspecialchars($longitude); ?>">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-gradient"><i class="fa-solid fa-paper-plane"></i> <?php echo __('incident.submit'); ?></button>
                    <a href="<?php echo app_url('index.php'); ?>" class="btn btn-outline-custom"><?php echo __('common.cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
