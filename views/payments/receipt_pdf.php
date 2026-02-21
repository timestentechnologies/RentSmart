<?php /* @var $payment array */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 14px; color: #222; position: relative; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { max-height: 60px; margin-bottom: 10px; }
        .receipt-title { font-size: 22px; font-weight: bold; margin-bottom: 5px; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 6px; border: 1px solid #ddd; }
        .details-table th { background: #f5f5f5; text-align: left; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; }
        .paid-ribbon {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 8px 40px;
            font-weight: bold;
            font-size: 12px;
            transform: rotate(0deg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="paid-ribbon">PAID</div>
    <div class="header">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div style="text-align:left;">
                <?php if (!empty($logoDataUri)): ?>
                    <img src="<?= $logoDataUri ?>" class="logo" alt="Site Logo">
                <?php endif; ?>
            </div>
            <div style="text-align:right;font-size:14px;">
                <?php if (!empty($payment['lease_id'])): ?>
                    <strong><?= htmlspecialchars($payment['property_name']) ?></strong><br>
                    <?= htmlspecialchars($payment['property_address']) ?>,<br>
                    <?= htmlspecialchars($payment['property_city']) ?>,
                    <?= htmlspecialchars($payment['property_state']) ?>,
                    <?= htmlspecialchars($payment['property_zip']) ?>
                <?php else: ?>
                    <strong><?= htmlspecialchars($siteName ?? 'RentSmart') ?></strong><br>
                    <?= htmlspecialchars($payment['listing_title'] ?? '-') ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="receipt-title">Payment Receipt</div>
    </div>
    <div class="section">
        <div class="section-title">Invoiced To</div>
        <table class="details-table">
            <?php if (!empty($payment['lease_id'])): ?>
                <tr><th>Name</th><td><?= htmlspecialchars($payment['tenant_name']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($payment['tenant_email'] ?? '-') ?></td></tr>
                <tr><th>Phone</th><td><?= htmlspecialchars($payment['tenant_phone'] ?? '-') ?></td></tr>
            <?php else: ?>
                <tr><th>Client</th><td><?= htmlspecialchars($payment['client_name'] ?? '-') ?></td></tr>
                <tr><th>Listing</th><td><?= htmlspecialchars($payment['listing_title'] ?? '-') ?></td></tr>
                <tr><th>Contract #</th><td><?= htmlspecialchars($payment['realtor_contract_id'] ?? '-') ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    <!-- Side-by-side Property & Unit and Tenant Details using a table for dompdf compatibility -->
    <?php if (!empty($payment['lease_id'])): ?>
        <table class="details-table" style="width:100%; margin-bottom:20px;">
            <tr>
                <td style="vertical-align:top; width:50%;">
                    <div class="section-title">Property & Unit</div>
                    <table class="details-table" style="margin-bottom:0;">
                        <tr><th>Property Name</th><td><?= htmlspecialchars($payment['property_name']) ?></td></tr>
                        <tr><th>Unit Number</th><td><?= htmlspecialchars($payment['unit_number']) ?></td></tr>
                    </table>
                </td>
                <td style="vertical-align:top; width:50%;">
                    <div class="section-title">Tenant Details</div>
                    <table class="details-table" style="margin-bottom:0;">
                        <tr><th>Name</th><td><?= htmlspecialchars($payment['tenant_name']) ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($payment['tenant_email'] ?? '-') ?></td></tr>
                        <tr><th>Phone</th><td><?= htmlspecialchars($payment['tenant_phone'] ?? '-') ?></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    <?php endif; ?>
    <div class="section">
        <div class="section-title">Payment Information</div>
        <table class="details-table">
            <tr><th>Receipt #</th><td><?= $payment['id'] ?></td></tr>
            <tr><th>Date</th><td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td></tr>
            <tr><th>Amount</th><td style="font-weight:bold;color:#1a7e1a;">Ksh<?= number_format($payment['amount'], 2) ?></td></tr>
            <tr><th>Method</th><td><?= ucwords(str_replace('_', ' ', $payment['payment_method'])) ?></td></tr>
            <tr><th>Status</th><td><span style="color:#1a7e1a;font-weight:bold;"><?= ucfirst($payment['status'] ?? 'Completed') ?></span></td></tr>
            <tr><th>Notes</th><td><?= htmlspecialchars($payment['notes'] ?? '-') ?></td></tr>
        </table>
    </div>
    <div class="section" style="margin-top:40px;">
        <div class="section-title">Authorized By</div>
        <table class="details-table" style="margin-bottom:0;">
            <tr>
                <th style="width:40%;">Name & Role</th>
                <th style="width:30%;">Signature</th>
                <th style="width:30%;">Date</th>
            </tr>
            <tr>
                <td>
                    <?php
                        $authName = $payment['property_manager_name'] ?? $payment['property_owner_name'] ?? $payment['property_agent_name'] ?? ($siteName ?? 'RentSmart');
                        $role = $payment['property_manager_name'] ? 'Manager' : ($payment['property_owner_name'] ? 'Landlord' : ($payment['property_agent_name'] ? 'Agent' : 'Realtor'));
                    ?>
                    <span style="font-weight:bold;"><?= htmlspecialchars($authName) ?></span><br>
                    <span style="font-size:12px;">(<?= $role ?>)</span>
                </td>
                <td style="height:40px;"></td>
                <td><?= date('M d, Y') ?></td>
            </tr>
        </table>
    </div>
    <div class="section" style="margin-top:30px;border-top:1px solid #ddd;padding-top:15px;">
        <div style="font-size:11px;color:#666;line-height:1.4;">
            <strong>Important Information:</strong><br>
            • This receipt serves as proof of payment for rent and related charges.<br>
            • Please keep this receipt for your records and tax purposes.<br>
            • For any discrepancies, please contact us within 7 days of receipt.<br>
            • Late payments may incur additional charges as per lease agreement.<br>
            • This receipt is computer generated and does not require a physical signature.<br>
            • For questions or concerns, please contact the property management office.
        </div>
    </div>
    <div class="footer">
        <span style="font-weight:bold;">Generated by <?= htmlspecialchars($siteName ?? 'RentSmart') ?></span> &mdash; <?= date('Y-m-d H:i') ?>
    </div>
</body>
</html> 