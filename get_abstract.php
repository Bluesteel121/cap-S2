<?php
require_once 'connect.php';
header('Content-Type: application/json');

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    echo json_encode(['error' => 'Invalid paper ID']);
    exit();
}

// Get paper details with enhanced fields
$sql = "SELECT paper_title, author_name, co_authors, author_email, affiliation, abstract, 
               keywords, research_type, methodology, funding_source, 
               research_start_date, research_end_date, ethics_approval, file_path, submission_date
        FROM paper_submissions 
        WHERE id = ? AND status IN ('approved', 'published')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Paper not found']);
    exit();
}

$paper = $result->fetch_assoc();

// Build authors string
$authors = $paper['author_name'];
if (!empty($paper['co_authors'])) {
    $authors .= ', ' . $paper['co_authors'];
}

// Format dates
$submission_year = date('Y', strtotime($paper['submission_date']));
$research_period = '';
if ($paper['research_start_date'] && $paper['research_end_date']) {
    $research_period = date('M Y', strtotime($paper['research_start_date'])) . ' - ' . 
                      date('M Y', strtotime($paper['research_end_date']));
}

// Return data as JSON
echo json_encode([
    'title' => $paper['paper_title'],
    'authors' => $authors,
    'author_email' => $paper['author_email'],
    'affiliation' => $paper['affiliation'],
    'abstract' => $paper['abstract'],
    'keywords' => $paper['keywords'],
    'research_type' => ucfirst(str_replace('_', ' ', $paper['research_type'])),
    'methodology' => $paper['methodology'],
    'funding_source' => $paper['funding_source'],
    'research_period' => $research_period,
    'ethics_approval' => $paper['ethics_approval'],
    'submission_year' => $submission_year,
    'file_path' => !empty($paper['file_path']) && file_exists($paper['file_path'])
]);

$stmt->close();
$conn->close();
?>