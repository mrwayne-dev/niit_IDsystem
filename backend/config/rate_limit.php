<?php
function check_rate_limit(string $action, int $max = 10, int $window = 60): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'cookie_secure'   => true,
        ]);
    }
    $key = 'rl_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $now = time();
    if (!isset($_SESSION[$key]) || ($now - $_SESSION[$key]['start']) > $window) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }
    $_SESSION[$key]['count']++;
    if ($_SESSION[$key]['count'] > $max) {
        http_response_code(429);
        header('Retry-After: ' . $window);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait and try again.']);
        exit;
    }
}
