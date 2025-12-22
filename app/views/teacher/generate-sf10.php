<?php
/**
 * Generate SF10 (Learner's Permanent Record)
 * DepEd School Form 10
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
$student = $stmt->fetch();

if (!$student) {
    header('Location: students-list.php');
    exit;
}

// Get all grades for this student across all school years
$stmt = $db->prepare("
    SELECT 
        g.*,
        s.subject_code,
        s.subject_name,
        sy.year_code,
        gp.period_name,
        gp.period_number
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN grading_periods gp ON g.grading_period_id = gp.id
    JOIN school_years sy ON gp.school_year_id = sy.id
    WHERE g.student_id = ?
    ORDER BY sy.year_start, s.grade_level, gp.period_number, s.subject_code
");
$stmt->execute([$studentId]);
$allGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize grades by school year and subject
$gradesByYear = [];
foreach ($allGrades as $grade) {
    $year = $grade['year_code'];
    $subjectCode = $grade['subject_code'];
    
    if (!isset($gradesByYear[$year])) {
        $gradesByYear[$year] = [];
    }
    
    if (!isset($gradesByYear[$year][$subjectCode])) {
        $gradesByYear[$year][$subjectCode] = [
            'subject_name' => $grade['subject_name'],
            'quarters' => []
        ];
    }
    
    $gradesByYear[$year][$subjectCode]['quarters'][$grade['period_number']] = $grade['quarterly_grade'] ?? null;
}

$pageTitle = 'Generate SF10 - ' . $student['full_name'];
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
        .sf10-form {
            background: white;
            padding: 2rem;
            border: 2px solid #333;
        }
        .sf10-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #333;
            padding-bottom: 1rem;
        }
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .grade-table th, .grade-table td {
            border: 1px solid #333;
            padding: 0.5rem;
            text-align: center;
        }
        .grade-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        @media print {
            .sidebar, .navbar-custom, .no-print { display: none; }
            .main-content { margin-left: 0; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar no-print">
        <div class="sidebar-brand">
            <i class="bi bi-mortarboard-fill me-2"></i>
            <?php echo APP_NAME; ?>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="my-classes.php">
                <i class="bi bi-book"></i> My Classes
            </a>
            <a href="grade-entry.php">
                <i class="bi bi-pencil-square"></i> Enter Grades
            </a>
            <a href="students-list.php">
                <i class="bi bi-people"></i> Students
            </a>
            <a href="generate-sf9.php">
                <i class="bi bi-file-earmark-text"></i> Generate SF9
            </a>
            <a href="generate-sf10.php" class="active">
                <i class="bi bi-file-earmark-pdf"></i> Generate SF10
            </a>
            <a href="certificates.php">
                <i class="bi bi-award"></i> Certificates
            </a>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <a href="../../../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar-custom no-print">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <div>
                    <button onclick="window.print()" class="btn btn-primary me-2">
                        <i class="bi bi-printer me-1"></i> Print SF10
                    </button>
                    <a href="students-list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Students
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- SF10 Form -->
        <div class="sf10-form">
            <div class="sf10-header">
                <h5>Republic of the Philippines</h5>
                <h6>Department of Education</h6>
                <h4 class="mt-3">LEARNER'S PERMANENT RECORD</h4>
                <h5>(School Form 10 - SF10)</h5>
            </div>
            
            <!-- Student Information -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6 class="text-decoration-underline">LEARNER'S INFORMATION</h6>
                </div>
                <div class="col-md-6">
                    <p><strong>LRN:</strong> <?php echo htmlspecialchars($student['lrn']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
                    <p><strong>Birth Date:</strong> <?php echo date('F d, Y', strtotime($student['birth_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Track:</strong> <?php echo htmlspecialchars($student['track']); ?></p>
                    <p><strong>Strand:</strong> <?php echo htmlspecialchars($student['strand']); ?></p>
                    <p><strong>Section:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($student['contact_number']); ?></p>
                </div>
            </div>
            
            <!-- Academic Records -->
            <?php if (!empty($gradesByYear)): ?>
                <?php foreach ($gradesByYear as $year => $subjects): ?>
                    <div class="mb-4">
                        <h6 class="text-decoration-underline">SCHOOL YEAR: <?php echo htmlspecialchars($year); ?></h6>
                        <table class="grade-table">
                            <thead>
                                <tr>
                                    <th rowspan="2">Subject Code</th>
                                    <th rowspan="2">Subject Name</th>
                                    <th colspan="4">Quarterly Grades</th>
                                    <th rowspan="2">Final Grade</th>
                                    <th rowspan="2">Remarks</th>
                                </tr>
                                <tr>
                                    <th>1st</th>
                                    <th>2nd</th>
                                    <th>3rd</th>
                                    <th>4th</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $code => $data): ?>
                                    <?php
                                    $quarters = $data['quarters'];
                                    $q1 = $quarters[1] ?? '-';
                                    $q2 = $quarters[2] ?? '-';
                                    $q3 = $quarters[3] ?? '-';
                                    $q4 = $quarters[4] ?? '-';
                                    
                                    // Calculate final grade (average of available quarters)
                                    $validGrades = array_filter($quarters, function($g) { return is_numeric($g); });
                                    $finalGrade = !empty($validGrades) ? round(array_sum($validGrades) / count($validGrades), 2) : '-';
                                    $remarks = is_numeric($finalGrade) && $finalGrade >= 75 ? 'PASSED' : 'FAILED';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($code); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($data['subject_name']); ?></td>
                                        <td><?php echo $q1; ?></td>
                                        <td><?php echo $q2; ?></td>
                                        <td><?php echo $q3; ?></td>
                                        <td><?php echo $q4; ?></td>
                                        <td><strong><?php echo $finalGrade; ?></strong></td>
                                        <td><?php echo $remarks; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No grades recorded for this student yet.
                </div>
            <?php endif; ?>
            
            <!-- Certification -->
            <div class="mt-5">
                <p><strong>This is to certify that this is a true copy of the student's permanent record.</strong></p>
                <div class="row mt-5">
                    <div class="col-md-6">
                        <p>_________________________________</p>
                        <p><strong>Class Adviser</strong></p>
                    </div>
                    <div class="col-md-6">
                        <p>_________________________________</p>
                        <p><strong>School Principal</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
