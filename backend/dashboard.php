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
    $logEntry = "[$timestamp] DASHBOARD: $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

logMessage("=== Dashboard API started ===");

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Check if view exists
function viewExists($conn, $viewName) {
    try {
        $escapedName = $conn->real_escape_string($viewName);
        $result = $conn->query("SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = '$escapedName'");
        if ($result) {
            $row = $result->fetch_assoc();
            return intval($row['count']) > 0;
        }
        return false;
    } catch (Exception $e) {
        logMessage("Error checking view existence: " . $e->getMessage());
        return false;
    }
}

// Check if required tables exist
function checkRequiredTables($conn) {
    $requiredTables = ['incidents', 'affected', 'assistance', 'evacuation', 'provinces', 'municipalities'];
    $missing = [];
    
    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$result || $result->num_rows === 0) {
            $missing[] = $table;
        }
    }
    
    return $missing;
}

// Create views if they don't exist (imported from process.php logic)
function ensureViewsExist($conn) {
    $queries = [];
    
    // View: Dashboard Statistics Summary
    $queries['view_dashboard_stats'] = "CREATE OR REPLACE VIEW `view_dashboard_stats` AS
        SELECT 
            (SELECT COUNT(DISTINCT incident_id) FROM incidents) as total_incidents,
            (SELECT COALESCE(SUM(person_no), 0) FROM affected) as total_affected,
            (SELECT COALESCE(SUM(fam_no), 0) FROM affected) as total_families,
            (SELECT COUNT(*) FROM assistance) as total_assistance,
            (SELECT COUNT(*) FROM evacuation) as total_evacuation,
            (SELECT COALESCE(SUM(total_amount), 0) FROM assistance) as total_amount,
            (SELECT COALESCE(SUM(ec_cum), 0) FROM evacuation) as total_ec_cum,
            (SELECT COALESCE(SUM(ec_now), 0) FROM evacuation) as total_ec_now,
            (SELECT COALESCE(SUM(family_cum), 0) FROM evacuation) as total_family_cum,
            (SELECT COALESCE(SUM(person_cum), 0) FROM evacuation) as total_person_cum";
    
    // View: Affected Details with Location Names
    $queries['view_affected_details'] = "CREATE OR REPLACE VIEW `view_affected_details` AS
        SELECT 
            a.affected_id,
            a.incident_id,
            a.municipality_id,
            a.fam_no,
            a.person_no,
            a.brgy_affected,
            i.disaster_name,
            i.disaster_date,
            m.municipality_name,
            m.provinceid,
            p.province_name
        FROM affected a
        LEFT JOIN incidents i ON a.incident_id = i.incident_id
        LEFT JOIN municipalities m ON a.municipality_id = m.municipality_id
        LEFT JOIN provinces p ON m.provinceid = p.provinceid";
    
    // View: Province Statistics
    $queries['view_province_stats'] = "CREATE OR REPLACE VIEW `view_province_stats` AS
        SELECT 
            p.provinceid,
            p.province_name,
            COALESCE(SUM(a.person_no), 0) as total_affected_persons,
            COALESCE(SUM(a.fam_no), 0) as total_affected_families,
            COUNT(DISTINCT a.incident_id) as incident_count,
            COUNT(DISTINCT a.municipality_id) as municipality_count
        FROM provinces p
        LEFT JOIN municipalities m ON p.provinceid = m.provinceid
        LEFT JOIN affected a ON m.municipality_id = a.municipality_id
        GROUP BY p.provinceid, p.province_name";
    
    // View: Municipality Statistics
    $queries['view_municipality_stats'] = "CREATE OR REPLACE VIEW `view_municipality_stats` AS
        SELECT 
            m.municipality_id,
            m.municipality_name,
            m.provinceid,
            p.province_name,
            COALESCE(SUM(a.person_no), 0) as total_affected_persons,
            COALESCE(SUM(a.fam_no), 0) as total_affected_families,
            COUNT(DISTINCT a.incident_id) as incident_count
        FROM municipalities m
        LEFT JOIN provinces p ON m.provinceid = p.provinceid
        LEFT JOIN affected a ON m.municipality_id = a.municipality_id
        GROUP BY m.municipality_id, m.municipality_name, m.provinceid, p.province_name";
    
    // View: Disaster Type Summary
    $queries['view_disaster_type_summary'] = "CREATE OR REPLACE VIEW `view_disaster_type_summary` AS
        SELECT 
            disaster_name,
            COUNT(DISTINCT incident_id) as incident_count,
            MIN(disaster_date) as first_occurrence,
            MAX(disaster_date) as last_occurrence
        FROM incidents
        GROUP BY disaster_name";
    
    // View: Disaster Timeline (by month)
    $queries['view_disaster_timeline'] = "CREATE OR REPLACE VIEW `view_disaster_timeline` AS
        SELECT 
            DATE_FORMAT(disaster_date, '%Y-%m') as month_year,
            DATE_FORMAT(disaster_date, '%b %Y') as month_label,
            COUNT(DISTINCT incident_id) as incident_count,
            COUNT(DISTINCT disaster_name) as disaster_type_count
        FROM incidents
        GROUP BY DATE_FORMAT(disaster_date, '%Y-%m'), DATE_FORMAT(disaster_date, '%b %Y')
        ORDER BY month_year";
    
    // View: Assistance Summary by Item
    $queries['view_assistance_summary'] = "CREATE OR REPLACE VIEW `view_assistance_summary` AS
        SELECT 
            fnfi_name,
            COUNT(*) as record_count,
            COALESCE(SUM(quantity), 0) as total_quantity,
            COALESCE(SUM(cost), 0) as total_cost,
            COALESCE(SUM(total_amount), 0) as total_amount,
            AVG(cost) as avg_cost,
            AVG(quantity) as avg_quantity
        FROM assistance
        GROUP BY fnfi_name";
    
    // View: Evacuation Summary
    $queries['view_evacuation_summary'] = "CREATE OR REPLACE VIEW `view_evacuation_summary` AS
        SELECT 
            evacuation_name,
            COUNT(*) as center_count,
            COALESCE(SUM(ec_cum), 0) as total_ec_cum,
            COALESCE(SUM(ec_now), 0) as total_ec_now,
            COALESCE(SUM(family_cum), 0) as total_family_cum,
            COALESCE(SUM(person_cum), 0) as total_person_cum
        FROM evacuation
        GROUP BY evacuation_name";
    
    $errors = [];
    foreach ($queries as $viewName => $query) {
        if (!viewExists($conn, $viewName)) {
            logMessage("Creating missing view: $viewName");
            if (!$conn->query($query)) {
                $error = "Error creating view $viewName: " . $conn->error;
                $errors[] = $error;
                logMessage($error);
            } else {
                logMessage("View $viewName created successfully");
            }
        }
    }
    
    return [
        'success' => count($errors) === 0,
        'errors' => $errors
    ];
}

// Valid views for dashboard
$validViews = [
    'view_dashboard_stats',
    'view_affected_details',
    'view_province_stats',
    'view_municipality_stats',
    'view_disaster_type_summary',
    'view_disaster_timeline',
    'view_assistance_summary',
    'view_evacuation_summary'
];

// Get view name from query parameters
$viewName = $_GET['view'] ?? null;

// If not in query params, try to parse from path
if (!$viewName) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Get view name from path (e.g., /backend/dashboard.php/view_dashboard_stats)
    $viewName = end($pathParts);
}

if (!in_array($viewName, $validViews)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid view name. Valid views: ' . implode(', ', $validViews)
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Check if required tables exist first
    $missingTables = checkRequiredTables($conn);
    if (!empty($missingTables)) {
        throw new Exception("Required tables are missing: " . implode(', ', $missingTables) . ". Please run the import process first.");
    }
    
    // Ensure views exist before querying
    logMessage("Checking views existence for: $viewName");
    $viewCheck = ensureViewsExist($conn);
    if (!$viewCheck['success']) {
        logMessage("View creation errors", $viewCheck['errors']);
        // Don't throw error yet, try to continue
    }
    
    // Check if the specific view exists
    if (!viewExists($conn, $viewName)) {
        // Try to create just this view
        logMessage("View $viewName does not exist, attempting to create");
        $viewResult = ensureViewsExist($conn);
        if (!viewExists($conn, $viewName)) {
            $errorDetails = !empty($viewCheck['errors']) ? implode('; ', $viewCheck['errors']) : 'Unknown error';
            throw new Exception("View $viewName does not exist and could not be created. Errors: $errorDetails. Please run the import process first.");
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle dashboard stats view (returns single row)
        if ($viewName === 'view_dashboard_stats') {
            $result = $conn->query("SELECT * FROM `$viewName`");
            if (!$result) {
                $errorMsg = "Query failed for $viewName: " . $conn->error;
                logMessage($errorMsg);
                throw new Exception($errorMsg);
            }
            $data = $result->fetch_assoc();
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'data' => $data ? $data : []
            ]);
        } else {
            // Other views can have pagination
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;
            $offset = ($page - 1) * $limit;
            
            // Add ORDER BY for better results
            // Note: Some views may already have ORDER BY in their definition, but MySQL allows overriding
            $orderBy = '';
            if ($viewName === 'view_province_stats' || $viewName === 'view_municipality_stats') {
                $orderBy = ' ORDER BY total_affected_persons DESC';
            } elseif ($viewName === 'view_disaster_timeline') {
                $orderBy = ' ORDER BY month_year ASC';
            } elseif ($viewName === 'view_assistance_summary') {
                $orderBy = ' ORDER BY total_quantity DESC';
            } elseif ($viewName === 'view_disaster_type_summary') {
                $orderBy = ' ORDER BY incident_count DESC';
            }
            
            // Build query
            $sql = "SELECT * FROM `$viewName`" . $orderBy . " LIMIT ? OFFSET ?";
            
            logMessage("Executing query for $viewName", ['sql' => $sql, 'limit' => $limit, 'offset' => $offset]);
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errorMsg = "Prepare failed for $viewName: " . $conn->error;
                logMessage($errorMsg);
                throw new Exception($errorMsg);
            }
            
            $stmt->bind_param("ii", $limit, $offset);
            if (!$stmt->execute()) {
                $errorMsg = "Execute failed for $viewName: " . $stmt->error;
                logMessage($errorMsg);
                $stmt->close();
                throw new Exception($errorMsg);
            }
            
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            // Get total count
            $countResult = $conn->query("SELECT COUNT(*) as total FROM `$viewName`");
            if (!$countResult) {
                $errorMsg = "Count query failed for $viewName: " . $conn->error;
                logMessage($errorMsg);
                $stmt->close();
                throw new Exception($errorMsg);
            }
            $countRow = $countResult->fetch_assoc();
            $total = $countRow ? intval($countRow['total']) : 0;
            
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
    } else {
        ob_end_clean();
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Dashboard views are read-only.'
        ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    logMessage("Error: " . $errorMsg, [
        'view' => $viewName ?? 'unknown',
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $errorMsg,
        'view' => $viewName ?? null
    ]);
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
    logMessage("Fatal Error: " . $errorMsg, [
        'view' => $viewName ?? 'unknown',
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $errorMsg,
        'view' => $viewName ?? null
    ]);
}
?>

