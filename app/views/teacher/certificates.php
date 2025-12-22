<?php
/**
 * Generate Certificates (Honors, Good Moral, etc.)
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
$stmt = $db->prepare("SELECT t.id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.user_id = ?");
$stmt->execute([getCurrentUserId()]);
$teacher = $stmt->fetch();
$teacherId = $teacher['id'];
$teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];

// Get students eligible for honors (using final_grades table for yearly average)
$stmt = $db->prepare("
    SELECT 
        s.id,
        s.lrn,
        u.first_name,
        u.middle_name,
        u.last_name,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name,
        s.grade_level,
        s.section,
        s.strand,
        AVG(fg.final_grade) as general_average
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN final_grades fg ON s.id = fg.student_id
    JOIN class_assignments ca ON s.section = ca.section
    WHERE ca.teacher_id = ? AND fg.final_grade IS NOT NULL
    GROUP BY s.id
    HAVING AVG(fg.final_grade) >= 90
    ORDER BY general_average DESC
");
$stmt->execute([$teacherId]);
$honorStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine honors level
foreach ($honorStudents as &$student) {
    $avg = $student['general_average'];
    if ($avg >= 98) {
        $student['honors'] = 'With Highest Honors';
        $student['badge'] = 'success';
    } elseif ($avg >= 95) {
        $student['honors'] = 'With High Honors';
        $student['badge'] = 'primary';
    } elseif ($avg >= 90) {
        $student['honors'] = 'With Honors';
        $student['badge'] = 'warning';
    }
}

$pageTitle = 'Generate Certificates';
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
        .certificate-preview {
            border: 3px double #FFD700;
            padding: 3rem;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            text-align: center;
            margin: 2rem 0;
        }
        @media print {
            .sidebar, .navbar-custom, .no-print { display: none; }
            .main-content { margin-left: 0; }
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
                <i class="bi bi-book"></i> My Classes
            </a>
            <a href="grade-entry.php">
                <i class="bi bi-pencil-square"></i> Enter Grades
            </a>
            <a href="students-list.php">
                <i class="bi bi-people"></i> Students
            </a>
            <a href="students-list.php" title="Select student to generate SF9">
                <i class="bi bi-file-earmark-text"></i> Generate SF9
            </a>
            <a href="students-list.php" title="Select student to generate SF10">
                <i class="bi bi-file-earmark-pdf"></i> Generate SF10
            </a>
            <a href="certificates.php" class="active">
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
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <span class="badge bg-success">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo getCurrentUserName(); ?>
                </span>
            </div>
        </nav>
        
        <!-- Certificate Types -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-award me-2"></i>
                            Certificate Types
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-warning mb-3">
                                    <div class="card-body text-center">
                                        <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3">Academic Honors</h5>
                                        <p class="text-muted">For students with 90+ average</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary mb-3">
                                    <div class="card-body text-center">
                                        <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3">Good Moral</h5>
                                        <p class="text-muted">Character certificate</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success mb-3">
                                    <div class="card-body text-center">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3">Completion</h5>
                                        <p class="text-muted">Course completion certificate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Honor Students List -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-star-fill me-2"></i>
                    Students Eligible for Honors (<?php echo count($honorStudents); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($honorStudents)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No students have qualified for honors yet. Students need a general average of 90 or above.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>LRN</th>
                                    <th>Student Name</th>
                                    <th>Grade & Section</th>
                                    <th>Strand</th>
                                    <th>General Average</th>
                                    <th>Honors</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($honorStudents as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td>Grade <?php echo $student['grade_level']; ?> - <?php echo htmlspecialchars($student['section']); ?></td>
                                        <td><?php echo htmlspecialchars($student['strand']); ?></td>
                                        <td><strong><?php echo number_format($student['general_average'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $student['badge']; ?>">
                                                <?php echo $student['honors']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="printCertificate(<?php echo $student['id']; ?>, '<?php echo addslashes($student['full_name']); ?>', '<?php echo $student['honors']; ?>', <?php echo $student['general_average']; ?>)">
                                                <i class="bi bi-printer me-1"></i> Print
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Certificate Preview (Hidden) -->
        <div id="certificatePreview" style="display:none;">
            <div class="certificate-preview">
                <h3>AMPAYON SENIOR HIGH SCHOOL</h3>
                <h5 class="text-muted">Butuan City, Philippines</h5>
                <hr style="width: 50%; margin: 2rem auto; border: 2px solid #333;">
                <h2 class="mt-5">CERTIFICATE OF RECOGNITION</h2>
                <p class="lead mt-4">This is to certify that</p>
                <h1 class="text-primary my-4" id="certStudentName"></h1>
                <p class="lead">has earned academic distinction</p>
                <h3 class="text-success my-4" id="certHonors"></h3>
                <p class="lead">with a General Average of <strong id="certAverage"></strong></p>
                <p class="lead mt-5">for the School Year 2024-2025</p>
                <div class="row mt-5">
                    <div class="col-md-6">
                        <p>_______________________________</p>
                        <p><strong>Class Adviser</strong></p>
                    </div>
                    <div class="col-md-6">
                        <p>_______________________________</p>
                        <p><strong>School Principal</strong></p>
                    </div>
                </div>
                <p class="mt-4"><small>Date Issued: <?php echo date('F d, Y'); ?></small></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printCertificate(studentId, studentName, honors, average) {
            // Set certificate details
            document.getElementById('certStudentName').textContent = studentName;
            document.getElementById('certHonors').textContent = honors;
            document.getElementById('certAverage').textContent = average.toFixed(2);
            
            // Show preview
            const preview = document.getElementById('certificatePreview');
            preview.style.display = 'block';
            
            // Print
            setTimeout(() => {
                window.print();
                preview.style.display = 'none';
            }, 100);
        }
    </script>
</body>
</html>
