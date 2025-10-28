<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="page-header card">
        <div class="card-body">
            <h1 class="mb-0">Add New Utility</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/utilities/store" id="addUtilityForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label for="unit_id" class="form-label">Unit</label>
                    <select class="form-select" id="unit_id" name="unit_id" required>
                        <option value="">Select Unit</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= $unit['id'] ?>">
                                <?= htmlspecialchars($unit['unit_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="utility_type" class="form-label">Utility Type</label>
                    <select class="form-select" id="utility_type" name="utility_type" required>
                        <option value="">Select Utility Type</option>
                        <?php foreach ($utilityTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type['utility_type']) ?>" data-billing-method="<?= htmlspecialchars($type['billing_method']) ?>">
                                <?= htmlspecialchars($type['utility_type']) ?> (<?= $type['billing_method'] === 'metered' ? 'Metered' : 'Flat Rate' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="metered_fields" style="display:none;">
                    <div class="mb-3">
                        <label for="meter_number" class="form-label">Meter Number</label>
                        <input type="text" class="form-control" id="meter_number" name="meter_number">
                    </div>
                    <div class="mb-3">
                        <label for="previous_reading" class="form-label">Previous Reading</label>
                        <input type="number" class="form-control" id="previous_reading" name="previous_reading" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="current_reading" class="form-label">Current Reading</label>
                        <input type="number" class="form-control" id="current_reading" name="current_reading" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="units_used" class="form-label">Units Used</label>
                        <input type="number" class="form-control" id="units_used" name="units_used" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="reading_date" class="form-label">Reading Date</label>
                        <input type="date" class="form-control" id="reading_date" name="reading_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="mb-3" id="cost_group" style="display:none;">
                    <label for="cost" class="form-label">Cost (KES)</label>
                    <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0" readonly>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/utilities" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Utility</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const utilityRates = <?php echo json_encode($utilityTypes); ?>;
function getRateAndMethod(type) {
    return utilityRates.find(t => t.utility_type === type) || null;
}
function updateFormFields() {
    const selectedType = document.getElementById('utility_type').value;
    const info = getRateAndMethod(selectedType);
    const billingMethod = info ? info.billing_method : '';
    const rate = info ? parseFloat(info.rate_per_unit) : 0;
    const meteredFields = document.getElementById('metered_fields');
    const costGroup = document.getElementById('cost_group');
    const costInput = document.getElementById('cost');
    if (billingMethod === 'metered') {
        meteredFields.style.display = '';
        costGroup.style.display = '';
        costInput.value = '';
        costInput.readOnly = true;
        calculateMeteredCost();
    } else if (billingMethod === 'flat_rate') {
        meteredFields.style.display = 'none';
        costGroup.style.display = '';
        costInput.value = rate;
        costInput.readOnly = true;
    } else {
        meteredFields.style.display = 'none';
        costGroup.style.display = 'none';
        costInput.value = '';
    }
}
document.getElementById('utility_type').addEventListener('change', function() {
    updateFormFields();
});
document.getElementById('previous_reading').addEventListener('input', calculateMeteredCost);
document.getElementById('current_reading').addEventListener('input', calculateMeteredCost);
function calculateMeteredCost() {
    const prev = parseFloat(document.getElementById('previous_reading').value) || 0;
    const curr = parseFloat(document.getElementById('current_reading').value) || 0;
    const units = curr - prev;
    document.getElementById('units_used').value = units > 0 ? units : 0;
    const selectedType = document.getElementById('utility_type').value;
    const info = getRateAndMethod(selectedType);
    const rate = info ? parseFloat(info.rate_per_unit) : 0;
    document.getElementById('cost').value = (units > 0 ? units * rate : 0).toFixed(2);
}
</script> 