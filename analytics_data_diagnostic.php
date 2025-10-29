<?php
session_start();

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type
header('Content-Type: application/json');

// Diagnostic array
$diagnostic = [];

// Step 1: Check session
$diagnostic['session_check'] = [
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'username_set' => isset($_SESSION['username']),
    'username' => $_SESSION['username'] ?? 'NOT SET',
    'role_set' => isset($_SESSION['role']),
    'role' => $_SESSION['role'] ?? 'NOT SET'
];

// Check authentication
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Access denied',
        'diagnostic' => $diagnostic
    ]);
    exit();
}

// Step 2: Check database connection file
if (!file_exists('connect.php')) {
    echo json_encode([
        'error' => 'connect.php file not found',
        'diagnostic' => $diagnostic
    ]);
    exit();
}

include 'connect.php';

// Step 3: Check database connection
$diagnostic['database_check'] = [
    'conn_exists' => isset($conn),
    'conn_type' => isset($conn) ? get_class($conn) : 'NULL',
    'conn_error' => isset($conn) ? $conn->connect_error : 'Connection object not created'
];

if (!$conn || $conn->connect_error) {
    echo json_encode([
        'error' => 'Database connection failed',
        'diagnostic' => $diagnostic
    ]);
    exit();
}

// Step 4: Check tables exist
$tables_to_check = ['accounts', 'paper_submissions', 'paper_metrics', 'paper_reviews'];
$diagnostic['tables'] = [];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $diagnostic['tables'][$table] = [
        'exists' => $result && $result->num_rows > 0,
        'row_count' => 0
    ];
    
    if ($diagnostic['tables'][$table]['exists']) {
        $count_result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
        if ($count_result) {
            $row = $count_result->fetch_assoc();
            $diagnostic['tables'][$table]['row_count'] = (int)$row['cnt'];
        }
    }
}

// Step 5: Check for required columns in paper_submissions
$diagnostic['columns_check'] = [];
$required_columns = [
    'paper_submissions' => ['id', 'paper_title', 'author_name', 'submission_date', 'status', 'created_at'],
    'paper_metrics' => ['id', 'paper_id', 'metric_type', 'created_at'],
    'paper_reviews' => ['id', 'paper_id', 'rating', 'created_at'],
    'accounts' => ['id', 'username', 'name', 'role', 'created_at']
];

foreach ($required_columns as $table => $columns) {
    if ($diagnostic['tables'][$table]['exists']) {
        $result = $conn->query("DESCRIBE $table");
        $existing_columns = [];
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        $diagnostic['columns_check'][$table] = [
            'required' => $columns,
            'existing' => $existing_columns,
            'missing' => array_diff($columns, $existing_columns)
        ];
    }
}

// Step 6: Get date parameters
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

$diagnostic['date_range'] = [
    'start' => $startDate,
    'end' => $endDate,
    'valid_start' => (bool)strtotime($startDate),
    'valid_end' => (bool)strtotime($endDate)
];

// Step 7: Try a simple query
try {
    $test_query = $conn->query("SELECT COUNT(*) as total FROM accounts");
    if ($test_query) {
        $result = $test_query->fetch_assoc();
        $diagnostic['simple_query'] = [
            'success' => true,
            'total_users' => (int)$result['total']
        ];
    } else {
        $diagnostic['simple_query'] = [
            'success' => false,
            'error' => $conn->error
        ];
    }
} catch (Exception $e) {
    $diagnostic['simple_query'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Step 8: Try a prepared statement
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_submissions WHERE DATE(submission_date) BETWEEN ? AND ?");
    if (!$stmt) {
        $diagnostic['prepared_statement'] = [
            'success' => false,
            'error' => $conn->error
        ];
    } else {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $diagnostic['prepared_statement'] = [
                'success' => true,
                'submissions_in_range' => (int)$row['total']
            ];
        } else {
            $diagnostic['prepared_statement'] = [
                'success' => false,
                'error' => 'get_result() failed - mysqlnd driver may not be installed'
            ];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $diagnostic['prepared_statement'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Step 9: Check PHP version and extensions
$diagnostic['php_environment'] = [
    'php_version' => phpversion(),
    'mysqli_available' => extension_loaded('mysqli'),
    'mysqlnd_available' => function_exists('mysqli_fetch_all'),
    'json_available' => function_exists('json_encode'),
    'date_default_timezone' => date_default_timezone_get()
];

// Step 10: Try to build actual response
$response_attempt = [];
try {
    // Try getting basic metrics
    $result = $conn->query("SELECT COUNT(*) as total FROM accounts");
    if ($result) {
        $response_attempt['totalUsers'] = (int)$result->fetch_assoc()['total'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as total FROM paper_submissions");
    if ($result) {
        $response_attempt['totalPublications'] = (int)$result->fetch_assoc()['total'];
    }
    
    $diagnostic['response_build'] = [
        'success' => true,
        'sample_data' => $response_attempt
    ];
} catch (Exception $e) {
    $diagnostic['response_build'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Output diagnostic report
echo json_encode([
    'diagnostic_mode' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'diagnostic' => $diagnostic,
    'recommendation' => getDiagnosticRecommendation($diagnostic)
], JSON_PRETTY_PRINT);

function getDiagnosticRecommendation($diagnostic) {
    $recommendations = [];
    
    // Check session issues
    if (!$diagnostic['session_check']['session_started']) {
        $recommendations[] = "Session not started properly";
    }
    
    if (!$diagnostic['session_check']['username_set'] || !$diagnostic['session_check']['role_set']) {
        $recommendations[] = "User not properly authenticated - please log in again";
    }
    
    // Check database issues
    if (!$diagnostic['database_check']['conn_exists']) {
        $recommendations[] = "Database connection object not created - check connect.php";
    }
    
    // Check table issues
    foreach ($diagnostic['tables'] as $table => $info) {
        if (!$info['exists']) {
            $recommendations[] = "Table '$table' does not exist in database";
        } elseif ($info['row_count'] === 0 && $table !== 'paper_metrics' && $table !== 'paper_reviews') {
            $recommendations[] = "Table '$table' is empty - this may cause issues";
        }
    }
    
    // Check column issues
    if (isset($diagnostic['columns_check'])) {
        foreach ($diagnostic['columns_check'] as $table => $info) {
            if (!empty($info['missing'])) {
                $recommendations[] = "Table '$table' is missing required columns: " . implode(', ', $info['missing']);
            }
        }
    }
    
    // Check prepared statement issues
    if (isset($diagnostic['prepared_statement']) && !$diagnostic['prepared_statement']['success']) {
        if (strpos($diagnostic['prepared_statement']['error'], 'mysqlnd') !== false) {
            $recommendations[] = "MySQLnd driver not available - this is required for prepared statements to work properly";
        } else {
            $recommendations[] = "Prepared statement error: " . $diagnostic['prepared_statement']['error'];
        }
    }
    
    // Check PHP environment
    if ($diagnostic['php_environment']['php_version'] < '7.0') {
        $recommendations[] = "PHP version is too old (" . $diagnostic['php_environment']['php_version'] . ") - upgrade to PHP 7.4 or higher";
    }
    
    if (!$diagnostic['php_environment']['mysqli_available']) {
        $recommendations[] = "MySQLi extension not loaded - enable it in php.ini";
    }
    
    if (!$diagnostic['php_environment']['mysqlnd_available']) {
        $recommendations[] = "MySQLnd driver not available - install/enable mysqlnd extension";
    }
    
    if (empty($recommendations)) {
        $recommendations[] = "All checks passed! The issue may be with the analytics functions themselves.";
    }
    
    return $recommendations;
}
?>