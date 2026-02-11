<?php
ob_start();

$companyName = $companyName ?? '';
$companyLogo = $companyLogo ?? '';

function brandingImageUrl($filename)
{
    if (empty($filename)) return '';
    return BASE_URL . '/public/assets/images/' . ltrim((string)$filename, '/');
}
?>

<div class="container-fluid px-4 pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-building text-primary me-2"></i>Company Branding</h1>
                <p class="text-muted mb-0 mt-1">Customize your dashboard logo and title for your company</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Branding Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/branding/update" enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="company_name">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($companyName) ?>" placeholder="e.g. Acme Property Managers">
                            <div class="form-text">If set, this will be used in the browser tab title and sidebar logo alt text.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="company_logo">Company Logo</label>
                            <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                            <div class="form-text">Recommended: transparent PNG, max-height ~50px.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/dashboard">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Branding</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Company Name</div>
                        <div class="fw-semibold"><?= $companyName !== '' ? htmlspecialchars($companyName) : 'Not set' ?></div>
                    </div>

                    <div>
                        <div class="text-muted small mb-2">Company Logo</div>
                        <?php if (!empty($companyLogo)): ?>
                            <img src="<?= htmlspecialchars(brandingImageUrl($companyLogo)) ?>" alt="Company Logo" style="max-height:50px; max-width:100%;" class="img-fluid">
                        <?php else: ?>
                            <div class="text-muted">Not set</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
