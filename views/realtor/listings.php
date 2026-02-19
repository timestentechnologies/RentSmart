<?php
ob_start();
?>
<style>
  .listing-actions .btn{
    min-width:36px;
    min-height:36px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:50% !important;
    transition:none !important;
    box-shadow:none !important;
  }
  .listing-actions .btn:focus,
  .listing-actions .btn:active{
    box-shadow:none !important;
    outline:0 !important;
  }
</style>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-building text-primary me-2"></i>Listings</h1>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addListingModal">
                <i class="bi bi-plus-circle me-1"></i>Add Listing
            </button>
        </div>

<div class="modal" id="sellListingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/realtor/contracts/store" id="sellListingForm">
        <?= csrf_field() ?>
        <input type="hidden" name="realtor_listing_id" id="sell_listing_id">
        <div class="modal-header">
          <h5 class="modal-title">Sell Listing</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-2">
                <div class="small text-muted">Listing</div>
                <div class="fw-semibold" id="sell_listing_title"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Client</label>
                <select class="form-select" name="realtor_client_id" id="sell_client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach (($clients ?? []) as $c): ?>
                        <option value="<?= (int)($c['id'] ?? 0) ?>"><?= htmlspecialchars((string)($c['name'] ?? '')) ?> (<?= htmlspecialchars((string)($c['phone'] ?? '')) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Payment Terms</label>
                <select class="form-select" name="terms_type" id="sell_terms_type" required>
                    <option value="one_time">One Time</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Total Amount</label>
                <div class="input-group">
                    <span class="input-group-text">Ksh</span>
                    <input type="number" step="0.01" min="0" class="form-control" name="total_amount" id="sell_total_amount" required>
                </div>
            </div>

            <div id="sell_monthly_fields" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">Start Month</label>
                    <input type="month" class="form-control" name="start_month" id="sell_start_month">
                </div>
                <div class="mb-3">
                    <label class="form-label">Duration (Months)</label>
                    <input type="number" min="1" step="1" class="form-control" name="duration_months" id="sell_duration_months">
                </div>

                <div class="alert alert-info py-2" id="sell_monthly_summary" style="display:none;"></div>
                <div class="border rounded p-2" id="sell_monthly_breakdown" style="display:none; max-height: 220px; overflow:auto;"></div>
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

    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="card-title mb-0">All Listings</h5>
                <div class="realtor-filters">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="realtorListingsSearch" placeholder="Search listings...">
                    </div>
                    <select class="form-select" id="realtorListingsFilterStatus">
                        <option value="">All Status</option>
                        <option value="active">Available</option>
                        <option value="inactive">Unavailable</option>
                        <option value="sold">Sold</option>
                        <option value="rented">Rented</option>
                    </select>
                </div>
            </div>
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
                            <?php $rowStatus = strtolower((string)($x['status'] ?? 'active')); ?>
                            <tr data-status="<?= htmlspecialchars($rowStatus) ?>">
                                <td><?= htmlspecialchars((string)($x['title'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['listing_type'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($x['location'] ?? '')) ?></td>
                                <td>Ksh<?= number_format((float)($x['price'] ?? 0), 2) ?></td>
                                <td>
                                    <?php
                                        $st = $rowStatus;
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
                                    <div class="listing-actions">
                                      <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="openSellListingModal(<?= (int)$x['id'] ?>, '<?= htmlspecialchars((string)($x['title'] ?? ''), ENT_QUOTES) ?>')"><i class="bi bi-cash-coin"></i></button>
                                      <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editRealtorListing(<?= (int)$x['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRealtorListing(<?= (int)$x['id'] ?>)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  const table = document.getElementById('realtorListingsTable');
  const q = document.getElementById('realtorListingsSearch');
  const fStatus = document.getElementById('realtorListingsFilterStatus');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const norm = (v) => (String(v || '')).toLowerCase().trim();

  function apply(){
    const query = norm(q && q.value);
    const status = norm(fStatus && fStatus.value);

    const rows = tbody.querySelectorAll('tr');
    rows.forEach((tr) => {
      const rowText = norm(tr.innerText);
      const rowStatus = norm(tr.getAttribute('data-status'));

      let ok = true;
      if (query && !rowText.includes(query)) ok = false;
      if (status && rowStatus !== status) ok = false;

      tr.style.display = ok ? '' : 'none';
    });
  }

  [q, fStatus].forEach((el) => {
    if (!el) return;
    el.addEventListener('input', apply);
    el.addEventListener('change', apply);
  });
  apply();
})();
</script>

<div class="modal fade" id="addListingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/realtor/listings/store" enctype="multipart/form-data">
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
            <div class="mb-3">
                <label class="form-label">Images</label>
                <input type="file" name="listing_images[]" class="form-control" accept="image/*" multiple>
            </div>
            <div class="mb-3" id="add_listing_images_preview" style="display:none;"></div>
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
      <form method="POST" id="editListingForm" enctype="multipart/form-data">
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
            <div class="mb-3">
                <label class="form-label">Add Images</label>
                <input type="file" name="listing_images[]" class="form-control" accept="image/*" multiple>
            </div>
            <div class="mb-3" id="edit_listing_existing_images" style="display:none;"></div>
            <div class="mb-3" id="edit_listing_images_preview" style="display:none;"></div>
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

<div class="modal fade" id="listingUpdateSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Success</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Listing updated successfully.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="listingDeleteImageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Delete this image?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteListingImageBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
let __sellListingModal = null;
const __RS_BASE_URL = '<?= BASE_URL ?>';
let __pendingDeleteListingImage = null;

function getSellListingModal(){
  const el = document.getElementById('sellListingModal');
  if(!el) return null;
  if(el.parentElement && el.parentElement !== document.body){
    document.body.appendChild(el);
  }
  if(!el.__rsBackdropCleanupAttached){
    el.__rsBackdropCleanupAttached = true;
    el.addEventListener('show.bs.modal', function(){
      const backdrops = document.querySelectorAll('.modal-backdrop');
      if(backdrops.length > 1){
        backdrops.forEach((b, idx) => { if(idx > 0) b.remove(); });
      }
    });
  }
  if(!__sellListingModal){
    __sellListingModal = bootstrap.Modal.getOrCreateInstance(el);
  }
  return __sellListingModal;
}

function openSellListingModal(listingId, listingTitle){
  document.getElementById('sell_listing_id').value = listingId;
  document.getElementById('sell_listing_title').textContent = listingTitle || '';
  document.getElementById('sell_client_id').value = '';
  document.getElementById('sell_terms_type').value = 'one_time';
  document.getElementById('sell_total_amount').value = '';
  document.getElementById('sell_start_month').value = '';
  document.getElementById('sell_duration_months').value = '';
  toggleSellTermsFields();

  const m = getSellListingModal();
  if(!m) return;
  const el = document.getElementById('sellListingModal');
  if(el && el.classList.contains('show')) return;
  m.show();
}

function monthAdd(ym, n){
  if(!ym) return '';
  const parts = String(ym).split('-');
  if(parts.length < 2) return '';
  const y = parseInt(parts[0], 10);
  const m = parseInt(parts[1], 10);
  if(isNaN(y) || isNaN(m)) return '';
  const d = new Date(y, m - 1, 1);
  d.setMonth(d.getMonth() + n);
  const yy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  return `${yy}-${mm}`;
}

function toggleSellTermsFields(){
  const type = document.getElementById('sell_terms_type').value;
  const fields = document.getElementById('sell_monthly_fields');
  const start = document.getElementById('sell_start_month');
  const dur = document.getElementById('sell_duration_months');
  if(type === 'monthly'){
    fields.style.display = '';
    start.setAttribute('required','');
    dur.setAttribute('required','');
  } else {
    fields.style.display = 'none';
    start.removeAttribute('required');
    dur.removeAttribute('required');
  }
  updateSellMonthlyPreview();
}

function updateSellMonthlyPreview(){
  const type = document.getElementById('sell_terms_type').value;
  const summary = document.getElementById('sell_monthly_summary');
  const breakdown = document.getElementById('sell_monthly_breakdown');
  if(type !== 'monthly'){
    summary.style.display = 'none';
    breakdown.style.display = 'none';
    summary.innerHTML = '';
    breakdown.innerHTML = '';
    return;
  }

  const total = parseFloat(document.getElementById('sell_total_amount').value || '0');
  const duration = parseInt(document.getElementById('sell_duration_months').value || '0', 10);
  const startMonth = document.getElementById('sell_start_month').value;

  if(!total || !duration || !startMonth){
    summary.style.display = 'none';
    breakdown.style.display = 'none';
    summary.innerHTML = '';
    breakdown.innerHTML = '';
    return;
  }

  const monthly = Math.round((total / Math.max(1, duration)) * 100) / 100;
  summary.style.display = '';
  breakdown.style.display = '';
  summary.innerHTML = `<strong>Monthly:</strong> Ksh${monthly.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})} for ${duration} months`;

  let html = '<div class="small fw-semibold mb-2">Breakdown</div>';
  html += '<div class="d-grid gap-1">';
  for(let i=0;i<duration;i++){
    const ym = monthAdd(startMonth, i);
    html += `<div class="d-flex justify-content-between border rounded px-2 py-1"><div>${ym}</div><div>Ksh${monthly.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}</div></div>`;
  }
  html += '</div>';
  breakdown.innerHTML = html;
}

document.getElementById('sell_terms_type')?.addEventListener('change', toggleSellTermsFields);
document.getElementById('sell_total_amount')?.addEventListener('input', updateSellMonthlyPreview);
document.getElementById('sell_start_month')?.addEventListener('change', updateSellMonthlyPreview);
document.getElementById('sell_duration_months')?.addEventListener('input', updateSellMonthlyPreview);

function renderSelectedImagePreviews(inputEl, containerEl){
  if(!inputEl || !containerEl) return;
  const files = Array.from(inputEl.files || []).filter(f => (f.type || '').startsWith('image/'));
  if(files.length === 0){
    containerEl.style.display = 'none';
    containerEl.innerHTML = '';
    return;
  }
  containerEl.style.display = '';
  containerEl.innerHTML = '<div class="d-flex flex-wrap gap-2"></div>';
  const wrap = containerEl.querySelector('div');
  files.forEach(f => {
    const url = URL.createObjectURL(f);
    const img = document.createElement('img');
    img.src = url;
    img.style.width = '88px';
    img.style.height = '66px';
    img.style.objectFit = 'cover';
    img.className = 'rounded border';
    img.onload = () => URL.revokeObjectURL(url);
    wrap.appendChild(img);
  });
}

document.querySelector('#addListingModal input[name="listing_images[]"]')?.addEventListener('change', function(){
  renderSelectedImagePreviews(this, document.getElementById('add_listing_images_preview'));
});

function editRealtorListing(id){
  fetch(__RS_BASE_URL + '/realtor/listings/get/' + id)
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

      const existing = document.getElementById('edit_listing_existing_images');
      const previews = document.getElementById('edit_listing_images_preview');
      if (previews) { previews.style.display = 'none'; previews.innerHTML = ''; }

      const normalizeImgUrl = (u) => {
        u = String(u || '').trim();
        if(!u) return '';
        if(/^https?:\/\//i.test(u)) return u;
        if(u.startsWith('//')) return u;
        if(u.startsWith('/')) return '<?= BASE_URL ?>' + u;
        if(u.startsWith('public/')) return '<?= BASE_URL ?>' + '/' + u;
        if(u.startsWith('uploads/')) return '<?= BASE_URL ?>' + '/public/' + u;
        return '<?= BASE_URL ?>' + '/public/uploads/' + u.replace(/^\/+/, '');
      };

      const urls = (Array.isArray(e.images) ? e.images : []).map(normalizeImgUrl).filter(Boolean);
      const meta = Array.isArray(e.images_meta) ? e.images_meta : [];
      if(existing){
        if(urls.length){
          existing.style.display = '';
          existing.innerHTML = '<label class="form-label">Current Images</label><div class="d-flex flex-wrap gap-2"></div>';
          const wrap = existing.querySelector('div');
          urls.forEach((u, idx) => {
            const fileId = parseInt((meta[idx] && meta[idx].id) ? meta[idx].id : '0', 10) || null;
            const box = document.createElement('div');
            box.className = 'position-relative';
            const img = document.createElement('img');
            img.src = u;
            img.style.width = '88px';
            img.style.height = '66px';
            img.style.objectFit = 'cover';
            img.className = 'rounded border';
            box.appendChild(img);

            if(fileId){
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
              btn.style.padding = '2px 6px';
              btn.innerHTML = '<i class="bi bi-x"></i>';
              btn.setAttribute('data-file-id', String(fileId));
              btn.setAttribute('title', 'Delete image');
              btn.addEventListener('click', async function(ev){
                ev.preventDefault();
                const fid = parseInt(this.getAttribute('data-file-id') || '0', 10);
                if(!fid) return;
                __pendingDeleteListingImage = { fileId: fid, boxEl: box, wrapEl: wrap, existingEl: existing };
                const mEl = document.getElementById('listingDeleteImageModal');
                if(mEl){
                  bootstrap.Modal.getOrCreateInstance(mEl).show();
                }
              });
              box.appendChild(btn);
            }

            wrap.appendChild(box);
          });
        } else {
          existing.style.display = 'none';
          existing.innerHTML = '';
        }
      }

      const editFileInput = document.querySelector('#editListingModal input[name="listing_images[]"]');
      if(editFileInput){
        editFileInput.value = '';
        editFileInput.onchange = function(){
          renderSelectedImagePreviews(editFileInput, document.getElementById('edit_listing_images_preview'));
        };
      }
      new bootstrap.Modal(document.getElementById('editListingModal')).show();
    }).catch(()=>alert('Failed to load listing'));
}

// Auto-open edit modal when redirected from lead conversion flow
(function(){
  try {
    const params = new URLSearchParams(window.location.search || '');
    const id = parseInt(params.get('edit') || '0', 10);
    if(id > 0){
      editRealtorListing(id);
    }
  } catch(e) {}
})();

document.getElementById('editListingForm')?.addEventListener('submit', function(ev){
  ev.preventDefault();
  const id = document.getElementById('edit_listing_id').value;
  const formData = new FormData(ev.target);
  fetch(__RS_BASE_URL + '/realtor/listings/update/' + id, { method:'POST', body: formData })
    .then(r=>r.json()).then(resp=>{
      if(resp && resp.success){
        const editM = bootstrap.Modal.getInstance(document.getElementById('editListingModal'));
        if(editM) editM.hide();
        const okEl = document.getElementById('listingUpdateSuccessModal');
        if(okEl){
          const okM = bootstrap.Modal.getOrCreateInstance(okEl);
          okEl.addEventListener('hidden.bs.modal', function(){ location.reload(); }, { once: true });
          okM.show();
        } else {
          location.reload();
        }
      } else {
        alert((resp && resp.message) ? resp.message : 'Failed');
      }
    })
    .catch(()=>alert('Failed to update listing'));
});

let deleteListingId = null;
function confirmDeleteRealtorListing(id){
  deleteListingId = id;
  new bootstrap.Modal(document.getElementById('deleteListingModal')).show();
}

document.getElementById('confirmDeleteListingBtn')?.addEventListener('click', function(){
  if(!deleteListingId) return;
  fetch(__RS_BASE_URL + '/realtor/listings/delete/' + deleteListingId, { method:'POST' })
    .then(r=>r.json()).then(resp=>{ if(resp.success){ location.reload(); } else { alert(resp.message || 'Failed'); } })
    .catch(()=>alert('Failed to delete listing'));
});

document.getElementById('confirmDeleteListingImageBtn')?.addEventListener('click', async function(){
  const pending = __pendingDeleteListingImage;
  __pendingDeleteListingImage = null;
  const mEl = document.getElementById('listingDeleteImageModal');
  const m = mEl ? bootstrap.Modal.getInstance(mEl) : null;
  if(m) m.hide();
  if(!pending || !pending.fileId) return;
  try {
    const res = await fetch(__RS_BASE_URL + '/files/delete/' + pending.fileId, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const out = await res.json();
    if(!out || !out.success){
      alert((out && out.message) ? out.message : 'Failed to delete image');
      return;
    }
    if(pending.boxEl) pending.boxEl.remove();
    if(pending.wrapEl && pending.wrapEl.children.length === 0 && pending.existingEl){
      pending.existingEl.style.display = 'none';
      pending.existingEl.innerHTML = '';
    }
  } catch(_e){
    alert('Failed to delete image');
  }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
