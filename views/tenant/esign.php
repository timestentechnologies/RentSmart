<!DOCTYPE html>
<html>
<head>
    <?php
    $siteName = isset($settings['site_name']) && $settings['site_name'] ? $settings['site_name'] : 'RentSmart';
    $pageTitle = 'E‑Signatures | ' . htmlspecialchars($siteName);
    ?>
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($siteFavicon ?? '') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>/tenant/dashboard" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($siteLogo)): ?>
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Site Logo" style="max-height:40px;max-width:160px;object-fit:contain;">
                <?php endif; ?>
                <span class="fw-semibold">E‑Signatures</span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/tenant/logout" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>

    <div class="card page-header mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0"><i class="bi bi-pen text-primary me-2"></i>E‑Signatures</h1>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show mt-2">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if (empty($requests)): ?>
        <div class="card">
            <div class="card-body text-center text-muted py-5">No signature requests found.</div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>From</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $r): ?>
                            <?php
                                $status = strtolower((string)($r['status'] ?? 'pending'));
                                $badge = 'secondary';
                                if ($status === 'pending') $badge = 'warning';
                                if ($status === 'signed') $badge = 'success';
                                if ($status === 'declined') $badge = 'danger';
                                if ($status === 'expired') $badge = 'dark';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['title'] ?? '-') ?></div>
                                    <?php if (!empty($r['message'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$r['message']), 0, 120, '…', 'UTF-8')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                                <td><?= htmlspecialchars($r['requester_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($r['created_at'] ?? 'now'))) ?></td>
                                <td class="text-end">
                                    <?php if ($status === 'pending'): ?>
                                        <a class="btn btn-sm btn-primary" target="_blank" href="<?= BASE_URL ?>/esign/sign/<?= htmlspecialchars($r['token']) ?>">Sign</a>
                                    <?php elseif (!empty($r['signed_document_path'])): ?>
                                        <a class="btn btn-sm btn-success" target="_blank" href="<?= BASE_URL ?>/public/<?= htmlspecialchars($r['signed_document_path']) ?>">Signed Copy</a>
                                    <?php elseif (!empty($r['document_path'])): ?>
                                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= BASE_URL ?>/public/<?= htmlspecialchars($r['document_path']) ?>">View</a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
