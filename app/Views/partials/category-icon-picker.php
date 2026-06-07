<?php
$picker_field = 'icon';
if (isset($icon_field_name) && $icon_field_name != '') {
    $picker_field = $icon_field_name;
}

$picker_selected = 'fa-tag';
if (isset($icon_selected) && $icon_selected != '') {
    $picker_selected = $icon_selected;
}

$icon_options = category_icon_options();
$emoji_presets = category_emoji_presets();
$picker_id = 'icon_picker_' . preg_replace('/[^a-z0-9_]/i', '_', $picker_field);
$is_emoji_selected = is_category_emoji_icon($picker_selected);
$emoji_value = '';
if ($is_emoji_selected) {
    $emoji_value = category_icon_emoji_char($picker_selected);
}
?>
<div class="category-icon-picker" id="<?php echo htmlspecialchars($picker_id); ?>" data-field="<?php echo htmlspecialchars($picker_field); ?>">
    <input type="hidden" name="<?php echo htmlspecialchars($picker_field); ?>" class="category-icon-input" value="<?php echo htmlspecialchars($picker_selected); ?>">

    <div class="icon-picker-tabs">
        <button type="button" class="icon-picker-tab <?php if (!$is_emoji_selected) echo 'active'; ?>" data-tab="fa">
            <i class="fa-solid fa-icons"></i> Icons
        </button>
        <button type="button" class="icon-picker-tab <?php if ($is_emoji_selected) echo 'active'; ?>" data-tab="emoji">
            <span class="icon-picker-tab-emoji">😀</span> Emoji / Sticker
        </button>
    </div>

    <div class="icon-picker-panel <?php if (!$is_emoji_selected) echo 'is-active'; ?>" data-panel="fa">
        <div class="icon-picker-grid">
            <?php foreach ($icon_options as $value => $label): ?>
            <button type="button"
                class="icon-pick-btn <?php if (!$is_emoji_selected && $picker_selected == $value) echo 'is-selected'; ?>"
                data-value="<?php echo htmlspecialchars($value); ?>"
                title="<?php echo htmlspecialchars($label); ?>">
                <i class="fa-solid <?php echo htmlspecialchars($value); ?>"></i>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="icon-picker-panel <?php if ($is_emoji_selected) echo 'is-active'; ?>" data-panel="emoji">
        <label class="form-label form-label-custom small mb-1">Paste emoji from your phone keyboard</label>
        <input type="text"
            class="form-control form-control-custom emoji-custom-input"
            inputmode="text"
            maxlength="16"
            placeholder="Tap here and choose emoji 😀🌾🏠"
            value="<?php echo htmlspecialchars($emoji_value); ?>">
        <p class="text-secondary small mb-2 mt-1"><i class="fa-solid fa-mobile-screen"></i> Works with phone emoji &amp; sticker keyboards</p>
        <div class="emoji-preset-grid">
            <?php foreach ($emoji_presets as $em): ?>
            <button type="button" class="emoji-pick-btn <?php if ($is_emoji_selected && $emoji_value == $em) echo 'is-selected'; ?>" data-emoji="<?php echo htmlspecialchars($em); ?>">
                <?php echo $em; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="icon-picker-preview">
        <span class="text-secondary small">Preview:</span>
        <span class="icon-picker-preview-value">
            <?php echo render_category_icon($picker_selected, 'icon-preview-glyph'); ?>
            <span class="icon-picker-preview-label"><?php echo htmlspecialchars(category_icon_label($picker_selected)); ?></span>
        </span>
    </div>
</div>
