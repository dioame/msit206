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

// Apriori Algorithm Implementation
class AprioriMining {
    private $transactions;
    private $minSupport;
    private $minConfidence;
    private $minLift;
    
    public function __construct($transactions, $minSupport = 0.1, $minConfidence = 0.6, $minLift = 1.2) {
        $this->transactions = $transactions;
        $this->minSupport = $minSupport;
        $this->minConfidence = $minConfidence;
        $this->minLift = $minLift;
    }
    
    // Get frequent itemsets
    public function getFrequentItemsets() {
        $frequentItemsets = [];
        $k = 1;
        
        // Generate 1-itemsets
        $candidates = $this->generateCandidates1();
        $frequent = $this->filterFrequent($candidates);
        
        if (empty($frequent)) {
            return [];
        }
        
        $frequentItemsets[$k] = $frequent;
        
        // Generate k-itemsets (k >= 2)
        while (!empty($frequent)) {
            $k++;
            $candidates = $this->generateCandidates($frequent, $k);
            $frequent = $this->filterFrequent($candidates);
            
            if (!empty($frequent)) {
                $frequentItemsets[$k] = $frequent;
            } else {
                break;
            }
        }
        
        return $frequentItemsets;
    }
    
    // Generate 1-itemset candidates
    private function generateCandidates1() {
        $items = [];
        foreach ($this->transactions as $transaction) {
            foreach ($transaction as $item) {
                if (!isset($items[$item])) {
                    $items[$item] = 0;
                }
                $items[$item]++;
            }
        }
        return $items;
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
    
    // Filter frequent itemsets
    private function filterFrequent($candidates) {
        $frequent = [];
        $totalTransactions = count($this->transactions);
        
        foreach ($candidates as $itemset => $count) {
            $items = is_array($itemset) ? $itemset : explode(',', $itemset);
            $support = $this->calculateSupport($items);
            
            if ($support >= $this->minSupport) {
                $frequent[implode(',', $items)] = $support;
            }
        }
        
        return $frequent;
    }
    
    // Calculate support for itemset
    private function calculateSupport($items) {
        $count = 0;
        foreach ($this->transactions as $transaction) {
            $found = true;
            foreach ($items as $item) {
                if (!in_array($item, $transaction)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                $count++;
            }
        }
        return $count / count($this->transactions);
    }
    
    // Generate association rules
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
    
    // Generate rules from an itemset
    private function generateRulesFromItemset($items, $itemsetSupport, &$rules) {
        $n = count($items);
        
        // Generate rules with different combinations
        for ($i = 1; $i < $n; $i++) {
            $combinations = $this->getCombinations($items, $i);
            
            foreach ($combinations as $antecedent) {
                $consequent = array_diff($items, $antecedent);
                
                if (empty($consequent)) continue;
                
                $antecedentSupport = $this->calculateSupport($antecedent);
                $consequentSupport = $this->calculateSupport($consequent);
                
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
    
    // Get assistance data grouped by assistance_id (transaction)
    $query = "SELECT 
        a.assistance_id,
        GROUP_CONCAT(DISTINCT a.fnfi_name ORDER BY a.fnfi_name SEPARATOR ',') as items
    FROM assistance a
    GROUP BY a.assistance_id
    HAVING COUNT(DISTINCT a.fnfi_name) > 1";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    // Prepare transactions
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $items = explode(',', $row['items']);
        $transactions[] = array_map('trim', $items);
    }
    
    $conn->close();
    
    if (empty($transactions)) {
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
    
    // Run Apriori algorithm
    $apriori = new AprioriMining($transactions, $minSupport, $minConfidence, $minLift);
    $frequentItemsets = $apriori->getFrequentItemsets();
    $rules = $apriori->generateRules($frequentItemsets);
    
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
            'total_transactions' => count($transactions),
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

