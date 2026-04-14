<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Add Walk-in Guest</h1>
            <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Walk-in Guests
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>/airbnb/walkin-guests/create">
                <div class="row">
                    <!-- Property Selection -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Property <span class="text-danger">*</span></label>
                        <select name="property_id" id="property_id" class="form-select" required>
                            <option value="">Select Property</option>
                            <?php foreach ($properties as $property): ?>
                            <option value="<?php echo $property['id']; ?>">
                                <?php echo htmlspecialchars($property['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Unit Selection -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Assigned Unit (Optional)</label>
                        <select name="assigned_unit_id" id="assigned_unit_id" class="form-select">
                            <option value="">Select Unit</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Guest Information -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guest Name <span class="text-danger">*</span></label>
                        <input type="text" name="guest_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guest Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="guest_phone" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guest Email</label>
                        <input type="email" name="guest_email" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <!-- Guest Count & Dates -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" name="guest_count" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Preferred Check-in</label>
                        <input type="date" name="preferred_check_in" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Preferred Check-out</label>
                        <input type="date" name="preferred_check_out" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Budget Range</label>
                        <input type="text" name="budget_range" class="form-control" placeholder="e.g., 3000-5000">
                    </div>
                </div>

                <div class="row">
                    <!-- Follow-up & Status -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Follow-up Date</label>
                        <input type="datetime-local" name="follow_up_date" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Requirements</label>
                        <input type="text" name="requirements" class="form-control" placeholder="e.g., ground floor, near pool">
                    </div>
                </div>

                <div class="row">
                    <!-- Notes -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Any additional information about this inquiry..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Walk-in Guest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('assigned_unit_id');

    // Load units when property changes
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        
        if (propertyId) {
            fetch('<?php echo BASE_URL; ?>/properties/' + propertyId + '/units')
                .then(response => response.json())
                .then(data => {
                    if (data.units) {
                        data.units.forEach(unit => {
                            const option = document.createElement('option');
                            option.value = unit.id;
                            option.textContent = unit.unit_number;
                            unitSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading units:', error));
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
