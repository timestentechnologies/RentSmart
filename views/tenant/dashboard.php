<!DOCTYPE html>
<html>
<head>
    <?php
    $siteName = isset($settings['site_name']) && $settings['site_name'] ? $settings['site_name'] : 'RentSmart';
    $pageTitle = isset($property['name']) ? htmlspecialchars($property['name']) . ' | ' . htmlspecialchars($siteName) : htmlspecialchars($siteName);
    ?>
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($siteFavicon) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(90deg, #f8fafc 60%, #e9ecef 100%);
            padding: 2.5rem 1rem 2rem 1rem;
            border-radius: 0 0 1.5rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .dashboard-logo {
            max-height: 60px;
            margin-bottom: 1rem;
        }
        .dashboard-cards {
            gap: 1.5rem;
        }
        .card-icon {
            font-size: 2rem;
            color: #0d6efd;
            margin-right: 0.5rem;
        }
        
        /* Ensure submit button is clickable */
        #submitMaintenanceBtn {
            pointer-events: auto !important;
            cursor: pointer !important;
            opacity: 1 !important;
        }
        
        #submitMaintenanceBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <!-- Hero Section with Logo -->
    <div class="hero-section text-center mb-4 position-relative overflow-hidden" style="min-height: 320px;">
        <!-- Decorative SVG background -->
        <svg viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg" style="position:absolute;left:0;top:0;width:100%;height:100%;z-index:0;">
            <defs>
                <linearGradient id="heroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#e9ecef"/>
                    <stop offset="100%" stop-color="#f8fafc"/>
                </linearGradient>
            </defs>
            <rect width="100%" height="100%" fill="url(#heroGradient)"/>
            <path fill="#e3f2fd" fill-opacity="0.5" d="M0,160L60,170.7C120,181,240,203,360,197.3C480,192,600,160,720,133.3C840,107,960,85,1080,101.3C1200,117,1320,171,1380,197.3L1440,224L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
        </svg>
        <!-- Logo -->
        <div class="d-flex flex-column align-items-center justify-content-center position-relative" style="z-index:1;">
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Site Logo" class="dashboard-logo mb-3" style="max-height:70px;max-width:220px;object-fit:contain;">
            <h1 class="fw-bold mb-2" style="font-size:2.5rem;">Welcome, <?php echo htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']); ?></h1>
            <p class="lead mb-0">Your personal tenant portal for managing your property, lease, payments, and utilities.</p>
        </div>
        <a href="<?= BASE_URL ?>/tenant/logout" class="btn btn-outline-secondary position-absolute end-0 top-0 m-4" style="z-index:2;">Logout</a>
    </div>

    <!-- Dashboard Info Cards -->
    <div class="dashboard-cards-container mb-4">
        <!-- Property Card -->
        <div class="dashboard-card">
            <div class="card info-card h-100 property-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="info-icon me-3">
                            <i class="bi bi-house-door text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark fw-bold">Property</h5>
                    </div>
                    <?php if ($property): ?>
                        <div class="info-item mb-2">
                            <span class="info-label fw-bold">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($property['name']); ?></span>
                        </div>
                        <div class="info-item mb-0">
                            <span class="info-label fw-bold">Address:</span>
                            <span class="info-value"><?php echo htmlspecialchars($property['address']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="info-item mb-0">
                            <span class="info-value text-muted">No property assigned.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Unit Card -->
        <div class="dashboard-card">
            <div class="card info-card h-100 unit-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="info-icon me-3">
                            <i class="bi bi-door-closed text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark fw-bold">Unit</h5>
                    </div>
                    <?php if ($unit): ?>
                        <div class="info-item mb-2">
                            <span class="info-label fw-bold">Unit Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($unit['unit_number']); ?></span>
                        </div>
                        <?php 
                        // Get meter numbers from utilities
                        $meterNumbers = [];
                        if (!empty($utilities)) {
                            foreach ($utilities as $utility) {
                                if ($utility['is_metered'] && !empty($utility['meter_number'])) {
                                    $meterNumbers[] = ucfirst($utility['utility_type']) . ': ' . $utility['meter_number'];
                                }
                            }
                        }
                        ?>
                        <div class="info-item mb-0">
                            <span class="info-label fw-bold">Meter Number:</span>
                            <span class="info-value">
                                <?php if (!empty($meterNumbers)): ?>
                                    <?php echo implode(', ', $meterNumbers); ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="info-item mb-0">
                            <span class="info-value text-muted">Not assigned to a unit.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Lease Card -->
        <div class="dashboard-card">
            <div class="card info-card h-100 lease-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="info-icon me-3">
                            <i class="bi bi-file-earmark-text text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark fw-bold">Lease</h5>
                    </div>
                    <?php if (isset($lease) && $lease): ?>
                        <div class="info-item mb-2">
                            <span class="info-label fw-bold">Status:</span>
                            <span class="info-value">
                                <span class="badge bg-<?php echo $lease['status'] === 'active' ? 'success' : 'secondary'; ?> rounded-pill px-3 py-1">
                                    <?php echo ucfirst($lease['status']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item mb-2">
                            <span class="info-label fw-bold">Start:</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['start_date']); ?></span>
                        </div>
                        <div class="info-item mb-2">
                            <span class="info-label fw-bold">End:</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['end_date']); ?></span>
                        </div>
                        <div class="info-item mb-0">
                            <span class="info-label fw-bold">Rent:</span>
                            <span class="info-value">Ksh <?php echo number_format($lease['rent_amount'], 2); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="info-item mb-0">
                            <span class="info-value text-muted">No active lease.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Latest Notices</h5>
                    <a href="<?= BASE_URL ?>/tenant/notices" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body">
                    <?php if (empty($tenantNotices)): ?>
                        <div class="text-muted">No notices available.</div>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach (array_slice($tenantNotices, 0, 5) as $n): ?>
                                <li class="mb-3">
                                    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#tenantNoticeModal"
                                       data-title="<?= htmlspecialchars($n['title'] ?? 'Notice', ENT_QUOTES) ?>"
                                       data-created="<?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at'] ?? 'now')), ENT_QUOTES) ?>"
                                       data-body="<?= htmlspecialchars($n['body'] ?? '', ENT_QUOTES) ?>"
                                       data-property="<?= htmlspecialchars($n['property_name'] ?? '', ENT_QUOTES) ?>"
                                       data-unit="<?= htmlspecialchars($n['unit_number'] ?? '', ENT_QUOTES) ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($n['title'] ?? 'Notice') ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at'] ?? 'now'))) ?></div>
                                        <div class="text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;">
                                            <?= htmlspecialchars(mb_strimwidth(strip_tags($n['body'] ?? ''), 0, 120, '…', 'UTF-8')) ?>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Recent Messages</h5>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/tenant/messaging" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                </div>
                <div class="card-body" style="padding:12px;">
                    <?php
                      $replyUserId = null;
                      if (!empty($property)) {
                          foreach (['owner_id','manager_id','agent_id','caretaker_user_id'] as $k) {
                              if (!empty($property[$k])) { $replyUserId = (int)$property[$k]; break; }
                          }
                      }
                    ?>
                    <?php if (empty($tenantMessages)): ?>
                        <div class="text-muted">No messages yet.</div>
                    <?php else: ?>
                        <div id="tenantRecentMessagesScroll" style="height: 220px; overflow-y: auto; background: #f4f6fb; border-radius: 10px; padding: 10px;">
                            <?php foreach (array_reverse(array_slice($tenantMessages, 0, 5)) as $m): ?>
                                <?php $mine = (($m['sender_type'] ?? '') === 'tenant'); ?>
                                <div class="d-flex mb-2 <?= $mine ? 'justify-content-end' : 'justify-content-start' ?>">
                                    <div style="max-width: 85%;">
                                        <div class="px-3 py-2" style="border-radius:14px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;<?= $mine ? 'background:#0d6efd;color:#fff;border-bottom-right-radius:6px;' : 'background:#fff;border:1px solid rgba(0,0,0,.08);border-bottom-left-radius:6px;' ?>">
                                            <?= htmlspecialchars(mb_strimwidth(trim(preg_replace('/\s+/', ' ', $m['body'] ?? '')), 0, 120, '…', 'UTF-8')) ?>
                                        </div>
                                        <div class="small" style="opacity:.75;<?= $mine ? 'text-align:right;color:#0d6efd;' : 'color:#6c757d;' ?>">
                                            <?= $mine ? 'You' : 'Management' ?> • <?= htmlspecialchars(date('M j, g:i A', strtotime($m['created_at'] ?? 'now'))) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-2">
                        <?php if (empty($replyUserId)): ?>
                            <div class="small text-muted">No management contact found for this property.</div>
                        <?php else: ?>
                            <form id="tenantRecentReplyForm" class="d-flex gap-2 align-items-end">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$replyUserId ?>">
                                <input type="text" name="body" id="tenantRecentReplyBody" class="form-control" placeholder="Type a reply…" autocomplete="off">
                                <button class="btn btn-success" type="submit" id="tenantRecentReplySendBtn">Send</button>
                            </form>
                            <div class="small text-danger mt-1" id="tenantRecentReplyError" style="display:none;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Make Payment</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Rent Payment -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 payment-card" style="border-left: 4px solid #28a745 !important;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="card-title text-dark mb-1">Rent Payment</h6>
                                            <small class="text-muted">Monthly rent payment</small>
                                        </div>
                                        <div class="text-success">
                                            <i class="bi bi-house-door fs-4"></i>
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($lease) && $lease): ?>
                                        <?php 
                                        $missedTotal = 0;
                                        if (!empty($missedRentMonths)) {
                                            foreach ($missedRentMonths as $mm) {
                                                $missedTotal += max(0, $mm['amount'] ?? 0);
                                            }
                                        }
                                        $dueNowFlag = isset($rentCoverage['due_now']) ? (bool)$rentCoverage['due_now'] : true;
                                        $nextDueLabel = isset($rentCoverage['next_due_label']) ? $rentCoverage['next_due_label'] : null;
                                        $totalRentAmount = $missedTotal;
                                        ?>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small">Monthly Rent:</span>
                                                <strong class="text-dark">Ksh <?= number_format($lease['rent_amount'], 2) ?></strong>
                                            </div>
                                            
                                            <?php if ($missedTotal > 0): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: #f8d7da;">
                                                    <span class="text-danger small fw-bold">Overdue:</span>
                                                    <strong class="text-danger">Ksh <?= number_format($missedTotal, 2) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <?php if (!$dueNowFlag && $nextDueLabel): ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: #d1edff;">
                                                        <span class="text-success small">Next Due:</span>
                                                        <strong class="text-success"><?= htmlspecialchars($nextDueLabel) ?></strong>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: #d1edff;">
                                                        <span class="text-success small">Overdue:</span>
                                                        <strong class="text-success">Ksh 0.00</strong>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background-color: #e8f5e8;">
                                                <span class="text-dark small fw-bold">Total Due:</span>
                                                <strong class="text-dark fs-6">Ksh <?= number_format($totalRentAmount, 2) ?></strong>
                                            </div>
                                        </div>
                                        
                            <?php if ($totalRentAmount > 0): ?>
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="openPaymentModal('rent', <?= htmlspecialchars(json_encode($paymentMethods)) ?>)">
                                    <i class="bi bi-credit-card me-1"></i>Pay Rent
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-success btn-sm w-100" onclick="openPaymentModal('rent_advance', <?= htmlspecialchars(json_encode($paymentMethods)) ?>)">
                                    <i class="bi bi-calendar-plus me-1"></i>Pay in Advance
                                </button>
                            <?php endif; ?>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <p class="text-muted small mb-0">No active lease</p>
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                                            <i class="bi bi-credit-card me-1"></i>Pay Rent
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Utilities Payment -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 payment-card" style="border-left: 4px solid #17a2b8 !important;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="card-title text-dark mb-1">Utilities Payment</h6>
                                            <small class="text-muted">Electricity, water, etc.</small>
                                        </div>
                                        <div class="text-info">
                                            <i class="bi bi-lightning-charge fs-4"></i>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($utilities)): ?>
                                        <?php 
                                        $totalUtilities = 0;
                                        $utilitiesByType = [];
                                        
                                        // Group utilities by type and calculate amounts
                                        foreach ($utilities as $utility) {
                                            $type = $utility['utility_type'];
                                            $netAmount = max(0, $utility['net_amount'] ?? $utility['amount'] ?? 0);
                                            
                                            if (!isset($utilitiesByType[$type])) {
                                                $utilitiesByType[$type] = [
                                                    'amount' => 0,
                                                    'meter_number' => $utility['meter_number'],
                                                    'is_metered' => $utility['is_metered'],
                                                    'flat_rate' => $utility['flat_rate']
                                                ];
                                            }
                                            $utilitiesByType[$type]['amount'] += $netAmount;
                                            $totalUtilities += $netAmount;
                                        }
                                        ?>
                                        
                                        <div class="mb-3">
                                            <?php foreach ($utilitiesByType as $type => $utility): ?>
                                                <?php if ($utility['amount'] > 0): ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: #f8f9fa;">
                                                        <div>
                                                            <span class="text-dark small fw-bold text-capitalize"><?= ucfirst($type) ?></span>
                                                            <?php if ($utility['is_metered'] && $utility['meter_number']): ?>
                                                                <div class="text-muted small">Meter: <?= htmlspecialchars($utility['meter_number']) ?></div>
                                                            <?php elseif (!$utility['is_metered'] && $utility['flat_rate']): ?>
                                                                <div class="text-muted small">Flat Rate</div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <strong class="text-info">Ksh <?= number_format($utility['amount'], 2) ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            
                                            <?php if ($totalUtilities > 0): ?>
                                                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background-color: #d1edff;">
                                                    <span class="text-info small fw-bold">Total Due:</span>
                                                    <strong class="text-info fs-6">Ksh <?= number_format($totalUtilities, 2) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($totalUtilities > 0): ?>
                                            <button type="button" class="btn btn-info btn-sm w-100" onclick="openPaymentModal('utility', <?= htmlspecialchars(json_encode($paymentMethods)) ?>)">
                                                <i class="bi bi-credit-card me-1"></i>Pay Utilities
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-check-circle me-1"></i>All Utilities Paid
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: #d1edff;">
                                                <span class="text-muted small">Total Due:</span>
                                                <strong class="text-muted">Ksh 0.00</strong>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                                            <i class="bi bi-credit-card me-1"></i>Pay Utilities
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Due -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 payment-card" style="border-left: 4px solid #ffc107 !important;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="card-title text-dark mb-1">Maintenance Due</h6>
                                            <small class="text-muted">Cost billed to you</small>
                                        </div>
                                        <div class="text-warning">
                                            <i class="bi bi-tools fs-4"></i>
                                        </div>
                                    </div>

                                    <?php $maintDue = (float)($maintenanceOutstanding ?? 0); ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background-color:#fff3cd;">
                                            <span class="text-muted small">Total Due:</span>
                                            <strong class="text-dark fs-6">Ksh <?= number_format($maintDue, 2) ?></strong>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-warning btn-sm w-100" <?= $maintDue > 0 ? '' : 'disabled' ?>
                                            onclick="openPaymentModal('maintenance', <?= htmlspecialchars(json_encode($paymentMethods)) ?>)">
                                        <i class="bi bi-credit-card me-1"></i>Pay Maintenance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                    // Combined totals for Rent + Utilities using missed months and coverage
                    $missedTotalCombined = 0; 
                    if (isset($lease) && $lease) {
                        if (!empty($missedRentMonths)) {
                            foreach ($missedRentMonths as $mm) {
                                $missedTotalCombined += max(0, $mm['amount'] ?? 0);
                            }
                        }
                    }
                    $dueNowFlagCombined = isset($rentCoverage['due_now']) ? (bool)$rentCoverage['due_now'] : true;
                    $totalRentAmountCombined = $dueNowFlagCombined ? $missedTotalCombined : 0;
                    $totalUtilitiesCombined = 0;
                    if (!empty($utilities)) {
                        foreach ($utilities as $u) {
                            $net = max(0, $u['net_amount'] ?? ($u['amount'] ?? 0));
                            $totalUtilitiesCombined += $net;
                        }
                    }
                    $grandTotalCombined = $totalRentAmountCombined + $totalUtilitiesCombined;
                    ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold">Total Rent + Utilities Due</span>
                            </div>
                            <div>
                                <strong class="fs-6">Ksh <?= number_format($grandTotalCombined, 2) ?></strong>
                            </div>
                        </div>
                        <?php $grandTotalAll = $grandTotalCombined + (float)($maintenanceOutstanding ?? 0); ?>
                        <button type="button" class="btn btn-warning btn-sm w-100" <?= $grandTotalAll > 0 ? '' : 'disabled' ?>
                                onclick="openPaymentModal('all', <?= htmlspecialchars(json_encode($paymentMethods)) ?>)">
                            <i class="bi bi-credit-card me-1"></i>Pay All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Due Rent Card -->
    <?php if (!empty($overdueRent)): ?>
    <div class="alert alert-danger mb-4">
        <strong>Lease Agreement:</strong>
        <?php foreach ($overdueRent as $overdue): ?>
            <div>
                Lease Period: <?php echo htmlspecialchars($overdue['start_date']); ?> - <?php echo htmlspecialchars($overdue['end_date']); ?>
            </div>
        <?php endforeach; ?>
        <?php if (!empty($missedRentMonths)): ?>
            <div class="mt-2">
                <div class="fw-bold">Missed Months:</div>
                <?php foreach ($missedRentMonths as $mm): ?>
                    <div>
                        Month: <?php echo htmlspecialchars($mm['label']); ?> |
                        Amount: <span class="fw-bold text-danger">Ksh <?php echo number_format($mm['amount'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Payments Table -->
    <div class="card mb-4">
        <div class="card-header fw-bold">Payment History</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>M-Pesa Code</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($rentPayments)): ?>
                        <?php foreach ($rentPayments as $payment): ?>
                            <?php
                                $rawType = strtolower((string)($payment['payment_type'] ?? 'rent'));
                                $notes = (string)($payment['notes'] ?? '');
                                $amount = (float)($payment['amount'] ?? 0);
                                $isMaintChargeRow = ($rawType === 'rent' && $amount < 0 && $notes !== '' && preg_match('/MAINT-\d+/i', $notes));
                                if ($isMaintChargeRow) {
                                    continue;
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td>Ksh <?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    $hasUtility = !empty($payment['utility_id']) || !empty($payment['utility_type']);
                                    $isUtilByNotes = ($notes !== '' && preg_match('/\b(util|utility|water|electricity|gas|internet)\b/i', $notes));

                                    // Normalize type to avoid everything showing as rent
                                    if ($rawType === 'other' && ($notes !== '' && (stripos($notes, 'Maintenance payment:') !== false || preg_match('/MAINT-\d+/i', $notes)))) {
                                        $typeClass = 'bg-warning text-dark';
                                        $typeText = 'Maintenance';
                                    } elseif ($rawType === 'utility' || $hasUtility || $isUtilByNotes) {
                                        $typeClass = 'bg-info';
                                        $typeText = !empty($payment['utility_type']) ? ucfirst((string)$payment['utility_type']) : 'Utility';
                                    } else {
                                        $typeClass = 'bg-success';
                                        $typeText = 'Rent';
                                    }
                                    ?>
                                    <span class="badge <?php echo $typeClass; ?>"><?php echo $typeText; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($payment['transaction_code'])): ?>
                                        <code class="text-primary"><?php echo htmlspecialchars($payment['transaction_code']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($payment['phone_number'])): ?>
                                        <span class="text-dark"><?php echo htmlspecialchars($payment['phone_number']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status = $payment['status'] ?? 'completed';
                                    $statusClass = 'bg-success';
                                    $statusText = 'Paid';
                                    
                                    if ($status === 'pending_verification') {
                                        $statusClass = 'bg-warning';
                                        $statusText = 'Pending';
                                    } elseif ($status === 'failed') {
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Failed';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/tenant/payment/receipt/<?php echo urlencode($payment['id']); ?>" class="btn btn-sm btn-primary" target="_blank">PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No payments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Utilities Table -->
    <div class="card mb-4">
        <div class="card-header fw-bold">Utilities</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Meter Number</th>
                            <th>Current Reading</th>
                            <th>Previous Reading</th>
                            <th>Amount Due</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($utilities)): ?>
                        <?php foreach ($utilities as $utility): ?>
                            <tr<?php if (isset($utility['net_amount']) && $utility['net_amount'] > 0) echo ' class="table-danger"'; ?>>
                                <td>
                                    <span class="badge bg-<?php echo $utility['utility_type'] === 'electricity' ? 'warning' : ($utility['utility_type'] === 'water' ? 'info' : 'secondary'); ?> text-dark">
                                        <?php echo ucfirst($utility['utility_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($utility['is_metered'] && !empty($utility['meter_number'])): ?>
                                        <span class="fw-bold"><?php echo htmlspecialchars($utility['meter_number']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Flat Rate</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($utility['is_metered']): ?>
                                        <?php echo !empty($utility['reading_value']) ? number_format($utility['reading_value']) : 'N/A'; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($utility['is_metered']): ?>
                                        <?php echo !empty($utility['previous_reading_value']) ? number_format($utility['previous_reading_value']) : 'N/A'; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-<?php echo (isset($utility['net_amount']) && $utility['net_amount'] > 0) ? 'danger' : 'success'; ?>">
                                        Ksh <?php echo number_format($utility['amount'] ?? 0, 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($utility['reading_date'])): ?>
                                        <?php echo date('M j, Y', strtotime($utility['reading_date'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No utilities found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Maintenance Requests Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Maintenance Requests</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#maintenanceRequestModal">
                <i class="bi bi-plus-lg me-1"></i>New Request
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Unit</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Requested Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="maintenanceRequestsTable">
                        <?php if (empty($maintenanceRequests)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-tools display-4 text-muted mb-3 d-block"></i>
                                    <h5>No maintenance requests found</h5>
                                    <p class="text-muted">Submit your first maintenance request</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($maintenanceRequests as $request): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <?php
                                                $categoryIcons = [
                                                    'plumbing' => 'bi-droplet',
                                                    'electrical' => 'bi-lightning',
                                                    'hvac' => 'bi-thermometer',
                                                    'appliance' => 'bi-gear',
                                                    'structural' => 'bi-building',
                                                    'pest_control' => 'bi-bug',
                                                    'cleaning' => 'bi-broom',
                                                    'other' => 'bi-tools'
                                                ];
                                                $icon = $categoryIcons[$request['category']] ?? 'bi-tools';
                                                ?>
                                                <i class="bi <?= $icon ?> text-primary"></i>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($request['title']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?= htmlspecialchars($request['unit_number'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= ucwords(str_replace('_', ' ', $request['category'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $priorityColors = [
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'info',
                                            'low' => 'secondary'
                                        ];
                                        $priorityColor = $priorityColors[$request['priority']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $priorityColor ?>">
                                            <?= ucfirst($request['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'completed' => 'success',
                                            'in_progress' => 'primary',
                                            'pending' => 'warning',
                                            'cancelled' => 'danger'
                                        ];
                                        $statusColor = $statusColors[$request['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= ucwords(str_replace('_', ' ', $request['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('M j, Y', strtotime($request['requested_date'])) ?>
                                        <br>
                                        <small class="text-muted"><?= date('g:i A', strtotime($request['requested_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showMaintenanceRequestDetails(<?= $request['id'] ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Request Modal -->
<div class="modal fade" id="maintenanceRequestModal" tabindex="-1" aria-labelledby="maintenanceRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="maintenanceRequestForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="maintenanceRequestModalLabel">Submit Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="requestTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="requestTitle" name="title" required placeholder="Brief description of the issue">
                    </div>
                    <div class="mb-3">
                        <label for="requestCategory" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="requestCategory" name="category" required>
                            <option value="">Select Category</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="hvac">HVAC</option>
                            <option value="appliance">Appliance</option>
                            <option value="structural">Structural</option>
                            <option value="pest_control">Pest Control</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requestPriority" class="form-label">Priority</label>
                        <select class="form-select" id="requestPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requestDescription" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="requestDescription" name="description" rows="4" required placeholder="Please provide detailed description of the issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitMaintenanceBtn">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Request Details Modal -->
<div class="modal fade" id="maintenanceRequestDetailsModal" tabindex="-1" aria-labelledby="maintenanceRequestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="maintenanceRequestDetailsModalLabel">
                    <i class="bi bi-tools me-2"></i>Maintenance Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="maintenanceRequestDetailsContent">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="card border-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Request Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Title:</strong></div>
                                    <div class="col-sm-8" id="detailTitle">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Category:</strong></div>
                                    <div class="col-sm-8" id="detailCategory">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Priority:</strong></div>
                                    <div class="col-sm-8" id="detailPriority">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Status:</strong></div>
                                    <div class="col-sm-8" id="detailStatus">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Requested Date:</strong></div>
                                    <div class="col-sm-8" id="detailRequestedDate">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Scheduled Date:</strong></div>
                                    <div class="col-sm-8" id="detailScheduledDate">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Completed Date:</strong></div>
                                    <div class="col-sm-8" id="detailCompletedDate">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="card border-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Assignment</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Property:</strong></div>
                                    <div class="col-sm-8" id="detailProperty">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Unit:</strong></div>
                                    <div class="col-sm-8" id="detailUnit">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Assigned To:</strong></div>
                                    <div class="col-sm-8" id="detailAssignedTo">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Estimated Cost:</strong></div>
                                    <div class="col-sm-8" id="detailEstimatedCost">-</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Actual Cost:</strong></div>
                                    <div class="col-sm-8" id="detailActualCost">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="card border-0 mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Description</h6>
                    </div>
                    <div class="card-body">
                        <p id="detailDescription" class="mb-0">-</p>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="card border-0 mt-3" id="notesCard" style="display: none;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-sticky me-2"></i>Notes</h6>
                    </div>
                    <div class="card-body">
                        <p id="detailNotes" class="mb-0">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Success Modal -->
<div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-labelledby="paymentSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="paymentSuccessModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>Payment Successful
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                </div>
                <h4 class="text-success mb-3">Payment Processed Successfully!</h4>
                <div id="paymentSuccessDetails">
                    <!-- Payment details will be populated here -->
                </div>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>M-Pesa Payments:</strong> Your payment is pending verification. You will be notified once it's confirmed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="bi bi-check me-2"></i>Continue
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="bi bi-credit-card me-2"></i>Make Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentType" name="payment_type">
                    <input type="hidden" id="paymentAmount" name="amount">
                    <input type="hidden" id="appliesToMonthHidden" name="applies_to_month" value="">
                    <input type="hidden" id="advanceMonthsHidden" value="0">
                    
                    <!-- Payment Summary -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Payment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Payment Type:</strong>
                                </div>
                                <div class="col-6" id="summaryType">
                                    -
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Amount:</strong>
                                </div>
                                <div class="col-6" id="summaryAmount">
                                    -
                                </div>
                            </div>

                            <div class="row mt-2" id="applyMonthRow" style="display:none;">
                                <div class="col-6">
                                    <label for="appliesToMonth" class="form-label mb-0"><strong>Pay For Month</strong></label>
                                    <input type="month" id="appliesToMonth" class="form-control form-control-sm mt-1" value="<?= date('Y-m') ?>" />
                                    <small class="text-muted">Choose the month you are settling</small>
                                    <div id="paidMonthWarning" class="text-danger small mt-1" style="display:none;"></div>
                                </div>
                                <div class="col-6">
                                    <label for="customAmount" class="form-label mb-0"><strong>Amount to Pay</strong></label>
                                    <input type="number" step="0.01" min="0" id="customAmount" class="form-control form-control-sm mt-1" value="" />
                                    <small class="text-muted">Partial payments allowed</small>
                                </div>
                            </div>
                            <div class="row" id="advanceControls" style="display:none;">
                                <div class="col-6">
                                    <label for="advanceMonths" class="form-label mb-0"><strong>Months to Prepay</strong></label>
                                    <input type="number" min="1" max="24" step="1" value="1" id="advanceMonths" class="form-control form-control-sm mt-1" />
                                    <small class="text-muted">Pay for upcoming months</small>
                                </div>
                                <div class="col-6 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="recalculateAdvanceAmount()">
                                        <i class="bi bi-calculator me-1"></i>Calculate Amount
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Available Payment Methods</label>
                        <div id="payment_methods_list">
                            <!-- Payment methods will be populated here -->
                        </div>
                    </div>
                    
                    <!-- M-Pesa Manual Details -->
                    <div id="mpesaDetails" class="payment-method-details" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>M-Pesa Payment Instructions:</strong>
                            <div id="mpesaInstructions" class="mt-2">
                                <!-- Instructions will be populated here -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesaNumber" class="form-label">Phone Number Used</label>
                            <input type="tel" id="mpesaNumber" name="mpesa_number" class="form-control" placeholder="07XXXXXXXX" required>
                            <div class="form-text">Enter the phone number you used to make the M-Pesa payment</div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesaTransactionCode" class="form-label">M-Pesa Transaction Code</label>
                            <input type="text" id="mpesaTransactionCode" name="mpesa_transaction_code" class="form-control" placeholder="e.g., QWN213948J" required>
                            <div class="form-text">Enter the transaction code you received from M-Pesa</div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesaNotes" class="form-label">Additional Notes (Optional)</label>
                            <textarea id="mpesaNotes" name="mpesa_notes" class="form-control" rows="3" placeholder="Any additional information about the payment"></textarea>
                        </div>
                    </div>
                    
                    <!-- M-Pesa STK Push Details -->
                    <div id="mpesaStkDetails" class="payment-method-details" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>M-Pesa Payment Instructions:</strong>
                            <div id="mpesaInstructions" class="mt-2">
                                <small class="text-info fw-bold">STK Push Payment:</small>
                                <div class="mt-1">
                                    <small class="text-muted">Click "Pay Now" to receive STK push on your phone</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mpesaStkNumber" class="form-label">Phone Number</label>
                            <input type="tel" id="mpesaStkNumber" name="mpesa_stk_number" class="form-control" placeholder="07XXXXXXXX or 254XXXXXXXXX" required>
                            <div class="form-text">Enter your M-Pesa phone number to receive the payment prompt</div>
                        </div>
                    </div>
                    
                    <!-- Bank Transfer Details -->
                    <div id="bankTransferDetails" class="payment-method-details" style="display: none;">
                        <div class="mb-3">
                            <label for="bankName" class="form-label">Bank Name</label>
                            <input type="text" id="bankName" name="bank_name" class="form-control" placeholder="Enter bank name">
                        </div>
                        <div class="mb-3">
                            <label for="accountNumber" class="form-label">Account Number</label>
                            <input type="text" id="accountNumber" name="account_number" class="form-control" placeholder="Enter account number">
                        </div>
                        <div class="mb-3">
                            <label for="transactionId" class="form-label">Transaction ID</label>
                            <input type="text" id="transactionId" name="transaction_id" class="form-control" placeholder="Enter transaction ID">
                        </div>
                    </div>
                    
                    <!-- Cash/Cheque Details -->
                    <div id="cashChequeDetails" class="payment-method-details" style="display: none;">
                        <div class="mb-3">
                            <label for="paymentReference" class="form-label">Payment Reference</label>
                            <input type="text" id="paymentReference" name="payment_reference" class="form-control" placeholder="Enter payment reference">
                        </div>
                        <div class="mb-3">
                            <label for="paymentNotes" class="form-label">Notes</label>
                            <textarea id="paymentNotes" name="payment_notes" class="form-control" rows="3" placeholder="Additional notes about the payment"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitPaymentBtn" onclick="submitPayment()">
                    <i class="bi bi-credit-card me-1"></i>Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- STK Push Waiting Modal -->
<div class="modal fade" id="stkWaitingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <div class="spinner-border text-success" style="width: 4rem; height: 4rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <h4 class="mb-3">Waiting for Payment Confirmation</h4>
                <p class="text-muted mb-4">
                    <i class="bi bi-phone me-2"></i>
                    Please check your phone and enter your M-Pesa PIN to complete the payment.
                </p>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> This may take up to 30 seconds. Do not close this window.
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="cancelStkWaiting()">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Status Modal -->
<div class="modal fade" id="paymentStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5" id="paymentStatusContent">
                <!-- Content will be dynamically inserted -->
            </div>
            <div class="modal-footer justify-content-center border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="window.location.reload()">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tenant Notice Modal -->
<div class="modal fade" id="tenantNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tenantNoticeModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="tenantNoticeModalMeta" class="text-muted"></p>
                <p id="tenantNoticeModalBody"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var noticeModal = document.getElementById('tenantNoticeModal');
    if (noticeModal) {
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
    }

    var scrollBox = document.getElementById('tenantRecentMessagesScroll');
    if (scrollBox) {
      scrollBox.scrollTop = scrollBox.scrollHeight;
    }

    var form = document.getElementById('tenantRecentReplyForm');
    if (form) {
      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var input = document.getElementById('tenantRecentReplyBody');
        var btn = document.getElementById('tenantRecentReplySendBtn');
        var err = document.getElementById('tenantRecentReplyError');
        if (err) { err.style.display = 'none'; err.textContent = ''; }

        var text = input ? String(input.value || '').trim() : '';
        if (!text) {
          if (err) { err.textContent = 'Please type a message.'; err.style.display = 'block'; }
          return;
        }

        if (btn) btn.disabled = true;
        try {
          var fd = new FormData(form);
          var resp = await fetch('<?= BASE_URL ?>/tenant/messaging/send', { method: 'POST', body: fd });
          var data = await resp.json();
          if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Failed to send');
          }
          if (input) input.value = '';
          window.location.reload();
        } catch (ex) {
          if (err) { err.textContent = ex.message || 'Failed to send'; err.style.display = 'block'; }
        } finally {
          if (btn) btn.disabled = false;
        }
      });
    }
  });
</script>

<style>
/* Payment Cards Styling */
.payment-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border-radius: 12px;
    overflow: hidden;
}

.payment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.payment-card .card-body {
    padding: 1.25rem;
}

.payment-card .card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.payment-card .fs-4 {
    font-size: 1.5rem !important;
}

.payment-card .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 8px;
    font-weight: 500;
}

.payment-card .small {
    font-size: 0.8rem;
}

.payment-card .fs-6 {
    font-size: 1rem !important;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .payment-card .card-body {
        padding: 1rem;
    }
    
    .payment-card .card-title {
        font-size: 0.9rem;
    }
    
    .payment-card .fs-4 {
        font-size: 1.25rem !important;
    }
    
    .payment-card .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .payment-card .small {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .payment-card .card-body {
        padding: 0.75rem;
    }
    
    .payment-card .d-flex.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .payment-card .d-flex.justify-content-between > div:last-child {
        margin-top: 0.5rem;
        align-self: flex-end;
    }
}

/* Dashboard Cards Container */
.dashboard-cards-container {
    display: flex;
    gap: 1rem;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 0.5rem 0;
    justify-content: center;
    max-width: 100%;
}

.dashboard-card {
    flex: 0 0 calc(33.333% - 0.67rem);
    min-width: 280px;
    max-width: 350px;
}

/* Info Cards Styling */
.info-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    background: #ffffff;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.info-card .card-body {
    padding: 0.75rem;
    height: auto;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.info-card .card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0;
}

.info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 10px;
}

.info-icon i {
    font-size: 1.2rem;
    color: #0d6efd;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 600;
}

.info-value {
    font-size: 0.95rem;
    color: #212529;
    font-weight: 400;
}

.info-value .badge {
    font-size: 0.8rem;
    font-weight: 500;
}

/* Individual card gradient colors */
.property-card::before {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}

.unit-card::before {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.lease-card::before {
    background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
}

/* Mobile adjustments for info cards */
@media (max-width: 768px) {
    .dashboard-cards-container {
        gap: 0.75rem;
        justify-content: center;
    }
    
    .dashboard-card {
        flex: 0 0 calc(33.333% - 0.5rem);
        min-width: 250px;
    }
    
    .info-card .card-body {
        padding: 0.5rem;
        min-height: 100px;
    }
    
    .info-card .card-title {
        font-size: 1rem;
    }
    
    .info-icon {
        width: 35px;
        height: 35px;
    }
    
    .info-icon i {
        font-size: 1rem;
    }
    
    .info-label {
        font-size: 0.85rem;
    }
    
    .info-value {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .dashboard-cards-container {
        gap: 0.5rem;
        padding: 0.25rem 0;
        justify-content: center;
    }
    
    .dashboard-card {
        flex: 0 0 calc(33.333% - 0.33rem);
        min-width: 220px;
    }
    
    .info-card .card-body {
        padding: 0.4rem;
        min-height: 90px;
    }
    
    .info-item {
        margin-bottom: 0.25rem;
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
}

/* Extra small screens - allow horizontal scroll */
@media (max-width: 480px) {
    .dashboard-cards-container {
        gap: 0.5rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        justify-content: flex-start;
    }
    
    .dashboard-card {
        flex: 0 0 280px;
        min-width: 280px;
    }
    
    .info-card .card-body {
        padding: 0.4rem;
        min-height: 80px;
    }
}

/* Payment Method Cards */
.payment-method-card {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.payment-method-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.payment-method-card.border-primary {
    border-color: #0d6efd !important;
    background-color: #f8f9fa !important;
}

.payment-instructions {
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-radius: 0.375rem;
    border-left: 3px solid #28a745;
}

/* Status Cards Styling */
.status-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.status-card .card-body {
    padding: 1.5rem;
}

.status-card .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.status-card .card-value {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.status-card .card-subtitle {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0;
}

.status-card .card-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    opacity: 0.3;
    font-size: 2rem;
}

/* Color variations for status cards */
.status-card.total {
    border-left: 4px solid #0d6efd;
}

.status-card.pending {
    border-left: 4px solid #ffc107;
}

.status-card.in-progress {
    border-left: 4px solid #0dcaf0;
}

.status-card.completed {
    border-left: 4px solid #198754;
}

/* Mobile adjustments for status cards */
@media (max-width: 768px) {
    .status-card .card-body {
        padding: 1rem;
    }
    
    .status-card .card-value {
        font-size: 1.5rem;
    }
    
    .status-card .card-icon {
        font-size: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up maintenance form handler');
    
    // Handle maintenance request form submission
    const maintenanceForm = document.getElementById('maintenanceRequestForm');
    console.log('Maintenance form found:', maintenanceForm);
    
    if (maintenanceForm) {
        maintenanceForm.addEventListener('submit', function(e) {
            console.log('Form submission triggered');
            e.preventDefault();
            
            // Validate form
            const title = document.getElementById('requestTitle').value.trim();
            const category = document.getElementById('requestCategory').value;
            const description = document.getElementById('requestDescription').value.trim();
            
            if (!title || !category || !description) {
                showAlert('error', 'Please fill in all required fields');
                return;
            }
            
            const formData = new FormData(this);
            console.log('Submitting form data:', Object.fromEntries(formData));
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            submitBtn.disabled = true;
            
            fetch('<?= BASE_URL ?>/tenant/maintenance/create', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response received:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('maintenanceRequestModal')).hide();
                    
                    // Reset form
                    this.reset();
                    
                    // Show success message
                    showAlert('success', 'Maintenance request submitted successfully!');
                    
                    // Reload the page to show the new request
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('error', data.message || 'Error submitting maintenance request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                showAlert('error', 'Error submitting maintenance request');
            });
        });
    } else {
        console.error('Maintenance form not found!');
    }
    
    // Also add click handler to the submit button as backup
    const submitBtn = document.getElementById('submitMaintenanceBtn');
    if (submitBtn) {
        console.log('Submit button found:', submitBtn);
        submitBtn.addEventListener('click', function(e) {
            console.log('Submit button clicked directly');
            e.preventDefault();
            
            // Trigger form submission
            const form = document.getElementById('maintenanceRequestForm');
            if (form) {
                console.log('Triggering form submission');
                form.dispatchEvent(new Event('submit'));
            }
        });
        
        // Ensure button is enabled and clickable
        submitBtn.disabled = false;
        submitBtn.style.pointerEvents = 'auto';
        console.log('Submit button enabled and clickable');
    } else {
        console.error('Submit button not found!');
    }
    
    // Add modal event listeners to ensure button is ready when modal opens
    const modal = document.getElementById('maintenanceRequestModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal shown, ensuring button is clickable');
            const btn = document.getElementById('submitMaintenanceBtn');
            if (btn) {
                btn.disabled = false;
                btn.style.pointerEvents = 'auto';
                console.log('Button enabled after modal shown');
            }
        });
    }
});

function showMaintenanceRequestDetails(requestId) {
    fetch(`<?= BASE_URL ?>/tenant/maintenance/get/${requestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const request = data.request;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Request Details</h6>
                            <p><strong>Title:</strong> ${request.title}</p>
                            <p><strong>Category:</strong> ${request.category.replace('_', ' ').toUpperCase()}</p>
                            <p><strong>Priority:</strong> <span class="badge bg-${getPriorityColor(request.priority)}">${request.priority.toUpperCase()}</span></p>
                            <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(request.status)}">${request.status.replace('_', ' ').toUpperCase()}</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Timeline</h6>
                            <p><strong>Requested:</strong> ${new Date(request.requested_date).toLocaleDateString()}</p>
                            ${request.scheduled_date ? `<p><strong>Scheduled:</strong> ${new Date(request.scheduled_date).toLocaleDateString()}</p>` : ''}
                            ${request.completed_date ? `<p><strong>Completed:</strong> ${new Date(request.completed_date).toLocaleDateString()}</p>` : ''}
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6>Description</h6>
                        <p>${request.description}</p>
                    </div>
                    ${request.notes ? `
                    <div class="mt-3">
                        <h6>Admin Notes</h6>
                        <p>${request.notes}</p>
                    </div>
                    ` : ''}
                    ${request.assigned_to ? `
                    <div class="mt-3">
                        <h6>Assigned To</h6>
                        <p>${request.assigned_to}</p>
                    </div>
                    ` : ''}
                `;
                
                document.getElementById('maintenanceRequestDetailsContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('maintenanceRequestDetailsModal')).show();
            } else {
                showAlert('error', data.message || 'Error loading request details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error loading request details');
        });
}

function getPriorityColor(priority) {
    switch(priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'in_progress': return 'primary';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert alert at the top of the container
    const container = document.querySelector('.container');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }
    }, 5000);
}

function showPaymentSuccessModal(paymentDetails) {
    const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
    const detailsContainer = document.getElementById('paymentSuccessDetails');
    
    // Build payment details HTML
    let detailsHtml = '<div class="row">';
    detailsHtml += '<div class="col-12">';
    detailsHtml += '<h6 class="mb-3">Payment Details</h6>';
    detailsHtml += '<div class="table-responsive">';
    detailsHtml += '<table class="table table-sm">';
    
    if (paymentDetails.payment_type) {
        detailsHtml += `<tr><td><strong>Payment Type:</strong></td><td>${paymentDetails.payment_type.charAt(0).toUpperCase() + paymentDetails.payment_type.slice(1)}</td></tr>`;
    }
    if (paymentDetails.amount) {
        detailsHtml += `<tr><td><strong>Amount:</strong></td><td>Ksh ${parseFloat(paymentDetails.amount).toLocaleString()}</td></tr>`;
    }
    if (paymentDetails.payment_method) {
        detailsHtml += `<tr><td><strong>Payment Method:</strong></td><td>${paymentDetails.payment_method}</td></tr>`;
    }
    if (paymentDetails.mpesa_transaction_code) {
        detailsHtml += `<tr><td><strong>Transaction Code:</strong></td><td><code>${paymentDetails.mpesa_transaction_code}</code></td></tr>`;
    }
    if (paymentDetails.mpesa_number) {
        detailsHtml += `<tr><td><strong>Phone Number:</strong></td><td>${paymentDetails.mpesa_number}</td></tr>`;
    }
    if (paymentDetails.status) {
        const statusClass = paymentDetails.status === 'pending_verification' ? 'warning' : 'success';
        const statusText = paymentDetails.status === 'pending_verification' ? 'Pending Verification' : 'Completed';
        detailsHtml += `<tr><td><strong>Status:</strong></td><td><span class="badge bg-${statusClass}">${statusText}</span></td></tr>`;
    }
    
    detailsHtml += '</table>';
    detailsHtml += '</div>';
    detailsHtml += '</div>';
    detailsHtml += '</div>';
    
    // Update the modal content
    detailsContainer.innerHTML = detailsHtml;
    
    // Show the modal
    modal.show();
}

// Keep hidden fields in sync
document.addEventListener('DOMContentLoaded', function() {
    const appliesToMonth = document.getElementById('appliesToMonth');
    const appliesToMonthHidden = document.getElementById('appliesToMonthHidden');
    const submitBtn = document.getElementById('submitPaymentBtn');
    const paidWarning = document.getElementById('paidMonthWarning');
    const paidMonths = <?= json_encode(array_values(array_unique($paidRentMonths ?? []))) ?>;
    const customAmount = document.getElementById('customAmount');
    const paymentAmount = document.getElementById('paymentAmount');
    const summaryAmount = document.getElementById('summaryAmount');

    const checkPaidMonth = () => {
        if (!appliesToMonth || !submitBtn || !paidWarning) return;
        const ym = (appliesToMonth.value || '').trim();
        const isPaid = ym !== '' && Array.isArray(paidMonths) && paidMonths.indexOf(ym) !== -1;
        if (isPaid) {
            paidWarning.textContent = 'This month is already fully paid. Please select another month.';
            paidWarning.style.display = 'block';
            submitBtn.disabled = true;
        } else {
            paidWarning.textContent = '';
            paidWarning.style.display = 'none';
            submitBtn.disabled = false;
        }
    };

    if (appliesToMonth && appliesToMonthHidden) {
        appliesToMonth.addEventListener('change', function() {
            appliesToMonthHidden.value = (this.value && this.value.match(/^\d{4}-\d{2}$/)) ? (this.value + '-01') : '';
            checkPaidMonth();
        });
        appliesToMonthHidden.value = (appliesToMonth.value && appliesToMonth.value.match(/^\d{4}-\d{2}$/)) ? (appliesToMonth.value + '-01') : '';
    }

    checkPaidMonth();

    if (customAmount && paymentAmount) {
        customAmount.addEventListener('input', function() {
            const v = parseFloat(this.value || '0');
            paymentAmount.value = isNaN(v) ? 0 : v;
            summaryAmount.textContent = 'Ksh ' + (isNaN(v) ? 0 : v).toLocaleString();
        });
    }
});

function recalculateAdvanceAmount() {
    const paymentTypeField = document.getElementById('paymentType');
    if (paymentTypeField.value !== 'rent' && paymentTypeField.value !== 'rent_advance') {
        return;
    }
    // In our flow, rent_advance sets paymentType to 'rent' for backend, so check the controls directly
    const monthsInput = document.getElementById('advanceMonths');
    const months = Math.max(1, parseInt(monthsInput.value, 10) || 1);
    <?php if (isset($lease) && $lease): ?>
        const advanceBaseRent = <?= (float)$lease['rent_amount'] ?>;
    <?php else: ?>
        const advanceBaseRent = 0;
    <?php endif; ?>
    const advAmount = Math.max(0, months * advanceBaseRent);
    document.getElementById('advanceMonthsHidden').value = months;
    document.getElementById('paymentAmount').value = advAmount;
    const summaryAmount = document.getElementById('summaryAmount');
    summaryAmount.textContent = 'Ksh ' + advAmount.toLocaleString();
}

// Function to show maintenance request details
function showMaintenanceRequestDetails(requestId) {
    console.log('Loading maintenance request details for ID:', requestId);
    
    // Show loading state
    const modal = new bootstrap.Modal(document.getElementById('maintenanceRequestDetailsModal'));
    modal.show();
    
    // Reset all fields
    document.getElementById('detailTitle').textContent = '-';
    document.getElementById('detailCategory').textContent = '-';
    document.getElementById('detailPriority').textContent = '-';
    document.getElementById('detailStatus').textContent = '-';
    document.getElementById('detailRequestedDate').textContent = '-';
    document.getElementById('detailScheduledDate').textContent = '-';
    document.getElementById('detailCompletedDate').textContent = '-';
    document.getElementById('detailProperty').textContent = '-';
    document.getElementById('detailUnit').textContent = '-';
    document.getElementById('detailAssignedTo').textContent = '-';
    document.getElementById('detailEstimatedCost').textContent = '-';
    document.getElementById('detailActualCost').textContent = '-';
    document.getElementById('detailDescription').textContent = '-';
    document.getElementById('detailNotes').textContent = '-';
    document.getElementById('notesCard').style.display = 'none';
    
    // Fetch request details
    fetch(`<?= BASE_URL ?>/tenant/maintenance/get/${requestId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Request details loaded:', data);
        
        if (data.success && data.request) {
            const request = data.request;
            console.log('Processing request data:', request);
            
            // Populate basic information
            document.getElementById('detailTitle').textContent = request.title || '-';
            document.getElementById('detailDescription').textContent = request.description || '-';
            
            // Category with icon
            const categoryIcons = {
                'plumbing': 'bi-droplet',
                'electrical': 'bi-lightning',
                'hvac': 'bi-thermometer',
                'appliance': 'bi-gear',
                'structural': 'bi-building',
                'pest_control': 'bi-bug',
                'cleaning': 'bi-broom',
                'other': 'bi-tools'
            };
            const categoryIcon = categoryIcons[request.category] || 'bi-tools';
            document.getElementById('detailCategory').innerHTML = 
                `<i class="bi ${categoryIcon} me-1"></i>${ucwords(request.category?.replace('_', ' ') || '-')}`;
            
            // Priority with color
            const priorityColors = {
                'urgent': 'danger',
                'high': 'warning',
                'medium': 'info',
                'low': 'secondary'
            };
            const priorityColor = priorityColors[request.priority] || 'secondary';
            document.getElementById('detailPriority').innerHTML = 
                `<span class="badge bg-${priorityColor}">${ucwords(request.priority || '-')}</span>`;
            
            // Status with color
            const statusColors = {
                'completed': 'success',
                'in_progress': 'primary',
                'pending': 'warning',
                'cancelled': 'danger'
            };
            const statusColor = statusColors[request.status] || 'secondary';
            document.getElementById('detailStatus').innerHTML = 
                `<span class="badge bg-${statusColor}">${ucwords(request.status || '-')}</span>`;
            
            // Dates
            document.getElementById('detailRequestedDate').textContent = 
                request.requested_date ? formatDateTime(request.requested_date) : '-';
            document.getElementById('detailScheduledDate').textContent = 
                request.scheduled_date ? formatDateTime(request.scheduled_date) : 'Not scheduled';
            document.getElementById('detailCompletedDate').textContent = 
                request.completed_date ? formatDateTime(request.completed_date) : 'Not completed';
            
            // Location information
            document.getElementById('detailProperty').textContent = 
                request.property_details?.name || request.property_name || '-';
            document.getElementById('detailUnit').textContent = 
                request.unit_details?.unit_number || request.unit_number || '-';
            document.getElementById('detailAssignedTo').textContent = 
                request.assigned_to || 'Not assigned';
            
            console.log('Cost data:', {
                estimated_cost: request.estimated_cost,
                actual_cost: request.actual_cost
            });
            
            document.getElementById('detailEstimatedCost').textContent = 
                request.estimated_cost ? `Ksh ${parseFloat(request.estimated_cost).toFixed(2)}` : 'Not specified';
            document.getElementById('detailActualCost').textContent = 
                request.actual_cost ? `Ksh ${parseFloat(request.actual_cost).toFixed(2)}` : 'Not specified';
            
            // Notes (show only if exists)
            if (request.notes && request.notes.trim()) {
                document.getElementById('detailNotes').textContent = request.notes;
                document.getElementById('notesCard').style.display = 'block';
            } else {
                document.getElementById('notesCard').style.display = 'none';
            }
        } else {
            showAlert('error', data.message || 'Error loading maintenance request details');
        }
    })
    .catch(error => {
        console.error('Error loading request details:', error);
        console.error('Error details:', error.message);
        showAlert('error', 'Error loading maintenance request details: ' + error.message);
    });
}

// Helper function to format date and time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Helper function to capitalize words
function ucwords(str) {
    return str.replace(/\b\w/g, l => l.toUpperCase());
}

// Payment Modal Functions
function parseJsonResponse(response) {
    return response.text().then(text => {
        if (!text) {
            return {
                success: false,
                message: `Request failed (${response.status}). Empty response from server.`
            };
        }
        try {
            return JSON.parse(text);
        } catch (e) {
            return {
                success: false,
                message: `Request failed (${response.status}). Invalid JSON response.`
            };
        }
    });
}

function populatePaymentMethods(paymentMethods) {
    const paymentMethodsList = document.getElementById('payment_methods_list');
    
    if (!paymentMethods || paymentMethods.length === 0) {
        paymentMethodsList.innerHTML = '<div class="alert alert-info">No payment methods available. Please contact your landlord.</div>';
        return;
    }
    
    let html = '<div class="row g-3">';
    
    paymentMethods.forEach((method, index) => {
        const details = method.details ? JSON.parse(method.details) : {};
        const methodId = `payment_method_${method.id}`;
        
        html += `
            <div class="col-md-6">
                <div class="card payment-method-card" style="cursor: pointer;" data-method-type="${method.type}" onclick="selectPaymentMethod('${methodId}', '${method.type}')">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <input type="radio" name="selected_payment_method" id="${methodId}" value="${method.id}" class="form-check-input me-2">
                            <label for="${methodId}" class="form-check-label fw-bold mb-0">${method.name}</label>
                        </div>
                        <p class="text-muted small mb-2">${method.description || ''}</p>
                        ${getPaymentMethodDetails(method.type, details)}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    paymentMethodsList.innerHTML = html;
}

function getPaymentMethodDetails(type, details) {
    if (type === 'mpesa_manual') {
        if (details.mpesa_method === 'paybill') {
            return `
                <div class="payment-instructions">
                    <small class="text-success fw-bold">Payment Instructions:</small>
                    <div class="mt-1">
                        <small class="text-muted">1. Go to M-Pesa Menu</small><br>
                        <small class="text-muted">2. Select "Pay Bill"</small><br>
                        <small class="text-muted">3. Enter Paybill: <strong>${details.paybill_number || 'N/A'}</strong></small><br>
                        <small class="text-muted">4. Enter Account: <strong>${details.account_number || 'N/A'}</strong></small><br>
                        <small class="text-muted">5. Enter amount and complete payment</small>
                    </div>
                </div>
            `;
        } else if (details.mpesa_method === 'till') {
            return `
                <div class="payment-instructions">
                    <small class="text-success fw-bold">Payment Instructions:</small>
                    <div class="mt-1">
                        <small class="text-muted">1. Go to M-Pesa Menu</small><br>
                        <small class="text-muted">2. Select "Buy Goods and Services"</small><br>
                        <small class="text-muted">3. Enter Till Number: <strong>${details.till_number || 'N/A'}</strong></small><br>
                        <small class="text-muted">4. Enter amount and complete payment</small>
                    </div>
                </div>
            `;
        }
    } else if (type === 'mpesa_stk') {
        return `
            <div class="payment-instructions">
                <small class="text-info fw-bold">STK Push Payment:</small>
                <div class="mt-1">
                    <small class="text-muted">Click "Pay Now" to receive STK push on your phone</small>
                </div>
            </div>
        `;
    } else if (type === 'bank_transfer') {
        return `
            <div class="payment-instructions">
                <small class="text-primary fw-bold">Bank Transfer:</small>
                <div class="mt-1">
                    <small class="text-muted">Transfer to the provided bank account and enter transaction details</small>
                </div>
            </div>
        `;
    } else if (type === 'cash') {
        return `
            <div class="payment-instructions">
                <small class="text-warning fw-bold">Cash Payment:</small>
                <div class="mt-1">
                    <small class="text-muted">Pay cash at the office and get a receipt</small>
                </div>
            </div>
        `;
    } else if (type === 'cheque') {
        return `
            <div class="payment-instructions">
                <small class="text-secondary fw-bold">Cheque Payment:</small>
                <div class="mt-1">
                    <small class="text-muted">Submit cheque to the office for processing</small>
                </div>
            </div>
        `;
    }
    
    return '<small class="text-muted">Contact landlord for payment details</small>';
}

function selectPaymentMethod(methodId, methodType) {
    // Uncheck all radio buttons
    document.querySelectorAll('input[name="selected_payment_method"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Check the selected one
    document.getElementById(methodId).checked = true;
    
    // Update visual selection
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('border-primary', 'bg-light');
    });
    
    document.getElementById(methodId).closest('.payment-method-card').classList.add('border-primary', 'bg-light');
    
    // Show/hide payment method details based on type
    showPaymentMethodDetails(methodType);
}

function showPaymentMethodDetails(methodType) {
    // Hide all payment method details
    document.querySelectorAll('.payment-method-details').forEach(detail => {
        detail.style.display = 'none';
    });
    
    // Show relevant details based on method type
    if (methodType === 'mpesa_manual') {
        document.getElementById('mpesaDetails').style.display = 'block';
        populateMpesaInstructions(methodType);
    } else if (methodType === 'mpesa_stk') {
        document.getElementById('mpesaStkDetails').style.display = 'block';
        populateMpesaInstructions(methodType);
    } else if (methodType === 'bank_transfer') {
        document.getElementById('bankTransferDetails').style.display = 'block';
    } else if (methodType === 'cash' || methodType === 'cheque') {
        document.getElementById('cashChequeDetails').style.display = 'block';
    }
}

function populateMpesaInstructions(methodType) {
    const instructionsDiv = document.getElementById('mpesaInstructions');
    const selectedMethod = document.querySelector('input[name="selected_payment_method"]:checked');
    
    if (!selectedMethod) return;
    
    // Get the payment method card to extract details
    const methodCard = selectedMethod.closest('.payment-method-card');
    const instructions = methodCard.querySelector('.payment-instructions');
    
    if (instructions) {
        instructionsDiv.innerHTML = instructions.innerHTML;
    } else {
        instructionsDiv.innerHTML = '<small class="text-muted">Please follow the M-Pesa payment instructions above.</small>';
    }
}

function openPaymentModal(type, paymentMethods = []) {
    console.log('Opening payment modal for:', type);
    console.log('Payment methods:', paymentMethods);
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const form = document.getElementById('paymentForm');
    // Reset form first to clear any previous values
    form.reset();
    const paymentTypeField = document.getElementById('paymentType');
    const summaryType = document.getElementById('summaryType');
    const summaryAmount = document.getElementById('summaryAmount');
    const applyMonthRow = document.getElementById('applyMonthRow');
    const appliesToMonth = document.getElementById('appliesToMonth');
    const appliesToMonthHidden = document.getElementById('appliesToMonthHidden');
    const customAmount = document.getElementById('customAmount');
    const paymentMethodsList = document.getElementById('payment_methods_list');
    
    // Set payment type
    paymentTypeField.value = type;
    
    // Show month/partial controls for normal payments (not advance)
    if (applyMonthRow) applyMonthRow.style.display = 'none';
    if (customAmount) customAmount.value = '';
    if (appliesToMonth) appliesToMonth.value = new Date().toISOString().slice(0, 7);
    if (appliesToMonthHidden) appliesToMonthHidden.value = '';

    // Update summary
    // Hide advance controls by default
    document.getElementById('advanceControls').style.display = 'none';
    if (type === 'rent') {
        summaryType.textContent = 'Rent Payment';
        <?php if (isset($lease) && $lease): ?>
            <?php 
            $missedTotalJs = 0;
            if (!empty($missedRentMonths)) {
                foreach ($missedRentMonths as $mm) { $missedTotalJs += max(0, $mm['amount'] ?? 0); }
            }
            ?>
            const totalRentAmount = <?= $missedTotalJs ?>;
            document.getElementById('paymentAmount').value = totalRentAmount;
            summaryAmount.textContent = 'Ksh ' + totalRentAmount.toLocaleString();

            // Default: pay for earliest missed month if any
            <?php if (!empty($missedRentMonths)): ?>
                const firstMissed = <?= json_encode($missedRentMonths[0]['year'] . '-' . str_pad((string)$missedRentMonths[0]['month'], 2, '0', STR_PAD_LEFT)) ?>;
                if (appliesToMonth) appliesToMonth.value = firstMissed;
            <?php endif; ?>

            if (applyMonthRow) applyMonthRow.style.display = 'flex';
            if (customAmount) customAmount.value = totalRentAmount;
        <?php endif; ?>
    } else if (type === 'utility' || type === 'utilities') {
        paymentTypeField.value = 'utility';
        summaryType.textContent = 'Utilities Payment';
        <?php if (!empty($utilities)): ?>
            <?php 
            $totalUtilities = 0;
            foreach ($utilities as $utility) {
                $netAmount = max(0, $utility['net_amount'] ?? $utility['amount'] ?? 0);
                $totalUtilities += $netAmount;
            }
            ?>
            const utilitiesAmount = <?= $totalUtilities ?>;
            document.getElementById('paymentAmount').value = utilitiesAmount;
            summaryAmount.textContent = 'Ksh ' + utilitiesAmount.toLocaleString();

            if (applyMonthRow) applyMonthRow.style.display = 'flex';
            if (customAmount) customAmount.value = utilitiesAmount;
        <?php endif; ?>
    } else if (type === 'maintenance') {
        summaryType.textContent = 'Maintenance Payment';
        const maintAmount = <?= (float)($maintenanceOutstanding ?? 0) ?>;
        document.getElementById('paymentAmount').value = maintAmount;
        summaryAmount.textContent = 'Ksh ' + maintAmount.toLocaleString();

        if (applyMonthRow) applyMonthRow.style.display = 'flex';
        if (customAmount) customAmount.value = maintAmount;
    } else if (type === 'all') {
        summaryType.textContent = 'Rent + Utilities + Maintenance Payment';
        <?php if (isset($lease) && $lease): ?>
            <?php 
            $missedTotalJsBoth = 0; 
            if (!empty($missedRentMonths)) {
                foreach ($missedRentMonths as $mm) { $missedTotalJsBoth += max(0, $mm['amount'] ?? 0); }
            }
            $dueNowFlagJs = isset($rentCoverage['due_now']) ? (bool)$rentCoverage['due_now'] : true;
            $rentDueNowBoth = $dueNowFlagJs ? $missedTotalJsBoth : 0;
            ?>
            const totalRentAmountBoth = <?= $rentDueNowBoth ?>;
        <?php else: ?>
            const totalRentAmountBoth = 0;
        <?php endif; ?>
        const maintenanceAmountAll = <?= (float)($maintenanceOutstanding ?? 0) ?>;
        <?php 
        $totalUtilitiesBoth = 0; 
        if (!empty($utilities)) {
            foreach ($utilities as $utility) {
                $netAmount = max(0, $utility['net_amount'] ?? $utility['amount'] ?? 0);
                $totalUtilitiesBoth += $netAmount;
            }
        }
        ?>
        const utilitiesAmountBoth = <?= $totalUtilitiesBoth ?>;
        const grandAmount = (maintenanceAmountAll + totalRentAmountBoth + utilitiesAmountBoth);
        document.getElementById('paymentAmount').value = grandAmount;
        summaryAmount.textContent = 'Ksh ' + grandAmount.toLocaleString();

        if (applyMonthRow) applyMonthRow.style.display = 'flex';
        if (customAmount) customAmount.value = grandAmount;
    } else if (type === 'rent_advance') {
        // Treat as a rent payment under the hood
        paymentTypeField.value = 'rent';
        summaryType.textContent = 'Pay Rent in Advance';
        document.getElementById('advanceControls').style.display = 'flex';
        // Default months 1
        const monthsInput = document.getElementById('advanceMonths');
        monthsInput.value = monthsInput.value && parseInt(monthsInput.value, 10) > 0 ? parseInt(monthsInput.value, 10) : 1;
        <?php if (isset($lease) && $lease): ?>
            const advanceBaseRent = <?= (float)$lease['rent_amount'] ?>;
        <?php else: ?>
            const advanceBaseRent = 0;
        <?php endif; ?>
        const months = parseInt(monthsInput.value, 10) || 1;
        const advAmount = Math.max(0, months * advanceBaseRent);
        document.getElementById('paymentAmount').value = advAmount;
        summaryAmount.textContent = 'Ksh ' + advAmount.toLocaleString();
        if (applyMonthRow) applyMonthRow.style.display = 'none';
    }
    
    // Hide all payment method details
    document.querySelectorAll('.payment-method-details').forEach(detail => {
        detail.style.display = 'none';
    });
    
    // Populate payment methods
    populatePaymentMethods(paymentMethods);
    
    modal.show();
}

// Handle payment method change
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.getElementById('paymentMethod');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            const selectedMethod = this.value;
            
            // Hide all payment method details
            document.querySelectorAll('.payment-method-details').forEach(detail => {
                detail.style.display = 'none';
            });
            
            // Show relevant details based on selection
            if (selectedMethod === 'mpesa') {
                document.getElementById('mpesaDetails').style.display = 'block';
            } else if (selectedMethod === 'bank_transfer') {
                document.getElementById('bankTransferDetails').style.display = 'block';
            } else if (selectedMethod === 'cash' || selectedMethod === 'cheque') {
                document.getElementById('cashChequeDetails').style.display = 'block';
            }
        });
    }
});

// Submit payment
function submitPayment() {
    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitPaymentBtn');
    
    // Validate form
    const selectedPaymentMethod = document.querySelector('input[name="selected_payment_method"]:checked');
    if (!selectedPaymentMethod) {
        showAlert('error', 'Please select a payment method');
        return;
    }
    
    // Get the payment method type from the card
    const methodCard = selectedPaymentMethod.closest('.payment-method-card');
    const methodType = methodCard.dataset.methodType;
    
    // Add selected payment method to form data
    formData.append('payment_method_id', selectedPaymentMethod.value);
    
    // Check if this is an STK push payment
    if (methodType === 'mpesa_stk') {
        // Combined payments are not supported via STK push in this flow
        if (formData.get('payment_type') === 'all') {
            showAlert('error', 'Pay All is not available with STK Push. Please use a different payment method.');
            return;
        }
        // Handle STK push differently
        const phoneNumber = document.getElementById('mpesaStkNumber').value;
        if (!phoneNumber) {
            showAlert('error', 'Please enter your phone number');
            return;
        }
        
        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Initiating STK Push...';
        submitBtn.disabled = true;
        
        // Prepare STK push data
        const stkData = new FormData();
        stkData.append('phone_number', phoneNumber);
        stkData.append('amount', formData.get('amount'));
        stkData.append('payment_type', formData.get('payment_type'));
        stkData.append('payment_method_id', selectedPaymentMethod.value);
        
        // Initiate STK push
        fetch('<?= BASE_URL ?>/tenant/payment/initiate-stk', {
            method: 'POST',
            body: stkData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => parseJsonResponse(response))
        .then(data => {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            if (data.success) {
                // Close payment modal
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                
                // Show waiting modal
                const waitingModal = new bootstrap.Modal(document.getElementById('stkWaitingModal'));
                waitingModal.show();
                
                // Store checkout request ID for polling
                window.stkCheckoutRequestId = data.checkout_request_id;
                window.stkMerchantRequestId = data.merchant_request_id;
                
                // Start polling for payment status
                startPaymentStatusPolling(data.checkout_request_id, data.merchant_request_id);
                
                // Reset form
                form.reset();
            } else {
                showAlert('error', data.message || 'Failed to initiate STK push');
            }
        })
        .catch(error => {
            console.error('STK Push error:', error);
            
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            showAlert('error', 'Error initiating STK push. Please try again.');
        });
        
        return; // Exit function for STK push
    }
    
    // Show loading state for regular payments
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    submitBtn.disabled = true;
    
    // Submit regular payment
    fetch('<?= BASE_URL ?>/tenant/payment/process', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => parseJsonResponse(response))
    .then(data => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            // Show success modal with payment details
            showPaymentSuccessModal(data.payment_details || {});
            
            // Close payment modal
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            
            // Reset form
            form.reset();
            
            // Reload page after a short delay to show updated amounts
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('error', data.message || 'Error processing payment');
        }
    })
    .catch(error => {
        console.error('Payment error:', error);
        
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        showAlert('error', 'Error processing payment');
    });
}

// Poll for payment status
let pollInterval;
let pollAttempts = 0;
const maxPollAttempts = 30; // Poll for 30 seconds (30 attempts x 1 second)

function startPaymentStatusPolling(checkoutRequestId, merchantRequestId) {
    pollAttempts = 0;
    
    pollInterval = setInterval(() => {
        pollAttempts++;
        
        // Stop polling after max attempts
        if (pollAttempts >= maxPollAttempts) {
            clearInterval(pollInterval);
            showPaymentTimeout();
            return;
        }
        
        // Check payment status
        fetch('<?= BASE_URL ?>/tenant/payment/check-stk-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `checkout_request_id=${checkoutRequestId}&merchant_request_id=${merchantRequestId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                clearInterval(pollInterval);
                showPaymentSuccess(data);
            } else if (data.status === 'failed' || data.status === 'cancelled') {
                clearInterval(pollInterval);
                showPaymentFailed(data);
            }
            // If pending, continue polling
        })
        .catch(error => {
            console.error('Status check error:', error);
        });
    }, 1000); // Poll every 1 second
}

function showPaymentSuccess(data) {
    // Hide waiting modal
    bootstrap.Modal.getInstance(document.getElementById('stkWaitingModal')).hide();
    
    // Show success modal
    const statusContent = document.getElementById('paymentStatusContent');
    statusContent.innerHTML = `
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
        </div>
        <h3 class="text-success mb-3">Payment Successful!</h3>
        <p class="text-muted mb-4">
            Your payment has been received and processed successfully.
        </p>
        ${data.receipt_number ? `
        <div class="alert alert-success">
            <strong>Receipt Number:</strong> ${data.receipt_number}
        </div>
        ` : ''}
    `;
    
    const statusModal = new bootstrap.Modal(document.getElementById('paymentStatusModal'));
    statusModal.show();
}

function showPaymentFailed(data) {
    // Hide waiting modal
    bootstrap.Modal.getInstance(document.getElementById('stkWaitingModal')).hide();
    
    // Show failed modal
    const statusContent = document.getElementById('paymentStatusContent');
    statusContent.innerHTML = `
        <div class="mb-4">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
        </div>
        <h3 class="text-danger mb-3">Payment Failed</h3>
        <p class="text-muted mb-4">
            ${data.message || 'The payment could not be completed. Please try again.'}
        </p>
        <div class="alert alert-danger">
            <small>${data.result_desc || 'Transaction was cancelled or failed.'}</small>
        </div>
    `;
    
    const statusModal = new bootstrap.Modal(document.getElementById('paymentStatusModal'));
    statusModal.show();
}

function showPaymentTimeout() {
    // Hide waiting modal
    bootstrap.Modal.getInstance(document.getElementById('stkWaitingModal')).hide();
    
    // Show timeout modal
    const statusContent = document.getElementById('paymentStatusContent');
    statusContent.innerHTML = `
        <div class="mb-4">
            <i class="bi bi-clock-fill text-warning" style="font-size: 5rem;"></i>
        </div>
        <h3 class="text-warning mb-3">Payment Pending</h3>
        <p class="text-muted mb-4">
            We're still waiting for payment confirmation. This may take a few minutes.
        </p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Please check your payment history in a few minutes. If you entered your PIN, the payment should be processed shortly.
        </div>
    `;
    
    const statusModal = new bootstrap.Modal(document.getElementById('paymentStatusModal'));
    statusModal.show();
}

function cancelStkWaiting() {
    clearInterval(pollInterval);
    bootstrap.Modal.getInstance(document.getElementById('stkWaitingModal')).hide();
    showAlert('info', 'Payment check cancelled. Please refresh the page to see your payment status.');
}
</script>

</body>
</html> 