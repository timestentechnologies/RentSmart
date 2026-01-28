<?php
ob_start();

$settings = $settings ?? [];

// Use a closure that captures $settings to avoid relying on global scope inside view()
$S = function($key, $default = '') use ($settings) {
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
};
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">AI Configuration</h4>
            <p class="text-muted">Configure AI provider and model for in-app assistance</p>
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

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-robot me-2"></i>AI Settings</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/updateAI" autocomplete="off">
                <?= csrf_field() ?>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="ai_enabled" name="ai_enabled" value="1" <?= $S('ai_enabled') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ai_enabled">Enable AI Assistant</label>
                </div>

                <div class="mb-3">
                    <label for="ai_provider" class="form-label">Provider</label>
                    <select class="form-select" id="ai_provider" name="ai_provider" onchange="toggleProviderFields()">
                        <option value="openai" <?= $S('ai_provider', 'openai') === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                        <option value="google" <?= $S('ai_provider') === 'google' ? 'selected' : '' ?>>Google (Gemini)</option>
                    </select>
                </div>

                <!-- OpenAI fields -->
                <div id="openai_fields">
                    <div class="mb-3">
                        <label for="openai_api_key" class="form-label">OpenAI API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="openai_api_key" name="openai_api_key" value="<?= htmlspecialchars($S('openai_api_key')) ?>" placeholder="sk-...">
                            <button class="btn btn-outline-secondary" type="button" onclick="(function(f){f.type=f.type==='password'?'text':'password'})(document.getElementById('openai_api_key'))"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="form-text text-primary">Status: <?= !empty($settings['openai_api_key']) ? 'API key is set' : 'Not set' ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="openai_model" class="form-label">Model</label>
                        <input type="text" class="form-control" id="openai_model" name="openai_model" value="<?= htmlspecialchars($S('openai_model', 'gpt-4.1-mini')) ?>">
                    </div>
                </div>

                <!-- Google Gemini fields -->
                <div id="google_fields" style="display:none;">
                    <div class="mb-3">
                        <label for="google_api_key" class="form-label">Google API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="google_api_key" name="google_api_key" value="<?= htmlspecialchars($S('google_api_key')) ?>" placeholder="AIza...">
                            <button class="btn btn-outline-secondary" type="button" onclick="(function(f){f.type=f.type==='password'?'text':'password'})(document.getElementById('google_api_key'))"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="form-text text-primary">Status: <?= !empty($settings['google_api_key']) ? 'API key is set' : 'Not set' ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="google_model" class="form-label">Model</label>
                        <select class="form-select" id="google_model" name="google_model">
                            <option value="gemini-3-flash-preview" <?= $S('google_model', 'gemini-3-flash-preview') === 'gemini-3-flash-preview' ? 'selected' : '' ?>>Gemini 3 Flash (Preview)</option>
                            <option value="gemini-1.5-flash" <?= $S('google_model') === 'gemini-1.5-flash' ? 'selected' : '' ?>>Gemini 1.5 Flash</option>
                            <option value="gemini-1.5-pro" <?= $S('google_model') === 'gemini-1.5-pro' ? 'selected' : '' ?>>Gemini 1.5 Pro</option>
                            <option value="gemini-1.0-pro" <?= $S('google_model') === 'gemini-1.0-pro' ? 'selected' : '' ?>>Gemini 1.0 Pro</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="ai_system_prompt" class="form-label">System Prompt</label>
                    <textarea class="form-control" id="ai_system_prompt" name="ai_system_prompt" rows="3" placeholder="Describe the assistant's role and tone."><?= htmlspecialchars($S('ai_system_prompt', 'You are RentSmart Support AI. Help users with property management tasks and app guidance.')) ?></textarea>
                </div>

                <div class="border-top pt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save AI Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleProviderFields() {
    const provider = document.getElementById('ai_provider').value;
    document.getElementById('openai_fields').style.display = provider === 'openai' ? 'block' : 'none';
    document.getElementById('google_fields').style.display = provider === 'google' ? 'block' : 'none';
}
// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleProviderFields);
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
