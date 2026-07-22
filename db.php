<?php
session_start();

define('ADMIN_PASS', '201112161s');
define('UPLOADS_DIR', __DIR__ . '/uploads');

if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

function getDB(): PDO {
    $dsn = getenv('DATABASE_URL') ?: 'postgresql://postgres:vVTlGSrdycctZMJVZujeFqxecYtaSZDX@postgres.railway.internal:5432/railway';
    $db = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $db->exec('CREATE TABLE IF NOT EXISTS keys (
        id SERIAL PRIMARY KEY,
        key_value TEXT UNIQUE NOT NULL,
        dll_id INTEGER,
        active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS dlls (
        id SERIAL PRIMARY KEY,
        name TEXT NOT NULL,
        filename TEXT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    return $db;
}

function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function jsonResponse(array $data, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
