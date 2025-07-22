<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            padding: 20px;
        }

        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .input-group-append {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .form-group .input-group {
            position: relative;
        }
    </style>
</head>
<body class="h-screen flex relative bg-gray-100">
    <!-- Signup Container -->
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">

        <?php
        session_start();
        if (isset($_SESSION['registration_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($_SESSION['registration_error']) . "</div>";
            unset($_SESSION['registration_error']);
        }
        if (isset($_SESSION['registration_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($_SESSION['registration_success']) . "</div>";
            unset($_SESSION['registration_success']);
        }
        ?>

        <form action="user_signup_handler.php" method="POST" id="signupForm">
            <h2 class="text-2xl font-bold text-center mb-4">User Sign Up</h2>
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
            </div>
            <div class="mb-4">
                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter full name" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
            </div>
            <div class="mb-4">
                <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" placeholder="Enter contact number" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                <input type="email" id="email" name="email" placeholder="Enter email address" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" placeholder="Enter your password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" required>
                    <button type="button" onclick="togglePassword('password', 'password-icon')" class="absolute right-3 top-3 text-gray-500">
                        <i class="fa fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <div class="relative">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" required>
                    <button type="button" onclick="togglePassword('confirm_password', 'confirm_password-icon')" class="absolute right-3 top-3 text-gray-500">
                        <i class="fa fa-eye" id="confirm_password-icon"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Birth Date</label>
                <input type="date" id="birth_date" name="birth_date" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
            </div>

            <div class="mb-4 flex items-center">
                <input type="checkbox" id="is_outside_philippines" name="is_outside_philippines" value="true" class="form-checkbox h-4 w-4 text-green-600 rounded focus:ring-green-500">
                <label class="ml-2 block text-sm text-gray-900" for="is_outside_philippines">I reside outside the Philippines</label>
            </div>

            <div id="philippines-address">
                <div class="mb-4">
                    <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                    <select id="province" name="province" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                        <option value="">Select Province</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="municipality" class="block text-sm font-medium text-gray-700 mb-1">Municipality/City</label>
                    <select id="municipality" name="municipality" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required disabled>
                        <option value="">Select Municipality/City</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="barangay" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                    <select id="barangay" name="barangay" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required disabled>
                        <option value="">Select Barangay</option>
                    </select>
                </div>
            </div>

            <div id="general-address" style="display: none;">
                <div class="mb-4">
                    <label for="general_address" class="block text-sm font-medium text-gray-700 mb-1">General Address</label>
                    <textarea id="general_address" name="general_address" rows="3" placeholder="Enter your general address" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
            </div>

            <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                Sign Up
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="userlogin.php" class="text-sm text-green-600 hover:underline">Already have an account? Login</a>
        </div>
        <!-- Back to Account Selection Button - FIXED PATH -->
        <div class="mt-6 text-center">
            <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                ← Back to Account Selection
            </a>
        </div>
    </div>

    <script>
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

        var isOutsideCheckbox = document.getElementById('is_outside_philippines');
        var philippinesAddress = document.getElementById('philippines-address');
        var generalAddress = document.getElementById('general-address');
        var provinceSelect = document.getElementById('province');
        var municipalitySelect = document.getElementById('municipality');
        var barangaySelect = document.getElementById('barangay');
        var generalAddressField = document.getElementById('general_address');

        function toggleAddressFields() {
            if (isOutsideCheckbox.checked) {
                // Show general address, hide Philippines address
                philippinesAddress.style.display = 'none';
                generalAddress.style.display = 'block';
                
                // Remove required from Philippines fields
                provinceSelect.removeAttribute('required');
                municipalitySelect.removeAttribute('required');
                barangaySelect.removeAttribute('required');
                
                // Add required to general address
                generalAddressField.setAttribute('required', 'required');
            } else {
                // Show Philippines address, hide general address
                philippinesAddress.style.display = 'block';
                generalAddress.style.display = 'none';
                
                // Add required to Philippines fields
                provinceSelect.setAttribute('required', 'required');
                municipalitySelect.setAttribute('required', 'required');
                barangaySelect.setAttribute('required', 'required');
                
                // Remove required from general address
                generalAddressField.removeAttribute('required');
            }
        }

        // Event listener for checkbox change
        isOutsideCheckbox.addEventListener('change', toggleAddressFields);

        // Initial state setup
        toggleAddressFields();

        // Function to populate dropdowns
        function populateDropdown(selectElement, data) {
            selectElement.innerHTML = ''; // Clear existing options
            var defaultOptionText = '';
            if (selectElement.id === 'province') defaultOptionText = 'Select Province';
            if (selectElement.id === 'municipality') defaultOptionText = 'Select Municipality/City';
            if (selectElement.id === 'barangay') defaultOptionText = 'Select Barangay';
            var defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = defaultOptionText;
            selectElement.appendChild(defaultOption);

            data.forEach(item => {
                var option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                selectElement.appendChild(option);
            });
        }

        // Fetch provinces on page load
        fetch('get_locations.php?level=province')
            .then(response => response.json())
            .then(data => {
                populateDropdown(provinceSelect, data);
            })
            .catch(error => console.error('Error fetching provinces:', error));

        // Event listener for province selection
        provinceSelect.addEventListener('change', function() {
            var provinceId = this.value;
            municipalitySelect.disabled = true;
            barangaySelect.disabled = true;
            populateDropdown(municipalitySelect, []); // Clear municipalities
            populateDropdown(barangaySelect, []); // Clear barangays

            if (provinceId) {
                fetch('get_locations.php?level=municipality&parent_id=' + provinceId)
                    .then(response => response.json())
                    .then(data => {
                        populateDropdown(municipalitySelect, data);
                        municipalitySelect.disabled = false;
                    })
                    .catch(error => console.error('Error fetching municipalities:', error));
            }
        });

        // Event listener for municipality selection
        municipalitySelect.addEventListener('change', function() {
            var municipalityId = this.value;
            barangaySelect.disabled = true;
            populateDropdown(barangaySelect, []); // Clear barangays

            if (municipalityId) {
                fetch('get_locations.php?level=barangay&province=' + provinceSelect.value + '&parent_id=' + municipalityId)
                    .then(response => response.json())
                    .then(data => {
                        populateDropdown(barangaySelect, data);
                        barangaySelect.disabled = false;
                    })
                    .catch(error => console.error('Error fetching barangays:', error));
            }
        });

        // Form validation before submit
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            if (isOutsideCheckbox.checked) {
                if (!generalAddressField.value.trim()) {
                    e.preventDefault();
                    alert('Please enter your general address.');
                    return false;
                }
            } else {
                if (!provinceSelect.value || !municipalitySelect.value || !barangaySelect.value) {
                    e.preventDefault();
                    alert('Please complete your Philippines address selection.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>