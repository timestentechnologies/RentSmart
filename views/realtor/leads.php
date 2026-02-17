<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-kanban text-primary me-2"></i>CRM - Leads</h1>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#manageStagesModal">
                    <i class="bi bi-sliders me-1"></i>Stages
                </button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Lead
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="leadSearch" class="form-control" placeholder="Search leads...">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="realtor-filters">
                        <select class="form-select" id="leadStageFilter">
                            <option value="">All Stages</option>
                            <?php foreach (($stagesArr ?? []) as $s): ?>
                                <?php $k = strtolower((string)($s['stage_key'] ?? '')); if ($k === '') continue; ?>
                                <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars((string)($s['label'] ?? $k)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-muted d-flex align-items-center">Pipeline</span>
                    </div>
                </div>
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
                ['stage_key'=>'contacted','label'=>'Qualified','color_class'=>'warning','is_won'=>0,'is_lost'=>0],
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
        foreach (($leads ?? []) as $l) {
            $st = strtolower((string)($l['status'] ?? 'new'));
            if (!isset($grouped[$st])) {
                $firstKey = 'new';
                foreach ($grouped as $kk => $_v) { $firstKey = $kk; break; }
                $st = $firstKey ?: 'new';
            }
            $grouped[$st][] = $l;
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
            ?>
            <div class="crm-col" data-status="<?= htmlspecialchars($key) ?>" data-color="<?= htmlspecialchars($colorClass) ?>" data-is-won="<?= (int)($stage['is_won'] ?? 0) ?>">
                <div class="crm-col-header">
                    <div>
                        <span class="crm-accent" style="background: <?= htmlspecialchars($accent) ?>;"></span>
                        <span class="crm-col-title"><?= htmlspecialchars($label) ?></span>
                        <span class="badge bg-light text-dark ms-2" id="count_<?= htmlspecialchars($key) ?>"><?= (int)count($grouped[$key] ?? []) ?></span>
                    </div>
                    <div class="text-muted"><i class="bi bi-plus"></i></div>
                </div>
                <div class="crm-col-body" data-dropzone="1" data-status="<?= htmlspecialchars($key) ?>">
                    <?php foreach (($grouped[$key] ?? []) as $x): ?>
                        <?php
                            $leadId = (int)($x['id'] ?? 0);
                            $name = (string)($x['name'] ?? '');
                            $phone = (string)($x['phone'] ?? '');
                            $email = (string)($x['email'] ?? '');
                            $source = (string)($x['source'] ?? '');
                            $status = strtolower((string)($x['status'] ?? 'new'));
                            $amount = (float)($x['amount'] ?? 0);
                            $stageDef = $stageMap[$status] ?? ['label'=>$label,'color_class'=>$colorClass,'is_won'=>0];
                            $stageLabel = (string)($stageDef['label'] ?? $label);
                            $stageColorClass = (string)($stageDef['color_class'] ?? 'secondary');
                            $isWonStage = (int)($stageDef['is_won'] ?? 0) === 1;
                        ?>
                        <div class="lead-card" draggable="true"
                             data-id="<?= $leadId ?>"
                             data-status="<?= htmlspecialchars($status) ?>"
                             data-name="<?= htmlspecialchars(strtolower($name)) ?>"
                             data-phone="<?= htmlspecialchars(strtolower($phone)) ?>"
                             data-email="<?= htmlspecialchars(strtolower($email)) ?>"
                             data-source="<?= htmlspecialchars(strtolower($source)) ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="lead-title"><?= htmlspecialchars($name) ?></div>
                                <div class="lead-sub">
                                    <?php if ($isWonStage): ?>
                                        <span class="text-success" title="Won" data-role="trophy"><i class="bi bi-trophy-fill"></i></span>
                                    <?php else: ?>
                                        <span class="text-success" title="Won" data-role="trophy" style="display:none;"><i class="bi bi-trophy-fill"></i></span>
                                    <?php endif; ?>
                                    <span class="ms-2">Ksh <?= number_format((float)$amount, 2) ?></span>
                                </div>
                            </div>
                            <div class="lead-sub mt-1">
                                <?= htmlspecialchars($phone) ?><?= $email ? ' • ' . htmlspecialchars($email) : '' ?>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                <?php if ($source !== ''): ?>
                                    <span class="badge bg-light text-dark lead-tag"><?= htmlspecialchars($source) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($x['listing_title'] ?? null)): ?>
                                    <span class="badge bg-light text-dark lead-tag"><?= htmlspecialchars((string)($x['listing_title'] ?? '')) ?></span>
                                <?php endif; ?>
                                <span class="badge bg-<?= htmlspecialchars($stageColorClass) ?> lead-tag" data-role="stage-badge"><?= htmlspecialchars($stageLabel) ?></span>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <?php if (!$isWonStage): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-role="win-btn" onclick="convertLead(<?= $leadId ?>)"><i class="bi bi-check2-circle"></i> Win</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-role="win-btn" style="display:none;" onclick="convertLead(<?= $leadId ?>)"><i class="bi bi-check2-circle"></i> Win</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editRealtorLead(<?= $leadId ?>)"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRealtorLead(<?= $leadId ?>)"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="manageStagesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage Stages</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3"><input type="text" id="new_stage_key" class="form-control" placeholder="key e.g follow_up"></div>
          <div class="col-md-3"><input type="text" id="new_stage_label" class="form-control" placeholder="Label"></div>
          <div class="col-md-3">
            <select id="new_stage_color" class="form-select">
              <option value="primary">Blue</option>
              <option value="success">Green</option>
              <option value="warning">Orange</option>
              <option value="danger">Red</option>
              <option value="info">Cyan</option>
              <option value="secondary">Gray</option>
              <option value="dark">Dark</option>
            </select>
          </div>
          <div class="col-md-1"><input type="number" id="new_stage_sort" class="form-control" placeholder="Order"></div>
          <div class="col-md-1 d-flex align-items-center justify-content-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="new_stage_is_won">
              <label class="form-check-label" for="new_stage_is_won">Won</label>
            </div>
          </div>
          <div class="col-md-1 d-flex align-items-center justify-content-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="new_stage_is_lost">
              <label class="form-check-label" for="new_stage_is_lost">Lost</label>
            </div>
          </div>
          <div class="col-md-1 d-grid"><button class="btn btn-primary" type="button" onclick="addStage()">Add</button></div>
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
                <th></th>
              </tr>
            </thead>
            <tbody id="stagesTableBody"></tbody>
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

<div class="modal fade" id="addLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/realtor/leads/store">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add Lead</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Source</label><input type="text" name="source" class="form-control" placeholder="e.g., Facebook, Walk-in, Referral"></div>
            <div class="mb-3">
                <label class="form-label">Listing / Property</label>
                <select name="realtor_listing_id" class="form-select">
                    <option value="">(Optional) Select Listing</option>
                    <?php foreach (($listings ?? []) as $l): ?>
                        <option value="<?= (int)($l['id'] ?? 0) ?>"><?= htmlspecialchars((string)($l['title'] ?? '')) ?><?= !empty($l['location'] ?? null) ? ' - ' . htmlspecialchars((string)($l['location'] ?? '')) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="lost">Lost</option>
                    <option value="won">Won</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" value="0">
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editLeadForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit Lead</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_lead_id">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" id="edit_lead_name" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" id="edit_lead_phone" name="phone" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" id="edit_lead_email" name="email" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Source</label><input type="text" id="edit_lead_source" name="source" class="form-control"></div>
            <div class="mb-3">
                <label class="form-label">Listing / Property</label>
                <select id="edit_lead_listing_id" name="realtor_listing_id" class="form-select">
                    <option value="">(Optional) Select Listing</option>
                    <?php foreach (($listings ?? []) as $l): ?>
                        <option value="<?= (int)($l['id'] ?? 0) ?>"><?= htmlspecialchars((string)($l['title'] ?? '')) ?><?= !empty($l['location'] ?? null) ? ' - ' . htmlspecialchars((string)($l['location'] ?? '')) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select id="edit_lead_status" name="status" class="form-select" required>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="lost">Lost</option>
                    <option value="won">Won</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" min="0" id="edit_lead_amount" name="amount" class="form-control" value="0">
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea id="edit_lead_notes" name="notes" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to delete this lead?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteLeadBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
function getAllStageKeys(){
  return Array.from(document.querySelectorAll('.crm-col[data-status]')).map(el=>el.getAttribute('data-status')).filter(Boolean);
}

function recomputeCounts(){
  const statuses = getAllStageKeys();
  statuses.forEach(st=>{
    const el = document.getElementById('count_' + st);
    if(!el) return;
    const count = document.querySelectorAll('.lead-card[data-status="' + st + '"]').length;
    el.textContent = String(count);
  });
}

function applyLeadSearch(){
  const q = (document.getElementById('leadSearch')?.value || '').toLowerCase().trim();
  const stage = (document.getElementById('leadStageFilter')?.value || '').toLowerCase().trim();
  document.querySelectorAll('.lead-card').forEach(card=>{
    const cardStage = (card.getAttribute('data-status') || '').toLowerCase().trim();
    const hay = [
      card.getAttribute('data-name') || '',
      card.getAttribute('data-phone') || '',
      card.getAttribute('data-email') || '',
      card.getAttribute('data-source') || ''
    ].join(' ');

    let ok = true;
    if (stage && cardStage !== stage) ok = false;
    if (q && !hay.includes(q)) ok = false;
    card.style.display = ok ? '' : 'none';
  });
}

document.getElementById('leadSearch')?.addEventListener('input', applyLeadSearch);
document.getElementById('leadStageFilter')?.addEventListener('change', applyLeadSearch);

let draggedLeadId = null;
document.querySelectorAll('.lead-card').forEach(card=>{
  card.addEventListener('dragstart', (e)=>{
    draggedLeadId = card.getAttribute('data-id');
    e.dataTransfer.effectAllowed = 'move';
  });
});

document.querySelectorAll('[data-dropzone="1"]').forEach(zone=>{
  zone.addEventListener('dragover', (e)=>{
    e.preventDefault();
    zone.classList.add('crm-drop-hover');
  });
  zone.addEventListener('dragleave', ()=> zone.classList.remove('crm-drop-hover'));
  zone.addEventListener('drop', async (e)=>{
    e.preventDefault();
    zone.classList.remove('crm-drop-hover');
    const targetStatus = zone.getAttribute('data-status');
    if(!draggedLeadId || !targetStatus) return;
    const card = document.querySelector('.lead-card[data-id="' + draggedLeadId + '"]');
    if(!card) return;
    const currentStatus = card.getAttribute('data-status');
    if(currentStatus === targetStatus) return;

    const formData = new FormData();
    formData.append('status', targetStatus);
    try {
      const res = await fetch('<?= BASE_URL ?>' + '/realtor/leads/update/' + draggedLeadId, { method:'POST', body: formData });
      const data = await res.json();
      if(!data.success){
        alert(data.message || 'Failed to update stage');
        return;
      }
      if(data.contract_id){
        window.location.href = '<?= BASE_URL ?>' + '/realtor/contracts/show/' + data.contract_id;
        return;
      }
      card.setAttribute('data-status', targetStatus);
      zone.appendChild(card);
      const col = document.querySelector('.crm-col[data-status="' + targetStatus + '"]');
      const stageLabel = col?.querySelector('.crm-col-title')?.textContent || targetStatus;
      const stageColor = col?.getAttribute('data-color') || 'secondary';
      const isWon = (col?.getAttribute('data-is-won') || '0') === '1';
      const stageBadge = card.querySelector('[data-role="stage-badge"]');
      if (stageBadge) {
        stageBadge.textContent = stageLabel;
        stageBadge.className = 'badge bg-' + stageColor + ' lead-tag';
        stageBadge.setAttribute('data-role','stage-badge');
      }

      const winBtn = card.querySelector('[data-role="win-btn"]');
      if (winBtn) {
        winBtn.style.display = isWon ? 'none' : '';
      }
      const trophy = card.querySelector('[data-role="trophy"]');
      if (trophy) {
        trophy.style.display = isWon ? '' : 'none';
      }
      recomputeCounts();
      applyLeadSearch();
    } catch(err){
      alert('Failed to update stage');
    }
  });
});

function editRealtorLead(id){
  fetch('<?= BASE_URL ?>' + '/realtor/leads/get/' + id)
    .then(r=>r.json()).then(resp=>{
      if(!resp.success){ alert('Lead not found'); return; }
      const e = resp.data;
      document.getElementById('edit_lead_id').value = e.id;
      document.getElementById('edit_lead_name').value = e.name || '';
      document.getElementById('edit_lead_phone').value = e.phone || '';
      document.getElementById('edit_lead_email').value = e.email || '';
      document.getElementById('edit_lead_source').value = e.source || '';
      document.getElementById('edit_lead_amount').value = (e.amount !== undefined && e.amount !== null) ? e.amount : 0;
      if (document.getElementById('edit_lead_listing_id')) {
        document.getElementById('edit_lead_listing_id').value = e.realtor_listing_id || '';
      }
      document.getElementById('edit_lead_status').value = e.status || 'new';
      document.getElementById('edit_lead_notes').value = e.notes || '';
      new bootstrap.Modal(document.getElementById('editLeadModal')).show();
    }).catch(()=>alert('Failed to load lead'));
}

document.getElementById('editLeadForm')?.addEventListener('submit', function(ev){
  ev.preventDefault();
  const id = document.getElementById('edit_lead_id').value;
  const formData = new FormData(ev.target);
  fetch('<?= BASE_URL ?>' + '/realtor/leads/update/' + id, { method:'POST', body: formData })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to update lead'));
});

async function convertLead(id){
  try {
    const res = await fetch('<?= BASE_URL ?>' + '/realtor/leads/convert/' + id, { method:'POST' });
    const data = await res.json();
    if(data.success){
      if(data.contract_id){
        window.location.href = '<?= BASE_URL ?>' + '/realtor/contracts/show/' + data.contract_id;
        return;
      }
      location.reload();
    }
    else { alert(data.message || 'Failed to convert'); }
  } catch(e){ alert('Failed to convert'); }
}

async function loadStages(){
  try {
    const res = await fetch('<?= BASE_URL ?>' + '/realtor/leads/stages');
    const data = await res.json();
    if(!data.success) return;
    const body = document.getElementById('stagesTableBody');
    if(!body) return;
    body.innerHTML = '';
    (data.data || []).forEach(s=>{
      const tr = document.createElement('tr');
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
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary me-1" type="button" onclick="saveStage(${s.id})"><i class="bi bi-save"></i></button>
          <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteStage(${s.id})"><i class="bi bi-trash"></i></button>
        </td>
      `;
      body.appendChild(tr);
    });
  } catch(e){}
}

async function addStage(){
  const stage_key = (document.getElementById('new_stage_key')?.value || '').trim();
  const label = (document.getElementById('new_stage_label')?.value || '').trim();
  const color_class = (document.getElementById('new_stage_color')?.value || 'secondary');
  const sort_order = (document.getElementById('new_stage_sort')?.value || '0');
  const is_won = document.getElementById('new_stage_is_won')?.checked ? '1' : '0';
  const is_lost = document.getElementById('new_stage_is_lost')?.checked ? '1' : '0';
  const fd = new FormData();
  fd.append('stage_key', stage_key);
  fd.append('label', label);
  fd.append('color_class', color_class);
  fd.append('sort_order', sort_order);
  fd.append('is_won', is_won);
  fd.append('is_lost', is_lost);
  try{
    const res = await fetch('<?= BASE_URL ?>' + '/realtor/leads/stages/store', { method:'POST', body: fd });
    const data = await res.json();
    if(!data.success){ alert(data.message || 'Failed to add stage'); return; }
    document.getElementById('new_stage_key').value='';
    document.getElementById('new_stage_label').value='';
    document.getElementById('new_stage_sort').value='';
    if (document.getElementById('new_stage_is_won')) document.getElementById('new_stage_is_won').checked = false;
    if (document.getElementById('new_stage_is_lost')) document.getElementById('new_stage_is_lost').checked = false;
    await loadStages();
  }catch(e){ alert('Failed to add stage'); }
}

async function saveStage(id){
  const label = document.querySelector(`[data-id="${id}"][data-field="label"]`)?.value || '';
  const color_class = document.querySelector(`[data-id="${id}"][data-field="color_class"]`)?.value || 'secondary';
  const sort_order = document.querySelector(`[data-id="${id}"][data-field="sort_order"]`)?.value || '0';
  const is_won = document.querySelector(`[data-id="${id}"][data-field="is_won"]`)?.checked ? '1' : '0';
  const is_lost = document.querySelector(`[data-id="${id}"][data-field="is_lost"]`)?.checked ? '1' : '0';
  const fd = new FormData();
  fd.append('label', label);
  fd.append('color_class', color_class);
  fd.append('sort_order', sort_order);
  fd.append('is_won', is_won);
  fd.append('is_lost', is_lost);
  try{
    const res = await fetch('<?= BASE_URL ?>' + '/realtor/leads/stages/update/' + id, { method:'POST', body: fd });
    const data = await res.json();
    if(!data.success){ alert(data.message || 'Failed to save'); return; }
  }catch(e){ alert('Failed to save'); }
}

async function deleteStage(id){
  if(!confirm('Delete this stage?')) return;
  try{
    const res = await fetch('<?= BASE_URL ?>' + '/realtor/leads/stages/delete/' + id, { method:'POST' });
    const data = await res.json();
    if(!data.success){ alert(data.message || 'Failed to delete'); return; }
    await loadStages();
  }catch(e){ alert('Failed to delete'); }
}

document.getElementById('manageStagesModal')?.addEventListener('shown.bs.modal', loadStages);

let deleteLeadId = null;
function confirmDeleteRealtorLead(id){
  deleteLeadId = id;
  new bootstrap.Modal(document.getElementById('deleteLeadModal')).show();
}

document.getElementById('confirmDeleteLeadBtn')?.addEventListener('click', function(){
  if(!deleteLeadId) return;
  fetch('<?= BASE_URL ?>' + '/realtor/leads/delete/' + deleteLeadId, { method:'POST' })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to delete lead'));
});

recomputeCounts();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
