<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    if ($pass === ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        header('Location: admin.php');
        exit;
    }
    $error = 'Wrong password';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff41; font-family: 'Consolas', monospace; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-box { background: #111; border: 1px solid #00ff41; padding: 40px; border-radius: 4px; width: 350px; }
        .login-box h1 { text-align: center; margin-bottom: 20px; font-size: 24px; text-shadow: 0 0 10px #00ff41; }
        .login-box input { width: 100%; padding: 10px; background: #0a0a0a; border: 1px solid #00ff41; color: #00ff41; font-family: 'Consolas', monospace; font-size: 14px; margin-bottom: 15px; outline: none; }
        .login-box input:focus { box-shadow: 0 0 5px #00ff41; }
        .login-box button { width: 100%; padding: 10px; background: #00ff41; color: #0a0a0a; border: none; font-family: 'Consolas', monospace; font-size: 14px; font-weight: bold; cursor: pointer; }
        .login-box button:hover { background: #00cc33; }
        .error { color: #ff4444; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>// PANEL</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Password" autofocus>
            <button type="submit">LOGIN</button>
        </form>
    </div>
</body>
</html>
