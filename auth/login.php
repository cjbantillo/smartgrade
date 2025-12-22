<?php
/**
 * Login Page
 * Handles user authentication for all roles (Admin, Teacher, Student)
 */

// Start session
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../app/views/admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: ../app/views/teacher/dashboard.php');
            break;
        case 'student':
            header('Location: ../app/views/student/dashboard.php');
            break;
    }
    exit();
}

// Include configuration
require_once '../app/config/config.php';
require_once '../app/helpers/security.php';
require_once '../app/helpers/utils.php';

$error = '';

// Handle login form submission
if (isPost()) {
    $username = sanitize(postData('username'));
    $password = postData('password');
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Connect to database
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                // Query user by username
                $query = "SELECT id, username, email, password_hash, role, first_name, last_name, middle_name, is_active 
                          FROM users 
                          WHERE username = :username LIMIT 1";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if account is active
                    if (!$user['is_active']) {
                        $error = 'Your account has been deactivated. Please contact the administrator.';
                    } 
                    // Verify password
                    elseif (verifyPassword($password, $user['password_hash'])) {
                        // Password is correct - create session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['middle_name'] = $user['middle_name'];
                        $_SESSION['login_time'] = time();
                        
                        // Log successful login
                        $logQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                                     VALUES (:user_id, 'user_login', 'users', :record_id, :ip, :user_agent)";
                        $logStmt = $db->prepare($logQuery);
                        $logStmt->execute([
                            ':user_id' => $user['id'],
                            ':record_id' => $user['id'],
                            ':ip' => getClientIP(),
                            ':user_agent' => getUserAgent()
                        ]);
                        
                        // Redirect based on role
                        $redirect = $_SESSION['redirect_after_login'] ?? null;
                        unset($_SESSION['redirect_after_login']);
                        
                        if ($redirect) {
                            header("Location: $redirect");
                        } else {
                            switch ($user['role']) {
                                case 'admin':
                                    header('Location: ../app/views/admin/dashboard.php');
                                    break;
                                case 'teacher':
                                    header('Location: ../app/views/teacher/dashboard.php');
                                    break;
                                case 'student':
                                    header('Location: ../app/views/student/dashboard.php');
                                    break;
                            }
                        }
                        exit();
                    } else {
                        $error = 'Invalid username or password.';
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Database connection failed. Please try again later.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .login-header p {
            margin: 0;
            opacity: 0.9;
        }
        .login-body {
            padding: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .default-accounts {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }
        .default-accounts h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .default-accounts ul {
            margin: 0;
            padding-left: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-mortarboard-fill" style="font-size: 3rem;"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p><?php echo SCHOOL_NAME; ?></p>
                <small>Automated Grading System</small>
            </div>
            
            <div class="login-body">
                <h4 class="text-center mb-4">Sign In</h4>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person-fill me-1"></i> Username
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username"
                               value="<?php echo isset($username) ? $username : ''; ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Password
                        </label>
                        <input type="password" 
                               class="form-control form-control-lg" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100 btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                    </button>
                </form>
                
                <!-- Development Mode: Show default accounts -->
                <?php if (ENV === 'development'): ?>
                <div class="default-accounts">
                    <h6><i class="bi bi-info-circle-fill me-1"></i> Default Test Accounts</h6>
                    <ul class="mb-0">
                        <li><strong>Admin:</strong> admin / admin123</li>
                        <li><strong>Teacher:</strong> jdelacruz / teacher123</li>
                        <li><strong>Student:</strong> 2024001 / student123</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="text-center text-white mt-3">
            <small>&copy; <?php echo date('Y'); ?> <?php echo SCHOOL_NAME; ?>. All rights reserved.</small>
        </p>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
