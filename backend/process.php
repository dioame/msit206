<?php
// Start output buffering to catch any accidental output
ob_start();

// Suppress error display and catch errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log errors to a file for debugging
$logFile = __DIR__ . '/../process.log';
ini_set('error_log', $logFile);

// Logging function
function logMessage($message, $data = null) {
    $logFile = __DIR__ . '/../process.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

logMessage("=== Process.php started ===");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    logMessage("OPTIONS request received");
    ob_end_clean();
    http_response_code(200);
    exit();
}

// Error handler to return JSON (disabled for now, using try-catch instead)
function handleError($errno, $errstr, $errfile, $errline) {
    // Let PHP handle errors normally, we'll catch exceptions
    return false;
}

// Exception handler
function handleException($exception) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $exception->getMessage() . ' in ' . basename($exception->getFile()) . ':' . $exception->getLine()
    ]);
    exit();
}

// Load config first
logMessage("Loading config.php");
try {
    require_once __DIR__ . '/../config.php';
    logMessage("Config loaded successfully");
} catch (Exception $e) {
    logMessage("Config load error", ['error' => $e->getMessage()]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Config error: ' . $e->getMessage()
    ]);
    exit();
} catch (Throwable $e) {
    logMessage("Config fatal error", ['error' => $e->getMessage()]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Config fatal error: ' . $e->getMessage()
    ]);
    exit();
}

// Check if PhpSpreadsheet is available
logMessage("Checking PhpSpreadsheet");
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    logMessage("PhpSpreadsheet not loaded, checking autoload", ['path' => $autoloadPath]);
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        logMessage("PhpSpreadsheet autoload loaded");
    } else {
        logMessage("PhpSpreadsheet autoload not found");
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'PhpSpreadsheet library not found. Please run: composer require phpoffice/phpspreadsheet'
        ]);
        exit();
    }
} else {
    logMessage("PhpSpreadsheet already loaded");
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

// Set exception handler only
set_exception_handler('handleException');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Only output if headers haven't been sent
        if (!headers_sent()) {
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Fatal error: ' . $error['message'] . ' in ' . basename($error['file']) . ':' . $error['line']
            ]);
        }
    }
});

// Database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        throw new Exception("Database connection error: " . $e->getMessage());
    }
}

// Convert Excel to CSV
function convertExcelToCSV($excelPath, $outputDir) {
    $results = [];
    $errors = [];
    
    try {
        $spreadsheet = IOFactory::load($excelPath);
        $sheetsToConvert = ['affected', 'assistance', 'evacuation'];
        
        foreach ($sheetsToConvert as $sheetName) {
            try {
                if (!$spreadsheet->sheetNameExists($sheetName)) {
                    $errors[] = "Sheet '$sheetName' not found in Excel file";
                    continue;
                }
                
                $spreadsheet->setActiveSheetIndexByName($sheetName);
                $sheet = $spreadsheet->getActiveSheet();
                
                $csvWriter = new Csv($spreadsheet);
                $csvWriter->setSheetIndex($spreadsheet->getIndex($spreadsheet->getSheetByName($sheetName)));
                
                $csvFilename = $sheetName . '.csv';
                $csvPath = $outputDir . $csvFilename;
                
                $csvWriter->save($csvPath);
                
                $results[] = [
                    'sheet' => $sheetName,
                    'filename' => $csvFilename,
                    'rows' => $sheet->getHighestRow(),
                    'columns' => $sheet->getHighestColumn()
                ];
                
            } catch (Exception $e) {
                $errors[] = "Error processing sheet '$sheetName': " . $e->getMessage();
            }
        }
        
        return [
            'success' => count($results) > 0,
            'results' => $results,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error loading Excel file: ' . $e->getMessage(),
            'errors' => $errors
        ];
    }
}

// Analyze CSV files
function analyzeCSVFiles($csvDir) {
    $results = [];
    $files = ['affected' => 'affected.csv', 'assistance' => 'assistance.csv', 'evacuation' => 'evacuation.csv'];
    
    foreach ($files as $name => $filename) {
        $filePath = $csvDir . $filename;
        if (!file_exists($filePath)) {
            $results[$name] = [
                'exists' => false,
                'error' => 'File not found'
            ];
            continue;
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $results[$name] = [
                'exists' => true,
                'error' => 'Cannot open file'
            ];
            continue;
        }
        
        $header = fgetcsv($handle);
        $rowCount = 0;
        
        while (($row = fgetcsv($handle)) !== false && $rowCount < 1000) {
            $rowCount++;
        }
        
        fclose($handle);
        
        $filteredHeader = array_filter($header, function($col) {
            return !empty(trim($col));
        });
        
        $results[$name] = [
            'exists' => true,
            'filename' => $filename,
            'columns' => array_values($filteredHeader),
            'column_count' => count($filteredHeader),
            'row_count' => $rowCount
        ];
    }
    
    return $results;
}

// Create normalized database schema
function createDatabaseSchema($conn) {
    $queries = [];
    $errors = [];
    
    try {
        $queries[] = "CREATE TABLE IF NOT EXISTS `incidents` (
            `incident_id` INT(11) NOT NULL,
            `latest_disaster_title_id` INT(11) NOT NULL,
            `disaster_name` TEXT NOT NULL,
            `disaster_date` DATE NOT NULL,
            PRIMARY KEY (`incident_id`),
            INDEX `idx_disaster_title` (`latest_disaster_title_id`),
            INDEX `idx_disaster_date` (`disaster_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $queries[] = "CREATE TABLE IF NOT EXISTS `provinces` (
            `provinceid` INT(11) NOT NULL,
            `province_name` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`provinceid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $queries[] = "CREATE TABLE IF NOT EXISTS `municipalities` (
            `municipality_id` INT(11) NOT NULL,
            `municipality_name` VARCHAR(255) NOT NULL,
            `provinceid` INT(11) NOT NULL,
            PRIMARY KEY (`municipality_id`),
            INDEX `idx_province` (`provinceid`),
            FOREIGN KEY (`provinceid`) REFERENCES `provinces`(`provinceid`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $queries[] = "CREATE TABLE IF NOT EXISTS `affected` (
            `affected_id` INT(11) NOT NULL,
            `incident_id` INT(11) NOT NULL,
            `municipality_id` INT(11) NOT NULL,
            `fam_no` INT(11) DEFAULT NULL,
            `person_no` INT(11) DEFAULT NULL,
            `brgy_affected` INT(11) DEFAULT NULL,
            PRIMARY KEY (`affected_id`),
            INDEX `idx_incident` (`incident_id`),
            INDEX `idx_municipality` (`municipality_id`),
            FOREIGN KEY (`incident_id`) REFERENCES `incidents`(`incident_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`municipality_id`) REFERENCES `municipalities`(`municipality_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $queries[] = "CREATE TABLE IF NOT EXISTS `assistance` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `assistance_id` INT(11) NOT NULL,
            `affected_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `fnfi_name` VARCHAR(255) NOT NULL,
            `cost` DECIMAL(10,2) DEFAULT NULL,
            `quantity` INT(11) DEFAULT NULL,
            `total_amount` DECIMAL(10,2) DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_assistance_id` (`assistance_id`),
            INDEX `idx_affected` (`affected_id`),
            FOREIGN KEY (`affected_id`) REFERENCES `affected`(`affected_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $queries[] = "CREATE TABLE IF NOT EXISTS `evacuation` (
            `evacuation_id` INT(11) NOT NULL AUTO_INCREMENT,
            `affected_id` INT(11) NOT NULL,
            `ec_cum` INT(11) DEFAULT NULL,
            `ec_now` INT(11) DEFAULT NULL,
            `family_cum` INT(11) DEFAULT NULL,
            `person_cum` INT(11) DEFAULT NULL,
            `evacuation_name` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`evacuation_id`),
            INDEX `idx_affected` (`affected_id`),
            FOREIGN KEY (`affected_id`) REFERENCES `affected`(`affected_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Create logs table for audit trail
        $queries[] = "CREATE TABLE IF NOT EXISTS `logs` (
            `log_id` INT(11) NOT NULL AUTO_INCREMENT,
            `table_name` VARCHAR(100) NOT NULL,
            `operation_type` ENUM('UPDATE', 'DELETE') NOT NULL,
            `record_id` VARCHAR(100) NOT NULL,
            `old_data` JSON DEFAULT NULL,
            `new_data` JSON DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`log_id`),
            INDEX `idx_table_name` (`table_name`),
            INDEX `idx_operation_type` (`operation_type`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        foreach ($queries as $query) {
            if (!$conn->query($query)) {
                $errors[] = "Error creating table: " . $conn->error;
            }
        }
        
        // Create views if they don't exist
        logMessage("Creating database views");
        $viewResult = createDatabaseViews($conn);
        if (!$viewResult['success']) {
            logMessage("View creation errors", $viewResult['errors']);
            $errors = array_merge($errors, $viewResult['errors']);
        } else {
            logMessage("Database views created successfully");
        }
        
        // Create triggers for logging
        logMessage("Creating database triggers");
        $triggerResult = createDatabaseTriggers($conn);
        if (!$triggerResult['success']) {
            logMessage("Trigger creation errors", $triggerResult['errors']);
            $errors = array_merge($errors, $triggerResult['errors']);
        } else {
            logMessage("Database triggers created successfully");
        }
        
        return [
            'success' => count($errors) === 0,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => [$e->getMessage()]
        ];
    }
}

// Create database views for dashboard
function createDatabaseViews($conn) {
    $queries = [];
    $errors = [];
    
    try {
        // View: Dashboard Statistics Summary
        $queries[] = "CREATE OR REPLACE VIEW `view_dashboard_stats` AS
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
        $queries[] = "CREATE OR REPLACE VIEW `view_affected_details` AS
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
        $queries[] = "CREATE OR REPLACE VIEW `view_province_stats` AS
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
        $queries[] = "CREATE OR REPLACE VIEW `view_municipality_stats` AS
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
        $queries[] = "CREATE OR REPLACE VIEW `view_disaster_type_summary` AS
            SELECT 
                disaster_name,
                COUNT(DISTINCT incident_id) as incident_count,
                MIN(disaster_date) as first_occurrence,
                MAX(disaster_date) as last_occurrence
            FROM incidents
            GROUP BY disaster_name";
        
        // View: Disaster Timeline (by month)
        $queries[] = "CREATE OR REPLACE VIEW `view_disaster_timeline` AS
            SELECT 
                DATE_FORMAT(disaster_date, '%Y-%m') as month_year,
                DATE_FORMAT(disaster_date, '%b %Y') as month_label,
                COUNT(DISTINCT incident_id) as incident_count,
                COUNT(DISTINCT disaster_name) as disaster_type_count
            FROM incidents
            GROUP BY DATE_FORMAT(disaster_date, '%Y-%m'), DATE_FORMAT(disaster_date, '%b %Y')
            ORDER BY month_year";
        
        // View: Assistance Summary by Item
        $queries[] = "CREATE OR REPLACE VIEW `view_assistance_summary` AS
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
        $queries[] = "CREATE OR REPLACE VIEW `view_evacuation_summary` AS
            SELECT 
                evacuation_name,
                COUNT(*) as center_count,
                COALESCE(SUM(ec_cum), 0) as total_ec_cum,
                COALESCE(SUM(ec_now), 0) as total_ec_now,
                COALESCE(SUM(family_cum), 0) as total_family_cum,
                COALESCE(SUM(person_cum), 0) as total_person_cum
            FROM evacuation
            GROUP BY evacuation_name";
        
        foreach ($queries as $index => $query) {
            if (!$conn->query($query)) {
                $errorMsg = "Error creating view #" . ($index + 1) . ": " . $conn->error;
                $errors[] = $errorMsg;
                logMessage($errorMsg);
            } else {
                logMessage("View created successfully", ['index' => $index + 1]);
            }
        }
        
        return [
            'success' => count($errors) === 0,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => [$e->getMessage()]
        ];
    }
}

// Create database triggers for logging UPDATE and DELETE operations
function createDatabaseTriggers($conn) {
    $errors = [];
    
    try {
        // Define tables and their primary keys
        $tables = [
            'provinces' => 'provinceid',
            'municipalities' => 'municipality_id',
            'incidents' => 'incident_id',
            'affected' => 'affected_id',
            'assistance' => 'id',
            'evacuation' => 'evacuation_id'
        ];
        
        foreach ($tables as $tableName => $primaryKey) {
            // Drop existing triggers if they exist
            $dropUpdateTrigger = "DROP TRIGGER IF EXISTS `trg_{$tableName}_update_log`";
            $dropDeleteTrigger = "DROP TRIGGER IF EXISTS `trg_{$tableName}_delete_log`";
            
            $conn->query($dropUpdateTrigger);
            $conn->query($dropDeleteTrigger);
            
            // Get column names for the table to build JSON object
            $columnsResult = $conn->query("SHOW COLUMNS FROM `{$tableName}`");
            $columns = [];
            while ($col = $columnsResult->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            
            // Build JSON object parts for OLD and NEW
            $oldJsonParts = [];
            $newJsonParts = [];
            
            foreach ($columns as $col) {
                $escapedCol = $conn->real_escape_string($col);
                $oldJsonParts[] = "'{$escapedCol}', OLD.`{$col}`";
                $newJsonParts[] = "'{$escapedCol}', NEW.`{$col}`";
            }
            
            $oldJsonStr = implode(', ', $oldJsonParts);
            $newJsonStr = implode(', ', $newJsonParts);
            
            // Create UPDATE trigger
            $updateTriggerSQL = "
            CREATE TRIGGER `trg_{$tableName}_update_log`
            AFTER UPDATE ON `{$tableName}`
            FOR EACH ROW
            BEGIN
                INSERT INTO `logs` (
                    `table_name`,
                    `operation_type`,
                    `record_id`,
                    `old_data`,
                    `new_data`
                ) VALUES (
                    '{$tableName}',
                    'UPDATE',
                    CAST(OLD.`{$primaryKey}` AS CHAR),
                    JSON_OBJECT({$oldJsonStr}),
                    JSON_OBJECT({$newJsonStr})
                );
            END";
            
            if (!$conn->query($updateTriggerSQL)) {
                $errors[] = "Error creating UPDATE trigger for {$tableName}: " . $conn->error;
                logMessage("Trigger creation error", ['table' => $tableName, 'type' => 'UPDATE', 'error' => $conn->error]);
            } else {
                logMessage("UPDATE trigger created", ['table' => $tableName]);
            }
            
            // Create DELETE trigger
            $deleteTriggerSQL = "
            CREATE TRIGGER `trg_{$tableName}_delete_log`
            AFTER DELETE ON `{$tableName}`
            FOR EACH ROW
            BEGIN
                INSERT INTO `logs` (
                    `table_name`,
                    `operation_type`,
                    `record_id`,
                    `old_data`,
                    `new_data`
                ) VALUES (
                    '{$tableName}',
                    'DELETE',
                    CAST(OLD.`{$primaryKey}` AS CHAR),
                    JSON_OBJECT({$oldJsonStr}),
                    NULL
                );
            END";
            
            if (!$conn->query($deleteTriggerSQL)) {
                $errors[] = "Error creating DELETE trigger for {$tableName}: " . $conn->error;
                logMessage("Trigger creation error", ['table' => $tableName, 'type' => 'DELETE', 'error' => $conn->error]);
            } else {
                logMessage("DELETE trigger created", ['table' => $tableName]);
            }
        }
        
        return [
            'success' => count($errors) === 0,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => [$e->getMessage()]
        ];
    }
}

// Parse date from various formats
function parseDate($dateStr) {
    if (empty($dateStr)) return null;
    
    if (is_numeric($dateStr)) {
        $excelEpoch = mktime(0, 0, 0, 1, 1, 1900);
        $timestamp = $excelEpoch + ($dateStr - 2) * 86400;
        return date('Y-m-d', $timestamp);
    }
    
    $formats = ['m/d/Y', 'Y-m-d', 'd/m/Y', 'Y/m/d'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    $timestamp = strtotime($dateStr);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}

// Import data from CSV files
function importCSVData($conn, $csvDir) {
    $results = [];
    $errors = [];
    
    try {
        // Log function is defined in global scope, so it should be accessible
        if (function_exists('logMessage')) {
            logMessage("Import: Starting import process");
        }
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("SET AUTOCOMMIT = 0");
        
        // Clear existing data
        logMessage("Import: Truncating tables");
        $conn->query("TRUNCATE TABLE `evacuation`");
        $conn->query("TRUNCATE TABLE `assistance`");
        $conn->query("TRUNCATE TABLE `affected`");
        $conn->query("TRUNCATE TABLE `municipalities`");
        $conn->query("TRUNCATE TABLE `provinces`");
        $conn->query("TRUNCATE TABLE `incidents`");
        
        // Import from affected.csv
        logMessage("Import: Processing affected.csv");
        $affectedFile = $csvDir . 'affected.csv';
        if (file_exists($affectedFile)) {
            $handle = fopen($affectedFile, 'r');
            if ($handle) {
                logMessage("Import: Opened affected.csv file");
                $header = fgetcsv($handle);
                $incidentStmt = $conn->prepare("INSERT IGNORE INTO `incidents` (incident_id, latest_disaster_title_id, disaster_name, disaster_date) VALUES (?, ?, ?, ?)");
                $provinceStmt = $conn->prepare("INSERT IGNORE INTO `provinces` (provinceid, province_name) VALUES (?, ?)");
                $municipalityStmt = $conn->prepare("INSERT IGNORE INTO `municipalities` (municipality_id, municipality_name, provinceid) VALUES (?, ?, ?)");
                $affectedStmt = $conn->prepare("INSERT INTO `affected` (affected_id, incident_id, municipality_id, fam_no, person_no, brgy_affected) VALUES (?, ?, ?, ?, ?, ?)");
                
                $incidentCount = 0;
                $provinceCount = 0;
                $municipalityCount = 0;
                $affectedCount = 0;
                
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 11 || empty($row[0])) continue;
                    
                    $incidentId = intval($row[1]);
                    $latestDisasterTitleId = intval($row[2]);
                    $disasterName = $conn->real_escape_string($row[3]);
                    $disasterDate = parseDate($row[4]);
                    $provinceName = $conn->real_escape_string($row[5]);
                    $municipalityName = $conn->real_escape_string($row[6]);
                    $provinceId = intval($row[7]);
                    $municipalityId = intval($row[8]);
                    $famNo = !empty($row[9]) && $row[9] !== '' ? intval($row[9]) : 0;
                    $personNo = !empty($row[10]) && $row[10] !== '' ? intval($row[10]) : 0;
                    $brgyAffected = !empty($row[11]) && $row[11] !== '' ? intval($row[11]) : 0;
                    $affectedId = intval($row[0]);
                    
                    $incidentStmt->bind_param("iiss", $incidentId, $latestDisasterTitleId, $disasterName, $disasterDate);
                    if ($incidentStmt->execute()) $incidentCount++;
                    
                    $provinceStmt->bind_param("is", $provinceId, $provinceName);
                    if ($provinceStmt->execute()) $provinceCount++;
                    
                    $municipalityStmt->bind_param("isi", $municipalityId, $municipalityName, $provinceId);
                    if ($municipalityStmt->execute()) $municipalityCount++;
                    
                    $affectedStmt->bind_param("iiiiii", $affectedId, $incidentId, $municipalityId, $famNo, $personNo, $brgyAffected);
                    if ($affectedStmt->execute()) $affectedCount++;
                }
                
                fclose($handle);
                $incidentStmt->close();
                $provinceStmt->close();
                $municipalityStmt->close();
                $affectedStmt->close();
                
                $results['affected'] = [
                    'incidents' => $incidentCount,
                    'provinces' => $provinceCount,
                    'municipalities' => $municipalityCount,
                    'affected' => $affectedCount
                ];
                logMessage("Import: Affected data imported", $results['affected']);
            } else {
                logMessage("Import: Failed to open affected.csv");
                $errors[] = "Failed to open affected.csv";
            }
        } else {
            logMessage("Import: affected.csv not found", ['path' => $affectedFile]);
            $errors[] = "affected.csv not found";
        }
        
        // Import assistance data
        logMessage("Import: Processing assistance.csv");
        $assistanceFile = $csvDir . 'assistance.csv';
        if (file_exists($assistanceFile)) {
            $handle = fopen($assistanceFile, 'r');
            if ($handle) {
                logMessage("Import: Opened assistance.csv file");
                $header = fgetcsv($handle);
                logMessage("Import: Assistance header read", ['header_count' => count($header)]);
                $stmt = $conn->prepare("INSERT IGNORE INTO `assistance` (assistance_id, affected_id, item_id, fnfi_name, cost, quantity, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    logMessage("Import: Failed to prepare assistance statement", ['error' => $conn->error]);
                    $errors[] = "Failed to prepare assistance statement: " . $conn->error;
                } else {
                    $count = 0;
                    $skipped = 0;
                    $failed = 0;
                    $rowNum = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        $rowNum++;
                        
                        // Skip empty rows or rows with insufficient columns
                        if (count($row) < 13) {
                            $skipped++;
                            if ($rowNum <= 5) {
                                logMessage("Import: Skipping assistance row - insufficient columns", ['row_num' => $rowNum, 'col_count' => count($row)]);
                            }
                            continue;
                        }
                        
                        // Skip rows with empty affected_id (first column)
                        if (empty($row[0]) || trim($row[0]) === '') {
                            $skipped++;
                            if ($rowNum <= 5) {
                                logMessage("Import: Skipping assistance row - empty affected_id", ['row_num' => $rowNum]);
                            }
                            continue;
                        }
                        
                        try {
                            $assistanceId = intval($row[7]);
                            $affectedId = intval($row[0]);
                            $itemId = intval($row[8]);
                            $fnfiName = !empty($row[9]) ? $conn->real_escape_string($row[9]) : '';
                            $cost = !empty($row[10]) && $row[10] !== '' ? floatval($row[10]) : 0.0;
                            $quantity = !empty($row[11]) && $row[11] !== '' ? intval($row[11]) : 0;
                            $totalAmount = !empty($row[12]) && $row[12] !== '' ? floatval($row[12]) : 0.0;
                            
                            $stmt->bind_param("iiisdid", $assistanceId, $affectedId, $itemId, $fnfiName, $cost, $quantity, $totalAmount);
                            if ($stmt->execute()) {
                                $count++;
                            } else {
                                $failed++;
                                if ($rowNum <= 5 || $failed <= 5) {
                                    logMessage("Import: Assistance execute failed", ['row_num' => $rowNum, 'error' => $stmt->error, 'affected_id' => $affectedId]);
                                }
                            }
                        } catch (Exception $e) {
                            $failed++;
                            if ($rowNum <= 5 || $failed <= 5) {
                                logMessage("Import: Assistance row error", ['row_num' => $rowNum, 'error' => $e->getMessage()]);
                            }
                        }
                    }
                    
                    logMessage("Import: Assistance import summary", ['imported' => $count, 'skipped' => $skipped, 'failed' => $failed, 'total_rows' => $rowNum]);
                    
                    fclose($handle);
                    $stmt->close();
                    
                    $results['assistance'] = ['records' => $count];
                    logMessage("Import: Assistance data imported", $results['assistance']);
                }
            } else {
                logMessage("Import: Failed to open assistance.csv");
                $errors[] = "Failed to open assistance.csv";
            }
        } else {
            logMessage("Import: assistance.csv not found", ['path' => $assistanceFile]);
            $errors[] = "assistance.csv not found";
        }
        
        // Import evacuation data
        logMessage("Import: Processing evacuation.csv");
        $evacuationFile = $csvDir . 'evacuation.csv';
        if (file_exists($evacuationFile)) {
            $handle = fopen($evacuationFile, 'r');
            if ($handle) {
                $header = fgetcsv($handle);
                $stmt = $conn->prepare("INSERT INTO `evacuation` (affected_id, ec_cum, ec_now, family_cum, person_cum, evacuation_name) VALUES (?, ?, ?, ?, ?, ?)");
                
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 12 || empty($row[0])) continue;
                    
                    $affectedId = intval($row[0]);
                    $ecCum = !empty($row[7]) && $row[7] !== '' ? intval($row[7]) : 0;
                    $ecNow = !empty($row[8]) && $row[8] !== '' ? intval($row[8]) : 0;
                    $familyCum = !empty($row[9]) && $row[9] !== '' ? intval($row[9]) : 0;
                    $personCum = !empty($row[10]) && $row[10] !== '' ? intval($row[10]) : 0;
                    $evacuationName = !empty($row[11]) && $row[11] !== '' ? $conn->real_escape_string($row[11]) : '';
                    
                    $stmt->bind_param("iiiiis", $affectedId, $ecCum, $ecNow, $familyCum, $personCum, $evacuationName);
                    if ($stmt->execute()) $count++;
                }
                
                fclose($handle);
                $stmt->close();
                
                $results['evacuation'] = ['records' => $count];
            }
        }
        
        $conn->query("COMMIT");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->query("SET AUTOCOMMIT = 1");
        
        $success = count($errors) === 0 && count($results) > 0;
        logMessage("Import: Completed", ['success' => $success, 'errors_count' => count($errors), 'results_count' => count($results)]);
        
        if (count($errors) > 0) {
            logMessage("Import: Errors found", $errors);
        }
        
        return [
            'success' => $success,
            'results' => $results,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Import: Exception caught in importCSVData", [
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        $conn->query("ROLLBACK");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->query("SET AUTOCOMMIT = 1");
        return [
            'success' => false,
            'message' => $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine(),
            'errors' => array_merge($errors, [$e->getMessage()])
        ];
    } catch (Throwable $e) {
        if (function_exists('logMessage')) {
            logMessage("Import: Fatal error caught in importCSVData", [
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]);
        }
        $conn->query("ROLLBACK");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->query("SET AUTOCOMMIT = 1");
        return [
            'success' => false,
            'message' => 'Fatal error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine(),
            'errors' => array_merge($errors, [$e->getMessage()])
        ];
    }
}

// Main processing function
try {
    logMessage("Starting main processing", ['method' => $_SERVER['REQUEST_METHOD']]);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logMessage("Invalid method", ['method' => $_SERVER['REQUEST_METHOD']]);
        ob_end_clean();
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed.'
        ]);
        exit();
    }

    logMessage("Checking uploaded file");
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        logMessage("File upload error", [
            'file_set' => isset($_FILES['excel_file']),
            'error_code' => isset($_FILES['excel_file']) ? $_FILES['excel_file']['error'] : 'not_set'
        ]);
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded or upload error occurred.'
        ]);
        exit();
    }

    $file = $_FILES['excel_file'];
    logMessage("File received", ['name' => $file['name'], 'size' => $file['size']]);
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    logMessage("Validating file type", ['extension' => $fileExtension]);
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        logMessage("Invalid file type", ['extension' => $fileExtension]);
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only .xlsx and .xls files are allowed.'
        ]);
        exit();
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds maximum allowed size of 10MB.'
        ]);
        exit();
    }
    
    // Step 1: Upload file
    logMessage("Step 1: Uploading file");
    $timestamp = date('Y-m-d_H-i-s');
    $uploadFilename = 'upload_' . $timestamp . '_' . basename($file['name']);
    $uploadPath = UPLOAD_DIR . $uploadFilename;
    logMessage("Upload path", ['path' => $uploadPath, 'tmp_name' => $file['tmp_name']]);
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        logMessage("File upload failed", ['error' => error_get_last()]);
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload file.'
        ]);
        exit();
    }
    
    // Step 2: Convert Excel to CSV
    logMessage("Step 2: Converting Excel to CSV");
    $convertResult = convertExcelToCSV($uploadPath, CSV_OUTPUT_DIR);
    logMessage("Conversion result", ['success' => $convertResult['success']]);
    if (!$convertResult['success']) {
        logMessage("Conversion failed", ['errors' => $convertResult['errors'] ?? []]);
        ob_end_clean();
        http_response_code(500);
        echo json_encode($convertResult);
        exit();
    }
    
    // Step 3: Analyze CSV files
    logMessage("Step 3: Analyzing CSV files");
    $analyzeResult = analyzeCSVFiles(CSV_OUTPUT_DIR);
    logMessage("Analysis completed");
    
    // Step 4: Import to database
    logMessage("Step 4: Importing to database");
    logMessage("Getting database connection");
    $conn = getDBConnection();
    logMessage("Database connected");
    logMessage("Creating database schema");
    $schemaResult = createDatabaseSchema($conn);
    logMessage("Schema creation result", ['success' => $schemaResult['success']]);
    
    if (!$schemaResult['success']) {
        $conn->close();
        ob_end_clean();
        http_response_code(500);
        echo json_encode($schemaResult);
        exit();
    }
    
    logMessage("Importing CSV data");
    $importResult = importCSVData($conn, CSV_OUTPUT_DIR);
    logMessage("Import completed", ['success' => $importResult['success']]);
    $conn->close();
    logMessage("Database connection closed");
    
    // Clean up uploaded file
    @unlink($uploadPath);
    logMessage("Uploaded file cleaned up");
    
    // Return combined results
    logMessage("Returning results");
    ob_end_clean();
    $response = [
        'success' => $importResult['success'],
        'conversion' => $convertResult,
        'analysis' => $analyzeResult,
        'import' => $importResult,
        'message' => $importResult['success'] ? 'All operations completed successfully!' : 'Import failed.'
    ];
    logMessage("Sending response", ['success' => $response['success']]);
    echo json_encode($response);
    logMessage("=== Process completed successfully ===");
    
} catch (Exception $e) {
    logMessage("Exception caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
    ]);
} catch (Throwable $e) {
    logMessage("Fatal error caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
    ]);
}
?>

