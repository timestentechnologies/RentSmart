<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-credit-card text-primary me-2"></i>Payment Methods
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <label for="pmFilterProperty" class="form-label mb-0 small text-muted">Filter by Property</label>
                        <select id="pmFilterProperty" class="form-select form-select-sm" style="min-width: 220px;">
                            <option value="all" selected>All Properties</option>
                            <?php if (!empty($properties)): ?>
                                <?php foreach ($properties as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name'] ?? ('Property #' . $p['id'])) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentMethodModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Payment Method
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Payment Methods Table -->
    <div class="card mt-4">
        <div class="table-responsive">
            <table id="paymentMethodsTable" class="table table-hover mb-0 datatable">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">NAME</th>
                        <th class="text-muted">TYPE</th>
                        <th class="text-muted">DESCRIPTION</th>
                        <th class="text-muted">LINKED PROPERTIES</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">CREATED</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentMethods)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-credit-card display-4 text-muted mb-3 d-block"></i>
                                <h5>No payment methods found</h5>
                                <p class="text-muted">Add your first payment method to get started</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paymentMethods as $method): ?>
                            <tr data-prop-ids="<?= htmlspecialchars(implode(',', $linkedPropertyIdsByMethod[$method['id']] ?? [])) ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php
                                            $typeIcons = [
                                                'mpesa' => 'bi-phone',
                                                'bank_transfer' => 'bi-bank',
                                                'cash' => 'bi-cash',
                                                'cheque' => 'bi-receipt',
                                                'card' => 'bi-credit-card'
                                            ];
                                            $icon = $typeIcons[$method['type']] ?? 'bi-credit-card';
                                            ?>
                                            <i class="bi <?= $icon ?> text-primary"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($method['name']) ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= ucwords(str_replace('_', ' ', $method['type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($method['description'] ?? 'No description') ?>
                                    </span>
                                    <?php if ($method['type'] === 'mpesa_manual' || $method['type'] === 'mpesa_stk'): ?>
                                        <?php 
                                        $details = json_decode($method['details'] ?? '{}', true);
                                        if ($method['type'] === 'mpesa_manual'): 
                                        ?>
                                            <br><small class="text-info">
                                                <?php if (isset($details['mpesa_method']) && $details['mpesa_method'] === 'paybill'): ?>
                                                    Paybill: <?= htmlspecialchars($details['paybill_number'] ?? 'N/A') ?> | 
                                                    Account: <?= htmlspecialchars($details['account_number'] ?? 'N/A') ?>
                                                <?php elseif (isset($details['mpesa_method']) && $details['mpesa_method'] === 'till'): ?>
                                                    Till: <?= htmlspecialchars($details['till_number'] ?? 'N/A') ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php elseif ($method['type'] === 'mpesa_stk'): ?>
                                            <br><small class="text-info">
                                                Shortcode: <?= htmlspecialchars($details['shortcode'] ?? 'N/A') ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $linked = $linkedPropertiesByMethod[$method['id']] ?? []; 
                                    if (empty($linked)) {
                                        echo '<span class="badge bg-secondary">None</span>';
                                    } else {
                                        echo '<div class="d-flex flex-wrap gap-1">';
                                        foreach ($linked as $pname) {
                                            echo '<span class="badge bg-light text-dark">' . htmlspecialchars($pname) . '</span>';
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $method['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $method['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($method['created_at'])) ?>
                                    <br>
                                    <small class="text-muted"><?= date('g:i A', strtotime($method['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editPaymentMethod(<?= $method['id'] ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePaymentMethod(<?= $method['id'] ?>)" title="Delete">
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

<!-- Add Payment Method Modal -->
<div class="modal fade" id="addPaymentMethodModal" tabindex="-1" aria-labelledby="addPaymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addPaymentMethodForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentMethodModalLabel">Add Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Name</label>
                        <input type="text" id="add_name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_type" class="form-label">Type</label>
                        <select id="add_type" name="type" class="form-select" required onchange="toggleMpesaFields('add')">
                            <option value="">Select Type</option>
                            <option value="mpesa_manual">M-Pesa (Manual)</option>
                            <option value="mpesa_stk">M-Pesa (STK Push)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                    
                    <!-- M-Pesa Manual Fields -->
                    <div id="add_mpesa_manual_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="radio" id="add_paybill" name="mpesa_method" value="paybill" class="form-check-input">
                                        <label for="add_paybill" class="form-check-label">Paybill</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="radio" id="add_till" name="mpesa_method" value="till" class="form-check-input">
                                        <label for="add_till" class="form-check-label">Buy Goods Till</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="add_paybill_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="add_paybill_number" class="form-label">Paybill Number</label>
                                <input type="text" id="add_paybill_number" name="paybill_number" class="form-control" placeholder="e.g., 123456">
                            </div>
                            <div class="mb-3">
                                <label for="add_account_number" class="form-label">Account Number</label>
                                <input type="text" id="add_account_number" name="account_number" class="form-control" placeholder="e.g., Rent Payment">
                            </div>
                        </div>
                        
                        <div id="add_till_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="add_till_number" class="form-label">Till Number</label>
                                <input type="text" id="add_till_number" name="till_number" class="form-control" placeholder="e.g., 123456">
                            </div>
                        </div>
                    </div>
                    
                    <!-- M-Pesa STK Push Fields -->
                    <div id="add_mpesa_stk_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="add_consumer_key" class="form-label">Consumer Key</label>
                            <input type="text" id="add_consumer_key" name="consumer_key" class="form-control" placeholder="M-Pesa Consumer Key">
                        </div>
                        <div class="mb-3">
                            <label for="add_consumer_secret" class="form-label">Consumer Secret</label>
                            <input type="password" id="add_consumer_secret" name="consumer_secret" class="form-control" placeholder="M-Pesa Consumer Secret">
                        </div>
                        <div class="mb-3">
                            <label for="add_shortcode" class="form-label">Shortcode</label>
                            <input type="text" id="add_shortcode" name="shortcode" class="form-control" placeholder="e.g., 123456">
                        </div>
                        <div class="mb-3">
                            <label for="add_passkey" class="form-label">Passkey</label>
                            <input type="password" id="add_passkey" name="passkey" class="form-control" placeholder="M-Pesa Passkey">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea id="add_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="add_is_active" name="is_active" class="form-check-input" checked>
                            <label for="add_is_active" class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link to Properties</label>
                        <div class="border rounded p-2" style="max-height: 220px; overflow:auto;">
                            <?php if (!empty($properties)): ?>
                                <?php foreach ($properties as $p): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add_property_ids_<?= $p['id'] ?>" name="property_ids[]" value="<?= $p['id'] ?>">
                                        <label class="form-check-label" for="add_property_ids_<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['name'] ?? ('Property #' . $p['id'])) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted small">No properties available to link.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Payment Method</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Payment Method Modal -->
<div class="modal fade" id="editPaymentMethodModal" tabindex="-1" aria-labelledby="editPaymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editPaymentMethodForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentMethodModalLabel">Edit Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type" class="form-label">Type</label>
                        <select id="edit_type" name="type" class="form-select" required onchange="toggleMpesaFields('edit')">
                            <option value="">Select Type</option>
                            <option value="mpesa_manual">M-Pesa (Manual)</option>
                            <option value="mpesa_stk">M-Pesa (STK Push)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                    
                    <!-- M-Pesa Manual Fields -->
                    <div id="edit_mpesa_manual_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="radio" id="edit_paybill" name="mpesa_method" value="paybill" class="form-check-input">
                                        <label for="edit_paybill" class="form-check-label">Paybill</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="radio" id="edit_till" name="mpesa_method" value="till" class="form-check-input">
                                        <label for="edit_till" class="form-check-label">Buy Goods Till</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="edit_paybill_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="edit_paybill_number" class="form-label">Paybill Number</label>
                                <input type="text" id="edit_paybill_number" name="paybill_number" class="form-control" placeholder="e.g., 123456">
                            </div>
                            <div class="mb-3">
                                <label for="edit_account_number" class="form-label">Account Number</label>
                                <input type="text" id="edit_account_number" name="account_number" class="form-control" placeholder="e.g., Rent Payment">
                            </div>
                        </div>
                        
                        <div id="edit_till_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="edit_till_number" class="form-label">Till Number</label>
                                <input type="text" id="edit_till_number" name="till_number" class="form-control" placeholder="e.g., 123456">
                            </div>
                        </div>
                    </div>
                    
                    <!-- M-Pesa STK Push Fields -->
                    <div id="edit_mpesa_stk_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_consumer_key" class="form-label">Consumer Key</label>
                            <input type="text" id="edit_consumer_key" name="consumer_key" class="form-control" placeholder="M-Pesa Consumer Key">
                        </div>
                        <div class="mb-3">
                            <label for="edit_consumer_secret" class="form-label">Consumer Secret</label>
                            <input type="password" id="edit_consumer_secret" name="consumer_secret" class="form-control" placeholder="M-Pesa Consumer Secret">
                        </div>
                        <div class="mb-3">
                            <label for="edit_shortcode" class="form-label">Shortcode</label>
                            <input type="text" id="edit_shortcode" name="shortcode" class="form-control" placeholder="e.g., 123456">
                        </div>
                        <div class="mb-3">
                            <label for="edit_passkey" class="form-label">Passkey</label>
                            <input type="password" id="edit_passkey" name="passkey" class="form-control" placeholder="M-Pesa Passkey">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input">
                            <label for="edit_is_active" class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link to Properties</label>
                        <div class="border rounded p-2" style="max-height: 220px; overflow:auto;">
                            <?php if (!empty($properties)): ?>
                                <?php foreach ($properties as $p): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_property_ids_<?= $p['id'] ?>" name="property_ids[]" value="<?= $p['id'] ?>">
                                        <label class="form-check-label" for="edit_property_ids_<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['name'] ?? ('Property #' . $p['id'])) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted small">No properties available to link.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Payment Method</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle M-Pesa Fields
function toggleMpesaFields(prefix) {
    const typeSelect = document.getElementById(prefix + '_type');
    const mpesaManualFields = document.getElementById(prefix + '_mpesa_manual_fields');
    const mpesaStkFields = document.getElementById(prefix + '_mpesa_stk_fields');
    
    // Hide all M-Pesa fields first
    mpesaManualFields.style.display = 'none';
    mpesaStkFields.style.display = 'none';
    
    // Show relevant fields based on type
    if (typeSelect.value === 'mpesa_manual') {
        mpesaManualFields.style.display = 'block';
    } else if (typeSelect.value === 'mpesa_stk') {
        mpesaStkFields.style.display = 'block';
    }
}

// Toggle M-Pesa Manual sub-fields
function toggleMpesaManualFields(prefix) {
    const paybillRadio = document.getElementById(prefix + '_paybill');
    const tillRadio = document.getElementById(prefix + '_till');
    const paybillFields = document.getElementById(prefix + '_paybill_fields');
    const tillFields = document.getElementById(prefix + '_till_fields');
    
    if (paybillRadio.checked) {
        paybillFields.style.display = 'block';
        tillFields.style.display = 'none';
    } else if (tillRadio.checked) {
        paybillFields.style.display = 'none';
        tillFields.style.display = 'block';
    } else {
        paybillFields.style.display = 'none';
        tillFields.style.display = 'none';
    }
}

// Add event listeners for M-Pesa manual fields
document.addEventListener('DOMContentLoaded', function() {
    // Add form radio button listeners
    document.getElementById('add_paybill').addEventListener('change', () => toggleMpesaManualFields('add'));
    document.getElementById('add_till').addEventListener('change', () => toggleMpesaManualFields('add'));
    
    // Edit form radio button listeners
    document.getElementById('edit_paybill').addEventListener('change', () => toggleMpesaManualFields('edit'));
    document.getElementById('edit_till').addEventListener('change', () => toggleMpesaManualFields('edit'));
});

// Add Payment Method Form
document.getElementById('addPaymentMethodForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
    submitBtn.disabled = true;
    
    fetch('<?= BASE_URL ?>/payment-methods/create', {
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
            bootstrap.Modal.getInstance(document.getElementById('addPaymentMethodModal')).hide();
            this.reset();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        alert('Error adding payment method');
    });
});

// Edit Payment Method Form
document.getElementById('editPaymentMethodForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    submitBtn.disabled = true;
    
    const id = formData.get('id');
    
    fetch(`<?= BASE_URL ?>/payment-methods/update/${id}`, {
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
            bootstrap.Modal.getInstance(document.getElementById('editPaymentMethodModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        alert('Error updating payment method');
    });
});

// Edit Payment Method
function editPaymentMethod(id) {
    fetch(`<?= BASE_URL ?>/payment-methods/get/${id}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const method = data.payment_method;
            document.getElementById('edit_id').value = method.id;
            document.getElementById('edit_name').value = method.name;
            document.getElementById('edit_type').value = method.type;
            document.getElementById('edit_description').value = method.description || '';
            document.getElementById('edit_is_active').checked = method.is_active == 1;
            
            // Toggle M-Pesa fields based on type
            toggleMpesaFields('edit');
            
            // Populate M-Pesa specific fields
            if (method.type === 'mpesa_manual' || method.type === 'mpesa_stk') {
                const details = JSON.parse(method.details || '{}');
                
                if (method.type === 'mpesa_manual') {
                    // Set radio button and populate fields
                    if (details.mpesa_method === 'paybill') {
                        document.getElementById('edit_paybill').checked = true;
                        document.getElementById('edit_paybill_number').value = details.paybill_number || '';
                        document.getElementById('edit_account_number').value = details.account_number || '';
                        toggleMpesaManualFields('edit');
                    } else if (details.mpesa_method === 'till') {
                        document.getElementById('edit_till').checked = true;
                        document.getElementById('edit_till_number').value = details.till_number || '';
                        toggleMpesaManualFields('edit');
                    }
                } else if (method.type === 'mpesa_stk') {
                    document.getElementById('edit_consumer_key').value = details.consumer_key || '';
                    document.getElementById('edit_consumer_secret').value = details.consumer_secret || '';
                    document.getElementById('edit_shortcode').value = details.shortcode || '';
                    document.getElementById('edit_passkey').value = details.passkey || '';
                }
            }
            
            // Pre-select linked properties
            try {
                // Uncheck all first
                document.querySelectorAll('input[id^="edit_property_ids_"]').forEach(cb => cb.checked = false);
                const linked = Array.isArray(data.property_ids) ? data.property_ids : [];
                linked.forEach(function(pid){
                    const el = document.getElementById('edit_property_ids_' + pid);
                    if (el) el.checked = true;
                });
            } catch (e) {}
            
            const modal = new bootstrap.Modal(document.getElementById('editPaymentMethodModal'));
            modal.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error loading payment method');
    });
}

// Delete Payment Method
function deletePaymentMethod(id) {
    if (confirm('Are you sure you want to delete this payment method?')) {
        fetch(`<?= BASE_URL ?>/payment-methods/delete/${id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting payment method');
        });
    }
}
</script>

<script>
// Payment Methods: property filter integrated with DataTables
document.addEventListener('DOMContentLoaded', function(){
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable) return;
    const $ = window.jQuery;
    const tableEl = document.getElementById('paymentMethodsTable');
    const filterEl = document.getElementById('pmFilterProperty');
    if (!tableEl || !filterEl) return;

    // Custom filter: only apply to this table
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!settings.nTable || settings.nTable.id !== 'paymentMethodsTable') return true;
        const selected = filterEl.value || 'all';
        if (selected === 'all') return true;
        const rowObj = (settings.aoData && settings.aoData[dataIndex]) ? settings.aoData[dataIndex] : null;
        const row = rowObj && rowObj.nTr ? rowObj.nTr : null;
        if (!row) return true;
        const ids = (row.getAttribute('data-prop-ids') || '').split(',').filter(Boolean);
        return ids.indexOf(String(selected)) !== -1;
    });

    // Trigger redraw on selection
    filterEl.addEventListener('change', function(){
        try {
            if ($.fn.DataTable.isDataTable(tableEl)) {
                $(tableEl).DataTable().draw();
            }
        } catch (e) {
            // no-op
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
