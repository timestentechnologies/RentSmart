<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - <?php echo htmlspecialchars($siteName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
        }
        .booking-reference {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .booking-reference code {
            font-size: 1.5rem;
            font-weight: bold;
            color: #212529;
        }
        .info-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
    </style>
    <?php if ($favicon): ?>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL . '/public/assets/images/' . $favicon; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <?php if ($siteLogo): ?>
                <img src="<?php echo $siteLogo; ?>" alt="<?php echo htmlspecialchars($siteName); ?>" height="40">
                <?php else: ?>
                <?php echo htmlspecialchars($siteName); ?>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <div class="container confirmation-container py-5">
        <div class="text-center mb-5">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="mb-3">Booking Request Received!</h2>
            <p class="text-muted">Thank you for your booking. We'll contact you shortly to confirm.</p>
        </div>

        <!-- Booking Reference -->
        <div class="booking-reference mb-4">
            <p class="text-muted mb-2">Your Booking Reference</p>
            <code><?php echo htmlspecialchars($booking['booking_reference']); ?></code>
            <p class="small text-muted mt-2">Please save this reference for your records</p>
        </div>

        <div class="row g-4">
            <!-- Booking Details -->
            <div class="col-md-6">
                <div class="info-box">
                    <h5 class="mb-3"><i class="fas fa-calendar-alt text-primary me-2"></i>Stay Details</h5>
                    <div class="mb-3">
                        <small class="text-muted d-block">Check-in</small>
                        <strong><?php echo date('l, F j, Y', strtotime($booking['check_in_date'])); ?></strong>
                        <span class="text-muted">at <?php echo date('g:i A', strtotime($booking['check_in_time'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Check-out</small>
                        <strong><?php echo date('l, F j, Y', strtotime($booking['check_out_date'])); ?></strong>
                        <span class="text-muted">at <?php echo date('g:i A', strtotime($booking['check_out_time'])); ?></span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Duration</small>
                        <strong><?php echo $booking['nights']; ?> night(s)</strong>
                    </div>
                </div>
            </div>

            <!-- Guest Details -->
            <div class="col-md-6">
                <div class="info-box">
                    <h5 class="mb-3"><i class="fas fa-user text-primary me-2"></i>Guest Information</h5>
                    <div class="mb-3">
                        <small class="text-muted d-block">Name</small>
                        <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Phone</small>
                        <strong><?php echo htmlspecialchars($booking['guest_phone']); ?></strong>
                    </div>
                    <?php if ($booking['guest_email']): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Email</small>
                        <strong><?php echo htmlspecialchars($booking['guest_email']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="mb-0">
                        <small class="text-muted d-block">Guests</small>
                        <strong><?php echo $booking['guest_count']; ?> person(s)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Price Summary -->
        <div class="info-box mt-4">
            <h5 class="mb-3"><i class="fas fa-receipt text-primary me-2"></i>Price Summary</h5>
            <div class="row">
                <div class="col-md-6">
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
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong class="text-primary">KES <?php echo number_format($booking['final_total'], 2); ?></strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Payment Information</h6>
                        <p class="mb-0 small">
                            Payment will be collected during check-in. Please have the total amount ready.
                            The security deposit will be refunded at check-out after property inspection.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="text-center mt-4">
            <p class="mb-2">Booking Status</p>
            <span class="status-badge status-<?php echo $booking['status']; ?>">
                <?php echo ucfirst($booking['status']); ?>
            </span>
            <?php if ($booking['status'] === 'pending'): ?>
            <p class="text-muted small mt-2">We're reviewing your booking and will confirm shortly.</p>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-5">
            <a href="<?php echo BASE_URL; ?>/airbnb" class="btn btn-outline-primary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Listings
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Confirmation
            </button>
        </div>

        <!-- Important Notes -->
        <div class="alert alert-warning mt-5">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h6>
            <ul class="mb-0">
                <li>Please bring a valid ID for check-in</li>
                <li>Cancellation must be made at least 24 hours before check-in</li>
                <li>Late check-out may incur additional charges</li>
                <li>No smoking inside the property</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo BASE_URL; ?>/privacy-policy" class="text-muted small me-3">Privacy Policy</a>
                    <a href="<?php echo BASE_URL; ?>/terms" class="text-muted small">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
