<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || !isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$action = $input['action'];
$user_id = intval($input['user_id']);

// Prevent admin from modifying their own account
$current_admin_query = "SELECT id FROM accounts WHERE username = ? AND role = 'admin'";
$stmt = $conn->prepare($current_admin_query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();

if ($current_admin && $current_admin['id'] == $user_id && ($action === 'demote' || $action === 'delete')) {
    echo json_encode(['success' => false, 'message' => 'Cannot modify your own account']);
    exit();
}

// Verify user exists
$user_check_query = "SELECT id, role FROM accounts WHERE id = ?";
$stmt = $conn->prepare($user_check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

switch ($action) {
    case 'promote':
        if ($user['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'User is already an administrator']);
            exit();
        }
        
        $update_query = "UPDATE accounts SET role = 'admin', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User promoted to administrator successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to promote user']);
        }
        break;
        
    case 'demote':
        if ($user['role'] === 'user') {
            echo json_encode(['success' => false, 'message' => 'User is already a regular user']);
            exit();
        }
        
        // Check if this is the last admin
        $admin_count_query = "SELECT COUNT(*) as count FROM accounts WHERE role = 'admin'";
        $result = $conn->query($admin_count_query);
        $admin_count = $result->fetch_assoc()['count'];
        
        if ($admin_count <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot demote the last administrator']);
            exit();
        }
        
        $update_query = "UPDATE accounts SET role = 'user', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Administrator demoted to user successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to demote administrator']);
        }
        break;
        
    case 'delete':
        // Check if this is the last admin (if deleting an admin)
        if ($user['role'] === 'admin') {
            $admin_count_query = "SELECT COUNT(*) as count FROM accounts WHERE role = 'admin'";
            $result = $conn->query($admin_count_query);
            $admin_count = $result->fetch_assoc()['count'];
            
            if ($admin_count <= 1) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete the last administrator']);
                exit();
            }
        }
        
        $delete_query = "DELETE FROM accounts WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User account deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user account']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$stmt->close();
$conn->close();
?>