<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Invoice <?= htmlspecialchars($invoice['number'] ?? ('#'.$invoice['id'])) ?></h1>
      <div class="btn-group">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/invoices">Back</a>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/invoices/pdf/<?= (int)$invoice['id'] ?>">Download PDF</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/invoices/email/<?= (int)$invoice['id'] ?>">Email PDF</a>
        <?php if (empty($invoice['posted_at'])): ?>
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
          <div><?= htmlspecialchars($invoice['tenant_name'] ?? '-') ?></div>
          <div class="text-muted small"><?= htmlspecialchars($invoice['tenant_email'] ?? '-') ?></div>
        </div>
        <div class="col-md-6 text-md-end">
          <div><strong>Issue:</strong> <?= htmlspecialchars($invoice['issue_date']) ?></div>
          <div><strong>Due:</strong> <?= htmlspecialchars($invoice['due_date'] ?? '-') ?></div>
          <div><strong>Status:</strong> <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($invoice['status'])) ?></span></div>
          <div><strong>Posted:</strong> <?= !empty($invoice['posted_at']) ? date('Y-m-d', strtotime($invoice['posted_at'])) : '-' ?></div>
        </div>
      </div>
      <?php if (!empty($paymentStatus)): ?>
      <div class="alert alert-light border mt-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
          <strong class="me-2">Payment Status for <?= htmlspecialchars($paymentStatus['month_label']) ?>:</strong>
          <?php
            $rentBadge = 'secondary';
            if ($paymentStatus['rent']['status'] === 'paid') $rentBadge = 'success';
            elseif ($paymentStatus['rent']['status'] === 'advance') $rentBadge = 'info';
            elseif ($paymentStatus['rent']['status'] === 'due') $rentBadge = 'danger';
            $utilBadge = ($paymentStatus['utilities']['status'] ?? 'due') === 'paid' ? 'success' : 'warning';
          ?>
          <span class="badge bg-<?= $rentBadge ?>">Rent: <?= htmlspecialchars(ucfirst($paymentStatus['rent']['status'])) ?> (Paid <?= number_format((float)$paymentStatus['rent']['paid'],2) ?> / Due <?= number_format((float)$paymentStatus['rent']['amount'],2) ?>)</span>
          <span class="badge bg-<?= $utilBadge ?>">Utilities: <?= htmlspecialchars(ucfirst($paymentStatus['utilities']['status'])) ?> (Paid <?= number_format((float)$paymentStatus['utilities']['paid'],2) ?>)</span>
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
      <?php if (!empty($invoice['notes'])): ?>
      <div class="mt-3">
        <h6>Notes</h6>
        <p><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
