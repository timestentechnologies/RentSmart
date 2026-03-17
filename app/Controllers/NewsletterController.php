<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;

class NewsletterController
{
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/home');
        }

        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if (!in_array($role, ['admin', 'administrator'], true)) {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/dashboard');
        }
    }

    public function index()
    {
        $db = Connection::getInstance()->getConnection();
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);

        try {
            $where = '';
            $params = [];
            if ($search) {
                $where = 'WHERE title LIKE ? OR subject LIKE ?';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $stmt = $db->prepare("SELECT * FROM email_campaigns $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $campaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM email_campaigns $where");
            $countStmt->execute($params);
            $total = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($total / $limit);

            echo \view('admin/newsletters/index', [
                'title' => 'Newsletter Management',
                'campaigns' => $campaigns,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'search' => $search
            ]);
        } catch (\Exception $e) {
            error_log("Newsletter index error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading newsletters';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/admin/dashboard');
        }
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->storeCampaign();
            return;
        }

        // Get system settings for dynamic content
        $settings = (new Setting())->getAllAsAssoc();
        $siteUrl = BASE_URL;
        // Ensure we have a full URL for email clients
        if (empty($siteUrl)) {
            $siteUrl = 'https://' . $_SERVER['HTTP_HOST'];
        }
        $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : ($siteUrl . '/public/assets/images/logo.png');
        $siteName = $settings['site_name'] ?? 'RentSmart';

        echo \view('admin/newsletters/create', [
            'title' => 'Create Newsletter',
            'settings' => $settings,
            'logoUrl' => $logoUrl,
            'siteName' => $siteName
        ]);
    }

    public function storeCampaign()
    {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $content = $_POST['content'] ?? '';
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?: 'newsletter';
        $scheduleDate = filter_input(INPUT_POST, 'schedule_date', FILTER_SANITIZE_STRING);
        $scheduleTime = filter_input(INPUT_POST, 'schedule_time', FILTER_SANITIZE_STRING);

        if (!$title || !$subject || !$content) {
            $_SESSION['flash_message'] = 'Please fill in all required fields';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/admin/newsletters/create');
            exit;
        }

        try {
            $db = Connection::getInstance()->getConnection();
            $scheduledAt = null;
            $status = 'draft';

            if ($scheduleDate && $scheduleTime) {
                $scheduledAt = $scheduleDate . ' ' . $scheduleTime . ':00';
                $status = 'scheduled';
            }

            $stmt = $db->prepare("INSERT INTO email_campaigns (title, subject, content, type, status, scheduled_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subject, $content, $type, $status, $scheduledAt, $_SESSION['user_id']]);

            $campaignId = $db->lastInsertId();

            // Handle attachments
            if (!empty($_FILES['attachments']['name'][0])) {
                $this->handleAttachments($campaignId);
            }

            // Handle survey questions
            if (!empty($_POST['survey_questions'])) {
                $this->handleSurveyQuestions($campaignId);
            }

            $_SESSION['flash_message'] = 'Newsletter created successfully';
            $_SESSION['flash_type'] = 'success';
            \redirect('/admin/newsletters');
        } catch (\Exception $e) {
            error_log("Store campaign error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error creating newsletter';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/admin/newsletters/create');
        }
    }

    public function edit($id)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$campaign) {
                $_SESSION['flash_message'] = 'Newsletter not found';
                $_SESSION['flash_type'] = 'danger';
                \redirect('/admin/newsletters');
            }

            // Get attachments
            $attStmt = $db->prepare("SELECT * FROM campaign_attachments WHERE campaign_id = ?");
            $attStmt->execute([$id]);
            $attachments = $attStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get survey questions
            $surveyStmt = $db->prepare("SELECT * FROM survey_questions WHERE campaign_id = ? ORDER BY order_index");
            $surveyStmt->execute([$id]);
            $surveyQuestions = $surveyStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get system settings for dynamic content
            $settings = (new Setting())->getAllAsAssoc();
            $siteUrl = BASE_URL;
            // Ensure we have a full URL for email clients
            if (empty($siteUrl)) {
                $siteUrl = 'https://' . $_SERVER['HTTP_HOST'];
            }
            $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : ($siteUrl . '/public/assets/images/logo.png');
            $siteName = $settings['site_name'] ?? 'RentSmart';

            echo \view('admin/newsletters/edit', [
                'title' => 'Edit Newsletter',
                'campaign' => $campaign,
                'attachments' => $attachments,
                'surveyQuestions' => $surveyQuestions,
                'settings' => $settings,
                'logoUrl' => $logoUrl,
                'siteName' => $siteName
            ]);
        } catch (\Exception $e) {
            error_log("Edit newsletter error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading newsletter';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/admin/newsletters');
        }
    }

    public function updateCampaign($id)
    {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $content = $_POST['content'] ?? '';
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?: 'newsletter';
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?: 'draft';
        $scheduleDate = filter_input(INPUT_POST, 'schedule_date', FILTER_SANITIZE_STRING);
        $scheduleTime = filter_input(INPUT_POST, 'schedule_time', FILTER_SANITIZE_STRING);

        if (!$title || !$subject || !$content) {
            $_SESSION['flash_message'] = 'Please fill in all required fields';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/admin/newsletters/edit/' . $id);
        }

        try {
            $db = Connection::getInstance()->getConnection();
            $scheduledAt = null;

            if ($status === 'scheduled' && $scheduleDate && $scheduleTime) {
                $scheduledAt = $scheduleDate . ' ' . $scheduleTime . ':00';
            }

            $stmt = $db->prepare("UPDATE email_campaigns SET title = ?, subject = ?, content = ?, type = ?, status = ?, scheduled_at = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $subject, $content, $type, $status, $scheduledAt, $id]);

            // Handle new attachments
            if (!empty($_FILES['attachments']['name'][0])) {
                $this->handleAttachments($id);
            }

            // Handle survey questions (delete existing and insert new)
            $db->prepare("DELETE FROM survey_questions WHERE campaign_id = ?")->execute([$id]);
            if (!empty($_POST['survey_questions'])) {
                $this->handleSurveyQuestions($id);
            }

            $_SESSION['flash_message'] = 'Newsletter updated successfully';
            $_SESSION['flash_type'] = 'success';
            \redirect('/admin/newsletters');
        } catch (\Exception $e) {
            error_log("Update campaign error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error updating newsletter';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/admin/newsletters/edit/' . $id);
        }
    }

    public function sendTest()
    {
        $campaignId = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
        $testEmail = filter_input(INPUT_POST, 'test_email', FILTER_VALIDATE_EMAIL);
        
        // Get form data for direct test (from create page)
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $content = $_POST['content'] ?? '';

        if (!$testEmail) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }

        if (!$campaignId && (!$title || !$subject || !$content)) {
            echo json_encode(['success' => false, 'message' => 'Missing campaign data']);
            exit;
        }

        try {
            if ($campaignId) {
                // Test existing campaign
                $db = Connection::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE id = ?");
                $stmt->execute([$campaignId]);
                $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$campaign) {
                    echo json_encode(['success' => false, 'message' => 'Campaign not found']);
                    exit;
                }

                $sent = $this->sendEmail($testEmail, 'Test User', $campaign['subject'], $campaign['content'], $campaign['id']);
            } else {
                // Test with direct form data (from create page)
                $sent = $this->sendEmail($testEmail, 'Test User', $subject, $content, null);
            }

            if ($sent) {
                echo json_encode(['success' => true, 'message' => 'Test email sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send test email']);
            }
        } catch (\Exception $e) {
            error_log("Send test error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error sending test email']);
        }
    }

    public function sendCampaign($id)
    {
        $isAjax = $_SERVER['REQUEST_METHOD'] === 'POST';
        
        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE id = ? AND status IN ('draft', 'scheduled')");
            $stmt->execute([$id]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$campaign) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Campaign not found or already sent']);
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Campaign not found or already sent';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/admin/newsletters');
                    exit;
                }
            }

            // Get all subscribed users
            $usersStmt = $db->prepare("SELECT id, name, email FROM users WHERE is_subscribed = 1");
            $usersStmt->execute();
            $users = $usersStmt->fetchAll(\PDO::FETCH_ASSOC);

            $sentCount = 0;
            foreach ($users as $user) {
                if ($this->sendEmail($user['email'], $user['name'], $campaign['subject'], $campaign['content'], $campaign['id'], $user['id'])) {
                    $sentCount++;
                }
            }

            // Update campaign status
            $updateStmt = $db->prepare("UPDATE email_campaigns SET status = 'sent', sent_at = NOW(), sent_count = ?, total_recipients = ? WHERE id = ?");
            $updateStmt->execute([$sentCount, count($users), $id]);

            if ($isAjax) {
                echo json_encode(['success' => true, 'message' => "Campaign sent to $sentCount recipients"]);
                exit;
            } else {
                $_SESSION['flash_message'] = "Campaign sent to $sentCount recipients";
                $_SESSION['flash_type'] = 'success';
                \redirect('/admin/newsletters');
            }
        } catch (\Exception $e) {
            error_log("Send campaign error: " . $e->getMessage());
            
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Error sending campaign']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Error sending campaign';
                $_SESSION['flash_type'] = 'danger';
                \redirect('/admin/newsletters');
            }
        }
    }

    public function viewStats($id)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            
            // Get campaign details
            $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$campaign) {
                $_SESSION['flash_message'] = 'Campaign not found';
                $_SESSION['flash_type'] = 'danger';
                \redirect('/admin/newsletters');
            }

            // Get tracking stats
            $trackingStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened, SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked FROM email_tracking WHERE campaign_id = ?");
            $trackingStmt->execute([$id]);
            $stats = $trackingStmt->fetch(\PDO::FETCH_ASSOC);

            // Get survey responses if any
            $surveyStmt = $db->prepare("SELECT q.question_text, COUNT(r.id) as response_count FROM survey_questions q LEFT JOIN survey_responses r ON q.id = r.question_id WHERE q.campaign_id = ? GROUP BY q.id, q.question_text");
            $surveyStmt->execute([$id]);
            $surveyStats = $surveyStmt->fetchAll(\PDO::FETCH_ASSOC);

            echo \view('admin/newsletters/stats', [
                'title' => 'Campaign Statistics',
                'campaign' => $campaign,
                'stats' => $stats,
                'surveyStats' => $surveyStats
            ]);
        } catch (\Exception $e) {
            error_log("View stats error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading statistics';
            $_SESSION['flash_type'] = 'danger';
            \redirect('/admin/newsletters');
        }
    }

    public function viewStatsAjax($id)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            
            // Get campaign details
            $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$campaign) {
                echo '<div class="alert alert-danger">Campaign not found</div>';
                return;
            }

            // Get tracking stats
            $trackingStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened, SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked FROM email_tracking WHERE campaign_id = ?");
            $trackingStmt->execute([$id]);
            $stats = $trackingStmt->fetch(\PDO::FETCH_ASSOC);

            // Get survey responses if any
            $surveyStmt = $db->prepare("SELECT q.question_text, COUNT(r.id) as response_count FROM survey_questions q LEFT JOIN survey_responses r ON q.id = r.question_id WHERE q.campaign_id = ? GROUP BY q.id, q.question_text");
            $surveyStmt->execute([$id]);
            $surveyStats = $surveyStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Load the stats view content directly without layout
            ob_start();
            include __DIR__ . '/../../views/admin/newsletters/stats_content.php';
            $content = ob_get_clean();
            
            echo $content;
        } catch (\Exception $e) {
            error_log("View stats AJAX error: " . $e->getMessage());
            echo '<div class="alert alert-danger">Error loading statistics</div>';
        }
    }

    public function toggleSchedule($id)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            
            $status = $_POST['status'] ?? '0';
            
            $stmt = $db->prepare("UPDATE follow_up_schedules SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
        } catch (\Exception $e) {
            error_log("Toggle schedule error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating schedule']);
        }
    }

    public function getSchedule($id)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM follow_up_schedules WHERE id = ?");
            $stmt->execute([$id]);
            $schedule = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($schedule) {
                echo json_encode(['success' => true, 'schedule' => $schedule]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Schedule not found']);
            }
        } catch (\Exception $e) {
            error_log("Get schedule error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading schedule']);
        }
    }

    public function updateSchedule($id)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            
            $name = $_POST['name'] ?? '';
            $days = $_POST['days_after_registration'] ?? 0;
            $subject = $_POST['subject'] ?? '';
            $content = $_POST['content'] ?? '';
            
            $stmt = $db->prepare("UPDATE follow_up_schedules SET name = ?, days_after_registration = ?, subject = ?, content = ? WHERE id = ?");
            $stmt->execute([$name, $days, $subject, $content, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
        } catch (\Exception $e) {
            error_log("Update schedule error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating schedule']);
        }
    }

    public function exportSchedules()
    {
        try {
            $db = Connection::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM follow_up_schedules ORDER BY created_at DESC");
            $stmt->execute();
            $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Generate CSV
            $csv = "Name,Days After Registration,Subject,Status,Created\n";
            foreach ($schedules as $schedule) {
                $status = $schedule['is_active'] ? 'Active' : 'Inactive';
                $csv .= '"' . $schedule['name'] . '",' . $schedule['days_after_registration'] . ',"' . $schedule['subject'] . '",' . $status . ',' . $schedule['created_at'] . "\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="follow-up-schedules.csv"');
            echo $csv;
            exit;
        } catch (\Exception $e) {
            error_log("Export schedules error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error exporting schedules']);
        }
    }

    private function sendEmail($toEmail, $toName, $subject, $content, $campaignId = null, $userId = null)
    {
        try {
            $settings = (new Setting())->getAllAsAssoc();
            if (empty($settings['smtp_host'])) {
                error_log('SMTP not configured');
                return false;
            }

            $siteUrl = BASE_URL;
            // Ensure we have a full URL for email clients
            if (empty($siteUrl)) {
                $siteUrl = 'https://' . $_SERVER['HTTP_HOST'];
            }
            $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : ($siteUrl . '/public/assets/images/logo.png');
            $siteName = $settings['site_name'] ?? 'RentSmart';
            
            // Log logo URL for debugging
            error_log("Newsletter logo URL: " . $logoUrl);
            error_log("Newsletter site name: " . $siteName);
            
            // Create professional email template
            $emailTemplate = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>' . htmlspecialchars($subject) . '</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        margin: 0;
                        padding: 0;
                        background-color: #f4f4f4;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    .email-header {
                        background-color: #2c3e50;
                        padding: 30px 20px;
                        text-align: center;
                    }
                    .email-header img {
                        max-width: 200px;
                        max-height: 80px;
                        margin-bottom: 10px;
                    }
                    .email-header h1 {
                        color: white;
                        margin: 0;
                        font-size: 24px;
                        font-weight: 300;
                    }
                    .email-body {
                        padding: 40px 30px;
                        background-color: #ffffff;
                    }
                    .email-body h1, .email-body h2, .email-body h3 {
                        color: #2c3e50;
                        margin-top: 0;
                    }
                    .email-body a {
                        color: #3498db;
                        text-decoration: none;
                    }
                    .email-body a:hover {
                        text-decoration: underline;
                    }
                    .email-footer {
                        background-color: #ecf0f1;
                        padding: 20px;
                        text-align: center;
                        font-size: 12px;
                        color: #7f8c8d;
                    }
                    .email-footer a {
                        color: #7f8c8d;
                        text-decoration: none;
                    }
                    @media only screen and (max-width: 600px) {
                        .email-container {
                            width: 100%;
                            border-radius: 0;
                        }
                        .email-body {
                            padding: 20px 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="email-header">
                        <img src="' . $logoUrl . '" alt="' . htmlspecialchars($siteName) . ' Logo" onerror="this.style.display=\'none\'">
                        <h1>' . htmlspecialchars($siteName) . '</h1>
                    </div>
                    <div class="email-body">
                        {CONTENT}
                    </div>
                    <div class="email-footer">
                        <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '. All rights reserved.</p>
                        <p>Powered by <a href="https://timestentechnologies.co.ke" target="_blank">Timesten Technologies</a></p>
                    </div>
                </div>';

            // Add tracking pixel
            $trackingPixel = '';
            if ($campaignId && $userId) {
                $trackingPixel = '<img src="' . BASE_URL . '/newsletter/track/' . $campaignId . '/' . $userId . '" width="1" height="1" style="display:none;">';
            }
            
            $emailTemplate .= $trackingPixel . '</body></html>';

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

            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;

            // Replace dynamic variables in content
            $displayName = !empty($toName) && $toName !== $toEmail ? $toName : $toEmail;
            $processedContent = str_replace(['{name}', '{email}'], [$displayName, $toEmail], $content);
            
            // Create final email template with processed content
            $finalEmailTemplate = str_replace('{CONTENT}', $processedContent, $emailTemplate);
            
            $mail->Body = $finalEmailTemplate;
            $mail->AltBody = strip_tags($processedContent);

            $sent = $mail->send();

            if ($sent && $campaignId && $userId) {
                // Log email sent
                $db = Connection::getInstance()->getConnection();
                $logStmt = $db->prepare("INSERT INTO email_tracking (campaign_id, user_id, email, sent_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE sent_at = NOW()");
                $logStmt->execute([$campaignId, $userId, $toEmail]);
            }

            return $sent;
        } catch (\Exception $e) {
            error_log('Send email error: ' . $e->getMessage());
            return false;
        }
    }

    private function handleAttachments($campaignId)
    {
        if (empty($_FILES['attachments']['name'][0])) {
            return;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/newsletters/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $db = Connection::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO campaign_attachments (campaign_id, filename, original_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9.]/', '_', $name);
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $filepath)) {
                    $stmt->execute([
                        $campaignId,
                        $filename,
                        $name,
                        'public/uploads/newsletters/' . $filename,
                        $_FILES['attachments']['size'][$key],
                        $_FILES['attachments']['type'][$key]
                    ]);
                }
            }
        }
    }

    private function handleSurveyQuestions($campaignId)
    {
        $questions = $_POST['survey_questions'];
        $types = $_POST['survey_types'];
        $options = $_POST['survey_options'];
        $required = $_POST['survey_required'] ?? [];

        $db = Connection::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO survey_questions (campaign_id, question_text, question_type, options, required, order_index) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($questions as $index => $question) {
            if (!empty(trim($question))) {
                $optionsJson = null;
                if (isset($options[$index]) && !empty($options[$index])) {
                    $optionsArray = array_map('trim', explode("\n", $options[$index]));
                    $optionsJson = json_encode($optionsArray);
                }

                $stmt->execute([
                    $campaignId,
                    $question,
                    $types[$index] ?? 'text',
                    $optionsJson,
                    in_array($index, $required) ? 1 : 0,
                    $index
                ]);
            }
        }
    }

    public function trackEmail($campaignId, $userId)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE email_tracking SET opened_at = NOW() WHERE campaign_id = ? AND user_id = ? AND opened_at IS NULL");
            $stmt->execute([$campaignId, $userId]);

            // Return 1x1 transparent pixel
            header('Content-Type: image/png');
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        } catch (\Exception $e) {
            error_log('Track email error: ' . $e->getMessage());
        }
    }

    public function followUpSchedules()
    {
        $db = Connection::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM follow_up_schedules ORDER BY days_after_registration");
        $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo \view('admin/newsletters/follow_up_schedules', [
            'title' => 'Follow-up Schedules',
            'schedules' => $schedules
        ]);
    }

    public function createFollowUpSchedule()
    {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $daysAfter = filter_input(INPUT_POST, 'days_after', FILTER_VALIDATE_INT);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $content = $_POST['content'] ?? '';

        if (!$name || !$daysAfter || !$subject || !$content) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
            exit;
        }

        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO follow_up_schedules (name, days_after_registration, subject, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $daysAfter, $subject, $content]);

            echo json_encode(['success' => true, 'message' => 'Follow-up schedule created successfully']);
        } catch (\Exception $e) {
            error_log("Create follow-up schedule error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error creating schedule']);
        }
    }

    public function processFollowUps()
    {
        // This method should be called by a cron job daily
        try {
            $db = Connection::getInstance()->getConnection();
            
            // Get active schedules
            $scheduleStmt = $db->query("SELECT * FROM follow_up_schedules WHERE is_active = 1");
            $schedules = $scheduleStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($schedules as $schedule) {
                // Find users who registered X days ago and haven't received this follow-up
                $targetDate = date('Y-m-d', strtotime("-{$schedule['days_after_registration']} days"));
                
                $usersStmt = $db->prepare("
                    SELECT u.id, u.name, u.email 
                    FROM users u 
                    WHERE DATE(u.created_at) = ? 
                    AND u.id NOT IN (
                        SELECT user_id FROM follow_up_sent_log WHERE schedule_id = ?
                    )
                ");
                $usersStmt->execute([$targetDate, $schedule['id']]);
                $users = $usersStmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($users as $user) {
                    if ($this->sendEmail($user['email'], $user['name'], $schedule['subject'], $schedule['content'])) {
                        // Log that this follow-up was sent
                        $logStmt = $db->prepare("INSERT INTO follow_up_sent_log (schedule_id, user_id, sent_at) VALUES (?, ?, NOW())");
                        $logStmt->execute([$schedule['id'], $user['id']]);
                    }
                }
            }

            echo "Follow-ups processed successfully\n";
        } catch (\Exception $e) {
            error_log("Process follow-ups error: " . $e->getMessage());
            echo "Error processing follow-ups: " . $e->getMessage() . "\n";
        }
    }
}
