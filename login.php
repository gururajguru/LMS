<?php
// Start session at the very beginning
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['USERID'])) {
    $redirect = (strtolower($_SESSION['TYPE'] ?? '') === 'administrator') ? 'Admin/' : 'student/';
    header("Location: $redirect");
    exit();
}

// Include required files
require_once 'include/database.php';
require_once 'include/users.php';

// Initialize variables
$error = '';
$username = '';
$userType = 'Administrator';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? 'Administrator';
    
    try {
        $users = new Users();
        $user = $users->authenticate($username, $password, $userType);
        
        if ($user) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['USERID'] = $user->id;
            $_SESSION['USERNAME'] = $user->username;
            $_SESSION['TYPE'] = $user->user_type;
            
            // Redirect based on user type
            $redirect = (strtolower($userType) === 'administrator') ? 'Admin/' : 'student/';
            header("Location: $redirect");
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    } catch (Exception $e) {
        $error = 'An error occurred during login. Please try again.';
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Learning Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a6cf7;
            --primary-dark: #3a5bd9;
            --text-color: #333;
            --light-gray: #f5f7ff;
            --border-color: #e0e4f5;
            --error-color: #ff4444;
            --success-color: #00c851;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7ff 0%, #e4e8ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            position: relative;
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 30px 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9faff;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.2);
            background-color: white;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 108, 247, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: #ffebee;
            color: var(--error-color);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: <?php echo $error ? 'block' : 'none'; ?>;
            animation: fadeIn 0.3s ease;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .user-type-selector {
            display: flex;
            background: var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
        }
        
        .user-type-option input[type="radio"] {
            display: none;
        }
        
        .user-type-option label {
            display: block;
            cursor: pointer;
            padding: 8px 0;
        }
        
        .user-type-option input[type="radio"]:checked + label {
            color: var(--primary-color);
        }
        
        .user-type-option:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 60%;
            background: rgba(0, 0, 0, 0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                border-radius: 12px;
            }
            
            .login-header {
                padding: 25px 20px 20px;
            }
            
            .login-form {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Sign in to access your account</p>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($username); ?>" 
                        placeholder="Enter your username"
                        required
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label>Login As</label>
                    <div class="user-type-selector">
                        <div class="user-type-option">
                            <input 
                                type="radio" 
                                id="admin-radio" 
                                name="userType" 
                                value="Administrator" 
                                <?php echo ($userType === 'Administrator') ? 'checked' : ''; ?>
                            >
                            <label for="admin-radio">Administrator</label>
                        </div>
                        <div class="user-type-option">
                            <input 
                                type="radio" 
                                id="student-radio" 
                                name="userType" 
                                value="Student"
                                <?php echo ($userType === 'Student') ? 'checked' : ''; ?>
                            >
                            <label for="student-radio">Student</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="#">Contact administrator</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Add smooth transitions
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to form elements
            const formElements = document.querySelectorAll('.form-control, .user-type-option');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(10px)';
                element.style.animation = `fadeIn 0.3s ease forwards ${index * 0.1}s`;
            });
            
            // Add focus styles
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('label').style.color = 'var(--primary-color)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('label').style.color = 'var(--text-color)';
                });
            });
        });
    </script>
</body>
</html>
