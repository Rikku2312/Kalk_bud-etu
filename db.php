<?php
// ============================================================
// db.php — Połączenie z bazą danych MySQL
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // domyślny użytkownik XAMPP
define('DB_PASS', '');            // domyślne hasło XAMPP (puste)
define('DB_NAME', 'kalk_budget');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Błąd połączenia z bazą danych: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
