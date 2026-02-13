<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>User Management
                    </h1>
                    <p class="text-muted mb-0 mt-1">Manage system users and their roles</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  
                    <div class="vr d-none d-md-block"></div>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-circle me-1"></i>Add User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mt-3 mb-2"><?= count($users) ?></h2>
                        <p class="mb-0 text-muted">All registered users</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-people fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Active Subscriptions</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($users, function($user) {
                                return $user['is_subscribed'] == 1;
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Users with active plans</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Recent Logins</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($users, function($user) {
                                return !empty($user['last_login_at']) && 
                                       strtotime($user['last_login_at']) > strtotime('-24 hours');
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">In last 24 hours</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-clock-history fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">NAME</th>
                        <th class="text-muted">EMAIL</th>
                        <th class="text-muted">PHONE</th>
                        <th class="text-muted">ROLE</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">LAST LOGIN</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <i class="bi bi-person-circle fs-4"></i>
                                    </div>
                                    <?= htmlspecialchars($user['name']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                             <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'success' : 'info') ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_subscribed']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['last_login_at'] ? date('M j, Y H:i', strtotime($user['last_login_at'])) : 'Never' ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?= $user['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/users/store" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="agent">Agent</option>
                            <option value="manager">Manager</option>
                            <option value="landlord">Landlord</option>
                            <option value="caretaker">Caretaker</option>
                            <option value="realtor">Realtor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" action="<?= BASE_URL ?>/admin/users/update" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="editUserName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editUserEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="editUserRole" required>
                            <option value="admin">Admin</option>
                            <option value="agent">Agent</option>
                            <option value="manager">Manager</option>
                            <option value="landlord">Landlord</option>
                            <option value="caretaker">Caretaker</option>
                            <option value="realtor">Realtor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Success Modal -->
<div class="modal fade" id="deleteUserSuccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Deleted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>User was successfully deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="closeDeleteUserSuccessBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
let userIdToDelete = null;
function editUser(userId) {
    // Fetch user details and populate modal
    fetch(`<?= BASE_URL ?>/admin/users/get/${userId}`)
        .then(response => response.json())
        .then(user => {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUserName').value = user.name;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserRole').value = user.role;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        });
}

function deleteUser(userId) {
    userIdToDelete = userId;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

document.getElementById('confirmDeleteUserBtn').onclick = function() {
    // Close the confirm modal first
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
    if (confirmModal) {
        confirmModal.hide();
    }
    
    // Show loading state
    Swal.fire({
        title: 'Deleting User...',
        html: 'Please wait while we delete the user and all related records.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`<?= BASE_URL ?>/admin/users/delete/${userIdToDelete}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'User Deleted!',
                text: data.message || 'User and all related records have been deleted successfully.',
                confirmButtonText: 'OK',
                allowOutsideClick: false
            }).then((result) => {
                // Reload page after user clicks OK
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        } else {
            // Show error message
            Swal.fire({
                icon: 'error',
                title: 'Deletion Failed',
                text: data.message || 'Failed to delete user. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        // Show error message for network/server errors
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while deleting the user. Please try again.',
            confirmButtonText: 'OK'
        });
        console.error('Delete user error:', error);
    });
};
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?> 