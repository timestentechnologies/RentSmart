<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-journal-text text-primary me-2"></i>Chart of Accounts</h1>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
      <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><strong>Add Account</strong></div>
        <div class="card-body">
          <form method="post" action="<?= BASE_URL ?>/accounting/accounts/store">
            <?= csrf_field() ?>
            <div class="mb-2">
              <label class="form-label">Code</label>
              <input type="text" name="code" class="form-control" required placeholder="e.g. 1000">
            </div>
            <div class="mb-2">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Cash">
            </div>
            <div class="mb-3">
              <label class="form-label">Type</label>
              <select name="type" class="form-select" required>
                <option value="">Select type</option>
                <option value="asset">Asset</option>
                <option value="liability">Liability</option>
                <option value="equity">Equity</option>
                <option value="revenue">Revenue</option>
                <option value="expense">Expense</option>
              </select>
            </div>
            <div class="text-end">
              <button class="btn btn-primary" type="submit">Create</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Accounts</strong>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Type</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($accounts ?? []) as $a): ?>
                  <tr>
                    <td><?= htmlspecialchars($a['code']) ?></td>
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($a['type'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($accounts)): ?>
                  <tr><td colspan="3" class="text-center py-4 text-muted">No accounts yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
