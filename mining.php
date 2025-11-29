<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mining - Association Rules</title>
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
            background: #9C27B0;
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

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            margin-bottom: 20px;
        }

        .controls {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .controls h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .control-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .control-item {
            display: flex;
            flex-direction: column;
        }

        .control-item label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .control-item input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            background: #9C27B0;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #7B1FA2;
        }

        .stats {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-item .value {
            color: #9C27B0;
            font-size: 24px;
            font-weight: bold;
        }

        .results-table {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .results-table h3 {
            margin-bottom: 15px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #9C27B0;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .highlight {
            background: #FFF9C4 !important;
            font-weight: 600;
        }

        .highlight-row {
            background: #E1BEE7 !important;
        }

        .pattern-cell {
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .metric-cell {
            text-align: center;
            font-weight: 600;
        }

        .metric-high {
            color: #4CAF50;
        }

        .metric-medium {
            color: #FF9800;
        }

        .metric-low {
            color: #F44336;
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

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .explanation-cell {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
            max-width: 400px;
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
                <li><a href="mining.php" class="active">Data Mining</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Association Rule Mining - Apriori Algorithm</h1>
            <p>Discover frequent patterns and associations in assistance item distributions</p>
            <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 13px;">
                <strong>Understanding the Metrics:</strong>
                <ul style="margin: 10px 0 0 20px; color: #555;">
                    <li><strong>min sup (Support):</strong> Percentage of transactions containing the pattern. Higher = more frequent.</li>
                    <li><strong>conf (Confidence):</strong> Probability that consequent appears when antecedent is present. Higher = stronger rule.</li>
                    <li><strong>lift:</strong> How much more likely consequent is when antecedent is present vs. overall. >1.2 = positive association.</li>
                </ul>
            </div>
        </div>

        <div class="controls">
            <h3>Mining Parameters</h3>
            <div class="control-group">
                <div class="control-item">
                    <label>Minimum Support (0.0 - 1.0)</label>
                    <input type="number" id="min-support" value="0.1" min="0" max="1" step="0.01">
                </div>
                <div class="control-item">
                    <label>Minimum Confidence (0.0 - 1.0)</label>
                    <input type="number" id="min-confidence" value="0.6" min="0" max="1" step="0.01">
                </div>
                <div class="control-item">
                    <label>Minimum Lift</label>
                    <input type="number" id="min-lift" value="1.2" min="0" step="0.1">
                </div>
            </div>
            <button class="btn" onclick="runMining()">Run Data Mining</button>
        </div>

        <div id="loading" class="loading" style="display: none;">Running Apriori algorithm...</div>
        <div id="error-container"></div>

        <div id="results" style="display: none;">
            <div class="stats" id="stats-container"></div>
            
            <div class="results-table">
                <h3>Frequent Patterns and Association Rules</h3>
                <table id="results-table">
                    <thead>
                        <tr>
                            <th>Frequent Patterns</th>
                            <th>min sup</th>
                            <th>conf</th>
                            <th>lift</th>
                            <th>Explanation</th>
                        </tr>
                    </thead>
                    <tbody id="results-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function runMining() {
            const minSupport = parseFloat(document.getElementById('min-support').value);
            const minConfidence = parseFloat(document.getElementById('min-confidence').value);
            const minLift = parseFloat(document.getElementById('min-lift').value);

            // Validate inputs
            if (minSupport < 0 || minSupport > 1) {
                showError('Minimum support must be between 0 and 1');
                return;
            }
            if (minConfidence < 0 || minConfidence > 1) {
                showError('Minimum confidence must be between 0 and 1');
                return;
            }
            if (minLift < 0) {
                showError('Minimum lift must be positive');
                return;
            }

            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').style.display = 'none';
            document.getElementById('error-container').innerHTML = '';

            try {
                const url = `backend/mining.php?min_support=${minSupport}&min_confidence=${minConfidence}&min_lift=${minLift}`;
                const response = await fetch(url);
                const result = await response.json();

                document.getElementById('loading').style.display = 'none';

                if (result.success) {
                    displayResults(result);
                } else {
                    showError(result.message || 'Mining failed');
                }
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                showError('Error: ' + error.message);
            }
        }

        function displayResults(result) {
            // Display statistics
            const statsContainer = document.getElementById('stats-container');
            statsContainer.innerHTML = `
                <div class="stat-item">
                    <div class="label">Total Transactions</div>
                    <div class="value">${result.stats.total_transactions}</div>
                </div>
                <div class="stat-item">
                    <div class="label">Rules Found</div>
                    <div class="value">${result.stats.total_rules}</div>
                </div>
                <div class="stat-item">
                    <div class="label">Min Support</div>
                    <div class="value">${result.stats.min_support}</div>
                </div>
                <div class="stat-item">
                    <div class="label">Min Confidence</div>
                    <div class="value">${result.stats.min_confidence}</div>
                </div>
                <div class="stat-item">
                    <div class="label">Min Lift</div>
                    <div class="value">${result.stats.min_lift}</div>
                </div>
            `;

            // Display rules table
            const tbody = document.getElementById('results-body');
            tbody.innerHTML = '';

            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="no-results">No association rules found with the current parameters. Try lowering the thresholds.</td></tr>';
            } else {
                result.data.forEach((rule, index) => {
                    const row = document.createElement('tr');
                    
                    // Highlight top rules
                    if (index < 3) {
                        row.classList.add('highlight-row');
                    }

                    // Determine metric classes
                    const supportClass = rule.support >= 0.5 ? 'metric-high' : (rule.support >= 0.3 ? 'metric-medium' : 'metric-low');
                    const confClass = rule.confidence >= 0.8 ? 'metric-high' : (rule.confidence >= 0.6 ? 'metric-medium' : 'metric-low');
                    const liftClass = rule.lift >= 1.5 ? 'metric-high' : (rule.lift >= 1.2 ? 'metric-medium' : 'metric-low');

                    row.innerHTML = `
                        <td class="pattern-cell">${rule.pattern}</td>
                        <td class="metric-cell ${supportClass}">${rule.support}</td>
                        <td class="metric-cell ${confClass}">${rule.confidence}</td>
                        <td class="metric-cell ${liftClass}">${rule.lift}</td>
                        <td class="explanation-cell">${rule.explanation || 'No explanation available'}</td>
                    `;
                    tbody.appendChild(row);
                });
            }

            document.getElementById('results').style.display = 'block';
        }

        function showError(message) {
            const container = document.getElementById('error-container');
            container.innerHTML = `<div class="error">${message}</div>`;
        }

        // Run mining on page load
        window.addEventListener('DOMContentLoaded', () => {
            runMining();
        });
    </script>
</body>
</html>

