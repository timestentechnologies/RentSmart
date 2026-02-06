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

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Invoice no, tenant, notes">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Status</label>
          <?php $st = $_GET['status'] ?? 'all'; ?>
          <select class="form-select" name="status">
            <option value="all" <?= ($st==='all')?'selected':'' ?>>All</option>
            <option value="draft" <?= ($st==='draft')?'selected':'' ?>>Draft</option>
            <option value="sent" <?= ($st==='sent')?'selected':'' ?>>Due</option>
            <option value="partial" <?= ($st==='partial')?'selected':'' ?>>Partial</option>
            <option value="paid" <?= ($st==='paid')?'selected':'' ?>>Paid</option>
            <option value="void" <?= ($st==='void')?'selected':'' ?>>Void</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Visibility</label>
          <?php $vis = $_GET['visibility'] ?? 'active'; ?>
          <select class="form-select" name="visibility">
            <option value="active" <?= ($vis==='active')?'selected':'' ?>>Active</option>
            <option value="archived" <?= ($vis==='archived')?'selected':'' ?>>Archived</option>
            <option value="all" <?= ($vis==='all')?'selected':'' ?>>All</option>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Tenant</label>
          <?php $tid = $_GET['tenant_id'] ?? ''; ?>
          <select class="form-select" name="tenant_id">
            <option value="">All tenants</option>
            <?php foreach (($tenants ?? []) as $t): ?>
              <option value="<?= (int)$t['id'] ?>" <?= ((string)$tid === (string)$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['name'] ?? ('Tenant #'.$t['id'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">From</label>
          <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">To</label>
          <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button class="btn btn-primary w-100" type="submit">Filter</button>
          <a class="btn btn-light w-100" href="<?= BASE_URL ?>/invoices">Clear</a>
        </div>
      </form>
    </div>
  </div>

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
                <?php
                  $statusLabel = ($inv['status'] === 'sent') ? 'Due' : ucfirst($inv['status']);
                  $archived = !empty($inv['archived_at']);
                ?>
                <td>
                  <span class="badge bg-secondary"><?= htmlspecialchars($statusLabel) ?></span>
                  <?php if ($archived): ?>
                    <span class="badge bg-light text-dark border">Archived</span>
                  <?php endif; ?>
                </td>
                <td><?= !empty($inv['posted_at']) ? date('Y-m-d', strtotime($inv['posted_at'])) : '-' ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?= BASE_URL ?>/invoices/show/<?= (int)$inv['id'] ?>" class="btn btn-outline-primary">View</a>
                    <a href="<?= BASE_URL ?>/invoices/pdf/<?= (int)$inv['id'] ?>" class="btn btn-outline-secondary">PDF</a>
                    <?php $isAuto = stripos((string)($inv['notes'] ?? ''), 'Auto-created') !== false || stripos((string)($inv['notes'] ?? ''), 'AUTO') !== false; ?>
                    <?php $label = $isAuto ? 'Void & Archive' : 'Archive'; ?>
                    <a href="<?= BASE_URL ?>/invoices/delete/<?= (int)$inv['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('<?= $isAuto ? 'Void & archive' : 'Archive' ?> this invoice?')"><?= htmlspecialchars($label) ?></a>
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
