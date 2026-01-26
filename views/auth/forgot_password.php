<?php
if (!defined('BASE_URL')) { define('BASE_URL', '/'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container" style="max-width:520px;margin-top:80px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Forgot your password?</h1>
                <p class="text-muted">Enter your email address and we'll send you a password reset link.</p>
                <?php if (!empty($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?>">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/forgot-password">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Email Reset Link</button>
                </form>
                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/">Back to login</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
