<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Receipt - <?= $booking['booking_reference'] ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.5; font-size: 14px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; }
        .header { border-bottom: 2px solid #FF5A5F; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-width: 150px; }
        .header-content { display: table; width: 100%; }
        .header-left { display: table-cell; width: 50%; vertical-align: middle; }
        .header-right { display: table-cell; width: 50%; text-align: right; vertical-align: middle; }
        .title { color: #FF5A5F; font-size: 24px; font-weight: bold; margin: 0; }
        .ref { color: #888; margin-top: 5px; }
        
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; color: #555; }
        
        .details-table { width: 100%; border-collapse: collapse; }
        .details-table td { padding: 8px 0; }
        .label { color: #888; width: 150px; }
        .value { font-weight: bold; }
        
        .pricing-table { width: 100%; margin-top: 20px; border-top: 1px solid #eee; }
        .pricing-table td { padding: 10px 0; border-bottom: 1px solid #f9f9f9; }
        .pricing-table .total-row { border-top: 2px solid #333; font-size: 18px; font-weight: bold; }
        .text-right { text-align: right; }
        
        .footer { margin-top: 50px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
        .accent { color: #FF5A5F; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <?php if (isset($logoPath) && file_exists($logoPath)): ?>
                        <img src="data:image/png;base64,<?= base64_encode(file_get_contents($logoPath)) ?>" class="logo">
                    <?php else: ?>
                        <h1 class="title"><?= htmlspecialchars($siteName) ?></h1>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <div class="title">BOOKING RECEIPT</div>
                    <div class="ref">REF: <?= htmlspecialchars($booking['booking_reference']) ?></div>
                    <div class="small">Issued: <?= date('M d, Y') ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Stay Information</div>
            <table class="details-table">
                <tr>
                    <td class="label">Property:</td>
                    <td class="value"><?= htmlspecialchars($booking['property_name']) ?></td>
                </tr>
                <tr>
                    <td class="label">Unit:</td>
                    <td class="value">Unit #<?= htmlspecialchars($booking['unit_number']) ?></td>
                </tr>
                <tr>
                    <td class="label">Check-in:</td>
                    <td class="value"><?= date('D, M j, Y', strtotime($booking['check_in_date'])) ?></td>
                </tr>
                <tr>
                    <td class="label">Check-out:</td>
                    <td class="value"><?= date('D, M j, Y', strtotime($booking['check_out_date'])) ?></td>
                </tr>
                <tr>
                    <td class="label">Duration:</td>
                    <td class="value"><?= $booking['nights'] ?> Night(s)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Guest Details</div>
            <table class="details-table">
                <tr>
                    <td class="label">Lead Guest:</td>
                    <td class="value"><?= htmlspecialchars($booking['guest_name']) ?></td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td class="value"><?= htmlspecialchars($booking['guest_phone']) ?></td>
                </tr>
                <?php if ($booking['guest_email']): ?>
                <tr>
                    <td class="label">Email:</td>
                    <td class="value"><?= htmlspecialchars($booking['guest_email']) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="label">Guests:</td>
                    <td class="value"><?= $booking['guest_count'] ?> Person(s)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Payment Summary</div>
            <table class="pricing-table">
                <tr>
                    <td>Stay (<?= $booking['nights'] ?> nights)</td>
                    <td class="text-right">KES <?= number_format($booking['total_amount'], 2) ?></td>
                </tr>
                <?php if ($booking['discount_amount'] > 0): ?>
                <tr>
                    <td>Discount Applied</td>
                    <td class="text-right text-success">-KES <?= number_format($booking['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Cleaning Fee</td>
                    <td class="text-right">KES <?= number_format($booking['cleaning_fee'], 2) ?></td>
                </tr>
                <tr>
                    <td>Security Deposit (Refundable)</td>
                    <td class="text-right">KES <?= number_format($booking['security_deposit'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total Paid</td>
                    <td class="text-right accent">KES <?= number_format($booking['final_total'], 2) ?></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p><strong>Note:</strong> This is an official receipt for your booking at <?= htmlspecialchars($booking['property_name']) ?>. Security deposits are partially or fully refundable after inspection at check-out.</p>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
