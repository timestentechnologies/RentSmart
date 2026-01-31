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
                            <select id="upload_entity_type" name="entity_type" class="form-select" required>
                                <option value="">Select entity</option>
                                <option value="property">Property</option>
                                <option value="unit">Unit</option>
                                <option value="payment">Payment</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Entity</label>
                            <input type="hidden" id="upload_entity_id" name="entity_id" required>
                            <input type="text" id="upload_entity_search" class="form-control" placeholder="Type to search..." list="entitySuggestions" autocomplete="off">
                            <datalist id="entitySuggestions"></datalist>
                            <div class="form-text">Start typing to search and select the entity you want to attach files to.</div>
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
                    <div class="d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-funnel me-2"></i>Search Filters</span>
                        <div class="d-flex gap-2">
                            <button id="btnBulkDelete" type="button" class="btn btn-sm btn-outline-danger" disabled>
                                <i class="bi bi-trash me-1"></i>Bulk Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="fileFilters" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" id="q" class="form-control" placeholder="Name, filename, entity..."><!-- no name, sent via JS -->
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Entity</label>
                            <select id="filter_entity_type" class="form-select">
                                <option value="">All</option>
                                <option value="property">Property</option>
                                <option value="unit">Unit</option>
                                <option value="payment">Payment</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Category</label>
                            <select id="filter_file_type" class="form-select">
                                <option value="">All</option>
                                <option value="attachment">Attachment</option>
                                <option value="image">Image</option>
                                <option value="document">Document</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">From</label>
                            <input type="date" id="filter_date_from" class="form-control">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">To</label>
                            <input type="date" id="filter_date_to" class="form-control">
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

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="filesTable" class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:36px;"><input type="checkbox" id="chkAll"></th>
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
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="previewContainer" class="ratio ratio-16x9 bg-light d-flex align-items-center justify-content-center" style="min-height:360px;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
  </div>

<script>
(function(){
    function bytesToSize(bytes){
        if (!bytes) return '0 B';
        const sizes = ['B','KB','MB','GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed( (i===0)?0:2 ) + ' ' + sizes[i];
    }

    const tableEl = document.getElementById('filesTable');
    let dt;

    function loadTable(){
        if (dt) { dt.destroy(); tableEl.querySelector('tbody').innerHTML = ''; }
        dt = $(tableEl).DataTable({
            processing: true,
            serverSide: false,
            searching: false,
            order: [[1, 'desc']],
            ajax: {
                url: '<?= BASE_URL ?>/files/search',
                data: function(d){
                    d.q = document.getElementById('q').value.trim();
                    d.entity_type = document.getElementById('filter_entity_type').value;
                    d.file_type = document.getElementById('filter_file_type').value;
                    d.date_from = document.getElementById('filter_date_from').value;
                    d.date_to = document.getElementById('filter_date_to').value;
                },
                dataSrc: function(json){ return json.data || []; }
            },
            columns: [
                { data: null, orderable:false, render: function(row){
                    return '<input type="checkbox" name="file_select" value="'+row.id+'" data-id="'+row.id+'">';
                }},
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
                    const viewBtn = '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Open in new tab"><i class="bi bi-box-arrow-up-right"></i></a>';
                    const previewBtn = '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-preview-file" data-url="'+url+'" data-mime="'+(row.mime_type||'')+'" title="Preview"><i class="bi bi-eye"></i></button>';
                    const delBtn = '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-id="'+row.id+'" title="Delete"><i class="bi bi-trash"></i></button>';
                    return previewBtn + viewBtn + delBtn;
                }}
            ]
        });
    }

    function debounce(fn, delay){let t;return function(){clearTimeout(t);t=setTimeout(()=>fn.apply(this, arguments), delay)}}

    document.getElementById('applyFilters').addEventListener('click', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('clearFilters').addEventListener('click', function(){
        document.getElementById('q').value='';
        document.getElementById('filter_entity_type').value='';
        document.getElementById('filter_file_type').value='';
        document.getElementById('filter_date_from').value='';
        document.getElementById('filter_date_to').value='';
        dt ? dt.ajax.reload() : loadTable();
    });
    document.getElementById('q').addEventListener('keyup', debounce(function(){ dt ? dt.ajax.reload() : loadTable(); }, 400));
    document.getElementById('filter_entity_type').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('filter_file_type').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('filter_date_from').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('filter_date_to').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });

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

    // initial load
    loadTable();

    // Select all and bulk delete controls
    $('#filesTable').on('change', 'input[name="file_select"]', function(){
        const anyChecked = $('input[name="file_select"]:checked').length > 0;
        document.getElementById('btnBulkDelete').disabled = !anyChecked;
    });
    $('#chkAll').on('change', function(){
        const checked = this.checked;
        $('input[name="file_select"]').prop('checked', checked).trigger('change');
    });
    document.getElementById('btnBulkDelete').addEventListener('click', function(){
        const ids = $('input[name="file_select"]:checked').map(function(){ return this.getAttribute('data-id'); }).get();
        if (!ids.length) return;
        const proceed = ()=>{
            fetch('<?= BASE_URL ?>/files/bulk-delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ ids })
            }).then(r=>r.json()).then(res=>{
                if (res.success){ if (window.Swal) Swal.fire('Deleted', (res.deleted||0)+' deleted'+(res.failed? (', '+res.failed+' failed') : ''), 'success'); if (dt) dt.ajax.reload(); }
                else { if (window.Swal) Swal.fire('Error', res.message||'Bulk delete failed', 'error'); }
            }).catch(()=>{ if (window.Swal) Swal.fire('Error','Bulk delete failed','error'); });
        };
        if (window.Swal){
            Swal.fire({title:'Delete selected files?', text:'This cannot be undone.', icon:'warning', showCancelButton:true, confirmButtonText:'Delete', confirmButtonColor:'#d33'}).then(r=>{ if (r.isConfirmed) proceed(); });
        } else { if (confirm('Delete selected files?')) proceed(); }
    });

    // Preview handler
    $(document).on('click', '.btn-preview-file', function(){
        const url = this.getAttribute('data-url');
        const mime = (this.getAttribute('data-mime')||'').toLowerCase();
        const cont = document.getElementById('previewContainer');
        cont.innerHTML = '';
        if (mime.startsWith('image/')) {
            cont.innerHTML = '<img src="'+url+'" class="img-fluid" style="max-height:80vh;object-fit:contain;">';
        } else if (mime === 'application/pdf' || url.toLowerCase().endsWith('.pdf')) {
            cont.innerHTML = '<embed src="'+url+'" type="application/pdf" style="width:100%;height:80vh;" />';
        } else {
            cont.innerHTML = '<div class="text-center text-muted">Preview not available. <a href="'+url+'" target="_blank">Open in new tab</a></div>';
        }
        const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
        modal.show();
    });

    // Upload entity autocomplete
    const uploadEntityType = document.getElementById('upload_entity_type');
    const uploadEntitySearch = document.getElementById('upload_entity_search');
    const uploadEntityId = document.getElementById('upload_entity_id');
    let lastTerm = '';
    function fetchEntities(term){
        const type = uploadEntityType.value;
        if (!type || term.length < 2) { return; }
        const url = '<?= BASE_URL ?>/files/entities?entity_type='+encodeURIComponent(type)+'&term='+encodeURIComponent(term);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
            .then(r=>r.json())
            .then(data=>{
                const list = document.getElementById('entitySuggestions');
                list.innerHTML='';
                (data.results||[]).forEach(it=>{
                    const opt = document.createElement('option');
                    opt.value = it.text + ' [ID:'+it.id+']';
                    list.appendChild(opt);
                });
            }).catch(()=>{});
    }
    uploadEntitySearch.addEventListener('input', debounce(function(){
        const v = uploadEntitySearch.value.trim();
        if (v !== lastTerm) { lastTerm = v; fetchEntities(v); }
        const m = v.match(/\[ID:(\d+)\]$/);
        if (m) { uploadEntityId.value = m[1]; }
    }, 300));
    uploadEntityType.addEventListener('change', function(){
        uploadEntitySearch.value=''; uploadEntityId.value=''; document.getElementById('entitySuggestions').innerHTML='';
    });
})();
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
