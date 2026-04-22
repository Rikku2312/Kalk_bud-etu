<?php
$_COOKIE['budget_session_id'] = 'test_session';
require_once 'db.php';
$data = loadData();
$data['transactions'][] = [
    'id' => nextId($data, 'transactions'),
    'category_id' => 5, // Jedzenie
    'type' => 'expense',
    'amount' => 50.0,
    'description' => 'Testowy lunch',
    'date' => date('Y-m-d'),
    'created_at' => date('Y-m-d H:i:s')
];
saveData($data);
echo "Transaction added. Total transactions: " . count($data['transactions']) . "\n";
?>
