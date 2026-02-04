<?php
ob_start();
?>
<div class="container-fluid pt-4">
  <div class="card page-header mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="bi bi-pen text-primary me-2"></i>New E‑Signature Request</h1>
      <a href="<?= BASE_URL ?>/esign" class="btn btn-outline-secondary">Back</a>
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
      <form method="post" action="<?= BASE_URL ?>/esign/store">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Approve Invoice">
          </div>
          <div class="col-md-6">
            <label class="form-label">Expires (optional)</label>
            <input type="date" name="expires_at" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Message (optional)</label>
            <textarea name="message" rows="3" class="form-control" placeholder="Please review and sign..."></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Recipient Type</label>
            <select class="form-select" id="recipient_type" name="recipient_type">
              <option value="user">User (admin/manager/agent/landlord/caretaker)</option>
              <option value="tenant">Tenant</option>
            </select>
          </div>
          <div class="col-md-4" id="user_select_wrap">
            <label class="form-label">Select User</label>
            <select class="form-select" name="recipient_id" id="user_select">
              <?php foreach (($users ?? []) as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-none" id="tenant_select_wrap">
            <label class="form-label">Select Tenant</label>
            <select class="form-select" id="tenant_select">
              <?php foreach (($tenants ?? []) as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?> <?= !empty($t['unit_number']) ? '(' . htmlspecialchars($t['unit_number']) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Entity Type (optional)</label>
            <input type="text" name="entity_type" class="form-control" value="<?= htmlspecialchars($entity_type ?? '') ?>" placeholder="e.g. invoice">
          </div>
          <div class="col-md-3">
            <label class="form-label">Entity ID (optional)</label>
            <input type="number" name="entity_id" class="form-control" value="<?= htmlspecialchars($entity_id ?? '') ?>" placeholder="e.g. 45">
          </div>
        </div>
        <div class="text-end mt-3">
          <button class="btn btn-primary" type="submit">Send Request</button>
        </div>
      </form>
      <small class="text-muted d-block mt-2">Tip: To request tenant signature for an invoice, come from the Invoice page using the Request Signature button.</small>
    </div>
  </div>
</div>
<script>
(function(){
  const typeSel = document.getElementById('recipient_type');
  const userWrap = document.getElementById('user_select_wrap');
  const tenantWrap = document.getElementById('tenant_select_wrap');
  const form = document.querySelector('form');
  const tenantSel = document.getElementById('tenant_select');

  function sync() {
    if (typeSel.value === 'tenant') {
      userWrap.classList.add('d-none');
      tenantWrap.classList.remove('d-none');
    } else {
      tenantWrap.classList.add('d-none');
      userWrap.classList.remove('d-none');
    }
  }
  typeSel.addEventListener('change', sync);
  sync();

  form.addEventListener('submit', function(){
    if (typeSel.value === 'tenant') {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'recipient_id';
      hidden.value = tenantSel.value;
      form.appendChild(hidden);
    }
  });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
