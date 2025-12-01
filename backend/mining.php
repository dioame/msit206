<?php
// Start output buffering
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$logFile = __DIR__ . '/../process.log';
ini_set('error_log', $logFile);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Apriori Algorithm Implementation with Database-Optimized Calculations
class AprioriMining {
    private $conn;
    private $totalTransactions;
    private $minSupport;
    private $minConfidence;
    private $minLift;
    
    public function __construct($conn, $totalTransactions, $minSupport = 0.1, $minConfidence = 0.6, $minLift = 1.2) {
        $this->conn = $conn;
        $this->totalTransactions = $totalTransactions;
        $this->minSupport = $minSupport;
        $this->minConfidence = $minConfidence;
        $this->minLift = $minLift;
    }
    
    // Get frequent itemsets using database calculations
    public function getFrequentItemsets() {
        $frequentItemsets = [];
        $k = 1;
        
        // Generate 1-itemsets using SQL
        $frequent = $this->getFrequent1Itemsets();
        
        if (empty($frequent)) {
            return [];
        }
        
        $frequentItemsets[$k] = $frequent;
        
        // Generate k-itemsets (k >= 2)
        while (!empty($frequent)) {
            $k++;
            $candidates = $this->generateCandidates($frequent, $k);
            $frequent = $this->filterFrequentWithDB($candidates);
            
            if (!empty($frequent)) {
                $frequentItemsets[$k] = $frequent;
            } else {
                break;
            }
        }
        
        return $frequentItemsets;
    }
    
    // Get frequent 1-itemsets using SQL
    private function getFrequent1Itemsets() {
        $minCount = ceil($this->totalTransactions * $this->minSupport);
        
        $query = "SELECT 
            fnfi_name as item,
            COUNT(DISTINCT assistance_id) as count,
            COUNT(DISTINCT assistance_id) / ? as support
        FROM assistance
        GROUP BY fnfi_name
        HAVING COUNT(DISTINCT assistance_id) >= ?
        ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("di", $this->totalTransactions, $minCount);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $frequent = [];
        while ($row = $result->fetch_assoc()) {
            $frequent[$row['item']] = floatval($row['support']);
        }
        $stmt->close();
        
        return $frequent;
    }
    
    // Generate k-itemset candidates from (k-1)-itemsets
    private function generateCandidates($frequent, $k) {
        $candidates = [];
        $itemsets = [];
        
        // Convert string keys to arrays
        foreach (array_keys($frequent) as $itemsetStr) {
            $itemsets[] = explode(',', $itemsetStr);
        }
        
        $n = count($itemsets);
        
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $itemset1 = $itemsets[$i];
                $itemset2 = $itemsets[$j];
                
                // For k=2, just combine any two 1-itemsets
                // For k>2, check if first k-2 items match
                $canJoin = true;
                if ($k > 2) {
                    for ($x = 0; $x < $k - 2; $x++) {
                        if (!isset($itemset1[$x]) || !isset($itemset2[$x]) || $itemset1[$x] !== $itemset2[$x]) {
                            $canJoin = false;
                            break;
                        }
                    }
                }
                
                if ($canJoin) {
                    // Join itemsets
                    $candidate = array_unique(array_merge($itemset1, $itemset2));
                    sort($candidate);
                    
                    if (count($candidate) === $k) {
                        $key = implode(',', $candidate);
                        if (!isset($candidates[$key])) {
                            $candidates[$key] = 0;
                        }
                    }
                }
            }
        }
        
        return $candidates;
    }
    
    // Filter frequent itemsets using database calculations
    private function filterFrequentWithDB($candidates) {
        $frequent = [];
        $minCount = ceil($this->totalTransactions * $this->minSupport);
        
        foreach ($candidates as $itemsetStr => $count) {
            $items = explode(',', $itemsetStr);
            $support = $this->calculateSupportWithDB($items);
            
            if ($support >= $this->minSupport) {
                $frequent[$itemsetStr] = $support;
            }
        }
        
        return $frequent;
    }
    
    // Calculate support for itemset using SQL
    private function calculateSupportWithDB($items) {
        if (empty($items)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($items) - 1) . '?';
        $itemCount = count($items);
        
        // Count transactions that contain ALL items in the itemset
        // Using a more efficient query that directly counts matching transactions
        $query = "SELECT COUNT(*) as count
        FROM (
            SELECT assistance_id
            FROM assistance
            WHERE fnfi_name IN ($placeholders)
            GROUP BY assistance_id
            HAVING COUNT(DISTINCT fnfi_name) = ?
        ) as matching_transactions";
        
        $stmt = $this->conn->prepare($query);
        $params = array_merge($items, [$itemCount]);
        $types = str_repeat('s', count($items)) . 'i';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = intval($row['count']);
        $stmt->close();
        
        return $count / $this->totalTransactions;
    }
    
    // Generate association rules using database calculations
    public function generateRules($frequentItemsets) {
        $rules = [];
        
        // Generate rules from frequent itemsets of size >= 2
        foreach ($frequentItemsets as $k => $itemsets) {
            if ($k < 2) continue;
            
            foreach ($itemsets as $itemset => $support) {
                $items = explode(',', $itemset);
                
                // Generate all possible rules
                $this->generateRulesFromItemset($items, $support, $rules);
            }
        }
        
        // Sort by confidence descending
        usort($rules, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return $rules;
    }
    
    // Generate rules from an itemset using database calculations
    private function generateRulesFromItemset($items, $itemsetSupport, &$rules) {
        $n = count($items);
        
        // Generate rules with different combinations
        for ($i = 1; $i < $n; $i++) {
            $combinations = $this->getCombinations($items, $i);
            
            foreach ($combinations as $antecedent) {
                $consequent = array_diff($items, $antecedent);
                
                if (empty($consequent)) continue;
                
                // Calculate support using database
                $antecedentSupport = $this->calculateSupportWithDB($antecedent);
                $consequentSupport = $this->calculateSupportWithDB($consequent);
                
                if ($antecedentSupport == 0) continue;
                
                $confidence = $itemsetSupport / $antecedentSupport;
                $lift = $consequentSupport > 0 ? $confidence / $consequentSupport : 0;
                
                if ($confidence >= $this->minConfidence && $lift >= $this->minLift) {
                    $rules[] = [
                        'antecedent' => $antecedent,
                        'consequent' => $consequent,
                        'support' => round($itemsetSupport, 2),
                        'confidence' => round($confidence, 2),
                        'lift' => round($lift, 2)
                    ];
                }
            }
        }
    }
    
    // Get combinations of items
    private function getCombinations($items, $r) {
        if ($r == 0) return [[]];
        if ($r == count($items)) return [$items];
        
        $combinations = [];
        $n = count($items);
        
        for ($i = 0; $i < $n; $i++) {
            $remaining = array_slice($items, $i + 1);
            $subCombinations = $this->getCombinations($remaining, $r - 1);
            
            foreach ($subCombinations as $sub) {
                $combinations[] = array_merge([$items[$i]], $sub);
            }
        }
        
        return $combinations;
    }
}

// Main processing
try {
    $conn = getDBConnection();
    
    // Get total transaction count using SQL
    $countQuery = "SELECT COUNT(DISTINCT assistance_id) as total
    FROM assistance
    WHERE assistance_id IN (
        SELECT assistance_id
        FROM assistance
        GROUP BY assistance_id
        HAVING COUNT(DISTINCT fnfi_name) > 1
    )";
    
    $countResult = $conn->query($countQuery);
    if (!$countResult) {
        throw new Exception("Count query failed: " . $conn->error);
    }
    
    $countRow = $countResult->fetch_assoc();
    $totalTransactions = intval($countRow['total']);
    
    if ($totalTransactions == 0) {
        $conn->close();
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'No transaction data found. Need at least 2 items per transaction.'
        ]);
        exit();
    }
    
    // Get parameters
    $minSupport = isset($_GET['min_support']) ? floatval($_GET['min_support']) : 0.1;
    $minConfidence = isset($_GET['min_confidence']) ? floatval($_GET['min_confidence']) : 0.6;
    $minLift = isset($_GET['min_lift']) ? floatval($_GET['min_lift']) : 1.2;
    
    // Run Apriori algorithm with database-optimized calculations
    $apriori = new AprioriMining($conn, $totalTransactions, $minSupport, $minConfidence, $minLift);
    $frequentItemsets = $apriori->getFrequentItemsets();
    $rules = $apriori->generateRules($frequentItemsets);
    
    $conn->close();
    
    // Format rules for display
    $formattedRules = [];
    foreach ($rules as $rule) {
        $antecedent = implode(', ', $rule['antecedent']);
        $consequent = implode(', ', $rule['consequent']);
        
        // Generate explanation
        $supportPercent = round($rule['support'] * 100, 1);
        $confidencePercent = round($rule['confidence'] * 100, 1);
        
        $explanation = sprintf(
            "This pattern occurs in %.1f%% of transactions. When %s is provided, there is a %.1f%% chance that %s will also be provided. ",
            $supportPercent,
            $antecedent,
            $confidencePercent,
            $consequent
        );
        
        if ($rule['lift'] > 1.0) {
            $explanation .= "Moderate positive association (lift > 1.2) - these items are related.";
        } else if ($rule['lift'] == 1.0) {
            $explanation .= "Independent association - no association.";
        } else if ($rule['lift'] < 1.0) {
            $explanation .= "Negative association";
        }
        
        $formattedRules[] = [
            'pattern' => $antecedent . ' → ' . $consequent,
            'support' => $rule['support'],
            'confidence' => $rule['confidence'],
            'lift' => $rule['lift'],
            'explanation' => $explanation
        ];
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => $formattedRules,
        'stats' => [
            'total_transactions' => $totalTransactions,
            'total_rules' => count($formattedRules),
            'min_support' => $minSupport,
            'min_confidence' => $minConfidence,
            'min_lift' => $minLift
        ]
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

