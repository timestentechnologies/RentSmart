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
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editContractTermsModal">
                    <i class="bi bi-pencil me-1"></i>Edit Terms
                </button>
                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/realtor/contracts">Back</a>
            </div>
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

    <div class="modal fade" id="editContractTermsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" action="<?= BASE_URL ?>/realtor/contracts/update/<?= (int)$contractId ?>" id="editContractTermsForm">
            <?= csrf_field() ?>
            <div class="modal-header">
              <h5 class="modal-title">Edit Contract Terms</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Payment Terms</label>
                    <select class="form-select" name="terms_type" id="ect_terms_type" required>
                        <option value="one_time" <?= $termsType === 'one_time' ? 'selected' : '' ?>>One Time</option>
                        <option value="monthly" <?= $termsType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">Ksh</span>
                        <input type="number" step="0.01" min="0" class="form-control" name="total_amount" id="ect_total_amount" value="<?= htmlspecialchars((string)$totalAmount) ?>" required>
                    </div>
                </div>
                <div id="ect_monthly_fields" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label">Start Month</label>
                        <input type="month" class="form-control" name="start_month" id="ect_start_month" value="<?= htmlspecialchars(substr((string)$startMonth, 0, 7)) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (Months)</label>
                        <input type="number" min="1" step="1" class="form-control" name="duration_months" id="ect_duration_months" value="<?= (int)$duration ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
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

<script>
(function(){
    const terms = document.getElementById('ect_terms_type');
    const fields = document.getElementById('ect_monthly_fields');
    const start = document.getElementById('ect_start_month');
    const dur = document.getElementById('ect_duration_months');
    if(!terms || !fields) return;

    const apply = () => {
        const t = terms.value;
        if(t === 'monthly'){
            fields.style.display = '';
            start && start.setAttribute('required','');
            dur && dur.setAttribute('required','');
        } else {
            fields.style.display = 'none';
            start && start.removeAttribute('required');
            dur && dur.removeAttribute('required');
        }
    };
    terms.addEventListener('change', apply);
    apply();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
