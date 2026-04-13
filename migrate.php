<?php
/**
 * Database Migration Runner
 * Run from CLI: php migrate.php
 * Or via web: https://yourdomain.com/migrate.php (DELETE THIS FILE AFTER USE!)
 */

// ⚠ IMPORTANT: Delete this file after running migrations!

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$isCli = (PHP_SAPI === 'cli');
$nl    = $isCli ? PHP_EOL : '<br>';

if (!$isCli) {
    echo '<pre>';
}

echo "NIIT ID System — Migration Runner{$nl}";
echo str_repeat('=', 40) . $nl;

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database: {$_ENV['DB_NAME']}{$nl}{$nl}";

    // ── Migration 002: Admin auth table ──────────────────────────
    echo "Running migration 002: Admin auth...{$nl}";
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        username      VARCHAR(80)  NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login    TIMESTAMP NULL
    )");
    $count = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE username = 'admin'")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('niit@admin2025', PASSWORD_ARGON2ID);
        $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?,?)")->execute(['admin', $hash]);
        echo "  ✓ Admin user created (username: admin, password: niit@admin2025){$nl}";
        echo "  ⚠ CHANGE THIS PASSWORD after first login!{$nl}";
    } else {
        echo "  ✓ Admin user already exists — skipped{$nl}";
    }
    echo "✓ Migration 002 complete{$nl}{$nl}";

    // ── Migration 003: updated_at column ────────────────────────
    echo "Running migration 003: updated_at column...{$nl}";
    $cols = $pdo->query("SHOW COLUMNS FROM students LIKE 'updated_at'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE students ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        echo "  ✓ updated_at column added{$nl}";
    } else {
        echo "  ✓ updated_at column already exists — skipped{$nl}";
    }
    // Ensure other_names column exists (may be missing in older installs)
    $cols2 = $pdo->query("SHOW COLUMNS FROM students LIKE 'other_names'")->fetchAll();
    if (empty($cols2)) {
        $pdo->exec("ALTER TABLE students ADD COLUMN other_names VARCHAR(150) NULL DEFAULT NULL AFTER last_name");
        echo "  ✓ other_names column added{$nl}";
    } else {
        echo "  ✓ other_names column already exists — skipped{$nl}";
    }
    echo "✓ Migration 003 complete{$nl}{$nl}";

    echo str_repeat('=', 40) . $nl;
    echo "✓ All migrations completed successfully!{$nl}";
    echo "⚠ DELETE this file now: rm migrate.php{$nl}";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . $nl;
    exit(1);
}

if (!$isCli) {
    echo '</pre>';
}
