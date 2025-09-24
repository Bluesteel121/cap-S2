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

// Helper function - define early so other functions can use it
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

try {
    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Prepare response array with enhanced data
    $response = [
        'metrics' => getEnhancedKeyMetrics($conn, $startDate, $endDate),
        'submissions' => getSubmissionsOverTime($conn, $startDate, $endDate),
        'userGrowth' => getUserGrowthOverTime($conn, $startDate, $endDate),
        'statusDistribution' => getStatusDistribution($conn),
        'categories' => getEnhancedCategoriesDistribution($conn),
        'ratings' => getRatingsDistribution($conn),
        'topPublications' => getEnhancedTopPublications($conn),
        'activeUsers' => getEnhancedActiveUsers($conn),
        'recentActivity' => getEnhancedRecentActivity($conn),
        // New enhanced metrics
        'fundingAnalysis' => getFundingAnalysis($conn),
        'researchTypeDistribution' => getResearchTypeDistribution($conn),
        'submissionQuality' => getSubmissionQualityMetrics($conn)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

function getEnhancedKeyMetrics($conn, $startDate, $endDate) {
    $metrics = [];
    
    try {
        // Total users
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM accounts");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['totalUsers'] = (int)$result->fetch_assoc()['total'];
        
        // User growth calculation
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $prevStartDate = date('Y-m-d', strtotime($startDate . ' -' . $daysDiff . ' days'));
        
        // Current period users
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
        
        // Publication growth
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
        
        // Total downloads
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_metrics WHERE metric_type = 'download'");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['totalDownloads'] = (int)$result->fetch_assoc()['total'];
        
        // Pending reviews
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM paper_submissions WHERE status IN ('pending', 'under_review')");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['pendingReviews'] = (int)$result->fetch_assoc()['total'];
        
        // Average review time
        $stmt = $conn->prepare("
            SELECT AVG(DATEDIFF(COALESCE(review_date, updated_at), submission_date)) as avg_time 
            FROM paper_submissions 
            WHERE status NOT IN ('pending') AND submission_date IS NOT NULL
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $avgTime = $result->fetch_assoc()['avg_time'];
        $metrics['avgReviewTime'] = $avgTime ? round($avgTime, 1) : 0;
        
        // Enhanced metrics for new features
        
        // Papers with complete enhanced data
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total FROM paper_submissions 
            WHERE author_email IS NOT NULL AND affiliation IS NOT NULL
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['enhancedSubmissions'] = (int)$result->fetch_assoc()['total'];
        
        // Funded research count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total FROM paper_submissions 
            WHERE funding_source IS NOT NULL AND funding_source != ''
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics['fundedResearch'] = (int)$result->fetch_assoc()['total'];
        
        // Average quality score (based on ratings)
        $stmt = $conn->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
            FROM paper_reviews 
            WHERE rating IS NOT NULL AND rating BETWEEN 1 AND 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $qualityData = $result->fetch_assoc();
        $metrics['avgQualityScore'] = $qualityData['avg_rating'] ? round($qualityData['avg_rating'], 1) : 0;
        $metrics['totalRatings'] = (int)$qualityData['total_ratings'];
        
        return $metrics;
    } catch (Exception $e) {
        error_log("Error in getEnhancedKeyMetrics: " . $e->getMessage());
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

function getEnhancedCategoriesDistribution($conn) {
    try {
        // Use the enhanced research_type field instead of basic categories
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN research_type = 'experimental' THEN 'Experimental'
                    WHEN research_type = 'observational' THEN 'Observational'
                    WHEN research_type = 'review' THEN 'Literature Review'
                    WHEN research_type = 'case_study' THEN 'Case Study'
                    ELSE 'Other'
                END as category_name,
                COUNT(*) as count 
            FROM paper_submissions 
            WHERE research_type IS NOT NULL AND research_type != '' 
            GROUP BY research_type 
            ORDER BY count DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[$row['category_name']] = (int)$row['count'];
        }
        
        return $categories;
    } catch (Exception $e) {
        error_log("Error in getEnhancedCategoriesDistribution: " . $e->getMessage());
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
        return [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    }
}

function getEnhancedTopPublications($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                ps.id,
                ps.paper_title as title, 
                ps.author_name as author,
                ps.affiliation,
                ps.research_type,
                ps.funding_source,
                COALESCE(pm_views.view_count, 0) as views,
                COALESCE(pm_downloads.download_count, 0) as downloads,
                COALESCE(pr.avg_rating, 0) as rating,
                ps.status
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
            ORDER BY (COALESCE(pm_views.view_count, 0) + COALESCE(pm_downloads.download_count, 0) * 3) DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $publications = [];
        while ($row = $result->fetch_assoc()) {
            $publications[] = [
                'title' => $row['title'] ?: 'Untitled',
                'author' => $row['author'] ?: 'Unknown Author',
                'affiliation' => $row['affiliation'] ?: 'Not specified',
                'research_type' => $row['research_type'] ?: 'other',
                'funding_source' => $row['funding_source'] ?: null,
                'views' => (int)$row['views'],
                'downloads' => (int)$row['downloads'],
                'rating' => $row['rating'] ? round($row['rating'], 1) : 0,
                'status' => $row['status']
            ];
        }
        
        return $publications;
    } catch (Exception $e) {
        error_log("Error in getEnhancedTopPublications: " . $e->getMessage());
        return [];
    }
}

function getEnhancedActiveUsers($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                a.name,
                a.username,
                a.email,
                COUNT(ps.id) as submissions,
                COALESCE(pm.total_views, 0) as views,
                MAX(ps.submission_date) as last_submission,
                MAX(ual.created_at) as last_activity,
                COUNT(DISTINCT CASE WHEN ps.funding_source IS NOT NULL AND ps.funding_source != '' THEN ps.id END) as funded_papers,
                AVG(CASE WHEN pr.rating IS NOT NULL THEN pr.rating END) as avg_rating
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
            LEFT JOIN paper_reviews pr ON ps.id = pr.paper_id
            WHERE a.role = 'user'
            GROUP BY a.id, a.name, a.username, a.email
            HAVING submissions > 0
            ORDER BY submissions DESC, views DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $lastActivity = $row['last_activity'] ?: $row['last_submission'];
            $lastActivityFormatted = $lastActivity ? date('M j, Y', strtotime($lastActivity)) : 'Unknown';
            
            $users[] = [
                'name' => $row['name'] ?: $row['username'],
                'email' => $row['email'],
                'submissions' => (int)$row['submissions'],
                'views' => (int)$row['views'],
                'funded_papers' => (int)$row['funded_papers'],
                'avg_rating' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0,
                'lastActivity' => $lastActivityFormatted
            ];
        }
        
        return $users;
    } catch (Exception $e) {
        error_log("Error in getEnhancedActiveUsers: " . $e->getMessage());
        return [];
    }
}

function getEnhancedRecentActivity($conn) {
    try {
        $activities = [];
        
        // Get recent enhanced submissions
        $stmt = $conn->prepare("
            SELECT 'submission' as type, user_name as user, paper_title as title, 
                   research_type, funding_source, created_at, author_email
            FROM paper_submissions 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['created_at']);
            $description = 'Submitted ';
            if ($row['research_type']) {
                $description .= ucfirst($row['research_type']) . ' research: ';
            }
            $description .= '"' . substr($row['title'], 0, 40) . (strlen($row['title']) > 40 ? '...' : '') . '"';
            
            if ($row['funding_source']) {
                $description .= ' (Funded by ' . substr($row['funding_source'], 0, 20) . ')';
            }
            
            $activities[] = [
                'type' => 'submission',
                'user' => $row['user'] ?: 'Anonymous',
                'description' => $description,
                'time' => $timeAgo,
                'icon' => 'fas fa-file-upload',
                'color' => 'blue',
                'timestamp' => $row['created_at']
            ];
        }
        
        // Get recent reviews with enhanced info
        $stmt = $conn->prepare("
            SELECT 'review' as type, pr.reviewer_name as user, ps.paper_title as title, 
                   pr.rating, pr.recommendations, pr.created_at, ps.research_type
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
            $description = 'Reviewed "' . substr($row['title'], 0, 30) . (strlen($row['title']) > 30 ? '...' : '') . '"';
            if ($row['rating']) {
                $description .= ' (Rating: ' . $row['rating'] . '/5)';
            }
            if ($row['recommendations']) {
                $description .= ' - ' . ucfirst(str_replace('_', ' ', $row['recommendations']));
            }
            
            $activities[] = [
                'type' => 'review',
                'user' => $row['user'],
                'description' => $description,
                'time' => $timeAgo,
                'icon' => 'fas fa-star',
                'color' => 'yellow',
                'timestamp' => $row['created_at']
            ];
        }
        
        // Get recent user registrations
        $stmt = $conn->prepare("
            SELECT 'registration' as type, name as user, email, created_at
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
                'description' => ($row['user'] ?: 'New researcher') . ' joined the platform',
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
        error_log("Error in getEnhancedRecentActivity: " . $e->getMessage());
        return [];
    }
}

function getFundingAnalysis($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN funding_source IS NULL OR funding_source = '' THEN 'Self-funded/Institutional'
                    ELSE funding_source
                END as funding_category,
                COUNT(*) as submission_count,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                AVG(CASE WHEN review_date IS NOT NULL THEN DATEDIFF(review_date, submission_date) END) as avg_review_days
            FROM paper_submissions
            GROUP BY funding_category
            HAVING submission_count > 0
            ORDER BY submission_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $funding = [];
        while ($row = $result->fetch_assoc()) {
            $publicationRate = $row['submission_count'] > 0 ? 
                round(($row['published_count'] / $row['submission_count']) * 100, 1) : 0;
                
            $funding[] = [
                'category' => $row['funding_category'],
                'submissions' => (int)$row['submission_count'],
                'published' => (int)$row['published_count'],
                'approved' => (int)$row['approved_count'],
                'rejected' => (int)$row['rejected_count'],
                'publication_rate' => $publicationRate,
                'avg_review_days' => $row['avg_review_days'] ? round($row['avg_review_days'], 1) : null
            ];
        }
        
        return $funding;
    } catch (Exception $e) {
        error_log("Error in getFundingAnalysis: " . $e->getMessage());
        return [];
    }
}

function getResearchTypeDistribution($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT research_type, COUNT(*) as count,
                   AVG(CASE WHEN pr.rating IS NOT NULL THEN pr.rating END) as avg_rating
            FROM paper_submissions ps
            LEFT JOIN paper_reviews pr ON ps.id = pr.paper_id
            WHERE research_type IS NOT NULL AND research_type != ''
            GROUP BY research_type
            ORDER BY count DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $distribution = [];
        while ($row = $result->fetch_assoc()) {
            $distribution[] = [
                'type' => $row['research_type'],
                'count' => (int)$row['count'],
                'avg_rating' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0
            ];
        }
        
        return $distribution;
    } catch (Exception $e) {
        error_log("Error in getResearchTypeDistribution: " . $e->getMessage());
        return [];
    }
}

function getSubmissionQualityMetrics($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN author_email IS NOT NULL AND author_email != '' THEN 1 END) as with_email,
                COUNT(CASE WHEN affiliation IS NOT NULL AND affiliation != '' THEN 1 END) as with_affiliation,
                COUNT(CASE WHEN funding_source IS NOT NULL AND funding_source != '' THEN 1 END) as with_funding,
                COUNT(CASE WHEN research_start_date IS NOT NULL THEN 1 END) as with_dates,
                COUNT(CASE WHEN terms_agreement = 1 THEN 1 END) as agreed_terms,
                COUNT(CASE WHEN email_consent = 1 THEN 1 END) as email_consent,
                COUNT(CASE WHEN data_consent = 1 THEN 1 END) as data_consent,
                AVG(CASE WHEN LENGTH(abstract) > 0 THEN LENGTH(abstract) END) as avg_abstract_length
            FROM paper_submissions
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $total = (int)$row['total_submissions'];
        
        return [
            'total_submissions' => $total,
            'completion_rates' => [
                'email' => $total > 0 ? round(($row['with_email'] / $total) * 100, 1) : 0,
                'affiliation' => $total > 0 ? round(($row['with_affiliation'] / $total) * 100, 1) : 0,
                'funding' => $total > 0 ? round(($row['with_funding'] / $total) * 100, 1) : 0,
                'research_dates' => $total > 0 ? round(($row['with_dates'] / $total) * 100, 1) : 0,
                'terms_agreement' => $total > 0 ? round(($row['agreed_terms'] / $total) * 100, 1) : 0,
                'email_consent' => $total > 0 ? round(($row['email_consent'] / $total) * 100, 1) : 0,
                'data_consent' => $total > 0 ? round(($row['data_consent'] / $total) * 100, 1) : 0
            ],
            'avg_abstract_length' => $row['avg_abstract_length'] ? round($row['avg_abstract_length']) : 0
        ];
    } catch (Exception $e) {
        error_log("Error in getSubmissionQualityMetrics: " . $e->getMessage());
        return [];
    }
}