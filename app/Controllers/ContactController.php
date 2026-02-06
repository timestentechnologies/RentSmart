<?php

namespace App\Controllers;

use App\Models\ContactMessage;
use App\Models\Setting;

class ContactController
{
    public function index()
    {
        require 'views/contact/index.php';
    }

    public function submit()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new \Exception('Invalid request'); }
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if ($name === '' || $subject === '' || $message === '') { throw new \Exception('Please fill in required fields'); }
            $cm = new ContactMessage();
            $msgId = (int)$cm->create([
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'subject' => $subject,
                'message' => $message,
            ]);

            // Email notify support/admin
            try {
                $settingModel = new Setting();
                $settings = $settingModel->getAllAsAssoc();
                $to = $settings['site_email'] ?? ($settings['smtp_user'] ?? '');
                $fromEmail = $settings['smtp_user'] ?? ($settings['site_email'] ?? '');
                if (!empty($to) && !empty($fromEmail)) {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = (int)($settings['smtp_port'] ?? 587);
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($fromEmail, $settings['site_name'] ?? 'RentSmart');
                    $mail->addAddress($to);
                    if (!empty($email)) {
                        try { $mail->addReplyTo($email, $name); } catch (\Exception $e) {}
                    }
                    $mail->isHTML(true);
                    $mail->Subject = 'New Contact Us message: ' . $subject;
                    $body = '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto;">'
                        . '<h3>New Contact Us Message</h3>'
                        . '<p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>'
                        . '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>'
                        . '<p><strong>Phone:</strong> ' . htmlspecialchars($phone) . '</p>'
                        . '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>'
                        . '<p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>'
                        . ($msgId ? '<p><strong>Message ID:</strong> ' . (int)$msgId . '</p>' : '')
                        . '</div>';
                    $mail->Body = $body;
                    $mail->send();
                }
            } catch (\Exception $e) {
                error_log('Contact notify email failed: ' . $e->getMessage());
            }
            $_SESSION['flash_message'] = 'Thank you! We will get back to you shortly.';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/contact');
        exit;
    }
}
