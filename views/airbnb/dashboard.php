<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Airbnb Management Dashboard</h1>
            <div>
                <a href="<?= BASE_URL ?>/airbnb/bookings/create" class="btn btn-primary me-2">
                    <i class="bi bi-plus-lg"></i> New Booking
                </a>
                <a href="<?= BASE_URL ?>/airbnb/walkin-guests/create" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus"></i> Add Walk-in
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Today's Check-ins</h6>
                    <h3 class="text-primary mb-0"><?php echo count($upcomingCheckIns); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Today's Check-outs</h6>
                    <h3 class="text-success mb-0"><?php echo count($upcomingCheckOuts); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Occupancy Rate</h6>
                    <h3 class="text-info mb-0"><?php echo round($occupancyData['occupancy_rate'] ?? 0, 1); ?>%</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Pending Walk-ins</h6>
                    <h3 class="text-warning mb-0"><?php echo count($pendingWalkins); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Walk-in Conversion</h6>
                    <h3 class="text-danger mb-0"><?php echo round($walkinStats['conversion_rate'] ?? 0, 1); ?>%</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Active Bookings</h6>
                    <h3 class="text-secondary mb-0"><?php echo ($stats['checked_in'] ?? 0) + ($stats['confirmed'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Check-ins -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Today's Check-ins</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings?status=confirmed" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingCheckIns)): ?>
                        <p class="text-muted text-center py-3">No check-ins today</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Unit</th>
                                        <th>Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingCheckIns as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['guest_phone']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['property_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['unit_number']); ?></small>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($booking['check_in_time'])); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkin" class="btn btn-sm btn-success" onclick="return confirm('Check in guest now?')">
                                                Check In
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Check-outs -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Today's Check-outs</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings?status=checked_in" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingCheckOuts)): ?>
                        <p class="text-muted text-center py-3">No check-outs today</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Unit</th>
                                        <th>Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingCheckOuts as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['guest_phone']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['property_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['unit_number']); ?></small>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($booking['check_out_time'])); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkout" class="btn btn-sm btn-info" onclick="return confirm('Check out guest now?')">
                                                Check Out
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Walk-ins -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Walk-in Inquiries</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingWalkins)): ?>
                        <p class="text-muted text-center py-3">No pending walk-in inquiries</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Property</th>
                                        <th>Follow-up</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingWalkins as $guest): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($guest['guest_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($guest['guest_phone']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($guest['property_name']); ?></td>
                                        <td>
                                            <?php if ($guest['follow_up_date']): ?>
                                                <span class="<?php echo strtotime($guest['follow_up_date']) < time() ? 'text-danger' : 'text-muted'; ?>">
                                                    <?php echo date('M d, H:i', strtotime($guest['follow_up_date'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests/<?php echo $guest['id']; ?>/convert" class="btn btn-sm btn-primary">Convert</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Bookings</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                        <p class="text-muted text-center py-3">No recent bookings</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Guest</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>">
                                                <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('M d', strtotime($booking['check_in_date'])); ?> - 
                                            <?php echo date('M d', strtotime($booking['check_out_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] === 'checked_in' ? 'success' : 
                                                    ($booking['status'] === 'checked_out' ? 'secondary' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 
                                                    ($booking['status'] === 'confirmed' ? 'primary' : 'danger'))); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Airbnb Properties -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Your Airbnb Properties</h5>
            <a href="<?php echo BASE_URL; ?>/airbnb/property-settings" class="btn btn-sm btn-outline-primary">Manage Settings</a>
        </div>
        <div class="card-body">
            <?php if (empty($airbnbProperties)): ?>
                <p class="text-muted text-center py-3">No Airbnb properties configured yet</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($airbnbProperties as $property): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($property['name']); ?></h6>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($property['address']); ?>, 
                                    <?php echo htmlspecialchars($property['city']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="badge bg-<?php echo $property['is_airbnb_enabled'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $property['is_airbnb_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                    <a href="<?php echo BASE_URL; ?>/airbnb/property-settings/<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
