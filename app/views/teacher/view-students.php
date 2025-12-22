<?php
/**
 * View Students in a Class
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

// Get parameters
$subjectId = getParam('subject_id');
$section = getParam('section');

if (!$subjectId || !$section) {
    header('Location: my-classes.php');
    exit;
}

// Get subject details
$stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subjectId]);
$subject = $stmt->fetch();

// Get students in the section with their contact details
$stmt = $db->prepare("
    SELECT 
        s.*,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.section = ?
    ORDER BY u.last_name, u.first_name
");
$stmt->execute([$section]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'View Students';
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
        .student-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
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
                <span class="badge bg-success">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo getCurrentUserName(); ?>
                </span>
            </div>
        </nav>
        
        <!-- Class Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">
                            <?php echo htmlspecialchars($subject['subject_code']); ?> - 
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </h5>
                        <p class="text-muted mb-0">Section <?php echo htmlspecialchars($section); ?></p>
                    </div>
                    <div>
                        <a href="my-classes.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Classes
                        </a>
                        <a href="grade-entry.php?subject_id=<?php echo $subjectId; ?>&section=<?php echo urlencode($section); ?>" 
                           class="btn btn-success">
                            <i class="bi bi-pencil-square me-1"></i> Enter Grades
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Students Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill me-2"></i>
                    Students List (<?php echo count($students); ?> students)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-success">
                            <tr>
                                <th>No.</th>
                                <th>LRN</th>
                                <th>Student Name</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Contact</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No students found in this section.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['birth_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
