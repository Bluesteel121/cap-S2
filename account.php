<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Selection</title>
    <!-- Include Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles for responsiveness and appearance */
        @media (min-width: 640px) {
            .option-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <h1 class="text-2xl font-bold text-center text-blue-600 mb-6">Select Your Account Type</h1>
        <div class="grid grid-cols-1 option-grid gap-4 mb-6">
            <a href="/adminlogin.php" class="block p-4 border border-blue-500 rounded-lg text-blue-600 hover:bg-blue-600 hover:text-white transition-colors duration-200">
                <div class="text-lg font-semibold">Admin</div>
            </a>
            <a href="/userlogin.php" class="block p-4 border border-green-500 rounded-lg text-green-600 hover:bg-green-600 hover:text-white transition-colors duration-200">
                <div class="text-lg font-semibold">User</div>
            </a>
        </div>
        <a href="/" class="inline-block px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors duration-200">
            Back to Home
        </a>
    </div>
</body>
</html>

