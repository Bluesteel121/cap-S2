<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Account Type</title>
  <link rel="icon" href="Images/Favicon.ico">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/YOUR_FONT_AWESOME_KIT.js" crossorigin="anonymous"></script>
</head>
<body class="min-h-screen flex flex-col md:flex-row relative">

  <!-- Left Section -->
<div class="w-full md:w-1/2 bg-[#115D5B] text-white flex flex-col justify-center items-center p-6">
    <h1 class="text-2xl md:text-4xl font-bold mb-6 text-center">WELCOME TO CNLRRS</h1>
    <div class="flex flex-col md:flex-row items-center md:space-x-6 space-y-4 md:space-y-0">
        
        <!-- Admin Button -->
        <button onclick="redirectToLogin('admin')" class="flex flex-col items-center group">
            <div class="bg-white w-[150px] h-[140px] p-4 rounded-lg shadow-lg flex flex-col items-center justify-center transition-all duration-300 group-hover:shadow-xl group-hover:scale-105">
                <img src="Images/adminlogo.png" alt="Admin Logo" class="h-12 mb-2" />
                <i class="fas fa-user-cog text-[#115D5B] text-3xl"></i>
            </div>
            <p class="mt-2 text-lg font-semibold">Admin/reviewer</p>
        </button>

      
        <!-- User Button -->
        <button onclick="redirectToLogin('user')" class="flex flex-col items-center group">
            <div class="bg-white w-[150px] h-[140px] p-4 rounded-lg shadow-lg flex flex-col items-center justify-center transition-all duration-300 group-hover:shadow-xl group-hover:scale-105">
                <img src="Images/farmerlogo.png" alt="User Logo" class="h-12 mb-2" />
                <i class="fas fa-users text-[#115D5B] text-3xl"></i>
            </div>
            <p class="mt-2 text-lg font-semibold">User</p>
        </button>

    </div>

    <!-- Back to Home Button (now centered below all buttons) -->
    <div class="mt-6 flex justify-center">
        <a href="index.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-gray-900 transition-all duration-300 ease-in-out group">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 text-gray-500 group-hover:text-gray-700 transform group-hover:-translate-x-0.5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Home
        </a>
    </div>
</div>

 <!-- Right Section -->
<div class="w-full md:w-1/2 bg-white text-black flex flex-col justify-center items-center p-6">
    <div class="flex justify-center space-x-4 mb-4 flex-wrap">
        <img src="Images/logo1.png" alt="Logo 1" class="h-32 md:h-40" />
        <img src="Images/logo2.png" alt="Logo 2" class="h-32 md:h-40" />
    </div>
    <h2 class="text-center font-bold text-lg md:text-xl mt-4 text-gray-900">
        DEPARTMENT OF AGRICULTURE RFO 5
    </h2>
    <h3 class="text-center font-bold text-base md:text-lg mt-2 text-gray-900">
        CAMARINES NORTE LOWLAND RAINFED RESEARCH STATION
    </h3>
    
    <div class="mt-4 text-center">
        <p class="flex justify-center items-center gap-2 font-semibold text-gray-800">
            <span class="text-gray-700">📍</span> Calasgasan, Daet, Camarines Norte
        </p>
        <p class="flex justify-center items-center gap-2 mt-2 font-semibold text-gray-800">
            <span class="text-gray-700">📧</span> dacnlrrs@gmail.com
        </p>
        <p class="flex flex-col items-center gap-1 mt-2 font-semibold text-gray-800">
            <span>👤 Engr. Bella B. Frias</span>
            <span class="text-sm font-medium text-gray-600">
                Superintendent/Agricultural Center Chief III
            </span>
        </p>
    </div>
</div>

 <!-- LOG-In Script -->
  <script>
    function redirectToLogin(role) {
      const routes = {
        admin: "adminlogin.php",
        reviewer: "adminlogin.php", // Both admin and reviewer use the same login page
        user: "userlogin.php",
      };
      window.location.href = routes[role];
    }
  </script>
</body>
</html>