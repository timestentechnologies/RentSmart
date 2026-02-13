<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-kanban text-primary me-2"></i>CRM - Leads</h1>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                <i class="bi bi-plus-circle me-1"></i>Add Lead
            </button>
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
                <div class="col-md-6 text-md-end">
                    <span class="text-muted">Pipeline</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .crm-board { display:flex; gap: 12px; overflow-x:auto; padding-bottom: 6px; }
        .crm-col { min-width: 280px; flex: 1 0 280px; background: #f8f9fa; border: 1px solid rgba(0,0,0,.075); border-radius: 10px; }
        .crm-col-header { padding: 10px 12px; border-bottom: 1px solid rgba(0,0,0,.075); display:flex; align-items:center; justify-content:space-between; }
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
        $cols = [
            'new' => 'New',
            'contacted' => 'Qualified',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
        $grouped = [ 'new'=>[], 'contacted'=>[], 'won'=>[], 'lost'=>[] ];
        foreach (($leads ?? []) as $l) {
            $st = strtolower((string)($l['status'] ?? 'new'));
            if (!isset($grouped[$st])) { $st = 'new'; }
            $grouped[$st][] = $l;
        }
    ?>

    <div class="crm-board" id="crmBoard">
        <?php foreach ($cols as $key => $label): ?>
            <div class="crm-col" data-status="<?= htmlspecialchars($key) ?>">
                <div class="crm-col-header">
                    <div>
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
                            $amount = 0;
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
                                <div class="lead-sub">Ksh <?= number_format((float)$amount, 2) ?></div>
                            </div>
                            <div class="lead-sub mt-1">
                                <?= htmlspecialchars($phone) ?><?= $email ? ' • ' . htmlspecialchars($email) : '' ?>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                <?php if ($source !== ''): ?>
                                    <span class="badge bg-light text-dark lead-tag"><?= htmlspecialchars($source) ?></span>
                                <?php endif; ?>
                                <span class="badge bg-secondary lead-tag"><?= htmlspecialchars($label) ?></span>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <?php if (($x['status'] ?? 'new') !== 'won'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="convertLead(<?= $leadId ?>)"><i class="bi bi-check2-circle"></i> Win</button>
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
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="lost">Lost</option>
                    <option value="won">Won</option>
                </select>
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
                <label class="form-label">Status</label>
                <select id="edit_lead_status" name="status" class="form-select" required>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="lost">Lost</option>
                    <option value="won">Won</option>
                </select>
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
function recomputeCounts(){
  const statuses = ['new','contacted','won','lost'];
  statuses.forEach(st=>{
    const el = document.getElementById('count_' + st);
    if(!el) return;
    const count = document.querySelectorAll('.lead-card[data-status="' + st + '"]').length;
    el.textContent = String(count);
  });
}

function applyLeadSearch(){
  const q = (document.getElementById('leadSearch')?.value || '').toLowerCase().trim();
  document.querySelectorAll('.lead-card').forEach(card=>{
    if(!q){ card.style.display=''; return; }
    const hay = [
      card.getAttribute('data-name') || '',
      card.getAttribute('data-phone') || '',
      card.getAttribute('data-email') || '',
      card.getAttribute('data-source') || ''
    ].join(' ');
    card.style.display = hay.includes(q) ? '' : 'none';
  });
}

document.getElementById('leadSearch')?.addEventListener('input', applyLeadSearch);

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
      card.setAttribute('data-status', targetStatus);
      zone.appendChild(card);
      const stageMap = { new: 'New', contacted: 'Qualified', won: 'Won', lost: 'Lost' };
      const stageBadge = card.querySelector('.lead-tag.bg-secondary');
      if (stageBadge && stageMap[targetStatus]) {
        stageBadge.textContent = stageMap[targetStatus];
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
    if(data.success){ location.reload(); }
    else { alert(data.message || 'Failed to convert'); }
  } catch(e){ alert('Failed to convert'); }
}

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
