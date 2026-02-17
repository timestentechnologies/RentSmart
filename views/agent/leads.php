<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-kanban text-primary me-2"></i>CRM - Leads</h1>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#manageAgentStagesModal">
                    <i class="bi bi-sliders me-1"></i>Stages
                </button>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAgentLeadModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Lead
                </button>
            </div>
        </div>
    </div>

    <style>
        .crm-board { display:flex; gap: 12px; overflow-x:auto; padding-bottom: 6px; }
        .crm-col { min-width: 280px; flex: 1 0 280px; background: #f8f9fa; border: 1px solid rgba(0,0,0,.075); border-radius: 10px; }
        .crm-col-header { padding: 10px 12px; border-bottom: 1px solid rgba(0,0,0,.075); display:flex; align-items:center; justify-content:space-between; }
        .crm-accent { width: 10px; height: 10px; border-radius: 999px; display:inline-block; margin-right: 8px; }
        .crm-col-title { font-weight: 600; }
        .crm-col-body { padding: 10px 12px; min-height: 360px; }
        .lead-card { border: 1px solid rgba(0,0,0,.08); border-radius: 10px; background: #fff; padding: 10px; margin-bottom: 10px; cursor: grab; }
        .lead-card:active { cursor: grabbing; }
        .lead-card .lead-title { font-weight: 600; line-height: 1.1; }
        .lead-card .lead-sub { font-size: .875rem; color: #6c757d; }
        .crm-drop-hover { outline: 2px dashed rgba(13,110,253,.5); outline-offset: 4px; }
        .lead-tag { font-size: .75rem; }
    </style>

    <?php
        $stagesArr = is_array($stages ?? null) ? $stages : [];
        if (empty($stagesArr)) {
            $stagesArr = [
                ['stage_key'=>'new','label'=>'New','color_class'=>'primary','is_won'=>0,'is_lost'=>0],
                ['stage_key'=>'contacted','label'=>'Contacted','color_class'=>'warning','is_won'=>0,'is_lost'=>0],
                ['stage_key'=>'qualified','label'=>'Qualified','color_class'=>'info','is_won'=>0,'is_lost'=>0],
                ['stage_key'=>'won','label'=>'Won','color_class'=>'success','is_won'=>1,'is_lost'=>0],
                ['stage_key'=>'lost','label'=>'Lost','color_class'=>'danger','is_won'=>0,'is_lost'=>1],
            ];
        }
        $stageMap = [];
        $grouped = [];
        foreach ($stagesArr as $s) {
            $k = strtolower((string)($s['stage_key'] ?? ''));
            if ($k === '') continue;
            $stageMap[$k] = $s;
            $grouped[$k] = [];
        }
        foreach (($inquiries ?? []) as $x) {
            $st = strtolower((string)($x['crm_stage'] ?? 'new'));
            if (!isset($grouped[$st])) {
                $firstKey = 'new';
                foreach ($grouped as $kk => $_v) { $firstKey = $kk; break; }
                $st = $firstKey ?: 'new';
            }
            $grouped[$st][] = $x;
        }
        $accentToHex = function($c){
            $m = [
                'primary'=>'#0d6efd','secondary'=>'#6c757d','success'=>'#198754','warning'=>'#ffc107','danger'=>'#dc3545','info'=>'#0dcaf0','dark'=>'#212529'
            ];
            return $m[$c] ?? '#6c757d';
        };
    ?>

    <div class="crm-board" id="crmBoard">
        <?php foreach ($stagesArr as $stage): ?>
            <?php
                $key = strtolower((string)($stage['stage_key'] ?? 'new'));
                $label = (string)($stage['label'] ?? $key);
                $colorClass = (string)($stage['color_class'] ?? 'secondary');
                $accent = $accentToHex($colorClass);
                $isWonStage = (int)($stage['is_won'] ?? 0) === 1;
            ?>
            <div class="crm-col" data-stage="<?= htmlspecialchars($key) ?>" data-color="<?= htmlspecialchars($colorClass) ?>" data-is-won="<?= $isWonStage ? 1 : 0 ?>">
                <div class="crm-col-header">
                    <div>
                        <span class="crm-accent" style="background: <?= htmlspecialchars($accent) ?>;"></span>
                        <span class="crm-col-title"><?= htmlspecialchars($label) ?></span>
                        <span class="badge bg-light text-dark ms-2" id="count_<?= htmlspecialchars($key) ?>"><?= (int)count($grouped[$key] ?? []) ?></span>
                    </div>
                </div>
                <div class="crm-col-body" data-dropzone="1" data-stage="<?= htmlspecialchars($key) ?>">
                    <?php foreach (($grouped[$key] ?? []) as $x): ?>
                        <?php
                            $id = (int)($x['id'] ?? 0);
                            $name = (string)($x['name'] ?? '');
                            $contact = (string)($x['contact'] ?? '');
                            $propertyName = (string)($x['property_name'] ?? '');
                            $unit = (string)($x['unit_number'] ?? '');
                            $message = (string)($x['message'] ?? '');
                        ?>
                        <div class="lead-card" draggable="true" data-id="<?= $id ?>" data-stage="<?= htmlspecialchars($key) ?>">
                            <div class="lead-title"><?= htmlspecialchars($name) ?></div>
                            <div class="lead-sub mt-1"><?= htmlspecialchars($contact) ?></div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <?php if ($propertyName !== ''): ?>
                                    <span class="badge bg-light text-dark lead-tag"><?= htmlspecialchars($propertyName) ?></span>
                                <?php endif; ?>
                                <?php if ($unit !== ''): ?>
                                    <span class="badge bg-light text-dark lead-tag">Unit <?= htmlspecialchars($unit) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($message !== ''): ?>
                                <div class="lead-sub mt-2" style="white-space: pre-wrap;"><?php echo htmlspecialchars($message) ?></div>
                            <?php endif; ?>

                            <?php if (!$isWonStage): ?>
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success" data-role="win-btn" data-id="<?= $id ?>">
                                        <i class="bi bi-check2-circle"></i> Win
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="manageAgentStagesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage Stages</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3"><input type="text" id="new_agent_stage_key" class="form-control" placeholder="key e.g follow_up"></div>
          <div class="col-md-3"><input type="text" id="new_agent_stage_label" class="form-control" placeholder="Label"></div>
          <div class="col-md-3">
            <select id="new_agent_stage_color" class="form-select">
              <option value="primary">Blue</option>
              <option value="success">Green</option>
              <option value="warning">Orange</option>
              <option value="danger">Red</option>
              <option value="info">Cyan</option>
              <option value="secondary">Gray</option>
              <option value="dark">Dark</option>
            </select>
          </div>
          <div class="col-md-1"><input type="number" id="new_agent_stage_sort" class="form-control" placeholder="Order"></div>
          <div class="col-md-1 d-flex align-items-center justify-content-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="new_agent_stage_is_won">
              <label class="form-check-label" for="new_agent_stage_is_won">Won</label>
            </div>
          </div>
          <div class="col-md-1 d-flex align-items-center justify-content-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="new_agent_stage_is_lost">
              <label class="form-check-label" for="new_agent_stage_is_lost">Lost</label>
            </div>
          </div>
          <div class="col-md-1 d-grid"><button class="btn btn-primary" type="button" onclick="addAgentStage()">Add</button></div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Key</th>
                <th>Label</th>
                <th>Color</th>
                <th>Order</th>
                <th>Won</th>
                <th>Lost</th>
                <th>Move Leads To</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="agentStagesTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="location.reload()">Apply</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addAgentLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/agent/leads/store">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="modal-header">
          <h5 class="modal-title">Add Lead</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Property</label>
              <div class="d-flex gap-2">
                <select class="form-select" name="property_id" id="agent_lead_property" required>
                  <option value="">Select property</option>
                  <?php foreach (($properties ?? []) as $p): ?>
                    <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-outline-primary" id="agentLeadAddPropertyBtn" title="Add Property" data-bs-toggle="modal" data-bs-target="#agentAddPropertyModal">
                  <i class="bi bi-plus-circle"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unit (optional)</label>
              <select class="form-select" name="unit_id" id="agent_lead_unit">
                <option value="">Select unit</option>
                <?php foreach (($units ?? []) as $u): ?>
                  <option value="<?= (int)($u['id'] ?? 0) ?>" data-property-id="<?= (int)($u['property_id'] ?? 0) ?>">
                    <?= htmlspecialchars((string)($u['property_name'] ?? '')) ?> - Unit <?= htmlspecialchars((string)($u['unit_number'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Units list is filtered by selected property.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact</label>
              <input class="form-control" name="contact" required>
            </div>
            <div class="col-12">
              <label class="form-label">Message/Notes</label>
              <textarea class="form-control" name="message" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Lead</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  let draggedId = null;
  let winStageKey = null;
  function csrfToken(){
    return (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  }

  function computeWinStageKey(){
    const winCol = document.querySelector('.crm-col[data-is-won="1"]');
    winStageKey = winCol ? (winCol.getAttribute('data-stage') || null) : null;
  }
  computeWinStageKey();

  async function setStage(id, stage){
    const fd = new FormData();
    fd.append('csrf_token', csrfToken());
    fd.append('stage', stage);
    const res = await fetch('<?= BASE_URL ?>/agent/leads/update-stage/' + id, { method: 'POST', body: fd });
    const data = await res.json();
    if(!data || !data.success){
      throw new Error(data && data.message ? data.message : 'Failed');
    }
    return data;
  }

  const propertySel = document.getElementById('agent_lead_property');
  const unitSel = document.getElementById('agent_lead_unit');
  function filterUnits(){
    if(!propertySel || !unitSel) return;
    const pid = propertySel.value;
    Array.from(unitSel.options).forEach(opt=>{
      if(!opt.value) return;
      const ok = !pid || opt.getAttribute('data-property-id') === pid;
      opt.hidden = !ok;
    });
    if(unitSel.selectedOptions.length && unitSel.selectedOptions[0].hidden){
      unitSel.value = '';
    }
  }
  if(propertySel){
    propertySel.addEventListener('change', filterUnits);
    filterUnits();
  }

  const addPropBtn = document.getElementById('agentLeadAddPropertyBtn');
  const addPropModalEl = document.getElementById('agentAddPropertyModal');
  const addPropForm = document.getElementById('agentAddPropertyForm');
  const addPropErr = document.getElementById('agentAddPropertyError');
  const addPropSubmit = document.getElementById('agentAddPropertySubmit');
  const addPropModal = (addPropModalEl && window.bootstrap && window.bootstrap.Modal)
    ? window.bootstrap.Modal.getOrCreateInstance(addPropModalEl)
    : null;

  if(addPropBtn){
    addPropBtn.addEventListener('click', ()=>{
      if(addPropErr){ addPropErr.classList.add('d-none'); addPropErr.textContent = ''; }
      addPropForm?.reset();
    });
  }

  addPropForm?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    if(!propertySel) return;
    if(addPropErr){ addPropErr.classList.add('d-none'); addPropErr.textContent = ''; }
    if(addPropSubmit) addPropSubmit.disabled = true;
    try {
      const fd = new FormData(addPropForm);
      fd.append('csrf_token', csrfToken());
      const res = await fetch('<?= BASE_URL ?>/properties/store', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      });
      const data = await res.json();
      if(!data || !data.success || !data.property_id){
        throw new Error((data && data.message) ? data.message : 'Failed to create property');
      }
      const opt = document.createElement('option');
      opt.value = String(data.property_id);
      opt.textContent = String(fd.get('name') || 'New Property');
      propertySel.appendChild(opt);
      propertySel.value = opt.value;
      propertySel.dispatchEvent(new Event('change'));
      if (addPropModal) {
        addPropModal.hide();
      }
    } catch (err){
      if(addPropErr){
        addPropErr.textContent = String(err && err.message ? err.message : err);
        addPropErr.classList.remove('d-none');
      }
    } finally {
      if(addPropSubmit) addPropSubmit.disabled = false;
    }
  });

  function recomputeCounts(){
    document.querySelectorAll('.crm-col[data-stage]').forEach(col=>{
      const k = col.getAttribute('data-stage');
      const count = col.querySelectorAll('.crm-col-body .lead-card').length;
      const badge = document.getElementById('count_' + k);
      if(badge) badge.textContent = String(count);
    });
  }

  document.querySelectorAll('.lead-card').forEach(card=>{
    card.addEventListener('dragstart', ()=>{ draggedId = card.getAttribute('data-id'); });
  });

  document.querySelectorAll('[data-dropzone="1"]').forEach(zone=>{
    zone.addEventListener('dragover', (e)=>{ e.preventDefault(); zone.classList.add('crm-drop-hover'); });
    zone.addEventListener('dragleave', ()=> zone.classList.remove('crm-drop-hover'));
    zone.addEventListener('drop', async (e)=>{
      e.preventDefault();
      zone.classList.remove('crm-drop-hover');
      const stage = zone.getAttribute('data-stage');
      if(!draggedId || !stage) return;
      const card = document.querySelector('.lead-card[data-id="' + draggedId + '"]');
      if(!card) return;
      zone.appendChild(card);
      recomputeCounts();

      try {
        const data = await setStage(draggedId, stage);
        if(winStageKey && stage === winStageKey && data.lease_id){
          window.location.href = '<?= BASE_URL ?>' + '/leases/edit/' + data.lease_id;
          return;
        }
      } catch (err){
        location.reload();
      }
    });
  });

  document.addEventListener('click', async (e)=>{
    const btn = e.target && e.target.closest ? e.target.closest('[data-role="win-btn"]') : null;
    if(!btn) return;
    const id = btn.getAttribute('data-id');
    if(!id) return;
    if(!winStageKey){
      location.reload();
      return;
    }
    btn.disabled = true;
    try {
      const data = await setStage(id, winStageKey);
      if(data.lease_id){
        window.location.href = '<?= BASE_URL ?>' + '/leases/edit/' + data.lease_id;
        return;
      }
      location.reload();
    } catch(err){
      location.reload();
    }
  });

  async function loadAgentStages(){
    try {
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/stages');
      const data = await res.json();
      if(!data.success) return;
      const body = document.getElementById('agentStagesTableBody');
      if(!body) return;
      body.innerHTML = '';
      const stageList = (data.data || []);
      const stageOptionsHtml = stageList.map(ss => {
        const k = (ss.stage_key || '');
        const l = (ss.label || k);
        return `<option value="${String(k).replace(/"/g,'&quot;')}">${String(l).replace(/"/g,'&quot;')}</option>`;
      }).join('');

      stageList.forEach(s=>{
        const tr = document.createElement('tr');
        const stageKey = (s.stage_key || '');
        const deleteDisabled = ['new','contacted','qualified','won','lost'].includes(String(stageKey));
        tr.innerHTML = `
          <td><code>${(s.stage_key||'')}</code></td>
          <td><input class="form-control form-control-sm" value="${(s.label||'').replace(/\"/g,'&quot;')}" data-id="${s.id}" data-field="label"></td>
          <td>
            <select class="form-select form-select-sm" data-id="${s.id}" data-field="color_class">
              <option value="primary" ${s.color_class==='primary'?'selected':''}>Blue</option>
              <option value="success" ${s.color_class==='success'?'selected':''}>Green</option>
              <option value="warning" ${s.color_class==='warning'?'selected':''}>Orange</option>
              <option value="danger" ${s.color_class==='danger'?'selected':''}>Red</option>
              <option value="info" ${s.color_class==='info'?'selected':''}>Cyan</option>
              <option value="secondary" ${s.color_class==='secondary'?'selected':''}>Gray</option>
              <option value="dark" ${s.color_class==='dark'?'selected':''}>Dark</option>
            </select>
          </td>
          <td><input type="number" class="form-control form-control-sm" value="${parseInt(s.sort_order||0,10)}" data-id="${s.id}" data-field="sort_order"></td>
          <td class="text-center"><input type="checkbox" ${parseInt(s.is_won||0,10)===1?'checked':''} data-id="${s.id}" data-field="is_won"></td>
          <td class="text-center"><input type="checkbox" ${parseInt(s.is_lost||0,10)===1?'checked':''} data-id="${s.id}" data-field="is_lost"></td>
          <td>
            <select class="form-select form-select-sm" data-id="${s.id}" data-field="transfer_to">
              <option value="">(Choose)</option>
              ${stageOptionsHtml}
            </select>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary me-1" type="button" onclick="saveAgentStage(${s.id})"><i class="bi bi-save"></i></button>
            <button class="btn btn-sm btn-outline-danger" type="button" ${deleteDisabled?'disabled':''} onclick="deleteAgentStage(${s.id})"><i class="bi bi-trash"></i></button>
          </td>
        `;
        body.appendChild(tr);
      });
    } catch(e){}
  }

  window.addAgentStage = async function(){
    const stage_key = (document.getElementById('new_agent_stage_key')?.value || '').trim();
    const label = (document.getElementById('new_agent_stage_label')?.value || '').trim();
    const color_class = (document.getElementById('new_agent_stage_color')?.value || 'secondary');
    const sort_order = (document.getElementById('new_agent_stage_sort')?.value || '0');
    const is_won = document.getElementById('new_agent_stage_is_won')?.checked ? '1' : '0';
    const is_lost = document.getElementById('new_agent_stage_is_lost')?.checked ? '1' : '0';
    const fd = new FormData();
    fd.append('csrf_token', csrfToken());
    fd.append('stage_key', stage_key);
    fd.append('label', label);
    fd.append('color_class', color_class);
    fd.append('sort_order', sort_order);
    fd.append('is_won', is_won);
    fd.append('is_lost', is_lost);
    try{
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/stages/store', { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){ alert(data.message || 'Failed to add stage'); return; }
      document.getElementById('new_agent_stage_key').value='';
      document.getElementById('new_agent_stage_label').value='';
      document.getElementById('new_agent_stage_sort').value='';
      if (document.getElementById('new_agent_stage_is_won')) document.getElementById('new_agent_stage_is_won').checked = false;
      if (document.getElementById('new_agent_stage_is_lost')) document.getElementById('new_agent_stage_is_lost').checked = false;
      await loadAgentStages();
    }catch(e){ alert('Failed to add stage'); }
  }

  window.saveAgentStage = async function(id){
    const label = document.querySelector(`[data-id="${id}"][data-field="label"]`)?.value || '';
    const color_class = document.querySelector(`[data-id="${id}"][data-field="color_class"]`)?.value || 'secondary';
    const sort_order = document.querySelector(`[data-id="${id}"][data-field="sort_order"]`)?.value || '0';
    const is_won = document.querySelector(`[data-id="${id}"][data-field="is_won"]`)?.checked ? '1' : '0';
    const is_lost = document.querySelector(`[data-id="${id}"][data-field="is_lost"]`)?.checked ? '1' : '0';
    const fd = new FormData();
    fd.append('csrf_token', csrfToken());
    fd.append('label', label);
    fd.append('color_class', color_class);
    fd.append('sort_order', sort_order);
    fd.append('is_won', is_won);
    fd.append('is_lost', is_lost);
    try{
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/stages/update/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){ alert(data.message || 'Failed to save'); return; }
    }catch(e){ alert('Failed to save'); }
  }

  window.deleteAgentStage = async function(id){
    const transfer_to = document.querySelector(`[data-id="${id}"][data-field="transfer_to"]`)?.value || '';
    if(!confirm('Delete this stage? If leads exist in this stage, you must choose "Move Leads To".')) return;
    try{
      const fd = new FormData();
      fd.append('csrf_token', csrfToken());
      fd.append('transfer_to', transfer_to);
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/stages/delete/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){ alert(data.message || 'Failed to delete'); return; }
      await loadAgentStages();
    }catch(e){ alert('Failed to delete'); }
  }

  document.getElementById('manageAgentStagesModal')?.addEventListener('shown.bs.modal', loadAgentStages);
})();
</script>

<div class="modal fade" id="agentAddPropertyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="agentAddPropertyForm">
        <div class="modal-header">
          <h5 class="modal-title">Add Property</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Property Type</label>
              <select class="form-select" name="property_type" required>
                <option value="">Select Type</option>
                <option value="apartment">Apartment</option>
                <option value="house">House</option>
                <option value="commercial">Commercial</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Address</label>
              <input class="form-control" name="address" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">City</label>
              <input class="form-control" name="city" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">State</label>
              <input class="form-control" name="state" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">ZIP Code</label>
              <input class="form-control" name="zip_code" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="alert alert-danger mt-3 d-none" id="agentAddPropertyError"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="agentAddPropertySubmit">Create Property</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
