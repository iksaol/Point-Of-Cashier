<?php
require_once __DIR__ . '/includes/helpers.php';
if (!empty($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $user = db_row('SELECT id_user, nama, username, password, role FROM users WHERE username = :username LIMIT 1', ['username'=>$username]);
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . BASE_URL . 'index.php'); exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Point Of Cashier</title><link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
<style>body{min-height:100vh;display:grid;place-items:center;background:#f0f5fb}.login-card{width:min(420px,calc(100% - 32px));background:#fff;padding:32px;border-radius:16px;box-shadow:0 12px 40px #19365b18}.login-card h1{margin-top:0}.login-card label{display:block;margin:16px 0 6px}.login-card input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #ccd6e3;border-radius:8px}.login-card button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:8px;background:#174a83;color:white;font-weight:700;cursor:pointer}.login-error{padding:10px;background:#fff0f0;color:#a11;border-radius:8px}</style></head><body>
<form class="login-card" method="post" autocomplete="on"><h1>Masuk ke Point Of Cashier</h1><p>Silakan masuk menggunakan akun Anda.</p>
<?php if ($error): ?><p class="login-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<label for="username">Username</label><input id="username" name="username" required autocomplete="username" autofocus>
<label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password">
<button type="submit">Login</button></form></body></html>
