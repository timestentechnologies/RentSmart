<?php
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
$activePage = $activePage ?? '';
$siteName = $siteName ?? 'RentSmart';
$faviconUrl = $faviconUrl ?? site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png');
?>
<style>
  :root { --primary-color:#6B3E99; --secondary-color:#8E5CC4; }
  .navbar .navbar-nav {
    gap: .35rem;
  }
  .navbar .nav-link {
    color: #111827;
    border-radius: .65rem;
    padding: .5rem .85rem;
    transition: background-color .15s ease, color .15s ease;
  }
  .navbar .nav-link:hover {
    background: rgba(107, 62, 153, 0.10);
    color: var(--primary-color);
  }
  .navbar .nav-link.active {
    background: rgba(107, 62, 153, 0.12);
    color: var(--primary-color);
    font-weight: 600;
  }
  .btn-gradient {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
  }
  .btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(107, 62, 153, 0.2);
    color: white;
  }
  .navbar .btn.btn-gradient { padding: .5rem .9rem; }
</style>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/">
      <img src="<?= asset('images/site_logo_1751627446.png') ?>" alt="<?= htmlspecialchars($siteName) ?> Logo" height="40">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= BASE_URL ?>/">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'vacant_units' ? 'active' : '' ?>" href="<?= BASE_URL ?>/vacant-units">Vacant Units</a></li>
        <li class="nav-item"><a class="nav-link" data-public-section="features" href="<?= BASE_URL ?>/#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" data-public-section="pricing" href="<?= BASE_URL ?>/#pricing">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" data-public-section="testimonials" href="<?= BASE_URL ?>/#testimonials">Testimonials</a></li>
        <li class="nav-item"><a class="nav-link" data-public-section="faq" href="<?= BASE_URL ?>/#faq">FAQs</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'contact' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact">Contact Us</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login">Login</a></li>
        <li class="nav-item ms-lg-2"><a class="btn btn-gradient" href="<?= BASE_URL ?>/register">Get Started - 7 Days Free</a></li>
      </ul>
    </div>
  </div>
</nav>

<script>
  (function () {
    try {
      var links = Array.prototype.slice.call(document.querySelectorAll('a.nav-link[data-public-section]'));
      if (!links.length) return;

      var normalize = function (p) {
        return (p || '').replace(/\/+$/, '') || '/';
      };

      var baseUrl = "<?= addslashes((string)BASE_URL) ?>";
      var path = normalize(window.location.pathname || '/');
      var homePath1 = normalize(baseUrl + '/');
      var homePath2 = normalize(baseUrl);
      var isHome = (path === homePath1 || path === homePath2 || path === '/');
      if (!isHome) return;

      var homeLink = document.querySelector('a.nav-link[href="<?= BASE_URL ?>/"]');

      var setActiveSection = function (section) {
        links.forEach(function (a) {
          if (a.getAttribute('data-public-section') === section) a.classList.add('active');
          else a.classList.remove('active');
        });
        if (homeLink) {
          if (section) homeLink.classList.remove('active');
          else homeLink.classList.add('active');
        }
      };

      links.forEach(function (a) {
        a.addEventListener('click', function () {
          var sec = a.getAttribute('data-public-section');
          setActiveSection(sec);
        });
      });

      var getSectionTop = function (id) {
        var el = document.getElementById(id);
        if (!el) return null;
        var rect = el.getBoundingClientRect();
        return rect.top;
      };

      var updateOnScroll = function () {
        var order = ['features', 'pricing', 'testimonials', 'faq'];
        var current = '';
        for (var i = 0; i < order.length; i++) {
          var t = getSectionTop(order[i]);
          if (t === null) continue;
          if (t <= 120) current = order[i];
        }
        setActiveSection(current || '');
      };

      window.addEventListener('scroll', updateOnScroll, { passive: true });
      window.addEventListener('hashchange', updateOnScroll);
      updateOnScroll();
    } catch (e) {
    }
  })();
</script>
