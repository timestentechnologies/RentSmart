<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RentSmart Documentation & User Guide</title>
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
  <nav class="navbar navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand" href="<?= BASE_URL ?>/">
        <img src="<?= BASE_URL ?>/public/assets/images/site_logo_1751627446.png" alt="RentSmart" height="36">
      </a>
      <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/">Back to Home</a>
    </div>
  </nav>

  <header class="docs-hero">
    <div class="container">
      <h1 class="display-6 fw-bold mb-2">RentSmart Documentation</h1>
      <p class="lead mb-0">Setup, user guide, and best practices for landlords, managers, agents, caretakers, tenants, and admins.</p>
    </div>
  </header>

  <div class="container my-4">
    <div class="row g-4">
      <aside class="col-lg-3">
        <div class="card docs-card">
          <div class="card-body">
            <h5 class="mb-3">Contents</h5>
            <div class="list-group toc" id="toc">
              <a class="list-group-item list-group-item-action" href="#getting-started">Getting Started</a>
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
              <a class="list-group-item list-group-item-action" href="#faq">FAQ</a>
            </div>
          </div>
        </div>
      </aside>
      <main class="col-lg-9">
        <div class="card docs-card mb-4">
          <div class="card-body">
            <section id="getting-started">
              <h2>Getting Started</h2>
              <p>Sign up for a 7‑day free trial, configure your profile and company settings, add your first property and unit, and invite team members.</p>
              <ol>
                <li>Register an account from the homepage.</li>
                <li>Open Settings → update Company name, logo, email, phone.</li>
                <li>Add Properties and Units; set rent amounts.</li>
                <li>Add Tenants and create Leases for units.</li>
              </ol>
            </section>
            <hr>
            <section id="properties">
              <h2>Properties & Units</h2>
              <p>Manage property details, caretaker info, occupancy, images, and unit statuses (vacant/occupied).</p>
              <ul>
                <li>Properties → Create, edit, view files and images.</li>
                <li>Units → Import/export, assign tenants via leases.</li>
              </ul>
            </section>
            <hr>
            <section id="tenants">
              <h2>Tenants & Leases</h2>
              <p>Create tenants, link active leases to units, set rent, and manage lease terms.</p>
              <ul>
                <li>Leases → Create/Import leases; active lease drives tenant balance.</li>
                <li>Reports → Tenant balances, delinquency.</li>
              </ul>
            </section>
            <hr>
            <section id="payments">
              <h2>Payments & Invoices</h2>
              <p>Record rent and utility payments, generate invoices, download/email PDF, and post to the ledger.</p>
              <ul>
                <li>Payments → Add rent and utility payments (supports M‑PESA manual logs).</li>
                <li>Invoices → Create multi‑item invoices, tax, PDF, email, post to ledger (A/R vs Revenue).</li>
              </ul>
            </section>
            <hr>
            <section id="maintenance">
              <h2>Maintenance</h2>
              <p>Track maintenance requests; on actual cost update, an expense posts automatically with optional rent balance deduction.</p>
            </section>
            <hr>
            <section id="utilities">
              <h2>Utilities</h2>
              <p>Manage metered/flat utilities per unit, record readings, and include in payments.</p>
            </section>
            <hr>
            <section id="messaging">
              <h2>Messaging</h2>
              <p>Chat between landlord/manager/agent and tenants/caretaker/admin with real‑time style UI.</p>
            </section>
            <hr>
            <section id="notices">
              <h2>Notices</h2>
              <p>Broadcast notices to all, by property/unit, or to specific tenants. Landlords/managers/agents can post.</p>
            </section>
            <hr>
            <section id="accounting">
              <h2>Accounting</h2>
              <p>Chart of Accounts, General Ledger, Trial Balance, Balance Sheet, Profit & Loss, and posting of invoices.</p>
            </section>
            <hr>
            <section id="esign">
              <h2>E‑Signatures</h2>
              <p>Create signature requests to users or tenants, share public link, capture signature and track status. Ideal for approvals and document sign‑offs.</p>
            </section>
            <hr>
            <section id="tenant-portal">
              <h2>Tenant Portal</h2>
              <p>Tenants log in to view active lease, pay rent (restricted strictly to their active unit), submit maintenance requests, and access notices.</p>
            </section>
            <hr>
            <section id="faq">
              <h2>FAQ & Support</h2>
              <p>Contact: timestentechnologies@gmail.com • +254 718 883 983. For more help, visit Contact page or Messaging within the app.</p>
            </section>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
