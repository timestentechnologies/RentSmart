<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Configure: <?php echo htmlspecialchars($property['name']); ?></h1>
            <div>
                <a href="<?php echo BASE_URL; ?>/airbnb/property-settings" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <form method="POST" action="<?php echo BASE_URL; ?>/airbnb/property-settings/<?php echo $property['id']; ?>">
        <!-- General Settings -->
        <div class="card mb-4">
            <div class="card-header text-white" style="background-color: #2c1343;">
                <h5 class="mb-0"><i class="fas fa-cog"></i> General Airbnb Settings</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_airbnb_enabled" id="is_airbnb_enabled" 
                                <?php echo (!empty($settings['is_airbnb_enabled'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_airbnb_enabled">
                                Enable Airbnb for this property
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="instant_booking" id="instant_booking" 
                                <?php echo (!empty($settings['instant_booking'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="instant_booking">
                                Enable Instant Booking
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Booking Lead Time (hours)</label>
                        <input type="number" name="booking_lead_time_hours" class="form-control" 
                            value="<?php echo htmlspecialchars($settings['booking_lead_time_hours'] ?? '24'); ?>" min="0">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Minimum Stay (nights)</label>
                        <input type="number" name="min_stay_nights" class="form-control" 
                            value="<?php echo htmlspecialchars($settings['min_stay_nights'] ?? '1'); ?>" min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Maximum Stay (nights)</label>
                        <input type="number" name="max_stay_nights" class="form-control" 
                            value="<?php echo htmlspecialchars($settings['max_stay_nights'] ?? '30'); ?>" min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Check-in Time</label>
                        <input type="time" name="check_in_time" class="form-control" 
                            value="<?php echo htmlspecialchars($settings['check_in_time'] ?? '14:00:00'); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Check-out Time</label>
                        <input type="time" name="check_out_time" class="form-control" 
                            value="<?php echo htmlspecialchars($settings['check_out_time'] ?? '11:00:00'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Fees & Charges -->
        <div class="card mb-4" style="overflow: visible; z-index: 100; position: relative;">
            <div class="card-header text-white" style="background-color: #ff8c00;">
                <h5 class="mb-0"><i class="fas fa-money-bill"></i> Fees & Charges</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cleaning Fee (KES)</label>
                        <input type="number" name="cleaning_fee" class="form-control" step="0.01" 
                            value="<?php echo htmlspecialchars($settings['cleaning_fee'] ?? '0.00'); ?>" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Security Deposit (KES)</label>
                        <input type="number" name="security_deposit" class="form-control" step="0.01" 
                            value="<?php echo htmlspecialchars($settings['security_deposit'] ?? '0.00'); ?>" min="0">
                    </div>
                    <div class="col-md-4 mb-3" style="position: relative; z-index: 2;">
                        <label class="form-label">Cancellation Policy</label>
                        <select name="cancellation_policy" class="form-select">
                            <option value="flexible" <?php echo ($settings['cancellation_policy'] ?? '') === 'flexible' ? 'selected' : ''; ?>>
                                Flexible (Full refund 24h before)
                            </option>
                            <option value="moderate" <?php echo ($settings['cancellation_policy'] ?? 'moderate') === 'moderate' ? 'selected' : ''; ?>>
                                Moderate (Full refund 5 days before)
                            </option>
                            <option value="strict" <?php echo ($settings['cancellation_policy'] ?? '') === 'strict' ? 'selected' : ''; ?>>
                                Strict (50% refund up to 1 week before)
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- House Rules -->
        <div class="card mb-4" style="position: relative; z-index: 1;">
            <div class="card-header text-white" style="background-color: #e69406;">
                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> House Rules</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">House Rules (displayed to guests)</label>
                    <textarea name="house_rules" class="form-control" rows="6" placeholder="Enter house rules here..."><?php echo htmlspecialchars($settings['house_rules'] ?? ''); ?></textarea>
                    <small class="text-muted">These rules will be shown to guests when they book.</small>
                </div>
            </div>
        </div>

        <!-- Unit Rates -->
        <div class="card mb-4">
            <div class="card-header text-white" style="background-color: #ff8c00;">
                <h5 class="mb-0"><i class="fas fa-door-open"></i> Unit Rates & Airbnb Eligibility</h5>
            </div>
            <div class="card-body">
                <?php if (empty($units)): ?>
                    <div class="alert alert-info">No units found for this property.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Type</th>
                                    <th>Airbnb Eligible</th>
                                    <th>Base Price/Night (KES)</th>
                                    <th>Weekend Price (KES)</th>
                                    <th>Weekly Discount (%)</th>
                                    <th>Monthly Discount (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($units as $unit): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($unit['unit_number']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($unit['type'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                name="unit_eligible[<?php echo $unit['id']; ?>]" 
                                                value="1"
                                                <?php echo (!empty($unit['is_airbnb_eligible'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Eligible</label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" 
                                            name="unit_rates[<?php echo $unit['id']; ?>][base_price]" 
                                            value="<?php echo htmlspecialchars($unit['airbnb_rates']['base_price_per_night'] ?? $unit['rent_amount'] ?? '0'); ?>" 
                                            step="0.01" min="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" 
                                            name="unit_rates[<?php echo $unit['id']; ?>][weekend_price]" 
                                            value="<?php echo htmlspecialchars($unit['airbnb_rates']['weekend_price'] ?? ''); ?>" 
                                            step="0.01" min="0" placeholder="Same as base">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" 
                                            name="unit_rates[<?php echo $unit['id']; ?>][weekly_discount]" 
                                            value="<?php echo htmlspecialchars($unit['airbnb_rates']['weekly_discount_percent'] ?? '0'); ?>" 
                                            step="0.01" min="0" max="100">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" 
                                            name="unit_rates[<?php echo $unit['id']; ?>][monthly_discount]" 
                                            value="<?php echo htmlspecialchars($unit['airbnb_rates']['monthly_discount_percent'] ?? '0'); ?>" 
                                            step="0.01" min="0" max="100">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-4">
            <a href="<?php echo BASE_URL; ?>/airbnb/property-settings" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
