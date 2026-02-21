<?php
ob_start();
$isRealtor = strtolower((string)($_SESSION['user_role'] ?? '')) === 'realtor';
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-cash-coin text-primary me-2"></i>Payments Management
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/payments/template">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/payments/export/csv">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/payments/export/xlsx">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/payments/export/pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <form action="<?= BASE_URL ?>/payments/import" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </form>
                    <div class="vr d-none d-md-block"></div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4 mt-4">
        <div class="col-12 col-md-4">
            <div class="stat-card payment-total">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Payments</h6>
                        <h2 class="mt-3 mb-2">
                            Ksh<?= number_format(array_sum(array_map(function($p){
                                return in_array($p['status'] ?? '', ['completed','verified']) ? (float)($p['amount'] ?? 0) : 0;
                            }, $payments)), 2) ?>
                        </h2>
                        <p class="mb-0 text-muted">All time payments</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card payment-month">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">This Month</h6>
                        <h2 class="mt-3 mb-2">
                            Ksh<?= number_format(array_sum(array_map(function($payment) {
                                return (
                                    date('Y-m', strtotime($payment['payment_date'])) === date('Y-m')
                                    && in_array($payment['status'] ?? '', ['completed','verified'])
                                ) ? (float)($payment['amount'] ?? 0) : 0;
                            }, $payments)), 2) ?>
                        </h2>
                        <p class="mb-0 text-muted">Current month payments</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-calendar-check fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card payment-pending">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Pending Payments</h6>
                        <h2 class="mt-3 mb-2">
                            <?= isset(
                                $pendingPaymentsCount) ? $pendingPaymentsCount : 0 ?>
                        </h2>
                        <p class="mb-0 text-muted">Awaiting payment</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-hourglass-split fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Date Range</label>
                    <select class="form-select" id="dateFilter">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="propertyFilter" class="form-label">Property</label>
                    <select class="form-select" id="propertyFilter">
                        <option value="">All Properties</option>
                        <?php 
                        $propertyNames = array_values(array_unique(array_filter(array_map(function($p){ return $p['property_name'] ?? ''; }, $payments))));
                        foreach ($propertyNames as $pname): ?>
                            <option value="<?= htmlspecialchars($pname) ?>"><?= htmlspecialchars($pname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$isRealtor): ?>
                    <div class="col-md-3">
                        <label for="methodFilter" class="form-label">Payment Method</label>
                        <select class="form-select" id="methodFilter">
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label for="amountFilter" class="form-label">Amount Range</label>
                    <select class="form-select" id="amountFilter">
                        <option value="">All Amounts</option>
                        <option value="0-1000">Ksh0 - Ksh1,000</option>
                        <option value="1000-2000">Ksh1,000 - Ksh2,000</option>
                        <option value="2000+">Ksh2,000+</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="reset" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-2"></i>Clear Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="paymentsTable">
                    <thead>
                        <tr>
                            <?php if ($isRealtor): ?>
                                <th>Client</th>
                                <th>Listing</th>
                            <?php else: ?>
                                <th>Tenant</th>
                            <?php endif; ?>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Type</th>
                            <?php if (!$isRealtor): ?>
                                <th>Method</th>
                            <?php endif; ?>
                            <th>M-Pesa Code</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <?php if (!$isRealtor): ?>
                                <th>Property</th>
                            <?php endif; ?>
                            <th>Receipt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr data-payment-id="<?= $payment['id'] ?>">
                                    <?php if ($isRealtor): ?>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?= strtoupper(substr($payment['client_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <?= htmlspecialchars($payment['client_name'] ?? 'Unknown') ?>
                                                    <div class="small text-muted">ID: <?= (int)($payment['client_id'] ?? 0) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($payment['listing_title'] ?? '') ?></td>
                                    <?php else: ?>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?= strtoupper(substr($payment['tenant_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <?= htmlspecialchars($payment['tenant_name'] ?? 'Unknown') ?>
                                                    <div class="small text-muted">ID: <?= $payment['tenant_id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="fw-medium text-success">
                                            Ksh<?= number_format($payment['amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar text-muted me-2"></i>
                                            <?= date('M d, Y', strtotime($payment['payment_date'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        if ($isRealtor) {
                                            $terms = strtolower((string)($payment['contract_terms_type'] ?? ''));
                                            if ($terms === 'monthly') {
                                                $typeClass = 'bg-info';
                                                $typeText = 'Monthly Contract';
                                            } else {
                                                $typeClass = 'bg-primary';
                                                $typeText = 'One-time Contract';
                                            }
                                        } else {
                                            $rawType = strtolower((string)($payment['payment_type'] ?? 'rent'));
                                            $notes = (string)($payment['notes'] ?? '');
                                            $amount = (float)($payment['amount'] ?? 0);
                                            $hasUtility = !empty($payment['utility_id']) || !empty($payment['utility_type']);
                                            $isMaint = ($notes !== '' && (
                                                $rawType === 'other'
                                                || stripos($notes, 'maintenance payment:') !== false
                                                || preg_match('/MAINT-\d+/i', $notes)
                                            ));
                                            $isUtilByNotes = ($notes !== '' && preg_match('/\b(util|utility|water|electricity|gas|internet)\b/i', $notes));

                                            if ($isMaint) {
                                                $typeClass = 'bg-warning text-dark';
                                                $typeText = 'Maintenance';
                                            } elseif ($rawType === 'utility' || $hasUtility || $isUtilByNotes) {
                                                $typeClass = 'bg-info';
                                                $typeText = !empty($payment['utility_type']) ? ucfirst((string)$payment['utility_type']) : 'Utility';
                                            } else {
                                                $typeClass = 'bg-success';
                                                $typeText = 'Rent';
                                            }
                                        }
                                        ?>
                                        <span class="badge <?= $typeClass ?>">
                                            <?= $typeText ?>
                                        </span>
                                    </td>
                                    <?php if (!$isRealtor): ?>
                                        <td>
                                            <?php
                                            $methodClasses = [
                                                'cash' => 'bg-success',
                                                'check' => 'bg-warning text-dark',
                                                'bank_transfer' => 'bg-info',
                                                'card' => 'bg-secondary',
                                                'credit_card' => 'bg-secondary',
                                                'mpesa_manual' => 'bg-primary',
                                                'mpesa_stk' => 'bg-info'
                                            ];
                                            $paymentMethod = (string)($payment['payment_method'] ?? '');
                                            $methodClass = $methodClasses[$paymentMethod] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $methodClass ?>">
                                                <?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $paymentMethod))) ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if (!empty($payment['transaction_code'] ?? null)): ?>
                                            <code class="text-primary"><?= htmlspecialchars((string)($payment['transaction_code'] ?? '')) ?></code>
                                        <?php elseif (!empty($payment['reference_number'] ?? null)): ?>
                                            <code class="text-primary"><?= htmlspecialchars((string)($payment['reference_number'] ?? '')) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($payment['phone_number'] ?? null)): ?>
                                            <span class="text-dark"><?= htmlspecialchars((string)($payment['phone_number'] ?? '')) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = (string)($payment['status'] ?? 'completed');
                                        $statusClasses = [
                                            'completed' => 'bg-success',
                                            'pending' => 'bg-warning',
                                            'failed' => 'bg-danger',
                                            'pending_verification' => 'bg-warning'
                                        ];
                                        $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                                        $statusText = ucwords(str_replace('_', ' ', $status));
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($payment['notes'] ?? null)): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                  data-bs-toggle="tooltip" 
                                                  title="<?= htmlspecialchars((string)($payment['notes'] ?? '')) ?>">
                                                <?= htmlspecialchars((string)($payment['notes'] ?? '')) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (!$isRealtor): ?>
                                        <td>
                                            <?= htmlspecialchars($payment['property_name'] ?? 'N/A') ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if (!empty($payment['receipt_path'] ?? null)): ?>
                                            <a href="<?= BASE_URL ?>/public/<?= (string)($payment['receipt_path'] ?? '') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-file-earmark"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="viewPayment(<?= $payment['id'] ?>)" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editPayment(<?= $payment['id'] ?>)" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePayment(<?= $payment['id'] ?>)" title="Delete">
                                                <i class="bi bi-trash"></i>
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
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/payments/store" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($isRealtor): ?>
                        <div class="mb-3">
                            <label class="form-label">Contract</label>
                            <select class="form-select" name="realtor_contract_id" id="realtor_contract_id" required>
                                <option value="">Select Contract</option>
                                <?php foreach (($contracts ?? []) as $ct): ?>
                                    <?php
                                        $ctId = (int)($ct['id'] ?? 0);
                                        $ctTerms = (string)($ct['terms_type'] ?? 'one_time');
                                        $ctClient = (string)($ct['client_name'] ?? '');
                                        $ctListing = (string)($ct['listing_title'] ?? '');
                                        $ctClientId = (int)($ct['realtor_client_id'] ?? 0);
                                        $ctListingId = (int)($ct['realtor_listing_id'] ?? 0);
                                        $ctTotalAmount = (float)($ct['total_amount'] ?? 0);
                                        $ctMonthlyAmount = (float)($ct['monthly_amount'] ?? 0);
                                        $ctStart = substr((string)($ct['start_month'] ?? ''), 0, 7);
                                        $ctDur = (int)($ct['duration_months'] ?? 0);
                                    ?>
                                    <option
                                        value="<?= $ctId ?>"
                                        data-client-id="<?= (int)$ctClientId ?>"
                                        data-listing-id="<?= (int)$ctListingId ?>"
                                        data-terms="<?= htmlspecialchars($ctTerms, ENT_QUOTES) ?>"
                                        data-start-month="<?= htmlspecialchars($ctStart, ENT_QUOTES) ?>"
                                        data-duration="<?= (int)$ctDur ?>"
                                        data-total-amount="<?= htmlspecialchars((string)$ctTotalAmount, ENT_QUOTES) ?>"
                                        data-monthly-amount="<?= htmlspecialchars((string)$ctMonthlyAmount, ENT_QUOTES) ?>"
                                    >
                                        #<?= $ctId ?> - <?= htmlspecialchars($ctClient) ?> / <?= htmlspecialchars($ctListing) ?> (<?= htmlspecialchars($ctTerms) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Client/listing and payment type will follow the contract terms.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="realtor_client_id" id="realtor_client_id" required>
                                <option value="">Select Client</option>
                                <?php foreach (($clients ?? []) as $c): ?>
                                    <option value="<?= (int)($c['id'] ?? 0) ?>"><?= htmlspecialchars($c['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Listing</label>
                            <select class="form-select" name="realtor_listing_id" id="realtor_listing_id" required>
                                <option value="">Select Listing</option>
                                <?php foreach (($listings ?? []) as $l): ?>
                                    <option value="<?= (int)($l['id'] ?? 0) ?>"><?= htmlspecialchars($l['title'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" step="0.01" class="form-control" name="amount" id="realtor_amount" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Type</label>
                            <input type="hidden" name="payment_type" id="realtor_payment_type" value="mortgage">
                            <input type="text" class="form-control" id="realtor_payment_type_label" value="One Time" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment For Month</label>
                            <input type="month" class="form-control" name="applies_to_month" id="realtor_applies_to_month" value="<?= date('Y-m') ?>">
                            <div class="form-text" id="realtor_month_hint" style="display:none;">Required for monthly contracts (mortgage monthly).</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="tenant_id" class="form-label">Tenant</label>
                            <select class="form-select" id="tenant_id" name="tenant_id" required>
                                <option value="">Select Tenant</option>
                                <?php foreach ($tenants as $tenant): ?>
                                    <?php
                                    $utilityData = [];
                                    if (!empty($tenant['utility_readings'])) {
                                        foreach ($tenant['utility_readings'] as $reading) {
                                            $utilityData[] = [
                                                'id' => $reading['utility_id'],
                                                'type' => $reading['utility_type'],
                                                'label' => !empty($reading['utility_type']) ? ucfirst((string)$reading['utility_type']) : ('Utility #' . (int)$reading['utility_id']),
                                                'current_reading' => $reading['current_reading'],
                                                'current_reading_date' => $reading['current_reading_date'],
                                                'previous_reading' => $reading['previous_reading'],
                                                'previous_reading_date' => $reading['previous_reading_date'],
                                                'cost' => $reading['cost'],
                                                'rate' => $reading['rate'],
                                                'is_metered' => $reading['is_metered']
                                            ];
                                        }
                                    }
                                    ?>
                                    <option value="<?= $tenant['id'] ?>" 
                                            data-due="<?= htmlspecialchars($tenant['due_amount'] ?? 0) ?>"
                                            data-paid-months='<?= htmlspecialchars(json_encode(array_values(array_unique($tenant['paid_rent_months'] ?? []))), ENT_QUOTES, 'UTF-8') ?>'
                                            data-utilities='<?= htmlspecialchars(json_encode($utilityData, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP), ENT_NOQUOTES, 'UTF-8') ?>'>
                                        <?= htmlspecialchars($tenant['name']) ?> 
                                        (Lease #<?= $tenant['lease_id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isRealtor): ?>
                        <div class="mb-3">
                            <label class="form-label">Payment Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rent_payment" name="payment_types[]" value="rent" checked>
                                <label class="form-check-label" for="rent_payment">
                                    Rent Payment
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="utility_payment" name="payment_types[]" value="utility">
                                <label class="form-check-label" for="utility_payment">
                                    Utility Payment
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="maintenance_payment" name="payment_types[]" value="maintenance">
                                <label class="form-check-label" for="maintenance_payment">
                                    Maintenance Payment
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isRealtor): ?>
                    <div id="rent_payment_section" class="mb-3">
                        <label for="rent_amount" class="form-label">Rent Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" step="0.01" class="form-control" id="rent_amount" name="rent_amount">
                        </div>
                    </div>

                    <div id="utility_payment_section" class="mb-3" style="display: none;">
                        <label for="utility_id" class="form-label">Utility</label>
                        <select class="form-select" id="utility_id" name="utility_ids[]" multiple>
                            <option value="">Select Utility</option>
                        </select>
                        <div class="mt-2" id="selected_utilities" style="display:none;"></div>
                        <div class="mt-2" id="utility_details" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="small">
                                        <div id="meter_reading_rows">
                                            <div><strong>Previous Reading:</strong> <span id="previous_reading">0</span> (<span id="previous_reading_date"></span>)</div>
                                            <div><strong>Current Reading:</strong> <span id="current_reading">0</span> (<span id="current_reading_date"></span>)</div>
                                        </div>
                                        <div id="rate_row"><strong>Rate:</strong> Ksh<span id="rate">0</span></div>
                                        <div><strong>Amount Due:</strong> Ksh<span id="amount_due">0</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label for="utility_amount" class="form-label">Amount to Pay</label>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" step="0.01" class="form-control" id="utility_amount" name="utility_amount" readonly>
                            </div>
                        </div>
                    </div>

                    <div id="maintenance_payment_section" class="mb-3" style="display: none;">
                        <label for="maintenance_amount" class="form-label">Maintenance Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" step="0.01" class="form-control" id="maintenance_amount" name="maintenance_amount">
                        </div>
                        <div class="form-text">Use this when a tenant is paying a maintenance charge (e.g. MAINT-123).</div>
                    </div>

                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <?php if (!$isRealtor): ?>
                        <div class="mb-3">
                            <label for="applies_to_month" class="form-label">Payment For Month</label>
                            <input type="month" class="form-control" id="applies_to_month" name="applies_to_month" value="<?= date('Y-m') ?>">
                            <div id="adminPaidMonthWarning" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required onchange="togglePaymentMethodFields()">
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="mpesa_manual">M-Pesa (Manual)</option>
                            <option value="mpesa_stk">M-Pesa (STK Push)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reference_number" class="form-label"><?= $isRealtor ? 'M-Pesa Reference' : 'Reference Number' ?></label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="<?= $isRealtor ? 'e.g., QWN213948J' : 'Optional reference number' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Payment Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="completed">Completed (Verified)</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="pending_verification">Pending Verification</option>
                        </select>
                    </div>
                    
                    <!-- M-Pesa Manual Fields -->
                    <div id="mpesa_manual_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="mpesa_phone" class="form-label">Phone Number</label>
                            <input type="tel" id="mpesa_phone" name="mpesa_phone" class="form-control" placeholder="07XXXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label for="mpesa_transaction_code" class="form-label">Transaction Code</label>
                            <input type="text" id="mpesa_transaction_code" name="mpesa_transaction_code" class="form-control" placeholder="e.g., QWN213948J">
                        </div>
                        <?php if (!$isRealtor): ?>
                            <div class="mb-3">
                                <label for="mpesa_verification_status" class="form-label">Verification Status</label>
                                <select id="mpesa_verification_status" name="mpesa_verification_status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <!-- Payment Attachments -->
                    <div class="mb-3">
                        <label for="payment_attachments" class="form-label">Payment Attachments</label>
                        <input type="file" class="form-control" id="payment_attachments" name="payment_attachments[]" 
                               multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf,.zip,.rar,.7z,.json,.xml,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff" 
                               onchange="previewAttachments(this, 'attachment-preview')">
                        <div class="form-text">Upload receipts, invoices, images, documents, or any supporting files (Max 10MB each)</div>
                        <div id="attachment-preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addPaymentSubmitBtn">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPaymentForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_payment_id" name="payment_id">
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="edit_payment_date" name="payment_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="edit_payment_method" name="payment_method" required onchange="toggleEditMpesaFields()">
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="mpesa_manual">M-Pesa (Manual)</option>
                            <option value="mpesa_stk">M-Pesa (STK Push)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="edit_reference_number" name="reference_number" placeholder="Optional reference number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="completed">Completed (Verified)</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="pending_verification">Pending Verification</option>
                        </select>
                    </div>
                    
                    <!-- Edit M-Pesa Manual Fields -->
                    <div id="edit_mpesa_manual_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_mpesa_phone" class="form-label">Phone Number</label>
                            <input type="tel" id="edit_mpesa_phone" name="mpesa_phone" class="form-control" placeholder="07XXXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label for="edit_mpesa_transaction_code" class="form-label">Transaction Code</label>
                            <input type="text" id="edit_mpesa_transaction_code" name="mpesa_transaction_code" class="form-control" placeholder="e.g., QWN213948J">
                        </div>
                        <div class="mb-3">
                            <label for="edit_mpesa_verification_status" class="form-label">Verification Status</label>
                            <select id="edit_mpesa_verification_status" name="mpesa_verification_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="verified">Verified</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>

                    <!-- Existing Attachments Display -->
                    <div id="edit-existing-attachments-section" class="mb-4">
                        <h6>Current Attachments</h6>
                        <div id="edit-existing-attachments" class="mb-3">
                            <!-- Existing attachments will be loaded here -->
                        </div>
                    </div>

                    <!-- New Attachments Upload -->
                    <div class="mb-3">
                        <label for="edit_payment_attachments" class="form-label">Add New Attachments</label>
                        <input type="file" class="form-control" id="edit_payment_attachments" name="payment_attachments[]" 
                               multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf,.zip,.rar,.7z,.json,.xml,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff" 
                               onchange="previewAttachments(this, 'edit-attachment-preview')">
                        <div class="form-text">Upload additional receipts, invoices, images, documents, or any supporting files (Max 10MB each)</div>
                        <div id="edit-attachment-preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPaymentModalLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Payment Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Payment Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Property:</strong></td>
                                        <td id="view_payment_property"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Unit:</strong></td>
                                        <td id="view_payment_unit"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tenant:</strong></td>
                                        <td id="view_payment_tenant"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Type:</strong></td>
                                        <td id="view_payment_type"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount:</strong></td>
                                        <td id="view_payment_amount"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Date:</strong></td>
                                        <td id="view_payment_date"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Method:</strong></td>
                                        <td id="view_payment_method"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Reference Number:</strong></td>
                                        <td id="view_payment_reference"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td><span id="view_payment_status" class="badge"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Notes -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Notes & Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Notes:</strong>
                                    <div id="view_payment_notes" class="mt-2"></div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a id="view_payment_receipt" href="#" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="bi bi-download"></i> Download Receipt
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Attachments -->
                    <div class="col-12 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Payment Attachments</h6>
                            </div>
                            <div class="card-body">
                                <div id="view-payment-attachments">
                                    <!-- Payment attachments will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editPayment(currentPaymentId)">Edit Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Payment Confirmation Modal -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePaymentModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="deletePaymentId">
                <p>Are you sure you want to delete this payment? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeletePayment()">Delete Payment</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
}

.payment-total::before {
    background: linear-gradient(45deg, var(--success-color), #28a745);
}

.payment-month::before {
    background: linear-gradient(45deg, var(--primary-color), #0a58ca);
}

.payment-pending::before {
    background: linear-gradient(45deg, var(--warning-color), #e6a800);
}

/* Ensure actions are clickable even if other positioned elements overlap */
#paymentsTable td:last-child,
#paymentsTable th:last-child {
    position: relative;
    z-index: 3;
}

#paymentsTable .btn-group,
#paymentsTable .btn-group .btn {
    position: relative;
    z-index: 4;
    pointer-events: auto;
}
</style>

<script>
// Toggle payment method fields for Add form
function togglePaymentMethodFields() {
    const paymentMethod = document.getElementById('payment_method').value;
    const mpesaFields = document.getElementById('mpesa_manual_fields');
    
    if (paymentMethod === 'mpesa_manual' || paymentMethod === 'mpesa_stk') {
        mpesaFields.style.display = 'block';
    } else {
        mpesaFields.style.display = 'none';
    }
}

// Toggle payment method fields for Edit form
function toggleEditMpesaFields() {
    const paymentMethod = document.getElementById('edit_payment_method').value;
    const mpesaFields = document.getElementById('edit_mpesa_manual_fields');
    
    if (paymentMethod === 'mpesa_manual' || paymentMethod === 'mpesa_stk') {
        mpesaFields.style.display = 'block';
    } else {
        mpesaFields.style.display = 'none';
    }
}

let currentPaymentId = null;

async function viewPayment(paymentId) {
    try {
        currentPaymentId = paymentId;
        
        const response = await fetch(`<?= BASE_URL ?>/payments/get/${paymentId}?t=${Date.now()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        
        if (data.success) {
            const payment = data;
            
            // Populate payment information
            document.getElementById('view_payment_property').textContent = payment.property_name || 'N/A';
            document.getElementById('view_payment_unit').textContent = payment.unit_number || 'N/A';
            document.getElementById('view_payment_tenant').textContent = payment.tenant_name || 'N/A';
            document.getElementById('view_payment_type').textContent = payment.payment_type || 'N/A';
            document.getElementById('view_payment_amount').textContent = '$' + parseFloat(payment.amount || 0).toFixed(2);
            document.getElementById('view_payment_date').textContent = payment.payment_date || 'N/A';
            document.getElementById('view_payment_method').textContent = payment.payment_method || 'N/A';
            document.getElementById('view_payment_reference').textContent = payment.reference_number || 'N/A';
            
            // Set status badge
            const statusBadge = document.getElementById('view_payment_status');
            statusBadge.textContent = payment.status ? payment.status.charAt(0).toUpperCase() + payment.status.slice(1) : 'Unknown';
            statusBadge.className = 'badge bg-' + getPaymentStatusBadgeClass(payment.status);
            
            // Set notes
            document.getElementById('view_payment_notes').textContent = payment.notes || 'No notes available';
            
            // Set receipt link
            document.getElementById('view_payment_receipt').href = `<?= BASE_URL ?>/payments/receipt/${paymentId}`;
            
            // Show the modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewPaymentModal'));
            viewModal.show();
            
            // Load payment attachments
            await loadPaymentAttachmentsForView(paymentId);
        } else {
            throw new Error(data.message || 'Failed to load payment details');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading payment details: ' + error.message);
    }
}

function getPaymentStatusBadgeClass(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'failed': return 'danger';
        case 'cancelled': return 'secondary';
        default: return 'primary';
    }
}

async function loadPaymentAttachmentsForView(paymentId) {
    try {
        const response = await fetch(`<?= BASE_URL ?>/payments/${paymentId}/files`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load payment attachments');
        }

        const data = await response.json();
        
        if (data.success) {
            displayPaymentAttachments(data.attachments || [], 'view-payment-attachments', false);
        }
    } catch (error) {
        console.error('Error loading payment attachments for view:', error);
        document.getElementById('view-payment-attachments').innerHTML = '<p class="text-muted">Unable to load attachments</p>';
    }
}

function editPayment(paymentId) {
    // Fetch payment data via AJAX and populate modal
    fetch(`<?= BASE_URL ?>/payments/get/${paymentId}?t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            console.log('Payment data received:', data); // Debug log
            if (data && data.id) {
                document.getElementById('edit_payment_id').value = data.id;
                // Ensure the value is a number and input is editable
                var amountInput = document.getElementById('edit_amount');
                amountInput.removeAttribute('readonly');
                amountInput.removeAttribute('disabled');
                amountInput.value = data.amount !== undefined && data.amount !== null ? parseFloat(data.amount) : '';
                document.getElementById('edit_payment_date').value = data.payment_date;
                document.getElementById('edit_payment_method').value = data.payment_method || '';
                document.getElementById('edit_reference_number').value = data.reference_number || '';
                
                // Debug status setting
                console.log('Setting status to:', data.status);
                
                // Use setTimeout to ensure the form is fully rendered
                setTimeout(() => {
                    document.getElementById('edit_status').value = data.status || 'completed';
                    console.log('Status field value after setting:', document.getElementById('edit_status').value);
                }, 100);
                
                document.getElementById('edit_notes').value = data.notes || '';
                
                // Handle M-Pesa fields
                if (data.payment_method === 'mpesa_manual' || data.payment_method === 'mpesa_stk') {
                    document.getElementById('edit_mpesa_manual_fields').style.display = 'block';
                    // Fetch M-Pesa transaction details if available
                    fetch(`<?= BASE_URL ?>/payments/mpesa/${data.id}`)
                        .then(response => response.json())
                        .then(mpesaData => {
                            if (mpesaData && mpesaData.phone_number) {
                                document.getElementById('edit_mpesa_phone').value = mpesaData.phone_number || '';
                                document.getElementById('edit_mpesa_transaction_code').value = mpesaData.transaction_code || '';
                                document.getElementById('edit_mpesa_verification_status').value = mpesaData.verification_status || 'pending';
                            }
                        })
                        .catch(() => {
                            // M-Pesa data not found, leave fields empty
                        });
                } else {
                    document.getElementById('edit_mpesa_manual_fields').style.display = 'none';
                }
                
                var modal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
                modal.show();
                
                // Load existing attachments
                loadPaymentAttachmentsForEdit(data.id);
            } else {
                alert('Could not load payment data.');
            }
        })
        .catch(() => alert('Error fetching payment data.'));
}

document.getElementById('editPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const paymentId = document.getElementById('edit_payment_id').value;
    const formData = new FormData(this);
    fetch(`<?= BASE_URL ?>/payments/update/${paymentId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error updating payment.');
        }
    })
    .catch(() => alert('Error updating payment.'));
});

function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

function deletePayment(paymentId) {
    // Show confirmation modal
    const modal = new bootstrap.Modal(document.getElementById('deletePaymentModal'));
    document.getElementById('deletePaymentId').value = paymentId;
    modal.show();
}

function confirmDeletePayment() {
    const paymentId = document.getElementById('deletePaymentId').value;
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = deleteBtn.innerHTML;
    
    // Show loading state
    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Deleting...';
    deleteBtn.disabled = true;
    
    fetch(`<?= BASE_URL ?>/payments/delete/${paymentId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('Payment deleted successfully!', 'success');
            
            // Remove the row from the table
            const row = document.querySelector(`tr[data-payment-id="${paymentId}"]`);
            if (row) {
                row.remove();
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
            modal.hide();
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting payment. Please try again.', 'danger');
    })
    .finally(() => {
        // Reset button state
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    const table = new DataTable('#paymentsTable', {
        responsive: true,
        order: [[2, 'desc']], // Sort by date by default
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "",
            searchPlaceholder: "Search payments...",
            emptyTable: "No payments found",
            zeroRecords: "No matching payments found",
            info: "Showing _START_ to _END_ of _TOTAL_ payments",
            infoEmpty: "Showing 0 to 0 of 0 payments",
            infoFiltered: "(filtered from _MAX_ total payments)"
        },
        drawCallback: function(settings) {
            // Hide pagination if we have no data or only one page
            const api = this.api();
            const pageInfo = api.page.info();
            
            if (pageInfo.pages <= 1) {
                $(this).parent().find('.dataTables_paginate').hide();
            } else {
                $(this).parent().find('.dataTables_paginate').show();
            }

            // Hide length selector if we have no data
            if (pageInfo.recordsTotal === 0) {
                $(this).parent().find('.dataTables_length').hide();
            } else {
                $(this).parent().find('.dataTables_length').show();
            }
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Filter functionality
    document.getElementById('dateFilter').addEventListener('change', filterTable);
    document.getElementById('propertyFilter').addEventListener('change', filterTable);
    const methodFilter = document.getElementById('methodFilter');
    methodFilter && methodFilter.addEventListener('change', filterTable);
    document.getElementById('amountFilter').addEventListener('change', filterTable);

    // Reset filters
    document.getElementById('filterForm').addEventListener('reset', function() {
        setTimeout(filterTable, 0);
    });

    function filterTable() {
        const dateFilter = document.getElementById('dateFilter').value;
        const propertyFilter = document.getElementById('propertyFilter').value.toLowerCase();
        const methodFilter = document.getElementById('methodFilter').value.toLowerCase();
        const amountFilter = document.getElementById('amountFilter').value;

        table.draw();

        // Remove any existing custom search functions before adding our new one
        DataTable.ext.search = [];

        // Custom filtering function
        DataTable.ext.search.push(function(settings, data, dataIndex) {
            const amount = parseFloat(data[1].replace(/[^0-9.-]+/g, ''));
            const date = new Date(data[2]);
            const method = (data[4] || '').toLowerCase();
            const property = (data[9] || '').toLowerCase();

            // Date filter
            if (dateFilter) {
                const today = new Date();
                const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                const startOfYear = new Date(today.getFullYear(), 0, 1);

                switch (dateFilter) {
                    case 'today':
                        if (date.toDateString() !== new Date().toDateString()) return false;
                        break;
                    case 'week':
                        if (date < startOfWeek) return false;
                        break;
                    case 'month':
                        if (date < startOfMonth) return false;
                        break;
                    case 'year':
                        if (date < startOfYear) return false;
                        break;
                }
            }

            // Property filter
            if (propertyFilter && property !== propertyFilter) return false;

            // Method filter
            if (methodFilter && !method.includes(methodFilter)) return false;

            // Amount range filter
            if (amountFilter) {
                const [min, max] = amountFilter.split('-').map(v => v.replace('+', '9999999'));
                if (amount < parseFloat(min) || amount > parseFloat(max)) return false;
            }

            return true;
        });

        table.draw();
    }

<?php if (!$isRealtor): ?>
    // Auto-populate amount with due amount when tenant is selected
    const tenantSelect = document.getElementById('tenant_id');
    const amountInput = document.getElementById('amount');
    if (tenantSelect && amountInput) {
        tenantSelect.addEventListener('change', function() {
            const selected = tenantSelect.options[tenantSelect.selectedIndex];
            const due = selected.getAttribute('data-due');
            if (due !== null && due !== undefined) {
                amountInput.value = due;
            }
        });
    }

    const rentPaymentCheckbox = document.getElementById('rent_payment');
    const utilityPaymentCheckbox = document.getElementById('utility_payment');
    const maintenancePaymentCheckbox = document.getElementById('maintenance_payment');
    const rentPaymentSection = document.getElementById('rent_payment_section');
    const utilityPaymentSection = document.getElementById('utility_payment_section');
    const maintenancePaymentSection = document.getElementById('maintenance_payment_section');
    const rentAmountInput = document.getElementById('rent_amount');
    const utilityAmountInput = document.getElementById('utility_amount');
    const maintenanceAmountInput = document.getElementById('maintenance_amount');
    const utilityTypeSelect = document.getElementById('utility_id');
    const utilityDetails = document.getElementById('utility_details');
    const selectedUtilitiesWrap = document.getElementById('selected_utilities');

    // Handle payment type checkbox changes
    rentPaymentCheckbox.addEventListener('change', function() {
        rentPaymentSection.style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            rentAmountInput.setAttribute('required', '');
        } else {
            rentAmountInput.removeAttribute('required');
        }
    });

    utilityPaymentCheckbox.addEventListener('change', function() {
        utilityPaymentSection.style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            // Prevent accidental double-capture as rent when user intends utilities only
            if (rentPaymentCheckbox && rentPaymentCheckbox.checked) {
                rentPaymentCheckbox.checked = false;
                rentPaymentCheckbox.dispatchEvent(new Event('change'));
            }
            utilityAmountInput.setAttribute('required', '');
            utilityTypeSelect.setAttribute('required', '');

            // If tenant is already selected, ensure utilities are populated
            if (tenantSelect && tenantSelect.value) {
                tenantSelect.dispatchEvent(new Event('change'));
            }
        } else {
            utilityAmountInput.removeAttribute('required');
            utilityTypeSelect.removeAttribute('required');
        }
    });

    maintenancePaymentCheckbox && maintenancePaymentCheckbox.addEventListener('change', function() {
        if (!maintenancePaymentSection) return;
        maintenancePaymentSection.style.display = this.checked ? 'block' : 'none';
        if (maintenanceAmountInput) {
            if (this.checked) {
                // Prevent accidental double-capture as rent when user intends maintenance only
                if (rentPaymentCheckbox && rentPaymentCheckbox.checked) {
                    rentPaymentCheckbox.checked = false;
                    rentPaymentCheckbox.dispatchEvent(new Event('change'));
                }
                maintenanceAmountInput.setAttribute('required', '');
            } else {
                maintenanceAmountInput.removeAttribute('required');
            }
        }
    });
<?php endif; ?>

    // Format date function
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }

<?php if (!$isRealtor): ?>
    // Handle tenant selection to show due amount and utilities
    document.getElementById('tenant_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const dueAmount = selectedOption.getAttribute('data-due');
        let paidMonths = [];
        try {
            paidMonths = JSON.parse(selectedOption.getAttribute('data-paid-months') || '[]');
        } catch (e) {
            paidMonths = [];
        }
        let utilities = [];
        try {
            utilities = JSON.parse(selectedOption.getAttribute('data-utilities') || '[]');
        } catch (e) {
            utilities = [];
        }

        // Default applies_to_month to current month
        const appliesToMonthInput = document.getElementById('applies_to_month');
        if (appliesToMonthInput && !appliesToMonthInput.value) {
            appliesToMonthInput.value = new Date().toISOString().slice(0, 7);
        }
        
        // Set rent amount
        if (dueAmount) {
            rentAmountInput.value = dueAmount;
        }

        // Validate month selection vs paid months
        const appliesToMonth = appliesToMonthInput;
        const warningEl = document.getElementById('adminPaidMonthWarning');
        const submitBtn = document.getElementById('addPaymentSubmitBtn');
        const rentChk = document.getElementById('rent_payment');

        const validatePaidMonth = () => {
            if (!appliesToMonth || !warningEl || !submitBtn) return;
            const ym = (appliesToMonth.value || '').trim();
            const rentSelected = !!(rentChk && rentChk.checked);
            const isPaid = rentSelected && ym !== '' && Array.isArray(paidMonths) && paidMonths.indexOf(ym) !== -1;
            if (isPaid) {
                warningEl.textContent = 'This month is already fully paid. Please select another month.';
                warningEl.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                warningEl.textContent = '';
                warningEl.style.display = 'none';
                submitBtn.disabled = false;
            }
        };

        appliesToMonth && appliesToMonth.addEventListener('change', validatePaidMonth);
        rentChk && rentChk.addEventListener('change', validatePaidMonth);
        validatePaidMonth();

        // Update utility type options
        utilityTypeSelect.innerHTML = '';
        utilities.forEach(utility => {
            const option = document.createElement('option');
            option.value = utility.id;
            const baseLabel = utility.label || (utility.type ? (utility.type.charAt(0).toUpperCase() + utility.type.slice(1)) : ('Utility #' + String(utility.id)));
            const methodLabel = (String(utility.is_metered) === '1' || utility.is_metered === 1) ? 'Metered' : 'Flat Rate';
            option.textContent = `${baseLabel} (${methodLabel})`;
            option.setAttribute('data-details', JSON.stringify(utility));
            utilityTypeSelect.appendChild(option);
        });

        if (selectedUtilitiesWrap) {
            selectedUtilitiesWrap.style.display = 'none';
            selectedUtilitiesWrap.innerHTML = '';
        }
        if (utilityDetails) {
            utilityDetails.style.display = 'none';
        }
        if (utilityAmountInput) {
            utilityAmountInput.value = '';
        }
    });

    // Handle utility type selection (supports multi-select)
    utilityTypeSelect.addEventListener('change', function() {
        const selectedOptions = Array.from(this.selectedOptions || []).filter(o => String(o.value || '').trim() !== '');
        const selectedDetails = [];
        selectedOptions.forEach(opt => {
            try {
                const d = JSON.parse(opt.getAttribute('data-details') || '{}');
                if (d && d.id) selectedDetails.push(d);
            } catch (e) {
            }
        });

        let total = 0;
        if (selectedUtilitiesWrap) {
            selectedUtilitiesWrap.innerHTML = '';
            if (selectedDetails.length) {
                selectedUtilitiesWrap.style.display = 'block';
                const list = document.createElement('div');
                list.className = 'd-grid gap-2';

                selectedDetails.forEach(d => {
                    const row = document.createElement('div');
                    row.className = 'border rounded p-2 d-flex justify-content-between align-items-center gap-2';

                    const label = document.createElement('div');
                    const baseLabel = d.label || (d.type ? (d.type.charAt(0).toUpperCase() + d.type.slice(1)) : ('Utility #' + String(d.id)));
                    label.className = 'small';
                    label.textContent = baseLabel;

                    const input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.01';
                    input.min = '0';
                    input.className = 'form-control form-control-sm';
                    input.style.maxWidth = '160px';
                    input.name = `utility_amounts[${d.id}]`;
                    input.value = (d.cost !== undefined && d.cost !== null) ? d.cost : '';

                    input.addEventListener('input', () => {
                        let sum = 0;
                        selectedUtilitiesWrap.querySelectorAll('input[name^="utility_amounts["]').forEach(i => {
                            const v = parseFloat(i.value);
                            if (!isNaN(v) && v > 0) sum += v;
                        });
                        if (utilityAmountInput) utilityAmountInput.value = sum ? sum.toFixed(2) : '';
                    });

                    row.appendChild(label);
                    row.appendChild(input);
                    list.appendChild(row);

                    const v = parseFloat(input.value);
                    if (!isNaN(v) && v > 0) total += v;
                });

                selectedUtilitiesWrap.appendChild(list);
            } else {
                selectedUtilitiesWrap.style.display = 'none';
            }
        }

        if (utilityDetails) {
            if (selectedDetails.length === 1) {
                const d = selectedDetails[0];
                const isMetered = (String(d.is_metered) === '1' || d.is_metered === 1);
                const meterRows = document.getElementById('meter_reading_rows');
                const rateRow = document.getElementById('rate_row');

                if (meterRows) meterRows.style.display = isMetered ? 'block' : 'none';

                if (isMetered) {
                    document.getElementById('previous_reading').textContent = d.previous_reading;
                    document.getElementById('previous_reading_date').textContent = formatDate(d.previous_reading_date);
                    document.getElementById('current_reading').textContent = d.current_reading;
                    document.getElementById('current_reading_date').textContent = formatDate(d.current_reading_date);
                    if (rateRow) rateRow.style.display = 'block';
                    document.getElementById('rate').textContent = d.rate;
                } else {
                    if (rateRow) rateRow.style.display = 'none';
                }

                document.getElementById('amount_due').textContent = d.cost;
                utilityDetails.style.display = 'block';
            } else {
                utilityDetails.style.display = 'none';
            }
        }

        if (utilityAmountInput) {
            utilityAmountInput.value = total ? total.toFixed(2) : '';
        }
    });

    // File upload preview function for attachments
    window.previewAttachments = function(input, previewId) {
        const preview = document.getElementById(previewId);
        preview.innerHTML = '';
        
        if (input.files) {
            Array.from(input.files).forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'border rounded p-2 mb-2 d-flex justify-content-between align-items-center';
                
                // Determine icon based on file type
                let icon = 'bi-file-earmark';
                if (file.type.startsWith('image/')) {
                    icon = 'bi-file-earmark-image';
                } else if (file.type.includes('pdf')) {
                    icon = 'bi-file-earmark-pdf';
                } else if (file.type.includes('word') || file.type.includes('document')) {
                    icon = 'bi-file-earmark-word';
                } else if (file.type.includes('excel') || file.type.includes('spreadsheet')) {
                    icon = 'bi-file-earmark-excel';
                }
                
                fileItem.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi ${icon} me-2 text-primary"></i>
                        <div>
                            <div class="fw-medium">${file.name}</div>
                            <small class="text-muted">${formatFileSize(file.size)}</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="removeAttachmentPreview(this, '${input.id}', ${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                preview.appendChild(fileItem);
            });
        }
    };

    window.removeAttachmentPreview = function(button, inputId, fileIndex) {
        // Remove the preview element
        button.closest('.border').remove();
        
        // Note: We can't actually remove files from input[type="file"] due to security restrictions
        // The user will need to reselect files if they want to remove one
        showAlert('To remove files, please reselect your files without the unwanted ones.', 'info');
    };

    window.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // Load payment attachments for editing
    window.loadPaymentAttachmentsForEdit = async function(paymentId) {
        try {
            console.log('Loading attachments for payment:', paymentId);
            const response = await fetch(`${BASE_URL}/payments/${paymentId}/files`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load payment attachments');
            }

            const data = await response.json();
            console.log('Payment attachments API response:', data);
            
            if (data.success) {
                console.log('Payment Attachments:', data.attachments);
                displayPaymentAttachments(data.attachments || [], 'edit-existing-attachments', true);
            } else {
                console.error('Payment API returned error:', data.message);
            }
        } catch (error) {
            console.error('Error loading payment attachments for edit:', error);
        }
    };

    // Display payment attachments
    window.displayPaymentAttachments = function(attachments, containerId, allowDelete = false) {
        console.log('displayPaymentAttachments called:', {attachments, containerId, allowDelete});
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        
        if (attachments.length === 0) {
            container.innerHTML = '<p class="text-muted">No attachments uploaded</p>';
            return;
        }
        
        attachments.forEach((attachment, index) => {
            console.log(`Processing payment attachment ${index}:`, attachment);
            console.log(`Payment attachment original_name: "${attachment.original_name}"`);
            
            const fileItem = document.createElement('div');
            fileItem.className = 'border rounded p-2 mb-2 d-flex justify-content-between align-items-center';
            
            // Determine icon based on file type
            let icon = 'bi-file-earmark';
            if (attachment.mime_type && attachment.mime_type.startsWith('image/')) {
                icon = 'bi-file-earmark-image';
            } else if (attachment.mime_type && attachment.mime_type.includes('pdf')) {
                icon = 'bi-file-earmark-pdf';
            } else if (attachment.mime_type && (attachment.mime_type.includes('word') || attachment.mime_type.includes('document'))) {
                icon = 'bi-file-earmark-word';
            } else if (attachment.mime_type && (attachment.mime_type.includes('excel') || attachment.mime_type.includes('spreadsheet'))) {
                icon = 'bi-file-earmark-excel';
            }
            
            fileItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} me-2 text-primary"></i>
                    <div>
                        <div class="fw-medium">
                            <a href="${attachment.url}" target="_blank" class="text-decoration-none">${attachment.original_name}</a>
                        </div>
                        <small class="text-muted">${formatFileSize(attachment.file_size)}</small>
                    </div>
                </div>
                ${allowDelete ? `
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="deletePaymentFile(${attachment.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                ` : ''}
            `;
            container.appendChild(fileItem);
        });
    };

    // Delete payment file
    window.deletePaymentFile = async function(fileId) {
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }
        
        try {
            const response = await fetch(`${BASE_URL}/files/delete/${fileId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                showAlert('File deleted successfully', 'success');
                // Reload the files
                const paymentId = document.getElementById('edit_payment_id').value;
                if (paymentId) {
                    await loadPaymentAttachmentsForEdit(paymentId);
                }
            } else {
                showAlert(data.message || 'Failed to delete file', 'danger');
            }
        } catch (error) {
            console.error('Error deleting file:', error);
            showAlert('Error deleting file', 'danger');
        }
    };

<?php endif; ?>
</script>
<script>
// Realtor Contracts -> Payments linkage
(function(){
    const contractSel = document.getElementById('realtor_contract_id');
    const clientSel = document.getElementById('realtor_client_id');
    const listingSel = document.getElementById('realtor_listing_id');
    const typeSel = document.getElementById('realtor_payment_type');
    const typeLabel = document.getElementById('realtor_payment_type_label');
    const amountInput = document.getElementById('realtor_amount');
    const monthInput = document.getElementById('realtor_applies_to_month');
    const monthHint = document.getElementById('realtor_month_hint');

    if (!contractSel || !typeSel || !monthInput) {
        return;
    }

    const applyContractRules = () => {
        const opt = contractSel.options[contractSel.selectedIndex];
        const cid = (contractSel.value || '').trim();
        const terms = (opt && opt.getAttribute) ? (opt.getAttribute('data-terms') || '') : '';
        const optClientId = (opt && opt.getAttribute) ? (opt.getAttribute('data-client-id') || '') : '';
        const optListingId = (opt && opt.getAttribute) ? (opt.getAttribute('data-listing-id') || '') : '';
        const optTotal = (opt && opt.getAttribute) ? (opt.getAttribute('data-total-amount') || '') : '';
        const optMonthly = (opt && opt.getAttribute) ? (opt.getAttribute('data-monthly-amount') || '') : '';

        const hasContract = cid !== '';
        if (clientSel) clientSel.disabled = hasContract;
        if (listingSel) listingSel.disabled = hasContract;

        if (hasContract) {
            if (clientSel && optClientId !== '') clientSel.value = optClientId;
            if (listingSel && optListingId !== '') listingSel.value = optListingId;

            if (amountInput) {
                const n = (terms === 'monthly') ? (parseFloat(optMonthly || '0') || 0) : (parseFloat(optTotal || '0') || 0);
                if (!Number.isNaN(n)) {
                    amountInput.value = n > 0 ? String(n) : '';
                }
            }

            if (terms === 'monthly') {
                typeSel.value = 'mortgage_monthly';
                if (typeLabel) typeLabel.value = 'Monthly';
                monthInput.required = true;
                if (monthHint) monthHint.style.display = '';
            } else {
                typeSel.value = 'mortgage';
                if (typeLabel) typeLabel.value = 'One Time';
                monthInput.required = false;
                monthInput.value = '';
                if (monthHint) monthHint.style.display = 'none';
            }
        } else {
            // Contract is required for Realtor, but keep the UI consistent if cleared.
            typeSel.value = 'mortgage';
            if (typeLabel) typeLabel.value = 'One Time';
            monthInput.required = false;
            if (monthHint) monthHint.style.display = 'none';
        }
    };

    contractSel.addEventListener('change', applyContractRules);
    applyContractRules();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';