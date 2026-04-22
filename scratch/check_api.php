<?php
// scratch/check_api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "--- Testing DB ---\n";
require_once __DIR__ . '/../db.php';
try {
    $data = loadData();
    echo "Data loaded successfully. Transactions: " . count($data['transactions']) . "\n";
    echo "Session ID: " . $session_id . "\n";
    echo "Data file: " . DATA_FILE . "\n";
} catch (Throwable $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n--- Testing API Simulation (Stats) ---\n";
$_GET['resource'] = 'stats';
$_GET['month'] = date('n');
$_GET['year'] = date('Y');

// Capture output
ob_start();
try {
    include __DIR__ . '/../api.php';
} catch (Throwable $e) {
    echo "API Fatal Error: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "API Response:\n";
echo $output;
echo "\n--- End ---\n";
