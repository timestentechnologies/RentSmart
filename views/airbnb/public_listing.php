<?php
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
$activePage = 'airbnb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Airbnb Stays | <?= htmlspecialchars((string)$siteName) ?> | Short term rentals Kenya</title>
    <meta name="description" content="Find the best short-term stays, apartments, and rooms for rent on <?= htmlspecialchars((string)$siteName) ?>. Secure, comfortable, and affordable airbnb-style accommodations in Kenya.">
    <meta name="keywords" content="airbnb Kenya, short term rentals, holiday homes, furnished apartments, <?= htmlspecialchars((string)$siteName) ?>">

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
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 4rem 0 6rem;
            position: relative;
            overflow: hidden;
        }
        .hero-section::after {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1), transparent 40%);
            pointer-events: none;
        }
        .property-card {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s;
            background: white;
        }
        .property-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(107, 62, 153, 0.1);
        }
        .property-image {
            height: 240px;
            object-fit: cover;
            width: 100%;
        }
        .price-tag {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(107, 62, 153, 0.3);
        }
        .search-box {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(107, 62, 153, 0.12);
            margin-top: -4rem;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(107, 62, 153, 0.05);
        }
        .search-box .form-control, .search-box .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }
        .search-box .form-control:focus, .search-box .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(107, 62, 153, 0.1);
        }
        .amenity-icon {
            width: 44px;
            height: 44px;
            background: rgba(107, 62, 153, 0.08);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        .btn-brand {
            background: linear-gradient(135deg, var(--accent-color) 0%, #ff6a00 100%);
            border: none;
            color: white;
            border-radius: 14px;
            padding: 0.85rem 1.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 138, 0, 0.25);
            color: white;
        }
        .btn-outline-brand {
            border: 1.5px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-outline-brand:hover {
            background: var(--primary-color);
            color: white;
        }
        .section-title {
            position: relative;
            padding-bottom: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 800;
            color: var(--dark-color);
        }
        .section-title::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }
    </style>
    <?php if ($faviconUrl): ?>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars((string)$faviconUrl) ?>">
    <?php endif; ?>
</head>
<body>
    <?php require __DIR__ . '/../partials/public_header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-3 fw-bold mb-3">Find Your Perfect Stay</h1>
            <p class="lead mb-0 opacity-75">Discover comfortable rooms and apartments for short-term stays</p>
        </div>
    </section>

    <!-- Search Box -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-box">
                    <form action="<?= BASE_URL ?>/airbnb" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">Check-in</label>
                            <input type="date" name="check_in" class="form-control" id="checkIn" value="<?= htmlspecialchars((string)($_GET['check_in'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">Check-out</label>
                            <input type="date" name="check_out" class="form-control" id="checkOut" value="<?= htmlspecialchars((string)($_GET['check_out'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">Guests</label>
                            <select name="guests" class="form-select">
                                <option value="1" <?= ($_GET['guests'] ?? '') == '1' ? 'selected' : '' ?>>1 Guest</option>
                                <option value="2" <?= ($_GET['guests'] ?? '') == '2' ? 'selected' : '' ?>>2 Guests</option>
                                <option value="3" <?= ($_GET['guests'] ?? '') == '3' ? 'selected' : '' ?>>3 Guests</option>
                                <option value="4" <?= ($_GET['guests'] ?? '') == '4' ? 'selected' : '' ?>>4+ Guests</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-brand w-100">
                                <i class="fas fa-search me-2"></i> Search Stays
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Properties Listing -->
    <main class="py-5 mt-4">
        <div class="container">
            <?php if (isset($_SESSION['airbnb_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= $_SESSION['airbnb_error']; unset($_SESSION['airbnb_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="section-title">Available Stays</h2>
            </div>
            
            <?php if (empty($airbnbProperties)): ?>
                <div class="text-center py-5 my-5">
                    <div class="mb-4">
                        <i class="bi bi-house-door text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                    </div>
                    <h4 class="text-muted fw-bold">No Airbnb properties available at the moment</h4>
                    <p class="text-muted">Please check back later for new listings or try different dates.</p>
                    <a href="<?= BASE_URL ?>/airbnb" class="btn btn-outline-brand mt-3">Reset Filters</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($airbnbProperties as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card property-card h-100">
                            <div class="position-relative">
                                <?php if (!empty($property['images'])): ?>
                                    <img src="<?= $property['images'][0]['url']; ?>" class="card-img-top property-image" alt="<?= htmlspecialchars((string)$property['name']); ?>">
                                <?php else: ?>
                                    <div class="property-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($property['units'][0]['base_price'])): ?>
                                <div class="price-tag">
                                    KES <?= number_format((float)$property['units'][0]['base_price']); ?> <span class="small fw-normal">/night</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4">
                                <h4 class="card-title fw-bold mb-1"><?= htmlspecialchars((string)$property['name']); ?></h4>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-geo-alt-fill me-1 text-danger"></i>
                                    <?= htmlspecialchars((string)$property['city']); ?>, <?= htmlspecialchars((string)$property['state']); ?>
                                </p>
                                
                                <?php if (!empty($property['description'])): ?>
                                <p class="card-text text-muted small mb-4">
                                    <?= substr(htmlspecialchars((string)$property['description']), 0, 120); ?>...
                                </p>
                                <?php endif; ?>

                                <div class="d-flex align-items-center mb-4">
                                    <div class="amenity-icon">
                                        <i class="fas fa-bed"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small"><?= count($property['units']); ?> Rooms</div>
                                        <div class="text-muted extra-small">Available Now</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <div class="small text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        In: <?= date('g:i A', strtotime((string)$property['check_in_time'])); ?>
                                    </div>
                                    <a href="<?= BASE_URL ?>/airbnb/property/<?= $property['id']; ?>" class="btn btn-brand btn-sm px-4">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Why Choose Us -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="mb-3">
                        <i class="bi bi-calendar-check text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="fw-bold">Instant Booking</h5>
                    <p class="text-muted small">Book your stay instantly without waiting for long approval processes.</p>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <i class="bi bi-shield-lock text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="fw-bold">Secure Payments</h5>
                    <p class="text-muted small">Your transactions are safe with our encrypted payment systems.</p>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <i class="bi bi-headset text-warning" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="fw-bold">24/7 Support</h5>
                    <p class="text-muted small">Need help? Our dedicated support team is available around the clock.</p>
                </div>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/../partials/public_footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const checkIn = document.getElementById('checkIn');
            const checkOut = document.getElementById('checkOut');
            
            if (checkIn && !checkIn.value) checkIn.setAttribute('min', today);
            if (checkOut && !checkOut.value) checkOut.setAttribute('min', today);
            
            if (checkIn && checkOut) {
                checkIn.addEventListener('change', function() {
                    checkOut.setAttribute('min', this.value);
                });
            }
        });
    </script>
</body>
</html>
