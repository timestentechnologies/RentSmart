<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-file-text text-primary me-2"></i>Contracts</h1>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addContractModal">
                <i class="bi bi-plus-circle me-1"></i> Add Contract
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Client</th>
                            <th>Terms</th>
                            <th>Total</th>
                            <th>Monthly</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($contracts ?? []) as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($c['property_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['client_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['terms_type'] ?? '')) ?></td>
                                <td><?= number_format((float)($c['total_amount'] ?? 0), 2) ?></td>
                                <td><?= ($c['terms_type'] ?? '') === 'monthly' ? number_format((float)($c['monthly_amount'] ?? 0), 2) : '-' ?></td>
                                <td><?= htmlspecialchars((string)($c['status'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($contracts)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No contracts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/agent/contracts/store">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select class="form-select" name="property_id" required>
                                <option value="">Select property</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="agent_client_id" required>
                                <option value="">Select client</option>
                                <?php foreach (($clients ?? []) as $cl): ?>
                                    <option value="<?= (int)($cl['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($cl['name'] ?? '')) ?> (<?= htmlspecialchars((string)($cl['property_name'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Terms</label>
                            <select class="form-select" name="terms_type" id="terms_type" required>
                                <option value="one_time">One-time</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input class="form-control" name="total_amount" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4" id="duration_wrap" style="display:none;">
                            <label class="form-label">Duration (months)</label>
                            <input class="form-control" name="duration_months" type="number" min="1">
                        </div>
                        <div class="col-md-6" id="start_wrap" style="display:none;">
                            <label class="form-label">Start Month</label>
                            <input class="form-control" name="start_month" type="month">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" name="instructions" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  const terms = document.getElementById('terms_type');
  const durationWrap = document.getElementById('duration_wrap');
  const startWrap = document.getElementById('start_wrap');
  function sync(){
    const isMonthly = (terms && terms.value === 'monthly');
    if(durationWrap) durationWrap.style.display = isMonthly ? '' : 'none';
    if(startWrap) startWrap.style.display = isMonthly ? '' : 'none';
  }
  if(terms){
    terms.addEventListener('change', sync);
    sync();
  }
})();
</script>
