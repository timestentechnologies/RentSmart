<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use Exception;
use DateTime;

class AuthController
{
    private $user;
    private $subscription;

    public function __construct()
    {
        $this->user = new User();
        $this->subscription = new Subscription();
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

            // Validate role
            if (!in_array($role, ['landlord', 'agent'])) {
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
            } catch (MailException $e) {
                error_log('Registration email error: ' . $e->getMessage());
            }

            // Set session variables
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
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'An error occurred during registration']);
                exit;
            }
            $_SESSION['flash_message'] = 'An error occurred during registration';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
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
                    exit;
                }
                $_SESSION['flash_message'] = 'Invalid email or password';
                $_SESSION['flash_type'] = 'danger';
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = strtolower($user['role']);
            $_SESSION['is_admin'] = ($user['role'] === 'admin' || $user['role'] === 'administrator');

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Login successful']);
                exit;
            }

            // Redirect to dashboard
            header('Location: ' . BASE_URL . '/dashboard');
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

    public function logout()
    {
        try {
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