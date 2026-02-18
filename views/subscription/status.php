<?php require_once __DIR__ . '/../layouts/main.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Subscription Status</h2>

                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
                            <?= $_SESSION['flash_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                    <?php endif; ?>

                    <div class="subscription-details">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Current Plan</h5>
                                <h3 class="text-primary"><?= $subscription['plan_type'] ?></h3>
                                <p class="text-muted"><?= $subscription['description'] ?></p>
                                <?php 
                                    $pl = isset($subscription['property_limit']) ? (int)$subscription['property_limit'] : null; 
                                    $pl_text = ($pl && $pl > 0) ? ('Up to ' . number_format($pl) . ' properties') : 'Unlimited properties';
                                ?>
                                <span class="badge bg-secondary"><?= $pl_text ?></span>
                                <?php 
                                    $ll = isset($subscription['listing_limit']) ? (int)$subscription['listing_limit'] : null; 
                                    $ll_text = ($ll && $ll > 0) ? ('Up to ' . number_format($ll) . ' listings') : 'Unlimited listings';
                                ?>
                                <span class="badge bg-secondary"><?= $ll_text ?></span>
                            </div>
                            <div class="col-md-6">
                                <h5>Status</h5>
                                <?php if ($subscription['status'] === 'trialing'): ?>
                                    <div class="badge bg-info text-white mb-2">Trial Period</div>
                                    <p>Your trial ends on <strong><?= date('F j, Y', strtotime($subscription['trial_ends_at'])) ?></strong></p>
                                <?php elseif ($subscription['status'] === 'active'): ?>
                                    <div class="badge bg-success text-white mb-2">Active</div>
                                    <p>Next billing date: <strong><?= date('F j, Y', strtotime($subscription['current_period_ends_at'])) ?></strong></p>
                                <?php else: ?>
                                    <div class="badge bg-warning text-dark mb-2">Expired</div>
                                    <p>Expired on <strong><?= date('F j, Y', strtotime($subscription['current_period_ends_at'])) ?></strong></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="features-list mb-4">
                            <h5>Plan Features</h5>
                            <ul class="list-unstyled">
                                <?php foreach (explode("\n", $subscription['features']) as $feature): ?>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <?= $feature ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="billing-details mb-4">
                            <h5>Billing Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">Monthly Fee</p>
                                    <h4>$<?= number_format($subscription['price'], 2) ?></h4>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">Billing Period</p>
                                    <p>
                                        <?= date('M j, Y', strtotime($subscription['current_period_starts_at'])) ?> -
                                        <?= date('M j, Y', strtotime($subscription['current_period_ends_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <?php if ($subscription['status'] === 'expired'): ?>
                                <a href="<?= BASE_URL ?>/subscription/renew" class="btn btn-primary btn-lg">
                                    <i class="bi bi-arrow-repeat me-2"></i>Renew Subscription
                                </a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/subscription/renew" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-up-circle me-2"></i>Upgrade Plan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.subscription-details {
    max-width: 800px;
    margin: 0 auto;
}

.badge {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.features-list li {
    font-size: 1rem;
    color: #6c757d;
}

.billing-details {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
}
</style> 