<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Dashboard</h1>
        </div>
    </div>

    <div class="card mt-4 mb-4">
        <div class="card-body">
            <form method="GET" id="dashboardFilters" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Month</label>
                    <input type="month" id="dashboardMonth" name="month" class="form-control" value="<?= htmlspecialchars($selectedMonth ?? date('Y-m')) ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label">Property</label>
                    <select id="dashboardProperty" name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        <?php foreach (($properties ?? []) as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (isset($selectedPropertyId) && (int)$selectedPropertyId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dashboardFilters');
    const month = document.getElementById('dashboardMonth');
    const property = document.getElementById('dashboardProperty');
    if (!form || !month || !property) return;

    const submit = () => {
        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
    };

    month.addEventListener('change', submit);
    property.addEventListener('change', submit);
});
</script>

    <!-- Welcome Message -->
    <div class="card mt-4 mb-4 bg-info bg-opacity-25">
        <div class="card-body">
            <h5 class="card-title">Welcome, <?= htmlspecialchars($user['name']) ?>!</h5>
            <p class="mb-0">Welcome to your RentSmart dashboard. Here you can manage your properties and view important information.</p>
        </div>
    </div>

    <!-- Subscription Warning -->
    <?php if (isset($_SESSION['subscription_ends_at']) && $_SESSION['user_role'] !== 'administrator'): ?>
        <?php
        $expiryDate = new DateTime($_SESSION['subscription_ends_at']);
        $now = new DateTime();
        $interval = $now->diff($expiryDate);
        $daysLeft = $interval->invert ? 0 : $interval->days;
        $status = isset($_SESSION['subscription_status']) ? $_SESSION['subscription_status'] : 'active';
        ?>
        <div class="card mb-4 bg-info bg-opacity-25">
            <div class="card-body">
                <p class="mb-0">
                    <i class="bi bi-calendar-event me-2"></i>
                    Your <?= $status === 'trialing' ? 'trial' : 'subscription' ?> expires on 
                    <strong><?= $expiryDate->format('F j, Y') ?></strong>
                    <?php if ($daysLeft <= 7): ?>
                        - <a href="<?= BASE_URL ?>/subscription/renew" class="text-info text-decoration-underline">Renew now</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Money Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card revenue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Rent Balance Wallet</h6>
                        <h2 class="mt-3 mb-2">Ksh<?= number_format($walletTotal ?? 0, 2) ?></h2>
                        <div class="small text-muted">
                            Received: Ksh<?= number_format($receivedTotal ?? 0, 2) ?><br>
                            Deductions: Ksh<?= number_format($rentBalanceExpenses ?? 0, 2) ?><br>
                            Rent: Ksh<?= number_format($rentReceived ?? 0, 2) ?><br>
                            Utilities: Ksh<?= number_format($utilityReceived ?? 0, 2) ?><br>
                            Maintenance: Ksh<?= number_format($maintenanceReceived ?? 0, 2) ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-wallet2 fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card occupancy">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Expected to Receive</h6>
                        <h2 class="mt-3 mb-2">Ksh<?= number_format($expectedTotal ?? 0, 2) ?></h2>
                        <div class="small text-muted">
                            Rent: Ksh<?= number_format($rentExpected ?? 0, 2) ?><br>
                            Utilities: Ksh<?= number_format($utilityExpected ?? 0, 2) ?><br>
                            Maintenance: Ksh<?= number_format($maintenanceExpected ?? 0, 2) ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-graph-up-arrow fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card outstanding">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Money Not Received</h6>
                        <h2 class="mt-3 mb-2">Ksh<?= number_format($notReceivedTotal ?? 0, 2) ?></h2>
                        <div class="small text-muted">
                            Rent: Ksh<?= number_format($notReceivedRent ?? 0, 2) ?><br>
                            Utilities: Ksh<?= number_format($notReceivedUtility ?? 0, 2) ?><br>
                            Maintenance: Ksh<?= number_format($notReceivedMaintenance ?? 0, 2) ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-exclamation-circle fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Totals -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card occupancy">
                <div class="d-flex justify-content-between align-items-start">
                    <div>Acive
                        <h6 class="card-title">Total Propictives</h6>Count
                        <h2 class="mt-3 mb-2"><?= (issigned)(o o utiaProperties ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Tracked properties</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-buildings fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card revenue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Tenants</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($totalTenants ?? 0) ?></h2>
                        <p class="mb-0 text-muted">All tenants</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-people fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card expenses">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Units</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($totalUnits ?? 0) ?></h2>
                        <p class="mb-0 text-muted">All units</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-door-open fs-1 text-danger opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card outstanding">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Expiring (30 days)</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($totalExpiringLeases ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Leases ending soon</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-calendar-event fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Revenue Trend -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Occupancy Distribution -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Property Occupancy</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="occupancyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Properties Section -->
    <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Properties</h5>
            <a href="<?= BASE_URL ?>/properties" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <?php foreach ($recentProperties as $property): ?>
                    <div class="col-md-6">
                        <div class="property-card card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($property['name']) ?></h6>
                                        <small class="text-muted"><?= $property['total_units'] ?> Units • <?= $property['occupied_units'] ?> Occupied</small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-1">Ksh<?= number_format($property['monthly_revenue'], 2) ?></h6>
                                        <small class="text-muted">Monthly Revenue</small>
                                    </div>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                        style="width: <?= ($property['occupied_units'] / $property['total_units']) * 100 ?>%" 
                                        aria-valuenow="<?= ($property['occupied_units'] / $property['total_units']) * 100 ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Payments</h5>
            <a href="<?= BASE_URL ?>/payments" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($recentPayments as $payment): ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-light text-muted me-3">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= htmlspecialchars($payment['tenant_name']) ?></h6>
                                <small class="text-muted">Paid on <?= date('M d, Y', strtotime($payment['payment_date'])) ?></small>
                            </div>
                            <div class="text-success fw-medium">
                                Ksh<?= number_format($payment['amount'], 2) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-container {
    position: relative;
    width: 100%;
}

.bg-info {
    background-color: #0dcaf0 !important;
}

.bg-info.bg-opacity-25 {
    background-color: rgba(13, 202, 240, 0.25) !important;
}

.revenue::before {
    background: linear-gradient(45deg, var(--success-color), #28a745);
}

.occupancy::before {
    background: linear-gradient(45deg, var(--primary-color), #0a58ca);
}

.outstanding::before {
    background: linear-gradient(45deg, var(--warning-color), #e6a800);
}

.expenses::before {
    background: linear-gradient(45deg, var(--danger-color), #dc3545);
}
</style>

<script>
// Initialize charts when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Trend Chart
    const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    new Chart(revenueTrendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($month) {
                return date('M Y', strtotime($month['month']));
            }, $monthlyRevenue)) ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?= json_encode(array_map(function($month) {
                    return $month['total_amount'];
                }, $monthlyRevenue)) ?>,
                borderColor: 'rgb(25, 135, 84)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Ksh' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Occupancy Chart
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Vacant'],
            datasets: [{
                data: [
                    <?= $occupancyStats['occupied_units'] ?>,
                    <?= $occupancyStats['total_units'] - $occupancyStats['occupied_units'] ?>
                ],
                backgroundColor: [
                    'rgb(25, 135, 84)',
                    'rgb(222, 226, 230)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?> 