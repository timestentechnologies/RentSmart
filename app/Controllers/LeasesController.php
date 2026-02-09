<?php

namespace App\Controllers;

use App\Database\Connection as Database;
use App\Models\Lease;
use App\Models\User;
use DateTime;
use Exception;
use PDO;

class LeasesController {
    private $db;
    private $leaseModel;
    private $userId;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->leaseModel = new Lease();
        
        // Get current user ID from session
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to access leases';
            $_SESSION['flash_type'] = 'warning';
            header("Location: " . BASE_URL . "/");
            exit;
        }
    }

    public function index() {
        // Get all leases with role-based access control
        $leases = $this->leaseModel->getAll($this->userId);

        // Get all properties for the filter (respecting user's role)
        $propertyModel = new \App\Models\Property();
        $properties = $propertyModel->getAll($this->userId);

        // Get all tenants for the form (respecting user's role)
        $tenantModel = new \App\Models\Tenant();
        $tenants = $tenantModel->getAll($this->userId);

        require 'views/leases/index.php';
    }

    public function store() {
        try {
            // Validate input
            $requiredFields = ['unit_id', 'tenant_id', 'start_date', 'end_date', 'rent_amount', 'security_deposit', 'payment_day'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Check if user has access to the unit
            $unitModel = new \App\Models\Unit();
            $unit = $unitModel->getById($_POST['unit_id'], $this->userId);
            if (!$unit) {
                throw new Exception("You don't have access to this unit");
            }

            // Check if user has access to the tenant
            $tenantModel = new \App\Models\Tenant();
            $tenant = $tenantModel->getById($_POST['tenant_id'], $this->userId);
            if (!$tenant) {
                throw new Exception("You don't have access to this tenant");
            }

            // Validate dates
            $startDate = new DateTime($_POST['start_date']);
            $endDate = new DateTime($_POST['end_date']);
            if ($endDate <= $startDate) {
                throw new Exception("End date must be after start date");
            }

            // Begin transaction
            $this->db->beginTransaction();

            // Insert lease
            $query = "INSERT INTO leases (unit_id, tenant_id, start_date, end_date, rent_amount, security_deposit, payment_day, notes) 
                     VALUES (:unit_id, :tenant_id, :start_date, :end_date, :rent_amount, :security_deposit, :payment_day, :notes)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'unit_id' => $_POST['unit_id'],
                'tenant_id' => $_POST['tenant_id'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'rent_amount' => $_POST['rent_amount'],
                'security_deposit' => $_POST['security_deposit'],
                'payment_day' => $_POST['payment_day'],
                'notes' => $_POST['notes'] ?? null
            ]);

            $leaseId = (int)$this->db->lastInsertId();

            // Update unit status
            $updateUnitQuery = "UPDATE units SET status = 'occupied', tenant_id = :tenant_id, rent_amount = :rent_amount WHERE id = :unit_id";
            $stmt = $this->db->prepare($updateUnitQuery);
            $stmt->execute([
                'unit_id' => $_POST['unit_id'],
                'tenant_id' => $_POST['tenant_id'],
                'rent_amount' => $_POST['rent_amount']
            ]);

            // Auto-create draft invoices immediately for this lease (idempotent)
            try {
                $inv = new \App\Models\Invoice();
                $inv->ensureInvoicesForLeaseMonths((int)$_POST['tenant_id'], (float)$_POST['rent_amount'], (string)$_POST['start_date'], date('Y-m-d'), (int)($_SESSION['user_id'] ?? 0), 'AUTO');
            } catch (\Exception $e) { error_log('Auto-invoice (lease store) failed: ' . $e->getMessage()); }

            $this->db->commit();

            $_SESSION['flash_message'] = "Lease added successfully";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_message'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }

        header("Location: " . BASE_URL . "/leases");
        exit;
    }

    public function edit($id) {
        try {
            // Get lease details with role-based access
            $lease = $this->leaseModel->getById($id, $this->userId);
            if (!$lease) {
                throw new Exception("Lease not found or access denied");
            }

            // Get all properties (respecting user's role)
            $propertyModel = new \App\Models\Property();
            $properties = $propertyModel->getAll($this->userId);

            // Get all tenants (respecting user's role)
            $tenantModel = new \App\Models\Tenant();
            $tenants = $tenantModel->getAll($this->userId);

            require 'views/leases/edit.php';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
            header("Location: " . BASE_URL . "/leases");
            exit;
        }
    }

    public function update($id) {
        try {
            // Check if user has access to the lease
            $lease = $this->leaseModel->getById($id, $this->userId);
            if (!$lease) {
                throw new Exception("Lease not found or access denied");
            }

            // Validate input
            $requiredFields = ['unit_id', 'tenant_id', 'start_date', 'end_date', 'rent_amount', 'security_deposit', 'payment_day'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Check if user has access to the unit
            $unitModel = new \App\Models\Unit();
            $unit = $unitModel->getById($_POST['unit_id'], $this->userId);
            if (!$unit) {
                throw new Exception("You don't have access to this unit");
            }

            // Check if user has access to the tenant
            $tenantModel = new \App\Models\Tenant();
            $tenant = $tenantModel->getById($_POST['tenant_id'], $this->userId);
            if (!$tenant) {
                throw new Exception("You don't have access to this tenant");
            }

            // Validate dates
            $startDate = new DateTime($_POST['start_date']);
            $endDate = new DateTime($_POST['end_date']);
            if ($endDate <= $startDate) {
                throw new Exception("End date must be after start date");
            }

            // Begin transaction
            $this->db->beginTransaction();

            // Get current lease details
            $query = "SELECT unit_id FROM leases WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            $currentLease = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update lease
            $query = "UPDATE leases 
                     SET unit_id = :unit_id,
                         tenant_id = :tenant_id,
                         start_date = :start_date,
                         end_date = :end_date,
                         rent_amount = :rent_amount,
                         security_deposit = :security_deposit,
                         payment_day = :payment_day,
                         notes = :notes,
                         status = :status
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'id' => $id,
                'unit_id' => $_POST['unit_id'],
                'tenant_id' => $_POST['tenant_id'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'rent_amount' => $_POST['rent_amount'],
                'security_deposit' => $_POST['security_deposit'],
                'payment_day' => $_POST['payment_day'],
                'notes' => $_POST['notes'] ?? null,
                'status' => $_POST['status'] ?? 'active'
            ]);

            // Update old unit status if unit changed
            if ($currentLease['unit_id'] != $_POST['unit_id']) {
                $updateOldUnitQuery = "UPDATE units SET status = 'vacant', tenant_id = NULL WHERE id = :unit_id";
                $stmt = $this->db->prepare($updateOldUnitQuery);
                $stmt->execute(['unit_id' => $currentLease['unit_id']]);

                // Update new unit status
                $updateNewUnitQuery = "UPDATE units SET status = 'occupied', tenant_id = :tenant_id, rent_amount = :rent_amount WHERE id = :unit_id";
                $stmt = $this->db->prepare($updateNewUnitQuery);
                $stmt->execute([
                    'unit_id' => $_POST['unit_id'],
                    'tenant_id' => $_POST['tenant_id'],
                    'rent_amount' => $_POST['rent_amount']
                ]);
            } else {
                // Unit unchanged: still sync rent and tenant link
                $stmt = $this->db->prepare("UPDATE units SET tenant_id = :tenant_id, rent_amount = :rent_amount, status = 'occupied' WHERE id = :unit_id");
                $stmt->execute([
                    'unit_id' => $_POST['unit_id'],
                    'tenant_id' => $_POST['tenant_id'],
                    'rent_amount' => $_POST['rent_amount']
                ]);
            }

            $this->db->commit();

            $_SESSION['flash_message'] = "Lease updated successfully";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_message'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }

        header("Location: " . BASE_URL . "/leases");
        exit;
    }

    public function delete($id = null) {
        if ($id === null) {
            $_SESSION['flash_message'] = "No lease ID provided for deletion.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . BASE_URL . "/leases");
            exit;
        }

        try {
            // Check if user has access to the lease
            $lease = $this->leaseModel->getById($id, $this->userId);
            if (!$lease) {
                throw new Exception("Lease not found or access denied");
            }

            // Begin transaction
            $this->db->beginTransaction();

            // Delete lease
            $query = "DELETE FROM leases WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);

            // Update unit status
            $updateUnitQuery = "UPDATE units SET status = 'vacant', tenant_id = NULL WHERE id = :unit_id";
            $stmt = $this->db->prepare($updateUnitQuery);
            $stmt->execute(['unit_id' => $lease['unit_id']]);

            $this->db->commit();

            $_SESSION['flash_message'] = "Lease deleted successfully";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_message'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }

        header("Location: " . BASE_URL . "/leases");
        exit;
    }

    public function getUnitsByProperty($propertyId) {
        try {
            $query = "SELECT id, unit_number, rent_amount 
                     FROM units 
                     WHERE property_id = :property_id AND status = 'vacant'
                     ORDER BY unit_number";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['property_id' => $propertyId]);
            $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($units);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function export($format = 'csv') {
        try {
            $leases = $this->leaseModel->getAll($this->userId);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="leases.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Property','Unit','Tenant','Start Date','End Date','Rent','Payment Day','Status']);
                foreach ($leases as $l) {
                    fputcsv($out, [
                        $l['property_name'] ?? '',
                        $l['unit_number'] ?? '',
                        $l['tenant_name'] ?? '',
                        $l['start_date'] ?? '',
                        $l['end_date'] ?? '',
                        $l['rent_amount'] ?? 0,
                        $l['payment_day'] ?? '',
                        $l['status'] ?? ''
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="leases.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Property</th><th>Unit</th><th>Tenant</th><th>Start Date</th><th>End Date</th><th>Rent</th><th>Payment Day</th><th>Status</th></tr>';
                foreach ($leases as $l) {
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($l['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['tenant_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['start_date'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['end_date'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['rent_amount'] ?? 0).'</td>'
                        .'<td>'.htmlspecialchars($l['payment_day'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['status'] ?? '').'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }

            if ($format === 'pdf') {
                $html = '<h3>Leases</h3><table width="100%" border="1" cellspacing="0" cellpadding="4">'
                    .'<tr><th>Property</th><th>Unit</th><th>Tenant</th><th>Start Date</th><th>End Date</th><th>Rent</th><th>Payment Day</th><th>Status</th></tr>';
                foreach ($leases as $l) {
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($l['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['tenant_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['start_date'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['end_date'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['rent_amount'] ?? 0).'</td>'
                        .'<td>'.htmlspecialchars($l['payment_day'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['status'] ?? '').'</td>'
                        .'</tr>';
                }
                $html .= '</table>';
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="leases.pdf"');
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
        $templateFile = __DIR__ . '/../../public/templates/leases_template.csv';
        
        if (file_exists($templateFile)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="leases_template.csv"');
            readfile($templateFile);
            exit;
        }
        
        // Fallback to empty template if file doesn't exist
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leases_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['property_name','unit_number','tenant_email','start_date','end_date','rent_amount','security_deposit','payment_day','status','notes']);
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
            $propertyModel = new \App\Models\Property();
            $unitModel = new \App\Models\Unit();
            $tenantModel = new \App\Models\Tenant();
            $handle = fopen($tmp, 'r');
            if ($handle === false) throw new \Exception('Cannot open uploaded file');
            $header = fgetcsv($handle);
            $created = 0;
            $updated = 0;
            $skipped = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (empty($data['tenant_email']) || empty($data['unit_number'])) continue;
                // Resolve property
                $property = null;
                foreach ($propertyModel->getAll($userId) as $p) { if (strcasecmp($p['name'], $data['property_name'] ?? '') === 0) { $property = $p; break; } }
                if (!$property) continue;
                // Resolve unit
                $units = $unitModel->where('property_id', $property['id'], $userId);
                $unit = null;
                foreach ($units as $u) { if (strcasecmp($u['unit_number'], $data['unit_number']) === 0) { $unit = $u; break; } }
                if (!$unit) continue;
                // Resolve tenant
                $tenant = method_exists($tenantModel, 'findByEmail') ? $tenantModel->findByEmail($data['tenant_email'], $userId) : null;
                if (!$tenant) continue;
                
                // Check if active lease exists for this tenant and unit
                $stmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = :tenant_id AND unit_id = :unit_id AND status = 'active' LIMIT 1");
                $stmt->execute(['tenant_id' => $tenant['id'], 'unit_id' => $unit['id']]);
                $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Update existing active lease
                    $updateStmt = $this->db->prepare("UPDATE leases SET start_date = :start_date, end_date = :end_date, rent_amount = :rent_amount, security_deposit = :security_deposit, payment_day = :payment_day, status = :status, notes = :notes WHERE id = :id");
                    $ok = $updateStmt->execute([
                        'id' => $existing['id'],
                        'start_date' => $data['start_date'] ?? date('Y-m-d'),
                        'end_date' => $data['end_date'] ?? date('Y-m-d', strtotime('+1 year')),
                        'rent_amount' => $data['rent_amount'] ?? 0,
                        'security_deposit' => $data['security_deposit'] ?? 0,
                        'payment_day' => $data['payment_day'] ?? 1,
                        'status' => $data['status'] ?? 'active',
                        'notes' => $data['notes'] ?? ''
                    ]);
                    if ($ok) $updated++;
                } else {
                    // Create new lease
                    $stmt = $this->db->prepare("INSERT INTO leases (unit_id, tenant_id, start_date, end_date, rent_amount, security_deposit, payment_day, status, notes) VALUES (:unit_id,:tenant_id,:start_date,:end_date,:rent_amount,:security_deposit,:payment_day,:status,:notes)");
                    $ok = $stmt->execute([
                        'unit_id' => $unit['id'],
                        'tenant_id' => $tenant['id'],
                        'start_date' => $data['start_date'] ?? date('Y-m-d'),
                        'end_date' => $data['end_date'] ?? date('Y-m-d', strtotime('+1 year')),
                        'rent_amount' => $data['rent_amount'] ?? 0,
                        'security_deposit' => $data['security_deposit'] ?? 0,
                        'payment_day' => $data['payment_day'] ?? 1,
                        'status' => $data['status'] ?? 'active',
                        'notes' => $data['notes'] ?? ''
                    ]);
                    if ($ok) $created++;
                }
            }
            fclose($handle);
            $message = [];
            if ($created > 0) $message[] = "Created {$created}";
            if ($updated > 0) $message[] = "Updated {$updated}";
            $_SESSION['flash_message'] = count($message) > 0 ? implode(', ', $message) . ' leases' : 'No leases imported';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Import failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        // Add timestamp to force page reload
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        redirect('/leases?t=' . time());
    }
} 