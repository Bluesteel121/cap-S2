<?php
session_start();

require_once 'connect.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data, using null coalescing operator for safety
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; // Should be 'user' from the form

    // Basic validation
    if (empty($username) || empty($password) || $role !== 'user') {
        $_SESSION['login_error'] = "Invalid request. Please try again.";
        header("Location: userlogin.php");
        exit();
    }

    // Prepare and execute the SQL query to find the user
    $sql = "SELECT id, name, password FROM accounts WHERE username = ? AND role = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $hashed_password);
            $stmt->fetch();

            // Verify the password
            // Note: Your current code stores plain passwords. It is highly recommended to hash passwords.
            // If you implement hashing, use password_verify($password, $hashed_password) instead of a direct comparison.
            if ($password === $hashed_password) { // Replace with password_verify if using hashed passwords
                // Login successful
                $_SESSION['id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['login_success'] = "Login successful!"; // Set success message
                header("Location: loggedin_index.php"); // Redirect to logged-in page
                exit();
            } else {
                // Incorrect password
                $_SESSION['login_error'] = "Invalid username or password."; // Set error message
            }
        } else {
            // User not found
            $_SESSION['login_error'] = "Invalid username or password."; // Set error message
        }

        $stmt->close();
    } else {
        // Error preparing the statement
        $_SESSION['login_error'] = "An internal error occurred. Please try again later."; // Set error message
    }
} else {
    // Not a POST request
    $_SESSION['login_error'] = "Invalid request method."; // Set error message
}

// Close database connection
closeConnection();

// Redirect back to the login page with messages
header("Location: userlogin.php");
exit();
?>

        header("Location: userlogin.php");
        exit();
    }

    // Check if username already exists
    $check_username_sql = "SELECT id FROM accounts WHERE username = ?";
    if (!($stmt_check = $conn->prepare($check_username_sql))) {
        logError("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        $_SESSION['registration_error'] = "An internal error occurred. Please try again later.";
        header("Location: userlogin.php");
        exit();
    }
    $stmt_check->bind_param("s", $username);
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
    logError('Determined address: ' . $address);

    // Validate address based on selection
    if ($is_outside_philippines === 'false' && (empty($barangay) || empty($municipality) || empty($province))) {
         $_SESSION['registration_error'] = "Please select your complete address or indicate if you are outside the Philippines.";
         header("Location: userlogin.php");
         exit();
    }

    logError('Address validation passed.');

    // Insert user into the database
    $insert_user_sql = "INSERT INTO accounts (username, name, password, contact, email, role, birth_date, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    if (!($stmt_insert = $conn->prepare($insert_user_sql))) {
        logError("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        $_SESSION['registration_error'] = "An internal error occurred. Please try again later.";
        header("Location: userlogin.php");
        exit();
    }

    if (!$stmt_insert->bind_param("ssssssss", $username, $fullname, $password, $contact_number, $email, $role, $birth_date, $address)) {
        logError("Bind failed: (" . $stmt_insert->errno . ") " . $stmt_insert->error);
        $_SESSION['registration_error'] = "An internal error occurred. Please try again later.";
        header("Location: userlogin.php");
        exit();
    }

    if ($stmt_insert->execute()) {
        $_SESSION['registration_success'] = "Registration successful. You can now log in.";
    } else {
        logError("Error during registration: " . $stmt_insert->error);
        $_SESSION['registration_error'] = "Error during registration. Please try again later.";
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
