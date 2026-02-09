<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-lightning-charge text-primary me-2"></i>Utilities Management
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/utilities/template">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/utilities/export/csv">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/utilities/export/xlsx">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/utilities/export/pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <form action="<?= BASE_URL ?>/utilities/import" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </form>
                    <div class="vr d-none d-md-block"></div>
                    <a href="<?= BASE_URL ?>/utilities/create" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add Utility
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Utility Types & Rates Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Utility Types & Rates</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addUtilityTypeModal">
                <i class="bi bi-plus"></i> Add Utility Type
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Type</th>
                            <th>Billing Method</th>
                            <th>Rate Per Unit (KES)</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($utility_rates)): ?>
                            <tr><td colspan="7" class="text-center">No utility types/rates found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($utility_rates as $type => $rateList): ?>
                                <?php foreach ($rateList as $rate): ?>
                                    <tr>
                                        <td>
                                            <?php
                                                $pname = $rate['property_name'] ?? null;
                                                if ($pname) {
                                                    echo htmlspecialchars($pname);
                                                } else {
                                                    $pid = $rate['property_id'] ?? null;
                                                    echo $pid ? ('Property #' . (int)$pid) : '';
                                                }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($type) ?></td>
                                        <td><?= htmlspecialchars($rate['billing_method']) === 'metered' ? 'Metered' : 'Flat Rate' ?></td>
                                        <td><?= number_format($rate['rate_per_unit'], 2) ?></td>
                                        <td><?= htmlspecialchars($rate['effective_from']) ?></td>
                                        <td><?= $rate['effective_to'] ? htmlspecialchars($rate['effective_to']) : '<span class="badge bg-success">Current</span>' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning edit-utility-type-btn"
                                                    data-id="<?= $rate['id'] ?>"
                                                    data-type="<?= htmlspecialchars($type) ?>"
                                                    data-billing-method="<?= $rate['billing_method'] ?>"
                                                    data-rate="<?= $rate['rate_per_unit'] ?>"
                                                    data-effective-from="<?= $rate['effective_from'] ?>"
                                                    data-effective-to="<?= $rate['effective_to'] ?>"
                                                    data-property-id="<?= htmlspecialchars($rate['property_id'] ?? '') ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editUtilityTypeModal">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>

                                            <form method="POST" action="<?= BASE_URL ?>/utility-rates/delete/<?= (int)$rate['id'] ?>" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this utility type/rate?');">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Utility Type Modal -->
    <div class="modal fade" id="addUtilityTypeModal" tabindex="-1" aria-labelledby="addUtilityTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= BASE_URL ?>/utility-rates/store">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUtilityTypeModalLabel">Add Utility Type & Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Property</label>
                            <select class="form-select" name="property_id" required>
                                <option value="">Select Property</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Utility Type</label>
                            <input type="text" class="form-control" name="utility_type" required placeholder="e.g. Water, Electricity">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Billing Method</label>
                            <select class="form-select" name="billing_method" required>
                                <option value="metered">Metered</option>
                                <option value="flat_rate">Flat Rate</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rate Per Unit (KES)</label>
                            <input type="number" class="form-control" name="rate_per_unit" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" class="form-control" name="effective_from" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effective To <span class="text-muted small">(optional)</span></label>
                            <input type="date" class="form-control" name="effective_to">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Rate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><?= count($utilities) ?></h2>
                        <p class="card-title">Total Utilities</p>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-lightning-charge fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><?= count(array_filter($utilities, fn($u) => $u['is_metered'])) ?></h2>
                        <p class="card-title">Metered Utilities</p>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-speedometer2 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><?= count(array_filter($utilities, fn($u) => !$u['is_metered'])) ?></h2>
                        <p class="card-title">Flat Rate Utilities</p>
                    </div>
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><?= count($properties) ?></h2>
                        <p class="card-title">Properties</p>
                    </div>
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Utilities Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">All Utilities</h5>
        </div>
        <div class="card-body">
            <?php if (empty($utilities)): ?>
                <div class="text-center py-5">
                    <h5 class="text-muted">No utilities found</h5>
                    <a href="<?= BASE_URL ?>/utilities/create" class="btn btn-primary">
                        Add First Utility
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Unit</th>
                                <th>Utility Type</th>
                                <th>Type</th>
                                <th>Meter</th>
                                <th>Previous Reading</th>
                                <th>Current Reading</th>
                                <th>Units Used</th>
                                <th>Cost</th>
                                <th>Paid</th>
                                <th>Balance Due</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilities as $utility): ?>
                                <tr>
                                    <td><?= htmlspecialchars($utility['property_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($utility['unit_number'] ?? '-') ?></td>
                                    <td><?= ucfirst($utility['utility_type']) ?></td>
                                    <td><?= $utility['is_metered'] ? 'Metered' : 'Flat Rate' ?></td>
                                    <td><?= htmlspecialchars($utility['meter_number']) ?></td>
                                    <td><?= htmlspecialchars($utility['previous_reading']) ?></td>
                                    <td><?= htmlspecialchars($utility['latest_reading']) ?></td>
                                    <td><?= htmlspecialchars($utility['units_used']) ?></td>
                                    <td><?= htmlspecialchars($utility['cost']) ?></td>
                                    <td><?= htmlspecialchars(number_format((float)($utility['paid_amount'] ?? 0), 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format((float)($utility['balance_due'] ?? 0), 2)) ?></td>
                                    <td>
                                        <?php if (strtolower($utility['is_metered'] ? '' : 'flat rate') !== 'flat rate'): ?>
                                            <button class="btn btn-sm btn-outline-primary edit-utility-btn"
                                                    data-id="<?= $utility['id'] ?>"
                                                    data-unit_id="<?= $utility['unit_id'] ?>"
                                                    data-unit_number="<?= htmlspecialchars($utility['unit_number']) ?>"
                                                    data-property_name="<?= htmlspecialchars($utility['property_name']) ?>"
                                                    data-utility_type="<?= htmlspecialchars($utility['utility_type']) ?>"
                                                    data-utility_type_label="<?= htmlspecialchars(ucfirst($utility['utility_type'])) ?>"
                                                    data-meter_number="<?= htmlspecialchars($utility['meter_number']) ?>"
                                                    data-is_metered="<?= $utility['is_metered'] ?>"
                                                    data-previous_reading="<?= htmlspecialchars($utility['previous_reading']) ?>"
                                                    data-latest_reading="<?= htmlspecialchars($utility['latest_reading']) ?>"
                                                    data-units_used="<?= htmlspecialchars($utility['units_used']) ?>"
                                                    data-cost="<?= htmlspecialchars($utility['cost']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editUtilityModal">
                                                Edit
                                            </button>
                                        <?php endif; ?>
                                        <form method="POST" action="<?= BASE_URL ?>/utilities/delete/<?= $utility['id'] ?>" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this utility?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUtilityModal" tabindex="-1" aria-labelledby="deleteUtilityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUtilityModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this utility?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Single Edit Utility Type Modal -->
<div class="modal fade" id="editUtilityTypeModal" tabindex="-1" aria-labelledby="editUtilityTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/utility-rates/store" id="editUtilityTypeForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUtilityTypeModalLabel">Edit Utility Type & Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <select class="form-select" name="property_id" id="edit_property_id" required>
                            <option value="">Select Property</option>
                            <?php foreach (($properties ?? []) as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Utility Type</label>
                        <input type="text" class="form-control" name="utility_type" id="edit_utility_type" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing Method</label>
                        <select class="form-select" name="billing_method" id="edit_billing_method" required>
                            <option value="metered">Metered</option>
                            <option value="flat_rate">Flat Rate</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rate Per Unit (KES)</label>
                        <input type="number" class="form-control" name="rate_per_unit" id="edit_rate_per_unit" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Effective From</label>
                        <input type="date" class="form-control" name="effective_from" id="edit_effective_from" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Effective To <span class="text-muted small">(optional)</span></label>
                        <input type="date" class="form-control" name="effective_to" id="edit_effective_to">
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

<!-- Edit Utility Modal -->
<div class="modal fade" id="editUtilityModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editUtilityForm">
        <div class="modal-header">
                    <h5 class="modal-title">Edit Utility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <div class="mb-3">
            <label class="form-label">Unit</label>
            <input type="text" class="form-control" id="edit_unit_display" readonly>
            <input type="hidden" name="unit_id" id="edit_unit_id">
          </div>
          <div class="mb-3">
            <label class="form-label">Utility Type</label>
            <input type="text" class="form-control" id="edit_utility_type_display" readonly>
                        <input type="hidden" name="utility_type" id="edit_utility_type_hidden">
          </div>
          <div class="mb-3">
            <label class="form-label">Meter Number</label>
            <input type="text" class="form-control" name="meter_number" id="edit_meter_number">
          </div>
          <div class="mb-3">
            <label class="form-label">Is Metered?</label>
            <select class="form-select" name="is_metered" id="edit_is_metered">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Previous Reading</label>
            <input type="number" class="form-control" name="previous_reading" id="edit_previous_reading" step="0.01" min="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Current Reading</label>
            <input type="number" class="form-control" name="current_reading" id="edit_current_reading" step="0.01" min="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Units Used</label>
            <input type="number" class="form-control" name="units_used" id="edit_units_used" step="0.01" min="0" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Cost</label>
                        <input type="text" class="form-control" name="cost" id="edit_cost">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reading Date</label>
                        <input type="date" class="form-control" name="reading_date" id="edit_reading_date" value="<?= date('Y-m-d') ?>">
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

<style>
.alert {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    min-width: 300px;
    text-align: center;
    box-shadow: 0 3px 6px rgba(0,0,0,0.16);
}
</style>

<script>
// Helper function to show alerts - defined at the top level
function showAlert(type, message) {
    console.log('Showing alert:', { type, message });
    
    // Remove any existing alerts
    $('.alert').remove();
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Add alert to body instead of after page-header
    $('body').append(alertHtml);
}

$(document).ready(function() {
    $('#utilitiesTable').DataTable({
        responsive: true,
        order: [[0, 'asc'], [1, 'asc']],
        pageLength: 25,
        language: {
            search: "Search utilities:",
            lengthMenu: "Show _MENU_ utilities per page",
            info: "Showing _START_ to _END_ of _TOTAL_ utilities"
        }
    });

    // Handle utility edit form submission
    $('#editUtilityForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        console.log('Form submission started');
        
        // Get form data
        const formData = new FormData(form[0]);
        
        // Log form data for debugging
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Disable submit button and show loading state
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        // Add calculated values
        const prevReading = parseFloat($('#edit_previous_reading').val()) || 0;
        const currReading = parseFloat($('#edit_current_reading').val()) || 0;
        const unitsUsed = currReading - prevReading;
        formData.set('units_used', unitsUsed);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Success response:', response);
                
                // Hide modal first
                const modal = bootstrap.Modal.getInstance(document.getElementById('editUtilityModal'));
                modal.hide();
                
                // Show success message
                showAlert('success', response.message || 'Utility updated successfully');
                
                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500); // Increased delay to ensure message is visible
            },
            error: function(xhr, status, error) {
                console.error('Error details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let message = 'Error updating utility';
                try {
                    if (xhr.responseText.includes('</table>')) {
                        // This is likely a PHP error output
                        message = 'A server error occurred. Please check the logs.';
                        console.error('PHP Error Output:', xhr.responseText);
                    } else {
                        const response = JSON.parse(xhr.responseText);
                        message = response.message || response.errors?.join(', ') || message;
                    }
                } catch(e) {
                    console.error('Error parsing response:', e);
                }
                
                showAlert('danger', message);
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Store the utility ID to be deleted
    let utilityToDelete = null;

    // Replace the inline delete form with a button
    $('form[action*="/utilities/delete/"]').each(function() {
        const form = $(this);
        const utilityId = form.attr('action').split('/').pop();
        const csrfToken = form.find('input[name="csrf_token"]').val();
        
        const deleteBtn = $('<button>')
            .addClass('btn btn-sm btn-outline-danger delete-utility-btn')
            .attr('data-id', utilityId)
            .attr('data-csrf', csrfToken)
            .text('Delete');
        
        form.replaceWith(deleteBtn);
    });

    // Handle delete button click
    $('.delete-utility-btn').on('click', function(e) {
        e.preventDefault();
        utilityToDelete = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('deleteUtilityModal'));
        modal.show();
    });

    // Handle delete confirmation
    $('#confirmDeleteBtn').on('click', function() {
        if (!utilityToDelete) return;

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Deleting...');

        $.ajax({
            url: `${BASE_URL}/utilities/delete/${utilityToDelete}`,
            method: 'POST',
            data: {
                csrf_token: $('input[name="csrf_token"]').val()
            },
            success: function(response) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUtilityModal'));
                modal.hide();
                
                if (response.success) {
                    showAlert('success', response.message || 'Utility deleted successfully');
                    // Remove the row from the table
                    $(`.delete-utility-btn[data-id="${utilityToDelete}"]`).closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    showAlert('danger', response.message || 'Error deleting utility');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let message = 'Error deleting utility';
                try {
                    if (xhr.responseText.includes('</table>')) {
                        message = 'A server error occurred. Please check the logs.';
                        console.error('PHP Error Output:', xhr.responseText);
                    } else {
                        const response = JSON.parse(xhr.responseText);
                        message = response.message || message;
                    }
                } catch(e) {
                    console.error('Error parsing response:', e);
                }
                
                showAlert('danger', message);
            },
            complete: function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUtilityModal'));
                modal.hide();
                btn.prop('disabled', false).html(originalText);
                utilityToDelete = null;
            }
        });
    });

    // Utility edit button click handler
    $('.edit-utility-btn').on('click', function() {
        const data = $(this).data();
        console.log('Edit button clicked, data:', data);
        
        $('#edit_unit_display').val(data.unit_number + ' - ' + data.property_name);
        $('#edit_unit_id').val(data.unit_id);
        $('#edit_utility_type_display').val(data.utility_type_label || data.utility_type);
        $('#edit_utility_type_hidden').val(data.utility_type);
        $('#edit_meter_number').val(data.meter_number);
        $('#edit_is_metered').val(data.is_metered);
        $('#edit_previous_reading').val(data.previous_reading || '');
        $('#edit_current_reading').val(data.latest_reading || '');
        $('#edit_units_used').val(data.units_used || '');
        $('#edit_cost').val(data.cost || '');
        
        // Set form action
        const formAction = `${BASE_URL}/utilities/update/${data.id}`;
        $('#editUtilityForm').attr('action', formAction);
        console.log('Form action set to:', formAction);
    });

    // Calculate units used and cost when readings change
    $('#edit_previous_reading, #edit_current_reading').on('input', function() {
        const prevReading = parseFloat($('#edit_previous_reading').val()) || 0;
        const currReading = parseFloat($('#edit_current_reading').val()) || 0;
        const unitsUsed = currReading - prevReading;
        $('#edit_units_used').val(unitsUsed.toFixed(2));
        
        // Calculate cost if metered
        if ($('#edit_is_metered').val() === '1') {
            const utilityType = $('#edit_utility_type_hidden').val().toLowerCase();
            const rate = utilityRates[utilityType] || 0;
            const cost = unitsUsed * rate;
            $('#edit_cost').val(cost.toFixed(2));
        }
    });
});

// Expose PHP utility_rates to JS
const utilityRates = {};
<?php foreach ($utility_rates as $type => $rateList): ?>
    utilityRates['<?= addslashes(strtolower($type)) ?>'] = <?= json_encode($rateList[0]['rate_per_unit'] ?? 0) ?>;
<?php endforeach; ?>

// Edit Utility Type Modal logic
const editBtns = document.querySelectorAll('.edit-utility-type-btn');
const editId = document.getElementById('edit_id');
const editType = document.getElementById('edit_utility_type');
const editBilling = document.getElementById('edit_billing_method');
const editRate = document.getElementById('edit_rate_per_unit');
const editFrom = document.getElementById('edit_effective_from');
const editTo = document.getElementById('edit_effective_to');
const editPropertyId = document.getElementById('edit_property_id');

editBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        editId.value = this.dataset.id;
        editType.value = this.dataset.type;
        editBilling.value = this.dataset['billingMethod'];
        editRate.value = this.dataset.rate;
        editFrom.value = this.dataset.effectiveFrom;
        editTo.value = this.dataset.effectiveTo;
        if (editPropertyId) {
            editPropertyId.value = this.dataset.propertyId || '';
        }
    });
});
</script>

<?php if (isset($_SESSION['edit_utility_id'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Replace 'editUtilityModal' with your actual modal ID
        var editModal = new bootstrap.Modal(document.getElementById('editUtilityModal'));
        editModal.show();
    });
</script>
<?php unset($_SESSION['edit_utility_id']); endif; ?> 