<?php
/**
 * Add Subject - Admin
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subjectCode = trim($_POST['subject_code']);
    $subjectName = trim($_POST['subject_name']);
    $gradeLevel = $_POST['grade_level'];
    $track = trim($_POST['track']);
    $strand = trim($_POST['strand']);
    $semester = $_POST['semester'];
    $subjectType = $_POST['subject_type'];
    $units = $_POST['units'];
    $description = trim($_POST['description']);
    
    $errors = [];
    
    // Validate required fields
    if (empty($subjectCode)) $errors[] = 'Subject code is required';
    if (empty($subjectName)) $errors[] = 'Subject name is required';
    if (empty($gradeLevel)) $errors[] = 'Grade level is required';
    if (empty($semester)) $errors[] = 'Semester is required';
    if (empty($subjectType)) $errors[] = 'Subject type is required';
    
    // Check subject code uniqueness
    if (!empty($subjectCode)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
        $stmt->execute([$subjectCode]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Subject code already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO subjects (subject_code, subject_name, grade_level, track, strand, semester, subject_type, units, description)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$subjectCode, $subjectName, $gradeLevel, $track, $strand, $semester, $subjectType, $units, $description]);
            
            // Audit log
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'create', 'subjects', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $db->lastInsertId(), $_SERVER['REMOTE_ADDR']]);
            
            setFlashMessage('Subject added successfully', 'success');
            header('Location: subjects.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Subject';
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
            <a href="teachers.php">
                <i class="bi bi-person-badge-fill"></i> Teachers
            </a>
            <a href="subjects.php" class="active">
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
                <a href="subjects.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Subjects
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
                    <i class="bi bi-book-fill me-2"></i>
                    Subject Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                                <input type="text" name="subject_code" class="form-control" required value="<?php echo isset($_POST['subject_code']) ? htmlspecialchars($_POST['subject_code']) : ''; ?>" placeholder="e.g., ENG11-Q1">
                                <small class="form-text text-muted">Unique identifier for the subject</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" name="subject_name" class="form-control" required value="<?php echo isset($_POST['subject_name']) ? htmlspecialchars($_POST['subject_name']) : ''; ?>" placeholder="e.g., English for Academic and Professional Purposes">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                                <select name="grade_level" class="form-select" required>
                                    <option value="">Select Grade</option>
                                    <option value="11" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                    <option value="12" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Track</label>
                                <select name="track" class="form-select">
                                    <option value="">Select Track (Optional)</option>
                                    <option value="Academic" <?php echo (isset($_POST['track']) && $_POST['track'] == 'Academic') ? 'selected' : ''; ?>>Academic</option>
                                    <option value="TVL" <?php echo (isset($_POST['track']) && $_POST['track'] == 'TVL') ? 'selected' : ''; ?>>TVL (Technical-Vocational-Livelihood)</option>
                                    <option value="Sports" <?php echo (isset($_POST['track']) && $_POST['track'] == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                    <option value="Arts and Design" <?php echo (isset($_POST['track']) && $_POST['track'] == 'Arts and Design') ? 'selected' : ''; ?>>Arts and Design</option>
                                    <option value="All Tracks" <?php echo (isset($_POST['track']) && $_POST['track'] == 'All Tracks') ? 'selected' : ''; ?>>All Tracks</option>
                                </select>
                                <small class="form-text text-muted">Leave blank or select "All Tracks" if subject is for all tracks</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Strand</label>
                                <input type="text" name="strand" class="form-control" value="<?php echo isset($_POST['strand']) ? htmlspecialchars($_POST['strand']) : ''; ?>" placeholder="e.g., STEM, HUMSS, ABM, GAS">
                                <small class="form-text text-muted">Leave blank if subject is for all strands</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester" <?php echo (isset($_POST['semester']) && $_POST['semester'] == '1st Semester') ? 'selected' : ''; ?>>1st Semester</option>
                                    <option value="2nd Semester" <?php echo (isset($_POST['semester']) && $_POST['semester'] == '2nd Semester') ? 'selected' : ''; ?>>2nd Semester</option>
                                    <option value="Year-round" <?php echo (isset($_POST['semester']) && $_POST['semester'] == 'Year-round') ? 'selected' : ''; ?>>Year-round</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject Type <span class="text-danger">*</span></label>
                                <select name="subject_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Core" <?php echo (isset($_POST['subject_type']) && $_POST['subject_type'] == 'Core') ? 'selected' : ''; ?>>Core Subject</option>
                                    <option value="Applied" <?php echo (isset($_POST['subject_type']) && $_POST['subject_type'] == 'Applied') ? 'selected' : ''; ?>>Applied Track Subject</option>
                                    <option value="Specialized" <?php echo (isset($_POST['subject_type']) && $_POST['subject_type'] == 'Specialized') ? 'selected' : ''; ?>>Specialized Subject</option>
                                </select>
                                <small class="form-text text-muted">
                                    Core: General subjects for all<br>
                                    Applied: Track-specific subjects<br>
                                    Specialized: Strand-specific subjects
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Units</label>
                                <input type="number" name="units" class="form-control" min="0" step="0.5" value="<?php echo isset($_POST['units']) ? htmlspecialchars($_POST['units']) : ''; ?>" placeholder="e.g., 1.0 or 0.5">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the subject"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="text-end">
                        <a href="subjects.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Subject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
