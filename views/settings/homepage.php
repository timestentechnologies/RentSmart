<?php
$settings = $settings ?? [];

function s($key, $default = '') {
    global $settings;
    $v = isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
    return htmlspecialchars((string)$v);
}

function sj($key, $defaultJson = '[]') {
    global $settings;
    $raw = isset($settings[$key]) && $settings[$key] !== '' ? (string)$settings[$key] : (string)$defaultJson;
    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        $arr = json_decode((string)$defaultJson, true);
        if (!is_array($arr)) $arr = [];
    }
    return $arr;
}

$splitList = sj('homepage_split_list_json', '[]');
$stats = sj('homepage_stats_json', '[]');
$whyCards = sj('homepage_why_cards_json', '[]');
$faqs = sj('homepage_faqs_json', '[]');

$splitList = array_values($splitList);
$stats = array_values($stats);
$whyCards = array_values($whyCards);
$faqs = array_values($faqs);

while (count($splitList) < 3) $splitList[] = ['title' => '', 'text' => ''];
while (count($stats) < 4) $stats[] = ['number' => '', 'label' => ''];
while (count($whyCards) < 4) $whyCards[] = ['icon' => 'bi-star', 'title' => '', 'text' => ''];
while (count($faqs) < 8) $faqs[] = ['q' => '', 'a' => ''];

function imgUrl($file) {
    if (!$file) return '';
    return BASE_URL . '/public/assets/images/' . $file;
}

$splitImage = $settings['homepage_split_image'] ?? 'new.png';
?>

<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0"><i class="bi bi-house-gear text-primary me-2"></i>Homepage Content</h1>
                    <p class="text-muted mb-0 mt-1">Manage all public homepage sections from here</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/home" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>View Homepage</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/updateHomepage" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Hero Section</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hero Title</label>
                            <input type="text" class="form-control" name="homepage_hero_title" value="<?= s('homepage_hero_title') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hero Subtitle</label>
                            <input type="text" class="form-control" name="homepage_hero_subtitle" value="<?= s('homepage_hero_subtitle') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Stats Section (4 cards)</h5>
                    <div class="row g-3">
                        <?php for ($i=0; $i<4; $i++): $st = $stats[$i]; ?>
                            <div class="col-md-3">
                                <label class="form-label">Stat <?= $i+1 ?> Number</label>
                                <input type="text" class="form-control" name="stats_number[]" value="<?= htmlspecialchars((string)($st['number'] ?? '')) ?>">
                                <label class="form-label mt-2">Stat <?= $i+1 ?> Label</label>
                                <input type="text" class="form-control" name="stats_label[]" value="<?= htmlspecialchars((string)($st['label'] ?? '')) ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Split Section (below stats)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Badge Text</label>
                            <input type="text" class="form-control" name="homepage_split_badge" value="<?= s('homepage_split_badge') ?>">

                            <label class="form-label mt-3">Title</label>
                            <input type="text" class="form-control" name="homepage_split_title" value="<?= s('homepage_split_title') ?>">

                            <label class="form-label mt-3">Description</label>
                            <textarea class="form-control" rows="3" name="homepage_split_text"><?= s('homepage_split_text') ?></textarea>

                            <div class="row g-3 mt-1">
                                <?php for ($i=0; $i<3; $i++): $it = $splitList[$i]; ?>
                                    <div class="col-12">
                                        <label class="form-label">Bullet <?= $i+1 ?> Title</label>
                                        <input type="text" class="form-control" name="split_item_title[]" value="<?= htmlspecialchars((string)($it['title'] ?? '')) ?>">
                                        <label class="form-label mt-2">Bullet <?= $i+1 ?> Text</label>
                                        <input type="text" class="form-control" name="split_item_text[]" value="<?= htmlspecialchars((string)($it['text'] ?? '')) ?>">
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="homepage_split_image" accept="image/*">
                            <div class="form-text">Uploads to public/assets/images</div>
                            <div class="mt-2">
                                <div class="small text-muted mb-1">Current image:</div>
                                <?php if (!empty($splitImage)): ?>
                                    <img src="<?= htmlspecialchars(imgUrl($splitImage)) ?>" class="img-thumbnail" style="max-height: 180px;" alt="Split image">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Why Choose Us</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Section Title</label>
                            <input type="text" class="form-control" name="homepage_why_title" value="<?= s('homepage_why_title') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Section Subtitle</label>
                            <input type="text" class="form-control" name="homepage_why_subtitle" value="<?= s('homepage_why_subtitle') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <?php for ($i=0; $i<4; $i++): $c = $whyCards[$i]; ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">Card <?= $i+1 ?></div>
                                    <label class="form-label">Bootstrap Icon class (e.g. bi-phone)</label>
                                    <input type="text" class="form-control" name="why_icon[]" value="<?= htmlspecialchars((string)($c['icon'] ?? '')) ?>">
                                    <label class="form-label mt-2">Title</label>
                                    <input type="text" class="form-control" name="why_title[]" value="<?= htmlspecialchars((string)($c['title'] ?? '')) ?>">
                                    <label class="form-label mt-2">Text</label>
                                    <input type="text" class="form-control" name="why_text[]" value="<?= htmlspecialchars((string)($c['text'] ?? '')) ?>">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">FAQs (8 items)</h5>
                    <div class="row g-3">
                        <?php for ($i=0; $i<8; $i++): $f = $faqs[$i]; ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">FAQ <?= $i+1 ?></div>
                                    <label class="form-label">Question</label>
                                    <input type="text" class="form-control" name="faq_q[]" value="<?= htmlspecialchars((string)($f['q'] ?? '')) ?>">
                                    <label class="form-label mt-2">Answer</label>
                                    <textarea class="form-control" rows="3" name="faq_a[]"><?= htmlspecialchars((string)($f['a'] ?? '')) ?></textarea>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Homepage</button>
                    <a href="<?= BASE_URL ?>/settings" class="btn btn-secondary ms-2">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
