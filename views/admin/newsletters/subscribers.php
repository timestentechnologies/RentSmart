<?php ob_start(); ?>

<style>
.bg-purple-faded {
    background-color: #8b5cf6 !important;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
}

.bg-orange-faded {
    background-color: #f97316 !important;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
}

.bg-green-faded {
    background-color: #10b981 !important;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.recent-subscriptions .d-flex {
    transition: background-color 0.2s ease-in-out;
}

.recent-subscriptions .d-flex:hover {
    background-color: rgba(255,255,255,0.2) !important;
}

.fs-1 {
    font-size: 2.5rem !important;
}

.card-title {
    line-height: 1.2;
}

.opacity-75 {
    opacity: 0.75 !important;
}

.opacity-50 {
    opacity: 0.5 !important;
}

.avatar-sm {
    width: 36px;
    height: 36px;
    font-size: 14px;
    font-weight: 600;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

.badge {
    font-weight: 500;
}

.btn-group .btn {
    border-radius: 0.375rem !important;
}

.card-header {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6 !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Newsletter Subscribers</h3>
                <div>
                    <button type="button" class="btn btn-outline-success me-2" onclick="syncUsers()">
                        <i class="bi bi-arrow-repeat me-2"></i>Sync Users
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubscriberModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Subscriber
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-purple-faded text-white border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="card-title mb-1 fw-bold"><?= $stats['total_active'] ?></h2>
                                    <p class="card-text mb-0 opacity-75">Active Subscribers</p>
                                </div>
                                <div class="text-white opacity-50">
                                    <i class="bi bi-person-check-fill fs-1"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="opacity-75">
                                    <i class="bi bi-graph-up me-1"></i>
                                    <?= count($stats['recent_subscriptions']) ?> new this week
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-orange-faded text-white border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="card-title mb-1 fw-bold"><?= $stats['total_unsubscribed'] ?></h2>
                                    <p class="card-text mb-0 opacity-75">Unsubscribed</p>
                                </div>
                                <div class="text-white opacity-50">
                                    <i class="bi bi-person-x-fill fs-1"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="opacity-75">
                                    <i class="bi bi-graph-down me-1"></i>
                                    Churn rate tracking
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-green-faded text-white border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1 fw-bold">Recent Subscriptions</h5>
                                    <p class="card-text mb-0 opacity-75 small">Latest newsletter signups</p>
                                </div>
                                <div class="text-white opacity-50">
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                            </div>
                            <div class="recent-subscriptions">
                                <?php if (!empty($stats['recent_subscriptions'])): ?>
                                    <?php foreach ($stats['recent_subscriptions'] as $recent): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white bg-opacity-10 rounded">
                                            <div>
                                                <strong class="d-block"><?= htmlspecialchars($recent['name'] ?? $recent['email']) ?></strong>
                                                <small class="opacity-75"><?= date('M j, Y', strtotime($recent['subscribed_at'])) ?></small>
                                            </div>
                                            <i class="bi bi-person-plus-fill opacity-50"></i>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3 opacity-75">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        <small>No recent subscriptions</small>
                                    </div>
                                <?php endif; ?>
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Subscriber List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">
                                        <i class="bi bi-person me-2"></i>Name
                                    </th>
                                    <th class="border-0">
                                        <i class="bi bi-envelope me-2"></i>Email
                                    </th>
                                    <th class="border-0">
                                        <i class="bi bi-flag me-2"></i>Status
                                    </th>
                                    <th class="border-0">
                                        <i class="bi bi-calendar me-2"></i>Subscribed At
                                    </th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscribers as $subscriber): ?>
                                    <tr class="border-bottom">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <?= strtoupper(substr($subscriber['name'] ?? $subscriber['email'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-medium"><?= htmlspecialchars($subscriber['name'] ?? 'N/A') ?></div>
                                                    <small class="text-muted">ID: #<?= $subscriber['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-envelope-fill text-muted me-2"></i>
                                                <?= htmlspecialchars($subscriber['email']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($subscriber['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success px-3 py-2">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning px-3 py-2">
                                                    <i class="bi bi-x-circle-fill me-1"></i>Unsubscribed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-clock text-muted me-2"></i>
                                                <div>
                                                    <div><?= date('M j, Y', strtotime($subscriber['subscribed_at'])) ?></div>
                                                    <small class="text-muted"><?= date('H:i', strtotime($subscriber['subscribed_at'])) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" onclick="editSubscriber(<?= $subscriber['id'] ?>, '<?= htmlspecialchars($subscriber['name'] ?? '') ?>', '<?= htmlspecialchars($subscriber['email']) ?>')" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($subscriber['status'] === 'active'): ?>
                                                    <a href="/admin/newsletters/subscribers/unsubscribe/<?= $subscriber['id'] ?>" class="btn btn-outline-warning" title="Unsubscribe" onclick="return confirm('Are you sure you want to unsubscribe this user?')">
                                                        <i class="bi bi-person-x"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="/admin/newsletters/subscribers/resubscribe/<?= $subscriber['id'] ?>" class="btn btn-outline-success" title="Resubscribe">
                                                        <i class="bi bi-person-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="/admin/newsletters/subscribers/delete/<?= $subscriber['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this subscriber?')">
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

function syncUsers() {
    const btn = event.target;
    const originalContent = btn.innerHTML;
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Syncing...';
    
    fetch('/admin/newsletters/subscribers', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.row'));
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Show error message
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                <i class="bi bi-exclamation-circle me-2"></i>
                ${data.message || 'Error syncing users'}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.row'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error message
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <i class="bi bi-exclamation-circle me-2"></i>
            Error syncing users. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.row'));
    })
    .finally(() => {
        // Restore button state
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/main.php';
?>
