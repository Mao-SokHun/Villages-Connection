<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal text-center text-md-start mb-4">
            <span class="hero-badge mb-3"><i class="fa-solid fa-flag me-2"></i>Moderation</span>
            <h1 class="text-white mb-3">Report Content</h1>
            <p class="text-secondary mb-0">Help keep <?php echo SITE_NAME; ?> safe. Tell us about spam, harassment, or other content that breaks community rules.</p>
        </div>

        <?php if ($sent): ?>
        <div class="alert alert-success glass-panel-sm reveal" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            Thank you for your report. Our team will review it and take action if needed.
        </div>
        <?php endif; ?>

        <div class="glass-panel p-4 p-md-5 reveal">
            <?php if (count($errors) > 0): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 small">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form action="report.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="report_name" class="form-label form-label-custom">Your Name</label>
                        <input type="text" name="name" id="report_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="report_email" class="form-label form-label-custom">Email Address</label>
                        <input type="email" name="email" id="report_email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="report_reason" class="form-label form-label-custom">Reason</label>
                        <select name="reason" id="report_reason" class="form-select form-control-custom" required>
                            <option value="">Select a reason</option>
                            <?php foreach ($reason_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php if ($reason == $option) echo 'selected'; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="report_post_url" class="form-label form-label-custom">Post URL (optional)</label>
                        <input type="url" name="post_url" id="report_post_url" class="form-control form-control-custom" value="<?php echo htmlspecialchars($post_url); ?>" placeholder="https://.../post.php?slug=...">
                    </div>
                    <div class="col-12">
                        <label for="report_details" class="form-label form-label-custom">Details</label>
                        <textarea name="details" id="report_details" rows="5" class="form-control form-control-custom" required placeholder="Describe what is wrong and why it should be reviewed."><?php echo htmlspecialchars($details); ?></textarea>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-gradient px-4">
                        <i class="fa-solid fa-flag"></i> Submit Report
                    </button>
                    <a href="help-us.php" class="btn btn-outline-custom px-4">Help Us Grow</a>
                </div>
            </form>
        </div>
    </div>
</div>
