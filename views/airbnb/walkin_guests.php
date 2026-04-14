<?php include 'views/layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Walk-in Guest Management</h2>
        <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Walk-in Guest
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/airbnb/walkin-guests" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="inquiry" <?php echo ($_GET['status'] ?? '') === 'inquiry' ? 'selected' : ''; ?>>Inquiry</option>
                        <option value="offered" <?php echo ($_GET['status'] ?? '') === 'offered' ? 'selected' : ''; ?>>Offered</option>
                        <option value="converted" <?php echo ($_GET['status'] ?? '') === 'converted' ? 'selected' : ''; ?>>Converted</option>
                        <option value="declined" <?php echo ($_GET['status'] ?? '') === 'declined' ? 'selected' : ''; ?>>Declined</option>
                        <option value="no_show" <?php echo ($_GET['status'] ?? '') === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        <?php foreach ($properties as $property): ?>
                        <option value="<?php echo $property['id']; ?>" <?php echo ($_GET['property_id'] ?? '') == $property['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($property['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Walk-in Guests Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($guests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-walking fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No walk-in guests found</h5>
                    <p class="text-muted">Add a walk-in guest inquiry to get started</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Property</th>
                                <th>Preferred Dates</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Follow-up</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guests as $guest): ?>
                            <tr class="<?php echo ($guest['status'] === 'inquiry' && !empty($guest['follow_up_date']) && strtotime($guest['follow_up_date']) < time()) ? 'table-warning' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($guest['guest_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($guest['guest_phone']); ?></small><br>
                                    <small class="text-muted"><?php echo $guest['guest_count']; ?> guest(s)</small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($guest['property_name']); ?><br>
                                    <?php if ($guest['unit_number']): ?>
                                    <small class="text-muted">Assigned: <?php echo htmlspecialchars($guest['unit_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($guest['preferred_check_in']): ?>
                                    <small class="text-muted">In:</small> <?php echo date('M d', strtotime($guest['preferred_check_in'])); ?><br>
                                    <small class="text-muted">Out:</small> <?php echo date('M d', strtotime($guest['preferred_check_out'])); ?>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $guest['budget_range'] ? htmlspecialchars($guest['budget_range']) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $guest['status'] === 'converted' ? 'success' : 
                                            ($guest['status'] === 'inquiry' ? 'warning' : 
                                            ($guest['status'] === 'offered' ? 'primary' : 
                                            ($guest['status'] === 'declined' ? 'secondary' : 'danger'))); 
                                    ?>">
                                        <?php echo ucfirst($guest['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($guest['follow_up_date']): ?>
                                        <span class="<?php echo strtotime($guest['follow_up_date']) < time() ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                            <?php echo date('M d, H:i', strtotime($guest['follow_up_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests/<?php echo $guest['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($guest['status'] !== 'converted'): ?>
                                        <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests/<?php echo $guest['id']; ?>/convert" class="btn btn-sm btn-success" title="Convert to Booking">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($guest['status'] === 'inquiry'): ?>
                                        <a href="<?php echo BASE_URL; ?>/airbnb/walkin-guests/<?php echo $guest['id']; ?>/offer" class="btn btn-sm btn-info" title="Mark as Offered">
                                            <i class="fas fa-hand-holding-usd"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
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

<?php include 'views/layouts/footer.php'; ?>
