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

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Clients</div>
                    <div class="h4 mb-0"><?= (int)(($stats['total_clients'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Properties</div>
                    <div class="h4 mb-0"><?= (int)(($stats['total_properties'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Contract Value</div>
                    <div class="h4 mb-0"><?= number_format((float)(($stats['total_contract_value'] ?? 0)), 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="contracts_search" placeholder="Search by client, property, status">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Property</label>
                    <select class="form-select" id="contracts_filter_property">
                        <option value="">All properties</option>
                        <?php foreach (($properties ?? []) as $p): ?>
                            <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="contracts_filter_status">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
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
                                <td data-property-id="<?= (int)($c['property_id'] ?? 0) ?>"><?= htmlspecialchars((string)($c['property_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['client_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['terms_type'] ?? '')) ?></td>
                                <td><?= number_format((float)($c['display_total_amount'] ?? ($c['total_amount'] ?? 0)), 2) ?></td>
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
                                    <?php foreach (($cl['property_pairs'] ?? []) as $pp): ?>
                                        <option value="<?= (int)($cl['id'] ?? 0) ?>" data-property-id="<?= (int)($pp['id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($pp['name'] ?? '')) ?>)
                                        </option>
                                    <?php endforeach; ?>
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
                            <label class="form-label">Commission %</label>
                            <input class="form-control" name="commission_percent" id="edit_commission_percent" type="number" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" id="edit_total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Units</label>
                            <div class="border rounded p-2" style="max-height:220px; overflow:auto; display:none;" id="edit_units_list"></div>
                            <div class="form-text" id="edit_units_hint">Select a property to load units.</div>
                        </div>
                        <div class="col-md-4" id="edit_duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" id="edit_duration_months" type="number" min="1">
                            <div class="form-text">Leave blank to run forever.</div>
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
                            <select class="form-select" name="property_id" id="add_contract_property" required>
                                <option value="">Select property</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="agent_client_id" id="add_contract_client" required>
                                <option value="">Select client</option>
                                <?php foreach (($clients ?? []) as $cl): ?>
                                    <?php foreach (($cl['property_pairs'] ?? []) as $pp): ?>
                                        <option value="<?= (int)($cl['id'] ?? 0) ?>" data-property-id="<?= (int)($pp['id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($pp['name'] ?? '')) ?>)
                                        </option>
                                    <?php endforeach; ?>
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
                            <label class="form-label">Commission %</label>
                            <input class="form-control" name="commission_percent" id="add_commission_percent" type="number" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" id="add_total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Units</label>
                            <div class="border rounded p-2" style="max-height:220px; overflow:auto; display:none;" id="add_units_list"></div>
                            <div class="form-text" id="add_units_hint">Select a property to load units.</div>
                        </div>
                        <div class="col-md-4" id="duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" type="number" min="1">
                            <div class="form-text">Leave blank to run forever.</div>
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
    const startInput = startWrap ? startWrap.querySelector('input[name="start_month"]') : null;
    if(startInput) startInput.required = !!isMonthly;
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
    const startInput = document.getElementById('edit_start_month');
    if(startInput) startInput.required = !!isMonthly;
  }
  if(editTerms){
    editTerms.addEventListener('change', editSync);
    editSync();
  }

  const propSel = document.getElementById('edit_contract_property');
  const clientSel = document.getElementById('edit_contract_client');
  function filterClients(){
    if(!propSel || !clientSel) return;
    const pid = String(propSel.value || '');
    Array.from(clientSel.options).forEach(opt=>{
      if(!opt.value) return;
      const optPid = opt.getAttribute('data-property-id') || '';
      const ok = !pid || optPid === pid;
      opt.hidden = !ok;
    });
    const sel = clientSel.selectedOptions && clientSel.selectedOptions.length ? clientSel.selectedOptions[0] : null;
    if(sel && sel.hidden){
      clientSel.value = '';
    }
  }
  if(propSel){
    propSel.addEventListener('change', filterClients);
  }

  const addPropSel = document.getElementById('add_contract_property');
  const addClientSel = document.getElementById('add_contract_client');
  function filterAddClients(){
    if(!addPropSel || !addClientSel) return;
    const pid = String(addPropSel.value || '');
    Array.from(addClientSel.options).forEach(opt=>{
      if(!opt.value) return;
      const optPid = opt.getAttribute('data-property-id') || '';
      const ok = !pid || optPid === pid;
      opt.hidden = !ok;
    });
    const sel = addClientSel.selectedOptions && addClientSel.selectedOptions.length ? addClientSel.selectedOptions[0] : null;
    if(sel && sel.hidden){
      addClientSel.value = '';
    }
  }
  if(addPropSel){
    addPropSel.addEventListener('change', filterAddClients);
  }

  function renderSelectAll(listEl){
    if(!listEl) return;
    const existing = listEl.querySelector('[data-select-all="1"]');
    if(existing) return;
    const wrap = document.createElement('div');
    wrap.className = 'form-check border-bottom pb-2 mb-2';
    wrap.setAttribute('data-select-all', '1');
    const input = document.createElement('input');
    input.className = 'form-check-input';
    input.type = 'checkbox';
    input.id = (listEl.id || 'units') + '_select_all';
    const label = document.createElement('label');
    label.className = 'form-check-label';
    label.setAttribute('for', input.id);
    label.textContent = 'Select all units';
    wrap.appendChild(input);
    wrap.appendChild(label);
    listEl.appendChild(wrap);

    input.addEventListener('change', ()=>{
      const checked = !!input.checked;
      Array.from(listEl.querySelectorAll('input[type="checkbox"][name="unit_ids[]"]')).forEach(cb=>{
        cb.checked = checked;
      });
      listEl.dispatchEvent(new Event('change'));
    });
  }

  function updateSelectAllState(listEl){
    if(!listEl) return;
    const selAll = listEl.querySelector('input[type="checkbox"]#' + (listEl.id || 'units') + '_select_all');
    if(!selAll) return;
    const boxes = Array.from(listEl.querySelectorAll('input[type="checkbox"][name="unit_ids[]"]'));
    if(!boxes.length){
      selAll.checked = false;
      return;
    }
    const checkedCount = boxes.filter(b=>b.checked).length;
    selAll.checked = checkedCount === boxes.length;
  }

  function loadUnits(propertyId, targetListEl, targetHintEl, selectedUnitIds){
    if(!targetListEl) return Promise.resolve();
    targetListEl.innerHTML = '';
    if(!propertyId){
      if(targetHintEl) targetHintEl.textContent = 'Select a property to load units.';
      return Promise.resolve();
    }
    if(targetHintEl) targetHintEl.textContent = 'Loading units...';
    return fetch('<?= BASE_URL ?>' + '/properties/' + propertyId + '/units')
      .then(r=>r.json())
      .then(resp=>{
        const units = (resp && resp.success && Array.isArray(resp.units)) ? resp.units : [];
        if(targetHintEl) targetHintEl.textContent = units.length ? 'Units loaded (all units included by default).' : 'No units found for this property.';
        // Keep unit selection hidden; units are included by default.
        targetListEl.style.display = 'none';

        const selectAllByDefault = !Array.isArray(selectedUnitIds);
        units.forEach(u=>{
          const id = String(u.id || '');
          if(!id) return;
          const rent = Number(u.rent_amount || 0);
          const wrap = document.createElement('div');
          wrap.className = 'form-check';
          const input = document.createElement('input');
          input.className = 'form-check-input';
          input.type = 'checkbox';
          input.name = 'unit_ids[]';
          input.value = id;
          input.setAttribute('data-rent', String(rent));
          input.id = (targetListEl.id || 'units') + '_' + id;
          if (selectAllByDefault) {
            input.checked = true;
          } else if (selectedUnitIds.includes(id)) {
            input.checked = true;
          }
          const label = document.createElement('label');
          label.className = 'form-check-label';
          label.setAttribute('for', input.id);
          const unitNo = (u.unit_number !== undefined && u.unit_number !== null) ? String(u.unit_number) : id;
          label.textContent = unitNo + ' - ' + rent.toFixed(2);
          wrap.appendChild(input);
          wrap.appendChild(label);
          targetListEl.appendChild(wrap);
        });
      })
      .catch(()=>{
        if(targetHintEl) targetHintEl.textContent = 'Failed to load units.';
      });
  }

  function calcTotal(listEl, percentEl, totalEl){
    if(!listEl || !percentEl || !totalEl) return;
    const pct = Number(percentEl.value || 0);
    const units = Array.from(listEl.querySelectorAll('input[type="checkbox"]:checked'));
    let rentTotal = 0;
    units.forEach(i=>{ rentTotal += Number(i.getAttribute('data-rent') || 0); });
    const amount = rentTotal > 0 && pct > 0 ? ((rentTotal * pct) / 100) : 0;
    // Only auto-write total when commission % is provided; otherwise keep manual value.
    if (String(percentEl.value || '').trim() !== '' && pct > 0) {
      totalEl.value = amount.toFixed(2);
    }
    updateSelectAllState(listEl);
  }

  function toggleAutoMode(listEl, percentEl, totalEl){
    if(!percentEl || !totalEl) return;
    const hasPct = String(percentEl.value || '').trim() !== '' && Number(percentEl.value || 0) > 0;
    totalEl.readOnly = hasPct;
    if(listEl){
      // Do not show unit list; always treat all loaded units as included
      listEl.style.display = 'none';
    }
    if(hasPct){
      calcTotal(listEl, percentEl, totalEl);
    }
  }

  const addUnitsList = document.getElementById('add_units_list');
  const addUnitsHint = document.getElementById('add_units_hint');
  const addPct = document.getElementById('add_commission_percent');
  const addTotal = document.getElementById('add_total_amount');
  addPropSel?.addEventListener('change', ()=>{
    loadUnits(addPropSel.value, addUnitsList, addUnitsHint, null).then(()=>{
      toggleAutoMode(addUnitsList, addPct, addTotal);
    });
    filterAddClients();
  });
  addUnitsList?.addEventListener('change', ()=>calcTotal(addUnitsList, addPct, addTotal));
  addPct?.addEventListener('input', ()=>toggleAutoMode(addUnitsList, addPct, addTotal));

  const editModalEl = document.getElementById('editContractModal');
  function getEditModal(){
    if(!editModalEl) return null;
    if(!(window.bootstrap && window.bootstrap.Modal)) return null;
    return window.bootstrap.Modal.getOrCreateInstance(editModalEl);
  }

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
        document.getElementById('edit_commission_percent').value = (c.commission_percent !== undefined && c.commission_percent !== null) ? String(c.commission_percent) : '0';
        // Show last saved total immediately (will be recalculated once units load)
        document.getElementById('edit_total_amount').value = (c.total_amount !== undefined && c.total_amount !== null) ? String(c.total_amount) : '0.00';
        document.getElementById('edit_duration_months').value = (c.duration_months !== undefined && c.duration_months !== null) ? String(c.duration_months) : '';
        if (c.start_month) {
          document.getElementById('edit_start_month').value = String(c.start_month).slice(0, 7);
        } else {
          document.getElementById('edit_start_month').value = '';
        }
        document.getElementById('edit_status').value = c.status || 'active';
        document.getElementById('edit_instructions').value = c.instructions || '';

        const editUnitsList = document.getElementById('edit_units_list');
        const editUnitsHint = document.getElementById('edit_units_hint');
        const editPct = document.getElementById('edit_commission_percent');
        const editTotal = document.getElementById('edit_total_amount');
        const unitIdsCsv = String(c.unit_ids || '');
        const selectedUnitIds = unitIdsCsv ? unitIdsCsv.split(',').map(s=>s.trim()).filter(Boolean) : null;
        loadUnits(String(c.property_id || ''), editUnitsList, editUnitsHint, selectedUnitIds).then(()=>{
          toggleAutoMode(editUnitsList, editPct, editTotal);
        });

        editSync();
        const editModal = getEditModal();
        if(!editModal){
          alert('Edit modal is not ready. Please refresh and try again.');
          return;
        }
        editModal.show();
      })
      .catch(()=>alert('Failed to load contract'));
  }

  const editUnitsList = document.getElementById('edit_units_list');
  const editUnitsHint = document.getElementById('edit_units_hint');
  const editPct = document.getElementById('edit_commission_percent');
  const editTotal = document.getElementById('edit_total_amount');
  propSel?.addEventListener('change', ()=>{
    loadUnits(propSel.value, editUnitsList, editUnitsHint, null).then(()=>{
      toggleAutoMode(editUnitsList, editPct, editTotal);
    });
    filterClients();
  });
  editUnitsList?.addEventListener('change', ()=>calcTotal(editUnitsList, editPct, editTotal));
  editPct?.addEventListener('input', ()=>toggleAutoMode(editUnitsList, editPct, editTotal));

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

  const searchEl = document.getElementById('contracts_search');
  const filterPropEl = document.getElementById('contracts_filter_property');
  const filterStatusEl = document.getElementById('contracts_filter_status');
  function filterTable(){
    const q = String(searchEl?.value || '').toLowerCase().trim();
    const pid = String(filterPropEl?.value || '');
    const st = String(filterStatusEl?.value || '').toLowerCase();
    document.querySelectorAll('table.table tbody tr').forEach(tr=>{
      const tds = tr.querySelectorAll('td');
      if(!tds || tds.length < 6) return;
      const propTd = tds[0];
      const propId = propTd?.getAttribute('data-property-id') || '';
      const propName = (tds[0]?.textContent || '').toLowerCase();
      const clientName = (tds[1]?.textContent || '').toLowerCase();
      const status = (tds[5]?.textContent || '').toLowerCase();
      const hay = (propName + ' ' + clientName + ' ' + status);
      const okQ = !q || hay.includes(q);
      const okP = !pid || propId === pid;
      const okS = !st || status === st;
      tr.style.display = (okQ && okP && okS) ? '' : 'none';
    });
  }
  searchEl?.addEventListener('input', filterTable);
  filterPropEl?.addEventListener('change', filterTable);
  filterStatusEl?.addEventListener('change', filterTable);
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
