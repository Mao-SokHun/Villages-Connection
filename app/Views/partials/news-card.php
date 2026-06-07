<?php
$p = null;
if (isset($art)) {
    $p = $art;
} elseif (isset($fp)) {
    $p = $fp;
}
if ($p == null) {
    return;
}

$img = media_url($p['image_url'], '');
$has_img = false;
if ($img != '' && isset($p['image_url']) && file_exists(PUBLIC_PATH . '/uploads/' . $p['image_url'])) {
    $has_img = true;
}
$has_video = post_has_video($p);
$slug = $p['slug'];

$cat_icon = 'fa-newspaper';
if (isset($p['category_icon']) && $p['category_icon'] != '') {
    $cat_icon = $p['category_icon'];
}

$cat_name = 'General';
if (isset($p['category_name']) && $p['category_name'] != '') {
    $cat_name = $p['category_name'];
}

$likes = 0;
if (isset($p['likes'])) {
    $likes = (int) $p['likes'];
}
?>
<article class="news-card glass-panel h-100">
    <a href="<?php echo post_url($slug); ?>" class="news-card-media d-block position-relative">
        <?php if ($has_img): ?>
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars(post_image_alt($p, $p['title'])); ?>" loading="lazy">
        <?php else: ?>
            <div class="news-card-placeholder">
                <?php echo render_category_icon($cat_icon, 'news-card-ph-icon'); ?>
            </div>
        <?php endif; ?>
        <?php if ($has_video): ?>
            <span class="media-badge video-badge"><i class="fa-solid fa-play me-1"></i>Video</span>
        <?php endif; ?>
        <?php if (!empty($p['is_featured'])): ?>
            <span class="media-badge featured-badge"><i class="fa-solid fa-star me-1"></i>Featured</span>
        <?php endif; ?>
    </a>
    <div class="news-card-body p-3 p-md-4">
        <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
            <span class="cat-badge"><?php echo render_category_icon($cat_icon, 'me-1'); ?><?php echo htmlspecialchars($cat_name); ?></span>
            <?php if (!empty($p['location'])): ?>
                <span class="text-secondary small"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($p['location']); ?></span>
            <?php endif; ?>
        </div>
        <h4 class="news-card-title">
            <a href="<?php echo post_url($slug); ?>"><?php echo htmlspecialchars($p['title']); ?></a>
        </h4>
        <p class="news-card-summary text-secondary small"><?php echo htmlspecialchars(excerpt($p['summary'], 100)); ?></p>
        <div class="news-card-meta d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary border-opacity-25">
            <span class="text-secondary small"><i class="fa-regular fa-calendar me-1"></i><?php echo khmer_date($p['created_at']); ?></span>
            <div class="d-flex gap-3 text-secondary small">
                <span><i class="fa-solid fa-eye me-1"></i><?php echo (int) $p['views']; ?></span>
                <span><i class="fa-solid fa-heart text-danger me-1"></i><?php echo $likes; ?></span>
            </div>
        </div>
        <a href="<?php echo post_url($slug); ?>" class="btn btn-outline-custom btn-sm w-100 mt-3">
            Read More <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
</article>
