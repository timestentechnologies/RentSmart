<?php /* @var $invoice array, $logoDataUri string|null, $siteName string, $settings array */ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?></title>
  <style>
    @page { margin: 10px; }
    html, body { height: 100%; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12.5px; line-height: 1.35; color: #111827; margin: 0; padding: 0; background: #e5e7eb; }
    .wrap {
      width: 100%;
      background: #f9fafb;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid #d1d5db;
    }

    .layout { width: 100%; border-collapse: collapse; table-layout: fixed; height: 100%; }
    .layout td { vertical-align: top; padding: 0; }

    .left {
      width: 34%;
      background: #111827;
      color: #e5e7eb;
      padding: 24px 18px 18px 18px;
    }
    .right {
      width: 66%;
      background: #f9fafb;
      padding: 24px 20px 18px 20px;
    }

    .logo { max-height: 64px; margin: 0 0 12px; }
    .brand { font-size: 16px; font-weight: 800; margin-top: 6px; color: #fff; }

    .left-title { font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #f59e0b; margin: 18px 0 10px; }
    .left-box { border: 1px solid rgba(255,255,255,0.14); padding: 12px 10px; background: rgba(255,255,255,0.035); border-radius: 10px; }
    .left-row { margin: 0 0 8px 0; }
    .left-label { font-size: 10px; letter-spacing: .8px; text-transform: uppercase; color: rgba(229,231,235,0.75); }
    .left-value { font-size: 12.5px; font-weight: 600; color: #fff; }
    .left-small { font-size: 12px; color: rgba(229,231,235,0.88); }

    .inv-title { font-size: 34px; font-weight: 900; letter-spacing: 1.8px; margin: 0; }
    .meta { margin-top: 6px; font-size: 12px; color: #6b7280; }
    .accent-bar { height: 10px; background: #f59e0b; margin: 12px 0 16px; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-success { background: #16a34a; color: #fff; }
    .badge-warning { background: #f59e0b; color: #111; }
    .badge-primary { background: #2563eb; color: #fff; }
    .badge-danger { background: #dc2626; color: #fff; }
    .badge-secondary { background: #6b7280; color: #fff; }
    .badge-dark { background: #111827; color: #fff; }

    .total-due { margin-top: 10px; border: 1px solid #e5e7eb; background: #fff; padding: 12px 12px; border-radius: 10px; }
    .total-due .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; }
    .total-due .amt { font-size: 18px; font-weight: 800; color: #111827; margin-top: 2px; }

    .section-title { font-size: 13px; font-weight: 800; margin: 18px 0 8px; }

    table.items { width: 100%; border-collapse: collapse; }
    table.items th, table.items td { border: 1px solid #e5e7eb; padding: 10px 8px; }
    table.items th { background: #f59e0b; color: #fff; text-transform: uppercase; letter-spacing: .6px; font-size: 11px; }
    table.items td { background: #fff; }
    .right-align { text-align: right; }
    .amount { font-weight: 800; color: #0f766e; }

    .totals { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .totals td { padding: 7px 8px; border: 1px solid #e5e7eb; background: #fff; }
    .totals .label { text-align: right; color: #6b7280; font-weight: 700; }
    .totals .value { text-align: right; font-weight: 800; }
    .totals .grand { background: #111827; color: #fff; }
    .totals .grand .label, .totals .grand .value { color: #fff; }

    .signature { margin-top: 18px; }
    .sig-line { border-top: 1px solid #9ca3af; height: 1px; margin-top: 46px; }
    .sig-note { margin-top: 6px; font-size: 11px; color: #6b7280; }

    .notes { margin-top: 16px; font-size: 12px; color: #374151; }
    .footer { margin-top: 18px; text-align: center; font-size: 11px; color: #9ca3af; }

    .watermark {
      position: fixed; top: 45%; left: 4%; right: 4%; text-align: center;
      font-size: 72px; color: rgba(17,24,39,0.05); transform: rotate(-18deg);
      z-index: 0;
    }
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
  <div class="wrap">
    <table class="layout">
      <tr>
        <td class="left">
          <?php if (!empty($logoDataUri)): ?>
            <img class="logo" src="<?= $logoDataUri ?>" alt="Logo">
          <?php endif; ?>
          <div class="brand"><?= htmlspecialchars($siteName ?? '-') ?></div>

          <div class="left-title">From</div>
          <div class="left-box">
            <div class="left-row">
              <div class="left-label">Company</div>
              <div class="left-value"><?= htmlspecialchars($siteName ?? '-') ?></div>
            </div>
            <div class="left-row">
              <div class="left-label">Address</div>
              <div class="left-small"><?= htmlspecialchars($settings['site_address'] ?? '-') ?></div>
            </div>
            <div class="left-row">
              <div class="left-label">Phone</div>
              <div class="left-small"><?= htmlspecialchars($settings['site_phone'] ?? '-') ?></div>
            </div>
            <div class="left-row">
              <div class="left-label">Email</div>
              <div class="left-small"><?= htmlspecialchars($settings['site_email'] ?? '-') ?></div>
            </div>
          </div>

          <div class="left-title">Bill To</div>
          <div class="left-box">
            <div class="left-row">
              <div class="left-label">Customer</div>
              <div class="left-value"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_name'] ?? '-') ?: '-') : ($invoice['tenant_name'] ?? '-')) ?></div>
            </div>
            <div class="left-row">
              <div class="left-label">Email</div>
              <div class="left-small"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_email'] ?? '-') ?: '-') : ($invoice['tenant_email'] ?? '-')) ?></div>
            </div>
            <?php if (!empty($realtorContext) && (!empty($realtorContext['listing_title']) || !empty($realtorContext['listing_location']))): ?>
            <div class="left-row">
              <div class="left-label">Listing</div>
              <div class="left-small">
                <?= htmlspecialchars((string)($realtorContext['listing_title'] ?? '')) ?>
                <?= !empty($realtorContext['listing_location']) ? (' • ' . htmlspecialchars((string)$realtorContext['listing_location'])) : '' ?>
              </div>
            </div>
            <?php endif; ?>
            <div class="left-row">
              <div class="left-label">Due Date</div>
              <div class="left-small"><?= htmlspecialchars($invoice['due_date'] ?? '-') ?></div>
            </div>
          </div>

          <div class="left-title">Terms & Notes</div>
          <div class="left-box">
            <div class="left-small">
              <?php if (!empty($invoice['notes'])): ?>
                <?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?>
              <?php else: ?>
                Payment is due by the due date indicated. Late payments may incur penalties as per your agreement.
              <?php endif; ?>
            </div>
          </div>
        </td>

        <td class="right">
          <div style="text-align:right;">
            <div class="inv-title">INVOICE</div>
            <div class="meta">
              Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?>
              • Date: <?= htmlspecialchars($invoice['issue_date']) ?>
              • Status: <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst((string)($invoice['status'] ?? 'draft'))) ?></span>
            </div>
          </div>
          <div class="accent-bar"></div>

          <div class="total-due">
            <div class="lbl">Total Due</div>
            <div class="amt">Ksh <?= number_format((float)($invoice['total'] ?? 0), 2) ?></div>
          </div>

          <div class="section-title">Product & Services</div>
          <table class="items">
            <thead>
              <tr>
                <th style="width:58%">Description</th>
                <th style="width:12%" class="right-align">Qty</th>
                <th style="width:15%" class="right-align">Unit Price</th>
                <th style="width:15%" class="right-align">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($invoice['items'] ?? []) as $it): ?>
                <tr>
                  <td><?= htmlspecialchars((string)($it['description'] ?? '')) ?></td>
                  <td class="right-align"><?= number_format((float)($it['quantity'] ?? 0), 2) ?></td>
                  <td class="right-align"><?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
                  <td class="right-align"><?= number_format((float)($it['line_total'] ?? 0), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <table class="totals">
            <tr>
              <td class="label" style="width:80%">Subtotal</td>
              <td class="value" style="width:20%">Ksh <?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></td>
            </tr>
            <?php if (!empty($invoice['tax_rate'])): ?>
            <tr>
              <td class="label">Tax (<?= number_format((float)$invoice['tax_rate'], 2) ?>%)</td>
              <td class="value">Ksh <?= number_format((float)($invoice['tax_amount'] ?? 0), 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand">
              <td class="label">Grand Total</td>
              <td class="value">Ksh <?= number_format((float)($invoice['total'] ?? 0), 2) ?></td>
            </tr>
          </table>

          <div class="signature">
            <div class="section-title">Authorized Signature</div>
            <div class="sig-line"></div>
            <div class="sig-note">Sign and return if required.</div>
          </div>

          <?php if (!empty($invoice['notes'])): ?>
            <div class="notes">
              <div class="section-title">Notes</div>
              <?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?>
            </div>
          <?php endif; ?>

          <div class="footer">
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
