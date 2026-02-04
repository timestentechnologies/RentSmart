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

            <div class="card">
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
            order: [[0, 'desc']],
            ajax: {
                url: '<?= BASE_URL ?>/files/search',
                data: function(d){
                    d.q = document.getElementById('q').value.trim();
                    d.entity_type = document.getElementById('entity_type').value;
                    d.file_type = document.getElementById('file_type').value;
                    d.date_from = document.getElementById('date_from').value;
                    d.date_to = document.getElementById('date_to').value;
                },
                dataSrc: function(json){ return json.data || []; }
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
                    const shareBtn = '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-share-file" data-id="'+row.id+'" title="Share"><i class="bi bi-share"></i></button>';
                    const delBtn = '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-id="'+row.id+'" title="Delete"><i class="bi bi-trash"></i></button>';
                    return viewBtn + shareBtn + delBtn;
                }}
            ]
        });
    }

    function debounce(fn, delay){let t;return function(){clearTimeout(t);t=setTimeout(()=>fn.apply(this, arguments), delay)}}

    document.getElementById('applyFilters').addEventListener('click', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('clearFilters').addEventListener('click', function(){
        document.getElementById('q').value='';
        document.getElementById('entity_type').value='';
        document.getElementById('file_type').value='';
        document.getElementById('date_from').value='';
        document.getElementById('date_to').value='';
        dt ? dt.ajax.reload() : loadTable();
    });
    document.getElementById('q').addEventListener('keyup', debounce(function(){ dt ? dt.ajax.reload() : loadTable(); }, 400));
    document.getElementById('entity_type').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('file_type').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('date_from').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });
    document.getElementById('date_to').addEventListener('change', function(){ dt ? dt.ajax.reload() : loadTable(); });

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

    document.getElementById('shareCategory').addEventListener('change', function(){
        const cat = this.value;
        const recSel = document.getElementById('shareRecipient');
        recSel.innerHTML = '<option value="">Select recipient</option>';
        if (!cat || !shareRecipients[cat]) return;
        shareRecipients[cat].forEach(function(item){
            const name = item.name || (item.id+''), meta = (item.property||item.role)? (' — '+(item.property||item.role)) : '';
            const opt = document.createElement('option');
            opt.value = item.id; opt.textContent = name + meta;
            recSel.appendChild(opt);
        });
    });

    document.getElementById('shareForm').addEventListener('submit', async function(e){
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
})();
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
