<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Airbnb Units</h1>
            <div>
                <a href="<?php echo BASE_URL; ?>/airbnb/property-settings" class="btn btn-outline-primary me-2">
                    <i class="fas fa-cog"></i> Configure Rates
                </a>
                <a href="<?php echo BASE_URL; ?>/airbnb/dashboard" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/airbnb/units" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        <?php foreach ($properties as $property): ?>
                        <option value="<?php echo $property['id']; ?>" <?php echo ($_GET['property_id'] ?? '') == $property['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($property['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Airbnb Eligibility</label>
                    <select name="eligible" class="form-select">
                        <option value="">All Units</option>
                        <option value="yes" <?php echo ($_GET['eligible'] ?? '') === 'yes' ? 'selected' : ''; ?>>Eligible Only</option>
                        <option value="no" <?php echo ($_GET['eligible'] ?? '') === 'no' ? 'selected' : ''; ?>>Not Eligible</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>/airbnb/units" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Units Grid -->
    <div class="row">
        <?php if (empty($allUnits)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No units found</h5>
                        <p class="text-muted">No units are available for the selected filters.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($allUnits as $unit): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 <?php echo !empty($unit['is_airbnb_eligible']) ? 'border-success' : 'border-secondary'; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-door-closed"></i> 
                            <?php echo htmlspecialchars($unit['unit_number']); ?>
                        </h5>
                        <?php if (!empty($unit['is_airbnb_eligible'])): ?>
                            <span class="badge bg-success">Airbnb Eligible</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Not Eligible</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?php echo htmlspecialchars($unit['property']['name']); ?>
                        </h6>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($unit['property']['city']); ?>
                            </small>
                        </p>

                        <!-- Pricing Info -->
                        <div class="mb-3">
                            <strong>Base Price:</strong> 
                            <?php if (!empty($unit['airbnb_rates']['base_price_per_night'])): ?>
                                KES <?php echo number_format($unit['airbnb_rates']['base_price_per_night'], 2); ?> / night
                            <?php else: ?>
                                <span class="text-warning">Not set</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($unit['airbnb_rates'])): ?>
                        <div class="small text-muted mb-2">
                            <?php if ($unit['airbnb_rates']['weekend_price']): ?>
                                Weekend: KES <?php echo number_format($unit['airbnb_rates']['weekend_price'], 2); ?><br>
                            <?php endif; ?>
                            <?php if ($unit['airbnb_rates']['weekly_discount_percent'] > 0): ?>
                                Weekly discount: <?php echo $unit['airbnb_rates']['weekly_discount_percent']; ?>%<br>
                            <?php endif; ?>
                            <?php if ($unit['airbnb_rates']['monthly_discount_percent'] > 0): ?>
                                Monthly discount: <?php echo $unit['airbnb_rates']['monthly_discount_percent']; ?>%
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Upcoming Bookings -->
                        <div class="mt-3">
                            <strong>Upcoming Bookings:</strong>
                            <?php if (empty($unit['upcoming_bookings'])): ?>
                                <span class="text-success">Available</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><?php echo count($unit['upcoming_bookings']); ?> upcoming</span>
                                <div class="small mt-1">
                                    <?php foreach (array_slice($unit['upcoming_bookings'], 0, 2) as $booking): ?>
                                        <div class="text-muted">
                                            <?php echo date('M d', strtotime($booking['check_in_date'])); ?> - 
                                            <?php echo date('M d', strtotime($booking['check_out_date'])); ?>
                                            <span class="badge bg-<?php echo $booking['status'] === 'checked_in' ? 'success' : 'primary'; ?> btn-xs">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($unit['upcoming_bookings']) > 2): ?>
                                        <div class="text-muted">+<?php echo count($unit['upcoming_bookings']) - 2; ?> more</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/airbnb/property-settings/<?php echo $unit['property_id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit Rates
                            </a>
                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/create?unit_id=<?php echo $unit['id']; ?>&property_id=<?php echo $unit['property_id']; ?>" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> New Booking
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
