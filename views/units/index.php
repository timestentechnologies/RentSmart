<?php
ob_start();
?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-building text-primary me-2"></i>Units Management
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/units/template">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/units/export/csv">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/units/export/xlsx">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/units/export/pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <form action="<?= BASE_URL ?>/units/import" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </form>
                    <div class="vr d-none d-md-block"></div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="showAddUnitModal()">
                        <i class="bi bi-plus-circle me-1"></i>Add Unit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="propertyFilter" class="form-label">Filter by Property</label>
                    <select class="form-select" id="propertyFilter">
                        <option value="">All Properties</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?= htmlspecialchars($property['name']) ?>">
                                <?= htmlspecialchars($property['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="statusFilter" class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="vacant">Vacant</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="searchInput" class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search units...">
                </div>
            </div>
        </div>
    </div>

    <!-- Units Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="unitsTable">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Unit Number</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Rent Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $unit): ?>
                            <tr>
                                <td><?= htmlspecialchars($unit['property_name']) ?></td>
                                <td><?= htmlspecialchars($unit['unit_number']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($unit['type'])) ?></td>
                                <td><?= $unit['size'] ? htmlspecialchars($unit['size']) . ' sq ft' : '-' ?></td>
                                <td>Ksh<?= number_format($unit['rent_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($unit['status']) ?>">
                                        <?= ucfirst($unit['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewUnit(<?= $unit['id'] ?>)" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUnit(<?= $unit['id'] ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUnit(<?= $unit['id'] ?>)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form onsubmit="return handleUnitSubmit(event)" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="property_id" class="form-label">Property</label>
                        <select class="form-select" id="property_id" name="property_id" required>
                            <option value="">Select Property</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= $property['id'] ?>">
                                    <?= htmlspecialchars($property['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="unit_number" class="form-label">Unit Number</label>
                        <input type="text" class="form-control" id="unit_number" name="unit_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="studio">Studio</option>
                            <option value="1bhk">1 BHK</option>
                            <option value="2bhk">2 BHK</option>
                            <option value="3bhk">3 BHK</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="size" class="form-label">Size (sq ft)</label>
                        <input type="number" class="form-control" id="size" name="size" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="rent_amount" class="form-label">Rent Amount</label>
                        <input type="number" class="form-control" id="rent_amount" name="rent_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    
                    <!-- Images and Documents Upload -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_images" class="form-label">Unit Images</label>
                                <input type="file" class="form-control" id="unit_images" name="unit_images[]" 
                                       multiple accept="image/*" onchange="previewImages(this, 'unit-image-preview')">
                                <div class="form-text">Upload images of the unit (JPG, PNG, GIF, WebP - Max 5MB each)</div>
                                <div id="unit-image-preview" class="mt-2 row g-2"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_documents" class="form-label">Unit Documents</label>
                                <input type="file" class="form-control" id="unit_documents" name="unit_documents[]" 
                                       multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" onchange="previewDocuments(this, 'unit-document-preview')">
                                <div class="form-text">Upload unit documents (PDF, DOC, XLS, TXT, CSV - Max 10MB each)</div>
                                <div id="unit-document-preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form onsubmit="return handleUnitEdit(event)" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit_unit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_unit_number" class="form-label">Unit Number</label>
                        <input type="text" class="form-control" id="edit_unit_number" name="unit_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type" class="form-label">Type</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="studio">Studio</option>
                            <option value="1bhk">1 BHK</option>
                            <option value="2bhk">2 BHK</option>
                            <option value="3bhk">3 BHK</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_size" class="form-label">Size (sq ft)</label>
                        <input type="number" class="form-control" id="edit_size" name="size" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="edit_rent_amount" class="form-label">Rent Amount</label>
                        <input type="number" class="form-control" id="edit_rent_amount" name="rent_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <!-- Existing Files Display -->
                    <div id="edit-existing-files-section" class="mb-4">
                        <h6>Current Files</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Current Images</label>
                                <div id="edit-existing-images" class="row g-2 mb-3">
                                    <!-- Existing images will be loaded here -->
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Current Documents</label>
                                <div id="edit-existing-documents" class="mb-3">
                                    <!-- Existing documents will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Files Upload -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_unit_images" class="form-label">Add New Images</label>
                                <input type="file" class="form-control" id="edit_unit_images" name="unit_images[]" 
                                       multiple accept="image/*" onchange="previewImages(this, 'edit-unit-image-preview')">
                                <div class="form-text">Upload additional images (JPG, PNG, GIF, WebP - Max 5MB each)</div>
                                <div id="edit-unit-image-preview" class="mt-2 row g-2"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_unit_documents" class="form-label">Add New Documents</label>
                                <input type="file" class="form-control" id="edit_unit_documents" name="unit_documents[]" 
                                       multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" onchange="previewDocuments(this, 'edit-unit-document-preview')">
                                <div class="form-text">Upload additional documents (PDF, DOC, XLS, TXT, CSV - Max 10MB each)</div>
                                <div id="edit-unit-document-preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Unit Confirmation Modal -->
<div class="modal fade" id="deleteUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this unit? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUnitBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Unit Success Modal -->
<div class="modal fade" id="deleteUnitSuccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unit Deleted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Unit was successfully deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="closeDeleteUnitSuccessBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- View Unit Modal -->
<div class="modal fade" id="viewUnitModal" tabindex="-1" aria-labelledby="viewUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUnitModalLabel">Unit Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Unit Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Unit Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Property:</strong></td>
                                        <td id="view_unit_property"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Unit Number:</strong></td>
                                        <td id="view_unit_number"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td id="view_unit_type"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Size:</strong></td>
                                        <td id="view_unit_size"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Rent Amount:</strong></td>
                                        <td id="view_unit_rent"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td><span id="view_unit_status" class="badge"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Current Tenant (if occupied) -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Current Tenant</h6>
                            </div>
                            <div class="card-body" id="view_unit_tenant_info">
                                <!-- Tenant info will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Unit Images & Documents -->
                    <div class="col-12 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Unit Images & Documents</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Images</h6>
                                        <div id="view-unit-images" class="row g-2 mb-3">
                                            <!-- Unit images will be loaded here -->
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Documents</h6>
                                        <div id="view-unit-documents">
                                            <!-- Unit documents will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editUnit(currentUnitId)">Edit Unit</button>
            </div>
        </div>
    </div>
</div>

<!-- Add jQuery first -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Then add DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

<script>
// VERSION: 2025-10-10-18:14 - Image path fix applied
// BASE_URL is already defined in app.js, no need to redeclare
// Test function to verify JavaScript is loading
console.log('Units page JavaScript loaded successfully - VERSION 2025-10-10-18:14');

// Wait for document ready
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#unitsTable').DataTable({
        order: [[0, 'asc'], [1, 'asc']],
        pageLength: 25,
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search units..."
        }
    });

    // Property filter
    $('#propertyFilter').on('change', function() {
        const value = $(this).val();
        table.column(0).search(value).draw();
    });

    // Status filter
    $('#statusFilter').on('change', function() {
        const value = $(this).val();
        table.column(5).search(value).draw();
    });

    // Custom search input
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Clear form when modal is hidden
    $('#editUnitModal').on('hidden.bs.modal', function() {
        const form = document.querySelector('#editUnitModal form');
        if (form) {
            form.reset();
        }
    });

    // Debug: Log when edit modal is shown
    $('#editUnitModal').on('shown.bs.modal', function() {
        console.log('Edit modal shown');
    });
});

function showAddUnitModal() {
    const modal = new bootstrap.Modal(document.getElementById('addUnitModal'));
    modal.show();
}

function handleUnitSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    // Disable submit button to prevent double submission
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    fetch(BASE_URL + '/units/store', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('addUnitModal'));
            modal.hide();
            showAlert('success', 'Unit added successfully');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Error adding unit');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', error.message || 'Error adding unit');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
    
    return false;
}

function showAlert(type, message, autoRemove = true) {
    // Remove existing alerts of the same type
    const existingAlerts = document.querySelectorAll(`.alert-${type}`);
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto remove after 5 seconds only if autoRemove is true
    if (autoRemove) {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    return alertDiv;
}

let currentUnitId = null;

// JavaScript function to get status badge class
function getStatusBadgeClass(status) {
    switch(status.toLowerCase()) {
        case 'occupied':
            return 'success';
        case 'vacant':
            return 'warning';
        case 'maintenance':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Simple viewUnit function without async/await
function viewUnit(unitId) {
    console.log('viewUnit called with ID:', unitId);
    currentUnitId = unitId;

    // Show the modal immediately
    const modal = document.getElementById('viewUnitModal');
    if (!modal) {
        console.error('viewUnitModal not found');
        return;
    }
    const viewModal = new bootstrap.Modal(modal);
    viewModal.show();

    // Clear previous content / show placeholders
    document.getElementById('view_unit_property').textContent = '';
    document.getElementById('view_unit_number').textContent = '';
    document.getElementById('view_unit_type').textContent = '';
    document.getElementById('view_unit_size').textContent = '';
    document.getElementById('view_unit_rent').textContent = '';
    const statusEl = document.getElementById('view_unit_status');
    statusEl.textContent = '';
    statusEl.className = 'badge';
    document.getElementById('view_unit_tenant_info').innerHTML = '<p class="text-muted">Loading tenant info...</p>';
    document.getElementById('view-unit-images').innerHTML = '';
    document.getElementById('view-unit-documents').innerHTML = '';

    // Fetch unit details
    fetch(`${BASE_URL}/units/get/${unitId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('Unit not found');
            }
            throw new Error('Failed to load unit details');
        }
        return response.json();
    })
    .then(data => {
        if (!data.success || !data.unit) {
            throw new Error(data.message || 'Failed to load unit details');
        }

        const unit = data.unit;
        document.getElementById('view_unit_property').textContent = unit.property_name || '';
        document.getElementById('view_unit_number').textContent = unit.unit_number || '';
        document.getElementById('view_unit_type').textContent = unit.type ? String(unit.type) : '';
        document.getElementById('view_unit_size').textContent = unit.size ? `${unit.size}` : '';
        document.getElementById('view_unit_rent').textContent = unit.rent_amount != null ? `Ksh${Number(unit.rent_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '';

        const badgeClass = `badge bg-${getStatusBadgeClass(unit.status || '')}`;
        statusEl.className = badgeClass;
        statusEl.textContent = unit.status ? String(unit.status).charAt(0).toUpperCase() + String(unit.status).slice(1) : '';

        // Load related resources
        loadUnitFilesForView(unitId);
        loadUnitTenantInfo(unitId);
    })
    .catch(error => {
        console.error('viewUnit error:', error);
        showAlert('danger', error.message || 'An error occurred while loading unit details');
    });
}

function editUnit(unitId) {
    console.log('=== EDIT UNIT FUNCTION CALLED - NEW VERSION ===');
    console.log('Edit unit called with ID:', unitId);
    
    // Show loading state and store the alert element
    const loadingAlert = showAlert('info', 'Loading unit details...', false);
    
    fetch(`${BASE_URL}/units/get/${unitId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('Unit not found');
            }
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success && data.unit) {
            const unit = data.unit;
            
            // Populate form fields
            document.getElementById('edit_unit_id').value = unit.id;
            document.getElementById('edit_unit_number').value = unit.unit_number || '';
            document.getElementById('edit_type').value = unit.type || 'studio';
            document.getElementById('edit_size').value = unit.size || '';
            document.getElementById('edit_rent_amount').value = unit.rent_amount || '';
            document.getElementById('edit_status').value = unit.status || 'vacant';

            // Remove the loading alert
            if (loadingAlert && loadingAlert.parentNode) {
                loadingAlert.remove();
            }

            // Show modal
            const editUnitModal = new bootstrap.Modal(document.getElementById('editUnitModal'));
            editUnitModal.show();
            
            console.log('About to load files for unit:', unit.id);
            
            // Load existing files for editing
            loadUnitFilesForEdit(unit.id);
            
            console.log('Modal should be shown now');
        } else {
            throw new Error(data.message || 'Failed to load unit details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Remove the loading alert if it exists
        if (loadingAlert && loadingAlert.parentNode) {
            loadingAlert.remove();
        }
        showAlert('danger', error.message || 'An error occurred while loading unit details', true);
    });
}

function handleUnitEdit(event) {
    event.preventDefault();
    const form = event.target;
    const unitId = document.getElementById('edit_unit_id').value;
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    
    console.log('Handle unit edit called');
    console.log('Unit ID:', unitId);
    console.log('Form data:', Object.fromEntries(new FormData(form)));
    
    // Disable submit button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    
    fetch(`${BASE_URL}/units/update/${unitId}`, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Update response status:', response.status);
        return response.json().then(data => {
            if (!response.ok) {
                throw new Error(data.message || `Network response was not ok: ${response.status}`);
            }
            return data;
        });
    })
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUnitModal'));
            modal.hide();
            showAlert('success', 'Unit updated successfully');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Error updating unit');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showAlert('danger', error.message || 'Error updating unit');
    })
    .finally(() => {
        // Re-enable submit button and restore original text
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });

    return false;
}

let unitIdToDelete = null;
function deleteUnit(unitId) {
    unitIdToDelete = unitId;
    new bootstrap.Modal(document.getElementById('deleteUnitModal')).show();
}

document.getElementById('confirmDeleteUnitBtn').onclick = function() {
    fetch(BASE_URL + '/units/delete/' + unitIdToDelete, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '6285d48569fc75eeca1da7dc6abc3138ac0ebef2ac4867db183b755ab0311cad'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const successModal = new bootstrap.Modal(document.getElementById('deleteUnitSuccessModal'));
            successModal.show();
            document.getElementById('closeDeleteUnitSuccessBtn').onclick = function() {
                document.querySelectorAll('.modal.show').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
                location.reload();
            };
        } else {
            throw new Error(data.message || 'Error deleting unit');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', error.message || 'An error occurred while deleting the unit');
    });
};

// File upload preview functions
function previewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 col-sm-4 col-6';
                    col.innerHTML = `
                        <div class="position-relative">
                            <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 100px; object-fit: cover;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                                    onclick="removeFilePreview(this, '${input.id}', ${index})" style="padding: 2px 6px;">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="small text-truncate mt-1">${file.name}</div>
                        </div>
                    `;
                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function previewDocuments(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'border rounded p-2 mb-2 d-flex justify-content-between align-items-center';
            fileItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    <div>
                        <div class="fw-medium">${file.name}</div>
                        <small class="text-muted">${formatFileSize(file.size)}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        onclick="removeFilePreview(this, '${input.id}', ${index})">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            preview.appendChild(fileItem);
        });
    }
}

function removeFilePreview(button, inputId, fileIndex) {
    // Remove the preview element
    button.closest('.col-md-3, .border').remove();
    
    // Note: We can't actually remove files from input[type="file"] due to security restrictions
    // The user will need to reselect files if they want to remove one
    showAlert('info', 'To remove files, please reselect your files without the unwanted ones.');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Load unit files for editing
function loadUnitFilesForEdit(unitId) {
    console.log('Loading files for unit:', unitId);
    
    fetch(`${BASE_URL}/units/${unitId}/files`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load unit files');
        }
        return response.json();
    })
    .then(data => {
        console.log('Unit files API response:', data);
        
        if (data.success) {
            console.log('Unit Images:', data.images);
            console.log('Unit Documents:', data.documents);
            displayUnitImages(data.images || [], 'edit-existing-images', true);
            displayUnitDocuments(data.documents || [], 'edit-existing-documents', true);
        } else {
            console.error('Unit API returned error:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading unit files for edit:', error);
    });
}

// Display unit images
function displayUnitImages(images, containerId, allowDelete = false) {
    console.log('displayUnitImages called:', {images, containerId, allowDelete});
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    
    if (images.length === 0) {
        container.innerHTML = '<p class="text-muted">No images uploaded</p>';
        return;
    }
    
    images.forEach((image, index) => {
        console.log(`Processing unit image ${index}:`, image);
        console.log(`Unit image original_name: "${image.original_name}"`);
        
        const col = document.createElement('div');
        col.className = 'col-md-4 col-sm-6 col-6';
        col.innerHTML = `
            <div class="position-relative">
                <img src="${image.url}" class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover;" 
                     onclick="openImageModal('${image.url}', '${image.original_name}')" style="cursor: pointer;">
                ${allowDelete ? `
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                            onclick="deleteUnitFile(${image.id}, 'image')" style="padding: 2px 6px;">
                        <i class="bi bi-x"></i>
                    </button>
                ` : ''}
                <div class="small text-truncate mt-1">${image.original_name}</div>
            </div>
        `;
        container.appendChild(col);
    });
}

// Display unit documents
function displayUnitDocuments(documents, containerId, allowDelete = false) {
    console.log('displayUnitDocuments called:', {documents, containerId, allowDelete});
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    
    if (documents.length === 0) {
        container.innerHTML = '<p class="text-muted">No documents uploaded</p>';
        return;
    }
    
    documents.forEach((doc, index) => {
        console.log(`Processing unit document ${index}:`, doc);
        console.log(`Unit document original_name: "${doc.original_name}"`);
        
        const fileItem = document.createElement('div');
        fileItem.className = 'border rounded p-2 mb-2 d-flex justify-content-between align-items-center';
        fileItem.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-file-earmark-text me-2"></i>
                <div>
                    <div class="fw-medium">
                        <a href="${doc.url}" target="_blank" class="text-decoration-none">${doc.original_name}</a>
                    </div>
                    <small class="text-muted">${formatFileSize(doc.file_size)}</small>
                </div>
            </div>
            ${allowDelete ? `
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        onclick="deleteUnitFile(${doc.id}, 'document')">
                    <i class="bi bi-trash"></i>
                </button>
            ` : ''}
        `;
        container.appendChild(fileItem);
    });
}

// Delete unit file
function deleteUnitFile(fileId, fileType) {
    if (!confirm('Are you sure you want to delete this file?')) {
        return;
    }
    
    fetch(`${BASE_URL}/files/delete/${fileId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'File deleted successfully');
            // Reload the files
            const unitId = document.getElementById('edit_unit_id').value;
            if (unitId) {
                loadUnitFilesForEdit(unitId);
            }
        } else {
            showAlert('danger', data.message || 'Failed to delete file');
        }
    })
    .catch(error => {
        console.error('Error deleting file:', error);
        showAlert('danger', 'Error deleting file');
    });
}

// Load unit files for viewing (without delete buttons)
function loadUnitFilesForView(unitId) {
    fetch(`${BASE_URL}/units/${unitId}/files`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load unit files');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            displayUnitImages(data.images || [], 'view-unit-images', false);
            displayUnitDocuments(data.documents || [], 'view-unit-documents', false);
        }
    })
    .catch(error => {
        console.error('Error loading unit files for view:', error);
    });
}

// Load tenant information for unit
function loadUnitTenantInfo(unitId) {
    fetch(`${BASE_URL}/units/${unitId}/tenant`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load tenant information');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.tenant) {
            const tenant = data.tenant;
            document.getElementById('view_unit_tenant_info').innerHTML = `
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>${tenant.first_name} ${tenant.last_name}</td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>${tenant.email}</td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td>${tenant.phone || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td><strong>Move-in Date:</strong></td>
                        <td>${tenant.move_in_date || 'N/A'}</td>
                    </tr>
                </table>
            `;
        } else {
            document.getElementById('view_unit_tenant_info').innerHTML = '<p class="text-muted">No tenant information available</p>';
        }
    })
    .catch(error => {
        console.error('Error loading tenant info:', error);
        document.getElementById('view_unit_tenant_info').innerHTML = '<p class="text-muted">Unable to load tenant information</p>';
    });
}

// Open image in modal (reuse from properties)
function openImageModal(imageUrl, imageName) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('imageViewModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageViewModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageViewModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalImage" src="" class="img-fluid" style="max-height: 70vh;">
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Update modal content
    document.getElementById('imageViewModalLabel').textContent = imageName;
    document.getElementById('modalImage').src = imageUrl;
    
    // Show modal
    const imageModal = new bootstrap.Modal(modal);
    imageModal.show();
}
</script>

<style>
/* Custom Styles */
.page-header {
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.table > thead > tr > th {
    background-color: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-bottom: none;
}

.table > tbody > tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.badge.bg-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
    color: #198754;
}

.badge.bg-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
    color: #ffc107;
}

.btn-group {
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
    border-radius: 0.5rem;
    overflow: hidden;
}

.btn-group .btn {
    border: none;
    padding: 0.5rem;
    line-height: 1;
}

.dataTables_filter {
    display: none;
}

.dataTables_length select {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 0.5rem;
    font-size: 0.875rem;
}

.page-link {
    border: none;
    padding: 0.5rem 1rem;
    color: var(--primary-color);
    border-radius: 0.5rem;
    margin: 0 0.25rem;
}

.page-item.active .page-link {
    background-color: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .btn-group {
        display: flex;
        width: 100%;
    }

    .btn-group .btn {
        flex: 1;
    }
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?> 