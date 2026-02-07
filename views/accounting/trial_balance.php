<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-columns-gap text-primary me-2"></i>Trial Balance</h1>
      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/accounting/trial-balance/export-csv?start=<?= urlencode($_GET['start'] ?? '') ?>&end=<?= urlencode($_GET['end'] ?? '') ?>">Export Excel</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/accounting/trial-balance/export-pdf?start=<?= urlencode($_GET['start'] ?? '') ?>&end=<?= urlencode($_GET['end'] ?? '') ?>">Export PDF</a>
        <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>/accounting/trial-balance">
          <input type="date" class="form-control" name="start" value="<?= htmlspecialchars($_GET['start'] ?? '') ?>">
          <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($_GET['end'] ?? '') ?>">
          <button class="btn btn-outline-primary" type="submit">Filter</button>
        </form>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="bg-light">
            <tr>
              <th>Code</th>
              <th>Name</th>
              <th>Type</th>
              <th class="text-end">Total Debit</th>
              <th class="text-end">Total Credit</th>
              <th class="text-end">Balance (Dr)</th>
              <th class="text-end">Balance (Cr)</th>
            </tr>
          </thead>
          <tbody>
            <?php $td=0;$tc=0;$bd=0;$bc=0; foreach (($rows ?? []) as $r): $td+=(float)$r['total_debit']; $tc+=(float)$r['total_credit']; $bd+=(float)($r['balance_debit']??0); $bc+=(float)($r['balance_credit']??0); ?>
            <tr>
              <td><?= htmlspecialchars($r['code']) ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars(ucfirst($r['type'])) ?></td>
              <td class="text-end"><?= number_format((float)$r['total_debit'], 2) ?></td>
              <td class="text-end"><?= number_format((float)$r['total_credit'], 2) ?></td>
              <td class="text-end"><?= number_format((float)($r['balance_debit'] ?? 0), 2) ?></td>
              <td class="text-end"><?= number_format((float)($r['balance_credit'] ?? 0), 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
              <tr><td colspan="7" class="text-center py-4 text-muted">No data</td></tr>
            <?php else: ?>
              <tr class="bg-light fw-bold">
                <td colspan="3" class="text-end">Totals</td>
                <td class="text-end"><?= number_format($td, 2) ?></td>
                <td class="text-end"><?= number_format($tc, 2) ?></td>
                <td class="text-end"><?= number_format($bd, 2) ?></td>
                <td class="text-end"><?= number_format($bc, 2) ?></td>
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
