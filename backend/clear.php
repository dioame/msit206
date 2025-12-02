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
    $logEntry = "[$timestamp] CLEAR: $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

logMessage("=== Clear Data API started ===");

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Clear all data from database tables
function clearAllData($conn) {
    $errors = [];
    $results = [];
    
    try {
        logMessage("Clear: Starting data clearing process");
        
        // Disable foreign key checks temporarily
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("SET AUTOCOMMIT = 0");
        
        // Tables to clear (in order to respect foreign keys)
        $tables = [
            'evacuation',
            'assistance',
            'affected',
            'municipalities',
            'provinces',
            'incidents',
            'logs'  // Also clear logs
        ];
        
        foreach ($tables as $table) {
            try {
                // Check if table exists
                $checkResult = $conn->query("SHOW TABLES LIKE '{$table}'");
                if ($checkResult && $checkResult->num_rows > 0) {
                    $truncateQuery = "TRUNCATE TABLE `{$table}`";
                    if ($conn->query($truncateQuery)) {
                        $results[$table] = 'cleared';
                        logMessage("Clear: Table {$table} cleared successfully");
                    } else {
                        $error = "Error clearing table {$table}: " . $conn->error;
                        $errors[] = $error;
                        logMessage("Clear: Error", ['table' => $table, 'error' => $conn->error]);
                    }
                } else {
                    logMessage("Clear: Table {$table} does not exist, skipping");
                }
            } catch (Exception $e) {
                $error = "Exception clearing table {$table}: " . $e->getMessage();
                $errors[] = $error;
                logMessage("Clear: Exception", ['table' => $table, 'error' => $e->getMessage()]);
            }
        }
        
        // Re-enable foreign key checks
        $conn->query("COMMIT");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->query("SET AUTOCOMMIT = 1");
        
        logMessage("Clear: Data clearing completed", [
            'success' => count($errors) === 0,
            'tables_cleared' => count($results),
            'errors_count' => count($errors)
        ]);
        
        return [
            'success' => count($errors) === 0,
            'results' => $results,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        $conn->query("ROLLBACK");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->query("SET AUTOCOMMIT = 1");
        logMessage("Clear: Fatal error", ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => array_merge($errors, [$e->getMessage()])
        ];
    }
}

// Main processing
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logMessage("Invalid method", ['method' => $_SERVER['REQUEST_METHOD']]);
        ob_end_clean();
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Only POST requests are accepted.'
        ]);
        exit();
    }

    logMessage("Clear: Processing clear request");
    $conn = getDBConnection();
    logMessage("Clear: Database connected");
    
    $clearResult = clearAllData($conn);
    $conn->close();
    logMessage("Clear: Database connection closed");
    
    ob_end_clean();
    
    if ($clearResult['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'All database tables cleared successfully!',
            'results' => $clearResult['results']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error clearing database: ' . implode(', ', $clearResult['errors']),
            'errors' => $clearResult['errors']
        ]);
    }
    
    logMessage("=== Clear Data completed ===");
    
} catch (Exception $e) {
    logMessage("Clear: Exception caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

