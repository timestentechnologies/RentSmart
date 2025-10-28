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
                    <p class="text-muted mb-0 mt-1">View and manage subscription payments and transactions</p>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mt-3 mb-2">
                            Ksh<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?>
                        </h2>
                        <p class="mb-0 text-muted">All time revenue</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
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
        <div class="col-12 col-md-4">
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
    </div>

    <!-- Subscription Payments -->
    <div class="card mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0">Subscription Payments</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">DATE</th>
                        <th class="text-muted">USER</th>
                        <th class="text-muted">PLAN</th>
                        <th class="text-muted">AMOUNT</th>
                        <th class="text-muted">METHOD</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">REFERENCE</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
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
                                <span class="badge bg-<?= $payment['payment_method'] === 'mpesa' ? 'success' : 'info' ?>">
                                    <?= ucfirst($payment['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($payment['transaction_reference'] ?? 'N/A') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewPayment(<?= $payment['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MPesa Transactions -->
    <div class="card">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0">MPesa Transactions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">DATE</th>
                        <th class="text-muted">PHONE</th>
                        <th class="text-muted">AMOUNT</th>
                        <th class="text-muted">RECEIPT NO.</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">RESULT</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mpesa_transactions as $transaction): ?>
                        <tr>
                            <td><?= date('M j, Y H:i', strtotime($transaction['created_at'])) ?></td>
                            <td><?= htmlspecialchars($transaction['phone_number']) ?></td>
                            <td>Ksh<?= number_format($transaction['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($transaction['mpesa_receipt_number'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge bg-<?= $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($transaction['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($transaction['result_code'] === '0'): ?>
                                    <span class="text-success">Success</span>
                                <?php elseif ($transaction['result_code']): ?>
                                    <span class="text-danger" title="<?= htmlspecialchars($transaction['result_description']) ?>">
                                        Failed (<?= $transaction['result_code'] ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="text-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction(<?= $transaction['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
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

<!-- View Transaction Modal -->
<div class="modal fade" id="viewTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactionDetails">
                    <!-- Transaction details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

function viewTransaction(transactionId) {
    fetch(`<?= BASE_URL ?>/admin/payments/transaction/${transactionId}`)
        .then(response => response.json())
        .then(transaction => {
            const details = document.getElementById('transactionDetails');
            details.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transaction ID:</strong> ${transaction.id}</p>
                        <p><strong>Phone Number:</strong> ${transaction.phone_number}</p>
                        <p><strong>Amount:</strong> ${transaction.amount}</p>
                        <p><strong>Receipt Number:</strong> ${transaction.mpesa_receipt_number || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> ${transaction.status}</p>
                        <p><strong>Result Code:</strong> ${transaction.result_code || 'N/A'}</p>
                        <p><strong>Result Description:</strong> ${transaction.result_description || 'N/A'}</p>
                        <p><strong>Date:</strong> ${new Date(transaction.created_at).toLocaleString()}</p>
                    </div>
                </div>
                ${transaction.log_data ? `
                    <div class="mt-4">
                        <h6>Transaction Logs</h6>
                        <pre class="bg-light p-3 rounded">${transaction.log_data}</pre>
                    </div>
                ` : ''}
            `;
            
            new bootstrap.Modal(document.getElementById('viewTransactionModal')).show();
        });
}
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?> 