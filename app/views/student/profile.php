<?php
/**
 * Student Profile
 * View student information and account settings
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';

// Require student role
requireRole('student');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get student information
$stmt = $db->prepare("
    SELECT 
        s.*,
        u.username,
        u.email,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.created_at
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ?
");
$stmt->execute([getCurrentUserId()]);
$studentInfo = $stmt->fetch();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlashMessage('All password fields are required.', 'danger');
    } elseif ($newPassword !== $confirmPassword) {
        setFlashMessage('New passwords do not match.', 'danger');
    } elseif (strlen($newPassword) < 6) {
        setFlashMessage('New password must be at least 6 characters.', 'danger');
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch();
        
        if (password_verify($currentPassword, $user['password_hash'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, getCurrentUserId()])) {
                setFlashMessage('Password changed successfully!', 'success');
                header('Location: profile.php');
                exit;
            } else {
                setFlashMessage('Error updating password.', 'danger');
            }
        } else {
            setFlashMessage('Current password is incorrect.', 'danger');
        }
    }
}

$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { background: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 52%, #2BFF88 90%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding: 0;
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 1.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        .sidebar-menu { padding: 1rem 0; }
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            display: block;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .sidebar-menu a i { width: 25px; text-align: center; margin-right: 10px; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-mortarboard-fill me-2"></i>
            <?php echo APP_NAME; ?>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="my-grades.php">
                <i class="bi bi-clipboard-data"></i> My Grades
            </a>
            <a href="honors.php">
                <i class="bi bi-award-fill"></i> Honors & Awards
            </a>
            <a href="profile.php" class="active">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <a href="../../../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <span class="badge bg-primary">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo getCurrentUserName(); ?>
                </span>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="d-flex align-items-center">
                <div class="profile-avatar me-4">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <h2 class="mb-1"><?php echo htmlspecialchars($studentInfo['first_name'] . ' ' . ($studentInfo['middle_name'] ? $studentInfo['middle_name'] . ' ' : '') . $studentInfo['last_name']); ?></h2>
                    <p class="mb-1 opacity-75">
                        <i class="bi bi-mortarboard me-2"></i>
                        Grade <?php echo $studentInfo['grade_level']; ?> - <?php echo htmlspecialchars($studentInfo['section']); ?>
                    </p>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-envelope me-2"></i>
                        <?php echo htmlspecialchars($studentInfo['email']); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-badge me-2"></i>
                            Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%;">Student Number:</th>
                                <td><?php echo htmlspecialchars($studentInfo['student_number']); ?></td>
                            </tr>
                            <tr>
                                <th>LRN:</th>
                                <td><?php echo htmlspecialchars($studentInfo['lrn']); ?></td>
                            </tr>
                            <tr>
                                <th>First Name:</th>
                                <td><?php echo htmlspecialchars($studentInfo['first_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Middle Name:</th>
                                <td><?php echo htmlspecialchars($studentInfo['middle_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Last Name:</th>
                                <td><?php echo htmlspecialchars($studentInfo['last_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Date of Birth:</th>
                                <td><?php echo isset($studentInfo['birth_date']) && $studentInfo['birth_date'] ? date('F d, Y', strtotime($studentInfo['birth_date'])) : 'Not set'; ?></td>
                            </tr>
                            <tr>
                                <th>Gender:</th>
                                <td><?php echo htmlspecialchars($studentInfo['gender']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Academic Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-book me-2"></i>
                            Academic Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%;">Grade Level:</th>
                                <td>Grade <?php echo $studentInfo['grade_level']; ?></td>
                            </tr>
                            <tr>
                                <th>Track:</th>
                                <td><?php echo htmlspecialchars($studentInfo['track']); ?></td>
                            </tr>
                            <tr>
                                <th>Strand:</th>
                                <td><?php echo htmlspecialchars($studentInfo['strand']); ?></td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td><?php echo htmlspecialchars($studentInfo['section']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Contact Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-telephone me-2"></i>
                            Contact Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%;">Email:</th>
                                <td><?php echo htmlspecialchars($studentInfo['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Contact Number:</th>
                                <td><?php echo htmlspecialchars($studentInfo['contact_number'] ?? 'Not set'); ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?php echo htmlspecialchars($studentInfo['address'] ?? 'Not set'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Guardian Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>
                            Guardian Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%;">Guardian Name:</th>
                                <td><?php echo htmlspecialchars($studentInfo['guardian_name'] ?? 'Not set'); ?></td>
                            </tr>
                            <tr>
                                <th>Guardian Contact:</th>
                                <td><?php echo htmlspecialchars($studentInfo['guardian_contact'] ?? 'Not set'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Account Security -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock me-2"></i>
                            Account Security
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Change your password to keep your account secure.</p>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                                <small class="text-muted">At least 6 characters</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="bi bi-key me-2"></i>Change Password
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-muted small">
                            <p class="mb-0">
                                <strong>Account Created:</strong> <?php echo date('F d, Y', strtotime($studentInfo['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
