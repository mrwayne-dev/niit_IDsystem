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
    http_response_code(405);
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid request method.']); exit;
}

if (!verify_csrf_token()) {
    http_response_code(403);
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid or expired request token.']); exit;
}

check_rate_limit('verify', 5, 60);

try {
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['student_id'])) {
        throw new Exception('All fields (First Name, Last Name, Student ID) are required.');
    }

    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $studentId = strtoupper(trim($_POST['student_id']));

    $stmt = $pdo->prepare("
        SELECT student_id, first_name, last_name, other_names,
               course, semester_code, batch_code, duration, expiry_date
        FROM students
        WHERE student_id = ?
          AND LOWER(first_name) = LOWER(?)
          AND LOWER(last_name)  = LOWER(?)
        LIMIT 1
    ");
    $stmt->execute([$studentId, $firstName, $lastName]);
    $student = $stmt->fetch();

    if ($student) {
        $today     = new DateTimeImmutable('today');
        $expiryDt  = new DateTimeImmutable($student['expiry_date']);
        $isExpired = $expiryDt < $today;

        $response['success']    = true;
        $response['is_expired'] = $isExpired;
        $response['message']    = $isExpired
            ? 'Student found but this ID card has expired.'
            : 'Student Verified Successfully.';
        $response['student_id'] = htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8');
        $response['student']    = [
            'student_id'    => htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8'),
            'full_name'     => htmlspecialchars($student['first_name'] . ' ' . $student['last_name'], ENT_QUOTES, 'UTF-8'),
            'course'        => htmlspecialchars($student['course'], ENT_QUOTES, 'UTF-8'),
            'semester_code' => htmlspecialchars($student['semester_code'], ENT_QUOTES, 'UTF-8'),
            'batch_code'    => htmlspecialchars($student['batch_code'], ENT_QUOTES, 'UTF-8'),
            'expiry_date'   => $student['expiry_date'],
            'is_expired'    => $isExpired,
        ];
    } else {
        throw new Exception('No record found matching these details. Please check your inputs.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (PDOException $e) {
    error_log('PDO Error [verify_id.php]: ' . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
    http_response_code(500);
}

ob_clean();
echo json_encode($response);
