<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-file-earmark-text text-primary me-2"></i>Statements</h1>
      <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>/accounting/statements">
        <input type="date" class="form-control" name="start" value="<?= htmlspecialchars($start ?? ($_GET['start'] ?? date('Y-m-01'))) ?>">
        <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($end ?? ($_GET['end'] ?? date('Y-m-d'))) ?>">
        <button class="btn btn-outline-primary" type="submit">Filter</button>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Money In</div>
          <div class="h4 mb-0 text-success"><?= number_format((float)($totalIn ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Money Out</div>
          <div class="h4 mb-0 text-danger"><?= number_format((float)($totalOut ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Net Cash Movement</div>
          <div class="h4 mb-0 <?= ((float)($net ?? 0)) >= 0 ? 'text-success' : 'text-danger' ?>">
            <?= number_format((float)($net ?? 0), 2) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Statement of Money In / Money Out</strong>
      <span class="text-muted small">
        <?= htmlspecialchars($start ?? date('Y-m-01')) ?> to <?= htmlspecialchars($end ?? date('Y-m-d')) ?>
      </span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Reference</th>
              <th>Type</th>
              <th>Description</th>
              <th class="text-end">Money In</th>
              <th class="text-end">Money Out</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transactions)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">No transactions found for this period</td>
              </tr>
            <?php else: ?>
              <?php foreach ($transactions as $t): ?>
                <tr>
                  <td><?= htmlspecialchars($t['date'] ?? '') ?></td>
                  <td><?= htmlspecialchars($t['reference'] ?? '') ?></td>
                  <td>
                    <span class="badge <?= ($t['direction'] ?? '') === 'in' ? 'bg-success' : 'bg-danger' ?>">
                      <?= ($t['direction'] ?? '') === 'in' ? 'Money In' : 'Money Out' ?>
                    </span>
                    <span class="text-muted small ms-1"><?= htmlspecialchars($t['category'] ?? '') ?></span>
                  </td>
                  <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
                  <td class="text-end">
                    <?php if (($t['direction'] ?? '') === 'in'): ?>
                      <?= number_format((float)($t['amount'] ?? 0), 2) ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <?php if (($t['direction'] ?? '') === 'out'): ?>
                      <?= number_format((float)($t['amount'] ?? 0), 2) ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr class="bg-light fw-bold">
                <td colspan="4">Totals</td>
                <td class="text-end"><?= number_format((float)($totalIn ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float)($totalOut ?? 0), 2) ?></td>
              </tr>
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
