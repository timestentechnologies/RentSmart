<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?></h1>
      <div class="btn-group">
        <?php if (!empty($prevInvoiceId)): ?>
          <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/invoices/show/<?= (int)$prevInvoiceId ?>">Previous</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/invoices">Back</a>
        <?php if (!empty($nextInvoiceId)): ?>
          <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/invoices/show/<?= (int)$nextInvoiceId ?>">Next</a>
        <?php endif; ?>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/invoices/pdf/<?= (int)$invoice['id'] ?>">Download PDF</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/invoices/email/<?= (int)$invoice['id'] ?>">Email PDF</a>
        <?php if (empty($hidePostToLedger) && empty($invoice['posted_at'])): ?>
          <a class="btn btn-outline-success" href="<?= BASE_URL ?>/invoices/post/<?= (int)$invoice['id'] ?>">Post to Ledger</a>
        <?php endif; ?>
        <a class="btn btn-outline-danger" href="<?= BASE_URL ?>/invoices/delete/<?= (int)$invoice['id'] ?>" onclick="return confirm('Delete this invoice?')">Delete</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <h6>Bill To</h6>
          <div><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_name'] ?? '-') ?: '-') : ($invoice['tenant_name'] ?? '-')) ?></div>
          <div class="text-muted small"><?= htmlspecialchars(!empty($realtorContext) ? (($realtorContext['client_email'] ?? '-') ?: '-') : ($invoice['tenant_email'] ?? '-')) ?></div>
          <?php if (!empty($realtorContext) && (!empty($realtorContext['listing_title']) || !empty($realtorContext['listing_location']))): ?>
            <div class="text-muted small">
              <?= htmlspecialchars((string)($realtorContext['listing_title'] ?? '')) ?>
              <?= !empty($realtorContext['listing_location']) ? (' • ' . htmlspecialchars((string)$realtorContext['listing_location'])) : '' ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-md-6 text-md-end">
          <div><strong>Issue:</strong> <?= htmlspecialchars($invoice['issue_date']) ?></div>
          <div><strong>Due:</strong> <?= htmlspecialchars($invoice['due_date'] ?? '-') ?></div>
          <?php
            $st = strtolower((string)($invoice['status'] ?? 'draft'));
            $statusBadge = 'secondary';
            if ($st === 'paid') $statusBadge = 'success';
            elseif ($st === 'partial') $statusBadge = 'warning';
            elseif ($st === 'sent') $statusBadge = 'primary';
            elseif ($st === 'overdue') $statusBadge = 'danger';
            elseif ($st === 'void') $statusBadge = 'dark';
          ?>
          <div><strong>Status:</strong> <span class="badge bg-<?= $statusBadge ?><?= $statusBadge === 'warning' ? ' text-dark' : '' ?>"><?= htmlspecialchars(ucfirst($invoice['status'])) ?></span></div>
          <div><strong>Posted:</strong> <?= !empty($invoice['posted_at']) ? date('Y-m-d', strtotime($invoice['posted_at'])) : '-' ?></div>
        </div>
      </div>
      <?php if (empty($realtorContext) && !empty($paymentStatus)): ?>
      <div class="alert alert-light border mt-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
          <strong class="me-2">Payment Status for <?= htmlspecialchars($paymentStatus['month_label']) ?>:</strong>
          <?php
            $rentBadge = 'secondary';
            if ($paymentStatus['rent']['status'] === 'paid') $rentBadge = 'success';
            elseif ($paymentStatus['rent']['status'] === 'advance') $rentBadge = 'info';
            elseif ($paymentStatus['rent']['status'] === 'due') $rentBadge = 'danger';
            $utilBadge = ($paymentStatus['utilities']['status'] ?? 'due') === 'paid' ? 'success' : 'warning';
            $maintBadge = ($paymentStatus['maintenance']['status'] ?? 'due') === 'paid' ? 'success' : 'warning';
          ?>
          <span class="badge bg-<?= $rentBadge ?>">Rent: <?= htmlspecialchars(ucfirst($paymentStatus['rent']['status'])) ?> (Paid <?= number_format((float)$paymentStatus['rent']['paid'],2) ?> / Due <?= number_format((float)$paymentStatus['rent']['amount'],2) ?>)</span>
          <span class="badge bg-<?= $utilBadge ?>">Utilities: <?= htmlspecialchars(ucfirst($paymentStatus['utilities']['status'])) ?> (Paid <?= number_format((float)$paymentStatus['utilities']['paid'],2) ?> / Due <?= number_format((float)($paymentStatus['utilities']['due'] ?? ($paymentStatus['utilities']['amount'] ?? 0)),2) ?>)</span>
          <span class="badge bg-<?= $maintBadge ?>">Maintenance: <?= htmlspecialchars(ucfirst($paymentStatus['maintenance']['status'] ?? 'due')) ?> (Paid <?= number_format((float)($paymentStatus['maintenance']['paid'] ?? 0),2) ?> / Due <?= number_format((float)($paymentStatus['maintenance']['due'] ?? ($paymentStatus['maintenance']['amount'] ?? 0)),2) ?>)</span>
        </div>
      </div>
      <?php endif; ?>
      <hr>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Description</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Line Total</th>
            </tr>
          </thead>
          <tbody>
            <?php $subtotal = 0.0; foreach (($invoice['items'] ?? []) as $it): $subtotal += (float)$it['line_total']; ?>
              <tr>
                <td><?= htmlspecialchars($it['description']) ?></td>
                <td class="text-end"><?= number_format((float)$it['quantity'], 2) ?></td>
                <td class="text-end"><?= number_format((float)$it['unit_price'], 2) ?></td>
                <td class="text-end"><?= number_format((float)$it['line_total'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" class="text-end">Subtotal</th>
              <th class="text-end"><?= number_format((float)$invoice['subtotal'], 2) ?></th>
            </tr>
            <?php if (!empty($invoice['tax_rate'])): ?>
            <tr>
              <th colspan="3" class="text-end">Tax (<?= number_format((float)$invoice['tax_rate'], 2) ?>%)</th>
              <th class="text-end"><?= number_format((float)$invoice['tax_amount'], 2) ?></th>
            </tr>
            <?php endif; ?>
            <tr>
              <th colspan="3" class="text-end">Total</th>
              <th class="text-end"><?= number_format((float)$invoice['total'], 2) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <?php if (empty($realtorContext) && !empty($maintenancePayments)): ?>
      <div class="mt-4">
        <h6>Maintenance Payments</h6>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Date</th>
                <th>Method</th>
                <th>Status</th>
                <th>M-Pesa Code</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($maintenancePayments as $p): ?>
                <tr>
                  <td><?= htmlspecialchars((string)($p['payment_date'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)($p['payment_method'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)($p['status'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)($p['transaction_code'] ?? '-')) ?></td>
                  <td class="text-end">Ksh <?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($invoice['notes'])): ?>
      <div class="mt-3">
        <h6>Notes</h6>
        <p><?= nl2br(htmlspecialchars($displayNotes ?? $invoice['notes'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
