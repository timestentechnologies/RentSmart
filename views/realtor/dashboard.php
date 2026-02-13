<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-speedometer2 text-primary me-2"></i>Realtor Dashboard</h1>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card occupancy">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Listings</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($stats['listings_total'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">All listings</p>
                    </div>
                    <div class="stats-icon"><i class="bi bi-building fs-1 text-primary opacity-25"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card revenue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Active Listings</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($stats['listings_active'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Currently available</p>
                    </div>
                    <div class="stats-icon"><i class="bi bi-check2-circle fs-1 text-success opacity-25"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card occupancy">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Clients</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($stats['clients_total'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Saved clients</p>
                    </div>
                    <div class="stats-icon"><i class="bi bi-people fs-1 text-primary opacity-25"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card outstanding">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">New Leads</h6>
                        <h2 class="mt-3 mb-2"><?= (int)($stats['leads_new'] ?? 0) ?></h2>
                        <p class="mb-0 text-muted">Leads not yet contacted</p>
                    </div>
                    <div class="stats-icon"><i class="bi bi-person-plus fs-1 text-warning opacity-25"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Leads</h5>
                    <a href="<?= BASE_URL ?>/realtor/leads" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($recentLeads ?? []) as $l): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($l['name'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($l['phone'] ?? '')) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($l['status'] ?? 'new')) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentLeads)): ?>
                                    <tr><td colspan="3" class="text-muted small">No leads yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Listings</h5>
                    <a href="<?= BASE_URL ?>/realtor/listings" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($recentListings ?? []) as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($r['title'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['listing_type'] ?? '')) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($r['status'] ?? 'active')) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentListings)): ?>
                                    <tr><td colspan="3" class="text-muted small">No listings yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
