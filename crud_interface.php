<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Management - Disaster Data</title>
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
            background: #4CAF50;
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
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 12px 24px;
            background: #f8f8f8;
            border: none;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background: #e8e8e8;
        }
        
        .tab.active {
            background: #4CAF50;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #0b7dda;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #da190b;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e68900;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f8f8;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .pagination {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            align-items: center;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #f5f5f5;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .actions-cell {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2>Disaster Data Management System</h2>
            <ul class="nav-links">
                <li><a href="index.php">Import Excel</a></li>
                <li><a href="crud_interface.php" class="active">Manage Data</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="mining.php">Data Mining</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <h1>Disaster Data CRUD Management</h1>
        <p class="subtitle">Manage provinces, municipalities, incidents, affected, assistance, and evacuation records</p>
        
        <div id="alert-container"></div>
        
        <div class="tabs">
            <button class="tab active" data-table="provinces">Provinces</button>
            <button class="tab" data-table="municipalities">Municipalities</button>
            <button class="tab" data-table="incidents">Incidents</button>
            <button class="tab" data-table="affected">Affected</button>
            <button class="tab" data-table="assistance">Assistance</button>
            <button class="tab" data-table="evacuation">Evacuation</button>
        </div>
        
        <div id="provinces" class="tab-content active">
            <div class="actions">
                <button class="btn btn-primary" data-action="add" data-table="provinces">Add Province</button>
                <button class="btn btn-secondary" data-action="refresh" data-table="provinces">Refresh</button>
            </div>
            <div id="provinces-table"></div>
        </div>
        
        <div id="municipalities" class="tab-content">
            <div class="actions">
                <button class="btn btn-primary" data-action="add" data-table="municipalities">Add Municipality</button>
                <button class="btn btn-secondary" data-action="refresh" data-table="municipalities">Refresh</button>
            </div>
            <div id="municipalities-table"></div>
        </div>
        
        <div id="incidents" class="tab-content">
            <div class="actions">
                <button class="btn btn-primary" data-action="add" data-table="incidents">Add Incident</button>
                <button class="btn btn-secondary" data-action="refresh" data-table="incidents">Refresh</button>
            </div>
            <div id="incidents-table"></div>
        </div>
        
        <div id="affected" class="tab-content">
            <div class="actions">
                <button class="btn btn-primary" data-action="add" data-table="affected">Add Affected</button>
                <button class="btn btn-secondary" data-action="refresh" data-table="affected">Refresh</button>
            </div>
            <div id="affected-table"></div>
        </div>
        
        <div id="assistance" class="tab-content">
            <div class="actions">
                <button class="btn btn-primary" data-action="add" data-table="assistance">Add Assistance</button>
                <button class="btn btn-secondary" data-action="refresh" data-table="assistance">Refresh</button>
            </div>
            <div id="assistance-table"></div>
        </div>
        
        <div id="evacuation" class="tab-content">
            <div class="actions">
                <button class="btn btn-primary" data-action="add" data-table="evacuation">Add Evacuation</button>
                <button class="btn btn-secondary" data-action="refresh" data-table="evacuation">Refresh</button>
            </div>
            <div id="evacuation-table"></div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Add Record</h2>
                <span class="close" id="modal-close-btn">&times;</span>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>
    
    <script>
        const API_BASE = 'backend/crud.php';
        
        // Helper to build API URL
        function apiUrl(table, id = null) {
            if (id !== null) {
                return `${API_BASE}?table=${table}&id=${id}`;
            }
            return `${API_BASE}?table=${table}`;
        }
        let currentTable = 'provinces';
        let currentPage = {};
        let allData = {};
        
        // Table field definitions
        const tableFields = {
            provinces: [
                {name: 'provinceid', label: 'Province ID', type: 'number', required: true},
                {name: 'province_name', label: 'Province Name', type: 'text', required: true}
            ],
            municipalities: [
                {name: 'municipality_id', label: 'Municipality ID', type: 'number', required: true},
                {name: 'municipality_name', label: 'Municipality Name', type: 'text', required: true},
                {name: 'provinceid', label: 'Province ID', type: 'number', required: true},
                {name: 'province_name', label: 'Province Name (auto-created if needed)', type: 'text', required: false}
            ],
            incidents: [
                {name: 'incident_id', label: 'Incident ID', type: 'number', required: true},
                {name: 'latest_disaster_title_id', label: 'Disaster Title ID', type: 'number', required: true},
                {name: 'disaster_name', label: 'Disaster Name', type: 'text', required: true},
                {name: 'disaster_date', label: 'Disaster Date', type: 'date', required: true}
            ],
            affected: [
                {name: 'affected_id', label: 'Affected ID', type: 'number', required: true},
                {name: 'incident_id', label: 'Incident ID', type: 'number', required: true},
                {name: 'latest_disaster_title_id', label: 'Disaster Title ID', type: 'number', required: false},
                {name: 'disaster_name', label: 'Disaster Name (auto-created if needed)', type: 'text', required: false},
                {name: 'disaster_date', label: 'Disaster Date (auto-created if needed)', type: 'date', required: false},
                {name: 'municipality_id', label: 'Municipality ID', type: 'number', required: true},
                {name: 'municipality_name', label: 'Municipality Name (auto-created if needed)', type: 'text', required: false},
                {name: 'provinceid', label: 'Province ID (auto-created if needed)', type: 'number', required: false},
                {name: 'province_name', label: 'Province Name (auto-created if needed)', type: 'text', required: false},
                {name: 'fam_no', label: 'Family Number', type: 'number', required: false},
                {name: 'person_no', label: 'Person Number', type: 'number', required: false},
                {name: 'brgy_affected', label: 'Barangay Affected', type: 'number', required: false}
            ],
            assistance: [
                {name: 'assistance_id', label: 'Assistance ID', type: 'number', required: true},
                {name: 'affected_id', label: 'Affected ID', type: 'number', required: true},
                {name: 'item_id', label: 'Item ID', type: 'number', required: true},
                {name: 'fnfi_name', label: 'FNFI Name', type: 'text', required: true},
                {name: 'cost', label: 'Cost', type: 'number', step: '0.01', required: false},
                {name: 'quantity', label: 'Quantity', type: 'number', required: false},
                {name: 'total_amount', label: 'Total Amount', type: 'number', step: '0.01', required: false}
            ],
            evacuation: [
                {name: 'affected_id', label: 'Affected ID', type: 'number', required: true},
                {name: 'ec_cum', label: 'EC Cumulative', type: 'number', required: false},
                {name: 'ec_now', label: 'EC Now', type: 'number', required: false},
                {name: 'family_cum', label: 'Family Cumulative', type: 'number', required: false},
                {name: 'person_cum', label: 'Person Cumulative', type: 'number', required: false},
                {name: 'evacuation_name', label: 'Evacuation Name', type: 'text', required: false}
            ]
        };
        
        function switchTab(table) {
            currentTable = table;
            
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            
            // Add active class to the tab button
            document.querySelectorAll('.tab').forEach(btn => {
                if (btn.getAttribute('data-table') === table) {
                    btn.classList.add('active');
                }
            });
            
            // Show the corresponding content
            const contentDiv = document.getElementById(table);
            if (contentDiv) {
                contentDiv.classList.add('active');
            }
            
            // Load table data
            loadTable(table);
        }
        
        async function loadTable(table, page = 1) {
            const tableDiv = document.getElementById(table + '-table');
            tableDiv.innerHTML = '<div class="loading">Loading...</div>';
            
            try {
                const url = `${apiUrl(table)}&page=${page}&limit=50`;
                console.log('Fetching URL:', url); // Debug log
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('API Response:', result); // Debug log
                
                if (result.success) {
                    allData[table] = result.data;
                    currentPage[table] = page;
                    renderTable(table, result.data, result.pagination);
                } else {
                    showAlert('error', result.message || 'Failed to load data');
                    tableDiv.innerHTML = `<p>Error: ${result.message || 'Failed to load data'}</p>`;
                }
            } catch (error) {
                console.error('Load table error:', error); // Debug log
                showAlert('error', 'Error: ' + error.message);
                tableDiv.innerHTML = `<p>Error loading data: ${error.message}</p>`;
            }
        }
        
        function renderTable(table, data, pagination) {
            const tableDiv = document.getElementById(table + '-table');
            
            if (!data || data.length === 0) {
                tableDiv.innerHTML = '<p>No records found</p>';
                return;
            }
            
            console.log('Rendering table:', table, 'with', data.length, 'records'); // Debug log
            
            let html = '<table><thead><tr>';
            // Show all fields from first record
            const firstRecord = data[0];
            if (!firstRecord) {
                tableDiv.innerHTML = '<p>No data to display</p>';
                return;
            }
            
            Object.keys(firstRecord).forEach(key => {
                html += `<th>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</th>`;
            });
            html += '<th>Actions</th></tr></thead><tbody>';
            
            data.forEach(record => {
                html += '<tr>';
                Object.values(record).forEach(value => {
                    const displayValue = value !== null && value !== undefined ? String(value) : '';
                    html += `<td>${displayValue}</td>`;
                });
                const primaryKey = getPrimaryKeyValue(table, record);
                html += `<td class="actions-cell">
                    <button class="btn btn-warning btn-sm" data-action="edit" data-table="${table}" data-id="${primaryKey}">Edit</button>
                    <button class="btn btn-danger btn-sm" data-action="delete" data-table="${table}" data-id="${primaryKey}">Delete</button>
                </td></tr>`;
            });
            
            html += '</tbody></table>';
            
            if (pagination && pagination.pages > 1) {
                html += '<div class="pagination">';
                html += `<button ${pagination.page === 1 ? 'disabled' : ''} data-action="pagination" data-table="${table}" data-page="${pagination.page - 1}">Previous</button>`;
                html += `<span>Page ${pagination.page} of ${pagination.pages} (${pagination.total} total)</span>`;
                html += `<button ${pagination.page >= pagination.pages ? 'disabled' : ''} data-action="pagination" data-table="${table}" data-page="${pagination.page + 1}">Next</button>`;
                html += '</div>';
            }
            
            tableDiv.innerHTML = html;
        }
        
        function getPrimaryKeyValue(table, record) {
            const keys = {
                provinces: 'provinceid',
                municipalities: 'municipality_id',
                incidents: 'incident_id',
                affected: 'affected_id',
                assistance: 'id',
                evacuation: 'evacuation_id'
            };
            return record[keys[table]];
        }
        
        function showAddModal(table) {
            currentTable = table;
            document.getElementById('modal-title').textContent = `Add ${table.charAt(0).toUpperCase() + table.slice(1)}`;
            const fields = tableFields[table];
            let html = '<form id="record-form">';
            fields.forEach(field => {
                const stepAttr = field.step ? ' step="' + field.step + '"' : '';
                const requiredAttr = field.required ? ' required' : '';
                html += '<div class="form-group">' +
                    '<label>' + field.label + (field.required ? ' *' : '') + '</label>' +
                    '<input type="' + field.type + '" name="' + field.name + '"' + requiredAttr + stepAttr + ' />' +
                    '</div>';
            });
            html += '<div style="margin-top: 20px;">' +
                '<button type="submit" class="btn btn-primary">Save</button>' +
                '<button type="button" class="btn btn-secondary" id="cancel-btn" style="margin-left: 10px;">Cancel</button>' +
                '</div></form>';
            document.getElementById('modal-body').innerHTML = html;
            document.getElementById('modal').style.display = 'block';
            
            // Add event listeners after DOM is updated
            setTimeout(() => {
                const form = document.getElementById('record-form');
                const cancelBtn = document.getElementById('cancel-btn');
                
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        saveRecord(table);
                    });
                }
                
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => {
                        closeModal();
                    });
                }
            }, 10);
        }
        
        async function showEditModal(table, id) {
            try {
                const response = await fetch(apiUrl(table, id));
                const result = await response.json();
                
                if (result.success) {
                    currentTable = table;
                    document.getElementById('modal-title').textContent = `Edit ${table.charAt(0).toUpperCase() + table.slice(1)}`;
                    const fields = tableFields[table];
                    let html = '<form id="record-form" data-id="' + id + '">';
                    fields.forEach(field => {
                        const value = result.data[field.name] || '';
                        const stepAttr = field.step ? ' step="' + field.step + '"' : '';
                        const requiredAttr = field.required ? ' required' : '';
                        const escapedValue = String(value).replace(/"/g, '&quot;');
                        html += '<div class="form-group">' +
                            '<label>' + field.label + (field.required ? ' *' : '') + '</label>' +
                            '<input type="' + field.type + '" name="' + field.name + '" value="' + escapedValue + '"' + requiredAttr + stepAttr + ' />' +
                            '</div>';
                    });
                    html += '<div style="margin-top: 20px;">' +
                        '<button type="submit" class="btn btn-primary">Update</button>' +
                        '<button type="button" class="btn btn-secondary" id="cancel-btn-edit" style="margin-left: 10px;">Cancel</button>' +
                        '</div></form>';
                    document.getElementById('modal-body').innerHTML = html;
                    document.getElementById('modal').style.display = 'block';
                    
                    // Add event listeners after DOM is updated
                    setTimeout(() => {
                        const form = document.getElementById('record-form');
                        const cancelBtn = document.getElementById('cancel-btn-edit');
                        
                        if (form) {
                            form.addEventListener('submit', (e) => {
                                e.preventDefault();
                                updateRecord(table, id);
                            });
                        }
                        
                        if (cancelBtn) {
                            cancelBtn.addEventListener('click', () => {
                                closeModal();
                            });
                        }
                    }, 10);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Error: ' + error.message);
            }
        }
        
        async function saveRecord(table) {
            const form = document.getElementById('record-form');
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (value !== '') {
                    if (tableFields[table].find(f => f.name === key && f.type === 'number')) {
                        data[key] = value.includes('.') ? parseFloat(value) : parseInt(value);
                    } else {
                        data[key] = value;
                    }
                }
            });
            
            try {
                const response = await fetch(apiUrl(table), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Record created successfully!');
                    closeModal();
                    loadTable(table, currentPage[table] || 1);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Error: ' + error.message);
            }
        }
        
        async function updateRecord(table, id) {
            const form = document.getElementById('record-form');
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (value !== '') {
                    if (tableFields[table].find(f => f.name === key && f.type === 'number')) {
                        data[key] = value.includes('.') ? parseFloat(value) : parseInt(value);
                    } else {
                        data[key] = value;
                    }
                }
            });
            
            try {
                const response = await fetch(apiUrl(table, id), {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Record updated successfully!');
                    closeModal();
                    loadTable(table, currentPage[table] || 1);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Error: ' + error.message);
            }
        }
        
        async function deleteRecord(table, id) {
            if (!confirm('Are you sure you want to delete this record?')) {
                return;
            }
            
            try {
                const response = await fetch(apiUrl(table, id), {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Record deleted successfully!');
                    loadTable(table, currentPage[table] || 1);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Error: ' + error.message);
            }
        }
        
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        function showAlert(type, message) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
        
        // Set up tab click handlers
        document.addEventListener('DOMContentLoaded', () => {
            // Add click handlers to all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    const table = e.target.getAttribute('data-table');
                    if (table) {
                        switchTab(table);
                    }
                });
            });
            
            // Add click handler for modal close button
            const modalCloseBtn = document.getElementById('modal-close-btn');
            if (modalCloseBtn) {
                modalCloseBtn.addEventListener('click', () => {
                    closeModal();
                });
            }
            
            // Add click handlers to all action buttons - use event delegation for dynamic buttons
            document.addEventListener('click', (e) => {
                const action = e.target.getAttribute('data-action');
                const table = e.target.getAttribute('data-table');
                const id = e.target.getAttribute('data-id');
                const page = e.target.getAttribute('data-page');
                
                if (!action) return;
                
                if (action === 'add' && table) {
                    showAddModal(table);
                } else if (action === 'refresh' && table) {
                    loadTable(table, currentPage[table] || 1);
                } else if (action === 'edit' && table && id) {
                    showEditModal(table, parseInt(id));
                } else if (action === 'delete' && table && id) {
                    deleteRecord(table, parseInt(id));
                } else if (action === 'pagination' && table && page) {
                    loadTable(table, parseInt(page));
                }
            });
            
            // Load initial table after a short delay to ensure DOM is ready
            setTimeout(() => {
                loadTable('provinces');
            }, 100);
        });
        
        // Close modal when clicking outside
        window.onclick = (event) => {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>

