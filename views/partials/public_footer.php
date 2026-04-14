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
  .social-links a {
    font-size: 1.5rem;
    opacity: 0.8;
    transition: all 0.3s ease;
  }
  .social-links a:hover {
    opacity: 1;
    transform: translateY(-2px);
    color: #FF8A00;
  }
  .newsletter-signup .form-control-sm {
    border-radius: 0.375rem 0 0 0.375rem;
  }
  .newsletter-signup .btn-sm {
    border-radius: 0 0.375rem 0.375rem 0;
  }
</style>

<footer>
  <div class="container">
    <?php
      $footerLogoFile = site_setting('site_logo', '');
      $appsLogoFile = site_setting('apps_page_logo', '');
      $footerLogoUrl = $appsLogoFile 
          ? (BASE_URL . '/public/assets/images/' . $appsLogoFile) 
          : ($footerLogoFile ? (BASE_URL . '/public/assets/images/' . $footerLogoFile) : (BASE_URL . '/public/assets/images/logo.svg'));
      
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
        <img src="<?= htmlspecialchars((string)$footerLogoUrl) ?>" alt="RentSmart Logo" class="mb-3" style="height: 40px;">
        <p class="mb-3"><?= htmlspecialchars($footerAboutText) ?></p>
        <div class="social-links">
          <a href="<?= htmlspecialchars((string)$footerFb) ?>" target="_blank" class="text-white mx-2" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="<?= htmlspecialchars((string)$footerTw) ?>" target="_blank" class="text-white mx-2" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
          <a href="<?= htmlspecialchars((string)$footerLi) ?>" target="_blank" class="text-white mx-2" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
          <a href="<?= htmlspecialchars((string)$footerIg) ?>" target="_blank" class="text-white mx-2" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        </div>

        <!-- Newsletter Signup Section -->
        <div class="newsletter-signup mt-4">
            <h6 class="text-white mb-3">Stay Updated</h6>
            <p class="text-white-50 small mb-3">Get property management tips and exclusive offers</p>
            <form id="footerNewsletterForm" class="d-flex gap-0">
                <input type="email" name="email" class="form-control form-control-sm" placeholder="Your email" required>
                <button type="submit" class="btn btn-warning text-white btn-sm" style="background-color: #f97316; border-color: #f97316;">
                    <i class="bi bi-send"></i>
                </button>
            </form>
            <div id="footerNewsletterMessage" class="small mt-2"></div>
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
          <li class="mb-2"><a href="<?= BASE_URL ?>/airbnb" class="text-white-50">Airbnb Stays</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5 class="mb-3">Contact Us</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:<?= htmlspecialchars((string)site_setting('contact_email', 'rentsmart@timestentechnologies.co.ke')) ?>" class="text-white-50"><?= htmlspecialchars((string)site_setting('contact_email', 'rentsmart@timestentechnologies.co.ke')) ?></a></li>
          <li class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:<?= preg_replace('/\s+/', '', (string)htmlspecialchars((string)site_setting('contact_phone', '+254 795 155 230'))) ?>" class="text-white-50"><?= htmlspecialchars((string)site_setting('contact_phone', '+254 795 155 230')) ?></a></li>
          <li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars((string)site_setting('contact_address', 'Nairobi, Kenya')) ?></li>
        </ul>
        <div class="mt-3">
          <a href="<?= BASE_URL ?>/docs" class="text-white-50 d-block mb-2">Documentation</a>
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
          Powered by <a href="<?= htmlspecialchars((string)$footerPoweredByUrl) ?>" target="_blank" class="text-white"><?= htmlspecialchars((string)$footerPoweredByText) ?></a>
        </p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const footerNewsletterForm = document.getElementById('footerNewsletterForm');
    if (footerNewsletterForm) {
        footerNewsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const messageDiv = document.getElementById('footerNewsletterMessage');
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            messageDiv.innerHTML = '';
            
            fetch('<?= BASE_URL ?>/newsletter/subscribe', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="text-success small"><i class="bi bi-check-circle me-1"></i>' + data.message + '</div>';
                    this.reset();
                } else {
                    messageDiv.innerHTML = '<div class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerHTML = '<div class="text-danger small">Error subscribing. Try again.</div>';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-send"></i>';
            });
        });
    }
});
</script>
