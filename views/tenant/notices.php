<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0"><i class="bi bi-megaphone text-primary me-2"></i>Notices</h1>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show mt-2">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if (empty($notices)): ?>
        <div class="card">
            <div class="card-body text-center text-muted py-5">No notices available.</div>
        </div>
    <?php else: ?>
        <?php foreach ($notices as $n): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($n['title']) ?></strong>
                        <?php if (!empty($n['pinned'])): ?>
                            <span class="badge bg-warning text-dark ms-2">Pinned</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at'] ?? 'now'))) ?></small>
                </div>
                <div class="card-body">
                    <div class="mb-2 text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;">
                        <?= htmlspecialchars(mb_strimwidth(strip_tags($n['body'] ?? ''), 0, 200, '…', 'UTF-8')) ?>
                    </div>
                    <div class="small text-muted">
                        <?php if(!empty($n['property_name'])): ?>Property: <?= htmlspecialchars($n['property_name']) ?><?php endif; ?>
                        <?php if(!empty($n['unit_number'])): ?> • Unit: <?= htmlspecialchars($n['unit_number']) ?><?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#tenantNoticeModal"
                                data-title="<?= htmlspecialchars($n['title'] ?? 'Notice', ENT_QUOTES) ?>"
                                data-created="<?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at'] ?? 'now')), ENT_QUOTES) ?>"
                                data-body="<?= htmlspecialchars($n['body'] ?? '', ENT_QUOTES) ?>"
                                data-property="<?= htmlspecialchars($n['property_name'] ?? '', ENT_QUOTES) ?>"
                                data-unit="<?= htmlspecialchars($n['unit_number'] ?? '', ENT_QUOTES) ?>">
                            View
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="tenantNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tenantNoticeModalTitle">Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted" id="tenantNoticeModalMeta"></div>
                <hr>
                <div id="tenantNoticeModalBody" style="white-space:pre-wrap;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var noticeModal = document.getElementById('tenantNoticeModal');
    if (!noticeModal) return;
    noticeModal.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (!btn) return;
      var title = btn.getAttribute('data-title') || 'Notice';
      var created = btn.getAttribute('data-created') || '';
      var body = btn.getAttribute('data-body') || '';
      var property = btn.getAttribute('data-property') || '';
      var unit = btn.getAttribute('data-unit') || '';

      var meta = created;
      if (property) meta += (meta ? ' • ' : '') + 'Property: ' + property;
      if (unit) meta += (meta ? ' • ' : '') + 'Unit: ' + unit;

      var t = document.getElementById('tenantNoticeModalTitle');
      var m = document.getElementById('tenantNoticeModalMeta');
      var b = document.getElementById('tenantNoticeModalBody');
      if (t) t.textContent = title;
      if (m) m.textContent = meta;
      if (b) b.textContent = body;
    });
  });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
