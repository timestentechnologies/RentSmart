<?php ob_start(); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Newsletter Subscribers</h3>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubscriberModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Subscriber
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= $stats['total_active'] ?></h4>
                                    <p class="card-text">Active Subscribers</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-person-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= $stats['total_unsubscribed'] ?></h4>
                                    <p class="card-text">Unsubscribed</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-person-x fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Recent Subscriptions</h5>
                            <div class="small">
                                <?php foreach ($stats['recent_subscriptions'] as $recent): ?>
                                    <div class="mb-1">
                                        <strong><?= htmlspecialchars($recent['name'] ?? $recent['email']) ?></strong>
                                        <small class="d-block"><?= date('M j, Y', strtotime($recent['subscribed_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="unsubscribed" <?= $status === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                            <a href="/admin/newsletters/subscribers" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscribers Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Subscribed At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscribers as $subscriber): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($subscriber['name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($subscriber['email']) ?></td>
                                        <td>
                                            <?php if ($subscriber['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Unsubscribed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y H:i', strtotime($subscriber['subscribed_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="editSubscriber(<?= $subscriber['id'] ?>, '<?= htmlspecialchars($subscriber['name'] ?? '') ?>', '<?= htmlspecialchars($subscriber['email']) ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($subscriber['status'] === 'active'): ?>
                                                    <a href="/admin/newsletters/subscribers/unsubscribe/<?= $subscriber['id'] ?>" class="btn btn-outline-warning" onclick="return confirm('Are you sure you want to unsubscribe this user?')">
                                                        <i class="bi bi-person-x"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="/admin/newsletters/subscribers/resubscribe/<?= $subscriber['id'] ?>" class="btn btn-outline-success">
                                                        <i class="bi bi-person-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="/admin/newsletters/subscribers/delete/<?= $subscriber['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this subscriber?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Subscriber Modal -->
<div class="modal fade" id="addSubscriberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subscriber</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/admin/newsletters/subscribers/add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter name (optional)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subscriber</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subscriber Modal -->
<div class="modal fade" id="editSubscriberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subscriber</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editSubscriberForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editSubscriberId">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="editSubscriberName" class="form-control" placeholder="Enter name (optional)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="editSubscriberEmail" class="form-control" placeholder="Enter email address" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subscriber</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSubscriber(id, name, email) {
    document.getElementById('editSubscriberId').value = id;
    document.getElementById('editSubscriberName').value = name;
    document.getElementById('editSubscriberEmail').value = email;
    document.getElementById('editSubscriberForm').action = '/admin/newsletters/subscribers/update/' + id;
    new bootstrap.Modal(document.getElementById('editSubscriberModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/main.php';
?>
