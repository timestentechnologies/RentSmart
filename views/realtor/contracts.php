<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-file-text text-primary me-2"></i>Contracts</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">All Contracts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="realtorContractsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Listing</th>
                            <th>Terms</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($contracts ?? []) as $x): ?>
                            <?php
                                $terms = (string)($x['terms_type'] ?? 'one_time');
                                $status = (string)($x['status'] ?? 'active');
                                $statusBadge = 'secondary';
                                if ($status === 'active') { $statusBadge = 'success'; }
                                elseif ($status === 'completed') { $statusBadge = 'primary'; }
                                elseif ($status === 'cancelled') { $statusBadge = 'danger'; }
                            ?>
                            <tr>
                                <td>#<?= (int)($x['id'] ?? 0) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($x['client_name'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($x['client_phone'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($x['listing_title'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($x['listing_location'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <?php if ($terms === 'monthly'): ?>
                                        <span class="badge bg-info">Monthly</span>
                                        <div class="small text-muted">
                                            <?= (int)($x['duration_months'] ?? 0) ?> months @ Ksh<?= number_format((float)($x['monthly_amount'] ?? 0), 2) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">One Time</span>
                                    <?php endif; ?>
                                </td>
                                <td>Ksh<?= number_format((float)($x['total_amount'] ?? 0), 2) ?></td>
                                <td><span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ', $status))) ?></span></td>
                                <td><?= htmlspecialchars((string)($x['created_at'] ?? '')) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/realtor/contracts/show/<?= (int)($x['id'] ?? 0) ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
