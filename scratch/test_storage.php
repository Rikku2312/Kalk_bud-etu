<?php
$_COOKIE['budget_session_id'] = 'test_session';
require_once 'db.php';
$data = loadData();
echo "Data loaded. Categories count: " . count($data['categories']) . "\n";
foreach($data['categories'] as $c) {
    echo "- {$c['name']} ({$c['type']})\n";
}
echo "Data file path: " . DATA_FILE . "\n";
?>
