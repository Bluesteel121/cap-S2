<?php
require_once 'connect.php';
header('Content-Type: application/json');

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    echo json_encode(['error' => 'Invalid paper ID']);
    exit();
}

// Get paper details
$sql = "SELECT paper_title, author_name, co_authors, abstract, keywords, affiliation, research_type, file_path 
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

// Return data as JSON
echo json_encode([
    'title' => $paper['paper_title'],
    'authors' => $authors,
    'abstract' => $paper['abstract'],
    'keywords' => $paper['keywords'],
    'affiliation' => $paper['affiliation'],
    'research_type' => ucfirst($paper['research_type']),
    'file_path' => !empty($paper['file_path']) && file_exists($paper['file_path'])
]);

$stmt->close();
$conn->close();
?>