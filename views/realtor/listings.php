<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-building text-primary me-2"></i>Listings</h1>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addListingModal">
                <i class="bi bi-plus-circle me-1"></i>Add Listing
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">All Listings</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="realtorListingsTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($listings ?? []) as $x): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($x['title'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['listing_type'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['location'] ?? '')) ?></td>
                                <td>Ksh<?= number_format((float)($x['price'] ?? 0), 2) ?></td>
                                <td>
                                    <?php
                                        $st = strtolower((string)($x['status'] ?? 'active'));
                                        $badge = 'secondary';
                                        $label = $st ?: 'active';
                                        if ($st === 'active') { $badge = 'success'; $label = 'Available'; }
                                        elseif ($st === 'inactive') { $badge = 'secondary'; $label = 'Unavailable'; }
                                        elseif ($st === 'sold') { $badge = 'danger'; $label = 'Sold'; }
                                        elseif ($st === 'rented') { $badge = 'warning'; $label = 'Rented'; }
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($label) ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editRealtorListing(<?= (int)$x['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRealtorListing(<?= (int)$x['id'] ?>)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addListingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/realtor/listings/store">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add Listing</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="listing_type" class="form-select" required>
                    <option value="plot">Plot</option>
                    <option value="commercial_apartment">Commercial Apartment</option>
                    <option value="residential_apartment">Residential Apartment</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="sold">Sold</option>
                    <option value="rented">Rented</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editListingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editListingForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit Listing</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_listing_id">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" id="edit_title" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select id="edit_listing_type" name="listing_type" class="form-select" required>
                    <option value="plot">Plot</option>
                    <option value="commercial_apartment">Commercial Apartment</option>
                    <option value="residential_apartment">Residential Apartment</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" id="edit_location" name="location" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" id="edit_price" name="price" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select id="edit_status" name="status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="sold">Sold</option>
                    <option value="rented">Rented</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteListingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to delete this listing?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteListingBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
function editRealtorListing(id){
  fetch('<?= BASE_URL ?>' + '/realtor/listings/get/' + id)
    .then(r=>r.json()).then(resp=>{
      if(!resp.success){ alert('Listing not found'); return; }
      const e = resp.data;
      document.getElementById('edit_listing_id').value = e.id;
      document.getElementById('edit_title').value = e.title || '';
      document.getElementById('edit_listing_type').value = e.listing_type || 'plot';
      document.getElementById('edit_location').value = e.location || '';
      document.getElementById('edit_price').value = e.price || 0;
      document.getElementById('edit_status').value = e.status || 'active';
      document.getElementById('edit_description').value = e.description || '';
      new bootstrap.Modal(document.getElementById('editListingModal')).show();
    }).catch(()=>alert('Failed to load listing'));
}

document.getElementById('editListingForm')?.addEventListener('submit', function(ev){
  ev.preventDefault();
  const id = document.getElementById('edit_listing_id').value;
  const formData = new FormData(ev.target);
  fetch('<?= BASE_URL ?>' + '/realtor/listings/update/' + id, { method:'POST', body: formData })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to update listing'));
});

let deleteListingId = null;
function confirmDeleteRealtorListing(id){
  deleteListingId = id;
  new bootstrap.Modal(document.getElementById('deleteListingModal')).show();
}

document.getElementById('confirmDeleteListingBtn')?.addEventListener('click', function(){
  if(!deleteListingId) return;
  fetch('<?= BASE_URL ?>' + '/realtor/listings/delete/' + deleteListingId, { method:'POST' })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to delete listing'));
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
