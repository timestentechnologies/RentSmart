<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Maintenance Requests</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintenanceRequestModal">
                <i class="bi bi-plus-lg me-2"></i>New Request
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show mt-4">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- Maintenance Requests Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">TITLE</th>
                        <th class="text-muted">CATEGORY</th>
                        <th class="text-muted">PRIORITY</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">REQUESTED DATE</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="maintenanceRequestsTable">
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-tools display-4 text-muted mb-3 d-block"></i>
                                <h5>No maintenance requests found</h5>
                                <p class="text-muted">Submit your first maintenance request</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintenanceRequestModal">
                                    <i class="bi bi-plus-lg me-2"></i>Submit Request
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
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
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($request['property_name'] ?? 'N/A') ?></small>
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
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showMaintenanceRequestDetails(<?= $request['id'] ?>)">
                                            <i class="bi bi-eye"></i>
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

<!-- Maintenance Request Modal -->
<div class="modal fade" id="maintenanceRequestModal" tabindex="-1" aria-labelledby="maintenanceRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="maintenanceRequestForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="maintenanceRequestModalLabel">Submit Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="requestTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="requestTitle" name="title" required placeholder="Brief description of the issue">
                    </div>
                    <div class="mb-3">
                        <label for="requestCategory" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="requestCategory" name="category" required>
                            <option value="">Select Category</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="hvac">HVAC</option>
                            <option value="appliance">Appliance</option>
                            <option value="structural">Structural</option>
                            <option value="pest_control">Pest Control</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requestPriority" class="form-label">Priority</label>
                        <select class="form-select" id="requestPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requestDescription" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="requestDescription" name="description" rows="4" required placeholder="Please provide detailed description of the issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Request Details Modal -->
<div class="modal fade" id="maintenanceRequestDetailsModal" tabindex="-1" aria-labelledby="maintenanceRequestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceRequestDetailsModalLabel">Maintenance Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="maintenanceRequestDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle maintenance request form submission
    document.getElementById('maintenanceRequestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('<?= BASE_URL ?>/tenant/maintenance/create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('maintenanceRequestModal')).hide();
                
                // Reset form
                this.reset();
                
                // Reload page to show new request
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error submitting maintenance request'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error submitting maintenance request'
            });
        });
    });
});

function showMaintenanceRequestDetails(requestId) {
    fetch(`<?= BASE_URL ?>/tenant/maintenance/get/${requestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const request = data.request;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Request Details</h6>
                            <p><strong>Title:</strong> ${request.title}</p>
                            <p><strong>Category:</strong> ${request.category.replace('_', ' ').toUpperCase()}</p>
                            <p><strong>Priority:</strong> <span class="badge bg-${getPriorityColor(request.priority)}">${request.priority.toUpperCase()}</span></p>
                            <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(request.status)}">${request.status.replace('_', ' ').toUpperCase()}</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Timeline</h6>
                            <p><strong>Requested:</strong> ${new Date(request.requested_date).toLocaleDateString()}</p>
                            ${request.scheduled_date ? `<p><strong>Scheduled:</strong> ${new Date(request.scheduled_date).toLocaleDateString()}</p>` : ''}
                            ${request.completed_date ? `<p><strong>Completed:</strong> ${new Date(request.completed_date).toLocaleDateString()}</p>` : ''}
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6>Description</h6>
                        <p>${request.description}</p>
                    </div>
                    ${request.notes ? `
                    <div class="mt-3">
                        <h6>Admin Notes</h6>
                        <p>${request.notes}</p>
                    </div>
                    ` : ''}
                    ${request.assigned_to ? `
                    <div class="mt-3">
                        <h6>Assigned To</h6>
                        <p>${request.assigned_to}</p>
                    </div>
                    ` : ''}
                `;
                
                document.getElementById('maintenanceRequestDetailsContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('maintenanceRequestDetailsModal')).show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error loading request details'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error loading request details'
            });
        });
}

function getPriorityColor(priority) {
    switch(priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'in_progress': return 'primary';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
