<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Follow-up Email Schedules</h1>
        <a href="<?= BASE_URL ?>/admin/newsletters" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Newsletters
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Active Follow-up Schedules</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($schedules)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No follow-up schedules configured yet.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
                                <i class="bi bi-plus-circle me-2"></i>Create Schedule
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Days After Registration</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($schedule['name']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $schedule['days_after_registration'] ?> days</span>
                                            </td>
                                            <td><?= htmlspecialchars($schedule['subject']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $schedule['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $schedule['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($schedule['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" onclick="editSchedule(<?= $schedule['id'] ?>)" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-<?= $schedule['is_active'] ? 'warning' : 'success' ?>" 
                                                            onclick="toggleSchedule(<?= $schedule['id'] ?>, <?= $schedule['is_active'] ? 0 : 1 ?>)" 
                                                            title="<?= $schedule['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="bi bi-<?= $schedule['is_active'] ? 'pause' : 'play' ?>"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info" onclick="previewSchedule(<?= $schedule['id'] ?>)" title="Preview">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Cron Job Setup</h6>
                        <p class="text-muted small">To automate follow-up emails, add this cron job:</p>
                        <code class="d-block p-2 bg-light">
                            curl <?= BASE_URL ?>/newsletter/process-follow-ups
                        </code>
                        <p class="text-muted small mt-2">Run daily at midnight for best results.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>How It Works</h6>
                        <ul class="small text-muted">
                            <li>System checks daily for users who registered X days ago</li>
                            <li>Follow-up emails are sent to eligible users</li>
                            <li>Each schedule runs only once per user</li>
                            <li>Users must be subscribed to receive emails</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Best Practices</h6>
                        <ul class="small text-muted">
                            <li>Space follow-ups 7-14 days apart</li>
                            <li>Keep emails concise and valuable</li>
                            <li>Include helpful tips or resources</li>
                            <li>Monitor open rates and adjust content</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Schedule Modal -->
<div class="modal fade" id="createScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Follow-up Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createScheduleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="scheduleName" class="form-label">Schedule Name *</label>
                        <input type="text" class="form-control" id="scheduleName" name="name" required>
                        <small class="text-muted">e.g., "2 Week Welcome Follow-up"</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="daysAfter" class="form-label">Days After Registration *</label>
                        <input type="number" class="form-control" id="daysAfter" name="days_after" min="1" required>
                        <small class="text-muted">Number of days after user registration to send this email</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="scheduleSubject" class="form-label">Email Subject *</label>
                        <input type="text" class="form-control" id="scheduleSubject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="scheduleContent" class="form-label">Email Content *</label>
                        <textarea class="form-control" id="scheduleContent" name="content" rows="10" required></textarea>
                        <small class="text-muted">You can use HTML formatting. Available variables: {name}, {email}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent" style="border: 1px solid #ddd; padding: 20px; background: white;">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Create schedule form submission
document.getElementById('createScheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    fetch('<?= BASE_URL ?>/admin/newsletters/create-follow-up', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Follow-up schedule created successfully!');
            bootstrap.Modal.getInstance(document.getElementById('createScheduleModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating schedule');
    });
});

// Toggle schedule activation
function toggleSchedule(id, status) {
    if (confirm('Are you sure you want to ' + (status ? 'activate' : 'deactivate') + ' this schedule?')) {
        // This would be implemented with AJAX
        alert('Toggle functionality would be implemented here');
    }
}

// Edit schedule
function editSchedule(id) {
    // This would open an edit modal with current data
    alert('Edit functionality would be implemented here');
}

// Preview schedule
function previewSchedule(id) {
    // This would load and display the email content
    document.getElementById('previewContent').innerHTML = '<p class="text-muted">Loading preview...</p>';
    new bootstrap.Modal(document.getElementById('previewModal')).show();
    
    // Simulate loading preview (would be actual implementation)
    setTimeout(() => {
        document.getElementById('previewContent').innerHTML = `
            <h2>Welcome to RentSmart!</h2>
            <p>Hi {name},</p>
            <p>It's been two weeks since you joined RentSmart. We hope you're enjoying the platform!</p>
            <p>Here are some tips to get the most out of your account:</p>
            <ul>
                <li>Complete your property profile</li>
                <li>Add your first listing</li>
                <li>Explore the dashboard features</li>
            </ul>
            <p>If you have any questions, don't hesitate to reach out to our support team.</p>
            <p>Best regards,<br>The RentSmart Team</p>
        `;
    }, 500);
}
</script>
