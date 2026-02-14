<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h1 class="h3 mb-0">
                    <i class="bi bi-envelope text-primary me-2"></i>Inquiries Management
                </h1>
               
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="inquiriesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php $isRealtor = strtolower((string)($_SESSION['user_role'] ?? '')) === 'realtor'; ?>
                            <?php if ($isRealtor): ?>
                                <th>Listing</th>
                            <?php else: ?>
                                <th>Property</th>
                                <th>Unit</th>
                            <?php endif; ?>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Preferred Date</th>
                            <th>Message</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($inquiries ?? []) as $inq): ?>
                            <tr>
                                <td><?= htmlspecialchars($inq['created_at'] ?? '') ?></td>
                                <?php if ($isRealtor): ?>
                                    <td><?= htmlspecialchars($inq['listing_title'] ?? '') ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($inq['property_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($inq['unit_number'] ?? '') ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($inq['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($inq['contact'] ?? '') ?></td>
                                <td><?= htmlspecialchars($inq['preferred_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($inq['message'] ?? '') ?></td>
                                <td><?= htmlspecialchars($inq['created_at'] ?? '') ?></td>
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
    if (window.jQuery && $('#inquiriesTable').DataTable) {
        $('#inquiriesTable').DataTable({ order:[[0,'desc']] });
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

