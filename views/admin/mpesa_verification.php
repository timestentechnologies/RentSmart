<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-phone text-primary me-2"></i>M-Pesa Payment Verification
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-warning fs-6">
                        <i class="bi bi-clock me-1"></i><?= count(($groupedPayments ?? [])) ?> Pending
                    </span>
                    <?php if (!empty($groupedPayments ?? [])): ?>
                        <button type="button" class="btn btn-success btn-sm" onclick="verifyAll('verified')">
                            <i class="bi bi-check2-circle me-1"></i> Verify All
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Pending Payments Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Pending M-Pesa Payments</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">TENANT</th>
                        <th class="text-muted">PROPERTY/UNIT</th>
                        <th class="text-muted">AMOUNT</th>
                        <th class="text-muted">PHONE NUMBER</th>
                        <th class="text-muted">TRANSACTION CODE</th>
                        <th class="text-muted">PAYMENT DATE</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groupedPayments ?? [])): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-check-circle display-4 text-success mb-3 d-block"></i>
                                <h5>No pending M-Pesa payments</h5>
                                <p class="text-muted">All M-Pesa payments have been verified</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (($groupedPayments ?? []) as $g): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($g['tenant_name'] ?? '') ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($g['tenant_email'] ?? '') ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($g['property_name'] ?? 'N/A') ?></strong>
                                        <br>
                                        <small class="text-muted">Unit: <?= htmlspecialchars($g['unit_number'] ?? 'N/A') ?></small>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success">Ksh <?= number_format((float)($g['total_amount'] ?? 0), 2) ?></strong>
                                    <br>
                                    <?php
                                        $types = $g['payment_types'] ?? [];
                                        $typesLabel = !empty($types) ? implode(', ', array_map('ucfirst', $types)) : 'Payment';
                                        $itemsCount = is_array($g['items'] ?? null) ? count($g['items']) : 0;
                                    ?>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($typesLabel) ?>
                                        <?php if ($itemsCount > 1): ?>
                                            <span class="text-muted">(<?= (int)$itemsCount ?> items)</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars((string)($g['phone_number'] ?? '')) ?></span>
                                </td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars((string)($g['transaction_code'] ?? '')) ?></code>
                                </td>
                                <td>
                                    <?= !empty($g['payment_date']) ? date('M j, Y', strtotime((string)$g['payment_date'])) : '' ?>
                                    <br>
                                    <small class="text-muted"><?= !empty($g['created_at']) ? date('g:i A', strtotime((string)$g['created_at'])) : '' ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Pending Verification</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-success" onclick="verifyPaymentGroup('<?= htmlspecialchars((string)($g['transaction_code'] ?? ''), ENT_QUOTES) ?>', 'verified')" title="Verify">
                                            <i class="bi bi-check-circle"></i> Verify
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="verifyPaymentGroup('<?= htmlspecialchars((string)($g['transaction_code'] ?? ''), ENT_QUOTES) ?>', 'rejected')" title="Reject">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewPaymentDetailsByCode('<?= htmlspecialchars((string)($g['transaction_code'] ?? ''), ENT_QUOTES) ?>')" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Verify M-Pesa Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="verificationForm">
                    <input type="hidden" id="payment_id" name="payment_id">
                    <input type="hidden" id="verification_status" name="status">
                    
                    <div class="mb-3">
                        <label class="form-label">Verification Action</label>
                        <div class="alert alert-info" id="verification_action">
                            <!-- Action will be populated here -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Notes (Optional)</label>
                        <textarea id="verification_notes" name="notes" class="form-control" rows="3" placeholder="Add any notes about this verification"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitVerification()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Payment details will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function verifyAll(status) {
    document.getElementById('payment_id').value = 'ALL';
    document.getElementById('verification_status').value = status;
    document.getElementById('verificationForm').dataset.mode = 'all';

    const actionDiv = document.getElementById('verification_action');
    if (status === 'verified') {
        actionDiv.innerHTML = '<i class="bi bi-check2-circle text-success me-2"></i><strong>Verify ALL pending payments</strong> - This will mark all pending M-Pesa codes as completed.';
        actionDiv.className = 'alert alert-success';
    } else {
        actionDiv.innerHTML = '<i class="bi bi-x-circle text-danger me-2"></i><strong>Reject ALL pending payments</strong> - This will mark all pending M-Pesa codes as failed.';
        actionDiv.className = 'alert alert-danger';
    }

    const modal = new bootstrap.Modal(document.getElementById('verificationModal'));
    modal.show();
}

function verifyPaymentGroup(transactionCode, status) {
    document.getElementById('payment_id').value = transactionCode;
    document.getElementById('verification_status').value = status;
    document.getElementById('verificationForm').dataset.mode = 'group';

    const actionDiv = document.getElementById('verification_action');
    if (status === 'verified') {
        actionDiv.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i><strong>Verify this payment</strong> - This will mark ALL items under this M-Pesa code as completed.';
        actionDiv.className = 'alert alert-success';
    } else {
        actionDiv.innerHTML = '<i class="bi bi-x-circle text-danger me-2"></i><strong>Reject this payment</strong> - This will mark ALL items under this M-Pesa code as failed.';
        actionDiv.className = 'alert alert-danger';
    }

    const modal = new bootstrap.Modal(document.getElementById('verificationModal'));
    modal.show();
}

function verifyPayment(paymentId, status) {
    document.getElementById('payment_id').value = paymentId;
    document.getElementById('verification_status').value = status;
    document.getElementById('verificationForm').dataset.mode = 'single';
    
    const actionDiv = document.getElementById('verification_action');
    if (status === 'verified') {
        actionDiv.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i><strong>Verify this payment</strong> - This will mark the payment as completed and update the tenant\'s account.';
        actionDiv.className = 'alert alert-success';
    } else {
        actionDiv.innerHTML = '<i class="bi bi-x-circle text-danger me-2"></i><strong>Reject this payment</strong> - This will mark the payment as failed and notify the tenant.';
        actionDiv.className = 'alert alert-danger';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('verificationModal'));
    modal.show();
}

function submitVerification() {
    const form = document.getElementById('verificationForm');
    const formData = new FormData(form);
    const submitBtn = document.querySelector('#verificationModal .btn-primary');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    submitBtn.disabled = true;
    
    const mode = form.dataset.mode || 'single';
    const idOrCode = formData.get('payment_id');
    const url = (mode === 'all')
        ? ('<?= BASE_URL ?>/mpesa-verification/verify-all')
        : ((mode === 'group')
            ? ('<?= BASE_URL ?>/mpesa-verification/verify-group/' + encodeURIComponent(idOrCode))
            : ('<?= BASE_URL ?>/mpesa-verification/verify/' + idOrCode));

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('verificationModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        alert('Error verifying payment');
    });
}

function viewPaymentDetailsByCode(transactionCode) {
    const code = String(transactionCode || '');
    document.getElementById('paymentDetailsContent').innerHTML = `
        <div>
            <div class="mb-2"><strong>M-Pesa Code:</strong> <code>${code}</code></div>
            <div class="text-muted small">Approval is done as one transaction. Payments page will still show separate rent/utility/maintenance rows.</div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
