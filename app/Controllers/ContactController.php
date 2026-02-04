<?php

namespace App\Controllers;

use App\Models\ContactMessage;

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
            $cm->create([
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'subject' => $subject,
                'message' => $message,
            ]);
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
