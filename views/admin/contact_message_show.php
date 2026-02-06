<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-envelope text-primary me-2"></i>Contact Message</h1>
                <p class="text-muted mb-0 mt-1">View and reply to this message</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/contact-messages" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Details</h5>
                    <div class="mb-2"><strong>Date:</strong> <?= htmlspecialchars($message['created_at'] ?? '') ?></div>
                    <div class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($message['name'] ?? '') ?></div>
                    <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($message['email'] ?? '') ?></div>
                    <div class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($message['phone'] ?? '') ?></div>
                    <div class="mb-2"><strong>Subject:</strong> <?= htmlspecialchars($message['subject'] ?? '') ?></div>
                    <div class="mb-2"><strong>Message:</strong><br>
                        <div class="border rounded p-2 bg-light" style="white-space:pre-wrap;">
                            <?= htmlspecialchars($message['message'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="mb-3">Reply</h5>
                    <form method="POST" action="<?= BASE_URL ?>/admin/contact-messages/reply/<?= (int)($message['id'] ?? 0) ?>">
                        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                        <div class="mb-3">
                            <label class="form-label">Reply Message</label>
                            <textarea name="reply_message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Send Reply
                        </button>
                    </form>
                    <?php if (empty($message['email'])): ?>
                        <div class="alert alert-warning mt-3 mb-0">No email provided by the sender; reply will be saved but cannot be emailed.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Replies</h5>
                    <?php if (empty($replies)): ?>
                        <div class="text-muted">No replies yet.</div>
                    <?php else: ?>
                        <?php foreach ($replies as $r): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= htmlspecialchars($r['user_name'] ?? 'Admin') ?></strong>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($r['created_at'] ?? '') ?></small>
                                </div>
                                <div class="mt-2" style="white-space:pre-wrap;"><?= htmlspecialchars($r['reply_message'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
