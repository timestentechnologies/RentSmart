<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/rentsmart');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - RentSmart</title>
    <?php
    $faviconUrl = $favicon ? BASE_URL . '/public/assets/images/' . $favicon : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';
    ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            line-height: 1.7;
        }
        .navbar {
            padding: 1rem 0;
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand img {
            height: 40px;
        }
        .policy-content {
            margin-top: 100px;
            margin-bottom: 60px;
        }
        h2 {
            color: #0061f2;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/home">
                <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Logo">
            </a>
            <a href="/home" class="btn btn-outline-primary">Back to Home</a>
        </div>
    </nav>

    <div class="policy-content">
        <div class="container">
            <h1 class="mb-4">Terms of Service</h1>
            
            <p class="lead">Last updated: <?= date('F d, Y') ?></p>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using RentSmart, you accept and agree to be bound by the terms and conditions of this agreement.</p>

            <h2>2. Description of Service</h2>
            <p>RentSmart provides property management software services including:</p>
            <ul>
                <li>Property and tenant management</li>
                <li>Rent collection and payment processing</li>
                <li>Financial reporting</li>
                <li>Document management</li>
            </ul>

            <h2>3. User Accounts</h2>
            <p>To use RentSmart, you must:</p>
            <ul>
                <li>Create an account with accurate information</li>
                <li>Maintain the security of your account</li>
                <li>Notify us of any unauthorized access</li>
                <li>Be at least 18 years old</li>
            </ul>

            <h2>4. Subscription and Payments</h2>
            <p>Our service is provided on a subscription basis:</p>
            <ul>
                <li>Subscription fees are billed monthly or annually</li>
                <li>Payments are non-refundable</li>
                <li>We may change pricing with 30 days notice</li>
                <li>Free trial periods are available for new users</li>
            </ul>

            <h2>5. User Responsibilities</h2>
            <p>You agree to:</p>
            <ul>
                <li>Comply with all applicable laws</li>
                <li>Maintain accurate records</li>
                <li>Protect tenant privacy</li>
                <li>Use the service responsibly</li>
            </ul>

            <h2>6. Data Ownership</h2>
            <p>You retain all rights to your data. We will:</p>
            <ul>
                <li>Protect your data security</li>
                <li>Not access your data without permission</li>
                <li>Delete your data upon account termination</li>
                <li>Allow data export in standard formats</li>
            </ul>

            <h2>7. Service Availability</h2>
            <p>While we strive for 99.9% uptime:</p>
            <ul>
                <li>We may perform scheduled maintenance</li>
                <li>Service may be occasionally interrupted</li>
                <li>We're not liable for downtime beyond our control</li>
            </ul>

            <h2>8. Termination</h2>
            <p>We may terminate service if you:</p>
            <ul>
                <li>Violate these terms</li>
                <li>Fail to pay subscription fees</li>
                <li>Engage in fraudulent activity</li>
                <li>Abuse the service</li>
            </ul>

            <h2>9. Contact Information</h2>
            <p>For questions about these terms, contact us at:</p>
            <ul>
                <li>Email: legal@rentsmart.com</li>
                <li>Phone: +254 700 000000</li>
                <li>Address: Nairobi, Kenya</li>
            </ul>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 