<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-pen text-primary me-2"></i>Signature Request</h1>
      <div class="btn-group">
        <a href="<?= BASE_URL ?>/esign" class="btn btn-outline-secondary">Back</a>
        <a href="<?= htmlspecialchars($signUrl) ?>" class="btn btn-primary" target="_blank">Open Sign Link</a>
      </div>
    </div>
  </div>
  <?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
      <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div><strong>Title:</strong> <?= htmlspecialchars($req['title']) ?></div>
          <div><strong>Status:</strong> <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($req['status'])) ?></span></div>
          <?php if (!empty($req['expires_at'])): ?><div><strong>Expires:</strong> <?= htmlspecialchars($req['expires_at']) ?></div><?php endif; ?>
          <?php if (!empty($req['signed_at'])): ?><div><strong>Signed:</strong> <?= htmlspecialchars($req['signed_at']) ?></div><?php endif; ?>
          <?php if (!empty($req['declined_at'])): ?><div><strong>Declined:</strong> <?= htmlspecialchars($req['declined_at']) ?></div><?php endif; ?>
          <?php if (!empty($req['signer_name'])): ?><div><strong>Signer:</strong> <?= htmlspecialchars($req['signer_name']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <div class="text-end"><small>Shareable Link:</small><br><a href="<?= htmlspecialchars($signUrl) ?>" target="_blank"><?= htmlspecialchars($signUrl) ?></a></div>
        </div>
      </div>
      <?php if (!empty($req['signature_image'])): ?>
      <hr>
      <h6 class="mb-2">Captured Signature</h6>
      <img src="data:image/png;base64,<?= $req['signature_image'] ?>" alt="Signature" style="max-height:140px;border:1px solid #ddd;padding:8px;background:#fff">
      <?php endif; ?>
      <?php if (!empty($req['message'])): ?>
      <hr>
      <h6 class="mb-2">Message</h6>
      <p><?= nl2br(htmlspecialchars($req['message'])) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
