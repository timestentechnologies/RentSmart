<?php
$title = 'Marketplace Export - Post to Multiple Platforms';
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">
                <i class="bi bi-shop text-primary me-2"></i>
                Marketplace Export
            </h1>
            <p class="text-muted">Export your vacant units to multiple marketplace platforms</p>
        </div>
    </div>

    <!-- Platforms Info -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Supported Platforms
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Jiji.co.ke</h6>
                            <p class="small text-muted">Kenya's largest marketplace</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-check-circle-fill text-success me-2"></i>PigiaMe.co.ke</h6>
                            <p class="small text-muted">Popular classifieds platform</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-check-circle-fill text-success me-2"></i>BuyRentKenya.com</h6>
                            <p class="small text-muted">Real estate portal</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-check-circle-fill text-success me-2"></i>OLX Kenya</h6>
                            <p class="small text-muted">Classifieds marketplace</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Property24.co.ke</h6>
                            <p class="small text-muted">Premium property listings</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-download me-2"></i>
                        Export Options
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong><?= $vacantCount ?></strong> vacant units ready to export
                    </div>

                    <div class="row g-3">
                        <!-- Universal Export -->
                        <div class="col-md-6">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="bi bi-file-earmark-spreadsheet display-4 text-primary mb-3"></i>
                                    <h5 class="card-title">Universal CSV Export</h5>
                                    <p class="card-text">One CSV file for all platforms. Upload to any marketplace.</p>
                                    <a href="<?= BASE_URL ?>/integrations/export/universal" class="btn btn-primary btn-lg">
                                        <i class="bi bi-download me-2"></i>Download Universal CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Platform-Specific -->
                        <div class="col-md-6">
                            <div class="card h-100 border-secondary">
                                <div class="card-body">
                                    <i class="bi bi-gear display-4 text-secondary mb-3 d-block text-center"></i>
                                    <h5 class="card-title text-center">Platform-Specific Export</h5>
                                    <p class="card-text text-center mb-3">Export optimized for specific platforms</p>
                                    <div class="d-grid gap-2">
                                        <a href="<?= BASE_URL ?>/integrations/export/jiji" class="btn btn-outline-primary">
                                            <i class="bi bi-download me-2"></i>Jiji.co.ke
                                        </a>
                                        <a href="<?= BASE_URL ?>/integrations/export/pigiame" class="btn btn-outline-primary">
                                            <i class="bi bi-download me-2"></i>PigiaMe.co.ke
                                        </a>
                                        <a href="<?= BASE_URL ?>/integrations/export/buyrentkenya" class="btn btn-outline-primary">
                                            <i class="bi bi-download me-2"></i>BuyRentKenya.com
                                        </a>
                                        <a href="<?= BASE_URL ?>/integrations/export/olx" class="btn btn-outline-primary">
                                            <i class="bi bi-download me-2"></i>OLX Kenya
                                        </a>
                                        <a href="<?= BASE_URL ?>/integrations/export/property24" class="btn btn-outline-primary">
                                            <i class="bi bi-download me-2"></i>Property24.co.ke
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-question-circle me-2"></i>
                        How to Use
                    </h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li class="mb-2">
                            <strong>Download CSV:</strong> Click on "Download Universal CSV" or choose a platform-specific export
                        </li>
                        <li class="mb-2">
                            <strong>Go to Platform:</strong> Visit the marketplace website (e.g., Jiji.co.ke)
                        </li>
                        <li class="mb-2">
                            <strong>Login/Register:</strong> Create an account if you don't have one
                        </li>
                        <li class="mb-2">
                            <strong>Upload CSV:</strong> Use the platform's bulk upload feature (if available) or post manually
                        </li>
                        <li class="mb-2">
                            <strong>Review & Publish:</strong> Check the listings and publish them
                        </li>
                    </ol>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Some platforms may require manual posting. The CSV contains all the information you need to quickly create listings.
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
