<?php 
$title = 'Lease Report';
include __DIR__ . '/_header.php'; 
?>

<!-- Active Leases -->
<?php if (!empty($data['activeLeases'])): ?>
<div class="summary-box">
    <h2>Active Leases</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Rent Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['activeLeases'] as $lease): ?>
            <tr>
                <td><?= htmlspecialchars($lease['tenant_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lease['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lease['unit_number'] ?? 'N/A') ?></td>
                <td><?= !empty($lease['start_date']) ? date('M j, Y', strtotime($lease['start_date'])) : 'N/A' ?></td>
                <td><?= !empty($lease['end_date']) ? date('M j, Y', strtotime($lease['end_date'])) : 'N/A' ?></td>
                <td>Ksh <?= number_format($lease['rent_amount'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Expiring Leases -->
<?php if (!empty($data['expiringLeases'])): ?>
<div>
    <h2>Expiring Leases (Next 30 Days)</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>End Date</th>
                <th>Days Remaining</th>
                <th>Rent Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['expiringLeases'] as $lease): ?>
            <?php 
            $daysRemaining = !empty($lease['end_date']) ? 
                ceil((strtotime($lease['end_date']) - time()) / (60 * 60 * 24)) : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($lease['tenant_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lease['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lease['unit_number'] ?? 'N/A') ?></td>
                <td><?= !empty($lease['end_date']) ? date('M j, Y', strtotime($lease['end_date'])) : 'N/A' ?></td>
                <td style="color: <?= $daysRemaining < 7 ? '#dc3545' : '#ffc107' ?>; font-weight: bold;">
                    <?= $daysRemaining ?> days
                </td>
                <td>Ksh <?= number_format($lease['rent_amount'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Lease History -->
<?php if (!empty($data['leaseHistory'])): ?>
<div>
    <h2>Lease History</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Period</th>
                <th>Status</th>
                <th>Rent</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($data['leaseHistory'], 0, 20) as $lease): ?>
            <tr>
                <td><?= htmlspecialchars($lease['tenant_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lease['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lease['unit_number'] ?? 'N/A') ?></td>
                <td>
                    <?php if (!empty($lease['start_date']) && !empty($lease['end_date'])): ?>
                        <?= date('M Y', strtotime($lease['start_date'])) ?> - 
                        <?= date('M Y', strtotime($lease['end_date'])) ?>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    $status = $lease['status'] ?? 'N/A';
                    $color = '#666';
                    if ($status === 'active') $color = '#28a745';
                    elseif ($status === 'expired') $color = '#dc3545';
                    ?>
                    <span style="color: <?= $color ?>; font-weight: bold;">
                        <?= ucfirst($status) ?>
                    </span>
                </td>
                <td>Ksh <?= number_format($lease['rent_amount'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
