<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'connect.php';
require_once 'user_activity_logger.php';

// Pagination settings
$logs_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

// Filter settings
$filter_user = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
$filter_action = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
$filter_date_from = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filter_date_to = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// Parse log file
$log_file = 'user_activity.txt';
$all_logs = [];
$unique_users = [];
$unique_actions = [];

if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach (array_reverse($lines) as $line) {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] USER: ([^|]+) \| IP: ([^|]+) \| PAGE: ([^|]+) \| ACTION: ([^|]+)(?:\| DETAILS: ([^|]+))?(?:\| USER_AGENT: (.+))?$/', $line, $matches)) {
            $log_entry = [
                'timestamp' => $matches[1],
                'user' => trim($matches[2]),
                'ip' => trim($matches[3]),
                'page' => trim($matches[4]),
                'action' => trim($matches[5]),
                'details' => isset($matches[6]) ? trim($matches[6]) : '',
                'user_agent' => isset($matches[7]) ? trim($matches[7]) : ''
            ];
            
            // Apply filters
            $include = true;
            
            if ($filter_user && stripos($log_entry['user'], $filter_user) === false) {
                $include = false;
            }
            
            if ($filter_action && stripos($log_entry['action'], $filter_action) === false) {
                $include = false;
            }
            
            if ($filter_date_from && $log_entry['timestamp'] < $filter_date_from . ' 00:00:00') {
                $include = false;
            }
            
            if ($filter_date_to && $log_entry['timestamp'] > $filter_date_to . ' 23:59:59') {
                $include = false;
            }
            
            if ($include) {
                $all_logs[] = $log_entry;
            }
            
            // Collect unique values for filters
            $unique_users[$log_entry['user']] = true;
            $unique_actions[$log_entry['action']] = true;
        }
    }
}

// Pagination
$total_logs = count($all_logs);
$total_pages = ceil($total_logs / $logs_per_page);
$current_logs = array_slice($all_logs, $offset, $logs_per_page);

// Sort unique values
ksort($unique_users);
ksort($unique_actions);

// Get activity statistics
$stats = getActivityStats(30);

// Function to get action icon and color
function getActionInfo($action) {
    $action_map = [
        'LOGIN_SUCCESS' => ['icon' => 'fas fa-sign-in-alt', 'color' => 'green'],
        'LOGIN_FAILED' => ['icon' => 'fas fa-times-circle', 'color' => 'red'],
        'LOGOUT' => ['icon' => 'fas fa-sign-out-alt', 'color' => 'blue'],
        'PAGE_VIEW' => ['icon' => 'fas fa-eye', 'color' => 'gray'],
        'PAPER_SUBMITTED' => ['icon' => 'fas fa-file-upload', 'color' => 'blue'],
        'PAPER_SUBMISSION_FAILED' => ['icon' => 'fas fa-exclamation-triangle', 'color' => 'red'],
        'PAPER_DOWNLOADED' => ['icon' => 'fas fa-download', 'color' => 'green'],
        'SEARCH_PERFORMED' => ['icon' => 'fas fa-search', 'color' => 'purple'],
        'REGISTRATION_SUCCESS' => ['icon' => 'fas fa-user-plus', 'color' => 'green'],
        'SECURITY_EVENT_UNAUTHORIZED_ACCESS_ATTEMPT' => ['icon' => 'fas fa-shield-alt', 'color' => 'red'],
        'FILE_UPLOADED' => ['icon' => 'fas fa-upload', 'color' => 'blue'],
        'ADMIN_ACTION' => ['icon' => 'fas fa-user-cog', 'color' => 'orange'],
        'EMAIL_SENT' => ['icon' => 'fas fa-envelope', 'color' => 'blue']
    ];
    
    return $action_map[$action] ?? ['icon' => 'fas fa-circle', 'color' => 'gray'];
}

// Function to format relative time
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 604800) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

// Function to extract browser from user agent
function getBrowser($user_agent) {
    if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
    if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
    if (strpos($user_agent, 'Safari') !== false) return 'Safari';
    if (strpos($user_agent, 'Edge') !== false) return 'Edge';
    return 'Unknown';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - CNLRRS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #115D5B;
            border-radius: 3px;
        }

        .log-entry {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .log-entry:hover {
            background-color: #f8fafc;
            border-left-color: #115D5B;
            transform: translateX(2px);
        }

        .filter-card {
            background: linear-gradient(135deg, #115D5B 0%, #103625 100%);
        }

        .stats-card {
            background: white;
            border-left: 4px solid #115D5B;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .action-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .pagination-button {
            transition: all 0.3s ease;
        }

        .pagination-button:hover:not(.disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-lg border-b-4 border-[#115D5B]">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="admin_loggedin_index.php" class="text-[#115D5B] hover:text-[#103625] transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-[#115D5B]">Activity Logs</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-database mr-1"></i>
                        <?php echo number_format($total_logs); ?> entries
                    </div>
                    <div class="relative">
                        <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['username'], 0, 1)); ?>.png" 
                             alt="Profile" 
                             class="w-10 h-10 rounded-full border-2 border-[#115D5B]"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-10 h-10 rounded-full border-2 border-[#115D5B] bg-[#115D5B] text-white flex items-center justify-center font-bold text-sm" style="display: none;">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stats-card p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Activities (30d)</p>
                        <p class="text-2xl font-bold text-[#115D5B]"><?php echo number_format($stats['total_activities']); ?></p>
                    </div>
                    <i class="fas fa-chart-line text-2xl text-[#115D5B]"></i>
                </div>
            </div>

            <div class="stats-card p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Active Users</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['unique_users']; ?></p>
                    </div>
                    <i class="fas fa-users text-2xl text-green-600"></i>
                </div>
            </div>

            <div class="stats-card p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Page Views</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['page_views']); ?></p>
                    </div>
                    <i class="fas fa-eye text-2xl text-blue-600"></i>
                </div>
            </div>

            <div class="stats-card p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Paper Submissions</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo $stats['paper_submissions']; ?></p>
                    </div>
                    <i class="fas fa-file-upload text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card text-white p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-lg font-bold mb-4">
                <i class="fas fa-filter mr-2"></i>Filter Logs
            </h3>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">User</label>
                    <select name="filter_user" class="w-full px-3 py-2 bg-white text-gray-900 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-white">
                        <option value="">All Users</option>
                        <?php foreach ($unique_users as $user => $dummy): ?>
                            <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $filter_user === $user ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Action Type</label>
                    <select name="filter_action" class="w-full px-3 py-2 bg-white text-gray-900 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-white">
                        <option value="">All Actions</option>
                        <?php foreach ($unique_actions as $action => $dummy): ?>
                            <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filter_action === $action ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(str_replace('_', ' ', $action)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Date From</label>
                    <input type="date" name="filter_date_from" value="<?php echo $filter_date_from; ?>" 
                           class="w-full px-3 py-2 bg-white text-gray-900 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-white">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Date To</label>
                    <input type="date" name="filter_date_to" value="<?php echo $filter_date_to; ?>" 
                           class="w-full px-3 py-2 bg-white text-gray-900 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-white">
                </div>

                <div class="md:col-span-2 lg:col-span-4 flex space-x-4 pt-4">
                    <button type="submit" class="bg-white text-[#115D5B] px-6 py-2 rounded-md font-medium hover:bg-gray-100 transition-colors">
                        <i class="fas fa-search mr-2"></i>Apply Filters
                    </button>
                    <a href="admin_activity_logs.php" class="bg-transparent border-2 border-white text-white px-6 py-2 rounded-md font-medium hover:bg-white hover:text-[#115D5B] transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Activity Logs -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">
                        <i class="fas fa-list mr-2"></i>Activity Log Entries
                    </h3>
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                        <span>|</span>
                        <span><?php echo number_format($total_logs); ?> total entries</span>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                <?php if (empty($current_logs)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p class="text-lg">No activity logs found</p>
                        <p class="text-sm">Try adjusting your filters or check back later</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($current_logs as $log): ?>
                        <?php $action_info = getActionInfo($log['action']); ?>
                        <div class="log-entry p-6">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-<?php echo $action_info['color']; ?>-100 flex items-center justify-center">
                                        <i class="<?php echo $action_info['icon']; ?> text-<?php echo $action_info['color']; ?>-600"></i>
                                    </div>
                                </div>

                                <div class="flex-grow min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-grow">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($log['user']); ?></span>
                                                <span class="action-badge bg-<?php echo $action_info['color']; ?>-100 text-<?php echo $action_info['color']; ?>-800">
                                                    <?php echo str_replace('_', ' ', $log['action']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if (!empty($log['details'])): ?>
                                                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($log['details']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                <span>
                                                    <i class="fas fa-globe mr-1"></i>
                                                    IP: <?php echo htmlspecialchars($log['ip']); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-file-code mr-1"></i>
                                                    <?php echo basename($log['page']); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-browser mr-1"></i>
                                                    <?php echo getBrowser($log['user_agent']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right flex-shrink-0 ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo date('M j, Y', strtotime($log['timestamp'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo date('g:i A', strtotime($log['timestamp'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-400 mt-1">
                                                <?php echo timeAgo($log['timestamp']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-gray-50 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <?php echo number_format($offset + 1); ?> to <?php echo number_format(min($offset + $logs_per_page, $total_logs)); ?> of <?php echo number_format($total_logs); ?> results
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                                   class="pagination-button px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    First
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="pagination-button px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="pagination-button px-3 py-2 border rounded-md text-sm font-medium <?php echo $i === $page ? 'bg-[#115D5B] text-white border-[#115D5B]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="pagination-button px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                                   class="pagination-button px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Last
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Add loading states for filter form
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>