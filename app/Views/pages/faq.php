<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal mb-4">
            <span class="hero-badge mb-3"><i class="fa-solid fa-circle-question me-2"></i><?php echo __('page.faq.badge'); ?></span>
            <h1 class="text-white mb-3"><?php echo __('page.faq.title'); ?></h1>
            <p class="text-secondary"><?php echo __('page.faq.intro', array('site' => __('site.name'))); ?></p>
        </div>

        <div class="faq-list reveal">
            <?php
            $faq_items = array(
                array('id' => 'faq1', 'q' => 'page.faq.q1', 'a' => 'page.faq.a1', 'open' => true),
                array('id' => 'faq2', 'q' => 'page.faq.q2', 'a' => 'page.faq.a2'),
                array('id' => 'faq3', 'q' => 'page.faq.q3', 'a' => 'page.faq.a3'),
                array('id' => 'faq4', 'q' => 'page.faq.q4', 'a' => 'page.faq.a4'),
                array('id' => 'faq5', 'q' => 'page.faq.q5', 'a' => 'page.faq.a5'),
                array('id' => 'faq6', 'q' => 'page.faq.q6', 'a' => 'page.faq.a6'),
            );
            foreach ($faq_items as $item):
            ?>
            <div class="faq-item glass-panel">
                <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $item['id']; ?>"<?php if (!empty($item['open'])) echo ' aria-expanded="true"'; ?>>
                    <span><?php echo __($item['q']); ?></span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="collapse<?php if (!empty($item['open'])) echo ' show'; ?>" id="<?php echo $item['id']; ?>">
                    <div class="faq-answer text-secondary"><?php echo __($item['a']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="glass-panel p-4 mt-4 reveal text-center">
            <p class="text-secondary mb-3"><?php echo __('page.faq.still_need', array('email' => site_contact_email())); ?></p>
            <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-gradient btn-sm me-2"><i class="fa-solid fa-envelope"></i> <?php echo __('nav.contact'); ?></a>
            <a href="<?php echo app_url('help-us.php'); ?>" class="btn btn-outline-custom btn-sm me-2"><i class="fa-solid fa-hand-holding-heart"></i> <?php echo __('nav.help_us'); ?></a>
            <a href="<?php echo app_url('report.php'); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-flag"></i> <?php echo __('nav.report'); ?></a>
        </div>
    </div>
</div>
