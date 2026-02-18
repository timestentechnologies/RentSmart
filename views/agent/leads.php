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

        .agent-win-footer { display:flex; flex-wrap:wrap; gap: 10px; justify-content:flex-end; }
        .btn-brand-orange { background:#f7941d; border-color:#f7941d; color:#fff; }
        .btn-brand-orange:hover { background:#e98300; border-color:#e98300; color:#fff; }
        .btn-brand-purple { background:#6f42c1; border-color:#6f42c1; color:#fff; }
        .btn-brand-purple:hover { background:#5a34a3; border-color:#5a34a3; color:#fff; }
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
              <label class="form-label">Client Name</label>
              <input class="form-control" name="name" placeholder="John Doe" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Property</label>
              <input class="form-control" name="property_name" placeholder="Type property name" required>
              <div class="form-text">Not linked to your Properties list.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input class="form-control" name="phone" placeholder="e.g. 0712345678">
              <div class="form-text">Provide phone or email (at least one).</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" placeholder="e.g. name@example.com">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <input class="form-control" name="address" placeholder="e.g. Westlands, Nairobi">
            </div>
            <div class="col-12">
              <div class="form-text">Note: CRM leads are not linked to your Properties list, so they are not limited by subscription property caps.</div>
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
window.addEventListener('DOMContentLoaded', function(){
  let draggedId = null;
  let winStageKey = null;
  let pendingWinLeadId = null;
  let pendingWinStage = null;
  let pendingWinCard = null;
  let pendingWinFromZone = null;
  function csrfToken(){
    return (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  }

  function computeWinStageKey(){
    const winCol = document.querySelector('.crm-col[data-is-won="1"]');
    winStageKey = winCol ? (winCol.getAttribute('data-stage') || null) : null;
  }
  computeWinStageKey();

  function setCardWonUI(card, isWon){
    if(!card) return;
    const btn = card.querySelector('[data-role="win-btn"]');
    if(btn){
      btn.style.display = isWon ? 'none' : '';
      btn.disabled = false;
    }
  }

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

  function showWinModal(leadId, stageKey){
    pendingWinLeadId = leadId;
    pendingWinStage = stageKey;
    if(window.bootstrap && window.bootstrap.Modal){
      window.bootstrap.Modal.getOrCreateInstance(document.getElementById('agentLeadWinModal')).show();
    }
  }

  async function confirmWin(action){
    if(!pendingWinLeadId || !pendingWinStage) return;
    const id = pendingWinLeadId;
    const stage = pendingWinStage;
    const card = pendingWinCard;
    const fromZone = pendingWinFromZone;
    pendingWinLeadId = null;
    pendingWinStage = null;
    pendingWinCard = null;
    pendingWinFromZone = null;

    // Move UI only after user confirms
    try {
      const targetZone = document.querySelector(`[data-dropzone="1"][data-stage="${stage}"]`);
      if(targetZone && card){
        targetZone.appendChild(card);
        recomputeCounts();
      }
    } catch(e){}

    try {
      if(action === 'create_property'){
        const fd = new FormData();
        fd.append('csrf_token', csrfToken());
        const res = await fetch('<?= BASE_URL ?>/agent/leads/win-create-property/' + id, { method: 'POST', body: fd });
        const data = await res.json();
        if(!data || !data.success){
          if(data && data.over_limit && data.upgrade_url){
            alert(data.message || 'You have reached your property limit.');
            window.location.href = data.upgrade_url;
            return;
          }
          throw new Error(data && data.message ? data.message : 'Failed');
        }
        window.location.href = data.redirect_url || ('<?= BASE_URL ?>' + '/properties');
        return;
      }

      await setStage(id, stage);
    } catch(e){
      // Revert UI on failure
      try {
        if(fromZone && card){
          fromZone.appendChild(card);
          recomputeCounts();
        }
      } catch(_e){}
      location.reload();
      return;
    }
    location.reload();
  }

  document.getElementById('agentLeadWinOnlyBtn')?.addEventListener('click', function(){
    const m = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(document.getElementById('agentLeadWinModal')) : null;
    if(m) m.hide();
    confirmWin('won_only');
  });
  document.getElementById('agentLeadWinCreatePropertyBtn')?.addEventListener('click', function(){
    const m = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(document.getElementById('agentLeadWinModal')) : null;
    if(m) m.hide();
    confirmWin('create_property');
  });

  // If user cancels/closes the modal, do not keep any pending win state.
  document.getElementById('agentLeadWinModal')?.addEventListener('hidden.bs.modal', function(){
    pendingWinLeadId = null;
    pendingWinStage = null;
    pendingWinCard = null;
    pendingWinFromZone = null;
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

      computeWinStageKey();
      const isWonTarget = (winStageKey && stage === winStageKey);

      try {
        if(isWonTarget){
          pendingWinCard = card;
          pendingWinFromZone = card.parentElement;
          showWinModal(draggedId, stage);
          return;
        }

        zone.appendChild(card);
        recomputeCounts();
        await setStage(draggedId, stage);
        card.setAttribute('data-stage', stage);
        setCardWonUI(card, false);
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
      const card = document.querySelector('.lead-card[data-id="' + id + '"]');
      pendingWinCard = card;
      pendingWinFromZone = card ? card.parentElement : null;
      showWinModal(id, winStageKey);
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
});
</script>

<div class="modal fade" id="agentLeadWinModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mark Lead as Won</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">Choose what you want to do:</div>
        <div class="form-text">Mark as won only keeps this as a CRM lead and does not affect your property limit.</div>
        <div class="form-text mt-1"><strong>Mark as won and create property</strong> will create a real Property and <strong>counts toward your subscription property limit</strong>.</div>
      </div>
      <div class="modal-footer">
        <div class="agent-win-footer w-100">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-brand-orange" id="agentLeadWinOnlyBtn">Mark as won only</button>
          <button type="button" class="btn btn-brand-purple" id="agentLeadWinCreatePropertyBtn">Mark as won and create property</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
