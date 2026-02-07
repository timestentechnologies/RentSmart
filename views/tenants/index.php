<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-people text-primary me-2"></i>Tenants Management
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/tenants/template">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/tenants/export/csv">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/tenants/export/xlsx">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/tenants/export/pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <form action="<?= BASE_URL ?>/tenants/import" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </form>
                    <div class="vr d-none d-md-block"></div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Tenant
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4 mt-4">
        <div class="col-12 col-md-4">
            <div class="stat-card tenant-total">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Tenants</h6>
                        <h2 class="mt-3 mb-2"><?= count($tenants) ?></h2>
                        <p class="mb-0 text-muted">Active and inactive tenants</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-people fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card tenant-active">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Active Tenants</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($tenants, function($tenant) {
                                return isset($tenant['property_name']) && $tenant['property_name'] !== null;
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Currently renting</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-house-check fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card tenant-revenue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Monthly Revenue</h6>
                        <h2 class="mt-3 mb-2">
                            Ksh<?= number_format(array_sum(array_column($tenants, 'rent_amount')), 2) ?>
                        </h2>
                        <p class="mb-0 text-muted">Total monthly rent</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-cash-stack fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="propertyFilter" class="form-label">Property</label>
                    <select class="form-select" id="propertyFilter">
                        <option value="">All Properties</option>
                        <?php 
                        // Use a different variable name for filtering to avoid conflict
                        $filterProperties = array_unique(array_column($tenants, 'property_name'));
                        foreach($filterProperties as $property): 
                            if($property): 
                        ?>
                            <option value="<?= htmlspecialchars($property) ?>">
                                <?= htmlspecialchars($property) ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="rentFilter" class="form-label">Rent Range</label>
                    <select class="form-select" id="rentFilter">
                        <option value="">All Ranges</option>
                        <option value="0-1000">Ksh0 - Ksh1,000</option>
                        <option value="1000-2000">Ksh1,000 - Ksh2,000</option>
                        <option value="2000+">Ksh2,000+</option>
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

    <!-- Tenants Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">NAME</th>
                        <th class="text-muted">PROPERTY</th>
                        <th class="text-muted">UNIT</th>
                        <th class="text-muted">RENT AMOUNT</th>
                        <th class="text-muted">PAYMENTS</th>
                        <th class="text-muted">BALANCE</th>
                        <th class="text-muted">UTILITIES</th>
                        <th class="text-muted">CONTACT</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tenants)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-people display-4 text-muted mb-3 d-block"></i>
                                <h5>No tenants found</h5>
                                <p class="text-muted">Start by adding your first tenant</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                                    <i class="bi bi-plus-lg me-2"></i>Add Tenant
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-2">
                                            <?= strtoupper(substr($tenant['name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($tenant['name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($tenant['property_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($tenant['unit_number'] ?? '-') ?></td>
                                <td>Ksh<?= number_format($tenant['rent_amount'] ?? 0, 2) ?></td>
                                <td>
                                    <div>
                                        <small class="text-muted">This Month:</small><br>
                                        Ksh<?= number_format($tenant['current_month_payment'] ?? 0, 2) ?>
                                        <br>
                                        <small class="text-muted">Total:</small><br>
                                        Ksh<?= number_format($tenant['total_payments'] ?? 0, 2) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $balance = ($tenant['rent_amount'] ?? 0) - ($tenant['current_month_payment'] ?? 0);
                                        $balanceClass = $balance > 0 ? 'text-danger' : 'text-success';
                                    ?>
                                    <span class="<?= $balanceClass ?>">
                                        Ksh<?= number_format(abs($balance), 2) ?>
                                        <?= $balance > 0 ? '(Due)' : '(Paid)' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($tenant['utility_readings']): ?>
                                        <?php
                                            $readings = explode(',', $tenant['utility_readings']);
                                            foreach ($readings as $reading):
                                                list($type, $value) = explode(':', $reading);
                                        ?>
                                            <div class="small">
                                                <strong><?= ucfirst($type) ?>:</strong> <?= trim($value) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No utilities</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <a href="mailto:<?= htmlspecialchars($tenant['email']) ?>" class="text-decoration-none d-block">
                                            <i class="bi bi-envelope text-muted me-1"></i><?= htmlspecialchars($tenant['email']) ?>
                                        </a>
                                        <a href="tel:<?= htmlspecialchars($tenant['phone']) ?>" class="text-decoration-none">
                                            <i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($tenant['phone']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $hasProperty = isset($tenant['property_name']) && $tenant['property_name'] !== '-';
                                        $hasUnit = isset($tenant['unit_number']) && $tenant['unit_number'] !== '-';
                                        if ($hasProperty && $hasUnit) {
                                            $statusClass = 'bg-success';
                                            $statusText = 'Active';
                                        } elseif ($hasProperty && !$hasUnit) {
                                            $statusClass = 'bg-warning';
                                            $statusText = 'Pending Unit';
                                        } else {
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Inactive';
                                        }
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td>
                                <div class="btn-group">
                                    <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'agent', 'landlord', 'manager'])): ?>
                                    <a href="<?= BASE_URL ?>/admin/tenants/login-as/<?= $tenant['id'] ?>" 
                                    class="btn btn-sm btn-outline-info" 
                                    title="Login as this tenant">
                                        <i class="bi bi-person-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-tenant" data-id="<?= $tenant['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTenant(<?= $tenant['id'] ?>)">
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

<!-- Add Tenant Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1" aria-labelledby="addTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addTenantForm" method="POST" action="<?= BASE_URL ?>/tenants">
                <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addTenantModalLabel">Add New Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="registered_on" class="form-label">Registration Date</label>
                                <input type="date" class="form-control" id="registered_on" name="registered_on" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Property Assignment (Optional) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="property_id" class="form-label">Property <span class="text-danger">*</span></label>
                                <select class="form-select" id="property_id" name="property_id" required>
                                    <option value="">Select Property</option>
                                    <?php if (isset($properties) && is_array($properties)): 
                                        foreach ($properties as $property): ?>
                                        <option value="<?= htmlspecialchars($property['id']) ?>">
                                            <?= htmlspecialchars($property['name']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="unit_id" class="form-label">Unit (Optional)</label>
                                <select class="form-select" id="unit_id" name="unit_id" disabled>
                                    <option value="">Select Unit (Optional)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="rent_amount" class="form-label">Monthly Rent (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" id="rent_amount" name="rent_amount">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_type" class="form-label">ID Type</label>
                                        <select class="form-select" id="id_type" name="id_type">
                                            <option value="">Select ID Type</option>
                                            <option value="national_id">National ID</option>
                                            <option value="passport">Passport</option>
                                            <option value="drivers_license">Driver's License</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_number" class="form-label">ID Number</label>
                                        <input type="text" class="form-control" id="id_number" name="id_number">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tenant Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1" aria-labelledby="editTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editTenantForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTenantModalLabel">Edit Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_property_id" class="form-label">Property</label>
                        <select id="edit_property_id" name="property_id" class="form-select" required>
                            <option value="">Select Property</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= htmlspecialchars($property['id']) ?>">
                                    <?= htmlspecialchars($property['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_unit_id" class="form-label">Unit</label>
                        <select id="edit_unit_id" name="unit_id" class="form-select">
                            <option value="">Select Unit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_rent_amount" class="form-label">Rent Amount</label>
                        <input type="number" id="edit_rent_amount" name="rent_amount" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Tenant Confirmation Modal -->
<div class="modal fade" id="deleteTenantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this tenant? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTenantBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Tenant Success Modal -->
<div class="modal fade" id="deleteTenantSuccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tenant Deleted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tenant was successfully deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="closeDeleteTenantSuccessBtn" data-bs-dismiss="modal">OK</button>
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

.tenant-total::before {
    background: linear-gradient(45deg, var(--primary-color), #0a58ca);
}

.tenant-active::before {
    background: linear-gradient(45deg, var(--success-color), #28a745);
}

.tenant-revenue::before {
    background: linear-gradient(45deg, var(--warning-color), #e6a800);
}

.btn-icon {
    padding: 0.5rem;
    line-height: 1;
}

.tenant-details {
    position: relative;
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 1px solid var(--bs-border-color);
}

@media (max-width: 768px) {
    .tenant-card {
        margin-bottom: 1rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter functionality
    const filterForm = document.getElementById('filterForm');
    const propertyFilter = document.getElementById('propertyFilter');
    const statusFilter = document.getElementById('statusFilter');
    const rentFilter = document.getElementById('rentFilter');
    const tableRows = document.querySelectorAll('tbody tr');

    function applyFilters() {
        const selectedProperty = propertyFilter.value.toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();
        const selectedRentRange = rentFilter.value;

        tableRows.forEach(row => {
            const propertyCell = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const statusCell = row.querySelector('td:nth-child(6) .badge').textContent.toLowerCase();
            const rentCell = parseFloat(row.querySelector('td:nth-child(4)').textContent.replace('$', '').replace(',', ''));

            let showRow = true;

            // Property filter
            if (selectedProperty && propertyCell !== selectedProperty) {
                showRow = false;
            }

            // Status filter
            if (selectedStatus && statusCell !== selectedStatus) {
                showRow = false;
            }

            // Rent range filter
            if (selectedRentRange) {
                const [min, max] = selectedRentRange.split('-').map(val => val === '+' ? Infinity : parseFloat(val));
                if (rentCell < min || (max !== Infinity && rentCell > max)) {
                    showRow = false;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    // Add event listeners to filters
    propertyFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    rentFilter.addEventListener('change', applyFilters);

    // Reset filters
    filterForm.addEventListener('reset', function() {
        setTimeout(applyFilters, 0);
    });

    // Property-Unit Dynamic Loading
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    const rentInput = document.getElementById('rent_amount');

    propertySelect.addEventListener('change', function() {
        unitSelect.disabled = true;
        unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
        rentInput.value = '';

        if (this.value) {
            fetch(`${BASE_URL}/properties/${this.value}/units`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.units) {
                        let vacantFound = false;
                        data.units.forEach(unit => {
                            if (unit.status !== 'occupied') {
                                const option = document.createElement('option');
                                option.value = unit.id;
                                option.textContent = `Unit ${unit.unit_number}`;
                                option.dataset.rent = unit.rent_amount;
                                unitSelect.appendChild(option);
                                vacantFound = true;
                            }
                        });
                        if (!vacantFound) {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'No vacant units available';
                            unitSelect.appendChild(option);
                        }
                        unitSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading units:', error);
                });
        }
    });

    // Update rent amount when unit is selected
    unitSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.rent) {
            rentInput.value = selectedOption.dataset.rent;
        } else {
            rentInput.value = '';
        }
    });

    // Add Tenant Form Handling
    const addTenantForm = document.getElementById('addTenantForm');
    if (addTenantForm) {
        addTenantForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('addTenantModal')).hide();

                    // Add the new tenant to the table
                    if (data.tenant) {
                        const tbody = document.querySelector('table tbody');
                        const tenant = data.tenant;
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-primary text-white me-2">
                                        ${tenant.name.charAt(0).toUpperCase()}
                                    </div>
                                    ${tenant.name}
                                </div>
                            </td>
                            <td>${tenant.property_name || '-'}</td>
                            <td>${tenant.unit_number || '-'}</td>
                            <td>Ksh${(tenant.rent_amount || 0).toFixed(2)}</td>
                            <td>
                                <div>
                                    <a href="mailto:${tenant.email}" class="text-decoration-none d-block">
                                        <i class="bi bi-envelope text-muted me-1"></i>${tenant.email}
                                    </a>
                                    <a href="tel:${tenant.phone}" class="text-decoration-none">
                                        <i class="bi bi-telephone text-muted me-1"></i>${tenant.phone}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="badge ${tenant.property_name ? 'bg-success' : 'bg-secondary'}">
                                    ${tenant.property_name ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="${BASE_URL}/tenants/edit/${tenant.id}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTenant(${tenant.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.prepend(row);
                    }
                    // Show SweetAlert success
                    Swal.fire({
                        icon: 'success',
                        title: 'Tenant Added!',
                        text: data.message || 'Tenant added successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    // Show SweetAlert error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error adding tenant.'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error adding tenant'
                });
            });
        });
    }

    // Edit Tenant Handling
    document.querySelectorAll('.btn-edit-tenant').forEach(btn => {
        btn.addEventListener('click', function() {
            const tenantId = this.getAttribute('data-id');
            fetch(`${BASE_URL}/tenants/get/${tenantId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.tenant.id;
                        document.getElementById('edit_name').value = data.tenant.name || '';
                        document.getElementById('edit_email').value = data.tenant.email || '';
                        document.getElementById('edit_phone').value = data.tenant.phone || '';
                        document.getElementById('edit_rent_amount').value = data.tenant.rent_amount || '';
                        document.getElementById('editTenantForm').dataset.tenantId = data.tenant.id;
                        // Set property and load units
                        const propertySelect = document.getElementById('edit_property_id');
                        propertySelect.value = data.tenant.property_id || '';
                        loadUnitsForProperty(data.tenant.property_id, data.tenant.unit_id);
                        var modal = new bootstrap.Modal(document.getElementById('editTenantModal'));
                        modal.show();
                    } else {
                        alert(data.message);
                    }
                });
        });
    });

    function loadUnitsForProperty(propertyId, selectedUnitId = null) {
        const unitSelect = document.getElementById('edit_unit_id');
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        if (propertyId) {
            fetch(`${BASE_URL}/properties/${propertyId}/units`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.units) {
                        data.units.forEach(unit => {
                            const option = document.createElement('option');
                            option.value = unit.id;
                            option.textContent = `Unit ${unit.unit_number}`;
                            if (selectedUnitId && unit.id == selectedUnitId) {
                                option.selected = true;
                            }
                            unitSelect.appendChild(option);
                        });
                    }
                });
        }
    }

    document.getElementById('edit_property_id').addEventListener('change', function() {
        loadUnitsForProperty(this.value);
    });

    // Edit Tenant Form Handling
    const editTenantForm = document.getElementById('editTenantForm');
    if (editTenantForm) {
        editTenantForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const tenantId = editTenantForm.dataset.tenantId;
            fetch(`${BASE_URL}/tenants/update/${tenantId}`, {
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
                    bootstrap.Modal.getInstance(document.getElementById('editTenantModal')).hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Tenant Updated!',
                        text: data.message || 'Tenant updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error updating tenant.'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating tenant'
                });
            });
        });
    }
});

let tenantIdToDelete = null;
function deleteTenant(id) {
    tenantIdToDelete = id;
    new bootstrap.Modal(document.getElementById('deleteTenantModal')).show();
}
document.getElementById('confirmDeleteTenantBtn').onclick = function() {
    fetch(`${BASE_URL}/tenants/delete/${tenantIdToDelete}`, {
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
            const row = document.querySelector(`button[onclick="deleteTenant(${tenantIdToDelete})"]`).closest('tr');
            if (row) row.parentNode.removeChild(row);
            const successModal = new bootstrap.Modal(document.getElementById('deleteTenantSuccessModal'));
            successModal.show();
            document.getElementById('closeDeleteTenantSuccessBtn').onclick = function() {
                document.querySelectorAll('.modal.show').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
                location.reload();
            };
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error deleting tenant.'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error deleting tenant.'
        });
    });
};
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';