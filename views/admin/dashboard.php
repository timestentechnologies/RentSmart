<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-shield-lock-fill text-primary me-2"></i>Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0 mt-1">Manage managers, landlords, realtors, and agents</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Expiring &lt; 30 days</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($counts['expiring_30_days'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Active/trial nearing end</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-hourglass-split fs-1" style="color:#ff6b00; opacity:.25;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Trialing</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($counts['trialing'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Users on trial</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-lightning-charge fs-1" style="color:#6B3E99; opacity:.25;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">New Users (30 days)</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($counts['new_users_30_days'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Recently registered</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-person-plus fs-1" style="color:#1b8f4a; opacity:.25;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Active Subscriptions</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($counts['active_subscriptions'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Latest subscription status</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-shield-check fs-1" style="color:#1b8f4a; opacity:.25;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <a class="text-decoration-none" href="<?= BASE_URL ?>/admin/managers">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title">Managers</h6>
                            <h2 class="mt-3 mb-2"><?= (int)($counts['managers'] ?? 0) ?></h2>
                            <p class="mb-0 text-muted">View all managers</p>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-person-workspace fs-1 text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-3">
            <a class="text-decoration-none" href="<?= BASE_URL ?>/admin/landlords">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title">Landlords</h6>
                            <h2 class="mt-3 mb-2"><?= (int)($counts['landlords'] ?? 0) ?></h2>
                            <p class="mb-0 text-muted">View all landlords</p>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-person-badge fs-1 text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-3">
            <a class="text-decoration-none" href="<?= BASE_URL ?>/admin/realtors">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title">Realtors</h6>
                            <h2 class="mt-3 mb-2"><?= (int)($counts['realtors'] ?? 0) ?></h2>
                            <p class="mb-0 text-muted">View all realtors</p>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-building fs-1 text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-3">
            <a class="text-decoration-none" href="<?= BASE_URL ?>/admin/agents">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title">Agents</h6>
                            <h2 class="mt-3 mb-2"><?= (int)($counts['agents'] ?? 0) ?></h2>
                            <p class="mb-0 text-muted">View all agents</p>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-people fs-1 text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <style>
                .admin-action-btn {
                    border-radius: 14px;
                    padding: 12px 14px;
                    font-weight: 600;
                    border-width: 2px;
                    transition: all .15s ease-in-out;
                }
                .admin-action-btn i { margin-right: .5rem; }

                .btn-brand-purple {
                    border-color: #6B3E99;
                    color: #6B3E99;
                }
                .btn-brand-purple:hover {
                    background: #6B3E99;
                    border-color: #6B3E99;
                    color: #fff;
                }

                .btn-brand-orange {
                    border-color: #ff6b00;
                    color: #ff6b00;
                }
                .btn-brand-orange:hover {
                    background: #ff6b00;
                    border-color: #ff6b00;
                    color: #fff;
                }

                .btn-brand-green {
                    border-color: #1b8f4a;
                    color: #1b8f4a;
                }
                .btn-brand-green:hover {
                    background: #1b8f4a;
                    border-color: #1b8f4a;
                    color: #fff;
                }
            </style>

            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <a class="btn admin-action-btn btn-brand-purple w-100" href="<?= BASE_URL ?>/admin/users">
                        <i class="bi bi-people-fill"></i>All Users
                    </a>
                </div>
                <div class="col-12 col-md-3">
                    <a class="btn admin-action-btn btn-brand-orange w-100" href="<?= BASE_URL ?>/admin/subscriptions">
                        <i class="bi bi-credit-card-2-front"></i>Subscriptions
                    </a>
                </div>
                <div class="col-12 col-md-3">
                    <a class="btn admin-action-btn btn-brand-green w-100" href="<?= BASE_URL ?>/admin/payments">
                        <i class="bi bi-cash-coin"></i>Payment History
                    </a>
                </div>
                <div class="col-12 col-md-3">
                    <a class="btn admin-action-btn btn-brand-purple w-100" href="<?= BASE_URL ?>/payment-methods">
                        <i class="bi bi-credit-card"></i>Payment Methods
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">User Signups (Last 12 months)</div>
                </div>
                <div class="card-body">
                    <canvas id="adminSignupChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Subscription Status</div>
                </div>
                <div class="card-body">
                    <canvas id="adminSubStatusChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Subscription Revenue (Last 12 months)</div>
                </div>
                <div class="card-body">
                    <canvas id="adminSubRevenueChart" height="90"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  try {
    const charts = <?= json_encode($charts ?? [], JSON_UNESCAPED_SLASHES) ?>;

    const signupCtx = document.getElementById('adminSignupChart');
    if (signupCtx && charts.signup_labels && charts.signup_counts) {
      new Chart(signupCtx, {
        type: 'line',
        data: {
          labels: charts.signup_labels,
          datasets: [{
            label: 'Signups',
            data: charts.signup_counts,
            borderColor: '#6B3E99',
            backgroundColor: 'rgba(107,62,153,0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#6B3E99'
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
          }
        }
      });
    }

    const stCtx = document.getElementById('adminSubStatusChart');
    if (stCtx && charts.subscription_status_labels && charts.subscription_status_counts) {
      new Chart(stCtx, {
        type: 'doughnut',
        data: {
          labels: charts.subscription_status_labels,
          datasets: [{
            data: charts.subscription_status_counts,
            backgroundColor: ['#1b8f4a', '#ff6b00', '#6B3E99', '#6c757d', '#0dcaf0', '#dc3545'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' }
          }
        }
      });
    }

    const revCtx = document.getElementById('adminSubRevenueChart');
    if (revCtx && charts.subscription_revenue_labels && charts.subscription_revenue_amounts) {
      new Chart(revCtx, {
        type: 'bar',
        data: {
          labels: charts.subscription_revenue_labels,
          datasets: [{
            label: 'Revenue',
            data: charts.subscription_revenue_amounts,
            backgroundColor: 'rgba(255,107,0,0.18)',
            borderColor: '#ff6b00',
            borderWidth: 1,
            borderRadius: 10
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }
  } catch (e) {}
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
