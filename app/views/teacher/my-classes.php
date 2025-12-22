<?php
/**
 * My Classes - Teacher's Assigned Classes
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';

// Require teacher role
requireRole('teacher');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get teacher ID
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([getCurrentUserId()]);
$teacher = $stmt->fetch();
$teacherId = $teacher['id'];

// Get active school year
$stmt = $db->query("SELECT id, year_code FROM school_years WHERE is_active = 1 LIMIT 1");
$activeYear = $stmt->fetch();
$schoolYearId = $activeYear['id'];
$schoolYear = $activeYear['year_code'];

// Get teacher's assigned classes with student count and grade statistics
$stmt = $db->prepare("
    SELECT 
        ca.subject_id,
        s.subject_code,
        s.subject_name,
        ca.section,
        COUNT(DISTINCT st.id) as student_count,
        COUNT(DISTINCT g.id) as grades_entered,
        COUNT(DISTINCT CASE WHEN g.quarterly_grade >= 75 THEN g.id END) as passed_count
    FROM class_assignments ca
    JOIN subjects s ON ca.subject_id = s.id
    LEFT JOIN students st ON st.section = ca.section
    LEFT JOIN grades g ON g.subject_id = ca.subject_id 
        AND g.student_id = st.id 
        AND g.school_year_id = ca.school_year_id
    WHERE ca.teacher_id = ? AND ca.school_year_id = ?
    GROUP BY ca.subject_id, ca.section
    ORDER BY ca.section, s.subject_name
");
$stmt->execute([$teacherId, $schoolYearId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Classes';
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
        .class-card {
            border-left: 4px solid #11998e;
            transition: transform 0.2s;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
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
            <a href="my-classes.php" class="active">
                <i class="bi bi-journal-bookmark-fill"></i> My Classes
            </a>
            <a href="grade-entry.php">
                <i class="bi bi-pencil-square"></i> Enter Grades
            </a>
            <a href="students-list.php">
                <i class="bi bi-people-fill"></i> Students
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
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                    <small class="text-muted">School Year: <?php echo $schoolYear; ?></small>
                </div>
                <span class="badge bg-success">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo getCurrentUserName(); ?>
                </span>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Classes Grid -->
        <div class="row g-4">
            <?php if (empty($classes)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No classes assigned yet. Please contact the administrator.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $class): ?>
                    <?php
                        $completion = $class['student_count'] > 0 
                            ? round(($class['grades_entered'] / $class['student_count']) * 100) 
                            : 0;
                        $passRate = $class['grades_entered'] > 0
                            ? round(($class['passed_count'] / $class['grades_entered']) * 100)
                            : 0;
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card class-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <?php echo htmlspecialchars($class['subject_code']); ?>
                                        </h5>
                                        <p class="text-muted mb-0 small">
                                            <?php echo htmlspecialchars($class['subject_name']); ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-primary">
                                        Section <?php echo htmlspecialchars($class['section']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="text-muted small">
                                        <i class="bi bi-bookmark me-1"></i>
                                        Section: <?php echo htmlspecialchars($class['section']); ?>
                                    </div>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="progress-circle bg-light">
                                            <span class="text-success"><?php echo $class['student_count']; ?></span>
                                        </div>
                                        <small class="d-block mt-1 text-muted">Students</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="progress-circle <?php echo $completion >= 75 ? 'bg-success text-white' : ($completion >= 50 ? 'bg-warning' : 'bg-light'); ?>">
                                            <span><?php echo $completion; ?>%</span>
                                        </div>
                                        <small class="d-block mt-1 text-muted">Graded</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="progress-circle <?php echo $passRate >= 75 ? 'bg-success text-white' : ($passRate >= 50 ? 'bg-warning' : 'bg-light'); ?>">
                                            <span><?php echo $passRate; ?>%</span>
                                        </div>
                                        <small class="d-block mt-1 text-muted">Passing</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="grade-entry.php?subject_id=<?php echo $class['subject_id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="bi bi-pencil-square me-1"></i> Enter Grades
                                    </a>
                                    <a href="view-students.php?subject_id=<?php echo $class['subject_id']; ?>&section=<?php echo urlencode($class['section']); ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-people me-1"></i> View Students
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
