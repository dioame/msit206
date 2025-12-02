<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Disaster Data Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 0;
            margin: 0;
        }

        .navbar {
            background: #2196F3;
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .navbar-content {
            max-width: 1400px;
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .info-note {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-note strong {
            color: #1976D2;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-secondary {
            background: #757575;
            color: white;
        }

        .btn-secondary:hover {
            background: #616161;
        }

        .btn-secondary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .logs-table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .logs-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
            position: sticky;
            top: 0;
        }

        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .logs-table tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-update {
            background: #fff3cd;
            color: #856404;
        }

        .badge-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .json-data {
            max-width: 400px;
            max-height: 150px;
            overflow: auto;
            background: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .pagination-info {
            color: #666;
            font-size: 14px;
        }

        .pagination-buttons {
            display: flex;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .logs-table-container {
                overflow-x: scroll;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2>Disaster Data Management System</h2>
            <ul class="nav-links">
                <li><a href="index.php">Import Excel</a></li>
                <li><a href="crud_interface.php">Manage Data</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="mining.php">Data Mining</a></li>
                <li><a href="logs.php" class="active">Activity Logs</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Activity Logs</h1>
            <p>View all database update and delete operations</p>
        </div>

        <div class="info-note">
            <strong>All UPDATE and DELETE operations on database tables (provinces, municipalities, incidents, affected, assistance, evacuation) are automatically logged by database triggers. These logs are created automatically and cannot be manually modified.</strong>
        </div>

        <div id="loading" class="loading">Loading logs...</div>
        <div id="error-container"></div>

        <div id="logs-content" style="display: none;">
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Timestamp</th>
                            <th>Table</th>
                            <th>Operation</th>
                            <th>Record ID</th>
                            <th>Old Data</th>
                            <th>New Data</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination" style="display: none;">
                <div class="pagination-info" id="pagination-info"></div>
                <div class="pagination-buttons">
                    <button class="btn btn-secondary" id="prev-btn" onclick="changePage(-1)">Previous</button>
                    <button class="btn btn-secondary" id="next-btn" onclick="changePage(1)">Next</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CRUD_API = 'backend/crud.php';
        const LIMIT_PER_PAGE = 100;
        let currentPage = 1;
        let totalPages = 1;
        let allLogs = [];

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function formatJSON(jsonString) {
            try {
                const obj = JSON.parse(jsonString);
                return JSON.stringify(obj, null, 2);
            } catch (e) {
                return jsonString;
            }
        }

        function showError(message) {
            const container = document.getElementById('error-container');
            container.innerHTML = `<div class="error">${message}</div>`;
        }

        async function loadLogs() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('logs-content').style.display = 'none';
            document.getElementById('error-container').innerHTML = '';

            try {
                // Load all logs
                let page = 1;
                let hasMore = true;
                allLogs = [];

                // Fetch all pages of logs
                while (hasMore) {
                    const queryString = `table=logs&page=${page}&limit=500`;
                    const response = await fetch(`${CRUD_API}?${queryString}`);
                    const result = await response.json();

                    if (result.success && result.data && result.data.length > 0) {
                        allLogs = allLogs.concat(result.data);
                        if (result.data.length < 500 || !result.pagination || page >= result.pagination.pages) {
                            hasMore = false;
                        } else {
                            page++;
                        }
                    } else {
                        hasMore = false;
                    }
                }

                // Sort by created_at descending (newest first)
                allLogs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                // Calculate pagination
                const total = allLogs.length;
                totalPages = Math.ceil(total / LIMIT_PER_PAGE);
                if (currentPage > totalPages && totalPages > 0) {
                    currentPage = totalPages;
                }

                displayLogs();
                updatePagination(total);
            } catch (error) {
                showError('Error loading logs: ' + error.message);
                document.getElementById('loading').style.display = 'none';
            }
        }

        function displayLogs() {
            const tbody = document.getElementById('logs-table-body');
            
            if (allLogs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div>No logs found.</div>
                        </td>
                    </tr>
                `;
                document.getElementById('loading').style.display = 'none';
                document.getElementById('logs-content').style.display = 'block';
                return;
            }

            // Apply pagination
            const start = (currentPage - 1) * LIMIT_PER_PAGE;
            const end = start + LIMIT_PER_PAGE;
            const paginatedLogs = allLogs.slice(start, end);

            tbody.innerHTML = paginatedLogs.map(log => {
                const operationClass = log.operation_type === 'UPDATE' ? 'badge-update' : 'badge-delete';
                const oldData = log.old_data ? formatJSON(log.old_data) : 'N/A';
                const newData = log.new_data ? formatJSON(log.new_data) : 'N/A';

                return `
                    <tr>
                        <td>${log.log_id}</td>
                        <td>${formatDate(log.created_at)}</td>
                        <td><strong>${log.table_name}</strong></td>
                        <td><span class="badge ${operationClass}">${log.operation_type}</span></td>
                        <td>${log.record_id}</td>
                        <td><div class="json-data">${oldData}</div></td>
                        <td><div class="json-data">${newData}</div></td>
                    </tr>
                `;
            }).join('');

            document.getElementById('loading').style.display = 'none';
            document.getElementById('logs-content').style.display = 'block';
        }

        function updatePagination(total) {
            const pagination = document.getElementById('pagination');
            const info = document.getElementById('pagination-info');
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');

            if (totalPages <= 1) {
                pagination.style.display = 'none';
                return;
            }

            pagination.style.display = 'flex';
            const start = (currentPage - 1) * LIMIT_PER_PAGE + 1;
            const end = Math.min(currentPage * LIMIT_PER_PAGE, total);
            info.textContent = `Showing ${start}-${end} of ${total} logs (Page ${currentPage} of ${totalPages})`;

            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage >= totalPages;
        }

        function changePage(direction) {
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                displayLogs();
                updatePagination(allLogs.length);
            }
        }

        // Load logs on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadLogs();
        });
    </script>
</body>
</html>

