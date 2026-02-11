<?php
ob_start();

$settings = $settings ?? [];

function setting_raw($key, $default = '') {
    global $settings;
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

function setting_json_pretty($key, $defaultJson = '[]') {
    $raw = trim((string)setting_raw($key, ''));
    if ($raw === '') {
        return $defaultJson;
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $defaultJson;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Public Pages Content</h4>
            <p class="text-muted">Manage homepage and public page text/images. If a field is left blank, the existing hardcoded content remains as fallback.</p>
        </div>
        <a href="<?= BASE_URL ?>/settings" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Settings
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/updatePublicPages" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <h6 class="fw-bold mb-3">Homepage - Hero</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Hero Title</label>
                        <input type="text" class="form-control" name="home_hero_title" value="<?= htmlspecialchars(setting_raw('home_hero_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hero Subtitle</label>
                        <input type="text" class="form-control" name="home_hero_subtitle" value="<?= htmlspecialchars(setting_raw('home_hero_subtitle', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hero Primary Button Text</label>
                        <input type="text" class="form-control" name="home_hero_primary_text" value="<?= htmlspecialchars(setting_raw('home_hero_primary_text', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hero Secondary Button Text</label>
                        <input type="text" class="form-control" name="home_hero_secondary_text" value="<?= htmlspecialchars(setting_raw('home_hero_secondary_text', '')) ?>">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Stats (4 cards)</h6>
                <div class="row g-3 mb-4">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="col-md-3">
                            <label class="form-label">Stat <?= $i ?> Number</label>
                            <input type="text" class="form-control" name="home_stat<?= $i ?>_number" value="<?= htmlspecialchars(setting_raw('home_stat' . $i . '_number', '')) ?>">
                            <label class="form-label mt-2">Stat <?= $i ?> Label</label>
                            <input type="text" class="form-control" name="home_stat<?= $i ?>_label" value="<?= htmlspecialchars(setting_raw('home_stat' . $i . '_label', '')) ?>">
                        </div>
                    <?php endfor; ?>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Split Section (below stats)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Split Badge Text</label>
                        <input type="text" class="form-control" name="home_split_badge" value="<?= htmlspecialchars(setting_raw('home_split_badge', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Split Title</label>
                        <input type="text" class="form-control" name="home_split_title" value="<?= htmlspecialchars(setting_raw('home_split_title', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Split Description</label>
                        <textarea class="form-control" name="home_split_description" rows="3"><?= htmlspecialchars(setting_raw('home_split_description', '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Split Image</label>
                        <input type="file" class="form-control" name="home_split_image" accept="image/*">
                        <?php if (!empty($settings['home_split_image'])): ?>
                            <div class="form-text">Current: <?= htmlspecialchars($settings['home_split_image']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Split Bullets (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_split_bullets_json" rows="8" placeholder='[{"title":"...","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_split_bullets_json', '[]')) ?></textarea>
                        <div class="form-text">Example: [{"title":"Accurate payment types","text":"Rent, utilities..."}]</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Why Choose Us</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_why_title" value="<?= htmlspecialchars(setting_raw('home_why_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_why_subtitle" value="<?= htmlspecialchars(setting_raw('home_why_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Cards (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_why_cards_json" rows="10" placeholder='[{"title":"M-PESA ready","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_why_cards_json', '[]')) ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Features</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_features_title" value="<?= htmlspecialchars(setting_raw('home_features_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_features_subtitle" value="<?= htmlspecialchars(setting_raw('home_features_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Feature Cards (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_features_cards_json" rows="12" placeholder='[{"icon":"bi bi-house-door","title":"Property Management","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_features_cards_json', '[]')) ?></textarea>
                        <div class="form-text">Example: [{"icon":"bi bi-cash-coin","title":"Rent Collection","text":"Collect rent via M-PESA..."}]</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Testimonials</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_testimonials_title" value="<?= htmlspecialchars(setting_raw('home_testimonials_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_testimonials_subtitle" value="<?= htmlspecialchars(setting_raw('home_testimonials_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Testimonials (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_testimonials_json" rows="10" placeholder='[{"name":"...","role":"...","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_testimonials_json', '[]')) ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - FAQs</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_faq_title" value="<?= htmlspecialchars(setting_raw('home_faq_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_faq_subtitle" value="<?= htmlspecialchars(setting_raw('home_faq_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">FAQ items (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_faq_items_json" rows="12" placeholder='[{"q":"...","a":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_faq_items_json', '[]')) ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - CTA</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">CTA Title</label>
                        <input type="text" class="form-control" name="home_cta_title" value="<?= htmlspecialchars(setting_raw('home_cta_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CTA Button Text</label>
                        <input type="text" class="form-control" name="home_cta_button_text" value="<?= htmlspecialchars(setting_raw('home_cta_button_text', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">CTA Description</label>
                        <textarea class="form-control" name="home_cta_description" rows="3"><?= htmlspecialchars(setting_raw('home_cta_description', '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CTA Footnote</label>
                        <input type="text" class="form-control" name="home_cta_footnote" value="<?= htmlspecialchars(setting_raw('home_cta_footnote', '')) ?>">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Contact Page - Hero</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Hero Title</label>
                        <input type="text" class="form-control" name="contact_hero_title" value="<?= htmlspecialchars(setting_raw('contact_hero_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hero Subtitle</label>
                        <input type="text" class="form-control" name="contact_hero_subtitle" value="<?= htmlspecialchars(setting_raw('contact_hero_subtitle', '')) ?>">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Terms & Privacy Page - Header</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Terms Header</label>
                        <input type="text" class="form-control" name="terms_header" value="<?= htmlspecialchars(setting_raw('terms_header', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Privacy Header</label>
                        <input type="text" class="form-control" name="privacy_header" value="<?= htmlspecialchars(setting_raw('privacy_header', '')) ?>">
                    </div>
                </div>

                <div class="border-top pt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Public Content
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
