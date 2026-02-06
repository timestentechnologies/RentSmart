<?php

namespace App\Controllers;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\Setting;

class ContactMessagesController
{
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    public function index()
    {
        try {
            $cm = new ContactMessage();
            $messages = $cm->getAll();
            echo view('admin/contact_messages', [
                'title' => 'Contact Messages',
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            error_log('ContactMessagesController@index error: ' . $e->getMessage());
            echo view('errors/500', ['title' => '500 Internal Server Error']);
        }
    }

    public function show($id)
    {
        try {
            $cm = new ContactMessage();
            $msg = $cm->find((int)$id);
            if (!$msg) {
                $_SESSION['flash_message'] = 'Message not found';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/contact-messages');
            }

            $replyModel = new ContactMessageReply();
            $replies = $replyModel->getByMessageId((int)$id);

            echo view('admin/contact_message_show', [
                'title' => 'Contact Message',
                'message' => $msg,
                'replies' => $replies,
            ]);
        } catch (\Exception $e) {
            error_log('ContactMessagesController@show error: ' . $e->getMessage());
            echo view('errors/500', ['title' => '500 Internal Server Error']);
        }
    }

    public function reply($id)
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new \Exception('Invalid request');
            }
            if (function_exists('verify_csrf_token') && !verify_csrf_token()) {
                throw new \Exception('Invalid security token');
            }

            $cm = new ContactMessage();
            $msg = $cm->find((int)$id);
            if (!$msg) {
                throw new \Exception('Message not found');
            }

            $replyText = trim($_POST['reply_message'] ?? '');
            if ($replyText === '') {
                throw new \Exception('Reply message is required');
            }

            $replyModel = new ContactMessageReply();
            $replyModel->create([
                'contact_message_id' => (int)$id,
                'user_id' => (int)($_SESSION['user_id'] ?? 0),
                'reply_message' => $replyText,
            ]);

            $recipientEmail = trim((string)($msg['email'] ?? ''));
            if ($recipientEmail !== '') {
                $settingModel = new Setting();
                $settings = $settingModel->getAllAsAssoc();

                $fromEmail = $settings['smtp_user'] ?? ($settings['site_email'] ?? '');
                $fromName = $settings['site_name'] ?? 'RentSmart';
                $siteUrl = rtrim($settings['site_url'] ?? (defined('BASE_URL') ? BASE_URL : ''), '/');

                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = (int)($settings['smtp_port'] ?? 587);
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($recipientEmail, $msg['name'] ?? '');
                    $mail->isHTML(true);
                    $mail->Subject = 'Re: ' . ($msg['subject'] ?? 'Contact message');
                    $body = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">'
                        . '<h3>Reply from ' . htmlspecialchars($fromName) . '</h3>'
                        . '<p><strong>Your message:</strong><br>' . nl2br(htmlspecialchars($msg['message'] ?? '')) . '</p>'
                        . '<hr>'
                        . '<p><strong>Our reply:</strong><br>' . nl2br(htmlspecialchars($replyText)) . '</p>'
                        . ($siteUrl ? '<p style="margin-top:20px;"><a href="' . htmlspecialchars($siteUrl) . '">Visit our site</a></p>' : '')
                        . '</div>';
                    $mail->Body = $body;
                    $mail->send();
                } catch (\Exception $ex) {
                    error_log('Contact reply email failed: ' . $ex->getMessage());
                }
            }

            $_SESSION['flash_message'] = 'Reply sent successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        redirect('/admin/contact-messages/show/' . (int)$id);
    }
}
