<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel to CSV Converter - Disaster Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #fff;
        }

        .navbar {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            margin: 0;
            font-size: 20px;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .nav-links a.active {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 5px 0;
        }

        .content {
            padding: 10px 0;
        }

        .upload-section {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .file-input-wrapper {
            margin: 10px 0;
        }

        .file-input {
            margin: 10px 0;
        }

        .file-name {
            margin: 10px 0;
            color: #666;
        }

        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover:not(:disabled) {
            background: #0056b3;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #28a745;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
        }

        .btn-info:hover:not(:disabled) {
            background: #138496;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-group .btn {
            flex: 1;
            margin-top: 0;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .loading.active {
            display: block;
        }

        .results {
            display: none;
            margin-top: 20px;
        }

        .results.active {
            display: block;
        }

        .result-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
        }

        .result-card.success {
            border-left: 4px solid #28a745;
        }

        .result-card.error {
            border-left: 4px solid #dc3545;
        }

        .result-card.info {
            border-left: 4px solid #17a2b8;
        }

        .result-card h3 {
            margin: 0 0 10px 0;
        }

        .result-card p {
            margin: 5px 0;
        }

        .result-card ul {
            margin: 10px 0 10px 20px;
        }

        .download-link {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
        }

        .download-link:hover {
            background: #218838;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid;
        }

        .alert.active {
            display: block;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .info-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            margin: 0 0 10px 0;
        }

        .info-box ul {
            margin: 10px 0 10px 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2>Disaster Data Management System</h2>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Import Excel</a></li>
                <li><a href="crud_interface.php">Manage Data</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="mining.php">Data Mining</a></li>
                <li><a href="logs.php">Activity Logs</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <div class="header">
            <h1>Excel to CSV Converter</h1>
            <p>Convert Disaster Data Excel Sheets to CSV Format</p>
        </div>

        <div class="content">

            <div class="alert" id="alert"></div>

            <div class="upload-section" id="uploadSection">
                <h2>Upload Excel File</h2>
                <div class="file-input-wrapper">
                    <input type="file" id="excelFile" class="file-input" accept=".xlsx,.xls" />
                    <label for="excelFile" class="file-input-label">
                        Choose Excel File
                    </label>
                </div>
                <div class="file-name" id="fileName"></div>
                <button class="btn" id="convertBtn" disabled>Process & Import</button>
            </div>


            <div class="loading" id="loading">
                <p id="loadingText">Processing...</p>
            </div>

            <div class="results" id="results"></div>
        </div>
    </div>

    <script>
        const excelFileInput = document.getElementById('excelFile');
        const fileNameDisplay = document.getElementById('fileName');
        const convertBtn = document.getElementById('convertBtn');
        const uploadSection = document.getElementById('uploadSection');
        const loading = document.getElementById('loading');
        const loadingText = document.getElementById('loadingText');
        const results = document.getElementById('results');
        const alert = document.getElementById('alert');

        // File input change handler
        excelFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = `Selected: ${file.name}`;
                convertBtn.disabled = false;
            } else {
                fileNameDisplay.textContent = '';
                convertBtn.disabled = true;
            }
        });

        // Drag and drop handlers
        uploadSection.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });

        uploadSection.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
        });

        uploadSection.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
                    excelFileInput.files = files;
                    fileNameDisplay.textContent = `Selected: ${file.name}`;
                    convertBtn.disabled = false;
                } else {
                    showAlert('Please select a valid Excel file (.xlsx or .xls)', 'error');
                }
            }
        });

        // Convert button click handler - now does convert, analyze, and import automatically
        convertBtn.addEventListener('click', function() {
            const file = excelFileInput.files[0];
            if (!file) {
                showAlert('Please select a file first', 'error');
                return;
            }

            // Start the automated process
            processExcelFile(file);
        });

        // Main function to process Excel file - all processing done in PHP
        function processExcelFile(file) {
            const formData = new FormData();
            formData.append('excel_file', file);

            loadingText.textContent = 'Processing: Converting Excel, Analyzing CSV, and Importing to Database...';
            loading.classList.add('active');
            results.classList.remove('active');
            convertBtn.disabled = true;
            hideAlert();

            fetch('backend/process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                    });
                }
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'HTTP error! status: ' + response.status);
                    }).catch(() => {
                        throw new Error('HTTP error! status: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                loading.classList.remove('active');
                convertBtn.disabled = false;

                if (data.success) {
                    displayCombinedResults(data);
                    showAlert(data.message || 'All operations completed successfully!', 'success');
                } else {
                    showAlert(data.message || 'Processing failed. Please try again.', 'error');
                    if (data.errors && data.errors.length > 0) {
                        displayErrors(data.errors);
                    }
                    if (data.conversion && data.conversion.errors && data.conversion.errors.length > 0) {
                        displayErrors(data.conversion.errors);
                    }
                    if (data.import && data.import.errors && data.import.errors.length > 0) {
                        displayErrors(data.import.errors);
                    }
                }
            })
            .catch(error => {
                loading.classList.remove('active');
                convertBtn.disabled = false;
                showAlert('An error occurred: ' + error.message, 'error');
                console.error('Error:', error);
            });
        }

        // Display combined results from all operations
        function displayCombinedResults(data) {
            results.innerHTML = '<h2>Processing Complete</h2>';
            
            // Show CSV conversion info
            if (data.conversion && data.conversion.results) {
                data.conversion.results.forEach(result => {
                    const card = document.createElement('div');
                    card.className = 'result-card success';
                    card.innerHTML = `
                        <h3>CSV Conversion - ${result.sheet.toUpperCase()}</h3>
                        <p><strong>File:</strong> ${result.filename}</p>
                        <p><strong>Rows:</strong> ${result.rows}</p>
                        <p><strong>Columns:</strong> ${result.columns}</p>
                    `;
                    results.appendChild(card);
                });
            }

            // Show analysis info
            if (data.analysis) {
                Object.keys(data.analysis).forEach(sheetName => {
                    const analysis = data.analysis[sheetName];
                    const card = document.createElement('div');
                    card.className = 'result-card info';
                    if (analysis.exists) {
                        card.innerHTML = `
                            <h3>CSV Analysis - ${sheetName.toUpperCase()}</h3>
                            <p><strong>File:</strong> ${analysis.filename}</p>
                            <p><strong>Total Rows:</strong> ${analysis.row_count}</p>
                            <p><strong>Columns:</strong> ${analysis.column_count}</p>
                        `;
                    } else {
                        card.innerHTML = `
                            <h3>CSV Analysis - ${sheetName.toUpperCase()}</h3>
                            <p><strong>Status:</strong> File not found</p>
                        `;
                    }
                    results.appendChild(card);
                });
            }

            // Show import results
            if (data.import && data.import.results) {
                // Affected data
                if (data.import.results.affected) {
                    const card = document.createElement('div');
                    card.className = 'result-card success';
                    card.innerHTML = `
                        <h3>Database Import - Affected Data</h3>
                        <p><strong>Incidents:</strong> ${data.import.results.affected.incidents || 0}</p>
                        <p><strong>Provinces:</strong> ${data.import.results.affected.provinces || 0}</p>
                        <p><strong>Municipalities:</strong> ${data.import.results.affected.municipalities || 0}</p>
                        <p><strong>Affected Records:</strong> ${data.import.results.affected.affected || 0}</p>
                    `;
                    results.appendChild(card);
                }

                // Assistance data
                if (data.import.results.assistance) {
                    const card = document.createElement('div');
                    card.className = 'result-card success';
                    card.innerHTML = `
                        <h3>Database Import - Assistance Data</h3>
                        <p><strong>Records Imported:</strong> ${data.import.results.assistance.records || 0}</p>
                    `;
                    results.appendChild(card);
                }

                // Evacuation data
                if (data.import.results.evacuation) {
                    const card = document.createElement('div');
                    card.className = 'result-card success';
                    card.innerHTML = `
                        <h3>Database Import - Evacuation Data</h3>
                        <p><strong>Records Imported:</strong> ${data.import.results.evacuation.records || 0}</p>
                    `;
                    results.appendChild(card);
                }
            }

            results.classList.add('active');
        }

        function displayResults(data) {
            results.innerHTML = '<h2>Conversion Results</h2>';
            
            if (data.results && data.results.length > 0) {
                data.results.forEach(result => {
                    const card = document.createElement('div');
                    card.className = 'result-card success';
                    card.innerHTML = `
                        <h3>${result.sheet.toUpperCase()} Sheet</h3>
                        <p><strong>File:</strong> ${result.filename}</p>
                        <p><strong>Rows:</strong> ${result.rows}</p>
                        <p><strong>Columns:</strong> ${result.columns}</p>
                        <a href="csv_output/${result.filename}" class="download-link" download>Download CSV</a>
                    `;
                    results.appendChild(card);
                });
            }

            if (data.errors && data.errors.length > 0) {
                data.errors.forEach(error => {
                    const card = document.createElement('div');
                    card.className = 'result-card error';
                    card.innerHTML = `
                        <h3>Error</h3>
                        <p>${error}</p>
                    `;
                    results.appendChild(card);
                });
            }

            results.classList.add('active');
        }

        function displayErrors(errors) {
            errors.forEach(error => {
                const card = document.createElement('div');
                card.className = 'result-card error';
                card.innerHTML = `
                    <h3>Error</h3>
                    <p>${error}</p>
                `;
                results.appendChild(card);
            });
            results.classList.add('active');
        }

        function showAlert(message, type) {
            alert.textContent = message;
            alert.className = `alert ${type} active`;
            setTimeout(() => {
                hideAlert();
            }, 5000);
        }

        function hideAlert() {
            alert.classList.remove('active');
        }


    </script>
</body>
</html>

