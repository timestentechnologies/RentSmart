<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-tools text-primary me-2"></i>Maintenance Requests
                </h1>
              
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4 mt-4">
        <div class="col-12 col-md-3">
            <div class="stat-card maintenance-total">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Requests</h6>
                        <h2 class="mt-3 mb-2"><?= $statistics['total_requests'] ?? 0 ?></h2>
                        <p class="mb-0 text-muted">All maintenance requests</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-tools fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="stat-card maintenance-pending">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Pending</h6>
                        <h2 class="mt-3 mb-2"><?= $statistics['pending_requests'] ?? 0 ?></h2>
                        <p class="mb-0 text-muted">Awaiting action</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-clock fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="stat-card maintenance-progress">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">In Progress</h6>
                        <h2 class="mt-3 mb-2"><?= $statistics['in_progress_requests'] ?? 0 ?></h2>
                        <p class="mb-0 text-muted">Currently being worked on</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-gear fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="stat-card maintenance-completed">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Completed</h6>
                        <h2 class="mt-3 mb-2"><?= $statistics['completed_requests'] ?? 0 ?></h2>
                        <p class="mb-0 text-muted">Successfully resolved</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3" method="GET" action="<?= BASE_URL ?>/maintenance">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= (($filters['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= (($filters['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= (($filters['status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= (($filters['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priorityFilter" class="form-label">Priority</label>
                    <select class="form-select" id="priorityFilter" name="priority">
                        <option value="">All Priorities</option>
                        <option value="urgent" <?= (($filters['priority'] ?? '') === 'urgent') ? 'selected' : '' ?>>Urgent</option>
                        <option value="high" <?= (($filters['priority'] ?? '') === 'high') ? 'selected' : '' ?>>High</option>
                        <option value="medium" <?= (($filters['priority'] ?? '') === 'medium') ? 'selected' : '' ?>>Medium</option>
                        <option value="low" <?= (($filters['priority'] ?? '') === 'low') ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select class="form-select" id="categoryFilter" name="category">
                        <option value="">All Categories</option>
                        <option value="plumbing" <?= (($filters['category'] ?? '') === 'plumbing') ? 'selected' : '' ?>>Plumbing</option>
                        <option value="electrical" <?= (($filters['category'] ?? '') === 'electrical') ? 'selected' : '' ?>>Electrical</option>
                        <option value="hvac" <?= (($filters['category'] ?? '') === 'hvac') ? 'selected' : '' ?>>HVAC</option>
                        <option value="appliance" <?= (($filters['category'] ?? '') === 'appliance') ? 'selected' : '' ?>>Appliance</option>
                        <option value="structural" <?= (($filters['category'] ?? '') === 'structural') ? 'selected' : '' ?>>Structural</option>
                        <option value="pest_control" <?= (($filters['category'] ?? '') === 'pest_control') ? 'selected' : '' ?>>Pest Control</option>
                        <option value="cleaning" <?= (($filters['category'] ?? '') === 'cleaning') ? 'selected' : '' ?>>Cleaning</option>
                        <option value="other" <?= (($filters['category'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="reset" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-2"></i>Clear Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Requests Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">TENANT</th>
                        <th class="text-muted">PROPERTY</th>
                        <th class="text-muted">UNIT</th>
                        <th class="text-muted">TITLE</th>
                        <th class="text-muted">CATEGORY</th>
                        <th class="text-muted">PRIORITY</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">REQUESTED DATE</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-tools display-4 text-muted mb-3 d-block"></i>
                                <h5>No maintenance requests found</h5>
                                <p class="text-muted">Maintenance requests from tenants will appear here</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-2">
                                            <?= strtoupper(substr($request['tenant_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($request['tenant_name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($request['tenant_email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($request['property_name'] ?? 'N/A') ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        <?= htmlspecialchars($request['unit_number'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php
                                            $categoryIcons = [
                                                'plumbing' => 'bi-droplet',
                                                'electrical' => 'bi-lightning',
                                                'hvac' => 'bi-thermometer',
                                                'appliance' => 'bi-gear',
                                                'structural' => 'bi-building',
                                                'pest_control' => 'bi-bug',
                                                'cleaning' => 'bi-broom',
                                                'other' => 'bi-tools'
                                            ];
                                            $icon = $categoryIcons[$request['category']] ?? 'bi-tools';
                                            ?>
                                            <i class="bi <?= $icon ?> text-primary"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($request['title']) ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= ucwords(str_replace('_', ' ', $request['category'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $priorityColors = [
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        'low' => 'secondary'
                                    ];
                                    $priorityColor = $priorityColors[$request['priority']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $priorityColor ?>">
                                        <?= ucfirst($request['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'in_progress' => 'primary',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$request['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusColor ?>">
                                        <?= ucwords(str_replace('_', ' ', $request['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($request['requested_date'])) ?>
                                    <br>
                                    <small class="text-muted"><?= date('g:i A', strtotime($request['requested_date'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="showMaintenanceRequestDetails(<?= $request['id'] ?>)" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-maintenance" data-id="<?= $request['id'] ?>" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMaintenanceRequest(<?= $request['id'] ?>)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Maintenance Request Modal -->
<div class="modal fade" id="editMaintenanceModal" tabindex="-1" aria-labelledby="editMaintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editMaintenanceForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMaintenanceModalLabel">Update Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select id="edit_status" name="status" class="form-select" required>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_assigned_to" class="form-label">Assigned To</label>
                                <input type="text" id="edit_assigned_to" name="assigned_to" class="form-control" placeholder="Contractor/Technician name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_scheduled_date" class="form-label">Scheduled Date</label>
                                <input type="datetime-local" id="edit_scheduled_date" name="scheduled_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_estimated_cost" class="form-label">Estimated Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">Ksh</span>
                                    <input type="number" id="edit_estimated_cost" name="estimated_cost" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_actual_cost" class="form-label">Actual Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">Ksh</span>
                                    <input type="number" id="edit_actual_cost" name="actual_cost" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_source_of_funds" class="form-label">Source of Funds</label>
                                <select id="edit_source_of_funds" name="source_of_funds" class="form-select">
                                    <option value="">Select Source</option>
                                    <option value="rent_balance">Rent Balance (deduct from payments)</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="owner_funds">Owner Funds</option>
                                    <option value="other">Other</option>
                                </select>
                                <div class="form-text">If Rent Balance is selected and a tenant is linked, a negative rent adjustment will be recorded.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_expense_payment_method" class="form-label">Expense Payment Method</label>
                                <select id="edit_expense_payment_method" name="expense_payment_method" class="form-select">
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="card">Card</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_charge_to_tenant" name="charge_to_tenant" value="1">
                                <label class="form-check-label" for="edit_charge_to_tenant">
                                    Charge this cost to tenant (adds to their balance)
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="edit_notes" class="form-label">Notes</label>
                                <textarea id="edit_notes" name="notes" class="form-control" rows="3" placeholder="Add notes about the maintenance work..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Maintenance Request Confirmation Modal -->
<div class="modal fade" id="deleteMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this maintenance request? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteMaintenanceBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.25rem;
}

.maintenance-total::before {
    background: linear-gradient(45deg, var(--primary-color), #0a58ca);
}

.maintenance-pending::before {
    background: linear-gradient(45deg, var(--warning-color), #e6a800);
}

.maintenance-progress::before {
    background: linear-gradient(45deg, var(--info-color), #0dcaf0);
}

.maintenance-completed::before {
    background: linear-gradient(45deg, var(--success-color), #28a745);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter functionality
    const filterForm = document.getElementById('filterForm');
    const statusFilter = document.getElementById('statusFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const tableRows = document.querySelectorAll('tbody tr');

    function submitFilters() {
        const url = new URL(window.location.href);
        const status = statusFilter.value;
        const priority = priorityFilter.value;
        const category = categoryFilter.value;
        if (status) { url.searchParams.set('status', status); } else { url.searchParams.delete('status'); }
        if (priority) { url.searchParams.set('priority', priority); } else { url.searchParams.delete('priority'); }
        if (category) { url.searchParams.set('category', category); } else { url.searchParams.delete('category'); }
        window.location.href = url.toString();
    }

    function applyFilters() {
        const selectedStatus = statusFilter.value.toLowerCase();
        const selectedPriority = priorityFilter.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const statusBadge = row.querySelector('td:nth-child(7) .badge');
            const priorityBadge = row.querySelector('td:nth-child(6) .badge');
            const categoryBadge = row.querySelector('td:nth-child(5) .badge');

            const statusCell = statusBadge ? statusBadge.textContent.toLowerCase().trim() : '';
            const priorityCell = priorityBadge ? priorityBadge.textContent.toLowerCase().trim() : '';
            const categoryCell = categoryBadge ? categoryBadge.textContent.toLowerCase().trim() : '';

            let showRow = true;

            // Status filter
            if (selectedStatus && statusCell !== selectedStatus) {
                showRow = false;
            }

            // Priority filter
            if (selectedPriority && priorityCell !== selectedPriority) {
                showRow = false;
            }

            // Category filter
            if (selectedCategory && categoryCell !== selectedCategory) {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    // Add event listeners to filters
    statusFilter.addEventListener('change', submitFilters);
    priorityFilter.addEventListener('change', submitFilters);
    categoryFilter.addEventListener('change', submitFilters);

    // Reset filters
    filterForm.addEventListener('reset', function() {
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('status');
            url.searchParams.delete('priority');
            url.searchParams.delete('category');
            window.location.href = url.toString();
        }, 0);
    });

    // Edit Maintenance Request Handling
    document.querySelectorAll('.btn-edit-maintenance').forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            fetch(`<?= BASE_URL ?>/maintenance/get/${requestId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.request.id;
                        document.getElementById('edit_status').value = data.request.status;
                        document.getElementById('edit_assigned_to').value = data.request.assigned_to || '';
                        document.getElementById('edit_scheduled_date').value = data.request.scheduled_date ? data.request.scheduled_date.replace(' ', 'T') : '';
                        document.getElementById('edit_estimated_cost').value = data.request.estimated_cost || '';
                        document.getElementById('edit_actual_cost').value = data.request.actual_cost || '';
                        document.getElementById('edit_notes').value = data.request.notes || '';
                        document.getElementById('editMaintenanceForm').dataset.requestId = data.request.id;
                        var modal = new bootstrap.Modal(document.getElementById('editMaintenanceModal'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }
                });
        });
    });

    // Edit Maintenance Form Handling
    const editMaintenanceForm = document.getElementById('editMaintenanceForm');
    if (editMaintenanceForm) {
        editMaintenanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const requestId = editMaintenanceForm.dataset.requestId;
            fetch(`<?= BASE_URL ?>/maintenance/update-status`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editMaintenanceModal')).hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Updated!',
                        text: data.message || 'Maintenance request updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error updating maintenance request.'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating maintenance request'
                });
            });
        });
    }
});

let maintenanceRequestIdToDelete = null;
function deleteMaintenanceRequest(id) {
    maintenanceRequestIdToDelete = id;
    new bootstrap.Modal(document.getElementById('deleteMaintenanceModal')).show();
}

document.getElementById('confirmDeleteMaintenanceBtn').onclick = function() {
    fetch(`<?= BASE_URL ?>/maintenance/delete/${maintenanceRequestIdToDelete}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from the table
            const row = document.querySelector(`button[onclick="deleteMaintenanceRequest(${maintenanceRequestIdToDelete})"]`).closest('tr');
            if (row) row.parentNode.removeChild(row);
            bootstrap.Modal.getInstance(document.getElementById('deleteMaintenanceModal')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Maintenance request deleted successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error deleting maintenance request.'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error deleting maintenance request.'
        });
    });
};

// Function to show maintenance request details
function showMaintenanceRequestDetails(requestId) {
    console.log('Loading maintenance request details for ID:', requestId);
    
    // Show loading state
    const modal = new bootstrap.Modal(document.getElementById('maintenanceRequestDetailsModal'));
    modal.show();
    
    // Reset all fields
    document.getElementById('detailTitle').textContent = '-';
    document.getElementById('detailCategory').textContent = '-';
    document.getElementById('detailPriority').textContent = '-';
    document.getElementById('detailStatus').textContent = '-';
    document.getElementById('detailRequestedDate').textContent = '-';
    document.getElementById('detailScheduledDate').textContent = '-';
    document.getElementById('detailCompletedDate').textContent = '-';
    document.getElementById('detailProperty').textContent = '-';
    document.getElementById('detailTenant').textContent = '-';
    document.getElementById('detailUnit').textContent = '-';
    document.getElementById('detailAssignedTo').textContent = '-';
    document.getElementById('detailEstimatedCost').textContent = '-';
    document.getElementById('detailActualCost').textContent = '-';
    document.getElementById('detailDescription').textContent = '-';
    document.getElementById('detailNotes').textContent = '-';
    document.getElementById('notesCard').style.display = 'none';
    
    // Fetch request details
    fetch(`<?= BASE_URL ?>/maintenance/get/${requestId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Request details loaded:', data);
        
        if (data.success && data.request) {
            const request = data.request;
            
            // Populate basic information
            document.getElementById('detailTitle').textContent = request.title || '-';
            document.getElementById('detailDescription').textContent = request.description || '-';
            
            // Category with icon
            const categoryIcons = {
                'plumbing': 'bi-droplet',
                'electrical': 'bi-lightning',
                'hvac': 'bi-thermometer',
                'appliance': 'bi-gear',
                'structural': 'bi-building',
                'pest_control': 'bi-bug',
                'cleaning': 'bi-broom',
                'other': 'bi-tools'
            };
            const categoryIcon = categoryIcons[request.category] || 'bi-tools';
            document.getElementById('detailCategory').innerHTML = 
                `<i class="bi ${categoryIcon} me-1"></i>${ucwords(request.category?.replace('_', ' ') || '-')}`;
            
            // Priority with color
            const priorityColors = {
                'urgent': 'danger',
                'high': 'warning',
                'medium': 'info',
                'low': 'secondary'
            };
            const priorityColor = priorityColors[request.priority] || 'secondary';
            document.getElementById('detailPriority').innerHTML = 
                `<span class="badge bg-${priorityColor}">${ucwords(request.priority || '-')}</span>`;
            
            // Status with color
            const statusColors = {
                'completed': 'success',
                'in_progress': 'primary',
                'pending': 'warning',
                'cancelled': 'danger'
            };
            const statusColor = statusColors[request.status] || 'secondary';
            document.getElementById('detailStatus').innerHTML = 
                `<span class="badge bg-${statusColor}">${ucwords(request.status || '-')}</span>`;
            
            // Dates
            document.getElementById('detailRequestedDate').textContent = 
                request.requested_date ? formatDateTime(request.requested_date) : '-';
            document.getElementById('detailScheduledDate').textContent = 
                request.scheduled_date ? formatDateTime(request.scheduled_date) : 'Not scheduled';
            document.getElementById('detailCompletedDate').textContent = 
                request.completed_date ? formatDateTime(request.completed_date) : 'Not completed';
            
            // Location information
            document.getElementById('detailProperty').textContent = 
                request.property_details?.name || request.property_name || '-';
            document.getElementById('detailTenant').textContent = 
                request.tenant_name || '-';
            document.getElementById('detailUnit').textContent = 
                request.unit_details?.unit_number || request.unit_number || '-';
            document.getElementById('detailAssignedTo').textContent = 
                request.assigned_to || 'Not assigned';
            document.getElementById('detailEstimatedCost').textContent = 
                request.estimated_cost ? `Ksh ${parseFloat(request.estimated_cost).toFixed(2)}` : 'Not specified';
            document.getElementById('detailActualCost').textContent = 
                request.actual_cost ? `Ksh ${parseFloat(request.actual_cost).toFixed(2)}` : 'Not specified';
            
            // Notes (show only if exists)
            if (request.notes && request.notes.trim()) {
                document.getElementById('detailNotes').textContent = request.notes;
                document.getElementById('notesCard').style.display = 'block';
            } else {
                document.getElementById('notesCard').style.display = 'none';
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error loading maintenance request details'
            });
        }
    })
    .catch(error => {
        console.error('Error loading request details:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error loading maintenance request details'
        });
    });
}

// Helper function to format date and time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Helper function to capitalize words
function ucwords(str) {
    return str.replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<!-- Maintenance Request Details Modal -->
<div class="modal fade" id="maintenanceRequestDetailsModal" tabindex="-1" aria-labelledby="maintenanceRequestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="maintenanceRequestDetailsModalLabel">
                    <i class="bi bi-tools me-2"></i>Maintenance Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="maintenanceRequestDetailsContent">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="card border-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Request Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Title:</strong></div>
                                    <div class="col-sm-8" id="detailTitle">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Category:</strong></div>
                                    <div class="col-sm-8" id="detailCategory">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Priority:</strong></div>
                                    <div class="col-sm-8" id="detailPriority">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Status:</strong></div>
                                    <div class="col-sm-8" id="detailStatus">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Requested Date:</strong></div>
                                    <div class="col-sm-8" id="detailRequestedDate">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Scheduled Date:</strong></div>
                                    <div class="col-sm-8" id="detailScheduledDate">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Completed Date:</strong></div>
                                    <div class="col-sm-8" id="detailCompletedDate">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="card border-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Assignment</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Property:</strong></div>
                                    <div class="col-sm-8" id="detailProperty">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Tenant:</strong></div>
                                    <div class="col-sm-8" id="detailTenant">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Unit:</strong></div>
                                    <div class="col-sm-8" id="detailUnit">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Assigned To:</strong></div>
                                    <div class="col-sm-8" id="detailAssignedTo">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Estimated Cost:</strong></div>
                                    <div class="col-sm-8" id="detailEstimatedCost">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Actual Cost:</strong></div>
                                    <div class="col-sm-8" id="detailActualCost">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="card border-0 mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Description</h6>
                    </div>
                    <div class="card-body">
                        <p id="detailDescription" class="mb-0">-</p>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="card border-0 mt-3" id="notesCard" style="display: none;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-sticky me-2"></i>Notes</h6>
                    </div>
                    <div class="card-body">
                        <p id="detailNotes" class="mb-0">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
