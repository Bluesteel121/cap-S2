<?php
/**
 * Test Search Functionality
 * Access this file at: http://yourdomain.com/test_search.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'search_functions.php';

// Initialize variables
$conn = null;
$is_logged_in = isset($_SESSION['name']);
$db_connected = false;

// Attempt database connection
if ($is_logged_in) {
    try {
        require_once 'connect.php';
        if (isset($conn) && !$conn->connect_error) {
            $db_connected = true;
        }
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Function Test - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-section {
            transition: all 0.3s ease;
        }
        .test-section:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow-lg">
            <h1 class="text-3xl font-bold text-[#115D5B] mb-2">
                <i class="fas fa-search-plus mr-2"></i>Search Function Debugger
            </h1>
            <p class="text-gray-600">CNLRRS Universal Search System Test Suite</p>
            
            <!-- Status Bar -->
            <div class="mt-4 flex gap-4">
                <div class="flex items-center">
                    <span class="font-semibold mr-2">Login Status:</span>
                    <?php if ($is_logged_in): ?>
                        <span class="text-green-600">
                            <i class="fas fa-check-circle"></i> Logged in as <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </span>
                    <?php else: ?>
                        <span class="text-yellow-600">
                            <i class="fas fa-exclamation-circle"></i> Not logged in
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center">
                    <span class="font-semibold mr-2">Database:</span>
                    <?php if ($db_connected): ?>
                        <span class="text-green-600">
                            <i class="fas fa-check-circle"></i> Connected
                        </span>
                    <?php else: ?>
                        <span class="text-red-600">
                            <i class="fas fa-times-circle"></i> Not connected
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Test 1: Search Pages -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-file mr-2"></i>Test 1: Search Pages Function
            </h2>
            <p class="text-gray-600 mb-4">Testing searchPages() with query: <strong>"about"</strong></p>
            <?php
            $test_query = "about";
            $pages = searchPages($test_query);
            ?>
            <div class="bg-gray-50 p-4 rounded mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="font-semibold">Query:</span> 
                        <span class="text-blue-600"><?php echo htmlspecialchars($test_query); ?></span>
                    </div>
                    <div>
                        <span class="font-semibold">Results Found:</span> 
                        <span class="text-green-600 font-bold"><?php echo count($pages); ?></span>
                    </div>
                </div>
            </div>
            <details class="cursor-pointer">
                <summary class="font-semibold text-gray-700 hover:text-[#115D5B]">
                    <i class="fas fa-code mr-2"></i>View Raw Results
                </summary>
                <pre class="bg-gray-800 text-green-400 p-4 rounded mt-2 overflow-x-auto text-sm"><?php print_r($pages); ?></pre>
            </details>
        </div>

        <!-- Test 2: Search Features -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-star mr-2"></i>Test 2: Search Features Function
            </h2>
            <p class="text-gray-600 mb-4">Testing searchFeatures() with query: <strong>"pineapple"</strong></p>
            <?php
            $test_query = "pineapple";
            $features = searchFeatures($test_query);
            ?>
            <div class="bg-gray-50 p-4 rounded mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="font-semibold">Query:</span> 
                        <span class="text-blue-600"><?php echo htmlspecialchars($test_query); ?></span>
                    </div>
                    <div>
                        <span class="font-semibold">Results Found:</span> 
                        <span class="text-green-600 font-bold"><?php echo count($features); ?></span>
                    </div>
                </div>
            </div>
            <details class="cursor-pointer">
                <summary class="font-semibold text-gray-700 hover:text-[#115D5B]">
                    <i class="fas fa-code mr-2"></i>View Raw Results
                </summary>
                <pre class="bg-gray-800 text-green-400 p-4 rounded mt-2 overflow-x-auto text-sm"><?php print_r($features); ?></pre>
            </details>
        </div>

        <!-- Test 3: Database Connection -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-database mr-2"></i>Test 3: Database Connection
            </h2>
            <?php
            $db_status_icon = $db_connected ? "✅" : "❌";
            $db_status_text = $db_connected ? "Connected successfully" : "Connection failed";
            $db_status_class = $db_connected ? "bg-green-50 border-green-200 text-green-800" : "bg-red-50 border-red-200 text-red-800";
            ?>
            <div class="p-4 rounded border <?php echo $db_status_class; ?>">
                <p class="font-bold text-lg">
                    <?php echo $db_status_icon . " " . $db_status_text; ?>
                </p>
                <?php if (!$db_connected && isset($conn) && $conn->connect_error): ?>
                    <p class="mt-2">
                        <span class="font-semibold">Error:</span> 
                        <?php echo htmlspecialchars($conn->connect_error); ?>
                    </p>
                <?php elseif (!$db_connected && !isset($conn)): ?>
                    <p class="mt-2">Connection object not created. Check connect.php file.</p>
                <?php endif; ?>
                <?php if (!$is_logged_in): ?>
                    <p class="mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Database connection only available when logged in. 
                        <a href="account.php" class="underline font-semibold">Login here</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Test 4: Search Papers -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-file-alt mr-2"></i>Test 4: Search Papers Function
            </h2>
            
            <?php if ($is_logged_in && $db_connected): ?>
                <?php
                $test_query = "pineapple";
                $papers = searchPapers($test_query, $conn);
                ?>
                <p class="text-gray-600 mb-4">Testing searchPapers() with query: <strong>"<?php echo htmlspecialchars($test_query); ?>"</strong></p>
                <div class="bg-gray-50 p-4 rounded mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="font-semibold">Query:</span> 
                            <span class="text-blue-600"><?php echo htmlspecialchars($test_query); ?></span>
                        </div>
                        <div>
                            <span class="font-semibold">Papers Found:</span> 
                            <span class="text-green-600 font-bold"><?php echo count($papers); ?></span>
                        </div>
                    </div>
                </div>
                <?php if (count($papers) > 0): ?>
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Sample Results:</h3>
                        <?php foreach (array_slice($papers, 0, 3) as $paper): ?>
                            <div class="border-l-4 border-[#115D5B] pl-4 py-2 mb-2 bg-gray-50">
                                <div class="font-semibold"><?php echo htmlspecialchars($paper['paper_title']); ?></div>
                                <div class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($paper['author_name']); ?> • 
                                    <?php echo date('Y', strtotime($paper['submission_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <details class="cursor-pointer">
                    <summary class="font-semibold text-gray-700 hover:text-[#115D5B]">
                        <i class="fas fa-code mr-2"></i>View Raw Results
                    </summary>
                    <pre class="bg-gray-800 text-green-400 p-4 rounded mt-2 overflow-x-auto text-sm"><?php print_r($papers); ?></pre>
                </details>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded">
                    <p class="text-yellow-800 font-semibold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Cannot test paper search
                    </p>
                    <p class="text-yellow-700 mt-2">
                        <?php if (!$is_logged_in): ?>
                            User not logged in. <a href="account.php" class="underline font-semibold">Login here</a> to test paper search.
                        <?php elseif (!$db_connected): ?>
                            Database connection not available. Check your connect.php configuration.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Test 5: Full Search Results -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-search mr-2"></i>Test 5: Complete Search System
            </h2>
            <p class="text-gray-600 mb-4">Testing getSearchResults() with query: <strong>"soil"</strong></p>
            <?php
            $test_query = "soil";
            $full_results = getSearchResults($test_query, $is_logged_in, $conn);
            ?>
            <div class="bg-gray-50 p-4 rounded mb-4">
                <h3 class="font-bold mb-3">Search Summary:</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-3 bg-white rounded border">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($full_results['pages']); ?></div>
                        <div class="text-sm text-gray-600">Pages</div>
                    </div>
                    <div class="text-center p-3 bg-white rounded border">
                        <div class="text-2xl font-bold text-green-600"><?php echo count($full_results['features']); ?></div>
                        <div class="text-sm text-gray-600">Features</div>
                    </div>
                    <div class="text-center p-3 bg-white rounded border">
                        <div class="text-2xl font-bold text-purple-600"><?php echo count($full_results['papers']); ?></div>
                        <div class="text-sm text-gray-600">Papers</div>
                    </div>
                </div>
            </div>
            
            <h3 class="font-bold mb-2">Rendered HTML Output:</h3>
            <div class="border-2 border-gray-300 rounded-lg overflow-hidden">
                <?php echo renderSearchResults($full_results, $is_logged_in); ?>
            </div>
        </div>

        <!-- Test 6: AJAX Handler Test -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-bolt mr-2"></i>Test 6: AJAX Search Handler
            </h2>
            <p class="text-gray-600 mb-4">Test search_handler.php with live AJAX requests</p>
            
            <div class="flex gap-2 mb-4">
                <input 
                    type="text" 
                    id="ajax-test-input" 
                    placeholder="Type to search (min 2 characters)..." 
                    class="flex-1 border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-[#115D5B] focus:outline-none"
                >
                <button 
                    onclick="testAjaxSearch()" 
                    class="bg-[#115D5B] text-white px-6 py-2 rounded-lg hover:bg-[#0d4745] transition-colors font-semibold">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </div>
            
            <div id="ajax-results" class="border-2 border-gray-300 rounded-lg p-4 hidden">
                <h3 class="font-bold mb-2">Results:</h3>
                <div id="ajax-output"></div>
            </div>
            
            <div id="ajax-error" class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mt-4 hidden">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span id="ajax-error-message"></span>
            </div>
        </div>

        <!-- Test 7: Session Information -->
        <div class="bg-white rounded-lg p-6 mb-6 shadow test-section">
            <h2 class="text-xl font-bold mb-4 text-[#115D5B]">
                <i class="fas fa-info-circle mr-2"></i>Test 7: Session Information
            </h2>
            <div class="bg-gray-50 p-4 rounded">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <span class="font-semibold">Session Status:</span>
                        <span class="ml-2 <?php echo session_status() === PHP_SESSION_ACTIVE ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold">Session ID:</span>
                        <code class="ml-2 text-sm bg-gray-200 px-2 py-1 rounded"><?php echo session_id(); ?></code>
                    </div>
                </div>
                <details class="cursor-pointer">
                    <summary class="font-semibold text-gray-700 hover:text-[#115D5B]">
                        <i class="fas fa-database mr-2"></i>View Session Variables
                    </summary>
                    <pre class="bg-gray-800 text-green-400 p-4 rounded mt-2 overflow-x-auto text-sm"><?php print_r($_SESSION); ?></pre>
                </details>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-800 text-white rounded-lg p-4 text-center">
            <p class="text-sm">
                <i class="fas fa-flask mr-2"></i>
                CNLRRS Search Function Test Suite • 
                <?php echo $is_logged_in ? 'Logged In Mode' : 'Guest Mode'; ?>
            </p>
        </div>
    </div>

    <script>
    async function testAjaxSearch() {
        const query = document.getElementById('ajax-test-input').value.trim();
        const resultsDiv = document.getElementById('ajax-results');
        const outputDiv = document.getElementById('ajax-output');
        const errorDiv = document.getElementById('ajax-error');
        const errorMessage = document.getElementById('ajax-error-message');
        
        // Hide error
        errorDiv.classList.add('hidden');
        
        // Validate input
        if (query.length === 0) {
            errorMessage.textContent = 'Please enter a search term';
            errorDiv.classList.remove('hidden');
            return;
        }
        
        if (query.length < 2) {
            errorMessage.textContent = 'Search term must be at least 2 characters';
            errorDiv.classList.remove('hidden');
            return;
        }
        
        // Show loading state
        resultsDiv.classList.remove('hidden');
        outputDiv.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-[#115D5B]"></i><p class="mt-2 text-gray-600">Searching...</p></div>';
        
        try {
            const response = await fetch('search_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `query=${encodeURIComponent(query)}&is_logged_in=<?php echo $is_logged_in ? '1' : '0'; ?>`
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const html = await response.text();
            outputDiv.innerHTML = html;
            
        } catch (error) {
            console.error('Search error:', error);
            errorMessage.textContent = 'Search failed: ' + error.message;
            errorDiv.classList.remove('hidden');
            outputDiv.innerHTML = '<div class="text-center py-4 text-red-600"><i class="fas fa-times-circle text-2xl"></i><p class="mt-2">Search request failed</p></div>';
        }
    }
    
    // Allow Enter key to trigger search
    document.getElementById('ajax-test-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            testAjaxSearch();
        }
    });
    
    // Focus input on page load
    document.getElementById('ajax-test-input').focus();
    </script>
</body>
</html>

<?php
// Close database connection if it was opened
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>