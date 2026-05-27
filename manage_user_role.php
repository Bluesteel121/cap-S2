<?php
session_start();
header('Content-Type: application/json');

// Only admins may call this
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'connect.php';

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action']  ?? '';
$userId = (int)($input['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

switch ($action) {

    // ── Promote to admin ──────────────────────────────────────────────────────
    case 'promote':
        $stmt = $conn->prepare("UPDATE accounts SET role = 'admin' WHERE id = ?");
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    // ── Demote to user ────────────────────────────────────────────────────────
    case 'demote':
        if ($userId === $currentAdminId) {
            echo json_encode(['success' => false, 'message' => 'Cannot demote yourself']);
            break;
        }
        $stmt = $conn->prepare("UPDATE accounts SET role = 'user' WHERE id = ?");
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    // ── Delete account ────────────────────────────────────────────────────────
    case 'delete':
        if ($userId === $currentAdminId) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
            break;
        }
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    // ── Update profile info ───────────────────────────────────────────────────
    case 'update_profile':
        $name       = trim($input['name']       ?? '');
        $username   = trim($input['username']   ?? '');
        $email      = trim($input['email']      ?? '');
        $contact    = trim($input['contact']    ?? '');
        $birth_date = trim($input['birth_date'] ?? '');
        $address    = trim($input['address']    ?? '');

        if (!$name || !$username || !$email || !$contact || !$birth_date || !$address) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            break;
        }

        // Check username uniqueness (exclude current user)
        $check = $conn->prepare("SELECT id FROM accounts WHERE username = ? AND id != ?");
        $check->bind_param('si', $username, $userId);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already taken']);
            break;
        }

        // Check email uniqueness (exclude current user)
        $checkEmail = $conn->prepare("SELECT id FROM accounts WHERE email = ? AND id != ?");
        $checkEmail->bind_param('si', $email, $userId);
        $checkEmail->execute();
        $checkEmail->store_result();
        if ($checkEmail->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
            break;
        }

        $stmt = $conn->prepare(
            "UPDATE accounts SET name=?, username=?, email=?, contact=?, birth_date=?, address=? WHERE id=?"
        );
        $stmt->bind_param('ssssssi', $name, $username, $email, $contact, $birth_date, $address, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        break;

    // ── Reset password ────────────────────────────────────────────────────────
    case 'reset_password':
        $newPassword = $input['new_password'] ?? '';
        if (strlen($newPassword) < 4) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters']);
            break;
        }

        // NOTE: If your app uses password_hash(), replace the line below with:
        // $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        // and use $hashed in the bind_param instead of $newPassword.
        // Currently storing plain text to match existing schema (not recommended for production).
        $stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $newPassword, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

$conn->close();
?>