php
<?php
session_start();

// Include the database connection file
require_once 'connect.php';

// Function to log errors
function logError($message) {
    file_put_contents('signup_errors.log', date('Y-m-d H:i:s') . ' - ' . $message . \"\n\", FILE_APPEND);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $password = $_POST['password'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $email = $_POST['email'] ?? '';    
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birth_date = $_POST['birth_date'] ?? null; // Use null if no date is provided
    $is_outside_philippines = $_POST['is_outside_philippines'] ?? 'false';
    $general_address = $_POST['general_address'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $municipality = $_POST['municipality'] ?? '';
    $province = $_POST['province'] ?? '';


    // Validate input
    if (empty($username) || empty($fullname) || empty($password) || empty($confirm_password) || empty($contact_number) || empty($email) || empty($birth_date)) {
        $_SESSION['registration_error'] = "All fields are required.";
 header("Location: userlogin.php");
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION['registration_error'] = "Passwords do not match.";
 // Redirect back to the signup form section
 header("Location: userlogin.php");
        exit();
    }

    // Check if username already exists
    $check_username_sql = "SELECT id FROM accounts WHERE username = ?";
    if (!($stmt_check = $conn->prepare($check_username_sql))) {
        logError(\"Prepare failed: (\" . $conn->errno . \") \" . $conn->error);
        $_SESSION['registration_error'] = "An internal error occurred. Please try again later.";
        header("Location: userlogin.php");
    }
    $stmt_check->bind_param("s", $username); // Assuming username is a string
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['registration_error'] = "Username already exists.";
        $stmt_check->close();
        closeConnection();
        header("Location: userlogin.php");
        exit();
    }
    $stmt_check->close();

    $role = 'user'; // Set the role to 'user'
    // Determine the address to save
    $address = ($is_outside_philippines === 'true') ? $general_address : implode(', ', array_filter([$barangay, $municipality, $province]));

    // Validate address based on selection
    if ($is_outside_philippines === 'false' && (empty($barangay) || empty($municipality) || empty($province))) {
         $_SESSION['registration_error'] = "Please select your complete address or indicate if you are outside the Philippines.";
         // Redirect back to the signup form section
         header("Location: userlogin.php");
        exit();
    }

    // Insert user into the database
    $insert_user_sql = "INSERT INTO accounts (username, name, password, contact, email, role, birth_date, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
 if (!($stmt_insert = $conn->prepare($insert_user_sql))) {
 logError(\"Prepare failed: (\" . $conn->errno . \") \" . $conn->error);
 $_SESSION['registration_error'] = "An internal error occurred. Please try again later.";
 header("Location: userlogin.php");
    }
    if (!$stmt_insert->bind_param("ssssssss", $username, $fullname, $password, $contact_number, $email, $role, $birth_date, $address)) {
 logError(\"Bind failed: (\" . $stmt_insert->errno . \") \" . $stmt_insert->error);
 $_SESSION['registration_error'] = "An internal error occurred. Please try again later.";
 header("Location: userlogin.php");
    }

 if ($stmt_insert->execute()) {
        $_SESSION['registration_success'] = "Registration successful. You can now log in.";
    } else {
        $_SESSION['registration_error'] = "Error during registration: " . $stmt_insert->error;
    }

    $stmt_insert->close();
} else {
    $_SESSION['registration_error'] = "Invalid request method.";
}

// Close the database connection
closeConnection();

// Redirect back to the login page
header("Location: userlogin.php");
exit();
?>