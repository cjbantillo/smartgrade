<?php
/**
 * Add Student - Admin
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

// Get unique values for dropdowns
$sections = $db->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL ORDER BY section")->fetchAll(PDO::FETCH_COLUMN);
$strands = $db->query("SELECT DISTINCT strand FROM students WHERE strand IS NOT NULL ORDER BY strand")->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lrn = trim($_POST['lrn']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $middleName = trim($_POST['middle_name']);
    $gradeLevel = $_POST['grade_level'];
    $section = trim($_POST['section']);
    $strand = trim($_POST['strand']);
    $track = trim($_POST['track']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $email = trim($_POST['email']);
    $contactNumber = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $guardianName = trim($_POST['guardian_name']);
    $guardianContact = trim($_POST['guardian_contact']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validate required fields
    if (empty($lrn)) $errors[] = 'LRN is required';
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($gradeLevel)) $errors[] = 'Grade level is required';
    if (empty($section)) $errors[] = 'Section is required';
    if (empty($strand)) $errors[] = 'Strand is required';
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($password)) $errors[] = 'Password is required';
    
    // Check LRN uniqueness
    if (!empty($lrn)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE lrn = ?");
        $stmt->execute([$lrn]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'LRN already exists';
        }
    }
    
    // Check username uniqueness
    if (!empty($username)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create user account
            $stmt = $db->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, is_active) 
                                  VALUES (?, ?, ?, ?, ?, 'student', ?)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$username, $hashedPassword, $email, $firstName, $lastName, $isActive]);
            $userId = $db->lastInsertId();
            
            // Create student record
            $stmt = $db->prepare("INSERT INTO students (user_id, lrn, first_name, last_name, middle_name, grade_level, section, strand, track, gender, birthdate, email, contact_number, address, guardian_name, guardian_contact, is_active)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $lrn, $firstName, $lastName, $middleName, $gradeLevel, $section, $strand, $track, $gender, $birthdate, $email, $contactNumber, $address, $guardianName, $guardianContact, $isActive]);
            
            // Audit log
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'create', 'students', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $db->lastInsertId(), $_SERVER['REMOTE_ADDR']]);
            
            $db->commit();
            setFlashMessage('Student added successfully', 'success');
            header('Location: students.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Student';
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
            <a href="students.php" class="active">
                <i class="bi bi-mortarboard-fill"></i> Students
            </a>
            <a href="teachers.php">
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
                <a href="students.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Students
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
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Student Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Student Info -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Personal Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">LRN <span class="text-danger">*</span></label>
                                <input type="text" name="lrn" class="form-control" required value="<?php echo isset($_POST['lrn']) ? htmlspecialchars($_POST['lrn']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" name="birthdate" class="form-control" value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Academic & Guardian Info -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Academic Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                                <select name="grade_level" class="form-select" required>
                                    <option value="">Select Grade</option>
                                    <option value="11" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                    <option value="12" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Section <span class="text-danger">*</span></label>
                                <input type="text" name="section" class="form-control" list="sectionList" required value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>">
                                <datalist id="sectionList">
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?php echo htmlspecialchars($sec); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Track</label>
                                <select name="track" class="form-select">
                                    <option value="">Select Track</option>
                                    <option value="Academic" <?php echo (isset($_POST['track']) && $_POST['track'] == 'Academic') ? 'selected' : ''; ?>>Academic</option>
                                    <option value="TVL" <?php echo (isset($_POST['track']) && $_POST['track'] == 'TVL') ? 'selected' : ''; ?>>TVL (Technical-Vocational-Livelihood)</option>
                                    <option value="Sports" <?php echo (isset($_POST['track']) && $_POST['track'] == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                    <option value="Arts and Design" <?php echo (isset($_POST['track']) && $_POST['track'] == 'Arts and Design') ? 'selected' : ''; ?>>Arts and Design</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Strand <span class="text-danger">*</span></label>
                                <input type="text" name="strand" class="form-control" list="strandList" required value="<?php echo isset($_POST['strand']) ? htmlspecialchars($_POST['strand']) : ''; ?>">
                                <datalist id="strandList">
                                    <?php foreach ($strands as $str): ?>
                                        <option value="<?php echo htmlspecialchars($str); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="form-text text-muted">e.g., STEM, HUMSS, ABM, GAS, ICT, HE</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="text-muted mb-3">Guardian Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" name="guardian_name" class="form-control" value="<?php echo isset($_POST['guardian_name']) ? htmlspecialchars($_POST['guardian_name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Guardian Contact</label>
                                <input type="text" name="guardian_contact" class="form-control" value="<?php echo isset($_POST['guardian_contact']) ? htmlspecialchars($_POST['guardian_contact']) : ''; ?>">
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="text-muted mb-3">Account Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?php echo (isset($_POST['is_active']) || !isset($_POST['submit'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="students.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
