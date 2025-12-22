<?php
/**
 * Student My Grades
 * Detailed view of all grades
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';

// Require student role
requireRole('student');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get student ID
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([getCurrentUserId()]);
$student = $stmt->fetch();
$studentId = $student['id'];

// Get all grades grouped by subject and grading period
$stmt = $db->prepare("
    SELECT 
        g.*,
        s.subject_code,
        s.subject_name,
        gp.period_name,
        gp.period_number,
        sy.year_code,
        CONCAT(u.first_name, ' ', u.last_name) as teacher_name
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN grading_periods gp ON g.grading_period_id = gp.id
    JOIN school_years sy ON g.school_year_id = sy.id
    JOIN teachers t ON g.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE g.student_id = ?
    ORDER BY sy.year_start DESC, s.subject_name, gp.period_number
");
$stmt->execute([$studentId]);
$grades = $stmt->fetchAll();

// Get final grades
$stmt = $db->prepare("
    SELECT 
        fg.*,
        s.subject_code,
        s.subject_name,
        sy.year_code
    FROM final_grades fg
    JOIN subjects s ON fg.subject_id = s.id
    JOIN school_years sy ON fg.school_year_id = sy.id
    WHERE fg.student_id = ?
    ORDER BY sy.year_start DESC, s.subject_name
");
$stmt->execute([$studentId]);
$finalGrades = $stmt->fetchAll();

$pageTitle = 'My Grades';
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
            <a href="my-grades.php" class="active">
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
        
        <!-- Quarterly Grades -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>
                    Quarterly Grades
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($grades)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No grades available yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>School Year</th>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Period</th>
                                    <th>Teacher</th>
                                    <th class="text-center">Written Work</th>
                                    <th class="text-center">Performance Task</th>
                                    <th class="text-center">Assessment</th>
                                    <th class="text-center">Quarterly Grade</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['year_code']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($grade['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['period_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['teacher_name']); ?></td>
                                        <td class="text-center">
                                            <?php if ($grade['written_work_score'] !== null): ?>
                                                <?php echo $grade['written_work_score']; ?>/<?php echo $grade['written_work_total']; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($grade['performance_task_score'] !== null): ?>
                                                <?php echo $grade['performance_task_score']; ?>/<?php echo $grade['performance_task_total']; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($grade['quarterly_assessment_score'] !== null): ?>
                                                <?php echo $grade['quarterly_assessment_score']; ?>/<?php echo $grade['quarterly_assessment_total']; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?php echo getGradeColorClass($grade['quarterly_grade']); ?>" style="font-size: 1.1rem; font-weight: bold;">
                                                <?php echo formatGrade($grade['quarterly_grade']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($grade['quarterly_grade'] >= 75): ?>
                                                <span class="badge bg-success">PASSED</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">FAILED</span>
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
        
        <!-- Final Grades -->
        <?php if (!empty($finalGrades)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-trophy me-2"></i>
                    Final Grades (Semester/Yearly)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>School Year</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Semester</th>
                                <th class="text-center">Q1 Grade</th>
                                <th class="text-center">Q2 Grade</th>
                                <th class="text-center">Final Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finalGrades as $fg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fg['year_code']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($fg['subject_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($fg['subject_name']); ?></td>
                                    <td>Semester <?php echo $fg['semester']; ?></td>
                                    <td class="text-center"><?php echo formatGrade($fg['q1_grade']); ?></td>
                                    <td class="text-center"><?php echo formatGrade($fg['q2_grade']); ?></td>
                                    <td class="text-center">
                                        <span class="<?php echo getGradeColorClass($fg['final_grade']); ?>" style="font-size: 1.1rem; font-weight: bold;">
                                            <?php echo formatGrade($fg['final_grade']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fg['remarks']): ?>
                                            <span class="badge <?php echo $fg['remarks'] == 'PASSED' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars($fg['remarks']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
