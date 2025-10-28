<?php
ob_start();

$settings = $settings ?? [];

function setting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Payment Integration Settings</h4>
            <p class="text-muted">Configure payment gateway integrations</p>
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

    <div class="row">
        <!-- M-Pesa Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-phone me-2"></i>
                        M-Pesa Integration
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/settings/updatePayments" id="mpesaForm">
                        <div class="mb-3">
                            <label for="mpesa_consumer_key" class="form-label">Consumer Key</label>
                            <input type="text" class="form-control" id="mpesa_consumer_key" name="mpesa_consumer_key" 
                                   value="<?= setting('mpesa_consumer_key') ?>" placeholder="Your Safaricom Developer Portal Consumer Key">
                            <div class="form-text text-primary">
                                Current value: <?= !empty($settings['mpesa_consumer_key']) ? $settings['mpesa_consumer_key'] : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesa_consumer_secret" class="form-label">Consumer Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mpesa_consumer_secret" name="mpesa_consumer_secret" 
                                       value="<?= setting('mpesa_consumer_secret') ?>" placeholder="Your Safaricom Developer Portal Consumer Secret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mpesa_consumer_secret')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-primary">
                                Status: <?= !empty($settings['mpesa_consumer_secret']) ? 'Secret is set' : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesa_shortcode" class="form-label">Business Shortcode</label>
                            <input type="text" class="form-control" id="mpesa_shortcode" name="mpesa_shortcode" 
                                   value="<?= setting('mpesa_shortcode') ?>" placeholder="Your M-Pesa Business Shortcode/Paybill Number">
                            <div class="form-text text-primary">
                                Current value: <?= !empty($settings['mpesa_shortcode']) ? $settings['mpesa_shortcode'] : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesa_passkey" class="form-label">Passkey</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mpesa_passkey" name="mpesa_passkey" 
                                       value="<?= setting('mpesa_passkey') ?>" placeholder="Your M-Pesa API Passkey">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mpesa_passkey')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-primary">
                                Status: <?= !empty($settings['mpesa_passkey']) ? 'Passkey is set' : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesa_environment" class="form-label">Environment</label>
                            <select class="form-select" id="mpesa_environment" name="mpesa_environment">
                                <option value="sandbox" <?= setting('mpesa_environment') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Testing)</option>
                                <option value="production" <?= setting('mpesa_environment') === 'production' ? 'selected' : '' ?>>Production (Live)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="mpesa_callback_url" class="form-label">Callback URL</label>
                            <input type="url" class="form-control" id="mpesa_callback_url" name="mpesa_callback_url" 
                                   value="<?= setting('mpesa_callback_url') ?>" placeholder="URL where M-Pesa will send payment notifications">
                            <div class="form-text text-primary">
                                Current value: <?= !empty($settings['mpesa_callback_url']) ? $settings['mpesa_callback_url'] : 'Not set' ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save M-Pesa Settings
                        </button>
                        <button type="button" class="btn btn-outline-primary ms-2" onclick="testMpesaConnection()">
                            <i class="bi bi-phone-check me-1"></i> Test Connection
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card Payment Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Card Payment Integration
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/settings/updatePayments" id="stripeForm">
                        <div class="mb-3">
                            <label for="stripe_public_key" class="form-label">Stripe Public Key</label>
                            <input type="text" class="form-control" id="stripe_public_key" name="stripe_public_key" 
                                   value="<?= setting('stripe_public_key') ?>" placeholder="Your Stripe Publishable Key">
                            <div class="form-text text-primary">
                                Current value: <?= !empty($settings['stripe_public_key']) ? $settings['stripe_public_key'] : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="stripe_secret_key" class="form-label">Stripe Secret Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="stripe_secret_key" name="stripe_secret_key" 
                                       value="<?= setting('stripe_secret_key') ?>" placeholder="Your Stripe Secret Key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('stripe_secret_key')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-primary">
                                Status: <?= !empty($settings['stripe_secret_key']) ? 'Secret key is set' : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="stripe_webhook_secret" class="form-label">Webhook Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="stripe_webhook_secret" name="stripe_webhook_secret" 
                                       value="<?= setting('stripe_webhook_secret') ?>" placeholder="Your Stripe Webhook Signing Secret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('stripe_webhook_secret')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-primary">
                                Status: <?= !empty($settings['stripe_webhook_secret']) ? 'Webhook secret is set' : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="stripe_environment" class="form-label">Environment</label>
                            <select class="form-select" id="stripe_environment" name="stripe_environment">
                                <option value="test" <?= setting('stripe_environment') === 'test' ? 'selected' : '' ?>>Test Mode</option>
                                <option value="live" <?= setting('stripe_environment') === 'live' ? 'selected' : '' ?>>Live Mode</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Stripe Settings
                        </button>
                        <button type="button" class="btn btn-outline-primary ms-2" onclick="testStripeConnection()">
                            <i class="bi bi-credit-card-check me-1"></i> Test Connection
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- PayPal Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-paypal me-2"></i>
                        PayPal Integration
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/settings/updatePayments" id="paypalForm">
                        <div class="mb-3">
                            <label for="paypal_client_id" class="form-label">Client ID</label>
                            <input type="text" class="form-control" id="paypal_client_id" name="paypal_client_id" 
                                   value="<?= setting('paypal_client_id') ?>" placeholder="Your PayPal Client ID">
                            <div class="form-text text-primary">
                                Current value: <?= !empty($settings['paypal_client_id']) ? $settings['paypal_client_id'] : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="paypal_secret" class="form-label">Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="paypal_secret" name="paypal_secret" 
                                       value="<?= setting('paypal_secret') ?>" placeholder="Your PayPal Secret Key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('paypal_secret')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-primary">
                                Status: <?= !empty($settings['paypal_secret']) ? 'Secret is set' : 'Not set' ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="paypal_environment" class="form-label">Environment</label>
                            <select class="form-select" id="paypal_environment" name="paypal_environment">
                                <option value="sandbox" <?= setting('paypal_environment') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                                <option value="live" <?= setting('paypal_environment') === 'live' ? 'selected' : '' ?>>Live</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paypal_webhook_id" class="form-label">Webhook ID</label>
                            <input type="text" class="form-control" id="paypal_webhook_id" name="paypal_webhook_id" 
                                   value="<?= setting('paypal_webhook_id') ?>" placeholder="Your PayPal Webhook ID">
                            <div class="form-text text-primary">
                                Current value: <?= !empty($settings['paypal_webhook_id']) ? $settings['paypal_webhook_id'] : 'Not set' ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save PayPal Settings
                        </button>
                        <button type="button" class="btn btn-outline-primary ms-2" onclick="testPayPalConnection()">
                            <i class="bi bi-paypal me-1"></i> Test Connection
                        </button>
                    </form>
                </div>
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

function testMpesaConnection() {
    testConnection('mpesa');
}

function testStripeConnection() {
    testConnection('stripe');
}

function testPayPalConnection() {
    testConnection('paypal');
}

function testConnection(provider) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Get current form values
    let formData = {};
    if (provider === 'mpesa') {
        const consumerKey = document.getElementById('mpesa_consumer_key').value.trim();
        const consumerSecret = document.getElementById('mpesa_consumer_secret').value.trim();
        
        if (!consumerKey || !consumerSecret) {
            showAlert('warning', 'Please enter Consumer Key and Consumer Secret before testing');
            return;
        }
        
        formData = {
            consumer_key: consumerKey,
            consumer_secret: consumerSecret,
            environment: document.getElementById('mpesa_environment').value
        };
    } else if (provider === 'stripe') {
        const publicKey = document.getElementById('stripe_public_key').value.trim();
        const secretKey = document.getElementById('stripe_secret_key').value.trim();
        const webhookSecret = document.getElementById('stripe_webhook_secret').value.trim();
        
        if (!publicKey || !secretKey || !webhookSecret) {
            showAlert('warning', 'Please enter Public Key, Secret Key and Webhook Secret before testing');
            return;
        }
        
        formData = {
            public_key: publicKey,
            secret_key: secretKey,
            webhook_secret: webhookSecret,
            environment: document.getElementById('stripe_environment').value
        };
    } else if (provider === 'paypal') {
        const clientId = document.getElementById('paypal_client_id').value.trim();
        const secret = document.getElementById('paypal_secret').value.trim();
        const webhookId = document.getElementById('paypal_webhook_id').value.trim();
        
        if (!clientId || !secret || !webhookId) {
            showAlert('warning', 'Please enter Client ID, Secret and Webhook ID before testing');
            return;
        }
        
        formData = {
            client_id: clientId,
            secret: secret,
            webhook_id: webhookId,
            environment: document.getElementById('paypal_environment').value
        };
    }
    
    button.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Testing...';
    button.disabled = true;

    fetch(`<?= BASE_URL ?>/settings/test${provider.charAt(0).toUpperCase() + provider.slice(1)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message || 'Connection test successful!');
        } else {
            showAlert('danger', 'Connection test failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        showAlert('danger', 'Error testing connection: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script> 