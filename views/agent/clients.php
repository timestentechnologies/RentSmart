<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-people text-primary me-2"></i>Clients</h1>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="bi bi-plus-circle me-1"></i> Add Client
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
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Notes</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($clients ?? []) as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($c['property_names'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['phone'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['email'] ?? '')) ?></td>
                                <td><?= nl2br(htmlspecialchars((string)($c['notes'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editAgentClient(<?= (int)($c['id'] ?? 0) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1" onclick="deleteAgentClient(<?= (int)($c['id'] ?? 0) ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No clients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editClientForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" id="edit_client_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <div class="dropdown w-100">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="edit_client_property_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select properties
                                </button>
                                <div class="dropdown-menu w-100 p-2" aria-labelledby="edit_client_property_btn" style="max-height:240px; overflow:auto;">
                                    <div id="edit_client_property_list">
                                        <?php foreach (($properties ?? []) as $p): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="property_ids[]" value="<?= (int)($p['id'] ?? 0) ?>" id="edit_prop_<?= (int)($p['id'] ?? 0) ?>">
                                                <label class="form-check-label" for="edit_prop_<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">Select one or more properties.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" id="edit_client_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" id="edit_client_phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" id="edit_client_email" type="email">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_client_notes" rows="3"></textarea>
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

<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Delete this client? This will also delete all contracts for this client.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteClientBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/agent/clients/store">
                <div class="modal-header">
                    <h5 class="modal-title">Add Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <div class="d-flex gap-2">
                                <div class="dropdown w-100">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="agent_client_property_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        Select properties
                                    </button>
                                    <div class="dropdown-menu w-100 p-2" aria-labelledby="agent_client_property_btn" style="max-height:240px; overflow:auto;">
                                        <div id="agent_client_property_list">
                                            <?php foreach (($properties ?? []) as $p): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="property_ids[]" value="<?= (int)($p['id'] ?? 0) ?>" id="add_prop_<?= (int)($p['id'] ?? 0) ?>">
                                                    <label class="form-check-label" for="add_prop_<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary" id="agentClientAddPropertyBtn" title="Add Property" data-bs-toggle="modal" data-bs-target="#agentClientAddPropertyModal">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
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

<div class="modal fade" id="agentClientAddPropertyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="agentClientAddPropertyForm">
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
          <div class="alert alert-danger mt-3 d-none" id="agentClientAddPropertyError"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="agentClientAddPropertySubmit">Create Property</button>
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

  function csrfToken(){
    return (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  }

  const editModalEl = document.getElementById('editClientModal');
  function getEditModal(){
    if(!editModalEl) return null;
    if(!(window.bootstrap && window.bootstrap.Modal)) return null;
    return window.bootstrap.Modal.getOrCreateInstance(editModalEl);
  }

  function setDropdownLabel(btnEl, checkedNames){
    if(!btnEl) return;
    if(!checkedNames.length){
      btnEl.textContent = 'Select properties';
      return;
    }
    const label = checkedNames.join(', ');
    btnEl.textContent = label.length > 60 ? (label.slice(0, 60) + '…') : label;
  }

  function syncClientPropertyDropdown(prefix){
    const btn = document.getElementById(prefix + '_btn');
    const list = document.getElementById(prefix + '_list');
    if(!btn || !list) return;
    const checked = Array.from(list.querySelectorAll('input[type="checkbox"]:checked'));
    const names = checked.map(i => (i.closest('.form-check')?.querySelector('label')?.textContent || '').trim()).filter(Boolean);
    setDropdownLabel(btn, names);
  }

  document.getElementById('agent_client_property_list')?.addEventListener('change', ()=>syncClientPropertyDropdown('agent_client_property'));
  document.getElementById('edit_client_property_list')?.addEventListener('change', ()=>syncClientPropertyDropdown('edit_client_property'));

  window.editAgentClient = function(id){
    fetch('<?= BASE_URL ?>' + '/agent/clients/get/' + id)
      .then(r=>r.json()).then(resp=>{
        if(!resp.success){ alert(resp.message || 'Client not found'); return; }
        const c = resp.data || {};
        document.getElementById('edit_client_id').value = String(c.id || id);

        const editList = document.getElementById('edit_client_property_list');
        if(editList){
          const avail = Array.isArray(c.available_properties) ? c.available_properties : [];
          editList.innerHTML = '';
          avail.forEach(p => {
            const pid = String(p.id || '');
            if(!pid) return;
            const wrap = document.createElement('div');
            wrap.className = 'form-check';
            const input = document.createElement('input');
            input.className = 'form-check-input';
            input.type = 'checkbox';
            input.name = 'property_ids[]';
            input.value = pid;
            input.id = 'edit_prop_' + pid;
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.setAttribute('for', input.id);
            label.textContent = String(p.name || pid);
            wrap.appendChild(input);
            wrap.appendChild(label);
            editList.appendChild(wrap);
          });

          const selectedIds = Array.isArray(c.property_ids) ? c.property_ids.map(v => String(v)) : [];
          selectedIds.forEach(pid => {
            const existing = editList.querySelector('input[type="checkbox"][value="' + pid.replace(/"/g,'') + '"]');
            if(existing){
              existing.checked = true;
            } else {
              const wrap = document.createElement('div');
              wrap.className = 'form-check';
              const input = document.createElement('input');
              input.className = 'form-check-input';
              input.type = 'checkbox';
              input.name = 'property_ids[]';
              input.value = pid;
              input.id = 'edit_prop_' + pid;
              input.checked = true;
              const label = document.createElement('label');
              label.className = 'form-check-label';
              label.setAttribute('for', input.id);
              label.textContent = pid;
              wrap.appendChild(input);
              wrap.appendChild(label);
              editList.appendChild(wrap);
            }
          });
          syncClientPropertyDropdown('edit_client_property');
        }
        document.getElementById('edit_client_name').value = c.name || '';
        document.getElementById('edit_client_phone').value = c.phone || '';
        document.getElementById('edit_client_email').value = c.email || '';
        document.getElementById('edit_client_notes').value = c.notes || '';
        const editModal = getEditModal();
        if(!editModal){
          alert('Edit modal is not ready. Please refresh and try again.');
          return;
        }
        editModal.show();
      })
      .catch(()=>alert('Failed to load client'));
  }

  const deleteModalEl = document.getElementById('deleteClientModal');
  const confirmDeleteBtn = document.getElementById('confirmDeleteClientBtn');
  let pendingDeleteClientId = null;

  function getDeleteModal(){
    if(!deleteModalEl) return null;
    if(!(window.bootstrap && window.bootstrap.Modal)) return null;
    return window.bootstrap.Modal.getOrCreateInstance(deleteModalEl);
  }

  window.deleteAgentClient = function(id){
    pendingDeleteClientId = id;
    const m = getDeleteModal();
    if(!m){
      alert('Delete modal is not ready. Please refresh and try again.');
      return;
    }
    m.show();
  }

  confirmDeleteBtn?.addEventListener('click', async function(){
    const id = pendingDeleteClientId;
    if(!id) return;
    const fd = new FormData();
    fd.set('csrf_token', csrfToken());
    try{
      confirmDeleteBtn.disabled = true;
      const res = await fetch('<?= BASE_URL ?>' + '/agent/clients/delete/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){ alert(data.message || 'Failed to delete client'); return; }
      location.reload();
    }catch(e){
      alert('Failed to delete client');
    }finally{
      confirmDeleteBtn.disabled = false;
    }
  });

  document.getElementById('editClientForm')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = document.getElementById('edit_client_id').value;
    if(!id) return;
    const fd = new FormData(e.target);
    fd.set('csrf_token', csrfToken() || fd.get('csrf_token') || '');
    try{
      const res = await fetch('<?= BASE_URL ?>' + '/agent/clients/update/' + id, { method:'POST', body: fd });
      const data = await res.json();
      if(!data.success){ alert(data.message || 'Failed to update'); return; }
      location.reload();
    }catch(err){
      alert('Failed to update');
    }
  });

  const propertyList = document.getElementById('agent_client_property_list');
  const editPropertyList = document.getElementById('edit_client_property_list');
  const addPropBtn = document.getElementById('agentClientAddPropertyBtn');
  const addPropModalEl = document.getElementById('agentClientAddPropertyModal');
  const addPropForm = document.getElementById('agentClientAddPropertyForm');
  const addPropErr = document.getElementById('agentClientAddPropertyError');
  const addPropSubmit = document.getElementById('agentClientAddPropertySubmit');
  function getAddPropModal(){
    if(!addPropModalEl) return null;
    if(!(window.bootstrap && window.bootstrap.Modal)) return null;
    return window.bootstrap.Modal.getOrCreateInstance(addPropModalEl);
  }

  function upsertPropertyCheckbox(listEl, id, label, prefix){
    if(!listEl) return;
    const val = String(id);
    const existing = listEl.querySelector('input[type="checkbox"][value="' + val.replace(/"/g,'') + '"]');
    if(existing){
      const lbl = existing.closest('.form-check')?.querySelector('label');
      if(lbl) lbl.textContent = label;
      return;
    }
    const wrap = document.createElement('div');
    wrap.className = 'form-check';
    const input = document.createElement('input');
    input.className = 'form-check-input';
    input.type = 'checkbox';
    input.name = 'property_ids[]';
    input.value = val;
    input.id = prefix + '_' + val;
    const lbl = document.createElement('label');
    lbl.className = 'form-check-label';
    lbl.setAttribute('for', input.id);
    lbl.textContent = label;
    wrap.appendChild(input);
    wrap.appendChild(lbl);
    listEl.appendChild(wrap);
  }

  if(addPropBtn){
    addPropBtn.addEventListener('click', ()=>{
      if(addPropErr){ addPropErr.classList.add('d-none'); addPropErr.textContent = ''; }
      addPropForm?.reset();
    });
  }

  addPropForm?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    if(!propertyList) return;
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
      const label = String(fd.get('name') || 'New Property');
      upsertPropertyCheckbox(propertyList, data.property_id, label, 'add_prop');
      upsertPropertyCheckbox(editPropertyList, data.property_id, label, 'edit_prop');
      const newVal = String(data.property_id);
      propertyList.querySelector('input[type="checkbox"][value="' + newVal.replace(/"/g,'') + '"]')?.click();
      syncClientPropertyDropdown('agent_client_property');
      syncClientPropertyDropdown('edit_client_property');
      const addPropModal = getAddPropModal();
      if (addPropModal) addPropModal.hide();
    } catch (err){
      if(addPropErr){
        addPropErr.textContent = String(err && err.message ? err.message : err);
        addPropErr.classList.remove('d-none');
      }
    } finally {
      if(addPropSubmit) addPropSubmit.disabled = false;
    }
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
