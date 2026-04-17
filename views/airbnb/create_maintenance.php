<?php
ob_start();
?>

<div class="container-fluid pt-4">
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
                        <select name="property_id" id="propertySelect" class="form-select" required>
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
const propertyUnits = <?= json_encode(array_reduce($properties, function($acc, $p) {
    $acc[$p['id']] = $p['units'] ?? [];
    return $acc;
}, [])) ?>;

document.getElementById('propertySelect').addEventListener('change', function() {
    const propertyId = this.value;
    const unitSelect = document.getElementById('unitSelect');
    
    unitSelect.innerHTML = '<option value="">Select Unit</option>';
    
    if (propertyId && propertyUnits[propertyId]) {
        propertyUnits[propertyId].forEach(function(unit) {
            const option = document.createElement('option');
            option.value = unit.id;
            option.textContent = unit.unit_number;
            unitSelect.appendChild(option);
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
