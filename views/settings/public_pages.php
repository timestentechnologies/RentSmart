<?php
$settings = $settings ?? [];

function sp($key, $fallback = '') {
    global $settings;
    $v = isset($settings[$key]) ? (string)$settings[$key] : '';
    return htmlspecialchars($v !== '' ? $v : (string)$fallback);
}

function rawSetting($key) {
    global $settings;
    return isset($settings[$key]) ? (string)$settings[$key] : '';
}

function jsonArray($key): array {
    $raw = rawSetting($key);
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

$testimonials = jsonArray('homepage_testimonials_json');
while (count($testimonials) < 3) $testimonials[] = ['name' => '', 'role' => '', 'text' => ''];

?>

<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0"><i class="bi bi-globe2 text-primary me-2"></i>Public Pages Content</h1>
                    <p class="text-muted mb-0 mt-1">Manage content for Contact, Terms, Privacy, and homepage sections like Testimonials & CTA</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/home" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>View Homepage</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/contact" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>View Contact</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/terms" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>View Terms</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/privacy-policy" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>View Privacy</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/updatePublicPages" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Contact Page</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hero Title</label>
                            <input type="text" class="form-control" name="contact_hero_title" value="<?= sp('contact_hero_title') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hero Subtitle</label>
                            <input type="text" class="form-control" name="contact_hero_subtitle" value="<?= sp('contact_hero_subtitle') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="contact_phone" value="<?= sp('contact_phone') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" name="contact_email" value="<?= sp('contact_email') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="contact_location" value="<?= sp('contact_location') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Terms Page</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Header Title</label>
                            <input type="text" class="form-control" name="terms_title" value="<?= sp('terms_title') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Email (in Terms)</label>
                            <input type="text" class="form-control" name="terms_contact_email" value="<?= sp('terms_contact_email') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Privacy Policy Page</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Header Title</label>
                            <input type="text" class="form-control" name="privacy_title" value="<?= sp('privacy_title') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Email (in Privacy)</label>
                            <input type="text" class="form-control" name="privacy_contact_email" value="<?= sp('privacy_contact_email') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Homepage - Testimonials</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Section Title</label>
                            <input type="text" class="form-control" name="homepage_testimonials_title" value="<?= sp('homepage_testimonials_title') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Section Subtitle</label>
                            <input type="text" class="form-control" name="homepage_testimonials_subtitle" value="<?= sp('homepage_testimonials_subtitle') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <?php for ($i=0; $i<3; $i++): $t = $testimonials[$i]; ?>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">Testimonial <?= $i+1 ?></div>
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="test_name[]" value="<?= htmlspecialchars((string)($t['name'] ?? '')) ?>">
                                    <label class="form-label mt-2">Role</label>
                                    <input type="text" class="form-control" name="test_role[]" value="<?= htmlspecialchars((string)($t['role'] ?? '')) ?>">
                                    <label class="form-label mt-2">Text</label>
                                    <textarea class="form-control" rows="3" name="test_text[]"><?= htmlspecialchars((string)($t['text'] ?? '')) ?></textarea>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Homepage - CTA Section</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">CTA Title</label>
                            <input type="text" class="form-control" name="homepage_cta_title" value="<?= sp('homepage_cta_title') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CTA Subtitle</label>
                            <input type="text" class="form-control" name="homepage_cta_subtitle" value="<?= sp('homepage_cta_subtitle') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CTA Button Text</label>
                            <input type="text" class="form-control" name="homepage_cta_button" value="<?= sp('homepage_cta_button') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CTA Note (small text)</label>
                            <input type="text" class="form-control" name="homepage_cta_note" value="<?= sp('homepage_cta_note') ?>" placeholder="(leave blank to keep current page text)">
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Public Pages</button>
                    <a href="<?= BASE_URL ?>/settings" class="btn btn-secondary ms-2">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
