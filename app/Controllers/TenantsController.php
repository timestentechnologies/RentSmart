<?php

namespace App\Controllers;

use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Payment;
use App\Database\Connection;

class TenantsController
{
    private $tenant;
    private $property;
    private $unit;
    private $db;

    public function __construct()
    {
        $this->tenant = new Tenant();
        $this->property = new Property();
        $this->unit = new Unit();
        $this->db = Connection::getInstance()->getConnection();
    }

    public function index()
    {
        try {
            error_log("TenantsController::index - Starting to load tenants and properties");
            
            $userId = $_SESSION['user_id'];
            $tenants = $this->tenant->getAll($userId);
            error_log("TenantsController::index - Loaded " . count($tenants) . " tenants");

            // Align rent due calculation with tenant portal (sum missed rent months)
            try {
                $paymentModel = new Payment();
                foreach ($tenants as &$tenant) {
                    $tenantId = isset($tenant['id']) ? (int)$tenant['id'] : 0;
                    if ($tenantId <= 0) {
                        $tenant['rent_due_total'] = 0.0;
                        $tenant['rent_due_months'] = 0;
                        $tenant['is_advance_paid'] = false;
                        continue;
                    }

                    $missed = $paymentModel->getTenantMissedRentMonths($tenantId);
                    $due = 0.0;
                    if (is_array($missed)) {
                        foreach ($missed as $mm) {
                            $due += isset($mm['amount']) ? (float)$mm['amount'] : 0.0;
                        }
                    }
                    $tenant['rent_due_total'] = round(max(0.0, $due), 2);
                    $tenant['rent_due_months'] = is_array($missed) ? count($missed) : 0;

                    $coverage = $paymentModel->getTenantRentCoverage($tenantId);
                    $tenant['is_advance_paid'] = is_array($coverage)
                        && isset($coverage['prepaid_months'])
                        && (int)$coverage['prepaid_months'] > 0;
                }
                unset($tenant);
            } catch (\Throwable $e) {
                // Fail safe: keep old one-month computation in view
            }
            
            $properties = $this->property->getAll($userId);
            error_log("TenantsController::index - Loaded " . count($properties) . " properties");
            error_log("TenantsController::index - Properties: " . print_r($properties, true));
            
            // Get only vacant units
            $vacantUnits = $this->unit->getVacantUnits($userId);
            
            echo view('tenants/index', [
                'title' => 'Tenants - RentSmart',
                'tenants' => $tenants,
                'properties' => $properties,
                'vacant_units' => $vacantUnits
            ]);
            
            error_log("TenantsController::index - View rendered successfully");
        } catch (\Exception $e) {
            error_log("Error in TenantsController::index: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            echo '<pre style="color:red;">' . $e->getMessage() . '</pre>';
            exit;
        }
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            // Generate random password
            $plainPassword = bin2hex(random_bytes(4));
            $data['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
            $tenantId = $this->tenant->create($data);
            if ($tenantId) {
                // Send tenant portal credentials email using PHPMailer (same as store)
                try {
                    $settingModel = new \App\Models\Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
                    $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                    $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = $settings['smtp_port'] ?? 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->isHTML(true);
                    $propertyName = '';
                    if (!empty($data['property_id'])) {
                        $property = $this->property->getById($data['property_id']);
                        if ($property && isset($property['name'])) {
                            $propertyName = $property['name'];
                        }
                    }
                    if ($data['email']) {
                        $mail->clearAddresses();
                        $mail->addAddress($data['email'], $data['name']);
                        $mail->Subject = 'Your RentSmart Tenant Portal Credentials';
                        $mail->Body = '
                            <div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                            . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') . '
                            <p style="font-size:16px;">Dear ' . htmlspecialchars($data['name']) . ',</p>
                            <p>Your tenant portal account has been created successfully.</p>'
                            . ($propertyName ? '<p><strong>Property:</strong> ' . htmlspecialchars($propertyName) . '</p>' : '') . '
                            <p>Login Credentials:</p>
                            <ul style="font-size:15px;">
                                <li><strong>Login Email:</strong> ' . htmlspecialchars($data['email']) . '</li>
                                <li><strong>Password:</strong> ' . htmlspecialchars($plainPassword) . '</li>
                                <li><strong>Portal URL:</strong> <a href="https://rentsmart.timestentechnologies.co.ke"> https://rentsmart.timestentechnologies.co.ke/</a></li>
                            </ul>
                            <p>Please change your password after your first login for security.</p>
                            <p>Please keep this information for your records.</p>
                            <p>Thank you,<br>RentSmart Team</p>'
                            . $footer . '
                            </div>';
                        $mail->send();
                    }
                } catch (\Exception $e) {
                    error_log('Failed to send tenant portal credentials email: ' . $e->getMessage());
                }
            }
            header('Location: /tenants');
            exit;
        }
        echo view('tenants/create', [
            'title' => 'Add Tenant - RentSmart'
        ]);
    }

    public function store()
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        error_log('TenantsController::store called. AJAX: ' . (isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : 'none'));
        try {
            // Validate input
            $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
            $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
            $name = trim($firstName . ' ' . $lastName);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $idType = filter_input(INPUT_POST, 'id_type', FILTER_SANITIZE_STRING);
            $idNumber = filter_input(INPUT_POST, 'id_number', FILTER_SANITIZE_STRING);
            $registeredOn = filter_input(INPUT_POST, 'registered_on', FILTER_SANITIZE_STRING);
            $emergencyContact = filter_input(INPUT_POST, 'emergency_contact', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            
            // Optional property assignment fields
            $propertyId = filter_input(INPUT_POST, 'property_id', FILTER_SANITIZE_NUMBER_INT);
            $unitId = filter_input(INPUT_POST, 'unit_id', FILTER_SANITIZE_NUMBER_INT);

            if (!$firstName || !$lastName || !$email || !$phone) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'First name, last name, email and phone are required']);
                    exit;
                }
                throw new \Exception('First name, last name, email and phone are required');
            }

            // Start transaction
            $txStarted = false;
            try {
                $txStarted = (bool)$this->db->beginTransaction();
            } catch (\Throwable $e) {
                $txStarted = false;
            }

            try {
                // Create tenant
                $data = [
                    'name' => $name,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'id_type' => $idType,
                    'id_number' => $idNumber,
                    'registered_on' => $registeredOn,
                    'emergency_contact' => $emergencyContact,
                    'notes' => $notes,
                    'created_at' => date('Y-m-d H:i:s'),
                    'property_id' => $propertyId ? $propertyId : null,
                    'unit_id' => $unitId ? $unitId : null
                ];

                // Normalize registration date for downstream defaults
                $registeredOnNormalized = trim((string)$registeredOn);
                if ($registeredOnNormalized === '' || strtotime($registeredOnNormalized) === false) {
                    $registeredOnNormalized = date('Y-m-d');
                }
                $data['registered_on'] = $registeredOnNormalized;

                // Generate random password for tenant portal
                $plainPassword = bin2hex(random_bytes(4));
                $data['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);

                $tenantId = $this->tenant->create($data);

                // Send tenant portal credentials email
                try {
                    $settingModel = new \App\Models\Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    
                    // Get logo URL and footer
                    $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
                    $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                    $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
                    
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = $settings['smtp_port'] ?? 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->isHTML(true);

                    // Email tenant portal credentials
                    if ($data['email']) {
                        $mail->clearAddresses();
                        $mail->addAddress($data['email'], $data['name']);
                        $mail->Subject = 'Your RentSmart Tenant Portal Credentials';
                        $mail->Body = '
                            <div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                            . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') . '
                            <p style="font-size:16px;">Dear ' . htmlspecialchars($data['name']) . ',</p>
                            <p>Your tenant portal account has been created successfully.</p>
                            <p>Login Credentials:</p>
                            <ul style="font-size:15px;">
                                <li><strong>Login Email:</strong> ' . htmlspecialchars($data['email']) . '</li>
                                <li><strong>Password:</strong> ' . htmlspecialchars($plainPassword) . '</li>
                                <li><strong>Portal URL:</strong> <a href="https://rentsmart.timestentechnologies.co.ke"> https://rentsmart.timestentechnologies.co.ke/</a></li>
                            </ul>
                            <p>Please change your password after your first login for security.</p>
                            <p>Please keep this information for your records.</p>
                            <p>Thank you,<br>RentSmart Team</p>'
                            . $footer . '
                            </div>';
                        $mail->send();
                    }
                } catch (\Exception $e) {
                    error_log('Failed to send tenant portal credentials email: ' . $e->getMessage());
                }

                // If property and unit are selected (optional)
                if ($propertyId && $unitId) {
                    // Fetch current unit data with user ID for access
                    $currentUnit = $this->unit->getById($unitId, $_SESSION['user_id']);
                    $unitData = [
                        'unit_number' => $currentUnit['unit_number'],
                        'type' => $currentUnit['type'],
                        'size' => $currentUnit['size'],
                        'rent_amount' => $currentUnit['rent_amount'],
                        'status' => 'occupied',
                        'tenant_id' => $tenantId
                    ];
                    $this->unit->update($unitId, $unitData);
                    // Create lease (security deposit equals rent)
                    $leaseModel = new \App\Models\Lease();
                    $effectiveRent = (float)($currentUnit['rent_amount'] ?? 0);

                    // Lease start date defaults to tenant registration date (or today) if not explicitly provided
                    $leaseStartDate = $registeredOnNormalized;
                    if ($leaseStartDate === '' || strtotime($leaseStartDate) === false) {
                        $leaseStartDate = date('Y-m-d');
                    }
                    $leaseData = [
                        'unit_id' => $unitId,
                        'tenant_id' => $tenantId,
                        'start_date' => $leaseStartDate,
                        'end_date' => date('Y-m-d', strtotime($leaseStartDate . ' +1 year')),
                        'rent_amount' => $effectiveRent,
                        'security_deposit' => $effectiveRent,
                        'status' => 'active',
                        'payment_day' => 1,
                        'notes' => '',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $leaseId = (int)$leaseModel->create($leaseData);

                    // Auto-create draft invoices immediately for this lease (idempotent)
                    try {
                        $inv = new \App\Models\Invoice();
                        $inv->ensureInvoicesForLeaseMonths((int)$tenantId, (float)$effectiveRent, (string)$leaseData['start_date'], date('Y-m-d'), (int)($_SESSION['user_id'] ?? 0), 'AUTO');
                    } catch (\Exception $e) { error_log('Auto-invoice (tenant assign) failed: ' . $e->getMessage()); }

                    // Send email notifications
                    try {
                        $settingModel = new \App\Models\Setting();
                        $settings = $settingModel->getAllAsAssoc();
                        $userModel = new \App\Models\User();
                        $propertyModel = new \App\Models\Property();

                        // Get property details
                        $property = $propertyModel->getById($propertyId);

                        if ($property) {
                            // Get logo URL and footer
                            $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
                            $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                            $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';

                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $settings['smtp_host'] ?? '';
                            $mail->Port = $settings['smtp_port'] ?? 587;
                            $mail->SMTPAuth = true;
                            $mail->Username = $settings['smtp_user'] ?? '';
                            $mail->Password = $settings['smtp_pass'] ?? '';
                            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                            $mail->isHTML(true);

                            // Email to tenant
                            if ($data['email']) {
                                $mail->clearAddresses();
                                $mail->addAddress($data['email'], $data['name']);
                                $mail->Subject = 'Welcome to ' . $property['name'];
                                $mail->Body = '
                                    <div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                                    . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') . '
                                    <p style="font-size:16px;">Dear ' . htmlspecialchars($data['name']) . ',</p>
                                    <p>Welcome to ' . htmlspecialchars($property['name']) . '! Your tenancy has been registered successfully.</p>
                                    <p>Property Details:</p>
                                    <ul style="font-size:15px;">
                                        <li><strong>Property:</strong> ' . htmlspecialchars($property['name']) . '</li>
                                        <li><strong>Unit:</strong> ' . htmlspecialchars($currentUnit['unit_number']) . '</li>
                                        <li><strong>Monthly Rent:</strong> Ksh ' . number_format($effectiveRent, 2) . '</li>
                                        <li><strong>Start Date:</strong> ' . htmlspecialchars($leaseStartDate) . '</li>
                                    </ul>
                                    <p>Thank you for choosing ' . htmlspecialchars($property['name']) . '.</p>
                                    <p>Best regards,<br>RentSmart Team</p>'
                                    . $footer . '
                                    </div>';
                                $mail->send();
                            }

                            // Email to property owner
                            if ($property['owner_id']) {
                                $owner = $userModel->find($property['owner_id']);
                                if ($owner && $owner['email']) {
                                    $mail->clearAddresses();
                                    $mail->addAddress($owner['email'], $owner['name']);
                                    $mail->Subject = 'New Tenant Registration - ' . $property['name'];
                                    $mail->Body = '
                                        <div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') . '
                                        <p style="font-size:16px;">Dear ' . htmlspecialchars($owner['name']) . ',</p>
                                        <p>A new tenant has been registered for your property.</p>
                                        <p>Details:</p>
                                        <ul style="font-size:15px;">
                                            <li><strong>Property:</strong> ' . htmlspecialchars($property['name']) . '</li>
                                            <li><strong>Unit:</strong> ' . htmlspecialchars($currentUnit['unit_number']) . '</li>
                                            <li><strong>Tenant Name:</strong> ' . htmlspecialchars($data['name']) . '</li>
                                            <li><strong>Tenant Email:</strong> ' . htmlspecialchars($data['email']) . '</li>
                                            <li><strong>Tenant Phone:</strong> ' . htmlspecialchars($data['phone']) . '</li>
                                            <li><strong>Monthly Rent:</strong> Ksh ' . number_format($effectiveRent, 2) . '</li>
                                            <li><strong>Start Date:</strong> ' . htmlspecialchars($leaseStartDate) . '</li>
                                        </ul>
                                        <p>Login to your dashboard for more details.</p>
                                        <p>Best regards,<br>RentSmart Team</p>'
                                        . $footer . '
                                        </div>';
                                    $mail->send();
                                }
                            }

                            // Email to property manager
                            if ($property['manager_id']) {
                                $manager = $userModel->find($property['manager_id']);
                                if ($manager && $manager['email']) {
                                    $mail->clearAddresses();
                                    $mail->addAddress($manager['email'], $manager['name']);
                                    $mail->Subject = 'New Tenant Registration - ' . $property['name'];
                                    $mail->Body = '
                                        <div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') . '
                                        <p style="font-size:16px;">Dear ' . htmlspecialchars($manager['name']) . ',</p>
                                        <p>A new tenant has been registered for the property you manage.</p>
                                        <p>Details:</p>
                                        <ul style="font-size:15px;">
                                            <li><strong>Property:</strong> ' . htmlspecialchars($property['name']) . '</li>
                                            <li><strong>Unit:</strong> ' . htmlspecialchars($currentUnit['unit_number']) . '</li>
                                            <li><strong>Tenant Name:</strong> ' . htmlspecialchars($data['name']) . '</li>
                                            <li><strong>Tenant Email:</strong> ' . htmlspecialchars($data['email']) . '</li>
                                            <li><strong>Tenant Phone:</strong> ' . htmlspecialchars($data['phone']) . '</li>
                                            <li><strong>Monthly Rent:</strong> Ksh ' . number_format($effectiveRent, 2) . '</li>
                                            <li><strong>Start Date:</strong> ' . htmlspecialchars($leaseStartDate) . '</li>
                                        </ul>
                                        <p>Login to your dashboard for more details.</p>
                                        <p>Best regards,<br>RentSmart Team</p>'
                                        . $footer . '
                                        </div>';
                                    $mail->send();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('Failed to send tenant registration notification emails: ' . $e->getMessage());
                    }
                }

                if ($txStarted && $this->db->inTransaction()) {
                    $this->db->commit();
                }
                if ($isAjax) {
                    // Fetch the newly created tenant with all joined info
                    $newTenant = $this->tenant->getById($tenantId);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tenant added successfully',
                        'tenant' => $newTenant,
                        'credentials' => [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'password' => $plainPassword,
                            'portal_url' => 'https://rentsmart.timestentechnologies.co.ke/'
                        ]
                    ]);
                    exit;
                }
                $_SESSION['flash_message'] = 'Tenant added successfully';
                $_SESSION['flash_type'] = 'success';
            } catch (\Exception $e) {
                if ($txStarted && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                error_log('TenantsController::store transaction error: ' . $e->getMessage());
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
                throw $e;
            }

            // Redirect to /tenants (not /tenants/store) after successful non-AJAX add
            redirect('/tenants');
        } catch (\Exception $e) {
            error_log('TenantsController::store error: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/tenants');
        }
    }

    public function edit($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            $tenant = $this->tenant->getById($id, $userId);
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            echo view('tenants/edit', [
                'title' => 'Edit Tenant - RentSmart',
                'tenant' => $tenant
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/tenants');
        }
    }

    public function update($id)
    {
        try {
            // Validate input
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $propertyId = filter_input(INPUT_POST, 'property_id', FILTER_SANITIZE_NUMBER_INT);
            $unitId = filter_input(INPUT_POST, 'unit_id', FILTER_SANITIZE_NUMBER_INT);

            if (!$name || !$email || !$phone) {
                throw new \Exception('All fields are required');
            }

            // Start transaction
            $txStarted = false;
            try {
                $txStarted = (bool)$this->db->beginTransaction();
            } catch (\Throwable $e) {
                $txStarted = false;
            }

            try {
                // Update tenant
                $data = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'property_id' => $propertyId ? $propertyId : null,
                    'unit_id' => $unitId ? $unitId : null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->tenant->update($id, $data);

                // Handle unit assignment - create or update lease if unit is assigned
                if ($unitId) {
                    // Check if there's already an active lease for this tenant
                    $leaseModel = new \App\Models\Lease();
                    $existingLease = $leaseModel->getActiveLeaseByTenant($id);

                    // If duplicates already exist (multiple active leases), inactivate all but latest
                    try {
                        $stmtActives = $this->db->prepare("SELECT id, unit_id FROM leases WHERE tenant_id = ? AND status = 'active' ORDER BY id DESC");
                        $stmtActives->execute([(int)$id]);
                        $activeRows = $stmtActives->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                        if (count($activeRows) > 1) {
                            $keep = $activeRows[0];
                            $idsToInactivate = [];
                            $unitsToVacate = [];
                            foreach (array_slice($activeRows, 1) as $r) {
                                $idsToInactivate[] = (int)$r['id'];
                                if (!empty($r['unit_id'])) {
                                    $unitsToVacate[] = (int)$r['unit_id'];
                                }
                            }
                            if (!empty($idsToInactivate)) {
                                $in = implode(',', array_fill(0, count($idsToInactivate), '?'));
                                $params = array_merge([date('Y-m-d H:i:s')], $idsToInactivate);
                                $this->db->prepare("UPDATE leases SET status = 'terminated', updated_at = ? WHERE id IN ($in)")->execute($params);
                            }
                            foreach ($unitsToVacate as $uId) {
                                if ((int)$uId !== (int)$unitId) {
                                    $this->unit->update((int)$uId, ['status' => 'vacant', 'tenant_id' => null]);
                                }
                            }
                            $existingLease = $leaseModel->getActiveLeaseByTenant($id);
                        }
                    } catch (\Throwable $e) {
                        // ignore cleanup failure
                    }
                    
                    if ($existingLease) {
                        // Update existing lease with new unit
                        $leaseData = [
                            'unit_id' => $unitId,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $leaseModel->update($existingLease['id'], $leaseData);
                        
                        // Update unit status
                        $this->unit->update($unitId, [
                            'status' => 'occupied',
                            'tenant_id' => $id
                        ]);
                        
                        // Set previous unit to vacant if different
                        if ($existingLease['unit_id'] != $unitId) {
                            $this->unit->update($existingLease['unit_id'], [
                                'status' => 'vacant',
                                'tenant_id' => null
                            ]);
                        }
                    } else {
                        // No active lease: reactivate most recent inactive lease if exists (avoid duplicates)
                        $inactiveLease = $leaseModel->getLatestInactiveLeaseByTenant($id);
                        if ($inactiveLease) {
                            $leaseModel->update($inactiveLease['id'], [
                                'unit_id' => $unitId,
                                'status' => 'active',
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);

                            // Update unit status
                            $this->unit->update($unitId, [
                                'status' => 'occupied',
                                'tenant_id' => $id
                            ]);

                            // Vacate previous unit if different
                            if (!empty($inactiveLease['unit_id']) && (int)$inactiveLease['unit_id'] !== (int)$unitId) {
                                $this->unit->update((int)$inactiveLease['unit_id'], [
                                    'status' => 'vacant',
                                    'tenant_id' => null
                                ]);
                            }
                        } else {
                            // Create new lease (security deposit equals rent)
                            $currentUnit = $this->unit->getById($unitId, $_SESSION['user_id']);
                            $effectiveRent = (float)($currentUnit['rent_amount'] ?? 0);

                            // Default lease start date to tenant registration date (or today)
                            $tenantRow = $this->tenant->getById($id, $_SESSION['user_id']);
                            $leaseStartDate = trim((string)($tenantRow['registered_on'] ?? ''));
                            if ($leaseStartDate === '' || strtotime($leaseStartDate) === false) {
                                $leaseStartDate = date('Y-m-d');
                            }
                            $leaseData = [
                                'unit_id' => $unitId,
                                'tenant_id' => $id,
                                'start_date' => $leaseStartDate,
                                'end_date' => date('Y-m-d', strtotime($leaseStartDate . ' +1 year')),
                                'rent_amount' => $effectiveRent,
                                'security_deposit' => $effectiveRent,
                                'status' => 'active',
                                'payment_day' => 1,
                                'notes' => '',
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            $leaseId = (int)$leaseModel->create($leaseData);

                            // Auto-create draft invoices immediately for this lease (idempotent)
                            try {
                                $inv = new \App\Models\Invoice();
                                $inv->ensureInvoicesForLeaseMonths((int)$id, (float)$effectiveRent, (string)$leaseData['start_date'], date('Y-m-d'), (int)($_SESSION['user_id'] ?? 0), 'AUTO');
                            } catch (\Exception $e) { error_log('Auto-invoice (tenant update assign) failed: ' . $e->getMessage()); }
                            
                            // Update unit status
                            $this->unit->update($unitId, [
                                'status' => 'occupied',
                                'tenant_id' => $id
                            ]);
                        }
                    }
                } else {
                    // If no unit assigned, deactivate ALL active leases (handles duplicates)
                    $leaseModel = new \App\Models\Lease();
                    try {
                        $stmtUnits = $this->db->prepare("SELECT DISTINCT unit_id FROM leases WHERE tenant_id = ? AND status = 'active' AND unit_id IS NOT NULL");
                        $stmtUnits->execute([(int)$id]);
                        $units = $stmtUnits->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                        $this->db->prepare("UPDATE leases SET status = 'terminated', updated_at = ? WHERE tenant_id = ? AND status = 'active'")
                            ->execute([date('Y-m-d H:i:s'), (int)$id]);

                        foreach ($units as $row) {
                            $uId = (int)($row['unit_id'] ?? 0);
                            if ($uId > 0) {
                                $this->unit->update($uId, ['status' => 'vacant', 'tenant_id' => null]);
                            }
                        }
                    } catch (\Throwable $e) {
                        // fallback to previous behavior
                        $existingLease = $leaseModel->getActiveLeaseByTenant($id);
                        if ($existingLease) {
                            $leaseModel->update($existingLease['id'], [
                                'status' => 'terminated',
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            $this->unit->update($existingLease['unit_id'], [
                                'status' => 'vacant',
                                'tenant_id' => null
                            ]);
                        }
                    }
                }

                if ($txStarted && $this->db->inTransaction()) {
                    $this->db->commit();
                }

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    $updatedTenant = $this->tenant->getById($id, $_SESSION['user_id']);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tenant updated successfully',
                        'tenant' => $updatedTenant
                    ]);
                    exit;
                }

                $_SESSION['flash_message'] = 'Tenant updated successfully';
                $_SESSION['flash_type'] = 'success';
                redirect('/tenants');
            } catch (\Exception $e) {
                if ($txStarted && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect("/tenants/edit/{$id}");
        }
    }

    public function delete($id = null)
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        try {
            $tenantId = (int)$id;
            if ($tenantId <= 0) {
                throw new \Exception('Invalid tenant');
            }

            try {
                $settings = new \App\Models\Setting();
                $raw = (string)($settings->get('demo_protected_tenant_ids_json') ?? '[]');
                $ids = json_decode($raw, true);
                $ids = is_array($ids) ? array_map('intval', $ids) : [];
                if (in_array((int)$tenantId, $ids, true)) {
                    throw new \Exception('Demo data cannot be deleted');
                }
            } catch (\Exception $e) {
                throw $e;
            } catch (\Throwable $e) {
            }

            $this->db->beginTransaction();

            // Find units occupied by this tenant via active lease(s)
            $unitIds = [];
            try {
                $stmt = $this->db->prepare("SELECT DISTINCT unit_id FROM leases WHERE tenant_id = ? AND status = 'active' AND unit_id IS NOT NULL");
                $stmt->execute([(int)$tenantId]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $uId = (int)($r['unit_id'] ?? 0);
                    if ($uId > 0) $unitIds[$uId] = true;
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Also check units table in case it is used as authoritative assignment
            try {
                $stmt = $this->db->prepare("SELECT id FROM units WHERE tenant_id = ?");
                $stmt->execute([(int)$tenantId]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $uId = (int)($r['id'] ?? 0);
                    if ($uId > 0) $unitIds[$uId] = true;
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Terminate any active leases for this tenant
            try {
                $this->db->prepare("UPDATE leases SET status = 'terminated', updated_at = NOW() WHERE tenant_id = ? AND status = 'active'")
                    ->execute([(int)$tenantId]);
            } catch (\Throwable $e) {
                // ignore
            }

            // Vacate any occupied units
            if (!empty($unitIds)) {
                $ids = array_keys($unitIds);
                foreach ($ids as $uId) {
                    $this->unit->update((int)$uId, [
                        'status' => 'vacant',
                        'tenant_id' => null
                    ]);
                }
            }

            // Delete the tenant
            $this->tenant->delete($tenantId);

            $this->db->commit();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Tenant deleted successfully']);
                exit;
            }
            $_SESSION['flash_message'] = 'Tenant deleted successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            try {
                if ($this->db && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (\Throwable $t) {
            }
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error deleting tenant']);
                exit;
            }
            $_SESSION['flash_message'] = 'Error deleting tenant';
            $_SESSION['flash_type'] = 'danger';
        }
        redirect('/tenants');
    }

    public function get($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            $tenant = $this->tenant->getById($id, $userId);
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'tenant' => $tenant]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function whatsappCredentials($id)
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Invalid request method');
            }

            if (empty($_SESSION['user_id'])) {
                throw new \Exception('Unauthorized');
            }

            $userId = (int)$_SESSION['user_id'];
            $tenantId = (int)$id;
            if ($tenantId <= 0) {
                throw new \Exception('Invalid tenant');
            }

            $tenant = $this->tenant->getById($tenantId, $userId);
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            $plainPassword = bin2hex(random_bytes(4));
            $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $ok = $this->tenant->update($tenantId, ['password' => $hash]);
            if (!$ok) {
                throw new \Exception('Failed to reset tenant password');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'credentials' => [
                    'name' => (string)($tenant['name'] ?? ''),
                    'email' => (string)($tenant['email'] ?? ''),
                    'phone' => (string)($tenant['phone'] ?? ''),
                    'password' => $plainPassword,
                    'portal_url' => 'https://rentsmart.timestentechnologies.co.ke/'
                ]
            ]);
            exit;
        } catch (\Exception $e) {
            if (!$isAjax) {
                $_SESSION['flash_message'] = $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
                redirect('/tenants');
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function export($format = 'csv')
    {
        try {
            $userId = $_SESSION['user_id'];
            $tenants = $this->tenant->getAll($userId);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="tenants.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Email','Phone','Property','Unit','Rent','Status','Registered On']);
                foreach ($tenants as $t) {
                    $hasProperty = isset($t['property_name']) && $t['property_name'] !== null && $t['property_name'] !== '-';
                    $hasUnit = isset($t['unit_number']) && $t['unit_number'] !== null && $t['unit_number'] !== '-';
                    $statusText = $hasProperty ? ($hasUnit ? 'Active' : 'Pending Unit') : 'Inactive';
                    fputcsv($out, [
                        $t['name'] ?? '',
                        $t['email'] ?? '',
                        $t['phone'] ?? '',
                        $t['property_name'] ?? '',
                        $t['unit_number'] ?? '',
                        isset($t['rent_amount']) ? $t['rent_amount'] : '',
                        $statusText,
                        $t['registered_on'] ?? ''
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="tenants.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Name</th><th>Email</th><th>Phone</th><th>Property</th><th>Unit</th><th>Rent</th><th>Status</th><th>Registered On</th></tr>';
                foreach ($tenants as $t) {
                    $hasProperty = isset($t['property_name']) && $t['property_name'] !== null && $t['property_name'] !== '-';
                    $hasUnit = isset($t['unit_number']) && $t['unit_number'] !== null && $t['unit_number'] !== '-';
                    $statusText = $hasProperty ? ($hasUnit ? 'Active' : 'Pending Unit') : 'Inactive';
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($t['name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['email'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['phone'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars(isset($t['rent_amount']) ? $t['rent_amount'] : '').'</td>'
                        .'<td>'.htmlspecialchars($statusText).'</td>'
                        .'<td>'.htmlspecialchars($t['registered_on'] ?? '').'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }

            if ($format === 'pdf') {
                $html = '<h3>Tenants</h3><table width="100%" border="1" cellspacing="0" cellpadding="4">'
                    .'<tr><th>Name</th><th>Email</th><th>Phone</th><th>Property</th><th>Unit</th><th>Rent</th><th>Status</th><th>Registered On</th></tr>';
                foreach ($tenants as $t) {
                    $hasProperty = isset($t['property_name']) && $t['property_name'] !== null && $t['property_name'] !== '-';
                    $hasUnit = isset($t['unit_number']) && $t['unit_number'] !== null && $t['unit_number'] !== '-';
                    $statusText = $hasProperty ? ($hasUnit ? 'Active' : 'Pending Unit') : 'Inactive';
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($t['name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['email'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['phone'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($t['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars(isset($t['rent_amount']) ? $t['rent_amount'] : '').'</td>'
                        .'<td>'.htmlspecialchars($statusText).'</td>'
                        .'<td>'.htmlspecialchars($t['registered_on'] ?? '').'</td>'
                        .'</tr>';
                }
                $html .= '</table>';
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="tenants.pdf"');
                echo $dompdf->output();
                exit;
            }

            http_response_code(400);
            echo 'Unsupported format';
        } catch (\Exception $e) {
            http_response_code(500);
            echo 'Export error';
        }
        exit;
    }

    public function template()
    {
        $templateFile = __DIR__ . '/../../public/templates/tenants_template.csv';
        
        if (file_exists($templateFile)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="tenants_template.csv"');
            readfile($templateFile);
            exit;
        }
        
        // Fallback to empty template if file doesn't exist
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tenants_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['first_name','last_name','email','phone','id_number','move_in_date','property_name','unit_number']);
        fclose($out);
        exit;
    }

    public function import()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
                http_response_code(400);
                echo 'No file uploaded';
                return;
            }

            $normalizeDate = function ($value, $fallback = null) {
                $raw = trim((string)($value ?? ''));
                if ($raw === '') {
                    return $fallback;
                }

                if (is_numeric($raw)) {
                    $days = (int)$raw;
                    if ($days > 0) {
                        $dt = (new \DateTime('1899-12-30'))->modify('+' . $days . ' days');
                        return $dt->format('Y-m-d');
                    }
                }

                $dt = \DateTime::createFromFormat('Y-m-d', $raw) ?: null;
                if (!$dt) {
                    $dt = \DateTime::createFromFormat('m/d/Y', $raw) ?: null;
                }
                if (!$dt) {
                    $dt = \DateTime::createFromFormat('d/m/Y', $raw) ?: null;
                }
                if (!$dt) {
                    $ts = strtotime($raw);
                    if ($ts !== false) {
                        return date('Y-m-d', $ts);
                    }
                    return $fallback;
                }

                return $dt->format('Y-m-d');
            };

            $tmp = $_FILES['file']['tmp_name'];
            if (!is_uploaded_file($tmp)) {
                throw new \Exception('Invalid upload');
            }
            $handle = fopen($tmp, 'r');
            if ($handle === false) throw new \Exception('Cannot open uploaded file');
            $header = fgetcsv($handle);
            $created = 0;
            $updated = 0;
            $assigned = 0;
            $userId = $_SESSION['user_id'];
            $importedOn = date('Y-m-d');
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (empty($data['email']) || empty($data['first_name']) || empty($data['last_name'])) continue;
                
                // Check if tenant exists by email
                $existing = method_exists($this->tenant, 'findByEmail') ? $this->tenant->findByEmail($data['email']) : null;

                // Map property and unit if provided
                $propertyId = null;
                $unitId = null;
                $propertyName = trim((string)($data['property_name'] ?? ''));
                $unitNumber = trim((string)($data['unit_number'] ?? ''));
                if ($propertyName !== '' && $unitNumber !== '') {
                    $property = null;
                    foreach ($this->property->getAll($userId) as $p) {
                        if (strcasecmp($p['name'], $propertyName) === 0) { $property = $p; break; }
                    }
                    if ($property && !empty($property['id'])) {
                        $propertyId = (int)$property['id'];
                        foreach ($this->unit->where('property_id', $propertyId, $userId) as $u) {
                            if (strcasecmp((string)($u['unit_number'] ?? ''), $unitNumber) === 0) {
                                $unitId = (int)$u['id'];
                                break;
                            }
                        }
                    }
                }
                
                $payload = [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? '',
                    'registered_on' => $data['registered_on'] ?? ($data['move_in_date'] ?? $importedOn),
                ];

                $payload['registered_on'] = $normalizeDate($payload['registered_on'], $importedOn) ?? $importedOn;

                if ($propertyId) {
                    $payload['property_id'] = $propertyId;
                }
                if ($unitId) {
                    $payload['unit_id'] = $unitId;
                }
                
                if ($existing) {
                    // Update existing tenant
                    if ($this->tenant->update($existing['id'], $payload)) {
                        $updated++;
                    }

                    // Assign to unit + create lease if mapped and unit is not currently occupied
                    if ($propertyId && $unitId) {
                        $currentUnit = $this->unit->getById($unitId, $userId);
                        if ($currentUnit) {
                            $unitData = [
                                'unit_number' => $currentUnit['unit_number'],
                                'type' => $currentUnit['type'],
                                'size' => $currentUnit['size'],
                                'rent_amount' => $currentUnit['rent_amount'],
                                'status' => 'occupied',
                                'tenant_id' => (int)$existing['id'],
                            ];
                            $this->unit->update($unitId, $unitData);

                            $leaseModel = new \App\Models\Lease();
                            $lease = $leaseModel->getActiveLeaseByTenant((int)$existing['id']);
                            if (!$lease) {
                                $effectiveRent = (float)($currentUnit['rent_amount'] ?? 0);
                                $startDate = $normalizeDate($data['move_in_date'] ?? null, (string)$payload['registered_on']) ?? (string)$payload['registered_on'];
                                $endDate = $normalizeDate($data['end_date'] ?? null, date('Y-m-d', strtotime($startDate . ' +1 year'))) ?? date('Y-m-d', strtotime($startDate . ' +1 year'));
                                $leaseData = [
                                    'unit_id' => $unitId,
                                    'tenant_id' => (int)$existing['id'],
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'rent_amount' => $effectiveRent,
                                    'security_deposit' => $effectiveRent,
                                    'status' => 'active',
                                    'payment_day' => 1,
                                    'notes' => '',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
                                $leaseId = (int)$leaseModel->create($leaseData);
                                if ($leaseId > 0) {
                                    $assigned++;
                                }
                            } else {
                                $moveInRaw = trim((string)($data['move_in_date'] ?? ''));
                                $endRaw = trim((string)($data['end_date'] ?? ''));

                                $hasMoveIn = $moveInRaw !== '';
                                $hasEnd = $endRaw !== '';

                                $updates = [];
                                if ($hasMoveIn) {
                                    $updates['start_date'] = $normalizeDate($moveInRaw, (string)($lease['start_date'] ?? $payload['registered_on']))
                                        ?? (string)($lease['start_date'] ?? $payload['registered_on']);
                                }

                                if ($hasEnd) {
                                    $updates['end_date'] = $normalizeDate($endRaw, (string)($lease['end_date'] ?? null))
                                        ?? (string)($lease['end_date'] ?? null);
                                }

                                // If previously-imported lease dates were invalid/empty, force-correct them.
                                $existingStart = trim((string)($lease['start_date'] ?? ''));
                                if (!$hasMoveIn && ($existingStart === '' || $existingStart === '0000-00-00' || strtotime($existingStart) === false)) {
                                    $fixedStart = $normalizeDate($data['move_in_date'] ?? null, (string)$payload['registered_on']) ?? (string)$payload['registered_on'];
                                    $updates['start_date'] = $fixedStart;
                                }

                                $existingEnd = trim((string)($lease['end_date'] ?? ''));
                                if (!$hasEnd && ($existingEnd === '' || $existingEnd === '0000-00-00' || strtotime($existingEnd) === false)) {
                                    $baseStart = (string)($updates['start_date'] ?? ($existingStart !== '' ? $existingStart : $payload['registered_on']));
                                    $updates['end_date'] = date('Y-m-d', strtotime($baseStart . ' +1 year'));
                                }

                                if (!empty($updates)) {
                                    $leaseModel->update((int)$lease['id'], $updates);
                                }

                                $assigned++;
                            }
                        }
                    }
                } else {
                    // Create new tenant
                    $tenantId = $this->tenant->create($payload);
                    if ($tenantId) {
                        $created++;

                        if ($propertyId && $unitId) {
                            $currentUnit = $this->unit->getById($unitId, $userId);
                            if ($currentUnit) {
                                $unitData = [
                                    'unit_number' => $currentUnit['unit_number'],
                                    'type' => $currentUnit['type'],
                                    'size' => $currentUnit['size'],
                                    'rent_amount' => $currentUnit['rent_amount'],
                                    'status' => 'occupied',
                                    'tenant_id' => (int)$tenantId,
                                ];
                                $this->unit->update($unitId, $unitData);

                                $leaseModel = new \App\Models\Lease();
                                $effectiveRent = (float)($currentUnit['rent_amount'] ?? 0);
                                $startDate = $normalizeDate($data['move_in_date'] ?? null, (string)$payload['registered_on']) ?? (string)$payload['registered_on'];
                                $endDate = $normalizeDate($data['end_date'] ?? null, date('Y-m-d', strtotime($startDate . ' +1 year'))) ?? date('Y-m-d', strtotime($startDate . ' +1 year'));
                                $leaseData = [
                                    'unit_id' => $unitId,
                                    'tenant_id' => (int)$tenantId,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'rent_amount' => $effectiveRent,
                                    'security_deposit' => $effectiveRent,
                                    'status' => 'active',
                                    'payment_day' => 1,
                                    'notes' => '',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
                                $leaseId = (int)$leaseModel->create($leaseData);
                                if ($leaseId > 0) {
                                    $assigned++;
                                }
                            }
                        }
                    }
                }
            }
            fclose($handle);
            $message = [];
            if ($created > 0) $message[] = "Created {$created}";
            if ($updated > 0) $message[] = "Updated {$updated}";
            if ($assigned > 0) $message[] = "Assigned {$assigned}";
            $_SESSION['flash_message'] = count($message) > 0 ? implode(', ', $message) . ' tenants' : 'No tenants imported';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Import failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        // Add timestamp to force page reload
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        redirect('/tenants?t=' . time());
    }
} 