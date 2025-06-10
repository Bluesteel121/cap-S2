<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'cap');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");


function closeConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Example usage in other files:
// include 'connect.php';
// $result = $conn->query("SELECT * FROM your_table");
?>