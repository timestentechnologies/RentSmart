<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Create New Booking</h1>
            <a href="<?php echo BASE_URL; ?>/airbnb/bookings" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>/airbnb/bookings/store" id="bookingForm">
                <div class="row">
                    <!-- Property Selection -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Property <span class="text-danger">*</span></label>
                        <select name="property_id" id="property_id" class="form-select" required>
                            <option value="">Select Property</option>
                            <?php foreach ($properties as $property): ?>
                            <option value="<?php echo $property['id']; ?>" <?php echo ($preselectedPropertyId == $property['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($property['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Unit Selection -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit_id" id="unit_id" class="form-select" required>
                            <option value="">Select Unit</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Guest Information -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guest Name <span class="text-danger">*</span></label>
                        <input type="text" name="guest_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guest Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="guest_phone" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guest Email</label>
                        <input type="email" name="guest_email" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <!-- Dates -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Check-in Date <span class="text-danger">*</span></label>
                        <input type="date" name="check_in_date" id="check_in_date" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Check-in Time</label>
                        <input type="time" name="check_in_time" class="form-control" value="14:00">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Check-out Date <span class="text-danger">*</span></label>
                        <input type="date" name="check_out_date" id="check_out_date" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Check-out Time</label>
                        <input type="time" name="check_out_time" class="form-control" value="11:00">
                    </div>
                </div>

                <div class="row">
                    <!-- Guest Count & Pricing -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" name="guest_count" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Price per Night (KES)</label>
                        <input type="number" name="price_per_night" id="price_per_night" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Cleaning Fee (KES)</label>
                        <input type="number" name="cleaning_fee" id="cleaning_fee" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Security Deposit (KES)</label>
                        <input type="number" name="security_deposit" id="security_deposit" class="form-control" step="0.01" value="0">
                    </div>
                </div>

                <div class="row">
                    <!-- Booking Details -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nights</label>
                        <input type="number" name="nights" id="nights" class="form-control" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Total Amount (KES)</label>
                        <input type="number" name="total_amount" id="total_amount" class="form-control" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Discount (KES)</label>
                        <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Final Total (KES)</label>
                        <input type="number" name="final_total" id="final_total" class="form-control" readonly>
                    </div>
                </div>

                <div class="row">
                    <!-- Payment Info -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount Paid (KES)</label>
                        <input type="number" name="amount_paid" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Booking Source</label>
                        <select name="booking_source" class="form-select">
                            <option value="walk_in">Walk-in</option>
                            <option value="phone">Phone</option>
                            <option value="email">Email</option>
                            <option value="online">Online</option>
                            <option value="ota">OTA (Booking.com, etc)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Status -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Booking Status</label>
                        <select name="status" class="form-select">
                            <option value="confirmed">Confirmed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Notes -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Special Requests</label>
                        <textarea name="special_requests" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="internal_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/airbnb/bookings" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    const checkInDate = document.getElementById('check_in_date');
    const checkOutDate = document.getElementById('check_out_date');
    const pricePerNight = document.getElementById('price_per_night');
    const cleaningFee = document.getElementById('cleaning_fee');
    const securityDeposit = document.getElementById('security_deposit');
    const discountAmount = document.getElementById('discount_amount');
    const nightsInput = document.getElementById('nights');
    const totalAmount = document.getElementById('total_amount');
    const finalTotal = document.getElementById('final_total');

    // Load units when property changes
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        
        if (propertyId) {
            fetch('<?php echo BASE_URL; ?>/airbnb/api/available-units?property_id=' + propertyId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.units) {
                        data.units.forEach(unit => {
                            const option = document.createElement('option');
                            option.value = unit.id;
                            option.textContent = unit.unit_number + ' (KES ' + unit.base_price + '/night)';
                            option.dataset.price = unit.base_price;
                            unitSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading units:', error));
        }
    });

    // Update price when unit changes
    unitSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.dataset.price) {
            pricePerNight.value = selectedOption.dataset.price;
            calculateTotals();
        }
    });

    // Calculate totals
    function calculateTotals() {
        const checkIn = new Date(checkInDate.value);
        const checkOut = new Date(checkOutDate.value);
        
        if (checkIn && checkOut && checkOut > checkIn) {
            const diffTime = Math.abs(checkOut - checkIn);
            const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            nightsInput.value = nights;
            
            const price = parseFloat(pricePerNight.value) || 0;
            const total = nights * price;
            totalAmount.value = total.toFixed(2);
            
            const cleaning = parseFloat(cleaningFee.value) || 0;
            const deposit = parseFloat(securityDeposit.value) || 0;
            const discount = parseFloat(discountAmount.value) || 0;
            const final = total + cleaning + deposit - discount;
            finalTotal.value = final.toFixed(2);
        }
    }

    checkInDate.addEventListener('change', calculateTotals);
    checkOutDate.addEventListener('change', calculateTotals);
    pricePerNight.addEventListener('input', calculateTotals);
    cleaningFee.addEventListener('input', calculateTotals);
    securityDeposit.addEventListener('input', calculateTotals);
    discountAmount.addEventListener('input', calculateTotals);

    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    checkInDate.min = today;
    checkOutDate.min = today;

    // Preload units if property is preselected
    if (propertySelect.value) {
        propertySelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
