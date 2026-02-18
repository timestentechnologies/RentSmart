<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div id="importLoadingOverlay" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.85); z-index:2000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
            <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;"></div>
            <div class="mt-3 fw-semibold">Importing... Please wait</div>
        </div>
    </div>
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-buildings text-primary me-2"></i>Properties Management
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/properties/template">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/properties/export/csv">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/properties/export/xlsx">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/properties/export/pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <form action="<?= BASE_URL ?>/properties/import" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 import-form">
                        <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-dark import-submit-btn">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </form>
                    <div class="vr d-none d-md-block"></div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Property
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card property-count">
                <div class="d-flex justify-content-between align-items-start">
                        <div>
                        <h6 class="card-title">Total Properties</h6>
                        <h2 class="mt-3 mb-2"><?= count($properties) ?></h2>
                        <p class="mb-0 text-muted">Active properties in management</p>
                        </div>
                        <div class="stats-icon">
                        <i class="bi bi-building fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card occupancy-rate">
                <div class="d-flex justify-content-between align-items-start">
                        <div>
                        <h6 class="card-title">Average Occupancy</h6>
                        <h2 class="mt-3 mb-2">
                                <?php
                                $totalOccupancy = array_sum(array_column($properties, 'occupancy_rate'));
                                $avgOccupancy = $properties ? round($totalOccupancy / count($properties), 1) : 0;
                                echo $avgOccupancy . '%';
                                ?>
                            </h2>
                        <p class="mb-0 text-muted">Across all properties</p>
                        </div>
                        <div class="stats-icon">
                        <i class="bi bi-person-check fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card monthly-income">
                <div class="d-flex justify-content-between align-items-start">
                        <div>
                        <h6 class="card-title">Total Monthly Income</h6>
                        <h2 class="mt-3 mb-2">
                                <?php
                                $totalIncome = array_sum(array_column($properties, 'monthly_income'));
                                echo 'Ksh' . number_format($totalIncome, 2);
                                ?>
                            </h2>
                        <p class="mb-0 text-muted">Expected monthly revenue</p>
                        </div>
                        <div class="stats-icon">
                        <i class="bi bi-cash-stack fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Properties List -->
    <div class="card">
        <div class="card-header border-bottom d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Property List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable" id="propertiesTable" data-order='[[0, "asc"]]'>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Units</th>
                            <th style="min-width: 150px;">Occupancy</th>
                        <th>Monthly Income</th>
                            <th class="no-sort" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $property): ?>
                        <tr data-id="<?= $property['id'] ?>">
                        <td>
                                <a href="#" 
                                   class="text-decoration-none text-primary fw-medium"
                                   onclick="viewProperty(<?= $property['id'] ?>); return false;">
                                <?= htmlspecialchars($property['name']) ?>
                            </a>
                        </td>
                        <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt text-muted me-2"></i>
                                    <div class="text-truncate" style="max-width: 200px;">
                                <?= htmlspecialchars($property['address']) ?><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($property['city']) ?>, 
                                            <?= htmlspecialchars($property['state']) ?> 
                                            <?= htmlspecialchars($property['zip_code']) ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary rounded-pill">
                                    <?= $property['units_count'] ?> units
                            </span>
                        </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $property['occupancy_rate'] >= 80 ? 'bg-success' : ($property['occupancy_rate'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                             role="progressbar" 
                                     style="width: <?= $property['occupancy_rate'] ?>%;"
                                     aria-valuenow="<?= $property['occupancy_rate'] ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="text-muted small" style="min-width: 45px;">
                                    <?= round($property['occupancy_rate'], 1) ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium text-success">
                                    Ksh<?= number_format($property['monthly_income'], 2) ?>
                                </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" 
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="tooltip"
                                    title="View Details"
                                    onclick="viewProperty(<?= $property['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" 
                                    class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="tooltip"
                                    title="Edit Property"
                                    onclick="editProperty(<?= $property['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" 
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="tooltip"
                                    title="Delete Property"
                                    onclick="deleteProperty(<?= $property['id'] ?>)">
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

<!-- Add Property Modal -->
<div class="modal fade" id="addPropertyModal" tabindex="-1" aria-labelledby="addPropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addPropertyForm" method="POST" action="<?= BASE_URL ?>/properties/store" enctype="multipart/form-data" onsubmit="return handlePropertySubmit(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPropertyModalLabel">Add New Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Property Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Property Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="mb-3">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" required pattern="[0-9]{5}(-[0-9]{4})?">
                            </div>
                            <div class="mb-3">
                                <label for="property_type" class="form-label">Property Type</label>
                                <select class="form-select" id="property_type" name="property_type" required>
                                    <option value="">Select Type</option>
                                    <option value="apartment">Apartment Building</option>
                                    <option value="house">Single Family House</option>
                                    <option value="commercial">Commercial Property</option>
                                    <option value="condo">Condominium</option>
                                </select>
                            </div>
                        </div>

                        <!-- Units Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Units</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addUnitField()">
                                        <i class="bi bi-plus-lg"></i> Add Unit
                                    </button>
                                </label>
                                <div id="unitsContainer">
                                    <div class="unit-entry mb-2">
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">Unit #</span>
                                            <input type="text" class="form-control" name="units[0][number]" placeholder="Number" required>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">Type</span>
                                            <select class="form-select" name="units[0][type]" required>
                                                <option value="">Select Type</option>
                                                <option value="studio">Studio</option>
                                                <option value="1bhk">1 BHK</option>
                                                <option value="2bhk">2 BHK</option>
                                                <option value="3bhk">3 BHK</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <span class="input-group-text">Size (sq ft)</span>
                                            <input type="number" step="0.01" class="form-control" name="units[0][size]" placeholder="Size">
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text">Rent Ksh</span>
                                            <input type="number" step="0.01" class="form-control" name="units[0][rent]" placeholder="Monthly Rent" required>
                                            <button type="button" class="btn btn-outline-danger" onclick="removeUnitField(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Property Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="caretaker_employee_id" class="form-label">Caretaker</label>
                                <select class="form-select" id="caretaker_employee_id" name="caretaker_employee_id">
                                    <option value="">None</option>
                                    <?php if (!empty($caretakers)):
                                        foreach ($caretakers as $ck): ?>
                                            <option value="<?= $ck['id'] ?>" data-name="<?= htmlspecialchars($ck['name']) ?>" data-contact="<?= htmlspecialchars($ck['phone'] ?: ($ck['email'] ?? '')) ?>">
                                                <?= htmlspecialchars($ck['name']) ?><?= isset($ck['property_name']) && $ck['property_name'] ? ' • ' . htmlspecialchars($ck['property_name']) : '' ?>
                                            </option>
                                    <?php endforeach; endif; ?>
                                </select>
                                <div class="form-text">Caretakers are managed under Employees. Set an employee's role to "Caretaker" to list here.</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="year_built" class="form-label">Year Built</label>
                                        <input type="number" class="form-control" id="year_built" name="year_built">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="total_area" class="form-label">Total Area (sq ft)</label>
                                        <input type="number" class="form-control" id="total_area" name="total_area">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Images and Documents Upload -->
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_images" class="form-label">Property Images</label>
                                        <input type="file" class="form-control" id="property_images" name="property_images[]" 
                                               multiple accept="image/*" onchange="previewImages(this, 'image-preview')">
                                        <div class="form-text">Upload multiple images of the building (JPG, PNG, GIF, WebP - Max 5MB each)</div>
                                        <div id="image-preview" class="mt-2 row g-2"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_documents" class="form-label">Property Documents</label>
                                        <input type="file" class="form-control" id="property_documents" name="property_documents[]" 
                                               multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" onchange="previewDocuments(this, 'document-preview')">
                                        <div class="form-text">Upload property documents (PDF, DOC, XLS, TXT, CSV - Max 10MB each)</div>
                                        <div id="document-preview" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Property Modal -->
<div class="modal fade" id="editPropertyModal" tabindex="-1" aria-labelledby="editPropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editPropertyForm" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPropertyModalLabel">Edit Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Property Information -->
                        <div class="col-md-6">
                            <input type="hidden" name="id" id="edit_property_id">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Property Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_address" class="form-label">Address</label>
                                <textarea class="form-control" id="edit_address" name="address" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="edit_city" name="city" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="edit_state" name="state" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="edit_zip_code" name="zip_code" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_property_type" class="form-label">Property Type</label>
                                <select class="form-select" id="edit_property_type" name="property_type" required>
                                    <option value="apartment">Apartment</option>
                                    <option value="house">House</option>
                                    <option value="commercial">Commercial</option>
                                    <option value="condo">Condo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_caretaker_employee_id" class="form-label">Caretaker</label>
                                <select class="form-select" id="edit_caretaker_employee_id" name="caretaker_employee_id">
                                    <option value="">None</option>
                                    <?php if (!empty($caretakers)):
                                        foreach ($caretakers as $ck): ?>
                                            <option value="<?= $ck['id'] ?>" data-name="<?= htmlspecialchars($ck['name']) ?>" data-contact="<?= htmlspecialchars($ck['phone'] ?: ($ck['email'] ?? '')) ?>">
                                                <?= htmlspecialchars($ck['name']) ?><?= isset($ck['property_name']) && $ck['property_name'] ? ' • ' . htmlspecialchars($ck['property_name']) : '' ?>
                                            </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_year_built" class="form-label">Year Built</label>
                                <input type="number" class="form-control" id="edit_year_built" name="year_built">
                            </div>
                            <div class="mb-3">
                                <label for="edit_total_area" class="form-label">Total Area (sq ft)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_total_area" name="total_area">
                            </div>
                        </div>

                        <!-- Images and Documents for Edit -->
                        <div class="col-12">
                            <!-- Existing Files Display -->
                            <div id="existing-files-section" class="mb-4">
                                <h6>Current Files</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Images</label>
                                        <div id="existing-images" class="row g-2 mb-3">
                                            <!-- Existing images will be loaded here -->
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Current Documents</label>
                                        <div id="existing-documents" class="mb-3">
                                            <!-- Existing documents will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Files Upload -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_property_images" class="form-label">Add New Images</label>
                                        <input type="file" class="form-control" id="edit_property_images" name="property_images[]" 
                                               multiple accept="image/*" onchange="previewImages(this, 'edit-image-preview')">
                                        <div class="form-text">Upload additional images (JPG, PNG, GIF, WebP - Max 5MB each)</div>
                                        <div id="edit-image-preview" class="mt-2 row g-2"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_property_documents" class="form-label">Add New Documents</label>
                                        <input type="file" class="form-control" id="edit_property_documents" name="property_documents[]" 
                                               multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" onchange="previewDocuments(this, 'edit-document-preview')">
                                        <div class="form-text">Upload additional documents (PDF, DOC, XLS, TXT, CSV - Max 10MB each)</div>
                                        <div id="edit-document-preview" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

<!-- View Property Modal -->
<div class="modal fade" id="viewPropertyModal" tabindex="-1" aria-labelledby="viewPropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPropertyModalLabel">Property Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <!-- Property Information -->
                    <div class="col-12 col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Property Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="30%">Name:</th>
                                        <td id="view_name"></td>
                                    </tr>
                                    <tr>
                                        <th>Address:</th>
                                        <td id="view_address"></td>
                                    </tr>
                                    <tr>
                                        <th>City:</th>
                                        <td id="view_city"></td>
                                    </tr>
                                    <tr>
                                        <th>State:</th>
                                        <td id="view_state"></td>
                                    </tr>
                                    <tr>
                                        <th>ZIP Code:</th>
                                        <td id="view_zip_code"></td>
                                    </tr>
                                    <tr>
                                        <th>Property Type:</th>
                                        <td id="view_property_type"></td>
                                    </tr>
                                    <tr>
                                        <th>Caretaker:</th>
                                        <td id="view_caretaker_name"></td>
                                    </tr>
                                    <tr>
                                        <th>Caretaker Contact:</th>
                                        <td id="view_caretaker_contact"></td>
                                    </tr>
                                    <tr>
                                        <th>Year Built:</th>
                                        <td id="view_year_built"></td>
                                    </tr>
                                    <tr>
                                        <th>Total Area:</th>
                                        <td id="view_total_area"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Property Statistics -->
                    <div class="col-12 col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Property Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6 col-md-6">
                                        <div class="stat-card">
                                            <h6 class="card-subtitle mb-2 text-muted">Total Units</h6>
                                            <h2 id="view_total_units">0</h2>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-6">
                                        <div class="stat-card">
                                            <h6 class="card-subtitle mb-2 text-muted">Occupancy Rate</h6>
                                            <h2 id="view_occupancy_rate">0%</h2>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-6">
                                        <div class="stat-card">
                                            <h6 class="card-subtitle mb-2 text-muted">Monthly Income</h6>
                                            <h2 id="view_monthly_income">Ksh0</h2>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-6">
                                        <div class="stat-card">
                                            <h6 class="card-subtitle mb-2 text-muted">Vacant Units</h6>
                                            <h2 id="view_vacant_units">0</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Property Files -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Property Images & Documents</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <h6>Images</h6>
                                        <div id="view-property-images" class="row g-2 mb-3">
                                            <!-- Property images will be loaded here -->
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <h6>Documents</h6>
                                        <div id="view-property-documents">
                                            <!-- Property documents will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Units List -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <h5 class="card-title mb-0">Units</h5>
                                <button type="button" class="btn btn-primary btn-sm w-100 w-sm-auto" onclick="showAddUnitModal()">
                                    <i class="bi bi-plus-lg"></i> Add Unit
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="unitsTable">
                                        <thead>
                                            <tr>
                                                <th>Unit Number</th>
                                                <th>Type</th>
                                <th>Size</th>
                                                <th>Rent</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="view_units_list">
                                            <!-- Units will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editProperty(currentPropertyId)">
                    <i class="bi bi-pencil"></i> Edit Property
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addUnitForm" onsubmit="return handleUnitSubmit(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitModalLabel">Add New Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="unit_property_id" name="property_id">
                    <div class="mb-3">
                        <label for="unit_number" class="form-label">Unit Number</label>
                        <input type="text" class="form-control" id="unit_number" name="unit_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_type" class="form-label">Type</label>
                        <select class="form-select" id="unit_type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="studio">Studio</option>
                            <option value="1bhk">1 BHK</option>
                            <option value="2bhk">2 BHK</option>
                            <option value="3bhk">3 BHK</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="unit_size" class="form-label">Size (sq ft)</label>
                        <input type="number" step="0.01" class="form-control" id="unit_size" name="size">
                    </div>
                    <div class="mb-3">
                        <label for="unit_rent" class="form-label">Monthly Rent</label>
                        <input type="number" step="0.01" class="form-control" id="unit_rent" name="rent_amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_status" class="form-label">Status</label>
                        <select class="form-select" id="unit_status" name="status" required>
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                        </select>
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
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUnitForm" method="POST" onsubmit="return handleUnitEdit(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUnitModalLabel">Edit Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUnitId" name="id">
                    <div class="mb-3">
                        <label for="editUnitNumber" class="form-label">Unit Number</label>
                        <input type="text" class="form-control" id="editUnitNumber" name="unit_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUnitType" class="form-label">Type</label>
                        <select class="form-select" id="editUnitType" name="type" required>
                            <option value="">Select Type</option>
                            <option value="studio">Studio</option>
                            <option value="1bhk">1 BHK</option>
                            <option value="2bhk">2 BHK</option>
                            <option value="3bhk">3 BHK</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editUnitSize" class="form-label">Size (sq ft)</label>
                        <input type="number" step="0.01" class="form-control" id="editUnitSize" name="size">
                    </div>
                    <div class="mb-3">
                        <label for="editUnitRent" class="form-label">Monthly Rent (Ksh)</label>
                        <input type="number" step="0.01" class="form-control" id="editUnitRent" name="rent_amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUnitStatus" class="form-label">Status</label>
                        <select class="form-select" id="editUnitStatus" name="status" required>
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                        </select>
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

<!-- Delete Property Confirmation Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this property? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePropertyBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Success modals handled by SweetAlert in app.js -->

<!-- Page Specific JavaScript -->
<script type="text/javascript">
// BASE_URL is already defined in app.js, no need to redeclare
// formatCurrency is already defined in app.js, no need to redeclare

// showAlert is already defined in app.js with SweetAlert2, no need to redeclare

const removeLoadingAlerts = () => {
    // SweetAlert2 toasts are not regular DOM .alert elements; avoid removing page banners.
};

// Global Variables
let currentPropertyId = null;
let propertyIdToDelete = null;

document.addEventListener('DOMContentLoaded', () => {
    try {
        const params = new URLSearchParams(window.location.search || '');
        const editId = parseInt(params.get('edit') || '0', 10);
        if (editId && typeof editProperty === 'function') {
            editProperty(editId);
        }
    } catch (e) {
    }
});

// Property Management Functions
const viewProperty = async (id) => {
    try {
        currentPropertyId = id;
        const loadingAlert = showAlert('info', 'Loading property details...', false);
        
        const response = await fetch(`${BASE_URL}/properties/get/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }

        const data = await response.json();
        removeLoadingAlerts();

        if (!data.success) {
            throw new Error(data.message || 'Error loading property details');
        }

        const property = data.property;
        
        // Update property details
        document.getElementById('view_name').textContent = property.name || 'N/A';
        document.getElementById('view_address').textContent = property.address || 'N/A';
        document.getElementById('view_city').textContent = property.city || 'N/A';
        document.getElementById('view_state').textContent = property.state || 'N/A';
        document.getElementById('view_zip_code').textContent = property.zip_code || 'N/A';
        document.getElementById('view_property_type').textContent = 
            property.property_type ? property.property_type.charAt(0).toUpperCase() + property.property_type.slice(1) : 'N/A';
        // Caretaker details
        const vcName = document.getElementById('view_caretaker_name');
        if (vcName) vcName.textContent = property.caretaker_name || 'N/A';
        const vcContact = document.getElementById('view_caretaker_contact');
        if (vcContact) vcContact.textContent = property.caretaker_contact || 'N/A';
        document.getElementById('view_year_built').textContent = property.year_built || 'N/A';
        document.getElementById('view_total_area').textContent = property.total_area ? `${property.total_area} sq ft` : 'N/A';
        
        // Update statistics
        document.getElementById('view_total_units').textContent = property.units_count || 0;
        document.getElementById('view_occupancy_rate').textContent = `${Math.round(property.occupancy_rate || 0)}%`;
        document.getElementById('view_monthly_income').textContent = formatCurrency(property.monthly_income || 0);
        document.getElementById('view_vacant_units').textContent = 
            property.units_count ? (property.units_count - Math.round(property.units_count * ((property.occupancy_rate || 0) / 100))) : 0;

        // Show the modal
        const viewModal = new bootstrap.Modal(document.getElementById('viewPropertyModal'));
        viewModal.show();
        
        // Load units for this property
        await loadPropertyUnits(property.id);
        
        // Load property files
        await loadPropertyFiles(property.id);
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', error.message || 'Error loading property details');
    }
};

const loadPropertyUnits = async (propertyId) => {
    try {
        console.log('Loading units for property:', propertyId);
        console.log('Request URL:', `${BASE_URL}/properties/${propertyId}/units`);
        console.log('BASE_URL:', BASE_URL);

        const loadingAlert = showAlert('info', 'Loading units...', false);

        const response = await fetch(`${BASE_URL}/properties/${propertyId}/units`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (!response.ok) {
            const text = await response.text();
            console.error('Response text:', text);
            throw new Error('Network response was not ok: ' + response.status);
        }

        const data = await response.json();
        console.log('Response data:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Error loading units');
        }

        const unitsList = document.getElementById('view_units_list');
        if (!unitsList) {
            throw new Error('Units list element not found');
        }

        unitsList.innerHTML = '';
        
        if (data.units && data.units.length > 0) {
            console.log('Found units:', data.units);
            data.units.forEach(unit => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${unit.unit_number || 'N/A'}</td>
                    <td>${unit.type ? (unit.type.charAt(0).toUpperCase() + unit.type.slice(1)) : 'N/A'}</td>
                    <td>${unit.size ? unit.size + ' sq ft' : 'N/A'}</td>
                    <td>${formatCurrency(unit.rent_amount || 0)}</td>
                    <td>
                        <span class="badge bg-${unit.status === 'occupied' ? 'success' : 'warning'}">
                            ${unit.status || 'vacant'}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUnit(${unit.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUnit(${unit.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                unitsList.appendChild(row);
            });
        } else {
            console.log('No units found');
            unitsList.innerHTML = '<tr><td colspan="6" class="text-center">No units found</td></tr>';
        }

        removeLoadingAlerts();
    } catch (error) {
        console.error('Error in loadPropertyUnits:', error);
        console.error('Error stack:', error.stack);
        showAlert('error', error.message || 'Error loading units');
        removeLoadingAlerts();
    }
};

const editProperty = async (id) => {
    try {
        // If called from view modal, close it
        const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewPropertyModal'));
        if (viewModal) {
            viewModal.hide();
        }
        
        // Show loading state
        const loadingAlert = showAlert('info', 'Loading property details...', false);
        
        const response = await fetch(`${BASE_URL}/properties/get/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }

        const data = await response.json();
        removeLoadingAlerts();

        if (!data.success) {
            throw new Error(data.message || 'Error loading property details');
        }

        const property = data.property;
        
        // Populate form fields
        document.getElementById('edit_property_id').value = property.id;
        document.getElementById('edit_name').value = property.name;
        document.getElementById('edit_address').value = property.address;
        document.getElementById('edit_city').value = property.city;
        document.getElementById('edit_state').value = property.state;
        document.getElementById('edit_zip_code').value = property.zip_code;
        document.getElementById('edit_property_type').value = property.property_type;
        document.getElementById('edit_description').value = property.description || '';
        const editCaretakerSelect = document.getElementById('edit_caretaker_employee_id');
        if (editCaretakerSelect) {
            const propCaretakerName = property.caretaker_name || '';
            const propCaretakerContact = property.caretaker_contact || '';
            let matched = false;
            for (const opt of editCaretakerSelect.options) {
                if (opt.value && (opt.dataset.name === propCaretakerName || opt.dataset.contact === propCaretakerContact)) {
                    editCaretakerSelect.value = opt.value;
                    matched = true;
                    break;
                }
            }
            if (!matched) editCaretakerSelect.value = '';
        }
        document.getElementById('edit_year_built').value = property.year_built || '';
        document.getElementById('edit_total_area').value = property.total_area || '';
        
        // Update form action
        document.getElementById('editPropertyForm').action = `${BASE_URL}/properties/update/${property.id}`;
        
        // Show the modal
        const editModal = new bootstrap.Modal(document.getElementById('editPropertyModal'));
        editModal.show();
        
        // Load existing files for editing
        await loadPropertyFilesForEdit(property.id);
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', error.message || 'Error loading property details');
        removeLoadingAlerts();
    }
};

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

// Load property files for viewing
async function loadPropertyFiles(propertyId) {
    try {
        const response = await fetch(`${BASE_URL}/properties/${propertyId}/files`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load property files');
        }

        const data = await response.json();
        
        if (data.success) {
            displayPropertyImages(data.images || [], 'view-property-images');
            displayPropertyDocuments(data.documents || [], 'view-property-documents');
        }
    } catch (error) {
        console.error('Error loading property files:', error);
    }
}

// Load property files for editing
async function loadPropertyFilesForEdit(propertyId) {
    try {
        const response = await fetch(`${BASE_URL}/properties/${propertyId}/files`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load property files');
        }

        const data = await response.json();
        
        if (data.success) {
            displayPropertyImages(data.images || [], 'existing-images', true);
            displayPropertyDocuments(data.documents || [], 'existing-documents', true);
        }
    } catch (error) {
        console.error('Error loading property files for edit:', error);
    }
}

// Display property images
function displayPropertyImages(images, containerId, allowDelete = false) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    
    if (images.length === 0) {
        container.innerHTML = '<p class="text-muted">No images uploaded</p>';
        return;
    }
    
    images.forEach(image => {
        const col = document.createElement('div');
        col.className = 'col-md-4 col-sm-6 col-6';
        
        // Truncate very long filenames for display
        const displayName = image.original_name.length > 25 
            ? image.original_name.substring(0, 22) + '...' 
            : image.original_name;
        
        col.innerHTML = `
            <div class="position-relative">
                <img src="${image.url}" class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover;" 
                     onclick="openImageModal('${image.url}', '${image.original_name}')" style="cursor: pointer;">
                ${allowDelete ? `
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                            onclick="deletePropertyFile(${image.id}, 'image')" style="padding: 2px 6px;">
                        <i class="bi bi-x"></i>
                    </button>
                ` : ''}
                <div class="small text-truncate mt-1" title="${image.original_name}">${displayName}</div>
            </div>
        `;
        container.appendChild(col);
    });
}

// Display property documents
function displayPropertyDocuments(documents, containerId, allowDelete = false) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    
    if (documents.length === 0) {
        container.innerHTML = '<p class="text-muted">No documents uploaded</p>';
        return;
    }
    
    documents.forEach(doc => {
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
                        onclick="deletePropertyFile(${doc.id}, 'document')">
                    <i class="bi bi-trash"></i>
                </button>
            ` : ''}
        `;
        container.appendChild(fileItem);
    });
}

// Delete property file
async function deletePropertyFile(fileId, fileType) {
    if (!confirm('Are you sure you want to delete this file?')) {
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/files/delete/${fileId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'File deleted successfully');
            // Reload the files
            const propertyId = document.getElementById('edit_property_id').value;
            if (propertyId) {
                await loadPropertyFilesForEdit(propertyId);
            }
        } else {
            showAlert('error', data.message || 'Failed to delete file');
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        showAlert('error', 'Error deleting file');
    }
}

// Unit field management functions
let unitFieldCounter = 1;

function addUnitField() {
    const container = document.getElementById('unitsContainer');
    const newUnitField = document.createElement('div');
    newUnitField.className = 'unit-entry mb-2';
    newUnitField.innerHTML = `
        <div class="input-group mb-2">
            <span class="input-group-text">Unit #</span>
            <input type="text" class="form-control" name="units[${unitFieldCounter}][number]" placeholder="Number" required>
        </div>
        <div class="input-group mb-2">
            <span class="input-group-text">Type</span>
            <select class="form-select" name="units[${unitFieldCounter}][type]" required>
                <option value="">Select Type</option>
                <option value="studio">Studio</option>
                <option value="1bhk">1 BHK</option>
                <option value="2bhk">2 BHK</option>
                <option value="3bhk">3 BHK</option>
                <option value="other">Other</option>
            </select>
            <span class="input-group-text">Size (sq ft)</span>
            <input type="number" step="0.01" class="form-control" name="units[${unitFieldCounter}][size]" placeholder="Size">
        </div>
        <div class="input-group">
            <span class="input-group-text">Rent Ksh</span>
            <input type="number" step="0.01" class="form-control" name="units[${unitFieldCounter}][rent]" placeholder="Monthly Rent" required>
            <button type="button" class="btn btn-outline-danger" onclick="removeUnitField(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newUnitField);
    unitFieldCounter++;
}

function removeUnitField(button) {
    const unitEntry = button.closest('.unit-entry');
    if (unitEntry) {
        unitEntry.remove();
    }
}

// Open image in modal
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
};

document.querySelectorAll('form.import-form').forEach((form) => {
    form.addEventListener('submit', () => {
        const overlay = document.getElementById('importLoadingOverlay');
        if (overlay) overlay.style.display = 'block';
        const btn = form.querySelector('.import-submit-btn');
        if (btn) {
            btn.disabled = true;
        }
    });
});
</script>

<style>
/* Mobile Responsive Styles for View Property Modal */
@media (max-width: 768px) {
    #viewPropertyModal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    
    #viewPropertyModal .modal-body {
        padding: 1rem;
    }
    
    #viewPropertyModal .stat-card {
        padding: 0.75rem;
        text-align: center;
    }
    
    #viewPropertyModal .stat-card h2 {
        font-size: 1.5rem;
    }
    
    #viewPropertyModal .stat-card h6 {
        font-size: 0.75rem;
    }
    
    #viewPropertyModal .card {
        margin-bottom: 1rem !important;
    }
    
    #viewPropertyModal .table {
        font-size: 0.875rem;
    }
    
    #viewPropertyModal .table th {
        width: 35%;
        font-size: 0.8rem;
    }
    
    #viewPropertyModal .table td {
        font-size: 0.85rem;
        word-break: break-word;
    }
    
    /* Make action buttons stack on mobile */
    #viewPropertyModal .btn-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    #viewPropertyModal .btn-group .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    #viewPropertyModal .modal-title {
        font-size: 1.1rem;
    }
    
    #viewPropertyModal .stat-card h2 {
        font-size: 1.25rem;
    }
    
    #viewPropertyModal .card-header h5 {
        font-size: 1rem;
    }
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';