<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-journal-check text-primary me-2"></i>General Ledger</h1>
      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/accounting/ledger/export-csv?start=<?= urlencode($_GET['start'] ?? '') ?>&end=<?= urlencode($_GET['end'] ?? '') ?>">Export Excel</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/accounting/ledger/export-pdf?start=<?= urlencode($_GET['start'] ?? '') ?>&end=<?= urlencode($_GET['end'] ?? '') ?>">Export PDF</a>
        <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>/accounting/ledger">
          <input type="date" class="form-control" name="start" value="<?= htmlspecialchars($_GET['start'] ?? '') ?>" placeholder="Start">
          <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($_GET['end'] ?? '') ?>" placeholder="End">
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
              <th>Date</th>
              <th>Account</th>
              <th>Description</th>
              <th class="text-end">Debit</th>
              <th class="text-end">Credit</th>
            </tr>
          </thead>
          <tbody>
            <?php $td=0; $tc=0; foreach (($rows ?? []) as $r): $td+=(float)$r['debit']; $tc+=(float)$r['credit']; ?>
            <tr>
              <td><?= htmlspecialchars($r['entry_date']) ?></td>
              <td><?= htmlspecialchars(($r['code'] ?? '') . ' - ' . ($r['name'] ?? '')) ?></td>
              <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
              <td class="text-end"><?= number_format((float)$r['debit'], 2) ?></td>
              <td class="text-end"><?= number_format((float)$r['credit'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">No entries</td></tr>
            <?php else: ?>
              <tr class="bg-light fw-bold">
                <td colspan="3" class="text-end">Totals</td>
                <td class="text-end"><?= number_format($td, 2) ?></td>
                <td class="text-end"><?= number_format($tc, 2) ?></td>
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
