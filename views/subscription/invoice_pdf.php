<?php /* @var $payment array, $subscription array, $user array, $logoDataUri string|null, $siteName string */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Invoice</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 0; }
        .page { padding: 24px 28px; position: relative; }

        /* Watermark using logo */
        .watermark {
            position: absolute;
            left: 50%;
            top: 55%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            z-index: 0;
        }
        .watermark img { width: 420px; max-width: 80%; }

        .header-row { width: 100%; margin-bottom: 20px; position: relative; z-index: 2; }
        .header-left { float: left; width: 60%; }
        .header-right { float: right; width: 38%; text-align: right; }

        .brand-name { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .brand-meta { font-size: 10px; color: #666; line-height: 1.4; }
        .logo-small { max-height: 40px; margin-bottom: 4px; }

        .invoice-label { font-size: 12px; color: #999; }
        .invoice-number { font-size: 13px; font-weight: bold; color: #c0392b; }
        .invoice-date { font-size: 10px; color: #666; margin-top: 4px; }

        .clear { clear: both; }

        .card-row { width: 100%; margin-bottom: 12px; position: relative; z-index: 2; }
        .card { border: 1px solid #e2e6f0; border-radius: 6px; padding: 10px 12px; font-size: 11px; }
        .card + .card { margin-left: 8px; }
        .card-title { font-weight: bold; font-size: 11px; margin-bottom: 6px; color: #555; }
        .muted { color: #777; font-size: 10px; line-height: 1.4; }

        .card-half { float: left; width: 48%; }

        .details-row { width: 100%; margin-bottom: 12px; position: relative; z-index: 2; }
        .details-card { border: 1px solid #e2e6f0; border-radius: 6px; padding: 10px 12px; font-size: 11px; }
        .details-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .details-table th { width: 25%; text-align: left; padding: 4px 0; color: #777; }
        .details-table td { padding: 4px 0; }

        .items-row { width: 100%; margin-bottom: 16px; position: relative; z-index: 2; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .items-table th, .items-table td { padding: 7px 6px; border: 1px solid #e3e6ef; }
        .items-table th { background: #f6f7fb; font-weight: bold; text-align: left; }
        .items-table td.amount { text-align: right; }

        .totals-row { width: 100%; margin-top: 4px; position: relative; z-index: 2; }
        .totals-card { width: 34%; float: right; border: 1px solid #e2e6f0; border-radius: 6px; padding: 8px 10px; font-size: 11px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 4px 0; font-size: 10px; }
        .totals-label { color: #777; }
        .totals-value { text-align: right; }
        .totals-strong { font-weight: bold; }
        .totals-paid { color: #16a34a; font-weight: bold; }
        .totals-balance { color: #c0392b; font-weight: bold; }

        .footer-text { margin-top: 28px; font-size: 10px; color: #777; position: relative; z-index: 2; }

        /* Simple status pill near totals */
        .status-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 9px; font-weight: bold; color: #fff; }
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
                <?= htmlspecialchars($user['address'] ?? 'Nairobi, Kenya') ?><br>
                <?= htmlspecialchars($user['email'] ?? '') ?>
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
                <?= htmlspecialchars($siteName ?? 'RentSmart') ?><br>
                <?= htmlspecialchars($settings['site_email'] ?? ($user['email'] ?? '')) ?><br>
                <?= htmlspecialchars($settings['site_phone'] ?? '') ?><br>
                <?= htmlspecialchars($settings['site_address'] ?? 'Nairobi, Kenya') ?>
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
                    <td><?= htmlspecialchars($planName) ?></td>
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
