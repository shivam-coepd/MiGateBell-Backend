<?php
require_once __DIR__.'/../../core/BaseController.php';
require_once __DIR__.'/../../helpers/uploader.php';

class FlatController extends BaseController {
     
    public function addHome() {
        try {
            // Authenticate user
            $user = $this->auth->authenticate();
            
            // Accept both JSON and multipart/form-data
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            $rawInput = file_get_contents("php://input");
            
            $data = [];
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode($rawInput, true) ?: [];
            } else {
                $data = $_POST;
            }
            
            // Get form data
            $societyId       = isset($data['society_id'])        ? (int)$data['society_id']         : null;
            $buildingId      = isset($data['building_id'])       ? (int)$data['building_id']        : null;
            $flatNumber      = isset($data['flat_number'])       ? trim($data['flat_number'])       : null;
            $flatType        = isset($data['flat_type'])         ? trim($data['flat_type'])         : null;
            $floorNumber     = isset($data['floor_number'])      ? trim($data['floor_number'])      : null;
            $areaSqft        = isset($data['area_sqft'])         ? (float)$data['area_sqft']        : null;
            $userRole        = isset($data['user_role'])         ? trim($data['user_role'])         : null;
            $occupancyStatus = isset($data['occupancy_status'])  ? trim($data['occupancy_status'])  : null;
            
            // Validate required fields
            $errors = [];
            if (empty($societyId))  $errors[] = "society_id is required";
            if (empty($buildingId)) $errors[] = "building_id is required";
            if (empty($flatNumber)) $errors[] = "flat_number is required";
            if (empty($userRole))   $errors[] = "user_role is required";
            
            // Validate user_role
            $validUserRoles = ['owner', 'renting_family', 'renting_flatmates'];
            if (!in_array($userRole, $validUserRoles)) {
                $errors[] = "user_role must be one of: " . implode(', ', $validUserRoles);
            }
            
            // Validate flat_type
            $validFlatTypes = ['1RK', '1BHK', '2BHK', '3BHK', '4BHK', '4BHK+'];
            if ($flatType && !in_array($flatType, $validFlatTypes)) {
                $errors[] = "flat_type must be one of: " . implode(', ', $validFlatTypes);
            }
            
            // Validate occupancy_status if provided
            if ($occupancyStatus) {
                $validOccupancyStatuses = ['residing', 'let_out', 'empty'];
                if (!in_array($occupancyStatus, $validOccupancyStatuses)) {
                    $errors[] = "occupancy_status must be one of: " . implode(', ', $validOccupancyStatuses);
                }
            }
            
            if (!empty($errors)) {
                Response::validationError($errors);
            }
            
            // Default flat_type
            $flatType = $flatType ?: '2BHK';
            
            // Check if society exists
            $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
            $stmt->execute([$societyId]);
            if (!$stmt->fetch()) {
                Response::notFound("Society not found");
            }
            
            // Check if building exists and belongs to the society
            $stmt = $this->db->prepare("SELECT id FROM buildings WHERE id = ? AND society_id = ?");
            $stmt->execute([$buildingId, $societyId]);
            if (!$stmt->fetch()) {
                Response::notFound("Building not found or does not belong to the specified society");
            }
            
            // Check if flat exists
            $stmt = $this->db->prepare("SELECT id, owner_id, tenant_id FROM flats WHERE building_id = ? AND flat_number = ?");
            $stmt->execute([$buildingId, $flatNumber]);
            $flat = $stmt->fetch();
            
            $isNewFlat = false;
            if (!$flat) {
                // Flat doesn't exist — create it first
                $isNewFlat = true;
            } else {
                // Flat exists — check if already has an owner/tenant to prevent duplicates
                if (($userRole === 'owner' && $flat['owner_id'] !== null) || 
                    ($userRole !== 'owner' && $flat['tenant_id'] !== null)) {
                    Response::error("This flat already has an " . ($userRole === 'owner' ? 'owner' : 'tenant'), 409);
                }
            }
            
            // Handle document upload (only for multipart/form-data requests)
            $documentUrl = null;
            if (stripos($contentType, 'multipart/form-data') !== false && isset($_FILES['document'])) {
                // Validate file
                if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                    Response::error("Document upload failed: " . $this->getUploadErrorMessage($_FILES['document']['error']));
                }
                
                // Check file size (max 10MB)
                if ($_FILES['document']['size'] > 10 * 1024 * 1024) {
                    Response::error("Document size exceeds maximum allowed size of 10MB");
                }
                
                // Validate file type based on user role
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['document']['tmp_name']);
                finfo_close($finfo);
                
                $allowedTypes = [];
                if ($userRole === 'owner' && $occupancyStatus === 'residing') {
                    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                } else if ($userRole === 'renting_family' || $userRole === 'renting_flatmates') {
                    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                }
                
                if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
                    Response::error("Invalid document type. Allowed types: " . implode(', ', $allowedTypes));
                }
                
                // Upload document
                $uploader = new Uploader('./uploads/');
                $uploadResult = $uploader->uploadFile($_FILES['document'], 'verification');
                $documentUrl = $uploadResult['path'];
            } else {
                // For multipart requests only, document is required in certain cases
                if (stripos($contentType, 'multipart/form-data') !== false) {
                    if ($userRole === 'owner' && $occupancyStatus === 'residing') {
                        Response::error("Document (Sales Deed) is required for owner residing");
                    } else if ($userRole === 'renting_family' || $userRole === 'renting_flatmates') {
                        Response::error("Document (Rental Agreement) is required for tenants");
                    }
                }
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            try {
                $flatId;
                if ($isNewFlat) {
                    // Create new flat
                    $flatId = $this->insert('flats', [
                        'building_id'        => $buildingId,
                        'flat_number'        => $flatNumber,
                        'flat_type'          => $flatType,
                        'floor_number'       => $floorNumber ?: null,
                        'area_sqft'          => $areaSqft,
                        'society_id'         => $societyId,
                        'is_occupied'        => 1,
                        'user_role'          => $userRole,
                        'occupancy_status'   => $occupancyStatus,
                        'document_url'       => $documentUrl,
                        'verification_status'=> 'pending',
                        'owner_id'           => $userRole === 'owner' ? $user['uid'] : null,
                        'tenant_id'          => $userRole !== 'owner' ? $user['uid'] : null,
                    ]);
                } else {
                    // Update existing flat
                    $updateData = [
                        'user_role'          => $userRole,
                        'occupancy_status'   => $occupancyStatus,
                        'document_url'       => $documentUrl,
                        'verification_status'=> 'pending',
                        'flat_type'          => $flatType,
                    ];
                    
                    if ($userRole === 'owner') {
                        $updateData['owner_id'] = $user['uid'];
                        if ($occupancyStatus === 'residing') {
                            $updateData['is_occupied'] = 1;
                        }
                    } else {
                        $updateData['tenant_id'] = $user['uid'];
                        $updateData['is_occupied'] = 1;
                    }
                    
                    $updated = $this->update('flats', $updateData, 'id = :id', ['id' => $flat['id']]);
                    if ($updated === 0) {
                        throw new Exception("Failed to update flat");
                    }
                    $flatId = $flat['id'];
                }
                
                // Commit transaction
                $this->db->commit();
                
                Response::success("Home added successfully", [
                    'flat_id'              => $flatId,
                    'flat_type'            => $flatType,
                    'document_url'         => $documentUrl,
                    'verification_status'  => 'pending',
                    'is_new_flat'          => $isNewFlat
                ], 201);
                
            } catch (Exception $e) {
                // Rollback transaction
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Add home error: " . $e->getMessage());
            Response::error("Failed to add home: " . $e->getMessage(), 500);
        }
    }
    
    public function getBuildingsBySociety($societyId) {
        try {
            // Allow unauthenticated access for building lookup during registration
            // But still authenticate if token is provided
            $user = null;
            $headers = apache_request_headers();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
            
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                try {
                    $user = $this->auth->validateToken($token);
                } catch (Exception $e) {
                    // Token invalid, continue without user
                    $user = null;
                }
            }
            
            // Validate society exists
            $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
            $stmt->execute([$societyId]);
            if (!$stmt->fetch()) {
                Response::notFound("Society not found");
            }
            
            // Get buildings for society
            $stmt = $this->db->prepare("
                SELECT id, name, total_floors, description
                FROM buildings 
                WHERE society_id = ?
                ORDER BY name
            ");
            $stmt->execute([$societyId]);
            $buildings = $stmt->fetchAll();
            
            Response::success("Buildings retrieved successfully", $buildings);
            
        } catch(Exception $e) {
            error_log("Get buildings error: " . $e->getMessage());
            Response::error("Failed to retrieve buildings: " . $e->getMessage(), 500);
        }
    }
    
    public function getFlatsByBuilding($buildingId) {
        try {
            // Allow unauthenticated access for flat lookup during registration
            // But still authenticate if token is provided
            $user = null;
            $headers = apache_request_headers();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
            
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                try {
                    $user = $this->auth->validateToken($token);
                } catch (Exception $e) {
                    // Token invalid, continue without user
                    $user = null;
                }
            }
            
            // Validate building exists and get society_id
            $stmt = $this->db->prepare("
                SELECT b.id, b.name, b.society_id, s.name as society_name
                FROM buildings b
                JOIN societies s ON b.society_id = s.id
                WHERE b.id = ?
            ");
            $stmt->execute([$buildingId]);
            $building = $stmt->fetch();
            
            if (!$building) {
                Response::notFound("Building not found");
            }
            
            // Get flats for building that are not occupied
            $stmt = $this->db->prepare("
                SELECT id, flat_number, flat_type, floor_number, area_sqft
                FROM flats 
                WHERE building_id = ? AND (is_occupied = 0 OR is_occupied IS NULL)
                ORDER BY floor_number, flat_number
            ");
            $stmt->execute([$buildingId]);
            $flats = $stmt->fetchAll();
            
            Response::success("Flats retrieved successfully", [
                'building' => [
                    'id' => $building['id'],
                    'name' => $building['name'],
                    'society_id' => $building['society_id'],
                    'society_name' => $building['society_name']
                ],
                'flats' => $flats
            ]);
            
        } catch(Exception $e) {
            error_log("Get flats error: " . $e->getMessage());
            Response::error("Failed to retrieve flats: " . $e->getMessage(), 500);
        }
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
}