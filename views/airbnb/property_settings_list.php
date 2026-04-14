<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Airbnb Property Settings</h1>
            <a href="<?php echo BASE_URL; ?>/airbnb/dashboard" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($properties)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No properties found</h5>
                    <p class="text-muted">You don't have access to any properties yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Location</th>
                                <th>Airbnb Status</th>
                                <th>Min/Max Stay</th>
                                <th>Check-in/out</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $property): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($property['name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($property['address']); ?>, 
                                    <?php echo htmlspecialchars($property['city']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($property['airbnb_settings']['is_airbnb_enabled'])): ?>
                                        <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($property['airbnb_settings'])): ?>
                                        <?php echo $property['airbnb_settings']['min_stay_nights']; ?> - 
                                        <?php echo $property['airbnb_settings']['max_stay_nights']; ?> nights
                                    <?php else: ?>
                                        <span class="text-muted">Not configured</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($property['airbnb_settings'])): ?>
                                        <?php echo substr($property['airbnb_settings']['check_in_time'], 0, 5); ?> / 
                                        <?php echo substr($property['airbnb_settings']['check_out_time'], 0, 5); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/airbnb/property-settings/<?php echo $property['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-cog"></i> Configure
                                    </a>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
