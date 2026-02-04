<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-file-earmark-text text-primary me-2"></i>Statements</h1>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <h5>Balance Sheet</h5>
          <form method="get" action="<?= BASE_URL ?>/accounting/balance-sheet" class="d-flex gap-2 align-items-end">
            <div>
              <label class="form-label">As of</label>
              <input type="date" name="end" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn btn-primary" type="submit">View</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <h5>Profit & Loss</h5>
          <form method="get" action="<?= BASE_URL ?>/accounting/profit-loss" class="row g-2 align-items-end">
            <div class="col">
              <label class="form-label">Start</label>
              <input type="date" name="start" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col">
              <label class="form-label">End</label>
              <input type="date" name="end" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-auto">
              <button class="btn btn-primary" type="submit">View</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
