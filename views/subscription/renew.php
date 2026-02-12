<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header">
        <div class="card-body">
            <h1 class="h3 mb-0">Renew Your Subscription</h1>
            <p class="text-muted">Choose a plan that best fits your needs</p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show mt-4">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
    
    <?php if ($subscription): ?>
        <div class="card mt-4 bg-info bg-opacity-25">
            <div class="card-body">
                <h5 class="card-title">Current Subscription Status</h5>
                <p class="mb-0">
                    Plan: <strong><?= $subscription['plan_type'] ?></strong><br>
                    Status: <strong><?= ucfirst($subscription['status']) ?></strong><br>
                    <?php if ($subscription['status'] === 'trialing'): ?>
                        Trial Ends: <strong><?= date('F j, Y', strtotime($subscription['trial_ends_at'])) ?></strong>
                    <?php else: ?>
                        Current Period Ends: <strong><?= date('F j, Y', strtotime($subscription['current_period_ends_at'])) ?></strong>
                    <?php endif; ?>
                </p>
                <?php if ($subscription['status'] === 'trialing' || ((isset($subscription['price']) ? (float)$subscription['price'] : 0) == 0)): ?>
                    <a href="<?= BASE_URL ?>/subscription/invoice/current" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download Trial/Free Invoice
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Invoices & Payments (moved to top) -->
    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Your Subscription Invoices</h5>
                <small class="text-muted">Managers, Agents, and Landlords can download their invoices here</small>
            </div>
            <?php 
                $onTrialOrFree = !empty($subscription) && ($subscription['status'] === 'trialing' || ((isset($subscription['price']) ? (float)$subscription['price'] : 0) == 0));
            ?>
            <?php if (!empty($payments) || $onTrialOrFree): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($onTrialOrFree): ?>
                                <tr>
                                    <td><?= htmlspecialchars('TRIAL-' . date('Ymd')) ?></td>
                                    <td><?= date('M d, Y H:i') ?></td>
                                    <td><?= htmlspecialchars($subscription['plan_type'] ?? 'Free Trial') ?></td>
                                    <td>Ksh<?= number_format(0, 2) ?></td>
                                    <td>Trial</td>
                                    <td><span class="badge bg-info text-dark">Trial</span></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/subscription/invoice/current">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= (int)$p['id'] ?></td>
                                    <td><?= isset($p['created_at']) ? date('M d, Y H:i', strtotime($p['created_at'])) : '' ?></td>
                                    <td><?= htmlspecialchars($p['plan_type'] ?? '-') ?></td>
                                    <td>Ksh<?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($p['payment_method'] ?? '-')) ?></td>
                                    <td>
                                        <?php $status = strtolower($p['status'] ?? 'pending'); ?>
                                        <span class="badge bg-<?= $status === 'completed' || $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                            <?= ucfirst($p['status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/subscription/invoice/<?= (int)$p['id'] ?>">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">
                    No subscription payments found yet.
                    <?php if (!empty($subscription) && ($subscription['status'] === 'trialing' || ((isset($subscription['price']) ? (float)$subscription['price'] : 0) == 0))): ?>
                        You are currently on a trial/free plan. 
                        <a href="<?= BASE_URL ?>/subscription/invoice/current" class="link-primary">Download Trial/Free Invoice</a>.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Subscription Status moved to top -->

    <div class="row mt-4">
        <?php foreach ($plans as $plan): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 plan-card" onclick="selectPlan(this, '<?= $plan['id'] ?>', '<?= htmlspecialchars($plan['name']) ?>', '<?= $plan['price'] ?>')">
                    <div class="card-body">
                        <input type="radio" name="plan_id" value="<?= $plan['id'] ?>" class="plan-radio" required>
                        <h5 class="card-title"><?= $plan['name'] ?></h5>
                        <?php $isEnterprise = isset($plan['name']) && strtolower($plan['name']) === 'enterprise'; ?>
                        <h3 class="text-primary"><?= $isEnterprise ? 'Custom Pricing' : 'Ksh' . number_format($plan['price'], 2) . '/month' ?></h3>
                        <p class="text-muted"><?= $plan['description'] ?></p>
                        <?php $__pl = isset($plan['property_limit']) ? (int)$plan['property_limit'] : null; $__pl_text = ($__pl && $__pl > 0) ? ('Up to ' . number_format($__pl) . ' properties') : 'Unlimited properties'; ?>
                        <div class="mb-2"><span class="badge bg-secondary"><?= $__pl_text ?></span></div>
                        <ul class="list-unstyled">
                            <?php foreach (explode("\n", $plan['features']) as $feature): ?>
                                <li><i class="bi bi-check-circle text-success me-2"></i><?= $feature ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row mt-4">
        <div class="col-12 text-center">
            <form action="<?= BASE_URL ?>/subscription/renew" method="POST" id="renewForm">
                <?= csrf_field() ?>
                <input type="hidden" name="payment_method" id="paymentMethod" value="">
                <input type="hidden" name="selected_plan_id" id="selectedPlanId" value="">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-repeat me-2"></i><?= (!empty($subscription) && strtolower($subscription['status']) === 'active') ? 'Upgrade Plan' : 'Renew Subscription' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Invoices section moved above -->
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Complete Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                    $subscriptionPaymentMethods = $subscriptionPaymentMethods ?? [];
                    $subMpesaManualMethods = [];
                    $subMpesaStkMethods = [];
                    foreach ($subscriptionPaymentMethods as $m) {
                        $t = strtolower((string)($m['type'] ?? ''));
                        if ($t === 'mpesa_manual') {
                            $subMpesaManualMethods[] = $m;
                        }
                        if ($t === 'mpesa_stk') {
                            $subMpesaStkMethods[] = $m;
                        }
                    }

                    $subMpesaManual = !empty($subMpesaManualMethods) ? $subMpesaManualMethods[0] : null;
                    $subMpesaStk = !empty($subMpesaStkMethods) ? $subMpesaStkMethods[0] : null;

                    $subManualDetails = [];
                    if ($subMpesaManual && !empty($subMpesaManual['details'])) {
                        $subManualDetails = json_decode((string)$subMpesaManual['details'], true) ?: [];
                    }
                ?>
                <div class="mb-4">
                    <h6>Selected Plan: <span id="selectedPlanName"></span></h6>
                    <h6>Amount: Ksh<span id="selectedPlanPrice"></span>Month</h6>
                </div>

                <!-- Payment Method Selection -->
                <div class="mb-4">
                    <h6>Select Payment Method</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="paymentMethodRadio" id="mpesaRadio" value="mpesa" checked>
                        <label class="form-check-label" for="mpesaRadio">
                            M-Pesa
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentMethodRadio" id="cardRadio" value="card">
                        <label class="form-check-label" for="cardRadio">
                            Credit/Debit Card
                        </label>
                    </div>
                </div>

                <!-- M-Pesa Form -->
                <div id="mpesaForm">
                    <div class="mb-3">
                        <label class="form-label">M-Pesa Payment Method</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="mpesaMethodRadio" id="mpesaStkRadio" value="stk" <?= $subMpesaStk ? 'checked' : (!$subMpesaManual ? 'checked' : '') ?> <?= $subMpesaStk ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="mpesaStkRadio">
                                STK Push (Automatic)
                            </label>
                            <small class="d-block text-muted">Receive payment prompt on your phone</small>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mpesaMethodRadio" id="mpesaManualRadio" value="manual" <?= (!$subMpesaStk && $subMpesaManual) ? 'checked' : '' ?> <?= $subMpesaManual ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="mpesaManualRadio">
                                Pay Bill (Manual)
                            </label>
                            <small class="d-block text-muted">Pay manually through M-Pesa menu</small>
                        </div>
                    </div>

                    <!-- STK Push Form -->
                    <div id="stkPushForm">
                        <div class="mb-3">
                            <label for="phoneNumber" class="form-label">M-Pesa Phone Number</label>
                            <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="254700000000" autocomplete="off" inputmode="numeric">
                            <small class="text-muted">Enter your M-Pesa registered phone number starting with 254</small>
                        </div>
                    </div>

                    <!-- Manual Payment Form -->
                    <div id="manualMpesaForm">
                        <?php if (count($subMpesaManualMethods) > 1): ?>
                            <div class="mb-3">
                                <label for="subscriptionManualMethodSelect" class="form-label">Choose Manual M-Pesa Option</label>
                                <select id="subscriptionManualMethodSelect" class="form-select">
                                    <?php foreach ($subMpesaManualMethods as $idx => $mm): ?>
                                        <option value="<?= (int)($mm['id'] ?? 0) ?>" <?= $idx === 0 ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)($mm['name'] ?? ('Manual Method #' . $idx))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="card bg-info bg-opacity-25">
                            <div class="card-body">
                                <h6 class="card-title mb-3">How to Pay via M-Pesa:</h6>
                                <ol class="mb-0">
                                    <li>Go to M-Pesa menu</li>
                                    <li id="mpesaStepPaybillMenu" style="display:none;">Select Pay Bill</li>
                                    <li id="mpesaStepPaybillBusiness" style="display:none;">Enter Business No: <strong id="mpesaPaybillNumber"></strong></li>
                                    <li id="mpesaStepPaybillAccount" style="display:none;">Enter Account No: <strong id="mpesaAccountNumber"></strong></li>

                                    <li id="mpesaStepTillMenu" style="display:none;">Select Lipa na M-Pesa</li>
                                    <li id="mpesaStepTillBuyGoods" style="display:none;">Select Buy Goods and Services</li>
                                    <li id="mpesaStepTillNumber" style="display:none;">Enter Till No: <strong id="mpesaTillNumber"></strong></li>

                                    <li>Enter Amount: Ksh<strong id="mpesaAmount"></strong></li>
                                    <li>Enter your M-Pesa PIN</li>
                                    <li>Save the M-Pesa message with transaction code</li>
                                </ol>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="mpesaPhone" class="form-label">Phone Number Used</label>
                            <input type="tel" class="form-control" id="mpesaPhone" name="mpesa_phone" placeholder="254700000000" autocomplete="off" inputmode="numeric">
                            <small class="text-muted">Enter the phone number you used to make the payment</small>
                        </div>
                        <div class="mb-3">
                            <label for="mpesaCode" class="form-label">M-Pesa Transaction Code</label>
                            <input type="text" class="form-control" id="mpesaCode" name="mpesa_code" placeholder="QWE1234567" autocomplete="off">
                            <small class="text-muted">Enter the M-Pesa transaction code received via SMS</small>
                        </div>
                    </div>
                </div>

                <!-- Card Payment Form -->
                <div id="cardForm" style="display: none;">
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expiryDate" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" placeholder="123">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="processPaymentBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.plan-card {
    border: 2px solid #dee2e6;
    border-radius: 1rem;
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.plan-card:hover {
    border-color: #0061f2;
    transform: translateY(-5px);
}

.plan-card.selected {
    border-color: #0061f2;
    background-color: #f8f9ff;
}

.plan-radio {
    position: absolute;
    top: 1rem;
    right: 1rem;
    margin: 0;
}

/* Payment Form Styles */
#mpesaForm, #cardForm {
    transition: display 0.3s ease-in-out;
}

#stkPushForm, #manualMpesaForm {
    transition: display 0.3s ease-in-out;
}

#manualMpesaForm {
    margin-top: 1rem;
}

#manualMpesaForm .alert {
    margin-bottom: 1.5rem;
}

#manualMpesaForm .form-control {
    margin-bottom: 0.5rem;
}

/* Ensure inputs inside modal always accept typing */
.modal .form-control {
    pointer-events: auto;
}
</style>

<script>
// Initialize Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    var modals = [].slice.call(document.querySelectorAll('.modal'))
    modals.map(function (modal) {
        return new bootstrap.Modal(modal);
    });

    // Set initial state for M-Pesa forms
    const initialMpesaMethod = document.querySelector('input[name="mpesaMethodRadio"]:checked').value;
    toggleMpesaForms(initialMpesaMethod);

    // Subscription manual methods (from server)
    window.__subscriptionManualMethods = <?php
        $manualPayload = [];
        foreach ($subMpesaManualMethods as $mm) {
            $d = [];
            if (!empty($mm['details'])) {
                $d = json_decode((string)$mm['details'], true) ?: [];
            }
            $manualPayload[] = [
                'id' => (int)($mm['id'] ?? 0),
                'name' => (string)($mm['name'] ?? ''),
                'details' => $d
            ];
        }
        echo json_encode($manualPayload);
    ?>;

    function applyManualMpesaDetails(details) {
        details = details || {};
        const method = String(details.mpesa_method || 'paybill').toLowerCase();

        const showPaybill = method !== 'till';
        const elPaybillMenu = document.getElementById('mpesaStepPaybillMenu');
        const elPaybillBusiness = document.getElementById('mpesaStepPaybillBusiness');
        const elPaybillAccount = document.getElementById('mpesaStepPaybillAccount');
        const elTillMenu = document.getElementById('mpesaStepTillMenu');
        const elTillBuy = document.getElementById('mpesaStepTillBuyGoods');
        const elTillNumber = document.getElementById('mpesaStepTillNumber');

        if (elPaybillMenu) elPaybillMenu.style.display = showPaybill ? '' : 'none';
        if (elPaybillBusiness) elPaybillBusiness.style.display = showPaybill ? '' : 'none';
        if (elPaybillAccount) elPaybillAccount.style.display = showPaybill ? '' : 'none';
        if (elTillMenu) elTillMenu.style.display = showPaybill ? 'none' : '';
        if (elTillBuy) elTillBuy.style.display = showPaybill ? 'none' : '';
        if (elTillNumber) elTillNumber.style.display = showPaybill ? 'none' : '';

        const pb = document.getElementById('mpesaPaybillNumber');
        const acc = document.getElementById('mpesaAccountNumber');
        const till = document.getElementById('mpesaTillNumber');
        if (pb) pb.textContent = details.paybill_number || '';
        if (acc) acc.textContent = details.account_number || '';
        if (till) till.textContent = details.till_number || '';
    }

    // Apply initial manual method details (first method)
    if (window.__subscriptionManualMethods && window.__subscriptionManualMethods.length) {
        applyManualMpesaDetails(window.__subscriptionManualMethods[0].details);
    } else {
        applyManualMpesaDetails({});
    }

    // Handle manual method selection
    const manualSelect = document.getElementById('subscriptionManualMethodSelect');
    if (manualSelect) {
        manualSelect.addEventListener('change', function() {
            const id = parseInt(this.value || '0', 10);
            const found = (window.__subscriptionManualMethods || []).find(m => m.id === id);
            applyManualMpesaDetails(found ? found.details : {});
        });
    }

    // Toggle M-Pesa payment forms
    document.querySelectorAll('input[name="mpesaMethodRadio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('M-Pesa method changed to:', this.value);
            toggleMpesaForms(this.value);
        });
    });

    // Toggle payment method forms
    document.querySelectorAll('input[name="paymentMethodRadio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Payment method changed to:', this.value);
            const mpesaForm = document.getElementById('mpesaForm');
            const cardForm = document.getElementById('cardForm');
            
            mpesaForm.style.display = this.value === 'mpesa' ? 'block' : 'none';
            cardForm.style.display = this.value === 'card' ? 'block' : 'none';
            
            // If switching to M-Pesa, ensure the correct M-Pesa form is shown
            if (this.value === 'mpesa') {
                const selectedMpesaMethod = document.querySelector('input[name="mpesaMethodRadio"]:checked').value;
                console.log('Selected M-Pesa method:', selectedMpesaMethod);
                toggleMpesaForms(selectedMpesaMethod);
            }
            
            document.getElementById('paymentMethod').value = this.value;
        });
    });
});

// Function to toggle M-Pesa form visibility
function toggleMpesaForms(method) {
    console.log('Toggling M-Pesa forms for method:', method);
    const stkForm = document.getElementById('stkPushForm');
    const manualForm = document.getElementById('manualMpesaForm');
    
    if (!stkForm || !manualForm) {
        console.error('Could not find M-Pesa forms:', { stkForm, manualForm });
        return;
    }
    
    stkForm.style.display = method === 'stk' ? 'block' : 'none';
    manualForm.style.display = method === 'manual' ? 'block' : 'none';
    
    console.log('Form visibility:', {
        stk: stkForm.style.display,
        manual: manualForm.style.display
    });
    
    // Enable/disable relevant inputs and focus the active one
    const phoneInput = document.getElementById('phoneNumber');
    const manualPhone = document.getElementById('mpesaPhone');
    const manualCode = document.getElementById('mpesaCode');

    if (phoneInput) phoneInput.disabled = method !== 'stk';
    if (manualPhone) manualPhone.disabled = method !== 'manual';
    if (manualCode) manualCode.disabled = method !== 'manual';

    if (method === 'stk' && phoneInput) {
        phoneInput.focus();
    } else if (method === 'manual' && manualPhone) {
        manualPhone.focus();
    }

    // Update amount in manual payment instructions
    if (method === 'manual' && selectedPlanDetails) {
        const amountElement = document.getElementById('mpesaAmount');
        if (amountElement) {
            amountElement.textContent = selectedPlanDetails.price;
            console.log('Updated manual amount to:', selectedPlanDetails.price);
        } else {
            console.error('Could not find mpesaAmount element');
        }
    }
}

let selectedPlanDetails = null;

function selectPlan(element, planId, planName, planPrice) {
    // Remove selected class from all plans
    document.querySelectorAll('.plan-card').forEach(card => {
        card.classList.remove('selected');
        card.querySelector('.plan-radio').checked = false;
    });
    
    // Add selected class to clicked plan
    element.classList.add('selected');
    
    // Check the radio button
    element.querySelector('.plan-radio').checked = true;

    // Store selected plan details
    selectedPlanDetails = {
        id: planId,
        name: planName,
        price: planPrice
    };

    // Update hidden input
    document.getElementById('selectedPlanId').value = planId;
}

// Toggle payment forms
document.querySelectorAll('input[name="paymentMethodRadio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('mpesaForm').style.display = this.value === 'mpesa' ? 'block' : 'none';
        document.getElementById('cardForm').style.display = this.value === 'card' ? 'block' : 'none';
        document.getElementById('paymentMethod').value = this.value;
    });
});

document.getElementById('renewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const selectedPlanId = document.getElementById('selectedPlanId').value;
    if (!selectedPlanId) {
        alert('Please select a subscription plan');
        return;
    }

    // Update modal with selected plan details
    document.getElementById('selectedPlanName').textContent = selectedPlanDetails.name;
    document.getElementById('selectedPlanPrice').textContent = selectedPlanDetails.price;

    // Show payment modal
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
});

// Handle payment processing
document.getElementById('processPaymentBtn').addEventListener('click', async function() {
    const button = this;
    const spinner = button.querySelector('.spinner-border');
    const paymentMethod = document.querySelector('input[name="paymentMethodRadio"]:checked').value;
    
    // Validate form based on payment method
    if (paymentMethod === 'mpesa') {
        const mpesaMethod = document.querySelector('input[name="mpesaMethodRadio"]:checked').value;
        
        if (mpesaMethod === 'stk') {
            const phoneNumber = document.getElementById('phoneNumber').value;
            if (!phoneNumber || !/^254\d{9}$/.test(phoneNumber)) {
                alert('Please enter a valid M-Pesa phone number starting with 254');
                return;
            }
        } else {
            const mpesaPhone = document.getElementById('mpesaPhone').value;
            const mpesaCode = document.getElementById('mpesaCode').value;
            if (!mpesaPhone || !/^254\d{9}$/.test(mpesaPhone)) {
                alert('Please enter a valid phone number used for payment');
                return;
            }
            if (!mpesaCode || !/^[A-Z0-9]{10}$/.test(mpesaCode)) {
                alert('Please enter a valid M-Pesa transaction code');
                return;
            }
        }
    } else {
        const cardForm = document.getElementById('cardForm');
        if (!cardForm.checkValidity()) {
            cardForm.reportValidity();
            return;
        }
    }

    try {
        // Show loading state
        button.disabled = true;
        spinner.classList.remove('d-none');

        // Add payment method to form
        document.getElementById('paymentMethod').value = paymentMethod;

        if (paymentMethod === 'mpesa') {
            const mpesaMethod = document.querySelector('input[name="mpesaMethodRadio"]:checked').value;
            
            if (mpesaMethod === 'stk') {
                // Initiate STK Push
                const response = await fetch(window.BASE_URL + '/subscription/initiate-stk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
                    },
                    body: JSON.stringify({
                        phone_number: document.getElementById('phoneNumber').value,
                        plan_id: selectedPlanDetails.id,
                        amount: selectedPlanDetails.price
                    })
                });
                const raw = await response.text();
                let result;
                try {
                    result = JSON.parse(raw);
                } catch (e) {
                    throw new Error(raw);
                }
                
                if (result.success) {
                    const checkoutId = result.checkout_request_id;
                    // Show waiting modal and poll for status
                    Swal.fire({
                        title: 'Waiting for M-Pesa confirmation',
                        text: 'Please check your phone and enter your M-Pesa PIN.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            if (checkoutId) {
                                startSubscriptionStkPolling(checkoutId);
                            }
                        }
                    });
                } else {
                    throw new Error(result.message || 'Failed to initiate M-Pesa payment');
                }
            } else {
                // Handle manual M-Pesa payment
                const response = await fetch(window.BASE_URL + '/subscription/verify-mpesa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
                    },
                    body: JSON.stringify({
                        phone_number: document.getElementById('mpesaPhone').value,
                        transaction_code: document.getElementById('mpesaCode').value,
                        plan_id: selectedPlanDetails.id,
                        amount: selectedPlanDetails.price,
                        is_manual: true
                    })
                });
                const raw = await response.text();
                let result;
                try {
                    result = JSON.parse(raw);
                } catch (e) {
                    throw new Error(raw);
                }
                
                if (result.success) {
                    Swal.fire({
                        title: 'Payment Submitted',
                        text: 'We received your M-Pesa payment. It is pending verification by admin. You will be notified once confirmed.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Close modal and redirect
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        window.location.href = window.BASE_URL + '/dashboard';
                    });
                } else {
                    throw new Error(result.message || 'Failed to verify M-Pesa payment');
                }
            }
        } else {
            // For card payments, just submit the form for now
            document.getElementById('renewForm').submit();
        }
    } catch (error) {
        alert(error.message || 'Payment processing failed. Please try again.');
    } finally {
        // Reset loading state
        button.disabled = false;
        spinner.classList.add('d-none');
    }
});

// Poll subscription STK status and show success modal on completion
let subStkPollInterval = null;
function startSubscriptionStkPolling(checkoutRequestId) {
    let attempts = 0;
    const maxAttempts = 60; // ~60 seconds
    const poll = async () => {
        try {
            attempts++;
            const resp = await fetch(window.BASE_URL + '/mpesa/callback?checkout_request_id=' + encodeURIComponent(checkoutRequestId), {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const raw = await resp.text();
            let data;
            try { data = JSON.parse(raw); } catch { data = { status: 'pending', message: raw }; }
            if (data.status === 'completed') {
                clearInterval(subStkPollInterval);
                Swal.fire({
                    title: 'Payment Successful!',
                    text: 'Your subscription has been activated.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    const modalEl = document.getElementById('paymentModal');
                    const inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                    window.location.href = window.BASE_URL + '/dashboard';
                });
            } else if (data.status === 'failed' || data.status === 'cancelled') {
                clearInterval(subStkPollInterval);
                Swal.fire({
                    title: 'Payment Failed',
                    text: data.result_desc || data.message || 'The payment could not be completed.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else if (attempts >= maxAttempts) {
                clearInterval(subStkPollInterval);
                Swal.fire({
                    title: 'Payment Pending',
                    text: 'We are still awaiting confirmation. You can check again shortly.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            }
        } catch (e) {
            // Ignore transient errors and continue polling
        }
    };
    clearInterval(subStkPollInterval);
    subStkPollInterval = setInterval(poll, 1000);
}

// When payment modal is shown, update amount and ensure correct form visibility
document.getElementById('paymentModal').addEventListener('show.bs.modal', function(event) {
    console.log('Payment modal is being shown');
    
    // Update amount in manual payment instructions
    if (selectedPlanDetails) {
        console.log('Selected plan details:', selectedPlanDetails);
        document.getElementById('mpesaAmount').textContent = selectedPlanDetails.price;
    }
    
    // Ensure correct payment method form is shown
    const selectedPaymentMethod = document.querySelector('input[name="paymentMethodRadio"]:checked').value;
    console.log('Initial payment method:', selectedPaymentMethod);
    
    const mpesaForm = document.getElementById('mpesaForm');
    const cardForm = document.getElementById('cardForm');
    
    mpesaForm.style.display = selectedPaymentMethod === 'mpesa' ? 'block' : 'none';
    cardForm.style.display = selectedPaymentMethod === 'card' ? 'block' : 'none';
    
    // If M-Pesa is selected, ensure correct M-Pesa form is shown
    if (selectedPaymentMethod === 'mpesa') {
        const selectedMpesaMethod = document.querySelector('input[name="mpesaMethodRadio"]:checked').value;
        console.log('Initial M-Pesa method:', selectedMpesaMethod);
        toggleMpesaForms(selectedMpesaMethod);
    }
});

// Format card inputs
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{4})/g, '$1 ').trim();
    e.target.value = value.substring(0, 19);
});

document.getElementById('expiryDate').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    e.target.value = value.substring(0, 5);
});

document.getElementById('cvv').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    e.target.value = value.substring(0, 3);
});

// Format phone number
document.getElementById('phoneNumber').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (!value.startsWith('254')) {
        value = '254' + value;
    }
    e.target.value = value.substring(0, 12);
});

// Format manual M-Pesa phone input similarly
const mpesaPhoneEl = document.getElementById('mpesaPhone');
if (mpesaPhoneEl) {
    mpesaPhoneEl.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (!value.startsWith('254')) {
            value = '254' + value;
        }
        e.target.value = value.substring(0, 12);
    });
}

// Uppercase and sanitize M-Pesa code
const mpesaCodeEl = document.getElementById('mpesaCode');
if (mpesaCodeEl) {
    mpesaCodeEl.addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?> 