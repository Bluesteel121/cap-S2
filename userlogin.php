<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="h-screen flex relative bg-gray-100">
    <!-- Login Container -->
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">

        <!-- Error/Success Messages -->
        <?php
        if (isset($_SESSION['login_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['login_error']) .
                 "</div>";
            unset($_SESSION['login_error']);
        }
        if (isset($_SESSION['registration_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['registration_success']) .
                 "</div>";
            unset($_SESSION['registration_success']);
        }
        ?>

        <!-- Login Form -->
        <div id="login-section">
            <h2 class="text-2xl font-bold text-center mb-4">User Login</h2>
            <form id="login-form" method="POST" action="user_login_handler.php" autocomplete="off" novalidate>
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label for="loginPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="loginPassword" placeholder="Enter your password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500">
                        <button type="button" onclick="togglePassword('loginPassword', 'loginToggleIcon')" class="absolute right-3 top-3 text-gray-500">
                            <i class="far fa-eye" id="loginToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="role" value="user">

                <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    Login
                </button>
            </form>
        </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="mb-4">
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label for="signupPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="signupPassword" placeholder="Create a password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" required>
                        <button type="button" onclick="togglePassword('signupPassword', 'signupToggleIcon')" class="absolute right-3 top-3 text-gray-500">
                            <i class="far fa-eye" id="signupToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" placeholder="Confirm your password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" required>
                        <button type="button" onclick="togglePassword('confirmPassword', 'confirmToggleIcon')" class="absolute right-3 top-3 text-gray-500">
                            <i class="far fa-eye" id="confirmToggleIcon"></i>
                        </button>
                    </div>
                    <p id="passwordMatchError" class="text-red-500 text-sm mt-1 hidden">Passwords do not match.</p>
                </div>
 
                <div class="mb-4">
                    <label for="birthDate" class="block text-sm font-medium text-gray-700 mb-1">Birth Date</label>
                    <input type="date" id="birthDate" name="birth_date" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="outsidePhilippines" name="outside_philippines" value="true" class="mr-2">
                        <label for="outsidePhilippines" class="text-sm text-gray-700">Outside Philippines</label>
                    </div>
                    <div id="philippine-address">
                        <div class="mb-2">
                            <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <select id="province" name="province" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500">
                                <option value="">Select Province</option>
                                <?php
                                require_once 'connect.php';
                                $sql = "SELECT DISTINCT province FROM location ORDER BY province";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['province']) . "'>" . htmlspecialchars($row['province']) . "</option>";
                                    }
                                }
                                closeConnection();
                                ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="municipality" class="block text-sm font-medium text-gray-700 mb-1">Municipality</label>
                            <select id="municipality" name="municipality" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" disabled>
                                <option value="">Select Municipality</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="barangay" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                            <select id="barangay" name="barangay" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" disabled>
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                    </div>
                     <div id="outside-philippines-address" class="hidden">
                        <label for="generalAddress" class="block text-sm font-medium text-gray-700 mb-1">General Address</label>
                        <input type="text" id="generalAddress" name="general_address" placeholder="Enter your address" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="contactNumber" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" id="contactNumber" name="contact_number" placeholder="Enter your contact number" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
 </div>

                <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    Sign Up
                </button>
            </form>
        </div>

        <!-- Toggle Buttons and Back to Account Selection -->
        <div class="mt-4 text-center">
            <button id="toggle-button" class="text-sm text-green-600 hover:underline" onclick="toggleForm()">
                Don't have an account? Sign Up
            </button>
        </div>

        <div class="mt-2 text-center">
            <button id="toggle-button-back" class="text-sm text-green-600 hover:underline hidden" onclick="toggleFormBack()">
                Already have an account? Login
            </button>
        </div>

        <!-- Back to Account Selection Button -->
        <div class="mt-6 text-center">
            <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                ← Back to Account Selection
            </a>
        </div>
    </div>

    <script>
    // Determine the initial form to display based on URL or session data
    const urlParams = new URLSearchParams(window.location.search);
    const showSignup = urlParams.get('showSignup');
    const loginSection = document.getElementById('login-section');
    const signupSection = document.getElementById('signup-section');

    if (showSignup === 'true') {
        toggleForm(); // Show signup form
    }
    </script>

    <script>
    function toggleForm() {
        const loginSection = document.getElementById('login-section');
        const signupSection = document.getElementById('signup-section');
        const toggleButton = document.getElementById('toggle-button');
        const toggleButtonBack = document.getElementById('toggle-button-back');

        loginSection.classList.add('hidden');
        signupSection.classList.remove('hidden');
        toggleButton.classList.add('hidden');
        toggleButtonBack.classList.remove('hidden');
    }
      function toggleFormBack() {
        const loginSection = document.getElementById('login-section');
        const signupSection = document.getElementById('signup-section');
         const toggleButton = document.getElementById('toggle-button');
        const toggleButtonBack = document.getElementById('toggle-button-back');
        loginSection.classList.remove('hidden');
        signupSection.classList.add('hidden');
        toggleButton.classList.remove('hidden');
        toggleButtonBack.classList.add('hidden');
    }
    function togglePassword(passwordFieldId, iconId) {
        const passwordField = document.getElementById(passwordFieldId);
        const icon = document.getElementById(iconId);

        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordField.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Address Dropdown Logic
    const provinceSelect = document.getElementById('province');
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');
    const outsidePhilippinesCheckbox = document.getElementById('outsidePhilippines');
    const philippineAddressDiv = document.getElementById('philippine-address');
    const outsidePhilippinesAddressDiv = document.getElementById('outside-philippines-address');
    const generalAddressInput = document.getElementById('generalAddress');

    outsidePhilippinesCheckbox.addEventListener('change', function() {
        if (this.checked) {
            philippineAddressDiv.classList.add('hidden');
            outsidePhilippinesAddressDiv.classList.remove('hidden');
            provinceSelect.disabled = true;
            municipalitySelect.disabled = true;
            barangaySelect.disabled = true;
            generalAddressInput.required = true;
        } else {
            philippineAddressDiv.classList.remove('hidden');
            outsidePhilippinesAddressDiv.classList.add('hidden');
            provinceSelect.disabled = false;
            municipalitySelect.disabled = true; // Still disabled until province is selected
            barangaySelect.disabled = true; // Still disabled until municipality is selected
            generalAddressInput.required = false;
        }
    });

    provinceSelect.addEventListener('change', function() {
        const selectedProvince = this.value;
        municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        municipalitySelect.disabled = true;
        barangaySelect.disabled = true;

        if (selectedProvince) {
            // Fetch municipalities based on selected province
            fetch(`get_locations.php?type=municipality&province=${encodeURIComponent(selectedProvince)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.municipality;
                        option.textContent = location.municipality;
                        municipalitySelect.appendChild(option);
                    });
                    municipalitySelect.disabled = false;
                });
        }
    });

    municipalitySelect.addEventListener('change', function() {
        const selectedProvince = provinceSelect.value;
        const selectedMunicipality = this.value;
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;

        if (selectedMunicipality) {
            // Fetch barangays based on selected province and municipality
            fetch(`get_locations.php?type=barangay&province=${encodeURIComponent(selectedProvince)}&municipality=${encodeURIComponent(selectedMunicipality)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.barangay;
                        option.textContent = location.barangay;
                        barangaySelect.appendChild(option);
                    });
                    barangaySelect.disabled = false;
                });
        }
    });

    // Password Confirmation Logic
    const signupPassword = document.getElementById('signupPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordMatchError = document.getElementById('passwordMatchError');
    const signupForm = document.getElementById('signup-form');

    function validatePassword() {
        if (signupPassword.value !== confirmPassword.value) {
            passwordMatchError.classList.remove('hidden');
            confirmPassword.setCustomValidity("Passwords do not match.");
        } else {
            passwordMatchError.classList.add('hidden');
            confirmPassword.setCustomValidity("");
        }
    }

    signupPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);

    signupForm.addEventListener('submit', function(event) {
        validatePassword(); // Run validation on submit as well
        if (!confirmPassword.validity.valid) {
            event.preventDefault(); // Prevent form submission if passwords don't match
        }
         // If Outside Philippines is selected, ensure general address is not empty
        if (outsidePhilippinesCheckbox.checked && generalAddressInput.value.trim() === '') {
            generalAddressInput.reportValidity(); // Show validation message
            event.preventDefault(); // Prevent form submission
        }

        // If Philippine address is selected, ensure all dropdowns have a value
        if (!outsidePhilippinesCheckbox.checked && (provinceSelect.value === '' || municipalitySelect.value === '' || barangaySelect.value === '')) {
             if (provinceSelect.value === '') provinceSelect.reportValidity();
             else if (municipalitySelect.value === '') municipalitySelect.reportValidity();
             else if (barangaySelect.value === '') barangaySelect.reportValidity();
             event.preventDefault(); // Prevent form submission
        }

    });

    // Add a basic check for required fields before allowing submission
    signupForm.addEventListener('submit', function(event) {
        if (!this.checkValidity()) {
            event.preventDefault(); // Prevent form submission if built-in validation fails
        }
    });
    </script>
</body>
</html>