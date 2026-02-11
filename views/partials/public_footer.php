<?php
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
?>
<style>
  footer {
    background-color: #1f2937;
    color: white;
    padding: 40px 0;
  }
  footer a {
    text-decoration: none;
    transition: color 0.3s ease;
  }
  footer a:hover {
    color: #FF8A00 !important;
  }
</style>

<footer>
  <div class="container">
    <?php
      $footerLogoUrl = site_setting_image_url('footer_logo', BASE_URL . '/public/assets/images/site_logo_1751627446.png');
      $footerAboutText = site_setting('footer_about_text', "Kenya's leading property and rental management software. Trusted by landlords, property managers, and real estate professionals.");
      $footerTagline = site_setting('footer_tagline', 'Property Management System | Rental Management Software | Real Estate Management');
      $footerPoweredByText = site_setting('footer_powered_by_text', 'Timesten Technologies');
      $footerPoweredByUrl = site_setting('footer_powered_by_url', 'https://timestentechnologies.co.ke');
      $footerFb = site_setting('footer_social_facebook', 'https://www.facebook.com/RentSmartKE');
      $footerTw = site_setting('footer_social_twitter', 'https://twitter.com/RentSmartKE');
      $footerLi = site_setting('footer_social_linkedin', 'https://www.linkedin.com/posts/timestentechnologies_proptech-propertymanagement-rentsmart-activity-7413190378020925440-JRdI?utm_source=share&utm_medium=member_desktop&rcm=ACoAADXDMdEBsC18bIJ4cOHS2WbzS9hlKU1YxY4');
      $footerIg = site_setting('footer_social_instagram', 'https://www.instagram.com/rentsmartke');
    ?>
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <img src="<?= htmlspecialchars($footerLogoUrl) ?>" alt="RentSmart Property Management Software Logo" class="mb-3" style="height: 40px;">
        <p class="mb-3"><?= htmlspecialchars($footerAboutText) ?></p>
        <div class="social-links">
          <a href="<?= htmlspecialchars($footerFb) ?>" target="_blank" class="text-white mx-2" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="<?= htmlspecialchars($footerTw) ?>" target="_blank" class="text-white mx-2" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
          <a href="<?= htmlspecialchars($footerLi) ?>" target="_blank" class="text-white mx-2" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
          <a href="<?= htmlspecialchars($footerIg) ?>" target="_blank" class="text-white mx-2" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
      <div class="col-md-3">
        <h5 class="mb-3">Features</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="<?= BASE_URL ?>/#features" class="text-white-50">Property Management</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/#features" class="text-white-50">Tenant Management</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/#features" class="text-white-50">Rent Collection</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/#features" class="text-white-50">Maintenance Tracking</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/#features" class="text-white-50">Utility Management</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/#features" class="text-white-50">Financial Reports</a></li>
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
          <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:<?= htmlspecialchars(site_setting('contact_email', 'rentsmart@timestentechnologies.co.ke')) ?>" class="text-white-50"><?= htmlspecialchars(site_setting('contact_email', 'rentsmart@timestentechnologies.co.ke')) ?></a></li>
          <li class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:<?= preg_replace('/\s+/', '', htmlspecialchars(site_setting('contact_phone', '+254 795 155 230'))) ?>" class="text-white-50"><?= htmlspecialchars(site_setting('contact_phone', '+254 795 155 230')) ?></a></li>
          <li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars(site_setting('contact_address', 'Nairobi, Kenya')) ?></li>
        </ul>
        <div class="mt-3">
          <a href="<?= BASE_URL ?>/privacy-policy" class="text-white-50 d-block mb-2">Privacy Policy</a>
          <a href="<?= BASE_URL ?>/terms" class="text-white-50 d-block mb-2">Terms of Service</a>
          <a href="<?= BASE_URL ?>/contact" class="text-white-50 d-block mb-2">Contact Us</a>
        </div>
      </div>
    </div>

    <div class="row border-top border-secondary pt-3">
      <div class="col-md-6">
        <p class="mb-0">&copy; <?= date('Y') ?> RentSmart. All rights reserved.</p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="mb-0 text-white-50"><?= htmlspecialchars($footerTagline) ?></p>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-12 text-center">
        <p class="mb-0 text-white-50">
          Powered by <a href="<?= htmlspecialchars($footerPoweredByUrl) ?>" target="_blank" class="text-white"><?= htmlspecialchars($footerPoweredByText) ?></a>
        </p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
