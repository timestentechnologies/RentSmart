<?php
// views/admin/subscription_view.php
?>
<div class="container mt-4">
    <h2><?= htmlspecialchars(
        $title ?? 'Subscription Details') ?></h2>
    <?php if (!empty($subscription)): ?>
        <table class="table table-bordered mt-3">
            <?php foreach ($subscription as $key => $value): ?>
                <tr>
                    <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></th>
                    <td><?= htmlspecialchars($value) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No subscription details found.</div>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/admin/subscriptions" class="btn btn-secondary mt-3">Back to Subscriptions</a>
</div> 