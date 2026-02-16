<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-file-text text-primary me-2"></i>Contracts</h1>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addContractModal">
                <i class="bi bi-plus-circle me-1"></i> Add Contract
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="card-title mb-0">All Contracts</h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="input-group" style="min-width: 260px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="realtorContractsSearch" placeholder="Search contracts...">
                    </div>
                    <select class="form-select" id="realtorContractsFilterStatus" style="min-width: 180px;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select class="form-select" id="realtorContractsFilterTerms" style="min-width: 180px;">
                        <option value="">All Terms</option>
                        <option value="monthly">Monthly</option>
                        <option value="one_time">One Time</option>
                    </select>
                    <select class="form-select" id="realtorContractsFilterPayment" style="min-width: 180px;">
                        <option value="">All Payments</option>
                        <option value="paid">Fully Paid</option>
                        <option value="unpaid">Not Paid</option>
                    </select>
                </div>
            </div>
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
                            <th>Payment</th>
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

                                $cid = (int)($x['id'] ?? 0);
                                $total = (float)($x['total_amount'] ?? 0);
                                $paid = (float)(($paidTotals ?? [])[$cid] ?? 0);
                                $isFullyPaid = ($total > 0 && $paid + 0.00001 >= $total);
                            ?>
                            <tr data-status="<?= htmlspecialchars($status) ?>" data-terms="<?= htmlspecialchars($terms) ?>" data-payment="<?= $isFullyPaid ? 'paid' : 'unpaid' ?>">
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
                                <td>
                                    <?php if ($isFullyPaid): ?>
                                        <span class="badge bg-success">Fully Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Not Paid</span>
                                    <?php endif; ?>
                                    <div class="small text-muted">Paid: Ksh<?= number_format($paid, 2) ?></div>
                                </td>
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

<script>
(function(){
  const table = document.getElementById('realtorContractsTable');
  const q = document.getElementById('realtorContractsSearch');
  const fStatus = document.getElementById('realtorContractsFilterStatus');
  const fTerms = document.getElementById('realtorContractsFilterTerms');
  const fPay = document.getElementById('realtorContractsFilterPayment');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const norm = (v) => (String(v || '')).toLowerCase().trim();

  function apply(){
    const query = norm(q && q.value);
    const status = norm(fStatus && fStatus.value);
    const terms = norm(fTerms && fTerms.value);
    const pay = norm(fPay && fPay.value);

    const rows = tbody.querySelectorAll('tr');
    rows.forEach((tr) => {
      const rowText = norm(tr.innerText);
      const rowStatus = norm(tr.getAttribute('data-status'));
      const rowTerms = norm(tr.getAttribute('data-terms'));
      const rowPay = norm(tr.getAttribute('data-payment'));

      let ok = true;
      if (query && !rowText.includes(query)) ok = false;
      if (status && rowStatus !== status) ok = false;
      if (terms && rowTerms !== terms) ok = false;
      if (pay && rowPay !== pay) ok = false;

      tr.style.display = ok ? '' : 'none';
    });
  }

  [q, fStatus, fTerms, fPay].forEach((el) => {
    if (!el) return;
    el.addEventListener('input', apply);
    el.addEventListener('change', apply);
  });
  apply();
})();
</script>

<div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/realtor/contracts/store">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="realtor_client_id" required>
                                <option value="">Select client</option>
                                <?php foreach (($clients ?? []) as $cl): ?>
                                    <option value="<?= (int)($cl['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($cl['phone'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Listing</label>
                            <select class="form-select" name="realtor_listing_id" required>
                                <option value="">Select listing</option>
                                <?php foreach (($listings ?? []) as $l): ?>
                                    <option value="<?= (int)($l['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($l['title'] ?? '')) ?><?= !empty($l['location']) ? ' - ' . htmlspecialchars((string)$l['location']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Terms</label>
                            <select class="form-select" name="terms_type" id="realtor_terms_type" required>
                                <option value="one_time">One-time</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4" id="realtor_duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" type="number" min="1">
                        </div>
                        <div class="col-md-6" id="realtor_start_wrap" style="display:none;">
                            <label class="form-label">Start Month</label>
                            <input class="form-control" name="start_month" type="month">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  const terms = document.getElementById('realtor_terms_type');
  const durationWrap = document.getElementById('realtor_duration_wrap');
  const startWrap = document.getElementById('realtor_start_wrap');
  function sync(){
    const isMonthly = (terms && terms.value === 'monthly');
    if(durationWrap) durationWrap.style.display = isMonthly ? '' : 'none';
    if(startWrap) startWrap.style.display = isMonthly ? '' : 'none';
  }
  if(terms){
    terms.addEventListener('change', sync);
    sync();
  }
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
