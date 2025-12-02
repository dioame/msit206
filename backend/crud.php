<?php
// Start output buffering
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$logFile = __DIR__ . '/../process.log';
ini_set('error_log', $logFile);

// Logging function
function logMessage($message, $data = null) {
    $logFile = __DIR__ . '/../process.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] CRUD: $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

logMessage("=== CRUD API started ===");

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

// Load config
require_once __DIR__ . '/../config.php';

// Database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        throw new Exception("Database connection error: " . $e->getMessage());
    }
}

// Helper function to get or create province
function getOrCreateProvince($conn, $provinceId, $provinceName) {
    if (empty($provinceId) || empty($provinceName)) {
        return null;
    }
    
    // Check if exists
    $stmt = $conn->prepare("SELECT provinceid FROM provinces WHERE provinceid = ?");
    $stmt->bind_param("i", $provinceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create it
        $stmt->close();
        $insertStmt = $conn->prepare("INSERT INTO provinces (provinceid, province_name) VALUES (?, ?)");
        $insertStmt->bind_param("is", $provinceId, $provinceName);
        if ($insertStmt->execute()) {
            $insertStmt->close();
            return $provinceId;
        }
        $insertStmt->close();
        return null;
    }
    
    $stmt->close();
    return $provinceId;
}

// Helper function to get or create municipality
function getOrCreateMunicipality($conn, $municipalityId, $municipalityName, $provinceId) {
    if (empty($municipalityId) || empty($municipalityName) || empty($provinceId)) {
        return null;
    }
    
    // Check if exists
    $stmt = $conn->prepare("SELECT municipality_id FROM municipalities WHERE municipality_id = ?");
    $stmt->bind_param("i", $municipalityId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create it (province should already exist)
        $stmt->close();
        $insertStmt = $conn->prepare("INSERT INTO municipalities (municipality_id, municipality_name, provinceid) VALUES (?, ?, ?)");
        $insertStmt->bind_param("isi", $municipalityId, $municipalityName, $provinceId);
        if ($insertStmt->execute()) {
            $insertStmt->close();
            return $municipalityId;
        }
        $insertStmt->close();
        return null;
    }
    
    $stmt->close();
    return $municipalityId;
}

// Helper function to get or create incident
function getOrCreateIncident($conn, $incidentId, $latestDisasterTitleId, $disasterName, $disasterDate) {
    if (empty($incidentId) || empty($disasterName) || empty($disasterDate)) {
        return null;
    }
    
    // Check if exists
    $stmt = $conn->prepare("SELECT incident_id FROM incidents WHERE incident_id = ?");
    $stmt->bind_param("i", $incidentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create it
        $stmt->close();
        $insertStmt = $conn->prepare("INSERT INTO incidents (incident_id, latest_disaster_title_id, disaster_name, disaster_date) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("iiss", $incidentId, $latestDisasterTitleId, $disasterName, $disasterDate);
        if ($insertStmt->execute()) {
            $insertStmt->close();
            return $incidentId;
        }
        $insertStmt->close();
        return null;
    }
    
    $stmt->close();
    return $incidentId;
}

// Helper function to get or create affected
function getOrCreateAffected($conn, $affectedId, $incidentId, $municipalityId, $famNo = null, $personNo = null, $brgyAffected = null) {
    if (empty($affectedId) || empty($incidentId) || empty($municipalityId)) {
        return null;
    }
    
    // Check if exists
    $stmt = $conn->prepare("SELECT affected_id FROM affected WHERE affected_id = ?");
    $stmt->bind_param("i", $affectedId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create it
        $stmt->close();
        $insertStmt = $conn->prepare("INSERT INTO affected (affected_id, incident_id, municipality_id, fam_no, person_no, brgy_affected) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("iiiiii", $affectedId, $incidentId, $municipalityId, $famNo, $personNo, $brgyAffected);
        if ($insertStmt->execute()) {
            $insertStmt->close();
            return $affectedId;
        }
        $insertStmt->close();
        return null;
    }
    
    $stmt->close();
    return $affectedId;
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];

// Get table name and ID from query parameters or path
$tableName = $_GET['table'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// If not in query params, try to parse from path
if (!$tableName) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Get table name from path (e.g., /backend/crud.php/provinces)
    $tableName = end($pathParts);
    
    // Get ID if present (e.g., /backend/crud.php/provinces/1)
    if (count($pathParts) >= 2 && is_numeric($pathParts[count($pathParts) - 1])) {
        $id = intval($pathParts[count($pathParts) - 1]);
        $tableName = $pathParts[count($pathParts) - 2];
    }
}

// Valid tables (CRUD operations only - no views)
$validTables = [
    'provinces', 'municipalities', 'incidents', 'affected', 'assistance', 'evacuation', 'logs'
];

// Read-only tables (logs can only be queried, not modified)
$readOnlyTables = ['logs'];

if (!in_array($tableName, $validTables)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid table name. Valid tables: ' . implode(', ', $validTables) . '. For dashboard views, use backend/dashboard.php'
    ]);
    exit();
}

// Prevent modifications to read-only tables
if (in_array($tableName, $readOnlyTables) && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Logs table is read-only. Only GET operations are allowed.'
    ]);
    exit();
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    $input = $_POST;
}

try {
    $conn = getDBConnection();
    
    switch ($method) {
        case 'GET':
            // List all or get one
            if ($id !== null) {
                // Get single record
                $stmt = $conn->prepare("SELECT * FROM `$tableName` WHERE " . getPrimaryKey($tableName) . " = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    ob_end_clean();
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Record not found'
                    ]);
                } else {
                    ob_end_clean();
                    echo json_encode([
                        'success' => true,
                        'data' => $result->fetch_assoc()
                    ]);
                }
                $stmt->close();
            } else {
                // List all with pagination
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $offset = ($page - 1) * $limit;
                
                $stmt = $conn->prepare("SELECT * FROM `$tableName` LIMIT ? OFFSET ?");
                $stmt->bind_param("ii", $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                
                // Get total count
                $countResult = $conn->query("SELECT COUNT(*) as total FROM `$tableName`");
                $total = $countResult->fetch_assoc()['total'];
                
                $stmt->close();
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;
            
        case 'POST':
            // Create new record
            if (empty($input)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No data provided'
                ]);
                break;
            }
            
            $result = createRecord($conn, $tableName, $input);
            ob_end_clean();
            if ($result['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            echo json_encode($result);
            break;
            
        case 'PUT':
        case 'PATCH':
            // Update record
            if ($id === null) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID required for update'
                ]);
                break;
            }
            
            if (empty($input)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No data provided'
                ]);
                break;
            }
            
            $result = updateRecord($conn, $tableName, $id, $input);
            ob_end_clean();
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Delete record
            if ($id === null) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID required for delete'
                ]);
                break;
            }
            
            $result = deleteRecord($conn, $tableName, $id);
            ob_end_clean();
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            echo json_encode($result);
            break;
            
        default:
            ob_end_clean();
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper functions
function getPrimaryKey($tableName) {
    $keys = [
        'provinces' => 'provinceid',
        'municipalities' => 'municipality_id',
        'incidents' => 'incident_id',
        'affected' => 'affected_id',
        'assistance' => 'id',
        'evacuation' => 'evacuation_id',
        'logs' => 'log_id'
    ];
    return $keys[$tableName] ?? 'id';
}

function createRecord($conn, $tableName, $data) {
    try {
        $conn->begin_transaction();
        
        // Handle foreign keys automatically
        if ($tableName === 'municipalities') {
            // Ensure province exists
            if (isset($data['provinceid']) && isset($data['province_name'])) {
                getOrCreateProvince($conn, $data['provinceid'], $data['province_name']);
            }
        } elseif ($tableName === 'affected') {
            // Ensure incident and municipality exist
            if (isset($data['incident_id']) && isset($data['latest_disaster_title_id']) && isset($data['disaster_name']) && isset($data['disaster_date'])) {
                getOrCreateIncident($conn, $data['incident_id'], $data['latest_disaster_title_id'], $data['disaster_name'], $data['disaster_date']);
            }
            if (isset($data['municipality_id']) && isset($data['municipality_name']) && isset($data['provinceid']) && isset($data['province_name'])) {
                getOrCreateProvince($conn, $data['provinceid'], $data['province_name']);
                getOrCreateMunicipality($conn, $data['municipality_id'], $data['municipality_name'], $data['provinceid']);
            }
        } elseif ($tableName === 'assistance') {
            // Ensure affected exists
            if (isset($data['affected_id']) && isset($data['incident_id']) && isset($data['municipality_id'])) {
                // If full affected data provided, create it
                if (isset($data['incident_data'])) {
                    $inc = $data['incident_data'];
                    getOrCreateIncident($conn, $inc['incident_id'], $inc['latest_disaster_title_id'], $inc['disaster_name'], $inc['disaster_date']);
                }
                if (isset($data['municipality_data'])) {
                    $mun = $data['municipality_data'];
                    if (isset($mun['province_data'])) {
                        getOrCreateProvince($conn, $mun['province_data']['provinceid'], $mun['province_data']['province_name']);
                    }
                    getOrCreateMunicipality($conn, $mun['municipality_id'], $mun['municipality_name'], $mun['provinceid']);
                }
                getOrCreateAffected($conn, $data['affected_id'], $data['incident_id'], $data['municipality_id'], 
                    $data['fam_no'] ?? null, $data['person_no'] ?? null, $data['brgy_affected'] ?? null);
            }
        } elseif ($tableName === 'evacuation') {
            // Ensure affected exists
            if (isset($data['affected_id']) && isset($data['incident_id']) && isset($data['municipality_id'])) {
                // If full affected data provided, create it
                if (isset($data['incident_data'])) {
                    $inc = $data['incident_data'];
                    getOrCreateIncident($conn, $inc['incident_id'], $inc['latest_disaster_title_id'], $inc['disaster_name'], $inc['disaster_date']);
                }
                if (isset($data['municipality_data'])) {
                    $mun = $data['municipality_data'];
                    if (isset($mun['province_data'])) {
                        getOrCreateProvince($conn, $mun['province_data']['provinceid'], $mun['province_data']['province_name']);
                    }
                    getOrCreateMunicipality($conn, $mun['municipality_id'], $mun['municipality_name'], $mun['provinceid']);
                }
                getOrCreateAffected($conn, $data['affected_id'], $data['incident_id'], $data['municipality_id'], 
                    $data['fam_no'] ?? null, $data['person_no'] ?? null, $data['brgy_affected'] ?? null);
            }
        }
        
        // Build INSERT query
        $columns = [];
        $values = [];
        $types = '';
        $params = [];
        
        foreach ($data as $key => $value) {
            // Skip nested data structures
            if (is_array($value) && !is_numeric($value)) {
                continue;
            }
            
            // Skip auto-increment fields for assistance and evacuation
            if (($tableName === 'assistance' && $key === 'id') || 
                ($tableName === 'evacuation' && $key === 'evacuation_id')) {
                continue;
            }
            
            $columns[] = "`$key`";
            
            if (is_int($value)) {
                $types .= 'i';
                $values[] = '?';
                $params[] = $value;
            } elseif (is_float($value)) {
                $types .= 'd';
                $values[] = '?';
                $params[] = $value;
            } else {
                $types .= 's';
                $values[] = '?';
                $params[] = $value;
            }
        }
        
        if (empty($columns)) {
            throw new Exception('No valid columns to insert');
        }
        
        $sql = "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $insertId = $conn->insert_id;
            $conn->commit();
            $stmt->close();
            
            // Get the created record
            $primaryKey = getPrimaryKey($tableName);
            $getStmt = $conn->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` = ?");
            if ($tableName === 'assistance' || $tableName === 'evacuation') {
                $getStmt->bind_param("i", $insertId);
            } else {
                $idValue = $data[getPrimaryKey($tableName)] ?? $insertId;
                $getStmt->bind_param("i", $idValue);
            }
            $getStmt->execute();
            $result = $getStmt->get_result();
            $record = $result->fetch_assoc();
            $getStmt->close();
            
            return [
                'success' => true,
                'message' => 'Record created successfully',
                'data' => $record
            ];
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        logMessage("Create error: " . $e->getMessage(), ['table' => $tableName, 'data' => $data]);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function updateRecord($conn, $tableName, $id, $data) {
    try {
        $primaryKey = getPrimaryKey($tableName);
        
        // Check if record exists
        $checkStmt = $conn->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $checkStmt->close();
            return [
                'success' => false,
                'message' => 'Record not found'
            ];
        }
        $checkStmt->close();
        
        // Handle foreign keys if needed (similar to create)
        if ($tableName === 'municipalities' && isset($data['provinceid']) && isset($data['province_name'])) {
            getOrCreateProvince($conn, $data['provinceid'], $data['province_name']);
        }
        
        // Build UPDATE query
        $setParts = [];
        $types = '';
        $params = [];
        
        foreach ($data as $key => $value) {
            // Skip nested data and primary key
            if ($key === $primaryKey || (is_array($value) && !is_numeric($value))) {
                continue;
            }
            
            $setParts[] = "`$key` = ?";
            
            if (is_int($value)) {
                $types .= 'i';
                $params[] = $value;
            } elseif (is_float($value)) {
                $types .= 'd';
                $params[] = $value;
            } else {
                $types .= 's';
                $params[] = $value;
            }
        }
        
        if (empty($setParts)) {
            return [
                'success' => false,
                'message' => 'No fields to update'
            ];
        }
        
        $types .= 'i'; // for the WHERE clause
        $params[] = $id;
        
        $sql = "UPDATE `$tableName` SET " . implode(', ', $setParts) . " WHERE `$primaryKey` = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Get updated record
            $getStmt = $conn->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` = ?");
            $getStmt->bind_param("i", $id);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $record = $result->fetch_assoc();
            $getStmt->close();
            
            return [
                'success' => true,
                'message' => 'Record updated successfully',
                'data' => $record
            ];
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        logMessage("Update error: " . $e->getMessage(), ['table' => $tableName, 'id' => $id, 'data' => $data]);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function deleteRecord($conn, $tableName, $id) {
    try {
        $primaryKey = getPrimaryKey($tableName);
        
        // Check if record exists
        $checkStmt = $conn->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $checkStmt->close();
            return [
                'success' => false,
                'message' => 'Record not found'
            ];
        }
        $checkStmt->close();
        
        // Delete record
        $stmt = $conn->prepare("DELETE FROM `$tableName` WHERE `$primaryKey` = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Record deleted successfully'
            ];
        } else {
            throw new Exception("Delete failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        logMessage("Delete error: " . $e->getMessage(), ['table' => $tableName, 'id' => $id]);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>

