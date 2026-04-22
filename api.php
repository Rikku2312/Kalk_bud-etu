<?php
// ============================================================
// api.php — REST API dla aplikacji budżetu domowego
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'db.php';

$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id       = isset($_GET['id']) ? (int)$_GET['id'] : null;
$input    = json_decode(file_get_contents('php://input'), true) ?? [];

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function err(string $msg, int $code = 400): void {
    respond(['error' => $msg], $code);
}

try {
    $db = getDB();

    // ── TRANSACTIONS ─────────────────────────────────────────
    if ($resource === 'transactions') {

        if ($method === 'GET') {
            $where  = [];
            $params = [];

            if (!empty($_GET['type'])) {
                $where[] = 't.type = ?';
                $params[] = $_GET['type'];
            }
            if (!empty($_GET['category_id'])) {
                $where[] = 't.category_id = ?';
                $params[] = (int)$_GET['category_id'];
            }
            if (!empty($_GET['month']) && !empty($_GET['year'])) {
                $where[] = 'MONTH(t.date) = ? AND YEAR(t.date) = ?';
                $params[] = (int)$_GET['month'];
                $params[] = (int)$_GET['year'];
            }
            if (!empty($_GET['date_from'])) {
                $where[] = 't.date >= ?';
                $params[] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $where[] = 't.date <= ?';
                $params[] = $_GET['date_to'];
            }
            if (!empty($_GET['search'])) {
                $where[] = 't.description LIKE ?';
                $params[] = '%' . $_GET['search'] . '%';
            }

            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $limit    = min((int)($_GET['limit'] ?? 50), 500);
            $offset   = (int)($_GET['offset'] ?? 0);

            $sql = "SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
                    FROM transactions t
                    LEFT JOIN categories c ON t.category_id = c.id
                    $whereSQL
                    ORDER BY t.date DESC, t.id DESC
                    LIMIT $limit OFFSET $offset";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // total count
            $cntStmt = $db->prepare("SELECT COUNT(*) FROM transactions t $whereSQL");
            $cntStmt->execute($params);
            $total = (int)$cntStmt->fetchColumn();

            respond(['data' => $rows, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        }

        if ($method === 'POST') {
            $required = ['type','amount','date'];
            foreach ($required as $f) {
                if (empty($input[$f])) err("Pole '$f' jest wymagane.");
            }
            if (!in_array($input['type'], ['income','expense'])) err('Nieprawidłowy typ transakcji.');
            if ((float)$input['amount'] <= 0) err('Kwota musi być dodatnia.');

            $stmt = $db->prepare("INSERT INTO transactions (category_id, type, amount, description, date, note)
                                  VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $input['category_id'] ?? null,
                $input['type'],
                (float)$input['amount'],
                trim($input['description'] ?? ''),
                $input['date'],
                trim($input['note'] ?? ''),
            ]);
            respond(['id' => $db->lastInsertId(), 'message' => 'Transakcja dodana.'], 201);
        }

        if ($method === 'PUT' && $id) {
            $stmt = $db->prepare("UPDATE transactions SET category_id=?, type=?, amount=?, description=?, date=?, note=? WHERE id=?");
            $stmt->execute([
                $input['category_id'] ?? null,
                $input['type'],
                (float)$input['amount'],
                trim($input['description'] ?? ''),
                $input['date'],
                trim($input['note'] ?? ''),
                $id,
            ]);
            respond(['message' => 'Transakcja zaktualizowana.']);
        }

        if ($method === 'DELETE' && $id) {
            $db->prepare("DELETE FROM transactions WHERE id=?")->execute([$id]);
            respond(['message' => 'Transakcja usunięta.']);
        }
    }

    // ── CATEGORIES ───────────────────────────────────────────
    if ($resource === 'categories') {

        if ($method === 'GET') {
            $type = $_GET['type'] ?? null;
            if ($type) {
                $stmt = $db->prepare("SELECT * FROM categories WHERE type=? ORDER BY name");
                $stmt->execute([$type]);
            } else {
                $stmt = $db->query("SELECT * FROM categories ORDER BY type, name");
            }
            respond(['data' => $stmt->fetchAll()]);
        }

        if ($method === 'POST') {
            if (empty($input['name'])) err('Nazwa kategorii jest wymagana.');
            if (!in_array($input['type'] ?? '', ['income','expense'])) err('Nieprawidłowy typ kategorii.');
            $stmt = $db->prepare("INSERT INTO categories (name, type, icon, color) VALUES (?,?,?,?)");
            $stmt->execute([
                trim($input['name']),
                $input['type'],
                $input['icon']  ?? '💰',
                $input['color'] ?? '#6366f1',
            ]);
            respond(['id' => $db->lastInsertId(), 'message' => 'Kategoria dodana.'], 201);
        }

        if ($method === 'PUT' && $id) {
            $stmt = $db->prepare("UPDATE categories SET name=?, type=?, icon=?, color=? WHERE id=?");
            $stmt->execute([trim($input['name']), $input['type'], $input['icon'] ?? '💰', $input['color'] ?? '#6366f1', $id]);
            respond(['message' => 'Kategoria zaktualizowana.']);
        }

        if ($method === 'DELETE' && $id) {
            $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
            respond(['message' => 'Kategoria usunięta.']);
        }
    }

    // ── BUDGETS ──────────────────────────────────────────────
    if ($resource === 'budgets') {

        if ($method === 'GET') {
            $month = (int)($_GET['month'] ?? date('n'));
            $year  = (int)($_GET['year']  ?? date('Y'));

            $stmt = $db->prepare("
                SELECT b.*, c.name AS category_name, c.icon, c.color,
                       COALESCE((
                           SELECT SUM(amount) FROM transactions
                           WHERE category_id = b.category_id
                             AND MONTH(date) = b.month
                             AND YEAR(date)  = b.year
                             AND type = 'expense'
                       ), 0) AS spent
                FROM budgets b
                JOIN categories c ON b.category_id = c.id
                WHERE b.month = ? AND b.year = ?
                ORDER BY c.name
            ");
            $stmt->execute([$month, $year]);
            respond(['data' => $stmt->fetchAll(), 'month' => $month, 'year' => $year]);
        }

        if ($method === 'POST') {
            if (empty($input['category_id']) || empty($input['amount'])) err('Wymagane: category_id, amount.');
            $month = (int)($input['month'] ?? date('n'));
            $year  = (int)($input['year']  ?? date('Y'));

            $stmt = $db->prepare("INSERT INTO budgets (category_id, amount, month, year)
                                  VALUES (?,?,?,?)
                                  ON DUPLICATE KEY UPDATE amount=VALUES(amount)");
            $stmt->execute([$input['category_id'], (float)$input['amount'], $month, $year]);
            respond(['message' => 'Budżet zapisany.'], 201);
        }

        if ($method === 'DELETE' && $id) {
            $db->prepare("DELETE FROM budgets WHERE id=?")->execute([$id]);
            respond(['message' => 'Budżet usunięty.']);
        }
    }

    // ── SAVINGS GOALS ────────────────────────────────────────
    if ($resource === 'savings') {

        if ($method === 'GET') {
            $rows = $db->query("SELECT * FROM savings_goals ORDER BY created_at DESC")->fetchAll();
            respond(['data' => $rows]);
        }

        if ($method === 'POST') {
            if (empty($input['name']) || empty($input['target_amount'])) err('Wymagane: name, target_amount.');
            $stmt = $db->prepare("INSERT INTO savings_goals (name, target_amount, saved_amount, deadline, icon, color)
                                  VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                trim($input['name']),
                (float)$input['target_amount'],
                (float)($input['saved_amount'] ?? 0),
                $input['deadline'] ?? null,
                $input['icon']  ?? '🎯',
                $input['color'] ?? '#10b981',
            ]);
            respond(['id' => $db->lastInsertId(), 'message' => 'Cel dodany.'], 201);
        }

        if ($method === 'PUT' && $id) {
            $stmt = $db->prepare("UPDATE savings_goals SET name=?, target_amount=?, saved_amount=?, deadline=?, icon=?, color=? WHERE id=?");
            $stmt->execute([
                trim($input['name']),
                (float)$input['target_amount'],
                (float)($input['saved_amount'] ?? 0),
                $input['deadline'] ?? null,
                $input['icon']  ?? '🎯',
                $input['color'] ?? '#10b981',
                $id,
            ]);
            respond(['message' => 'Cel zaktualizowany.']);
        }

        if ($method === 'DELETE' && $id) {
            $db->prepare("DELETE FROM savings_goals WHERE id=?")->execute([$id]);
            respond(['message' => 'Cel usunięty.']);
        }
    }

    // ── STATS / DASHBOARD ─────────────────────────────────────
    if ($resource === 'stats') {
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));

        // Suma przychodów i wydatków bieżącego miesiąca
        $summaryStmt = $db->prepare("
            SELECT type, SUM(amount) AS total
            FROM transactions
            WHERE MONTH(date) = ? AND YEAR(date) = ?
            GROUP BY type
        ");
        $summaryStmt->execute([$month, $year]);
        $summary = ['income' => 0, 'expense' => 0];
        foreach ($summaryStmt->fetchAll() as $row) {
            $summary[$row['type']] = (float)$row['total'];
        }
        $summary['balance'] = $summary['income'] - $summary['expense'];

        // Wydatki per kategoria (bieżący miesiąc)
        $catStmt = $db->prepare("
            SELECT c.name, c.icon, c.color, t.type, SUM(t.amount) AS total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE MONTH(t.date) = ? AND YEAR(t.date) = ?
            GROUP BY t.category_id, t.type
            ORDER BY total DESC
        ");
        $catStmt->execute([$month, $year]);
        $byCategory = $catStmt->fetchAll();

        // Trend 12 miesięcy
        $trendStmt = $db->prepare("
            SELECT YEAR(date) AS y, MONTH(date) AS m, type, SUM(amount) AS total
            FROM transactions
            WHERE date >= DATE_SUB(?, INTERVAL 11 MONTH)
            GROUP BY y, m, type
            ORDER BY y, m
        ");
        $trendStmt->execute(["$year-$month-01"]);
        $trend = $trendStmt->fetchAll();

        // Ostatnie 5 transakcji
        $recentStmt = $db->prepare("
            SELECT t.*, c.name AS category_name, c.icon, c.color
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            ORDER BY t.date DESC, t.id DESC
            LIMIT 5
        ");
        $recentStmt->execute();
        $recent = $recentStmt->fetchAll();

        respond([
            'summary'     => $summary,
            'by_category' => $byCategory,
            'trend'       => $trend,
            'recent'      => $recent,
            'month'       => $month,
            'year'        => $year,
        ]);
    }

    err('Nieznany zasób API.', 404);

} catch (PDOException $e) {
    err('Błąd bazy danych: ' . $e->getMessage(), 500);
}
