<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Book <?= htmlspecialchars($unit['unit_number']); ?> | <?= htmlspecialchars($siteName); ?> | Airbnb Kenya</title>
    
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
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .unit-image {
            height: 400px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border-radius: 20px;
            background: white;
        }
        .amenity-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 138, 0, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            margin-right: 0.75rem;
        }
        .form-control, .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
            background-color: white;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236B3E99' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(107, 62, 153, 0.1);
        }
        /* Custom select dropdown styling */
        select option {
            background-color: white;
            color: var(--dark-color);
            padding: 0.75rem;
        }
        select option:hover,
        select option:focus,
        select option:active,
        select option:checked {
            background-color: rgba(255, 138, 0, 0.15) !important;
            color: var(--dark-color) !important;
        }
        select:focus option:checked {
            background-color: var(--accent-color) !important;
            color: white !important;
        }
        .text-accent { color: var(--accent-color) !important; }

        /* Custom Styled Select Dropdown */
        .custom-select-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .custom-select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
            color: #212529;
            min-height: 46px;
        }
        .custom-select-trigger:hover {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(107, 62, 153, 0.15);
        }
        .custom-select-trigger.active {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(107, 62, 153, 0.25);
        }
        .custom-select-trigger .arrow {
            margin-left: 8px;
            transition: transform 0.2s;
            color: var(--primary-color);
        }
        .custom-select-trigger.active .arrow {
            transform: rotate(180deg);
        }
        .custom-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid rgba(107, 62, 153, 0.3);
            border-radius: 14px;
            margin-top: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            max-height: 280px;
            overflow-y: auto;
            display: none;
        }
        .custom-select-dropdown.show {
            display: block;
        }
        .custom-select-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .custom-select-option:last-child {
            border-bottom: none;
        }
        .custom-select-option:hover,
        .custom-select-option.selected {
            background: var(--accent-color);
            color: white;
        }
        .custom-select-option:first-child {
            border-radius: 13px 13px 0 0;
        }
        .custom-select-option:last-child {
            border-radius: 0 0 13px 13px;
        }
        select.js-enhanced {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            height: 0;
            width: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php require __DIR__ . '/../partials/public_header.php'; ?>

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
                    <i class="fas fa-map-marker-alt me-2 text-accent"></i>
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
                                <div class="d-flex align-items-center">
                                    <div class="amenity-icon">
                                        <i class="fas fa-bed"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($unit['unit_number']); ?></strong>
                                        <small class="text-muted d-block">Unit Number</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="amenity-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($unit['type']); ?></strong>
                                        <small class="text-muted d-block">Type</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="amenity-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo date('g:i A', strtotime($airbnbSettings['check_in_time'] ?? '14:00:00')); ?></strong>
                                        <small class="text-muted d-block">Check-in</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Availability Status -->
                <?php if ($isAvailable !== null): ?>
                <div class="mb-4">
                    <h5>Availability</h5>
                    <div class="availability-badge <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                        <i class="fas <?php echo $isAvailable ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> me-2"></i>
                        <?php echo $isAvailable ? 'Available for selected dates' : 'Not available for selected dates'; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- House Rules -->
                <?php if (!empty($airbnbSettings['house_rules'])): ?>
                <div class="card info-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clipboard-list me-2 text-accent"></i>House Rules</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($airbnbSettings['house_rules'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Caretaker Info -->
                <?php if (!empty($property['caretaker_name'])): ?>
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-tie me-2 text-accent"></i>Property Manager</h5>
                        <p class="card-text mb-1"><strong><?php echo htmlspecialchars($property['caretaker_name']); ?></strong></p>
                        <?php if (!empty($property['caretaker_contact'])): ?>
                        <p class="card-text text-muted">
                            <i class="fas fa-phone me-2 text-accent"></i><?php echo htmlspecialchars($property['caretaker_contact']); ?>
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

                            <button type="submit" class="btn btn-primary w-100 py-3" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none;"
                                    <?php echo ($isAvailable !== null && !$isAvailable) ? 'disabled' : ''; ?>>
                                <?php echo ($isAvailable !== null && !$isAvailable) ? 'Not Available' : 'Request to Book'; ?>
                            </button>

                            <p class="text-muted small mt-2 text-center">
                                <i class="fas fa-info-circle me-1 text-accent"></i>
                                You won't be charged yet
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require __DIR__ . '/../partials/public_footer.php'; ?>

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

            // Initialize custom selects
            initCustomSelects();
        });

        // Custom Select Dropdown Functionality
        function initCustomSelects() {
            const selects = document.querySelectorAll('select.form-select:not(.js-enhanced)');
            
            selects.forEach(function(select) {
                if (select.classList.contains('js-enhanced')) return;
                
                select.classList.add('js-enhanced');
                
                const wrapper = document.createElement('div');
                wrapper.className = 'custom-select-wrapper';
                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(select);
                
                const trigger = document.createElement('div');
                trigger.className = 'custom-select-trigger';
                trigger.innerHTML = '<span class="selected-text">-- Select --</span><i class="bi bi-chevron-down arrow"></i>';
                wrapper.appendChild(trigger);
                
                const dropdown = document.createElement('div');
                dropdown.className = 'custom-select-dropdown';
                wrapper.appendChild(dropdown);
                
                Array.from(select.options).forEach(function(option, index) {
                    const opt = document.createElement('div');
                    opt.className = 'custom-select-option';
                    opt.textContent = option.text;
                    opt.setAttribute('data-value', option.value);
                    opt.setAttribute('data-index', index);
                    
                    if (option.selected) {
                        opt.classList.add('selected');
                        trigger.querySelector('.selected-text').textContent = option.text;
                    }
                    
                    dropdown.appendChild(opt);
                });
                
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = dropdown.classList.contains('show');
                    
                    document.querySelectorAll('.custom-select-dropdown.show').forEach(function(d) {
                        d.classList.remove('show');
                        const wrapperEl = d.closest('.custom-select-wrapper');
                        if (wrapperEl) {
                            const trig = wrapperEl.querySelector('.custom-select-trigger');
                            if (trig) trig.classList.remove('active');
                        }
                    });
                    
                    if (!isOpen) {
                        dropdown.classList.add('show');
                        trigger.classList.add('active');
                    }
                });
                
                dropdown.querySelectorAll('.custom-select-option').forEach(function(opt) {
                    opt.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const index = parseInt(this.getAttribute('data-index'));
                        const value = this.getAttribute('data-value');
                        
                        select.selectedIndex = index;
                        select.value = value;
                        
                        const event = new Event('change', { bubbles: true });
                        select.dispatchEvent(event);
                        
                        dropdown.querySelectorAll('.custom-select-option').forEach(function(o) {
                            o.classList.remove('selected');
                        });
                        this.classList.add('selected');
                        trigger.querySelector('.selected-text').textContent = this.textContent;
                        
                        dropdown.classList.remove('show');
                        trigger.classList.remove('active');
                    });
                });
                
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption) {
                        trigger.querySelector('.selected-text').textContent = selectedOption.text;
                        dropdown.querySelectorAll('.custom-select-option').forEach(function(opt, idx) {
                            opt.classList.toggle('selected', idx === this.selectedIndex);
                        }.bind(this));
                    }
                });
            });
        }
        
        document.addEventListener('click', function() {
            document.querySelectorAll('.custom-select-dropdown.show').forEach(function(d) {
                d.classList.remove('show');
                const wrapperEl = d.closest('.custom-select-wrapper');
                if (wrapperEl) {
                    const trig = wrapperEl.querySelector('.custom-select-trigger');
                    if (trig) trig.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
