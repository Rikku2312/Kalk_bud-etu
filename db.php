<?php
// ============================================================
// db.php — Przechowywanie danych w pliku JSON
// ============================================================

// Pobranie lub wygenerowanie ID sesji z ciasteczka
$session_id = $_COOKIE['budget_session_id'] ?? null;
if (!$session_id) {
    $session_id = bin2hex(random_bytes(8));
    // Ustawiamy ciasteczko na rok
    setcookie('budget_session_id', $session_id, time() + (86400 * 365), "/");
}

define('STORAGE_DIR', __DIR__ . '/storage');
define('DATA_FILE', STORAGE_DIR . "/data_{$session_id}.json");

// Upewnienie się, że katalog istnieje
if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0777, true);
}

/**
 * Ładuje wszystkie dane z pliku JSON.
 */
function loadData(): array {
    if (!file_exists(DATA_FILE)) {
        return initializeData();
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    if (!$data) return initializeData();
    return $data;
}

/**
 * Zapisuje dane do pliku JSON.
 */
function saveData(array $data): void {
    file_put_contents(DATA_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * Inicjalizuje domyślne dane.
 */
function initializeData(): array {
    $defaultData = [
        'categories' => [
            ['id' => 1,  'name' => 'Wynagrodzenie',  'type' => 'income',  'icon' => '💼', 'color' => '#10b981'],
            ['id' => 2,  'name' => 'Freelance',      'type' => 'income',  'icon' => '💻', 'color' => '#06b6d4'],
            ['id' => 3,  'name' => 'Inwestycje',     'type' => 'income',  'icon' => '📈', 'color' => '#8b5cf6'],
            ['id' => 4,  'name' => 'Inne przychody', 'type' => 'income',  'icon' => '💰', 'color' => '#f59e0b'],
            ['id' => 5,  'name' => 'Jedzenie',       'type' => 'expense', 'icon' => '🍕', 'color' => '#ef4444'],
            ['id' => 6,  'name' => 'Transport',      'type' => 'expense', 'icon' => '🚗', 'color' => '#f97316'],
            ['id' => 7,  'name' => 'Mieszkanie',     'type' => 'expense', 'icon' => '🏠', 'color' => '#eab308'],
            ['id' => 8,  'name' => 'Rozrywka',       'type' => 'expense', 'icon' => '🎮', 'color' => '#a855f7'],
            ['id' => 9,  'name' => 'Zdrowie',        'type' => 'expense', 'icon' => '💊', 'color' => '#ec4899'],
            ['id' => 10, 'name' => 'Ubrania',        'type' => 'expense', 'icon' => '👗', 'color' => '#14b8a6'],
            ['id' => 11, 'name' => 'Edukacja',       'type' => 'expense', 'icon' => '📚', 'color' => '#3b82f6'],
            ['id' => 12, 'name' => 'Inne wydatki',   'type' => 'expense', 'icon' => '🛒', 'color' => '#6b7280'],
        ],
        'transactions' => [
            [
                'id'          => 1,
                'category_id' => 4, // Inne przychody
                'type'        => 'income',
                'amount'      => 10000.0,
                'description' => 'Bilans początkowy',
                'date'        => date('Y-m-d'),
                'note'        => 'Automatyczny bilans początkowy',
                'created_at'  => date('Y-m-d H:i:s')
            ]
        ],
        'budgets' => [],
        'savings_goals' => [],
        'next_ids' => [
            'categories' => 13,
            'transactions' => 2,
            'budgets' => 1,
            'savings_goals' => 1
        ]
    ];
    saveData($defaultData);
    return $defaultData;
}

/**
 * Generuje nowe ID dla danej kolekcji.
 */
function nextId(array &$data, string $collection): int {
    $id = $data['next_ids'][$collection];
    $data['next_ids'][$collection]++;
    return $id;
}
