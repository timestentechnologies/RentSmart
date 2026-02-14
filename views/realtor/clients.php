<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-people text-primary me-2"></i>Clients</h1>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/realtor/contracts">
                    <i class="bi bi-file-text me-1"></i>Contracts
                </a>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Client
                </button>
            </div>

<div class="modal fade" id="clientContractModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/realtor/contracts/store" id="clientContractForm">
        <?= csrf_field() ?>
        <input type="hidden" name="realtor_client_id" id="cc_client_id">
        <input type="hidden" name="realtor_listing_id" id="cc_listing_id">
        <div class="modal-header">
          <h5 class="modal-title">Create Contract</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-2">
                <div class="small text-muted">Client</div>
                <div class="fw-semibold" id="cc_client_name"></div>
            </div>
            <div class="mb-3">
                <div class="small text-muted">Listing</div>
                <div class="fw-semibold" id="cc_listing_title"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Payment Terms</label>
                <select class="form-select" name="terms_type" id="cc_terms_type" required>
                    <option value="one_time">One Time</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Total Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" min="0" class="form-control" name="total_amount" id="cc_total_amount" required>
                </div>
            </div>

            <div id="cc_monthly_fields" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">Start Month</label>
                    <input type="month" class="form-control" name="start_month" id="cc_start_month">
                </div>
                <div class="mb-3">
                    <label class="form-label">Duration (Months)</label>
                    <input type="number" min="1" step="1" class="form-control" name="duration_months" id="cc_duration_months">
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Create Contract</button>
        </div>
      </form>
    </div>
  </div>
</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">All Clients</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="realtorClientsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Listing</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($clients ?? []) as $x): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($x['name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['phone'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['email'] ?? '')) ?></td>
                                <td>
                                    <?php if (!empty($x['listing_title'] ?? null)): ?>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($x['listing_title'] ?? '')) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars((string)($x['listing_location'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-truncate" style="max-width:240px;" title="<?= htmlspecialchars((string)($x['notes'] ?? '')) ?>"><?= htmlspecialchars((string)($x['notes'] ?? '')) ?></td>
                                <td>
                                    <?php if (!empty($x['realtor_listing_id'] ?? null)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="openClientContractModal(<?= (int)$x['id'] ?>, <?= (int)($x['realtor_listing_id'] ?? 0) ?>, '<?= htmlspecialchars((string)($x['name'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars((string)($x['listing_title'] ?? ''), ENT_QUOTES) ?>')" title="Create Contract">
                                            <i class="bi bi-file-earmark-plus"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editRealtorClient(<?= (int)$x['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRealtorClient(<?= (int)$x['id'] ?>)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/realtor/clients/store">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
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

<div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editClientForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_client_id">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" id="edit_client_name" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" id="edit_client_phone" name="phone" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" id="edit_client_email" name="email" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea id="edit_client_notes" name="notes" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to delete this client?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteClientBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
function openClientContractModal(clientId, listingId, clientName, listingTitle){
  document.getElementById('cc_client_id').value = String(clientId || '');
  document.getElementById('cc_listing_id').value = String(listingId || '');
  document.getElementById('cc_client_name').textContent = clientName || '';
  document.getElementById('cc_listing_title').textContent = listingTitle || '';
  document.getElementById('cc_terms_type').value = 'one_time';
  document.getElementById('cc_total_amount').value = '';
  document.getElementById('cc_start_month').value = '';
  document.getElementById('cc_duration_months').value = '';
  toggleClientContractTerms();
  new bootstrap.Modal(document.getElementById('clientContractModal')).show();
}

function toggleClientContractTerms(){
  const type = document.getElementById('cc_terms_type').value;
  const fields = document.getElementById('cc_monthly_fields');
  const start = document.getElementById('cc_start_month');
  const dur = document.getElementById('cc_duration_months');
  if(type === 'monthly'){
    fields.style.display = '';
    start.setAttribute('required','');
    dur.setAttribute('required','');
  } else {
    fields.style.display = 'none';
    start.removeAttribute('required');
    dur.removeAttribute('required');
  }
}

document.getElementById('cc_terms_type')?.addEventListener('change', toggleClientContractTerms);

function editRealtorClient(id){
  fetch('<?= BASE_URL ?>' + '/realtor/clients/get/' + id)
    .then(r=>r.json()).then(resp=>{
      if(!resp.success){ alert('Client not found'); return; }
      const e = resp.data;
      document.getElementById('edit_client_id').value = e.id;
      document.getElementById('edit_client_name').value = e.name || '';
      document.getElementById('edit_client_phone').value = e.phone || '';
      document.getElementById('edit_client_email').value = e.email || '';
      document.getElementById('edit_client_notes').value = e.notes || '';
      new bootstrap.Modal(document.getElementById('editClientModal')).show();
    }).catch(()=>alert('Failed to load client'));
}

document.getElementById('editClientForm')?.addEventListener('submit', function(ev){
  ev.preventDefault();
  const id = document.getElementById('edit_client_id').value;
  const formData = new FormData(ev.target);
  fetch('<?= BASE_URL ?>' + '/realtor/clients/update/' + id, { method:'POST', body: formData })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to update client'));
});

let deleteClientId = null;
function confirmDeleteRealtorClient(id){
  deleteClientId = id;
  new bootstrap.Modal(document.getElementById('deleteClientModal')).show();
}

document.getElementById('confirmDeleteClientBtn')?.addEventListener('click', function(){
  if(!deleteClientId) return;
  fetch('<?= BASE_URL ?>' + '/realtor/clients/delete/' + deleteClientId, { method:'POST' })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to delete client'));
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
