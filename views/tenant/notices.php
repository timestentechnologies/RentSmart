<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0"><i class="bi bi-megaphone text-primary me-2"></i>Notices</h1>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show mt-2">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if (empty($notices)): ?>
        <div class="card">
            <div class="card-body text-center text-muted py-5">No notices available.</div>
        </div>
    <?php else: ?>
        <?php foreach ($notices as $n): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($n['title']) ?></strong>
                        <?php if (!empty($n['pinned'])): ?>
                            <span class="badge bg-warning text-dark ms-2">Pinned</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at'] ?? 'now'))) ?></small>
                </div>
                <div class="card-body">
                    <div class="mb-2"><?= nl2br(htmlspecialchars($n['body'])) ?></div>
                    <div class="small text-muted">
                        <?php if(!empty($n['property_name'])): ?>Property: <?= htmlspecialchars($n['property_name']) ?><?php endif; ?>
                        <?php if(!empty($n['unit_number'])): ?> • Unit: <?= htmlspecialchars($n['unit_number']) ?><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
