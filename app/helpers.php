<?php

if (!function_exists('view')) {
    /**
     * Render a view file with the given data
     * 
     * @param string $view The view file path relative to views directory
     * @param array $data Data to be passed to the view
     * @return string The rendered view content
     */
    function view($view, $data = [])
    {
        try {
            // Add current URI to the view data
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
            if ($scriptPath !== '/') {
                $uri = str_replace($scriptPath, '', $uri);
            }
            $uri = '/' . trim($uri, '/');
            
            $data['current_uri'] = $uri;
            
            // Extract data to make variables available in view
            extract($data);

            // Start output buffering
            ob_start();

            // Include the view file
            $view_file = __DIR__ . '/../views/' . $view . '.php';
            if (!file_exists($view_file)) {
                throw new Exception("View file {$view_file} not found");
            }
            
            require $view_file;

            // Get the buffered content and clean the buffer
            $content = ob_get_clean();
            
            if ($content === false) {
                throw new Exception("Error capturing view output");
            }
            
            return $content;
        } catch (Exception $e) {
            error_log("Error in view function: " . $e->getMessage());
            error_log("View file: " . $view);
            error_log("Data: " . print_r($data, true));
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}

if (!function_exists('demo_setting_ids')) {
    function demo_setting_ids(string $key): array
    {
        try {
            $settings = new \App\Models\Setting();
            $raw = (string)($settings->get($key) ?? '[]');
            $ids = json_decode($raw, true);
            if (!is_array($ids)) return [];
            return array_values(array_unique(array_map('intval', $ids)));
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('demo_cleanup_user_data')) {
    function demo_cleanup_user_data(int $userId): void
    {
        if ($userId <= 0) return;
        try {
            $db = \App\Database\Connection::getInstance()->getConnection();
            $protectedUsers = demo_setting_ids('demo_protected_user_ids_json');
            if (!in_array($userId, $protectedUsers, true)) {
                return;
            }

            $protectedPayments = demo_setting_ids('demo_protected_payment_ids_json');
            $protectedLeases = demo_setting_ids('demo_protected_lease_ids_json');
            $protectedTenants = demo_setting_ids('demo_protected_tenant_ids_json');
            $protectedUnits = demo_setting_ids('demo_protected_unit_ids_json');
            $protectedProps = demo_setting_ids('demo_protected_property_ids_json');
            $protectedInvoices = demo_setting_ids('demo_protected_invoice_ids_json');
            $protectedRListings = demo_setting_ids('demo_protected_realtor_listing_ids_json');
            $protectedRClients = demo_setting_ids('demo_protected_realtor_client_ids_json');
            $protectedRContracts = demo_setting_ids('demo_protected_realtor_contract_ids_json');

            $placeholders = function(array $ids): string {
                return implode(',', array_fill(0, count($ids), '?'));
            };

            $deletedPaymentIds = [];
            try {
                $sql = "SELECT id FROM payments WHERE (user_id = ? OR realtor_user_id = ?)";
                $params = [(int)$userId, (int)$userId];
                if (!empty($protectedPayments)) {
                    $sql .= " AND id NOT IN (" . $placeholders($protectedPayments) . ")";
                    $params = array_merge($params, $protectedPayments);
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $pid = (int)($r['id'] ?? 0);
                    if ($pid > 0) $deletedPaymentIds[] = $pid;
                }
                if (!empty($deletedPaymentIds)) {
                    $delSql = "DELETE FROM payments WHERE id IN (" . $placeholders($deletedPaymentIds) . ")";
                    $db->prepare($delSql)->execute($deletedPaymentIds);
                }
            } catch (\Throwable $e) {
            }

            if (!empty($deletedPaymentIds)) {
                try {
                    $delJe = "DELETE FROM journal_entries WHERE reference_type = 'payment' AND reference_id IN (" . $placeholders($deletedPaymentIds) . ")";
                    $db->prepare($delJe)->execute($deletedPaymentIds);
                } catch (\Throwable $e) {
                }
            }

            $deletedInvoiceIds = [];
            try {
                $sql = "SELECT id FROM invoices WHERE user_id = ?";
                $params = [(int)$userId];
                if (!empty($protectedInvoices)) {
                    $sql .= " AND id NOT IN (" . $placeholders($protectedInvoices) . ")";
                    $params = array_merge($params, $protectedInvoices);
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $iid = (int)($r['id'] ?? 0);
                    if ($iid > 0) $deletedInvoiceIds[] = $iid;
                }
                if (!empty($deletedInvoiceIds)) {
                    $db->prepare("DELETE FROM invoice_items WHERE invoice_id IN (" . $placeholders($deletedInvoiceIds) . ")")->execute($deletedInvoiceIds);
                    $db->prepare("DELETE FROM invoices WHERE id IN (" . $placeholders($deletedInvoiceIds) . ")")->execute($deletedInvoiceIds);
                }
            } catch (\Throwable $e) {
            }

            if (!empty($deletedInvoiceIds)) {
                try {
                    $db->prepare("DELETE FROM journal_entries WHERE reference_type = 'invoice' AND reference_id IN (" . $placeholders($deletedInvoiceIds) . ")")->execute($deletedInvoiceIds);
                } catch (\Throwable $e) {
                }
            }

            try {
                $sql = "SELECT id FROM realtor_contracts WHERE user_id = ?";
                $params = [(int)$userId];
                if (!empty($protectedRContracts)) {
                    $sql .= " AND id NOT IN (" . $placeholders($protectedRContracts) . ")";
                    $params = array_merge($params, $protectedRContracts);
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $delIds = [];
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0) $delIds[] = $id;
                }
                if (!empty($delIds)) {
                    $db->prepare("DELETE FROM realtor_contracts WHERE id IN (" . $placeholders($delIds) . ")")->execute($delIds);
                }
            } catch (\Throwable $e) {
            }

            try {
                $sql = "SELECT id FROM realtor_clients WHERE user_id = ?";
                $params = [(int)$userId];
                if (!empty($protectedRClients)) {
                    $sql .= " AND id NOT IN (" . $placeholders($protectedRClients) . ")";
                    $params = array_merge($params, $protectedRClients);
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $delIds = [];
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0) $delIds[] = $id;
                }
                if (!empty($delIds)) {
                    $db->prepare("DELETE FROM realtor_clients WHERE id IN (" . $placeholders($delIds) . ")")->execute($delIds);
                }
            } catch (\Throwable $e) {
            }

            try {
                $sql = "SELECT id FROM realtor_listings WHERE user_id = ?";
                $params = [(int)$userId];
                if (!empty($protectedRListings)) {
                    $sql .= " AND id NOT IN (" . $placeholders($protectedRListings) . ")";
                    $params = array_merge($params, $protectedRListings);
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $delIds = [];
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0) $delIds[] = $id;
                }
                if (!empty($delIds)) {
                    $db->prepare("DELETE FROM realtor_listings WHERE id IN (" . $placeholders($delIds) . ")")->execute($delIds);
                }
            } catch (\Throwable $e) {
            }

            try {
                $sqlProps = "SELECT id FROM properties WHERE (owner_id = ? OR manager_id = ? OR agent_id = ? OR caretaker_user_id = ?)";
                $paramsProps = [(int)$userId, (int)$userId, (int)$userId, (int)$userId];
                if (!empty($protectedProps)) {
                    $sqlProps .= " AND id NOT IN (" . $placeholders($protectedProps) . ")";
                    $paramsProps = array_merge($paramsProps, $protectedProps);
                }
                $stmt = $db->prepare($sqlProps);
                $stmt->execute($paramsProps);
                $propRows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $deletePropIds = [];
                foreach ($propRows as $r) {
                    $pid = (int)($r['id'] ?? 0);
                    if ($pid > 0) $deletePropIds[] = $pid;
                }
                if (!empty($deletePropIds)) {
                    $db->prepare("DELETE FROM properties WHERE id IN (" . $placeholders($deletePropIds) . ")")->execute($deletePropIds);
                }
            } catch (\Throwable $e) {
            }

            try {
                $sqlUnits = "SELECT id FROM units WHERE user_id = ?";
                $paramsUnits = [(int)$userId];
                if (!empty($protectedUnits)) {
                    $sqlUnits .= " AND id NOT IN (" . $placeholders($protectedUnits) . ")";
                    $paramsUnits = array_merge($paramsUnits, $protectedUnits);
                }
                $stmt = $db->prepare($sqlUnits);
                $stmt->execute($paramsUnits);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $delIds = [];
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0) $delIds[] = $id;
                }
                if (!empty($delIds)) {
                    $db->prepare("DELETE FROM units WHERE id IN (" . $placeholders($delIds) . ")")->execute($delIds);
                }
            } catch (\Throwable $e) {
            }

            try {
                $sqlTen = "SELECT id FROM tenants WHERE (user_id = ? OR property_id IN (SELECT id FROM properties WHERE owner_id = ? OR manager_id = ? OR agent_id = ? OR caretaker_user_id = ?))";
                $paramsTen = [(int)$userId, (int)$userId, (int)$userId, (int)$userId, (int)$userId];
                if (!empty($protectedTenants)) {
                    $sqlTen .= " AND id NOT IN (" . $placeholders($protectedTenants) . ")";
                    $paramsTen = array_merge($paramsTen, $protectedTenants);
                }
                $stmt = $db->prepare($sqlTen);
                $stmt->execute($paramsTen);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $delIds = [];
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0) $delIds[] = $id;
                }
                if (!empty($delIds)) {
                    $db->prepare("DELETE FROM tenants WHERE id IN (" . $placeholders($delIds) . ")")->execute($delIds);
                }
            } catch (\Throwable $e) {
            }

            try {
                $sqlLease = "SELECT id FROM leases WHERE id IN (SELECT lease_id FROM payments WHERE user_id = ?)";
                $stmt = $db->prepare($sqlLease);
                $stmt->execute([(int)$userId]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $delIds = [];
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id > 0 && !in_array($id, $protectedLeases, true)) $delIds[] = $id;
                }
                if (!empty($delIds)) {
                    $db->prepare("DELETE FROM leases WHERE id IN (" . $placeholders($delIds) . ")")->execute($delIds);
                }
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a given URL
     * 
     * @param string $path The path to redirect to
     * @return void
     */
    function redirect($path)
    {
        if (!defined('BASE_URL')) {
            $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
            $http_host = $_SERVER['HTTP_HOST'] ?? '';
            
            // Detect if we're on cPanel (domain-based hosting)
            if (strpos($http_host, 'rentsmart.timestentechnologies.co.ke') !== false) {
                // On cPanel addon domain, the domain points directly to this directory
                // So the base URL should be empty (root)
                $base_url = '';
            } else {
                // Localhost or other environments - app is in a subdirectory
                $base_dir = dirname($script_name);
                $base_url = $base_dir !== '/' ? $base_dir : '';
            }
            
            define('BASE_URL', $base_url);
        }

        $path = trim($path, '/');
        $location = BASE_URL . '/' . $path;
        
        header("Location: {$location}");
        exit;
    }
}

if (!function_exists('asset')) {
    /**
     * Get the URL for an asset file
     * 
     * @param string $path The path to the asset file
     * @return string The complete URL to the asset
     */
    function asset($path)
    {
        if (!defined('BASE_URL')) {
            $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
            $http_host = $_SERVER['HTTP_HOST'] ?? '';
            
            // Detect if we're on cPanel (domain-based hosting)
            if (strpos($http_host, 'rentsmart.timestentechnologies.co.ke') !== false) {
                // On cPanel addon domain, the domain points directly to this directory
                // So the base URL should be empty (root)
                $base_url = '';
            } else {
                // Localhost or other environments - app is in a subdirectory
                $base_dir = dirname($script_name);
                $base_url = $base_dir !== '/' ? $base_dir : '';
            }
            
            define('BASE_URL', $base_url);
        }
        return BASE_URL . '/public/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('site_settings_all')) {
    function site_settings_all()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        try {
            if (!class_exists('App\\Models\\Setting')) {
                require_once __DIR__ . '/Models/Setting.php';
            }
            $settingsModel = new \App\Models\Setting();
            $cache = $settingsModel->getAllAsAssoc();
            if (!is_array($cache)) {
                $cache = [];
            }
        } catch (\Exception $e) {
            error_log('site_settings_all failed: ' . $e->getMessage());
            $cache = [];
        }

        return $cache;
    }
}

if (!function_exists('site_setting')) {
    function site_setting($key, $default = '')
    {
        $settings = site_settings_all();
        if (isset($settings[$key]) && $settings[$key] !== '') {
            return $settings[$key];
        }
        return $default;
    }
}

if (!function_exists('site_setting_image_url')) {
    function site_setting_image_url($key, $defaultUrl = '')
    {
        $filename = trim((string)site_setting($key, ''));
        if ($filename !== '') {
            return BASE_URL . '/public/assets/images/' . ltrim($filename, '/');
        }
        return $defaultUrl;
    }
}

if (!function_exists('site_setting_json')) {
    function site_setting_json($key, $default = [])
    {
        $raw = site_setting($key, '');
        if ($raw === '' || $raw === null) {
            return $default;
        }

        $decoded = json_decode((string)$raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) {
            return $default;
        }
        return $decoded;
    }
}

// Polyfill for getallheaders on environments where it's unavailable
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            } elseif ($name === 'CONTENT_TYPE') {
                $headers['Content-Type'] = $value;
            } elseif ($name === 'CONTENT_LENGTH') {
                $headers['Content-Length'] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('requireAuth')) {
    /**
     * Check if a user is authenticated and has an active subscription
     * 
     * @return void
     */
    function requireAuth()
    {
        $path = trim(current_uri(), '/');
        if (
            $path === 'subscription/initiate-stk' ||
            $path === 'subscription/verify-mpesa' ||
            $path === 'mpesa/callback'
        ) {
            return;
        }

        if (!empty($_SESSION['demo_mode'])) {
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            redirect('home');
        }

        // Allow notifications endpoints even if subscription is missing/expired
        // (still requires authentication via user_id)
        if (strpos($path, 'notifications/') === 0) {
            return;
        }

        // Skip subscription check for administrators and caretakers
        if (isset($_SESSION['user_role']) && (in_array(strtolower($_SESSION['user_role']), ['administrator','admin','caretaker']))) {
            return;
        }

        // Allow payment initiation/verification endpoints without active subscription
        if (
            $path === 'subscription/initiate-stk' ||
            $path === 'subscription/verify-mpesa' ||
            $path === 'mpesa/callback'
        ) {
            return;
        }

        // Check subscription status
        $subscription = new \App\Models\Subscription();
        $userSubscription = $subscription->getUserSubscription($_SESSION['user_id']);
        
        if ($userSubscription) {
            $_SESSION['subscription_ends_at'] = $userSubscription['status'] === 'trialing' 
                ? $userSubscription['trial_ends_at'] 
                : $userSubscription['current_period_ends_at'];
            $_SESSION['subscription_status'] = $userSubscription['status'];
            // If subscription is not active, set the expired flag but don't redirect immediately
            if (!$subscription->isSubscriptionActive($_SESSION['user_id'])) {
                $_SESSION['subscription_expired'] = true;
            } else {
                unset($_SESSION['subscription_expired']);
            }
        } else {
            // No subscription found, redirect to renewal page
            $_SESSION['flash_message'] = 'Please set up your subscription to continue.';
            $_SESSION['flash_type'] = 'warning';
            redirect('subscription/renew');
        }
    }
}

if (!function_exists('current_uri')) {
    /**
     * Get the current URI path
     * 
     * @return string The current URI path
     */
    function current_uri()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!is_string($uri)) {
            $uri = '/';
        }
        $base_path = parse_url(BASE_URL, PHP_URL_PATH);
        if (!is_string($base_path)) {
            $base_path = '';
        }
        return substr($uri, strlen($base_path));
    }
}

if (!function_exists('is_current_path')) {
    /**
     * Check if the current URI matches a given path
     * 
     * @param string $path The path to check against
     * @return bool True if the current URI matches the path
     */
    function is_current_path($path)
    {
        $current = trim(current_uri(), '/');
        $path = trim($path, '/');
        return $current === $path;
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as currency
     * 
     * @param float $amount The amount to format
     * @return string The formatted amount
     */
    function format_currency($amount)
    {
        return 'Ksh ' . number_format($amount, 2);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date in a human-readable format
     * 
     * @param string $date The date to format
     * @param string $format The format to use (default: 'M d, Y')
     * @return string The formatted date
     */
    function format_date($date, $format = 'M d, Y')
    {
        return date($format, strtotime($date));
    }
}

if (!function_exists('old')) {
    function old($key, $default = '')
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token()
    {
        $token = $_POST['csrf_token'] ?? null;
        // Check header for AJAX requests
        if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return $token && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch ($status) {
            case 'vacant':
                return 'success';
            case 'occupied':
                return 'primary';
            case 'maintenance':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('send_email')) {
    function send_email($to, $subject, $body)
    {
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = get_setting('smtp_host');
        $mail->SMTPAuth = true;
        $mail->Username = get_setting('smtp_user');
        $mail->Password = get_setting('smtp_pass');
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = get_setting('smtp_port');
        $fromEmail = get_setting('smtp_user') ?: get_setting('site_email');
        $fromName = get_setting('site_name');
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = defined('BASE_URL') ? BASE_URL : '';
        $siteUrl = rtrim((string)(get_setting('site_url') ?: ($scheme . '://' . $host . $base)), '/');
        $logo = get_setting('site_logo');
        $logoUrl = $logo ? ($siteUrl . '/public/assets/images/' . $logo) : '';
        $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
        $wrapped =
            '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
            . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
            '<div style="white-space:pre-wrap;">' . nl2br(htmlspecialchars((string)$body)) . '</div>' .
            $footer .
            '</div>';
        $mail->Body = $wrapped;
        $mail->AltBody = (string)$body;
        $mail->isHTML(true);
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log('Email error: ' . $mail->ErrorInfo);
        }
    }
} 