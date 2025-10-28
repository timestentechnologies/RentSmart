<?php
$title = 'Odoo ERP Integration';
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">
                <i class="bi bi-gear-wide-connected text-primary me-2"></i>
                Odoo ERP Integration
            </h1>
            <p class="text-muted">Automatically sync your payments and expenses to Odoo ERP</p>
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
                    <p class="mb-3">Please configure your Odoo credentials to start syncing data.</p>

                    <form method="POST" action="<?= BASE_URL ?>/integrations/odoo/config">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Odoo URL</label>
                                <input type="url" name="url" class="form-control" placeholder="https://yourcompany.odoo.com">
                                <small class="form-text text-muted">Your Odoo instance URL</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database Name</label>
                                <input type="text" name="database" class="form-control" placeholder="yourcompany">
                                <small class="form-text text-muted">Odoo database name</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="admin@example.com">
                                <small class="form-text text-muted">Your Odoo login email</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Your Odoo password">
                                <small class="form-text text-muted">Your Odoo login password</small>
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
                        Odoo ERP Connected
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <i class="bi bi-gear-wide-connected display-4 text-primary"></i>
                                <h4 class="mt-2">Connected</h4>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <p class="mb-2"><strong>URL:</strong> <?= substr($url ?? '', 0, 30) ?>...</p>
                            <p class="mb-2"><strong>Database:</strong> <?= $database ?? 'Not set' ?></p>
                            <p class="mb-0"><strong>Status:</strong> Ready to sync data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sync Actions -->
    <?php if ($isConfigured): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Sync Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="bi bi-cash-stack display-4 text-primary mb-3"></i>
                                    <h5 class="card-title">Sync Payments</h5>
                                    <p class="card-text">Create invoices and record payments in Odoo</p>
                                    <button type="button" class="btn btn-primary btn-lg" onclick="syncPayments()">
                                        <i class="bi bi-arrow-up me-2"></i>Sync Payments
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="bi bi-receipt display-4 text-warning mb-3"></i>
                                    <h5 class="card-title">Sync Expenses</h5>
                                    <p class="card-text">Create expense records in Odoo</p>
                                    <button type="button" class="btn btn-warning btn-lg" onclick="syncExpenses()">
                                        <i class="bi bi-arrow-up me-2"></i>Sync Expenses
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Sync Activity -->
    <?php if (!empty($syncLog)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Sync Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Entity ID</th>
                                    <th>Status</th>
                                    <th>External ID</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($syncLog, 0, 10) as $log): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">Payment</span></td>
                                        <td>#<?= $log['entity_id'] ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $log['external_id'] ?? '-' ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($log['synced_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function syncPayments() {
    try {
        const button = event.target;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Syncing...';

        const response = await fetch('<?= BASE_URL ?>/integrations/odoo/sync-payments', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sync Complete!',
                html: `Successfully synced <strong>${data.synced}</strong> payments to Odoo`,
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sync Failed',
                text: data.message || 'Failed to sync payments',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to sync payments. Please try again.',
            confirmButtonText: 'OK'
        });
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-arrow-up me-2"></i>Sync Payments';
    }
}

async function syncExpenses() {
    try {
        const button = event.target;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Syncing...';

        const response = await fetch('<?= BASE_URL ?>/integrations/odoo/sync-expenses', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sync Complete!',
                html: `Successfully synced <strong>${data.synced}</strong> expenses to Odoo`,
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sync Failed',
                text: data.message || 'Failed to sync expenses',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to sync expenses. Please try again.',
            confirmButtonText: 'OK'
        });
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-arrow-up me-2"></i>Sync Expenses';
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
