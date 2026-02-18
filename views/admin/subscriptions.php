<?php
ob_start();
?>
<div class="container-fluid px-4">
    <div class="card page-header mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-calendar-check text-primary me-2"></i>Subscription Management
                    </h1>
                    <p class="text-muted mb-0 mt-1">Manage user subscriptions and plans</p>
                </div>
                
            </div>
        </div>
    </div>

<!-- Edit Subscription Modal -->
<div class="modal fade" id="editSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSubscriptionForm" action="<?= BASE_URL ?>/admin/subscriptions/update" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="subscription_id" id="editSubId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <select class="form-select" name="user_id" id="editSubUser" required>
                            <?php foreach (($users ?? []) as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name'] . ' - ' . $u['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plan</label>
                        <select class="form-select" name="plan_id" id="editSubPlan" required>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= (int)$plan['id'] ?>"><?= htmlspecialchars($plan['name']) ?> (Ksh<?= number_format($plan['price'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editSubStatus" required>
                            <option value="active">Active</option>
                            <option value="trialing">Trialing</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                            <option value="pending_verification">Pending Verification</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Period Start</label>
                            <input type="datetime-local" class="form-control" name="start_at" id="editSubStart" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Period End</label>
                            <input type="datetime-local" class="form-control" name="end_at" id="editSubEnd" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Trial Ends At (optional)</label>
                        <input type="datetime-local" class="form-control" name="trial_ends_at" id="editSubTrial">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
</div>

    <!-- Flash messages are now handled by main layout with SweetAlert2 -->

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Active Subscriptions</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($subscriptions, function($sub) {
                                return $sub['status'] === 'active';
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Currently active users</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Trial Users</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($subscriptions, function($sub) {
                                return $sub['status'] === 'trialing';
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Users in trial period</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-hourglass-split fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Expiring Soon</h6>
                        <h2 class="mt-3 mb-2">
                            <?= count(array_filter($subscriptions, function($sub) {
                                return strtotime($sub['current_period_ends_at']) <= strtotime('+7 days');
                            })) ?>
                        </h2>
                        <p class="mb-0 text-muted">Next 7 days</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-clock-history fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Plans -->
    <div class="card mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0">Subscription Plans</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">PLAN NAME</th>
                        <th class="text-muted">PRICE</th>
                        <th class="text-muted">PROPERTY LIMIT</th>
                        <th class="text-muted">LISTING LIMIT</th>
                        <th class="text-muted">DESCRIPTION</th>
                        <th class="text-muted">FEATURES</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <i class="bi bi-award fs-4"></i>
                                    </div>
                                    <?= htmlspecialchars($plan['name']) ?>
                                </div>
                            </td>
                            <td>Ksh<?= number_format($plan['price'], 2) ?></td>
                            <td>
                                <?php
                                $nameLower = strtolower($plan['name']);
                                $limit = null;
                                if (isset($plan['property_limit']) && $plan['property_limit'] !== null && $plan['property_limit'] !== '' && (int)$plan['property_limit'] > 0) {
                                    $limit = (int)$plan['property_limit'];
                                } else {
                                    if ($nameLower === 'basic') { $limit = 10; }
                                    elseif ($nameLower === 'professional') { $limit = 50; }
                                    elseif ($nameLower === 'enterprise') { $limit = null; }
                                }
                                echo $limit === null ? '<span class="badge bg-success">Unlimited</span>' : '<span class="badge bg-secondary">' . (int)$limit . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $listingLimit = null;
                                if (isset($plan['listing_limit']) && $plan['listing_limit'] !== null && $plan['listing_limit'] !== '' && (int)$plan['listing_limit'] > 0) {
                                    $listingLimit = (int)$plan['listing_limit'];
                                }
                                echo $listingLimit === null ? '<span class="badge bg-success">Unlimited</span>' : '<span class="badge bg-secondary">' . (int)$listingLimit . '</span>';
                                ?>
                            </td>
                            <td><?= htmlspecialchars($plan['description']) ?></td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach (explode("\n", $plan['features']) as $feature): ?>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars($feature) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editPlan(<?= $plan['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Subscriptions -->
    <div class="card">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0">Active Subscriptions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-muted">USER</th>
                        <th class="text-muted">PLAN</th>
                        <th class="text-muted">PROPERTIES</th>
                        <th class="text-muted">STATUS</th>
                        <th class="text-muted">START DATE</th>
                        <th class="text-muted">END DATE</th>
                        <th class="text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <i class="bi bi-person-circle fs-4"></i>
                                    </div>
                                    <?= htmlspecialchars($sub['user_name']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($sub['plan_type']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= (int)($sub['property_count'] ?? 0) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'trialing' ? 'info' : 'warning') ?>">
                                    <?= ucfirst($sub['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($sub['current_period_starts_at'])) ?></td>
                            <td><?= date('M j, Y', strtotime($sub['current_period_ends_at'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editSubscription(<?= $sub['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewSubscription(<?= $sub['id'] ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="extendSubscription(<?= $sub['id'] ?>)">
                                        <i class="bi bi-calendar-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Plan Modal -->
<div class="modal fade" id="editPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPlanForm" action="<?= BASE_URL ?>/admin/subscriptions/update-plan" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="plan_id" id="editPlanId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Plan Name</label>
                        <input type="text" class="form-control" name="name" id="editPlanName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" class="form-control" name="price" id="editPlanPrice" step="0.01" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Property Limit</label>
                                <input type="number" class="form-control" name="property_limit" id="editPlanPropertyLimit" placeholder="Unlimited if blank or 0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit Limit</label>
                                <input type="number" class="form-control" name="unit_limit" id="editPlanUnitLimit" placeholder="Unlimited if blank or 0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Listing Limit</label>
                        <input type="number" class="form-control" name="listing_limit" id="editPlanListingLimit" placeholder="Unlimited if blank or 0" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editPlanDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Features (one per line)</label>
                        <textarea class="form-control" name="features" id="editPlanFeatures" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Subscription Modal -->
<div class="modal fade" id="viewSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subscription Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="subscriptionDetails">
                <!-- Subscription details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Extend Subscription Modal -->
<div class="modal fade" id="extendSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Extend Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="extendSubscriptionForm">
                    <input type="hidden" id="extendSubscriptionId">
                    <div class="mb-3">
                        <label for="extendDays" class="form-label">Number of days to extend</label>
                        <input type="number" class="form-control" id="extendDays" name="days" value="30" min="1" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Extend</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Extend Success Modal -->
<div class="modal fade" id="extendSuccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subscription Extended</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="extendSuccessMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
function editPlan(planId) {
    // Fetch plan details and populate modal
    fetch(`<?= BASE_URL ?>/admin/subscriptions/get-plan/${planId}`)
        .then(response => response.json())
        .then(plan => {
            document.getElementById('editPlanId').value = plan.id;
            document.getElementById('editPlanName').value = plan.name;
            document.getElementById('editPlanPrice').value = plan.price;
            // Limits (may be null or undefined if columns not yet created)
            const propLimitEl = document.getElementById('editPlanPropertyLimit');
            const unitLimitEl = document.getElementById('editPlanUnitLimit');
            const listingLimitEl = document.getElementById('editPlanListingLimit');
            if (propLimitEl) { propLimitEl.value = (plan.property_limit !== null && plan.property_limit !== undefined) ? plan.property_limit : ''; }
            if (unitLimitEl) { unitLimitEl.value = (plan.unit_limit !== null && plan.unit_limit !== undefined) ? plan.unit_limit : ''; }
            if (listingLimitEl) { listingLimitEl.value = (plan.listing_limit !== null && plan.listing_limit !== undefined) ? plan.listing_limit : ''; }
            document.getElementById('editPlanDescription').value = plan.description;
            document.getElementById('editPlanFeatures').value = plan.features;
            
            new bootstrap.Modal(document.getElementById('editPlanModal')).show();
        });
}

function editSubscription(subId) {
    fetch(`<?= BASE_URL ?>/admin/subscriptions/get-subscription/${subId}`)
        .then(response => response.json())
        .then(sub => {
            document.getElementById('editSubId').value = sub.id;
            // Preselect user and plan
            const userSel = document.getElementById('editSubUser');
            if (userSel) userSel.value = sub.user_id;
            const planSel = document.getElementById('editSubPlan');
            if (planSel) planSel.value = sub.plan_id;
            const statusSel = document.getElementById('editSubStatus');
            if (statusSel) statusSel.value = sub.status;

            // Convert datetime to input value (YYYY-MM-DDTHH:MM)
            const toLocal = (s) => {
                if (!s) return '';
                const t = s.replace(' ', 'T');
                return t.length > 16 ? t.slice(0,16) : t;
            };
            document.getElementById('editSubStart').value = toLocal(sub.current_period_starts_at);
            document.getElementById('editSubEnd').value = toLocal(sub.current_period_ends_at);
            document.getElementById('editSubTrial').value = toLocal(sub.trial_ends_at);

            new bootstrap.Modal(document.getElementById('editSubscriptionModal')).show();
        });
}

function viewSubscription(subId) {
    fetch(`<?= BASE_URL ?>/admin/subscriptions/get-subscription/${subId}`)
        .then(response => response.json())
        .then(subscription => {
            let html = '<table class="table table-bordered">';
            for (const key in subscription) {
                if (subscription.hasOwnProperty(key)) {
                    html += `<tr><th>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</th><td>${subscription[key]}</td></tr>`;
                }
            }
            html += '</table>';
            document.getElementById('subscriptionDetails').innerHTML = html;
            new bootstrap.Modal(document.getElementById('viewSubscriptionModal')).show();
        });
}

let currentExtendId = null;
function extendSubscription(subId) {
    currentExtendId = subId;
    document.getElementById('extendSubscriptionId').value = subId;
    document.getElementById('extendDays').value = 30;
    new bootstrap.Modal(document.getElementById('extendSubscriptionModal')).show();
}

document.getElementById('extendSubscriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const subId = document.getElementById('extendSubscriptionId').value;
    const days = document.getElementById('extendDays').value;
    fetch(`<?= BASE_URL ?>/admin/subscriptions/extend/${subId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        },
        body: JSON.stringify({ days: days })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const msg = `Subscription successfully extended. New expiry date: <strong>${data.new_expiry}</strong>`;
            document.getElementById('extendSuccessMessage').innerHTML = msg;
            const successModal = new bootstrap.Modal(document.getElementById('extendSuccessModal'));
            successModal.show();
            // Attach reload handler to OK button
            document.querySelector('#extendSuccessModal .btn-primary').onclick = function() {
                // Hide all modals
                document.querySelectorAll('.modal.show').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
                location.reload();
            };
        } else {
            alert('Failed to extend subscription: ' + (data.message || 'Unknown error'));
        }
    });
});
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?> 