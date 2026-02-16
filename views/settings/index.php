<?php
ob_start();

$settings = $settings ?? [];

// Debug settings at the start of the view
error_log("Settings array in view:");
error_log(print_r($settings, true));

function setting($key, $default = '') {
    global $settings;
    $value = isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
    error_log("Getting setting for key '{$key}': " . ($value ?: '(empty)'));
    return htmlspecialchars($value);
}

// Debug all available settings at the start
error_log("All available settings in view:");
error_log(print_r($settings, true));

// Helper function to get image URL
function getImageUrl($path) {
    if (empty($path)) return '';
    return BASE_URL . '/public/assets/images/' . $path;
}

// If settings array is empty, try to load from database directly
if (empty($settings)) {
    try {
        $db = App\Database\Connection::getInstance()->getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = $dbSettings;
        error_log("Loaded settings directly from database:");
        error_log(print_r($settings, true));
    } catch (Exception $e) {
        error_log("Error loading settings from database: " . $e->getMessage());
    }
}
?>

<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-gear text-primary me-2"></i>System Settings
                    </h1>
                    <p class="text-muted mb-0 mt-1">Configure your application settings and preferences</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/settings/backup">
                        <i class="bi bi-download me-1"></i>Backup
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/settings/restore">
                        <i class="bi bi-upload me-1"></i>Restore
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Debug Information -->
    <!-- <div class="alert alert-info mb-4">
        <h6 class="alert-heading">Debug Information</h6>
        <pre><?= print_r($settings, true) ?></pre>
    </div> -->

    <div class="row">
        <!-- Quick Links -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-grid me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?= BASE_URL ?>/settings/email" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-envelope me-2"></i>
                                Email & SMS Settings
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/settings/payments" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-credit-card me-2"></i>
                                Payment Integrations
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/settings/public-pages" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-layout-text-window-reverse me-2"></i>
                                Public Pages Content
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/settings/ai" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-robot me-2"></i>
                                AI Configuration
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#backupModal">
                            <div>
                                <i class="bi bi-cloud-download me-2"></i>
                                Backup & Restore
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#systemInfoModal">
                            <div>
                                <i class="bi bi-info-circle me-2"></i>
                                System Information
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site Settings -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Site Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/settings/update" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Basic Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="site_name" class="form-label">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?= $settings['site_name'] ?? '' ?>" required>
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['site_name']) ? $settings['site_name'] : 'Not set' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="site_email" class="form-label">Site Email</label>
                                    <input type="email" class="form-control" id="site_email" name="site_email" value="<?= $settings['site_email'] ?? '' ?>">
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['site_email']) ? $settings['site_email'] : 'Not set' ?></div>
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label for="site_phone" class="form-label">Site Phone</label>
                                    <input type="tel" class="form-control" id="site_phone" name="site_phone" value="<?= $settings['site_phone'] ?? '' ?>">
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['site_phone']) ? $settings['site_phone'] : 'Not set' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                        <option value="KES" <?= ($settings['currency'] ?? '') === 'KES' ? 'selected' : '' ?>>KES - Kenyan Shilling</option>
                                        <option value="EUR" <?= ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                        <option value="GBP" <?= ($settings['currency'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                                    </select>
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['currency']) ? $settings['currency'] : 'Not set' ?></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="site_address" class="form-label">Site Address</label>
                                <textarea class="form-control" id="site_address" name="site_address" rows="2"><?= $settings['site_address'] ?? '' ?></textarea>
                                <div class="form-text text-primary">Current value: <?= !empty($settings['site_address']) ? $settings['site_address'] : 'Not set' ?></div>
                            </div>
                        </div>

                        <!-- Branding -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Branding</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="site_logo" class="form-label">Site Logo</label>
                                    <input type="file" class="form-control" id="site_logo" name="site_logo" accept="image/*" data-preview="logoPreview">
                                    <div class="form-text">Recommended size: 200x50 pixels</div>
                                    <?php if (!empty($settings['site_logo'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= getImageUrl($settings['site_logo']) ?>" alt="Current Logo" class="img-thumbnail" style="max-height: 50px;">
                                            <div class="form-text">Current logo</div>
                                        </div>
                                    <?php endif; ?>
                                    <div id="logoPreview" class="mt-2"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="site_favicon" class="form-label">Favicon</label>
                                    <input type="file" class="form-control" id="site_favicon" name="site_favicon" accept=".ico,.png" data-preview="faviconPreview">
                                    <div class="form-text">Recommended size: 32x32 pixels</div>
                                    <?php if (!empty($settings['site_favicon'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= getImageUrl($settings['site_favicon']) ?>" alt="Current Favicon" class="img-thumbnail" style="max-height: 32px;">
                                            <div class="form-text">Current favicon</div>
                                        </div>
                                    <?php endif; ?>
                                    <div id="faviconPreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Settings -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">SEO Settings</h6>
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="2"><?= $settings['site_description'] ?? '' ?></textarea>
                                <div class="form-text text-primary">Current value: <?= !empty($settings['site_description']) ? $settings['site_description'] : 'Not set' ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="site_keywords" class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" id="site_keywords" name="site_keywords" value="<?= $settings['site_keywords'] ?? '' ?>">
                                <div class="form-text text-primary">Current value: <?= !empty($settings['site_keywords']) ? $settings['site_keywords'] : 'Not set' ?></div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Additional Settings</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        foreach ($timezones as $tz) {
                                            $selected = ($settings['timezone'] ?? '') === $tz ? 'selected' : '';
                                            echo "<option value=\"{$tz}\" {$selected}>{$tz}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['timezone']) ? $settings['timezone'] : 'Not set' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="Y-m-d" <?= ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                        <option value="d/m/Y" <?= ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?= ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                        <option value="d-m-Y" <?= ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY</option>
                                    </select>
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['date_format']) ? $settings['date_format'] : 'Not set' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Odoo CRM Configuration -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Odoo CRM Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="odoo_url" class="form-label">Odoo URL</label>
                                    <input type="url" class="form-control" id="odoo_url" name="odoo_url" value="<?= $settings['odoo_url'] ?? '' ?>" placeholder="https://yourcompany.odoo.com">
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['odoo_url']) ? $settings['odoo_url'] : 'Not set' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="odoo_database" class="form-label">Database</label>
                                    <input type="text" class="form-control" id="odoo_database" name="odoo_database" value="<?= $settings['odoo_database'] ?? '' ?>" placeholder="yourcompany">
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['odoo_database']) ? $settings['odoo_database'] : 'Not set' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="odoo_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="odoo_username" name="odoo_username" value="<?= $settings['odoo_username'] ?? '' ?>" placeholder="admin@example.com">
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['odoo_username']) ? $settings['odoo_username'] : 'Not set' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="odoo_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="odoo_password" name="odoo_password" value="<?= $settings['odoo_password'] ?? '' ?>" placeholder="Your Odoo password">
                                    <div class="form-text text-primary">Current value: <?= !empty($settings['odoo_password']) ? '********' : 'Not set' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">System Settings</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="maintenance_mode">Enable Maintenance Mode</label>
                                <div class="form-text text-primary">Current value: <?= ($settings['maintenance_mode'] ?? '') === '1' ? 'Enabled' : 'Disabled' ?></div>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Info Modal -->
<div class="modal fade" id="systemInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-striped mb-0">
                    <tr>
                        <th>PHP Version</th>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <th>Server Software</th>
                        <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                    </tr>
                    <tr>
                        <th>Database Version</th>
                        <td><?php
                            try {
                                $db = App\Database\Connection::getInstance()->getConnection();
                                echo $db->getAttribute(PDO::ATTR_SERVER_VERSION);
                            } catch (Exception $e) {
                                echo 'Not available';
                            }
                        ?></td>
                    </tr>
                    <tr>
                        <th>Operating System</th>
                        <td><?= php_uname('s') . ' ' . php_uname('r') ?></td>
                    </tr>
                    <tr>
                        <th>Current Timezone</th>
                        <td><?= setting('timezone', date_default_timezone_get()) ?></td>
                    </tr>
                    <tr>
                        <th>Current Time</th>
                        <td><?= date('Y-m-d H:i:s') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Backup Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backup & Restore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/settings/backup" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Download Backup
                    </a>
                    <hr>
                    <form method="POST" action="<?= BASE_URL ?>/settings/restore" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="backup_file" class="form-label">Restore from Backup</label>
                            <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                            <div class="form-text">Select a SQL backup file to restore</div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Are you sure you want to restore? This will overwrite existing data.')">
                            <i class="bi bi-upload me-1"></i> Restore Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Image preview functionality
function handleFileSelect(event) {
    const file = event.target.files[0];
    const previewId = event.target.dataset.preview;
    const previewDiv = document.getElementById(previewId);
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewDiv.innerHTML = `
                <div class="border rounded p-2 bg-light text-center mt-2">
                    <img src="${e.target.result}" class="img-fluid" style="max-height: ${previewId === 'faviconPreview' ? '32px' : '50px'};">
                    <div class="form-text">Preview</div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        previewDiv.innerHTML = '';
    }
}

document.getElementById('site_logo').addEventListener('change', handleFileSelect);
document.getElementById('site_favicon').addEventListener('change', handleFileSelect);

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
})

// Debug: Log current settings to console
console.log('Current settings:', <?= json_encode($settings) ?>);

function testMpesaConnection() {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Testing...';
    button.disabled = true;

    // Make AJAX call to test connection
    fetch('<?= BASE_URL ?>/settings/testMpesa', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('M-Pesa connection successful!');
        } else {
            alert('M-Pesa connection failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error testing M-Pesa connection: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>