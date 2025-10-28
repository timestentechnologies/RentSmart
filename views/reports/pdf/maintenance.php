<?php 
$title = 'Maintenance Report';
include __DIR__ . '/_header.php'; 
?>

<!-- Maintenance Requests -->
<?php if (!empty($data['maintenanceRequests'])): ?>
<div class="summary-box">
    <h2>Maintenance Requests</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Status</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['maintenanceRequests'] as $request): ?>
            <tr>
                <td><?= !empty($request['created_at']) ? date('M j, Y', strtotime($request['created_at'])) : 'N/A' ?></td>
                <td><?= htmlspecialchars($request['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($request['unit_number'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars(substr($request['description'] ?? 'N/A', 0, 50)) ?></td>
                <td>
                    <?php 
                    $status = $request['status'] ?? 'pending';
                    $color = '#666';
                    if ($status === 'completed') $color = '#28a745';
                    elseif ($status === 'in_progress') $color = '#ffc107';
                    elseif ($status === 'pending') $color = '#dc3545';
                    ?>
                    <span style="color: <?= $color ?>; font-weight: bold;">
                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                    </span>
                </td>
                <td>Ksh <?= number_format($request['actual_cost'] ?? $request['cost'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Maintenance Costs by Property -->
<?php if (!empty($data['maintenanceCosts'])): ?>
<div>
    <h2>Maintenance Costs by Property</h2>
    <table>
        <thead>
            <tr>
                <th>Property</th>
                <th>Total Requests</th>
                <th>Completed</th>
                <th>Pending</th>
                <th>Total Cost</th>
                <th>Average Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['maintenanceCosts'] as $cost): ?>
            <tr>
                <td><?= htmlspecialchars($cost['property_name'] ?? 'N/A') ?></td>
                <td><?= $cost['total_requests'] ?? 0 ?></td>
                <td><?= $cost['completed_requests'] ?? 0 ?></td>
                <td><?= $cost['pending_requests'] ?? 0 ?></td>
                <td>Ksh <?= number_format($cost['total_cost'] ?? 0, 2) ?></td>
                <td>Ksh <?= number_format($cost['average_cost'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
