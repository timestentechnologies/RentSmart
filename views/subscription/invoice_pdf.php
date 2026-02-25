<?php /* @var $payment array, $subscription array, $user array, $logoDataUri string|null, $siteName string */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Invoice</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 0; background: #fff; }
        .page { padding: 24px 28px; position: relative; overflow: visible; }

        /* Watermark using logo */
        .watermark {
            position: absolute;
            left: 50%;
            top: 55%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: 0;
        }
        .watermark img { width: 360px; max-width: 70%; }

        .header-row { width: 100%; margin-bottom: 18px; position: relative; z-index: 2; }
        .header-left { float: left; width: 58%; }
        .header-right { float: right; width: 38%; text-align: right; }

        .brand-name { font-size: 15px; font-weight: bold; margin-bottom: 2px; color: #222; }
        .brand-meta { font-size: 10px; color: #555; line-height: 1.4; }
        .logo-small { max-height: 36px; margin-bottom: 4px; }

        .invoice-label { font-size: 11px; color: #999; }
        .invoice-number { font-size: 13px; font-weight: bold; color: #c0392b; }
        .invoice-date { font-size: 10px; color: #666; margin-top: 4px; }

        .clear { clear: both; }

        .card-row { width: 100%; margin-bottom: 10px; position: relative; z-index: 2; }
        .card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 10px;
            background: #fff;
            min-height: 70px;
        }
        .card + .card { margin-left: 10px; }
        .card-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 6px;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .muted { color: #4b5563; font-size: 10px; line-height: 1.5; }

        .card-half { float: left; width: 48%; box-sizing: border-box; }

        .details-row { width: 100%; margin-bottom: 10px; position: relative; z-index: 2; }
        .details-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 10px;
            background: #fff;
        }
        .details-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .details-table th { width: 25%; text-align: left; padding: 4px 0; color: #4b5563; }
        .details-table td { padding: 4px 0; color: #111; }

        .items-row { width: 100%; margin-bottom: 12px; position: relative; z-index: 2; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 10px; border: 1px solid #e5e7eb; }
        .items-table th, .items-table td { padding: 8px 8px; border: 1px solid #e5e7eb; }
        .items-table th { background: #f3f4f6; font-weight: bold; text-align: left; color: #374151; }
        .items-table td.amount { text-align: right; }

        .totals-row { width: 100%; margin-top: 4px; position: relative; z-index: 2; }
        .totals-card {
            width: 38%;
            float: right;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 10px;
            background: #fff;
        }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 4px 0; font-size: 10px; }
        .totals-label { color: #4b5563; }
        .totals-value { text-align: right; }
        .totals-strong { font-weight: bold; color: #111; }
        .totals-paid { color: #16a34a; font-weight: bold; }
        .totals-balance { color: #c0392b; font-weight: bold; }

        .footer-text { margin-top: 28px; font-size: 10px; color: #6b7280; position: relative; z-index: 2; }

        /* Decorative corner shapes - inline styles used instead */
        .status-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
        }
        .status-paid { background: #16a34a; }
        .status-pending { background: #f59e0b; }
        .status-failed { background: #dc2626; }
    </style>
</head>
<body>
<?php
    $ps = strtolower($payment['status'] ?? 'completed');
    $badgeClass = ($ps === 'completed') ? 'status-paid' : (($ps === 'failed') ? 'status-failed' : 'status-pending');
    $badgeText = ($ps === 'completed') ? 'Paid' : (($ps === 'failed') ? 'Failed' : 'Pending');

    $planName = $subscription['plan_name'] ?? ($subscription['plan_type'] ?? 'Subscription Plan');
    $planDisplay = 'Subscription Plan - ' . $planName;
    $amount = (float)($payment['amount'] ?? $subscription['plan_price'] ?? 0);
    $subtotal = $amount;
    $tax = 0.0; // currently exempt
    $total = $subtotal + $tax;
    $paid = ($ps === 'completed') ? $total : 0.0;
    $balance = $total - $paid;

    $issueDate = isset($payment['created_at']) ? date('Y-m-d', strtotime($payment['created_at'])) : date('Y-m-d');
    $dueDate = !empty($subscription['current_period_ends_at'])
        ? date('Y-m-d', strtotime($subscription['current_period_ends_at']))
        : date('Y-m-d', strtotime('+30 days', strtotime($issueDate)));
?>
<div class="page">
    <!-- Large curved decorative shapes using SVG -->
    <!-- Top right decorative shape - large curved peach/pink -->
    <svg style="position:absolute;top:0;right:0;z-index:0;" width="300" height="250" viewBox="0 0 300 250" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="220" cy="80" rx="180" ry="140" fill="#fca5a5" opacity="0.35"/>
    </svg>
    <!-- Bottom left decorative shape - large curved purple -->
    <svg style="position:absolute;bottom:0;left:0;z-index:0;" width="300" height="250" viewBox="0 0 300 250" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="80" cy="180" rx="160" ry="130" fill="#c4b5fd" opacity="0.35"/>
    </svg>

    <?php if (!empty($logoDataUri)): ?>
        <div class="watermark">
            <img src="<?= $logoDataUri ?>" alt="Watermark">
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="header-row">
        <div class="header-left">
            <?php if (!empty($logoDataUri)): ?>
                <img class="logo-small" src="<?= $logoDataUri ?>" alt="Logo">
            <?php endif; ?>
            <div class="brand-name"><?= htmlspecialchars($siteName ?? 'RentSmart') ?></div>
            <div class="brand-meta">
                Westlands, Nairobi, Kenya 00100<br>
                +254795155230
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-label">Invoice</div>
            <div class="invoice-number">INV-<?= htmlspecialchars(str_pad((string)$payment['id'], 6, '0', STR_PAD_LEFT)) ?></div>
            <div class="invoice-date">Date: <?= $issueDate ?></div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Billed From / Billed To cards -->
    <div class="card-row">
        <div class="card card-half">
            <div class="card-title">BILLED FROM</div>
            <div class="muted">
                Rent Smart Kenya<br>
                Westlands, Nairobi, Kenya 00100<br>
                +254795155230<br>
                rentsmart@timestentechnologies.co.ke
            </div>
        </div>
        <div class="card card-half">
            <div class="card-title">BILLED TO</div>
            <div class="muted">
                <?= htmlspecialchars($user['name'] ?? '-') ?><br>
                <?= htmlspecialchars($user['email'] ?? '-') ?><br>
                <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) . '<br>' : '' ?>
                <?= htmlspecialchars($user['address'] ?? 'Kenya') ?>
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Details card -->
    <div class="details-row">
        <div class="details-card">
            <div class="card-title">DETAILS</div>
            <table class="details-table">
                <tr>
                    <th>Issue Date</th>
                    <td><?= $issueDate ?></td>
                </tr>
                <tr>
                    <th>Due Date</th>
                    <td><?= $dueDate ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Items table -->
    <div class="items-row">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 60%;">DESCRIPTION</th>
                    <th style="width: 10%;">QTY</th>
                    <th style="width: 15%; text-align: right;">UNIT PRICE</th>
                    <th style="width: 15%; text-align: right;">LINE TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($planDisplay) ?></td>
                    <td>1.00</td>
                    <td class="amount"><?= number_format($amount, 2) ?></td>
                    <td class="amount"><?= number_format($amount, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Totals card -->
    <div class="totals-row">
        <div class="totals-card">
            <div style="text-align:right; margin-bottom:4px;">
                <span class="status-pill <?= $badgeClass ?>"><?= $badgeText ?></span>
            </div>
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Subtotal</td>
                    <td class="totals-value"><?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td class="totals-label">Tax (Exempt)</td>
                    <td class="totals-value">0.00</td>
                </tr>
                <tr>
                    <td class="totals-label totals-strong">Total</td>
                    <td class="totals-value totals-strong"><?= number_format($total, 2) ?></td>
                </tr>
                <tr>
                    <td class="totals-label totals-paid">Paid</td>
                    <td class="totals-value totals-paid"><?= number_format($paid, 2) ?></td>
                </tr>
                <tr>
                    <td class="totals-label totals-balance">Balance Due</td>
                    <td class="totals-value totals-balance"><?= number_format($balance, 2) ?></td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer-text">
        Thank you for your business.
    </div>
</div>
</body>
</html>
