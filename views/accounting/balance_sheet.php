<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i>Balance Sheet</h1>
      <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>/accounting/balance-sheet">
        <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($_GET['end'] ?? date('Y-m-d')) ?>">
        <button class="btn btn-outline-primary" type="submit">As of</button>
      </form>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><strong>Assets</strong></div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <tbody>
              <?php foreach (($assets['rows'] ?? []) as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['code'] . ' - ' . $r['name']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['balance'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="bg-light fw-bold">
                <td>Total Assets</td>
                <td class="text-end"><?= number_format((float)($assets['total'] ?? 0), 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card mb-3">
        <div class="card-header"><strong>Liabilities</strong></div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <tbody>
              <?php foreach (($liabilities['rows'] ?? []) as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['code'] . ' - ' . $r['name']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['balance'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="bg-light fw-bold">
                <td>Total Liabilities</td>
                <td class="text-end"><?= number_format((float)($liabilities['total'] ?? 0), 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><strong>Equity</strong></div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <tbody>
              <?php foreach (($equity['rows'] ?? []) as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['code'] . ' - ' . $r['name']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['balance'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="bg-light fw-bold">
                <td>Total Equity</td>
                <td class="text-end"><?= number_format((float)($equity['total'] ?? 0), 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="alert alert-info mt-3">
        <div class="d-flex justify-content-between"><span><strong>Check:</strong> Assets</span><span><?= number_format((float)($assets['total'] ?? 0), 2) ?></span></div>
        <div class="d-flex justify-content-between"><span>Liabilities + Equity</span><span><?= number_format((float)($liabilities['total'] ?? 0) + (float)($equity['total'] ?? 0), 2) ?></span></div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
