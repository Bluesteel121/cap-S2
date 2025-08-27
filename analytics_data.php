<?php
session_start();
include 'connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get date range parameters
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

try {
    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Prepare response array
    $response = [
        'metrics' => getKeyMetrics($conn, $startDate, $endDate),
        'submissions' => getSubmissionsOverTime($conn, $startDate, $endDate),
        'userGrowth' => getUserGrowthOverTime($conn, $startDate, $endDate),
        'statusDistribution' => getStatusDistribution($conn),
        'categories' => getCategoriesDistribution($conn),
        'ratings' => getRatingsDistribution($conn),
        'topPublications' => getTopPublications($conn),
        'activeUsers' => getMostActiveUsers($conn),
        'recentActivity' => getRecentActivity($conn)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

function getKeyMetrics($conn, $startDate, $endDate) {
    $metrics = [];
    
    try {
        // Total users
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM accounts");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['totalUsers'] = (int)$result->fetch_assoc()['total'];
        
        // User growth calculation
        $prevStartDate = date('Y-m-d', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate)) . ' seconds'));
        
        // Current period users (using created_at from schema)
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM accounts WHERE DATE(created_at) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $currentPeriodUsers = (int)$stmt->get_result()->fetch_assoc()['total'];
        
        // Previous period users
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM accounts WHERE DATE(created_at) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $prevStartDate, $startDate);
        $stmt->execute();
        $prevPeriodUsers = (int)$stmt->get_result()->fetch_assoc()['total'];
        
        $metrics['userGrowth'] = $prevPeriodUsers > 0 ? round((($currentPeriodUsers - $prevPeriodUsers) / $prevPeriodUsers) * 100, 1) : 0;
        
        // Total publications
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_submissions");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['totalPublications'] = (int)$result->fetch_assoc()['total'];
        
        // Publication growth (using submission_date from schema)
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_submissions WHERE DATE(submission_date) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $currentPeriodPubs = (int)$stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_submissions WHERE DATE(submission_date) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $prevStartDate, $startDate);
        $stmt->execute();
        $prevPeriodPubs = (int)$stmt->get_result()->fetch_assoc()['total'];
        
        $metrics['publicationGrowth'] = $prevPeriodPubs > 0 ? round((($currentPeriodPubs - $prevPeriodPubs) / $prevPeriodPubs) * 100, 1) : 0;
        
        // Total views from paper_metrics table
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_metrics WHERE metric_type = 'view'");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['totalViews'] = (int)$result->fetch_assoc()['total'];
        
        // Views growth
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_metrics WHERE metric_type = 'view' AND DATE(created_at) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $currentViews = (int)$stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_metrics WHERE metric_type = 'view' AND DATE(created_at) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $prevStartDate, $startDate);
        $stmt->execute();
        $prevViews = (int)$stmt->get_result()->fetch_assoc()['total'];
        
        $metrics['viewsGrowth'] = $prevViews > 0 ? round((($currentViews - $prevViews) / $prevViews) * 100, 1) : 0;
        
        // Pending reviews
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_submissions WHERE status IN ('pending', 'under_review')");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['pendingReviews'] = (int)$result->fetch_assoc()['total'];
        
        // Average review time
        $stmt = $conn->prepare("
            SELECT AVG(DATEDIFF(review_date, submission_date)) as avg_time 
            FROM paper_submissions 
            WHERE review_date IS NOT NULL AND submission_date IS NOT NULL
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $avgTime = $result->fetch_assoc()['avg_time'];
        $metrics['avgReviewTime'] = $avgTime ? round($avgTime, 1) : 0;
        
        return $metrics;
    } catch (Exception $e) {
        error_log("Error in getKeyMetrics: " . $e->getMessage());
        throw $e;
    }
}

function getSubmissionsOverTime($conn, $startDate, $endDate) {
    try {
        $submissions = [];
        
        $stmt = $conn->prepare("
            SELECT DATE(submission_date) as date, COUNT(*) as count
            FROM paper_submissions 
            WHERE DATE(submission_date) BETWEEN ? AND ?
            GROUP BY DATE(submission_date)
            ORDER BY date
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['date']] = (int)$row['count'];
        }
        
        // Fill in missing dates with 0
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $submissions[] = [
                'date' => $dateStr,
                'count' => isset($data[$dateStr]) ? $data[$dateStr] : 0
            ];
            $current->add(new DateInterval('P1D'));
        }
        
        return $submissions;
    } catch (Exception $e) {
        error_log("Error in getSubmissionsOverTime: " . $e->getMessage());
        return [];
    }
}

function getUserGrowthOverTime($conn, $startDate, $endDate) {
    try {
        $userGrowth = [];
        
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM accounts 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['date']] = (int)$row['count'];
        }
        
        // Fill in missing dates with 0
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $userGrowth[] = [
                'date' => $dateStr,
                'count' => isset($data[$dateStr]) ? $data[$dateStr] : 0
            ];
            $current->add(new DateInterval('P1D'));
        }
        
        return $userGrowth;
    } catch (Exception $e) {
        error_log("Error in getUserGrowthOverTime: " . $e->getMessage());
        return [];
    }
}

function getStatusDistribution($conn) {
    try {
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM paper_submissions GROUP BY status");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $distribution = [
            'pending' => 0,
            'under_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'published' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            $status = strtolower(str_replace(' ', '_', $row['status']));
            if (isset($distribution[$status])) {
                $distribution[$status] = (int)$row['count'];
            }
        }
        
        return $distribution;
    } catch (Exception $e) {
        error_log("Error in getStatusDistribution: " . $e->getMessage());
        return [];
    }
}

function getCategoriesDistribution($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT research_type as category, COUNT(*) as count 
            FROM paper_submissions 
            WHERE research_type IS NOT NULL AND research_type != '' 
            GROUP BY research_type 
            ORDER BY count DESC 
            LIMIT 8
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[$row['category']] = (int)$row['count'];
        }
        
        return $categories;
    } catch (Exception $e) {
        error_log("Error in getCategoriesDistribution: " . $e->getMessage());
        return [];
    }
}

function getRatingsDistribution($conn) {
    try {
        $stmt = $conn->prepare("SELECT rating, COUNT(*) as count FROM paper_reviews WHERE rating IS NOT NULL AND rating BETWEEN 1 AND 5 GROUP BY rating ORDER BY rating");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ratings = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        while ($row = $result->fetch_assoc()) {
            $ratings[(int)$row['rating']] = (int)$row['count'];
        }
        
        return $ratings;
    } catch (Exception $e) {
        error_log("Error in getRatingsDistribution: " . $e->getMessage());
        return [];
    }
}

function getTopPublications($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                ps.id,
                ps.paper_title as title, 
                ps.author_name as author,
                COALESCE(pm_views.view_count, 0) as views,
                COALESCE(pm_downloads.download_count, 0) as downloads,
                COALESCE(pr.avg_rating, 0) as rating
            FROM paper_submissions ps
            LEFT JOIN (
                SELECT paper_id, COUNT(*) as view_count 
                FROM paper_metrics 
                WHERE metric_type = 'view' 
                GROUP BY paper_id
            ) pm_views ON ps.id = pm_views.paper_id
            LEFT JOIN (
                SELECT paper_id, COUNT(*) as download_count 
                FROM paper_metrics 
                WHERE metric_type = 'download' 
                GROUP BY paper_id
            ) pm_downloads ON ps.id = pm_downloads.paper_id
            LEFT JOIN (
                SELECT paper_id, AVG(rating) as avg_rating 
                FROM paper_reviews 
                WHERE rating IS NOT NULL 
                GROUP BY paper_id
            ) pr ON ps.id = pr.paper_id
            WHERE ps.status IN ('approved', 'published') 
            ORDER BY views DESC, downloads DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $publications = [];
        while ($row = $result->fetch_assoc()) {
            $publications[] = [
                'title' => $row['title'] ?: 'Untitled',
                'author' => $row['author'] ?: 'Unknown Author',
                'views' => (int)$row['views'],
                'downloads' => (int)$row['downloads'],
                'rating' => $row['rating'] ? round($row['rating'], 1) : 0
            ];
        }
        
        return $publications;
    } catch (Exception $e) {
        error_log("Error in getTopPublications: " . $e->getMessage());
        return [];
    }
}

function getMostActiveUsers($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                a.name,
                a.username,
                COUNT(ps.id) as submissions,
                COALESCE(pm.total_views, 0) as views,
                MAX(ual.created_at) as last_activity
            FROM accounts a
            LEFT JOIN paper_submissions ps ON a.username = ps.user_name OR a.name = ps.author_name
            LEFT JOIN (
                SELECT ps.author_name, COUNT(pm.id) as total_views
                FROM paper_submissions ps
                JOIN paper_metrics pm ON ps.id = pm.paper_id
                WHERE pm.metric_type = 'view'
                GROUP BY ps.author_name
            ) pm ON a.name = pm.author_name
            LEFT JOIN user_activity_logs ual ON a.id = ual.user_id
            WHERE a.role = 'user'
            GROUP BY a.id, a.name, a.username
            HAVING submissions > 0
            ORDER BY submissions DESC, views DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $lastActivity = $row['last_activity'] ? date('M j, Y', strtotime($row['last_activity'])) : 'Unknown';
            $users[] = [
                'name' => $row['name'] ?: $row['username'],
                'submissions' => (int)$row['submissions'],
                'views' => (int)$row['views'],
                'lastActivity' => $lastActivity
            ];
        }
        
        return $users;
    } catch (Exception $e) {
        error_log("Error in getMostActiveUsers: " . $e->getMessage());
        return [];
    }
}

function getRecentActivity($conn) {
    try {
        $activities = [];
        
        // Get recent submissions
        $stmt = $conn->prepare("
            SELECT 'submission' as type, user_name as user, paper_title as title, created_at
            FROM paper_submissions 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['created_at']);
            $activities[] = [
                'type' => 'submission',
                'user' => $row['user'] ?: 'Anonymous',
                'description' => 'Submitted: "' . substr($row['title'], 0, 50) . (strlen($row['title']) > 50 ? '...' : '') . '"',
                'time' => $timeAgo,
                'icon' => 'fas fa-file-upload',
                'color' => 'blue',
                'timestamp' => $row['created_at']
            ];
        }
        
        // Get recent reviews
        $stmt = $conn->prepare("
            SELECT 'review' as type, pr.reviewer_name as user, ps.paper_title as title, pr.created_at
            FROM paper_reviews pr
            JOIN paper_submissions ps ON pr.paper_id = ps.id
            WHERE pr.review_status = 'completed'
            ORDER BY pr.created_at DESC 
            LIMIT 3
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['created_at']);
            $activities[] = [
                'type' => 'review',
                'user' => $row['user'],
                'description' => 'Completed review for "' . substr($row['title'], 0, 40) . (strlen($row['title']) > 40 ? '...' : '') . '"',
                'time' => $timeAgo,
                'icon' => 'fas fa-star',
                'color' => 'yellow',
                'timestamp' => $row['created_at']
            ];
        }
        
        // Get recent user registrations
        $stmt = $conn->prepare("
            SELECT 'registration' as type, name as user, created_at
            FROM accounts 
            WHERE role = 'user'
            ORDER BY created_at DESC 
            LIMIT 2
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['created_at']);
            $activities[] = [
                'type' => 'registration',
                'user' => 'System',
                'description' => $row['user'] . ' registered as a new researcher',
                'time' => $timeAgo,
                'icon' => 'fas fa-user-plus',
                'color' => 'purple',
                'timestamp' => $row['created_at']
            ];
        }
        
        // Sort by timestamp and return top 10
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($activities, 0, 10);
    } catch (Exception $e) {
        error_log("Error in getRecentActivity: " . $e->getMessage());
        return [];
    }
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

// Close connection
$conn->close();
?>