php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Signup</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
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
        .form-title {
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .password-toggle {
            cursor: pointer;
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
<body>
    <div class="form-container">
        <h2 class="form-title">User Sign Up</h2>

        <?php
        session_start();
        if (isset($_SESSION['registration_error'])) {
            echo '<div class="alert alert-danger" role="alert">' . $_SESSION['registration_error'] . '</div>';
            unset($_SESSION['registration_error']);
        }
        if (isset($_SESSION['registration_success'])) {
            echo '<div class="alert alert-success" role="alert">' . $_SESSION['registration_success'] . '</div>';
            unset($_SESSION['registration_success']);
        }
        ?>

        <form action="user_signup_handler.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" required>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
            </div>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="input-group-append">
                        <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                            <i class="fa fa-eye" id="password-icon"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div class="input-group-append">
                        <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fa fa-eye" id="confirm_password-icon"></i>
                        </span>
                    </div>
                </div>
            </div>
             <div class="form-group">
                <label for="birth_date">Birth Date</label>
                <input type="date" class="form-control" id="birth_date" name="birth_date" required>
            </div>

            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="is_outside_philippines" name="is_outside_philippines" value="true">
                <label class="form-check-label" for="is_outside_philippines">I reside outside the Philippines</label>
            </div>

            <div id="philippines-address" style="display: block;">
                <div class="form-group">
                    <label for="province">Province</label>
                    <input type="text" class="form-control" id="province" name="province">
                </div>
                <div class="form-group">
                    <label for="municipality">Municipality/City</label>
                    <input type="text" class="form-control" id="municipality" name="municipality">
                </div>
                <div class="form-group">
                    <label for="barangay">Barangay</label>
                    <input type="text" class="form-control" id="barangay" name="barangay">
                </div>
            </div>

            <div id="general-address" style="display: none;">
                 <div class="form-group">
                    <label for="general_address">General Address</label>
                    <textarea class="form-control" id="general_address" name="general_address" rows="3"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
        </form>
        <div class="text-center mt-3">
            <a href="userlogin.php">Already have an account? Login</a>
        </div>
        <div class="text-center mt-2">
            <a href="account.php">Back to Account Selection</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        const isOutsideCheckbox = document.getElementById('is_outside_philippines');
        const philippinesAddress = document.getElementById('philippines-address');
        const generalAddress = document.getElementById('general-address');
        const philippinesFields = philippinesAddress.querySelectorAll('input, textarea');
        const generalFields = generalAddress.querySelectorAll('input, textarea');


        isOutsideCheckbox.addEventListener('change', function() {
            if (this.checked) {
                philippinesAddress.style.display = 'none';
                generalAddress.style.display = 'block';
                 philippinesFields.forEach(field => field.removeAttribute('required'));
                 generalFields.forEach(field => field.setAttribute('required', 'required'));
            } else {
                philippinesAddress.style.display = 'block';
                generalAddress.style.display = 'none';
                philippinesFields.forEach(field => field.setAttribute('required', 'required'));
                generalFields.forEach(field => field.removeAttribute('required'));
            }
        });

         // Initial state check
         if (isOutsideCheckbox.checked) {
            philippinesAddress.style.display = 'none';
            generalAddress.style.display = 'block';
             philippinesFields.forEach(field => field.removeAttribute('required'));
             generalFields.forEach(field => field.setAttribute('required', 'required'));
         } else {
             philippinesAddress.style.display = 'block';
             generalAddress.style.display = 'none';
             philippinesFields.forEach(field => field.setAttribute('required', 'required'));
             generalFields.forEach(field => field.removeAttribute('required'));
         }

    </script>
</body>
</html>