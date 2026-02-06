<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-folder2-open text-primary me-2"></i>Files
                </h1>
                <p class="text-muted mb-0 mt-1">Upload and manage your files. Use the filters to search dynamically.</p>
            </div>
            <div class="btn-group" role="group" aria-label="View mode">
                <button type="button" class="btn btn-outline-secondary" id="viewTableBtn">
                    <i class="bi bi-table me-1"></i>Table
                </button>
                <button type="button" class="btn btn-outline-secondary" id="viewGridBtn">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Grid
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-light fw-semibold">
                    <i class="bi bi-upload me-2"></i>Upload Files
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/files/upload" method="POST" enctype="multipart/form-data" class="vstack gap-3">
                        <?= csrf_field() ?>

                        <div>
                            <label class="form-label">Entity Type</label>
                            <select name="entity_type" class="form-select" required>
                                <option value="">Select entity</option>
                                <option value="property">Property</option>
                                <option value="unit">Unit</option>
                                <option value="payment">Payment</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Entity ID</label>
                            <input type="number" name="entity_id" class="form-control" placeholder="Enter entity ID" required>
                            <div class="form-text">Enter the ID of the selected entity.</div>
                        </div>

                        <div>
                            <label class="form-label">File Category</label>
                            <select name="file_type" class="form-select">
                                <option value="attachment">Attachment (default)</option>
                                <option value="image">Image</option>
                                <option value="document">Document</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Select Files</label>
                            <input type="file" name="files[]" class="form-control" multiple required>
                            <div class="form-text">You can select multiple files (max 10MB each, images up to 5MB).</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cloud-arrow-up me-1"></i>Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-light fw-semibold">
                    <i class="bi bi-funnel me-2"></i>Search Filters
                </div>
                <div class="card-body">
                    <form id="fileFilters" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" id="q" class="form-control" placeholder="Name, filename, entity..."><!-- no name, sent via JS -->
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Entity</label>
                            <select id="entity_type" class="form-select">
                                <option value="">All</option>
                                <option value="property">Property</option>
                                <option value="unit">Unit</option>
                                <option value="payment">Payment</option>
                                <option value="expense">Expense</option>
                                <option value="esign">E‑Sign</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Category</label>
                            <select id="file_type" class="form-select">
                                <option value="">All</option>
                                <option value="attachment">Attachment</option>
                                <option value="image">Image</option>
                                <option value="document">Document</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">From</label>
                            <input type="date" id="date_from" class="form-control">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">To</label>
                            <input type="date" id="date_to" class="form-control">
                        </div>
                        <div class="col-12 col-md-3">
                            <button id="applyFilters" type="button" class="btn btn-outline-primary w-100">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                        </div>
                        <div class="col-12 col-md-3">
                            <button id="clearFilters" type="button" class="btn btn-light w-100">
                                <i class="bi bi-x-circle me-1"></i>Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card" id="tableViewCard">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="filesTable" class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Entity</th>
                                    <th>Entity ID</th>
                                    <th>Size</th>
                                    <th>Uploaded By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card d-none" id="gridViewCard">
                <div class="card-body">
                    <div class="row g-3" id="filesGrid"></div>
                    <div class="text-muted small mt-2 d-none" id="gridEmptyState">No files found.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
(function(){
    function bytesToSize(bytes){
        if (!bytes) return '0 B';
        const sizes = ['B','KB','MB','GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed( (i===0)?0:2 ) + ' ' + sizes[i];
    }

    const tableEl = document.getElementById('filesTable');
    let dt;
    let viewMode = (localStorage.getItem('files_view_mode') || 'table');
    const tableCard = document.getElementById('tableViewCard');
    const gridCard = document.getElementById('gridViewCard');
    const gridEl = document.getElementById('filesGrid');
    const gridEmptyEl = document.getElementById('gridEmptyState');

    function getFilters(){
        const qEl = document.getElementById('q');
        return {
            q: ((qEl && qEl.value) || '').trim(),
            entity_type: document.getElementById('entity_type') ? document.getElementById('entity_type').value : '',
            file_type: document.getElementById('file_type') ? document.getElementById('file_type').value : '',
            date_from: document.getElementById('date_from') ? document.getElementById('date_from').value : '',
            date_to: document.getElementById('date_to') ? document.getElementById('date_to').value : ''
        };
    }

    function setViewMode(mode){
        viewMode = (mode === 'grid') ? 'grid' : 'table';
        localStorage.setItem('files_view_mode', viewMode);
        const tableBtn = document.getElementById('viewTableBtn');
        const gridBtn = document.getElementById('viewGridBtn');
        if (tableBtn) tableBtn.classList.toggle('active', viewMode === 'table');
        if (gridBtn) gridBtn.classList.toggle('active', viewMode === 'grid');
        if (tableCard) tableCard.classList.toggle('d-none', viewMode !== 'table');
        if (gridCard) gridCard.classList.toggle('d-none', viewMode !== 'grid');
        reloadResults();
    }

    async function fetchFiles(){
        const filters = getFilters();
        const url = new URL('<?= BASE_URL ?>/files/search', window.location.origin);
        Object.keys(filters).forEach(k => { if (filters[k]) url.searchParams.set(k, filters[k]); });
        url.searchParams.set('_ts', Date.now().toString());
        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const txt = await res.text();
        let json;
        try { json = JSON.parse(txt); } catch (e) {
            console.error('Grid fetch: invalid JSON', txt);
            return [];
        }
        if (!json || json.error) {
            console.error('Grid fetch error:', json);
            return [];
        }
        return Array.isArray(json.data) ? json.data : [];
    }

    function renderGrid(rows){
        if (!gridEl) return;
        gridEl.innerHTML = '';
        const hasRows = Array.isArray(rows) && rows.length > 0;
        if (gridEmptyEl) gridEmptyEl.classList.toggle('d-none', hasRows);
        if (!hasRows) return;

        rows.forEach(function(row){
            const name = row.original_name || row.filename || '';
            const url = (row.url) ? row.url : ('<?= BASE_URL ?>/public/' + (row.upload_path || ''));
            const size = bytesToSize(parseInt(row.file_size||0));
            const created = row.created_at || '';
            const entity = row.entity_type || '';
            const entityId = (row.entity_id !== undefined && row.entity_id !== null) ? row.entity_id : '';
            const uploader = (row.uploader_name||'') + (row.uploader_email? (' ('+row.uploader_email+')') : '');

            const canShare = (row.can_share === undefined) ? true : !!parseInt(row.can_share);
            const canDelete = (row.can_delete === undefined) ? true : !!parseInt(row.can_delete);
            const isVirtual = (parseInt(row.id || 0) < 0);

            const shareBtn = (canShare && !isVirtual)
                ? '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-share-file" data-id="'+row.id+'" title="Share"><i class="bi bi-share"></i></button>'
                : '';
            const delBtn = (canDelete && !isVirtual)
                ? '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-id="'+row.id+'" title="Delete"><i class="bi bi-trash"></i></button>'
                : '';

            const badgeMap = {image:'primary',document:'info',attachment:'secondary'};
            const b = badgeMap[row.file_type] || 'secondary';
            const badge = '<span class="badge bg-'+b+' text-uppercase">'+ $('<div>').text(row.file_type||'').html() +'</span>';

            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-xl-4';
            col.innerHTML =
                '<div class="card h-100">'
                + '<div class="card-body">'
                +   '<div class="d-flex justify-content-between align-items-start gap-2">'
                +     '<div class="flex-grow-1">'
                +       '<div class="fw-semibold mb-1">'
                +         '<a href="'+url+'" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark me-1"></i>'+ $('<div>').text(name).html() +'</a>'
                +       '</div>'
                +       '<div class="text-muted small">'+ $('<div>').text(created).html() +'</div>'
                +     '</div>'
                +     '<div>'+badge+'</div>'
                +   '</div>'
                +   '<div class="mt-2 small">'
                +     '<div><span class="text-muted">Entity:</span> '+ $('<div>').text(entity).html() +' <span class="text-muted">#</span>'+ $('<div>').text(entityId+'').html() +'</div>'
                +     '<div><span class="text-muted">Size:</span> '+ $('<div>').text(size).html() +'</div>'
                +     '<div class="text-truncate"><span class="text-muted">By:</span> '+ $('<div>').text(uploader).html() +'</div>'
                +   '</div>'
                + '</div>'
                + '<div class="card-footer bg-transparent">'
                +   '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Open"><i class="bi bi-box-arrow-up-right"></i></a>'
                +   shareBtn
                +   delBtn
                + '</div>'
                + '</div>';
            gridEl.appendChild(col);
        });
    }

    async function reloadResults(){
        if (viewMode === 'grid') {
            try {
                const rows = await fetchFiles();
                renderGrid(rows);
            } catch (e) {
                console.error('Grid reload failed', e);
                renderGrid([]);
            }
            return;
        }
        if (dt) { dt.ajax.reload(); }
    }

    function loadTable(){
        if (dt) { dt.destroy(); tableEl.querySelector('tbody').innerHTML = ''; }
        dt = $(tableEl).DataTable({
            processing: true,
            serverSide: false,
            searching: false,
            order: [[0, 'desc']],
            ajax: {
                url: '<?= BASE_URL ?>/files/search',
                data: function(d){
                    const f = getFilters();
                    d.q = f.q;
                    d.entity_type = f.entity_type;
                    d.file_type = f.file_type;
                    d.date_from = f.date_from;
                    d.date_to = f.date_to;
                    d._ts = Date.now();
                },
                dataSrc: function(json){
                    try {
                        if (!json || typeof json !== 'object') {
                            console.error('Files search: invalid JSON payload', json);
                            return [];
                        }
                        if (json.error) {
                            console.error('Files search error:', json);
                            return [];
                        }
                        if (!Array.isArray(json.data)) {
                            console.error('Files search: expected {data: []}, got:', json);
                            return [];
                        }
                        return json.data;
                    } catch (e) {
                        console.error('Files search dataSrc error:', e, json);
                        return [];
                    }
                },
                error: function(xhr, status, err){
                    console.error('Files search AJAX failed:', status, err);
                    try { console.error('Response:', xhr && xhr.responseText); } catch(e) {}
                }
            },
            columns: [
                { data: 'created_at', render: function(v){ return v ? v : ''; } },
                { data: null, render: function(row){
                    const name = row.original_name || row.filename || '';
                    const url = (row.url) ? row.url : ('<?= BASE_URL ?>/public/' + (row.upload_path || ''));
                    return '<a href="'+url+'" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark me-1"></i>'+ $('<div>').text(name).html() +'</a>';
                }},
                { data: 'file_type', render: function(v){
                    const map={image:'primary',document:'info',attachment:'secondary'};
                    const badge = map[v] || 'secondary';
                    return '<span class="badge bg-'+badge+' text-uppercase">'+ $('<div>').text(v||'').html() +'</span>';
                }},
                { data: 'entity_type' },
                { data: 'entity_id' },
                { data: 'file_size', render: function(v){ return bytesToSize(parseInt(v||0)); } },
                { data: null, render: function(row){
                    return $('<div>').text((row.uploader_name||'') + (row.uploader_email? (' ('+row.uploader_email+')') : '')).html();
                }},
                { data: null, orderable: false, render: function(row){
                    const url = (row.url) ? row.url : ('<?= BASE_URL ?>/public/' + (row.upload_path || ''));
                    const viewBtn = '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Open"><i class="bi bi-box-arrow-up-right"></i></a>';
                    const canShare = (row.can_share === undefined) ? true : !!parseInt(row.can_share);
                    const canDelete = (row.can_delete === undefined) ? true : !!parseInt(row.can_delete);
                    const isVirtual = (parseInt(row.id || 0) < 0);
                    const shareBtn = (canShare && !isVirtual)
                        ? '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-share-file" data-id="'+row.id+'" title="Share"><i class="bi bi-share"></i></button>'
                        : '';
                    const delBtn = (canDelete && !isVirtual)
                        ? '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-id="'+row.id+'" title="Delete"><i class="bi bi-trash"></i></button>'
                        : '';
                    return viewBtn + shareBtn + delBtn;
                }}
            ]
        });
    }

    function debounce(fn, delay){let t;return function(){clearTimeout(t);t=setTimeout(()=>fn.apply(this, arguments), delay)}}

    const filtersForm = document.getElementById('fileFilters');
    if (filtersForm) filtersForm.addEventListener('submit', function(e){
        e.preventDefault();
        reloadResults();
        return false;
    });

    const applyBtn = document.getElementById('applyFilters');
    if (applyBtn) applyBtn.addEventListener('click', function(){ reloadResults(); });

    const clearBtn = document.getElementById('clearFilters');
    if (clearBtn) clearBtn.addEventListener('click', function(){
        const qEl = document.getElementById('q');
        const entityEl = document.getElementById('entity_type');
        const ftEl = document.getElementById('file_type');
        const dfEl = document.getElementById('date_from');
        const dtEl = document.getElementById('date_to');
        if (qEl) qEl.value='';
        if (entityEl) entityEl.value='';
        if (ftEl) ftEl.value='';
        if (dfEl) dfEl.value='';
        if (dtEl) dtEl.value='';
        reloadResults();
    });

    const qInput = document.getElementById('q');
    if (qInput) qInput.addEventListener('input', debounce(function(){ reloadResults(); }, 350));
    if (qInput) qInput.addEventListener('keydown', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            reloadResults();
        }
    });
    const entitySel = document.getElementById('entity_type');
    if (entitySel) entitySel.addEventListener('change', function(){ reloadResults(); });
    const ftSel = document.getElementById('file_type');
    if (ftSel) ftSel.addEventListener('change', function(){ reloadResults(); });
    const dfSel = document.getElementById('date_from');
    if (dfSel) dfSel.addEventListener('change', function(){ reloadResults(); });
    const dtSel = document.getElementById('date_to');
    if (dtSel) dtSel.addEventListener('change', function(){ reloadResults(); });

    const viewTableBtn = document.getElementById('viewTableBtn');
    if (viewTableBtn) viewTableBtn.addEventListener('click', function(){ setViewMode('table'); });
    const viewGridBtn = document.getElementById('viewGridBtn');
    if (viewGridBtn) viewGridBtn.addEventListener('click', function(){ setViewMode('grid'); });

    // Delete handler
    $(document).on('click', '.btn-delete-file', function(){
        const id = this.getAttribute('data-id');
        if (!id) return;
        if (window.Swal) {
            Swal.fire({
                title: 'Delete file?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d33'
            }).then(res => {
                if (res.isConfirmed) doDelete(id);
            });
        } else {
            if (confirm('Delete this file?')) doDelete(id);
        }
    });

    function doDelete(id){
        fetch('<?= BASE_URL ?>/files/delete/'+id, {method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (window.Swal) Swal.fire('Deleted','File deleted','success');
                    if (dt) dt.ajax.reload();
                } else {
                    if (window.Swal) Swal.fire('Error', res.message || 'Failed to delete','error');
                }
            })
            .catch(()=>{ if (window.Swal) Swal.fire('Error','Failed to delete','error'); });
    }

    // Share modal logic
    const shareRecipients = <?php echo json_encode($shareRecipients ?? ['tenants'=>[], 'caretakers'=>[], 'admins'=>[], 'users'=>[]]); ?>;
    let shareFileId = null;

    $(document).on('click', '.btn-share-file', function(){
        shareFileId = this.getAttribute('data-id');
        const modal = new bootstrap.Modal(document.getElementById('shareFileModal'));
        // Reset form
        document.getElementById('shareCategory').value = '';
        const recSel = document.getElementById('shareRecipient');
        recSel.innerHTML = '<option value="">Select recipient</option>';
        modal.show();
    });

    const shareCategoryEl = document.getElementById('shareCategory');
    if (shareCategoryEl) shareCategoryEl.addEventListener('change', function(){
        const cat = this.value;
        const recSel = document.getElementById('shareRecipient');
        if (!recSel) return;
        recSel.innerHTML = '<option value="">Select recipient</option>';
        if (!cat || !shareRecipients[cat]) return;
        shareRecipients[cat].forEach(function(item){
            const name = item.name || (item.id+''), meta = (item.property||item.role)? (' — '+(item.property||item.role)) : '';
            const opt = document.createElement('option');
            opt.value = item.id; opt.textContent = name + meta;
            recSel.appendChild(opt);
        });
    });

    const shareFormEl = document.getElementById('shareForm');
    if (shareFormEl) shareFormEl.addEventListener('submit', async function(e){
        e.preventDefault();
        const category = document.getElementById('shareCategory').value;
        const recipientId = document.getElementById('shareRecipient').value;
        if (!shareFileId || !category || !recipientId) return;
        try {
            const res = await fetch('<?= BASE_URL ?>/files/share', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                },
                body: new URLSearchParams({
                    file_id: shareFileId,
                    recipient_category: category,
                    recipient_id: recipientId
                })
            });
            const data = await res.json();
            if (data && data.success) {
                if (window.Swal) Swal.fire('Shared','File shared successfully','success');
                bootstrap.Modal.getInstance(document.getElementById('shareFileModal')).hide();
            } else {
                throw new Error((data && data.message) || 'Failed to share');
            }
        } catch (err) {
            if (window.Swal) Swal.fire('Error', err.message || 'Failed to share','error');
        }
    });

    // initial load
    loadTable();
    setViewMode(viewMode);
})();
});
</script>
<!-- Share File Modal -->
<div class="modal fade" id="shareFileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Share File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="shareForm" class="modal-body vstack gap-3">
        <div>
          <label class="form-label">Recipient Group</label>
          <select id="shareCategory" class="form-select" required>
            <option value="">Select group</option>
            <option value="tenants">Tenants</option>
            <option value="caretakers">Caretakers</option>
            <option value="admins">Admins</option>
            <?php $meRole = strtolower($_SESSION['user_role'] ?? ''); if ($meRole === 'admin' || $meRole === 'administrator'): ?>
            <option value="users">Users</option>
            <?php endif; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Recipient</label>
          <select id="shareRecipient" class="form-select" required>
            <option value="">Select recipient</option>
          </select>
        </div>
        <div class="modal-footer px-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Share</button>
        </div>
      </form>
    </div>
  </div>
 </div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
