<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$user_id = intval($_GET['id']);

// Get user details
$query = "SELECT * FROM accounts WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

// Remove password from response for security
unset($user['password']);

echo json_encode(['success' => true, 'user' => $user]);

$stmt->close();
$conn->close();
?>