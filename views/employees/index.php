<?php
ob_start();
$isRealtor = strtolower((string)($_SESSION['user_role'] ?? '')) === 'realtor';
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
            <div class="p-3 border-bottom bg-light">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Search</label>
                        <input type="text" id="empSearch" class="form-control" placeholder="Search by name, role<?= $isRealtor ? '' : ', property' ?>...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Role</label>
                        <select id="empRoleFilter" class="form-select">
                            <option value="">All</option>
                            <option value="caretaker">Caretaker</option>
                            <option value="accountant">Accountant</option>
                            <option value="gardener">Gardener</option>
                            <option value="cleaner">Cleaner</option>
                            <option value="plumber">Plumber</option>
                            <option value="electrician">Electrician</option>
                            <option value="security">Security</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="driver">Driver</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="concierge">Concierge</option>
                            <option value="pool_attendant">Pool Attendant</option>
                            <option value="waste_collector">Waste Collector</option>
                            <option value="landscaper">Landscaper</option>
                            <option value="pest_control">Pest Control</option>
                            <option value="hvac_technician">HVAC Technician</option>
                            <option value="carpenter">Carpenter</option>
                            <option value="painter">Painter</option>
                            <option value="roofer">Roofer</option>
                            <option value="mason">Mason</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <?php if (!$isRealtor): ?>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Property</label>
                            <select id="empPropertyFilter" class="form-select">
                                <option value="">All</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select id="empStatusFilter" class="form-select">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="employeesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <?php if (!$isRealtor): ?><th>Property</th><?php endif; ?>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($employees ?? []) as $emp): ?>
                        <tr>
                            <td class="emp-name"><?= htmlspecialchars($emp['name']) ?></td>
                            <td class="emp-role" data-role="<?= htmlspecialchars(strtolower($emp['role'] ?? 'general')) ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ', $emp['role'] ?? 'general'))) ?></td>
                            <?php if (!$isRealtor): ?><td class="emp-property"><?= htmlspecialchars($emp['property_name'] ?? '-') ?></td><?php endif; ?>
                            <td class="emp-salary">Ksh<?= number_format($emp['salary'] ?? 0, 2) ?></td>
                            <td class="emp-status" data-status="<?= htmlspecialchars(strtolower($emp['status'] ?? 'active')) ?>"><span class="badge <?= ($emp['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucwords($emp['status'] ?? 'active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#payEmployeeModal" data-id="<?= $emp['id'] ?>" data-name="<?= htmlspecialchars($emp['name']) ?>" data-salary="<?= $emp['salary'] ?>">
                                    <i class="bi bi-cash-coin"></i> Pay
                                </button>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editEmployee(<?= $emp['id'] ?>)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteEmployee(<?= $emp['id'] ?>)" title="Delete">
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
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="caretaker">Caretaker</option>
                <option value="accountant">Accountant</option>
                <option value="gardener">Gardener</option>
                <option value="cleaner">Cleaner</option>
                <option value="plumber">Plumber</option>
                <option value="electrician">Electrician</option>
                <option value="security">Security</option>
                <option value="maintenance">Maintenance</option>
                <option value="supervisor">Supervisor</option>
                <option value="driver">Driver</option>
                <option value="receptionist">Receptionist</option>
                <option value="concierge">Concierge</option>
                <option value="pool_attendant">Pool Attendant</option>
                <option value="waste_collector">Waste Collector</option>
                <option value="landscaper">Landscaper</option>
                <option value="pest_control">Pest Control</option>
                <option value="hvac_technician">HVAC Technician</option>
                <option value="carpenter">Carpenter</option>
                <option value="painter">Painter</option>
                <option value="roofer">Roofer</option>
                <option value="mason">Mason</option>
                <option value="general">General</option>
            </select>
          </div>
          <?php if (!$isRealtor): ?>
              <div class="mb-3">
                <label class="form-label">Property (optional)</label>
                <select name="property_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach (($properties ?? []) as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
          <?php endif; ?>
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

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editEmployeeForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_emp_id">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" id="edit_name" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" id="edit_email" name="email" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" id="edit_phone" name="phone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select id="edit_role" name="role" class="form-select">
                <option value="caretaker">Caretaker</option>
                <option value="accountant">Accountant</option>
                <option value="gardener">Gardener</option>
                <option value="cleaner">Cleaner</option>
                <option value="plumber">Plumber</option>
                <option value="electrician">Electrician</option>
                <option value="security">Security</option>
                <option value="maintenance">Maintenance</option>
                <option value="supervisor">Supervisor</option>
                <option value="driver">Driver</option>
                <option value="receptionist">Receptionist</option>
                <option value="concierge">Concierge</option>
                <option value="pool_attendant">Pool Attendant</option>
                <option value="waste_collector">Waste Collector</option>
                <option value="landscaper">Landscaper</option>
                <option value="pest_control">Pest Control</option>
                <option value="hvac_technician">HVAC Technician</option>
                <option value="carpenter">Carpenter</option>
                <option value="painter">Painter</option>
                <option value="roofer">Roofer</option>
                <option value="mason">Mason</option>
                <option value="general">General</option>
            </select>
          </div>
          <?php if (!$isRealtor): ?>
              <div class="mb-3">
                <label class="form-label">Property (optional)</label>
                <select id="edit_property_id" name="property_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach (($properties ?? []) as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Salary (Monthly)</label>
            <div class="input-group">
                <span class="input-group-text">Ksh</span>
                <input type="number" step="0.01" id="edit_salary" name="salary" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select id="edit_status" name="status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
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

<!-- Delete Employee Modal -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this employee?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteEmployeeBtn" class="btn btn-danger">Delete</button>
      </div>
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

// Dynamic search and filters
const empSearch = document.getElementById('empSearch');
const empRoleFilter = document.getElementById('empRoleFilter');
const empPropertyFilter = document.getElementById('empPropertyFilter');
const empStatusFilter = document.getElementById('empStatusFilter');
function filterEmployees() {
  const rows = document.querySelectorAll('#employeesTable tbody tr');
  const q = (empSearch?.value || '').toLowerCase();
  const role = (empRoleFilter?.value || '').toLowerCase();
  const prop = (empPropertyFilter?.value || '').toLowerCase();
  const status = (empStatusFilter?.value || '').toLowerCase();
  rows.forEach(tr => {
    const name = tr.querySelector('.emp-name')?.innerText.toLowerCase() || '';
    const r = tr.querySelector('.emp-role')?.dataset.role || '';
    const p = tr.querySelector('.emp-property')?.innerText.toLowerCase() || '';
    const s = tr.querySelector('.emp-status')?.dataset.status || '';
    const matchesQ = !q || name.includes(q) || p.includes(q) || (tr.innerText.toLowerCase().includes(q));
    const matchesRole = !role || r === role;
    const matchesProp = !prop || p === prop;
    const matchesStatus = !status || s === status;
    tr.style.display = (matchesQ && matchesRole && matchesProp && matchesStatus) ? '' : 'none';
  });
}
empSearch && empSearch.addEventListener('input', filterEmployees);
empRoleFilter && empRoleFilter.addEventListener('change', filterEmployees);
empPropertyFilter && empPropertyFilter.addEventListener('change', filterEmployees);
empStatusFilter && empStatusFilter.addEventListener('change', filterEmployees);

// Edit employee
function editEmployee(id) {
  fetch(`${BASE_URL}/employees/get/${id}`).then(r=>r.json()).then(resp=>{
    if (!resp.success) return;
    const e = resp.data;
    document.getElementById('edit_emp_id').value = id;
    document.getElementById('edit_name').value = e.name || '';
    document.getElementById('edit_email').value = e.email || '';
    document.getElementById('edit_phone').value = e.phone || '';
    document.getElementById('edit_role').value = (e.role || 'general').toLowerCase();
    const propEl = document.getElementById('edit_property_id');
    if (propEl) propEl.value = e.property_id || '';
    document.getElementById('edit_salary').value = e.salary || '';
    document.getElementById('edit_status').value = (e.status || 'active').toLowerCase();
    const modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    modal.show();
  }).catch(()=>{});
}

document.getElementById('editEmployeeForm')?.addEventListener('submit', function(ev){
  ev.preventDefault();
  const id = document.getElementById('edit_emp_id').value;
  const form = ev.target;
  const formData = new FormData(form);
  fetch(`${BASE_URL}/employees/update/${id}`, {method:'POST', body: formData}).then(r=>r.json()).then(resp=>{
    if (resp.success) {
      location.reload();
    } else {
      alert(resp.message || 'Failed to update');
    }
  }).catch(()=>alert('Failed to update'));
});

// Delete employee
let deleteEmpId = null;
function confirmDeleteEmployee(id){
  deleteEmpId = id;
  const modal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
  modal.show();
}
document.getElementById('confirmDeleteEmployeeBtn')?.addEventListener('click', function(){
  if (!deleteEmpId) return;
  fetch(`${BASE_URL}/employees/delete/${deleteEmpId}`, {method:'POST'}).then(r=>r.json()).then(resp=>{
    if (resp.success) location.reload();
    else alert(resp.message || 'Failed to delete');
  }).catch(()=>alert('Failed to delete'));
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
