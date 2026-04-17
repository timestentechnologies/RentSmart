<?php
ob_start();
?>

<style>
    /* Airbnb Dashboard Specific Styles */
    .stat-card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    /* Left border for stat cards */
    .border-left-primary { border-left: 5px solid #0d6efd !important; }
    .border-left-success { border-left: 5px solid #198754 !important; }
    .border-left-info { border-left: 5px solid #0dcaf0 !important; }
    .border-left-warning { border-left: 5px solid #ffc107 !important; }
    .border-left-danger { border-left: 5px solid #dc3545 !important; }
    .border-left-secondary { border-left: 5px solid #6c757d !important; }

    /* Top border for table cards */
    .border-top-primary { border-top: 5px solid #0d6efd !important; }
    .border-top-success { border-top: 5px solid #198754 !important; }
    .border-top-warning { border-top: 5px solid #ffc107 !important; }
    .border-top-secondary { border-top: 5px solid #6c757d !important; }

    .card-header {
        background-color: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1.25rem;
    }

    .card-body {
        padding: 1.25rem;
    }

    .stat-card .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 120px;
    }

    /* Responsive action buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-buttons .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    /* Mobile view - stack buttons vertically with same width */
    @media (max-width: 575.98px) {
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }

        .action-buttons .btn {
            width: 100%;
            min-width: 140px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .action-buttons .btn i {
            margin-right: 0.25rem;
        }

        /* Remove margin from first button on mobile */
        .action-buttons .btn.me-2 {
            margin-right: 0 !important;
            margin-bottom: 0.5rem !important;
        }
    }

    /* Tablet and up - side by side */
    @media (min-width: 576px) {
        .action-buttons {
            flex-direction: row;
        }

        .action-buttons .btn {
            width: auto;
            min-width: 130px;
        }
    }

    /* Large screens - larger buttons */
    @media (min-width: 992px) {
        .action-buttons .btn {
            min-width: 150px;
            padding: 0.5rem 1.25rem;
        }
    }

    /* Mobile header layout */
    @media (max-width: 575.98px) {
        .page-header .card-body {
            flex-direction: column;
            align-items: stretch !important;
            gap: 1rem;
        }

        .page-header h1 {
            font-size: 1.25rem;
            text-align: center;
        }
    }

    @media (min-width: 576px) and (max-width: 767.98px) {
        .page-header .card-body {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h1 {
            font-size: 1.35rem;
        }
    }
</style>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Airbnb Management Dashboard</h1>
            <div class="action-buttons">
                <a href="<?= BASE_URL ?>/airbnb/bookings/create" class="btn btn-primary me-2 shadow-sm">
                    <i class="bi bi-plus-lg"></i> New Booking
                </a>
                <a href="<?= BASE_URL ?>/airbnb/walkin-guests/create" class="btn btn-outline-primary shadow-sm">
                    <i class="bi bi-person-plus"></i> Add Walk-in
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards - 3 per row -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card border-left-primary h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Today's Check-ins</h6>
                    <h2 class="text-primary mb-0 fw-bold"><?php echo count($upcomingCheckIns); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card border-left-success h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Today's Check-outs</h6>
                    <h2 class="text-success mb-0 fw-bold"><?php echo count($upcomingCheckOuts); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card border-left-info h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Occupancy Rate</h6>
                    <h2 class="text-info mb-0 fw-bold"><?php echo round($occupancyData['occupancy_rate'] ?? 0, 1); ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card border-left-warning h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Pending Walk-ins</h6>
                    <h2 class="text-warning mb-0 fw-bold"><?php echo count($pendingWalkins); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card border-left-danger h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Walk-in Conversion</h6>
                    <h2 class="text-danger mb-0 fw-bold"><?php echo round($walkinStats['conversion_rate'] ?? 0, 1); ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card border-left-secondary h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Active Bookings</h6>
                    <h2 class="text-secondary mb-0 fw-bold"><?php echo ($stats['checked_in'] ?? 0) + ($stats['confirmed'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Check-ins -->
        <div class="col-lg-6 mb-4">
            <div class="card border-top-primary h-100 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Today's Check-ins</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings?status=confirmed" class="btn btn-sm btn-outline-primary shadow-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingCheckIns)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted opacity-25" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No check-ins today</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                            <div class="fw-bold"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['guest_phone']); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($booking['property_name']); ?></div>
                                            <small class="text-muted">Unit <?php echo htmlspecialchars($booking['unit_number']); ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark fw-normal"><?php echo date('H:i', strtotime($booking['check_in_time'])); ?></span></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkin" class="btn btn-sm btn-success px-3 rounded-pill" onclick="return confirm('Check in guest now?')">
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
            <div class="card border-top-success h-100 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Today's Check-outs</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings?status=checked_in" class="btn btn-sm btn-outline-success shadow-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingCheckOuts)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted opacity-25" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No check-outs today</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                            <div class="fw-bold"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['guest_phone']); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($booking['property_name']); ?></div>
                                            <small class="text-muted">Unit <?php echo htmlspecialchars($booking['unit_number']); ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark fw-normal"><?php echo date('H:i', strtotime($booking['check_out_time'])); ?></span></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkout" class="btn btn-sm btn-info px-3 text-white rounded-pill" onclick="return confirm('Check out guest now?')">
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
            <div class="card border-top-warning h-100 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Pending Walk-in Inquiries</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests" class="btn btn-sm btn-outline-warning shadow-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingWalkins)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-left-dots text-muted opacity-25" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No pending walk-in inquiries</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                            <div class="fw-bold"><?php echo htmlspecialchars($guest['guest_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($guest['guest_phone']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($guest['property_name']); ?></td>
                                        <td>
                                            <?php if ($guest['follow_up_date']): ?>
                                                <span class="badge <?php echo strtotime($guest['follow_up_date']) < time() ? 'bg-danger-soft text-danger' : 'bg-light text-dark'; ?> fw-normal">
                                                    <?php echo date('M d, H:i', strtotime($guest['follow_up_date'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests/<?php echo $guest['id']; ?>/convert" class="btn btn-sm btn-primary px-3 rounded-pill">Convert</a>
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
            <div class="card border-top-secondary h-100 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Recent Bookings</h5>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings" class="btn btn-sm btn-outline-secondary shadow-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-journal-text text-muted opacity-25" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No recent bookings</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                            <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <?php echo date('M d', strtotime($booking['check_in_date'])); ?> - 
                                                <?php echo date('M d', strtotime($booking['check_out_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php 
                                                echo $booking['status'] === 'checked_in' ? 'success' : 
                                                    ($booking['status'] === 'checked_out' ? 'secondary' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 
                                                    ($booking['status'] === 'confirmed' ? 'primary' : 'danger'))); 
                                            ?> px-3">
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
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center border-0">
            <h5 class="mb-0 fw-bold">Your Airbnb Properties</h5>
            <a href="<?php echo BASE_URL; ?>/airbnb/property-settings" class="btn btn-sm btn-outline-primary px-3 rounded-pill">Manage Settings</a>
        </div>
        <div class="card-body">
            <?php if (empty($airbnbProperties)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-building text-muted opacity-25" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No Airbnb properties configured yet</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($airbnbProperties as $property): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm bg-light">
                            <div class="card-body">
                                <h6 class="card-title fw-bold"><?php echo htmlspecialchars($property['name']); ?></h6>
                                <p class="card-text text-muted small mb-0">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?php echo htmlspecialchars($property['address']); ?>, 
                                    <?php echo htmlspecialchars($property['city']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-white">
                                    <span class="badge rounded-pill bg-<?php echo $property['is_airbnb_enabled'] ? 'success' : 'secondary'; ?> px-3">
                                        <?php echo $property['is_airbnb_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                    <a href="<?php echo BASE_URL; ?>/airbnb/property-settings/<?php echo $property['id']; ?>" class="btn btn-sm btn-primary px-3 rounded-pill">
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
