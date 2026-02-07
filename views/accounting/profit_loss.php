<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Profit & Loss</h1>
      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/accounting/profit-loss/export-csv?start=<?= urlencode($_GET['start'] ?? date('Y-m-01')) ?>&end=<?= urlencode($_GET['end'] ?? date('Y-m-d')) ?>">Export Excel</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/accounting/profit-loss/export-pdf?start=<?= urlencode($_GET['start'] ?? date('Y-m-01')) ?>&end=<?= urlencode($_GET['end'] ?? date('Y-m-d')) ?>">Export PDF</a>
        <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>/accounting/profit-loss">
          <input type="date" class="form-control" name="start" value="<?= htmlspecialchars($_GET['start'] ?? date('Y-m-01')) ?>">
          <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($_GET['end'] ?? date('Y-m-d')) ?>">
          <button class="btn btn-outline-primary" type="submit">Filter</button>
        </form>
      </div>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><strong>Revenue</strong></div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <tbody>
              <?php foreach (($revenue['rows'] ?? []) as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['code'] . ' - ' . $r['name']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['balance'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="bg-light fw-bold">
                <td>Total Revenue</td>
                <td class="text-end"><?= number_format((float)($revenue['total'] ?? 0), 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><strong>Expenses</strong></div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <tbody>
              <?php foreach (($expenses['rows'] ?? []) as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['code'] . ' - ' . $r['name']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['balance'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="bg-light fw-bold">
                <td>Total Expenses</td>
                <td class="text-end"><?= number_format((float)($expenses['total'] ?? 0), 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <?php $net = (float)($revenue['total'] ?? 0) - (float)($expenses['total'] ?? 0); ?>
      <div class="alert <?= $net >= 0 ? 'alert-success' : 'alert-danger' ?> mt-3">
        <div class="d-flex justify-content-between"><strong>Net Income</strong><span><?= number_format($net, 2) ?></span></div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
