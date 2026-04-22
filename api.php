<?php
require_once 'db.php';
// ============================================================
// api.php — REST API dla aplikacji budżetu domowego (JSON)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

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
    $allData = loadData();

    // ── TRANSACTIONS ─────────────────────────────────────────
    if ($resource === 'transactions') {
        if ($method === 'GET') {
            $transactions = $allData['transactions'];
            $categories   = $allData['categories'];
            $catMap       = [];
            foreach ($categories as $c) $catMap[$c['id']] = $c;

            // Enrich with category info
            foreach ($transactions as &$t) {
                $c = $catMap[$t['category_id'] ?? 0] ?? null;
                $t['category_name']  = $c ? $c['name']  : 'Brak';
                $t['category_icon']  = $c ? $c['icon']  : '💰';
                $t['category_color'] = $c ? $c['color'] : '#666';
            }
            unset($t);

            // Filtering
            $transactions = array_filter($transactions, function($t) {
                if (!empty($_GET['type']) && $t['type'] !== $_GET['type']) return false;
                if (!empty($_GET['category_id']) && $t['category_id'] != $_GET['category_id']) return false;
                if (!empty($_GET['month']) && !empty($_GET['year'])) {
                    $d = strtotime($t['date']);
                    if (date('n', $d) != $_GET['month'] || date('Y', $d) != $_GET['year']) return false;
                }
                if (!empty($_GET['date_from']) && $t['date'] < $_GET['date_from']) return false;
                if (!empty($_GET['date_to']) && $t['date'] > $_GET['date_to']) return false;
                if (!empty($_GET['search'])) {
                    if (stripos($t['description'] ?? '', $_GET['search']) === false) return false;
                }
                return true;
            });

            // Sorting (newest first)
            usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']) ?: ($b['id'] - $a['id']));

            $total  = count($transactions);
            $limit  = min((int)($_GET['limit'] ?? 50), 500);
            $offset = (int)($_GET['offset'] ?? 0);
            $paged  = array_slice($transactions, $offset, $limit);

            respond(['data' => $paged, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        }

        if ($method === 'POST') {
            $required = ['type','amount','date'];
            foreach ($required as $f) {
                if (empty($input[$f])) err("Pole '$f' jest wymagane.");
            }
            $newTransaction = [
                'id'          => nextId($allData, 'transactions'),
                'category_id' => isset($input['category_id']) ? (int)$input['category_id'] : null,
                'type'        => $input['type'],
                'amount'      => (float)$input['amount'],
                'description' => trim($input['description'] ?? ''),
                'date'        => $input['date'],
                'note'        => trim($input['note'] ?? ''),
                'created_at'  => date('Y-m-d H:i:s')
            ];
            $allData['transactions'][] = $newTransaction;
            saveData($allData);
            respond(['id' => $newTransaction['id'], 'message' => 'Transakcja dodana.'], 201);
        }

        if ($method === 'PUT' && $id) {
            $found = false;
            foreach ($allData['transactions'] as &$t) {
                if ($t['id'] === $id) {
                    $t['category_id'] = isset($input['category_id']) ? (int)$input['category_id'] : $t['category_id'];
                    $t['type']        = $input['type'] ?? $t['type'];
                    $t['amount']      = isset($input['amount']) ? (float)$input['amount'] : $t['amount'];
                    $t['description'] = trim($input['description'] ?? $t['description']);
                    $t['date']        = $input['date'] ?? $t['date'];
                    $t['note']        = trim($input['note'] ?? $t['note']);
                    $found = true;
                    break;
                }
            }
            if ($found) {
                saveData($allData);
                respond(['message' => 'Transakcja zaktualizowana.']);
            }
            err('Nie znaleziono transakcji.', 404);
        }

        if ($method === 'DELETE' && $id) {
            $allData['transactions'] = array_filter($allData['transactions'], fn($t) => $t['id'] !== $id);
            saveData($allData);
            respond(['message' => 'Transakcja usunięta.']);
        }
    }

    // ── CATEGORIES ───────────────────────────────────────────
    if ($resource === 'categories') {
        if ($method === 'GET') {
            $type = $_GET['type'] ?? null;
            $cats = $allData['categories'];
            if ($type) {
                $cats = array_filter($cats, fn($c) => $c['type'] === $type);
            }
            usort($cats, fn($a, $b) => strcmp($a['name'], $b['name']));
            respond(['data' => array_values($cats)]);
        }

        if ($method === 'POST') {
            if (empty($input['name'])) err('Nazwa kategorii jest wymagana.');
            $newCat = [
                'id'    => nextId($allData, 'categories'),
                'name'  => trim($input['name']),
                'type'  => $input['type'],
                'icon'  => $input['icon']  ?? '💰',
                'color' => $input['color'] ?? '#6366f1'
            ];
            $allData['categories'][] = $newCat;
            saveData($allData);
            respond(['id' => $newCat['id'], 'message' => 'Kategoria dodana.'], 201);
        }

        if ($method === 'PUT' && $id) {
            foreach ($allData['categories'] as &$c) {
                if ($c['id'] === $id) {
                    $c['name']  = trim($input['name'] ?? $c['name']);
                    $c['type']  = $input['type'] ?? $c['type'];
                    $c['icon']  = $input['icon'] ?? $c['icon'];
                    $c['color'] = $input['color'] ?? $c['color'];
                    saveData($allData);
                    respond(['message' => 'Kategoria zaktualizowana.']);
                }
            }
            err('Nie znaleziono kategorii.', 404);
        }

        if ($method === 'DELETE' && $id) {
            $allData['categories'] = array_filter($allData['categories'], fn($c) => $c['id'] !== $id);
            saveData($allData);
            respond(['message' => 'Kategoria usunięta.']);
        }
    }

    // ── BUDGETS ──────────────────────────────────────────────
    if ($resource === 'budgets') {
        if ($method === 'GET') {
            $month = (int)($_GET['month'] ?? date('n'));
            $year  = (int)($_GET['year']  ?? date('Y'));

            $budgets    = array_filter($allData['budgets'], fn($b) => $b['month'] == $month && $b['year'] == $year);
            $categories = $allData['categories'];
            $catMap     = [];
            foreach ($categories as $c) $catMap[$c['id']] = $c;

            $enriched = [];
            foreach ($budgets as $b) {
                $c = $catMap[$b['category_id']] ?? null;
                if (!$c) continue;
                
                // Calculate spent
                $spent = 0;
                foreach ($allData['transactions'] as $t) {
                    if ($t['category_id'] == $b['category_id'] && $t['type'] === 'expense') {
                        $td = strtotime($t['date']);
                        if (date('n', $td) == $month && date('Y', $td) == $year) {
                            $spent += $t['amount'];
                        }
                    }
                }

                $b['category_name'] = $c['name'];
                $b['icon']          = $c['icon'];
                $b['color']         = $c['color'];
                $b['spent']         = $spent;
                $enriched[] = $b;
            }
            respond(['data' => $enriched, 'month' => $month, 'year' => $year]);
        }

        if ($method === 'POST') {
            if (empty($input['category_id']) || empty($input['amount'])) err('Wymagane: category_id, amount.');
            $month = (int)($input['month'] ?? date('n'));
            $year  = (int)($input['year']  ?? date('Y'));
            $catId = (int)$input['category_id'];

            $found = false;
            foreach ($allData['budgets'] as &$b) {
                if ($b['category_id'] == $catId && $b['month'] == $month && $b['year'] == $year) {
                    $b['amount'] = (float)$input['amount'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $allData['budgets'][] = [
                    'id'          => nextId($allData, 'budgets'),
                    'category_id' => $catId,
                    'amount'      => (float)$input['amount'],
                    'month'       => $month,
                    'year'        => $year
                ];
            }
            saveData($allData);
            respond(['message' => 'Budżet zapisany.'], 201);
        }

        if ($method === 'DELETE' && $id) {
            $allData['budgets'] = array_filter($allData['budgets'], fn($b) => $b['id'] !== $id);
            saveData($allData);
            respond(['message' => 'Budżet usunięty.']);
        }
    }

    // ── SAVINGS GOALS ────────────────────────────────────────
    if ($resource === 'savings') {
        if ($method === 'GET') {
            $goals = $allData['savings_goals'];
            usort($goals, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
            respond(['data' => array_values($goals)]);
        }

        if ($method === 'POST') {
            if (empty($input['name']) || empty($input['target_amount'])) err('Wymagane: name, target_amount.');
            $newGoal = [
                'id'            => nextId($allData, 'savings_goals'),
                'name'          => trim($input['name']),
                'target_amount' => (float)$input['target_amount'],
                'saved_amount'  => (float)($input['saved_amount'] ?? 0),
                'deadline'      => $input['deadline'] ?? null,
                'icon'          => $input['icon']  ?? '🎯',
                'color'         => $input['color'] ?? '#10b981',
                'created_at'    => date('Y-m-d H:i:s')
            ];
            $allData['savings_goals'][] = $newGoal;
            saveData($allData);
            respond(['id' => $newGoal['id'], 'message' => 'Cel dodany.'], 201);
        }

        if ($method === 'PUT' && $id) {
            foreach ($allData['savings_goals'] as &$g) {
                if ($g['id'] === $id) {
                    $g['name']          = trim($input['name'] ?? $g['name']);
                    $g['target_amount'] = isset($input['target_amount']) ? (float)$input['target_amount'] : $g['target_amount'];
                    $g['saved_amount']  = isset($input['saved_amount']) ? (float)$input['saved_amount'] : $g['saved_amount'];
                    $g['deadline']      = $input['deadline'] ?? $g['deadline'];
                    $g['icon']          = $input['icon'] ?? $g['icon'];
                    $g['color']         = $input['color'] ?? $g['color'];
                    saveData($allData);
                    respond(['message' => 'Cel zaktualizowany.']);
                }
            }
            err('Nie znaleziono celu.', 404);
        }

        if ($method === 'DELETE' && $id) {
            $allData['savings_goals'] = array_filter($allData['savings_goals'], fn($g) => $g['id'] !== $id);
            saveData($allData);
            respond(['message' => 'Cel usunięty.']);
        }
    }

    // ── STATS / DASHBOARD ─────────────────────────────────────
    if ($resource === 'stats') {
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));

        $summary = ['income' => 0, 'expense' => 0];
        $byCategory = [];
        $catMap = [];
        foreach ($allData['categories'] as $c) $catMap[$c['id']] = $c;

        foreach ($allData['transactions'] as $t) {
            $td = strtotime($t['date']);
            $tm = (int)date('n', $td);
            $ty = (int)date('Y', $td);

            if ($tm === $month && $ty === $year) {
                $summary[$t['type']] += $t['amount'];

                $catId = $t['category_id'] ?? 0;
                $cat = $catMap[$catId] ?? ['name' => 'Inne', 'icon' => '💰', 'color' => '#666'];
                $key = $catId . '_' . $t['type'];
                if (!isset($byCategory[$key])) {
                    $byCategory[$key] = [
                        'name'  => $cat['name'],
                        'icon'  => $cat['icon'],
                        'color' => $cat['color'],
                        'type'  => $t['type'],
                        'total' => 0
                    ];
                }
                $byCategory[$key]['total'] += $t['amount'];
            }
        }
        $summary['balance'] = $summary['income'] - $summary['expense'];

        // Trend 12 months
        $trend = [];
        $startDate = strtotime("-11 months", strtotime("$year-$month-01"));
        for ($i = 0; $i < 12; $i++) {
            $m = (int)date('n', strtotime("+$i months", $startDate));
            $y = (int)date('Y', strtotime("+$i months", $startDate));
            
            $inc = 0; $exp = 0;
            foreach ($allData['transactions'] as $t) {
                $td = strtotime($t['date']);
                if ((int)date('n', $td) === $m && (int)date('Y', $td) === $y) {
                    if ($t['type'] === 'income') $inc += $t['amount'];
                    else $exp += $t['amount'];
                }
            }
            $trend[] = ['y' => $y, 'm' => $m, 'type' => 'income',  'total' => $inc];
            $trend[] = ['y' => $y, 'm' => $m, 'type' => 'expense', 'total' => $exp];
        }

        // Recent 5
        $recent = $allData['transactions'];
        foreach ($recent as &$r) {
            $c = $catMap[$r['category_id'] ?? 0] ?? null;
            $r['category_name'] = $c ? $c['name'] : 'Inne';
            $r['icon']          = $c ? $c['icon'] : '💰';
            $r['color']         = $c ? $c['color'] : '#666';
        }
        unset($r);
        usort($recent, fn($a, $b) => strcmp($b['date'], $a['date']) ?: ($b['id'] - $a['id']));
        $recent = array_slice($recent, 0, 5);

        respond([
            'summary'     => $summary,
            'by_category' => array_values($byCategory),
            'trend'       => $trend,
            'recent'      => $recent,
            'month'       => $month,
            'year'        => $year,
        ]);
    }

    err('Nieznany zasób API.', 404);

} catch (Exception $e) {
    err('Błąd serwera: ' . $e->getMessage(), 500);
}
