<?php 
$title = 'Delinquency Report';
include __DIR__ . '/_header.php'; 
?>

<!-- Delinquent Tenants -->
<?php if (!empty($data['delinquentTenants'])): ?>
<div class="summary-box">
    <h2>Delinquent Tenants</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Rent Amount</th>
                <th>Total Paid</th>
                <th>Balance Due</th>
                <th>Last Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['delinquentTenants'] as $tenant): ?>
            <tr>
                <td><?= htmlspecialchars($tenant['name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($tenant['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($tenant['unit_number'] ?? 'N/A') ?></td>
                <td>Ksh <?= number_format($tenant['rent_amount'] ?? 0, 2) ?></td>
                <td>Ksh <?= number_format($tenant['total_paid'] ?? 0, 2) ?></td>
                <td style="color: #dc3545; font-weight: bold;">
                    Ksh <?= number_format($tenant['balance_due'] ?? 0, 2) ?>
                </td>
                <td>
                    <?php if (!empty($tenant['last_payment_date'])): ?>
                        <?= date('M j, Y', strtotime($tenant['last_payment_date'])) ?>
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

<!-- Outstanding Payments -->
<?php if (!empty($data['outstandingPayments'])): ?>
<div>
    <h2>Outstanding Payments</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Rent Amount</th>
                <th>Paid</th>
                <th>Outstanding</th>
                <th>Lease Period</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['outstandingPayments'] as $payment): ?>
            <tr>
                <td><?= htmlspecialchars($payment['tenant_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($payment['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($payment['unit_number'] ?? 'N/A') ?></td>
                <td>Ksh <?= number_format($payment['rent_amount'] ?? 0, 2) ?></td>
                <td>Ksh <?= number_format($payment['total_paid'] ?? 0, 2) ?></td>
                <td style="color: #dc3545; font-weight: bold;">
                    Ksh <?= number_format($payment['balance_due'] ?? 0, 2) ?>
                </td>
                <td>
                    <?php if (!empty($payment['start_date']) && !empty($payment['end_date'])): ?>
                        <?= date('M Y', strtotime($payment['start_date'])) ?> - 
                        <?= date('M Y', strtotime($payment['end_date'])) ?>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Payment History -->
<?php if (!empty($data['paymentHistory'])): ?>
<div>
    <h2>Recent Payment History</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Tenant</th>
                <th>Property</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($data['paymentHistory'], 0, 15) as $payment): ?>
            <tr>
                <td><?= !empty($payment['payment_date']) ? date('M j, Y', strtotime($payment['payment_date'])) : 'N/A' ?></td>
                <td><?= htmlspecialchars($payment['tenant_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($payment['property_name'] ?? 'N/A') ?></td>
                <td>Ksh <?= number_format($payment['amount'] ?? 0, 2) ?></td>
                <td>
                    <span style="color: #28a745; font-weight: bold;">
                        <?= ucfirst($payment['status'] ?? 'N/A') ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
