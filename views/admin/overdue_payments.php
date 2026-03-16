<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-exclamation-circle text-warning me-2"></i>Overdue Payments
                    </h1>
                    <p class="text-muted mb-0 mt-1">Users with unpaid subscription months after trial ended</p>
                </div>
                <div>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/admin/payments">
                        <i class="bi bi-arrow-left me-1"></i>Back to Payment History
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-muted">USER</th>
                            <th class="text-muted">PLAN</th>
                            <th class="text-muted">TRIAL ENDED</th>
                            <th class="text-muted">BILLABLE START</th>
                            <th class="text-muted">OVERDUE MONTHS</th>
                            <th class="text-muted">MONTHS COUNT</th>
                            <th class="text-muted">AMOUNT DUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows ?? [])): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No overdue subscription payments found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (($rows ?? []) as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($r['name'] ?? '')) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars((string)($r['email'] ?? '')) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($r['plan'] ?? '')) ?></div>
                                        <div class="small text-muted">Ksh <?= number_format((float)($r['plan_price'] ?? 0), 2) ?>/mo</div>
                                    </td>
                                    <td><?= htmlspecialchars((string)($r['trial_ends_at'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($r['billable_start'] ?? '')) ?></td>
                                    <td style="max-width: 420px;">
                                        <?php
                                        $miss = (array)($r['missing_months'] ?? []);
                                        $labels = [];
                                        foreach ($miss as $ym) {
                                            $ym = (string)$ym;
                                            if ($ym === '') continue;
                                            $dt = \DateTime::createFromFormat('Y-m-d', $ym . '-01');
                                            $labels[] = $dt ? $dt->format('M Y') : $ym;
                                        }
                                        echo htmlspecialchars(implode(', ', $labels));
                                        ?>
                                    </td>
                                    <td><?= (int)($r['missing_count'] ?? 0) ?></td>
                                    <td class="fw-semibold text-danger">Ksh <?= number_format((float)($r['amount_due'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'views/layouts/main.php';
?>
