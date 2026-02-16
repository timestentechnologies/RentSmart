<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Setting;
use App\Models\PasswordReset;
use App\Models\ActivityLog;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use Exception;
use DateTime;
use App\Database\Connection;

class AuthController
{
    private $user;
    private $subscription;
    private $activityLog;

    public function __construct()
    {
        $this->user = new User();
        $this->subscription = new Subscription();
        $this->activityLog = new ActivityLog();
    }

    public function showLogin()
    {
        header('Location: ' . BASE_URL . '/#loginModal');
        exit;
    }

    public function showRegister()
    {
        header('Location: ' . BASE_URL . '/#registerModal');
        exit;
    }

    public function register()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/register');
                exit;
            }

            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

            if (!$name || !$email || !$phone || !$address || !$password || !$role) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }
                $_SESSION['flash_message'] = 'All fields are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/register');
                exit;
            }

            if ($confirmPassword !== '' && $password !== $confirmPassword) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Passwords do not match';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/register');
                exit;
            }

            $hasMinLen = strlen($password) >= 8;
            $hasUpper = (bool)preg_match('/[A-Z]/', $password);
            $hasLower = (bool)preg_match('/[a-z]/', $password);
            $hasDigit = (bool)preg_match('/\d/', $password);
            $hasSpecial = (bool)preg_match('/[^A-Za-z0-9]/', $password);
            if (!$hasMinLen || !$hasUpper || !$hasLower || !$hasDigit || !$hasSpecial) {
                $msg = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                $_SESSION['flash_message'] = $msg;
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/register');
                exit;
            }

            // Validate role
            if (!in_array($role, ['landlord', 'agent', 'manager', 'realtor'])) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Invalid role selected';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/register');
                exit;
            }

            // Check if email already exists
            if ($this->user->findByEmail($email)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Email already registered']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Email already registered';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/register');
                exit;
            }

            // Prepare user data
            $userData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'password' => $password,
                'role' => $role,
                'is_subscribed' => true,
                'trial_ends_at' => (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s')
            ];
            
            // Set manager_id for agent role
            if ($role === 'agent') {
                // Assign agent to themselves as manager for now
                // This can be modified later to assign to a specific manager
                $userData['manager_id'] = null; // Will be updated after user creation
            }
            
            // Create user
            $userId = $this->user->createUser($userData);

            // Update manager_id for agent role
            if ($role === 'agent') {
                $this->user->update($userId, [
                    'manager_id' => $userId
                ]);
            }

            // Create subscription with Basic plan (id=1)
            $this->subscription->createSubscription($userId, 1);

            // Create Odoo CRM lead (do not block registration on failures)
            try {
                $this->createOdooLeadFromRegistration([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'role' => $role,
                    'user_id' => $userId,
                ]);
            } catch (\Throwable $ex) {
                error_log('Odoo lead create failed (non-blocking): ' . $ex->getMessage());
            }

            // Activity Log: auth.register
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $this->activityLog->add(
                    (int)$userId,
                    strtolower($role),
                    'auth.register',
                    'user',
                    (int)$userId,
                    null,
                    json_encode(['name' => $name, 'email' => $email, 'role' => $role]),
                    $ip,
                    $agent
                );
            } catch (\Exception $ex) { error_log('auth.register log failed: ' . $ex->getMessage()); }

            // Send email notifications
            try {
                $settingModel = new Setting();
                $settings = $settingModel->getAllAsAssoc();
                $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
                $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $settings['smtp_host'] ?? '';
                $mail->Port = $settings['smtp_port'] ?? 587;
                $mail->SMTPAuth = true;
                $mail->Username = $settings['smtp_user'] ?? '';
                $mail->Password = $settings['smtp_pass'] ?? '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                $mail->addReplyTo($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                $mail->isHTML(true);

                // Email to user
                $mail->clearAddresses();
                $mail->addAddress($email, $name);
                $mail->Subject = 'Welcome to RentSmart!';
                $mail->Body =
                    '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                    . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                    '<p style="font-size:16px;">Dear ' . htmlspecialchars($name) . ',</p>' .
                    '<p>Welcome to RentSmart! Your account has been created and your 7-day trial has started.</p>' .
                    '<p>Login to your dashboard to get started.</p>' .
                    '<p>Thank you,<br>RentSmart Team</p>' .
                    $footer .
                    '</div>';
                $userMailSent = false;
                try {
                    $userMailSent = $mail->send();
                    error_log('Registration user email sent: ' . ($userMailSent ? 'yes' : 'no'));
                } catch (MailException $e) {
                    error_log('Registration user email error: ' . $e->getMessage());
                }

                // Email to admin
                if (!empty($settings['site_email'])) {
                    $mail->clearAddresses();
                    $mail->addAddress($settings['site_email'], $settings['site_name'] ?? 'Admin');
                    $mail->Subject = 'New User Registration on RentSmart';
                    $mail->Body =
                        '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                        '<p style="font-size:16px;">A new user has registered:</p>' .
                        '<ul style="font-size:15px;">'
                        . '<li><strong>Name:</strong> ' . htmlspecialchars($name) . '</li>'
                        . '<li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>'
                        . '<li><strong>Role:</strong> ' . htmlspecialchars($role) . '</li>'
                        . '</ul>' .
                        '<p>Login to the admin dashboard for more details.</p>' .
                        $footer .
                        '</div>';
                    $adminMailSent = false;
                    try {
                        $adminMailSent = $mail->send();
                        error_log('Registration admin email sent: ' . ($adminMailSent ? 'yes' : 'no'));
                    } catch (MailException $e) {
                        error_log('Registration admin email error: ' . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                error_log('Registration email error (non-blocking): ' . $e->getMessage());
            }

            // Set session variables
            unset($_SESSION['tenant_id'], $_SESSION['impersonating'], $_SESSION['original_user']);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Welcome to RentSmart! Your 7-day trial has started.']);
                exit;
            }

            // Redirect to dashboard
            $_SESSION['flash_message'] = 'Welcome to RentSmart! Your 7-day trial has started.';
            $_SESSION['flash_type'] = 'success';
            $redirectPath = ($role === 'realtor') ? '/realtor/dashboard' : '/dashboard';
            header('Location: ' . BASE_URL . $redirectPath);
            exit;
        } catch (\Throwable $e) {
            error_log("Registration error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Registration error: ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_message'] = 'Registration error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
    }

    private function createOdooLeadFromRegistration(array $payload): void
    {
        $cfg = $this->getOdooConfig();
        if (empty($cfg['url']) || empty($cfg['db']) || empty($cfg['username']) || empty($cfg['password'])) {
            error_log('Odoo lead skipped: missing config (url/db/username/password)');
            return;
        }

        if (!function_exists('xmlrpc_encode_request') || !function_exists('xmlrpc_decode')) {
            error_log('Odoo lead skipped: PHP XML-RPC functions are not available on this server');
            return;
        }

        $urlBase = rtrim((string)$cfg['url'], '/');
        $db = (string)$cfg['db'];
        $username = (string)$cfg['username'];
        $password = (string)$cfg['password'];

        $uid = $this->odooAuthenticate($urlBase, $db, $username, $password);
        if (empty($uid)) {
            error_log('Odoo lead skipped: authentication failed');
            return;
        }

        $name = (string)($payload['name'] ?? '');
        $email = (string)($payload['email'] ?? '');
        $phone = (string)($payload['phone'] ?? '');
        $address = (string)($payload['address'] ?? '');
        $role = (string)($payload['role'] ?? '');
        $userId = (int)($payload['user_id'] ?? 0);

        $leadTitle = trim($name !== '' ? $name : $email);
        if ($leadTitle === '') {
            $leadTitle = 'New Website Registration';
        }

        $descParts = [];
        if ($role !== '') $descParts[] = 'Role: ' . $role;
        if ($address !== '') $descParts[] = 'Address: ' . $address;
        if ($userId > 0) $descParts[] = 'RentSmart User ID: ' . $userId;
        $descParts[] = 'Source: Website Registration';
        $description = implode("\n", $descParts);

        $leadData = [
            'name' => 'RentSmart - ' . $leadTitle,
            'type' => 'lead',
            'contact_name' => $name,
            'email_from' => $email,
            'phone' => $phone,
            'description' => $description,
        ];

        $createdId = $this->odooExecuteKw($urlBase, $db, (int)$uid, $password, 'crm.lead', 'create', [$leadData]);
        if (!empty($createdId)) {
            error_log('Odoo lead created from registration. Lead ID: ' . (is_scalar($createdId) ? (string)$createdId : json_encode($createdId)));
        }
    }

    private function getOdooConfig(): array
    {
        $out = [
            'url' => getenv('ODOO_URL') ?: null,
            'db' => getenv('ODOO_DB') ?: null,
            'username' => getenv('ODOO_USERNAME') ?: null,
            'password' => getenv('ODOO_PASSWORD') ?: null,
        ];

        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('odoo_url','odoo_database','odoo_username','odoo_password')");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
            if (!empty($rows['odoo_url'])) $out['url'] = (string)$rows['odoo_url'];
            if (!empty($rows['odoo_database'])) $out['db'] = (string)$rows['odoo_database'];
            if (!empty($rows['odoo_username'])) $out['username'] = (string)$rows['odoo_username'];
            if (!empty($rows['odoo_password'])) $out['password'] = (string)$rows['odoo_password'];
        } catch (\Exception $e) {
            // ignore
        }

        if (!empty($out['url'])) {
            $raw = trim((string)$out['url']);
            $p = @parse_url($raw);
            if (!empty($p['scheme']) && !empty($p['host'])) {
                $out['url'] = $p['scheme'] . '://' . $p['host'];
                if (!empty($p['port'])) {
                    $out['url'] .= ':' . $p['port'];
                }
            }
        }

        return $out;
    }

    private function odooAuthenticate(string $urlBase, string $db, string $username, string $password): ?int
    {
        if (!function_exists('xmlrpc_encode_request') || !function_exists('xmlrpc_decode')) {
            return null;
        }
        $url = $urlBase . '/xmlrpc/2/common';
        $req = xmlrpc_encode_request('authenticate', [$db, $username, $password, []]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml\r\n",
                'content' => $req,
                'timeout' => 10,
            ]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            $last = error_get_last();
            error_log('Odoo auth failed: HTTP request failed. URL: ' . $url . ' Error: ' . ($last['message'] ?? 'unknown'));
            return null;
        }
        $decoded = xmlrpc_decode($resp);
        if (is_array($decoded) && xmlrpc_is_fault($decoded)) {
            error_log('Odoo auth fault: ' . ($decoded['faultString'] ?? 'unknown'));
            return null;
        }
        $uid = (int)$decoded;
        return $uid > 0 ? $uid : null;
    }

    private function odooExecuteKw(string $urlBase, string $db, int $uid, string $password, string $model, string $method, array $params = [])
    {
        if (!function_exists('xmlrpc_encode_request') || !function_exists('xmlrpc_decode')) {
            throw new \Exception('XML-RPC not available');
        }
        $url = $urlBase . '/xmlrpc/2/object';
        $req = xmlrpc_encode_request('execute_kw', [$db, $uid, $password, $model, $method, $params]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml\r\n",
                'content' => $req,
                'timeout' => 10,
            ]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            $last = error_get_last();
            error_log('Odoo execute_kw failed: HTTP request failed. URL: ' . $url . ' Model: ' . $model . ' Method: ' . $method . ' Error: ' . ($last['message'] ?? 'unknown'));
            throw new \Exception('Failed to connect to Odoo');
        }
        $decoded = xmlrpc_decode($resp);
        if (is_array($decoded) && xmlrpc_is_fault($decoded)) {
            error_log('Odoo execute_kw fault. Model: ' . $model . ' Method: ' . $method . ' Fault: ' . ($decoded['faultString'] ?? 'unknown'));
            throw new \Exception('Odoo API fault: ' . ($decoded['faultString'] ?? 'unknown'));
        }
        return $decoded;
    }

    public function login()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                // Activity Log: auth.login_failed (CSRF)
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $this->activityLog->add(
                        null,
                        null,
                        'auth.login_failed',
                        'auth',
                        null,
                        null,
                        json_encode(['reason' => 'csrf_invalid']),
                        $ip,
                        $agent
                    );
                } catch (\Exception $ex) { error_log('auth.login_failed (csrf) log failed: ' . $ex->getMessage()); }
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $emailOrPhone = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
            $password = $_POST['password'] ?? '';

            if (!$emailOrPhone || !$password) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Email/phone and password are required']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Email/phone and password are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            // Check if input is email or phone, then find user
            $user = null;
            if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
                // It's an email
                $user = $this->user->findByEmail($emailOrPhone);
            } else {
                // It's a phone number - check if user model has phone lookup method
                if (method_exists($this->user, 'findByPhone')) {
                    $user = $this->user->findByPhone($emailOrPhone);
                } else {
                    // Fallback: try to find by email if phone lookup not available
                    $user = $this->user->findByEmail($emailOrPhone);
                }
            }

            if (!$user || !password_verify($password, $user['password'])) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                    // Activity Log: auth.login_failed (invalid credentials)
                    try {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        $this->activityLog->add(
                            null,
                            null,
                            'auth.login_failed',
                            'auth',
                            null,
                            null,
                            json_encode(['reason' => 'invalid_credentials', 'identifier' => $emailOrPhone]),
                            $ip,
                            $agent
                        );
                    } catch (\Exception $ex) { error_log('auth.login_failed log failed: ' . $ex->getMessage()); }
                    exit;
                }
                $_SESSION['flash_message'] = 'Invalid email or password';
                $_SESSION['flash_type'] = 'danger';
                // Activity Log: auth.login_failed (invalid credentials)
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $this->activityLog->add(
                        null,
                        null,
                        'auth.login_failed',
                        'auth',
                        null,
                        null,
                        json_encode(['reason' => 'invalid_credentials', 'identifier' => $emailOrPhone]),
                        $ip,
                        $agent
                    );
                } catch (\Exception $ex) { error_log('auth.login_failed log failed: ' . $ex->getMessage()); }
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            // Check subscription status for non-admin users
            if ($user['role'] !== 'admin' && $user['role'] !== 'administrator') {
                $subscription = $this->subscription->getUserSubscription($user['id']);
                $_SESSION['subscription_expired'] = false;
                
                if (!$subscription || !$this->subscription->isSubscriptionActive($user['id'])) {
                    $_SESSION['subscription_expired'] = true;
                    $_SESSION['subscription_ends_at'] = $subscription ? $subscription['current_period_ends_at'] : null;
                }

                if ($subscription) {
                    // Store subscription expiry in session for dashboard notice
                    $_SESSION['subscription_ends_at'] = $subscription['status'] === 'trialing' 
                        ? $subscription['trial_ends_at'] 
                        : $subscription['current_period_ends_at'];
                    $_SESSION['subscription_status'] = $subscription['status'];
                }
            }

            // Update last login time
            $this->user->update($user['id'], [
                'last_login_at' => (new DateTime())->format('Y-m-d H:i:s')
            ]);

            // Set session variables
            unset($_SESSION['tenant_id'], $_SESSION['impersonating'], $_SESSION['original_user']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = strtolower($user['role']);
            $_SESSION['is_admin'] = ($user['role'] === 'admin' || $user['role'] === 'administrator');

            // Clear any stale flash messages from previous redirects (e.g. auth-required messages)
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);

            // Activity Log: auth.login
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $this->activityLog->add(
                    (int)$user['id'],
                    strtolower($user['role']),
                    'auth.login',
                    'user',
                    (int)$user['id'],
                    null,
                    json_encode(['email' => $user['email'] ?? null]),
                    $ip,
                    $agent
                );
            } catch (\Exception $ex) { error_log('auth.login log failed: ' . $ex->getMessage()); }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Login successful']);
                exit;
            }

            // Redirect to dashboard
            $redirectPath = (strtolower((string)($user['role'] ?? '')) === 'realtor') ? '/realtor/dashboard' : '/dashboard';
            header('Location: ' . BASE_URL . $redirectPath);
            exit;
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'An error occurred during login']);
                exit;
            }
            $_SESSION['flash_message'] = 'An error occurred during login';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    public function showForgotPassword()
    {
        try {
            echo view('auth/forgot_password', [
                'title' => 'Forgot Password'
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }

    public function sendResetLink()
    {
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }

            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            if (!$email) {
                $_SESSION['flash_message'] = 'Please enter a valid email address';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }

            $user = $this->user->findByEmail($email);
            $passwordReset = new PasswordReset();
            if ($user) {
                $token = $passwordReset->createToken($email, 60);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $resetUrl = $scheme . '://' . $host . BASE_URL . '/reset-password/' . $token;

                $settingModel = new Setting();
                $settings = $settingModel->getAllAsAssoc();
                $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($scheme . '://' . $host . BASE_URL . '/public/assets/images/' . $settings['site_logo']) : '';
                $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = (int)($settings['smtp_port'] ?? 587);
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->addReplyTo($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->isHTML(true);
                    $mail->clearAddresses();
                    $mail->addAddress($email);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body =
                        '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '')
                        . '<p style="font-size:16px;">We received a request to reset your password.</p>'
                        . '<p>Click the button below to reset your password. This link will expire in 60 minutes.</p>'
                        . '<p style="text-align:center;margin:24px 0;"><a href="' . htmlspecialchars($resetUrl) . '" style="background:#0061f2;color:#fff;padding:12px 18px;border-radius:6px;text-decoration:none;display:inline-block;">Reset Password</a></p>'
                        . '<p>If you did not request a password reset, you can safely ignore this email.</p>'
                        . $footer
                        . '</div>';
                    try {
                        $mail->send();
                        error_log('Password reset email sent to ' . $email);
                    } catch (MailException $me) {
                        error_log('Password reset email error: ' . $me->getMessage());
                    }
                } catch (MailException $e) {
                    error_log('Mailer setup error: ' . $e->getMessage());
                }
            }

            $_SESSION['flash_message'] = 'If that email exists in our system, we have emailed a password reset link.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'An error occurred. Please try again.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }
    }

    public function showResetForm($token)
    {
        try {
            $passwordReset = new PasswordReset();
            $record = $passwordReset->findByToken($token);
            if (!$record) {
                $_SESSION['flash_message'] = 'This reset link is invalid or has expired. Please request a new one.';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }
            echo view('auth/reset_password', [
                'title' => 'Reset Password',
                'token' => $token,
                'email' => $record['email']
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }

    public function resetPassword()
    {
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }

            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirmation'] ?? '';

            if (!$token || !$password || !$passwordConfirm) {
                $_SESSION['flash_message'] = 'Please fill in all required fields';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }
            if ($password !== $passwordConfirm) {
                $_SESSION['flash_message'] = 'Passwords do not match';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/reset-password/' . urlencode($token));
                exit;
            }
            if (strlen($password) < 6) {
                $_SESSION['flash_message'] = 'Password must be at least 6 characters';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/reset-password/' . urlencode($token));
                exit;
            }

            $passwordReset = new PasswordReset();
            $record = $passwordReset->findByToken($token);
            if (!$record) {
                $_SESSION['flash_message'] = 'This reset link is invalid or has expired. Please request a new one.';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }

            $user = $this->user->findByEmail($record['email']);
            if (!$user) {
                $passwordReset->deleteByToken($token);
                $_SESSION['flash_message'] = 'Account not found for this reset request.';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/forgot-password');
                exit;
            }

            $this->user->updatePassword($user['id'], $password);
            $passwordReset->deleteByToken($token);

            $_SESSION['flash_message'] = 'Your password has been reset. Please log in.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/');
            exit;
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'An error occurred while resetting your password.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }
    }

    public function logout()
    {
        try {
            // Activity Log: auth.logout (log before destroying session)
            try {
                $uid = $_SESSION['user_id'] ?? null;
                $role = $_SESSION['user_role'] ?? null;
                if ($uid) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $this->activityLog->add(
                        (int)$uid,
                        $role,
                        'auth.logout',
                        'user',
                        (int)$uid,
                        null,
                        null,
                        $ip,
                        $agent
                    );
                }
            } catch (\Exception $ex) { error_log('auth.logout log failed: ' . $ex->getMessage()); }
            // Clear all session variables
            session_unset();
            
            // Destroy the session
            session_destroy();
            
            // Redirect to home page instead of login
            header('Location: ' . BASE_URL . '/');
            exit;
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }
} 