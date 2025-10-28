<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-file-text text-primary me-2"></i>Leases Management
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/leases/template">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/leases/export/csv">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/leases/export/xlsx">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/leases/export/pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <form action="<?= BASE_URL ?>/leases/import" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </form>
                    <div class="vr d-none d-md-block"></div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaseModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Lease
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4 mt-4">
        <div class="col-12 col-md-4">
            <div class="stat-card lease-total">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Leases</h6>
                        <h2 class="mt-3 mb-2"><?= count($leases) ?></h2>
                        <p class="mb-0 text-muted">All time leases</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-file-text fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card lease-active">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Active Leases</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($leases, function($lease) {
                                return $lease['status'] === 'active';
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Currently active</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card lease-expiring">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Expiring Soon</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($leases, function($lease) {
                                $end_date = strtotime($lease['end_date']);
                                $thirty_days_from_now = strtotime('+30 days');
                                return $lease['status'] === 'active' && $end_date <= $thirty_days_from_now;
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Next 30 days</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-clock-history fs-1 text-warning opacity-25"></i>
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
                        <?php foreach($properties as $property): ?>
                            <option value="<?= $property['id'] ?>"><?= htmlspecialchars($property['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Date Range</label>
                    <select class="form-select" id="dateFilter">
                        <option value="">All Time</option>
                        <option value="current">Current</option>
                        <option value="expired">Expired</option>
                        <option value="expiring-soon">Expiring Soon</option>
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

    <!-- Leases Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="leasesTable">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Unit</th>
                            <th>Tenant</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leases)): ?>
                            <tr class="no-data">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-center">No leases found</td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leases as $lease): ?>
                                <tr>
                                    <td><?= htmlspecialchars($lease['property_name']) ?></td>
                                    <td><?= htmlspecialchars($lease['unit_number']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary text-white me-2">
                                                <?= strtoupper(substr($lease['tenant_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <?= htmlspecialchars($lease['tenant_name']) ?>
                                                <div class="small text-muted">ID: <?= $lease['tenant_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($lease['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($lease['end_date'])) ?></td>
                                    <td>
                                        <span class="fw-medium">
                                            Ksh<?= number_format($lease['rent_amount'], 2) ?>
                                        </span>
                                        <div class="small text-muted">Due: Day <?= $lease['payment_day'] ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'active' => 'bg-success',
                                            'expired' => 'bg-danger',
                                            'terminated' => 'bg-secondary'
                                        ];
                                        $statusClass = $statusClasses[$lease['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= ucfirst($lease['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editLease(<?= $lease['id'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteLease(<?= $lease['id'] ?>)">
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
</div>

<!-- Add Lease Modal -->
<div class="modal fade" id="addLeaseModal" tabindex="-1" aria-labelledby="addLeaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addLeaseForm" method="POST" action="<?= BASE_URL ?>/leases/store">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLeaseModalLabel">Add New Lease</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Property and Unit Selection -->
                        <div class="col-md-6">
                            <label for="property_id" class="form-label">Property</label>
                            <select class="form-select" id="property_id" name="property_id" required>
                                <option value="">Select Property</option>
                                <?php foreach($properties as $property): ?>
                                    <option value="<?= $property['id'] ?>"><?= htmlspecialchars($property['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="unit_id" class="form-label">Unit</label>
                            <select class="form-select" id="unit_id" name="unit_id" required disabled>
                                <option value="">Select Unit</option>
                            </select>
                        </div>

                        <!-- Tenant Selection -->
                        <div class="col-md-12">
                            <label for="tenant_id" class="form-label">Tenant</label>
                            <select class="form-select" id="tenant_id" name="tenant_id" required>
                                <option value="">Select Tenant</option>
                                <?php foreach($tenants as $tenant): ?>
                                    <option value="<?= $tenant['id'] ?>"><?= htmlspecialchars($tenant['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Lease Terms -->
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>

                        <!-- Financial Details -->
                        <div class="col-md-6">
                            <label for="rent_amount" class="form-label">Monthly Rent</label>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" step="0.01" class="form-control" id="rent_amount" name="rent_amount" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="security_deposit" class="form-label">Security Deposit</label>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" step="0.01" class="form-control" id="security_deposit" name="security_deposit" required>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="col-md-6">
                            <label for="payment_day" class="form-label">Payment Due Day</label>
                            <input type="number" min="1" max="31" class="form-control" id="payment_day" name="payment_day" value="1" required>
                            <div class="form-text">Day of the month when rent is due</div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Lease</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
}

.lease-total::before {
    background: linear-gradient(45deg, var(--primary-color), #0a58ca);
}

.lease-active::before {
    background: linear-gradient(45deg, var(--success-color), #28a745);
}

.lease-expiring::before {
    background: linear-gradient(45deg, var(--warning-color), #e6a800);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    const table = $('#leasesTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by start date by default
        columnDefs: [
            { orderable: false, targets: [7] } // Disable sorting for actions column
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "",
            searchPlaceholder: "Search leases...",
            emptyTable: "No leases found"
        },
        drawCallback: function(settings) {
            // Hide pagination if we have no data or only one page
            const api = this.api();
            const pageInfo = api.page.info();
            
            if (pageInfo.pages <= 1) {
                $(this).parent().find('.dataTables_paginate').hide();
            } else {
                $(this).parent().find('.dataTables_paginate').show();
            }

            // Hide length selector if we have no data
            if (pageInfo.recordsTotal === 0) {
                $(this).parent().find('.dataTables_length').hide();
            } else {
                $(this).parent().find('.dataTables_length').show();
            }
        }
    });

    // Property Change Handler
    document.getElementById('property_id').addEventListener('change', function() {
        const propertyId = this.value;
        const unitSelect = document.getElementById('unit_id');
        
        // Clear and disable unit select
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        unitSelect.disabled = !propertyId;
        
        if (propertyId) {
            // Fetch units for selected property
            fetch(`${BASE_URL}/units/getByProperty/${propertyId}`)
                .then(response => response.json())
                .then(units => {
                    units.forEach(unit => {
                        if (unit.status === 'vacant') {
                            const option = new Option(unit.unit_number, unit.id);
                            unitSelect.add(option);
                        }
                    });
                    unitSelect.disabled = false;
                })
                .catch(error => console.error('Error:', error));
        }
    });

    // Date Validation
    document.getElementById('end_date').addEventListener('change', function() {
        const startDate = document.getElementById('start_date').value;
        const endDate = this.value;
        
        if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
            alert('End date must be after start date');
            this.value = '';
        }
    });

    // Filter functionality
    document.getElementById('propertyFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('dateFilter').addEventListener('change', filterTable);

    // Reset filters
    document.getElementById('filterForm').addEventListener('reset', function() {
        setTimeout(filterTable, 0);
    });

    function filterTable() {
        const propertyFilter = document.getElementById('propertyFilter').value;
        const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
        const dateFilter = document.getElementById('dateFilter').value;

        // Remove any existing custom search functions
        $.fn.dataTable.ext.search.pop();

        // Add our custom search function
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const property = data[0]; // Property column
            const status = data[6].toLowerCase(); // Status column
            const startDate = new Date(data[3]); // Start date column
            const endDate = new Date(data[4]); // End date column
            const today = new Date();
            const thirtyDaysFromNow = new Date();
            thirtyDaysFromNow.setDate(today.getDate() + 30);

            // Property filter
            if (propertyFilter && !property.includes(propertyFilter)) return false;

            // Status filter
            if (statusFilter && !status.includes(statusFilter)) return false;

            // Date filter
            if (dateFilter) {
                switch (dateFilter) {
                    case 'current':
                        if (endDate < today) return false;
                        break;
                    case 'expired':
                        if (endDate >= today) return false;
                        break;
                    case 'expiring-soon':
                        if (endDate < today || endDate > thirtyDaysFromNow) return false;
                        break;
                }
            }

            return true;
        });

        table.draw();
    }
});

// Lease Actions
function editLease(id) {
    window.location.href = `${BASE_URL}/leases/edit/${id}`;
}

function deleteLease(id) {
    if (confirm('Are you sure you want to delete this lease? This action cannot be undone.')) {
        window.location.href = `${BASE_URL}/leases/delete/${id}`;
    }
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php'; 