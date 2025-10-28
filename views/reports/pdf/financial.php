<?php 
$title = 'Financial Report';
include __DIR__ . '/_header.php'; 

// Calculate totals from property revenue
$totalRevenue = 0;
$totalOutstanding = 0;
$totalExpected = 0;

if (!empty($data['propertyRevenue'])) {
    foreach ($data['propertyRevenue'] as $property) {
        $totalRevenue += $property['revenue'] ?? 0;
        $totalOutstanding += $property['outstanding'] ?? 0;
    }
}

// Calculate collection rate
$collectionRate = $totalExpected > 0 ? ($totalRevenue / $totalExpected) * 100 : 0;
?>

    <!-- Financial Summary -->
    <div class="summary-box">
        <h2>Financial Summary</h2>
        <table>
            <tr>
                <th>Total Revenue</th>
                <td>Ksh <?= number_format($totalRevenue, 2) ?></td>
                <th>Outstanding Balance</th>
                <td>Ksh <?= number_format($data['outstandingBalance'] ?? 0, 2) ?></td>
            </tr>
            <tr>
                <th>Collection Rate</th>
                <td><?= number_format($data['propertyRevenue'][0]['collection_rate'] ?? 0, 1) ?>%</td>
                <th>Total Properties</th>
                <td><?= count($data['propertyRevenue'] ?? []) ?></td>
            </tr>
        </table>
    </div>

    <!-- Revenue by Property -->
    <?php if (!empty($data['propertyRevenue'])): ?>
    <div>
        <h2>Revenue by Property</h2>
        <table>
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Revenue</th>
                    <th>Outstanding</th>
                    <th>Collection Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['propertyRevenue'] as $property): ?>
                <tr>
                    <td><?= htmlspecialchars($property['name']) ?></td>
                    <td>Ksh <?= number_format($property['revenue'], 2) ?></td>
                    <td>Ksh <?= number_format($property['outstanding'], 2) ?></td>
                    <td><?= number_format($property['collection_rate'], 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Recent Payments -->
    <?php if (!empty($data['recentPayments'])): ?>
    <div>
        <h2>Recent Payments</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tenant</th>
                    <th>Property</th>
                    <th>Unit</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['recentPayments'] as $payment): ?>
                <tr>
                    <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                    <td><?= htmlspecialchars($payment['tenant_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($payment['property_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($payment['unit_number'] ?? 'N/A') ?></td>
                    <td>Ksh <?= number_format($payment['amount'], 2) ?></td>
                    <td><span style="color: #28a745; font-weight: bold;"><?= ucfirst($payment['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>