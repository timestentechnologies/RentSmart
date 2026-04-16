<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Booking Confirmed | <?= htmlspecialchars($siteName); ?> | Airbnb Kenya</title>
    
    <?php $faviconUrl = site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png'); ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($faviconUrl) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6B3E99;
            --secondary-color: #8E5CC4;
            --accent-color: #FF8A00;
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
        }
        body { font-family: 'Inter', sans-serif; color: var(--dark-color); background-color: #fbfbff; }
        .confirmation-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .success-icon {
            width: 70px;
            height: 70px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(255, 138, 0, 0.2);
        }
        .booking-reference {
            background: #fff;
            border: 2px dashed var(--accent-color);
            border-radius: 12px;
            padding: 0.75rem;
            text-align: center;
        }
        .booking-reference code {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--accent-color);
            letter-spacing: 2px;
        }
        .info-box {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            height: 100%;
        }
        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        
        .payment-card {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        .payment-card:hover {
            border-color: var(--accent-color);
            background: rgba(255, 138, 0, 0.05);
        }
        .payment-card.active {
            border-color: var(--accent-color);
            background: rgba(255, 138, 0, 0.1);
        }
        .payment-card i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }
        .btn-brand {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            padding: 0.6rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-brand:hover {
            background: #e67c00;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 138, 0, 0.3);
        }
        .text-accent { color: var(--accent-color) !important; }
        .bg-accent-soft { background: rgba(255, 138, 0, 0.1); }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../partials/public_header.php'; ?>

    <div class="container confirmation-container py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="success-icon m-0">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 h3">Booking Request Received!</h2>
                        <p class="text-muted mb-0">Your reservation details are below.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="booking-reference">
                    <small class="text-muted d-block mb-1">Booking Reference</small>
                    <code><?php echo htmlspecialchars($booking['booking_reference']); ?></code>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Stay Details -->
            <div class="col-md-4">
                <div class="info-box">
                    <h6 class="mb-3 text-uppercase text-muted small fw-bold"><i class="fas fa-calendar-alt text-accent me-2"></i>Stay Details</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Check-in</small>
                        <span class="fw-bold"><?php echo date('D, M j, Y', strtotime($booking['check_in_date'])); ?></span>
                        <div class="small text-muted"><?php echo date('g:i A', strtotime($booking['check_in_time'])); ?> onwards</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Check-out</small>
                        <span class="fw-bold"><?php echo date('D, M j, Y', strtotime($booking['check_out_date'])); ?></span>
                        <div class="small text-muted">by <?php echo date('g:i A', strtotime($booking['check_out_time'])); ?></div>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Duration</small>
                        <span class="badge bg-accent-soft text-accent"><?php echo $booking['nights']; ?> Night(s)</span>
                    </div>
                </div>
            </div>

            <!-- Guest Details -->
            <div class="col-md-4">
                <div class="info-box">
                    <h6 class="mb-3 text-uppercase text-muted small fw-bold"><i class="fas fa-user text-accent me-2"></i>Guest Information</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Lead Guest</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Phone</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Travelers</small>
                        <span class="fw-bold"><?php echo $booking['guest_count']; ?> Person(s)</span>
                    </div>
                </div>
            </div>

            <!-- Unit Details -->
            <div class="col-md-4">
                <div class="info-box">
                    <h6 class="mb-3 text-uppercase text-muted small fw-bold"><i class="fas fa-home text-accent me-2"></i>Property</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Property Name</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($booking['property_name']); ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Unit Number</small>
                        <span class="fw-bold">Unit #<?php echo htmlspecialchars($booking['unit_number']); ?></span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Location</small>
                        <span class="small"><?php echo htmlspecialchars($booking['address'] . ', ' . $booking['city']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3 g-3">
            <!-- Price Breakdown -->
            <div class="col-md-6">
                <div class="info-box">
                    <h6 class="mb-3 text-uppercase text-muted small fw-bold"><i class="fas fa-receipt text-accent me-2"></i>Price Summary</h6>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Stay (<?php echo $booking['nights']; ?> nights)</span>
                        <span>KES <?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                    <?php if ($booking['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 small text-success">
                        <span>Discount Applied</span>
                        <span>-KES <?php echo number_format($booking['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Cleaning Fee</span>
                        <span>KES <?php echo number_format($booking['cleaning_fee'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Security Deposit (Refundable)</span>
                        <span>KES <?php echo number_format($booking['security_deposit'], 2); ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Payable</span>
                        <h4 class="text-accent mb-0">KES <?php echo number_format($booking['final_total'], 2); ?></h4>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="col-md-6">
                <div class="info-box bg-light border-0">
                    <h6 class="mb-3 text-uppercase text-muted small fw-bold"><i class="fas fa-credit-card text-accent me-2"></i>Secure Your Booking</h6>
                    <p class="small text-muted mb-3">Select your preferred payment method to finalize the reservation.</p>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="payment-card" data-method="M-Pesa">
                                <i class="fas fa-mobile-alt"></i>
                                <span class="small fw-bold">M-Pesa</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="payment-card active" data-method="Pay at Office">
                                <i class="fas fa-building"></i>
                                <span class="small fw-bold">Pay at Office</span>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-brand w-100" id="btnConfirmPayment">
                        Confirm & Reserve
                    </button>
                    
                    <div id="paymentStatus" class="mt-2 text-center small d-none">
                        <span class="spinner-border spinner-border-sm text-accent me-1"></span> Processing...
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-2">
            <div class="col-md-3">
                <a href="<?php echo BASE_URL; ?>/airbnb/booking-confirmation/<?php echo $booking['booking_reference']; ?>/download-receipt" class="btn btn-outline-brand w-100">
                    <i class="fas fa-file-pdf me-1"></i> Download PDF
                </a>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100" id="btnWhatsAppShare">
                    <i class="fab fa-whatsapp me-1"></i> Share WhatsApp
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-light w-100" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Page
                </button>
            </div>
            <div class="col-md-3">
                <a href="<?php echo BASE_URL; ?>/airbnb" class="btn btn-link text-muted text-decoration-none w-100">
                    <i class="fas fa-chevron-left me-1"></i> Back to Home
                </a>
            </div>
        </div>

        <!-- Footer Notes -->
        <div class="mt-4 border-top pt-4">
            <h6 class="text-muted small fw-bold mb-3">GUEST GUIDELINES</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <ul class="small text-muted ps-3 mb-0">
                        <li>Present original ID/Passport upon check-in.</li>
                        <li>Check-in is from <?php echo date('g:i A', strtotime($booking['check_in_time'])); ?>.</li>
                        <li>Maximum of <?php echo $booking['guest_count']; ?> guests allowed.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="small text-muted ps-3 mb-0">
                        <li>Strictly No Smoking inside the property.</li>
                        <li>Security deposit is partially/fully refundable after inspection.</li>
                        <li>Quiet hours start from 10:00 PM.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/public_footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedMethod = 'Pay at Office';

            $('.payment-card').click(function() {
                $('.payment-card').removeClass('active');
                $(this).addClass('active');
                selectedMethod = $(this).data('method');
            });

            $('#btnWhatsAppShare').click(function() {
                const reference = '<?php echo $booking['booking_reference']; ?>';
                const property = '<?php echo addslashes($booking['property_name']); ?>';
                const total = '<?php echo number_format($booking['final_total'], 2); ?>';
                const checkIn = '<?php echo date('M d', strtotime($booking['check_in_date'])); ?>';
                const pdfUrl = window.location.origin + '<?= BASE_URL ?>/airbnb/booking-confirmation/' + reference + '/download-receipt';
                
                const text = `*Booking Confirmation - ${property}*\n` +
                             `Reference: ${reference}\n` +
                             `Check-in: ${checkIn}\n` +
                             `Total: KES ${total}\n\n` +
                             `Download your receipt here: ${pdfUrl}`;
                
                window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
            });

            $('#btnConfirmPayment').click(function() {
                const btn = $(this);
                const status = $('#paymentStatus');
                const reference = '<?php echo $booking['booking_reference']; ?>';

                btn.prop('disabled', true);
                status.removeClass('d-none');

                $.ajax({
                    url: '<?= BASE_URL ?>/airbnb/booking-confirmation/' + reference + '/capture-payment',
                    method: 'POST',
                    data: {
                        method: selectedMethod,
                        csrf_token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Booking Confirmed! Your receipt has been generated and sent to your email.');
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            btn.prop('disabled', false);
                            status.addClass('d-none');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again or contact support.');
                        btn.prop('disabled', false);
                        status.addClass('d-none');
                    }
                });
            });
        });
    </script>
</body>
</html>

