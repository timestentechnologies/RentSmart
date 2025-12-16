<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Essential Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> | #1 Property Management System in Kenya | Rental & Real Estate Software</title>
    <meta name="description" content="Transform your property management with <?= htmlspecialchars($siteName) ?> - Kenya's leading rental management software. Manage properties, tenants, rent collection, maintenance, utilities & more. 7-day free trial. Perfect for landlords, property managers & real estate agents.">
    <meta name="keywords" content="property management system Kenya, rental management software, property management software Kenya, real estate management system, landlord software Kenya, tenant management system, rental property software, property manager app, online rent collection Kenya, real estate software Kenya, property accounting software, maintenance management system, utility billing software, lease management system, property portfolio management, residential property management, commercial property management, apartment management software, rental tracking system, property management app Kenya, <?= htmlspecialchars($siteName) ?>">
    <meta name="author" content="<?= htmlspecialchars($siteName) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?= BASE_URL ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:locale" content="en_KE">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($siteName) ?> | Best Property & Rental Management Software in Kenya">
    <meta property="og:description" content="Streamline your property and rental operations with Kenya's most trusted management software. Manage properties, tenants, rent payments, maintenance & utilities in one platform. Start your 7-day free trial today!">
    <meta property="og:image" content="<?= BASE_URL ?>/public/assets/images/social_preview.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($siteName) ?> | Property & Rental Management Software Kenya">
    <meta name="twitter:description" content="Manage properties, units, tenants, rent collection, maintenance & utilities with <?= htmlspecialchars($siteName) ?>. Trusted by landlords and real estate professionals across Kenya. 7-day free trial!">
    <meta name="twitter:image" content="<?= BASE_URL ?>/public/assets/images/social_preview.png">
    <meta name="twitter:site" content="@RentSmartKE">
    
    <!-- Structured Data (JSON-LD) for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "<?= htmlspecialchars($siteName) ?>",
        "applicationCategory": "BusinessApplication",
        "applicationSubCategory": "Property Management Software",
        "operatingSystem": "Web Browser",
        "description": "Comprehensive property and rental management software for landlords, property managers, and real estate agents in Kenya. Manage properties, tenants, rent collection, maintenance, and utilities all in one platform.",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "KES",
            "priceValidUntil": "<?= date('Y-12-31') ?>",
            "availability": "https://schema.org/InStock",
            "description": "7-day free trial"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "150",
            "bestRating": "5",
            "worstRating": "1"
        },
        "featureList": [
            "Property Management",
            "Tenant Management",
            "Rent Collection",
            "Maintenance Tracking",
            "Utility Management",
            "Lease Management",
            "Financial Reporting",
            "M-Pesa Integration",
            "Automated Reminders",
            "Document Management"
        ],
        "url": "<?= BASE_URL ?>",
        "screenshot": "<?= BASE_URL ?>/public/assets/images/social_preview.png"
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?= htmlspecialchars($siteName) ?>",
        "url": "<?= BASE_URL ?>",
        "logo": "<?= BASE_URL ?>/public/assets/images/site_logo_1751627446.png",
        "description": "Leading property and rental management software in Kenya",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "KE",
            "addressLocality": "Nairobi"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Support",
            "telephone": "+254718883983",
            "email": "timestentechnologies@gmail.com"
        },
        "sameAs": [
            "https://www.facebook.com/RentSmartKE",
            "https://twitter.com/RentSmartKE",
            "https://www.linkedin.com/company/rentsmart-kenya"
        ]
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?= htmlspecialchars($siteName) ?>",
        "url": "<?= BASE_URL ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= BASE_URL ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "What is RentSmart property management software?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "RentSmart is a comprehensive cloud-based property management system designed for landlords, property managers, and real estate agents in Kenya. It helps you manage properties, tenants, rent collection, maintenance, utilities, and financial reporting all in one platform. With M-PESA integration and automated features, RentSmart simplifies rental property management."
                }
            },
            {
                "@type": "Question",
                "name": "How long is the free trial period?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "RentSmart offers a generous 7-day free trial with full access to all features. No credit card required to start. You can explore all property management features, add properties and tenants, collect rent, and generate reports during the trial period."
                }
            },
            {
                "@type": "Question",
                "name": "Does RentSmart integrate with M-PESA?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Yes! RentSmart has full M-PESA integration for seamless rent collection. Tenants can pay rent directly through M-PESA, and payments are automatically recorded in the system. You'll receive instant notifications when payments are made."
                }
            },
            {
                "@type": "Question",
                "name": "How many properties can I manage with RentSmart?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "The number of properties you can manage depends on your subscription plan. Our Basic plan supports up to 10 properties, Professional plan up to 50 properties, and Enterprise plan offers unlimited properties."
                }
            },
            {
                "@type": "Question",
                "name": "Can tenants access the system?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Yes! RentSmart includes a dedicated tenant portal where tenants can log in to view their lease details, make rent payments, submit maintenance requests, and access important documents."
                }
            },
            {
                "@type": "Question",
                "name": "Is my data secure with RentSmart?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Absolutely! RentSmart uses bank-level security with SSL encryption to protect your data. All information is stored on secure cloud servers with automatic daily backups."
                }
            },
            {
                "@type": "Question",
                "name": "What kind of reports can I generate?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "RentSmart provides comprehensive financial and operational reports including income statements, rent collection reports, occupancy reports, expense tracking, tenant payment history, maintenance reports, utility billing reports, and property performance analytics."
                }
            },
            {
                "@type": "Question",
                "name": "Do you offer customer support?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Yes! We provide excellent customer support through email at timestentechnologies@gmail.com and phone at +254 718 883 983. Our Kenyan support team is ready to help you with setup, training, and any questions you may have."
                }
            }
        ]
    }
    </script>
    <?php
    $faviconUrl = $favicon ? BASE_URL . '/public/assets/images/' . $favicon : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';
    ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($faviconUrl) ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6B3E99; /* Purple from logo */
            --secondary-color: #8E5CC4; /* Lighter purple for gradients */
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
            --accent-color: #FF8A00;  /* Orange accent color */
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark-color);
        }

        .navbar {
            padding: 1rem 0;
            background-color: white;
            box-shadow: 0 2px 15px rgba(107, 62, 153, 0.1);
        }

        .navbar-brand img {
            height: 40px;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 50px 0;
            color: white;
            margin-top: 76px;
        }

        .hero-section h1 {
            font-size: 3rem;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .hero-section .lead {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .hero-section .btn-accent {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        .hero-section .btn-accent:hover {
            background-color: #e67a00;
            border-color: #e67a00;
            color: white;
        }

        .hero-section .btn-outline-accent {
            border-color: var(--accent-color);
            color: var(--accent-color);
            background-color: transparent;
        }

        .hero-section .btn-outline-accent:hover {
            background-color: var(--accent-color);
            color: white;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(107, 62, 153, 0.2);
        }

        .feature-card {
            padding: 2rem;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 10px 30px rgba(107, 62, 153, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon-circle {
            width: 64px;
            height: 64px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .feature-icon-circle i {
            font-size: 1.75rem;
            color: white;
        }

        .cta-section {
            background-color: var(--light-color);
            padding: 80px 0;
        }

        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(107, 62, 153, 0.1);
            margin: 1rem 0;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1rem;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .testimonial-avatar i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .pricing-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(107, 62, 153, 0.1);
            height: 100%;
        }

        .pricing-card.featured {
            border: 2px solid var(--primary-color);
            transform: scale(1.05);
        }

        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 40px 0;
        }
        
        footer a {
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: var(--accent-color) !important;
        }
        
        .social-links a {
            font-size: 1.5rem;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            opacity: 1;
            transform: translateY(-2px);
            color: var(--accent-color);
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(107, 62, 153, 0.2);
            color: white;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
        }

        .stats-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(107, 62, 153, 0.1);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .dashboard-preview {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(107, 62, 153, 0.1);
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
        }

        .dashboard-icon-circle {
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dashboard-icon-circle i {
            font-size: 1rem;
            color: white;
        }

        .preview-stat-card {
            background: rgba(107, 62, 153, 0.03);
            border-radius: 10px;
            padding: 0.75rem;
            height: 100%;
            transition: all 0.3s ease;
        }

        .preview-stat-card:hover {
            transform: translateY(-3px);
            background: rgba(107, 62, 153, 0.05);
        }

        .preview-stat-card i {
            font-size: 1.5rem;
            color: var(--accent-color) !important;
        }

        .preview-stat-card h4 {
            font-size: 1rem;
            color: var(--primary-color);
        }

        .preview-stat-card p {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .preview-chart-container {
            margin-top: 0.75rem;
            padding: 0.5rem;
            background: rgba(107, 62, 153, 0.03);
            border-radius: 10px;
            height: 80px;
            position: relative;
            overflow: hidden;
        }

        .preview-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 100%;
            padding: 0 0.5rem;
        }

        .preview-bar {
            width: 8px;
            background: linear-gradient(to top, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            transition: height 0.3s ease;
        }

        .preview-line-chart {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to right, transparent, var(--accent-color) 50%, transparent);
            opacity: 0.1;
            transform: skewY(-5deg);
        }

        @media (max-width: 576px) {
            .preview-stat-card {
                padding: 0.75rem;
            }

            .preview-stat-card i {
                font-size: 1.2rem;
            }

            .preview-stat-card h4 {
                font-size: 0.9rem;
            }

            .preview-stat-card p {
                font-size: 0.8rem;
            }
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(107, 62, 153, 0.2);
        }

        .modal-header {
            border-bottom: none;
        }

        .modal .btn-close {
            background-color: var(--light-color);
            opacity: 1;
            padding: 0.5rem;
            margin: 0.5rem;
            border-radius: 50%;
        }

        .modal .btn-close:hover {
            background-color: var(--primary-color);
            opacity: 0.8;
        }

        .modal .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(107, 62, 153, 0.25);
        }

        .modal .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .modal .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
        }

        .modal a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .modal a:hover {
            color: var(--secondary-color);
        }

        .badge.bg-success {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
        }
    </style>
</head>
<body>
    <?php
    // Ensure BASE_URL is defined
    if (!defined('BASE_URL')) {
        define('BASE_URL', '/rentsmart');
    }
    ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/vacant-units">Vacant Units</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                            <a class="nav-link" href="#faq">FAQs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-gradient ms-2" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                            Get Started - 7 Days Free
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Property Management Made Easy</h1>
                    <p class="lead">Streamline your property management with RentSmart. The all-in-one solution for landlords and property managers.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-accent btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="bi bi-rocket-takeoff me-2"></i>Start 7-Day Free Trial
                        </a>
                        <a href="#features" class="btn btn-outline-accent btn-lg">Learn More</a>
                    </div>
                    <a href="#" class="btn btn-accent btn-lg" style="margin: 20px 0;" data-bs-toggle="modal" data-bs-target="#tenantLoginModal">Tenant Portal</a>
                </div>
                <div class="col-lg-6">
                    <div class="dashboard-preview">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="dashboard-icon-circle me-2">
                                        <i class="bi bi-grid-1x2-fill"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" style="font-size: 1.25rem;">Everything you need in one place</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="preview-stat-card">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building me-2"></i>
                                        <div>
                                            <h4 class="mb-0" style="font-size: 0.9rem;">Properties</h4>
                                            <p class="mb-0" style="font-size: 0.8rem;">Manage all properties</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="preview-stat-card">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-people me-2"></i>
                                        <div>
                                            <h4 class="mb-0" style="font-size: 0.9rem;">Tenants</h4>
                                            <p class="mb-0" style="font-size: 0.8rem;">Track all tenants</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="preview-stat-card">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-cash-stack me-2"></i>
                                        <div>
                                            <h4 class="mb-0" style="font-size: 0.9rem;">Payments</h4>
                                            <p class="mb-0" style="font-size: 0.8rem;">Monitor revenue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="preview-stat-card">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-graph-up me-2"></i>
                                        <div>
                                            <h4 class="mb-0" style="font-size: 0.9rem;">Analytics</h4>
                                            <p class="mb-0" style="font-size: 0.8rem;">Quick insights</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="preview-chart-container">
                                    <div class="preview-chart">
                                        <div class="preview-bar" style="height: 60%;"></div>
                                        <div class="preview-bar" style="height: 80%;"></div>
                                        <div class="preview-bar" style="height: 40%;"></div>
                                        <div class="preview-bar" style="height: 90%;"></div>
                                        <div class="preview-bar" style="height: 70%;"></div>
                                        <div class="preview-bar" style="height: 85%;"></div>
                                    </div>
                                    <div class="preview-line-chart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">1000+</div>
                        <div class="stats-label">Properties Managed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">500+</div>
                        <div class="stats-label">Happy Clients</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">99%</div>
                        <div class="stats-label">Customer Satisfaction</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">24/7</div>
                        <div class="stats-label">Support Available</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Why Choose RentSmart for Property Management?</h2>
                <p class="lead text-muted">The most trusted rental management software in Kenya</p>
            </div>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h3 class="mb-4">Built for Kenyan Landlords & Property Managers</h3>
                    <div class="mb-4">
                        <h5><i class="bi bi-check-circle-fill text-success me-2"></i>M-PESA Integration</h5>
                        <p class="text-muted">Accept rent payments directly through M-PESA, Kenya's most popular mobile money platform. Automated payment reconciliation and instant notifications.</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="bi bi-check-circle-fill text-success me-2"></i>Cloud-Based & Secure</h5>
                        <p class="text-muted">Access your property data anywhere, anytime. Bank-level security with automatic backups and data encryption to protect your information.</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="bi bi-check-circle-fill text-success me-2"></i>Save Time & Money</h5>
                        <p class="text-muted">Automate repetitive tasks like rent reminders, lease renewals, and financial reports. Reduce administrative costs by up to 70%.</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="bi bi-check-circle-fill text-success me-2"></i>Excellent Customer Support</h5>
                        <p class="text-muted">Get help when you need it with our dedicated Kenyan support team. Email, phone, and live chat support available.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="p-4 bg-white rounded shadow-sm">
                        <h4 class="mb-3">Perfect for:</h4>
                        <ul class="list-unstyled">
                            <li class="mb-3"><i class="bi bi-building text-primary me-2"></i><strong>Individual Landlords</strong> - Manage 1-10 properties efficiently</li>
                            <li class="mb-3"><i class="bi bi-buildings text-primary me-2"></i><strong>Property Managers</strong> - Handle multiple properties and clients</li>
                            <li class="mb-3"><i class="bi bi-house-heart text-primary me-2"></i><strong>Real Estate Agents</strong> - Track rentals and commissions</li>
                            <li class="mb-3"><i class="bi bi-shop text-primary me-2"></i><strong>Commercial Properties</strong> - Manage offices, shops, and warehouses</li>
                            <li class="mb-3"><i class="bi bi-houses text-primary me-2"></i><strong>Residential Properties</strong> - Apartments, houses, and condos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Complete Property Management Software Features</h2>
                <p class="lead text-muted">Streamline your rental business with our comprehensive property management tools designed for landlords and real estate professionals in Kenya</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon-circle mx-auto">
                            <i class="bi bi-house-door"></i>
                        </div>
                        <h3>Property Management</h3>
                        <p>Manage unlimited properties, units, and tenants from one centralized dashboard. Track occupancy rates, rental income, and property performance in real-time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon-circle mx-auto">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <h3>Online Rent Collection</h3>
                        <p>Automate rent collection with M-PESA integration. Accept payments online, send reminders, and track payment history effortlessly.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon-circle mx-auto">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h3>Lease Management System</h3>
                        <p>Create digital lease agreements, track lease terms, and receive automated renewal reminders. Simplify tenant onboarding and documentation.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon-circle mx-auto">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3>Financial Reporting & Analytics</h3>
                        <p>Generate comprehensive financial reports including income statements, expense tracking, and property performance analytics for better decision-making.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon-circle mx-auto">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3>Tenant Management Portal</h3>
                        <p>Provide tenants with self-service access to make rent payments, submit maintenance requests, and view lease documents online 24/7.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon-circle mx-auto">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h3>Automated Notifications</h3>
                        <p>Stay informed with automated SMS and email notifications for rent due dates, maintenance updates, lease renewals, and payment confirmations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Affordable Property Management Software Pricing</h2>
                <p class="lead text-muted">Flexible pricing plans for landlords and property managers of all sizes. Start with a 7-day free trial - no credit card required</p>
            </div>
            <div class="row g-4">
                <?php 
                // Sort plans by price
                usort($plans, function($a, $b) {
                    return $a['price'] - $b['price'];
                });
                foreach ($plans as $plan): 
                ?>
                <div class="col-md-4">
                    <div class="pricing-card <?= $plan['name'] === 'Professional' ? 'featured' : '' ?>">
                        <h3><?= htmlspecialchars($plan['name']) ?></h3>
                        <div class="display-6 fw-bold mb-3">
                            Ksh <?= number_format($plan['price'], 2) ?><small class="fs-6">/month</small>
                        </div>
                        <p class="text-muted mb-4"><?= htmlspecialchars($plan['description']) ?></p>
                        <ul class="list-unstyled mb-4">
                            <?php foreach ($plan['features_array'] as $feature): ?>
                                <li class="mb-2">
                                    <i class="bi bi-check2 text-success me-2"></i><?= htmlspecialchars($feature) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($plan['name'] === 'Enterprise'): ?>
                            <a href="#" class="btn <?= $plan['name'] === 'Professional' ? 'btn-gradient' : 'btn-outline-primary' ?> w-100">Contact Sales</a>
                        <?php else: ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" class="btn <?= $plan['name'] === 'Professional' ? 'btn-gradient' : 'btn-outline-primary' ?> w-100">
                                Start 7-Day Free Trial
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Trusted by Property Managers Across Kenya</h2>
                <p class="lead text-muted">See how landlords and real estate professionals are transforming their property management with RentSmart</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="testimonial-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Mercy Wanjiru</h5>
                                <small class="text-muted">Property Manager</small>
                            </div>
                        </div>
                        <p>"RentSmart has completely transformed how we manage our properties. The automated rent collection and reporting features save us hours every week."</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="testimonial-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">James Kamau</h5>
                                <small class="text-muted">Landlord</small>
                            </div>
                        </div>
                        <p>"The tenant portal has made communication so much easier. My tenants love being able to pay rent and submit maintenance requests online."</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="testimonial-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">David Kibara</h5>
                                <small class="text-muted">Real Estate Agent</small>
                            </div>
                        </div>
                        <p>"The financial reports and analytics help me make data-driven decisions. RentSmart has helped us increase our property portfolio's performance."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Frequently Asked Questions</h2>
                <p class="lead text-muted">Everything you need to know about RentSmart property management software</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ 1 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What is RentSmart property management software?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    RentSmart is a comprehensive cloud-based property management system designed for landlords, property managers, and real estate agents in Kenya. It helps you manage properties, tenants, rent collection, maintenance, utilities, and financial reporting all in one platform. With M-PESA integration and automated features, RentSmart simplifies rental property management.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 2 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How long is the free trial period?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    RentSmart offers a generous 7-day free trial with full access to all features. No credit card required to start. You can explore all property management features, add properties and tenants, collect rent, and generate reports during the trial period. After 7-day, you can choose a plan that fits your needs.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 3 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Does RentSmart integrate with M-PESA?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! RentSmart has full M-PESA integration for seamless rent collection. Tenants can pay rent directly through M-PESA, and payments are automatically recorded in the system. You'll receive instant notifications when payments are made, and the system automatically reconciles payments with tenant accounts.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 4 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    How many properties can I manage with RentSmart?
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The number of properties you can manage depends on your subscription plan. Our Basic plan supports up to 10 properties, Professional plan up to 50 properties, and Enterprise plan offers unlimited properties. Each property can have multiple units, and you can manage all of them from a single dashboard.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 5 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    Can tenants access the system?
                                </button>
                            </h3>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! RentSmart includes a dedicated tenant portal where tenants can log in to view their lease details, make rent payments, submit maintenance requests, and access important documents. This self-service portal reduces your workload and improves tenant satisfaction.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 6 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    Is my data secure with RentSmart?
                                </button>
                            </h3>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutely! RentSmart uses bank-level security with SSL encryption to protect your data. All information is stored on secure cloud servers with automatic daily backups. We comply with data protection regulations and never share your information with third parties. Your property and tenant data is safe with us.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 7 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    What kind of reports can I generate?
                                </button>
                            </h3>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    RentSmart provides comprehensive financial and operational reports including: income statements, rent collection reports, occupancy reports, expense tracking, tenant payment history, maintenance reports, utility billing reports, and property performance analytics. All reports can be exported to PDF or Excel for easy sharing.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 8 -->
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                    Do you offer customer support?
                                </button>
                            </h3>
                            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! We provide excellent customer support through email at <a href="mailto:timestentechnologies@gmail.com">timestentechnologies@gmail.com</a> and phone at <a href="tel:+254718883983">+254 718 883 983</a>. Our Kenyan support team is ready to help you with setup, training, and any questions you may have. We also offer video tutorials and documentation to help you get started.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Transform Your Property Management Today</h2>
            <p class="lead mb-4">Join hundreds of landlords and property managers in Kenya who are simplifying their rental business with RentSmart. Start your free trial now!</p>
            <a class="btn btn-gradient btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="bi bi-rocket-takeoff me-2"></i>Start Your 7-Day Free Trial
            </a>
            
            <p class="mt-3 text-muted">No credit card required</p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Property Management Software Logo" class="mb-3" style="height: 40px;">
                    <p class="mb-3">Kenya's leading property and rental management software. Trusted by landlords, property managers, and real estate professionals.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/RentSmartKE" target="_blank" class="text-white mx-2" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://twitter.com/RentSmartKE" target="_blank" class="text-white mx-2" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                        <a href="https://www.linkedin.com/company/rentsmart-kenya" target="_blank" class="text-white mx-2" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="https://www.instagram.com/rentsmartke" target="_blank" class="text-white mx-2" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-3">Features</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features" class="text-white-50">Property Management</a></li>
                        <li class="mb-2"><a href="#features" class="text-white-50">Tenant Management</a></li>
                        <li class="mb-2"><a href="#features" class="text-white-50">Rent Collection</a></li>
                        <li class="mb-2"><a href="#features" class="text-white-50">Maintenance Tracking</a></li>
                        <li class="mb-2"><a href="#features" class="text-white-50">Utility Management</a></li>
                        <li class="mb-2"><a href="#features" class="text-white-50">Financial Reports</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-3">Solutions</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white-50">For Landlords</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">For Property Managers</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">For Real Estate Agents</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">Residential Properties</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">Commercial Properties</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/vacant-units" class="text-white-50">Find Vacant Units</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-3">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:timestentechnologies@gmail.com" class="text-white-50">timestentechnologies@gmail.com</a></li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:+254718883983" class="text-white-50">+254 718 883 983</a></li>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Nairobi, Kenya</li>
                    </ul>
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/privacy-policy" class="text-white-50 d-block mb-2">Privacy Policy</a>
                        <a href="<?= BASE_URL ?>/terms" class="text-white-50 d-block mb-2">Terms of Service</a>
                    </div>
                </div>
            </div>
            <div class="row border-top border-secondary pt-3">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> RentSmart. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-white-50">Property Management System | Rental Management Software | Real Estate Management</p>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <p class="mb-0 text-white-50">
                        Powered by <a href="https://timestentechnologies.co.ke" target="_blank" class="text-white">Timesten Technologies</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-2">
                    <div class="text-center mb-4">
                        <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Logo" class="logo" style="width: 200px;">
                        <h1 class="h3 mb-3 fw-normal">Welcome Back!</h1>
                    </div>

                    <div id="loginAlert" class="alert d-none" role="alert"></div>

                    <form id="loginForm" onsubmit="return handleLogin(event);">
                        <?= csrf_field() ?>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="loginEmail" name="email" placeholder="Email or Phone Number" required>
                            <label for="loginEmail">Email or Phone Number</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Password" required>
                            <label for="loginPassword">Password</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="loginRemember" name="remember">
                            <label class="form-check-label" for="loginRemember">Remember me</label>
                        </div>

                        <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
                        </button>

                        <div class="text-center mb-3">
                            <a href="<?= asset('forgot-password') ?>" class="text-muted text-decoration-none">Forgot your password?</a>
                        </div>

                        <div class="text-center">
                            <p class="text-muted">
                                Don't have an account? 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Create one</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-2">
                    <div class="text-center mb-4">
                        <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Logo" class="logo" style="width: 200px;">
                        <h1 class="h3 mb-3 fw-normal">Create Your Account</h1>
                        <div class="badge bg-success">
                            <i class="bi bi-clock"></i> Includes 7-day free trial
                        </div>
                    </div>

                    <div id="registerAlert" class="alert d-none" role="alert"></div>

                    <form id="registerForm" onsubmit="return handleRegister(event);">
                        <?= csrf_field() ?>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="registerName" name="name" placeholder="Full Name" required>
                            <label for="registerName">Full Name</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="registerEmail" name="email" placeholder="name@example.com" required>
                            <label for="registerEmail">Email address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="registerPhone" name="phone" placeholder="Phone Number" required>
                            <label for="registerPhone">Phone Number</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="registerAddress" name="address" placeholder="Address" required>
                            <label for="registerAddress">Address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Password" required>
                            <label for="registerPassword">Password</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                            <label for="registerConfirmPassword">Confirm Password</label>
                        </div>

                        <div class="form-floating mb-3">
                            <select class="form-select" id="registerRole" name="role" required>
                                <option value="">Select Role</option>
                                <option value="landlord">Landlord</option>
                                <option value="agent">Agent</option>
                            </select>
                            <label for="registerRole">Your Role</label>
                        </div>

                        <input type="hidden" name="plan_id" value="1">

                        <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                            <i class="bi bi-person-plus me-2"></i>Create Account - Start 7-Day Trial
                        </button>

                        <div class="text-center">
                            <p class="text-muted">
                                Already have an account? 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Sign in</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Login Modal -->
    <div class="modal fade" id="tenantLoginModal" tabindex="-1" aria-labelledby="tenantLoginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-2">
                    <div class="text-center mb-4">
                        <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Logo" class="logo" style="width: 200px;">
                        <h1 class="h4 mb-3 fw-normal">Tenant Portal Login</h1>
                    </div>
                    <div id="tenantLoginAlert" class="alert d-none" role="alert"></div>
                    <form id="tenantLoginForm" onsubmit="return handleTenantLogin(event);">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="tenant-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="tenant-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="tenant-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="tenant-password" name="password" required>
                        </div>
                        <div class="modal-footer border-0 px-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-accent">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAlert(formId, message, type = 'danger') {
            const alert = document.getElementById(formId + 'Alert');
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.classList.remove('d-none');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alert.classList.add('d-none');
            }, 5000);
        }

        async function handleLogin(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('<?= BASE_URL ?>/login', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('login', result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect || '<?= BASE_URL ?>/dashboard';
                    }, 1000);
                } else {
                    showAlert('login', result.message);
                    if (result.redirect) {
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 2000);
                    }
                }
            } catch (error) {
                showAlert('login', 'An error occurred during login. Please try again.');
            }
        }

        async function handleTenantLogin(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('<?= BASE_URL ?>/tenant/login', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('tenantLogin', result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect || '<?= BASE_URL ?>/tenant/dashboard';
                    }, 1000);
                } else {
                    showAlert('tenantLogin', result.message);
                }
            } catch (error) {
                showAlert('tenantLogin', 'An error occurred during login. Please try again.');
            }
        }

        async function handleRegister(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            // Validate password match
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if (password !== confirmPassword) {
                showAlert('register', 'Passwords do not match');
                return false;
            }

            // Get the button and set loading
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating Account...';

            try {
                const response = await fetch('<?= BASE_URL ?>/register', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('register', result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect || '<?= BASE_URL ?>/dashboard';
                    }, 1000);
                } else {
                    showAlert('register', result.message);
                }
            } catch (error) {
                showAlert('register', 'An error occurred during registration. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        }

        // Add animation to preview bars
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.preview-bar');
            bars.forEach(bar => {
                const originalHeight = bar.style.height;
                bar.style.height = '0';
                setTimeout(() => {
                    bar.style.height = originalHeight;
                }, 100);
            });
        });
    </script>
</body>
</html>