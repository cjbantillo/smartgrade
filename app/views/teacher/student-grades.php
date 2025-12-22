<?php
/**
 * View Student Grades - Teacher
 * Teachers can view detailed grades for a specific student
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';

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

// Get selected student
$studentId = getParam('student_id');

if (!$studentId) {
    header('Location: students-list.php');
    exit;
}

// Get student details
$stmt = $db->prepare("
    SELECT 
        s.*,
        u.first_name,
        u.middle_name,
        u.last_name,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name,
        u.email
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    setFlashMessage('Student not found', 'danger');
    header('Location: students-list.php');
    exit;
}

// Get active school year
$stmt = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1");
$schoolYear = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all grading periods
$stmt = $db->prepare("SELECT * FROM grading_periods WHERE school_year_id = ? ORDER BY period_number");
$stmt->execute([$schoolYear['id']]);
$gradingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's grades for this school year
$stmt = $db->prepare("
    SELECT 
        g.*,
        s.subject_code,
        s.subject_name,
        gp.period_name,
        gp.period_number,
        CONCAT(tu.first_name, ' ', tu.last_name) as teacher_name
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN grading_periods gp ON g.grading_period_id = gp.id
    JOIN teachers t ON g.teacher_id = t.id
    JOIN users tu ON t.user_id = tu.id
    WHERE g.student_id = ? AND g.school_year_id = ?
    ORDER BY s.subject_name, gp.period_number
");
$stmt->execute([$studentId, $schoolYear['id']]);
$gradesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize grades by subject
$gradesBySubject = [];
foreach ($gradesData as $grade) {
    $subjectId = $grade['subject_id'];
    if (!isset($gradesBySubject[$subjectId])) {
        $gradesBySubject[$subjectId] = [
            'subject_code' => $grade['subject_code'],
            'subject_name' => $grade['subject_name'],
            'teacher_name' => $grade['teacher_name'],
            'quarters' => []
        ];
    }
    $gradesBySubject[$subjectId]['quarters'][$grade['period_number']] = [
        'quarterly_grade' => $grade['quarterly_grade'],
        'written_work_score' => $grade['written_work_score'],
        'written_work_total' => $grade['written_work_total'],
        'performance_task_score' => $grade['performance_task_score'],
        'performance_task_total' => $grade['performance_task_total'],
        'quarterly_assessment_score' => $grade['quarterly_assessment_score'],
        'quarterly_assessment_total' => $grade['quarterly_assessment_total']
    ];
}

// Calculate final grades
foreach ($gradesBySubject as $subjectId => &$subject) {
    $quarterGrades = array_column($subject['quarters'], 'quarterly_grade');
    $validGrades = array_filter($quarterGrades, function($g) { return is_numeric($g); });
    if (!empty($validGrades)) {
        $subject['final_grade'] = array_sum($validGrades) / count($validGrades);
        $subject['remarks'] = $subject['final_grade'] >= 75 ? 'PASSED' : 'FAILED';
    } else {
        $subject['final_grade'] = null;
        $subject['remarks'] = '-';
    }
}

$pageTitle = 'Student Grades';
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
        .student-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .grades-table {
            font-size: 0.9rem;
        }
        .grade-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
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
            <a href="my-classes.php">
                <i class="bi bi-journal-bookmark-fill"></i> My Classes
            </a>
            <a href="grade-entry.php">
                <i class="bi bi-pencil-square"></i> Enter Grades
            </a>
            <a href="students-list.php" class="active">
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
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <a href="students-list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Students
                </a>
            </div>
        </nav>
        
        <!-- Student Info Card -->
        <div class="student-info-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-2">
                        <i class="bi bi-person-circle me-2"></i>
                        <?php echo htmlspecialchars($student['full_name']); ?>
                    </h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>LRN:</strong> <?php echo htmlspecialchars($student['lrn']); ?></p>
                            <p class="mb-1"><strong>Grade & Section:</strong> Grade <?php echo htmlspecialchars($student['grade_level']); ?> - <?php echo htmlspecialchars($student['section']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Track:</strong> <?php echo htmlspecialchars($student['track']); ?></p>
                            <p class="mb-1"><strong>Strand:</strong> <?php echo htmlspecialchars($student['strand']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group">
                        <a href="generate-sf9.php?student_id=<?php echo $studentId; ?>" class="btn btn-light" target="_blank">
                            <i class="bi bi-file-earmark-text me-1"></i> Generate SF9
                        </a>
                        <a href="generate-sf10.php?student_id=<?php echo $studentId; ?>" class="btn btn-light" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Generate SF10
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grades Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-clipboard-data me-2"></i>
                    Academic Performance - S.Y. <?php echo htmlspecialchars($schoolYear['year_code'] ?? 'N/A'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($gradesBySubject)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No grades recorded for this student yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover grades-table">
                            <thead class="table-success">
                                <tr>
                                    <th rowspan="2" class="align-middle">Subject</th>
                                    <th rowspan="2" class="align-middle">Teacher</th>
                                    <th colspan="4" class="text-center">Quarterly Grades</th>
                                    <th rowspan="2" class="text-center align-middle">Final Grade</th>
                                    <th rowspan="2" class="text-center align-middle">Remarks</th>
                                </tr>
                                <tr>
                                    <th class="text-center">1st</th>
                                    <th class="text-center">2nd</th>
                                    <th class="text-center">3rd</th>
                                    <th class="text-center">4th</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gradesBySubject as $subject): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($subject['subject_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($subject['teacher_name']); ?></td>
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <td class="text-center">
                                                <?php 
                                                if (isset($subject['quarters'][$i])) {
                                                    $grade = $subject['quarters'][$i]['quarterly_grade'];
                                                    if ($grade !== null) {
                                                        $gradeColor = $grade >= 90 ? 'success' : 
                                                                    ($grade >= 85 ? 'primary' : 
                                                                    ($grade >= 80 ? 'info' : 
                                                                    ($grade >= 75 ? 'warning' : 'danger')));
                                                        echo '<span class="grade-badge bg-' . $gradeColor . ' text-white">' . 
                                                             formatGrade($grade) . '</span>';
                                                        
                                                        // Breakdown tooltip
                                                        $ww = $subject['quarters'][$i]['written_work_score'] . '/' . $subject['quarters'][$i]['written_work_total'];
                                                        $pt = $subject['quarters'][$i]['performance_task_score'] . '/' . $subject['quarters'][$i]['performance_task_total'];
                                                        $qa = $subject['quarters'][$i]['quarterly_assessment_score'] . '/' . $subject['quarters'][$i]['quarterly_assessment_total'];
                                                        echo '<br><small class="text-muted" style="font-size: 0.7rem;">WW: ' . $ww . ' | PT: ' . $pt . ' | QA: ' . $qa . '</small>';
                                                    } else {
                                                        echo '-';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                        <?php endfor; ?>
                                        <td class="text-center">
                                            <?php if ($subject['final_grade'] !== null): ?>
                                                <strong class="fs-5">
                                                    <?php echo formatGrade($subject['final_grade']); ?>
                                                </strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($subject['remarks'] == 'PASSED'): ?>
                                                <span class="badge bg-success">PASSED</span>
                                            <?php elseif ($subject['remarks'] == 'FAILED'): ?>
                                                <span class="badge bg-danger">FAILED</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- General Average -->
                    <?php 
                    $allFinalGrades = array_filter(array_column($gradesBySubject, 'final_grade'));
                    if (!empty($allFinalGrades)):
                        $generalAverage = array_sum($allFinalGrades) / count($allFinalGrades);
                    ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0">General Average</h5>
                                    <small class="text-muted">Computed from all final grades</small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <h3 class="mb-0">
                                        <span class="badge bg-<?php echo $generalAverage >= 90 ? 'success' : ($generalAverage >= 85 ? 'primary' : ($generalAverage >= 75 ? 'warning' : 'danger')); ?>">
                                            <?php echo formatGrade($generalAverage); ?>
                                        </span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
