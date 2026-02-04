<?php
// Start output buffering
ob_start();

// Helper function for PHP 7.4 compatibility
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Define base URL only if not already defined
if (!defined('BASE_URL')) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Detect if we're on cPanel production (domain-based hosting)
    if (strpos($http_host, 'rentsmart.timestentechnologies.co.ke') !== false) {
        // On cPanel addon domain, the domain points directly to this directory
        // So the base URL should be empty (root) for production
        $base_url = '';
    } else {
        // Localhost or other environments - app is in a subdirectory
        $base_dir = dirname($script_name);
        $base_url = $base_dir !== '/' ? $base_dir : '';
    }
    
    define('BASE_URL', $base_url);
    
    // Debug BASE_URL in development
    if (strpos($http_host, 'localhost') !== false || strpos($http_host, '127.0.0.1') !== false) {
        error_log("BASE_URL Debug - Host: {$http_host}, Script: {$script_name}, URI: {$request_uri}, Base URL: {$base_url}");
    }
}

// Normalize current_uri for sidebar highlighting and routing (strip base path)
if (function_exists('current_uri')) {
    $current_uri = trim(current_uri(), '/');
} else {
    $current_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $base_path = parse_url(defined('BASE_URL') ? BASE_URL : '', PHP_URL_PATH);
    if ($base_path && strpos('/' . $current_uri, $base_path) === 0) {
        $current_uri = trim(substr('/' . $current_uri, strlen($base_path)), '/');
    }
}
// Remove front controller prefix if present (e.g., index.php/route)
if (strpos($current_uri, 'index.php/') === 0) {
    $current_uri = substr($current_uri, strlen('index.php/'));
}

try {
    // Get site settings
    $settingsModel = new \App\Models\Setting();
    $settings = $settingsModel->getAllAsAssoc();

    // Get site name and description, with fallbacks
    $siteName = $settings['site_name'] ?? 'RentSmart';
    $siteDescription = $settings['site_description'] ?? 'Property Management System';
    $siteLogo = $settings['site_logo'] ? BASE_URL . '/public/assets/images/' . $settings['site_logo'] : BASE_URL . '/public/assets/images/logo.svg';
} catch (Exception $e) {
    error_log("Error loading settings: " . $e->getMessage());
    $siteName = 'RentSmart';
    $siteDescription = 'Property Management System';
    $siteLogo = BASE_URL . '/public/assets/images/logo.svg';
}

// Clean the output buffer before starting the HTML
ob_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((isset($title) && $title) ? $title . ' - ' . $siteName : $siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($siteDescription) ?>">
    
    <!-- Add BASE_URL to JavaScript -->
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script>
    // Persist and focus active sidebar item across refresh
    document.addEventListener('DOMContentLoaded', function(){
      try {
        const currentPath = window.location.pathname.replace(/^\/+/, '');
        // Save last visited path for sidebar persistence
        localStorage.setItem('rentsmart:lastPath', currentPath);
        // Auto-expand any collapsed submenu that contains the active link
        const activeLink = document.querySelector('.sidebar .nav-link.active');
        if (activeLink) {
          const collapse = activeLink.closest('.collapse');
          if (collapse && !collapse.classList.contains('show')) {
            collapse.classList.add('show');
            const toggle = document.querySelector('.sidebar .nav-link.dropdown-toggle[href="#' + collapse.id + '"]');
            if (toggle) {
              toggle.setAttribute('aria-expanded', 'true');
            }
          }
        }
        // Ensure active nav-link is scrolled into view
        if (activeLink && typeof activeLink.scrollIntoView === 'function') {
          activeLink.scrollIntoView({ block: 'center' });
        }
      } catch (e) {}
    });
    </script>

    <script>
    // Client-side safety net: highlight active sidebar link by URL matching
    document.addEventListener('DOMContentLoaded', function(){
      try {
        const base = '<?= BASE_URL ?>' || '';
        function normalizePath(p){
          let out = p || '';
          // Remove origin if present
          try { out = new URL(out, window.location.origin).pathname; } catch(e) {}
          // Strip BASE_URL prefix
          if (base && out.indexOf(base) === 0) out = out.substring(base.length);
          // Strip front controller
          out = out.replace(/^\/index\.php/, '');
          // Trim leading slashes
          out = out.replace(/^\/+/, '');
          return out;
        }
        const path = normalizePath(window.location.pathname);
        const links = document.querySelectorAll('.sidebar .nav-link[href]');
        let bestMatch = null;
        let bestLen = -1;
        links.forEach(a => {
          const href = a.getAttribute('href') || '';
          const hp = normalizePath(href);
          if (!hp) return;
          // Prefer the longest prefix match
          if (path === hp || path.indexOf(hp) === 0) {
            if (hp.length > bestLen) { bestLen = hp.length; bestMatch = a; }
          }
        });
        if (bestMatch) {
          document.querySelectorAll('.sidebar .nav-link.active').forEach(el => el.classList.remove('active'));
          bestMatch.classList.add('active');
          const collapse = bestMatch.closest('.collapse');
          if (collapse && !collapse.classList.contains('show')) {
            collapse.classList.add('show');
            const toggle = document.querySelector('.sidebar .nav-link.dropdown-toggle[href="#' + collapse.id + '"]');
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
          }
          if (typeof bestMatch.scrollIntoView === 'function') {
            bestMatch.scrollIntoView({ block: 'center' });
          }
        }
      } catch(e) {}
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
      const fab = document.getElementById('aiChatFab');
      const panel = document.getElementById('aiChatPanel');
      const closeBtn = document.getElementById('aiChatClose');
      const input = document.getElementById('aiChatInput');
      const sendBtn = document.getElementById('aiChatSend');
      const messages = document.getElementById('aiChatMessages');
      const csrf = (document.querySelector('input[name="csrf_token"]')||{}).value || (document.querySelector('meta[name="csrf-token"]')||{}).content || '';

      if (!fab || !panel) return;

      function togglePanel(show){
        panel.style.display = show ? 'flex' : 'none';
        if (show) {
          setTimeout(()=> input && input.focus(), 50);
        }
      }
      function appendMsg(text, who){
        const div = document.createElement('div');
        div.className = 'ai-msg ' + (who || 'bot');
        if (who === 'bot') {
          // Basic markdown: bold, headings, lists
          let html = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/^### (.*$)/gim, '<h6>$1</h6>')
            .replace(/^## (.*$)/gim, '<h5>$1</h5>')
            .replace(/^# (.*$)/gim, '<h4>$1</h4>')
            .replace(/^\* (.+)$/gim, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            .replace(/\n/g, '<br>');
          div.innerHTML = html;
        } else {
          div.textContent = text;
        }
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
      }
      function showThinking(){
        const div = document.createElement('div');
        div.className = 'ai-msg bot thinking';
        div.innerHTML = '<em>Thinking…</em>';
        div.id = 'aiThinking';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
      }
      function hideThinking(){
        const el = document.getElementById('aiThinking');
        if (el) el.remove();
      }
      async function send(){
        const text = (input.value||'').trim();
        if (!text) return;
        input.value = '';
        appendMsg(text, 'user');
        sendBtn.disabled = true;
        showThinking();
        try {
          const res = await fetch('<?= BASE_URL ?>/ai/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ message: text })
          });
          let data;
          const ct = (res.headers.get('content-type')||'').toLowerCase();
          if (ct.includes('application/json')) {
            data = await res.json().catch(()=>({success:false,message:'Invalid response'}));
          } else {
            const txt = await res.text().catch(()=> '');
            data = { success: false, message: txt || 'Invalid response' };
          }
          hideThinking();
          if (data && data.success && data.reply){
            appendMsg(data.reply, 'bot');
          } else {
            appendMsg(data.message || 'Sorry, I could not process that right now.', 'bot');
          }
        } catch (e) {
          hideThinking();
          appendMsg('Network error. Please try again.', 'bot');
        } finally {
          sendBtn.disabled = false;
        }
      }

      fab.addEventListener('click', ()=> togglePanel(panel.style.display !== 'flex'));
      closeBtn && closeBtn.addEventListener('click', ()=> togglePanel(false));
      sendBtn && sendBtn.addEventListener('click', send);
      input && input.addEventListener('keydown', (e)=>{ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); send(); }});
    });
    </script>
    
    <!-- Favicon -->
    <?php
    $favicon = $settings['site_favicon'] ? BASE_URL . '/public/assets/images/' . $settings['site_favicon'] : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';
    ?>
    <!-- Standard Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($favicon) ?>">
    
    <!-- For iOS devices -->
    <link rel="apple-touch-icon" sizes="57x57" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="60x60" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="76x76" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="114x114" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="120x120" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="144x144" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= htmlspecialchars($favicon) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($favicon) ?>">
    
    <!-- For Android devices -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.php">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?= htmlspecialchars($favicon) ?>">
    <meta name="theme-color" content="#ffffff">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/public/assets/css/style.css" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color:rgb(82, 16, 98);
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            
            /* Light mode colors */
            --bg-primary: #f8f9fa;
            --bg-secondary: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --card-hover-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] {
            --bg-primary: #1a1d20;
            --bg-secondary: #2b2f33;
            --text-primary: #f8f9fa;
            --text-secondary: #cbd5e0;
            --border-color: #3a3f47;
            --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
            --card-hover-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.5);
        }
        
        body {
            min-height: 100vh;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            z-index: 1040;
            transition: transform 0.3s ease, background-color 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-logo a {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .sidebar-logo img {
            height: 70px;
            width: auto;
            object-fit: contain;
            max-width: auto;
        }

        .sidebar-logo span {
            font-weight: 600;
            color: var(--primary-color);
            margin-left: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-close {
            display: none;
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.5rem;
            padding: 0.25rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .sidebar-close:hover {
            color: var(--danger-color);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1030;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            padding: 0.5rem 1.5rem;
        }

        .nav-link {
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .nav-link:hover {
            color: rgb(60, 4, 68);
            background: white;
        }

        /* Active link styled like a button */
        .nav-link.active {
            background: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .nav-link.active i { color: #ffffff; }
        .nav-link.active:hover { color: #ffffff; background: var(--primary-color); }

        .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            background: var(--bg-primary);
            transition: background-color 0.3s ease;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }

        .card-header {
            background: none;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem;
            color: var(--text-primary);
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Stats Cards */
        .stat-card {
            border-radius: 1rem;
            padding: 1.5rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            position: relative;
            overflow: hidden;
            height: 100%;
            margin-bottom: 1rem;
            transition: background-color 0.3s ease;
        }
        
        /* Dark mode stat card text visibility */
        [data-theme="dark"] .stat-card h1,
        [data-theme="dark"] .stat-card h2,
        [data-theme="dark"] .stat-card h3,
        [data-theme="dark"] .stat-card h4,
        [data-theme="dark"] .stat-card h5,
        [data-theme="dark"] .stat-card h6 {
            color: #f8f9fa !important;
        }
        
        [data-theme="dark"] .stat-card p,
        [data-theme="dark"] .stat-card small,
        [data-theme="dark"] .stat-card span {
            color: #cbd5e0 !important;
        }
        
        [data-theme="dark"] .stat-card .text-muted {
            color: #9ca3af !important;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 4px 0 0 4px;
        }

        .stat-card.revenue::before {
            background: linear-gradient(45deg, var(--success-color), #28a745);
        }

        .stat-card.occupancy::before {
            background: linear-gradient(45deg, var(--primary-color), #0a58ca);
        }

        .stat-card.outstanding::before {
            background: linear-gradient(45deg, var(--warning-color), #e6a800);
        }

        .stat-card h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .stat-card .card-title {
            color: #6c757d;
            font-weight: 500;
        }

        .stat-card p {
            margin-bottom: 0;
            color: #6c757d;
        }

        /* Recent Payment Cards */
        .recent-payment {
            position: relative;
            padding: 1.25rem;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #dee2e6;
        }

        .recent-payment::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: transparent;
            transition: background-color 0.2s ease;
        }

        .recent-payment:hover {
            background-color: #f8f9fa;
        }

        .recent-payment:hover::before {
            background: linear-gradient(45deg, var(--primary-color), #0a58ca);
        }

        /* Property Cards */
        .property-card {
            position: relative;
            overflow: hidden;
        }

        .property-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(45deg, var(--success-color), #28a745);
            border-radius: 4px 0 0 4px;
        }

        .property-card .progress {
            height: 8px;
            margin-top: 1rem;
            background-color: #e9ecef;
        }

        .property-card .progress-bar {
            background: linear-gradient(45deg, var(--success-color), #28a745);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-close {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .row {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }

            .col-12, .col-md-4 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .card {
                margin-bottom: 1rem;
            }

            .table-responsive {
                margin: 0 -1rem;
                padding: 0 1rem;
                width: calc(100% + 2rem);
            }

            .dataTables_wrapper .row {
                margin: 0 -1rem;
                padding: 1rem;
            }

            .btn-group {
                display: flex;
                width: 100%;
            }

            .btn-group .btn {
                flex: 1;
                padding: 0.75rem;
            }

            .toggle-sidebar {
                display: block !important;
            }

            .stat-card h2 {
                font-size: 1.5rem;
            }

            .sidebar-logo span {
                display: none;
            }

            .sidebar-logo img {
                height: 40px;
            }
        }

        /* Custom Styles */
        .progress {
            height: 8px;
            border-radius: 4px;
        }

        .recent-payment {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .recent-payment:last-child {
            border-bottom: none;
        }

        .payment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .payment-details {
            flex-grow: 1;
        }

        /* Property Page Specific Styles */
        .property-count::before {
            background: linear-gradient(45deg, var(--primary-color), #0a58ca);
        }

        .occupancy-rate::before {
            background: linear-gradient(45deg, var(--success-color), #28a745);
        }

        .monthly-income::before {
            background: linear-gradient(45deg, var(--info-color), #0dcaf0);
        }

        /* Enhanced Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table > :not(caption) > * > * {
            padding: 1rem;
        }

        .table > thead > tr > th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: none;
        }

        .table > tbody > tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Progress Bar Enhancements */
        .progress {
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        /* Badge Styles */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
        }

        .badge.bg-primary {
            background-color: rgba(13, 110, 253, 0.1) !important;
            color: var(--primary-color);
        }

        /* Button Group Styles */
        .btn-group {
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .btn-group .btn {
            border: none;
            padding: 0.5rem;
            line-height: 1;
        }

        .btn-group .btn:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .btn-group .btn-outline-primary {
            color: var(--primary-color);
        }

        .btn-group .btn-outline-warning {
            color: var(--warning-color);
        }

        .btn-group .btn-outline-danger {
            color: var(--danger-color);
        }

        /* Card Header Enhancements */
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
        }

        /* DataTable Customization */
        .dataTables_wrapper .row {
            margin: 0;
            padding: 1rem;
        }

        .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        .page-link {
            border: none;
            padding: 0.5rem 1rem;
            color: var(--primary-color);
            border-radius: 0.5rem;
            margin: 0 0.25rem;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            color: white;
        }

        /* Stats Icon Styles */
        .stats-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
        }

        /* Responsive Table Adjustments */
        .table-responsive {
            border-radius: 0.5rem;
            background: var(--bg-secondary);
        }
        
        /* Dark mode table styles */
        [data-theme="dark"] .table {
            color: var(--text-primary);
            --bs-table-bg: var(--bg-secondary);
            --bs-table-border-color: var(--border-color);
        }
        
        [data-theme="dark"] .table > :not(caption) > * > * {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-bottom-color: var(--border-color);
        }
        
        [data-theme="dark"] .table thead {
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.03);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .table-hover > tbody > tr:hover > * {
            --bs-table-hover-bg: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .table tbody {
            border-top-color: var(--border-color);
        }
        
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--bg-secondary);
            border-color: var(--primary-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .modal-content {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .modal-header {
            border-bottom-color: var(--border-color);
        }
        
        [data-theme="dark"] .modal-footer {
            border-top-color: var(--border-color);
        }
        
        [data-theme="dark"] .btn-close {
            filter: invert(1);
        }
        
        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1050;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #212529;
            border: none;
            color: white;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        [data-theme="dark"] .theme-toggle {
            background: #ff8c00;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        }
        
        [data-theme="dark"] .theme-toggle:hover {
            box-shadow: 0 6px 16px rgba(255, 140, 0, 0.5);
        }
        
        .theme-toggle i {
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover i {
            transform: rotate(20deg);
        }
        
        /* WhatsApp Floating Button */
        .whatsapp-fab {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1051;
            background: #25D366;
            color: #fff !important;
            border-radius: 999px;
            padding: 10px 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            text-decoration: none;
        }
        .whatsapp-fab:hover { background: #1ebe57; color: #fff !important; transform: translateY(-2px); }
        .whatsapp-fab i { font-size: 1.25rem; }

        .ai-chat-fab {
            position: fixed;
            right: 20px;
            bottom: 76px;
            z-index: 1052;
            background: #6B3E99;
            color: #fff;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            cursor: pointer;
            pointer-events: auto;
        }
        .ai-chat-fab:hover { transform: translateY(-2px); }
        .ai-chat-panel {
            position: fixed;
            right: 20px;
            bottom: 90px;
            width: 320px;
            max-width: calc(100vw - 40px);
            height: 420px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1053;
            pointer-events: auto;
        }
        [data-theme="dark"] .ai-chat-panel { background: #1f2937; color: #e5e7eb; }
        .ai-chat-header { padding: 10px 12px; background: #6B3E99; color: #fff; display: flex; align-items: center; justify-content: space-between; }
        .ai-chat-messages { padding: 10px; gap: 8px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; }
        .ai-chat-input { padding: 8px; border-top: 1px solid rgba(0,0,0,0.08); display: flex; gap: 8px; }
        [data-theme="dark"] .ai-chat-input { border-top-color: rgba(255,255,255,0.12); }
        .ai-msg { padding: 8px 10px; border-radius: 10px; max-width: 85%; font-size: 0.9rem; }
        .ai-msg.user { align-self: flex-end; background: #e9ecef; }
        .ai-msg.bot { align-self: flex-start; background: #f8f9fa; }
        .ai-msg.thinking { opacity: 0.6; font-style: italic; }
        [data-theme="dark"] .ai-msg.user { background: #374151; }
        [data-theme="dark"] .ai-msg.bot { background: #2d3748; }

        /* Additional dark mode styles */
        /* Navigation section headers */
        .nav-header {
            color:rgb(51, 3, 59) !important;
            font-weight: bold !important;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        [data-theme="dark"] .nav-header {
            color: #ff8c00 !important;
        }
        
        [data-theme="dark"] .badge {
            filter: brightness(0.9);
        }
        
        [data-theme="dark"] .alert {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .dropdown-menu {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .dropdown-item {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="dark"] .text-muted {
            color: #9ca3af !important;
        }
        
        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6 {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] p {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .border {
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_filter input,
        [data-theme="dark"] .dataTables_wrapper .dataTables_length select {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_info,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .page-link {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Dark mode list group styles */
        [data-theme="dark"] .list-group {
            --bs-list-group-bg: var(--bg-secondary);
            --bs-list-group-border-color: var(--border-color);
            --bs-list-group-color: var(--text-primary);
        }
        
        [data-theme="dark"] .list-group-item {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        [data-theme="dark"] .list-group-flush .list-group-item {
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .bg-light {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        [data-theme="dark"] .avatar-circle {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: var(--text-secondary) !important;
        }

        @media (max-width: 576px) {
            .table > :not(caption) > * > * {
                padding: 0.75rem 0.5rem;
            }

            .table > thead > tr > th {
                white-space: nowrap;
            }

            .badge {
                white-space: nowrap;
            }

            .progress {
                min-width: 100px;
            }

            .dataTables_filter input,
            .dataTables_length select {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .dataTables_wrapper .row > div {
                width: 100%;
                margin-bottom: 1rem;
            }

            .dataTables_wrapper .row > div:last-child {
                margin-bottom: 0;
            }
        }

        /* Page Header Adjustments */
        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header .card-body {
            padding: 1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.5rem;
            line-height: 1.2;
        }

        @media (max-width: 576px) {
            .page-header .card-body {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch !important;
            }

            /* Keep export buttons inline on mobile */
            .page-header .card-body > .d-flex.flex-wrap {
                flex-direction: row !important;
                justify-content: flex-start;
                width: 100%;
            }

            /* Only make these specific elements full width */
            .page-header .card-body form,
            .page-header .card-body .input-group,
            .page-header .card-body input[type="file"] {
                width: 100%;
            }

            /* Export buttons stay inline but are smaller */
            .page-header .card-body .btn-sm {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
                white-space: nowrap;
            }

            /* Add Property button full width */
            .page-header .card-body .btn-primary:not(.btn-sm) {
                width: 100%;
            }

            /* Import button full width */
            .page-header form .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <a href="<?= BASE_URL ?>/" class="d-flex align-items-center text-decoration-none">
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?> Logo" class="img-fluid">
            </a>
            <button class="sidebar-close" id="sidebarClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= ($current_uri === 'dashboard') ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                <!-- PROPERTY MANAGEMENT Section -->
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">PROPERTY MANAGEMENT</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($current_uri, 'properties') === 0) ? 'active' : '' ?>" href="<?= BASE_URL ?>/properties">
                        <i class="bi bi-building me-2"></i> Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/units" class="nav-link <?= strpos($current_uri, 'units') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-door-open me-2"></i> Units
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/tenants" class="nav-link <?= strpos($current_uri, 'tenants') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-people me-2"></i> Tenants
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/leases" class="nav-link <?= strpos($current_uri, 'leases') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-file-text me-2"></i> Leases
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/utilities" class="nav-link <?= strpos($current_uri, 'utilities') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-lightning-charge me-2"></i> Utilities
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/maintenance" class="nav-link <?= strpos($current_uri, 'maintenance') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-tools me-2"></i> Maintenance
                    </a>
                </li>
                
                
                <!-- FINANCIAL Section -->
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">FINANCIAL</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/payment-methods" class="nav-link <?= strpos($current_uri, 'payment-methods') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-credit-card me-2"></i> Payment Methods
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/payments" class="nav-link <?= strpos($current_uri, 'payments') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-cash-stack me-2"></i> Payments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/mpesa-verification" class="nav-link <?= strpos($current_uri, 'mpesa-verification') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-shield-check me-2"></i> M-Pesa Verification
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/expenses" class="nav-link <?= strpos($current_uri, 'expenses') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-receipt me-2"></i> Expenses
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/invoices" class="nav-link <?= strpos($current_uri, 'invoices') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-receipt-cutoff me-2"></i> Invoices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle <?= (strpos($current_uri, 'accounting') === 0) ? 'active' : '' ?>" 
                       href="#accountingSubmenu" 
                       data-bs-toggle="collapse" 
                       role="button" 
                       aria-expanded="false">
                        <i class="bi bi-calculator me-2"></i> Accounting
                    </a>
                    <div class="collapse" id="accountingSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/accounting/accounts" class="nav-link">
                                    <i class="bi bi-journal-text me-2"></i> Chart of Accounts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/accounting/ledger" class="nav-link">
                                    <i class="bi bi-journal-check me-2"></i> General Ledger
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/accounting/trial-balance" class="nav-link">
                                    <i class="bi bi-columns-gap me-2"></i> Trial Balance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/accounting/balance-sheet" class="nav-link">
                                    <i class="bi bi-diagram-3 me-2"></i> Balance Sheet
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/accounting/profit-loss" class="nav-link">
                                    <i class="bi bi-graph-up me-2"></i> Profit & Loss
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/accounting/statements" class="nav-link">
                                    <i class="bi bi-file-earmark-text me-2"></i> Statements
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">Reports</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/reports" class="nav-link <?= strpos($current_uri, 'reports') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-graph-up me-2"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/reports/tenant-balances" class="nav-link <?= (strpos($current_uri, 'reports/tenant-balances') === 0) ? 'active' : '' ?>">
                        <i class="bi bi-calendar-check me-2"></i> Monthly Tenant Balances
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">HR & PAYROLL</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/employees" class="nav-link <?= strpos($current_uri, 'employees') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-person-badge me-2"></i> Employees
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/files" class="nav-link <?= strpos($current_uri, 'files') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-folder2-open me-2"></i> Files
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/esign" class="nav-link <?= strpos($current_uri, 'esign') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-pen me-2"></i> E‑Signatures
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">Communication</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/admin/inquiries" class="nav-link <?= strpos($current_uri, 'admin/inquiries') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-envelope-open me-2"></i> Inquiries
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/messaging" class="nav-link <?= strpos($current_uri, 'messaging') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-chat-dots me-2"></i> Messaging
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/notices" class="nav-link <?= strpos($current_uri, 'notices') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-megaphone me-2"></i> Notices
                    </a>
                </li>
                
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">BILLING & SUBSCRIPTION</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/subscription/renew" class="nav-link <?= strpos($current_uri, 'subscription') !== false ? 'active' : '' ?>">
                        <i class="bi bi-credit-card-2-back me-2"></i> Billing
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">OTHERS</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/vacant-units" class="nav-link" target="_blank">
                        <i class="bi bi-house-door me-2"></i> Vacant Units (Public)
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/contact" class="nav-link <?= strpos($current_uri, 'contact') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-envelope me-2"></i> Contact Us
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/jiji" class="nav-link <?= (strpos($current_uri, 'jiji') === 0) ? 'active' : '' ?>">
                        <i class="bi bi-megaphone-fill me-2"></i> Post to Jiji
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle <?= (strpos($current_uri, 'integrations') === 0) ? 'active' : '' ?>" 
                       href="#integrationsSubmenu" 
                       data-bs-toggle="collapse" 
                       role="button" 
                       aria-expanded="false">
                        <i class="bi bi-plugin me-2"></i> Integrations
                    </a>
                    <div class="collapse" id="integrationsSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/integrations/facebook" class="nav-link">
                                    <i class="bi bi-facebook me-2"></i> Facebook
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/integrations/marketplaces" class="nav-link">
                                    <i class="bi bi-shop me-2"></i> Marketplaces
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/integrations/zoho" class="nav-link">
                                    <i class="bi bi-journal-text me-2"></i> Zoho Books
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/integrations/quickbooks" class="nav-link">
                                    <i class="bi bi-calculator me-2"></i> QuickBooks
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/integrations/odoo" class="nav-link">
                                    <i class="bi bi-gear-wide-connected me-2"></i> Odoo ERP
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/activity-logs" class="nav-link <?= strpos($current_uri, 'activity-logs') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-activity me-2"></i> Activity Logs
                    </a>
                </li>

                

                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <!-- ADMINISTRATION Section -->
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">ADMINISTRATION</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($current_uri, 'admin/users') === 0) ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/admin/users">
                        <i class="bi bi-people-fill me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($current_uri, 'admin/subscriptions') === 0) ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/admin/subscriptions">
                        <i class="bi bi-credit-card-2-front me-2"></i> Subscriptions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($current_uri, 'admin/payments') === 0) ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/admin/payments">
                        <i class="bi bi-cash-coin me-2"></i> Payment History
                    </a>
                </li>

                <!-- SYSTEM Section -->
                <li class="nav-item mt-3">
                    <small class="nav-header text-uppercase px-3">SYSTEM</small>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/settings" class="nav-link <?= strpos($current_uri, 'settings') === 0 ? 'active' : '' ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <?php endif; ?>

            

                <!-- Logout -->
                <li class="nav-item mt-4">
                    <a href="<?= BASE_URL ?>/logout" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
            <!-- Profile Button at Bottom -->
            <div class="sidebar-profile mt-auto px-3 pb-3">
                <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-start" id="openProfileModalBtn" type="button">
                    <i class="bi bi-person-circle me-2 fs-4"></i>
                    <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Profile') ?></span>
                </button>
            </div>
        </nav>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
        <i class="bi bi-moon-fill" id="themeIcon"></i>
    </button>

    <?php if (isset($_SESSION['user_role']) && in_array(strtolower($_SESSION['user_role']), ['manager','agent','landlord'])): ?>
        <?php 
            $roleTxt = ucfirst($_SESSION['user_role'] ?? 'User');
            $nameTxt = $_SESSION['user_name'] ?? '';
            $startMsg = "Hi RentSmart Support, I'm {$nameTxt} ({$roleTxt}). I need help getting started.";
        ?>
        <a href="https://wa.me/254718883983?text=<?= rawurlencode($startMsg) ?>" 
           class="whatsapp-fab d-print-none" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i>
        </a>
        <button type="button" class="ai-chat-fab d-print-none" id="aiChatFab" title="AI Assistant">
            <i class="bi bi-robot"></i>
        </button>
        <div class="ai-chat-panel d-print-none" id="aiChatPanel" aria-hidden="true">
            <div class="ai-chat-header">
                <span>AI Assistant</span>
                <button type="button" class="btn btn-sm btn-light" id="aiChatClose"><i class="bi bi-x"></i></button>
            </div>
            <div class="ai-chat-messages" id="aiChatMessages"></div>
            <div class="ai-chat-input">
                <input type="text" class="form-control" id="aiChatInput" placeholder="Type your message...">
                <button class="btn btn-primary" id="aiChatSend"><i class="bi bi-send"></i></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- PWA Install Banner -->
        <div id="pwaInstallBanner" class="alert alert-info d-none d-print-none" role="alert" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-download"></i>
                <span>Add RentSmart to your device for a faster, app-like experience.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-primary" onclick="handlePwaInstallClick()">Install App</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="dismissPwaBanner()">Not now</button>
            </div>
        </div>
        <!-- Mobile Toggle Button -->
        <button class="btn btn-primary d-md-none mb-3 toggle-sidebar" type="button">
            <i class="bi bi-list"></i>
        </button>

        <!-- CSRF Token -->
        <?= csrf_field() ?>

        <?= $content ?? '' ?>
    </div>

    <!-- Upgrade Limit Modal (reusable across pages) -->
    <div class="modal fade" id="upgradeLimitModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-upgrade-title>Upgrade Required</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="mb-2" data-upgrade-message>You have reached your plan limit. Please upgrade to continue.</p>
            <p class="text-muted small mb-2" data-upgrade-counts></p>
            <p class="text-muted small mb-0">Current plan: <strong data-upgrade-plan></strong></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <a href="<?= BASE_URL ?>/subscription/renew" class="btn btn-primary" data-upgrade-cta>Upgrade Plan</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <?php $v = urlencode(date('YmdHis')); ?>
    <script src="<?= BASE_URL ?>/public/assets/js/app.js?v=<?= $v ?>"></script>
    <script>
      // Ensure manifest link has correct BASE_URL at runtime for some hosting setups
      (function ensureManifestScope(){
        const link = document.querySelector('link[rel="manifest"]');
        if (link && !link.href.includes(window.location.origin)) {
          link.href = window.location.origin + '<?= BASE_URL ?>' + '/manifest.php';
        }
      })();
    </script>

    <!-- Manual Install Help Modal -->
    <div class="modal fade" id="pwaInstallHelpModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Install RentSmart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Your browser may not show the native install prompt on HTTP. You can still add RentSmart to your home screen:</n></p>
            <ul>
              <li><strong>Chrome/Edge (Desktop)</strong>: Menu (⋮) → Install RentSmart.</li>
              <li><strong>Android Chrome</strong>: Menu (⋮) → Add to Home screen.</li>
              <li><strong>iOS Safari</strong>: Share → Add to Home Screen.</li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarClose = document.getElementById('sidebarClose');
            const menuToggle = document.querySelector('.toggle-sidebar');

            // Function to close sidebar
            function closeSidebar() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }

            // Function to open sidebar
            function openSidebar() {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            // Toggle sidebar on menu button click
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (sidebar.classList.contains('show')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
            }

            // Close sidebar on close button click
            if (sidebarClose) {
                sidebarClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                });
            }

            // Close sidebar on overlay click
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebar();
                });
            }

            // Close sidebar when clicking outside
            document.addEventListener('click', function(e) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggle = menuToggle && menuToggle.contains(e.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            });

            // Prevent sidebar close when clicking inside sidebar
            if (sidebar) {
                sidebar.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>

    <!-- Subscription Warning Modal -->
    <?php 
    // Only show modal if:
    // 1. User has a subscription end date
    // 2. User is not an administrator
    // 3. Current page is not the renewal page
    $current_page = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $is_renewal_page = strpos($current_page, 'subscription/renew') !== false;
    
    if (isset($_SESSION['subscription_ends_at']) && $_SESSION['user_role'] !== 'administrator' && !$is_renewal_page): 
    ?>
    <div class="modal fade" id="subscriptionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header <?= isset($_SESSION['subscription_expired']) ? 'bg-danger text-white' : 'bg-warning' ?>">
                    <h5 class="modal-title" id="subscriptionModalLabel">
                        <?= isset($_SESSION['subscription_expired']) ? 'Subscription Expired' : 'Subscription Expiring' ?>
                    </h5>
                </div>
                <div class="modal-body">
                    <?php if (isset($_SESSION['subscription_expired'])): ?>
                        <p>Your subscription has expired. Please renew your subscription to continue using RentSmart.</p>
                        <p>You will be redirected to the renewal page in <span id="logoutTimer">60</span> seconds.</p>
                    <?php else: ?>
                        <p>Your subscription is about to expire. Please renew your subscription to continue using RentSmart.</p>
                        <p>You will be logged out in <span id="logoutTimer">60</span> seconds.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <a href="<?= BASE_URL ?>/subscription/renew" class="btn btn-warning" style="background-color: #ff6b00; border-color: #ff6b00; color: white;">Renew Now</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Check subscription status every minute
    function checkSubscription() {
        <?php if (isset($_SESSION['subscription_ends_at']) && $_SESSION['user_role'] !== 'administrator' && !$is_renewal_page): ?>
        const expiryDate = new Date('<?= $_SESSION['subscription_ends_at'] ?>');
        const now = new Date();
        const timeLeft = expiryDate - now;

        // If subscription has expired or is about to expire in the next hour
        if (timeLeft <= 3600000 || <?= isset($_SESSION['subscription_expired']) ? 'true' : 'false' ?>) { // 1 hour in milliseconds
            const modal = new bootstrap.Modal(document.getElementById('subscriptionModal'), {
                backdrop: 'static',
                keyboard: false
            });
            modal.show();

            let secondsLeft = 60;
            const timerElement = document.getElementById('logoutTimer');
            
            const timer = setInterval(() => {
                secondsLeft--;
                timerElement.textContent = secondsLeft;
                
                if (secondsLeft <= 0) {
                    clearInterval(timer);
                    <?php if (isset($_SESSION['subscription_expired'])): ?>
                        window.location.href = '<?= BASE_URL ?>/subscription/renew';
                    <?php else: ?>
                        window.location.href = '<?= BASE_URL ?>/logout';
                    <?php endif; ?>
                }
            }, 1000);
        }
        <?php endif; ?>
    }

    // Check subscription status on page load and every minute
    document.addEventListener('DOMContentLoaded', function() {
        checkSubscription();
        setInterval(checkSubscription, 60000); // Check every minute
    });
    </script>
    <?php endif; ?>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="profileModalLabel">My Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= BASE_URL ?>/settings/updateProfile" autocomplete="off">
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label for="profileName" class="form-label">Name</label>
                <input type="text" class="form-control" id="profileName" name="name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
              </div>
              <div class="mb-3">
                <label for="profileEmail" class="form-label">Email</label>
                <input type="email" class="form-control" id="profileEmail" name="email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
              </div>
              <div class="mb-3">
                <label for="profilePassword" class="form-label">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
                <input type="password" class="form-control" id="profilePassword" name="password" autocomplete="new-password">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    // Theme Toggle Functionality
    (function() {
      const themeToggle = document.getElementById('themeToggle');
      const themeIcon = document.getElementById('themeIcon');
      const htmlElement = document.documentElement;
      
      // Get saved theme from localStorage or default to light
      const savedTheme = localStorage.getItem('theme') || 'light';
      
      // Apply saved theme on page load
      if (savedTheme === 'dark') {
        htmlElement.setAttribute('data-theme', 'dark');
        themeIcon.classList.remove('bi-moon-fill');
        themeIcon.classList.add('bi-sun-fill');
      }
      
      // Toggle theme on button click
      themeToggle.addEventListener('click', function() {
        const currentTheme = htmlElement.getAttribute('data-theme');
        
        if (currentTheme === 'dark') {
          htmlElement.setAttribute('data-theme', 'light');
          localStorage.setItem('theme', 'light');
          themeIcon.classList.remove('bi-sun-fill');
          themeIcon.classList.add('bi-moon-fill');
        } else {
          htmlElement.setAttribute('data-theme', 'dark');
          localStorage.setItem('theme', 'dark');
          themeIcon.classList.remove('bi-moon-fill');
          themeIcon.classList.add('bi-sun-fill');
        }
      });
    })();
    
    // Profile Modal open logic
    document.addEventListener('DOMContentLoaded', function() {
      var profileBtn = document.getElementById('openProfileModalBtn');
      if (profileBtn) {
        profileBtn.addEventListener('click', function() {
          var modal = new bootstrap.Modal(document.getElementById('profileModal'));
          modal.show();
        });
      }
      
      // Flash message handler - Show SweetAlert2 modal for import success/error
      <?php if (isset($_SESSION['flash_message'])): ?>
        Swal.fire({
          icon: '<?= $_SESSION['flash_type'] === 'success' ? 'success' : ($_SESSION['flash_type'] === 'danger' ? 'error' : $_SESSION['flash_type']) ?>',
          title: '<?= $_SESSION['flash_type'] === 'success' ? 'Success!' : 'Notice' ?>',
          text: '<?= addslashes($_SESSION['flash_message']) ?>',
          confirmButtonText: 'OK',
          confirmButtonColor: '<?= $_SESSION['flash_type'] === 'success' ? '#198754' : '#dc3545' ?>'
        });
        <?php 
          unset($_SESSION['flash_message']);
          unset($_SESSION['flash_type']);
        ?>
      <?php endif; ?>
    });
    </script>
</body>
</html> 