<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-kanban text-primary me-2"></i>CRM - Leads</h1>
            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAgentLeadModal">
                <i class="bi bi-plus-circle me-1"></i>Add Lead
            </button>
        </div>
    </div>

    <style>
        .crm-board { display:flex; gap: 12px; overflow-x:auto; padding-bottom: 6px; }
        .crm-col { min-width: 280px; flex: 1 0 280px; background: #f8f9fa; border: 1px solid rgba(0,0,0,.075); border-radius: 10px; }
        .crm-col-header { padding: 10px 12px; border-bottom: 1px solid rgba(0,0,0,.075); display:flex; align-items:center; justify-content:space-between; }
        .crm-accent { width: 10px; height: 10px; border-radius: 999px; display:inline-block; margin-right: 8px; }
        .crm-col-title { font-weight: 600; }
        .crm-col-body { padding: 10px 12px; min-height: 360px; }
        .lead-card { border: 1px solid rgba(0,0,0,.08); border-radius: 10px; background: #fff; padding: 10px; margin-bottom: 10px; cursor: grab; }
        .lead-card:active { cursor: grabbing; }
        .lead-card .lead-title { font-weight: 600; line-height: 1.1; }
        .lead-card .lead-sub { font-size: .875rem; color: #6c757d; }
        .crm-drop-hover { outline: 2px dashed rgba(13,110,253,.5); outline-offset: 4px; }
        .lead-tag { font-size: .75rem; }
    </style>

    <?php
        $stagesArr = [
            ['stage_key'=>'new','label'=>'New','color_class'=>'primary','is_won'=>0,'is_lost'=>0],
            ['stage_key'=>'contacted','label'=>'Contacted','color_class'=>'warning','is_won'=>0,'is_lost'=>0],
            ['stage_key'=>'qualified','label'=>'Qualified','color_class'=>'info','is_won'=>0,'is_lost'=>0],
            ['stage_key'=>'won','label'=>'Won','color_class'=>'success','is_won'=>1,'is_lost'=>0],
            ['stage_key'=>'lost','label'=>'Lost','color_class'=>'danger','is_won'=>0,'is_lost'=>1],
        ];
        $grouped = [ 'new'=>[], 'contacted'=>[], 'qualified'=>[], 'won'=>[], 'lost'=>[] ];
        foreach (($inquiries ?? []) as $x) {
            $st = strtolower((string)($x['crm_stage'] ?? 'new'));
            if (!isset($grouped[$st])) { $st = 'new'; }
            $grouped[$st][] = $x;
        }
        $accentToHex = function($c){
            $m = [
                'primary'=>'#0d6efd','secondary'=>'#6c757d','success'=>'#198754','warning'=>'#ffc107','danger'=>'#dc3545','info'=>'#0dcaf0','dark'=>'#212529'
            ];
            return $m[$c] ?? '#6c757d';
        };
    ?>

    <div class="crm-board" id="crmBoard">
        <?php foreach ($stagesArr as $stage): ?>
            <?php
                $key = strtolower((string)($stage['stage_key'] ?? 'new'));
                $label = (string)($stage['label'] ?? $key);
                $colorClass = (string)($stage['color_class'] ?? 'secondary');
                $accent = $accentToHex($colorClass);
            ?>
            <div class="crm-col" data-stage="<?= htmlspecialchars($key) ?>">
                <div class="crm-col-header">
                    <div>
                        <span class="crm-accent" style="background: <?= htmlspecialchars($accent) ?>;"></span>
                        <span class="crm-col-title"><?= htmlspecialchars($label) ?></span>
                        <span class="badge bg-light text-dark ms-2" id="count_<?= htmlspecialchars($key) ?>"><?= (int)count($grouped[$key] ?? []) ?></span>
                    </div>
                </div>
                <div class="crm-col-body" data-dropzone="1" data-stage="<?= htmlspecialchars($key) ?>">
                    <?php foreach (($grouped[$key] ?? []) as $x): ?>
                        <?php
                            $id = (int)($x['id'] ?? 0);
                            $name = (string)($x['name'] ?? '');
                            $contact = (string)($x['contact'] ?? '');
                            $propertyName = (string)($x['property_name'] ?? '');
                            $unit = (string)($x['unit_number'] ?? '');
                            $message = (string)($x['message'] ?? '');
                        ?>
                        <div class="lead-card" draggable="true" data-id="<?= $id ?>" data-stage="<?= htmlspecialchars($key) ?>">
                            <div class="lead-title"><?= htmlspecialchars($name) ?></div>
                            <div class="lead-sub mt-1"><?= htmlspecialchars($contact) ?></div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <?php if ($propertyName !== ''): ?>
                                    <span class="badge bg-light text-dark lead-tag"><?= htmlspecialchars($propertyName) ?></span>
                                <?php endif; ?>
                                <?php if ($unit !== ''): ?>
                                    <span class="badge bg-light text-dark lead-tag">Unit <?= htmlspecialchars($unit) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($message !== ''): ?>
                                <div class="lead-sub mt-2" style="white-space: pre-wrap;"><?= htmlspecialchars($message) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addAgentLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_URL ?>/agent/leads/store">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="modal-header">
          <h5 class="modal-title">Add Lead</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Property</label>
              <select class="form-select" name="property_id" id="agent_lead_property" required>
                <option value="">Select property</option>
                <?php foreach (($properties ?? []) as $p): ?>
                  <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unit (optional)</label>
              <select class="form-select" name="unit_id" id="agent_lead_unit">
                <option value="">Select unit</option>
                <?php foreach (($units ?? []) as $u): ?>
                  <option value="<?= (int)($u['id'] ?? 0) ?>" data-property-id="<?= (int)($u['property_id'] ?? 0) ?>">
                    <?= htmlspecialchars((string)($u['property_name'] ?? '')) ?> - Unit <?= htmlspecialchars((string)($u['unit_number'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Units list is filtered by selected property.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact</label>
              <input class="form-control" name="contact" required>
            </div>
            <div class="col-12">
              <label class="form-label">Message/Notes</label>
              <textarea class="form-control" name="message" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Lead</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  let draggedId = null;

  const propertySel = document.getElementById('agent_lead_property');
  const unitSel = document.getElementById('agent_lead_unit');
  function filterUnits(){
    if(!propertySel || !unitSel) return;
    const pid = propertySel.value;
    Array.from(unitSel.options).forEach(opt=>{
      if(!opt.value) return;
      const ok = !pid || opt.getAttribute('data-property-id') === pid;
      opt.hidden = !ok;
    });
    if(unitSel.selectedOptions.length && unitSel.selectedOptions[0].hidden){
      unitSel.value = '';
    }
  }
  if(propertySel){
    propertySel.addEventListener('change', filterUnits);
    filterUnits();
  }

  function recomputeCounts(){
    document.querySelectorAll('.crm-col[data-stage]').forEach(col=>{
      const k = col.getAttribute('data-stage');
      const count = col.querySelectorAll('.crm-col-body .lead-card').length;
      const badge = document.getElementById('count_' + k);
      if(badge) badge.textContent = String(count);
    });
  }

  document.querySelectorAll('.lead-card').forEach(card=>{
    card.addEventListener('dragstart', ()=>{ draggedId = card.getAttribute('data-id'); });
  });

  document.querySelectorAll('[data-dropzone="1"]').forEach(zone=>{
    zone.addEventListener('dragover', (e)=>{ e.preventDefault(); zone.classList.add('crm-drop-hover'); });
    zone.addEventListener('dragleave', ()=> zone.classList.remove('crm-drop-hover'));
    zone.addEventListener('drop', async (e)=>{
      e.preventDefault();
      zone.classList.remove('crm-drop-hover');
      const stage = zone.getAttribute('data-stage');
      if(!draggedId || !stage) return;
      const card = document.querySelector('.lead-card[data-id="' + draggedId + '"]');
      if(!card) return;
      zone.appendChild(card);
      recomputeCounts();

      const fd = new FormData();
      fd.append('csrf_token', (document.querySelector('meta[name="csrf-token"]')||{}).content || '');
      fd.append('stage', stage);

      try {
        const res = await fetch('<?= BASE_URL ?>/agent/leads/update-stage/' + draggedId, { method: 'POST', body: fd });
        const data = await res.json();
        if(!data || !data.success){
          throw new Error(data && data.message ? data.message : 'Failed');
        }
      } catch (err){
        location.reload();
      }
    });
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
