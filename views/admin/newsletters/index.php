<?php
ob_start();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Newsletter Management</h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/newsletters/create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Create Newsletter
            </a>
            <a href="<?= BASE_URL ?>/admin/newsletters/follow-up-schedules" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-clock me-2"></i>Follow-up Schedules
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>/admin/newsletters" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Search newsletters..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                    <a href="<?= BASE_URL ?>/admin/newsletters" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Email Campaigns</h5>
        </div>
        <div class="card-body">
            <?php if (empty($campaigns)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-envelope text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No newsletters found. Create your first newsletter!</p>
                    <a href="<?= BASE_URL ?>/admin/newsletters/create" class="btn btn-primary">Create Newsletter</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Recipients</th>
                                <th>Sent</th>
                                <th>Opened</th>
                                <th>Clicked</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($campaign['title']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($campaign['subject']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $campaign['type'] === 'newsletter' ? 'primary' : ($campaign['type'] === 'follow_up' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($campaign['type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'scheduled' => 'warning',
                                            'sent' => 'success',
                                            'paused' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $statusColors[$campaign['status']] ?? 'secondary' ?>">
                                            <?= ucfirst($campaign['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($campaign['total_recipients']) ?></td>
                                    <td><?= number_format($campaign['sent_count']) ?></td>
                                    <td><?= number_format($campaign['opened_count']) ?></td>
                                    <td><?= number_format($campaign['clicked_count']) ?></td>
                                    <td><?= date('M j, Y', strtotime($campaign['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/admin/newsletters/edit/<?= $campaign['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-info" onclick="showStats(<?= $campaign['id'] ?>)" title="Statistics">
                                                <i class="bi bi-bar-chart"></i>
                                            </button>
                                            <?php if ($campaign['status'] === 'draft' || $campaign['status'] === 'scheduled'): ?>
                                                <button type="button" class="btn btn-outline-success" onclick="sendTestEmail(<?= $campaign['id'] ?>)" title="Send Test">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                                <?php if ($campaign['status'] === 'draft'): ?>
                                                    <button type="button" class="btn btn-success" onclick="sendCampaign(<?= $campaign['id'] ?>)" title="Send Now">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
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
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= BASE_URL ?>/admin/newsletters?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Send Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testEmailForm">
                    <input type="hidden" id="testCampaignId">
                    <div class="mb-3">
                        <label for="testEmail" class="form-label">Test Email Address</label>
                        <input type="email" class="form-control" id="testEmail" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmailSubmit()">Send Test</button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Campaign Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="statsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalTitle">Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="messageModalContent"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Campaign Confirmation Modal -->
<div class="modal fade" id="sendCampaignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to send this campaign to all subscribers? This action cannot be undone.</p>
                <div id="sendCampaignProgress" style="display: none;">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Sending...</span>
                        </div>
                        <span>Sending campaign...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmSendCampaignBtn">Send Campaign</button>
            </div>
        </div>
    </div>
</div>

<script>
function sendTestEmail(campaignId) {
    document.getElementById('testCampaignId').value = campaignId;
    new bootstrap.Modal(document.getElementById('testEmailModal')).show();
}

function sendTestEmailSubmit() {
    const campaignId = document.getElementById('testCampaignId').value;
    const testEmail = document.getElementById('testEmail').value;
    
    if (!testEmail) {
        showMessage('Error', 'Please enter a test email address', 'danger');
        return;
    }
    
    // Show loading spinner
    const submitBtn = document.querySelector('#testEmailModal .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    submitBtn.disabled = true;
    
    fetch('<?= BASE_URL ?>/admin/newsletters/send-test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            campaign_id: campaignId,
            test_email: testEmail
        })
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
            showMessage('Success', 'Test email sent successfully!', 'success');
            document.getElementById('testEmail').value = '';
        } else {
            showMessage('Error', data.message || 'Failed to send test email', 'danger');
        }
    })
    .catch(error => {
        // Reset button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        showMessage('Error', 'Error sending test email', 'danger');
    });
}

function sendCampaign(campaignId) {
    const modal = new bootstrap.Modal(document.getElementById('sendCampaignModal'));
    
    // Set up confirm button click handler
    document.getElementById('confirmSendCampaignBtn').onclick = function() {
        // Show loading spinner
        document.getElementById('sendCampaignProgress').style.display = 'block';
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
        
        // Send campaign via AJAX
        fetch('<?= BASE_URL ?>/admin/newsletters/send/' + campaignId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                showMessage('Success', 'Campaign sent successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showMessage('Error', data.message || 'Failed to send campaign', 'danger');
                // Reset modal
                document.getElementById('sendCampaignProgress').style.display = 'none';
                this.disabled = false;
                this.textContent = 'Send Campaign';
            }
        })
        .catch(error => {
            showMessage('Error', 'Error sending campaign', 'danger');
            // Reset modal
            document.getElementById('sendCampaignProgress').style.display = 'none';
            this.disabled = false;
            this.textContent = 'Send Campaign';
        });
    };
    
    // Reset modal state
    document.getElementById('sendCampaignProgress').style.display = 'none';
    document.getElementById('confirmSendCampaignBtn').disabled = false;
    document.getElementById('confirmSendCampaignBtn').textContent = 'Send Campaign';
    
    modal.show();
}

function showStats(campaignId) {
    // Show modal with loading spinner
    const statsModal = new bootstrap.Modal(document.getElementById('statsModal'));
    const statsContent = document.getElementById('statsContent');
    
    // Reset to loading state
    statsContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    statsModal.show();
    
    // Load stats via AJAX
    fetch(`<?= BASE_URL ?>/admin/newsletters/stats-ajax/${campaignId}`)
        .then(response => response.text())
        .then(html => {
            statsContent.innerHTML = html;
        })
        .catch(error => {
            statsContent.innerHTML = `
                <div class="alert alert-danger">
                    Error loading statistics: ${error.message}
                </div>
            `;
        });
}

// Show message modal
function showMessage(title, message, type) {
    document.getElementById('messageModalTitle').textContent = title;
    document.getElementById('messageModalContent').textContent = message;
    
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/main.php';
?>
