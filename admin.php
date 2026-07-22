<?php
require_once __DIR__ . '/db.php';
requireLogin();

$db = getDB();
$message = '';

// Add DLL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dll'])) {
    $name = trim($_POST['dll_name'] ?? '');
    if (!empty($_FILES['dll_file']['name']) && $name) {
        $ext = strtolower(pathinfo($_FILES['dll_file']['name'], PATHINFO_EXTENSION));
        if ($ext === 'dll') {
            $filename = bin2hex(random_bytes(16)) . '.dll';
            if (move_uploaded_file($_FILES['dll_file']['tmp_name'], UPLOADS_DIR . '/' . $filename)) {
                $stmt = $db->prepare('INSERT INTO dlls (name, filename) VALUES (:name, :filename)');
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
                $stmt->execute();
                $message = 'DLL uploaded: ' . htmlspecialchars($name);
            } else {
                $message = 'Upload failed';
            }
        } else {
            $message = 'Only .dll files allowed';
        }
    } else {
        $message = 'Provide name and file';
    }
}

// Delete DLL
if (isset($_GET['delete_dll'])) {
    $id = (int) $_GET['delete_dll'];
    $stmt = $db->prepare('SELECT filename FROM dlls WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $path = UPLOADS_DIR . '/' . $row['filename'];
        if (file_exists($path)) unlink($path);
        $db->exec('DELETE FROM dlls WHERE id = ' . $id);
        $db->exec('UPDATE keys SET dll_id = NULL WHERE dll_id = ' . $id);
        $message = 'DLL deleted';
    }
}

// Replace DLL file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replace_dll'])) {
    $id = (int) $_POST['dll_id'];
    if (!empty($_FILES['new_dll_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['new_dll_file']['name'], PATHINFO_EXTENSION));
        if ($ext === 'dll') {
            $stmt = $db->prepare('SELECT filename FROM dlls WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                $old_path = UPLOADS_DIR . '/' . $row['filename'];
                if (file_exists($old_path)) unlink($old_path);
                $new_filename = bin2hex(random_bytes(16)) . '.dll';
                move_uploaded_file($_FILES['new_dll_file']['tmp_name'], UPLOADS_DIR . '/' . $new_filename);
                $stmt2 = $db->prepare('UPDATE dlls SET filename = :fn WHERE id = :id');
                $stmt2->bindValue(':fn', $new_filename, SQLITE3_TEXT);
                $stmt2->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt2->execute();
                $message = 'DLL file replaced';
            }
        }
    }
}

// Add key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_key'])) {
    $key = trim($_POST['key_value'] ?? '');
    $dll_id = (int) ($_POST['assign_dll'] ?? 0);
    if ($key) {
        try {
            $stmt = $db->prepare('INSERT INTO keys (key_value, dll_id) VALUES (:key, :dll_id)');
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':dll_id', $dll_id > 0 ? $dll_id : null, $dll_id > 0 ? SQLITE3_INTEGER : SQLITE3_NULL);
            $stmt->execute();
            $message = 'Key added: ' . htmlspecialchars($key);
        } catch (Exception $e) {
            $message = 'Key already exists';
        }
    }
}

// Delete key
if (isset($_GET['delete_key'])) {
    $id = (int) $_GET['delete_key'];
    $db->exec('DELETE FROM keys WHERE id = ' . $id);
    $message = 'Key deleted';
}

// Toggle key
if (isset($_GET['toggle_key'])) {
    $id = (int) $_GET['toggle_key'];
    $db->exec('UPDATE keys SET active = 1 - active WHERE id = ' . $id);
    $message = 'Key status toggled';
}

// Edit key value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_key'])) {
    $id = (int) $_POST['key_id'];
    $new_value = trim($_POST['new_key_value'] ?? '');
    if ($new_value) {
        try {
            $stmt = $db->prepare('UPDATE keys SET key_value = :kv WHERE id = :id');
            $stmt->bindValue(':kv', $new_value, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            $message = 'Key updated';
        } catch (Exception $e) {
            $message = 'Key value already exists';
        }
    }
}

// Reassign key DLL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_key'])) {
    $id = (int) $_POST['key_id'];
    $dll_id = (int) ($_POST['new_dll_id'] ?? 0);
    $stmt = $db->prepare('UPDATE keys SET dll_id = :dll_id WHERE id = :id');
    $stmt->bindValue(':dll_id', $dll_id > 0 ? $dll_id : null, $dll_id > 0 ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    $message = 'Key reassigned';
}

$dllResult = $db->query('SELECT * FROM dlls ORDER BY uploaded_at DESC');
$keysResult = $db->query('SELECT k.*, d.name as dll_name FROM keys k LEFT JOIN dlls d ON k.dll_id = d.id ORDER BY k.created_at DESC');
$dllList = $db->query('SELECT * FROM dlls ORDER BY name');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff41; font-family: 'Consolas', monospace; padding: 20px; }
        h1, h2 { text-shadow: 0 0 10px #00ff41; margin-bottom: 15px; }
        h1 { font-size: 28px; border-bottom: 1px solid #00ff41; padding-bottom: 10px; margin-bottom: 20px; }
        h2 { font-size: 18px; margin-top: 30px; }
        .msg { background: #111; border: 1px solid #00ff41; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .section { background: #111; border: 1px solid #333; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #222; }
        th { color: #00ff41; }
        input[type="text"], input[type="password"], select { padding: 8px; background: #0a0a0a; border: 1px solid #00ff41; color: #00ff41; font-family: 'Consolas', monospace; font-size: 13px; outline: none; }
        input[type="text"]:focus, input[type="password"]:focus { box-shadow: 0 0 5px #00ff41; }
        input[type="file"] { color: #00ff41; font-family: 'Consolas', monospace; }
        button, .btn { padding: 8px 16px; background: #00ff41; color: #0a0a0a; border: none; font-family: 'Consolas', monospace; font-size: 13px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        button:hover, .btn:hover { background: #00cc33; }
        .btn-red { background: #ff4444; }
        .btn-red:hover { background: #cc0000; }
        .btn-blue { background: #4444ff; }
        .btn-blue:hover { background: #0000cc; }
        .btn-small { padding: 4px 8px; font-size: 11px; }
        form.inline { display: inline; }
        .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 10px; }
        a { color: #00ff41; }
        .active { color: #00ff41; }
        .inactive { color: #ff4444; }
    </style>
</head>
<body>
    <h1>// ADMIN PANEL</h1>

    <?php if ($message): ?>
        <div class="msg"><?= $message ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>DLLs</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <input type="text" name="dll_name" placeholder="DLL name" required>
                <input type="file" name="dll_file" accept=".dll" required>
                <button type="submit" name="add_dll" value="1">UPLOAD</button>
            </div>
        </form>
        <table>
            <tr><th>ID</th><th>Name</th><th>File</th><th>Date</th><th>Actions</th></tr>
            <?php
            $hasDlls = false;
            while ($row = $dllResult->fetchArray(SQLITE3_ASSOC)):
                $hasDlls = true;
            ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['filename']) ?></td>
                    <td><?= $row['uploaded_at'] ?></td>
                    <td>
                        <a href="?delete_dll=<?= $row['id'] ?>" class="btn btn-red btn-small" onclick="return confirm('Delete this DLL?')">DELETE</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$hasDlls): ?>
                <tr><td colspan="5">No DLLs uploaded</td></tr>
            <?php endif; ?>
        </table>

        <h2 style="margin-top:20px">Replace DLL file</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <select name="dll_id" required>
                    <option value="">Select DLL...</option>
                    <?php
                    $dllListR = $db->query('SELECT * FROM dlls ORDER BY name');
                    while ($d = $dllListR->fetchArray(SQLITE3_ASSOC)):
                    ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="file" name="new_dll_file" accept=".dll" required>
                <button type="submit" name="replace_dll" value="1">REPLACE FILE</button>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Keys</h2>
        <form method="POST">
            <div class="row">
                <input type="text" name="key_value" placeholder="Key value" required>
                <select name="assign_dll">
                    <option value="0">No DLL</option>
                    <?php
                    $dllListR2 = $db->query('SELECT * FROM dlls ORDER BY name');
                    while ($d = $dllListR2->fetchArray(SQLITE3_ASSOC)):
                    ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_key" value="1">ADD KEY</button>
            </div>
        </form>
        <table>
            <tr><th>ID</th><th>Key</th><th>Assigned DLL</th><th>Status</th><th>Created</th><th>Actions</th></tr>
            <?php
            $hasKeys = false;
            while ($row = $keysResult->fetchArray(SQLITE3_ASSOC)):
                $hasKeys = true;
            ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <form method="POST" class="inline">
                            <input type="hidden" name="key_id" value="<?= $row['id'] ?>">
                            <input type="text" name="new_key_value" value="<?= htmlspecialchars($row['key_value']) ?>" style="width:200px">
                            <button type="submit" name="edit_key" value="1" class="btn-small">SAVE</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" class="inline">
                            <input type="hidden" name="key_id" value="<?= $row['id'] ?>">
                            <select name="new_dll_id" style="width:150px">
                                <option value="0" <?= !$row['dll_id'] ? 'selected' : '' ?>>No DLL</option>
                                <?php
                                $dllListR3 = $db->query('SELECT * FROM dlls ORDER BY name');
                                while ($d = $dllListR3->fetchArray(SQLITE3_ASSOC)):
                                ?>
                                    <option value="<?= $d['id'] ?>" <?= $row['dll_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" name="reassign_key" value="1" class="btn-small">SET</button>
                        </form>
                    </td>
                    <td>
                        <a href="?toggle_key=<?= $row['id'] ?>" class="<?= $row['active'] ? 'active' : 'inactive' ?>">
                            <?= $row['active'] ? 'ACTIVE' : 'DISABLED' ?>
                        </a>
                    </td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <a href="?delete_key=<?= $row['id'] ?>" class="btn btn-red btn-small" onclick="return confirm('Delete this key?')">DEL</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$hasKeys): ?>
                <tr><td colspan="6">No keys created</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div style="margin-top:20px">
        <a href="logout.php" class="btn btn-red">LOGOUT</a>
    </div>
</body>
</html>
