<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <div class="card page-header">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0"><?= $title ?></h1>
                    <p class="text-muted mb-0">
                        <?php if (isset($startDate) && isset($endDate)): ?>
                            Period: <?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($isAdmin && isset($data['user_info'])): ?>
                    <p class="text-muted mb-0">
                        User: <?= htmlspecialchars($data['user_info']['name']) ?> (<?= htmlspecialchars($data['user_info']['role']) ?>)
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/reports/generate?type=<?= $reportType ?>&format=pdf<?= isset($startDate) ? '&start_date=' . $startDate : '' ?><?= isset($endDate) ? '&end_date=' . $endDate : '' ?>" class="btn btn-outline-primary me-2">
                        <i class="bi bi-file-pdf me-2"></i>Export as PDF
                    </a>
                    <a href="<?= BASE_URL ?>/reports/generate?type=<?= $reportType ?>&format=csv<?= isset($startDate) ? '&start_date=' . $startDate : '' ?><?= isset($endDate) ? '&end_date=' . $endDate : '' ?>" class="btn btn-outline-primary">
                        <i class="bi bi-file-spreadsheet me-2"></i>Export as CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php switch($reportType): 
        case 'financial': ?>
        <!-- Financial Report -->
        <div class="row g-3 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Revenue Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Total Revenue</th>
                                        <th>Outstanding Balance</th>
                                        <th>Collection Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['propertyRevenue'] as $property): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($property['name']) ?></td>
                                        <td>Ksh<?= number_format($property['revenue'], 2) ?></td>
                                        <td>Ksh<?= number_format($property['outstanding'], 2) ?></td>
                                        <td><?= number_format($property['collection_rate'], 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php break; ?>

        <?php case 'occupancy': ?>
        <!-- Occupancy Report -->
        <div class="row g-3 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Occupancy Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Total Units</th>
                                        <th>Occupied Units</th>
                                        <th>Vacant Units</th>
                                        <th>Occupancy Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['propertyOccupancy'] as $property): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($property['name']) ?></td>
                                        <td><?= $property['total_units'] ?></td>
                                        <td><?= $property['occupied_units'] ?></td>
                                        <td><?= $property['total_units'] - $property['occupied_units'] ?></td>
                                        <td><?= number_format(($property['occupied_units'] / $property['total_units']) * 100, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php break; ?>

        <?php case 'tenant': ?>
        <!-- Tenant Report -->
        <div class="row g-3 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tenant Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tenant Name</th>
                                        <th>Property</th>
                                        <th>Unit</th>
                                        <th>Lease Start</th>
                                        <th>Lease End</th>
                                        <th>Rent Amount</th>
                                        <th>Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['tenants'] as $tenant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tenant['name']) ?></td>
                                        <td><?= htmlspecialchars($tenant['property_name']) ?></td>
                                        <td><?= htmlspecialchars($tenant['unit_number']) ?></td>
                                        <td><?= date('M j, Y', strtotime($tenant['lease_start'])) ?></td>
                                        <td><?= date('M j, Y', strtotime($tenant['lease_end'])) ?></td>
                                        <td>Ksh<?= number_format($tenant['rent_amount'], 2) ?></td>
                                        <td>
                                            <?php if ($tenant['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($tenant['payment_status'] === 'partial'): ?>
                                                <span class="badge bg-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php break; ?>

        <?php case 'lease': ?>
        <!-- Lease Report -->
        <div class="row g-3 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Active Leases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Unit</th>
                                        <th>Tenant</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Monthly Rent</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['activeLeases'] as $lease): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($lease['property_name']) ?></td>
                                        <td><?= htmlspecialchars($lease['unit_number']) ?></td>
                                        <td><?= htmlspecialchars($lease['tenant_name']) ?></td>
                                        <td><?= date('M j, Y', strtotime($lease['start_date'])) ?></td>
                                        <td><?= date('M j, Y', strtotime($lease['end_date'])) ?></td>
                                        <td>Ksh<?= number_format($lease['monthly_rent'], 2) ?></td>
                                        <td>
                                            <?php 
                                            $daysLeft = (strtotime($lease['end_date']) - time()) / (60 * 60 * 24);
                                            if ($daysLeft < 30): ?>
                                                <span class="badge bg-danger">Expiring Soon</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php break; ?>

        <?php case 'maintenance': ?>
        <!-- Maintenance Report -->
        <div class="row g-3 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Maintenance Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Unit</th>
                                        <th>Issue</th>
                                        <th>Reported Date</th>
                                        <th>Status</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['maintenanceRequests'] as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['property_name']) ?></td>
                                        <td><?= htmlspecialchars($request['unit_number']) ?></td>
                                        <td><?= htmlspecialchars($request['issue']) ?></td>
                                        <td><?= date('M j, Y', strtotime($request['reported_date'])) ?></td>
                                        <td>
                                            <?php if ($request['status'] === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($request['status'] === 'in_progress'): ?>
                                                <span class="badge bg-warning">In Progress</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>Ksh<?= number_format($request['cost'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php break; ?>

        <?php case 'delinquency': ?>
        <!-- Delinquency Report -->
        <div class="row g-3 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Delinquent Tenants</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tenant</th>
                                        <th>Property</th>
                                        <th>Unit</th>
                                        <th>Outstanding Amount</th>
                                        <th>Days Overdue</th>
                                        <th>Last Payment Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['delinquentTenants'] as $tenant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tenant['name']) ?></td>
                                        <td><?= htmlspecialchars($tenant['property_name']) ?></td>
                                        <td><?= htmlspecialchars($tenant['unit_number']) ?></td>
                                        <td>Ksh<?= number_format($tenant['outstanding_amount'], 2) ?></td>
                                        <td><?= $tenant['days_overdue'] ?> days</td>
                                        <td><?= $tenant['last_payment_date'] ? date('M j, Y', strtotime($tenant['last_payment_date'])) : 'Never' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php break; ?>

    <?php endswitch; ?>
</div>

<?php
$content = ob_get_clean();
echo view('layouts/main', ['content' => $content, 'title' => $title ?? 'Report']);
?> 