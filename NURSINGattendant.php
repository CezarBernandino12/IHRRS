<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nurse Attendant Login</title>
    <link rel="stylesheet" href="css/login.css" />
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container">
        <div class="form-content"> 
             <div id="login-form">
             <a href="role.html" class="back-btn">
        <i class="bx bx-arrow-back"></i> <!-- Back arrow icon -->
      </a>
                <img src="img/logo.png" class="Img" alt="Admin Logo">
                <h2>Nursing Attendant</h2>
                <form action="LOGIN/NursingAttendant.php" method="POST">
                    <!-- Error message display -->
                    <div id="error-message" style="color: red; text-align: center;">
                        <?php if (isset($_GET['error'])) echo htmlspecialchars($_GET['error']); ?>
                    </div>

                    <label for="username">Username</label>
                    <div class="input-box">
                        <span class="icon"><i class="bx bx-user"></i></span>
                        <input type="text" id="username" name="username" placeholder="Enter your username"
                               value="<?php echo isset($_COOKIE['attendant_username']) ? htmlspecialchars($_COOKIE['attendant_username']) : ''; ?>" required />
                    </div>

                    <label for="password">Password</label>
                    <div class="input-box">
                        <span class="icon"><i class="bx bx-lock"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required />
                        <span class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                     <i class="bx bx-show" id="togglePasswordIcon"></i>
                     </span>
                        
                    </div>



                    <button type="submit" class="login-btn">LOGIN</button>
                </form>

                        <div style="text-align: center; margin-top: 15px;">
          <a href="LOGIN/nursing_attendant_forgot_password.html" style="color: #666; text-decoration: none; font-size: 14px;">Forgot Password?</a>
        </div>

            </div>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    const passwordInput = document.getElementById('password');

    togglePasswordIcon.addEventListener('click', function () {
        const isPasswordVisible = passwordInput.type === 'text';
        passwordInput.type = isPasswordVisible ? 'password' : 'text';
        togglePasswordIcon.classList.toggle('bx-show');
        togglePasswordIcon.classList.toggle('bx-hide');
    });
});


// Check for saved credentials when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we have stored username in a cookie
            const savedUsername = getCookie('attendant_username');
            if (savedUsername) {
                document.getElementById('username').value = savedUsername;
                // Focus on the password field instead
                document.getElementById('password').focus();
            }
        });
        
        // Helper function to get cookie value by name
        function getCookie(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? decodeURIComponent(match[2]) : null;
        }
                
        const successMessage = urlParams.get('success');
        if (successMessage) {
            const successModal = document.getElementById("successModal");
            const successText = document.getElementById("success-message");
            if (successModal && successText) {
                successText.textContent = successMessage;
                successModal.style.display = "block";
            }
        }


</script>
</body>
</html>