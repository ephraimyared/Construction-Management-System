<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salale University Construction Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Construction Management System for Salale University" name="description">

    <style>
        :root {
            --primary: #ff6600;
            --primary-dark: #e65c00;
            --secondary: #ff8533;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e5383b;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: url('images/SLU.jpg') no-repeat center center/cover;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        
        .container {
            background-color: rgba(225, 220, 220, 0.9);
            backdrop-filter: blur(9px);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            max-width: 400px;
            width: 150%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo img {
             max-width: 150px;
             height: auto;
             filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
        }

        h2 {
          text-align: center;
         margin-bottom: 10px;
         color: #333;
         font-weight: 600;
         font-size: 28px;
         }

        h4 {
          text-align: center;
         margin-bottom: 25px;
         color: #555;
         font-weight: 400;
        font-size: 15px;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            color: white;
            background-color: var(--danger);
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
            display: block;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid rgba(255, 102, 0, 0.3);
            background-color: rgba(255, 255, 255, 0.8);
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: #333;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.2);
            outline: none;
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .password-container {
            position: relative;
        }
        
        .eye-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--primary);
            transition: var(--transition);
        }
        
        .eye-icon:hover {
            color: var(--primary-dark);
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: var(--gradient);
            border: none;
            color: white;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
button:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 102, 0, 0.4);
}
        
        .link-group {
            text-align: center;
            margin-top: 25px;
        }
        
        .link-group a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .link-group a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .error {
            color: var(--danger);
            font-size: 14px;
            margin-top: -15px;
            margin-bottom: 15px;
            display: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="logo">
        <!-- Replace this block inside your .logo div -->
     <div class="logo">
     <img src="images/LOGO.png" alt="SLU Logo">
</div>

        <!-- You can add a logo image here -->
    </div>
    <h2>LOGIN</h2>
    <h4>Access your Construction Management System Account</h4>

    <form action="process_login.php" method="POST" autocomplete="off" onsubmit="return validatePassword()">
        <?php if (isset($_SESSION['login_error'])): ?>
        <div class="alert">
            <?= $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
        </div>
        <?php endif; ?>
        
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required placeholder="Enter your username">

        <label for="password">Password</label>
        <div class="password-container">
            <input type="password" name="password" id="password" required placeholder="Enter your password">
            <i class="fas fa-eye eye-icon" id="togglePassword" onclick="togglePassword()"></i>
        </div>
        <div id="passwordError" class="error">Password must be at least 6 characters and not easily guessable.</div>

        <button type="submit">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
        
        <!-- Add this inside the link-group div, right after the Back to Home link -->
<div class="link-group">
    <a href="@SLU/index.html">
        <i class="fas fa-home"></i> Back to Home
    </a>
    <br>
    <a href="forgot_password.php">
        <i class="fas fa-key"></i> Forgot Password?
    </a>
</div>


<script>
    function togglePassword() {
        const password = document.getElementById("password");
        const icon = document.getElementById("togglePassword");
        if (password.type === "password") {
            password.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            password.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    function validatePassword() {
        const password = document.getElementById("password").value;
        const error = document.getElementById("passwordError");
        const weakPassword = /^(123|abc|password|qwerty|password123|abc123|\d{3,})$/i;

        if (weakPassword.test(password) || password.length < 6) {
            error.style.display = "block";
            return false;
        }
        error.style.display = "none";
        return true;
    }
    
    // Make the eye icon visible when the password field is focused
    document.getElementById("password").addEventListener("focus", function() {
        document.getElementById("togglePassword").style.visibility = "visible";
    });
    
    // Keep the eye icon visible when clicking on it
    document.getElementById("togglePassword").addEventListener("click", function(e) {
        e.preventDefault();
        this.style.visibility = "visible";
    });
</script>

</body>
</html>
