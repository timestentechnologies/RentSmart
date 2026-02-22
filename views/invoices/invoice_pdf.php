<?php /* @var $invoice array, $logoDataUri string|null, $siteName string, $settings array */ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?></title>
  <style>
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12.5px; color: #222; position: relative; }
    .header { text-align: center; margin-bottom: 18px; }
    .logo { max-height: 60px; margin-bottom: 6px; }
    .title { font-size: 22px; font-weight: bold; letter-spacing: .5px; }
    .meta { font-size: 12px; color: #666; }
    .section { margin-bottom: 12px; }
    .section-title { font-weight: bold; margin-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px 6px; border: 1px solid #ddd; }
    th { background: #f7f7f7; text-align: left; }
    .amount { font-weight: bold; color: #1a7e1a; }
    .right { text-align: right; }
    .small { font-size: 12px; color: #666; }
    .footer { text-align: center; font-size: 12px; color: #888; margin-top: 16px; }
    .flex { display: table; width: 100%; }
    .col { display: table-cell; vertical-align: top; padding: 0 6px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
    .badge-success { background: #198754; color: #fff; }
    .badge-warning { background: #ffc107; color: #111; }
    .badge-primary { background: #0d6efd; color: #fff; }
    .badge-danger { background: #dc3545; color: #fff; }
    .badge-secondary { background: #6c757d; color: #fff; }
    .badge-dark { background: #212529; color: #fff; }
    .watermark {
      position: fixed; top: 35%; left: 10%; right: 10%; text-align: center;
      font-size: 64px; color: rgba(0,0,0,0.06); transform: rotate(-20deg);
      z-index: 0;
    }
    .no-border td, .no-border th { border: none; }
  </style>
</head>
<body>
  <div class="watermark"><?= htmlspecialchars($siteName ?? 'RentSmart') ?></div>
  <div class="header">
    <?php if (!empty($logoDataUri)): ?>
      <img class="logo" src="<?= $logoDataUri ?>" alt="Logo">
    <?php endif; ?>
    <div class="title">INVOICE</div>
    <?php
      $st = strtolower((string)($invoice['status'] ?? 'draft'));
      $statusClass = 'badge-secondary';
      if ($st === 'paid') $statusClass = 'badge-success';
      elseif ($st === 'partial') $statusClass = 'badge-warning';
      elseif ($st === 'sent') $statusClass = 'badge-primary';
      elseif ($st === 'overdue') $statusClass = 'badge-danger';
      elseif ($st === 'void') $statusClass = 'badge-dark';
    ?>
    <div class="meta">Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?> • Date: <?= htmlspecialchars($invoice['issue_date']) ?> • Status: <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst((string)($invoice['status'] ?? 'draft'))) ?></span></div>
  </div>

  <div class="section">
    <div class="flex">
      <div class="col" style="width:50%">
        <div class="section-title">From</div>
        <table class="no-border">
          <tr>
            <td><strong><?= htmlspecialchars($siteName ?? '-') ?></strong></td>
          </tr>
          <tr>
            <td><?= htmlspecialchars($settings['site_address'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>Phone: <?= htmlspecialchars($settings['site_phone'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>Email: <?= htmlspecialchars($settings['site_email'] ?? '-') ?></td>
          </tr>
        </table>
      </div>
      <div class="col" style="width:50%">
        <div class="section-title">Bill To</div>
        <table class="no-border">
          <tr>
            <td>
              <strong>
                <?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_name'] ?? '-') ?: '-') : ($invoice['tenant_name'] ?? '-')) ?>
              </strong>
            </td>
          </tr>
          <tr>
            <td><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_email'] ?? '-') ?: '-') : ($invoice['tenant_email'] ?? '-')) ?></td>
          </tr>
          <?php if (!empty($realtorContext) && (!empty($realtorContext['listing_title']) || !empty($realtorContext['listing_location']))): ?>
          <tr>
            <td>
              <?= htmlspecialchars((string)($realtorContext['listing_title'] ?? '')) ?>
              <?= !empty($realtorContext['listing_location']) ? (' • ' . htmlspecialchars((string)$realtorContext['listing_location'])) : '' ?>
            </td>
          </tr>
          <?php endif; ?>
          <tr>
            <td>Due Date: <?= htmlspecialchars($invoice['due_date'] ?? '-') ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Items</div>
    <table>
      <thead>
        <tr>
          <th>Description</th>
          <th class="right">Qty</th>
          <th class="right">Unit Price</th>
          <th class="right">Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($invoice['items'] ?? []) as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['description']) ?></td>
            <td class="right"><?= number_format((float)$it['quantity'], 2) ?></td>
            <td class="right"><?= number_format((float)$it['unit_price'], 2) ?></td>
            <td class="right"><?= number_format((float)$it['line_total'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" class="right">Subtotal</th>
          <th class="right"><?= number_format((float)$invoice['subtotal'], 2) ?></th>
        </tr>
        <?php if (!empty($invoice['tax_rate'])): ?>
        <tr>
          <th colspan="3" class="right">Tax (<?= number_format((float)$invoice['tax_rate'], 2) ?>%)</th>
          <th class="right"><?= number_format((float)$invoice['tax_amount'], 2) ?></th>
        </tr>
        <?php endif; ?>
        <tr>
          <th colspan="3" class="right">Total</th>
          <th class="right amount"><?= number_format((float)$invoice['total'], 2) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>

  <?php if (empty($realtorContext) && !empty($paymentStatus)): ?>
  <div class="section">
    <div class="section-title">Payment Status for <?= htmlspecialchars($paymentStatus['month_label']) ?></div>
    <table class="no-border">
      <tr>
        <td><strong>Rent:</strong></td>
        <td><?= htmlspecialchars(ucfirst($paymentStatus['rent']['status'])) ?> (Paid <?= number_format((float)$paymentStatus['rent']['paid'], 2) ?> / Due <?= number_format((float)$paymentStatus['rent']['amount'], 2) ?>)</td>
      </tr>
      <tr>
        <td><strong>Utilities:</strong></td>
        <td><?= htmlspecialchars(ucfirst($paymentStatus['utilities']['status'])) ?> (Paid <?= number_format((float)$paymentStatus['utilities']['paid'], 2) ?> / Due <?= number_format((float)($paymentStatus['utilities']['due'] ?? ($paymentStatus['utilities']['amount'] ?? 0)), 2) ?>)</td>
      </tr>
      <tr>
        <td><strong>Maintenance:</strong></td>
        <td><?= htmlspecialchars(ucfirst($paymentStatus['maintenance']['status'] ?? 'due')) ?> (Paid <?= number_format((float)($paymentStatus['maintenance']['paid'] ?? 0), 2) ?> / Due <?= number_format((float)($paymentStatus['maintenance']['due'] ?? ($paymentStatus['maintenance']['amount'] ?? 0)), 2) ?>)</td>
      </tr>
    </table>
  </div>
  <?php endif; ?>

  <?php if (empty($realtorContext) && !empty($maintenancePayments)): ?>
  <div class="section">
    <div class="section-title">Maintenance Payments</div>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Method</th>
          <th>Status</th>
          <th>M-Pesa Code</th>
          <th class="right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($maintenancePayments as $p): ?>
          <tr>
            <td><?= htmlspecialchars((string)($p['payment_date'] ?? '-')) ?></td>
            <td><?= htmlspecialchars((string)($p['payment_method'] ?? '-')) ?></td>
            <td><?= htmlspecialchars((string)($p['status'] ?? '-')) ?></td>
            <td><?= htmlspecialchars((string)($p['transaction_code'] ?? '-')) ?></td>
            <td class="right"><?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="section">
    <div class="flex">
      <div class="col" style="width:60%">
        <div class="section-title">Terms & Notes</div>
        <div class="small">
          <?php if (!empty($invoice['notes'])): ?>
            <?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?>
          <?php else: ?>
            Payment is due by the due date indicated. Late payments may incur penalties as per your agreement. Thank you for your business.
          <?php endif; ?>
        </div>
      </div>
      <div class="col" style="width:40%">
        <div class="section-title">Authorized Signature</div>
        <table class="no-border">
          <tr><td style="height:70px;border-bottom:1px solid #aaa;">&nbsp;</td></tr>
          <tr><td class="small">Sign and return if required.</td></tr>
        </table>
      </div>
    </div>
  </div>

  <?php if (!empty($invoice['notes'])): ?>
    <div class="section">
      <div class="section-title">Notes</div>
      <div class="small"><?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?></div>
    </div>
  <?php endif; ?>

  <div class="footer">
    Generated by <?= htmlspecialchars($siteName ?? 'RentSmart') ?> — <?= date('Y-m-d H:i') ?>
  </div>
</body>
</html>
