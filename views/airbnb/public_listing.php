<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airbnb Stays - <?php echo htmlspecialchars($siteName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }
        .property-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .property-image {
            height: 200px;
            object-fit: cover;
        }
        .price-tag {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            position: absolute;
            bottom: 1rem;
            right: 1rem;
        }
        .search-box {
            background: white;
            border-radius: 50px;
            padding: 1rem 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .amenity-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        .availability-calendar {
            max-width: 100%;
            overflow-x: auto;
        }
    </style>
    <?php if ($favicon): ?>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL . '/public/assets/images/' . $favicon; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <?php if ($siteLogo): ?>
                <img src="<?php echo $siteLogo; ?>" alt="<?php echo htmlspecialchars($siteName); ?>" height="40">
                <?php else: ?>
                <?php echo htmlspecialchars($siteName); ?>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/vacant-units">Vacant Units</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>/airbnb">Airbnb Stays</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/login">Sign In</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-3">Find Your Perfect Stay</h1>
            <p class="lead mb-4">Discover comfortable rooms and apartments for short-term stays</p>
            
            <!-- Search Box -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="search-box">
                        <form action="<?php echo BASE_URL; ?>/airbnb" method="GET" class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Check-in</label>
                                <input type="date" name="check_in" class="form-control border-0" id="checkIn">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Check-out</label>
                                <input type="date" name="check_out" class="form-control border-0" id="checkOut">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Guests</label>
                                <select name="guests" class="form-select border-0">
                                    <option value="1">1 Guest</option>
                                    <option value="2">2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4+ Guests</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Properties Listing -->
    <section class="py-5">
        <div class="container">
            <?php if (isset($_SESSION['airbnb_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['airbnb_error']; unset($_SESSION['airbnb_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h2 class="mb-4">Available Stays</h2>
            
            <?php if (empty($airbnbProperties)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-home fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Airbnb properties available at the moment</h4>
                    <p class="text-muted">Please check back later for new listings</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($airbnbProperties as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card property-card h-100">
                            <div class="position-relative">
                                <?php if (!empty($property['images'])): ?>
                                    <img src="<?php echo $property['images'][0]['url']; ?>" class="card-img-top property-image" alt="<?php echo htmlspecialchars($property['name']); ?>">
                                <?php else: ?>
                                    <div class="property-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-building fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($property['units'][0]['base_price'])): ?>
                                <div class="price-tag">
                                    KES <?php echo number_format($property['units'][0]['base_price']); ?>/night
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($property['name']); ?></h5>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($property['city']); ?>, <?php echo htmlspecialchars($property['state']); ?>
                                </p>
                                
                                <?php if (!empty($property['description'])): ?>
                                <p class="card-text text-muted small">
                                    <?php echo substr(htmlspecialchars($property['description']), 0, 100); ?>...
                                </p>
                                <?php endif; ?>

                                <div class="d-flex align-items-center mb-3">
                                    <div class="amenity-icon">
                                        <i class="fas fa-bed text-primary"></i>
                                    </div>
                                    <span class="small"><?php echo count($property['units']); ?> Rooms Available</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Check-in: <?php echo date('g:i A', strtotime($property['check_in_time'])); ?>
                                    </small>
                                    <a href="<?php echo BASE_URL; ?>/airbnb/property/<?php echo $property['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        View Rooms
                                    </a>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Min stay: <?php echo $property['min_stay_nights']; ?> night(s)
                                    </small>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($property['cancellation_policy']); ?> cancellation
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="amenity-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                    </div>
                    <h5>Instant Booking</h5>
                    <p class="text-muted">Book your stay instantly without waiting for approval</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="amenity-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-shield-alt fa-2x text-primary"></i>
                    </div>
                    <h5>Secure Payments</h5>
                    <p class="text-muted">Your payments are protected and secure</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="amenity-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-headset fa-2x text-primary"></i>
                    </div>
                    <h5>24/7 Support</h5>
                    <p class="text-muted">Our team is available to help you anytime</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo htmlspecialchars($siteName); ?></h5>
                    <p class="small text-muted">Your trusted platform for short-term stays</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo BASE_URL; ?>/privacy-policy" class="text-muted small me-3">Privacy Policy</a>
                    <a href="<?php echo BASE_URL; ?>/terms" class="text-muted small">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('checkIn').setAttribute('min', today);
            document.getElementById('checkOut').setAttribute('min', today);
            
            // Update checkout min date when checkin changes
            document.getElementById('checkIn').addEventListener('change', function() {
                document.getElementById('checkOut').setAttribute('min', this.value);
            });
        });
    </script>
</body>
</html>
