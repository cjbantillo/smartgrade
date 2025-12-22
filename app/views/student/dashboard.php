<?php
/**
 * Student Dashboard
 * Main dashboard for students
 */

session_start();

// Include configuration and helpers
require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';

// Require student role
requireRole('student');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get student info
$studentId = null;
$studentInfo = [];
$grades = [];
$generalAverage = 0;
$honorStatus = null;

try {
    // Get student record
    $stmt = $db->prepare("
        SELECT s.*, u.email
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.user_id = ?
    ");
    $stmt->execute([getCurrentUserId()]);
    $studentInfo = $stmt->fetch();
    $studentId = $studentInfo['id'];
    
    // Get current grades (active grading period)
    $stmt = $db->prepare("
        SELECT 
            g.*,
            s.subject_code,
            s.subject_name,
            gp.period_name,
            t.first_name as teacher_fname,
            t.last_name as teacher_lname
        FROM grades g
        JOIN subjects s ON g.subject_id = s.id
        JOIN grading_periods gp ON g.grading_period_id = gp.id
        JOIN teachers teach ON g.teacher_id = teach.id
        JOIN users t ON teach.user_id = t.id
        WHERE g.student_id = ?
        ORDER BY gp.period_number DESC, s.subject_name
    ");
    $stmt->execute([$studentId]);
    $grades = $stmt->fetchAll();
    
    // Calculate general average
    if (!empty($grades)) {
        $quarterlyGrades = array_column($grades, 'quarterly_grade');
        $generalAverage = computeGeneralAverage($quarterlyGrades);
        $honorStatus = getHonorStatus($generalAverage);
    }
    
    // Get honor awards
    $stmt = $db->prepare("
        SELECT h.*, sy.year_code
        FROM honors h
        JOIN school_years sy ON h.school_year_id = sy.id
        WHERE h.student_id = ?
        ORDER BY sy.year_start DESC
    ");
    $stmt->execute([$studentId]);
    $honors = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Student dashboard error: " . $e->getMessage());
    $studentInfo = [];
    $grades = [];
    $honors = [];
}

$pageTitle = 'Student Dashboard';
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
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
        }
        .grade-excellent { color: #28a745; font-weight: bold; }
        .grade-good { color: #17a2b8; }
        .grade-passing { color: #ffc107; }
        .grade-failing { color: #dc3545; font-weight: bold; }
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
            <a href="my-grades.php">
                <i class="bi bi-clipboard-data"></i> My Grades
            </a>
            <a href="honors.php">
                <i class="bi bi-award-fill"></i> Honors & Awards
            </a>
            <a href="profile.php">
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
        <!-- Top Navigation -->
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <div>
                    <span class="badge bg-primary">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo getCurrentUserName(); ?>
                    </span>
                </div>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Welcome Message -->
        <div class="alert alert-info" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-emoji-smile me-2"></i>
                Welcome, <?php echo getCurrentUserName(); ?>!
            </h5>
            <p class="mb-0">Track your academic progress and view your grades for School Year <?php echo SCHOOL_YEAR; ?>.</p>
        </div>
        
        <!-- Student Information -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="bi bi-person-badge me-2"></i>
                        Student Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Student Number:</strong> <?php echo htmlspecialchars($studentInfo['student_number']); ?></p>
                            <p><strong>LRN:</strong> <?php echo htmlspecialchars($studentInfo['lrn']); ?></p>
                            <p><strong>Grade Level:</strong> Grade <?php echo $studentInfo['grade_level']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Track:</strong> <?php echo htmlspecialchars($studentInfo['track']); ?></p>
                            <p><strong>Strand:</strong> <?php echo htmlspecialchars($studentInfo['strand']); ?></p>
                            <p><strong>Section:</strong> <?php echo htmlspecialchars($studentInfo['section']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="info-card text-center">
                    <h5 class="mb-3">
                        <i class="bi bi-graph-up me-2"></i>
                        General Average
                    </h5>
                    <h1 class="display-4 <?php echo getGradeColorClass($generalAverage); ?>">
                        <?php echo formatGrade($generalAverage); ?>
                    </h1>
                    <?php if ($honorStatus): ?>
                        <div class="alert alert-success mt-3">
                            <i class="bi bi-award-fill me-2"></i>
                            <strong><?php echo $honorStatus; ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Current Grades -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-clipboard-data me-2"></i>
                            My Current Grades
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($grades)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle me-2"></i>
                                No grades available yet. Grades will appear here once your teachers enter them.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                            <th>Grading Period</th>
                                            <th>Teacher</th>
                                            <th class="text-center">Quarterly Grade</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grades as $grade): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($grade['subject_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['period_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['teacher_fname'] . ' ' . $grade['teacher_lname']); ?></td>
                                                <td class="text-center">
                                                    <span class="<?php echo getGradeColorClass($grade['quarterly_grade']); ?>" style="font-size: 1.1rem;">
                                                        <?php echo formatGrade($grade['quarterly_grade']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($grade['quarterly_grade'] >= 75): ?>
                                                        <span class="badge bg-success">PASSED</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">NEEDS IMPROVEMENT</span>
                                                    <?php endif; ?>
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
        
        <!-- Honors & Awards -->
        <?php if (!empty($honors)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-trophy-fill me-2"></i>
                            Honors & Awards
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($honors as $honor): ?>
                                <div class="col-md-4">
                                    <div class="alert alert-success">
                                        <h6><i class="bi bi-award-fill me-2"></i><?php echo htmlspecialchars($honor['honor_type']); ?></h6>
                                        <p class="mb-0">
                                            <small>
                                                <?php echo htmlspecialchars($honor['year_code']); ?> - Semester <?php echo $honor['semester']; ?><br>
                                                General Average: <strong><?php echo formatGrade($honor['general_average']); ?></strong>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
