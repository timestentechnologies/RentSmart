<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Invoices</h1>
      <a href="<?= BASE_URL ?>/invoices/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Invoice</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
      <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="bg-light">
            <tr>
              <th>No.</th>
              <th>Tenant</th>
              <th>Issued</th>
              <th>Due</th>
              <th class="text-end">Total</th>
              <th>Status</th>
              <th>Posted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($invoices ?? []) as $inv): ?>
              <tr>
                <td><?= htmlspecialchars($inv['number']) ?></td>
                <td><?= htmlspecialchars($inv['tenant_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($inv['issue_date']) ?></td>
                <td><?= htmlspecialchars($inv['due_date'] ?? '-') ?></td>
                <td class="text-end"><?= number_format((float)$inv['total'], 2) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($inv['status'])) ?></span></td>
                <td><?= !empty($inv['posted_at']) ? date('Y-m-d', strtotime($inv['posted_at'])) : '-' ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?= BASE_URL ?>/invoices/show/<?= (int)$inv['id'] ?>" class="btn btn-outline-primary">View</a>
                    <a href="<?= BASE_URL ?>/invoices/pdf/<?= (int)$inv['id'] ?>" class="btn btn-outline-secondary">PDF</a>
                    <a href="<?= BASE_URL ?>/invoices/delete/<?= (int)$inv['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this invoice?')">Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($invoices)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No invoices</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
