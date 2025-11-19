<?php
ob_start();

require_once('../config/database.php');
require_once('../config/constants.php');
require_once('../fpdf/fpdf.php'); 

header('Content-Type: application/json');
function getFontPath($fontName) {
    $path = ASSETS_DIR . '/fonts/' . $fontName;
    if (!file_exists($path)) {
        throw new Exception("System Error: Missing font '$fontName'. Check assets/fonts/");
    }
    return $path;
}

function getCenteredX($fontSize, $fontFile, $text, $imgWidth) {
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
    $textWidth = $bbox[2] - $bbox[0];
    return (int) (($imgWidth - $textWidth) / 2);
}

function drawRoundedRectangle($im, $x1, $y1, $x2, $y2, $radius, $color) {
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledarc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, IMG_ARC_PIE);
    imagefilledarc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color, IMG_ARC_PIE);
}

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'pdf_url' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean(); echo json_encode(['message' => 'Invalid request method']); exit;
}

$student_id = strtoupper(trim($_POST['student_id'] ?? ''));

try {
    if (empty($student_id)) throw new Exception('Student ID is missing.');

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) throw new Exception('Student not found.');

    // directories
    $outDir = PDF_TMP_DIR; 
    if (!is_dir($outDir)) mkdir($outDir, 0777, true);

    // font
    $fontBold = getFontPath('HostGrotesk-Bold.ttf');
    $fontReg  = getFontPath('HostGrotesk-Regular.ttf');
    
    // filename
    $safeId = preg_replace('/[^A-Z0-9]/', '', $student['student_id']);
    $baseName = 'NIIT_' . $safeId;
    
    $frontImgPath = "$outDir/{$baseName}_front.png";
    $backImgPath  = "$outDir/{$baseName}_back.png";
    
    // dimensions
    $width  = 638; 
    $height = 1008;

    // generate front of id card 
    $front = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($front, 255, 255, 255);
    $blue  = imagecolorallocate($front, 0, 116, 217); 
    $black = imagecolorallocate($front, 28, 38, 40);
    $gray  = imagecolorallocate($front, 180, 180, 180);
    $lightGray = imagecolorallocate($front, 245, 245, 245); 

    imagefill($front, 0, 0, $white);

    // id card header
    imagefilledrectangle($front, 40, 40, $width-40, 140, $blue); 

    $text1 = "NIIT";
    $text2 = "Port Harcourt";
    $size1 = 48; 
    $size2 = 26;
    
    $box1 = imagettfbbox($size1, 0, $fontBold, $text1);
    $w1 = $box1[2] - $box1[0];
    
    $box2 = imagettfbbox($size2, 0, $fontBold, $text2);
    $w2 = $box2[2] - $box2[0];
    
    $gap = 15;
    $totalTextW = $w1 + $gap + $w2;
    

    $startX = ($width - $totalTextW) / 2;
    $baselineY = 115;

    imagettftext($front, $size1, 0, (int)$startX, $baselineY, $white, $fontBold, $text1);
    imagettftext($front, $size2, 0, (int)($startX + $w1 + $gap), $baselineY, $white, $fontBold, $text2);

    $subText = "STUDENT IDENTITY CARD";
    $x = getCenteredX(20, $fontBold, $subText, $width);
    imagettftext($front, 20, 0, $x, 180, $blue, $fontBold, $subText);

    // id card photo
    $photoW = 280; 
    $photoH = 300; 
    $photoX = (int)(($width - $photoW) / 2);
    $photoY = 210;

    
    imagerectangle($front, $photoX-2, $photoY-2, $photoX+$photoW+2, $photoY+$photoH+2, $gray);
    
    $photoPath = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $student['photo']);
    
    if (file_exists($photoPath)) {
        $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        $photo = null;
        if($ext == 'png') $photo = @imagecreatefrompng($photoPath);
        elseif($ext == 'jpg' || $ext == 'jpeg') $photo = @imagecreatefromjpeg($photoPath);
        
        if($photo) {
            imagecopyresampled($front, $photo, $photoX, $photoY, 0, 0, $photoW, $photoH, imagesx($photo), imagesy($photo));
            imagedestroy($photo);
        }
    }

    // expiry date
    $expiryDate = strtoupper(date('M, Y', strtotime($student['expiry_date'])));
    $expiryText = "Expiry Date: $expiryDate";
    imagettftext($front, 12, 90, 55, 480, $black, $fontBold, $expiryText);

    // id card holder name
    $fullName = strtoupper($student['first_name'] . ' ' . $student['last_name']);
    $x = getCenteredX(28, $fontBold, $fullName, $width);
    imagettftext($front, 28, 0, $x, 560, $black, $fontBold, $fullName);

    // signature image
    $sigY = 575;
    $sigPath = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $student['signature']);
    if (!empty($student['signature']) && file_exists($sigPath)) {
        $sig = @imagecreatefromstring(file_get_contents($sigPath));
        if($sig) {
            $sigW = 180; $sigH = 60;
            $sigX = (int)(($width - $sigW) / 2);
            imagecopyresampled($front, $sig, $sigX, $sigY, 0, 0, $sigW, $sigH, imagesx($sig), imagesy($sig));
            imagedestroy($sig);
        }
    }
    
    $sigLabel = "Holder's Signature";
    $sigFont = file_exists(ASSETS_DIR . '/fonts/HostGrotesk-Italic.ttf') 
        ? ASSETS_DIR . '/fonts/HostGrotesk-Italic.ttf' 
        : $fontReg;
        
    $x = getCenteredX(14, $sigFont, $sigLabel, $width);
    imagettftext($front, 14, 0, $x, $sigY + 80, $black, $sigFont, $sigLabel);

    $y = 680; 
    $barH = 55; 
    $gap = 10;
    $barW = 560; 
    $barX = (int)(($width - $barW) / 2); 

    $data = [
        'STUDENT ID:' => $student['student_id'],
        'Semester Code:' => $student['semester_code'],
        'Batch Code:' => $student['batch_code'],
        'Course:' => (strlen($student['course']) > 28 ? substr($student['course'],0,28).'..' : $student['course']),
        'Duration:' => $student['duration']
    ];

    foreach($data as $label => $val) {
        drawRoundedRectangle($front, $barX, $y, $barX+$barW, $y+$barH, 8, $blue);

        imagettftext($front, 18, 0, $barX + 20, $y+38, $white, $fontBold, $label);

        $bbox = imagettfbbox(18, 0, $fontBold, $val);
        $textW = $bbox[2] - $bbox[0];
        $valX = (int)($barX + $barW - 20 - $textW);
        imagettftext($front, 18, 0, $valX, $y+38, $white, $fontBold, $val);
        
        $y += $barH + $gap;
    }

    imagerectangle($front, 0, 0, $width-1, $height-1, $gray);
    
    imagepng($front, $frontImgPath);
    imagedestroy($front);


    // generate back of id card
    $back = imagecreatetruecolor($width, $height);
    imagefill($back, 0, 0, $white);
    imagerectangle($back, 0, 0, $width-1, $height-1, $gray);

    // disclaimer
    $disclaimer = "This card is issued for identification of the holder whose name, photograph and signature appear on the reverse side.\n\nThis card is NIIT Port Harcourt property and remains valid for the period stated overleaf.";
    $wrapped = wordwrap($disclaimer, 45, "\n"); 
    $y = 250;
    foreach(explode("\n", $wrapped) as $line) {
        $x = getCenteredX(20, $fontReg, $line, $width);
        imagettftext($back, 20, 0, $x, $y, $black, $fontReg, $line);
        $y += 40;
    }

    // address
    $y = 600;
    $title = "NIIT Education & Training Centre";
    $x = getCenteredX(24, $fontBold, $title, $width);
    imagettftext($back, 24, 0, $x, $y, $black, $fontBold, $title);
    
    $addr = "1, Kaduna Street, D/Line,\nPort Harcourt, Rivers State.\nTel/Fax: 234-084-230997";
    $y += 50;
    foreach(explode("\n", $addr) as $line) {
        $x = getCenteredX(20, $fontReg, $line, $width);
        imagettftext($back, 20, 0, $x, $y, $black, $fontReg, $line);
        $y += 40;
    }

    // auth signature
    $authSig = ASSETS_DIR . '/img/auth_signature_placeholder.png';
    if (file_exists($authSig)) {
        $sig = @imagecreatefrompng($authSig);
        if ($sig) {
            $sigX = (int)(($width - 200) / 2);
            imagecopyresampled($back, $sig, $sigX, 820, 0, 0, 200, 80, imagesx($sig), imagesy($sig));
            imagedestroy($sig);
        }
    }
    $text = "Authorized Signatory";
    $x = getCenteredX(18, $fontReg, $text, $width);
    imagettftext($back, 18, 0, $x, 930, $black, $fontReg, $text);

    imagepng($back, $backImgPath);
    imagedestroy($back);


    // generate pdf
    $pdf = new FPDF('P', 'mm', [54, 86]);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0,0,0);

    $pdf->AddPage();
    $pdf->Image($frontImgPath, 0, 0, 54, 86);

    $pdf->AddPage();
    $pdf->Image($backImgPath, 0, 0, 54, 86);

    $pdfFileName = 'NIIT_ID_' . $safeId . '.pdf';
    $pdfPath = $outDir . '/' . $pdfFileName;
    
    $pdf->Output($pdfPath, 'F');

    if(file_exists($frontImgPath)) unlink($frontImgPath);
    if(file_exists($backImgPath)) unlink($backImgPath);

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
    $response['message'] = 'Database Error: ' . $e->getMessage();
    http_response_code(500);
}

ob_clean();
echo json_encode($response);
?>