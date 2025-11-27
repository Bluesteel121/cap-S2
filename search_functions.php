<?php
/**
 * Universal Search System for CNLRRS
 * This file contains all search functionality for both logged-in and non-logged-in users
 */

/**
 * Get search results based on user login status
 */
function getSearchResults($search_query, $is_logged_in = false, $conn = null) {
    $results = [
        'pages' => [],
        'papers' => [],
        'features' => []
    ];
    
    $search_query = trim($search_query);
    if (empty($search_query)) {
        return $results;
    }
    
    // Search for accessible pages (available to all users)
    $results['pages'] = searchPages($search_query);
    
    // Search for features/sections (available to all users)
    $results['features'] = searchFeatures($search_query);
    
    // Search for papers (only for logged-in users)
    if ($is_logged_in && $conn) {
        $results['papers'] = searchPapers($search_query, $conn);
    }
    
    return $results;
}

/**
 * Search for accessible pages
 */
function searchPages($query) {
    $query_lower = strtolower($query);
    
    $all_pages = [
        [
            'title' => 'Home',
            'url' => 'index.php',
            'description' => 'CNLRRS Research Library homepage',
            'keywords' => ['home', 'main', 'start', 'homepage', 'cnlrrs']
        ],
        [
            'title' => 'About Us',
            'url' => 'About.php',
            'description' => 'Learn about CNLRRS and our mission',
            'keywords' => ['about', 'cnlrrs', 'mission', 'history', 'information']
        ],
        [
            'title' => 'Our Services',
            'url' => 'OurService.php',
            'description' => 'Discover the services offered by CNLRRS',
            'keywords' => ['services', 'offerings', 'what we do', 'programs']
        ],
        [
            'title' => 'User Guide',
            'url' => 'UserGuide.php',
            'description' => 'Learn how to use the research library',
            'keywords' => ['guide', 'help', 'tutorial', 'how to', 'instructions']
        ],
        [
            'title' => 'For Authors',
            'url' => 'ForAuthor.php',
            'description' => 'Information for researchers and authors',
            'keywords' => ['author', 'researcher', 'submit', 'publish', 'writer']
        ],
        [
            'title' => 'For Publishers',
            'url' => 'ForPublisher.php',
            'description' => 'Information for publishers and journals',
            'keywords' => ['publisher', 'journal', 'publication']
        ],
        [
            'title' => 'E-Library',
            'url' => 'elibrary.php',
            'description' => 'Browse research papers and publications',
            'keywords' => ['library', 'research', 'papers', 'browse', 'collection', 'publications']
        ],
        [
            'title' => 'Login',
            'url' => 'account.php',
            'description' => 'Login to your account',
            'keywords' => ['login', 'sign in', 'account', 'authenticate']
        ]
    ];
    
    $matched_pages = [];
    
    foreach ($all_pages as $page) {
        $match_score = 0;
        
        // Check title
        if (stripos($page['title'], $query) !== false) {
            $match_score += 10;
        }
        
        // Check description
        if (stripos($page['description'], $query) !== false) {
            $match_score += 5;
        }
        
        // Check keywords
        foreach ($page['keywords'] as $keyword) {
            if (stripos($keyword, $query_lower) !== false) {
                $match_score += 3;
            }
        }
        
        if ($match_score > 0) {
            $page['match_score'] = $match_score;
            $matched_pages[] = $page;
        }
    }
    
    // Sort by match score (highest first)
    usort($matched_pages, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    return $matched_pages;
}

/**
 * Search for features and sections
 */
function searchFeatures($query) {
    $query_lower = strtolower($query);
    
    $all_features = [
        [
            'title' => 'Pineapple Cultivation Research',
            'url' => 'elibrary.php?category=Crop Science',
            'description' => 'Research papers about pineapple cultivation techniques',
            'keywords' => ['pineapple', 'cultivation', 'growing', 'farming', 'crop']
        ],
        [
            'title' => 'Soil Science',
            'url' => 'elibrary.php?category=Soil Science',
            'description' => 'Studies on soil management and amendments for pineapple',
            'keywords' => ['soil', 'amendment', 'fertilizer', 'nutrients', 'ground']
        ],
        [
            'title' => 'Pest Management',
            'url' => 'elibrary.php?category=Plant Pathology',
            'description' => 'Research on pest control and disease management',
            'keywords' => ['pest', 'disease', 'pathology', 'insects', 'control', 'management']
        ],
        [
            'title' => 'Queen Pineapple Varieties',
            'url' => 'elibrary.php?search=queen pineapple',
            'description' => 'Research about Queen Pineapple varieties',
            'keywords' => ['queen', 'variety', 'varieties', 'types', 'strains']
        ],
        [
            'title' => 'Food Technology',
            'url' => 'elibrary.php?category=Food Technology',
            'description' => 'Processing and food technology research',
            'keywords' => ['food', 'processing', 'technology', 'preservation']
        ],
        [
            'title' => 'Agricultural Research',
            'url' => 'elibrary.php?category=Agricultural Research',
            'description' => 'General agricultural research and studies',
            'keywords' => ['agriculture', 'farming', 'research', 'study']
        ]
    ];
    
    $matched_features = [];
    
    foreach ($all_features as $feature) {
        $match_score = 0;
        
        if (stripos($feature['title'], $query) !== false) {
            $match_score += 10;
        }
        
        if (stripos($feature['description'], $query) !== false) {
            $match_score += 5;
        }
        
        foreach ($feature['keywords'] as $keyword) {
            if (stripos($keyword, $query_lower) !== false) {
                $match_score += 3;
            }
        }
        
        if ($match_score > 0) {
            $feature['match_score'] = $match_score;
            $matched_features[] = $feature;
        }
    }
    
    usort($matched_features, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    return $matched_features;
}

/**
 * Search for papers (logged-in users only)
 */
function searchPapers($query, $conn) {
    $papers = [];
    
    // Validate connection
    if (!$conn || $conn->connect_error) {
        error_log("Database connection error in searchPapers: " . ($conn ? $conn->connect_error : 'null connection'));
        return $papers;
    }
    
    try {
        // Build search query
        $search_term = "%$query%";
        
        $sql = "SELECT ps.*, 
                       COALESCE(AVG(pr.rating), 0) as avg_rating,
                       COUNT(pr.id) as review_count,
                       COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
                       COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
                FROM paper_submissions ps 
                LEFT JOIN paper_reviews pr ON ps.id = pr.paper_id
                LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
                WHERE ps.status IN ('approved', 'published')
                AND (ps.paper_title LIKE ? 
                     OR ps.keywords LIKE ? 
                     OR ps.abstract LIKE ? 
                     OR ps.author_name LIKE ? 
                     OR ps.co_authors LIKE ?
                     OR ps.research_type LIKE ?)
                GROUP BY ps.id 
                ORDER BY ps.submission_date DESC 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare statement failed in searchPapers: " . $conn->error);
            return $papers;
        }
        
        $stmt->bind_param('ssssss', $search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
        
        if (!$stmt->execute()) {
            error_log("Execute failed in searchPapers: " . $stmt->error);
            return $papers;
        }
        
        $result = $stmt->get_result();
        $papers = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Exception in searchPapers: " . $e->getMessage());
    }
    
    return $papers;
}

/**
 * Generate search results HTML
 */
function renderSearchResults($results, $is_logged_in = false) {
    $html = '<div class="search-results-container">';
    
    // No results found
    if (empty($results['pages']) && empty($results['features']) && empty($results['papers'])) {
        $html .= '<div class="no-results p-4 text-center text-gray-600">';
        $html .= '<i class="fas fa-search text-4xl mb-2 text-gray-400"></i>';
        $html .= '<p>No results found. Try different keywords.</p>';
        $html .= '</div>';
        return $html;
    }
    
    // Pages section
    if (!empty($results['pages'])) {
        $html .= '<div class="results-section mb-4">';
        $html .= '<h3 class="text-sm font-bold text-gray-700 mb-2 px-4 py-2 bg-gray-100"><i class="fas fa-file mr-2"></i>Pages</h3>';
        foreach ($results['pages'] as $page) {
            $html .= '<a href="' . htmlspecialchars($page['url']) . '" class="result-item block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">';
            $html .= '<div class="font-semibold text-[#115D5B]">' . htmlspecialchars($page['title']) . '</div>';
            $html .= '<div class="text-sm text-gray-600">' . htmlspecialchars($page['description']) . '</div>';
            $html .= '</a>';
        }
        $html .= '</div>';
    }
    
    // Features section
    if (!empty($results['features'])) {
        $html .= '<div class="results-section mb-4">';
        $html .= '<h3 class="text-sm font-bold text-gray-700 mb-2 px-4 py-2 bg-gray-100"><i class="fas fa-star mr-2"></i>Research Topics</h3>';
        foreach ($results['features'] as $feature) {
            $html .= '<a href="' . htmlspecialchars($feature['url']) . '" class="result-item block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">';
            $html .= '<div class="font-semibold text-[#115D5B]">' . htmlspecialchars($feature['title']) . '</div>';
            $html .= '<div class="text-sm text-gray-600">' . htmlspecialchars($feature['description']) . '</div>';
            $html .= '</a>';
        }
        $html .= '</div>';
    }
    
    // Papers section (logged-in only)
    if ($is_logged_in && !empty($results['papers'])) {
        $html .= '<div class="results-section mb-4">';
        $html .= '<h3 class="text-sm font-bold text-gray-700 mb-2 px-4 py-2 bg-gray-100"><i class="fas fa-file-alt mr-2"></i>Research Papers</h3>';
        foreach ($results['papers'] as $paper) {
            $paper_url = $is_logged_in ? 'paper_details_loggedin.php?id=' : 'paper_details.php?id=';
            $html .= '<a href="' . $paper_url . $paper['id'] . '" class="result-item block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">';
            $html .= '<div class="font-semibold text-[#115D5B]">' . htmlspecialchars($paper['paper_title']) . '</div>';
            $html .= '<div class="text-xs text-gray-500 mb-1">';
            $html .= '<span>' . htmlspecialchars($paper['author_name']) . '</span> • ';
            $html .= '<span>' . date('Y', strtotime($paper['submission_date'])) . '</span> • ';
            $html .= '<span>' . htmlspecialchars($paper['research_type']) . '</span>';
            $html .= '</div>';
            $html .= '<div class="text-sm text-gray-600">' . htmlspecialchars(substr($paper['abstract'], 0, 150)) . '...</div>';
            $html .= '</a>';
        }
        $html .= '</div>';
    }
    
    // Show login prompt if not logged in
    if (!$is_logged_in && (count($results['pages']) > 0 || count($results['features']) > 0)) {
        $html .= '<div class="login-prompt p-4 bg-blue-50 border-t border-blue-200">';
        $html .= '<p class="text-sm text-blue-800 mb-2"><i class="fas fa-info-circle mr-1"></i>Want to search research papers?</p>';
        $html .= '<a href="account.php" class="text-sm text-blue-600 font-semibold hover:underline">Login to access full search</a>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>