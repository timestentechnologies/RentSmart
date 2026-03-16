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
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <a class="btn btn-outline-primary w-100" href="<?= BASE_URL ?>/admin/users">
                        <i class="bi bi-people-fill me-2"></i>All Users
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/admin/subscriptions">
                        <i class="bi bi-credit-card-2-front me-2"></i>Subscriptions
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <a class="btn btn-outline-success w-100" href="<?= BASE_URL ?>/admin/payments">
                        <i class="bi bi-cash-coin me-2"></i>Payment History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
