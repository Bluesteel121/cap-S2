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

            <!-- Research Types -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar mr-2 text-indigo-600"></i>
                    Research Types
                </h3>
                <div class="chart-container">
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>

            <!-- Views vs Downloads -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-eye mr-2 text-yellow-600"></i>
                    Views vs Downloads
                </h3>
                <div class="chart-container">
                    <canvas id="metricsChart"></canvas>
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
                    <div class="text-center py-8 text-gray-500">
                        <span class="loading"></span>
                        <p class="mt-2">Loading data...</p>
                    </div>
                </div>
            </div>

            <!-- Most Active Users -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user-friends mr-2 text-blue-600"></i>
                    Most Active Users
                </h3>
                <div id="activeUsers" class="space-y-4">
                    <div class="text-center py-8 text-gray-500">
                        <span class="loading"></span>
                        <p class="mt-2">Loading data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-clock mr-2 text-gray-600"></i>
                Recent System Activity
            </h3>
            <div id="activityTimeline" class="space-y-4">
                <div class="text-center py-8 text-gray-500">
                    <span class="loading"></span>
                    <p class="mt-2">Loading activities...</p>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card p-6 text-center">
                <div class="text-3xl font-bold text-blue-600" id="totalUsers">0</div>
                <div class="text-sm text-gray-600 mt-2">Total Users</div>
            </div>
            <div class="card p-6 text-center">
                <div class="text-3xl font-bold text-green-600" id="totalSubmissions">0</div>
                <div class="text-sm text-gray-600 mt-2">Total Submissions</div>
            </div>
            <div class="card p-6 text-center">
                <div class="text-3xl font-bold text-purple-600" id="totalViews">0</div>
                <div class="text-sm text-gray-600 mt-2">Total Views</div>
            </div>
            <div class="card p-6 text-center">
                <div class="text-3xl font-bold text-orange-600" id="totalDownloads">0</div>
                <div class="text-sm text-gray-600 mt-2">Total Downloads</div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let submissionsChart, userGrowthChart, statusChart, categoriesChart, metricsChart;
        let currentDateRange = '7d';

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            setDefaultDateRange();
            loadDashboardData();
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
                        legend: { display: true }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });

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
                    plugins: { legend: { display: true } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });

            const statusCtx = document.getElementById('statusChart').getContext('2d');
            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Under Review', 'Approved', 'Rejected'],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#fbbf24', '#3b82f6', '#10b981', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            categoriesChart = new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Count',
                        data: [],
                        backgroundColor: '#6366f1',
                        borderColor: '#4f46e5',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { ticks: { maxRotation: 45 } }
                    }
                }
            });

            const metricsCtx = document.getElementById('metricsChart').getContext('2d');
            metricsChart = new Chart(metricsCtx, {
                type: 'bar',
                data: {
                    labels: ['Views', 'Downloads'],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#8b5cf6', '#f59e0b'],
                        borderColor: ['#7c3aed', '#d97706'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                
                const response = await fetch(`analytics_data.php?start=${startDate}&end=${endDate}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    console.error('Server error:', data.error);
                    showError('Failed to load data: ' + data.error);
                    return;
                }
                
                updateCharts(data);
                updateSummaryStats(data);
                updateTopPublications(data.topPublications || []);
                updateActiveUsers(data.activeUsers || []);
                updateActivityTimeline(data.recentActivity || []);
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showError('Failed to connect to server. Please check your connection.');
            }
        }

        function updateCharts(data) {
            // Update submissions chart
            if (data.submissions && data.submissions.length > 0) {
                const labels = data.submissions.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const values = data.submissions.map(item => item.count);
                
                submissionsChart.data.labels = labels;
                submissionsChart.data.datasets[0].data = values;
                submissionsChart.update();
            }

            // Update user growth chart
            if (data.userGrowth && data.userGrowth.length > 0) {
                const labels = data.userGrowth.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const values = data.userGrowth.map(item => item.count);
                
                userGrowthChart.data.labels = labels;
                userGrowthChart.data.datasets[0].data = values;
                userGrowthChart.update();
            }

            // Update status chart
            if (data.statusDistribution) {
                const values = [
                    data.statusDistribution.pending || 0,
                    data.statusDistribution.under_review || 0,
                    data.statusDistribution.approved || 0,
                    data.statusDistribution.rejected || 0
                ];
                statusChart.data.datasets[0].data = values;
                statusChart.update();
            }

            // Update categories chart
            if (data.categories && Object.keys(data.categories).length > 0) {
                const labels = Object.keys(data.categories);
                const values = Object.values(data.categories);
                
                categoriesChart.data.labels = labels;
                categoriesChart.data.datasets[0].data = values;
                categoriesChart.update();
            }

            // Update metrics chart
            if (data.metrics) {
                const views = data.metrics.totalViews || 0;
                const downloads = data.metrics.totalDownloads || 0;
                
                metricsChart.data.datasets[0].data = [views, downloads];
                metricsChart.update();
            }
        }

        function updateSummaryStats(data) {
            if (data.metrics) {
                document.getElementById('totalUsers').textContent = (data.metrics.totalUsers || 0).toLocaleString();
                document.getElementById('totalSubmissions').textContent = (data.metrics.totalPublications || 0).toLocaleString();
                document.getElementById('totalViews').textContent = (data.metrics.totalViews || 0).toLocaleString();
                document.getElementById('totalDownloads').textContent = (data.metrics.totalDownloads || 0).toLocaleString();
            }
        }

        function updateTopPublications(publications) {
            const container = document.getElementById('topPublications');
            
            if (!publications || publications.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No publications found</div>';
                return;
            }
            
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
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function updateActiveUsers(users) {
            const container = document.getElementById('activeUsers');
            
            if (!users || users.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No active users found</div>';
                return;
            }
            
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
            
            if (!activities || activities.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No recent activities</div>';
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

        function showError(message) {
            alert(message);
        }

        function refreshData() {
            const btn = event.target;
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

        function exportData() {
            const btn = event.target;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
            btn.disabled = true;
            
            setTimeout(() => {
                const csvData = generateCSVReport();
                downloadCSV(csvData, `analytics_report_${new Date().toISOString().split('T')[0]}.csv`);
                
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 1000);
        }

        function generateCSVReport() {
            const totalUsers = document.getElementById('totalUsers').textContent;
            const totalSubmissions = document.getElementById('totalSubmissions').textContent;
            const totalViews = document.getElementById('totalViews').textContent;
            const totalDownloads = document.getElementById('totalDownloads').textContent;
            
            return `Metric,Value,Generated
Total Users,${totalUsers},${new Date().toLocaleString()}
Total Submissions,${totalSubmissions},${new Date().toLocaleString()}
Total Views,${totalViews},${new Date().toLocaleString()}
Total Downloads,${totalDownloads},${new Date().toLocaleString()}`;
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
    </script>
</body>
</html>