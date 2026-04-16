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

        /* Custom Styled Select Dropdown */
        .custom-select-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .custom-select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            color: #212529;
            min-height: 46px;
        }
        .custom-select-trigger:hover {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 138, 0, 0.15);
        }
        .custom-select-trigger.active {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 138, 0, 0.25);
        }
        .custom-select-trigger .arrow {
            margin-left: 8px;
            transition: transform 0.2s;
            color: var(--accent-color);
        }
        .custom-select-trigger.active .arrow {
            transform: rotate(180deg);
        }
        .custom-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid rgba(255, 138, 0, 0.3);
            border-radius: 14px;
            margin-top: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            max-height: 280px;
            overflow-y: auto;
            display: none;
        }
        .custom-select-dropdown.show {
            display: block;
        }
        .custom-select-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.875rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .custom-select-option:last-child {
            border-bottom: none;
        }
        .custom-select-option:hover,
        .custom-select-option.selected {
            background: var(--accent-color);
            color: white;
        }
        .custom-select-option:first-child {
            border-radius: 13px 13px 0 0;
        }
        .custom-select-option:last-child {
            border-radius: 0 0 13px 13px;
        }
        select.js-enhanced {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            height: 0;
            width: 0;
        }
        .bg-brand-purple {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
            color: white;
        }
        .text-brand {
            color: var(--primary-color);
        }
        #searchResultsContainer {
            transition: opacity 0.3s ease;
        }
        #searchResultsContainer.loading {
            opacity: 0.6;
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
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted mb-1">Location</label>
                            <div class="position-relative">
                                <input type="text" id="locationSearch" name="location" class="form-control" placeholder="Enter city or area" value="<?= htmlspecialchars((string)($_GET['location'] ?? '')) ?>" autocomplete="off">
                                <div id="searchSpinner" class="position-absolute end-0 top-50 translate-middle-y me-3 d-none">
                                    <div class="spinner-border spinner-border-sm text-brand" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">Guests</label>
                            <select name="guests" id="guestsSelect" class="form-select">
                                <option value="1" <?= ($_GET['guests'] ?? '') == '1' ? 'selected' : '' ?>>1 Guest</option>
                                <option value="2" <?= ($_GET['guests'] ?? '') == '2' ? 'selected' : '' ?>>2 Guests</option>
                                <option value="3" <?= ($_GET['guests'] ?? '') == '3' ? 'selected' : '' ?>>3 Guests</option>
                                <option value="4" <?= ($_GET['guests'] ?? '') == '4' ? 'selected' : '' ?>>4+ Guests</option>
                            </select>
                        </div>
                    </div>
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

            <div id="searchResultsContainer">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h2 class="section-title" id="resultsTitle">
                        <?php if (!empty($_GET['location'])): ?>
                            Search Results for "<?= htmlspecialchars($_GET['location']) ?>"
                        <?php else: ?>
                            Available Stays
                        <?php endif; ?>
                    </h2>
                    <span id="resultsBadge" class="badge bg-brand-purple <?= empty($_GET['location']) || empty($airbnbProperties) ? 'd-none' : '' ?>">
                        <?= count($airbnbProperties) ?> property<?= count($airbnbProperties) > 1 ? 'ies' : 'y' ?> found
                    </span>
                </div>
                
                <div id="propertiesList">
                    <?php if (empty($airbnbProperties)): ?>
                        <div class="text-center py-5 my-5" id="emptyState">
                            <div class="mb-4">
                                <i class="bi bi-house-door text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                            </div>
                            <h4 class="text-muted" id="emptyTitle">
                                <?php if (!empty($_GET['location'])): ?>
                                    No stays found in "<?= htmlspecialchars($_GET['location']) ?>"
                                <?php else: ?>
                                    No stays available at the moment
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted" id="emptyMessage">
                                <?php if (!empty($_GET['location'])): ?>
                                    Try searching for a different location
                                <?php else: ?>
                                    Check back soon for new properties!
                                <?php endif; ?>
                            </p>
                            <a href="<?= BASE_URL ?>/airbnb" id="viewAllBtn" class="btn btn-outline-primary <?= empty($_GET['location']) ? 'd-none' : '' ?>">View All Properties</a>
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
            </div>
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
        // Store all properties for client-side filtering
        const allProperties = <?= json_encode($airbnbProperties) ?>;
        const baseUrl = '<?= BASE_URL ?>';

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize custom select dropdowns
            initCustomSelects();
            
            // Setup dynamic search
            setupDynamicSearch();
        });

        // Dynamic Search Functionality
        function setupDynamicSearch() {
            const locationInput = document.getElementById('locationSearch');
            const guestsSelect = document.getElementById('guestsSelect');
            const searchBtn = document.getElementById('searchBtn');
            const spinner = document.getElementById('searchSpinner');
            const resultsContainer = document.getElementById('searchResultsContainer');
            
            let searchTimeout;
            
            // Search as user types (with debounce)
            locationInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                spinner.classList.remove('d-none');
                
                searchTimeout = setTimeout(function() {
                    performSearch();
                    spinner.classList.add('d-none');
                }, 300); // 300ms debounce
            });
            
            // Search when guests change
            guestsSelect.addEventListener('change', function() {
                performSearch();
            });
            
            // Enter key on location input
            locationInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
            
            function performSearch() {
                const location = locationInput.value.trim().toLowerCase();
                const guests = parseInt(guestsSelect.value) || 1;
                
                // Filter properties
                let filtered = allProperties;
                
                if (location) {
                    filtered = allProperties.filter(function(property) {
                        const searchFields = [
                            property.name || '',
                            property.city || '',
                            property.state || '',
                            property.address || ''
                        ].join(' ').toLowerCase();
                        
                        return searchFields.includes(location);
                    });
                }
                
                // Filter by guests (minimum rooms needed)
                filtered = filtered.filter(function(property) {
                    return property.units && property.units.length >= Math.ceil(guests / 2);
                });
                
                // Update UI
                updateResults(filtered, location);
                
                // Update URL without reload
                updateUrl(location, guests);
            }
        }
        
        function updateResults(properties, searchTerm) {
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBadge = document.getElementById('resultsBadge');
            const propertiesList = document.getElementById('propertiesList');
            const viewAllBtn = document.getElementById('viewAllBtn');
            
            // Update title
            if (searchTerm) {
                resultsTitle.textContent = 'Search Results for "' + searchTerm + '"';
            } else {
                resultsTitle.textContent = 'Available Stays';
            }
            
            // Update badge
            if (properties.length > 0) {
                resultsBadge.textContent = properties.length + ' propert' + (properties.length > 1 ? 'ies' : 'y') + ' found';
                resultsBadge.classList.remove('d-none');
            } else {
                resultsBadge.classList.add('d-none');
            }
            
            // Update view all button
            if (searchTerm) {
                viewAllBtn.classList.remove('d-none');
            } else {
                viewAllBtn.classList.add('d-none');
            }
            
            // Render properties
            if (properties.length === 0) {
                const emptyTitle = document.getElementById('emptyTitle');
                const emptyMessage = document.getElementById('emptyMessage');
                
                if (searchTerm) {
                    emptyTitle.textContent = 'No stays found in "' + searchTerm + '"';
                    emptyMessage.textContent = 'Try searching for a different location';
                } else {
                    emptyTitle.textContent = 'No stays available at the moment';
                    emptyMessage.textContent = 'Check back soon for new properties!';
                }
                
                propertiesList.innerHTML = document.getElementById('emptyState').outerHTML;
            } else {
                propertiesList.innerHTML = renderProperties(properties);
            }
        }
        
        function renderProperties(properties) {
            return '<div class="row g-4">' + properties.map(function(property) {
                const image = property.images && property.images.length > 0 
                    ? '<img src="' + property.images[0].url + '" class="card-img-top property-image" alt="' + escapeHtml(property.name) + '">'
                    : '<div class="property-image bg-light d-flex align-items-center justify-content-center"><i class="bi bi-building text-muted" style="font-size: 3rem;"></i></div>';
                
                const price = property.units && property.units.length > 0 && property.units[0].base_price
                    ? '<div class="price-tag">KES ' + Number(property.units[0].base_price).toLocaleString() + ' <span class="small fw-normal">/night</span></div>'
                    : '';
                
                const description = property.description
                    ? '<p class="card-text text-muted small mb-4">' + escapeHtml(property.description.substring(0, 120)) + '...</p>'
                    : '';
                
                const checkInTime = property.check_in_time || '14:00';
                const formattedTime = new Date('2000-01-01T' + checkInTime).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                
                return '<div class="col-md-6 col-lg-4">' +
                    '<div class="card property-card h-100">' +
                        '<div class="position-relative">' + image + price + '</div>' +
                        '<div class="card-body p-4">' +
                            '<h4 class="card-title fw-bold mb-1">' + escapeHtml(property.name) + '</h4>' +
                            '<p class="text-muted small mb-3">' +
                                '<i class="bi bi-geo-alt-fill me-1 text-danger"></i>' +
                                escapeHtml(property.city) + ', ' + escapeHtml(property.state) +
                            '</p>' +
                            description +
                            '<div class="d-flex align-items-center mb-4">' +
                                '<div class="amenity-icon"><i class="fas fa-bed"></i></div>' +
                                '<div>' +
                                    '<div class="fw-bold small">' + (property.units ? property.units.length : 0) + ' Rooms</div>' +
                                    '<div class="text-muted extra-small">Available Now</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between align-items-center pt-3 border-top">' +
                                '<div class="small text-muted"><i class="bi bi-clock me-1"></i>In: ' + formattedTime + '</div>' +
                                '<a href="' + baseUrl + '/airbnb/property/' + property.id + '" class="btn btn-brand btn-sm px-4">View Details</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('') + '</div>';
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            return text.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
        
        function updateUrl(location, guests) {
            const params = new URLSearchParams();
            if (location) params.set('location', location);
            if (guests && guests !== 1) params.set('guests', guests);
            
            const newUrl = baseUrl + '/airbnb' + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }

        // Custom Select Dropdown Functionality
        function initCustomSelects() {
            const selects = document.querySelectorAll('select.form-select:not(.js-enhanced)');
            
            selects.forEach(function(select) {
                if (select.classList.contains('js-enhanced')) return;
                
                select.classList.add('js-enhanced');
                
                const wrapper = document.createElement('div');
                wrapper.className = 'custom-select-wrapper';
                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(select);
                
                const trigger = document.createElement('div');
                trigger.className = 'custom-select-trigger';
                trigger.innerHTML = '<span class="selected-text">-- Select --</span><i class="bi bi-chevron-down arrow"></i>';
                wrapper.appendChild(trigger);
                
                const dropdown = document.createElement('div');
                dropdown.className = 'custom-select-dropdown';
                wrapper.appendChild(dropdown);
                
                Array.from(select.options).forEach(function(option, index) {
                    const opt = document.createElement('div');
                    opt.className = 'custom-select-option';
                    opt.textContent = option.text;
                    opt.setAttribute('data-value', option.value);
                    opt.setAttribute('data-index', index);
                    
                    if (option.selected) {
                        opt.classList.add('selected');
                        trigger.querySelector('.selected-text').textContent = option.text;
                    }
                    
                    dropdown.appendChild(opt);
                });
                
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = dropdown.classList.contains('show');
                    
                    document.querySelectorAll('.custom-select-dropdown.show').forEach(function(d) {
                        d.classList.remove('show');
                        d.closest('.custom-select-wrapper').querySelector('.custom-select-trigger').classList.remove('active');
                    });
                    
                    if (!isOpen) {
                        dropdown.classList.add('show');
                        trigger.classList.add('active');
                    }
                });
                
                dropdown.querySelectorAll('.custom-select-option').forEach(function(opt) {
                    opt.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const index = parseInt(this.getAttribute('data-index'));
                        const value = this.getAttribute('data-value');
                        
                        select.selectedIndex = index;
                        select.value = value;
                        
                        const event = new Event('change', { bubbles: true });
                        select.dispatchEvent(event);
                        
                        dropdown.querySelectorAll('.custom-select-option').forEach(function(o) {
                            o.classList.remove('selected');
                        });
                        this.classList.add('selected');
                        trigger.querySelector('.selected-text').textContent = this.textContent;
                        
                        dropdown.classList.remove('show');
                        trigger.classList.remove('active');
                    });
                });
                
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption) {
                        trigger.querySelector('.selected-text').textContent = selectedOption.text;
                        dropdown.querySelectorAll('.custom-select-option').forEach(function(opt, idx) {
                            opt.classList.toggle('selected', idx === this.selectedIndex);
                        }.bind(this));
                    }
                });
            });
        }
        
        document.addEventListener('click', function() {
            document.querySelectorAll('.custom-select-dropdown.show').forEach(function(d) {
                d.classList.remove('show');
                d.closest('.custom-select-wrapper').querySelector('.custom-select-trigger').classList.remove('active');
            });
        });
        
        // Handle View All Properties button click
        document.addEventListener('click', function(e) {
            if (e.target.id === 'viewAllBtn' || e.target.closest('#viewAllBtn')) {
                e.preventDefault();
                document.getElementById('locationSearch').value = '';
                document.getElementById('guestsSelect').value = '1';
                updateResults(allProperties, '');
                updateUrl('', 1);
            }
        });
    </script>
</body>
</html>
