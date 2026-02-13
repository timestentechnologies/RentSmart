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

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">All Leads</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="realtorLeadsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($leads ?? []) as $x): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($x['name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['phone'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['email'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['source'] ?? '')) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($x['status'] ?? 'new')) ?></span></td>
                                <td>
                                    <?php if (($x['status'] ?? 'new') !== 'won'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="convertLead(<?= (int)$x['id'] ?>)"><i class="bi bi-check2-circle"></i> Win</button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editRealtorLead(<?= (int)$x['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRealtorLead(<?= (int)$x['id'] ?>)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
