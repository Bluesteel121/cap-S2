<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNLRRS - Enhanced Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
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
        
        .gradient-bg {
            background: linear-gradient(135deg, #115D5B 0%, #103625 100%);
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-left: 4px solid #115D5B;
        }
        
        .metric-positive {
            color: #10b981;
        }
        
        .metric-negative {
            color: #ef4444;
        }
        
        .metric-neutral {
            color: #6b7280;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(17, 93, 91, 0.3);
            border-radius: 50%;
            border-top-color: #115D5B;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .filter-btn {
            transition: all 0.2s ease;
        }
        
        .filter-btn.active {
            background: #115D5B;
            color: white;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #115D5B, #10b981);
            transition: width 0.3s ease;
        }

        /* Enhanced styling for new features */
        .enhanced-metric {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid #0ea5e9;
        }

        .funding-badge {
            background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);
            color: #92400e;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .research-type-badge {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .experimental { background: #dbeafe; color: #1e40af; }
        .observational { background: #dcfce7; color: #166534; }
        .review { background: #fef3c7; color: #92400e; }
        .case_study { background: #fce7f3; color: #be185d; }
        .other { background: #f3f4f6; color: #374151; }

        .quality-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .insight-panel {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
        }

        .metric-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="admin_loggedin_index.php" class="flex items-center text-white hover:text-yellow-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <span>Back to Dashboard</span>
                    </a>
                    <div class="h-6 w-px bg-white opacity-30"></div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-chart-line mr-3"></i>
                        Analytics Dashboard
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="exportEnhancedData()" class="bg-yellow-500 hover:bg-yellow-600 text-black px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-download mr-2"></i>Export Enhanced Report
                    </button>
                    <button onclick="refreshData()" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Date Range Filter -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Date Range:</label>
                    <div class="flex space-x-2">
                        <button onclick="setDateRange('7d')" class="filter-btn active px-3 py-1 text-sm border border-gray-300 rounded-md">7 Days</button>
                        <button onclick="setDateRange('30d')" class="filter-btn px-3 py-1 text-sm border border-gray-300 rounded-md">30 Days</button>
                        <button onclick="setDateRange('90d')" class="filter-btn px-3 py-1 text-sm border border-gray-300 rounded-md">90 Days</button>
                        <button onclick="setDateRange('1y')" class="filter-btn px-3 py-1 text-sm border border-gray-300 rounded-md">1 Year</button>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <input type="date" id="startDate" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <span class="text-gray-500">to</span>
                    <input type="date" id="endDate" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <button onclick="applyCustomRange()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">Apply</button>
                </div>
            </div>
        </div>

        <!-- Enhanced Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalUsers">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="metric-positive text-sm font-medium" id="userGrowth">+0%</span>
                            <span class="text-xs text-gray-500">vs previous period</span>
                        </div>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="insight-panel">
                    <div class="text-xs text-gray-600" id="userInsight">Loading insights...</div>
                </div>
            </div>

            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Publications</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalPublications">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="metric-positive text-sm font-medium" id="publicationGrowth">+0%</span>
                            <span class="text-xs text-gray-500">vs previous period</span>
                        </div>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-file-alt text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="insight-panel">
                    <div class="text-xs text-gray-600" id="publicationInsight">Loading insights...</div>
                </div>
            </div>

            <div class="enhanced-metric card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Avg Quality Score</p>
                        <p class="text-3xl font-bold text-gray-900" id="qualityScore">
                            <span class="loading"></span>
                        </p>
                        <div class="quality-indicator mt-2">
                            <div id="qualityStars" class="text-yellow-500"></div>
                            <span class="text-xs text-gray-500" id="qualityRatings">0 ratings</span>
                        </div>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-medal text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="insight-panel">
                    <div class="text-xs text-gray-600" id="qualityInsight">Loading insights...</div>
                </div>
            </div>

            <div class="enhanced-metric card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Funded Research</p>
                        <p class="text-3xl font-bold text-gray-900" id="fundedResearch">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="text-xs text-gray-500" id="fundingPercentage">0% of total</span>
                        </div>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-coins text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="insight-panel">
                    <div class="text-xs text-gray-600" id="fundingInsight">Loading insights...</div>
                </div>
            </div>
        </div>

        <!-- Row for standard metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Views</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalViews">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="metric-positive text-sm font-medium" id="viewsGrowth">+0%</span>
                            <span class="text-xs text-gray-500">vs previous period</span>
                        </div>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-eye text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Downloads</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalDownloads">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="text-xs text-gray-500">Research impact</span>
                        </div>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-download text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Pending Reviews</p>
                        <p class="text-3xl font-bold text-gray-900" id="pendingReviews">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="metric-neutral text-sm font-medium" id="reviewsBacklog">0 days avg</span>
                            <span class="text-xs text-gray-500">review time</span>
                        </div>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="enhanced-metric card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Complete Submissions</p>
                        <p class="text-3xl font-bold text-gray-900" id="enhancedSubmissions">
                            <span class="loading"></span>
                        </p>
                        <div class="metric-trend">
                            <span class="text-xs text-gray-500" id="completionRate">0% completion</span>
                        </div>
                    </div>
                    <div class="bg-teal-100 p-3 rounded-full">
                        <i class="fas fa-clipboard-check text-teal-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Submissions Over Time -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                    Submissions Over Time
                </h3>
                <div class="chart-container">
                    <canvas id="submissionsChart"></canvas>
                </div>
            </div>

            <!-- User Growth -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user-plus mr-2 text-green-600"></i>
                    User Registration Growth
                </h3>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Enhanced Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Enhanced Research Type Distribution -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-flask mr-2 text-indigo-600"></i>
                    Research Type Distribution
                </h3>
                <div class="chart-container">
                    <canvas id="researchTypeChart"></canvas>
                </div>
                <div class="mt-4">
                    <div id="researchTypeInsights" class="text-sm text-gray-600">
                        <!-- Dynamic insights will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Publication Status Distribution -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-pie mr-2 text-purple-600"></i>
                    Publication Status
                </h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Review Ratings Distribution -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-star mr-2 text-yellow-600"></i>
                    Review Ratings
                </h3>
                <div class="chart-container">
                    <canvas id="ratingsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Enhanced Top Performers -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Enhanced Top Publications -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-trophy mr-2 text-yellow-600"></i>
                    Top Performing Publications
                </h3>
                <div id="topPublications" class="space-y-4">
                    <!-- Enhanced content will be loaded here -->
                </div>
            </div>

            <!-- Enhanced Active Users -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user-friends mr-2 text-blue-600"></i>
                    Most Active Researchers
                </h3>
                <div id="activeUsers" class="space-y-4">
                    <!-- Enhanced content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Funding Analysis -->
        <div class="card p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-coins mr-2 text-green-600"></i>
                Funding Source Analysis
            </h3>
            <div id="fundingAnalysis" class="overflow-x-auto">
                <!-- Enhanced funding data will be populated here -->
            </div>
        </div>

        <!-- Enhanced Activity Timeline -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-clock mr-2 text-gray-600"></i>
                Recent Research Activity
            </h3>
            <div id="activityTimeline" class="space-y-4">
                <!-- Enhanced content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let submissionsChart, userGrowthChart, statusChart, researchTypeChart, ratingsChart;
        let currentDateRange = '7d';
        let analyticsData = {};

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadDashboardData();
            setDefaultDateRange();
        });

        // Set default date range
        function setDefaultDateRange() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - 7);
            
            document.getElementById('endDate').valueAsDate = endDate;
            document.getElementById('startDate').valueAsDate = startDate;
        }

        // Date range filter functions
        function setDateRange(range) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            currentDateRange = range;
            const endDate = new Date();
            const startDate = new Date();
            
            switch(range) {
                case '7d':
                    startDate.setDate(endDate.getDate() - 7);
                    break;
                case '30d':
                    startDate.setDate(endDate.getDate() - 30);
                    break;
                case '90d':
                    startDate.setDate(endDate.getDate() - 90);
                    break;
                case '1y':
                    startDate.setFullYear(endDate.getFullYear() - 1);
                    break;
            }
            
            document.getElementById('startDate').valueAsDate = startDate;
            document.getElementById('endDate').valueAsDate = endDate;
            
            loadDashboardData();
        }

        function applyCustomRange() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                currentDateRange = 'custom';
                loadDashboardData();
            }
        }

        // Initialize all charts
        function initializeCharts() {
            // Submissions Over Time Chart
            const submissionsCtx = document.getElementById('submissionsChart').getContext('2d');
            submissionsChart = new Chart(submissionsCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Submissions',
                        data: [],
                        borderColor: '#115D5B',
                        backgroundColor: 'rgba(17, 93, 91, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            userGrowthChart = new Chart(userGrowthCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'New Users',
                        data: [],
                        backgroundColor: '#10b981',
                        borderColor: '#059669',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Enhanced Research Type Chart
            const researchTypeCtx = document.getElementById('researchTypeChart').getContext('2d');
            researchTypeChart = new Chart(researchTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#3b82f6', // Blue - Experimental
                            '#10b981', // Green - Observational  
                            '#f59e0b', // Yellow - Review
                            '#ef4444', // Red - Case Study
                            '#8b5cf6'  // Purple - Other
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '60%'
                }
            });

            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Under Review', 'Approved', 'Rejected', 'Published'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#fbbf24',
                            '#3b82f6',
                            '#10b981',
                            '#ef4444',
                            '#8b5cf6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Ratings Chart
            const ratingsCtx = document.getElementById('ratingsChart').getContext('2d');
            ratingsChart = new Chart(ratingsCtx, {
                type: 'bar',
                data: {
                    labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#ef4444',
                            '#f97316',
                            '#eab308',
                            '#22c55e',
                            '#10b981'
                        ],
                        borderColor: '#d97706',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Load dashboard data
        async function loadDashboardData() {
            try {
                showLoading();
                
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                
                // Make API call to get enhanced data
                const response = await fetch(`analytics_data.php?start=${startDate}&end=${endDate}`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Store data globally for other functions
                analyticsData = data;
                
                // Update enhanced metrics
                updateEnhancedKeyMetrics(data.metrics);
                
                // Update charts
                updateSubmissionsChart(data.submissions);
                updateUserGrowthChart(data.userGrowth);
                updateStatusChart(data.statusDistribution);
                updateResearchTypeChart(data.researchTypeDistribution);
                updateRatingsChart(data.ratings);
                
                // Update enhanced lists
                updateEnhancedTopPublications(data.topPublications);
                updateEnhancedActiveUsers(data.activeUsers);
                updateEnhancedActivityTimeline(data.recentActivity);
                updateFundingAnalysis(data.fundingAnalysis);
                
                hideLoading();
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                hideLoading();
                showErrorMessage('Failed to load analytics data. Please try again.');
            }
        }

        // Update enhanced key metrics
        function updateEnhancedKeyMetrics(metrics) {
            // Standard metrics
            document.getElementById('totalUsers').textContent = metrics.totalUsers.toLocaleString();
            document.getElementById('userGrowth').textContent = `${metrics.userGrowth > 0 ? '+' : ''}${metrics.userGrowth}%`;
            document.getElementById('userGrowth').className = metrics.userGrowth > 0 ? 'metric-positive text-sm font-medium' : metrics.userGrowth < 0 ? 'metric-negative text-sm font-medium' : 'metric-neutral text-sm font-medium';
            
            document.getElementById('totalPublications').textContent = metrics.totalPublications.toLocaleString();
            document.getElementById('publicationGrowth').textContent = `${metrics.publicationGrowth > 0 ? '+' : ''}${metrics.publicationGrowth}%`;
            document.getElementById('publicationGrowth').className = metrics.publicationGrowth > 0 ? 'metric-positive text-sm font-medium' : metrics.publicationGrowth < 0 ? 'metric-negative text-sm font-medium' : 'metric-neutral text-sm font-medium';
            
            document.getElementById('totalViews').textContent = metrics.totalViews > 1000 ? `${(metrics.totalViews / 1000).toFixed(1)}k` : metrics.totalViews;
            document.getElementById('viewsGrowth').textContent = `${metrics.viewsGrowth > 0 ? '+' : ''}${metrics.viewsGrowth}%`;
            document.getElementById('viewsGrowth').className = metrics.viewsGrowth > 0 ? 'metric-positive text-sm font-medium' : metrics.viewsGrowth < 0 ? 'metric-negative text-sm font-medium' : 'metric-neutral text-sm font-medium';
            
            document.getElementById('totalDownloads').textContent = metrics.totalDownloads ? metrics.totalDownloads.toLocaleString() : '0';
            
            document.getElementById('pendingReviews').textContent = metrics.pendingReviews;
            document.getElementById('reviewsBacklog').textContent = `${metrics.avgReviewTime} days avg`;
            
            // Enhanced metrics
            document.getElementById('qualityScore').textContent = metrics.avgQualityScore || '0.0';
            
            // Quality stars display
            const stars = '★'.repeat(Math.floor(metrics.avgQualityScore || 0)) + '☆'.repeat(5 - Math.floor(metrics.avgQualityScore || 0));
            document.getElementById('qualityStars').textContent = stars;
            document.getElementById('qualityRatings').textContent = `${metrics.totalRatings || 0} ratings`;
            
            document.getElementById('fundedResearch').textContent = metrics.fundedResearch || '0';
            const fundingPercentage = metrics.totalPublications > 0 ? Math.round((metrics.fundedResearch / metrics.totalPublications) * 100) : 0;
            document.getElementById('fundingPercentage').textContent = `${fundingPercentage}% of total`;
            
            document.getElementById('enhancedSubmissions').textContent = metrics.enhancedSubmissions || '0';
            const completionRate = metrics.totalPublications > 0 ? Math.round((metrics.enhancedSubmissions / metrics.totalPublications) * 100) : 0;
            document.getElementById('completionRate').textContent = `${completionRate}% completion`;
            
            // Generate insights
            updateInsights(metrics);
        }

        function updateInsights(metrics) {
            // User insights
            let userInsight = '';
            if (metrics.userGrowth > 10) {
                userInsight = 'Strong user growth this period! Research community is expanding.';
            } else if (metrics.userGrowth < -5) {
                userInsight = 'User growth declined. Consider outreach initiatives.';
            } else {
                userInsight = 'Steady user growth. Community engagement is stable.';
            }
            document.getElementById('userInsight').textContent = userInsight;
            
            // Publication insights
            let pubInsight = '';
            if (metrics.publicationGrowth > 15) {
                pubInsight = 'Excellent publication growth! Research activity is thriving.';
            } else if (metrics.publicationGrowth < 0) {
                pubInsight = 'Publication submissions decreased. Review submission barriers.';
            } else {
                pubInsight = 'Consistent publication flow. Quality standards maintained.';
            }
            document.getElementById('publicationInsight').textContent = pubInsight;
            
            // Quality insights
            let qualityInsight = '';
            if (metrics.avgQualityScore >= 4) {
                qualityInsight = 'High research quality maintained. Peer review effective.';
            } else if (metrics.avgQualityScore >= 3) {
                qualityInsight = 'Good quality standards. Some room for improvement.';
            } else if (metrics.totalRatings > 0) {
                qualityInsight = 'Quality scores suggest need for review process enhancement.';
            } else {
                qualityInsight = 'Limited quality data available. More reviews needed.';
            }
            document.getElementById('qualityInsight').textContent = qualityInsight;
            
            // Funding insights
            let fundingInsight = '';
            const fundingRate = metrics.totalPublications > 0 ? (metrics.fundedResearch / metrics.totalPublications) * 100 : 0;
            if (fundingRate > 50) {
                fundingInsight = 'Strong external funding support. Well-connected research community.';
            } else if (fundingRate > 25) {
                fundingInsight = 'Moderate funding levels. Opportunities for grant applications.';
            } else {
                fundingInsight = 'Limited external funding. Focus on funding opportunity awareness.';
            }
            document.getElementById('fundingInsight').textContent = fundingInsight;
        }

        function updateSubmissionsChart(data) {
            const labels = data.map(item => new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            const values = data.map(item => item.count);
            
            submissionsChart.data.labels = labels;
            submissionsChart.data.datasets[0].data = values;
            submissionsChart.update();
        }

        function updateUserGrowthChart(data) {
            const labels = data.map(item => new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            const values = data.map(item => item.count);
            
            userGrowthChart.data.labels = labels;
            userGrowthChart.data.datasets[0].data = values;
            userGrowthChart.update();
        }

        function updateStatusChart(data) {
            const values = [data.pending, data.under_review, data.approved, data.rejected, data.published];
            statusChart.data.datasets[0].data = values;
            statusChart.update();
        }

        function updateResearchTypeChart(data) {
            if (!data || data.length === 0) return;
            
            const labels = data.map(item => item.type.charAt(0).toUpperCase() + item.type.slice(1).replace('_', ' '));
            const values = data.map(item => item.count);
            
            researchTypeChart.data.labels = labels;
            researchTypeChart.data.datasets[0].data = values;
            researchTypeChart.update();
            
            // Generate research type insights
            let insights = '';
            if (data.length > 0) {
                const dominant = data[0];
                const total = data.reduce((sum, item) => sum + item.count, 0);
                const percentage = Math.round((dominant.count / total) * 100);
                
                insights = `<div class="bg-blue-50 p-3 rounded-lg">`;
                insights += `<p><strong>${dominant.type.charAt(0).toUpperCase() + dominant.type.slice(1).replace('_', ' ')}</strong> research dominates at ${percentage}%</p>`;
                
                if (dominant.avg_rating > 0) {
                    insights += `<p class="text-sm mt-1">Avg quality: ${dominant.avg_rating}/5 stars</p>`;
                }
                
                if (dominant.type === 'experimental' && percentage > 60) {
                    insights += `<p class="text-green-700 text-sm">✓ Strong focus on original experimental research</p>`;
                } else if (dominant.type === 'review' && percentage > 50) {
                    insights += `<p class="text-yellow-700 text-sm">⚠ Consider encouraging more original research</p>`;
                }
                
                insights += `</div>`;
            }
            document.getElementById('researchTypeInsights').innerHTML = insights;
        }

        function updateRatingsChart(data) {
            const values = [data[1] || 0, data[2] || 0, data[3] || 0, data[4] || 0, data[5] || 0];
            ratingsChart.data.datasets[0].data = values;
            ratingsChart.update();
        }

        function updateEnhancedTopPublications(publications) {
            const container = document.getElementById('topPublications');
            if (!publications || publications.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">No publications data available</p>';
                return;
            }
            
            container.innerHTML = publications.map((pub, index) => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded">#${index + 1}</span>
                            <h4 class="font-medium text-gray-900 truncate" title="${pub.title}">${pub.title.length > 40 ? pub.title.substring(0, 40) + '...' : pub.title}</h4>
                        </div>
                        <div class="flex items-center space-x-2 mb-2">
                            <p class="text-sm text-gray-600">by ${pub.author}</p>
                            ${pub.research_type ? `<span class="research-type-badge ${pub.research_type}">${pub.research_type.replace('_', ' ')}</span>` : ''}
                        </div>
                        ${pub.affiliation ? `<p class="text-xs text-gray-500 mb-2">${pub.affiliation}</p>` : ''}
                        ${pub.funding_source ? `<div class="funding-badge mb-2">Funded: ${pub.funding_source.length > 20 ? pub.funding_source.substring(0, 20) + '...' : pub.funding_source}</div>` : ''}
                        <div class="flex items-center space-x-4">
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-eye mr-1"></i>${pub.views} views
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-download mr-1"></i>${pub.downloads} downloads
                            </span>
                            ${pub.rating > 0 ? `<span class="text-xs text-yellow-600">
                                <i class="fas fa-star mr-1"></i>${pub.rating}/5
                            </span>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function updateEnhancedActiveUsers(users) {
            const container = document.getElementById('activeUsers');
            if (!users || users.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">No active users data available</p>';
                return;
            }
            
            container.innerHTML = users.map(user => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 p-2 rounded-full">
                            <i class="fas fa-user-graduate text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">${user.name}</h4>
                            <div class="text-sm text-gray-600">
                                ${user.submissions} submissions • ${user.views} views
                                ${user.funded_papers > 0 ? ` • ${user.funded_papers} funded` : ''}
                            </div>
                            ${user.avg_rating > 0 ? `<div class="text-xs text-yellow-600">Avg rating: ${user.avg_rating}/5 ⭐</div>` : ''}
                            <p class="text-xs text-gray-500">Last active: ${user.lastActivity}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function updateEnhancedActivityTimeline(activities) {
            const container = document.getElementById('activityTimeline');
            if (!activities || activities.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent activity</p>';
                return;
            }
            
            container.innerHTML = activities.map(activity => `
                <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                    <div class="bg-${activity.color}-100 p-2 rounded-full flex-shrink-0">
                        <i class="${activity.icon} text-${activity.color}-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-900">${activity.description}</p>
                        <p class="text-xs text-gray-500 mt-1">by ${activity.user} • ${activity.time}</p>
                    </div>
                </div>
            `).join('');
        }

        function updateFundingAnalysis(fundingData) {
            const container = document.getElementById('fundingAnalysis');
            if (!fundingData || fundingData.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">No funding data available</p>';
                return;
            }
            
            const tableHTML = `
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left p-3">Funding Source</th>
                            <th class="text-center p-3">Submissions</th>
                            <th class="text-center p-3">Published</th>
                            <th class="text-center p-3">Success Rate</th>
                            <th class="text-center p-3">Avg Review Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${fundingData.map(item => `
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="p-3 font-medium">${item.category}</td>
                                <td class="p-3 text-center">${item.submissions}</td>
                                <td class="p-3 text-center">${item.published}</td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs ${item.publication_rate >= 70 ? 'bg-green-100 text-green-800' : item.publication_rate >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                        ${item.publication_rate}%
                                    </span>
                                </td>
                                <td class="p-3 text-center">${item.avg_review_days ? item.avg_review_days + ' days' : 'N/A'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            container.innerHTML = tableHTML;
        }

        function showLoading() {
            document.querySelectorAll('.loading').forEach(el => el.style.display = 'inline-block');
        }

        function hideLoading() {
            document.querySelectorAll('.loading').forEach(el => el.style.display = 'none');
        }

        function showErrorMessage(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        function refreshData() {
            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
            btn.disabled = true;
            
            loadDashboardData().finally(() => {
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }, 1000);
            });
        }

        function exportEnhancedData() {
            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
            btn.disabled = true;
            
            setTimeout(() => {
                try {
                    const csvData = generateEnhancedCSVReport();
                    downloadCSV(csvData, `enhanced_analytics_report_${new Date().toISOString().split('T')[0]}.csv`);
                } catch (error) {
                    showErrorMessage('Failed to export enhanced data');
                }
                
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 2000);
        }

        function generateEnhancedCSVReport() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            let csv = `CNLRRS Enhanced Analytics Report\n`;
            csv += `Generated on: ${new Date().toLocaleDateString()}\n`;
            csv += `Period: ${startDate} to ${endDate}\n\n`;
            
            // Key Metrics
            csv += `Key Metrics\n`;
            csv += `Metric,Value,Notes\n`;
            csv += `Total Users,${document.getElementById('totalUsers').textContent},All registered users\n`;
            csv += `Total Publications,${document.getElementById('totalPublications').textContent},All submitted papers\n`;
            csv += `Quality Score,${document.getElementById('qualityScore').textContent},Average peer review rating\n`;
            csv += `Funded Research,${document.getElementById('fundedResearch').textContent},Papers with external funding\n`;
            csv += `Complete Submissions,${document.getElementById('enhancedSubmissions').textContent},Papers with full DOST-compliant data\n`;
            csv += `Total Views,${document.getElementById('totalViews').textContent},Paper view count\n`;
            csv += `Total Downloads,${document.getElementById('totalDownloads').textContent},Paper download count\n`;
            csv += `Pending Reviews,${document.getElementById('pendingReviews').textContent},Papers awaiting review\n`;
            csv += `Average Review Time,${document.getElementById('reviewsBacklog').textContent},Days to complete review\n\n`;
            
            // Research Types
            if (analyticsData.researchTypeDistribution) {
                csv += `Research Type Distribution\n`;
                csv += `Type,Count,Average Rating\n`;
                analyticsData.researchTypeDistribution.forEach(type => {
                    csv += `${type.type},${type.count},${type.avg_rating}\n`;
                });
                csv += `\n`;
            }
            
            // Funding Analysis
            if (analyticsData.fundingAnalysis) {
                csv += `Funding Analysis\n`;
                csv += `Source,Submissions,Published,Success Rate,Avg Review Days\n`;
                analyticsData.fundingAnalysis.forEach(funding => {
                    csv += `"${funding.category}",${funding.submissions},${funding.published},${funding.publication_rate}%,${funding.avg_review_days || 'N/A'}\n`;
                });
            }
            
            return csv;
        }

        function downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</body>
</html>