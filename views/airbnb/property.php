<?php 
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
include 'views/partials/header.php'; 
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Property Header -->
            <h1 class="mb-3"><?php echo htmlspecialchars($property['name']); ?></h1>
            <p class="text-muted">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars(($property['address'] ?? '') . ', ' . ($property['city'] ?? '')); ?>
            </p>

            <!-- Property Images -->
            <?php if (!empty($property['images'])): ?>
            <div id="propertyCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                <div class="carousel-inner rounded">
                    <?php foreach ($property['images'] as $index => $image): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($image['path']); ?>" 
                             class="d-block w-100" style="height: 400px; object-fit: cover;" 
                             alt="Property Image">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($property['images']) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Available Units -->
            <h3 class="mb-3">Available Units</h3>
            <?php if (!empty($units)): ?>
                <div class="row">
                    <?php foreach ($units as $unit): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($unit['images'])): ?>
                            <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($unit['images'][0]['path']); ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;" 
                                 alt="Unit Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title">Unit <?php echo htmlspecialchars($unit['unit_number']); ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-info"><?php echo htmlspecialchars($unit['type'] ?? 'Unknown'); ?></span>
                                    <?php if (!empty($unit['size'])): ?>
                                    <span class="badge bg-secondary"><?php echo $unit['size']; ?> sq ft</span>
                                    <?php endif; ?>
                                </p>
                                <p class="card-text">
                                    <strong class="text-primary">
                                        KES <?php echo number_format($unit['base_price_per_night'] ?? $unit['rent_amount'] ?? 0, 2); ?> 
                                        / night
                                    </strong>
                                </p>
                                <a href="<?php echo BASE_URL; ?>/airbnb/book?unit_id=<?php echo $unit['id']; ?>" 
                                   class="btn btn-primary btn-block">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No units available at the moment.
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Property Details</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-home text-primary"></i>
                            <strong><?php echo count($units); ?></strong> units available
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            <?php echo htmlspecialchars($property['city'] ?? ''); ?>
                        </li>
                        <?php if (!empty($airbnbSettings['check_in_time'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-clock text-primary"></i>
                            Check-in: <?php echo substr($airbnbSettings['check_in_time'], 0, 5); ?>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($airbnbSettings['check_out_time'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-clock text-primary"></i>
                            Check-out: <?php echo substr($airbnbSettings['check_out_time'], 0, 5); ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/partials/footer.php'; 
} catch (\Throwable $e) {
    echo '<h2>View Error:</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
?>
