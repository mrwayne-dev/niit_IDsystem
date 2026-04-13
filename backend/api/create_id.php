<?php
ob_start();

require_once('../config/security.php');
require_once('../config/database.php');
require_once('../config/constants.php');
require_once('../config/csrf.php');
require_once('../config/rate_limit.php');

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

if (!verify_csrf_token()) {
    http_response_code(403);
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid or expired request token.']); exit;
}

check_rate_limit('create', 20, 60);

// Handle file upload with MIME validation and random filename
function handleFileUpload($fileInputName, $required = false) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) throw new Exception("The '{$fileInputName}' file is required.");
        return null;
    }
    if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error for '{$fileInputName}'.");
    }

    $file = $_FILES[$fileInputName];

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception("File '{$fileInputName}' exceeds the 2MB size limit.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        throw new Exception("Invalid file type for '{$fileInputName}'. Allowed: JPG, PNG.");
    }

    // MIME type check to prevent polyglot file attacks
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        throw new Exception("File '{$fileInputName}' content does not match an allowed image type.");
    }

    // Cryptographically random filename (not predictable)
    $filename   = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save uploaded file.");
    }
    // Store only the filename; path is derived server-side via UPLOAD_DIR
    return $filename;
}

try {
    // Validation rules
    $rules = [
        'first_name'    => ['req' => true,  'max' => 100, 'pattern' => "/^[A-Za-z\s\-']+$/"],
        'last_name'     => ['req' => true,  'max' => 100, 'pattern' => "/^[A-Za-z\s\-']+$/"],
        'other_names'   => ['req' => false, 'max' => 150],
        'student_id'    => ['req' => true,  'max' => 50,  'pattern' => "/^[A-Z0-9\-]+$/i"],
        'semester_code' => ['req' => true,  'max' => 50],
        'batch_code'    => ['req' => true,  'max' => 50],
        'course'        => ['req' => true,  'max' => 150],
        'duration'      => ['req' => true,  'max' => 100],
        'expiry_date'   => ['req' => true,  'date' => true],
    ];

    foreach ($rules as $field => $rule) {
        $value = trim($_POST[$field] ?? '');
        if ($rule['req'] && $value === '') {
            throw new Exception("The field '{$field}' is required.");
        }
        if ($value !== '' && isset($rule['max']) && mb_strlen($value) > $rule['max']) {
            throw new Exception("Field '{$field}' is too long (max {$rule['max']} characters).");
        }
        if ($value !== '' && isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            throw new Exception("Field '{$field}' contains invalid characters.");
        }
        if ($value !== '' && !empty($rule['date'])) {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                throw new Exception("'expiry_date' must be a valid date (YYYY-MM-DD).");
            }
        }
    }

    // Store raw trimmed values — PDO prepared statements handle SQL safety
    $data = [
        'first_name'    => trim($_POST['first_name']),
        'last_name'     => trim($_POST['last_name']),
        'other_names'   => trim($_POST['other_names'] ?? ''),
        'student_id'    => strtoupper(trim($_POST['student_id'])),
        'semester_code' => trim($_POST['semester_code']),
        'batch_code'    => trim($_POST['batch_code']),
        'course'        => trim($_POST['course']),
        'duration'      => trim($_POST['duration']),
        'expiry_date'   => trim($_POST['expiry_date']),
    ];

    $photo_path     = handleFileUpload('photo', true);
    $signature_path = handleFileUpload('signature', false);

    $stmt = $pdo->prepare("SELECT id, photo, signature FROM students WHERE student_id = ?");
    $stmt->execute([$data['student_id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Keep existing files if no new upload provided
        if (!$photo_path)     $photo_path     = $existing['photo'];
        if (!$signature_path) $signature_path = $existing['signature'];

        $sql = "UPDATE students SET
                    first_name=?, last_name=?, other_names=?, semester_code=?, batch_code=?,
                    course=?, duration=?, expiry_date=?, photo=?, signature=?
                WHERE student_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'], $data['last_name'], $data['other_names'],
            $data['semester_code'], $data['batch_code'], $data['course'],
            $data['duration'], $data['expiry_date'],
            $photo_path, $signature_path,
            $data['student_id']
        ]);
        $response['message'] = 'Student details updated successfully.';
        $response['id'] = $existing['id'];
    } else {
        $sql = "INSERT INTO students
                    (first_name, last_name, other_names, student_id, semester_code, batch_code, course, duration, expiry_date, photo, signature)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'], $data['last_name'], $data['other_names'],
            $data['student_id'], $data['semester_code'], $data['batch_code'],
            $data['course'], $data['duration'], $data['expiry_date'],
            $photo_path, $signature_path
        ]);
        $response['message'] = 'Student details saved successfully.';
        $response['id'] = $pdo->lastInsertId();
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (PDOException $e) {
    error_log('PDO Error [create_id.php]: ' . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
    http_response_code(500);
}

ob_clean();
echo json_encode($response);
