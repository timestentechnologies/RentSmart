<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Essential Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
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
    <?php $faviconUrl = site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png'); ?>
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
            padding: 0.85rem 0;
            background-color: #fff;
            box-shadow: 0 2px 15px rgba(107, 62, 153, 0.1);
            transition: box-shadow .2s ease;
        }
        .navbar .nav-link {
            color: #4a5568;
            font-weight: 500;
            border-radius: .5rem;
            padding: .5rem .75rem;
            transition: color .15s ease, background-color .15s ease;
        }
        .navbar .nav-link:hover {
            color: var(--primary-color);
            background: rgba(107,62,153,.08);
        }
        .navbar .btn.btn-gradient {
            padding: .5rem .9rem;
        }

        .navbar-brand img {
            height: 40px;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 50px 0;
            color: white;
            margin-top: 0;
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

        .features-section {
            background: #fbfbff;
        }

        .features-section .feature-card {
            background: #fff;
            border: 1px solid rgba(17, 24, 39, 0.08);
            box-shadow: 0 10px 26px rgba(17, 24, 39, 0.08);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .features-section .feature-card:hover {
            transform: translateY(-6px);
            border-color: #FF8A00;
            box-shadow:
                0 0 0 4px rgba(255, 138, 0, 0.12),
                0 22px 60px rgba(17, 24, 39, 0.12);
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

        .pricing-section {
            background: linear-gradient(180deg, rgba(107, 62, 153, 0.03) 0%, rgba(255, 138, 0, 0.03) 100%);
        }

        .pricing-card {
            border: 1px solid rgba(107, 62, 153, 0.10);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .pricing-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px rgba(107, 62, 153, 0.14);
        }

        .pricing-card .plan-name {
            letter-spacing: -0.02em;
        }

        .pricing-price {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: -0.02em;
            white-space: nowrap;
        }

        .pricing-subtext {
            color: #6b7280;
        }

        .pricing-card.featured {
            border: 3px solid #FF8A00;
            box-shadow:
                0 0 0 4px rgba(255, 138, 0, 0.18),
                0 18px 50px rgba(255, 138, 0, 0.22);
            transform: none;
        }

        .why-section {
            background: #fff;
        }

        .why-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.25rem;
            border: 1px solid rgba(107, 62, 153, 0.10);
            box-shadow: 0 12px 30px rgba(107, 62, 153, 0.08);
            height: 100%;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .why-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px rgba(107, 62, 153, 0.12);
        }

        .why-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 138, 0, 0.14);
            color: var(--accent-color);
            font-size: 1.2rem;
        }

        .why-panel {
            border-radius: 1.25rem;
            border: 1px solid rgba(107, 62, 153, 0.10);
            background: linear-gradient(180deg, rgba(107, 62, 153, 0.04) 0%, rgba(255, 138, 0, 0.03) 100%);
            box-shadow: 0 18px 45px rgba(107, 62, 153, 0.10);
        }

        .why-chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            background: rgba(107, 62, 153, 0.10);
            color: var(--primary-color);
            font-weight: 600;
            font-size: .9rem;
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

        .split-section {
            background: linear-gradient(180deg, rgba(107, 62, 153, 0.04) 0%, rgba(255, 138, 0, 0.03) 100%);
        }

        .split-media {
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(107, 62, 153, 0.16);
            background:red;
            border: 1px solid rgba(107, 62, 153, 0.10);
        }

        .split-media img {
            width: 100%;
            height: auto;
            display: block;
        }

        .split-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem .75rem;
            border-radius: 999px;
            background: rgba(107, 62, 153, 0.10);
            color: var(--primary-color);
            font-weight: 600;
            font-size: .9rem;
        }

        .split-title {
            letter-spacing: -0.02em;
        }

        .split-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .split-list li {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .65rem .75rem;
            border-radius: .85rem;
            background: rgba(107, 62, 153, 0.04);
            border: 1px solid rgba(107, 62, 153, 0.08);
            margin-bottom: .6rem;
        }

        .split-list i {
            color: var(--accent-color);
            font-size: 1.1rem;
            line-height: 1.2;
            margin-top: .1rem;
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

    $heroTitle = site_setting('home_hero_title', 'Property Management Made Easy');
    $heroSubtitle = site_setting('home_hero_subtitle', 'Streamline your property management with RentSmart. The all-in-one solution for landlords and property managers.');
    $heroPrimaryText = site_setting('home_hero_primary_text', 'Start 7-Day Free Trial');
    $heroSecondaryText = site_setting('home_hero_secondary_text', 'Learn More');

    $stats = [
        [
            'number' => site_setting('home_stat1_number', '500+'),
            'label' => site_setting('home_stat1_label', 'Properties Managed'),
        ],
        [
            'number' => site_setting('home_stat2_number', '500+'),
            'label' => site_setting('home_stat2_label', 'Happy Clients'),
        ],
        [
            'number' => site_setting('home_stat3_number', '99%'),
            'label' => site_setting('home_stat3_label', 'Customer Satisfaction'),
        ],
        [
            'number' => site_setting('home_stat4_number', '24/7'),
            'label' => site_setting('home_stat4_label', 'Support Available'),
        ],
    ];

    $splitBadge = site_setting('home_split_badge', 'All-in-one platform');
    $splitTitle = site_setting('home_split_title', 'Manage Rent, Utilities & Maintenance in One Place');
    $splitDescription = site_setting('home_split_description', 'Track payments, utilities, and maintenance requests with clear records and automated invoicing—so landlords and tenants always know what is due and what has been paid.');
    $splitImage = site_setting('home_split_image', '');
    $splitImageUrl = $splitImage !== '' ? asset('images/' . $splitImage) : asset('images/new.png');
    $splitBullets = site_setting_json('home_split_bullets_json', [
        ['title' => 'Accurate payment types', 'text' => 'Rent, utilities, and maintenance always recorded correctly.'],
        ['title' => 'Automated invoicing', 'text' => 'Invoices update based on what was paid—no confusion.'],
        ['title' => 'Tenant self-service', 'text' => 'Tenants can pay and track balances from the portal.'],
    ]);

    $whyTitle = site_setting('home_why_title', 'Why Choose RentSmart for Property Management?');
    $whySubtitle = site_setting('home_why_subtitle', 'A modern, Kenyan-ready platform for landlords, managers, and agents—built for speed, clarity, and accurate records.');
    $whyCards = site_setting_json('home_why_cards_json', [
        ['title' => 'M-PESA ready', 'text' => 'Accept payments and keep references organized for quick verification and reporting.'],
        ['title' => 'Secure & reliable', 'text' => 'Keep your tenant and payment records safe with a cloud-ready setup and clear audit trails.'],
        ['title' => 'Clear dashboards', 'text' => 'See what is due, what was paid, and what needs action—without digging through spreadsheets.'],
        ['title' => 'Accurate invoicing', 'text' => 'Rent, utilities, and maintenance are tracked separately so invoices and balances remain correct.'],
    ]);

    $featuresTitle = site_setting('home_features_title', 'Complete Property Management Software Features');
    $featuresSubtitle = site_setting('home_features_subtitle', 'Streamline your rental business with our comprehensive property management tools designed for landlords and real estate professionals in Kenya');
    $featuresCards = site_setting_json('home_features_cards_json', [
        ['icon' => 'bi bi-house-door', 'title' => 'Property Management', 'text' => 'Manage unlimited properties, units, and tenants from one centralized dashboard. Track occupancy rates, rental income, and property performance in real-time.'],
        ['icon' => 'bi bi-cash-coin', 'title' => 'Rent Collection & Tenant Portal', 'text' => 'Collect rent via M-PESA and give tenants self-service access to pay, view balances, and track payment history—anytime, anywhere.'],
        ['icon' => 'bi bi-file-earmark-text', 'title' => 'Lease Management System', 'text' => 'Create digital lease agreements, track lease terms, and receive automated renewal reminders. Simplify tenant onboarding and documentation.'],
        ['icon' => 'bi bi-graph-up', 'title' => 'Invoices & Financial Reports', 'text' => 'Generate invoices and receipts, and access clear reporting on income, expenses, and performance for confident decision-making.'],
        ['icon' => 'bi bi-bell', 'title' => 'Automated Notifications', 'text' => 'Stay informed with automated SMS and email notifications for rent due dates, maintenance updates, lease renewals, and payment confirmations.'],
        ['icon' => 'bi bi-lightning-charge', 'title' => 'Utilities Management', 'text' => 'Track metered and flat-rate utilities, readings, charges, and payments—so utilities are always clearly separated from rent.'],
        ['icon' => 'bi bi-tools', 'title' => 'Maintenance Management', 'text' => 'Log requests, assign work, track progress, and record maintenance costs with clear references for invoices and statements.'],
        ['icon' => 'bi bi-cash-stack', 'title' => 'Expense Tracking', 'text' => 'Record property expenses and keep a clear view of profitability with statements and reports by property and time period.'],
    ]);

    $testimonialsTitle = site_setting('home_testimonials_title', 'Trusted by Property Managers Across Kenya');
    $testimonialsSubtitle = site_setting('home_testimonials_subtitle', 'See how landlords and real estate professionals are transforming their property management with RentSmart');
    $testimonials = site_setting_json('home_testimonials_json', [
        ['name' => 'Mercy Wanjiru', 'role' => 'Property Manager', 'text' => '"RentSmart has completely transformed how we manage our properties. The automated rent collection and reporting features save us hours every week."'],
        ['name' => 'James Kamau', 'role' => 'Landlord', 'text' => '"The tenant portal has made communication so much easier. My tenants love being able to pay rent and submit maintenance requests online."'],
        ['name' => 'David Kibara', 'role' => 'Real Estate Agent', 'text' => '"The financial reports and analytics help me make data-driven decisions. RentSmart has helped us increase our property portfolio\'s performance."'],
    ]);

    $faqTitle = site_setting('home_faq_title', 'Frequently Asked Questions');
    $faqSubtitle = site_setting('home_faq_subtitle', 'Everything you need to know about RentSmart property management software');
    $faqItems = site_setting_json('home_faq_items_json', [
        ['q' => 'What is RentSmart property management software?', 'a' => 'RentSmart is a comprehensive cloud-based property management system designed for landlords, property managers, and real estate agents in Kenya. It helps you manage properties, tenants, rent collection, maintenance, utilities, and financial reporting all in one platform. With M-PESA integration and automated features, RentSmart simplifies rental property management.'],
        ['q' => 'How long is the free trial period?', 'a' => 'RentSmart offers a generous 7-day free trial with full access to all features. No credit card required to start. You can explore all property management features, add properties and tenants, collect rent, and generate reports during the trial period. After 7-day, you can choose a plan that fits your needs.'],
        ['q' => 'Does RentSmart integrate with M-PESA?', 'a' => 'Yes! RentSmart has full M-PESA integration for seamless rent collection. Tenants can pay rent directly through M-PESA, and payments are automatically recorded in the system. You\'ll receive instant notifications when payments are made, and the system automatically reconciles payments with tenant accounts.'],
        ['q' => 'How many properties can I manage with RentSmart?', 'a' => 'The number of properties you can manage depends on your subscription plan. Our Basic plan supports up to 10 properties, Professional plan up to 50 properties, and Enterprise plan offers unlimited properties. Each property can have multiple units, and you can manage all of them from a single dashboard.'],
        ['q' => 'Can tenants access the system?', 'a' => 'Yes! RentSmart includes a dedicated tenant portal where tenants can log in to view their lease details, make rent payments, submit maintenance requests, and access important documents. This self-service portal reduces your workload and improves tenant satisfaction.'],
        ['q' => 'Is my data secure with RentSmart?', 'a' => 'Absolutely! RentSmart uses bank-level security with SSL encryption to protect your data. All information is stored on secure cloud servers with automatic daily backups. We comply with data protection regulations and never share your information with third parties. Your property and tenant data is safe with us.'],
        ['q' => 'What kind of reports can I generate?', 'a' => 'RentSmart provides comprehensive financial and operational reports including: income statements, rent collection reports, occupancy reports, expense tracking, tenant payment history, maintenance reports, utility billing reports, and property performance analytics. All reports can be exported to PDF or Excel for easy sharing.'],
        ['q' => 'Do you offer customer support?', 'a' => 'Yes! We provide excellent customer support through email at rentsmart@timestentechnologies.co.ke and phone at +254 795 155 230. Our Kenyan support team is ready to help you with setup, training, and any questions you may have. We also offer video tutorials and documentation to help you get started.'],
    ]);

    $ctaTitle = site_setting('home_cta_title', 'Transform Your Property Management Today');
    $ctaDescription = site_setting('home_cta_description', 'Join hundreds of landlords and property managers in Kenya who are simplifying their rental business with RentSmart. Start your free trial now!');
    $ctaButtonText = site_setting('home_cta_button_text', 'Start Your 7-Day Free Trial');
    $ctaFootnote = site_setting('home_cta_footnote', 'No credit card required');
    ?>
    <?php $activePage = 'home'; require __DIR__ . '/partials/public_header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold"><?= htmlspecialchars($heroTitle) ?></h1>
                    <p class="lead"><?= htmlspecialchars($heroSubtitle) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#" class="btn btn-accent btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="bi bi-rocket-takeoff me-2"></i><?= htmlspecialchars($heroPrimaryText) ?>
                        </a>
                        <a href="#features" class="btn btn-outline-accent btn-lg"><?= htmlspecialchars($heroSecondaryText) ?></a>
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
                <?php foreach ($stats as $s): ?>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?= htmlspecialchars((string)($s['number'] ?? '')) ?></div>
                            <div class="stats-label"><?= htmlspecialchars((string)($s['label'] ?? '')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 split-section">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <div style="background: transparent !important;">
                    <img src="<?= htmlspecialchars($splitImageUrl) ?>" alt="RentSmart" class="img-fluid">
                </div>

                </div>
                <div class="col-lg-6">
                    <div class="split-badge mb-3">
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span><?= htmlspecialchars($splitBadge) ?></span>
                    </div>
                    <h2 class="fw-bold mb-3 split-title"><?= htmlspecialchars($splitTitle) ?></h2>
                    <p class="text-muted mb-4"><?= htmlspecialchars($splitDescription) ?></p>
                    <ul class="split-list mb-4">
                        <?php foreach ($splitBullets as $b): ?>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    <strong><?= htmlspecialchars((string)($b['title'] ?? '')) ?></strong><br>
                                    <span class="text-muted"><?= htmlspecialchars((string)($b['text'] ?? '')) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#" class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started</a>
                        <a href="#features" class="btn btn-outline-accent">See Features</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-5 why-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="why-chip mb-3">
                    <i class="bi bi-stars"></i>
                    <span>Why RentSmart</span>
                </div>
                <h2 class="display-5 fw-bold"><?= htmlspecialchars($whyTitle) ?></h2>
                <p class="lead text-muted mb-0"><?= htmlspecialchars($whySubtitle) ?></p>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-8">
                    <div class="row g-4">
                        <?php foreach ($whyCards as $idx => $card): ?>
                            <?php if ($idx >= 4) { break; } ?>
                            <div class="col-md-6">
                                <div class="why-card">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="why-icon"><i class="bi bi-check2-circle"></i></div>
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars((string)($card['title'] ?? '')) ?></h5>
                                            <p class="text-muted mb-0"><?= htmlspecialchars((string)($card['text'] ?? '')) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="why-panel p-4 h-100">
                        <h4 class="fw-bold mb-3">Perfect for</h4>
                        <div class="d-grid gap-3">
                            <div class="d-flex gap-3">
                                <div class="why-icon" style="background: rgba(107,62,153,.10); color: var(--primary-color);"><i class="bi bi-building"></i></div>
                                <div>
                                    <div class="fw-semibold">Individual Landlords</div>
                                    <div class="text-muted small">Manage 1-10 properties efficiently.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="why-icon" style="background: rgba(107,62,153,.10); color: var(--primary-color);"><i class="bi bi-buildings"></i></div>
                                <div>
                                    <div class="fw-semibold">Property Managers</div>
                                    <div class="text-muted small">Handle multiple properties and clients.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="why-icon" style="background: rgba(107,62,153,.10); color: var(--primary-color);"><i class="bi bi-person-badge"></i></div>
                                <div>
                                    <div class="fw-semibold">Real Estate Agents</div>
                                    <div class="text-muted small">Track rentals and commissions.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="why-icon" style="background: rgba(107,62,153,.10); color: var(--primary-color);"><i class="bi bi-shop"></i></div>
                                <div>
                                    <div class="fw-semibold">Commercial Properties</div>
                                    <div class="text-muted small">Manage offices, shops, and warehouses.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="why-icon" style="background: rgba(107,62,153,.10); color: var(--primary-color);"><i class="bi bi-houses"></i></div>
                                <div>
                                    <div class="fw-semibold">Residential Properties</div>
                                    <div class="text-muted small">Apartments, houses, and condos.</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 d-grid">
                            <a href="#" class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#registerModal">Start Free Trial</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold"><?= htmlspecialchars($featuresTitle) ?></h2>
                <p class="lead text-muted"><?= htmlspecialchars($featuresSubtitle) ?></p>
            </div>
            <div class="row g-4">
                <?php foreach ($featuresCards as $card): ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="feature-card text-center p-4">
                            <div class="feature-icon-circle mx-auto">
                                <i class="<?= htmlspecialchars((string)($card['icon'] ?? 'bi bi-star')) ?>"></i>
                            </div>
                            <h3><?= htmlspecialchars((string)($card['title'] ?? '')) ?></h3>
                            <p><?= htmlspecialchars((string)($card['text'] ?? '')) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 pricing-section">
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
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="pricing-card <?= $plan['name'] === 'Professional' ? 'featured' : '' ?>">
                        <h3 class="fw-bold mb-3 plan-name"><?= htmlspecialchars($plan['name']) ?></h3>
                        <div class="mb-3">
                            <?php if (strcasecmp((string)$plan['name'], 'Enterprise') === 0): ?>
                                <div class="pricing-price">Custom Pricing</div>
                                <div class="pricing-subtext small">Let’s tailor a plan for your portfolio.</div>
                            <?php else: ?>
                                <div class="d-flex align-items-baseline">
                                    <div class="pricing-price">Ksh <?= number_format($plan['price']) ?></div>
                                    <div class="text-muted ms-2">/month</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <?php
                            $nameLower = strtolower($plan['name']);
                            $limit = null;
                            if (isset($plan['property_limit']) && $plan['property_limit'] !== null && $plan['property_limit'] !== '' && (int)$plan['property_limit'] > 0) {
                                $limit = (int)$plan['property_limit'];
                            } else {
                                if ($nameLower === 'basic') { $limit = 10; }
                                elseif ($nameLower === 'professional') { $limit = 50; }
                                elseif ($nameLower === 'enterprise') { $limit = null; }
                            }
                            if ($limit === null) {
                                echo '<span class="badge bg-success">Unlimited Properties</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Up to ' . (int)$limit . ' Properties</span>';
                            }
                            ?>
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
                            <a href="<?= BASE_URL ?>/contact" class="btn <?= $plan['name'] === 'Enterprise' ? 'btn-gradient' : 'btn-outline-primary' ?> w-100">Contact Sales</a>
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
                <h2 class="display-5 fw-bold"><?= htmlspecialchars($testimonialsTitle) ?></h2>
                <p class="lead text-muted"><?= htmlspecialchars($testimonialsSubtitle) ?></p>
            </div>
            <div class="row g-4">
                <?php foreach ($testimonials as $t): ?>
                    <div class="col-md-4">
                        <div class="testimonial-card">
                            <div class="d-flex align-items-center mb-3">
                                <div class="testimonial-avatar">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars((string)($t['name'] ?? '')) ?></h5>
                                    <small class="text-muted"><?= htmlspecialchars((string)($t['role'] ?? '')) ?></small>
                                </div>
                            </div>
                            <p><?= htmlspecialchars((string)($t['text'] ?? '')) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold"><?= htmlspecialchars($faqTitle) ?></h2>
                <p class="lead text-muted"><?= htmlspecialchars($faqSubtitle) ?></p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="faqAccordion">
                        <?php foreach (array_values($faqItems) as $i => $item): ?>
                            <?php
                                $collapseId = 'faq' . ($i + 1);
                                $isFirst = $i === 0;
                            ?>
                            <div class="accordion-item mb-3 border-0 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button <?= $isFirst ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId) ?>">
                                        <?= htmlspecialchars((string)($item['q'] ?? '')) ?>
                                    </button>
                                </h3>
                                <div id="<?= htmlspecialchars($collapseId) ?>" class="accordion-collapse collapse <?= $isFirst ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?= htmlspecialchars((string)($item['a'] ?? '')) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4"><?= htmlspecialchars($ctaTitle) ?></h2>
            <p class="lead mb-4"><?= htmlspecialchars($ctaDescription) ?></p>
            <a class="btn btn-gradient btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="bi bi-rocket-takeoff me-2"></i><?= htmlspecialchars($ctaButtonText) ?>
            </a>
            
            <p class="mt-3 text-muted"><?= htmlspecialchars($ctaFootnote) ?></p>
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
                        <a href="https://www.linkedin.com/posts/timestentechnologies_proptech-propertymanagement-rentsmart-activity-7413190378020925440-JRdI?utm_source=share&utm_medium=member_desktop&rcm=ACoAADXDMdEBsC18bIJ4cOHS2WbzS9hlKU1YxY4" target="_blank" class="text-white mx-2" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
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
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:rentsmart@timestentechnologies.co.ke" class="text-white-50">rentsmart@timestentechnologies.co.ke</a></li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:+254795155230" class="text-white-50">+254 795 155 230</a></li>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Nairobi, Kenya</li>
                    </ul>
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/privacy-policy" class="text-white-50 d-block mb-2">Privacy Policy</a>
                        <a href="<?= BASE_URL ?>/terms" class="text-white-50 d-block mb-2">Terms of Service</a>
    
                        <a href="<?= BASE_URL ?>/contact" class="text-white-50 d-block mb-2">
                             Contact Us
                        </a>
                 
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

                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Password" required>
                                <label for="loginPassword">Password</label>
                            </div>
                            <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="loginRemember" name="remember">
                            <label class="form-check-label" for="loginRemember">Remember me</label>
                        </div>

                        <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
                        </button>

                        <div class="text-center mb-3">
                            <a href="<?= BASE_URL ?>/forgot-password" class="text-muted text-decoration-none">Forgot your password?</a>
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

                        <div class="input-group mb-2">
                            <div class="form-floating flex-grow-1">
                                <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Password" required>
                                <label for="registerPassword">Password</label>
                            </div>
                            <button class="btn btn-outline-secondary" type="button" id="toggleRegisterPassword" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="small text-muted mb-3" id="passwordHint">
                            Password must be at least 8 characters and include uppercase, lowercase, number, and special character.
                        </div>

                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                                <label for="registerConfirmPassword">Confirm Password</label>
                            </div>
                            <button class="btn btn-outline-secondary" type="button" id="toggleRegisterConfirmPassword" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <div class="form-floating mb-3">
                            <select class="form-select" id="registerRole" name="role" required>
                                <option value="">Select Role</option>
                                <option value="landlord">Landlord</option>
                                <option value="manager">Manager</option>
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

            // Strong password check (UX only; server also enforces)
            const hasMinLen = (password || '').length >= 8;
            const hasUpper = /[A-Z]/.test(password || '');
            const hasLower = /[a-z]/.test(password || '');
            const hasDigit = /\d/.test(password || '');
            const hasSpecial = /[^A-Za-z0-9]/.test(password || '');
            if (!hasMinLen || !hasUpper || !hasLower || !hasDigit || !hasSpecial) {
                showAlert('register', 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.');
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
            function wireToggle(btnId, inputId) {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);
                if (!btn || !input) return;
                btn.addEventListener('click', function() {
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            }

            wireToggle('toggleLoginPassword', 'loginPassword');
            wireToggle('toggleRegisterPassword', 'registerPassword');
            wireToggle('toggleRegisterConfirmPassword', 'registerConfirmPassword');

            const bars = document.querySelectorAll('.preview-bar');
            bars.forEach(bar => {
                const originalHeight = bar.style.height;
                bar.style.height = '0';
                setTimeout(() => {
                    bar.style.height = originalHeight;
                }, 100);
            });

            try {
                var h = (window.location.hash || '').replace('#', '');
                if (h === 'loginModal' || h === 'registerModal' || h === 'tenantLoginModal') {
                    var el = document.getElementById(h);
                    if (el && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                        window.bootstrap.Modal.getOrCreateInstance(el).show();
                    }
                }
            } catch (e) {}
        });
    </script>

<!-- AI Chat Widget (Public) -->
<style>
.ai-chat-fab {
    position: fixed; right: 20px; bottom: 80px; z-index: 1052;
    background: #6B3E99; color: #fff; border-radius: 999px;
    width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 16px rgba(0,0,0,0.2); cursor: pointer; transition: all 0.3s ease;
    border: none; pointer-events: auto;
}
.ai-chat-fab:hover { background: #8E5CC4; transform: scale(1.05); }
.ai-chat-panel {
    position: fixed; right: 20px; bottom: 144px; width: 320px; height: 420px; z-index: 1053;
    background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: none; flex-direction: column; font-family: system-ui, sans-serif; pointer-events: auto;
}
.ai-chat-header { padding: 10px 12px; background: #6B3E99; color: #fff; display: flex; align-items: center; justify-content: space-between; border-radius: 12px 12px 0 0; }
.ai-chat-messages { padding: 10px; gap: 8px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; }
.ai-chat-input { padding: 8px; border-top: 1px solid rgba(0,0,0,0.08); display: flex; gap: 8px; }
.ai-msg { padding: 8px 10px; border-radius: 10px; max-width: 85%; font-size: 0.9rem; }
.ai-msg.user { align-self: flex-end; background: #e9ecef; }
.ai-msg.bot { align-self: flex-start; background: #f8f9fa; }
.ai-msg.thinking { opacity: 0.6; font-style: italic; }
</style>

<div id="aiChatFab" class="ai-chat-fab" title="Ask AI">
    <i class="bi bi-robot" style="font-size: 1.25rem;"></i>
</div>

<div id="aiChatPanel" class="ai-chat-panel">
    <div class="ai-chat-header">
        <span><i class="bi bi-robot me-2"></i>Ask RentSmart AI</span>
        <button id="aiChatClose" style="background:none;border:none;color:#fff;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="aiChatMessages" class="ai-chat-messages"></div>
    <div class="ai-chat-input">
        <input id="aiChatInput" type="text" class="form-control" placeholder="Type a message..." autocomplete="off">
        <button id="aiChatSend" class="btn btn-sm btn-primary"><i class="bi bi-send"></i></button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const fab = document.getElementById('aiChatFab');
  const panel = document.getElementById('aiChatPanel');
  const closeBtn = document.getElementById('aiChatClose');
  const input = document.getElementById('aiChatInput');
  const sendBtn = document.getElementById('aiChatSend');
  const messages = document.getElementById('aiChatMessages');
  const csrf = (document.querySelector('input[name="csrf_token"]')||{}).value || (document.querySelector('meta[name="csrf-token"]')||{}).content || '';

  if (!fab || !panel) return;

  function togglePanel(show){
    panel.style.display = show ? 'flex' : 'none';
    if (show) {
      setTimeout(()=> input && input.focus(), 50);
    }
  }
  function appendMsg(text, who){
    const div = document.createElement('div');
    div.className = 'ai-msg ' + (who || 'bot');
    if (who === 'bot') {
      let html = text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/^### (.*$)/gim, '<h6>$1</h6>')
        .replace(/^## (.*$)/gim, '<h5>$1</h5>')
        .replace(/^# (.*$)/gim, '<h4>$1</h4>')
        .replace(/^\* (.+)$/gim, '<li>$1</li>')
        .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
        .replace(/\n/g, '<br>');
      div.innerHTML = html;
    } else {
      div.textContent = text;
    }
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }
  function showThinking(){
    const div = document.createElement('div');
    div.className = 'ai-msg bot thinking';
    div.innerHTML = '<em>Thinking…</em>';
    div.id = 'aiThinking';
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }
  function hideThinking(){
    const el = document.getElementById('aiThinking');
    if (el) el.remove();
  }
  async function send(){
    const text = (input.value||'').trim();
    if (!text) return;
    input.value = '';
    appendMsg(text, 'user');
    sendBtn.disabled = true;
    showThinking();
    try {
      const res = await fetch('<?= BASE_URL ?>/ai/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ message: text })
      });
      let data;
      const ct = (res.headers.get('content-type')||'').toLowerCase();
      if (ct.includes('application/json')) {
        data = await res.json().catch(()=>({success:false,message:'Invalid response'}));
      } else {
        const txt = await res.text().catch(()=> '');
        data = { success: false, message: txt || 'Invalid response' };
      }
      hideThinking();
      if (data && data.success && data.reply){
        appendMsg(data.reply, 'bot');
      } else {
        appendMsg(data.message || 'Sorry, I could not process that right now.', 'bot');
      }
    } catch (e) {
      hideThinking();
      appendMsg('Network error. Please try again.', 'bot');
    } finally {
      sendBtn.disabled = false;
    }
  }

  fab.addEventListener('click', ()=> togglePanel(panel.style.display !== 'flex'));
  closeBtn && closeBtn.addEventListener('click', ()=> togglePanel(false));
  sendBtn && sendBtn.addEventListener('click', send);
  input && input.addEventListener('keydown', (e)=>{ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); send(); }});
});
</script>

<a href="https://wa.me/254718883983?text=Hi%20RentSmart%20Support%2C%20I%20would%20like%20to%20get%20started%20with%20RentSmart.%20Please%20assist%20me." class="d-print-none" style="position: fixed; right: 20px; bottom: 24px; z-index: 1051; background: #25D366; color: #fff; border-radius: 999px; padding: 10px 14px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 6px 16px rgba(0,0,0,0.2); text-decoration: none;" target="_blank" rel="noopener"><i class="bi bi-whatsapp" style="font-size: 1.25rem;"></i></a>
</body>
</html>
