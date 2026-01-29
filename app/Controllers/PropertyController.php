<?php

namespace App\Controllers;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Models\Employee;
use App\Database\Connection;
use App\Helpers\FileUploadHelper;
use Exception;

class PropertyController
{
    private $property;
    private $unit;
    private $user;

    public function __construct()
    {
        $this->property = new Property();
        $this->unit = new Unit();
        $this->user = new User();
        
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
            header('Location: ' . BASE_URL . '/home');
            exit;
        }
        
        // Load user data
        $this->user->find($_SESSION['user_id']);
    }

    public function index()
    {
        try {
            $properties = $this->property->getAll($_SESSION['user_id']);
            // Load caretakers for selection dropdown
            $employeeModel = new Employee();
            $caretakers = $employeeModel->getCaretakers($_SESSION['user_id']);
            
            // If current user is a caretaker, only show assigned properties
            if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'caretaker') {
                $db = $this->property->getDb();
                $stmt = $db->prepare("SELECT * FROM properties WHERE caretaker_user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $properties = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        
            // Calculate statistics for each property
            foreach ($properties as &$property) {
                // Get all units for this property with accurate status
                $units = $this->unit->where('property_id', $property['id'], $_SESSION['user_id']);
                
                // Calculate total units and occupied units
                $totalUnits = count($units);
                $occupiedUnits = array_filter($units, function($unit) {
                    // Check both unit status and active lease status
                    return $unit['status'] === 'occupied';
                });
                $occupiedCount = count($occupiedUnits);
                
                // Calculate occupancy rate
                $property['occupancy_rate'] = $totalUnits > 0 
                    ? ($occupiedCount / $totalUnits) * 100 
                    : 0;
                
                // Calculate monthly income (only from occupied units with valid rent)
                $property['monthly_income'] = array_sum(
                    array_map(function($unit) {
                        return $unit['status'] === 'occupied' && is_numeric($unit['rent_amount']) 
                            ? floatval($unit['rent_amount']) 
                            : 0;
                    }, $units)
                );
                
                // Store unit counts
                $property['units_count'] = $totalUnits;
                $property['occupied_units'] = $occupiedCount;
            }

            echo view('properties/index', [
                'title' => 'Properties',
                'properties' => $properties,
                'caretakers' => $caretakers,
            ]);
        } catch (Exception $e) {
            error_log("Error in PropertyController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading properties';
            $_SESSION['flash_type'] = 'danger';
            echo view('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading the properties.'
            ]);
        }
    }

    public function create()
    {
        // Only admin, landlord, agent and manager can create properties
        if (!$this->user->isAdmin() && !$this->user->isLandlord() && !$this->user->isAgent() && !$this->user->isManager()) {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/properties');
            exit;
        }

        echo view('properties/create', [
            'title' => 'Add New Property'
        ]);
    }

    public function store()
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        try {
            // Only admin, landlord, agent and manager can create properties
            if (!$this->user->isAdmin() && !$this->user->isLandlord() && !$this->user->isAgent() && !$this->user->isManager()) {
                throw new Exception('Access denied');
            }

            // Check if it's an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            // Validate and sanitize input (PHP 8+ safe)
            $data = [
                'name' => filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING),
                'address' => filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING),
                'city' => filter_var($_POST['city'] ?? '', FILTER_SANITIZE_STRING),
                'state' => filter_var($_POST['state'] ?? '', FILTER_SANITIZE_STRING),
                'zip_code' => filter_var($_POST['zip_code'] ?? '', FILTER_SANITIZE_STRING),
                'property_type' => filter_var($_POST['property_type'] ?? '', FILTER_SANITIZE_STRING),
                'description' => filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING),
                'year_built' => filter_var($_POST['year_built'] ?? '', FILTER_VALIDATE_INT),
                'total_area' => filter_var($_POST['total_area'] ?? '', FILTER_VALIDATE_FLOAT),
                // caretaker fields now handled via caretaker_employee_id
            ];
            $caretakerEmployeeId = isset($_POST['caretaker_employee_id']) ? (int)$_POST['caretaker_employee_id'] : 0;

            // Set owner_id, manager_id, or agent_id based on role
            if ($this->user->isLandlord()) {
                $data['owner_id'] = $_SESSION['user_id'];
            } elseif ($this->user->isManager()) {
                $data['manager_id'] = $_SESSION['user_id'];
            } elseif ($this->user->isAgent()) {
                $data['agent_id'] = $_SESSION['user_id'];
            }

            // Validate required fields
            $requiredFields = ['name', 'address', 'city', 'state', 'zip_code', 'property_type'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = ucfirst(str_replace('_', ' ', $field));
                }
            }

            if (!empty($missingFields)) {
                throw new Exception('The following fields are required: ' . implode(', ', $missingFields));
            }

            // Validate ZIP code format
            if (!preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $data['zip_code'])) {
                throw new Exception('Invalid ZIP code format. Use 12345 or 12345-6789');
            }

            // Start transaction
            $db = Connection::getInstance()->getConnection();
            $db->beginTransaction();

            try {
                // Create property
                $propertyId = $this->property->create($data);

                if (!$propertyId) {
                    throw new Exception('Failed to create property');
                }

                // If caretaker selected, assign caretaker to this property and create caretaker user if needed
                if ($caretakerEmployeeId > 0) {
                    $empModel = new Employee();
                    $emp = $empModel->find($caretakerEmployeeId);
                    if ($emp) {
                        $caretakerName = $emp['name'] ?? '';
                        $caretakerContact = $emp['phone'] ?: ($emp['email'] ?? null);
                        $userModel = new User();
                        // Try find existing user by email or phone
                        $caretakerUserId = null;
                        if (!empty($emp['email'])) {
                            $u = $userModel->findByEmail($emp['email']);
                            if ($u) { $caretakerUserId = $u['id']; }
                        }
                        if (!$caretakerUserId && !empty($emp['phone'])) {
                            $stmtU = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                            $stmtU->execute([$emp['phone']]);
                            $rowU = $stmtU->fetch(\PDO::FETCH_ASSOC);
                            if ($rowU) { $caretakerUserId = $rowU['id']; }
                        }
                        if (!$caretakerUserId) {
                            // Ensure caretaker role exists in users.role enum
                            try {
                                $stmtRole = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
                                $col = $stmtRole->fetch(\PDO::FETCH_ASSOC);
                                if ($col && isset($col['Type']) && strpos($col['Type'], "'caretaker'") === false) {
                                    $db->exec("ALTER TABLE users MODIFY role ENUM('admin','landlord','agent','manager','caretaker') NOT NULL DEFAULT 'agent'");
                                }
                            } catch (\Exception $e) {}
                            // Generate password from name+phone
                            $name = $caretakerName ?: 'Caretaker';
                            $phoneDigits = preg_replace('/\D+/', '', (string)($emp['phone'] ?? ''));
                            $base = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', trim($name))[0] ?? 'caretaker'));
                            $suffix = substr($phoneDigits, -4) ?: '1234';
                            $plainPassword = $base . $suffix . '!';
                            $caretakerUserId = $userModel->createUser([
                                'name' => $caretakerName,
                                'email' => $emp['email'] ?: (($emp['phone'] ?? 'caretaker') . '@caretaker.local'),
                                'phone' => $emp['phone'] ?? null,
                                'address' => null,
                                'password' => $plainPassword,
                                'role' => 'caretaker',
                                'is_subscribed' => 0
                            ]);
                            // Notify current user
                            $to = $_SESSION['user_email'] ?? null;
                            if ($to) {
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $loginUrl = $scheme . '://' . $host . BASE_URL . '/';
                                $subject = 'Caretaker account created';
                                $body = "Caretaker account created for {$caretakerName} (contact: {$caretakerContact}).\nLogin URL: {$loginUrl}\nUsername (email/phone): " . ($emp['email'] ?: $emp['phone']) . "\nTemporary Password: {$plainPassword}";
                                send_email($to, $subject, $body);
                            }
                        }
                        // Update property with caretaker details
                        $stmtUpd = $db->prepare("UPDATE properties SET caretaker_user_id = ?, caretaker_name = ?, caretaker_contact = ? WHERE id = ?");
                        $stmtUpd->execute([$caretakerUserId, $caretakerName, $caretakerContact, $propertyId]);
                    }
                }

                // Handle file uploads
                $fileUploadHelper = new FileUploadHelper();
                $uploadErrors = [];

                // Upload property images
                if (!empty($_FILES['property_images']['name'][0])) {
                    try {
                        $imageResult = $fileUploadHelper->uploadFiles(
                            $_FILES['property_images'], 
                            'property', 
                            $propertyId, 
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

                // Upload property documents
                if (!empty($_FILES['property_documents']['name'][0])) {
                    try {
                        $docResult = $fileUploadHelper->uploadFiles(
                            $_FILES['property_documents'], 
                            'property', 
                            $propertyId, 
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

                // Handle units if provided
                if (!empty($_POST['units'])) {
                    foreach ($_POST['units'] as $index => $unit) {
                        if (!empty($unit['number']) && !empty($unit['rent'])) {
                            $unitData = [
                                'property_id' => $propertyId,
                                'unit_number' => $unit['number'],
                                'type' => $unit['type'] ?? 'other',
                                'size' => !empty($unit['size']) ? (float)$unit['size'] : null,
                                'rent_amount' => (float)$unit['rent'],
                                'status' => 'vacant'
                            ];
                            
                            $unitId = $this->unit->create($unitData);
                            if (!$unitId) {
                                throw new Exception('Failed to create unit: ' . $unit['number']);
                            }
                        }
                    }
                }

            $message = 'Property added successfully';
            if (!empty($uploadErrors)) {
                $message .= ' with some file upload issues: ' . implode(', ', $uploadErrors);
            }

            $response = [
                'success' => true,
                'message' => $message,
                'property_id' => $propertyId,
                'upload_errors' => $uploadErrors
            ];

            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode($response);
            exit;
        }

        // For non-AJAX requests
        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = $response['success'] ? 'success' : 'danger';
        header('Location: ' . BASE_URL . '/properties'); exit;
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }

        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];

        if ($isAjax) {
            header('Content-Type: application/json');
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode($response);
            exit;
        }

        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/properties'); exit;
    }
    }

    public function edit($id)
    {
        try {
            $property = $this->property->getById($id, $_SESSION['user_id']);
            
            if (!$property) {
                $_SESSION['flash_message'] = 'Property not found or access denied';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/properties'); exit;
            }

            echo view('properties/edit', [
                'title' => 'Edit Property',
                'property' => $property
            ]);
        } catch (Exception $e) {
            error_log("Error in PropertyController::edit: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading property';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/properties'); exit;
        }
    }

    public function update($id)
    {
        try {
            // Verify access
            $property = $this->property->getById($id, $_SESSION['user_id']);
            
            if (!$property) {
                throw new Exception('Property not found or access denied');
            }

            // Check if it's an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            // Validate and sanitize input (PHP 8+ safe)
            $data = [
                'name' => (string)filter_input(INPUT_POST, 'name'),
                'address' => (string)filter_input(INPUT_POST, 'address'),
                'city' => (string)filter_input(INPUT_POST, 'city'),
                'state' => (string)filter_input(INPUT_POST, 'state'),
                'zip_code' => (string)filter_input(INPUT_POST, 'zip_code'),
                'property_type' => (string)filter_input(INPUT_POST, 'property_type'),
                'description' => (string)filter_input(INPUT_POST, 'description'),
                'year_built' => filter_input(INPUT_POST, 'year_built', FILTER_VALIDATE_INT),
                'total_area' => filter_input(INPUT_POST, 'total_area', FILTER_VALIDATE_FLOAT),
                // caretaker handled via caretaker_employee_id
            ];
            $caretakerEmployeeId = (int)($_POST['caretaker_employee_id'] ?? 0);

            // Validate required fields
            $requiredFields = ['name', 'address', 'city', 'state', 'zip_code', 'property_type'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = ucfirst(str_replace('_', ' ', $field));
                }
            }

            if (!empty($missingFields)) {
                throw new Exception('The following fields are required: ' . implode(', ', $missingFields));
            }

            // Validate ZIP code format
            if (!preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $data['zip_code'])) {
                throw new Exception('Invalid ZIP code format. Use 12345 or 12345-6789');
            }

            // Update property
            if ($this->property->update($id, $data)) {
                // Assign caretaker if provided
                if ($caretakerEmployeeId > 0) {
                    $empModel = new Employee();
                    $emp = $empModel->find($caretakerEmployeeId);
                    if ($emp) {
                        $userModel = new User();
                        $caretakerUserId = null;
                        if (!empty($emp['email'])) {
                            $u = $userModel->findByEmail($emp['email']);
                            if ($u) { $caretakerUserId = $u['id']; }
                        }
                        if (!$caretakerUserId && !empty($emp['phone'])) {
                            $stmtU = $this->property->getDb()->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                            $stmtU->execute([$emp['phone']]);
                            $rowU = $stmtU->fetch(\PDO::FETCH_ASSOC);
                            if ($rowU) { $caretakerUserId = $rowU['id']; }
                        }
                        if (!$caretakerUserId) {
                            // Create user
                            $name = $emp['name'] ?? 'Caretaker';
                            $phoneDigits = preg_replace('/\D+/', '', (string)($emp['phone'] ?? ''));
                            $base = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', trim($name))[0] ?? 'caretaker'));
                            $suffix = substr($phoneDigits, -4) ?: '1234';
                            $plainPassword = $base . $suffix . '!';
                            $caretakerUserId = $userModel->createUser([
                                'name' => $name,
                                'email' => $emp['email'] ?: (($emp['phone'] ?? 'caretaker') . '@caretaker.local'),
                                'phone' => $emp['phone'] ?? null,
                                'address' => null,
                                'password' => $plainPassword,
                                'role' => 'caretaker',
                                'is_subscribed' => 0
                            ]);
                            // Notify current user
                            $to = $_SESSION['user_email'] ?? null;
                            if ($to) {
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $loginUrl = $scheme . '://' . $host . BASE_URL . '/';
                                $subject = 'Caretaker account created';
                                $body = "Caretaker account created for {$name} (contact: " . ($emp['phone'] ?: $emp['email']) . ").\nLogin URL: {$loginUrl}\nUsername (email/phone): " . ($emp['email'] ?: $emp['phone']) . "\nTemporary Password: {$plainPassword}";
                                send_email($to, $subject, $body);
                            }
                        }
                        $stmtUpd = $this->property->getDb()->prepare("UPDATE properties SET caretaker_user_id = ?, caretaker_name = ?, caretaker_contact = ? WHERE id = ?");
                        $stmtUpd->execute([$caretakerUserId, $emp['name'] ?? '', ($emp['phone'] ?: $emp['email']), $id]);
                    }
                }
                // Handle file uploads
                $fileUploadHelper = new FileUploadHelper();
                $uploadErrors = [];

                // Upload property images
                if (!empty($_FILES['property_images']['name'][0])) {
                    try {
                        $imageResult = $fileUploadHelper->uploadFiles(
                            $_FILES['property_images'], 
                            'property', 
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

                // Upload property documents
                if (!empty($_FILES['property_documents']['name'][0])) {
                    try {
                        $docResult = $fileUploadHelper->uploadFiles(
                            $_FILES['property_documents'], 
                            'property', 
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

                // Update property with file references
                if (empty($uploadErrors)) {
                    $fileUploadHelper->updateEntityFiles('property', $id);
                }

                $message = 'Property updated successfully';
                if (!empty($uploadErrors)) {
                    $message .= ' with some file upload issues: ' . implode(', ', $uploadErrors);
                }

                $response = [
                    'success' => true,
                    'message' => $message,
                    'upload_errors' => $uploadErrors
                ];
            } else {
                throw new Exception('Failed to update property');
            }
        } catch (Exception $e) {
            error_log("Error in PropertyController::update: " . $e->getMessage());
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

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = $response['success'] ? 'success' : 'danger';
        header('Location: ' . BASE_URL . '/properties'); exit;
    }

    public function delete($id)
    {
        try {
            // Verify access
            $property = $this->property->getById($id, $_SESSION['user_id']);
            
            if (!$property) {
                throw new Exception('Property not found or access denied');
            }

            // Only admin, owner, manager, or agent can delete
            if (!$this->user->isAdmin() && 
                $property['owner_id'] != $_SESSION['user_id'] && 
                $property['manager_id'] != $_SESSION['user_id'] &&
                $property['agent_id'] != $_SESSION['user_id']) {
                throw new Exception('Access denied');
            }

            if ($this->property->delete($id)) {
                $response = [
                    'success' => true,
                    'message' => 'Property deleted successfully'
                ];
            } else {
                throw new Exception('Failed to delete property');
            }
        } catch (Exception $e) {
            error_log("Error in PropertyController::delete: " . $e->getMessage());
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

    public function show($id)
    {
        try {
            $property = $this->property->getById($id, $_SESSION['user_id']);
            
            if (!$property) {
                $_SESSION['flash_message'] = 'Property not found or access denied';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/properties'); exit;
            }

            $units = $this->unit->where('property_id', $id);
            $property['occupancy_rate'] = $this->property->getOccupancyRate($id);
            $property['monthly_income'] = $this->property->getMonthlyIncome($id);

            echo view('properties/show', [
                'title' => $property['name'],
                'property' => $property,
                'units' => $units
            ]);
        } catch (Exception $e) {
            error_log("Error in PropertyController::show: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading property details';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/properties'); exit;
        }
    }

    public function get($id)
    {
        try {
            // Verify access
            $property = $this->property->getById($id, $_SESSION['user_id']);
            
            if (!$property) {
                throw new Exception('Property not found or access denied');
            }

            // Get units for this property
            $units = $this->unit->where('property_id', $property['id'], $_SESSION['user_id']);
            $occupied = array_filter($units, function($unit) {
                return $unit['status'] === 'occupied';
            });
            
            // Calculate statistics
            $property['units_count'] = count($units);
            $property['occupancy_rate'] = $property['units_count'] > 0 
                ? (count($occupied) / $property['units_count']) * 100 
                : 0;
            $property['monthly_income'] = array_sum(array_column($units, 'rent_amount'));
            $property['units'] = $units;

            $response = [
                'success' => true,
                'property' => $property
            ];
        } catch (Exception $e) {
            error_log("Error in PropertyController::get: " . $e->getMessage());
            http_response_code(500);
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
            $properties = $this->property->getAll($userId);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="properties.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Address','City','State','ZIP','Type','Units','Occupied','Occupancy %','Monthly Income']);
                foreach ($properties as $p) {
                    $units = $this->unit->where('property_id', $p['id'], $userId);
                    $totalUnits = count($units);
                    $occupied = array_sum(array_map(function($u){return $u['status'] === 'occupied' ? 1 : 0;}, $units));
                    $occ = $totalUnits > 0 ? round(($occupied/$totalUnits)*100,1) : 0;
                    $income = array_sum(array_map(function($u){return is_numeric($u['rent_amount']) ? (float)$u['rent_amount'] : 0;}, $units));
                    fputcsv($out, [
                        $p['name'], $p['address'], $p['city'], $p['state'], $p['zip_code'], $p['property_type'],
                        $totalUnits, $occupied, $occ, $income
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="properties.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Name</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th><th>Type</th><th>Units</th><th>Occupied</th><th>Occupancy %</th><th>Monthly Income</th></tr>';
                foreach ($properties as $p) {
                    $units = $this->unit->where('property_id', $p['id'], $userId);
                    $totalUnits = count($units);
                    $occupied = array_sum(array_map(function($u){return $u['status'] === 'occupied' ? 1 : 0;}, $units));
                    $occ = $totalUnits > 0 ? round(($occupied/$totalUnits)*100,1) : 0;
                    $income = array_sum(array_map(function($u){return is_numeric($u['rent_amount']) ? (float)$u['rent_amount'] : 0;}, $units));
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($p['name']).'</td>'
                        .'<td>'.htmlspecialchars($p['address']).'</td>'
                        .'<td>'.htmlspecialchars($p['city']).'</td>'
                        .'<td>'.htmlspecialchars($p['state']).'</td>'
                        .'<td>'.htmlspecialchars($p['zip_code']).'</td>'
                        .'<td>'.htmlspecialchars($p['property_type']).'</td>'
                        .'<td>'.$totalUnits.'</td>'
                        .'<td>'.$occupied.'</td>'
                        .'<td>'.$occ.'</td>'
                        .'<td>'.$income.'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }

            if ($format === 'pdf') {
                $html = '<h3>Properties</h3><table width="100%" border="1" cellspacing="0" cellpadding="4">'
                    .'<tr><th>Name</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th><th>Type</th><th>Units</th><th>Occupied</th><th>Occupancy %</th><th>Monthly Income</th></tr>';
                foreach ($properties as $p) {
                    $units = $this->unit->where('property_id', $p['id'], $userId);
                    $totalUnits = count($units);
                    $occupied = array_sum(array_map(function($u){return $u['status'] === 'occupied' ? 1 : 0;}, $units));
                    $occ = $totalUnits > 0 ? round(($occupied/$totalUnits)*100,1) : 0;
                    $income = array_sum(array_map(function($u){return is_numeric($u['rent_amount']) ? (float)$u['rent_amount'] : 0;}, $units));
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($p['name']).'</td>'
                        .'<td>'.htmlspecialchars($p['address']).'</td>'
                        .'<td>'.htmlspecialchars($p['city']).'</td>'
                        .'<td>'.htmlspecialchars($p['state']).'</td>'
                        .'<td>'.htmlspecialchars($p['zip_code']).'</td>'
                        .'<td>'.htmlspecialchars($p['property_type']).'</td>'
                        .'<td>'.$totalUnits.'</td>'
                        .'<td>'.$occupied.'</td>'
                        .'<td>'.$occ.'</td>'
                        .'<td>'.$income.'</td>'
                        .'</tr>';
                }
                $html .= '</table>';
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="properties.pdf"');
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
        $templateFile = __DIR__ . '/../../public/templates/properties_template.csv';
        
        if (file_exists($templateFile)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="properties_template.csv"');
            readfile($templateFile);
            exit;
        }
        
        // Fallback to empty template if file doesn't exist
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="properties_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name','address','city','state','zip_code','property_type','year_built','total_area','description']);
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
                throw new Exception('Invalid upload');
            }
            $handle = fopen($tmp, 'r');
            if ($handle === false) throw new Exception('Cannot open uploaded file');
            $header = fgetcsv($handle);
            $expected = ['name','address','city','state','zip_code','property_type','year_built','total_area'];
            $created = 0;
            $userId = $_SESSION['user_id'];
            $updated = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (empty($data['name'])) continue;
                
                // Check if property exists by name
                $existing = null;
                foreach ($this->property->getAll($userId) as $prop) {
                    if (strcasecmp($prop['name'], $data['name']) === 0) {
                        $existing = $prop;
                        break;
                    }
                }
                
                $payload = [
                    'name' => $data['name'] ?? '',
                    'address' => $data['address'] ?? '',
                    'city' => $data['city'] ?? '',
                    'state' => $data['state'] ?? '',
                    'zip_code' => $data['zip_code'] ?? '',
                    'property_type' => $data['property_type'] ?? '',
                    'year_built' => $data['year_built'] ?? null,
                    'total_area' => $data['total_area'] ?? null,
                ];
                
                if ($existing) {
                    // Update existing property
                    if ($this->property->update($existing['id'], $payload)) {
                        $updated++;
                    }
                } else {
                    // Create new property
                    if ($this->user->isLandlord()) $payload['owner_id'] = $userId;
                    if ($this->user->isManager()) $payload['manager_id'] = $userId;
                    if ($this->user->isAgent()) $payload['agent_id'] = $userId;
                    $id = $this->property->create($payload);
                    if ($id) $created++;
                }
            }
            fclose($handle);
            $message = [];
            if ($created > 0) $message[] = "Created {$created}";
            if ($updated > 0) $message[] = "Updated {$updated}";
            $_SESSION['flash_message'] = count($message) > 0 ? implode(', ', $message) . ' properties' : 'No properties imported';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Import failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        // Add timestamp to force page reload
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Location: ' . BASE_URL . '/properties?t=' . time()); exit;
    }

    public function storeUnit()
    {
        try {
            // Validate and sanitize input
            $data = [
                'property_id' => filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT),
                'unit_number' => filter_input(INPUT_POST, 'unit_number', FILTER_SANITIZE_STRING),
                'type' => filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING),
                'size' => filter_input(INPUT_POST, 'size', FILTER_VALIDATE_FLOAT),
                'rent_amount' => filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT),
                'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING)
            ];

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
            if ($this->unit->create($data)) {
                $response = [
                    'success' => true,
                    'message' => 'Unit added successfully'
                ];
            } else {
                throw new Exception('Failed to add unit');
            }
        } catch (Exception $e) {
            error_log("Error in PropertyController::storeUnit: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function updateUnit($id)
    {
        try {
            // Validate and sanitize input
            $data = [
                'unit_number' => filter_input(INPUT_POST, 'unit_number', FILTER_SANITIZE_STRING),
                'type' => filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING),
                'size' => filter_input(INPUT_POST, 'size', FILTER_VALIDATE_FLOAT),
                'rent_amount' => filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT),
                'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING)
            ];

            // Validate required fields
            if (!$data['unit_number'] || !$data['rent_amount']) {
                throw new Exception('Missing required fields');
            }

            // Check if unit exists
            $unit = $this->unit->find($id);
            if (!$unit) {
                throw new Exception('Unit not found');
            }

            // Check if unit number already exists for this property (excluding current unit)
            $existingUnit = $this->unit->checkExistingUnit($unit['property_id'], $data['unit_number'], $id);

            if ($existingUnit) {
                throw new Exception('Unit number already exists for this property');
            }

            // Update unit
            if ($this->unit->update($id, $data)) {
                $response = [
                    'success' => true,
                    'message' => 'Unit updated successfully'
                ];
            } else {
                throw new Exception('Failed to update unit');
            }
        } catch (Exception $e) {
            error_log("Error in PropertyController::updateUnit: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function deleteUnit($id)
    {
        try {
            // Check if unit exists
            $unit = $this->unit->find($id);
            if (!$unit) {
                throw new Exception('Unit not found');
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
            error_log("Error in PropertyController::deleteUnit: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function getUnit($id)
    {
        try {
            $unit = $this->unit->find($id);
            
            if (!$unit) {
                throw new Exception('Unit not found');
            }

            $response = [
                'success' => true,
                'unit' => $unit
            ];
        } catch (Exception $e) {
            error_log("Error in PropertyController::getUnit: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function getUnits($propertyId)
    {
        try {
            // Set JSON header
            header('Content-Type: application/json');

            // Get only vacant units for the property
            $units = $this->unit->where('property_id', $propertyId);
            $vacantUnits = array_filter($units, function($unit) {
                return $unit['status'] !== 'occupied';
            });

            echo json_encode([
                'success' => true,
                'units' => array_values($vacantUnits)
            ]);
        } catch (Exception $e) {
            error_log("Error in PropertyController::getUnits: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error loading units'
            ]);
        }
        exit;
    }

    private function createUnits($propertyId, $units)
    {
        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO units (
                    property_id, 
                    unit_number, 
                    type,
                    size,
                    rent_amount, 
                    status
                ) VALUES (
                    :property_id, 
                    :unit_number, 
                    :type,
                    :size,
                    :rent_amount, 
                    'vacant'
                )
            ");

            foreach ($units as $unit) {
                $stmt->execute([
                    'property_id' => $propertyId,
                    'unit_number' => $unit['number'],
                    'type' => $unit['type'],
                    'size' => !empty($unit['size']) ? $unit['size'] : null,
                    'rent_amount' => $unit['rent']
                ]);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error creating units: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get files for a property
     */
    public function getFiles($id)
    {
        try {
            // Verify access
            $property = $this->property->getById($id, $_SESSION['user_id']);
            
            if (!$property) {
                throw new Exception('Property not found or access denied');
            }

            $fileUploadHelper = new FileUploadHelper();
            
            // Check if file_uploads table exists, if not return empty arrays
            try {
                $images = $fileUploadHelper->getEntityFiles('property', $id, 'image');
                $documents = $fileUploadHelper->getEntityFiles('property', $id, 'document');
            } catch (Exception $e) {
                // If file_uploads table doesn't exist, return empty arrays
                error_log("FileUploadHelper error (table may not exist): " . $e->getMessage());
                $images = [];
                $documents = [];
            }

            $response = [
                'success' => true,
                'images' => $images,
                'documents' => $documents
            ];
        } catch (Exception $e) {
            error_log("Error in PropertyController::getFiles: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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
} 
