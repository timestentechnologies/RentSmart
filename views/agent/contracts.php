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
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Client</th>
                            <th>Terms</th>
                            <th>Total</th>
                            <th>Monthly</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($contracts ?? []) as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($c['property_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['client_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['terms_type'] ?? '')) ?></td>
                                <td><?= number_format((float)($c['total_amount'] ?? 0), 2) ?></td>
                                <td><?= ($c['terms_type'] ?? '') === 'monthly' ? number_format((float)($c['monthly_amount'] ?? 0), 2) : '-' ?></td>
                                <td><?= htmlspecialchars((string)($c['status'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editAgentContract(<?= (int)($c['id'] ?? 0) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($contracts)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No contracts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editContractForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" id="edit_contract_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select class="form-select" name="property_id" id="edit_contract_property" required>
                                <option value="">Select property</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="agent_client_id" id="edit_contract_client" required>
                                <option value="">Select client</option>
                                <?php foreach (($clients ?? []) as $cl): ?>
                                    <option value="<?= (int)($cl['id'] ?? 0) ?>" data-property-id="<?= (int)($cl['property_id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($cl['property_name'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Client list should match the selected property.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Terms</label>
                            <select class="form-select" name="terms_type" id="edit_terms_type" required>
                                <option value="one_time">One-time</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" id="edit_total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4" id="edit_duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" id="edit_duration_months" type="number" min="1">
                        </div>
                        <div class="col-md-6" id="edit_start_wrap" style="display:none;">
                            <label class="form-label">Start Month</label>
                            <input class="form-control" name="start_month" id="edit_start_month" type="month">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" name="instructions" id="edit_instructions" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/agent/contracts/store">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select class="form-select" name="property_id" required>
                                <option value="">Select property</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="agent_client_id" required>
                                <option value="">Select client</option>
                                <?php foreach (($clients ?? []) as $cl): ?>
                                    <option value="<?= (int)($cl['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($cl['property_name'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Terms</label>
                            <select class="form-select" name="terms_type" id="terms_type" required>
                                <option value="one_time">One-time</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4" id="duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" type="number" min="1">
                        </div>
                        <div class="col-md-6" id="start_wrap" style="display:none;">
                            <label class="form-label">Start Month</label>
                            <input class="form-control" name="start_month" type="month">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" name="instructions" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  // Defensive cleanup: sometimes a leftover backdrop can block clicks on buttons.
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  document.body.classList.remove('modal-open');
  document.body.style.removeProperty('padding-right');

  const terms = document.getElementById('terms_type');
  const durationWrap = document.getElementById('duration_wrap');
  const startWrap = document.getElementById('start_wrap');
  function sync(){
    const isMonthly = (terms && terms.value === 'monthly');
    if(durationWrap) durationWrap.style.display = isMonthly ? '' : 'none';
    if(startWrap) startWrap.style.display = isMonthly ? '' : 'none';
  }
  if(terms){
    terms.addEventListener('change', sync);
    sync();
  }

  function csrfToken(){
    return (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  }

  const editTerms = document.getElementById('edit_terms_type');
  const editDurationWrap = document.getElementById('edit_duration_wrap');
  const editStartWrap = document.getElementById('edit_start_wrap');
  function editSync(){
    const isMonthly = (editTerms && editTerms.value === 'monthly');
    if(editDurationWrap) editDurationWrap.style.display = isMonthly ? '' : 'none';
    if(editStartWrap) editStartWrap.style.display = isMonthly ? '' : 'none';
  }
  if(editTerms){
    editTerms.addEventListener('change', editSync);
    editSync();
  }

  const propSel = document.getElementById('edit_contract_property');
  const clientSel = document.getElementById('edit_contract_client');
  function filterClients(){
    if(!propSel || !clientSel) return;
    const pid = propSel.value;
    Array.from(clientSel.options).forEach(opt=>{
      if(!opt.value) return;
      const ok = !pid || opt.getAttribute('data-property-id') === pid;
      opt.hidden = !ok;
    });
    if(clientSel.selectedOptions.length && clientSel.selectedOptions[0].hidden){
      clientSel.value = '';
    }
  }
  if(propSel){
    propSel.addEventListener('change', filterClients);
  }

  const editModalEl = document.getElementById('editContractModal');
  const editModal = (editModalEl && window.bootstrap && window.bootstrap.Modal)
    ? window.bootstrap.Modal.getOrCreateInstance(editModalEl)
    : null;

  window.editAgentContract = function(id){
    fetch('<?= BASE_URL ?>' + '/agent/contracts/get/' + id)
      .then(r=>r.json()).then(resp=>{
        if(!resp.success){ alert(resp.message || 'Contract not found'); return; }
        const c = resp.data || {};
        document.getElementById('edit_contract_id').value = String(c.id || id);
        document.getElementById('edit_contract_property').value = (c.property_id !== undefined && c.property_id !== null) ? String(c.property_id) : '';
        filterClients();
        document.getElementById('edit_contract_client').value = (c.agent_client_id !== undefined && c.agent_client_id !== null) ? String(c.agent_client_id) : '';
        document.getElementById('edit_terms_type').value = c.terms_type || 'one_time';
        document.getElementById('edit_total_amount').value = (c.total_amount !== undefined && c.total_amount !== null) ? String(c.total_amount) : '0';
        document.getElementById('edit_duration_months').value = (c.duration_months !== undefined && c.duration_months !== null) ? String(c.duration_months) : '';
        if (c.start_month) {
          document.getElementById('edit_start_month').value = String(c.start_month).slice(0, 7);
        } else {
          document.getElementById('edit_start_month').value = '';
        }
        document.getElementById('edit_status').value = c.status || 'active';
        document.getElementById('edit_instructions').value = c.instructions || '';
        editSync();
        if(editModal){ editModal.show(); }
      })
      .catch(()=>alert('Failed to load contract'));
  }

  document.getElementById('editContractForm')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = document.getElementById('edit_contract_id').value;
    if(!id) return;
    const fd = new FormData(e.target);
    fd.set('csrf_token', csrfToken() || fd.get('csrf_token') || '');
    try{
      const res = await fetch('<?= BASE_URL ?>' + '/agent/contracts/update/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){ alert(data.message || 'Failed to update'); return; }
      location.reload();
    }catch(err){
      alert('Failed to update');
    }
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
