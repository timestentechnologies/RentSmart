<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Booking #<?php echo htmlspecialchars($booking['booking_reference']); ?></h2>
            <small class="text-muted">Created: <?php echo date('F d, Y H:i', strtotime($booking['created_at'])); ?></small>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/airbnb/bookings" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Guest Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Guest Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Name</small>
                            <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Phone</small>
                            <strong><?php echo htmlspecialchars($booking['guest_phone']); ?></strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Email</small>
                            <strong><?php echo $booking['guest_email'] ? htmlspecialchars($booking['guest_email']) : '-'; ?></strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Number of Guests</small>
                            <strong><?php echo $booking['guest_count']; ?> person(s)</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Booking Source</small>
                            <strong><?php echo ucfirst($booking['booking_source']); ?></strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Booked By</small>
                            <strong><?php echo $booking['booked_by_name'] ?? 'Online'; ?></strong>
                        </div>
                    </div>
                    <?php if ($booking['special_requests']): ?>
                    <hr>
                    <div class="mb-0">
                        <small class="text-muted d-block">Special Requests</small>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stay Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Stay Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Property</small>
                            <strong><?php echo htmlspecialchars($booking['property_name']); ?></strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Unit</small>
                            <strong><?php echo htmlspecialchars($booking['unit_number']); ?></strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Check-in Date</small>
                            <strong><?php echo date('l, F d, Y', strtotime($booking['check_in_date'])); ?></strong>
                            <small class="text-muted">at <?php echo date('g:i A', strtotime($booking['check_in_time'])); ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Check-out Date</small>
                            <strong><?php echo date('l, F d, Y', strtotime($booking['check_out_date'])); ?></strong>
                            <small class="text-muted">at <?php echo date('g:i A', strtotime($booking['check_out_time'])); ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Duration</small>
                            <strong><?php echo $booking['nights']; ?> night(s)</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-<?php 
                                echo $booking['status'] === 'checked_in' ? 'success' : 
                                    ($booking['status'] === 'checked_out' ? 'secondary' : 
                                    ($booking['status'] === 'pending' ? 'warning' : 
                                    ($booking['status'] === 'confirmed' ? 'primary' : 'danger'))); 
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($booking['actual_check_in']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Actual Check-in</small>
                            <strong><?php echo date('F d, Y g:i A', strtotime($booking['actual_check_in'])); ?></strong>
                        </div>
                        <?php if ($booking['actual_check_out']): ?>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Actual Check-out</small>
                            <strong><?php echo date('F d, Y g:i A', strtotime($booking['actual_check_out'])); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($booking['internal_notes']): ?>
                    <hr>
                    <div class="mb-0">
                        <small class="text-muted d-block">Internal Notes</small>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['internal_notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-money-bill me-2"></i>Payment History</h5>
                    <?php if ($booking['amount_paid'] < $booking['final_total'] && in_array($booking['status'], ['pending', 'confirmed', 'checked_in'])): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="fas fa-plus"></i> Add Payment
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <p class="text-muted mb-0">No payments recorded yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                        <td>KES <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                        <td><?php echo $payment['transaction_reference'] ?: '-'; ?></td>
                                        <td><?php echo $payment['notes'] ?: '-'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Price Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Price Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">KES <?php echo number_format($booking['price_per_night'], 2); ?> x <?php echo $booking['nights']; ?> nights</span>
                        <span>KES <?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                    <?php if ($booking['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount</span>
                        <span>-KES <?php echo number_format($booking['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Cleaning fee</span>
                        <span>KES <?php echo number_format($booking['cleaning_fee'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Security deposit</span>
                        <span>KES <?php echo number_format($booking['security_deposit'], 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold">KES <?php echo number_format($booking['final_total'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Paid</span>
                        <span class="text-success">KES <?php echo number_format($booking['amount_paid'], 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold <?php echo $booking['amount_paid'] >= $booking['final_total'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $booking['amount_paid'] >= $booking['final_total'] ? 'Fully Paid' : 'Balance Due'; ?>
                        </span>
                        <span class="fw-bold <?php echo $booking['amount_paid'] >= $booking['final_total'] ? 'text-success' : 'text-danger'; ?>">
                            KES <?php echo number_format($booking['final_total'] - $booking['amount_paid'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkin" class="btn btn-success w-100 mb-2" onclick="return confirm('Check in guest now?')">
                            <i class="fas fa-sign-in-alt me-2"></i>Check In Guest
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] === 'checked_in'): ?>
                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/checkout" class="btn btn-info w-100 mb-2" onclick="return confirm('Check out guest now?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Check Out Guest
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#editBookingModal">
                        <i class="fas fa-edit me-2"></i>Edit Booking
                    </button>
                    
                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                        <a href="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/cancel" class="btn btn-outline-danger w-100" onclick="return confirm('Are you sure you want to cancel this booking?')">
                            <i class="fas fa-times me-2"></i>Cancel Booking
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/payment" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount (KES)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" 
                               max="<?php echo $booking['final_total'] - $booking['amount_paid']; ?>" required>
                        <small class="text-muted">Balance due: KES <?php echo number_format($booking['final_total'] - $booking['amount_paid'], 2); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction Reference</label>
                        <input type="text" name="transaction_reference" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?php echo BASE_URL; ?>/airbnb/bookings/<?php echo $booking['id']; ?>/update" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guest Name</label>
                            <input type="text" name="guest_name" class="form-control" value="<?php echo htmlspecialchars($booking['guest_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guest Phone</label>
                            <input type="tel" name="guest_phone" class="form-control" value="<?php echo htmlspecialchars($booking['guest_phone']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guest Email</label>
                            <input type="email" name="guest_email" class="form-control" value="<?php echo htmlspecialchars($booking['guest_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Number of Guests</label>
                            <input type="number" name="guest_count" class="form-control" value="<?php echo $booking['guest_count']; ?>" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check-in Date</label>
                            <input type="date" name="check_in_date" class="form-control" value="<?php echo $booking['check_in_date']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check-out Date</label>
                            <input type="date" name="check_out_date" class="form-control" value="<?php echo $booking['check_out_date']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="checked_in" <?php echo $booking['status'] === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                <option value="checked_out" <?php echo $booking['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="partial" <?php echo $booking['payment_status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Special Requests</label>
                            <textarea name="special_requests" class="form-control" rows="2"><?php echo htmlspecialchars($booking['special_requests'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Internal Notes</label>
                            <textarea name="internal_notes" class="form-control" rows="2"><?php echo htmlspecialchars($booking['internal_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
