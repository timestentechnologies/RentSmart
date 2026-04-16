<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title><?php echo htmlspecialchars($property['name']); ?> | Airbnb Stays | <?php echo htmlspecialchars($siteName ?? 'RentSmart'); ?></title>
    
    <?php $faviconUrl = site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png'); ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($faviconUrl) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6B3E99;
            --secondary-color: #8E5CC4;
            --accent-color: #FF8A00;
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark-color);
            background-color: #fbfbff;
        }
        .navbar {
            box-shadow: 0 2px 15px rgba(107, 62, 153, 0.1);
        }
        .property-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .property-carousel img {
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            object-fit: cover;
            width: 100%;
        }
        .unit-card {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            background: white;
            height: 100%;
        }
        .unit-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(107, 62, 153, 0.1);
        }
        .sidebar-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
            background: white;
        }
        .btn-brand {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            border-radius: 14px;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(107, 62, 153, 0.25);
            color: white;
        }
        .section-title {
            font-weight: 800;
            position: relative;
            padding-bottom: 0.75rem;
            margin-bottom: 2rem;
        }
        .section-title::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }
        .amenity-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 138, 0, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            margin-right: 0.75rem;
        }
        .text-accent { color: var(--accent-color) !important; }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../partials/public_header.php'; ?>

    <div class="container property-container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <!-- Property Header -->
                <div class="mb-4">
                    <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($property['name']); ?></h1>
                    <p class="text-muted mb-0">
                        <i class="bi bi-geo-alt-fill text-accent me-2"></i>
                        <?php echo htmlspecialchars(($property['address'] ?? '') . ', ' . ($property['city'] ?? '')); ?>
                    </p>
                </div>

                <!-- Property Images -->
                <?php if (!empty($property['images'])): ?>
                <div id="propertyCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
                    <div class="carousel-inner rounded-4 overflow-hidden">
                        <?php foreach ($property['images'] as $index => $image): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($image['path']); ?>" 
                                 class="d-block w-100" style="height: 450px; object-fit: cover;" 
                                 alt="Property Image">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($property['images']) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon shadow-sm" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon shadow-sm" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Property Description -->
                <?php if (!empty($property['description'])): ?>
                <div class="mb-5">
                    <h3 class="section-title">About this property</h3>
                    <p class="text-muted lead" style="line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($property['description'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Available Units -->
                <h3 class="section-title">Available Units</h3>
                <?php if (!empty($units)): ?>
                    <div class="row g-4">
                        <?php foreach ($units as $unit): ?>
                        <div class="col-md-6">
                            <div class="card unit-card">
                                <div class="position-relative">
                                    <?php if (!empty($unit['images'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($unit['images'][0]['path']); ?>" 
                                         class="card-img-top" style="height: 220px; object-fit: cover;" 
                                         alt="Unit Image">
                                    <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 220px;">
                                        <i class="bi bi-door-open text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div class="position-absolute bottom-0 end-0 p-3">
                                        <span class="badge bg-white text-dark shadow-sm rounded-pill px-3 py-2">
                                            KES <?php echo number_format($unit['base_price_per_night'] ?? $unit['rent_amount'] ?? 0); ?> / night
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title fw-bold mb-0">Unit <?php echo htmlspecialchars($unit['unit_number']); ?></h5>
                                        <span class="badge bg-light text-primary"><?php echo htmlspecialchars($unit['type'] ?? 'Standard'); ?></span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="amenity-icon">
                                            <i class="fas fa-expand-arrows-alt"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Size</small>
                                            <span class="fw-bold"><?php echo !empty($unit['size']) ? $unit['size'] . ' sq ft' : 'Private Room'; ?></span>
                                        </div>
                                    </div>

                                    <a href="<?php echo BASE_URL; ?>/airbnb/book?unit_id=<?php echo $unit['id']; ?>" 
                                       class="btn btn-brand w-100 py-3">
                                        <i class="bi bi-calendar-check me-2"></i>Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 bg-light rounded-4">
                        <i class="bi bi-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No units available at the moment</h5>
                        <a href="<?= BASE_URL ?>/airbnb" class="btn btn-outline-primary mt-3">Browse Other Properties</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card sidebar-card p-4">
                    <h4 class="fw-bold mb-4">Property Stats</h4>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="amenity-icon">
                            <i class="bi bi-houses"></i>
                        </div>
                        <div>
                            <span class="fw-bold d-block"><?php echo count($units); ?></span>
                            <small class="text-muted">Available Rooms</small>
                        </div>
                    </div>

                    <?php if (!empty($airbnbSettings['check_in_time'])): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="amenity-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <span class="fw-bold d-block"><?php echo date('g:i A', strtotime($airbnbSettings['check_in_time'])); ?></span>
                            <small class="text-muted">Check-in After</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($airbnbSettings['check_out_time'])): ?>
                    <div class="d-flex align-items-center mb-4">
                        <div class="amenity-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <span class="fw-bold d-block"><?php echo date('g:i A', strtotime($airbnbSettings['check_out_time'])); ?></span>
                            <small class="text-muted">Check-out Before</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr class="my-4 opacity-50">

                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <div class="list-group list-group-flush gap-2 border-0">
                        <a href="<?= BASE_URL ?>/airbnb" class="list-group-item list-group-item-action border-0 rounded-3 small">
                            <i class="bi bi-arrow-left me-2"></i>Back to All Stays
                        </a>
                        <a href="<?= BASE_URL ?>/contact" class="list-group-item list-group-item-action border-0 rounded-3 small">
                            <i class="bi bi-question-circle me-2"></i>Ask a Question
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/public_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
