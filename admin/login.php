<?php
require_once dirname(__DIR__) . '/backend/config/security.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/backend/config/database.php';
require_once dirname(__DIR__) . '/backend/config/auth.php';
require_once dirname(__DIR__) . '/backend/config/rate_limit.php';

// Already logged in? Go to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_rate_limit('admin_login', 5, 300);
    if (!verify_csrf_token()) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (admin_login($pdo, $username, $password)) {
            header('Location: /admin/dashboard');
            exit;
        } else {
            // Generic error — don't reveal whether username or password was wrong
            $error = 'Invalid credentials. Please try again.';
            usleep(random_int(100000, 300000)); // Timing-safe blunt brute force
        }
    }
}
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — NIIT ID System</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: #fff; border-radius: 16px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 4px 24px rgba(11,115,207,0.12); }
        .login-logo { text-align: center; margin-bottom: 24px; }
        .login-logo h1 { color: #0B73CF; font-size: 28px; font-weight: 700; margin: 0; }
        .login-logo p  { color: #666; font-size: 14px; margin: 4px 0 0; }
        .login-card h2 { font-size: 20px; font-weight: 600; margin-bottom: 24px; text-align: center; }
        .form-label { font-weight: 500; font-size: 14px; }
        .btn-login { width: 100%; background: #0B73CF; color: #fff; border: none; padding: 12px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-login:hover { background: #084B95; }
        .alert-error { background: #ffebee; color: #c62828; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <h1>NIIT</h1>
            <p>Port Harcourt — Admin Portal</p>
        </div>
        <h2>Sign In</h2>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/login" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       placeholder="admin" required autofocus autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:13px; color:#999;">
            <a href="/verify" style="color:#0B73CF; text-decoration:none;">← Student Verification</a>
        </p>
    </div>

    <script src="/assets/js/ui.js"></script>
</body>
</html>
