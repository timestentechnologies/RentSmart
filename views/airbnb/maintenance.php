<?php
ob_start();
$siteName = $siteName ?? 'RentSmart';
$title = $title ?? 'Airbnb Maintenance - RentSmart';
?>
<style>
        :root {
            --brand-purple: #6B3E99;
            --brand-orange: #FF8A00;
            --brand-orange-light: #ffaa4d;
        }
        
        .btn-brand {
            background: linear-gradient(135deg, var(--brand-purple) 0%, #4a2c6b 100%);
            border: none;
            color: white;
        }
        .btn-brand:hover {
            background: linear-gradient(135deg, #5a3285 0%, #3d2258 100%);
            color: white;
        }
        
        .btn-orange {
            background: var(--brand-orange);
            border: none;
            color: white;
        }
        .btn-orange:hover {
            background: var(--brand-orange-light);
            color: white;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--brand-purple);
        }
        
        .stat-card.maintenance-pending { border-left-color: #ffc107; }
        .stat-card.maintenance-progress { border-left-color: #17a2b8; }
        .stat-card.maintenance-completed { border-left-color: #28a745; }
        
        .maintenance-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .maintenance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-urgent { background: #dc3545; color: white; }
        .priority-high { background: #fd7e14; color: white; }
        .priority-medium { background: #ffc107; color: #212529; }
        .priority-low { background: #6c757d; color: white; }
        
        .wallet-balance {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-option:hover {
            border-color: var(--brand-orange);
            background: #fff8f0;
        }
        
        .payment-option.selected {
            border-color: var(--brand-orange);
            background: #fff8f0;
            box-shadow: 0 0 0 3px rgba(255,138,0,0.2);
        }
        
        .expense-flow-panel {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .category-plumbing { background: #e3f2fd; color: #1976d2; }
        .category-electrical { background: #fff3e0; color: #f57c00; }
        .category-hvac { background: #e8f5e9; color: #388e3c; }
        .category-appliance { background: #f3e5f5; color: #7b1fa2; }
        .category-cleaning { background: #e0f2f1; color: #00695c; }
        .category-other { background: #f5f5f5; color: #616161; }
    </style>

<div class="container-fluid pt-4">
    <!-- Page Header -->
    <div class="card page-header border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-tools text-warning me-2"></i>Airbnb Maintenance</h1>
                <p class="text-muted mb-0 small">Manage maintenance requests for your Airbnb properties</p>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="badge bg-success p-2">
                    <i class="bi bi-wallet2 me-1"></i>
                    Wallet: KES <?= number_format($walletBalance ?? 0, 2) ?>
                </div>
                <a href="<?= BASE_URL ?>/maintenance/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> New Request
                </a>
            </div>
        </div>
    </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Total Requests</h6>
                            <h3 class="mb-0"><?= $statistics['total_requests'] ?? 0 ?></h3>
                        </div>
                        <div class="fs-1 text-primary opacity-25"><i class="bi bi-tools"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card maintenance-pending">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Pending</h6>
                            <h3 class="mb-0"><?= $statistics['pending_requests'] ?? 0 ?></h3>
                        </div>
                        <div class="fs-1 text-warning opacity-25"><i class="bi bi-clock"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card maintenance-progress">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">In Progress</h6>
                            <h3 class="mb-0"><?= $statistics['in_progress_requests'] ?? 0 ?></h3>
                        </div>
                        <div class="fs-1 text-info opacity-25"><i class="bi bi-gear"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card maintenance-completed">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Completed</h6>
                            <h3 class="mb-0"><?= $statistics['completed_requests'] ?? 0 ?></h3>
                        </div>
                        <div class="fs-1 text-success opacity-25"><i class="bi bi-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Requests List -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($requests)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-tools fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No maintenance requests found</h5>
                        <p class="text-muted">Maintenance requests for your Airbnb properties will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="maintenance-card" data-request-id="<?= $request['id'] ?>">
                            <div class="row align-items-start">
                                <div class="col-auto">
                                    <div class="category-icon category-<?= $request['category'] ?? 'other' ?>">
                                        <i class="bi <?= getMaintenanceIcon($request['category'] ?? 'other') ?>"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($request['title'] ?? 'Untitled Request') ?></h5>
                                            <p class="text-muted mb-0 small">
                                                <i class="bi bi-building me-1"></i><?= htmlspecialchars($request['property_name'] ?? 'Unknown Property') ?>
                                                <?php if ($request['unit_number']): ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="bi bi-door-closed me-1"></i>Unit <?= htmlspecialchars($request['unit_number']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="status-badge status-<?= $request['status'] ?? 'pending' ?>">
                                                <?= ucwords(str_replace('_', ' ', $request['status'] ?? 'pending')) ?>
                                            </span>
                                            <div class="mt-1">
                                                <span class="priority-badge priority-<?= $request['priority'] ?? 'medium' ?>">
                                                    <?= strtoupper($request['priority'] ?? 'MEDIUM') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2"><?= htmlspecialchars($request['description'] ?? '') ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($request['tenant_name'] ?? 'Unknown') ?>
                                            <span class="mx-2">|</span>
                                            <i class="bi bi-calendar me-1"></i><?= date('M d, Y', strtotime($request['requested_date'] ?? 'now')) ?>
                                            <?php if ($request['actual_cost']): ?>
                                                <span class="mx-2">|</span>
                                                <i class="bi bi-cash me-1"></i>KES <?= number_format($request['actual_cost'], 2) ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($request['status'] !== 'completed' && $request['status'] !== 'cancelled'): ?>
                                            <button class="btn btn-sm btn-orange" onclick="openUpdateModal(<?= $request['id'] ?>)">
                                                <i class="bi bi-pencil me-1"></i>Update
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Update Maintenance Modal -->
    <div class="modal fade" id="updateMaintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateMaintenanceForm" method="POST" action="<?= BASE_URL ?>/airbnb/maintenance/update-status">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="requestId">
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="statusSelect" required>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="prioritySelect">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assigned To</label>
                                <input type="text" class="form-control" name="assigned_to" id="assignedToInput" placeholder="Name of technician">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Scheduled Date</label>
                                <input type="date" class="form-control" name="scheduled_date" id="scheduledDateInput">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estimated Cost (KES)</label>
                                <input type="number" class="form-control" name="estimated_cost" id="estimatedCostInput" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Actual Cost (KES)</label>
                                <input type="number" class="form-control" name="actual_cost" id="actualCostInput" step="0.01" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="notesInput" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Expense Flow Panel - Only show when completing -->
                        <div id="expenseFlowPanel" class="expense-flow-panel d-none">
                            <h6 class="mb-3"><i class="bi bi-cash-coin me-2"></i>How would you like to handle this expense?</h6>
                            
                            <div class="row g-3">
                                <!-- Option 1: Pay from Wallet -->
                                <div class="col-md-4">
                                    <div class="payment-option" data-option="wallet" onclick="selectPaymentOption('wallet')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_source" value="wallet" id="payWallet">
                                            <label class="form-check-label fw-bold" for="payWallet">
                                                <i class="bi bi-wallet2 me-2 text-success"></i>Pay from Wallet
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">Deduct from your wallet balance</p>
                                        <p class="small text-success mb-0">Available: KES <?= number_format($walletBalance ?? 0, 2) ?></p>
                                    </div>
                                </div>
                                
                                <!-- Option 2: Owner Pays -->
                                <div class="col-md-4">
                                    <div class="payment-option selected" data-option="owner" onclick="selectPaymentOption('owner')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_source" value="owner_funds" id="payOwner" checked>
                                            <label class="form-check-label fw-bold" for="payOwner">
                                                <i class="bi bi-person me-2 text-primary"></i>I Will Pay
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">Record as expense paid by you</p>
                                        <p class="small text-primary mb-0">Cash/Bank/M-Pesa</p>
                                    </div>
                                </div>
                                
                                <!-- Option 3: Bill to Client -->
                                <div class="col-md-4">
                                    <div class="payment-option" data-option="client" onclick="selectPaymentOption('client')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_source" value="client" id="billClient">
                                            <label class="form-check-label fw-bold" for="billClient">
                                                <i class="bi bi-person-lines-fill me-2 text-warning"></i>Bill Guest
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">Add charge to guest's bill</p>
                                        <p class="small text-warning mb-0">Guest pays on checkout</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="bill_to_client" id="billToClient" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-orange">
                            <i class="bi bi-check-lg me-1"></i>Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const updateModal = new bootstrap.Modal(document.getElementById('updateMaintenanceModal'));
        const expenseFlowPanel = document.getElementById('expenseFlowPanel');
        const statusSelect = document.getElementById('statusSelect');
        const walletBalance = <?= $walletBalance ?? 0 ?>;
        
        // Show/hide expense flow panel based on status
        statusSelect.addEventListener('change', function() {
            if (this.value === 'completed') {
                expenseFlowPanel.classList.remove('d-none');
            } else {
                expenseFlowPanel.classList.add('d-none');
            }
        });
        
        // Payment option selection
        function selectPaymentOption(option) {
            document.querySelectorAll('.payment-option').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('input[type="radio"]').checked = false;
            });
            
            const selectedOption = document.querySelector(`[data-option="${option}"]`);
            selectedOption.classList.add('selected');
            selectedOption.querySelector('input[type="radio"]').checked = true;
            
            // Set bill_to_client flag
            document.getElementById('billToClient').value = option === 'client' ? '1' : '0';
        }
        
        // Open update modal with request data
        function openUpdateModal(requestId) {
            // Reset form
            document.getElementById('updateMaintenanceForm').reset();
            document.getElementById('requestId').value = requestId;
            expenseFlowPanel.classList.add('d-none');
            
            // Fetch request details via AJAX
            fetch(`<?= BASE_URL ?>/maintenance/get/${requestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const r = data.request;
                        document.getElementById('statusSelect').value = r.status;
                        document.getElementById('prioritySelect').value = r.priority;
                        document.getElementById('assignedToInput').value = r.assigned_to || '';
                        document.getElementById('scheduledDateInput').value = r.scheduled_date || '';
                        document.getElementById('estimatedCostInput').value = r.estimated_cost || '';
                        document.getElementById('actualCostInput').value = r.actual_cost || '';
                        document.getElementById('notesInput').value = r.notes || '';
                        
                        // Show expense panel if already completed
                        if (r.status === 'completed') {
                            expenseFlowPanel.classList.remove('d-none');
                        }
                    }
                });
            
            updateModal.show();
        }
        
        // Form submission
        document.getElementById('updateMaintenanceForm').addEventListener('submit', function(e) {
            const actualCost = parseFloat(document.getElementById('actualCostInput').value) || 0;
            const paymentSource = document.querySelector('input[name="payment_source"]:checked')?.value;
            
            // Validate wallet balance if paying from wallet
            if (statusSelect.value === 'completed' && actualCost > 0 && paymentSource === 'wallet') {
                if (actualCost > walletBalance) {
                    e.preventDefault();
                    alert('Insufficient wallet balance. Please choose another payment option or add funds to your wallet.');
                    return false;
                }
            }
            
            return true;
        });
        
        // Helper function to get icon class
        function getMaintenanceIcon(category) {
            const icons = {
                'plumbing': 'bi-droplet',
                'electrical': 'bi-lightning-charge',
                'hvac': 'bi-thermometer-half',
                'appliance': 'bi-tv',
                'structural': 'bi-building',
                'pest_control': 'bi-bug',
                'cleaning': 'bi-bucket',
                'other': 'bi-tools',
                'maintenance': 'bi-tools'
            };
            return icons[category] || 'bi-tools';
        }
    </script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';

// Helper function for maintenance icons
function getMaintenanceIcon($category) {
    $icons = [
        'plumbing' => 'bi-droplet',
        'electrical' => 'bi-lightning-charge',
        'hvac' => 'bi-thermometer-half',
        'appliance' => 'bi-tv',
        'structural' => 'bi-building',
        'pest_control' => 'bi-bug',
        'cleaning' => 'bi-bucket',
        'other' => 'bi-tools',
        'maintenance' => 'bi-tools'
    ];
    return $icons[$category] ?? 'bi-tools';
}
?>
