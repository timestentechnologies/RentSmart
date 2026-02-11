<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RentSmart Documentation & User Guide</title>
  <?php $faviconUrl = site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png'); ?>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($faviconUrl) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f8f9fb; }
    .docs-hero { background: linear-gradient(135deg, #6B3E99 0%, #8E5CC4 100%); color:#fff; padding:56px 0; }
    .docs-card { border:none; border-radius:1rem; box-shadow: 0 20px 40px rgba(107,62,153,.08); }
    .toc { position: sticky; top: 24px; }
    .toc .list-group-item { border: none; border-left: 3px solid transparent; }
    .toc .list-group-item.active { background: rgba(107,62,153,.1); border-left-color:#6B3E99; color:#6B3E99; font-weight:600; }
    h2, h3 { scroll-margin-top: 90px; }
    pre { background:#171923; color:#e2e8f0; padding:16px; border-radius:8px; overflow:auto }
    code { color:#92400e }
  </style>
</head>
<body>
  <?php $activePage = 'docs'; require __DIR__ . '/../partials/public_header.php'; ?>

  <header class="docs-hero">
    <div class="container">
      <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars(site_setting('docs_hero_title', 'RentSmart Documentation')) ?></h1>
      <p class="lead mb-0"><?= htmlspecialchars(site_setting('docs_hero_subtitle', 'Comprehensive guide for landlords, property managers, agents, caretakers, tenants, and administrators.')) ?></p>
    </div>
  </header>

  <div class="container my-4">
    <div class="row g-4">
      <!-- Sidebar / Table of Contents -->
      <aside class="col-lg-3">
        <div class="card docs-card">
          <div class="card-body">
            <h5 class="mb-3">Contents</h5>
            <div class="list-group toc" id="toc">
              <a class="list-group-item list-group-item-action" href="#getting-started">Getting Started</a>
              <a class="list-group-item list-group-item-action" href="#user-roles">User Roles & Permissions</a>
              <a class="list-group-item list-group-item-action" href="#properties">Properties & Units</a>
              <a class="list-group-item list-group-item-action" href="#tenants">Tenants & Leases</a>
              <a class="list-group-item list-group-item-action" href="#payments">Payments & Invoices</a>
              <a class="list-group-item list-group-item-action" href="#maintenance">Maintenance</a>
              <a class="list-group-item list-group-item-action" href="#utilities">Utilities</a>
              <a class="list-group-item list-group-item-action" href="#messaging">Messaging</a>
              <a class="list-group-item list-group-item-action" href="#notices">Notices</a>
              <a class="list-group-item list-group-item-action" href="#accounting">Accounting</a>
              <a class="list-group-item list-group-item-action" href="#esign">E‑Signatures</a>
              <a class="list-group-item list-group-item-action" href="#tenant-portal">Tenant Portal</a>
              <a class="list-group-item list-group-item-action" href="#reports">Reports & Analytics</a>
              <a class="list-group-item list-group-item-action" href="#notifications">Notifications & Alerts</a>
              <a class="list-group-item list-group-item-action" href="#mobile-access">Mobile & Accessibility</a>
              <a class="list-group-item list-group-item-action" href="#tips-best-practices">Tips & Best Practices</a>
              <a class="list-group-item list-group-item-action" href="#faq">FAQ & Support</a>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="col-lg-9">
        <div class="card docs-card mb-4">
          <div class="card-body">

            <?php
            $docsBodyDefault = '';
            ob_start();
            ?>

            <!-- Getting Started -->
            <section id="getting-started">
              <h2>Getting Started</h2>
              <p>Sign up for a free trial, configure your company profile, add properties and units, and invite team members.</p>
              <ol>
                <li>Register an account from the homepage.</li>
                <li>Go to Settings → update Company name, logo, email, and phone.</li>
                <li>Add Properties and Units; set rent amounts and occupancy details.</li>
                <li>Add Tenants and create Leases for units.</li>
                <li>Invite your team with role-specific permissions.</li>
              </ol>
            </section>

            <hr>

            <!-- User Roles & Permissions -->
            <section id="user-roles">
              <h2>User Roles & Permissions</h2>
              <p>Assign roles to users to control access:</p>
              <ul>
                <li><strong>Admin:</strong> Full access to system features, reporting, user management.</li>
                <li><strong>Landlord:</strong> Monitor properties, tenants, leases, payments, notices.</li>
                <li><strong>Property Manager / Agent:</strong> Manage properties, leases, tenants, maintenance, and notices.</li>
                <li><strong>Caretaker:</strong> Update unit status, submit maintenance updates.</li>
                <li><strong>Tenant:</strong> Access lease details, make payments, submit maintenance requests, and view notices.</li>
              </ul>
              <p><em>Tip:</em> Use role-based permissions to maintain security and ensure accountability.</p>
            </section>

            <hr>

            <!-- Properties & Units -->
            <section id="properties">
              <h2>Properties & Units</h2>
              <p>Manage property and unit details efficiently:</p>
              <ul>
                <li><strong>Properties:</strong> Create, edit, and upload documents or images for each property.</li>
                <li><strong>Units:</strong> Add multiple units, assign rent, occupancy status, and link tenants via leases.</li>
                <li>Track vacant and occupied units for better occupancy management.</li>
              </ul>
              <p><em>Tip:</em> Update unit details whenever tenants move in/out to keep records accurate.</p>
            </section>

            <hr>

            <!-- Tenants & Leases -->
            <section id="tenants">
              <h2>Tenants & Leases</h2>
              <p>Create and manage tenants and their leases:</p>
              <ul>
                <li>Link active leases to units, setting rent, deposit, and lease duration.</li>
                <li>Track tenant payment history, arrears, and lease expiry.</li>
                <li>Generate reports for tenant balances and overdue payments.</li>
              </ul>
            </section>

            <hr>

            <!-- Payments & Invoices -->
            <section id="payments">
              <h2>Payments & Invoices</h2>
              <p>Track all payments and generate invoices:</p>
              <ul>
                <li>Record rent and utility payments (supports M-PESA manual logs).</li>
                <li>Create invoices with multiple line items, apply tax, and post to ledger.</li>
                <li>Download or email invoices in PDF format to tenants.</li>
                <li>Automatic ledger posting ensures accounting consistency.</li>
              </ul>
              <p><em>Tip:</em> Reconcile payments weekly to avoid discrepancies.</p>
            </section>

            <hr>

            <!-- Maintenance -->
            <section id="maintenance">
              <h2>Maintenance</h2>
              <p>Track maintenance requests and costs:</p>
              <ul>
                <li>Tenants or caretakers can submit requests via the system.</li>
                <li>Record actual maintenance cost; optionally deduct from tenant rent balance.</li>
                <li>Track maintenance status and completion dates for reporting.</li>
              </ul>
            </section>

            <hr>

            <!-- Utilities -->
            <section id="utilities">
              <h2>Utilities</h2>
              <p>Manage utilities efficiently:</p>
              <ul>
                <li>Add metered or flat utilities per unit.</li>
                <li>Record readings or monthly charges.</li>
                <li>Include utilities in tenant invoices for accurate billing.</li>
              </ul>
            </section>

            <hr>

            <!-- Messaging -->
            <section id="messaging">
              <h2>Messaging</h2>
              <p>Communicate securely within RentSmart:</p>
              <ul>
                <li>Chat with tenants, managers, caretakers, and admins in real-time.</li>
                <li>Broadcast notices or individual messages.</li>
                <li>Keep all communication centralized for auditing and tracking.</li>
              </ul>
            </section>

            <hr>

            <!-- Notices -->
            <section id="notices">
              <h2>Notices</h2>
              <p>Broadcast or schedule notices to tenants and staff:</p>
              <ul>
                <li>Send notices by property, unit, or individual tenant.</li>
                <li>Schedule future notifications for rent reminders or announcements.</li>
                <li>Track which tenants have read the notices.</li>
              </ul>
            </section>

            <hr>

            <!-- Accounting -->
            <section id="accounting">
              <h2>Accounting</h2>
              <p>Comprehensive accounting module:</p>
              <ul>
                <li>Chart of Accounts, General Ledger, Trial Balance.</li>
                <li>Profit & Loss, Balance Sheet reporting.</li>
                <li>Invoices automatically post to accounts receivable/revenue.</li>
              </ul>
            </section>

            <hr>

            <!-- E-Signatures -->
            <section id="esign">
              <h2>E‑Signatures</h2>
              <p>Digitally sign documents securely:</p>
              <ul>
                <li>Create signature requests for tenants, managers, or staff.</li>
                <li>Share public links for document signing.</li>
                <li>Track signature status and download signed documents.</li>
              </ul>
            </section>

            <hr>

            <!-- Tenant Portal -->
            <section id="tenant-portal">
              <h2>Tenant Portal</h2>
              <p>Tenant self-service portal:</p>
              <ul>
                <li>View active lease, rent due, and payment history.</li>
                <li>Submit maintenance requests and track progress.</li>
                <li>Receive notices and communicate with property managers.</li>
              </ul>
            </section>

            <hr>

            <!-- Reports & Analytics -->
            <section id="reports">
              <h2>Reports & Analytics</h2>
              <p>Generate detailed reports for operational insights:</p>
              <ul>
                <li>Occupancy & vacancy reports per property/unit.</li>
                <li>Tenant payment history & arrears.</li>
                <li>Maintenance cost and completion analytics.</li>
                <li>Revenue, Profit & Loss, Trial Balance, Balance Sheet.</li>
                <li>Export reports in PDF, Excel, or CSV format.</li>
              </ul>
            </section>

            <hr>

            <!-- Notifications & Alerts -->
            <section id="notifications">
              <h2>Notifications & Alerts</h2>
              <p>Automated alerts for timely actions:</p>
              <ul>
                <li>Upcoming rent due notifications for tenants.</li>
                <li>Lease expiry alerts for managers and landlords.</li>
                <li>Maintenance updates and completion notifications.</li>
                <li>System alerts for unpaid balances or failed payments.</li>
              </ul>
            </section>

            <hr>

            <!-- Mobile & Accessibility -->
            <section id="mobile-access">
              <h2>Mobile & Accessibility</h2>
              <p>RentSmart is fully responsive and mobile-friendly:</p>
              <ul>
                <li>Access all core features on tablets and smartphones.</li>
                <li>Tenants can pay rent and submit requests on the go.</li>
                <li>Receive push notifications for alerts and updates.</li>
              </ul>
            </section>

            <hr>

            <!-- Tips & Best Practices -->
            <section id="tips-best-practices">
              <h2>Tips & Best Practices</h2>
              <ul>
                <li>Update property and unit details regularly for accurate records.</li>
                <li>Use scheduled maintenance tracking to prevent overdue repairs.</li>
                <li>Reconcile payments weekly to avoid accounting errors.</li>
                <li>Encourage tenants to use the portal for payments and requests.</li>
                <li>Regularly back up system data and maintain user role security.</li>
              </ul>
            </section>

            <hr>

            <!-- FAQ & Support -->
            <section id="faq">
              <h2>FAQ & Support</h2>
              <p>For questions or help, contact:</p>
              <ul>
                <li>Email: <a href="mailto:rentsmart@timestentechnologies.co.ke">rentsmart@timestentechnologies.co.ke</a></li>
                <li>Phone: +254 795 155 230</li>
                <li>In-app Messaging for real-time support</li>
                <li>Visit the Contact page for additional resources.</li>
              </ul>
            </section>

            <?php
            $docsBodyDefault = ob_get_clean();
            echo site_setting('docs_body_html', $docsBodyDefault);
            ?>

          </div>
        </div>
      </main>
    </div>
  </div>

  <?php require __DIR__ . '/../partials/public_footer.php'; ?>
</body>
</html>
