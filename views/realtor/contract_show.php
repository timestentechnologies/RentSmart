<?php
ob_start();

$contractId = (int)($contract['id'] ?? 0);
$termsType = (string)($contract['terms_type'] ?? 'one_time');
$totalAmount = (float)($contract['total_amount'] ?? 0);
$duration = (int)($contract['duration_months'] ?? 0);
$monthly = (float)($contract['monthly_amount'] ?? 0);
$startMonth = (string)($contract['start_month'] ?? '');

$paidTotal = 0.0;
$paymentsByMonth = [];
foreach (($payments ?? []) as $p) {
    $st = strtolower((string)($p['status'] ?? 'completed'));
    if (!in_array($st, ['completed', 'verified'], true)) {
        continue;
    }
    $amt = (float)($p['amount'] ?? 0);
    if ($amt <= 0) continue;
    $paidTotal += $amt;

    $m = '';
    if (!empty($p['applies_to_month'])) {
        $m = substr((string)$p['applies_to_month'], 0, 7);
    }
    if ($m !== '') {
        $paymentsByMonth[$m] = ($paymentsByMonth[$m] ?? 0) + $amt;
    }
}

$remaining = max(0.0, $totalAmount - $paidTotal);

function monthAddPhp(string $ym, int $n): string {
    if ($ym === '' || strpos($ym, '-') === false) return '';
    $parts = explode('-', $ym);
    if (count($parts) < 2) return '';
    $y = (int)$parts[0];
    $m = (int)$parts[1];
    if ($y <= 0 || $m <= 0) return '';
    $dt = new \DateTime(sprintf('%04d-%02d-01', $y, $m));
    $dt->modify(($n >= 0 ? '+' : '') . $n . ' month');
    return $dt->format('Y-m');
}
?>

<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-file-text text-primary me-2"></i>Contract #<?= $contractId ?></h1>
                <div class="text-muted">
                    Client: <span class="fw-semibold"><?= htmlspecialchars((string)($client['name'] ?? '')) ?></span>
                    &mdash; Listing: <span class="fw-semibold"><?= htmlspecialchars((string)($listing['title'] ?? '')) ?></span>
                </div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/realtor/contracts">Back</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Total</div>
                    <div class="h4 mb-0">Ksh<?= number_format($totalAmount, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Paid</div>
                    <div class="h4 mb-0 text-success">Ksh<?= number_format($paidTotal, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Remaining</div>
                    <div class="h4 mb-0 text-danger">Ksh<?= number_format($remaining, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Terms</h5>
        </div>
        <div class="card-body">
            <?php if ($termsType === 'monthly'): ?>
                <div class="mb-2"><strong>Type:</strong> Monthly</div>
                <div class="mb-2"><strong>Start Month:</strong> <?= htmlspecialchars(substr($startMonth, 0, 7)) ?></div>
                <div class="mb-2"><strong>Duration:</strong> <?= (int)$duration ?> months</div>
                <div class="mb-2"><strong>Monthly Amount:</strong> Ksh<?= number_format($monthly, 2) ?></div>
            <?php else: ?>
                <div class="mb-2"><strong>Type:</strong> One Time</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($termsType === 'monthly'): ?>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Monthly Breakdown</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Expected</th>
                                <th>Paid</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < max(0, $duration); $i++): ?>
                                <?php
                                    $ym = monthAddPhp(substr($startMonth, 0, 7), $i);
                                    $paid = (float)($paymentsByMonth[$ym] ?? 0);
                                    $bal = max(0.0, $monthly - $paid);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($ym) ?></td>
                                    <td>Ksh<?= number_format($monthly, 2) ?></td>
                                    <td class="text-success">Ksh<?= number_format($paid, 2) ?></td>
                                    <td class="text-danger">Ksh<?= number_format($bal, 2) ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Payments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>For Month</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($payments ?? []) as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($p['payment_date'] ?? '')) ?></td>
                                <td>Ksh<?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars(substr((string)($p['applies_to_month'] ?? ''), 0, 7)) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_',' ', (string)($p['payment_method'] ?? '')))) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_',' ', (string)($p['status'] ?? '')))) ?></td>
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
