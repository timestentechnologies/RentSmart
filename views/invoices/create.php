<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Create Invoice</h1>
      <a href="<?= BASE_URL ?>/invoices" class="btn btn-outline-secondary">Back</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
      <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="post" action="<?= BASE_URL ?>/invoices/store">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tenant (optional)</label>
            <select name="tenant_id" class="form-select">
              <option value="">-- Select Tenant --</option>
              <?php foreach (($tenants ?? []) as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?> <?= !empty($t['unit_number']) ? '(' . htmlspecialchars($t['unit_number']) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Issue Date</label>
            <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tax Rate (%)</label>
            <input type="number" step="0.01" name="tax_rate" class="form-control" placeholder="e.g. 16">
          </div>
        </div>

        <hr>
        <h6 class="mb-2">Items</h6>
        <div id="items">
          <div class="row g-2 align-items-end item-row">
            <div class="col-md-6">
              <label class="form-label">Description</label>
              <input type="text" name="item_desc[]" class="form-control" placeholder="Description" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Qty</label>
              <input type="number" step="0.01" name="item_qty[]" class="form-control" value="1" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Unit Price</label>
              <input type="number" step="0.01" name="item_price[]" class="form-control" value="0" required>
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-outline-danger w-100 remove-item"><i class="bi bi-trash"></i></button>
            </div>
          </div>
        </div>
        <div class="mt-2">
          <button type="button" class="btn btn-outline-secondary" id="addItem"><i class="bi bi-plus-lg me-1"></i>Add Item</button>
        </div>
        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" value="1" id="post_to_ledger" name="post_to_ledger">
          <label class="form-check-label" for="post_to_ledger">Post to ledger after creating</label>
        </div>
        <div class="text-end mt-3">
          <button type="submit" class="btn btn-primary">Create Invoice</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function(){
  const addBtn = document.getElementById('addItem');
  const items = document.getElementById('items');
  addBtn.addEventListener('click', function(){
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end item-row mt-2';
    row.innerHTML = `
      <div class="col-md-6">
        <input type="text" name="item_desc[]" class="form-control" placeholder="Description" required>
      </div>
      <div class="col-md-2">
        <input type="number" step="0.01" name="item_qty[]" class="form-control" value="1" required>
      </div>
      <div class="col-md-2">
        <input type="number" step="0.01" name="item_price[]" class="form-control" value="0" required>
      </div>
      <div class="col-md-2">
        <button type="button" class="btn btn-outline-danger w-100 remove-item"><i class="bi bi-trash"></i></button>
      </div>`;
    items.appendChild(row);
  });
  items.addEventListener('click', function(e){
    const btn = e.target.closest('.remove-item');
    if (!btn) return;
    const row = btn.closest('.item-row');
    if (row && items.querySelectorAll('.item-row').length > 1) row.remove();
  });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
