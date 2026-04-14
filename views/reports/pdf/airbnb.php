<?php 
$title = 'Airbnb Performance Report';
include __DIR__ . '/_header.php'; 

// Data is passed in from ReportsController::exportToPdf via getAirbnbPerformanceReport
?>

    <!-- Performance Summary -->
    <div class="summary-box">
        <h2>Performance Summary</h2>
        <table style="width: 100%;">
            <tr>
                <th style="width: 25%;">Total Revenue</th>
                <td style="width: 25%;">Ksh <?= number_format($data['total_revenue'], 2) ?></td>
                <th style="width: 25%;">Nights Booked</th>
                <td style="width: 25%;"><?= $data['total_nights'] ?></td>
            </tr>
            <tr>
                <th>Total Bookings</th>
                <td><?= $data['total_bookings'] ?></td>
                <th>Average Daily Rate (ADR)</th>
                <td>Ksh <?= number_format($data['adr'], 2) ?></td>
            </tr>
            <tr>
                <th>Completed Stays</th>
                <td><?= $data['completed_bookings'] ?></td>
                <th>Cancelled Stays</th>
                <td style="color: #dc3545;"><?= $data['cancelled_bookings'] ?></td>
            </tr>
        </table>
    </div>

    <!-- Revenue by Listing -->
    <?php if (!empty($data['propertyRevenue'])): ?>
    <div style="margin-top: 20px;">
        <h2>Revenue by Listing</h2>
        <table>
            <thead>
                <tr>
                    <th>Listing Name</th>
                    <th>Revenue</th>
                    <th>Contribution</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['propertyRevenue'] as $property): ?>
                <tr>
                    <td><?= htmlspecialchars($property['name']) ?></td>
                    <td>Ksh <?= number_format($property['revenue'], 2) ?></td>
                    <td><?= $data['total_revenue'] > 0 ? number_format(($property['revenue'] / $data['total_revenue']) * 100, 1) : 0 ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Booking Details -->
    <?php if (!empty($data['bookings'])): ?>
    <div style="margin-top: 30px;">
        <h2>Booking Details</h2>
        <table style="font-size: 11px;">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Guest</th>
                    <th>Listing</th>
                    <th>Dates</th>
                    <th>Nights</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['bookings'] as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking['booking_reference']) ?></td>
                    <td><?= htmlspecialchars($booking['guest_name']) ?></td>
                    <td><?= htmlspecialchars($booking['property_name']) ?> - <?= htmlspecialchars($booking['unit_number']) ?></td>
                    <td><?= date('M j', strtotime($booking['check_in_date'])) ?> - <?= date('M j', strtotime($booking['check_out_date'])) ?></td>
                    <td><?= $booking['nights'] ?></td>
                    <td>Ksh <?= number_format($booking['final_total'], 2) ?></td>
                    <td>Ksh <?= number_format($booking['amount_paid'], 2) ?></td>
                    <td>
                        <?php 
                            $color = '#6c757d';
                            switch($booking['status']) {
                                case 'confirmed': $color = '#28a745'; break;
                                case 'checked_in': $color = '#007bff'; break;
                                case 'checked_out': $color = '#17a2b8'; break;
                                case 'cancelled': $color = '#dc3545'; break;
                            }
                        ?>
                        <span style="color: <?= $color ?>; font-weight: bold;"><?= ucfirst($booking['status']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
