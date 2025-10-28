<?php
ob_start();

$settings = $settings ?? [];

// Debug settings at the start of the view
error_log("Settings array at start of email view:");
error_log(print_r($settings, true));

function setting($key, $default = '') {
    global $settings;
    $value = isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
    error_log("Getting setting for key '{$key}': " . ($value ?: '(empty)'));
    return htmlspecialchars($value);
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Email & SMS Settings</h4>
        <p class="text-muted">Configure your email and SMS notification settings.</p>
    </div>
    <a href="<?= BASE_URL ?>/settings" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Back to Settings
    </a>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
        <?= $_SESSION['flash_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

<!-- Debug Information -->
<!-- <div class="alert alert-info mb-4">
    <h6 class="alert-heading">Debug Information</h6>
    <pre><?= print_r($settings, true) ?></pre>
</div> -->

<div class="row">
    <!-- SMTP Settings -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-envelope me-2"></i>
                    Email (SMTP) Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/settings/updateMail" id="smtpForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_host" 
                               name="smtp_host" 
                               value="<?= $settings['smtp_host'] ?? '' ?>" 
                               placeholder="e.g., smtp.gmail.com">
                        <div class="form-text text-primary">
                            Current value: <?= !empty($settings['smtp_host']) ? $settings['smtp_host'] : 'Not set' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_port" 
                               name="smtp_port" 
                               value="<?= $settings['smtp_port'] ?? '' ?>" 
                               placeholder="e.g., 587">
                        <div class="form-text text-primary">
                            Current value: <?= !empty($settings['smtp_port']) ? $settings['smtp_port'] : 'Not set' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_user" class="form-label">SMTP Username</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_user" 
                               name="smtp_user" 
                               value="<?= $settings['smtp_user'] ?? '' ?>" 
                               placeholder="Your email address">
                        <div class="form-text text-primary">
                            Current value: <?= !empty($settings['smtp_user']) ? $settings['smtp_user'] : 'Not set' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_pass" class="form-label">SMTP Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="smtp_pass" 
                                   name="smtp_pass" 
                                   value="<?= $settings['smtp_pass'] ?? '' ?>" 
                                   placeholder="Your email password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('smtp_pass')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text text-primary">
                            Status: <?= !empty($settings['smtp_pass']) ? 'Password is set' : 'Not set' ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save SMTP Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary ms-2" onclick="testEmailSettings()">
                        <i class="bi bi-envelope-check me-1"></i> Test Connection
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SMS Settings -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-chat-dots me-2"></i>
                    SMS Gateway Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/settings/updateMail" id="smsForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="sms_provider" class="form-label">SMS Provider</label>
                        <select class="form-select" id="sms_provider" name="sms_provider">
                            <option value="">Select a provider</option>
                            <option value="twilio" <?= ($settings['sms_provider'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                            <option value="nexmo" <?= ($settings['sms_provider'] ?? '') === 'nexmo' ? 'selected' : '' ?>>Nexmo/Vonage</option>
                            <option value="africas_talking" <?= ($settings['sms_provider'] ?? '') === 'africas_talking' ? 'selected' : '' ?>>Africa's Talking</option>
                        </select>
                        <div class="form-text text-primary">
                            Current value: <?= !empty($settings['sms_provider']) ? $settings['sms_provider'] : 'Not set' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sms_api_key" class="form-label">API Key</label>
                        <input type="text" 
                               class="form-control" 
                               id="sms_api_key" 
                               name="sms_api_key" 
                               value="<?= $settings['sms_api_key'] ?? '' ?>" 
                               placeholder="Your SMS API key">
                        <div class="form-text text-primary">
                            Current value: <?= !empty($settings['sms_api_key']) ? $settings['sms_api_key'] : 'Not set' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sms_api_secret" class="form-label">API Secret</label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="sms_api_secret" 
                                   name="sms_api_secret" 
                                   value="<?= $settings['sms_api_secret'] ?? '' ?>" 
                                   placeholder="Your SMS API secret">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('sms_api_secret')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text text-primary">
                            Status: <?= !empty($settings['sms_api_secret']) ? 'Secret is set' : 'Not set' ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save SMS Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary ms-2" onclick="testSmsSettings()">
                        <i class="bi bi-phone me-1"></i> Test Connection
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

function testEmailSettings() {
    const data = {
        smtp_host: document.getElementById('smtp_host').value,
        smtp_port: document.getElementById('smtp_port').value,
        smtp_user: document.getElementById('smtp_user').value,
        smtp_pass: document.getElementById('smtp_pass').value
    };

    fetch('<?= BASE_URL ?>/settings/testEmail', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email test successful! Check your inbox.');
        } else {
            alert('Email test failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error testing email settings: ' + error.message);
    });
}

function testSmsSettings() {
    const data = {
        sms_provider: document.getElementById('sms_provider').value,
        sms_api_key: document.getElementById('sms_api_key').value,
        sms_api_secret: document.getElementById('sms_api_secret').value
    };

    // Add SMS test implementation here
    alert('SMS test functionality coming soon!');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>