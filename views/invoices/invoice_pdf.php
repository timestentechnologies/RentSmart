<?php /* @var $invoice array, $logoDataUri string|null, $siteName string, $settings array */ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?></title>
  <style>
    @page { margin: 14px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.35; color: #111827; margin: 0; padding: 0; background: #ffffff; }

    .container { width: 100%; border: 1px solid #e5e7eb; }
    .header { width: 100%; border-collapse: collapse; }
    .header td { vertical-align: top; padding: 14px 14px; }
    .logo { max-height: 58px; width: auto; }
    .brand { font-size: 14px; font-weight: 800; margin-top: 6px; }
    .invoice-title { font-size: 28px; font-weight: 900; letter-spacing: 1px; margin: 0; }
    .meta { margin-top: 6px; font-size: 11.5px; color: #6b7280; }
    .accent { height: 6px; background: #f59e0b; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-success { background: #16a34a; color: #fff; }
    .badge-warning { background: #f59e0b; color: #111; }
    .badge-primary { background: #2563eb; color: #fff; }
    .badge-danger { background: #dc2626; color: #fff; }
    .badge-secondary { background: #6b7280; color: #fff; }
    .badge-dark { background: #111827; color: #fff; }

    .section { padding: 12px 14px; }
    .section-title { font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .6px; color: #111827; margin: 0 0 8px 0; }

    .info { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .info td { vertical-align: top; width: 50%; padding: 0 8px; }
    .info td:first-child { padding-left: 0; }
    .info td:last-child { padding-right: 0; }
    .box { border: 1px solid #e5e7eb; background: #f9fafb; padding: 10px; }
    .row { margin: 0 0 6px 0; }
    .label { font-size: 10px; letter-spacing: .6px; text-transform: uppercase; color: #6b7280; }
    .value { font-weight: 700; color: #111827; word-break: break-word; overflow-wrap: anywhere; }
    .small { font-size: 11.5px; color: #374151; word-break: break-word; overflow-wrap: anywhere; }

    .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .items th, .items td { border: 1px solid #e5e7eb; padding: 9px 8px; }
    .items th { background: #111827; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; }
    .right { text-align: right; }

    .totals { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .totals td { border: 1px solid #e5e7eb; padding: 8px 8px; }
    .totals .k { text-align: right; color: #6b7280; font-weight: 800; }
    .totals .v { text-align: right; font-weight: 900; }
    .totals .grand td { background: #f59e0b; color: #111827; }

    .signature { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .signature td { vertical-align: top; width: 50%; padding: 0 8px; }
    .signature td:first-child { padding-left: 0; }
    .signature td:last-child { padding-right: 0; }
    .sigline { border-top: 1px solid #9ca3af; height: 1px; margin-top: 38px; }
    .foot { text-align: center; font-size: 11px; color: #9ca3af; padding: 10px 14px 14px 14px; }

    .watermark { position: fixed; top: 46%; left: 8%; right: 8%; text-align: center; font-size: 64px; color: rgba(17,24,39,0.05); transform: rotate(-18deg); z-index: 0; }
  </style>
</head>
<body>
  <div class="watermark"><?= htmlspecialchars($siteName ?? 'RentSmart') ?></div>
  <?php
    $st = strtolower((string)($invoice['status'] ?? 'draft'));
    $statusClass = 'badge-secondary';
    if ($st === 'paid') $statusClass = 'badge-success';
    elseif ($st === 'partial') $statusClass = 'badge-warning';
    elseif ($st === 'sent') $statusClass = 'badge-primary';
    elseif ($st === 'overdue') $statusClass = 'badge-danger';
    elseif ($st === 'void') $statusClass = 'badge-dark';
  ?>
  <div class="container">
    <table class="header">
      <tr>
        <td style="width:50%">
          <?php if (!empty($logoDataUri)): ?>
            <img class="logo" src="<?= $logoDataUri ?>" alt="Logo">
          <?php endif; ?>
          <div class="brand"><?= htmlspecialchars($siteName ?? '-') ?></div>
        </td>
        <td style="width:50%; text-align:right;">
          <div class="invoice-title">INVOICE</div>
          <div class="meta">
            Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?>
            • Date: <?= htmlspecialchars($invoice['issue_date']) ?>
            • Status: <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst((string)($invoice['status'] ?? 'draft'))) ?></span>
          </div>
        </td>
      </tr>
    </table>
    <div class="accent"></div>

    <div class="section">
      <table class="info">
        <tr>
          <td>
            <div class="section-title">From</div>
            <div class="box">
              <div class="row"><div class="label">Company</div><div class="value"><?= htmlspecialchars($siteName ?? '-') ?></div></div>
              <div class="row"><div class="label">Address</div><div class="small"><?= htmlspecialchars($settings['site_address'] ?? '-') ?></div></div>
              <div class="row"><div class="label">Phone</div><div class="small"><?= htmlspecialchars($settings['site_phone'] ?? '-') ?></div></div>
              <div class="row"><div class="label">Email</div><div class="small"><?= htmlspecialchars($settings['site_email'] ?? '-') ?></div></div>
            </div>
          </td>
          <td>
            <div class="section-title">Bill To</div>
            <div class="box">
              <div class="row"><div class="label">Customer</div><div class="value"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_name'] ?? '-') ?: '-') : ($invoice['tenant_name'] ?? '-')) ?></div></div>
              <div class="row"><div class="label">Email</div><div class="small"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_email'] ?? '-') ?: '-') : ($invoice['tenant_email'] ?? '-')) ?></div></div>
              <?php if (!empty($realtorContext) && (!empty($realtorContext['listing_title']) || !empty($realtorContext['listing_location']))): ?>
              <div class="row"><div class="label">Listing</div><div class="small"><?= htmlspecialchars((string)($realtorContext['listing_title'] ?? '')) ?><?= !empty($realtorContext['listing_location']) ? (' • ' . htmlspecialchars((string)$realtorContext['listing_location'])) : '' ?></div></div>
              <?php endif; ?>
              <div class="row"><div class="label">Due Date</div><div class="small"><?= htmlspecialchars($invoice['due_date'] ?? '-') ?></div></div>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="section">
      <div class="section-title">Items</div>
      <table class="items">
        <thead>
          <tr>
            <th style="width:58%">Description</th>
            <th style="width:12%" class="right">Qty</th>
            <th style="width:15%" class="right">Unit Price</th>
            <th style="width:15%" class="right">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($invoice['items'] ?? []) as $it): ?>
            <tr>
              <td><?= htmlspecialchars((string)($it['description'] ?? '')) ?></td>
              <td class="right"><?= number_format((float)($it['quantity'] ?? 0), 2) ?></td>
              <td class="right"><?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
              <td class="right"><?= number_format((float)($it['line_total'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <table class="totals">
        <tr>
          <td class="k" style="width:80%">Subtotal</td>
          <td class="v" style="width:20%">Ksh <?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></td>
        </tr>
        <?php if (!empty($invoice['tax_rate'])): ?>
        <tr>
          <td class="k">Tax (<?= number_format((float)$invoice['tax_rate'], 2) ?>%)</td>
          <td class="v">Ksh <?= number_format((float)($invoice['tax_amount'] ?? 0), 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="grand">
          <td class="k">Grand Total</td>
          <td class="v">Ksh <?= number_format((float)($invoice['total'] ?? 0), 2) ?></td>
        </tr>
      </table>
    </div>

    <div class="section">
      <table class="signature">
        <tr>
          <td>
            <div class="section-title">Terms & Notes</div>
            <div class="box">
              <div class="small">
                <?php if (!empty($invoice['notes'])): ?>
                  <?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?>
                <?php else: ?>
                  Payment is due by the due date indicated. Late payments may incur penalties as per your agreement.
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <div class="section-title">Authorized Signature</div>
            <div class="box" style="height:92px;">
              <div class="sigline"></div>
              <div class="small" style="margin-top:6px; color:#6b7280;">Sign and return if required.</div>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="foot">
      Generated by <?= htmlspecialchars($siteName ?? 'RentSmart') ?> — <?= date('Y-m-d H:i') ?>
    </div>
  </div>

  <?php if (empty($realtorContext) && !empty($paymentStatus)): ?>
  <div style="margin: 10px 0 0 0;">
    <div style="font-size:11px;color:#6b7280;">Payment Status for <?= htmlspecialchars($paymentStatus['month_label']) ?>:</div>
    <div style="font-size:11px;color:#6b7280;">
      Rent: <?= htmlspecialchars(ucfirst($paymentStatus['rent']['status'])) ?>,
      Utilities: <?= htmlspecialchars(ucfirst($paymentStatus['utilities']['status'])) ?>,
      Maintenance: <?= htmlspecialchars(ucfirst($paymentStatus['maintenance']['status'] ?? 'due')) ?>
    </div>
  </div>
  <?php endif; ?>
</body>
</html>
