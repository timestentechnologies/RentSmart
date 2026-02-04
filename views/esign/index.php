<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-pen text-primary me-2"></i>E‑Signatures</h1>
      <a href="<?= BASE_URL ?>/esign/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Request</a>
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
              <th>Title</th>
              <th>Recipient</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($requests ?? []) as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['recipient_name'] ?? '-') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td>
                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at'] ?? 'now'))) ?></td>
                <td>
                  <a href="<?= BASE_URL ?>/esign/show/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">No requests</td></tr>
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
