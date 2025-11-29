<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Disaster Data Visualization</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2196F3;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .chart-container {
            position: relative;
            height: 300px;
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

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="mining.php">Data Mining</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Disaster Data Dashboard</h1>
            <p>Visual analytics and insights from disaster management data</p>
        </div>

        <div id="loading" class="loading">Loading dashboard data...</div>
        <div id="error-container"></div>

        <div id="dashboard-content" style="display: none;">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Incidents</h3>
                    <div class="value" id="total-incidents">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Affected</h3>
                    <div class="value" id="total-affected">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Families</h3>
                    <div class="value" id="total-families">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Assistance</h3>
                    <div class="value" id="total-assistance">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Evacuation Centers</h3>
                    <div class="value" id="total-evacuation">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Assistance Amount</h3>
                    <div class="value" id="total-amount">₱0</div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>Disasters by Type</h3>
                    <div class="chart-container">
                        <canvas id="disasterTypeChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Top 10 Affected Provinces</h3>
                    <div class="chart-container">
                        <canvas id="provinceChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Disasters Timeline</h3>
                    <div class="chart-container">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Assistance Distribution</h3>
                    <div class="chart-container">
                        <canvas id="assistanceChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Evacuation Statistics</h3>
                    <div class="chart-container">
                        <canvas id="evacuationChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Top 10 Affected Municipalities</h3>
                    <div class="chart-container">
                        <canvas id="municipalityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = 'backend/crud.php';

        // Format number with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Format currency
        function formatCurrency(num) {
            return '₱' + formatNumber(parseFloat(num).toFixed(2));
        }

        // Show error message
        function showError(message) {
            const container = document.getElementById('error-container');
            container.innerHTML = `<div class="error">${message}</div>`;
        }

        // Fetch data from API
        async function fetchTableData(table) {
            try {
                const response = await fetch(`${API_BASE}?table=${table}&limit=10000`);
                const result = await response.json();
                if (result.success) {
                    return result.data;
                }
                return [];
            } catch (error) {
                console.error(`Error fetching ${table}:`, error);
                return [];
            }
        }

        // Load all dashboard data
        async function loadDashboardData() {
            try {
                const [incidents, affected, assistance, evacuation, provinces, municipalities] = await Promise.all([
                    fetchTableData('incidents'),
                    fetchTableData('affected'),
                    fetchTableData('assistance'),
                    fetchTableData('evacuation'),
                    fetchTableData('provinces'),
                    fetchTableData('municipalities')
                ]);

                // Calculate statistics
                const stats = calculateStatistics(incidents, affected, assistance, evacuation);
                updateStatistics(stats);

                // Create charts
                createDisasterTypeChart(incidents);
                createProvinceChart(affected, provinces, municipalities);
                createTimelineChart(incidents);
                createAssistanceChart(assistance);
                createEvacuationChart(evacuation);
                createMunicipalityChart(affected, municipalities);

                // Hide loading, show content
                document.getElementById('loading').style.display = 'none';
                document.getElementById('dashboard-content').style.display = 'block';

            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                showError('Error loading dashboard data: ' + error.message);
            }
        }

        // Calculate statistics
        function calculateStatistics(incidents, affected, assistance, evacuation) {
            const totalIncidents = incidents.length;
            const totalAffected = affected.reduce((sum, a) => sum + (a.person_no || 0), 0);
            const totalFamilies = affected.reduce((sum, a) => sum + (a.fam_no || 0), 0);
            const totalAssistance = assistance.length;
            const totalEvacuation = evacuation.length;
            const totalAmount = assistance.reduce((sum, a) => sum + parseFloat(a.total_amount || 0), 0);

            return {
                totalIncidents,
                totalAffected,
                totalFamilies,
                totalAssistance,
                totalEvacuation,
                totalAmount
            };
        }

        // Update statistics cards
        function updateStatistics(stats) {
            document.getElementById('total-incidents').textContent = formatNumber(stats.totalIncidents);
            document.getElementById('total-affected').textContent = formatNumber(stats.totalAffected);
            document.getElementById('total-families').textContent = formatNumber(stats.totalFamilies);
            document.getElementById('total-assistance').textContent = formatNumber(stats.totalAssistance);
            document.getElementById('total-evacuation').textContent = formatNumber(stats.totalEvacuation);
            document.getElementById('total-amount').textContent = formatCurrency(stats.totalAmount);
        }

        // Create Disaster Type Chart
        function createDisasterTypeChart(incidents) {
            const disasterCounts = {};
            incidents.forEach(incident => {
                const name = incident.disaster_name || 'Unknown';
                disasterCounts[name] = (disasterCounts[name] || 0) + 1;
            });

            const labels = Object.keys(disasterCounts);
            const data = Object.values(disasterCounts);

            new Chart(document.getElementById('disasterTypeChart'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Create Province Chart
        function createProvinceChart(affected, provinces, municipalities) {
            const provinceMap = {};
            provinces.forEach(p => {
                provinceMap[p.provinceid] = p.province_name;
            });

            const municipalityMap = {};
            municipalities.forEach(m => {
                municipalityMap[m.municipality_id] = m.provinceid;
            });

            const provinceCounts = {};
            affected.forEach(a => {
                const provinceId = municipalityMap[a.municipality_id];
                if (provinceId) {
                    const provinceName = provinceMap[provinceId] || 'Unknown';
                    provinceCounts[provinceName] = (provinceCounts[provinceName] || 0) + (a.person_no || 0);
                }
            });

            const sorted = Object.entries(provinceCounts)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10);

            new Chart(document.getElementById('provinceChart'), {
                type: 'bar',
                data: {
                    labels: sorted.map(s => s[0]),
                    datasets: [{
                        label: 'Affected Persons',
                        data: sorted.map(s => s[1]),
                        backgroundColor: '#36A2EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Create Timeline Chart
        function createTimelineChart(incidents) {
            const monthCounts = {};
            incidents.forEach(incident => {
                if (incident.disaster_date) {
                    const date = new Date(incident.disaster_date);
                    const month = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
                    monthCounts[month] = (monthCounts[month] || 0) + 1;
                }
            });

            const sorted = Object.entries(monthCounts).sort((a, b) => {
                return new Date(a[0]) - new Date(b[0]);
            });

            new Chart(document.getElementById('timelineChart'), {
                type: 'line',
                data: {
                    labels: sorted.map(s => s[0]),
                    datasets: [{
                        label: 'Number of Disasters',
                        data: sorted.map(s => s[1]),
                        borderColor: '#FF6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Create Assistance Chart
        function createAssistanceChart(assistance) {
            const itemCounts = {};
            assistance.forEach(a => {
                const item = a.fnfi_name || 'Unknown';
                itemCounts[item] = (itemCounts[item] || 0) + (a.quantity || 0);
            });

            const sorted = Object.entries(itemCounts)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 8);

            new Chart(document.getElementById('assistanceChart'), {
                type: 'pie',
                data: {
                    labels: sorted.map(s => s[0]),
                    datasets: [{
                        data: sorted.map(s => s[1]),
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Create Evacuation Chart
        function createEvacuationChart(evacuation) {
            const totalEC = evacuation.reduce((sum, e) => sum + (e.ec_cum || 0), 0);
            const totalECNow = evacuation.reduce((sum, e) => sum + (e.ec_now || 0), 0);
            const totalFamily = evacuation.reduce((sum, e) => sum + (e.family_cum || 0), 0);
            const totalPerson = evacuation.reduce((sum, e) => sum + (e.person_cum || 0), 0);

            new Chart(document.getElementById('evacuationChart'), {
                type: 'bar',
                data: {
                    labels: ['Evacuation Centers (Cumulative)', 'Evacuation Centers (Now)', 'Families (Cumulative)', 'Persons (Cumulative)'],
                    datasets: [{
                        label: 'Count',
                        data: [totalEC, totalECNow, totalFamily, totalPerson],
                        backgroundColor: ['#4BC0C0', '#36A2EB', '#FFCE56', '#FF6384']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Create Municipality Chart
        function createMunicipalityChart(affected, municipalities) {
            const municipalityMap = {};
            municipalities.forEach(m => {
                municipalityMap[m.municipality_id] = m.municipality_name;
            });

            const municipalityCounts = {};
            affected.forEach(a => {
                const municipalityName = municipalityMap[a.municipality_id] || 'Unknown';
                municipalityCounts[municipalityName] = (municipalityCounts[municipalityName] || 0) + (a.person_no || 0);
            });

            const sorted = Object.entries(municipalityCounts)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10);

            new Chart(document.getElementById('municipalityChart'), {
                type: 'bar',
                data: {
                    labels: sorted.map(s => s[0]),
                    datasets: [{
                        label: 'Affected Persons',
                        data: sorted.map(s => s[1]),
                        backgroundColor: '#FF9F40'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Load dashboard on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
        });
    </script>
</body>
</html>

