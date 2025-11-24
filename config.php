<?php
// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'msit206');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('CSV_OUTPUT_DIR', __DIR__ . '/csv_output/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Allowed file types
define('ALLOWED_EXTENSIONS', ['xlsx', 'xls']);

// Create directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if (!file_exists(CSV_OUTPUT_DIR)) {
    mkdir(CSV_OUTPUT_DIR, 0777, true);
}
?>

