<?php

namespace App\Controllers;

use App\Models\Utility;
use App\Models\UtilityReading;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UtilityRate;

require_once __DIR__ . '/../helpers.php';

class UtilitiesController
{
    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }

    }

    private function ratesSupportUserScope(): bool
    {
        try {
            $db = (new \App\Models\UtilityRate())->getDb();
            $stmt = $db->query("SHOW COLUMNS FROM utility_rates LIKE 'user_id'");
            return (bool)$stmt->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function ratesSupportPropertyScope(): bool
    {
        try {
            $db = (new \App\Models\UtilityRate())->getDb();
            $stmt = $db->query("SHOW COLUMNS FROM utility_rates LIKE 'property_id'");
            return (bool)$stmt->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function typesByProperty($propertyId)
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $propertyModel = new Property();
        $property = $propertyModel->getById((int)$propertyId, $userId);
        if (!$property) {
            echo json_encode(['success' => false, 'message' => 'Property not found']);
            exit;
        }

        $rateModel = new UtilityRate();
        if (!$this->ratesSupportPropertyScope()) {
            echo json_encode(['success' => true, 'types' => $rateModel->getAllTypesWithMethod()]);
            exit;
        }

        $db = $rateModel->getDb();
        $sql = "SELECT ur.*
            FROM utility_rates ur
            INNER JOIN (
                SELECT utility_type, MAX(effective_from) as max_date
                FROM utility_rates
                WHERE (effective_to IS NULL OR effective_to >= CURDATE())
                  AND property_id = ?
                GROUP BY utility_type
            ) latest ON ur.utility_type = latest.utility_type AND ur.effective_from = latest.max_date
            WHERE ur.property_id = ?
            ORDER BY ur.utility_type";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int)$propertyId, (int)$propertyId]);
        $types = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'types' => $types]);
        exit;
    }

    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $userModel = new \App\Models\User();
        $userData = $userModel->find($userId);
        $utilityModel = new Utility();
        $propertyModel = new Property();
        $rateModel = new UtilityRate();
        $readingModel = new \App\Models\UtilityReading();

        // Only admins see all, others see only their own
        if ($userModel->isAdmin()) {
            $properties = $propertyModel->getAll();
            $utilities = $utilityModel->getAll();
        } else {
            $properties = $propertyModel->getAll($userId);
            // Get property IDs accessible to this user
            $propertyIds = $userModel->getAccessiblePropertyIds();
            if (!empty($propertyIds)) {
                // Fetch only utilities for these properties
                $utilitiesById = [];
                foreach ($propertyIds as $propertyId) {
                    $units = (new \App\Models\Unit())->where('property_id', $propertyId, $userId);
                    foreach ($units as $unit) {
                        $unitUtilities = $utilityModel->getUtilitiesByUnit($unit['id']);
                        foreach ($unitUtilities as $u) {
                            if (isset($u['id'])) {
                                $utilitiesById[$u['id']] = $u;
                            }
                        }
                    }
                }
                $utilities = array_values($utilitiesById);
            } else {
                $utilities = [];
            }
        }
        $utility_types = [];
        $utility_rates = [];
        $db = $rateModel->getDb();
        if ($this->ratesSupportUserScope() && $userId && !$userModel->isAdmin()) {
            $stmt = $db->prepare("SELECT DISTINCT utility_type FROM utility_rates WHERE user_id = ? ORDER BY utility_type");
            $stmt->execute([$userId]);
            $utility_types = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($utility_types as $type) {
                $stmtR = $db->prepare("SELECT ur.*, p.name as property_name FROM utility_rates ur LEFT JOIN properties p ON ur.property_id = p.id WHERE ur.utility_type = ? AND ur.user_id = ? ORDER BY ur.effective_from DESC");
                $stmtR->execute([$type, $userId]);
                $utility_rates[$type] = $stmtR->fetchAll(\PDO::FETCH_ASSOC);
            }
        } elseif ($this->ratesSupportUserScope() && $userId && $userModel->isAdmin()) {
            $utility_types = $rateModel->getAllTypes();
            foreach ($utility_types as $type) {
                $utility_rates[$type] = $rateModel->getRatesByType($type);
            }
        } else {
            $utility_types = $rateModel->getAllTypes();
            foreach ($utility_types as $type) {
                $utility_rates[$type] = $rateModel->getRatesByType($type);
            }
        }

        $payStmt = null;
        try {
            $payStmt = $rateModel->getDb()->prepare(
                "SELECT COALESCE(SUM(amount),0) AS s\n"
                . "FROM payments\n"
                . "WHERE utility_id = ?\n"
                . "  AND payment_type = 'utility'\n"
                . "  AND status IN ('completed','verified')"
            );
        } catch (\Exception $e) {
            $payStmt = null;
        }

        // Add readings, cost, and paid/balance due to each utility
        foreach ($utilities as &$utility) {
            $latest = $readingModel->getLatestByUtilityId($utility['id']);
            $previous = $readingModel->getPreviousByUtilityId($utility['id']);
            $utility['latest_reading'] = $latest['reading_value'] ?? null;
            $utility['previous_reading'] = $latest['previous_reading'] ?? ($previous['reading_value'] ?? null);
            $utility['units_used'] = $latest['units_used'] ?? (isset($latest['reading_value'], $latest['previous_reading']) ? $latest['reading_value'] - $latest['previous_reading'] : null);
            if ($utility['is_metered']) {
                $utility['cost'] = $latest['cost'] ?? null;
            } else {
                $utility['cost'] = $utility['flat_rate'];
            }
            $utility['meter_number'] = $utility['meter_number'] ?? '';

            // Infer missing utility name for flat rate utilities by matching rate per unit
            $ut = trim((string)($utility['utility_type'] ?? ''));
            if ($ut === '' && empty($utility['is_metered'])) {
                $pid = (int)($utility['property_id'] ?? 0);
                $fr = isset($utility['flat_rate']) ? (float)$utility['flat_rate'] : null;
                if ($pid && $fr !== null) {
                    foreach (($utility_rates ?? []) as $type => $rateList) {
                        foreach (($rateList ?? []) as $r) {
                            if ((int)($r['property_id'] ?? 0) !== $pid) continue;
                            if (($r['billing_method'] ?? '') !== 'flat_rate') continue;
                            $rp = isset($r['rate_per_unit']) ? (float)$r['rate_per_unit'] : null;
                            if ($rp !== null && abs($rp - $fr) < 0.0001) {
                                $utility['utility_type'] = $type;
                                break 2;
                            }
                        }
                    }
                }
            }

            // Fallbacks for display
            $utility['unit_number'] = $utility['unit_number'] ?? ($utility['unit'] ?? null);
            $utility['property_name'] = $utility['property_name'] ?? ($utility['property'] ?? null);

            // Paid and balance due (for displaying paid utilities too)
            $paid = 0.0;
            if ($payStmt) {
                try {
                    $payStmt->execute([(int)$utility['id']]);
                    $paid = (float)($payStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
                } catch (\Exception $e) {
                    $paid = 0.0;
                }
            }
            $cost = (float)($utility['cost'] ?? 0);
            $utility['paid_amount'] = round($paid, 2);
            $utility['balance_due'] = round(max($cost - $paid, 0.0), 2);
        }
        unset($utility);

        $content = view('utilities/index', [
            'title' => 'Utilities Management',
            'properties' => $properties,
            'utilities' => $utilities,
            'utility_rates' => $utility_rates
        ]);
        echo view('layouts/main', [
            'title' => 'Utilities Management',
            'content' => $content
        ]);
    }

    public function create()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $userModel = new \App\Models\User();
        $userData = $userModel->find($userId);
        $propertyModel = new Property();
        $unitModel = new Unit();
        $rateModel = new \App\Models\UtilityRate();

        // Only admins see all, others see only their own
        if ($userModel->isAdmin()) {
            $properties = $propertyModel->getAll();
            $units = $unitModel->getAll();
        } else {
            $properties = $propertyModel->getAll($userId);
            $units = $unitModel->getAll($userId);
        }
        // Utility types will be loaded dynamically per selected property owner
        $utilityTypes = [];

        $content = view('utilities/create', [
            'title' => 'Add New Utility',
            'properties' => $properties,
            'units' => $units,
            'utilityTypes' => $utilityTypes
        ]);
        echo view('layouts/main', [
            'title' => 'Add New Utility',
            'content' => $content
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/utilities');
        }

        $rateModel = new \App\Models\UtilityRate();
        $utilityType = trim((string)($_POST['utility_type'] ?? ''));
        if ($utilityType === '') {
            $_SESSION['errors'] = ['Utility type is required'];
            redirect('/utilities/create');
        }
        $userId = $_SESSION['user_id'] ?? null;
        $db = $rateModel->getDb();
        $userModel = new \App\Models\User();
        $userModel->find($userId);

        // Resolve property_id (from form or from unit)
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $unitId = (int)($_POST['unit_id'] ?? 0);
        if (!$propertyId && $unitId) {
            $unitModel = new \App\Models\Unit();
            $unit = $unitModel->getById($unitId, $userId);
            $propertyId = (int)($unit['property_id'] ?? 0);
        }

        // Access control: ensure user can access the property
        if (!$userModel->isAdmin()) {
            $accessible = $userModel->getAccessiblePropertyIds();
            if ($propertyId && !in_array($propertyId, $accessible)) {
                $_SESSION['errors'] = ['Unauthorized'];
                redirect('/utilities/create');
            }
        }

        // Rate lookup must be based on the property's configured rates
        if ($this->ratesSupportPropertyScope() && $propertyId) {
            $stmt = $db->prepare("
                SELECT *
                FROM utility_rates
                WHERE utility_type = ?
                  AND effective_from <= CURDATE()
                  AND (effective_to IS NULL OR effective_to >= CURDATE())
                  AND property_id = ?
                ORDER BY effective_from DESC
                LIMIT 1
            ");
            $stmt->execute([$utilityType, $propertyId]);
            $rateInfo = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } else {
            $rateInfo = $rateModel->getCurrentRate($utilityType);
        }

        if ($this->ratesSupportPropertyScope() && $propertyId && empty($rateInfo)) {
            $_SESSION['errors'] = ['Utility type not configured for this property'];
            redirect('/utilities/create');
        }
        $billingMethod = $rateInfo['billing_method'] ?? 'flat_rate';

        $data = [
            'unit_id' => $unitId ?: ($_POST['unit_id'] ?? ''),
            'utility_type' => $utilityType,
            'is_metered' => $billingMethod === 'metered' ? 1 : 0,
            'flat_rate' => $billingMethod === 'flat_rate' ? ($rateInfo['rate_per_unit'] ?? null) : null,
            'meter_number' => $billingMethod === 'metered' ? ($_POST['meter_number'] ?? null) : null,
        ];

        $utilityModel = new Utility();
        $utilityId = $utilityModel->create($data);

        if ($utilityId && $billingMethod === 'metered') {
            // Save initial reading
            $readingModel = new \App\Models\UtilityReading();
            $previous = isset($_POST['previous_reading']) ? floatval($_POST['previous_reading']) : 0;
            $current = isset($_POST['current_reading']) ? floatval($_POST['current_reading']) : 0;
            $units = $current - $previous;
            $readingData = [
                'utility_id' => $utilityId,
                'reading_date' => $_POST['reading_date'] ?? date('Y-m-d'),
                'reading_value' => $current,
                'previous_reading' => $previous,
                'units_used' => $units > 0 ? $units : 0,
                'cost' => isset($_POST['cost']) ? floatval($_POST['cost']) : 0
            ];
            $readingModel->create($readingData);
        }

        if ($utilityId && $billingMethod === 'flat_rate') {
            // Optionally, you could log a reading or just save the cost in the utility record
            // For now, just save the cost in the session for confirmation
            $_SESSION['last_flat_rate_cost'] = isset($_POST['cost']) ? floatval($_POST['cost']) : 0;
        }

        if ($utilityId) {
            $_SESSION['success'] = 'Utility added successfully';
        } else {
            $_SESSION['errors'] = ['Failed to add utility'];
        }

        redirect('/utilities');
    }

    public function edit($id)
    {
        $utilityModel = new Utility();
        $utility = $utilityModel->find($id);
        if (!$utility) {
            $_SESSION['errors'] = ['Utility not found'];
            redirect('/utilities');
        }

        $_SESSION['edit_utility_id'] = $id;
        redirect('/utilities');
    }

    public function update($id)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        // CSRF validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }

        try {
            $utilityModel = new Utility();
            $readingModel = new \App\Models\UtilityReading();
            $rateModel = new \App\Models\UtilityRate();
            
            // Start transaction
            $utilityModel->beginTransaction();
            
            // 1. Update utility
            $utility = $utilityModel->find($id);
            if (!$utility) {
                throw new \Exception('Utility not found');
            }

            // Resolve property_id from unit
            $unitId = (int)($_POST['unit_id'] ?? 0);
            if (!$unitId) {
                throw new \Exception('Unit is required');
            }
            $unitModel = new \App\Models\Unit();
            $userId = $_SESSION['user_id'] ?? null;
            $unit = $unitModel->getById($unitId, $userId);
            $propertyId = (int)($unit['property_id'] ?? 0);

            $utilityType = trim((string)($_POST['utility_type'] ?? ''));
            if ($utilityType === '') {
                throw new \Exception('Utility type is required');
            }

            // Lookup current rate + billing method for this property/type
            $db = $rateModel->getDb();
            $billingMethod = 'flat_rate';
            $ratePerUnit = null;

            if ($this->ratesSupportPropertyScope() && $propertyId) {
                $stmt = $db->prepare(
                    "SELECT * FROM utility_rates\n"
                    ."WHERE utility_type = ?\n"
                    ."  AND effective_from <= CURDATE()\n"
                    ."  AND (effective_to IS NULL OR effective_to >= CURDATE())\n"
                    ."  AND property_id = ?\n"
                    ."ORDER BY effective_from DESC\n"
                    ."LIMIT 1"
                );
                $stmt->execute([$utilityType, $propertyId]);
                $rateInfo = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                if (!empty($rateInfo)) {
                    $billingMethod = $rateInfo['billing_method'] ?? 'flat_rate';
                    $ratePerUnit = $rateInfo['rate_per_unit'] ?? null;
                }
            } else {
                $rateInfo = $rateModel->getCurrentRate($utilityType);
                if (!empty($rateInfo)) {
                    $billingMethod = $rateInfo['billing_method'] ?? 'flat_rate';
                    $ratePerUnit = $rateInfo['rate_per_unit'] ?? null;
                }
            }

            $isMetered = ($billingMethod === 'metered');
            $flatRate = (!$isMetered && $ratePerUnit !== null) ? $ratePerUnit : null;

            $utilityData = [
                'unit_id' => $unitId,
                'utility_type' => $utilityType,
                'is_metered' => $isMetered ? 1 : 0,
                'flat_rate' => $flatRate,
                'meter_number' => $isMetered ? ($_POST['meter_number'] ?? null) : null,
            ];

            $utilityModel->update($id, $utilityData);

            // 2. Add new reading if provided
            if ($isMetered && isset($_POST['current_reading']) && isset($_POST['reading_date'])) {
                $readingData = [
                    'utility_id' => $id,
                    'reading_date' => $_POST['reading_date'],
                    'previous_reading' => $_POST['previous_reading'],
                    'reading_value' => $_POST['current_reading'],
                    'units_used' => $_POST['units_used'],
                    'cost' => $_POST['cost']
                ];

                $readingModel->create($readingData);
            }

            // Commit transaction
            $utilityModel->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Utility updated successfully'
            ]);
        } catch (\Exception $e) {
            // Rollback transaction
            if (isset($utilityModel)) {
                $utilityModel->rollback();
            }

            error_log("Error updating utility: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating utility: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit('Method not allowed');
        }

        // CSRF validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            header('HTTP/1.1 403 Forbidden');
            exit('Invalid CSRF token');
        }

        $utilityModel = new Utility();
        $utility = $utilityModel->find($id);
        if (!$utility) {
            header('HTTP/1.1 404 Not Found');
            exit('Utility not found');
        }

        $result = $utilityModel->delete($id);

        header('Content-Type: application/json');
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utility deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete utility']);
        }
        exit;
    }

    public function readings($utilityId)
    {
        $utilityModel = new Utility();
        $readingModel = new UtilityReading();
        
        $utility = $utilityModel->find($utilityId);
        if (!$utility) {
            $_SESSION['errors'] = ['Utility not found'];
            redirect('/utilities');
        }

        $readings = $readingModel->db->query("
            SELECT * FROM utility_readings 
            WHERE utility_id = ? 
            ORDER BY reading_date DESC
        ", [$utilityId])->fetchAll();

        $title = 'Utility Readings';
        
        require_once VIEWS_PATH . '/utilities/readings.php';
    }

    public function addReading($utilityId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect("/utilities/readings/$utilityId");
        }

        // CSRF validation
        if (!verify_csrf_token()) {
            $_SESSION['errors'] = ['Invalid security token'];
            redirect("/utilities/readings/$utilityId");
        }

        $utilityModel = new Utility();
        $utility = $utilityModel->find($utilityId);
        if (!$utility) {
            $_SESSION['errors'] = ['Utility not found'];
            redirect('/utilities');
        }

        $data = [
            'utility_id' => $utilityId,
            'reading_date' => $_POST['reading_date'] ?? '',
            'reading_value' => $_POST['reading_value'] ?? ''
        ];

        // Validation
        $errors = [];
        if (empty($data['reading_date'])) {
            $errors[] = 'Reading date is required';
        }
        if (empty($data['reading_value'])) {
            $errors[] = 'Reading value is required';
        }
        if (!is_numeric($data['reading_value'])) {
            $errors[] = 'Reading value must be a number';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            redirect("/utilities/readings/$utilityId");
        }

        $readingModel = new UtilityReading();
        $result = $readingModel->create($data);

        if ($result) {
            $_SESSION['success'] = 'Reading added successfully';
        } else {
            $_SESSION['errors'] = ['Failed to add reading'];
        }

        redirect("/utilities/readings/$utilityId");
    }

    public function export($format = 'csv')
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $utilityModel = new Utility();
            $utilities = $utilityModel->getAll();
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="utilities.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Property','Unit','Utility Type','Billing','Meter','Previous','Current','Units Used','Cost']);
                foreach ($utilities as $u) {
                    fputcsv($out, [
                        $u['property_name'] ?? '',
                        $u['unit_number'] ?? '',
                        $u['utility_type'] ?? '',
                        ($u['is_metered'] ? 'Metered' : 'Flat Rate'),
                        $u['meter_number'] ?? '',
                        $u['previous_reading'] ?? '',
                        $u['latest_reading'] ?? '',
                        $u['units_used'] ?? '',
                        $u['cost'] ?? ''
                    ]);
                }
                fclose($out);
                exit;
            }
            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="utilities.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Property</th><th>Unit</th><th>Utility Type</th><th>Billing</th><th>Meter</th><th>Previous</th><th>Current</th><th>Units Used</th><th>Cost</th></tr>';
                foreach ($utilities as $u) {
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($u['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['utility_type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['is_metered'] ? 'Metered' : 'Flat Rate').'</td>'
                        .'<td>'.htmlspecialchars($u['meter_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['previous_reading'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['latest_reading'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['units_used'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['cost'] ?? '').'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }
            if ($format === 'pdf') {
                $html = '<h3>Utilities</h3><table width="100%" border="1" cellspacing="0" cellpadding="4">'
                    .'<tr><th>Property</th><th>Unit</th><th>Utility Type</th><th>Billing</th><th>Meter</th><th>Previous</th><th>Current</th><th>Units Used</th><th>Cost</th></tr>';
                foreach ($utilities as $u) {
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($u['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['utility_type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['is_metered'] ? 'Metered' : 'Flat Rate').'</td>'
                        .'<td>'.htmlspecialchars($u['meter_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['previous_reading'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['latest_reading'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['units_used'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['cost'] ?? '').'</td>'
                        .'</tr>';
                }
                $html .= '</table>';
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="utilities.pdf"');
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
        $templateFile = __DIR__ . '/../../public/templates/utilities_template.csv';
        
        if (file_exists($templateFile)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="utilities_template.csv"');
            readfile($templateFile);
            exit;
        }
        
        // Fallback to empty template if file doesn't exist
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="utilities_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['property_name','unit_number','utility_type','reading_date','previous_reading','current_reading','units_consumed','rate_per_unit','total_amount','status','notes']);
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
            $tmp = $_FILES['file']['tmp_name'];
            if (!is_uploaded_file($tmp)) {
                throw new \Exception('Invalid upload');
            }
            $userId = $_SESSION['user_id'] ?? null;
            $propertyModel = new Property();
            $unitModel = new Unit();
            $handle = fopen($tmp, 'r');
            if ($handle === false) throw new \Exception('Cannot open uploaded file');
            $header = fgetcsv($handle);
            $created = 0;
            $updated = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                $property = null;
                foreach ($propertyModel->getAll($userId) as $p) { if (strcasecmp($p['name'], $data['property_name'] ?? '') === 0) { $property = $p; break; } }
                if (!$property) continue;
                $units = $unitModel->where('property_id', $property['id'], $userId);
                $unit = null;
                foreach ($units as $u) { if (strcasecmp($u['unit_number'], $data['unit_number'] ?? '') === 0) { $unit = $u; break; } }
                if (!$unit) continue;
                
                // Check if utility exists for this unit and type
                $utilityModel = new Utility();
                $existingUtilities = $utilityModel->getUtilitiesByUnit($unit['id']);
                $existing = null;
                foreach ($existingUtilities as $util) {
                    if (strcasecmp($util['utility_type'], $data['utility_type'] ?? '') === 0) {
                        $existing = $util;
                        break;
                    }
                }
                
                $isMetered = strtolower($data['billing_method'] ?? 'flat_rate') === 'metered';
                $utilityData = [
                    'unit_id' => $unit['id'],
                    'utility_type' => $data['utility_type'] ?? '',
                    'is_metered' => $isMetered ? 1 : 0,
                    'flat_rate' => $isMetered ? null : ($data['cost'] ?? null),
                    'meter_number' => $isMetered ? ($data['meter_number'] ?? null) : null,
                ];
                
                if ($existing) {
                    // Update existing utility
                    if ($utilityModel->update($existing['id'], $utilityData)) {
                        $updated++;
                        $utilityId = $existing['id'];
                    } else {
                        $utilityId = null;
                    }
                } else {
                    // Create new utility
                    $utilityId = $utilityModel->create($utilityData);
                    if ($utilityId) $created++;
                }
                
                if ($utilityId && $isMetered && ($data['previous_reading'] !== '' || $data['current_reading'] !== '')) {
                    $readingModel = new UtilityReading();
                    $readingModel->create([
                        'utility_id' => $utilityId,
                        'reading_date' => date('Y-m-d'),
                        'previous_reading' => (float)($data['previous_reading'] ?? 0),
                        'reading_value' => (float)($data['current_reading'] ?? 0),
                        'units_used' => max(0, (float)($data['current_reading'] ?? 0) - (float)($data['previous_reading'] ?? 0)),
                        'cost' => $data['cost'] ?? 0
                    ]);
                }
            }
            fclose($handle);
            $message = [];
            if ($created > 0) $message[] = "Created {$created}";
            if ($updated > 0) $message[] = "Updated {$updated}";
            $_SESSION['flash_message'] = count($message) > 0 ? implode(', ', $message) . ' utilities' : 'No utilities imported';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Import failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        // Add timestamp to force page reload
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        redirect('/utilities?t=' . time());
    }

    public function getUnitsByProperty()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $propertyId = $_POST['property_id'] ?? '';
        if (empty($propertyId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Property ID is required']);
            return;
        }

        $unitModel = new Unit();
        $units = $unitModel->db->query("
            SELECT id, unit_number, type 
            FROM units 
            WHERE property_id = ? 
            ORDER BY unit_number
        ", [$propertyId])->fetchAll();

        echo json_encode(['units' => $units]);
    }
} 