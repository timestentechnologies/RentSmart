<?php
if (!defined('BASE_URL')) { define('BASE_URL', '/'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container" style="max-width:520px;margin-top:80px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Reset your password</h1>
                <?php if (!empty($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?>">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/reset-password">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
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
