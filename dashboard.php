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
                <li><a href="logs.php">Activity Logs</a></li>
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
        const DASHBOARD_API = 'backend/dashboard.php';

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

        // Fetch view data from dashboard API
        async function fetchViewData(view) {
            try {
                const response = await fetch(`${DASHBOARD_API}?view=${view}`);
                const result = await response.json();
                if (result.success) {
                    return result.data;
                }
                return null;
            } catch (error) {
                console.error(`Error fetching view ${view}:`, error);
                return null;
            }
        }

        // Fetch view data with pagination (returns array)
        async function fetchViewDataList(view, limit = 1000) {
            try {
                const response = await fetch(`${DASHBOARD_API}?view=${view}&limit=${limit}`);
                const result = await response.json();
                if (result.success) {
                    return result.data || [];
                }
                return [];
            } catch (error) {
                console.error(`Error fetching view ${view}:`, error);
                return [];
            }
        }

        // Load all dashboard data using views
        async function loadDashboardData() {
            try {
                // Fetch all data in parallel using dashboard API
                const [
                    dashboardStats,
                    disasterTypes,
                    provinceStats,
                    municipalityStats,
                    timeline,
                    assistanceSummary,
                    evacuationSummary
                ] = await Promise.all([
                    fetchViewData('view_dashboard_stats'),
                    fetchViewDataList('view_disaster_type_summary'),
                    fetchViewDataList('view_province_stats'),
                    fetchViewDataList('view_municipality_stats'),
                    fetchViewDataList('view_disaster_timeline'),
                    fetchViewDataList('view_assistance_summary'),
                    fetchViewDataList('view_evacuation_summary')
                ]);

                // Update statistics from view
                if (dashboardStats) {
                    updateStatistics(dashboardStats);
                }

                // Create charts using view data
                createDisasterTypeChart(disasterTypes);
                createProvinceChart(provinceStats);
                createTimelineChart(timeline);
                createAssistanceChart(assistanceSummary);
                createEvacuationChart(evacuationSummary, dashboardStats);
                createMunicipalityChart(municipalityStats);

                // Hide loading, show content
                document.getElementById('loading').style.display = 'none';
                document.getElementById('dashboard-content').style.display = 'block';

            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                showError('Error loading dashboard data: ' + error.message);
            }
        }

        // Update statistics cards
        function updateStatistics(stats) {
            document.getElementById('total-incidents').textContent = formatNumber(stats.total_incidents || 0);
            document.getElementById('total-affected').textContent = formatNumber(stats.total_affected || 0);
            document.getElementById('total-families').textContent = formatNumber(stats.total_families || 0);
            document.getElementById('total-assistance').textContent = formatNumber(stats.total_assistance || 0);
            document.getElementById('total-evacuation').textContent = formatNumber(stats.total_evacuation || 0);
            document.getElementById('total-amount').textContent = formatCurrency(stats.total_amount || 0);
        }

        // Create Disaster Type Chart
        function createDisasterTypeChart(disasterTypes) {
            if (!disasterTypes || disasterTypes.length === 0) {
                return;
            }

            const labels = disasterTypes.map(d => d.disaster_name || 'Unknown');
            const data = disasterTypes.map(d => d.incident_count || 0);

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
        function createProvinceChart(provinceStats) {
            if (!provinceStats || provinceStats.length === 0) {
                return;
            }

            const sorted = provinceStats
                .sort((a, b) => (b.total_affected_persons || 0) - (a.total_affected_persons || 0))
                .slice(0, 10);

            new Chart(document.getElementById('provinceChart'), {
                type: 'bar',
                data: {
                    labels: sorted.map(p => p.province_name || 'Unknown'),
                    datasets: [{
                        label: 'Affected Persons',
                        data: sorted.map(p => p.total_affected_persons || 0),
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
        function createTimelineChart(timeline) {
            if (!timeline || timeline.length === 0) {
                return;
            }

            new Chart(document.getElementById('timelineChart'), {
                type: 'line',
                data: {
                    labels: timeline.map(t => t.month_label || t.month_year || ''),
                    datasets: [{
                        label: 'Number of Disasters',
                        data: timeline.map(t => t.incident_count || 0),
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
        function createAssistanceChart(assistanceSummary) {
            if (!assistanceSummary || assistanceSummary.length === 0) {
                return;
            }

            const sorted = assistanceSummary
                .sort((a, b) => (b.total_quantity || 0) - (a.total_quantity || 0))
                .slice(0, 8);

            new Chart(document.getElementById('assistanceChart'), {
                type: 'pie',
                data: {
                    labels: sorted.map(a => a.fnfi_name || 'Unknown'),
                    datasets: [{
                        data: sorted.map(a => a.total_quantity || 0),
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
        function createEvacuationChart(evacuationSummary, dashboardStats) {
            // Use dashboard stats if available, otherwise calculate from summary
            let totalEC, totalECNow, totalFamily, totalPerson;
            
            if (dashboardStats) {
                totalEC = dashboardStats.total_ec_cum || 0;
                totalECNow = dashboardStats.total_ec_now || 0;
                totalFamily = dashboardStats.total_family_cum || 0;
                totalPerson = dashboardStats.total_person_cum || 0;
            } else if (evacuationSummary && evacuationSummary.length > 0) {
                totalEC = evacuationSummary.reduce((sum, e) => sum + (e.total_ec_cum || 0), 0);
                totalECNow = evacuationSummary.reduce((sum, e) => sum + (e.total_ec_now || 0), 0);
                totalFamily = evacuationSummary.reduce((sum, e) => sum + (e.total_family_cum || 0), 0);
                totalPerson = evacuationSummary.reduce((sum, e) => sum + (e.total_person_cum || 0), 0);
            } else {
                totalEC = totalECNow = totalFamily = totalPerson = 0;
            }

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
        function createMunicipalityChart(municipalityStats) {
            if (!municipalityStats || municipalityStats.length === 0) {
                return;
            }

            const sorted = municipalityStats
                .sort((a, b) => (b.total_affected_persons || 0) - (a.total_affected_persons || 0))
                .slice(0, 10);

            new Chart(document.getElementById('municipalityChart'), {
                type: 'bar',
                data: {
                    labels: sorted.map(m => m.municipality_name || 'Unknown'),
                    datasets: [{
                        label: 'Affected Persons',
                        data: sorted.map(m => m.total_affected_persons || 0),
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

