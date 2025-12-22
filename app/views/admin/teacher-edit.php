<?php
/**
 * Edit Teacher - Admin
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';

// Require admin role
requireRole('admin');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get teacher ID
$teacherId = getParam('id');
if (!$teacherId) {
    setFlashMessage('Invalid teacher ID', 'danger');
    header('Location: teachers.php');
    exit;
}

// Get teacher data
$stmt = $db->prepare("SELECT t.*, u.username, u.email as user_email, u.first_name as user_first_name, 
                      u.last_name as user_last_name, u.middle_name as user_middle_name, u.is_active as user_active
                      FROM teachers t
                      JOIN users u ON t.user_id = u.id
                      WHERE t.id = ?");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    setFlashMessage('Teacher not found', 'danger');
    header('Location: teachers.php');
    exit;
}

// Get unique values for dropdowns
$departments = $db->query("SELECT DISTINCT department FROM teachers WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeNumber = trim($_POST['employee_number']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $middleName = trim($_POST['middle_name']);
    $department = trim($_POST['department']);
    $specialization = trim($_POST['specialization']);
    $contactNumber = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validate required fields
    if (empty($employeeNumber)) $errors[] = 'Employee number is required';
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($department)) $errors[] = 'Department is required';
    if (empty($username)) $errors[] = 'Username is required';
    
    // Check employee number uniqueness (excluding current teacher)
    if (!empty($employeeNumber)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM teachers WHERE employee_number = ? AND id != ?");
        $stmt->execute([$employeeNumber, $teacherId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Employee number already exists';
        }
    }
    
    // Check username uniqueness (excluding current user)
    if (!empty($username)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $teacher['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update user account
            if (!empty($password)) {
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, email = ?, first_name = ?, last_name = ?, middle_name = ?, is_active = ? WHERE id = ?");
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$username, $hashedPassword, $email, $firstName, $lastName, $middleName, $isActive, $teacher['user_id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, middle_name = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $email, $firstName, $lastName, $middleName, $isActive, $teacher['user_id']]);
            }
            
            // Update teacher record
            $stmt = $db->prepare("UPDATE teachers SET employee_number = ?, department = ?, specialization = ?, contact_number = ? WHERE id = ?");
            $stmt->execute([$employeeNumber, $department, $specialization, $contactNumber, $teacherId]);
            
            // Audit log
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'update', 'teachers', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $teacherId, $_SERVER['REMOTE_ADDR']]);
            
            $db->commit();
            setFlashMessage('Teacher updated successfully', 'success');
            header('Location: teachers.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Teacher';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shield-lock-fill me-2"></i>
            Admin Panel
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="users.php">
                <i class="bi bi-people-fill"></i> Users
            </a>
            <a href="students.php">
                <i class="bi bi-mortarboard-fill"></i> Students
            </a>
            <a href="teachers.php" class="active">
                <i class="bi bi-person-badge-fill"></i> Teachers
            </a>
            <a href="subjects.php">
                <i class="bi bi-book-fill"></i> Subjects
            </a>
            <a href="school-years.php">
                <i class="bi bi-calendar-range"></i> School Years
            </a>
            <a href="audit-logs.php">
                <i class="bi bi-clock-history"></i> Audit Logs
            </a>
            <a href="settings.php">
                <i class="bi bi-gear-fill"></i> Settings
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
                <a href="teachers.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Teachers
                </a>
            </div>
        </nav>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-person-fill-gear me-2"></i>
                    Teacher Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Personal Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Employee Number <span class="text-danger">*</span></label>
                                <input type="text" name="employee_number" class="form-control" required value="<?php echo isset($_POST['employee_number']) ? htmlspecialchars($_POST['employee_number']) : htmlspecialchars($teacher['employee_number']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : htmlspecialchars($teacher['user_first_name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : htmlspecialchars($teacher['user_last_name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : htmlspecialchars($teacher['user_middle_name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($teacher['user_email']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : htmlspecialchars($teacher['contact_number']); ?>">
                            </div>
                        </div>
                        
                        <!-- Professional & Account Info -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Professional Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <input type="text" name="department" class="form-control" list="departmentList" required value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : htmlspecialchars($teacher['department']); ?>">
                                <datalist id="departmentList">
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="form-text text-muted">e.g., Science, Mathematics, English, Filipino, Social Studies</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : htmlspecialchars($teacher['specialization']); ?>">
                                <small class="form-text text-muted">e.g., Physics, Calculus, Literature</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="text-muted mb-3">Account Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : htmlspecialchars($teacher['username']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control">
                                <small class="form-text text-muted">Leave blank to keep current password</small>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?php echo (isset($_POST['is_active']) ? 'checked' : ($teacher['user_active'] ? 'checked' : '')); ?>>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="teachers.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
