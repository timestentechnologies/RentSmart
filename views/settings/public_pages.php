<?php
ob_start();

$settings = $settings ?? [];

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
];

function setting_raw($key, $default = '') {
    global $settings;
    global $publicDefaults;
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
                        <?php if (!empty($settings['home_split_image'])): ?>
                            <div class="form-text">Current: <?= htmlspecialchars($settings['home_split_image']) ?></div>
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

                <h6 class="fw-bold mb-3">Terms & Privacy Page - Header</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Terms Header</label>
                        <input type="text" class="form-control" name="terms_header" value="<?= htmlspecialchars(setting_raw('terms_header', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Privacy Header</label>
                        <input type="text" class="form-control" name="privacy_header" value="<?= htmlspecialchars(setting_raw('privacy_header', '')) ?>">
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
