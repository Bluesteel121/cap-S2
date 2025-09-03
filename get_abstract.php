<?php
// Include database connection
require_once 'connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get paper ID from request
$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paper_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid paper ID'
    ]);
    exit;
}

try {
    // Prepare and execute query to get paper details
    $sql = "SELECT 
                paper_title,
                author_name,
                co_authors,
                abstract,
                research_type,
                keywords,
                submission_date,
                status
            FROM paper_submissions 
            WHERE id = ? AND status IN ('approved', 'published')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Paper not found or not available'
        ]);
        exit;
    }
    
    $paper = $result->fetch_assoc();
    
    // Prepare authors string
    $authors = $paper['author_name'];
    if (!empty($paper['co_authors'])) {
        $authors .= ', ' . $paper['co_authors'];
    }
    
    // Return paper data as JSON
    echo json_encode([
        'success' => true,
        'title' => $paper['paper_title'],
        'authors' => $authors,
        'abstract' => $paper['abstract'],
        'research_type' => $paper['research_type'],
        'keywords' => $paper['keywords'],
        'submission_date' => date('F j, Y', strtotime($paper['submission_date'])),
        'status' => ucfirst($paper['status'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>