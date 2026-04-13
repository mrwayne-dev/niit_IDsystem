<?php
ob_start();

require_once('../config/security.php');
require_once('../config/database.php');
require_once('../config/constants.php');
require_once('../config/csrf.php');
require_once('../fpdf/fpdf.php');

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Load Composer autoloader for QR code
require_once BASE_PATH . '/vendor/autoload.php';

header('Content-Type: application/json');

// Opportunistic temp PDF cleanup (10% of requests)
if (random_int(1, 10) === 1) {
    $maxAge = 3600;
    $now    = time();
    foreach (glob(PDF_TMP_DIR . '/*.pdf') as $f) {
        if (is_file($f) && ($now - filemtime($f)) > $maxAge) @unlink($f);
    }
}

function getFontPath(string $fontName): string {
    $path = ASSETS_DIR . '/fonts/' . $fontName;
    if (!file_exists($path)) {
        throw new Exception("System Error: Missing font '{$fontName}'. Check assets/fonts/");
    }
    return $path;
}

function getCenteredX(int $fontSize, string $fontFile, string $text, int $imgWidth): int {
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
    return (int)(($imgWidth - ($bbox[2] - $bbox[0])) / 2);
}

function drawRoundedRectangle($im, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void {
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledarc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, IMG_ARC_PIE);
    imagefilledarc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2,  90, 180, $color, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2,   0,  90, $color, IMG_ARC_PIE);
}

// Safe GD image loader — replaces all @ suppressed calls
function safeImageCreate(string $path): GdImage|false {
    if (!file_exists($path)) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    try {
        return match($ext) {
            'png'         => imagecreatefrompng($path),
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            default       => false,
        };
    } catch (Throwable $e) {
        error_log("GD image load error [{$path}]: " . $e->getMessage());
        return false;
    }
}

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'pdf_url' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid request method.']); exit;
}

if (!verify_csrf_token()) {
    http_response_code(403);
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid or expired request token.']); exit;
}

$student_id = strtoupper(trim($_POST['student_id'] ?? ''));

try {
    if (empty($student_id)) throw new Exception('Student ID is missing.');

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) throw new Exception('Student not found.');

    $outDir = PDF_TMP_DIR;
    if (!is_dir($outDir)) mkdir($outDir, 0750, true);

    $fontBold = getFontPath('HostGrotesk-Bold.ttf');
    $fontReg  = getFontPath('HostGrotesk-Regular.ttf');
    $sigFont  = file_exists(ASSETS_DIR . '/fonts/HostGrotesk-Italic.ttf')
        ? ASSETS_DIR . '/fonts/HostGrotesk-Italic.ttf'
        : $fontReg;

    $safeId       = preg_replace('/[^A-Z0-9]/', '', strtoupper($student['student_id']));
    $baseName     = 'NIIT_' . $safeId;
    $frontImgPath = "{$outDir}/{$baseName}_front.png";
    $backImgPath  = "{$outDir}/{$baseName}_back.png";

    $width  = 638;
    $height = 1008;

    // ─── FRONT CARD ───────────────────────────────────────────────
    $front     = imagecreatetruecolor($width, $height);
    $white     = imagecolorallocate($front, 255, 255, 255);
    $blue      = imagecolorallocate($front, 0, 116, 217);
    $black     = imagecolorallocate($front, 28, 38, 40);
    $gray      = imagecolorallocate($front, 180, 180, 180);

    imagefill($front, 0, 0, $white);

    // Header banner
    imagefilledrectangle($front, 40, 40, $width - 40, 140, $blue);
    $text1 = "NIIT";
    $text2 = "Port Harcourt";
    $box1  = imagettfbbox(48, 0, $fontBold, $text1);
    $w1    = $box1[2] - $box1[0];
    $box2  = imagettfbbox(26, 0, $fontBold, $text2);
    $w2    = $box2[2] - $box2[0];
    $gap   = 15;
    $startX = ($width - ($w1 + $gap + $w2)) / 2;
    imagettftext($front, 48, 0, (int)$startX,            115, $white, $fontBold, $text1);
    imagettftext($front, 26, 0, (int)($startX + $w1 + $gap), 115, $white, $fontBold, $text2);

    $subText = "STUDENT IDENTITY CARD";
    $x = getCenteredX(20, $fontBold, $subText, $width);
    imagettftext($front, 20, 0, $x, 180, $blue, $fontBold, $subText);

    // Photo area
    $photoW = 280;
    $photoH = 300;
    $photoX = (int)(($width - $photoW) / 2);
    $photoY = 210;
    imagerectangle($front, $photoX - 2, $photoY - 2, $photoX + $photoW + 2, $photoY + $photoH + 2, $gray);

    // Files now stored as filename only; derive full path from UPLOAD_DIR
    $photoFile = basename($student['photo'] ?? '');
    if ($photoFile) {
        $photoPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFile;
        $photo = safeImageCreate($photoPath);
        if ($photo) {
            imagecopyresampled($front, $photo, $photoX, $photoY, 0, 0, $photoW, $photoH, imagesx($photo), imagesy($photo));
            imagedestroy($photo);
        }
    }

    // Expiry date (rotated, left margin)
    $expiryDate = strtoupper(date('M, Y', strtotime($student['expiry_date'])));
    imagettftext($front, 12, 90, 55, 480, $black, $fontBold, "Expiry Date: {$expiryDate}");

    // Full name
    $fullName = strtoupper($student['first_name'] . ' ' . $student['last_name']);
    $x = getCenteredX(28, $fontBold, $fullName, $width);
    imagettftext($front, 28, 0, $x, 560, $black, $fontBold, $fullName);

    // Signature
    $sigY = 575;
    $sigFile = basename($student['signature'] ?? '');
    if ($sigFile) {
        $sigPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $sigFile;
        $sig = safeImageCreate($sigPath);
        if ($sig) {
            $sigW = 180; $sigH = 60;
            $sigX = (int)(($width - $sigW) / 2);
            imagecopyresampled($front, $sig, $sigX, $sigY, 0, 0, $sigW, $sigH, imagesx($sig), imagesy($sig));
            imagedestroy($sig);
        }
    }
    $x = getCenteredX(14, $sigFont, "Holder's Signature", $width);
    imagettftext($front, 14, 0, $x, $sigY + 80, $black, $sigFont, "Holder's Signature");

    // Info blocks
    $y    = 680;
    $barH = 55;
    $barW = 560;
    $barX = (int)(($width - $barW) / 2);
    $infoData = [
        'STUDENT ID:'    => $student['student_id'],
        'Semester Code:' => $student['semester_code'],
        'Batch Code:'    => $student['batch_code'],
        'Course:'        => (mb_strlen($student['course']) > 28 ? mb_substr($student['course'], 0, 28) . '..' : $student['course']),
        'Duration:'      => $student['duration'],
    ];
    foreach ($infoData as $label => $val) {
        drawRoundedRectangle($front, $barX, $y, $barX + $barW, $y + $barH, 8, $blue);
        imagettftext($front, 18, 0, $barX + 20, $y + 38, $white, $fontBold, $label);
        $bbox  = imagettfbbox(18, 0, $fontBold, $val);
        $valX  = (int)($barX + $barW - 20 - ($bbox[2] - $bbox[0]));
        imagettftext($front, 18, 0, $valX, $y + 38, $white, $fontBold, $val);
        $y += $barH + 10;
    }

    imagerectangle($front, 0, 0, $width - 1, $height - 1, $gray);
    imagepng($front, $frontImgPath);
    imagedestroy($front);

    // ─── BACK CARD ────────────────────────────────────────────────
    $back  = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($back, 255, 255, 255);
    $black = imagecolorallocate($back, 28, 38, 40);
    $gray  = imagecolorallocate($back, 180, 180, 180);
    imagefill($back, 0, 0, $white);
    imagerectangle($back, 0, 0, $width - 1, $height - 1, $gray);

    // QR Code — links to verify page
    $qrContent = 'https://niit-ph.com/verify?id=' . urlencode($student['student_id']);
    try {
        $qrResult = (new PngWriter())->write(
            QrCode::create($qrContent)->setSize(200)->setMargin(8)
        );
        $qrTmp = "{$outDir}/qr_{$safeId}.png";
        $qrResult->saveToFile($qrTmp);
        $qrImg = safeImageCreate($qrTmp);
        if ($qrImg) {
            $qrX = (int)(($width - 200) / 2);
            imagecopyresampled($back, $qrImg, $qrX, 80, 0, 0, 200, 200, imagesx($qrImg), imagesy($qrImg));
            imagedestroy($qrImg);
        }
        if (file_exists($qrTmp)) unlink($qrTmp);
        $x = getCenteredX(18, $fontReg, 'Scan to Verify', $width);
        imagettftext($back, 18, 0, $x, 310, $black, $fontReg, 'Scan to Verify');
    } catch (Throwable $e) {
        error_log("QR code generation failed: " . $e->getMessage());
    }

    // Disclaimer
    $disclaimer = "This card is issued for identification of the holder whose name, photograph and signature appear on the reverse side.\n\nThis card is NIIT Port Harcourt property and remains valid for the period stated overleaf.";
    $y = 380;
    foreach (explode("\n", wordwrap($disclaimer, 45, "\n")) as $line) {
        $x = getCenteredX(20, $fontReg, $line, $width);
        imagettftext($back, 20, 0, $x, $y, $black, $fontReg, $line);
        $y += 40;
    }

    // Address
    $y = 700;
    $title = "NIIT Education & Training Centre";
    $x = getCenteredX(24, $fontBold, $title, $width);
    imagettftext($back, 24, 0, $x, $y, $black, $fontBold, $title);
    $y += 50;
    foreach (explode("\n", "1, Kaduna Street, D/Line,\nPort Harcourt, Rivers State.\nTel/Fax: 234-084-230997") as $line) {
        $x = getCenteredX(20, $fontReg, $line, $width);
        imagettftext($back, 20, 0, $x, $y, $black, $fontReg, $line);
        $y += 40;
    }

    // Auth signature
    $authSig = ASSETS_DIR . '/img/auth_signature_placeholder.png';
    $authImg = safeImageCreate($authSig);
    if ($authImg) {
        $sigX = (int)(($width - 200) / 2);
        imagecopyresampled($back, $authImg, $sigX, 870, 0, 0, 200, 80, imagesx($authImg), imagesy($authImg));
        imagedestroy($authImg);
    }
    $x = getCenteredX(18, $fontReg, 'Authorized Signatory', $width);
    imagettftext($back, 18, 0, $x, 970, $black, $fontReg, 'Authorized Signatory');

    imagepng($back, $backImgPath);
    imagedestroy($back);

    // ─── PDF ASSEMBLY ─────────────────────────────────────────────
    $pdf = new FPDF('P', 'mm', [54, 86]);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->AddPage();
    $pdf->Image($frontImgPath, 0, 0, 54, 86);
    $pdf->AddPage();
    $pdf->Image($backImgPath, 0, 0, 54, 86);

    $pdfFileName = 'NIIT_ID_' . $safeId . '.pdf';
    $pdfPath     = "{$outDir}/{$pdfFileName}";
    $pdf->Output($pdfPath, 'F');

    if (file_exists($frontImgPath)) unlink($frontImgPath);
    if (file_exists($backImgPath))  unlink($backImgPath);

    if (file_exists($pdfPath)) {
        $response['success'] = true;
        $response['message'] = 'ID Card PDF generated successfully!';
        $response['pdf_url'] = 'temp_pdfs/' . $pdfFileName;
    } else {
        throw new Exception('PDF file was not created.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (PDOException $e) {
    error_log('PDO Error [download.php]: ' . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
    http_response_code(500);
}

ob_clean();
echo json_encode($response);
