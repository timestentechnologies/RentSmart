<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Debug Info -->
    <?php if (empty($properties)): ?>
        <div class="alert alert-warning">
            <strong>Debug:</strong> No properties available. Check user access permissions.
        </div>
    <?php else: ?>
        <?php foreach ($properties as $p): ?>
            <?php if (empty($p['units'])): ?>
                <div class="alert alert-info">
                    <strong>Debug:</strong> Property '<?= htmlspecialchars($p['name'] ?? 'Unknown') ?>' (ID: <?= $p['id'] ?>) has <strong>0 units</strong>.
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="card page-header border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-tools text-warning me-2"></i>Create Maintenance Request</h1>
            <a href="<?= BASE_URL ?>/airbnb/maintenance" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Maintenance
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/airbnb/maintenance/store">
                <?= csrf_field() ?>
                
                <div class="row g-3">
                    <!-- Property -->
                    <div class="col-md-6">
                        <label class="form-label">Property <span class="text-danger">*</span></label>
                        <select name="property_id" id="propertySelect" class="form-select" required onchange="loadUnits(this.value)">
                            <option value="">Select Property</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= $property['id'] ?>"><?= htmlspecialchars($property['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Unit -->
                    <div class="col-md-6">
                        <label class="form-label">Unit (Optional)</label>
                        <select name="unit_id" id="unitSelect" class="form-select">
                            <option value="">Select Unit</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="col-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Fix leaking faucet" required>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the maintenance issue in detail..." required></textarea>
                    </div>

                    <!-- Category -->
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="hvac">HVAC</option>
                            <option value="appliance">Appliance</option>
                            <option value="structural">Structural</option>
                            <option value="pest_control">Pest Control</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="other" selected>Other</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Estimated Cost -->
                    <div class="col-md-4">
                        <label class="form-label">Estimated Cost (KES)</label>
                        <input type="number" name="estimated_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3"><i class="bi bi-credit-card me-2"></i>Payment Details</h5>

                <div class="row g-3">
                    <!-- Who Pays -->
                    <div class="col-md-6">
                        <label class="form-label">Who will pay for this maintenance? <span class="text-danger">*</span></label>
                        <select name="payment_source" class="form-select" required>
                            <option value="owner_funds">Owner/Manager Pays (My Wallet)</option>
                            <option value="client_bill">Bill to Client</option>
                            <option value="shared">Shared Cost</option>
                        </select>
                        <small class="text-muted">Select who will be responsible for payment</small>
                    </div>

                    <!-- Bill to Client -->
                    <div class="col-md-6">
                        <label class="form-label">Bill Client for this expense?</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="bill_to_client" value="1" id="billToClientSwitch">
                            <label class="form-check-label" for="billToClientSwitch">
                                Yes, add this to client's invoice
                            </label>
                        </div>
                        <small class="text-muted">Toggle to include this in client's next bill</small>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-6">
                        <label class="form-label">How will you pay? <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="wallet">Wallet (Available: KES <?= number_format($walletBalance ?? 0, 2) ?>)</option>
                            <option value="cash">Cash</option>
                            <option value="mpesa">MPesa</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="later">Pay Later</option>
                        </select>
                        <small class="text-muted">Select your payment method</small>
                    </div>

                    <!-- Actual Cost -->
                    <div class="col-md-6">
                        <label class="form-label">Actual Cost (KES)</label>
                        <input type="number" name="actual_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                        <small class="text-muted">Leave blank if same as estimated</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/airbnb/maintenance" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Create Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Property units data
<?php
// Debug: Log what properties and units we have
error_log('Create Maintenance View - Properties count: ' . count($properties));
foreach ($properties as $p) {
    error_log('  Property: ' . ($p['name'] ?? 'N/A') . ' ID=' . ($p['id'] ?? 'N/A') . ' Units=' . count($p['units'] ?? []));
}
?>
const propertyUnits = <?= json_encode(array_reduce($properties, function($acc, $p) {
    // Use string keys to match HTML select values
    $acc[(string)$p['id']] = array_values($p['units'] ?? []);
    return $acc;
}, []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_FORCE_OBJECT) ?>;
console.log('Property units data initialized:', propertyUnits);

function loadUnits(propertyId) {
    const unitSelect = document.getElementById('unitSelect');
    if (!unitSelect) {
        console.error('Unit select element not found!');
        return;
    }

    // Clear and reset
    unitSelect.innerHTML = '<option value="">-- Select Unit --</option>';

    try {
        // Ensure propertyId is a string for consistent object key lookup
        const key = String(propertyId);
        const units = propertyUnits[key];
        
        console.log('Loading units for property:', propertyId, 'Key:', key, 'Found units:', units);

        if (propertyId && units && Array.isArray(units) && units.length > 0) {
            units.forEach(function(unit) {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = (unit.unit_number || 'Unit ' + unit.id);
                unitSelect.appendChild(option);
            });
            console.log('Successfully loaded ' + units.length + ' units');
        } else if (propertyId) {
            unitSelect.innerHTML = '<option value="">-- No Units Available --</option>';
            console.warn('No units found for property key:', key);
        }
    } catch (err) {
        console.error('Error in loadUnits:', err);
        unitSelect.innerHTML = '<option value="">-- Error Loading Units --</option>';
    }
}

// Initialize on page load if property is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const propertySelect = document.getElementById('propertySelect');
    if (propertySelect.value) {
        loadUnits(propertySelect.value);
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
