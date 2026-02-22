<?php
$title = 'Facebook Marketplace Integration';
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">
                <i class="bi bi-facebook text-primary me-2"></i>
                Facebook Marketplace Integration
            </h1>
            <p class="text-muted">Automatically post your vacant units to Facebook Marketplace</p>
        </div>
    </div>

    <?php if (!$isConfigured): ?>
    <!-- Configuration Required -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Configuration Required
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Please configure your Facebook credentials to start posting units.</p>

                    <form method="POST" action="<?= BASE_URL ?>/integrations/facebook/config">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Facebook Page Access Token</label>
                                <input type="text" name="access_token" class="form-control" placeholder="EAAI...">
                                <small class="form-text text-muted">Get this from Facebook Developers Console</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Facebook Page ID</label>
                                <input type="text" name="page_id" class="form-control" placeholder="123456789">
                                <small class="form-text text-muted">Your Facebook Page ID</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Save Configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isConfigured): ?>
    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        Facebook Marketplace Connected
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <i class="bi bi-facebook display-4 text-primary"></i>
                                <h4 class="mt-2">Connected</h4>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <p class="mb-2"><strong>Access Token:</strong> <?= substr($accessToken ?? '', 0, 20) ?>...</p>
                            <p class="mb-2"><strong>Page ID:</strong> <?= $pageId ?? 'Not set' ?></p>
                            <p class="mb-0"><strong>Status:</strong> Ready to post units</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vacant Units -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-house-door me-2"></i>
                        Vacant Units Ready to Post
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($vacantUnits)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No vacant units available. All your units are currently occupied.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Unit</th>
                                        <th>Type</th>
                                        <th>Rent (KSh)</th>
                                        <th>Images</th>
                                        <th>Facebook Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vacantUnits as $unit): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($unit['property_name']) ?></strong></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($unit['unit_number']) ?></span></td>
                                            <td><?= htmlspecialchars($unit['type']) ?></td>
                                            <td><strong>KSh <?= number_format($unit['rent_amount'], 2) ?></strong></td>
                                            <td>
                                                <?php if ($unit['image_count'] > 0): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-images me-1"></i><?= $unit['image_count'] ?> photos
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>No photos
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($unit['__is_realtor_listing'] ?? null)): ?>
                                                    <span class="badge bg-light text-dark">Listing</span>
                                                <?php elseif ($unit['is_posted']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Posted
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-circle me-1"></i>Not Posted
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($unit['__is_realtor_listing'] ?? null)): ?>
                                                    <span class="text-muted small">Posting is available for Units only</span>
                                                <?php elseif ($unit['is_posted']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteFromFacebook(<?= $unit['id'] ?>)">
                                                        <i class="bi bi-trash me-1"></i>Remove
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="postToFacebook(<?= $unit['id'] ?>)">
                                                        <i class="bi bi-facebook me-1"></i>Post
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function postToFacebook(unitId) {
    try {
        const response = await fetch('<?= BASE_URL ?>/integrations/facebook/post', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ unit_id: unitId })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Posted Successfully!',
                text: 'Your unit has been posted to Facebook Marketplace',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Post Failed',
                text: data.message || 'Failed to post to Facebook Marketplace',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to post to Facebook Marketplace. Please try again.',
            confirmButtonText: 'OK'
        });
    }
}

async function deleteFromFacebook(unitId) {
    if (!confirm('Are you sure you want to remove this listing from Facebook Marketplace?')) {
        return;
    }

    try {
        const response = await fetch('<?= BASE_URL ?>/integrations/facebook/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ unit_id: unitId })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Removed Successfully!',
                text: 'Listing removed from Facebook Marketplace',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Delete Failed',
                text: data.message || 'Failed to remove from Facebook Marketplace',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to remove from Facebook Marketplace. Please try again.',
            confirmButtonText: 'OK'
        });
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
