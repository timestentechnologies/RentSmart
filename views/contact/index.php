<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - RentSmart</title>
  <?php $faviconUrl = site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png'); ?>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($faviconUrl) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .contact-hero { background: linear-gradient(135deg, #6B3E99 0%, #8E5CC4 100%); color: #fff; padding: 60px 0; }
    .contact-card { border: none; border-radius: 1rem; box-shadow: 0 10px 30px rgba(107,62,153,.1); }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand" href="<?= BASE_URL ?>/">
        <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="RentSmart Logo" height="40">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/vacant-units">Vacant Units</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#pricing">Pricing</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#testimonials">Testimonials</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#faq">FAQs</a></li>
          <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>/contact">Contact Us</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/docs">Documentation</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login">Login</a></li>
          <li class="nav-item"><a class="btn btn-gradient ms-2" href="<?= BASE_URL ?>/register">Get Started - 7 Days Free</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="contact-hero">
    <div class="container">
      <h1 class="display-6"><?= htmlspecialchars(site_setting('contact_hero_title', 'Contact Sales & Support')) ?></h1>
      <p class="lead mb-0"><?= htmlspecialchars(site_setting('contact_hero_subtitle', "We'd love to hear from you. Send us a message and we'll respond shortly.")) ?></p>
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
            <p class="mb-2"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars(site_setting('contact_phone', '+254 795 155 230')) ?></p>
            <p class="mb-2"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars(site_setting('contact_email', 'rentsmart@timestentechnologies.co.ke')) ?></p>
            <p class="mb-0"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars(site_setting('contact_address', 'Nairobi, Kenya')) ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
