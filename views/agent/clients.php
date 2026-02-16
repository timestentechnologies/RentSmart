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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($clients ?? []) as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($c['property_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['phone'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['email'] ?? '')) ?></td>
                                <td><?= nl2br(htmlspecialchars((string)($c['notes'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No clients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                                <select class="form-select" name="property_id" id="agent_client_property" required>
                                    <option value="">Select property</option>
                                    <?php foreach (($properties ?? []) as $p): ?>
                                        <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
  function csrfToken(){
    return (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  }

  const propertySel = document.getElementById('agent_client_property');
  const addPropBtn = document.getElementById('agentClientAddPropertyBtn');
  const addPropModalEl = document.getElementById('agentClientAddPropertyModal');
  const addPropForm = document.getElementById('agentClientAddPropertyForm');
  const addPropErr = document.getElementById('agentClientAddPropertyError');
  const addPropSubmit = document.getElementById('agentClientAddPropertySubmit');
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
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
