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
                <div class="info-box bg-light border-2 border-accent-soft h-100">
                    <h6 class="mb-3 text-uppercase text-muted small fw-bold"><i class="fas fa-credit-card text-accent me-2"></i>Secure Your Booking</h6>
                    <p class="small text-muted mb-3">Select your preferred payment method to finalize the reservation.</p>
                    
                    <div class="row g-2 mb-3">
                        <?php 
                        $hasManualMethods = !empty($paymentMethods);
                        if ($hasManualMethods): 
                            foreach ($paymentMethods as $index => $pm):
                                $icon = 'fa-credit-card';
                                $pmName = strtolower($pm['name']);
                                if (strpos($pmName, 'm-pesa') !== false || in_array($pm['type'], ['mpesa_stk', 'mpesa_manual', 'mpesa_pochi', 'mpesa_send_money'])) $icon = 'fa-mobile-alt';
                                if (strpos($pmName, 'bank') !== false || $pm['type'] === 'bank_transfer') $icon = 'fa-university';
                                if (strpos($pmName, 'office') !== false || $pm['type'] === 'cash') $icon = 'fa-building';
                        ?>
                            <div class="col-6">
                                <div class="payment-card <?= $index === 0 ? 'active' : '' ?>" 
                                     data-method-id="<?= $pm['id'] ?>" 
                                     data-method-type="<?= $pm['type'] ?>"
                                     data-method-name="<?= htmlspecialchars($pm['name']) ?>"
                                     data-instructions="<?= htmlspecialchars($pm['description']) ?>"
                                     data-details='<?= htmlspecialchars($pm['details']) ?>'>
                                    <i class="fas <?= $icon ?>"></i>
                                    <span class="small fw-bold text-truncate d-block"><?= htmlspecialchars($pm['name']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>

                        <?php if ($allowOfficePayment): ?>
                            <div class="col-6">
                                <div class="payment-card <?= (!$hasManualMethods) ? 'active' : '' ?>" 
                                     data-method-name="Pay at Office" 
                                     data-method-type="cash"
                                     data-method-id="0">
                                    <i class="fas fa-building"></i>
                                    <span class="small fw-bold text-truncate d-block">Pay at Office</span>
                                </div>
                            </div>
                        <?php elseif (!$hasManualMethods): ?>
                            <div class="col-12">
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i> No payment methods available. Please contact support.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Hidden instructions helper for modal sync -->
                    <div id="mpesaInstructions" class="d-none"></div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-accent text-white">
                        <h5 class="modal-title fw-bold" id="paymentModalLabel">
                            <i class="fas fa-shield-alt me-2"></i>Complete Payment
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="display-6 text-accent mb-2">
                                <i id="modalMethodIcon" class="fas fa-credit-card"></i>
                            </div>
                            <h4 id="modalMethodName" class="fw-bold mb-1">Payment Method</h4>
                            <p class="text-muted small">Reference: <?= htmlspecialchars($booking['booking_reference']) ?></p>
                        </div>

                        <div id="modalInstructionsBox" class="bg-light rounded p-3 mb-4 text-center">
                            <div id="modalInstructions" class="fw-bold text-primary mb-0" style="white-space: pre-line;"></div>
                        </div>

                        <!-- M-Pesa Modal Fields -->
                        <div id="modalMpesaFields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">M-Pesa Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-phone-alt text-muted small"></i></span>
                                    <input type="text" id="modalMpesaPhone" class="form-control border-start-0 ps-0" placeholder="07XXXXXXXX" value="<?= htmlspecialchars($booking['guest_phone']) ?>">
                                </div>
                            </div>
                            <div id="modalManualMpesaFields" class="d-none">
                                <label class="form-label small fw-bold text-muted">Transaction Code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-muted small"></i></span>
                                    <input type="text" id="modalMpesaCode" class="form-control border-start-0 ps-0" placeholder="e.g. RKL7W8X9Y1" style="text-transform: uppercase;">
                                </div>
                                <div class="form-text mt-1 small">Enter the code received in your confirmation SMS.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 bg-light rounded p-2 px-3">
                            <span class="text-muted small">Total Payable:</span>
                            <span class="h5 fw-bold text-accent mb-0">KES <?= number_format($booking['final_total'], 2) ?></span>
                        </div>

                        <button class="btn btn-brand w-100 py-2 fw-bold" id="btnConfirmPayment">
                            Confirm & Complete Reservation
                        </button>
                        
                        <div id="paymentStatus" class="mt-3 text-center small d-none">
                            <div class="spinner-border spinner-border-sm text-accent me-2" role="status"></div>
                            <span id="paymentStatusText" class="fw-medium">Processing...</span>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 justify-content-center py-2">
                        <small class="text-muted"><i class="fas fa-lock me-1"></i> Secure Encrypted Transaction</small>
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
            // Initial selection logic
            let activeCard = $('.payment-card.active').first();
            if (activeCard.length === 0) {
                // If no card is active (edge case), activate the first one
                activeCard = $('.payment-card').first();
                activeCard.addClass('active');
            }

            let selectedMethodName = activeCard.data('method-name');
            let selectedMethodType = activeCard.data('method-type');
            let selectedMethodId = activeCard.data('method-id');

            function updatePaymentUI() {
                const card = $('.payment-card.active');
                if (!card.length) return;

                const type = card.data('method-type');
                const cardInstructions = card.data('instructions');
                let details = {};
                
                try {
                    const rawDetails = card.data('details');
                    if (rawDetails) {
                        details = (typeof rawDetails === 'object') ? rawDetails : JSON.parse(rawDetails);
                    }
                } catch (e) {
                    console.error('Error parsing payment details:', e);
                }
                
                $('#mpesaInstructions').text('');

                // Use card instructions if available (these are the auto-populated ones from admin)
                if (cardInstructions && cardInstructions.trim() !== '') {
                    $('#mpesaInstructions').text(cardInstructions);
                    return;
                }

                // Fallback for older methods or missing descriptions
                if (type === 'mpesa_manual') {
                    let instr = 'Follow instructions to pay: ';
                    if (details.mpesa_method === 'till') instr += 'Buy Goods Till ' + (details.till_number || '---');
                    else if (details.mpesa_method === 'paybill') instr += 'Paybill ' + (details.paybill_number || '---') + ' (Acc: ' + (details.account_number || 'STAY') + ')';
                    else instr += 'Manual M-Pesa';
                    $('#mpesaInstructions').text(instr);
                } else if (type === 'mpesa_stk') {
                    $('#mpesaInstructions').text('You will receive an M-Pesa prompt to enter your PIN.');
                }
            }

            function openPaymentModal() {
                const card = $('.payment-card.active');
                if (!card.length) return;

                const name = card.data('method-name');
                const type = card.data('method-type');
                let iconClass = 'fas fa-credit-card';
                if (card.find('i').length) {
                    iconClass = card.find('i').attr('class').split(' ').filter(c => c.startsWith('fa')).join(' ');
                }

                $('#modalMethodName').text(name);
                $('#modalMethodIcon').attr('class', iconClass + ' fa-3x');
                
                // Reset fields
                $('#modalMpesaFields').addClass('d-none');
                $('#modalManualMpesaFields').addClass('d-none');
                
                let instructions = $('#mpesaInstructions').text();
                if (type === 'cash') {
                    instructions = 'Payment can be settled at the property reception upon check-in.';
                } else if (!instructions) {
                    instructions = 'Please follow the prompts on your device to finalize the payment.';
                }
                $('#modalInstructions').text(instructions);

                if (['mpesa_manual', 'mpesa_stk', 'mpesa_pochi', 'mpesa_send_money'].includes(type)) {
                    $('#modalMpesaFields').removeClass('d-none');
                    if (['mpesa_manual', 'mpesa_pochi', 'mpesa_send_money'].includes(type)) $('#modalManualMpesaFields').removeClass('d-none');
                }

                const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                paymentModal.show();
            }
            
            // Initial UI update
            updatePaymentUI();

            $('.payment-card').click(function() {
                $('.payment-card').removeClass('active');
                $(this).addClass('active');
                selectedMethodName = $(this).data('method-name');
                selectedMethodType = $(this).data('method-type');
                selectedMethodId = $(this).data('method-id');
                console.log('Selected Method:', selectedMethodName, selectedMethodType);
                updatePaymentUI();
                openPaymentModal(); // Trigger modal on click
            });

            // Keep the function but button is gone
            // $('#btnOpenPaymentModal').click(function() { ... });

            $('#btnWhatsAppShare').click(function() {
                const reference = '<?php echo $booking['booking_reference']; ?>';
                const property = '<?php echo addslashes($booking['property_name']); ?>';
                const total = '<?php echo number_format($booking['final_total'], 2); ?>';
                const checkIn = '<?php echo date('M d', strtotime($booking['check_in_date'])); ?>';
                const confirmationUrl = window.location.href;
                
                const text = `*Booking Confirmation - ${property}*\n` +
                             `Reference: ${reference}\n` +
                             `Check-in: ${checkIn}\n` +
                             `Total: KES ${total}\n\n` +
                             `View Details & Receipt: ${confirmationUrl}`;
                
                window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
            });

            $('#btnConfirmPayment').click(function(e) {
                e.preventDefault();
                console.log('Confirm button clicked');
                
                const btn = $(this);
                const status = $('#paymentStatus');
                const statusText = $('#paymentStatusText');
                const reference = '<?php echo $booking['booking_reference']; ?>';

                // Basic validation for M-Pesa
                if (['mpesa_manual', 'mpesa_stk', 'mpesa_pochi', 'mpesa_send_money'].includes(selectedMethodType)) {
                    const phone = $('#modalMpesaPhone').val();
                    if (!phone) return alert('Phone number is required');
                    if (['mpesa_manual', 'mpesa_pochi', 'mpesa_send_money'].includes(selectedMethodType) && !$('#modalMpesaCode').val()) return alert('Transaction Code is required');
                }

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Completing...');
                status.removeClass('d-none');
                statusText.text(selectedMethodType === 'mpesa_stk' ? 'Sending prompt...' : 'Processing...');

                const commonData = {
                    method: selectedMethodName,
                    method_type: selectedMethodType,
                    method_id: selectedMethodId,
                    mpesa_phone: $('#modalMpesaPhone').val(),
                    mpesa_transaction_code: $('#modalMpesaCode').val(),
                    csrf_token: $('meta[name="csrf-token"]').attr('content')
                };

                // Handle STK Push specifically
                if (selectedMethodType === 'mpesa_stk') {
                    $.post('<?= BASE_URL ?>/airbnb/booking/initiate-stk', {
                        booking_id: '<?= $booking['id'] ?>',
                        phone_number: $('#mpesaPhone').val(),
                        amount: '<?= $booking['final_total'] ?>',
                        payment_method_id: selectedMethodId,
                        csrf_token: commonData.csrf_token
                    }, function(res) {
                        if (res.success) {
                            alert('STK Prompt sent! Please complete on your phone. We will verify and confirm your booking automatically.');
                            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                            window.location.reload();
                        } else {
                            alert('STK Error: ' + res.message);
                            btn.prop('disabled', false).text('Confirm & Complete Reservation');
                            status.addClass('d-none');
                        }
                    }, 'json').fail(function() {
                        alert('Connecton error while initiating STK.');
                        btn.prop('disabled', false).text('Confirm & Reserve');
                        status.addClass('d-none');
                    });
                    return;
                }

                // Default capture (Manual/Office/Other)
                $.ajax({
                    url: '<?= BASE_URL ?>/airbnb/booking-confirmation/' + reference + '/capture-payment',
                    method: 'POST',
                    data: commonData,
                    success: function(response) {
                        if (response.success) {
                            alert('Booking Reserved Successfully!');
                            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            btn.prop('disabled', false).text('Confirm & Complete Reservation');
                            status.addClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr.responseText);
                        alert('An error occurred. Please try again or contact support.');
                        btn.prop('disabled', false).text('Confirm & Complete Reservation');
                        status.addClass('d-none');
                    }
                });
            });
        });
    </script>
</body>
</html>

