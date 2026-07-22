<?php
require_once __DIR__ . '/db.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'check_key':
        $key = trim($input['key'] ?? '');
        if (empty($key)) {
            jsonResponse(['status' => 'error', 'message' => 'No key provided']);
        }

        $stmt = $db->prepare('SELECT k.id, k.dll_id, k.active, d.filename, d.name FROM keys k LEFT JOIN dlls d ON k.dll_id = d.id WHERE k.key_value = :key');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if (!$row) {
            jsonResponse(['status' => 'error', 'message' => 'Key not found']);
        }
        if (!$row['active']) {
            jsonResponse(['status' => 'error', 'message' => 'Key deactivated']);
        }
        if (!$row['dll_id'] || !$row['filename']) {
            jsonResponse(['status' => 'error', 'message' => 'No DLL assigned to this key']);
        }
        if (!file_exists(UPLOADS_DIR . '/' . $row['filename'])) {
            jsonResponse(['status' => 'error', 'message' => 'DLL file missing on server']);
        }

        jsonResponse([
            'status' => 'ok',
            'message' => 'Key valid',
            'dll_name' => $row['name'],
            'download_url' => '/api.php?action=download&key=' . urlencode($key),
        ]);
        break;

    case 'download':
        $key = trim($_GET['key'] ?? '');
        if (empty($key)) {
            http_response_code(403);
            exit('Forbidden');
        }

        $stmt = $db->prepare('SELECT k.key_value, k.active, d.filename, d.name FROM keys k LEFT JOIN dlls d ON k.dll_id = d.id WHERE k.key_value = :key');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if (!$row || !$row['active'] || !$row['filename']) {
            http_response_code(403);
            exit('Forbidden');
        }

        $filepath = UPLOADS_DIR . '/' . $row['filename'];
        if (!file_exists($filepath)) {
            http_response_code(404);
            exit('File not found');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $row['name'] . '.dll"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-store, no-cache');
        readfile($filepath);
        exit;

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
