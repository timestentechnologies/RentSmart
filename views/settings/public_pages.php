<?php
ob_start();

$settings = $settings ?? [];

$GLOBALS['__public_pages_settings'] = $settings;

$publicDefaults = [
    'home_hero_title' => 'Property Management Made Easy',
    'home_hero_subtitle' => 'Streamline your property management with RentSmart. The all-in-one solution for landlords and property managers.',
    'home_hero_primary_text' => 'Start 7-Day Free Trial',
    'home_hero_secondary_text' => 'Learn More',

    'home_stat1_number' => '500+',
    'home_stat1_label' => 'Properties Managed',
    'home_stat2_number' => '500+',
    'home_stat2_label' => 'Happy Clients',
    'home_stat3_number' => '99%',
    'home_stat3_label' => 'Customer Satisfaction',
    'home_stat4_number' => '24/7',
    'home_stat4_label' => 'Support Available',

    'home_split_badge' => 'All-in-one platform',
    'home_split_title' => 'Manage Rent, Utilities & Maintenance in One Place',
    'home_split_description' => 'Track payments, utilities, and maintenance requests with clear records and automated invoicing—so landlords and tenants always know what is due and what has been paid.',
    'home_split_bullets_json' => json_encode([
        ['title' => 'Accurate payment types', 'text' => 'Rent, utilities, and maintenance always recorded correctly.'],
        ['title' => 'Automated invoicing', 'text' => 'Invoices update based on what was paid—no confusion.'],
        ['title' => 'Tenant self-service', 'text' => 'Tenants can pay and track balances from the portal.'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

    'home_why_title' => 'Why Choose RentSmart for Property Management?',
    'home_why_subtitle' => 'A modern, Kenyan-ready platform for landlords, managers, and agents—built for speed, clarity, and accurate records.',
    'home_why_cards_json' => json_encode([
        ['title' => 'M-PESA ready', 'text' => 'Accept payments and keep references organized for quick verification and reporting.'],
        ['title' => 'Secure & reliable', 'text' => 'Keep your tenant and payment records safe with a cloud-ready setup and clear audit trails.'],
        ['title' => 'Clear dashboards', 'text' => 'See what is due, what was paid, and what needs action—without digging through spreadsheets.'],
        ['title' => 'Accurate invoicing', 'text' => 'Rent, utilities, and maintenance are tracked separately so invoices and balances remain correct.'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

    'home_features_title' => 'Complete Property Management Software Features',
    'home_features_subtitle' => 'Streamline your rental business with our comprehensive property management tools designed for landlords and real estate professionals in Kenya',
    'home_features_cards_json' => json_encode([
        ['icon' => 'bi bi-house-door', 'title' => 'Property Management', 'text' => 'Manage unlimited properties, units, and tenants from one centralized dashboard. Track occupancy rates, rental income, and property performance in real-time.'],
        ['icon' => 'bi bi-cash-coin', 'title' => 'Rent Collection & Tenant Portal', 'text' => 'Collect rent via M-PESA and give tenants self-service access to pay, view balances, and track payment history—anytime, anywhere.'],
        ['icon' => 'bi bi-file-earmark-text', 'title' => 'Lease Management System', 'text' => 'Create digital lease agreements, track lease terms, and receive automated renewal reminders. Simplify tenant onboarding and documentation.'],
        ['icon' => 'bi bi-graph-up', 'title' => 'Invoices & Financial Reports', 'text' => 'Generate invoices and receipts, and access clear reporting on income, expenses, and performance for confident decision-making.'],
        ['icon' => 'bi bi-bell', 'title' => 'Automated Notifications', 'text' => 'Stay informed with automated SMS and email notifications for rent due dates, maintenance updates, lease renewals, and payment confirmations.'],
        ['icon' => 'bi bi-lightning-charge', 'title' => 'Utilities Management', 'text' => 'Track metered and flat-rate utilities, readings, charges, and payments—so utilities are always clearly separated from rent.'],
        ['icon' => 'bi bi-tools', 'title' => 'Maintenance Management', 'text' => 'Log requests, assign work, track progress, and record maintenance costs with clear references for invoices and statements.'],
        ['icon' => 'bi bi-cash-stack', 'title' => 'Expense Tracking', 'text' => 'Record property expenses and keep a clear view of profitability with statements and reports by property and time period.'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

    'home_testimonials_title' => 'Trusted by Property Managers Across Kenya',
    'home_testimonials_subtitle' => 'See how landlords and real estate professionals are transforming their property management with RentSmart',
    'home_testimonials_json' => json_encode([
        ['name' => 'Mercy Wanjiru', 'role' => 'Property Manager', 'text' => '"RentSmart has completely transformed how we manage our properties. The automated rent collection and reporting features save us hours every week."'],
        ['name' => 'James Kamau', 'role' => 'Landlord', 'text' => '"The tenant portal has made communication so much easier. My tenants love being able to pay rent and submit maintenance requests online."'],
        ['name' => 'David Kibara', 'role' => 'Real Estate Agent', 'text' => '"The financial reports and analytics help me make data-driven decisions. RentSmart has helped us increase our property portfolio\'s performance."'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

    'home_faq_title' => 'Frequently Asked Questions',
    'home_faq_subtitle' => 'Everything you need to know about RentSmart property management software',
    'home_faq_items_json' => json_encode([
        ['q' => 'What is RentSmart property management software?', 'a' => 'RentSmart is a comprehensive cloud-based property management system designed for landlords, property managers, and real estate agents in Kenya. It helps you manage properties, tenants, rent collection, maintenance, utilities, and financial reporting all in one platform. With M-PESA integration and automated features, RentSmart simplifies rental property management.'],
        ['q' => 'How long is the free trial period?', 'a' => 'RentSmart offers a generous 7-day free trial with full access to all features. No credit card required to start. You can explore all property management features, add properties and tenants, collect rent, and generate reports during the trial period. After 7-day, you can choose a plan that fits your needs.'],
        ['q' => 'Does RentSmart integrate with M-PESA?', 'a' => 'Yes! RentSmart has full M-PESA integration for seamless rent collection. Tenants can pay rent directly through M-PESA, and payments are automatically recorded in the system. You\'ll receive instant notifications when payments are made, and the system automatically reconciles payments with tenant accounts.'],
        ['q' => 'How many properties can I manage with RentSmart?', 'a' => 'The number of properties you can manage depends on your subscription plan. Our Basic plan supports up to 10 properties, Professional plan up to 50 properties, and Enterprise plan offers unlimited properties. Each property can have multiple units, and you can manage all of them from a single dashboard.'],
        ['q' => 'Can tenants access the system?', 'a' => 'Yes! RentSmart includes a dedicated tenant portal where tenants can log in to view their lease details, make rent payments, submit maintenance requests, and access important documents. This self-service portal reduces your workload and improves tenant satisfaction.'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

    'home_cta_title' => 'Transform Your Property Management Today',
    'home_cta_description' => 'Join hundreds of landlords and property managers in Kenya who are simplifying their rental business with RentSmart. Start your free trial now!',
    'home_cta_button_text' => 'Start Your 7-Day Free Trial',
    'home_cta_footnote' => 'No credit card required',

    'contact_hero_title' => 'Contact Sales & Support',
    'contact_hero_subtitle' => "We'd love to hear from you. Send us a message and we'll respond shortly.",

    'terms_header' => 'Terms of Service',
    'privacy_header' => 'Privacy Policy',

    'terms_body_html' => '<p class="lead">Last updated: ' . date('F d, Y') . '</p>'
        . '<h2>1. Acceptance of Terms</h2><p>By accessing and using RentSmart, you accept and agree to be bound by the terms and conditions of this agreement.</p>'
        . '<h2>2. Description of Service</h2><p>RentSmart provides property management software services including:</p>'
        . '<ul><li>Property and tenant management</li><li>Rent collection and payment processing</li><li>Financial reporting</li><li>Document management</li></ul>'
        . '<h2>3. User Accounts</h2><p>To use RentSmart, you must:</p>'
        . '<ul><li>Create an account with accurate information</li><li>Maintain the security of your account</li><li>Notify us of any unauthorized access</li><li>Be at least 18 years old</li></ul>'
        . '<h2>4. Subscription and Payments</h2><p>Our service is provided on a subscription basis:</p>'
        . '<ul><li>Subscription fees are billed monthly or annually</li><li>Payments are non-refundable</li><li>We may change pricing with 30 days notice</li><li>Free trial periods are available for new users</li></ul>'
        . '<h2>5. User Responsibilities</h2><p>You agree to:</p>'
        . '<ul><li>Comply with all applicable laws</li><li>Maintain accurate records</li><li>Protect tenant privacy</li><li>Use the service responsibly</li></ul>'
        . '<h2>6. Data Ownership</h2><p>You retain all rights to your data. We will:</p>'
        . '<ul><li>Protect your data security</li><li>Not access your data without permission</li><li>Delete your data upon account termination</li><li>Allow data export in standard formats</li></ul>'
        . '<h2>7. Service Availability</h2><p>While we strive for 99.9% uptime:</p>'
        . '<ul><li>We may perform scheduled maintenance</li><li>Service may be occasionally interrupted</li><li>We\'re not liable for downtime beyond our control</li></ul>'
        . '<h2>8. Termination</h2><p>We may terminate service if you:</p>'
        . '<ul><li>Violate these terms</li><li>Fail to pay subscription fees</li><li>Engage in fraudulent activity</li><li>Abuse the service</li></ul>'
        . '<h2>9. Contact Information</h2><p>For questions about these terms, contact us at:</p>'
        . '<ul><li>Email: legal@rentsmart.com</li><li>Phone: +254 700 000000</li><li>Address: Nairobi, Kenya</li></ul>',

    'privacy_body_html' => '<p class="lead">Last updated: ' . date('F d, Y') . '</p>'
        . '<h2>1. Information We Collect</h2><p>We collect information that you provide directly to us, including:</p>'
        . '<ul><li>Name and contact information</li><li>Property management details</li><li>Payment information</li><li>Communication preferences</li></ul>'
        . '<h2>2. How We Use Your Information</h2><p>We use the information we collect to:</p>'
        . '<ul><li>Provide and maintain our services</li><li>Process your transactions</li><li>Send you important updates</li><li>Improve our services</li><li>Comply with legal obligations</li></ul>'
        . '<h2>3. Information Sharing</h2><p>We do not sell your personal information. We may share your information with:</p>'
        . '<ul><li>Service providers who assist in our operations</li><li>Legal authorities when required by law</li><li>Business partners with your consent</li></ul>'
        . '<h2>4. Data Security</h2><p>We implement appropriate security measures to protect your personal information, including:</p>'
        . '<ul><li>Encryption of sensitive data</li><li>Regular security assessments</li><li>Access controls and authentication</li><li>Secure data storage</li></ul>'
        . '<h2>5. Your Rights</h2><p>You have the right to:</p>'
        . '<ul><li>Access your personal information</li><li>Correct inaccurate data</li><li>Request deletion of your data</li><li>Opt-out of marketing communications</li></ul>'
        . '<h2>6. Contact Us</h2><p>If you have any questions about this Privacy Policy, please contact us at:</p>'
        . '<ul><li>Email: privacy@rentsmart.com</li><li>Phone: +254 700 000000</li><li>Address: Nairobi, Kenya</li></ul>',

    'contact_phone' => '+254 795 155 230',
    'contact_email' => 'rentsmart@timestentechnologies.co.ke',
    'contact_address' => 'Nairobi, Kenya',

    'docs_hero_title' => 'RentSmart Documentation',
    'docs_hero_subtitle' => 'Comprehensive guide for landlords, property managers, agents, caretakers, tenants, and administrators.',
    'docs_body_html' => '<section id="getting-started"><h2>Getting Started</h2><p>Sign up for a free trial, configure your company profile, add properties and units, and invite team members.</p>'
        . '<ol><li>Register an account from the homepage.</li><li>Go to Settings → update Company name, logo, email, and phone.</li><li>Add Properties and Units; set rent amounts and occupancy details.</li><li>Add Tenants and create Leases for units.</li><li>Invite your team with role-specific permissions.</li></ol></section><hr>'
        . '<section id="user-roles"><h2>User Roles & Permissions</h2><p>Assign roles to users to control access:</p>'
        . '<ul><li><strong>Admin:</strong> Full access to system features, reporting, user management.</li><li><strong>Landlord:</strong> Monitor properties, tenants, leases, payments, notices.</li><li><strong>Property Manager / Agent:</strong> Manage properties, leases, tenants, maintenance, and notices.</li><li><strong>Caretaker:</strong> Update unit status, submit maintenance updates.</li><li><strong>Tenant:</strong> Access lease details, make payments, submit maintenance requests, and view notices.</li></ul>'
        . '<p><em>Tip:</em> Use role-based permissions to maintain security and ensure accountability.</p></section><hr>'
        . '<section id="properties"><h2>Properties & Units</h2><p>Manage property and unit details efficiently:</p>'
        . '<ul><li><strong>Properties:</strong> Create, edit, and upload documents or images for each property.</li><li><strong>Units:</strong> Add multiple units, assign rent, occupancy status, and link tenants via leases.</li><li>Track vacant and occupied units for better occupancy management.</li></ul>'
        . '<p><em>Tip:</em> Update unit details whenever tenants move in/out to keep records accurate.</p></section><hr>'
        . '<section id="tenants"><h2>Tenants & Leases</h2><p>Create and manage tenants and their leases:</p>'
        . '<ul><li>Link active leases to units, setting rent, deposit, and lease duration.</li><li>Track tenant payment history, arrears, and lease expiry.</li><li>Generate reports for tenant balances and overdue payments.</li></ul></section><hr>'
        . '<section id="payments"><h2>Payments & Invoices</h2><p>Track all payments and generate invoices:</p>'
        . '<ul><li>Record rent and utility payments (supports M-PESA manual logs).</li><li>Create invoices with multiple line items, apply tax, and post to ledger.</li><li>Download or email invoices in PDF format to tenants.</li><li>Automatic ledger posting ensures accounting consistency.</li></ul>'
        . '<p><em>Tip:</em> Reconcile payments weekly to avoid discrepancies.</p></section><hr>'
        . '<section id="maintenance"><h2>Maintenance</h2><p>Track maintenance requests and costs:</p>'
        . '<ul><li>Tenants or caretakers can submit requests via the system.</li><li>Record actual maintenance cost; optionally deduct from tenant rent balance.</li><li>Track maintenance status and completion dates for reporting.</li></ul></section><hr>'
        . '<section id="utilities"><h2>Utilities</h2><p>Manage utilities efficiently:</p>'
        . '<ul><li>Add metered or flat utilities per unit.</li><li>Record readings or monthly charges.</li><li>Include utilities in tenant invoices for accurate billing.</li></ul></section><hr>'
        . '<section id="messaging"><h2>Messaging</h2><p>Communicate securely within RentSmart:</p>'
        . '<ul><li>Chat with tenants, managers, caretakers, and admins in real-time.</li><li>Broadcast notices or individual messages.</li><li>Keep all communication centralized for auditing and tracking.</li></ul></section><hr>'
        . '<section id="notices"><h2>Notices</h2><p>Broadcast or schedule notices to tenants and staff:</p>'
        . '<ul><li>Send notices by property, unit, or individual tenant.</li><li>Schedule future notifications for rent reminders or announcements.</li><li>Track which tenants have read the notices.</li></ul></section><hr>'
        . '<section id="accounting"><h2>Accounting</h2><p>Comprehensive accounting module:</p>'
        . '<ul><li>Chart of Accounts, General Ledger, Trial Balance.</li><li>Profit & Loss, Balance Sheet reporting.</li><li>Invoices automatically post to accounts receivable/revenue.</li></ul></section><hr>'
        . '<section id="esign"><h2>E‑Signatures</h2><p>Digitally sign documents securely:</p>'
        . '<ul><li>Create signature requests for tenants, managers, or staff.</li><li>Share public links for document signing.</li><li>Track signature status and download signed documents.</li></ul></section><hr>'
        . '<section id="tenant-portal"><h2>Tenant Portal</h2><p>Tenant self-service portal:</p>'
        . '<ul><li>View active lease, rent due, and payment history.</li><li>Submit maintenance requests and track progress.</li><li>Receive notices and communicate with property managers.</li></ul></section><hr>'
        . '<section id="reports"><h2>Reports & Analytics</h2><p>Generate detailed reports for operational insights:</p>'
        . '<ul><li>Occupancy & vacancy reports per property/unit.</li><li>Tenant payment history & arrears.</li><li>Maintenance cost and completion analytics.</li><li>Revenue, Profit & Loss, Trial Balance, Balance Sheet.</li><li>Export reports in PDF, Excel, or CSV format.</li></ul></section><hr>'
        . '<section id="notifications"><h2>Notifications & Alerts</h2><p>Automated alerts for timely actions:</p>'
        . '<ul><li>Upcoming rent due notifications for tenants.</li><li>Lease expiry alerts for managers and landlords.</li><li>Maintenance updates and completion notifications.</li><li>System alerts for unpaid balances or failed payments.</li></ul></section><hr>'
        . '<section id="mobile-access"><h2>Mobile & Accessibility</h2><p>RentSmart is fully responsive and mobile-friendly:</p>'
        . '<ul><li>Access all core features on tablets and smartphones.</li><li>Tenants can pay rent and submit requests on the go.</li><li>Receive push notifications for alerts and updates.</li></ul></section><hr>'
        . '<section id="tips-best-practices"><h2>Tips & Best Practices</h2><ul><li>Update property and unit details regularly for accurate records.</li><li>Use scheduled maintenance tracking to prevent overdue repairs.</li><li>Reconcile payments weekly to avoid accounting errors.</li><li>Encourage tenants to use the portal for payments and requests.</li><li>Regularly back up system data and maintain user role security.</li></ul></section><hr>'
        . '<section id="faq"><h2>FAQ & Support</h2><p>For questions or help, contact:</p>'
        . '<ul><li>Email: <a href="mailto:rentsmart@timestentechnologies.co.ke">rentsmart@timestentechnologies.co.ke</a></li><li>Phone: +254 795 155 230</li><li>In-app Messaging for real-time support</li><li>Visit the Contact page for additional resources.</li></ul></section>',
];

$GLOBALS['__public_pages_defaults'] = $publicDefaults;

function setting_raw($key, $default = '') {
    $settings = $GLOBALS['__public_pages_settings'] ?? [];
    $publicDefaults = $GLOBALS['__public_pages_defaults'] ?? [];
    if (isset($settings[$key]) && $settings[$key] !== '') {
        return $settings[$key];
    }
    if ($default !== '') {
        return $default;
    }
    return $publicDefaults[$key] ?? '';
}

function setting_json_pretty($key, $defaultJson = '[]') {
    $raw = trim((string)setting_raw($key, ''));
    if ($raw === '') {
        $fallback = setting_raw($key, '');
        if ($fallback !== '') {
            $decodedFallback = json_decode((string)$fallback, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decodedFallback, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        return $defaultJson;
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $defaultJson;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Public Pages Content</h4>
            <div class="text-muted" style="font-size: 12px;">Template version: 2026-02-11-01</div>
            <p class="text-muted">Manage homepage and public page text/images. If a field is left blank, the existing hardcoded content remains as fallback.</p>
        </div>
        <a href="<?= BASE_URL ?>/settings" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Settings
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/updatePublicPages" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-homepage" data-bs-toggle="tab" data-bs-target="#tab-homepage-pane" type="button" role="tab">Homepage</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-pages" data-bs-toggle="tab" data-bs-target="#tab-pages-pane" type="button" role="tab">Policies & Pages</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-homepage-pane" role="tabpanel" aria-labelledby="tab-homepage">

                        <h6 class="fw-bold mb-3">Homepage - Hero</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Hero Title</label>
                                <input type="text" class="form-control" name="home_hero_title" value="<?= htmlspecialchars(setting_raw('home_hero_title', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hero Subtitle</label>
                                <input type="text" class="form-control" name="home_hero_subtitle" value="<?= htmlspecialchars(setting_raw('home_hero_subtitle', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hero Primary Button Text</label>
                                <input type="text" class="form-control" name="home_hero_primary_text" value="<?= htmlspecialchars(setting_raw('home_hero_primary_text', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hero Secondary Button Text</label>
                                <input type="text" class="form-control" name="home_hero_secondary_text" value="<?= htmlspecialchars(setting_raw('home_hero_secondary_text', '')) ?>">
                            </div>
                        </div>

                <h6 class="fw-bold mb-3">Homepage - Stats (4 cards)</h6>
                <div class="row g-3 mb-4">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="col-md-3">
                            <label class="form-label">Stat <?= $i ?> Number</label>
                            <input type="text" class="form-control" name="home_stat<?= $i ?>_number" value="<?= htmlspecialchars(setting_raw('home_stat' . $i . '_number', '')) ?>">
                            <label class="form-label mt-2">Stat <?= $i ?> Label</label>
                            <input type="text" class="form-control" name="home_stat<?= $i ?>_label" value="<?= htmlspecialchars(setting_raw('home_stat' . $i . '_label', '')) ?>">
                        </div>
                    <?php endfor; ?>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Split Section (below stats)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Split Badge Text</label>
                        <input type="text" class="form-control" name="home_split_badge" value="<?= htmlspecialchars(setting_raw('home_split_badge', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Split Title</label>
                        <input type="text" class="form-control" name="home_split_title" value="<?= htmlspecialchars(setting_raw('home_split_title', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Split Description</label>
                        <textarea class="form-control" name="home_split_description" rows="3"><?= htmlspecialchars(setting_raw('home_split_description', '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Split Image</label>
                        <input type="file" class="form-control" name="home_split_image" accept="image/*">
                        <?php $currentSplitImage = (string)setting_raw('home_split_image', ''); ?>
                        <?php if ($currentSplitImage !== ''): ?>
                            <div class="form-text">Current: <?= htmlspecialchars($currentSplitImage) ?></div>
                            <div class="mt-2">
                                <img
                                    src="<?= htmlspecialchars(asset('images/' . $currentSplitImage)) ?>"
                                    alt="Current split image"
                                    class="img-fluid rounded border"
                                    style="max-height: 140px;"
                                    onerror="this.style.display='none'"
                                >
                            </div>
                        <?php else: ?>
                            <div class="form-text">Current: Default</div>
                            <div class="mt-2">
                                <img
                                    src="<?= htmlspecialchars(asset('images/new.png')) ?>"
                                    alt="Default split image"
                                    class="img-fluid rounded border"
                                    style="max-height: 140px;"
                                    onerror="this.style.display='none'"
                                >
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Split Bullets (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_split_bullets_json" rows="8" placeholder='[{"title":"...","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_split_bullets_json', '[]')) ?></textarea>
                        <div class="form-text">Example: [{"title":"Accurate payment types","text":"Rent, utilities..."}]</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Why Choose Us</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_why_title" value="<?= htmlspecialchars(setting_raw('home_why_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_why_subtitle" value="<?= htmlspecialchars(setting_raw('home_why_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Cards (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_why_cards_json" rows="10" placeholder='[{"title":"M-PESA ready","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_why_cards_json', '[]')) ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Features</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_features_title" value="<?= htmlspecialchars(setting_raw('home_features_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_features_subtitle" value="<?= htmlspecialchars(setting_raw('home_features_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Feature Cards (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_features_cards_json" rows="12" placeholder='[{"icon":"bi bi-house-door","title":"Property Management","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_features_cards_json', '[]')) ?></textarea>
                        <div class="form-text">Example: [{"icon":"bi bi-cash-coin","title":"Rent Collection","text":"Collect rent via M-PESA..."}]</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - Testimonials</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_testimonials_title" value="<?= htmlspecialchars(setting_raw('home_testimonials_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_testimonials_subtitle" value="<?= htmlspecialchars(setting_raw('home_testimonials_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Testimonials (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_testimonials_json" rows="10" placeholder='[{"name":"...","role":"...","text":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_testimonials_json', '[]')) ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - FAQs</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="home_faq_title" value="<?= htmlspecialchars(setting_raw('home_faq_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" class="form-control" name="home_faq_subtitle" value="<?= htmlspecialchars(setting_raw('home_faq_subtitle', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">FAQ items (JSON array)</label>
                        <textarea class="form-control font-monospace" name="home_faq_items_json" rows="12" placeholder='[{"q":"...","a":"..."}]'><?= htmlspecialchars(setting_json_pretty('home_faq_items_json', '[]')) ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Homepage - CTA</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">CTA Title</label>
                        <input type="text" class="form-control" name="home_cta_title" value="<?= htmlspecialchars(setting_raw('home_cta_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CTA Button Text</label>
                        <input type="text" class="form-control" name="home_cta_button_text" value="<?= htmlspecialchars(setting_raw('home_cta_button_text', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">CTA Description</label>
                        <textarea class="form-control" name="home_cta_description" rows="3"><?= htmlspecialchars(setting_raw('home_cta_description', '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CTA Footnote</label>
                        <input type="text" class="form-control" name="home_cta_footnote" value="<?= htmlspecialchars(setting_raw('home_cta_footnote', '')) ?>">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Contact Page - Hero</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Hero Title</label>
                        <input type="text" class="form-control" name="contact_hero_title" value="<?= htmlspecialchars(setting_raw('contact_hero_title', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hero Subtitle</label>
                        <input type="text" class="form-control" name="contact_hero_subtitle" value="<?= htmlspecialchars(setting_raw('contact_hero_subtitle', '')) ?>">
                    </div>
                </div>

                    </div>

                    <div class="tab-pane fade" id="tab-pages-pane" role="tabpanel" aria-labelledby="tab-pages">

                        <h6 class="fw-bold mb-3">Contact Us</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="contact_phone" value="<?= htmlspecialchars(setting_raw('contact_phone', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" name="contact_email" value="<?= htmlspecialchars(setting_raw('contact_email', '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="contact_address" value="<?= htmlspecialchars(setting_raw('contact_address', '')) ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Terms of Service</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Header</label>
                                <input type="text" class="form-control" name="terms_header" value="<?= htmlspecialchars(setting_raw('terms_header', '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Body (HTML)</label>
                                <textarea class="form-control font-monospace" name="terms_body_html" rows="12"><?= htmlspecialchars(setting_raw('terms_body_html', '')) ?></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Privacy Policy</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Header</label>
                                <input type="text" class="form-control" name="privacy_header" value="<?= htmlspecialchars(setting_raw('privacy_header', '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Body (HTML)</label>
                                <textarea class="form-control font-monospace" name="privacy_body_html" rows="12"><?= htmlspecialchars(setting_raw('privacy_body_html', '')) ?></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Documentation</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Hero Title</label>
                                <input type="text" class="form-control" name="docs_hero_title" value="<?= htmlspecialchars(setting_raw('docs_hero_title', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hero Subtitle</label>
                                <input type="text" class="form-control" name="docs_hero_subtitle" value="<?= htmlspecialchars(setting_raw('docs_hero_subtitle', '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Documentation Body (HTML)</label>
                                <textarea class="form-control font-monospace" name="docs_body_html" rows="16"><?= htmlspecialchars(setting_raw('docs_body_html', '')) ?></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Footer</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Footer Logo</label>
                                <input type="file" class="form-control" name="footer_logo" accept="image/*">
                                <?php if (!empty($settings['footer_logo'])): ?>
                                    <div class="form-text">Current: <?= htmlspecialchars($settings['footer_logo']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer About Text</label>
                                <textarea class="form-control" name="footer_about_text" rows="3"><?= htmlspecialchars(setting_raw('footer_about_text', '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Tagline (right side)</label>
                                <input type="text" class="form-control" name="footer_tagline" value="<?= htmlspecialchars(setting_raw('footer_tagline', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Powered By Text</label>
                                <input type="text" class="form-control" name="footer_powered_by_text" value="<?= htmlspecialchars(setting_raw('footer_powered_by_text', '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Powered By URL</label>
                                <input type="text" class="form-control" name="footer_powered_by_url" value="<?= htmlspecialchars(setting_raw('footer_powered_by_url', '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Facebook URL</label>
                                <input type="text" class="form-control" name="footer_social_facebook" value="<?= htmlspecialchars(setting_raw('footer_social_facebook', '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Twitter URL</label>
                                <input type="text" class="form-control" name="footer_social_twitter" value="<?= htmlspecialchars(setting_raw('footer_social_twitter', '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">LinkedIn URL</label>
                                <input type="text" class="form-control" name="footer_social_linkedin" value="<?= htmlspecialchars(setting_raw('footer_social_linkedin', '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Instagram URL</label>
                                <input type="text" class="form-control" name="footer_social_instagram" value="<?= htmlspecialchars(setting_raw('footer_social_instagram', '')) ?>">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="border-top pt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Public Content
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
