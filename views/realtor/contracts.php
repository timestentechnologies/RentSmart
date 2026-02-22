<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-file-text text-primary me-2"></i>Contracts</h1>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addContractModal">
                <i class="bi bi-plus-circle me-1"></i> Add Contract
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="card-title mb-0">All Contracts</h5>
                <div class="realtor-filters">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="realtorContractsSearch" placeholder="Search contracts...">
                    </div>
                    <select class="form-select" id="realtorContractsFilterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select class="form-select" id="realtorContractsFilterTerms">
                        <option value="">All Terms</option>
                        <option value="monthly">Monthly</option>
                        <option value="one_time">One Time</option>
                    </select>
                    <select class="form-select" id="realtorContractsFilterPayment">
                        <option value="">All Payments</option>
                        <option value="paid">Fully Paid</option>
                        <option value="unpaid">Not Paid</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="realtorContractsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Listing</th>
                            <th>Terms</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($contracts ?? []) as $x): ?>
                            <?php
                                $terms = (string)($x['terms_type'] ?? 'one_time');
                                $status = (string)($x['status'] ?? 'active');
                                $statusBadge = 'secondary';
                                if ($status === 'active') { $statusBadge = 'success'; }
                                elseif ($status === 'completed') { $statusBadge = 'primary'; }
                                elseif ($status === 'cancelled') { $statusBadge = 'danger'; }

                                $cid = (int)($x['id'] ?? 0);
                                $total = (float)($x['total_amount'] ?? 0);
                                $paid = (float)(($paidTotals ?? [])[$cid] ?? 0);
                                $isFullyPaid = ($total > 0 && $paid + 0.00001 >= $total);
                            ?>
                            <tr data-status="<?= htmlspecialchars($status) ?>" data-terms="<?= htmlspecialchars($terms) ?>" data-payment="<?= $isFullyPaid ? 'paid' : 'unpaid' ?>">
                                <td>#<?= (int)($x['id'] ?? 0) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($x['client_name'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($x['client_phone'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($x['listing_title'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($x['listing_location'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <?php if ($terms === 'monthly'): ?>
                                        <span class="badge bg-info">Monthly</span>
                                        <div class="small text-muted">
                                            <?= (int)($x['duration_months'] ?? 0) ?> months @ Ksh<?= number_format((float)($x['monthly_amount'] ?? 0), 2) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">One Time</span>
                                    <?php endif; ?>
                                </td>
                                <td>Ksh<?= number_format((float)($x['total_amount'] ?? 0), 2) ?></td>
                                <td>
                                    <?php if ($isFullyPaid): ?>
                                        <span class="badge bg-success">Fully Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Not Paid</span>
                                    <?php endif; ?>
                                    <div class="small text-muted">Paid: Ksh<?= number_format($paid, 2) ?></div>
                                </td>
                                <td><span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ', $status))) ?></span></td>
                                <td><?= htmlspecialchars((string)($x['created_at'] ?? '')) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/realtor/contracts/show/<?= (int)($x['id'] ?? 0) ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editRealtorContract(<?= (int)($x['id'] ?? 0) ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
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

<script>
(function(){
  const table = document.getElementById('realtorContractsTable');
  const q = document.getElementById('realtorContractsSearch');
  const fStatus = document.getElementById('realtorContractsFilterStatus');
  const fTerms = document.getElementById('realtorContractsFilterTerms');
  const fPay = document.getElementById('realtorContractsFilterPayment');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const norm = (v) => (String(v || '')).toLowerCase().trim();

  function apply(){
    const query = norm(q && q.value);
    const status = norm(fStatus && fStatus.value);
    const terms = norm(fTerms && fTerms.value);
    const pay = norm(fPay && fPay.value);

    const rows = tbody.querySelectorAll('tr');
    rows.forEach((tr) => {
      const rowText = norm(tr.innerText);
      const rowStatus = norm(tr.getAttribute('data-status'));
      const rowTerms = norm(tr.getAttribute('data-terms'));
      const rowPay = norm(tr.getAttribute('data-payment'));

      let ok = true;
      if (query && !rowText.includes(query)) ok = false;
      if (status && rowStatus !== status) ok = false;
      if (terms && rowTerms !== terms) ok = false;
      if (pay && rowPay !== pay) ok = false;

      tr.style.display = ok ? '' : 'none';
    });
  }

  [q, fStatus, fTerms, fPay].forEach((el) => {
    if (!el) return;
    el.addEventListener('input', apply);
    el.addEventListener('change', apply);
  });
  apply();
})();
</script>

<script>
(function(){
  function upsertClientOption(selectEl, id, label){
    if(!selectEl) return;
    const val = String(id);
    const existing = Array.from(selectEl.options).find(o => o.value === val);
    if(existing){
      existing.textContent = label;
    }
  }

  const btn = document.getElementById('erc_edit_client_btn');
  const contractModalEl = document.getElementById('editRealtorContractModal');
  const clientModalEl = document.getElementById('editRealtorClientModal');
  const clientSel = document.getElementById('erc_client_id');
  const err = document.getElementById('ercu_error');
  const submitBtn = document.getElementById('ercu_submit');
  const form = document.getElementById('editRealtorClientForm');

  function getModal(el){
    if(!el || !(window.bootstrap && window.bootstrap.Modal)) return null;
    return window.bootstrap.Modal.getOrCreateInstance(el);
  }

  btn?.addEventListener('click', ()=>{
    const id = clientSel ? (clientSel.value || '') : '';
    if(!id) return;
    if(err){ err.classList.add('d-none'); err.textContent = ''; }
    if(submitBtn) submitBtn.disabled = true;

    fetch('<?= BASE_URL ?>' + '/realtor/clients/get/' + id)
      .then(r=>r.json())
      .then(resp=>{
        if(!resp || !resp.success || !resp.data){ throw new Error(resp && resp.message ? resp.message : 'Failed to load client'); }
        const c = resp.data;
        document.getElementById('edit_realtor_client_id').value = String(id);
        document.getElementById('ercu_name').value = String(c.name || '');
        document.getElementById('ercu_phone').value = String(c.phone || '');
        document.getElementById('ercu_email').value = String(c.email || '');
        document.getElementById('ercu_notes').value = String(c.notes || '');

        const cm = getModal(clientModalEl);
        const pm = getModal(contractModalEl);
        if(pm) pm.hide();
        if(cm) cm.show();
      })
      .catch(e=>{
        if(err){ err.textContent = String(e && e.message ? e.message : e); err.classList.remove('d-none'); }
        const cm = getModal(clientModalEl);
        if(cm) cm.show();
      })
      .finally(()=>{ if(submitBtn) submitBtn.disabled = false; });
  });

  clientModalEl?.addEventListener('hidden.bs.modal', ()=>{
    const pm = getModal(contractModalEl);
    if(pm) pm.show();
  });

  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = document.getElementById('edit_realtor_client_id')?.value || '';
    if(!id) return;
    if(err){ err.classList.add('d-none'); err.textContent = ''; }
    if(submitBtn) submitBtn.disabled = true;
    try{
      const fd = new FormData(form);
      const res = await fetch('<?= BASE_URL ?>' + '/realtor/clients/update/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data || !data.success){
        throw new Error(data && data.message ? data.message : 'Failed to update client');
      }
      const label = String(fd.get('name') || '') + ' (' + String(fd.get('phone') || '') + ')';
      upsertClientOption(clientSel, id, label);
      const cm = getModal(clientModalEl);
      if(cm) cm.hide();
    }catch(e2){
      if(err){ err.textContent = String(e2 && e2.message ? e2.message : e2); err.classList.remove('d-none'); }
    }finally{
      if(submitBtn) submitBtn.disabled = false;
    }
  });
})();
</script>

<div class="modal fade" id="editRealtorContractModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" id="editRealtorContractForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" id="edit_realtor_contract_id" value="">
        <div class="modal-header">
          <h5 class="modal-title">Edit Contract</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Client</label>
              <div class="d-flex gap-2">
                <select class="form-select" name="realtor_client_id" id="erc_client_id" required>
                  <option value="">Select client</option>
                  <?php foreach (($clients ?? []) as $cl): ?>
                    <option value="<?= (int)($cl['id'] ?? 0) ?>">
                      <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($cl['phone'] ?? '')) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="erc_edit_client_btn" title="Edit Client">
                  <i class="bi bi-person-gear"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Terms</label>
              <select class="form-select" name="terms_type" id="erc_terms_type" required>
                <option value="one_time">One-time</option>
                <option value="monthly">Monthly</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Total Amount</label>
              <input class="form-control" name="total_amount" id="erc_total_amount" type="number" step="0.01" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="erc_status" required>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            <div class="col-md-4" id="erc_duration_wrap" style="display:none;">
              <label class="form-label">Duration (months)</label>
              <input class="form-control" name="duration_months" id="erc_duration_months" type="number" min="1">
            </div>
            <div class="col-md-4" id="erc_start_wrap" style="display:none;">
              <label class="form-label">Start Month</label>
              <input class="form-control" name="start_month" id="erc_start_month" type="month">
            </div>
            <div class="col-12">
              <label class="form-label">Instructions / Notes</label>
              <textarea class="form-control" name="instructions" id="erc_instructions" rows="4"></textarea>
            </div>
          </div>
          <div class="alert alert-danger d-none mt-3" id="erc_error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="erc_submit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editRealtorClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editRealtorClientForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" id="edit_realtor_client_id" value="">
        <div class="modal-header">
          <h5 class="modal-title">Edit Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" id="ercu_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" id="ercu_phone" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" id="ercu_email" type="email">
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" id="ercu_notes" rows="3"></textarea>
          </div>
          <div class="alert alert-danger d-none" id="ercu_error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="ercu_submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/realtor/contracts/store">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="realtor_client_id" required>
                                <option value="">Select client</option>
                                <?php foreach (($clients ?? []) as $cl): ?>
                                    <option value="<?= (int)($cl['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($cl['phone'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Listing</label>
                            <select class="form-select" name="realtor_listing_id" required>
                                <option value="">Select listing</option>
                                <?php foreach (($listings ?? []) as $l): ?>
                                    <option value="<?= (int)($l['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($l['title'] ?? '')) ?><?= !empty($l['location']) ? ' - ' . htmlspecialchars((string)$l['location']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Terms</label>
                            <select class="form-select" name="terms_type" id="realtor_terms_type" required>
                                <option value="one_time">One-time</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4" id="realtor_duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" type="number" min="1">
                        </div>
                        <div class="col-md-6" id="realtor_start_wrap" style="display:none;">
                            <label class="form-label">Start Month</label>
                            <input class="form-control" name="start_month" type="month">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  const terms = document.getElementById('realtor_terms_type');
  const durationWrap = document.getElementById('realtor_duration_wrap');
  const startWrap = document.getElementById('realtor_start_wrap');
  function sync(){
    const isMonthly = (terms && terms.value === 'monthly');
    if(durationWrap) durationWrap.style.display = isMonthly ? '' : 'none';
    if(startWrap) startWrap.style.display = isMonthly ? '' : 'none';
  }
  if(terms){
    terms.addEventListener('change', sync);
    sync();
  }
})();
</script>

<script>
function editRealtorContract(id){
  const modalEl = document.getElementById('editRealtorContractModal');
  if(!modalEl || !(window.bootstrap && window.bootstrap.Modal)) return;
  const m = window.bootstrap.Modal.getOrCreateInstance(modalEl);

  const err = document.getElementById('erc_error');
  const submitBtn = document.getElementById('erc_submit');
  if(err){ err.classList.add('d-none'); err.textContent = ''; }
  if(submitBtn) submitBtn.disabled = true;

  fetch('<?= BASE_URL ?>' + '/realtor/contracts/get/' + id)
    .then(r=>r.json())
    .then(resp=>{
      if(!resp || !resp.success || !resp.data){ throw new Error(resp && resp.message ? resp.message : 'Failed to load'); }
      const c = resp.data;

      const form = document.getElementById('editRealtorContractForm');
      if(form) form.action = '<?= BASE_URL ?>' + '/realtor/contracts/update/' + id;

      document.getElementById('edit_realtor_contract_id').value = String(id);
      const clientSel = document.getElementById('erc_client_id');
      if(clientSel){
        clientSel.value = c.realtor_client_id ? String(c.realtor_client_id) : '';
      }
      document.getElementById('erc_terms_type').value = String(c.terms_type || 'one_time');
      document.getElementById('erc_total_amount').value = String(c.total_amount || 0);
      document.getElementById('erc_status').value = String(c.status || 'active');
      document.getElementById('erc_duration_months').value = c.duration_months ? String(c.duration_months) : '';
      document.getElementById('erc_start_month').value = c.start_month ? String(c.start_month).slice(0,7) : '';
      document.getElementById('erc_instructions').value = String(c.instructions || '');

      const isMonthly = (document.getElementById('erc_terms_type').value === 'monthly');
      const dw = document.getElementById('erc_duration_wrap');
      const sw = document.getElementById('erc_start_wrap');
      if(dw) dw.style.display = isMonthly ? '' : 'none';
      if(sw) sw.style.display = isMonthly ? '' : 'none';

      m.show();
    })
    .catch(e=>{
      if(err){ err.textContent = String(e && e.message ? e.message : e); err.classList.remove('d-none'); }
      m.show();
    })
    .finally(()=>{ if(submitBtn) submitBtn.disabled = false; });
}

(function(){
  const terms = document.getElementById('erc_terms_type');
  const durationWrap = document.getElementById('erc_duration_wrap');
  const startWrap = document.getElementById('erc_start_wrap');
  function sync(){
    const isMonthly = (terms && terms.value === 'monthly');
    if(durationWrap) durationWrap.style.display = isMonthly ? '' : 'none';
    if(startWrap) startWrap.style.display = isMonthly ? '' : 'none';
  }
  if(terms){
    terms.addEventListener('change', sync);
    sync();
  }
})();

// Auto-open edit modal when redirected from lead win / client creation flows
(function(){
  function onReady(fn){
    if(document.readyState === 'loading'){
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function(){
    try {
      const params = new URLSearchParams(window.location.search || '');
      const id = parseInt(params.get('edit') || '0', 10);
      if(id > 0 && typeof window.editRealtorContract === 'function'){
        window.editRealtorContract(id);
      }
    } catch(e) {}
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
