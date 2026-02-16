<?php
/** @var array $contract */
/** @var array $client */
/** @var array $listing */

$contractId = (int)($contract['id'] ?? 0);
$termsType = (string)($contract['terms_type'] ?? 'one_time');
$totalAmount = (float)($contract['total_amount'] ?? 0);
$duration = (int)($contract['duration_months'] ?? 0);
$monthly = (float)($contract['monthly_amount'] ?? 0);
$startMonth = (string)($contract['start_month'] ?? '');
$instructions = (string)($contract['instructions'] ?? '');

$clientName = (string)($client['name'] ?? '');
$clientPhone = (string)($client['phone'] ?? '');
$clientEmail = (string)($client['email'] ?? '');

$listingTitle = (string)($listing['title'] ?? '');
$listingLocation = (string)($listing['location'] ?? '');

$siteName = (string)($siteName ?? 'RentSmart');
$logoDataUri = $logoDataUri ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract #<?= (int)$contractId ?></title>
    <style>
        @page { margin: 32px 34px; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111827; }
        .muted { color: #6b7280; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0; }
        .h2 { font-size: 13px; font-weight: 700; margin: 18px 0 8px 0; }
        .hr { height: 1px; background: #e5e7eb; margin: 12px 0; }

        .header-table { width: 100%; border-collapse: collapse; }
        .header-left { width: 70%; vertical-align: top; }
        .header-right { width: 30%; vertical-align: top; text-align: right; }
        .logo { max-height: 56px; max-width: 180px; }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 10px;
            font-weight: 700;
        }

        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 7px 10px; border: 1px solid #e5e7eb; vertical-align: top; }
        .kv td.label { width: 32%; background: #f9fafb; font-weight: 700; }

        .box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; }

        .totals { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .totals td { padding: 8px 10px; border: 1px solid #e5e7eb; }
        .totals td.label { background: #f9fafb; font-weight: 700; width: 50%; }
        .totals td.value { text-align: right; font-weight: 700; }

        .sign-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sign-table td { border: 1px solid #e5e7eb; padding: 14px 12px; vertical-align: top; }
        .sign-line { height: 20px; border-bottom: 1px solid #111827; margin-top: 26px; }

        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 10px; color: #6b7280; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-right { text-align: right; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td class="header-left">
            <?php if (!empty($logoDataUri)): ?>
                <img src="<?= $logoDataUri ?>" class="logo" alt="Logo">
            <?php endif; ?>
            <div style="margin-top:6px;">
                <div class="h1"><?= htmlspecialchars($siteName) ?></div>
                <div class="muted">Contract Agreement</div>
            </div>
        </td>
        <td class="header-right">
            <div class="badge">CONTRACT #<?= (int)$contractId ?></div>
            <div class="muted" style="margin-top:8px;">Generated: <?= date('Y-m-d H:i') ?></div>
        </td>
    </tr>
</table>

<div class="hr"></div>

<div class="h2">Client Details</div>
<table class="kv">
    <tr>
        <td class="label">Name</td>
        <td><?= htmlspecialchars($clientName) ?></td>
    </tr>
    <tr>
        <td class="label">Phone</td>
        <td><?= htmlspecialchars($clientPhone) ?></td>
    </tr>
    <tr>
        <td class="label">Email</td>
        <td><?= htmlspecialchars($clientEmail) ?></td>
    </tr>
</table>

<div class="h2">Listing Details</div>
<table class="kv">
    <tr>
        <td class="label">Title</td>
        <td><?= htmlspecialchars($listingTitle) ?></td>
    </tr>
    <tr>
        <td class="label">Location</td>
        <td><?= htmlspecialchars($listingLocation) ?></td>
    </tr>
</table>

<div class="h2">Payment Terms</div>
<table class="kv">
    <tr>
        <td class="label">Mode</td>
        <td><?= htmlspecialchars($termsType === 'monthly' ? 'Monthly' : 'One Time') ?></td>
    </tr>
    <?php if ($termsType === 'monthly'): ?>
        <tr>
            <td class="label">Start Month</td>
            <td><?= htmlspecialchars(substr($startMonth, 0, 7)) ?></td>
        </tr>
        <tr>
            <td class="label">Duration</td>
            <td><?= (int)$duration ?> months</td>
        </tr>
        <tr>
            <td class="label">Monthly Amount</td>
            <td>Ksh<?= number_format((float)$monthly, 2) ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <td class="label">Total Amount</td>
        <td><strong>Ksh<?= number_format((float)$totalAmount, 2) ?></strong></td>
    </tr>
</table>

<div class="h2">Instructions / Notes</div>
<div class="box" style="white-space: pre-wrap; min-height: 90px;">
    <?= htmlspecialchars($instructions) ?>
</div>

<div class="h2">Signatures</div>
<table class="sign-table">
    <tr>
        <td style="width:50%;">
            <div class="muted">Client Signature</div>
            <div class="sign-line"></div>
            <div class="muted" style="margin-top:10px;">Date</div>
            <div class="sign-line" style="margin-top:14px;"></div>
        </td>
        <td style="width:50%;">
            <div class="muted">Realtor Signature</div>
            <div class="sign-line"></div>
            <div class="muted" style="margin-top:10px;">Date</div>
            <div class="sign-line" style="margin-top:14px;"></div>
        </td>
    </tr>
</table>

<div class="footer">
    <table class="footer-table">
        <tr>
            <td>Generated by <?= htmlspecialchars($siteName) ?></td>
            <td class="footer-right">Contract #<?= (int)$contractId ?></td>
        </tr>
    </table>
</div>

</body>
</html>
