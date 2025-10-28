<?php 
$title = 'Tenant Report';
include __DIR__ . '/_header.php'; 

// Calculate statistics from tenant data
$tenants = $data['tenants'] ?? [];
$leaseStats = $data['leaseStats'] ?? [];
$paymentStats = $data['paymentStats'] ?? [];

$totalTenants = count($tenants);
$activeLeases = 0;
$totalRent = 0;

foreach ($tenants as $tenant) {
    if (!empty($tenant['lease_status']) && $tenant['lease_status'] === 'active') {
        $activeLeases++;
    }
    $totalRent += $tenant['rent_amount'] ?? 0;
}

$averageRent = $totalTenants > 0 ? $totalRent / $totalTenants : 0;
?>

<!-- Tenant Statistics Summary -->
<div class="summary-box">
    <h2>Tenant Statistics</h2>
    <table>
        <tr>
            <th>Total Tenants</th>
            <td><?= $totalTenants ?></td>
            <th>Active Leases</th>
            <td><?= $leaseStats['active_leases'] ?? $activeLeases ?></td>
        </tr>
        <tr>
            <th>Total Leases</th>
            <td><?= $leaseStats['total_leases'] ?? 0 ?></td>
            <th>Average Rent</th>
            <td>Ksh <?= number_format($leaseStats['average_rent'] ?? $averageRent, 2) ?></td>
        </tr>
    </table>
</div>

<!-- Tenants List -->
<?php if (!empty($tenants)): ?>
<div>
    <h2>Tenant Details</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Lease Dates</th>
                <th>Rent Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tenants as $tenant): ?>
            <tr>
                <td><?= htmlspecialchars($tenant['name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($tenant['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($tenant['unit_number'] ?? 'N/A') ?></td>
                <td>
                    <?php if (!empty($tenant['start_date']) && !empty($tenant['end_date'])): ?>
                        <?= date('M j, Y', strtotime($tenant['start_date'])) ?><br>
                        to <?= date('M j, Y', strtotime($tenant['end_date'])) ?>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>Ksh <?= number_format($tenant['rent_amount'] ?? 0, 2) ?></td>
                <td>
                    <?php 
                    $status = $tenant['lease_status'] ?? 'N/A';
                    $statusColor = '#666';
                    if ($status === 'active') {
                        $statusColor = '#28a745';
                    } elseif ($status === 'expired') {
                        $statusColor = '#dc3545';
                    }
                    ?>
                    <span style="color: <?= $statusColor ?>; font-weight: bold;">
                        <?= ucfirst($status) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Payment Statistics -->
<?php if (!empty($paymentStats)): ?>
<div>
    <h2>Payment Statistics</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th>Total Payments</th>
                <th>Total Amount Paid</th>
                <th>Last Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paymentStats as $stat): ?>
            <tr>
                <td><?= htmlspecialchars($stat['tenant_name'] ?? 'N/A') ?></td>
                <td><?= $stat['total_payments'] ?? 0 ?></td>
                <td>Ksh <?= number_format($stat['total_paid'] ?? 0, 2) ?></td>
                <td>
                    <?php if (!empty($stat['last_payment_date'])): ?>
                        <?= date('M j, Y', strtotime($stat['last_payment_date'])) ?>
                    <?php else: ?>
                        No payments
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>