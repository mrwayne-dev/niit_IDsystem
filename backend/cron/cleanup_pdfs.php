<?php
/**
 * PDF Cleanup Cron Job
 * Deletes temp PDFs older than 1 hour.
 * Register: 0 * * * * php /path/to/backend/cron/cleanup_pdfs.php >> /var/log/niit_cleanup.log 2>&1
 */
require_once dirname(__DIR__) . '/config/constants.php';

$maxAge  = 3600; // 1 hour
$now     = time();
$deleted = 0;
$errors  = 0;

foreach (glob(PDF_TMP_DIR . '/*.pdf') as $file) {
    if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
        if (@unlink($file)) {
            $deleted++;
        } else {
            $errors++;
            error_log("Cleanup failed to delete: {$file}");
        }
    }
}

echo date('Y-m-d H:i:s') . " | Cleanup: deleted={$deleted} errors={$errors}" . PHP_EOL;
