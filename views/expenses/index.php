<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Expenses</h1>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="bi bi-plus-circle me-1"></i>Add Expense
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Expense History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Property</th>
                            <th>Source of Funds</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($expenses ?? []) as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['expense_date']) ?></td>
                            <td><?= htmlspecialchars($e['category']) ?></td>
                            <td><?= htmlspecialchars($e['property_name'] ?? '-') ?></td>
                            <td><span class="badge bg-secondary"><?= ucwords(str_replace('_',' ', $e['source_of_funds'])) ?></span></td>
                            <td><?= ucwords(str_replace('_',' ', $e['payment_method'])) ?></td>
                            <td class="text-danger">Ksh<?= number_format($e['amount'], 2) ?></td>
                            <td class="text-truncate" style="max-width:240px;" title="<?= htmlspecialchars($e['notes'] ?? '') ?>"><?= htmlspecialchars($e['notes'] ?? '') ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editExpense(<?= (int)$e['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteExpense(<?= (int)$e['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/expenses/store" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" placeholder="e.g., Repairs, Utilities, Payroll" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Property (optional)</label>
                <select name="property_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach (($properties ?? []) as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select" required>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Source of Funds</label>
                <select name="source_of_funds" class="form-select" required>
                    <option value="rent_balance">Rent Balance</option>
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="owner_funds">Owner Funds</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Attachments</label>
                <input type="file" name="expense_attachments[]" class="form-control" multiple>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editExpenseForm" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_expense_id" name="id">
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" id="edit_expense_date" name="expense_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" id="edit_category" name="category" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Property (optional)</label>
                <select id="edit_property_id" name="property_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach (($properties ?? []) as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" id="edit_amount" name="amount" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select id="edit_payment_method" name="payment_method" class="form-select" required>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Source of Funds</label>
                <select id="edit_source_of_funds" name="source_of_funds" class="form-select" required>
                    <option value="rent_balance">Rent Balance</option>
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="owner_funds">Owner Funds</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Attachments</label>
                <input type="file" id="edit_attachments" name="expense_attachments[]" class="form-control" multiple>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea id="edit_notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteExpenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this expense?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteExpenseBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
function editExpense(id){
  fetch('<?= BASE_URL ?>' + '/expenses/get/' + id)
    .then(r=>r.json()).then(resp=>{
      if(!resp.success){ alert('Expense not found'); return; }
      const e = resp.data;
      document.getElementById('edit_expense_id').value = e.id;
      document.getElementById('edit_expense_date').value = e.expense_date || '';
      document.getElementById('edit_category').value = e.category || '';
      document.getElementById('edit_property_id').value = e.property_id || '';
      document.getElementById('edit_amount').value = e.amount || '';
      document.getElementById('edit_payment_method').value = (e.payment_method || 'cash');
      document.getElementById('edit_source_of_funds').value = (e.source_of_funds || 'cash');
      document.getElementById('edit_notes').value = e.notes || '';
      const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
      modal.show();
    }).catch(()=>alert('Failed to load expense'));
}

document.getElementById('editExpenseForm')?.addEventListener('submit', function(ev){
  ev.preventDefault();
  const id = document.getElementById('edit_expense_id').value;
  const formData = new FormData(ev.target);
  fetch('<?= BASE_URL ?>' + '/expenses/update/' + id, { method:'POST', body: formData })
    .then(r=>r.json()).then(resp=>{
      if(resp.success){ location.reload(); }
      else { alert(resp.message || 'Failed to update expense'); }
    }).catch(()=>alert('Failed to update expense'));
});

let deleteExpenseId = null;
function confirmDeleteExpense(id){
  deleteExpenseId = id;
  const modal = new bootstrap.Modal(document.getElementById('deleteExpenseModal'));
  modal.show();
}
document.getElementById('confirmDeleteExpenseBtn')?.addEventListener('click', function(){
  if(!deleteExpenseId) return;
  fetch('<?= BASE_URL ?>' + '/expenses/delete/' + deleteExpenseId, { method:'POST' })
    .then(r=>r.json()).then(resp=>{
      if(resp.success){ location.reload(); }
      else { alert(resp.message || 'Failed to delete expense'); }
    }).catch(()=>alert('Failed to delete expense'));
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
