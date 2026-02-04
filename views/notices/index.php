<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0"><i class="bi bi-megaphone text-primary me-2"></i>Notices</h1>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8 order-2 order-lg-1">
            <?php if (empty($notices)): ?>
                <div class="card">
                    <div class="card-body text-center text-muted py-5">No notices yet</div>
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
                                <?php if(!empty($n['tenant_name'])): ?> • Tenant: <?= htmlspecialchars($n['tenant_name']) ?><?php endif; ?>
                            </div>
                        </div>
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <div class="card-footer text-end">
                            <a href="<?= BASE_URL ?>/notices/delete/<?= (int)$n['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="col-12 col-lg-4 order-1 order-lg-2">
            <?php if (!empty($canPost) && $canPost): ?>
            <div class="card">
                <div class="card-header"><strong>Post a Notice</strong></div>
                <div class="card-body">
                    <form method="post" action="<?= BASE_URL ?>/notices/store">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Message</label>
                            <textarea name="body" rows="4" class="form-control" required></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Target (optional)</label>
                            <select name="property_id" class="form-select">
                                <option value="">All Properties</option>
                                <?php foreach ($properties as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tenant (optional)</label>
                            <select name="tenant_id" class="form-select">
                                <option value="">All Tenants</option>
                                <?php foreach ($tenants as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="pinned" name="pinned">
                            <label class="form-check-label" for="pinned">
                                Pin to top
                            </label>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Post Notice</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
