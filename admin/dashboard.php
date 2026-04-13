<?php
require_once dirname(__DIR__) . '/backend/config/security.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/backend/config/database.php';
require_once dirname(__DIR__) . '/backend/config/auth.php';

require_admin_auth();

$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['q'] ?? '');

// Build query with optional search
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, student_id, course, expiry_date, created_at
        FROM students
        WHERE student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like, $perPage, $offset]);

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ?");
    $totalStmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, student_id, course, expiry_date, created_at FROM students ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM students");
}

$students   = $stmt->fetchAll();
$totalCount = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($totalCount / $perPage);
$today      = new DateTimeImmutable('today');

function esc(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — NIIT ID System</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: #f0f4f8; }
        .dashboard-nav { background: #0B73CF; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; }
        .dashboard-nav .brand { color: #fff; font-size: 20px; font-weight: 700; text-decoration: none; }
        .dashboard-nav .nav-actions { display: flex; gap: 12px; align-items: center; }
        .btn-sm-white { background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3); padding: 6px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; cursor: pointer; }
        .btn-sm-white:hover { background: rgba(255,255,255,0.25); color: #fff; }
        .main-container { max-width: 1200px; margin: 32px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 24px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
        .card-header h2 { font-size: 18px; font-weight: 600; margin: 0; }
        .search-box { display: flex; gap: 8px; }
        .search-box input { border: 1px solid #ddd; border-radius: 8px; padding: 8px 12px; font-size: 14px; width: 240px; }
        .search-box button { background: #0B73CF; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f7fafc; padding: 10px 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #eee; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:hover td { background: #fafbfc; }
        .badge-active  { background: #e8f5e9; color: #2e7d32; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-expired { background: #ffebee; color: #c62828; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
        .pagination a, .pagination span { padding: 6px 12px; border-radius: 8px; font-size: 14px; text-decoration: none; border: 1px solid #ddd; color: #333; }
        .pagination a:hover { background: #0B73CF; color: #fff; border-color: #0B73CF; }
        .pagination .current { background: #0B73CF; color: #fff; border-color: #0B73CF; }
        .stats { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-card { background: #fff; border-radius: 12px; padding: 16px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); flex: 1; min-width: 160px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #0B73CF; }
        .stat-card .label { font-size: 13px; color: #888; margin-top: 2px; }
        .btn-create { background: #0B73CF; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn-create:hover { background: #084B95; color: #fff; }
        .empty-state { text-align: center; padding: 48px 0; color: #888; }
    </style>
</head>
<body>
    <nav class="dashboard-nav">
        <a href="/admin/dashboard" class="brand">NIIT ID System</a>
        <div class="nav-actions">
            <span style="color:rgba(255,255,255,0.7); font-size:13px;">
                Welcome, <?= esc($_SESSION['admin_user'] ?? 'Admin') ?>
            </span>
            <a href="/verify" class="btn-sm-white" target="_blank">Verify Page</a>
            <a href="/" class="btn-sm-white">Create ID</a>
            <a href="/admin/logout" class="btn-sm-white">Logout</a>
        </div>
    </nav>

    <div class="main-container">
        <?php
        $totalStmt2 = $pdo->query("SELECT COUNT(*) FROM students");
        $allTotal   = (int)$totalStmt2->fetchColumn();
        $expiredCount = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE expiry_date < CURDATE()")->fetchColumn();
        $activeCount  = $allTotal - $expiredCount;
        ?>
        <div class="stats">
            <div class="stat-card">
                <div class="value"><?= $allTotal ?></div>
                <div class="label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="value" style="color:#2e7d32;"><?= $activeCount ?></div>
                <div class="label">Active IDs</div>
            </div>
            <div class="stat-card">
                <div class="value" style="color:#c62828;"><?= $expiredCount ?></div>
                <div class="label">Expired IDs</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Student Records <small style="font-weight:400;color:#888;font-size:14px;">(<?= $totalCount ?> results)</small></h2>
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/admin/dashboard" class="search-box">
                        <input type="text" name="q" placeholder="Search by name or ID..." value="<?= esc($search) ?>">
                        <button type="submit">Search</button>
                    </form>
                    <a href="/" class="btn-create">+ Create ID</a>
                </div>
            </div>

            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <p>No student records found.</p>
                    <?php if ($search): ?>
                        <a href="/admin/dashboard">Clear search</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s):
                            $expiry    = new DateTimeImmutable($s['expiry_date']);
                            $isExpired = $expiry < $today;
                        ?>
                        <tr>
                            <td><?= ($offset + $i + 1) ?></td>
                            <td><strong><?= esc($s['student_id']) ?></strong></td>
                            <td><?= esc($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><?= esc($s['course']) ?></td>
                            <td><?= esc(date('d M Y', strtotime($s['expiry_date']))) ?></td>
                            <td>
                                <span class="<?= $isExpired ? 'badge-expired' : 'badge-active' ?>">
                                    <?= $isExpired ? 'Expired' : 'Active' ?>
                                </span>
                            </td>
                            <td><?= esc(date('d M Y', strtotime($s['created_at']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= ($page - 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="current"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= ($page + 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/ui.js"></script>
</body>
</html>
