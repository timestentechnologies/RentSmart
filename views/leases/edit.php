<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">
                <i class="bi bi-pencil-square text-primary me-2"></i>Edit Lease
            </h1>
            <a href="<?= BASE_URL ?>/leases" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Leases
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/leases/update/<?= (int)$lease['id'] ?>">
                <div class="row g-3">
                    <!-- Property and Unit -->
                    <div class="col-md-6">
                        <label for="property_id" class="form-label">Property</label>
                        <select id="property_id" class="form-select" required>
                            <option value="">Select Property</option>
                            <?php foreach ($properties as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ((int)($lease['property_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Used to filter units list. The selected unit determines the property saved.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="unit_id" class="form-label">Unit</label>
                        <select id="unit_id" name="unit_id" class="form-select" required>
                            <?php
                                // Ensure the current unit appears even if not vacant
                                $currentUnitOption = sprintf(
                                    '<option value="%d" selected>%s</option>',
                                    (int)$lease['unit_id'],
                                    htmlspecialchars(($lease['unit_number'] ?? ('Unit #' . $lease['unit_id'])))
                                );
                                echo $currentUnitOption;
                            ?>
                        </select>
                    </div>

                    <!-- Tenant -->
                    <div class="col-md-12">
                        <label for="tenant_id" class="form-label">Tenant</label>
                        <select id="tenant_id" name="tenant_id" class="form-select" required>
                            <option value="">Select Tenant</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= ((int)$lease['tenant_id'] === (int)$t['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dates -->
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($lease['start_date']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($lease['end_date']) ?>" required>
                    </div>

                    <!-- Financials -->
                    <div class="col-md-6">
                        <label for="rent_amount" class="form-label">Monthly Rent</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" step="0.01" id="rent_amount" name="rent_amount" class="form-control" value="<?= htmlspecialchars($lease['rent_amount']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="security_deposit" class="form-label">Security Deposit</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" step="0.01" id="security_deposit" name="security_deposit" class="form-control" value="<?= htmlspecialchars($lease['security_deposit']) ?>" required>
                        </div>
                        <div class="form-text">Typically equals one month of rent.</div>
                    </div>

                    <!-- Payment and Status -->
                    <div class="col-md-6">
                        <label for="payment_day" class="form-label">Payment Due Day</label>
                        <input type="number" min="1" max="31" id="payment_day" name="payment_day" class="form-control" value="<?= (int)($lease['payment_day'] ?? 1) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <?php
                                $statuses = ['active' => 'Active', 'expired' => 'Expired', 'terminated' => 'Terminated'];
                                $curStatus = $lease['status'] ?? 'active';
                                foreach ($statuses as $val => $label) {
                                    $sel = ($curStatus === $val) ? 'selected' : '';
                                    echo "<option value=\"$val\" $sel>$label</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($lease['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>/leases" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Load units for selected property (vacant only), but keep current unit available
    document.getElementById('property_id').addEventListener('change', async function() {
        const propertyId = this.value;
        const unitSelect = document.getElementById('unit_id');
        const currentUnitId = <?= (int)$lease['unit_id'] ?>;
        const currentUnitLabel = <?= json_encode($lease['unit_number'] ?? ('Unit #'.$lease['unit_id'])) ?>;

        unitSelect.innerHTML = '';

        // Always include current unit as the first option
        const opt = document.createElement('option');
        opt.value = currentUnitId;
        opt.textContent = currentUnitLabel + ' (current)';
        unitSelect.appendChild(opt);

        if (!propertyId) return;

        try {
            const res = await fetch(`${BASE_URL}/leases/units/${propertyId}`);
            const units = await res.json();
            units.forEach(u => {
                // Avoid duplicating current unit
                if (parseInt(u.id) === currentUnitId) return;
                const o = document.createElement('option');
                o.value = u.id;
                o.textContent = `${u.unit_number} (Ksh ${parseFloat(u.rent_amount).toFixed(2)})`;
                unitSelect.appendChild(o);
            });
        } catch (e) {
            console.error('Failed loading units', e);
        }
    });

    // Validate dates
    document.getElementById('end_date').addEventListener('change', function() {
        const startDate = document.getElementById('start_date').value;
        if (startDate && this.value && new Date(this.value) <= new Date(startDate)) {
            alert('End date must be after start date');
            this.value = '';
        }
    });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
