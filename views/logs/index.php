<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-activity text-primary me-2"></i>Activity Logs
                </h1>
                <p class="text-muted mb-0 mt-1">View recent actions across the system<?= !empty($isAdmin) ? ' (Admin)' : '' ?></p>
            </div>
            <?php $qs = http_build_query($_GET ?? []); ?>
            <div class="d-flex gap-2 ms-md-auto">
                <a href="<?= BASE_URL ?>/logs/export/csv<?= $qs ? ('?' . $qs) : '' ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-filetype-csv me-1"></i>Export CSV
                </a>
                <a href="<?= BASE_URL ?>/logs/export/xlsx<?= $qs ? ('?' . $qs) : '' ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export XLSX
                </a>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <?php if (!empty($isAdmin)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label">User</label>
                    <select class="form-select" name="user_id">
                        <option value="">All Users</option>
                        <?php foreach (($users ?? []) as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= isset($_GET['user_id']) && (int)$_GET['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($u['name'] ?? 'User') . ' - ' . ($u['email'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-3">
                    <label class="form-label">Property</label>
                    <select class="form-select" name="property_id">
                        <option value="">All Properties</option>
                        <?php foreach (($properties ?? []) as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= isset($_GET['property_id']) && (int)$_GET['property_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Entity</label>
                    <input type="text" class="form-control" name="entity_type" value="<?= htmlspecialchars($_GET['entity_type'] ?? '') ?>" placeholder="e.g. property">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Action</label>
                    <input type="text" class="form-control" name="action" value="<?= htmlspecialchars($_GET['action'] ?? '') ?>" placeholder="e.g. property.create">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable" data-order='[[0,"desc"]]'>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Entity ID</th>
                            <th>Property</th>
                            <th>IP</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($logs ?? []) as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                            <td><?= htmlspecialchars($log['role'] ?? '') ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($log['entity_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['entity_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['property_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                            <td style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                                <code class="text-wrap d-inline-block" style="max-width:100%;white-space:pre-wrap;">
                                    <?= htmlspecialchars($log['details'] ?? '') ?>
                                </code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
