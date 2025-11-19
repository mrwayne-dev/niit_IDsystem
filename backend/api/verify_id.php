<?php
ob_start(); 

require_once('../config/database.php');
require_once('../config/constants.php');

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean(); echo json_encode(['message' => 'Invalid request method']); exit;
}

try {
    // validate student details
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['student_id'])) {
        throw new Exception('All fields (First Name, Last Name, Student ID) are required.');
    }

    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $studentId = strtoupper(trim($_POST['student_id']));

    // check db
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ? AND LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)");
    $stmt->execute([$studentId, $firstName, $lastName]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $response['success'] = true;
        $response['message'] = 'Student Verified Successfully.';
        $response['student_id'] = $studentId; 
    } else {
        throw new Exception('No record found matching these details. Please check your inputs.');
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