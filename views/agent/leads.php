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
        .lead-actions { display:flex; gap: 8px; justify-content:flex-end; align-items:center; }
        .lead-actions .btn { padding: .15rem .4rem; }
        .lead-stage-badge { font-size: .70rem; }

        .agent-win-footer { display:flex; flex-wrap:nowrap; gap: 10px; justify-content:flex-end; align-items:center; }
        .agent-win-footer .btn { white-space: nowrap; }
        .btn-brand-orange { background:#f7941d; border-color:#f7941d; color:#fff; }
        .btn-brand-orange:hover { background:#e98300; border-color:#e98300; color:#fff; }
        .btn-brand-purple { background:#6f42c1; border-color:#6f42c1; color:#fff; }
        .btn-brand-purple:hover { background:#5a34a3; border-color:#5a34a3; color:#fff; }

        #manageAgentStagesModal .modal-content,
        #manageAgentStagesModal .modal-body,
        #manageAgentStagesModal .table-responsive,
        #manageAgentStagesModal button { pointer-events: auto; }

        #confirmDeleteAgentStageModal { z-index: 1085; }
        #agentStagesMessageModal { z-index: 1085; }
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
                            $cardStageMeta = $stageMap[$key] ?? $stage;
                            $cardStageLabel = (string)($cardStageMeta['label'] ?? $key);
                            $cardStageColor = (string)($cardStageMeta['color_class'] ?? $colorClass);
                        ?>
                        <div class="lead-card" draggable="true" data-id="<?= $id ?>" data-stage="<?= htmlspecialchars($key) ?>">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="lead-title"><?= htmlspecialchars($name) ?></div>
                                <span class="badge bg-<?= htmlspecialchars($cardStageColor) ?> lead-stage-badge"><?= htmlspecialchars($cardStageLabel) ?></span>
                            </div>
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

                            <div class="mt-2 d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <?php if (!$isWonStage): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" data-role="win-btn" data-id="<?= $id ?>">
                                            <i class="bi bi-check2-circle"></i> Win
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="lead-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-role="edit-lead" data-id="<?= $id ?>" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-role="delete-lead" data-id="<?= $id ?>" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="agentStagesMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Stages</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="agentStagesMessageBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmDeleteAgentStageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Stage</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div>Delete this stage? If leads exist in this stage, you must choose "Move Leads To" first.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteAgentStageBtn">Delete</button>
      </div>
    </div>
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
          <div class="col-md-1 d-grid"><button class="btn btn-primary" type="button" id="addAgentStageBtn">Add</button></div>
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
        <button type="button" class="btn btn-primary" id="applyAgentStagesBtn">Apply</button>
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

<div class="modal fade" id="editAgentLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editAgentLeadForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="lead_id" id="edit_agent_lead_id" value="">
        <div class="modal-header">
          <h5 class="modal-title">Edit Lead</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Client Name</label>
              <input class="form-control" name="name" id="edit_agent_lead_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Property</label>
              <input class="form-control" name="property_name" id="edit_agent_lead_property_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input class="form-control" name="phone" id="edit_agent_lead_phone" placeholder="e.g. 0712345678">
              <div class="form-text">Provide phone or email (at least one).</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" id="edit_agent_lead_email" placeholder="e.g. name@example.com">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <input class="form-control" name="address" id="edit_agent_lead_address" placeholder="e.g. Westlands, Nairobi">
            </div>
            <div class="col-12">
              <label class="form-label">Message/Notes</label>
              <textarea class="form-control" name="message" id="edit_agent_lead_message" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveEditAgentLeadBtn">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmDeleteAgentLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Lead</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Delete this lead? This cannot be undone.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteAgentLeadBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
// Global handler (not dependent on DOMContentLoaded) to ensure stage actions always respond.
if (!window.__agentStagesGlobalClickBound) {
  window.__agentStagesGlobalClickBound = true;
  const handleStageActionEvent = function(e){
    const addBtn = e.target && e.target.closest ? e.target.closest('#addAgentStageBtn') : null;
    if(addBtn){
      e.preventDefault();
      window.__agentStagesLastEvent = { type: e.type, action: 'add' };
      console.log('[agent stages] add clicked');
      if (typeof window.addAgentStage === 'function') window.addAgentStage();
      return;
    }

    const applyBtn = e.target && e.target.closest ? e.target.closest('#applyAgentStagesBtn') : null;
    if(applyBtn){
      e.preventDefault();
      window.__agentStagesLastEvent = { type: e.type, action: 'apply' };
      console.log('[agent stages] apply clicked');
      location.reload();
      return;
    }

    const actionBtn = e.target && e.target.closest ? e.target.closest('#agentStagesTableBody button[data-action]') : null;
    if(!actionBtn) return;
    e.preventDefault();
    const action = actionBtn.getAttribute('data-action');
    const id = parseInt(actionBtn.getAttribute('data-id') || '0', 10);
    window.__agentStagesLastEvent = { type: e.type, action, id };
    console.log('[agent stages] action clicked', action, id);
    if(!id) return;
    if(action === 'save-stage' && typeof window.saveAgentStage === 'function'){
      window.saveAgentStage(id);
    }
    if(action === 'delete-stage' && typeof window.deleteAgentStage === 'function'){
      window.deleteAgentStage(id);
    }
  };

  // Some environments suppress click; listen to pointerup/mouseup as well.
  document.addEventListener('click', handleStageActionEvent, true);
  document.addEventListener('pointerup', handleStageActionEvent, true);
  document.addEventListener('mouseup', handleStageActionEvent, true);
}

window.addEventListener('DOMContentLoaded', function(){
  let draggedId = null;
  let winStageKey = null;
  let pendingWinLeadId = null;
  let pendingWinStage = null;
  let pendingWinCard = null;
  let pendingWinFromZone = null;
  let pendingDeleteLeadId = null;
  function csrfToken(){
    return (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  }

  function parseContactToPhoneEmail(contact){
    const out = { phone: '', email: '' };
    const c = String(contact || '');
    const parts = c.split('/').map(p => p.trim()).filter(Boolean);
    parts.forEach(p => {
      if (p.includes('@')) {
        if (!out.email) out.email = p;
      } else {
        if (!out.phone) out.phone = p;
      }
    });
    return out;
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
        let data = null;
        try {
          data = await res.json();
        } catch(_e) {
          const txt = await res.text();
          throw new Error('Unexpected response from server. ' + (txt ? String(txt).slice(0, 200) : ''));
        }
        if(!res.ok){
          throw new Error((data && data.message) ? data.message : ('Request failed (' + res.status + ')'));
        }
        if(!data || !data.success){
          if(data && data.over_limit && data.upgrade_url){
            try {
              window.__agentOverLimitWin = {
                id: id,
                stage: stage,
                upgrade_url: data.upgrade_url,
                message: data.message || 'You have reached your property limit.'
              };
              const modalEl = document.getElementById('agentOverLimitModal');
              if(modalEl && window.bootstrap && window.bootstrap.Modal){
                const msgEl = modalEl.querySelector('[data-agent-overlimit-message]');
                if(msgEl) msgEl.textContent = window.__agentOverLimitWin.message;
                window.bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: true }).show();
                return;
              }
            } catch(e){}
            alert(data.message || 'You have reached your property limit.');
            return;
          }
          throw new Error(data && data.message ? data.message : 'Failed');
        }
        const fallback = (data && data.property_id) ? ('<?= BASE_URL ?>' + '/properties?edit=' + String(data.property_id)) : ('<?= BASE_URL ?>' + '/properties');
        const target = (data && data.redirect_url) ? data.redirect_url : fallback;
        window.location.assign(target);
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
      alert((e && e.message) ? e.message : 'Action failed');
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
    const editBtn = e.target && e.target.closest ? e.target.closest('[data-role="edit-lead"]') : null;
    if(editBtn){
      e.preventDefault();
      const id = editBtn.getAttribute('data-id');
      if(!id) return;
      try {
        const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/get/' + id);
        const data = await res.json();
        if(!data || !data.success || !data.data){
          location.reload();
          return;
        }
        const lead = data.data;
        const ce = parseContactToPhoneEmail(lead.contact || '');
        const idEl = document.getElementById('edit_agent_lead_id');
        if(idEl) idEl.value = String(id);
        const nEl = document.getElementById('edit_agent_lead_name');
        if(nEl) nEl.value = String(lead.name || '');
        const pnEl = document.getElementById('edit_agent_lead_property_name');
        if(pnEl) pnEl.value = String(lead.property_name || '');
        const pEl = document.getElementById('edit_agent_lead_phone');
        if(pEl) pEl.value = String(ce.phone || '');
        const emEl = document.getElementById('edit_agent_lead_email');
        if(emEl) emEl.value = String(ce.email || '');
        const aEl = document.getElementById('edit_agent_lead_address');
        if(aEl) aEl.value = String(lead.address || '');
        const mEl = document.getElementById('edit_agent_lead_message');
        if(mEl) mEl.value = String(lead.message || '');
        if(window.bootstrap && window.bootstrap.Modal){
          window.bootstrap.Modal.getOrCreateInstance(document.getElementById('editAgentLeadModal')).show();
        }
      } catch(err){ location.reload(); }
      return;
    }

    const delBtn = e.target && e.target.closest ? e.target.closest('[data-role="delete-lead"]') : null;
    if(delBtn){
      e.preventDefault();
      const id = delBtn.getAttribute('data-id');
      pendingDeleteLeadId = parseInt(id || '0', 10) || null;
      const mEl = document.getElementById('confirmDeleteAgentLeadModal');
      if(window.bootstrap && window.bootstrap.Modal && mEl){
        window.bootstrap.Modal.getOrCreateInstance(mEl).show();
      }
      return;
    }

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

  document.getElementById('editAgentLeadForm')?.addEventListener('submit', async function(e){
    e.preventDefault();
    const id = document.getElementById('edit_agent_lead_id')?.value || '';
    if(!id) return;
    try {
      const fd = new FormData(this);
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/update/' + id, { method: 'POST', body: fd });
      const data = await res.json();
      if(!data || !data.success){
        location.reload();
        return;
      }
    } catch(err){
      location.reload();
      return;
    }
    location.reload();
  });

  document.getElementById('confirmDeleteAgentLeadBtn')?.addEventListener('click', async function(){
    const id = parseInt(pendingDeleteLeadId || 0, 10);
    const mEl = document.getElementById('confirmDeleteAgentLeadModal');
    const m = window.bootstrap && window.bootstrap.Modal && mEl ? window.bootstrap.Modal.getInstance(mEl) : null;
    if(m) m.hide();
    if(!id) return;
    pendingDeleteLeadId = null;
    try {
      const fd = new FormData();
      fd.append('csrf_token', csrfToken());
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/delete/' + id, { method: 'POST', body: fd });
      const data = await res.json();
      if(!data || !data.success){
        location.reload();
        return;
      }
    } catch(err){
      location.reload();
      return;
    }
    location.reload();
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
            <button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="save-stage" data-id="${s.id}"><i class="bi bi-save"></i></button>
            <button class="btn btn-sm btn-outline-danger" type="button" data-action="delete-stage" data-id="${s.id}" ${deleteDisabled?'disabled':''}><i class="bi bi-trash"></i></button>
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
    window.__pendingDeleteStageId = parseInt(id || 0, 10) || 0;
    const mEl = document.getElementById('confirmDeleteAgentStageModal');
    if(window.bootstrap && window.bootstrap.Modal && mEl){
      window.bootstrap.Modal.getOrCreateInstance(mEl).show();
    }
  }

  async function performDeleteAgentStage(id){
    const transfer_to = document.querySelector(`[data-id="${id}"][data-field="transfer_to"]`)?.value || '';
    try{
      const fd = new FormData();
      fd.append('csrf_token', csrfToken());
      fd.append('transfer_to', transfer_to);
      const res = await fetch('<?= BASE_URL ?>' + '/agent/leads/stages/delete/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){
        showAgentStagesMessage(data.message || 'Failed to delete');
        return;
      }
      await loadAgentStages();
    }catch(e){ showAgentStagesMessage('Failed to delete'); }
  }

  function showAgentStagesMessage(msg){
    const body = document.getElementById('agentStagesMessageBody');
    if(body) body.textContent = String(msg || '');
    const el = document.getElementById('agentStagesMessageModal');
    if(window.bootstrap && window.bootstrap.Modal && el){
      window.bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
      alert(String(msg || ''));
    }
  }

  // Ensure message modal stacks above the Manage Stages modal.
  document.getElementById('agentStagesMessageModal')?.addEventListener('show.bs.modal', function(){
    const openCount = document.querySelectorAll('.modal.show').length;
    const zIndex = 1055 + (openCount * 20);
    this.style.zIndex = String(zIndex);
    setTimeout(function(){
      const backdrops = document.querySelectorAll('.modal-backdrop');
      const bd = backdrops[backdrops.length - 1];
      if(bd){
        bd.style.zIndex = String(zIndex - 10);
      }
    }, 0);
  });

  document.getElementById('confirmDeleteAgentStageBtn')?.addEventListener('click', async function(){
    const id = parseInt(window.__pendingDeleteStageId || 0, 10);
    const mEl = document.getElementById('confirmDeleteAgentStageModal');
    const m = window.bootstrap && window.bootstrap.Modal && mEl ? window.bootstrap.Modal.getInstance(mEl) : null;
    if(m) m.hide();
    if(!id) return;
    window.__pendingDeleteStageId = 0;
    await performDeleteAgentStage(id);
  });

  // Ensure confirm modal stacks above the Manage Stages modal (Bootstrap doesn't always handle nested modals).
  document.getElementById('confirmDeleteAgentStageModal')?.addEventListener('show.bs.modal', function(){
    const openCount = document.querySelectorAll('.modal.show').length;
    const zIndex = 1055 + (openCount * 20);
    this.style.zIndex = String(zIndex);
    setTimeout(function(){
      const backdrops = document.querySelectorAll('.modal-backdrop');
      const bd = backdrops[backdrops.length - 1];
      if(bd){
        bd.style.zIndex = String(zIndex - 10);
      }
    }, 0);
  });

  document.getElementById('manageAgentStagesModal')?.addEventListener('shown.bs.modal', loadAgentStages);

  document.getElementById('agentOverLimitWinOnlyBtn')?.addEventListener('click', async function(){
    const mEl = document.getElementById('agentOverLimitModal');
    const m = window.bootstrap && window.bootstrap.Modal && mEl ? window.bootstrap.Modal.getInstance(mEl) : null;
    if(m) m.hide();
    const payload = window.__agentOverLimitWin || null;
    window.__agentOverLimitWin = null;
    if(!payload || !payload.id || !payload.stage) return;
    try {
      await setStage(payload.id, payload.stage);
    } catch(e) {}
    location.reload();
  });

  document.getElementById('agentOverLimitUpgradeBtn')?.addEventListener('click', function(){
    const payload = window.__agentOverLimitWin || null;
    const target = payload && payload.upgrade_url ? payload.upgrade_url : ('<?= BASE_URL ?>' + '/subscription/renew');
    window.location.href = target;
  });

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
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-sm btn-brand-orange" id="agentLeadWinOnlyBtn">Mark as won only</button>
          <button type="button" class="btn btn-sm btn-brand-purple" id="agentLeadWinCreatePropertyBtn">Mark as won and create property</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="agentOverLimitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upgrade Required</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div data-agent-overlimit-message>You have reached your plan limit. Please upgrade to continue.</div>
        <div class="form-text mt-2">You can still mark this lead as won only without creating a property.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-brand-orange" id="agentOverLimitWinOnlyBtn">Win Only</button>
        <button type="button" class="btn btn-sm btn-brand-purple" id="agentOverLimitUpgradeBtn">Upgrade</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
