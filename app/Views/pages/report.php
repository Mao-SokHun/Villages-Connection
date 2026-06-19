<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal text-center text-md-start mb-4">
            <span class="hero-badge mb-3"><i class="fa-solid fa-flag me-2"></i><?php echo __('page.report.badge'); ?></span>
            <h1 class="text-white mb-3"><?php echo __('page.report.title'); ?></h1>
            <p class="text-secondary mb-0"><?php echo __('page.report.intro', array('site' => __('site.name'))); ?></p>
        </div>

        <?php if ($sent): ?>
        <div class="alert alert-success glass-panel-sm reveal" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?php echo __('page.report.thank_you'); ?>
        </div>
        <?php endif; ?>

        <div class="glass-panel p-4 p-md-5 reveal">
            <?php if (count($errors) > 0): ?>
            <?php render_user_alerts($errors, 'danger'); ?>
            <?php endif; ?>

            <form action="<?php echo app_url('report.php'); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="report_name" class="form-label form-label-custom"><?php echo __('form.your_name'); ?></label>
                        <input type="text" name="name" id="report_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="report_email" class="form-label form-label-custom"><?php echo __('form.email'); ?></label>
                        <input type="email" name="email" id="report_email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="report_reason" class="form-label form-label-custom"><?php echo __('form.reason'); ?></label>
                        <select name="reason" id="report_reason" class="form-select form-control-custom" required>
                            <option value=""><?php echo __('form.select_reason'); ?></option>
                            <?php foreach ($reason_options as $option_key => $option_label): ?>
                            <option value="<?php echo htmlspecialchars($option_key); ?>" <?php if ($reason == $option_key) echo 'selected'; ?>><?php echo htmlspecialchars($option_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="report_post_url" class="form-label form-label-custom"><?php echo __('form.post_url'); ?></label>
                        <input type="url" name="post_url" id="report_post_url" class="form-control form-control-custom" value="<?php echo htmlspecialchars($post_url); ?>" placeholder="<?php echo htmlspecialchars(__('page.report.post_url_placeholder')); ?>">
                    </div>
                    <div class="col-12">
                        <label for="report_details" class="form-label form-label-custom"><?php echo __('form.details'); ?></label>
                        <textarea name="details" id="report_details" rows="5" class="form-control form-control-custom" required placeholder="<?php echo htmlspecialchars(__('page.report.details_placeholder')); ?>"><?php echo htmlspecialchars($details); ?></textarea>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-gradient px-4">
                        <i class="fa-solid fa-flag"></i> <?php echo __('form.submit_report'); ?>
                    </button>
                    <a href="<?php echo app_url('help-us.php'); ?>" class="btn btn-outline-custom px-4"><?php echo __('page.report.help_us'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
