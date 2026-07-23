<?php
require_once __DIR__ . '/db.php';

$db = getDB();

$ENC_KEY = 'Kz/9xZWod/gk19dygcK8YB5eqTOcYJRcLhaatOO8MlE=';
$ENC_IV = 'caN/9YpLp6JQa2JrCgCZMQ==';

function aes_encrypt(string $plaintext): string {
    global $ENC_KEY, $ENC_IV;
    $key = base64_decode($ENC_KEY);
    $iv = base64_decode($ENC_IV);
    $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted);
}

function aes_decrypt(string $encoded): string {
    global $ENC_KEY, $ENC_IV;
    $key = base64_decode($ENC_KEY);
    $iv = base64_decode($ENC_IV);
    $ciphertext = base64_decode($encoded);
    $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : '';
}

function encryptResponse(array $data): string {
    return json_encode(['data' => aes_encrypt(json_encode($data))]);
}

function jsonBadRequest($msg) {
    echo encryptResponse(['status' => 'error', 'message' => $msg]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $outer = json_decode($raw, true);

    if (isset($outer['data'])) {
        $decrypted = aes_decrypt($outer['data']);
        if (empty($decrypted)) {
            jsonBadRequest('Decryption failed');
        }
        $input = json_decode($decrypted, true);
        if (!$input) {
            jsonBadRequest('Invalid payload');
        }
    } else {
        $input = $outer;
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'check_key':
            $key = trim($input['key'] ?? '');
            if (empty($key)) {
                jsonBadRequest('No key provided');
            }

            $stmt = $db->prepare('SELECT k.id, k.dll_id, k.active, d.filename, d.name FROM keys k LEFT JOIN dlls d ON k.dll_id = d.id WHERE k.key_value = :key');
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch();

            if (!$row) {
                jsonBadRequest('Key not found');
            }
            if (!$row['active']) {
                jsonBadRequest('Key deactivated');
            }
            if (!$row['dll_id'] || !$row['filename']) {
                jsonBadRequest('No DLL assigned to this key');
            }
            if (!file_exists(UPLOADS_DIR . '/' . $row['filename'])) {
                jsonBadRequest('DLL file missing on server');
            }

            echo encryptResponse([
                'status' => 'ok',
                'message' => 'Key valid',
                'dll_name' => $row['name'],
                'download_url' => '/api.php?action=download&key=' . urlencode($key),
            ]);
            exit;

        default:
            jsonBadRequest('Unknown action');
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'download') {
    $key = trim($_GET['key'] ?? '');
    if (empty($key)) {
        http_response_code(403);
        exit('Forbidden');
    }

    $stmt = $db->prepare('SELECT k.key_value, k.active, d.filename, d.name FROM keys k LEFT JOIN dlls d ON k.dll_id = d.id WHERE k.key_value = :key');
    $stmt->execute([':key' => $key]);
    $row = $stmt->fetch();

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
}

http_response_code(400);
echo json_encode(['error' => 'Bad request']);
