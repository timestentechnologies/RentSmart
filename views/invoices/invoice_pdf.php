<?php /* @var $invoice array, $logoDataUri string|null, $siteName string, $settings array */ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?></title>
  <style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; color: #111827; margin: 0; padding: 0; background: #ffffff; }

    .sheet { padding: 12mm; }
    .frame { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #e5e7eb; }
    .frame td { vertical-align: top; }

    .side { width: 56mm; background: #1f2937; color: #e5e7eb; padding: 10mm 6mm; }
    .main { background: #ffffff; padding: 10mm 9mm; }

    .brand-logo { max-height: 24mm; width: auto; display: block; margin-bottom: 4mm; }
    .brand-name { font-size: 12px; font-weight: 900; color: #fff; }

    .side-divider { height: 1px; background: rgba(255,255,255,0.14); margin: 6mm 0; }
    .side-title { font-size: 10px; font-weight: 900; letter-spacing: .8px; text-transform: uppercase; color: #f59e0b; margin: 0 0 3mm 0; }
    .side-label { font-size: 9px; letter-spacing: .6px; text-transform: uppercase; color: rgba(229,231,235,0.75); margin: 0; }
    .side-value { font-size: 10.5px; font-weight: 700; color: #fff; margin: 0 0 2mm 0; word-break: break-word; overflow-wrap: anywhere; }
    .side-small { font-size: 10px; color: rgba(229,231,235,0.9); margin: 0 0 2mm 0; word-break: break-word; overflow-wrap: anywhere; }

    .main-title { font-size: 30px; font-weight: 900; letter-spacing: 1.4px; margin: 0; }
    .meta { margin-top: 2mm; font-size: 10.5px; color: #6b7280; }
    .accent { height: 4mm; background: #f59e0b; margin: 5mm 0 6mm; }

    .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 800; }
    .badge-success { background: #16a34a; color: #fff; }
    .badge-warning { background: #f59e0b; color: #111; }
    .badge-primary { background: #2563eb; color: #fff; }
    .badge-danger { background: #dc2626; color: #fff; }
    .badge-secondary { background: #6b7280; color: #fff; }
    .badge-dark { background: #111827; color: #fff; }

    .total-block { border: 1px solid #e5e7eb; padding: 4mm 4mm; margin: 0 0 6mm; }
    .total-label { font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; margin: 0; }
    .total-value { font-size: 16px; font-weight: 900; margin: 1mm 0 0; }

    .section-title { font-size: 11px; font-weight: 900; margin: 0 0 3mm 0; }

    table.items { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.items th, table.items td { border: 1px solid #e5e7eb; padding: 7px 6px; }
    table.items th { background: #f59e0b; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .6px; }
    .right { text-align: right; }
    .num { white-space: nowrap; font-size: 9.5px; }
    .desc { word-break: break-word; overflow-wrap: anywhere; }

    table.summary { width: 100%; border-collapse: collapse; margin-top: 4mm; }
    table.summary td { padding: 6px 6px; }
    .sum-k { text-align: right; color: #6b7280; font-weight: 800; }
    .sum-v { text-align: right; font-weight: 900; }
    .grand { background: #f59e0b; color: #fff; padding: 7px 8px; }

    .sig { margin-top: 10mm; }
    .sig-line { border-top: 1px solid #9ca3af; height: 1px; margin-top: 14mm; }
    .foot { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 8mm; }

    .watermark { position: fixed; top: 52%; left: 12%; right: 12%; text-align: center; font-size: 58px; color: rgba(17,24,39,0.05); transform: rotate(-18deg); z-index: 0; }
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
  <div class="sheet">
    <table class="frame">
      <tr>
        <td class="side">
          <?php if (!empty($logoDataUri)): ?>
            <img class="brand-logo" src="<?= $logoDataUri ?>" alt="Logo">
          <?php endif; ?>
          <div class="brand-name"><?= htmlspecialchars($siteName ?? '-') ?></div>

          <div class="side-divider"></div>

          <div class="side-title">Invoice To</div>
          <p class="side-value"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_name'] ?? '-') ?: '-') : ($invoice['tenant_name'] ?? '-')) ?></p>
          <p class="side-small"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_email'] ?? '-') ?: '-') : ($invoice['tenant_email'] ?? '-')) ?></p>
          <?php if (!empty($realtorContext) && (!empty($realtorContext['client_phone']))): ?>
            <p class="side-small"><?= htmlspecialchars((string)$realtorContext['client_phone']) ?></p>
          <?php endif; ?>
          <?php if (!empty($realtorContext) && (!empty($realtorContext['listing_title']) || !empty($realtorContext['listing_location']))): ?>
            <p class="side-small"><?= htmlspecialchars((string)($realtorContext['listing_title'] ?? '')) ?><?= !empty($realtorContext['listing_location']) ? (' • ' . htmlspecialchars((string)$realtorContext['listing_location'])) : '' ?></p>
          <?php endif; ?>

          <div class="side-divider"></div>

          <div class="side-title">From</div>
          <p class="side-label">Company</p>
          <p class="side-value"><?= htmlspecialchars($siteName ?? '-') ?></p>
          <p class="side-label">Email</p>
          <p class="side-small"><?= htmlspecialchars($settings['site_email'] ?? '-') ?></p>
          <p class="side-label">Phone</p>
          <p class="side-small"><?= htmlspecialchars($settings['site_phone'] ?? '-') ?></p>

          <div class="side-divider"></div>

          <div class="side-title">Terms & Notes</div>
          <p class="side-small">
            <?php if (!empty($invoice['notes'])): ?>
              <?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?>
            <?php else: ?>
              Payment is due by the due date indicated. Late payments may incur penalties.
            <?php endif; ?>
          </p>
        </td>

        <td class="main">
          <div class="main-title">INVOICE</div>
          <div class="meta">
            Invoice No: <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?>
            • Date: <?= htmlspecialchars($invoice['issue_date']) ?>
            • Status: <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst((string)($invoice['status'] ?? 'draft'))) ?></span>
          </div>

          <div class="accent"></div>

          <div class="total-block">
            <p class="total-label">Total Due</p>
            <p class="total-value">Ksh <?= number_format((float)($invoice['total'] ?? 0), 2) ?></p>
          </div>

          <div class="section-title">Product & Services</div>
          <table class="items">
            <thead>
              <tr>
                <th style="width:55%">Description</th>
                <th style="width:10%" class="right">Qty</th>
                <th style="width:17%" class="right">Unit Price</th>
                <th style="width:18%" class="right">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($invoice['items'] ?? []) as $it): ?>
                <tr>
                  <td class="desc"><?= htmlspecialchars((string)($it['description'] ?? '')) ?></td>
                  <td class="right num"><?= number_format((float)($it['quantity'] ?? 0), 2) ?></td>
                  <td class="right num"><?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
                  <td class="right num"><?= number_format((float)($it['line_total'] ?? 0), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <table class="summary">
            <tr>
              <td class="sum-k" style="width:70%">Subtotal</td>
              <td class="sum-v" style="width:30%">Ksh <?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></td>
            </tr>
            <?php if (!empty($invoice['tax_rate'])): ?>
            <tr>
              <td class="sum-k">Tax (<?= number_format((float)$invoice['tax_rate'], 2) ?>%)</td>
              <td class="sum-v">Ksh <?= number_format((float)($invoice['tax_amount'] ?? 0), 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
              <td class="sum-k grand">Grand Total</td>
              <td class="sum-v grand">Ksh <?= number_format((float)($invoice['total'] ?? 0), 2) ?></td>
            </tr>
          </table>

          <div class="sig">
            <div class="section-title">Authorized Signature</div>
            <div class="sig-line"></div>
            <div class="meta">Sign and return if required.</div>
          </div>

          <div class="foot">
            Generated by <?= htmlspecialchars($siteName ?? 'RentSmart') ?> — <?= date('Y-m-d H:i') ?>
          </div>
        </td>
      </tr>
    </table>
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
