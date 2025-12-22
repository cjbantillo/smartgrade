<?php
/**
 * Teacher Dashboard
 * Main dashboard for teachers
 */

session_start();

// Include configuration and helpers
require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';

// Require teacher role
requireRole('teacher');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get teacher info
$teacherId = null;
$stats = [];

try {
    // Get teacher record
    $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $teacher = $stmt->fetch();
    $teacherId = $teacher['id'];
    
    // Get assigned classes count
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ca.id) as total
        FROM class_assignments ca
        JOIN school_years sy ON ca.school_year_id = sy.id
        WHERE ca.teacher_id = ? AND sy.is_active = 1
    ");
    $stmt->execute([$teacherId]);
    $stats['assigned_classes'] = $stmt->fetch()['total'];
    
    // Get total students in assigned classes
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT s.id) as total
        FROM students s
        JOIN class_assignments ca ON s.section = ca.section
        JOIN school_years sy ON ca.school_year_id = sy.id
        WHERE ca.teacher_id = ? AND sy.is_active = 1 AND s.is_archived = 0
    ");
    $stmt->execute([$teacherId]);
    $stats['total_students'] = $stmt->fetch()['total'];
    
    // Get grades entered count (current quarter)
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM grades g
        JOIN grading_periods gp ON g.grading_period_id = gp.id
        WHERE g.teacher_id = ? AND gp.is_active = 1
    ");
    $stmt->execute([$teacherId]);
    $stats['grades_entered'] = $stmt->fetch()['total'];
    
    // Get assigned subjects
    $stmt = $db->prepare("
        SELECT DISTINCT s.subject_name, s.subject_code, ca.section
        FROM class_assignments ca
        JOIN subjects s ON ca.subject_id = s.id
        JOIN school_years sy ON ca.school_year_id = sy.id
        WHERE ca.teacher_id = ? AND sy.is_active = 1
        ORDER BY s.subject_name, ca.section
    ");
    $stmt->execute([$teacherId]);
    $assignedSubjects = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Teacher dashboard error: " . $e->getMessage());
    $stats = [
        'assigned_classes' => 0,
        'total_students' => 0,
        'grades_entered' => 0
    ];
    $assignedSubjects = [];
}

$pageTitle = 'Teacher Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
        .sidebar-menu {
            padding: 1rem 0;
        }
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
        .sidebar-menu a i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
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
            <i class="bi bi-mortarboard-fill me-2"></i>
            <?php echo APP_NAME; ?>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="my-classes.php">
                <i class="bi bi-journal-bookmark-fill"></i> My Classes
            </a>
            <a href="grade-entry.php">
                <i class="bi bi-pencil-square"></i> Enter Grades
            </a>
            <a href="students-list.php">
                <i class="bi bi-people-fill"></i> Students
            </a>
            <a href="students-list.php" title="Select student to generate SF9">
                <i class="bi bi-file-earmark-text"></i> Generate SF9
            </a>
            <a href="students-list.php" title="Select student to generate SF10">
                <i class="bi bi-file-earmark-pdf"></i> Generate SF10
            </a>
          
            <a href="certificates.php">
                <i class="bi bi-award-fill"></i> Certificates
            </a>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <a href="../../../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <div>
                    <span class="badge bg-success">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo getCurrentUserName(); ?>
                    </span>
                </div>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Welcome Message -->
        <div class="alert alert-success" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-emoji-smile me-2"></i>
                Welcome back, <?php echo getCurrentUserName(); ?>!
            </h5>
            <p class="mb-0">Here's an overview of your classes and grade entry progress for School Year <?php echo SCHOOL_YEAR; ?>.</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Assigned Classes</h6>
                            <h3 class="mb-0"><?php echo $stats['assigned_classes']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Students</h6>
                            <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Grades Entered</h6>
                            <h3 class="mb-0"><?php echo $stats['grades_entered']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assigned Subjects -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-book-fill me-2"></i>
                            My Assigned Subjects
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignedSubjects)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No subjects assigned yet. Please contact the administrator.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                            <th>Section</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignedSubjects as $subject): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($subject['section']); ?></span>
                                                </td>
                                                <td>
                                                    <a href="grade-entry.php?subject=<?php echo urlencode($subject['subject_code']); ?>&section=<?php echo urlencode($subject['section']); ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil-square me-1"></i>
                                                        Enter Grades
                                                    </a>
                                                    <a href="students-list.php?section=<?php echo urlencode($subject['section']); ?>" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="bi bi-people-fill me-1"></i>
                                                        View Students
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
