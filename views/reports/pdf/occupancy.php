<?php 
$title = 'Occupancy Report';
include __DIR__ . '/_header.php'; 

// Get occupancy stats
$occupancyStats = $data['occupancyStats'] ?? [];
$totalUnits = $occupancyStats['total_units'] ?? 0;
$occupiedUnits = $occupancyStats['occupied_units'] ?? 0;
$vacantUnits = $totalUnits - $occupiedUnits;
$occupancyRate = $occupancyStats['occupancy_rate'] ?? 0;
?>

<!-- Overall Occupancy Summary -->
<div class="summary-box">
    <h2>Overall Occupancy Summary</h2>
    <table>
        <tr>
            <th>Total Units</th>
            <td><?= $totalUnits ?></td>
            <th>Occupied Units</th>
            <td><?= $occupiedUnits ?></td>
        </tr>
        <tr>
            <th>Vacant Units</th>
            <td><?= $vacantUnits ?></td>
            <th>Overall Occupancy Rate</th>
            <td><?= number_format($occupancyRate, 1) ?>%</td>
        </tr>
    </table>
</div>

<!-- Property-wise Occupancy -->
<?php if (!empty($data['propertyOccupancy'])): ?>
<div>
    <h2>Property-wise Occupancy Details</h2>
    <table>
        <thead>
            <tr>
                <th>Property</th>
                <th>Total Units</th>
                <th>Occupied</th>
                <th>Vacant</th>
                <th>Occupancy Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['propertyOccupancy'] as $property): ?>
            <tr>
                <td><?= htmlspecialchars($property['name'] ?? $property['property_name'] ?? 'N/A') ?></td>
                <td><?= $property['total_units'] ?? 0 ?></td>
                <td><?= $property['occupied_units'] ?? 0 ?></td>
                <td><?= ($property['total_units'] ?? 0) - ($property['occupied_units'] ?? 0) ?></td>
                <td><?= number_format($property['occupancy_rate'] ?? 0, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Vacant Units -->
<?php if (!empty($data['vacantUnits'])): ?>
<div>
    <h2>Vacant Units</h2>
    <table>
        <thead>
            <tr>
                <th>Property</th>
                <th>Unit Number</th>
                <th>Bedrooms</th>
                <th>Rent</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['vacantUnits'] as $unit): ?>
            <tr>
                <td><?= htmlspecialchars($unit['property_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($unit['unit_number'] ?? 'N/A') ?></td>
                <td><?= $unit['bedrooms'] ?? 'N/A' ?></td>
                <td>Ksh <?= number_format($unit['rent'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Occupancy Status Distribution -->
<div>
    <h2>Occupancy Status Distribution</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Occupied Units</td>
                <td><?= $occupiedUnits ?></td>
                <td><?= number_format($occupancyRate, 1) ?>%</td>
            </tr>
            <tr>
                <td>Vacant Units</td>
                <td><?= $vacantUnits ?></td>
                <td><?= number_format(100 - $occupancyRate, 1) ?>%</td>
            </tr>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/_footer.php'; ?>