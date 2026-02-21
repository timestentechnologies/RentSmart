<?php
ob_start();
$stats = $stats ?? [];
$occupancy = $stats['occupancy'] ?? ['occupancy_rate' => 0, 'occupied_units' => 0, 'total_units' => 0];
$isAdmin = $isAdmin ?? false;
$isRealtor = $isRealtor ?? false;
$users = $users ?? [];
?>

<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-graph-up text-primary me-2"></i>Reports & Analytics
                    </h1>
                    <p class="text-muted mb-0 mt-1"><?= $isRealtor ? 'Generate and export your realtor performance reports' : 'Generate and export detailed property management reports' ?></p>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row g-3 mb-4 mt-4">
        <div class="col-12 col-md-4">
            <div class="stat-card revenue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mt-3 mb-2">Ksh<?= number_format($stats['total_revenue'] ?? 0, 2) ?></h2>
                        <p class="mb-0 text-muted">Current month</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($isRealtor): ?>
        <div class="col-12 col-md-4">
            <div class="stat-card outstanding">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Listings Sold</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($stats['listings_sold'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Total listings marked sold</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-check2-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card occupancy">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Won Leads</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($stats['leads_won'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Converted / won</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-trophy fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-12 col-md-4">
            <div class="stat-card outstanding">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Outstanding Balance</h6>
                        <h2 class="mt-3 mb-2">Ksh<?= number_format($stats['outstanding_balance'] ?? 0, 2) ?></h2>
                        <p class="mb-0 text-muted">Total unpaid rent</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card occupancy">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Occupancy Rate</h6>
                        <h2 class="mt-3 mb-2"><?= number_format($occupancy['occupancy_rate'], 1) ?>%</h2>
                        <p class="mb-0 text-muted"><?= $occupancy['occupied_units'] ?> / <?= $occupancy['total_units'] ?> units</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-house-door fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Report Generator -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Generate Reports</h5>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/reports/generate" method="GET" class="row g-3">
                <?php if ($isAdmin): ?>
                <div class="col-md-3">
                    <label for="userId" class="form-label">Select User</label>
                    <select class="form-select" id="userId" name="user_id">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label for="reportType" class="form-label">Report Type</label>
                    <select class="form-select" id="reportType" name="type" required>
                        <option value="">Select Report Type</option>
                        <?php if ($isRealtor): ?>
                            <option value="realtor_financial">Financial Report</option>
                            <option value="realtor_listings">Listings Sold / Not Sold</option>
                            <option value="realtor_won_leads">Won Leads Report</option>
                        <?php else: ?>
                            <option value="financial">Financial Report</option>
                            <option value="occupancy">Occupancy Report</option>
                            <option value="tenant">Tenant Report</option>
                            <option value="lease">Lease Report</option>
                            <option value="maintenance">Maintenance Report</option>
                            <option value="delinquency">Delinquency Report</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" 
                           value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-2">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" 
                           value="<?= date('Y-m-t') ?>">
                </div>
                <div class="col-md-2">
                    <label for="format" class="form-label">Export Format</label>
                    <select class="form-select" id="format" name="format">
                        <option value="html">View Online</option>
                        <option value="pdf">Export as PDF</option>
                        <option value="csv">Export as CSV</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Reports -->
    <div class="row g-3">
        <!-- Financial Overview -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Financial Overview</h5>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/generate?type=<?= $isRealtor ? 'realtor_financial' : 'financial' ?>&format=pdf">Export as PDF</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/generate?type=<?= $isRealtor ? 'realtor_financial' : 'financial' ?>&format=csv">Export as CSV</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?= $isRealtor ? 'Client' : 'Tenant' ?></th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stats['recent_payments'])): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">No recent payments</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stats['recent_payments'] as $payment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($isRealtor ? ($payment['client_name'] ?? 'N/A') : ($payment['tenant_name'] ?? 'N/A')) ?></td>
                                            <td>Ksh<?= number_format($payment['amount'], 2) ?></td>
                                            <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                            <td><span class="badge bg-success">Completed</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$isRealtor): ?>
        <!-- Occupancy Overview -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Occupancy Overview</h5>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/generate?type=occupancy&format=pdf">Export as PDF</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/generate?type=occupancy&format=csv">Export as CSV</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Overall Occupancy</span>
                            <span class="text-primary"><?= number_format($occupancy['occupancy_rate'], 1) ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: <?= $occupancy['occupancy_rate'] ?>%" 
                                 aria-valuenow="<?= $occupancy['occupancy_rate'] ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col">
                            <h3 class="fw-bold text-success"><?= $occupancy['occupied_units'] ?></h3>
                            <p class="text-muted mb-0">Occupied Units</p>
                        </div>
                        <div class="col">
                            <h3 class="fw-bold text-warning"><?= $occupancy['total_units'] - $occupancy['occupied_units'] ?></h3>
                            <p class="text-muted mb-0">Vacant Units</p>
                        </div>
                        <div class="col">
                            <h3 class="fw-bold text-info"><?= $occupancy['total_units'] ?></h3>
                            <p class="text-muted mb-0">Total Units</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Listings Overview</h5>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/generate?type=realtor_listings&format=pdf">Export as PDF</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/generate?type=realtor_listings&format=csv">Export as CSV</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col">
                            <h3 class="fw-bold text-info"><?= (int)($stats['listings_total'] ?? 0) ?></h3>
                            <p class="text-muted mb-0">Total Listings</p>
                        </div>
                        <div class="col">
                            <h3 class="fw-bold text-success"><?= (int)($stats['listings_sold'] ?? 0) ?></h3>
                            <p class="text-muted mb-0">Sold</p>
                        </div>
                        <div class="col">
                            <h3 class="fw-bold text-warning"><?= (int)($stats['listings_not_sold'] ?? 0) ?></h3>
                            <p class="text-muted mb-0">Not Sold</p>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/reports/generate?type=realtor_won_leads&format=html">View Won Leads</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.stat-card {
    position: relative;
    padding: 1.5rem;
    border-radius: 0.5rem;
    background: var(--bg-secondary);
    color: var(--text-primary);
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.stat-card h2,
.stat-card h3,
.stat-card h4,
.stat-card h5,
.stat-card h6 {
    color: var(--text-primary);
}

.stat-card p,
.stat-card .text-muted {
    color: var(--text-secondary) !important;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    opacity: 0.8;
}

.revenue::before {
    background: linear-gradient(45deg, var(--success-color), #28a745);
}

.outstanding::before {
    background: linear-gradient(45deg, var(--warning-color), #e6a800);
}

.occupancy::before {
    background: linear-gradient(45deg, var(--primary-color), #0a58ca);
}

.stats-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range based on report type
    const reportType = document.getElementById('reportType');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    reportType.addEventListener('change', function() {
        const type = this.value;
        if (type === 'financial' || type === 'maintenance') {
            startDate.parentElement.style.display = 'block';
            endDate.parentElement.style.display = 'block';
        } else {
            startDate.parentElement.style.display = 'none';
            endDate.parentElement.style.display = 'none';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php'; 