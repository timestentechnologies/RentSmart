<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($unit['unit_number']); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .unit-image {
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
        }
        .price-breakdown {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .price-total {
            border-top: 2px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: bold;
        }
        .availability-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .available {
            background: #d4edda;
            color: #155724;
        }
        .unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        .info-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 12px;
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
            <a href="<?php echo BASE_URL; ?>/airbnb" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Listings
            </a>
        </div>
    </nav>

    <div class="container booking-container py-4">
        <?php if (isset($_SESSION['airbnb_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['airbnb_error']; unset($_SESSION['airbnb_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column - Unit Details -->
            <div class="col-lg-8">
                <h2 class="mb-3"><?php echo htmlspecialchars($property['name']); ?></h2>
                <p class="text-muted mb-4">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <?php echo htmlspecialchars($property['address']); ?>, 
                    <?php echo htmlspecialchars($property['city']); ?>, 
                    <?php echo htmlspecialchars($property['state']); ?>
                </p>

                <!-- Unit Images -->
                <div class="mb-4">
                    <?php if (!empty($unit['images'])): ?>
                        <img src="<?php echo $unit['images'][0]['url']; ?>" class="unit-image w-100" alt="<?php echo htmlspecialchars($unit['unit_number']); ?>">
                    <?php else: ?>
                        <div class="unit-image bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-bed fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Unit Info -->
                <div class="card info-card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-bed text-primary me-2"></i>
                                <strong><?php echo htmlspecialchars($unit['unit_number']); ?></strong>
                                <small class="text-muted d-block">Unit Number</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-home text-primary me-2"></i>
                                <strong><?php echo htmlspecialchars($unit['type']); ?></strong>
                                <small class="text-muted d-block">Type</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <strong><?php echo date('g:i A', strtotime($airbnbSettings['check_in_time'] ?? '14:00:00')); ?></strong>
                                <small class="text-muted d-block">Check-in</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Availability Status -->
                <?php if ($isAvailable !== null): ?>
                <div class="mb-4">
                    <h5>Availability</h5>
                    <div class="availability-badge <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                        <i class="fas <?php echo $isAvailable ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                        <?php echo $isAvailable ? 'Available for selected dates' : 'Not available for selected dates'; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- House Rules -->
                <?php if (!empty($airbnbSettings['house_rules'])): ?>
                <div class="card info-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clipboard-list me-2"></i>House Rules</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($airbnbSettings['house_rules'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Caretaker Info -->
                <?php if (!empty($property['caretaker_name'])): ?>
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-tie me-2"></i>Property Manager</h5>
                        <p class="card-text mb-1"><strong><?php echo htmlspecialchars($property['caretaker_name']); ?></strong></p>
                        <?php if (!empty($property['caretaker_contact'])): ?>
                        <p class="card-text text-muted">
                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($property['caretaker_contact']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Booking Form -->
            <div class="col-lg-4">
                <div class="card info-card sticky-top" style="top: 20px; z-index: 100;">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Book This Room</h4>

                        <form action="<?php echo BASE_URL; ?>/airbnb/submit-booking" method="POST" id="bookingForm">
                            <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">

                            <!-- Date Selection -->
                            <div class="mb-3">
                                <label class="form-label">Dates</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" name="check_in" class="form-control" id="checkIn" 
                                               value="<?php echo $checkIn; ?>" required>
                                        <small class="text-muted">Check-in</small>
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="check_out" class="form-control" id="checkOut"
                                               value="<?php echo $checkOut; ?>" required>
                                        <small class="text-muted">Check-out</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Guests -->
                            <div class="mb-3">
                                <label class="form-label">Guests</label>
                                <select name="guest_count" class="form-select" id="guestCount">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> guest<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Guest Details -->
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="guest_name" class="form-control" required 
                                       placeholder="Enter your full name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="guest_phone" class="form-control" required 
                                       placeholder="e.g., 0712345678">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email (optional)</label>
                                <input type="email" name="guest_email" class="form-control" 
                                       placeholder="your@email.com">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Special Requests</label>
                                <textarea name="special_requests" class="form-control" rows="3" 
                                          placeholder="Any special requirements..."></textarea>
                            </div>

                            <!-- Price Breakdown -->
                            <?php if ($price): ?>
                            <div class="price-breakdown mb-3">
                                <div class="price-row">
                                    <span>KES <?php echo number_format($price['base_total'] / $price['nights'], 2); ?> x <?php echo $price['nights']; ?> nights</span>
                                    <span>KES <?php echo number_format($price['base_total'], 2); ?></span>
                                </div>
                                <?php if ($price['discount'] > 0): ?>
                                <div class="price-row text-success">
                                    <span>Discount</span>
                                    <span>-KES <?php echo number_format($price['discount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="price-row">
                                    <span>Cleaning fee</span>
                                    <span>KES <?php echo number_format($price['cleaning_fee'], 2); ?></span>
                                </div>
                                <div class="price-row">
                                    <span>Security deposit</span>
                                    <span>KES <?php echo number_format($price['security_deposit'], 2); ?></span>
                                </div>
                                <div class="price-row price-total">
                                    <span>Total</span>
                                    <span>KES <?php echo number_format($price['final_total'], 2); ?></span>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Select dates to see pricing
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary w-100 py-3" 
                                    <?php echo ($isAvailable !== null && !$isAvailable) ? 'disabled' : ''; ?>>
                                <?php echo ($isAvailable !== null && !$isAvailable) ? 'Not Available' : 'Request to Book'; ?>
                            </button>

                            <p class="text-muted small mt-2 text-center">
                                <i class="fas fa-info-circle me-1"></i>
                                You won't be charged yet
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <p class="text-muted text-center mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const checkIn = document.getElementById('checkIn');
            const checkOut = document.getElementById('checkOut');
            
            checkIn.setAttribute('min', today);
            checkOut.setAttribute('min', today);
            
            checkIn.addEventListener('change', function() {
                checkOut.setAttribute('min', this.value);
                if (checkOut.value && checkOut.value < this.value) {
                    checkOut.value = '';
                }
                // Refresh page with new dates
                if (this.value && checkOut.value) {
                    window.location.href = '<?php echo BASE_URL; ?>/airbnb/book?unit_id=<?php echo $unit['id']; ?>&check_in=' + this.value + '&check_out=' + checkOut.value + '&guests=' + document.getElementById('guestCount').value;
                }
            });
            
            checkOut.addEventListener('change', function() {
                if (checkIn.value && this.value) {
                    window.location.href = '<?php echo BASE_URL; ?>/airbnb/book?unit_id=<?php echo $unit['id']; ?>&check_in=' + checkIn.value + '&check_out=' + this.value + '&guests=' + document.getElementById('guestCount').value;
                }
            });

            document.getElementById('guestCount').addEventListener('change', function() {
                if (checkIn.value && checkOut.value) {
                    window.location.href = '<?php echo BASE_URL; ?>/airbnb/book?unit_id=<?php echo $unit['id']; ?>&check_in=' + checkIn.value + '&check_out=' + checkOut.value + '&guests=' + this.value;
                }
            });
        });
    </script>
</body>
</html>
