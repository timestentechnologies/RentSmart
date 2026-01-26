<?php

namespace App\Controllers;

use App\Models\Unit;
use App\Models\Property;
use App\Models\User;
use App\Database\Connection;
use App\Helpers\FileUploadHelper;
use Exception;

class UnitsController
{
    private $unit;
    private $property;
    private $user;
    private $db;

    public function __construct()
    {
        $this->unit = new Unit();
        $this->property = new Property();
        $this->user = new User();
        $this->db = Connection::getInstance()->getConnection();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            // If this is an AJAX/JSON request, return JSON 401 instead of redirecting
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if ($isAjax) {
                if (!headers_sent()) {
                    http_response_code(401);
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }
        
        // Load user data
        $this->user->find($_SESSION['user_id']);
    }

    public function index()
    {
        try {
            $units = $this->unit->getAll($_SESSION['user_id']);
            $properties = $this->property->getAll($_SESSION['user_id']);
            
            echo view('units/index', [
                'title' => 'Units',
                'units' => $units,
                'properties' => $properties
            ]);
        } catch (Exception $e) {
            error_log("Error in UnitsController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading units';
            $_SESSION['flash_type'] = 'danger';
            echo view('errors/500');
        }
    }

    public function create()
    {
        try {
            // Only admin, landlord, manager, and agent can create units
            if (!$this->user->isAdmin() && !$this->user->isLandlord() && !$this->user->isManager() && !$this->user->isAgent()) {
                $_SESSION['flash_message'] = 'Access denied';
                $_SESSION['flash_type'] = 'danger';
                return redirect('/units');
            }

            // Get accessible properties for the user
            $properties = $this->property->getAll($_SESSION['user_id']);
            
            echo view('units/create', [
                'title' => 'Add New Unit',
                'properties' => $properties
            ]);
        } catch (Exception $e) {
            error_log("Error in UnitsController::create: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading form';
            $_SESSION['flash_type'] = 'danger';
            return redirect('/units');
        }
    }

    public function store()
    {
        try {
            // Only admin, landlord, manager, and agent can create units
            if (!$this->user->isAdmin() && !$this->user->isLandlord() && !$this->user->isManager() && !$this->user->isAgent()) {
                throw new Exception('Access denied');
            }

            // Verify property access
            $property = $this->property->getById($_POST['property_id'], $_SESSION['user_id']);
            if (!$property) {
                throw new Exception('Property not found or access denied');
            }

            // Log the request
            error_log("UnitsController::store - Request received");
            error_log("POST data: " . print_r($_POST, true));

            // Validate and sanitize input
            $data = [
                'property_id' => filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT),
                'unit_number' => filter_input(INPUT_POST, 'unit_number', FILTER_SANITIZE_STRING),
                'type' => filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING),
                'size' => filter_input(INPUT_POST, 'size', FILTER_VALIDATE_FLOAT),
                'rent_amount' => filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT),
                'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING)
            ];

            error_log("Sanitized data: " . print_r($data, true));

            // Validate required fields
            if (!$data['property_id'] || !$data['unit_number'] || !$data['rent_amount']) {
                throw new Exception('Missing required fields');
            }

            // Check if unit number already exists for this property
            $existingUnit = $this->unit->checkExistingUnit($data['property_id'], $data['unit_number']);
            if ($existingUnit) {
                throw new Exception('Unit number already exists for this property');
            }

            // Create unit
            $unitId = $this->unit->create($data);
            if (!$unitId) {
                throw new Exception('Failed to add unit');
            }

            // Handle file uploads
            $fileUploadHelper = new FileUploadHelper();
            $uploadErrors = [];

            // Upload unit images
            if (!empty($_FILES['unit_images']['name'][0])) {
                try {
                    $imageResult = $fileUploadHelper->uploadFiles(
                        $_FILES['unit_images'], 
                        'unit', 
                        $unitId, 
                        'image', 
                        $_SESSION['user_id']
                    );
                    if (!empty($imageResult['errors'])) {
                        $uploadErrors = array_merge($uploadErrors, $imageResult['errors']);
                    }
                } catch (Exception $e) {
                    $uploadErrors[] = 'Image upload error: ' . $e->getMessage();
                }
            }

            // Upload unit documents
            if (!empty($_FILES['unit_documents']['name'][0])) {
                try {
                    $docResult = $fileUploadHelper->uploadFiles(
                        $_FILES['unit_documents'], 
                        'unit', 
                        $unitId, 
                        'document', 
                        $_SESSION['user_id']
                    );
                    if (!empty($docResult['errors'])) {
                        $uploadErrors = array_merge($uploadErrors, $docResult['errors']);
                    }
                } catch (Exception $e) {
                    $uploadErrors[] = 'Document upload error: ' . $e->getMessage();
                }
            }

            // Update unit with file references
            if (empty($uploadErrors)) {
                $fileUploadHelper->updateEntityFiles('unit', $unitId);
            }

            $message = 'Unit added successfully';
            if (!empty($uploadErrors)) {
                $message .= ' with some file upload issues: ' . implode(', ', $uploadErrors);
            }

            $response = [
                'success' => true,
                'message' => $message,
                'unit_id' => $unitId,
                'upload_errors' => $uploadErrors
            ];
        } catch (Exception $e) {
            error_log("Error in UnitsController::store: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode($response);
        exit;
    }

    public function edit($id)
    {
        try {
            $unit = $this->unit->getById($id, $_SESSION['user_id']);
            
            if (!$unit) {
                $_SESSION['flash_message'] = 'Unit not found or access denied';
                $_SESSION['flash_type'] = 'danger';
                return redirect('/units');
            }

            // Get accessible properties for the user
            $properties = $this->property->getAll($_SESSION['user_id']);
            
            echo view('units/edit', [
                'title' => 'Edit Unit',
                'unit' => $unit,
                'properties' => $properties
            ]);
        } catch (Exception $e) {
            error_log("Error in UnitsController::edit: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading unit';
            $_SESSION['flash_type'] = 'danger';
            return redirect('/units');
        }
    }

    public function update($id)
    {
        // Start output buffering to prevent any accidental output
        ob_start();
        
        try {
            // Verify unit access
            $unit = $this->unit->getById($id, $_SESSION['user_id']);
            if (!$unit) {
                throw new Exception('Unit not found or access denied');
            }

            // Verify property access if property_id is being changed
            if (isset($_POST['property_id']) && $_POST['property_id'] != $unit['property_id']) {
                $property = $this->property->getById($_POST['property_id'], $_SESSION['user_id']);
                if (!$property) {
                    throw new Exception('Property not found or access denied');
                }
            }

            // Log the request
            error_log("UnitsController::update - Request received for ID: " . $id);
            error_log("POST data: " . print_r($_POST, true));
            // Safely access request headers (getallheaders may not exist on some servers)
            if (function_exists('getallheaders')) {
                error_log("Headers: " . print_r(getallheaders(), true));
            }

            // Treat both AJAX and normal POST as valid
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            if (!$isAjax) {
                error_log("UnitsController::update - Proceeding without X-Requested-With header");
            }

            // Validate and sanitize input (relaxed: default to existing values if missing)
            $data = [
                'unit_number' => trim((string)filter_input(INPUT_POST, 'unit_number')), // default filter
                'type' => trim((string)filter_input(INPUT_POST, 'type')),
                'size' => filter_input(INPUT_POST, 'size', FILTER_VALIDATE_FLOAT),
                'rent_amount' => filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT),
                'status' => trim((string)filter_input(INPUT_POST, 'status'))
            ];

            error_log("UnitsController::update - Sanitized data: " . print_r($data, true));

            // Validate required fields (only unit_number and rent_amount strictly required)
            if (empty($data['unit_number']) || $data['rent_amount'] === false) {
                error_log("UnitsController::update - Missing required fields (unit_number, rent_amount)");
                throw new Exception('Missing required fields: unit_number and rent_amount are required');
            }

            // Validate enum values if provided; otherwise use current DB values
            $validTypes = ['studio', '1bhk', '2bhk', '3bhk', 'other'];
            $validStatuses = ['vacant', 'occupied', 'maintenance'];
            if ($data['type'] === '' || $data['type'] === null) {
                $data['type'] = $unit['type'];
            } elseif (!in_array($data['type'], $validTypes)) {
                throw new Exception('Invalid unit type');
            }
            if ($data['status'] === '' || $data['status'] === null) {
                $data['status'] = $unit['status'];
            } elseif (!in_array($data['status'], $validStatuses)) {
                throw new Exception('Invalid unit status');
            }

            // Check if unit number already exists for this property (excluding current unit)
            $existingUnit = $this->unit->checkExistingUnit($unit['property_id'], $data['unit_number'], $id);
            if ($existingUnit) {
                throw new Exception('Unit number already exists for this property');
            }

            // Clean up data - remove null values for optional fields
            if ($data['size'] === false || $data['size'] === null) {
                $data['size'] = null;
            }

            error_log("UnitsController::update - Final data for update: " . print_r($data, true));

            // Update unit
            $updateResult = $this->unit->update($id, $data);
            error_log("UnitsController::update - Update result: " . ($updateResult ? 'true' : 'false'));

            if ($updateResult) {
                // Handle file uploads
                $fileUploadHelper = new FileUploadHelper();
                $uploadErrors = [];

                // Upload unit images
                if (!empty($_FILES['unit_images']['name'][0])) {
                    try {
                        $imageResult = $fileUploadHelper->uploadFiles(
                            $_FILES['unit_images'], 
                            'unit', 
                            $id, 
                            'image', 
                            $_SESSION['user_id']
                        );
                        if (!empty($imageResult['errors'])) {
                            $uploadErrors = array_merge($uploadErrors, $imageResult['errors']);
                        }
                    } catch (Exception $e) {
                        $uploadErrors[] = 'Image upload error: ' . $e->getMessage();
                    }
                }

                // Upload unit documents
                if (!empty($_FILES['unit_documents']['name'][0])) {
                    try {
                        $docResult = $fileUploadHelper->uploadFiles(
                            $_FILES['unit_documents'], 
                            'unit', 
                            $id, 
                            'document', 
                            $_SESSION['user_id']
                        );
                        if (!empty($docResult['errors'])) {
                            $uploadErrors = array_merge($uploadErrors, $docResult['errors']);
                        }
                    } catch (Exception $e) {
                        $uploadErrors[] = 'Document upload error: ' . $e->getMessage();
                    }
                }

                // Update unit with file references
                if (empty($uploadErrors)) {
                    try {
                        $fileUploadHelper->updateEntityFiles('unit', $id);
                    } catch (Exception $e) {
                        error_log("Error updating entity files: " . $e->getMessage());
                        $uploadErrors[] = 'File reference update error: ' . $e->getMessage();
                    }
                }

                $message = 'Unit updated successfully';
                if (!empty($uploadErrors)) {
                    $message .= ' with some file upload issues: ' . implode(', ', $uploadErrors);
                }

                $response = [
                    'success' => true,
                    'message' => $message,
                    'upload_errors' => $uploadErrors
                ];
                error_log("UnitsController::update - Unit updated successfully");
            } else {
                throw new Exception('Failed to update unit - no rows affected');
            }
        } catch (Exception $e) {
            error_log("Error in UnitsController::update: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            // Set proper HTTP status code for errors
            if (!headers_sent()) {
                http_response_code(500);
            }
        }

        // Clear any output buffer content
        ob_clean();
        
        // Set proper headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Output JSON response
        echo json_encode($response);
        exit;
    }

    public function delete($id)
    {
        try {
            // Verify unit access
            $unit = $this->unit->getById($id, $_SESSION['user_id']);
            if (!$unit) {
                throw new Exception('Unit not found or access denied');
            }

            // Only admin, property owner, manager, or agent can delete
            $property = $this->property->getById($unit['property_id'], $_SESSION['user_id']);
            if (!$this->user->isAdmin() && 
                $property['owner_id'] != $_SESSION['user_id'] && 
                $property['manager_id'] != $_SESSION['user_id'] &&
                $property['agent_id'] != $_SESSION['user_id']) {
                throw new Exception('Access denied');
            }

            // Delete unit
            if ($this->unit->delete($id)) {
                $response = [
                    'success' => true,
                    'message' => 'Unit deleted successfully'
                ];
            } else {
                throw new Exception('Failed to delete unit');
            }
        } catch (Exception $e) {
            error_log("Error in UnitsController::delete: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function show($id)
    {
        try {
            $unit = $this->unit->getById($id, $_SESSION['user_id']);
            
            if (!$unit) {
                $_SESSION['flash_message'] = 'Unit not found or access denied';
                $_SESSION['flash_type'] = 'danger';
                return redirect('/units');
            }
            
            echo view('units/show', [
                'title' => 'Unit Details',
                'unit' => $unit
            ]);
        } catch (Exception $e) {
            error_log("Error in UnitsController::show: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading unit details';
            $_SESSION['flash_type'] = 'danger';
            return redirect('/units');
        }
    }

    public function get($id)
    {
        try {
            error_log("UnitsController::get - Request received for ID: " . $id);
            // Safely access request headers (getallheaders may not exist on some servers)
            if (function_exists('getallheaders')) {
                error_log("Headers: " . print_r(getallheaders(), true));
            }

            // Treat both AJAX and normal GET as valid; prefer JSON responses for this endpoint
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                error_log("UnitsController::get - Invalid ID: " . $id);
                throw new Exception('Invalid unit ID');
            }

            // Get unit with property information
            $sql = "SELECT u.*, p.name as property_name 
                   FROM units u 
                   LEFT JOIN properties p ON u.property_id = p.id 
                   WHERE u.id = ?";
            
            error_log("UnitsController::get - Executing SQL: " . $sql . " with ID: " . $id);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $unit = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            error_log("UnitsController::get - Query result: " . print_r($unit, true));
            
            if (!$unit) {
                error_log("UnitsController::get - Unit not found with ID: " . $id);
                http_response_code(404);
                throw new Exception('Unit not found');
            }

            $response = [
                'success' => true,
                'unit' => $unit
            ];

            error_log("UnitsController::get - Sending successful response");

            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } catch (Exception $e) {
            error_log("Error in UnitsController::get: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if (!headers_sent()) {
                http_response_code(500);
            }
            
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            error_log("UnitsController::get - Sending error response: " . json_encode($response));
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    public function getByProperty($propertyId)
    {
        try {
            error_log("UnitsController::getByProperty - Request received for property ID: " . $propertyId);
            error_log("Headers: " . print_r(getallheaders(), true));
            error_log("Request URI: " . $_SERVER['REQUEST_URI']);
            error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

            // Validate property ID
            if (!is_numeric($propertyId) || $propertyId <= 0) {
                throw new Exception('Invalid property ID');
            }

            // Get units for this property using direct SQL query
            error_log("UnitsController::getByProperty - Fetching units for property ID: " . $propertyId);
            $sql = "SELECT * FROM units WHERE property_id = ?";
            error_log("UnitsController::getByProperty - SQL Query: " . $sql);

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$propertyId]);
            $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            error_log("UnitsController::getByProperty - Found units: " . print_r($units, true));

            if ($units === false) {
                throw new Exception('Failed to fetch units');
            }

            $response = [
                'success' => true,
                'units' => $units
            ];
        } catch (Exception $e) {
            error_log("Error in UnitsController::getByProperty: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function export($format = 'csv')
    {
        try {
            $userId = $_SESSION['user_id'];
            $units = $this->unit->getAll($userId);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="units.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Property','Unit Number','Type','Size','Rent Amount','Status']);
                foreach ($units as $u) {
                    fputcsv($out, [
                        $u['property_name'] ?? '',
                        $u['unit_number'] ?? '',
                        $u['type'] ?? '',
                        isset($u['size']) ? $u['size'] : '',
                        isset($u['rent_amount']) ? $u['rent_amount'] : '',
                        $u['status'] ?? ''
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="units.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Property</th><th>Unit Number</th><th>Type</th><th>Size</th><th>Rent Amount</th><th>Status</th></tr>';
                foreach ($units as $u) {
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($u['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars(isset($u['size']) ? $u['size'] : '').'</td>'
                        .'<td>'.htmlspecialchars(isset($u['rent_amount']) ? $u['rent_amount'] : '').'</td>'
                        .'<td>'.htmlspecialchars($u['status'] ?? '').'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }

            if ($format === 'pdf') {
                $html = '<h3>Units</h3><table width="100%" border="1" cellspacing="0" cellpadding="4">'
                    .'<tr><th>Property</th><th>Unit Number</th><th>Type</th><th>Size</th><th>Rent Amount</th><th>Status</th></tr>';
                foreach ($units as $u) {
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($u['property_name'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['unit_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($u['type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars(isset($u['size']) ? $u['size'] : '').'</td>'
                        .'<td>'.htmlspecialchars(isset($u['rent_amount']) ? $u['rent_amount'] : '').'</td>'
                        .'<td>'.htmlspecialchars($u['status'] ?? '').'</td>'
                        .'</tr>';
                }
                $html .= '</table>';
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="units.pdf"');
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
        $templateFile = __DIR__ . '/../../public/templates/units_template.csv';
        
        if (file_exists($templateFile)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="units_template.csv"');
            readfile($templateFile);
            exit;
        }
        
        // Fallback to empty template if file doesn't exist
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="units_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['property_name','unit_number','type','size','rent_amount','status']);
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
            $handle = fopen($tmp, 'r');
            if ($handle === false) throw new \Exception('Cannot open uploaded file');
            $header = fgetcsv($handle);
            $created = 0;
            $updated = 0;
            $userId = $_SESSION['user_id'];
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (empty($data['unit_number'])) continue;
                // Map property by name for simplicity
                $propertyName = $data['property_name'] ?? '';
                $property = null;
                foreach ($this->property->getAll($userId) as $p) {
                    if (strcasecmp($p['name'], $propertyName) === 0) { $property = $p; break; }
                }
                if (!$property) continue;
                
                // Check if unit exists by property_id and unit_number
                $existing = null;
                foreach ($this->unit->where('property_id', $property['id'], $userId) as $unit) {
                    if (strcasecmp($unit['unit_number'], $data['unit_number']) === 0) {
                        $existing = $unit;
                        break;
                    }
                }
                
                $payload = [
                    'property_id' => $property['id'],
                    'unit_number' => $data['unit_number'] ?? '',
                    'type' => $data['type'] ?? 'other',
                    'size' => $data['size'] !== '' ? (float)$data['size'] : null,
                    'rent_amount' => $data['rent_amount'] !== '' ? (float)$data['rent_amount'] : null,
                    'status' => $data['status'] ?? 'vacant'
                ];
                
                if ($existing) {
                    // Update existing unit
                    if ($this->unit->update($existing['id'], $payload)) {
                        $updated++;
                    }
                } else {
                    // Create new unit
                    if ($this->unit->create($payload)) $created++;
                }
            }
            fclose($handle);
            $message = [];
            if ($created > 0) $message[] = "Created {$created}";
            if ($updated > 0) $message[] = "Updated {$updated}";
            $_SESSION['flash_message'] = count($message) > 0 ? implode(', ', $message) . ' units' : 'No units imported';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Import failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        // Add timestamp to force page reload
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        redirect('/units?t=' . time());
    }

    /**
     * Get files for a unit
     */
    public function getFiles($id)
    {
        try {
            error_log("UnitsController::getFiles called for unit ID: $id");
            
            // Verify unit access
            $unit = $this->unit->getById($id, $_SESSION['user_id']);
            if (!$unit) {
                throw new Exception('Unit not found or access denied');
            }

            // Initialize empty arrays as defaults
            $images = [];
            $documents = [];

            // Try to get files if file_uploads table exists
            try {
                $fileUploadHelper = new FileUploadHelper();
                $images = $fileUploadHelper->getEntityFiles('unit', $id, 'image');
                $documents = $fileUploadHelper->getEntityFiles('unit', $id, 'document');
                
                error_log("UnitsController::getFiles - Found " . count($images) . " images and " . count($documents) . " documents");
                if (!empty($images)) {
                    error_log("First image URL: " . $images[0]['url']);
                    error_log("First image upload_path: " . $images[0]['upload_path']);
                }
            } catch (Exception $e) {
                // If file_uploads table doesn't exist or other error, log and continue with empty arrays
                error_log("FileUploadHelper error in UnitsController::getFiles: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Don't throw the error, just use empty arrays
                $images = [];
                $documents = [];
            }

            $response = [
                'success' => true,
                'images' => $images,
                'documents' => $documents
            ];
            
            error_log("UnitsController::getFiles - Returning response: " . json_encode($response));
        } catch (Exception $e) {
            error_log("Error in UnitsController::getFiles: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            // Set proper HTTP status code for errors
            if (!headers_sent()) {
                http_response_code(500);
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Get tenant information for a unit
     */
    public function getTenant($unitId)
    {
        try {
            // Verify unit access
            $unit = $this->unit->getById($unitId, $_SESSION['user_id']);
            if (!$unit) {
                throw new Exception('Unit not found or access denied');
            }

            // Get current lease for this unit
            $sql = "SELECT t.*, l.start_date as move_in_date 
                   FROM tenants t
                   JOIN leases l ON t.id = l.tenant_id
                   WHERE l.unit_id = ? AND l.status = 'active'
                   ORDER BY l.created_at DESC
                   LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$unitId]);
            $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'tenant' => $tenant
            ];
        } catch (Exception $e) {
            error_log("Error in UnitsController::getTenant: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}