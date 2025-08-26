<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNLRRS - Analytics Dashboard</title>
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
                    <button onclick="exportData()" class="bg-yellow-500 hover:bg-yellow-600 text-black px-4 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-download mr-2"></i>Export Report
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

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalUsers">
                            <span class="loading"></span>
                        </p>
                        <div class="flex items-center mt-2">
                            <span class="metric-positive text-sm font-medium" id="userGrowth">+0%</span>
                            <span class="text-xs text-gray-500 ml-1">vs previous period</span>
                        </div>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Publications</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalPublications">
                            <span class="loading"></span>
                        </p>
                        <div class="flex items-center mt-2">
                            <span class="metric-positive text-sm font-medium" id="publicationGrowth">+0%</span>
                            <span class="text-xs text-gray-500 ml-1">vs previous period</span>
                        </div>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-file-alt text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Views</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalViews">
                            <span class="loading"></span>
                        </p>
                        <div class="flex items-center mt-2">
                            <span class="metric-positive text-sm font-medium" id="viewsGrowth">+0%</span>
                            <span class="text-xs text-gray-500 ml-1">vs previous period</span>
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
                        <p class="text-sm text-gray-600 font-medium">Pending Reviews</p>
                        <p class="text-3xl font-bold text-gray-900" id="pendingReviews">
                            <span class="loading"></span>
                        </p>
                        <div class="flex items-center mt-2">
                            <span class="metric-neutral text-sm font-medium" id="reviewsBacklog">0 days avg</span>
                            <span class="text-xs text-gray-500 ml-1">review time</span>
                        </div>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
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

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
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

            <!-- Research Categories -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar mr-2 text-indigo-600"></i>
                    Research Categories
                </h3>
                <div class="chart-container">
                    <canvas id="categoriesChart"></canvas>
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

        <!-- Top Performers -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Publications -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-trophy mr-2 text-yellow-600"></i>
                    Top Performing Publications
                </h3>
                <div id="topPublications" class="space-y-4">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>

            <!-- Most Active Users -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user-friends mr-2 text-blue-600"></i>
                    Most Active Users
                </h3>
                <div id="activeUsers" class="space-y-4">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-clock mr-2 text-gray-600"></i>
                Recent System Activity
            </h3>
            <div id="activityTimeline" class="space-y-4">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let submissionsChart, userGrowthChart, statusChart, categoriesChart, ratingsChart;
        let currentDateRange = '7d';

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
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
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
                // Remove active class from preset buttons
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

            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            categoriesChart = new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: '#6366f1',
                        borderColor: '#4f46e5',
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
                        },
                        x: {
                            ticks: {
                                maxRotation: 45
                            }
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
                
                // Simulate API calls - replace with actual PHP endpoints
                const response = await fetch(`analytics_data.php?start=${startDate}&end=${endDate}`);
                const data = await response.json();
                
                // Update key metrics
                updateKeyMetrics(data.metrics);
                
                // Update charts
                updateSubmissionsChart(data.submissions);
                updateUserGrowthChart(data.userGrowth);
                updateStatusChart(data.statusDistribution);
                updateCategoriesChart(data.categories);
                updateRatingsChart(data.ratings);
                
                // Update lists
                updateTopPublications(data.topPublications);
                updateActiveUsers(data.activeUsers);
                updateActivityTimeline(data.recentActivity);
                
                hideLoading();
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                // Load demo data if API fails
                loadDemoData();
                hideLoading();
            }
        }

        // Demo data for testing
        function loadDemoData() {
            // Key metrics
            updateKeyMetrics({
                totalUsers: 2847,
                userGrowth: 12.5,
                totalPublications: 1234,
                publicationGrowth: 8.2,
                totalViews: 89234,
                viewsGrowth: 24.1,
                pendingReviews: 45,
                avgReviewTime: 5.2
            });

            // Sample chart data
            updateSubmissionsChart([
                { date: '2025-08-19', count: 5 },
                { date: '2025-08-20', count: 8 },
                { date: '2025-08-21', count: 3 },
                { date: '2025-08-22', count: 12 },
                { date: '2025-08-23', count: 7 },
                { date: '2025-08-24', count: 9 },
                { date: '2025-08-25', count: 15 },
                { date: '2025-08-26', count: 6 }
            ]);

            updateUserGrowthChart([
                { date: '2025-08-19', count: 25 },
                { date: '2025-08-20', count: 32 },
                { date: '2025-08-21', count: 18 },
                { date: '2025-08-22', count: 41 },
                { date: '2025-08-23', count: 28 },
                { date: '2025-08-24', count: 35 },
                { date: '2025-08-25', count: 52 },
                { date: '2025-08-26', count: 29 }
            ]);

            updateStatusChart({
                pending: 45,
                under_review: 23,
                approved: 156,
                rejected: 12,
                published: 998
            });

            updateCategoriesChart({
                'Agricultural Research': 234,
                'Crop Science': 189,
                'Soil Science': 145,
                'Plant Pathology': 98,
                'Entomology': 76,
                'Food Technology': 123,
                'Sustainable Agriculture': 167,
                'Climate Change Agriculture': 89
            });

            updateRatingsChart({
                1: 5,
                2: 12,
                3: 45,
                4: 123,
                5: 98
            });

            // Sample top publications
            updateTopPublications([
                {
                    title: 'Sustainable Pineapple Cultivation Techniques',
                    author: 'Dr. Maria Santos',
                    views: 1234,
                    downloads: 456,
                    rating: 4.8
                },
                {
                    title: 'Climate-Resilient Rice Varieties',
                    author: 'Prof. Juan Dela Cruz',
                    views: 987,
                    downloads: 321,
                    rating: 4.7
                },
                {
                    title: 'Integrated Pest Management in Corn Production',
                    author: 'Dr. Ana Rodriguez',
                    views: 876,
                    downloads: 298,
                    rating: 4.6
                }
            ]);

            // Sample active users
            updateActiveUsers([
                {
                    name: 'Dr. Maria Santos',
                    submissions: 12,
                    views: 5432,
                    lastActivity: '2 hours ago'
                },
                {
                    name: 'Prof. Juan Dela Cruz',
                    submissions: 8,
                    views: 3456,
                    lastActivity: '1 day ago'
                },
                {
                    name: 'Dr. Ana Rodriguez',
                    submissions: 6,
                    views: 2341,
                    lastActivity: '3 hours ago'
                }
            ]);

            // Sample recent activity
            updateActivityTimeline([
                {
                    type: 'submission',
                    user: 'Dr. Maria Santos',
                    description: 'Submitted new paper: "Advanced Irrigation Techniques"',
                    time: '5 minutes ago',
                    icon: 'fas fa-file-upload',
                    color: 'blue'
                },
                {
                    type: 'approval',
                    user: 'Admin',
                    description: 'Approved paper: "Sustainable Farming Practices"',
                    time: '1 hour ago',
                    icon: 'fas fa-check-circle',
                    color: 'green'
                },
                {
                    type: 'review',
                    user: 'Dr. Juan Dela Cruz',
                    description: 'Completed review for "Climate Change Impact Study"',
                    time: '2 hours ago',
                    icon: 'fas fa-star',
                    color: 'yellow'
                },
                {
                    type: 'registration',
                    user: 'New User',
                    description: 'Emily Chen registered as a new researcher',
                    time: '4 hours ago',
                    icon: 'fas fa-user-plus',
                    color: 'purple'
                }
            ]);
        }

        // Update functions for each section
        function updateKeyMetrics(metrics) {
            document.getElementById('totalUsers').textContent = metrics.totalUsers.toLocaleString();
            document.getElementById('userGrowth').textContent = `+${metrics.userGrowth}%`;
            document.getElementById('userGrowth').className = metrics.userGrowth > 0 ? 'metric-positive text-sm font-medium' : 'metric-negative text-sm font-medium';
            
            document.getElementById('totalPublications').textContent = metrics.totalPublications.toLocaleString();
            document.getElementById('publicationGrowth').textContent = `+${metrics.publicationGrowth}%`;
            document.getElementById('publicationGrowth').className = metrics.publicationGrowth > 0 ? 'metric-positive text-sm font-medium' : 'metric-negative text-sm font-medium';
            
            document.getElementById('totalViews').textContent = `${(metrics.totalViews / 1000).toFixed(1)}k`;
            document.getElementById('viewsGrowth').textContent = `+${metrics.viewsGrowth}%`;
            document.getElementById('viewsGrowth').className = metrics.viewsGrowth > 0 ? 'metric-positive text-sm font-medium' : 'metric-negative text-sm font-medium';
            
            document.getElementById('pendingReviews').textContent = metrics.pendingReviews;
            document.getElementById('reviewsBacklog').textContent = `${metrics.avgReviewTime} days avg`;
        }

        function updateSubmissionsChart(data) {
            const labels = data.map(item => new Date(item.date).toLocaleDateString());
            const values = data.map(item => item.count);
            
            submissionsChart.data.labels = labels;
            submissionsChart.data.datasets[0].data = values;
            submissionsChart.update();
        }

        function updateUserGrowthChart(data) {
            const labels = data.map(item => new Date(item.date).toLocaleDateString());
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

        function updateCategoriesChart(data) {
            const labels = Object.keys(data);
            const values = Object.values(data);
            
            categoriesChart.data.labels = labels;
            categoriesChart.data.datasets[0].data = values;
            categoriesChart.update();
        }

        function updateRatingsChart(data) {
            const values = [data[1] || 0, data[2] || 0, data[3] || 0, data[4] || 0, data[5] || 0];
            ratingsChart.data.datasets[0].data = values;
            ratingsChart.update();
        }

        function updateTopPublications(publications) {
            const container = document.getElementById('topPublications');
            container.innerHTML = publications.map((pub, index) => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded">#${index + 1}</span>
                            <h4 class="font-medium text-gray-900 truncate">${pub.title}</h4>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">by ${pub.author}</p>
                        <div class="flex items-center space-x-4 mt-2">
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-eye mr-1"></i>${pub.views} views
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-download mr-1"></i>${pub.downloads} downloads
                            </span>
                            <span class="text-xs text-yellow-600">
                                <i class="fas fa-star mr-1"></i>${pub.rating}
                            </span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function updateActiveUsers(users) {
            const container = document.getElementById('activeUsers');
            container.innerHTML = users.map(user => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 p-2 rounded-full">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">${user.name}</h4>
                            <p class="text-sm text-gray-600">${user.submissions} submissions • ${user.views} views</p>
                            <p class="text-xs text-gray-500">Last active: ${user.lastActivity}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function updateActivityTimeline(activities) {
            const container = document.getElementById('activityTimeline');
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

        function showLoading() {
            // Show loading states for metrics
            document.querySelectorAll('.loading').forEach(el => el.style.display = 'inline-block');
        }

        function hideLoading() {
            // Hide loading states
            document.querySelectorAll('.loading').forEach(el => el.style.display = 'none');
        }

        function refreshData() {
            const btn = event.target;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
            btn.disabled = true;
            
            loadDashboardData().then(() => {
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }, 1000);
            });
        }

        function exportData() {
            const btn = event.target;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
            btn.disabled = true;
            
            // Simulate export process
            setTimeout(() => {
                // Create CSV data
                const csvData = generateCSVReport();
                downloadCSV(csvData, `analytics_report_${new Date().toISOString().split('T')[0]}.csv`);
                
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 2000);
        }

        function generateCSVReport() {
            // Generate sample CSV data
            return `Metric,Value,Period
Total Users,2847,Last 7 days
Total Publications,1234,All time
Total Views,89234,Last 7 days
Pending Reviews,45,Current
Average Review Time,5.2 days,Last 30 days`;
        }

        function downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', filename);
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Initialize with demo data
        loadDemoData();
    </script>
</body>
</html>