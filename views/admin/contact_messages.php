<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-inbox text-primary me-2"></i>Contact Messages</h1>
                <p class="text-muted mb-0 mt-1">Messages submitted from the public Contact Us page</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="contactMessagesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($messages ?? []) as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['subject'] ?? '') ?></td>
                                <td style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($m['message'] ?? '') ?>">
                                    <?= htmlspecialchars($m['message'] ?? '') ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/contact-messages/show/<?= (int)$m['id'] ?>">
                                        View & Reply
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    if (window.jQuery && $('#contactMessagesTable').DataTable) {
        $('#contactMessagesTable').DataTable({ order:[[0,'desc']] });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
