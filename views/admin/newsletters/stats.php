<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Campaign Statistics</h1>
        <a href="<?= BASE_URL ?>/admin/newsletters" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Newsletters
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= number_format($stats['total']) ?></h5>
                    <p class="card-text">Total Recipients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= number_format($stats['opened']) ?></h5>
                    <p class="card-text">Emails Opened</p>
                    <small class="text-muted">
                        <?= $stats['total'] > 0 ? round(($stats['opened'] / $stats['total']) * 100, 1) : 0 ?>% rate
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?= number_format($stats['clicked']) ?></h5>
                    <p class="card-text">Links Clicked</p>
                    <small class="text-muted">
                        <?= $stats['opened'] > 0 ? round(($stats['clicked'] / $stats['opened']) * 100, 1) : 0 ?>% of opened
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= number_format($stats['total'] - $stats['opened']) ?></h5>
                    <p class="card-text">Not Opened</p>
                    <small class="text-muted">
                        <?= $stats['total'] > 0 ? round((($stats['total'] - $stats['opened']) / $stats['total']) * 100, 1) : 0 ?>% rate
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Campaign Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Title:</th>
                            <td><?= htmlspecialchars($campaign['title']) ?></td>
                        </tr>
                        <tr>
                            <th>Subject:</th>
                            <td><?= htmlspecialchars($campaign['subject']) ?></td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>
                                <span class="badge bg-<?= $campaign['type'] === 'newsletter' ? 'primary' : 'secondary' ?>">
                                    <?= ucfirst($campaign['type']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?= $campaign['status'] === 'sent' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($campaign['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Sent At:</th>
                            <td><?= $campaign['sent_at'] ? date('M j, Y g:i A', strtotime($campaign['sent_at'])) : 'Not sent yet' ?></td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td><?= date('M j, Y g:i A', strtotime($campaign['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Survey Responses -->
            <?php if (!empty($surveyStats)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Survey Responses</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($surveyStats as $stat): ?>
                            <div class="mb-3">
                                <h6><?= htmlspecialchars($stat['question_text']) ?></h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?= $campaign['total_recipients'] > 0 ? ($stat['response_count'] / $campaign['total_recipients']) * 100 : 0 ?>%">
                                        <?= $stat['response_count'] ?> responses
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= $campaign['total_recipients'] > 0 ? round(($stat['response_count'] / $campaign['total_recipients']) * 100, 1) : 0 ?>% response rate
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Performance Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="exportStats()">
                            <i class="bi bi-download me-2"></i>Export Statistics
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="viewRecipients()">
                            <i class="bi bi-people me-2"></i>View Recipients
                        </button>
                        <a href="<?= BASE_URL ?>/admin/newsletters/edit/<?= $campaign['id'] ?>" class="btn btn-outline-warning">
                            <i class="bi bi-pencil me-2"></i>Edit Campaign
                        </a>
                        <button type="button" class="btn btn-outline-success" onclick="duplicateCampaign()">
                            <i class="bi bi-copy me-2"></i>Duplicate Campaign
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Performance Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Opened', 'Clicked', 'Not Opened'],
        datasets: [{
            data: [<?= $stats['opened'] ?>, <?= $stats['clicked'] ?>, <?= $stats['total'] - $stats['opened'] ?>],
            backgroundColor: [
                '#28a745',
                '#17a2b8',
                '#ffc107'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Export statistics
function exportStats() {
    const stats = {
        campaign: '<?= htmlspecialchars($campaign['title']) ?>',
        totalRecipients: <?= $stats['total'] ?>,
        opened: <?= $stats['opened'] ?>,
        clicked: <?= $stats['clicked'] ?>,
        notOpened: <?= $stats['total'] - $stats['opened'] ?>,
        openRate: <?= $stats['total'] > 0 ? round(($stats['opened'] / $stats['total']) * 100, 1) : 0 ?>,
        clickRate: <?= $stats['opened'] > 0 ? round(($stats['clicked'] / $stats['opened']) * 100, 1) : 0 ?>
    };
    
    const csv = Object.keys(stats).map(key => key + ',' + stats[key]).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'campaign_stats_<?= $campaign['id'] ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// View recipients
function viewRecipients() {
    alert('Recipient list functionality would be implemented here');
}

// Duplicate campaign
function duplicateCampaign() {
    if (confirm('Create a copy of this campaign?')) {
        window.location.href = '<?= BASE_URL ?>/admin/newsletters/create?duplicate=<?= $campaign['id'] ?>';
    }
}
</script>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
