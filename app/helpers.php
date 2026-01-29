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

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            redirect('home');
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
        $mail->SMTPSecure = 'tls';
        $mail->Port = get_setting('smtp_port');
        $mail->setFrom(get_setting('site_email'), get_setting('site_name'));
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(false);
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log('Email error: ' . $mail->ErrorInfo);
        }
    }
} 