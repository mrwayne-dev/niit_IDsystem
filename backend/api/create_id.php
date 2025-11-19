<?php
ob_start(); // START BUFFERING

require_once('../config/database.php'); 
require_once('../config/constants.php'); 

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    http_response_code(405);
    ob_clean(); echo json_encode($response); exit;
}

// Function to handle file uploads
function handleFileUpload($fileInputName, $prefix, $required = false) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) throw new Exception("The '{$fileInputName}' file is required.");
        return null; 
    }
    
    if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed for '{$fileInputName}'. Error code: {$_FILES[$fileInputName]['error']}");
    }

    $file = $_FILES[$fileInputName];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (defined('MAX_FILE_SIZE') && $file['size'] > MAX_FILE_SIZE) {
        throw new Exception("File '{$fileInputName}' is too large.");
    }
    if (defined('ALLOWED_EXTENSIONS') && !in_array($ext, ALLOWED_EXTENSIONS)) {
        throw new Exception("Invalid file type for '{$fileInputName}'.");
    }

    $filename = $prefix . '_' . time() . '.' . $ext;
    $targetPath = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save uploaded file to server.");
    }
    return 'assets/uploads/' . $filename; 
}

try {
    $required_fields = ['first_name', 'last_name', 'student_id', 'semester_code', 'batch_code', 'course', 'duration', 'expiry_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) throw new Exception("The field '{$field}' is required.");
    }
    
    $student_id = strtoupper(htmlspecialchars(trim($_POST['student_id'])));

    $photo_path = handleFileUpload('photo', 'photo_' . $student_id, true);
    $signature_path = handleFileUpload('signature', 'sig_' . $student_id, false);
    
    $data = [
        'first_name'    => htmlspecialchars(trim($_POST['first_name'])),
        'last_name'     => htmlspecialchars(trim($_POST['last_name'])),
        'student_id'    => $student_id,
        'semester_code' => htmlspecialchars(trim($_POST['semester_code'])),
        'batch_code'    => htmlspecialchars(trim($_POST['batch_code'])),
        'course'        => htmlspecialchars(trim($_POST['course'])),
        'duration'      => htmlspecialchars(trim($_POST['duration'])),
        'expiry_date'   => trim($_POST['expiry_date']),
        'photo_path'    => $photo_path,
        'signature_path'=> $signature_path,
    ];

    $stmt = $pdo->prepare("SELECT id, photo, signature FROM students WHERE student_id = ?");
    $stmt->execute([$data['student_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (!$photo_path) $data['photo_path'] = $existing['photo'];
        if (!$signature_path) $data['signature_path'] = $existing['signature'];

        $sql = "UPDATE students SET 
                first_name=?, last_name=?, semester_code=?, batch_code=?, 
                course=?, duration=?, expiry_date=?, photo=?, signature=? 
                WHERE student_id=?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'], $data['last_name'], $data['semester_code'], 
            $data['batch_code'], $data['course'], $data['duration'], $data['expiry_date'], 
            $data['photo_path'], $data['signature_path'], 
            $data['student_id']
        ]);
        $response['message'] = 'Student details updated successfully.';
        $response['id'] = $existing['id'];
    } else {
        $sql = "INSERT INTO students (first_name, last_name, student_id, semester_code, batch_code, course, duration, expiry_date, photo, signature) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'], $data['last_name'], $data['student_id'], $data['semester_code'], 
            $data['batch_code'], $data['course'], $data['duration'], $data['expiry_date'], 
            $data['photo_path'], $data['signature_path']
        ]);
        $response['message'] = 'Student details saved successfully.';
        $response['id'] = $pdo->lastInsertId();
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400); 
} catch (PDOException $e) {
    $response['message'] = 'Database Error: ' . $e->getMessage();
    http_response_code(500); 
}

ob_clean(); // CLEAN BUFFER
echo json_encode($response);
?>