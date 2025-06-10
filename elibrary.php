<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queen Pineapple Research E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navigation Bar -->
    <nav class="bg-[#115D5B] text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="Images/logo.png" alt="CNLRRS Logo" class="h-10 w-10 mr-2">
                <span class="text-xl font-bold">CNLRRS Rainfed Research Station</span>
            </div>
            <div class="space-x-4">
                <a href="#" class="hover:underline">Home</a>
                <a href="#" class="hover:underline">Our Services</a>
                <a href="#" class="hover:underline">About Us</a>
            </div>
            <button id="loginBtn" class="bg-[#F2C94C] hover:bg-yellow-500 text-[#115D5B] font-bold py-2 px-4 rounded">Log In</button>
            <div class="flex items-center space-x-4">
            </div>
        </div>
    </nav>

    <!-- Main Content -->
   <div class="container mx-auto p-4">
    <!-- Hero Section -->
    <div class="flex flex-col md:flex-row items-center justify-between bg-white rounded-lg p-6 mb-8 shadow-md">
        <div class="md:w-1/2 mb-4 md:mb-0">
            <h1 class="text-3xl font-bold text-[#115D5B] mb-2">CNLRRS Queen Pineapple Research Repository</h1>
            <p class="text-gray-700 mb-4">Access the latest research, studies, and publications about Queen Pineapple varieties, cultivation, health benefits, and more.</p>
            <div class="flex space-x-2">
                <button class="bg-[#115D5B] hover:bg-green-600 text-white px-6 py-2 rounded-lg">Browse Research</button>
                <button class="bg-[#115D5B] hover:bg-green-600 text-white px-6 py-2 rounded-lg">Submit Paper</button>
            </div>
        </div>
        <div class="md:w-1/3">
 <img src="Images/md2.jpg" alt="Queen Pineapple" class="rounded-lg shadow-md w-full h-auto max-w-md" />
        </div>
    </div>
</div>
        <!-- Search Section -->
        <div class="bg-white rounded-lg p-6 mb-8 shadow-md">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Advanced Search</h2>
            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <div class="flex-1">
                    <input type="text" placeholder="Search keywords" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                </div>
                <div class="md:w-1/4">
                    <select class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <option value="">Category</option>
                        <option value="cultivation">Cultivation</option>
                        <option value="genetics">Genetics</option>
                        <option value="nutrition">Nutrition</option>
                        <option value="processing">Processing</option>
                        <option value="market">Market Research</option>
                    </select>
                </div>
                <div class="md:w-1/4">
                    <select class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <option value="">Publication Year</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="older">2020 & Older</option>
                    </select>
                </div>
                <button class="bg-[#115D5B] hover:bg-green-600 text-white px-6 py-3 rounded-lg">Search</button>
            </div>
        </div>

        <!-- Research Categories -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl text-[#115D5B] mb-4"><i class="fas fa-seedling"></i></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Cultivation & Production</h3>
                <p class="text-gray-600 mb-4">Research on optimal growing conditions, disease resistance, and production techniques.</p>
                <button class="text-yellow-600 hover:text-yellow-700 font-semibold">View 28 Papers →</button>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl text-[#115D5B] mb-4"><i class="fas fa-dna"></i></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Genetics & Breeding</h3>
                <p class="text-gray-600 mb-4">Studies on genetic improvement, variety development, and hybridization.</p>
                <button class="text-yellow-600 hover:text-yellow-700 font-semibold">View 16 Papers →</button>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl text-[#115D5B] mb-4"><i class="fas fa-apple-alt"></i></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Nutritional Benefits</h3>
                <p class="text-gray-600 mb-4">Research on health benefits, nutritional content, and medical applications.</p>
                <button class="text-yellow-600 hover:text-yellow-700 font-semibold">View 22 Papers →</button>
            </div>
        </div>

        <!-- Featured Research -->
        <div class="bg-white rounded-lg p-6 mb-8 shadow-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Featured Research</h2>
            <div class="space-y-6">
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-yellow-600 mb-2">Enhanced Bromelain Extraction from Queen Pineapple Crown</h3>
                    <div class="flex items-center space-x-2 text-gray-600 mb-2">
                        <span>Authors: Rodriguez, M.L., Lee, S.J., Tanaka, H.</span>
                        <span>•</span>
                        <span>2024</span>
                        <span>•</span>
                        <span>Journal of Food Biochemistry</span>
                    </div>
                    <p class="text-gray-700 mb-3">This study presents a novel method for extracting higher yields of bromelain enzyme from Queen pineapple crown waste, improving the sustainability of pineapple processing.</p>
                    <div class="flex items-center space-x-4">
                        <button class="text-blue-600 hover:text-blue-800 font-medium">Abstract</button>
                        <button class="text-green-600 hover:text-green-800 font-medium">Download PDF</button>
                        <button class="text-gray-600 hover:text-gray-800 font-medium">Cite</button>
                    </div>
                </div>
                
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-yellow-600 mb-2">Comparative Analysis of Antioxidant Properties in Five Pineapple Varieties</h3>
                    <div class="flex items-center space-x-2 text-gray-600 mb-2">
                        <span>Authors: Garcia, A.P., Singh, D., Williams, R.T.</span>
                        <span>•</span>
                        <span>2023</span>
                        <span>•</span>
                        <span>International Journal of Fruit Science</span>
                    </div>
                    <p class="text-gray-700 mb-3">This research compares the antioxidant profiles of five pineapple varieties, with Queen pineapple showing significant advantages in certain bioactive compounds.</p>
                    <div class="flex items-center space-x-4">
                        <button class="text-blue-600 hover:text-blue-800 font-medium">Abstract</button>
                        <button class="text-green-600 hover:text-green-800 font-medium">Download PDF</button>
                        <button class="text-gray-600 hover:text-gray-800 font-medium">Cite</button>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-semibold text-yellow-600 mb-2">Drought Tolerance in Queen Pineapple: Physiological Responses and Genetic Markers</h3>
                    <div class="flex items-center space-x-2 text-gray-600 mb-2">
                        <span>Authors: Patel, K., Nguyen, T., Fernandez, J.</span>
                        <span>•</span>
                        <span>2023</span>
                        <span>•</span>
                        <span>Agricultural Water Management</span>
                    </div>
                    <p class="text-gray-700 mb-3">An investigation into the drought resistance mechanisms in Queen pineapple cultivars, identifying key genetic markers for future breeding programs.</p>
                    <div class="flex items-center space-x-4">
                        <button class="text-blue-600 hover:text-blue-800 font-medium">Abstract</button>
                        <button class="text-green-600 hover:text-green-800 font-medium">Download PDF</button>
                        <button class="text-gray-600 hover:text-gray-800 font-medium">Cite</button>
                    </div>
                </div>
            </div>
            <div class="mt-6 text-center">
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg">View All Research</button>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-md text-center">
                <div class="text-4xl font-bold text-yellow-600 mb-2">127</div>
                <p class="text-gray-700">Research Papers</p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md text-center">
                <div class="text-4xl font-bold text-yellow-600 mb-2">83</div>
                <p class="text-gray-700">Researchers</p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md text-center">
                <div class="text-4xl font-bold text-yellow-600 mb-2">14</div>
                <p class="text-gray-700">Partner Institutions</p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md text-center">
                <div class="text-4xl font-bold text-yellow-600 mb-2">5</div>
                <p class="text-gray-700">Active Projects</p>
            </div>
        </div>

        <!-- Recent Publications -->
        <div class="bg-white rounded-lg p-6 mb-8 shadow-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Recent Publications</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="py-3 px-4 text-left">Title</th>
                            <th class="py-3 px-4 text-left">Authors</th>
                            <th class="py-3 px-4 text-left">Journal</th>
                            <th class="py-3 px-4 text-left">Year</th>
                            <th class="py-3 px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4 text-yellow-600 font-medium">Micropropagation of Queen Pineapple Using Temporary Immersion Systems</td>
                            <td class="py-3 px-4">Chen, L., Oliveira, M.</td>
                            <td class="py-3 px-4">Plant Cell Reports</td>
                            <td class="py-3 px-4">2024</td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-800"><i class="fas fa-download"></i></button>
                                    <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-quote-right"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4 text-yellow-600 font-medium">Market Trends and Consumer Preferences for Queen Pineapple in Asian Markets</td>
                            <td class="py-3 px-4">Kim, S., Jackson, P.</td>
                            <td class="py-3 px-4">Journal of Food Marketing</td>
                            <td class="py-3 px-4">2024</td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-800"><i class="fas fa-download"></i></button>
                                    <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-quote-right"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4 text-yellow-600 font-medium">Bioactive Compounds in Queen Pineapple and Their Anti-inflammatory Properties</td>
                            <td class="py-3 px-4">Martinez, C., Ahmed, K.</td>
                            <td class="py-3 px-4">Food Chemistry</td>
                            <td class="py-3 px-4">2023</td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-800"><i class="fas fa-download"></i></button>
                                    <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-quote-right"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4 text-yellow-600 font-medium">Climate Change Impacts on Queen Pineapple Cultivation in Southeast Asia</td>
                            <td class="py-3 px-4">Wong, R., Brown, A.</td>
                            <td class="py-3 px-4">Climate Change Biology</td>
                            <td class="py-3 px-4">2023</td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-800"><i class="fas fa-download"></i></button>
                                    <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-quote-right"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-yellow-600 font-medium">Postharvest Quality Preservation of Queen Pineapple Using Modified Atmosphere Packaging</td>
                            <td class="py-3 px-4">Sharma, V., Wilson, T.</td>
                            <td class="py-3 px-4">Postharvest Biology and Technology</td>
                            <td class="py-3 px-4">2023</td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-800"><i class="fas fa-download"></i></button>
                                    <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-quote-right"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <div class="text-gray-600">Showing 5 of 127 papers</div>
                <div class="flex space-x-2">
                    <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg disabled:opacity-50" disabled>Previous</button>
                    <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">Next</button>
                </div>
            </div>
        </div>

        <!-- Submit Research & Contact -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Submit Your Research</h2>
                <p class="text-gray-700 mb-4">Are you a researcher working on Queen Pineapple? Submit your work to our e-library and reach a global audience of specialists.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Research Title</label>
                        <input type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Abstract</label>
                        <textarea class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 h-24"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Upload Paper (PDF)</label>
                        <div class="border border-dashed border-gray-300 rounded-lg p-4 text-center">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-600">Drag and drop your file here, or click to browse</p>
                        </div>
                    </div>
                    <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg">Submit Research</button>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Contact Us</h2>
                <p class="text-gray-700 mb-4">Have questions about the e-library or need assistance with research submissions? Contact our team.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Your Name</label>
                        <input type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Email Address</label>
                        <input type="email" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Message</label>
                        <textarea class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 h-24"></textarea>
                    </div>
                    <button class="w-full bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg">Send Message</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-8">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Queen Pineapple Research E-Library</h3>
                    <p class="text-gray-400">A comprehensive repository of research papers and studies focused on Queen Pineapple varieties, cultivation methods, nutritional benefits, and more.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Browse Research</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Submit Paper</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">About Queen Pineapple</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Research Categories</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Cultivation & Production</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Genetics & Breeding</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Nutritional Benefits</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Processing & Technology</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Market Research</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4 mb-4">
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-instagram"></i></a>
                    </div>
                    <p class="text-gray-400">Subscribe to our newsletter for updates on the latest research.</p>
                    <div class="flex mt-2">
                        <input type="email" placeholder="Your email" class="p-2 rounded-l-lg w-full focus:outline-none" />
                        <button class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-r-lg">Subscribe</button>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400">
                <p>&copy; 2025 Queen Pineapple Research E-Library. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal (Hidden by default) -->
    <div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Login</h2>
                <button id="closeLoginModal" class="text-gray-500 hover:text-gray-800 text-xl">&times;</button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Email Address</label>
                    <input type="email" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Password</label>
                    <input type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" class="mr-2" />
                        <label for="remember" class="text-gray-700">Remember me</label>
                    </div>
                    <a href="#" class="text-yellow-600 hover:text-yellow-700">Forgot password?</a>
                </div>
                <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg">Login</button>
                <p class="text-center text-gray-700">Don't have an account? <a href="#" class="text-yellow-600 hover:text-yellow-700">Sign up</a></p>
            </div>
        </div>
    </div>

    <!-- Basic JavaScript for Modal Functionality -->
    <script>
        // Login Modal
        const loginBtn = document.getElementById('loginBtn');
        const loginModal = document.getElementById('loginModal');
        const closeLoginModal = document.getElementById('closeLoginModal');

        loginBtn.addEventListener('click', () => {
            loginModal.classList.remove('hidden');
        });

        closeLoginModal.addEventListener('click', () => {
            loginModal.classList.add('hidden');
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>