<?php
require_once __DIR__ . '/csrf.php';

function require_admin_auth(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login');
        exit;
    }
    // Regenerate session ID periodically to prevent fixation attacks
    if (empty($_SESSION['last_regen']) || (time() - $_SESSION['last_regen']) > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    }
}

function admin_login(PDO $pdo, string $username, string $password): bool {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_user'] = trim($username);
        $_SESSION['last_regen'] = time();
        $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
        return true;
    }
    return false;
}

function admin_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: /admin/login');
    exit;
}
