<?php
ob_start();
?>

<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <style>
            .page-header .filters-toolbar{display:flex;flex-wrap:nowrap;gap:.5rem;overflow-x:auto;padding-bottom:2px}
            .page-header .filters-toolbar .form-select,.page-header .filters-toolbar .form-control{min-width:160px}
            .page-header .filters-toolbar .input-group{min-width:260px}
            </style>
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-auto mb-2 mb-md-0">
                    <h1 class="h3 mb-0 d-flex align-items-center">
                        <i class="bi bi-people text-primary me-2"></i>
                        Monthly Tenant Balances
                    </h1>
                    <div class="text-muted small">Period: <?= htmlspecialchars($period ?? date('Y-m')) ?></div>
                </div>
                <div class="col-12 col-md">
                    <div class="filters-toolbar justify-content-end align-items-center">
                        <input type="month" id="tbPeriod" class="form-control form-control-sm w-auto" value="<?= htmlspecialchars($period ?? date('Y-m')) ?>" />
                        <select id="tbProperty" class="form-select form-select-sm w-auto" style="min-width: 220px;">
                            <option value="">All Properties</option>
                            <?php foreach (($properties ?? []) as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= isset($selectedPropertyId) && (int)$selectedPropertyId === (int)$p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="tbStatus" class="form-select form-select-sm w-auto" style="min-width: 160px;">
                            <?php $st = ($status ?? 'all'); ?>
                            <option value="all" <?= $st === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="paid" <?= $st === 'paid' ? 'selected' : '' ?>>Paid (0 balance)</option>
                            <option value="due" <?= $st === 'due' ? 'selected' : '' ?>>Due (has balance)</option>
                            <option value="advance" <?= $st === 'advance' ? 'selected' : '' ?>>Advance (prepaid)</option>
                        </select>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="tbSearch" class="form-control" placeholder="Search tenant, unit, property..." />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tenantBalancesTable">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Unit</th>
                            <th>Tenant</th>
                            <th>Month</th>
                            <th class="text-end">Rent</th>
                            <th class="text-end">Paid In Month</th>
                            <th class="text-end">Utilities</th>
                            <th class="text-end">Maintenance</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="text-center">Prepaid Months</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($rows ?? []) as $r): ?>
                        <tr data-prop-id="<?= (int)$r['property_id'] ?>" data-status="<?= htmlspecialchars($r['status']) ?>">
                            <td><?= htmlspecialchars($r['property_name']) ?></td>
                            <td><?= htmlspecialchars($r['unit_number']) ?></td>
                            <td><?= htmlspecialchars($r['tenant_name']) ?></td>
                            <td><?= htmlspecialchars($r['month_label']) ?></td>
                            <td class="text-end"><?= number_format((float)$r['rent_amount'], 2) ?></td>
                            <td class="text-end"><?= number_format((float)$r['paid_in_month'], 2) ?></td>
                            <td class="text-end"><?= number_format((float)($r['utilities_due'] ?? 0), 2) ?></td>
                            <td class="text-end"><?= number_format((float)($r['maintenance_due'] ?? 0), 2) ?></td>
                            <td class="text-end fw-semibold <?= ($r['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format((float)$r['balance'], 2) ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'due'): ?>
                                    <span class="badge bg-danger">Due</span>
                                <?php elseif ($r['status'] === 'advance'): ?>
                                    <span class="badge bg-info text-dark">Advance</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= (int)$r['prepaid_months'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable with dynamic filters
(function(){
    document.addEventListener('DOMContentLoaded', function(){
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable) return;
        var $ = window.jQuery;
        var dt = $('#tenantBalancesTable').DataTable({
            pageLength: 25,
            order: [[0,'asc'], [1,'asc'], [2,'asc']]
        });

        var $prop = document.getElementById('tbProperty');
        var $status = document.getElementById('tbStatus');
        var $search = document.getElementById('tbSearch');
        var $period = document.getElementById('tbPeriod');

        // Custom filter for property and status
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            if (settings.nTable !== document.getElementById('tenantBalancesTable')) return true;
            var api = new $.fn.dataTable.Api(settings);
            var row = api.row(dataIndex).node();
            var propId = row.getAttribute('data-prop-id');
            var stat = row.getAttribute('data-status');
            var okProp = !$prop.value || $prop.value === '' || $prop.value === propId;
            var okStat = ($status.value === 'all') || (stat === $status.value);
            return okProp && okStat;
        });

        // Wire up filters
        $prop.addEventListener('change', function(){ dt.draw(); });
        $status.addEventListener('change', function(){ dt.draw(); });
        $search.addEventListener('input', function(){ dt.search(this.value).draw(); });
        $period.addEventListener('change', function(){
            var params = new URLSearchParams(window.location.search);
            params.set('period', this.value);
            if ($prop.value) { params.set('property_id', $prop.value); } else { params.delete('property_id'); }
            if ($status.value && $status.value !== 'all') { params.set('status', $status.value); } else { params.delete('status'); }
            var base = '<?= BASE_URL ?>/reports/tenant-balances';
            window.location.href = base + '?' + params.toString();
        });

        // Initial draw to apply default filters
        dt.draw();
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
