<?php
$isRealtorListings = !empty($isRealtorListings ?? false);
$title = $isRealtorListings ? 'Jiji Integration - Post Listings' : 'Jiji Integration - Post Vacant Units';
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-megaphone-fill text-success me-2"></i>
                        Jiji Integration
                    </h1>
                    <p class="text-muted"><?= $isRealtorListings ? 'Post your listings to Jiji.co.ke marketplace' : 'Post your vacant units to Jiji.co.ke marketplace' ?></p>
                </div>
                <div class="btn-group">
                    <a href="<?= BASE_URL ?>/jiji/export" class="btn btn-success">
                        <i class="bi bi-download me-2"></i>Export to Jiji CSV
                    </a>
                    <button type="button" class="btn btn-primary" onclick="bulkPostToJiji()">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Post All to Jiji
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>
                        How to Post to Jiji
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mt-3">Method 1: CSV Export (Bulk Upload)</h6>
                            <ol>
                                <li>Click "Export to Jiji CSV" button above</li>
                                <li>Download the CSV file with all your <?= $isRealtorListings ? 'listings' : 'vacant units' ?></li>
                                <li>Go to <a href="https://jiji.co.ke" target="_blank">Jiji.co.ke</a> and login</li>
                                <li>Use Jiji's bulk upload feature to post all units at once</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mt-3">Method 2: Direct Posting (One by One)</h6>
                            <ol>
                                <li>Click "Post to Jiji" button on any unit below</li>
                                <li>Jiji.co.ke will open with pre-filled details</li>
                                <li>Upload photos and complete the listing</li>
                                <li>Click "Post Ad" on Jiji to publish</li>
                            </ol>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Note:</strong> You need a Jiji.co.ke account to post ads. Sign up for free at <a href="https://jiji.co.ke/register" target="_blank">jiji.co.ke/register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vacant Units List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-door-open me-2"></i>
                        <?= $isRealtorListings ? 'Listings Ready to Post' : 'Vacant Units Ready to Post' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($vacantUnits)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <?= $isRealtorListings ? 'No listings available to post.' : 'No vacant units available. All your units are currently occupied.' ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= $isRealtorListings ? 'Listing' : 'Property' ?></th>
                                        <th><?= $isRealtorListings ? 'Ref' : 'Unit' ?></th>
                                        <th>Type</th>
                                        <th><?= $isRealtorListings ? 'Price (KSh)' : 'Rent (KSh)' ?></th>
                                        <th><?= $isRealtorListings ? 'Location' : 'Location' ?></th>
                                        <th>Images</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vacantUnits as $unit): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($unit['property_name']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($unit['unit_number']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($unit['type']) ?></td>
                                            <td>
                                                <strong>KSh <?= number_format($unit['rent_amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?= htmlspecialchars($unit['city'] ?? 'N/A') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $imageCount = 0;
                                                if (!empty($unit['images'])) {
                                                    $imageCount = is_array($unit['images']) ? count($unit['images']) : 0;
                                                }
                                                ?>
                                                <?php if ($imageCount > 0): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-images me-1"></i><?= $imageCount ?> photos
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>No photos
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="postSingleToJiji(<?= $unit['id'] ?>)">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i>Post to Jiji
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Total <?= $isRealtorListings ? 'listings' : 'vacant units' ?>: <strong><?= count($vacantUnits) ?></strong>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Jiji Integration Functions
async function bulkPostToJiji() {
    try {
        const response = await fetch('<?= BASE_URL ?>/jiji/bulk-post', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.urls && data.urls.length > 0) {
            // Show confirmation with SweetAlert2
            Swal.fire({
                title: 'Post to Jiji?',
                html: `This will open <strong>${data.count}</strong> Jiji listing pages in new tabs.<br><br>You'll need to complete each listing manually on Jiji.co.ke`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Open Tabs',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0d6efd'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Open each URL in a new tab with a small delay
                    data.urls.forEach((item, index) => {
                        setTimeout(() => {
                            window.open(item.url, '_blank');
                        }, index * 500); // 500ms delay between each tab
                    });
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Tabs Opened!',
                        html: `Opened ${data.count} Jiji listing pages.<br>Complete each listing and click "Post Ad" on Jiji.`,
                        confirmButtonText: 'OK'
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Nothing Available',
                text: data.message || 'No <?= $isRealtorListings ? 'listings' : 'vacant units' ?> to post',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to generate Jiji listings. Please try again.',
            confirmButtonText: 'OK'
        });
    }
}

async function postSingleToJiji(unitId) {
    try {
        const response = await fetch('<?= BASE_URL ?>/jiji/generate-url?unit_id=' + unitId, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.url) {
            window.open(data.url, '_blank');
            
            Swal.fire({
                icon: 'success',
                title: 'Jiji Page Opened!',
                text: 'Complete the listing details and click "Post Ad" on Jiji.',
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to generate Jiji listing URL',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to generate Jiji listing. Please try again.',
            confirmButtonText: 'OK'
        });
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
