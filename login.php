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
            --primary-color: #2563EB;
            --primary-dark: #1D4ED8;
            --text-color: #333;
            --light-gray: #EFF6FF;
            --border-color: #E5E7EB;
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
            background: linear-gradient(135deg, #F9FAFB 0%, #EFF6FF 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .login-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.02);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1E40AF 100%);
            color: white;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 1;
            margin: 0;
        }
        
        .login-form {
            padding: 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9375rem;
            letter-spacing: 0.025em;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #F9FAFB;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            min-height: 3.25rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15), 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background-color: white;
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.8;
            font-weight: 400;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1E40AF 100%);
            color: white;
            border: 2px solid var(--primary-color);
            border-radius: 0.75rem;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            margin-top: 1rem;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
            letter-spacing: 0.025em;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1E3A8A 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }
        
        .error-message {
            background: linear-gradient(135deg, #FEE2E2 0%, #FEF2F2 100%);
            color: var(--error-color);
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
            font-weight: 500;
            display: <?php echo $error ? 'block' : 'none'; ?>;
            animation: slideIn 0.4s ease-out;
            border-left: 4px solid var(--error-color);
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1);
        }
        
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.9375rem;
            color: var(--text-muted);
        }
        
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .form-footer a:hover {
            color: var(--primary-dark);
        }
        
        .user-type-selector {
            display: flex;
            background: linear-gradient(135deg, var(--light-gray) 0%, #F0F9FF 100%);
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 1.75rem;
            border: 2px solid var(--border-color);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 0.9375rem;
            position: relative;
        }
        
        .user-type-option input[type="radio"] {
            display: none;
        }
        
        .user-type-option label {
            display: block;
            cursor: pointer;
            padding: 0.75rem 0;
            margin: 0;
            transition: var(--transition);
        }
        
        .user-type-option input[type="radio"]:checked + label {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .user-type-option:hover {
            background: rgba(37, 99, 235, 0.05);
        }
        
        .user-type-option:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 50%;
            background: var(--border-color);
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                border-radius: 1rem;
                margin: 1rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem 1.5rem;
            }
            
            .login-form {
                padding: 2rem 1.5rem;
            }
            
            .form-control {
                font-size: 1rem;
                padding: 1rem 1.25rem;
            }
            
            .btn {
                font-size: 1rem;
                padding: 1rem 1.5rem;
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
