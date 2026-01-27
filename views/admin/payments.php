<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-receipt text-primary me-2"></i>Payment History
                    </h1>
                    <p class="text-muted mb-0 mt-1">View and manage subscription payments</p>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mt-3 mb-2">
                            Ksh<?= number_format(array_sum(array_map(function($p){ return ($p['status'] === 'completed') ? (float)$p['amount'] : 0; }, $payments)), 2) ?>
                        </h2>
                        <p class="mb-0 text-muted">All time revenue</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Successful Payments</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($payments, function($payment) {
                                return $payment['status'] === 'completed';
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Completed transactions</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-check-circle fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Recent Payments</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($payments, function($payment) {
                                return strtotime($payment['created_at']) > strtotime('-24 hours');
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Last 24 hours</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-clock-history fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Expected Revenue</h6>
                        <h2 class="mt-3 mb-2">
                            Ksh<?= number_format($expected_revenue ?? 0, 2) ?>
                        </h2>
                        <p class="mb-0 text-muted">Based on active subscriptions</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-graph-up-arrow fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Payments -->
    <div class="card mb-4">
        <div class="card-header bg-light py-3">
            <div class="d-flex flex-column flex-md-row flex-md-nowrap align-items-stretch align-items-md-center justify-content-between gap-2">
                <h5 class="mb-0">Subscription Payments</h5>
                <div class="d-flex flex-nowrap gap-2">
                    <div class="input-group" style="max-width: 260px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="sp-search" class="form-control" placeholder="Search user, plan, ref, phone...">
                    </div>
                    <select id="sp-status-filter" class="form-select" style="max-width: 180px;">
                        <option value="">All Statuses</option>
                        <option value="Completed">Completed</option>
                        <option value="Pending">Pending</option>
                        <option value="Failed">Failed</option>
                    </select>
                    <select id="sp-method-filter" class="form-select" style="max-width: 200px;">
                        <option value="">All Methods</option>
                        <option value="Mpesa STK">Mpesa STK</option>
                        <option value="Mpesa Paybill">Mpesa Paybill (Manual)</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="subscriptionPaymentsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">DATE</th>
                        <th class="text-muted">USER</th>
                        <th class="text-muted">PLAN</th>
                        <th class="text-muted">AMOUNT</th>
                        <th class="text-muted">METHOD</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">PHONE</th>
                        <th class="text-muted">REFERENCE</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <?php
                            $isManual = !empty($payment['manual_mpesa_id']);
                            $isStk = !$isManual && (!empty($payment['mpesa_receipt_number']) || !empty($payment['phone_number']));
                            $methodLabel = ($payment['payment_method'] === 'mpesa')
                                ? ($isManual ? 'Mpesa Paybill' : ($isStk ? 'Mpesa STK' : 'Mpesa'))
                                : ucfirst($payment['payment_method'] ?? 'Other');
                            $phone = $isManual ? ($payment['manual_phone_number'] ?? '') : ($payment['phone_number'] ?? '');
                            $reference = $payment['transaction_reference'] ?? '';
                            if (!$reference) { $reference = $isManual ? ($payment['manual_transaction_code'] ?? '') : ($payment['mpesa_receipt_number'] ?? ''); }
                            if (!$reference) { $reference = 'N/A'; }
                        ?>
                        <tr>
                            <td><?= date('M j, Y H:i', strtotime($payment['created_at'])) ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <i class="bi bi-person-circle fs-4"></i>
                                    </div>
                                    <?= htmlspecialchars($payment['user_name']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($payment['plan_type']) ?></td>
                            <td>Ksh<?= number_format($payment['amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= (stripos($methodLabel, 'Mpesa') === 0) ? 'success' : 'info' ?>">
                                    <?= htmlspecialchars($methodLabel) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($phone ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($reference) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewPayment(<?= $payment['id'] ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (($payment['status'] ?? '') === 'pending' && !empty($payment['manual_mpesa_id']) && (($payment['manual_verification_status'] ?? '') === 'pending')): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="verifyManualMpesa(<?= (int)$payment['manual_mpesa_id'] ?>, 'approve')" title="Approve">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="verifyManualMpesa(<?= (int)$payment['manual_mpesa_id'] ?>, 'reject')" title="Reject">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="paymentDetails">
                    <!-- Payment details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Verify Manual MPesa Modal -->
<div class="modal fade" id="verifyManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verifyManualTitle">Verify Manual M-Pesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="verifyManualMessage" class="mb-3">Are you sure you want to proceed?</p>
                <div id="verifyNotesGroup" style="display:none;">
                    <label for="verifyNotes" class="form-label">Notes (optional)</label>
                    <textarea id="verifyNotes" class="form-control" rows="3" placeholder="Add a reason (only for rejection)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="verifyManualConfirmBtn" data-id="" data-action="">Confirm</button>
            </div>
        </div>
    </div>
    </div>

<script>
function viewPayment(paymentId) {
    fetch(`<?= BASE_URL ?>/admin/payments/get/${paymentId}`)
        .then(response => response.json())
        .then(payment => {
            const details = document.getElementById('paymentDetails');
            details.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Payment ID:</strong> ${payment.id}</p>
                        <p><strong>User:</strong> ${payment.user_name}</p>
                        <p><strong>Plan:</strong> ${payment.plan_type}</p>
                        <p><strong>Amount:</strong> ${payment.amount}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Method:</strong> ${payment.payment_method}</p>
                        <p><strong>Status:</strong> ${payment.status}</p>
                        <p><strong>Reference:</strong> ${payment.transaction_reference || 'N/A'}</p>
                        <p><strong>Date:</strong> ${new Date(payment.created_at).toLocaleString()}</p>
                    </div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('viewPaymentModal')).show();
        });
}

 

// Modal-based verification flow
function verifyManualMpesa(manualMpesaId, action) {
    const title = action === 'approve' ? 'Approve Manual M-Pesa Payment' : 'Reject Manual M-Pesa Payment';
    const msg = action === 'approve'
        ? 'Are you sure you want to approve this manual M-Pesa payment?'
        : 'Are you sure you want to reject this manual M-Pesa payment?';

    document.getElementById('verifyManualTitle').textContent = title;
    document.getElementById('verifyManualMessage').textContent = msg;

    // Show notes only on rejection
    const notesGroup = document.getElementById('verifyNotesGroup');
    const notesEl = document.getElementById('verifyNotes');
    notesEl.value = '';
    notesGroup.style.display = action === 'reject' ? 'block' : 'none';

    const confirmBtn = document.getElementById('verifyManualConfirmBtn');
    confirmBtn.dataset.id = String(manualMpesaId);
    confirmBtn.dataset.action = action;

    const modal = new bootstrap.Modal(document.getElementById('verifyManualModal'));
    modal.show();
}

document.getElementById('verifyManualConfirmBtn').addEventListener('click', async function() {
    const manualMpesaId = this.dataset.id;
    const action = this.dataset.action;
    const notes = action === 'reject' ? (document.getElementById('verifyNotes').value || '') : '';

    // Disable while processing
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Processing...';
    try {
        const resp = await fetch(`<?= BASE_URL ?>/admin/payments/manual-mpesa/${manualMpesaId}/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('input[name="csrf_token"]') || {}).value || ''
            },
            body: JSON.stringify({ action, notes })
        });
        const raw = await resp.text();
        let data; try { data = JSON.parse(raw); } catch { data = { success: false, message: raw }; }
        if (data.success) {
            // Close modal then reload
            bootstrap.Modal.getInstance(document.getElementById('verifyManualModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch (e) {
        alert('Request failed');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirm';
    }
});

// Client-side filtering for Subscription Payments
(function() {
    const table = document.getElementById('subscriptionPaymentsTable');
    if (!table) return;
    const searchEl = document.getElementById('sp-search');
    const statusEl = document.getElementById('sp-status-filter');
    const methodEl = document.getElementById('sp-method-filter');

    function normalize(s){ return (s||'').toString().toLowerCase(); }

    function filterRows() {
        const q = normalize(searchEl && searchEl.value);
        const status = normalize(statusEl && statusEl.value);
        const method = normalize(methodEl && methodEl.value);
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.children;
            const user = normalize(cells[1]?.innerText);
            const plan = normalize(cells[2]?.innerText);
            const methodText = normalize(cells[4]?.innerText);
            const statusText = normalize(cells[5]?.innerText);
            const phoneText = normalize(cells[6]?.innerText);
            const refText = normalize(cells[7]?.innerText);

            const matchesSearch = !q || user.includes(q) || plan.includes(q) || methodText.includes(q) || phoneText.includes(q) || refText.includes(q);
            const matchesStatus = !status || statusText === status;
            const matchesMethod = !method || methodText.includes(method);

            row.style.display = (matchesSearch && matchesStatus && matchesMethod) ? '' : 'none';
        });
    }

    [searchEl, statusEl, methodEl].forEach(el => { if (el){ el.addEventListener('input', filterRows); el.addEventListener('change', filterRows); } });
})();
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?> 