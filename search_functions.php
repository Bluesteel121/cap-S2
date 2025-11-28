<?php
/**
 * Universal Search System for CNLRRS - Enhanced Version
 * This file contains all search functionality with improved keyword matching
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
 * Enhanced to better match keywords and only show approved papers
 */
function searchPapers($query, $conn) {
    $papers = [];
    
    // Validate connection
    if (!$conn || $conn->connect_error) {
        error_log("Database connection error in searchPapers: " . ($conn ? $conn->connect_error : 'null connection'));
        return $papers;
    }
    
    try {
        // Prepare search term for LIKE query
        $search_term = "%$query%";
        
        // Simplified query without LEFT JOINs that might not exist
        $sql = "SELECT 
                    ps.*,
                    0 as avg_rating,
                    0 as review_count,
                    0 as total_views,
                    0 as total_downloads,
                    (
                        CASE WHEN ps.paper_title LIKE ? THEN 20 ELSE 0 END +
                        CASE WHEN ps.keywords LIKE ? THEN 15 ELSE 0 END +
                        CASE WHEN ps.abstract LIKE ? THEN 5 ELSE 0 END +
                        CASE WHEN ps.author_name LIKE ? THEN 3 ELSE 0 END +
                        CASE WHEN ps.co_authors LIKE ? THEN 2 ELSE 0 END +
                        CASE WHEN ps.research_type LIKE ? THEN 1 ELSE 0 END
                    ) as relevance_score
                FROM paper_submissions ps 
                WHERE ps.status = 'approved'
                AND (
                    ps.paper_title LIKE ? 
                    OR ps.keywords LIKE ? 
                    OR ps.abstract LIKE ? 
                    OR ps.author_name LIKE ? 
                    OR ps.co_authors LIKE ?
                    OR ps.research_type LIKE ?
                )
                ORDER BY relevance_score DESC, ps.submission_date DESC 
                LIMIT 20";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare statement failed in searchPapers: " . $conn->error);
            error_log("SQL: " . $sql);
            return $papers;
        }
        
        // Bind all 12 parameters (6 for relevance score + 6 for WHERE clause)
        $stmt->bind_param(
            'ssssssssssss', 
            $search_term, $search_term, $search_term, $search_term, $search_term, $search_term,  // relevance score
            $search_term, $search_term, $search_term, $search_term, $search_term, $search_term   // WHERE clause
        );
        
        if (!$stmt->execute()) {
            error_log("Execute failed in searchPapers: " . $stmt->error);
            error_log("Search term: " . $search_term);
            return $papers;
        }
        
        $result = $stmt->get_result();
        
        if ($result) {
            $papers = $result->fetch_all(MYSQLI_ASSOC);
            error_log("Search for '$query' found " . count($papers) . " papers");
        } else {
            error_log("No result set returned for query: " . $query);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Exception in searchPapers: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
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
        return $html . '</div>';
    }
    
    // Papers section (logged-in only) - Show first for better visibility
    if ($is_logged_in && !empty($results['papers'])) {
        $html .= '<div class="results-section mb-4">';
        $html .= '<h3 class="text-sm font-bold text-gray-700 mb-2 px-4 py-2 bg-gray-100">';
        $html .= '<i class="fas fa-file-alt mr-2"></i>Research Papers (' . count($results['papers']) . ' results)';
        $html .= '</h3>';
        
        foreach ($results['papers'] as $paper) {
            $paper_url = 'research_details.php?id=' . $paper['id'];
            
            // Highlight matching keywords
            $keywords_array = array_filter(array_map('trim', explode(',', $paper['keywords'])));
            $keywords_display = implode(', ', array_slice($keywords_array, 0, 5));
            if (count($keywords_array) > 5) {
                $keywords_display .= '...';
            }
            
            $html .= '<a href="' . htmlspecialchars($paper_url) . '" class="result-item block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">';
            $html .= '<div class="font-semibold text-[#115D5B] mb-1">' . htmlspecialchars($paper['paper_title']) . '</div>';
            $html .= '<div class="text-xs text-gray-500 mb-1 flex items-center gap-2 flex-wrap">';
            $html .= '<span class="flex items-center"><i class="fas fa-user text-xs mr-1"></i>' . htmlspecialchars($paper['author_name']) . '</span>';
            $html .= '<span>•</span>';
            $html .= '<span class="flex items-center"><i class="fas fa-calendar text-xs mr-1"></i>' . date('Y', strtotime($paper['submission_date'])) . '</span>';
            $html .= '<span>•</span>';
            $html .= '<span class="flex items-center"><i class="fas fa-flask text-xs mr-1"></i>' . htmlspecialchars($paper['research_type']) . '</span>';
            $html .= '</div>';
            
            // Show keywords
            if (!empty($keywords_display)) {
                $html .= '<div class="text-xs text-blue-600 mb-1 flex items-start">';
                $html .= '<i class="fas fa-tags text-xs mr-1 mt-0.5"></i>';
                $html .= '<span>' . htmlspecialchars($keywords_display) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '<div class="text-sm text-gray-600">' . htmlspecialchars(substr($paper['abstract'], 0, 180)) . '...</div>';
            $html .= '</a>';
        }
        $html .= '</div>';
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