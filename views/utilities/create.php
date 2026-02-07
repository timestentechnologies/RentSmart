<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="page-header card">
        <div class="card-body">
            <h1 class="mb-0">Add New Utility</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/utilities/store" id="addUtilityForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label for="unit_id" class="form-label">Unit</label>
                    <select class="form-select" id="unit_id" name="unit_id" required>
                        <option value="">Select Unit</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= $unit['id'] ?>">
                                <?= htmlspecialchars($unit['unit_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="utility_type" class="form-label">Utility Type</label>
                    <select class="form-select" id="utility_type" name="utility_type" required>
                        <option value="">Select Utility Type</option>
                        <?php foreach ($utilityTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type['utility_type']) ?>" data-billing-method="<?= htmlspecialchars($type['billing_method']) ?>">
                                <?= htmlspecialchars($type['utility_type']) ?> (<?= $type['billing_method'] === 'metered' ? 'Metered' : 'Flat Rate' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="metered_fields" style="display:none;">
                    <div class="mb-3">
                        <label for="meter_number" class="form-label">Meter Number</label>
                        <input type="text" class="form-control" id="meter_number" name="meter_number">
                    </div>
                    <div class="mb-3">
                        <label for="previous_reading" class="form-label">Previous Reading</label>
                        <input type="number" class="form-control" id="previous_reading" name="previous_reading" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="current_reading" class="form-label">Current Reading</label>
                        <input type="number" class="form-control" id="current_reading" name="current_reading" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="units_used" class="form-label">Units Used</label>
                        <input type="number" class="form-control" id="units_used" name="units_used" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="reading_date" class="form-label">Reading Date</label>
                        <input type="date" class="form-control" id="reading_date" name="reading_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="mb-3" id="cost_group" style="display:none;">
                    <label for="cost" class="form-label">Cost (KES)</label>
                    <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0" readonly>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/utilities" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Utility</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
let utilityRates = <?php echo json_encode($utilityTypes); ?>;
function getRateAndMethod(type) {
    return utilityRates.find(t => t.utility_type === type) || null;
}
function updateFormFields() {
    const selectedType = document.getElementById('utility_type').value;
    const info = getRateAndMethod(selectedType);
    const billingMethod = info ? info.billing_method : '';
    const rate = info ? parseFloat(info.rate_per_unit) : 0;
    const meteredFields = document.getElementById('metered_fields');
    const costGroup = document.getElementById('cost_group');
    const costInput = document.getElementById('cost');
    if (billingMethod === 'metered') {
        meteredFields.style.display = '';
        costGroup.style.display = '';
        costInput.value = '';
        costInput.readOnly = true;
        calculateMeteredCost();
    } else if (billingMethod === 'flat_rate') {
        meteredFields.style.display = 'none';
        costGroup.style.display = '';
        costInput.value = rate;
        costInput.readOnly = true;
    } else {
        meteredFields.style.display = 'none';
        costGroup.style.display = 'none';
        costInput.value = '';
    }
}
document.getElementById('utility_type').addEventListener('change', function() {
    updateFormFields();
});
document.getElementById('previous_reading').addEventListener('input', calculateMeteredCost);
document.getElementById('current_reading').addEventListener('input', calculateMeteredCost);
function calculateMeteredCost() {
    const prev = parseFloat(document.getElementById('previous_reading').value) || 0;
    const curr = parseFloat(document.getElementById('current_reading').value) || 0;
    const units = curr - prev;
    document.getElementById('units_used').value = units > 0 ? units : 0;
    const selectedType = document.getElementById('utility_type').value;
    const info = getRateAndMethod(selectedType);
    const rate = info ? parseFloat(info.rate_per_unit) : 0;
    document.getElementById('cost').value = (units > 0 ? units * rate : 0).toFixed(2);
}
</script> <script>
(function(){
  try {
    const form = document.getElementById("addUtilityForm");
    const unitSelect = document.getElementById("unit_id");
    const typeSelect = document.getElementById('utility_type');
    if (!form || !unitSelect) return;

    const propWrap = document.createElement("div");
    propWrap.className = "mb-3";
    const propLabel = document.createElement("label");
    propLabel.setAttribute("for","property_id");
    propLabel.className = "form-label";
    propLabel.textContent = "Property";
    const propSelect = document.createElement("select");
    propSelect.id = "property_id";
    propSelect.name = "property_id";
    propSelect.className = "form-select";
    propSelect.required = true;

    const optDefault = document.createElement("option");
    optDefault.value = "";
    optDefault.textContent = "Select Property";
    propSelect.appendChild(optDefault);

    const properties = <?php echo json_encode($properties); ?>;
    (properties || []).forEach(function(p){
      const o = document.createElement("option");
      o.value = p.id;
      o.textContent = p.name;
      propSelect.appendChild(o);
    });

    propWrap.appendChild(propLabel);
    propWrap.appendChild(propSelect);

    const unitLabel = document.querySelector("label[for=\"unit_id\"]");
    const unitBlock = unitLabel ? unitLabel.closest(".mb-3") : (unitSelect.closest(".mb-3") || form.firstChild);
    if (unitBlock && unitBlock.parentNode) {
      unitBlock.parentNode.insertBefore(propWrap, unitBlock);
    } else {
      form.insertBefore(propWrap, form.firstChild);
    }

    unitSelect.innerHTML = "";
    const unitPlaceholder = document.createElement("option");
    unitPlaceholder.value = "";
    unitPlaceholder.textContent = "Select Property first";
    unitSelect.appendChild(unitPlaceholder);
    unitSelect.disabled = true;

    if (typeSelect) {
      typeSelect.disabled = true;
      // keep existing placeholder, remove other options
      [...typeSelect.querySelectorAll('option')].forEach((opt, idx) => {
        if (idx !== 0) opt.remove();
      });
    }

    propSelect.addEventListener("change", async function(){
      const propId = this.value;

      // Load allowed utility types for property owner
      if (typeSelect) {
        typeSelect.disabled = true;
        // keep existing placeholder, remove other options
        [...typeSelect.querySelectorAll('option')].forEach((opt, idx) => {
          if (idx !== 0) opt.remove();
        });
        document.getElementById('metered_fields').style.display = 'none';
        document.getElementById('cost_group').style.display = 'none';
        document.getElementById('cost').value = '';

        if (propId) {
          try {
            const resTypes = await fetch('<?= BASE_URL ?>/utilities/types-by-property/' + propId, {
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const typesData = await resTypes.json();
            if (typesData && typesData.success && Array.isArray(typesData.types)) {
              utilityRates = typesData.types;
              typesData.types.forEach(function(t){
                const opt = document.createElement('option');
                opt.value = t.utility_type;
                opt.textContent = t.utility_type + ' (' + (t.billing_method === 'metered' ? 'Metered' : 'Flat Rate') + ')';
                opt.dataset.billingMethod = t.billing_method;
                typeSelect.appendChild(opt);
              });
              typeSelect.disabled = false;
            } else {
              utilityRates = [];
            }
          } catch (e) {
            utilityRates = [];
          }
        } else {
          utilityRates = [];
        }
      }

      unitSelect.disabled = true;
      unitSelect.innerHTML = "";
      const loading = document.createElement("option");
      loading.value = "";
      loading.textContent = propId ? "Loading..." : "Select Property first";
      unitSelect.appendChild(loading);
      if (!propId) return;
      try {
        const res = await fetch("<?= BASE_URL ?>/properties/" + propId + "/units");
        const data = await res.json();
        unitSelect.innerHTML = "";
        if (data && data.success && Array.isArray(data.units) && data.units.length) {
          const def = document.createElement("option");
          def.value = "";
          def.textContent = "Select Unit";
          unitSelect.appendChild(def);
          data.units.forEach(function(u){
            const opt = document.createElement("option");
            opt.value = u.id;
            opt.textContent = u.unit_number || ("Unit #" + u.id);
            unitSelect.appendChild(opt);
          });
          unitSelect.disabled = false;
        } else {
          const none = document.createElement("option");
          none.value = "";
          none.textContent = "No units available";
          unitSelect.appendChild(none);
          unitSelect.disabled = true;
        }
      } catch (e) {
        unitSelect.innerHTML = "";
        const err = document.createElement("option");
        err.value = "";
        err.textContent = "Failed to load units";
        unitSelect.appendChild(err);
        unitSelect.disabled = true;
      }
    });

    // Prevent double-submit
    form.addEventListener('submit', function() {
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
      }
    });

  } catch (e) {}
})();
</script>
