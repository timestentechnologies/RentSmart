<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Airbnb Bookings</h1>
            <a href="<?= BASE_URL ?>/airbnb/bookings/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Booking
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/airbnb/bookings" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo ($_GET['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo ($_GET['status'] ?? '') === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo ($_GET['status'] ?? '') === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Check-in From</label>
                    <input type="date" name="check_in_from" class="form-control" value="<?php echo $_GET['check_in_from'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Check-in To</label>
                    <input type="date" name="check_in_to" class="form-control" value="<?php echo $_GET['check_in_to'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Guest Name</label>
                    <input type="text" name="guest_name" class="form-control" placeholder="Search by name..." value="<?php echo $_GET['guest_name'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
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
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No bookings found</h5>
                    <p class="text-muted">Create a new booking to get started</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Guest</th>
                                <th>Property / Unit</th>
                                <th>Dates</th>
                                <th>Nights</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>" class="fw-bold">
                                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['guest_phone']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['property_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['unit_number']); ?></small>
                                </td>
                                <td>
                                    <small class="text-muted">In:</small> <?php echo date('M d', strtotime($booking['check_in_date'])); ?><br>
                                    <small class="text-muted">Out:</small> <?php echo date('M d', strtotime($booking['check_out_date'])); ?>
                                </td>
                                <td><?php echo $booking['nights']; ?></td>
                                <td>KES <?php echo number_format($booking['final_total'], 2); ?></td>
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
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['payment_status'] === 'paid' ? 'success' : 
                                            ($booking['payment_status'] === 'partial' ? 'warning' : 
                                            ($booking['payment_status'] === 'refunded' ? 'info' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span><br>
                                    <small class="text-muted">KES <?php echo number_format($booking['amount_paid'], 2); ?> / KES <?php echo number_format($booking['final_total'], 2); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkin" class="btn btn-sm btn-success" title="Check In" onclick="return confirm('Check in guest now?')">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['status'] === 'checked_in'): ?>
                                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkout" class="btn btn-sm btn-info" title="Check Out" onclick="return confirm('Check out guest now?')">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/cancel" class="btn btn-sm btn-outline-danger" title="Cancel" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
