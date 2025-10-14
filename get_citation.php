<?php
require_once 'connect.php';
header('Content-Type: application/json');

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    echo json_encode(['error' => 'Invalid paper ID']);
    exit();
}

// Get paper details with enhanced fields
$sql = "SELECT paper_title, author_name, co_authors, affiliation, submission_date, research_type 
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
$year = date('Y', strtotime($paper['submission_date']));
$month = date('F', strtotime($paper['submission_date']));
$day = date('d', strtotime($paper['submission_date']));

// Parse authors
$authors_array = [];
$authors_array[] = trim($paper['author_name']);
if (!empty($paper['co_authors'])) {
    $co_authors = explode(',', $paper['co_authors']);
    foreach ($co_authors as $co_author) {
        $authors_array[] = trim($co_author);
    }
}

// Format authors for different citation styles
function formatAuthorsAPA($authors) {
    if (count($authors) == 1) {
        return $authors[0];
    } elseif (count($authors) == 2) {
        return $authors[0] . ', & ' . $authors[1];
    } else {
        $formatted = '';
        for ($i = 0; $i < count($authors) - 1; $i++) {
            $formatted .= $authors[$i] . ', ';
        }
        $formatted .= '& ' . $authors[count($authors) - 1];
        return $formatted;
    }
}

function formatAuthorsMLA($authors) {
    if (count($authors) == 1) {
        return $authors[0];
    } elseif (count($authors) == 2) {
        return $authors[0] . ', and ' . $authors[1];
    } else {
        return $authors[0] . ', et al.';
    }
}

function formatAuthorsChicago($authors) {
    if (count($authors) == 1) {
        return $authors[0];
    } elseif (count($authors) == 2) {
        return $authors[0] . ' and ' . $authors[1];
    } else {
        $formatted = '';
        for ($i = 0; $i < count($authors) - 1; $i++) {
            $formatted .= $authors[$i] . ', ';
        }
        $formatted .= 'and ' . $authors[count($authors) - 1];
        return $formatted;
    }
}

function formatAuthorsIEEE($authors) {
    if (count($authors) == 1) {
        return $authors[0];
    } else {
        $formatted = '';
        for ($i = 0; $i < count($authors) - 1; $i++) {
            $formatted .= $authors[$i] . ', ';
        }
        $formatted .= 'and ' . $authors[count($authors) - 1];
        return $formatted;
    }
}

// Generate citations
$apa = formatAuthorsAPA($authors_array) . ' (' . $year . '). ' . $paper['paper_title'] . '. ' . 
       $paper['affiliation'] . '. CNLRRS Queen Pineapple Research Repository.';

$mla = formatAuthorsMLA($authors_array) . '. "' . $paper['paper_title'] . '." ' . 
       $paper['affiliation'] . ', CNLRRS Queen Pineapple Research Repository, ' . $year . '.';

$chicago = formatAuthorsChicago($authors_array) . '. ' . $year . '. "' . $paper['paper_title'] . 
           '." ' . $paper['affiliation'] . '. CNLRRS Queen Pineapple Research Repository.';

$ieee = formatAuthorsIEEE($authors_array) . ', "' . $paper['paper_title'] . '," ' . 
        $paper['affiliation'] . ', CNLRRS Queen Pineapple Research Repository, ' . $month . ' ' . $year . '.';

$bibtex = "@article{" . strtolower(str_replace(' ', '', $authors_array[0])) . $year . ",\n" .
          "  author = {" . implode(' and ', $authors_array) . "},\n" .
          "  title = {" . $paper['paper_title'] . "},\n" .
          "  institution = {" . $paper['affiliation'] . "},\n" .
          "  year = {" . $year . "},\n" .
          "  type = {" . ucfirst(str_replace('_', ' ', $paper['research_type'])) . "},\n" .
          "  publisher = {CNLRRS Queen Pineapple Research Repository}\n" .
          "}";

// Return citations as JSON
echo json_encode([
    'apa' => $apa,
    'mla' => $mla,
    'chicago' => $chicago,
    'ieee' => $ieee,
    'bibtex' => $bibtex
]);

$stmt->close();
$conn->close();
?>