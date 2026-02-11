<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - RentSmart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .contact-hero { background: linear-gradient(135deg, #6B3E99 0%, #8E5CC4 100%); color: #fff; padding: 60px 0; }
    .contact-card { border: none; border-radius: 1rem; box-shadow: 0 10px 30px rgba(107,62,153,.1); }
  </style>
</head>
<body>
  <?php
    try {
      require_once __DIR__ . '/../../app/Models/Setting.php';
      $settingsModel = new \App\Models\Setting();
      $pageSettings = $settingsModel->getAllAsAssoc();
    } catch (Throwable $e) {
      $pageSettings = [];
    }
    $contactHeroTitle = isset($pageSettings['contact_hero_title']) && $pageSettings['contact_hero_title'] !== '' ? (string)$pageSettings['contact_hero_title'] : 'Contact Sales & Support';
    $contactHeroSubtitle = isset($pageSettings['contact_hero_subtitle']) && $pageSettings['contact_hero_subtitle'] !== '' ? (string)$pageSettings['contact_hero_subtitle'] : "We'd love to hear from you. Send us a message and we'll respond shortly.";
    $contactPhone = isset($pageSettings['contact_phone']) && $pageSettings['contact_phone'] !== '' ? (string)$pageSettings['contact_phone'] : '+254 795 155 230';
    $contactEmail = isset($pageSettings['contact_email']) && $pageSettings['contact_email'] !== '' ? (string)$pageSettings['contact_email'] : 'rentsmart@timestentechnologies.co.ke';
    $contactLocation = isset($pageSettings['contact_location']) && $pageSettings['contact_location'] !== '' ? (string)$pageSettings['contact_location'] : 'Nairobi, Kenya';
  ?>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="<?= BASE_URL ?>/">
        <img src="<?= BASE_URL ?>/public/assets/images/site_logo_1751627446.png" alt="RentSmart" height="40">
      </a>
      <a href="<?= BASE_URL ?>/" class="btn btn-outline-primary">Home</a>
    </div>
  </nav>

  <section class="contact-hero">
    <div class="container">
      <h1 class="display-6"><?= htmlspecialchars($contactHeroTitle) ?></h1>
      <p class="lead mb-0"><?= htmlspecialchars($contactHeroSubtitle) ?></p>
    </div>
  </section>

  <div class="container my-5">
    <?php if (!empty($_SESSION['flash_message'])): ?>
      <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
      </div>
      <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card contact-card">
          <div class="card-body p-4">
            <h5 class="mb-3">Send us a message</h5>
            <form method="post" action="<?= BASE_URL ?>/contact/submit">
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Name *</label>
                  <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone</label>
                  <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Subject *</label>
                  <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Message *</label>
                  <textarea name="message" rows="5" class="form-control" required></textarea>
                </div>
                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Send Message</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card contact-card">
          <div class="card-body p-4">
            <h5 class="mb-3">Contact Details</h5>
            <p class="mb-2"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($contactPhone) ?></p>
            <p class="mb-2"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($contactEmail) ?></p>
            <p class="mb-0"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($contactLocation) ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
