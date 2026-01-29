<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-person-badge text-primary me-2"></i>Employees</h1>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Employee
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Staff</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="employeesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Property</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($employees ?? []) as $emp): ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['name']) ?></td>
                            <td><?= htmlspecialchars($emp['title'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($emp['property_name'] ?? '-') ?></td>
                            <td>Ksh<?= number_format($emp['salary'] ?? 0, 2) ?></td>
                            <td><span class="badge <?= ($emp['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucwords($emp['status'] ?? 'active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#payEmployeeModal" data-id="<?= $emp['id'] ?>" data-name="<?= htmlspecialchars($emp['name']) ?>" data-salary="<?= $emp['salary'] ?>">
                                    <i class="bi bi-cash-coin"></i> Pay
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/employees/store">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control">
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
            <label class="form-label">Salary (Monthly)</label>
            <div class="input-group">
                <span class="input-group-text">Ksh</span>
                <input type="number" step="0.01" name="salary" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Pay Employee Modal -->
<div class="modal fade" id="payEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="payEmployeeForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Pay Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="pay_employee_id" name="employee_id">
            <div class="mb-3">
                <label class="form-label">Employee</label>
                <input type="text" id="pay_employee_name" class="form-control" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Pay Date</label>
                <input type="date" name="pay_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" id="pay_amount" name="amount" class="form-control" required>
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
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Record Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const payModal = document.getElementById('payEmployeeModal');
payModal && payModal.addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  document.getElementById('pay_employee_id').value = btn.getAttribute('data-id');
  document.getElementById('pay_employee_name').value = btn.getAttribute('data-name');
  const salary = btn.getAttribute('data-salary') || 0;
  document.getElementById('pay_amount').value = salary;
  document.getElementById('payEmployeeForm').setAttribute('action', `${BASE_URL}/employees/pay/${btn.getAttribute('data-id')}`);
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
