<?php
/**
 * Students List - All students in teacher's classes
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
$stmt = $db->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$activeYear = $stmt->fetch();
$schoolYearId = $activeYear['id'];

// Get search and filter parameters
$searchTerm = getParam('search', '');
$filterSection = getParam('section', '');

// Build query for students
$sql = "
    SELECT DISTINCT
        s.id,
        s.lrn,
        u.first_name,
        u.middle_name,
        u.last_name,
        s.gender,
        s.section,
        s.birth_date,
        s.contact_number,
        u.email,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN class_assignments ca ON s.section = ca.section
    WHERE ca.teacher_id = ? AND ca.school_year_id = ?
";

$params = [$teacherId, $schoolYearId];

// Add search filter
if ($searchTerm) {
    $sql .= " AND (s.lrn LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Add section filter
if ($filterSection) {
    $sql .= " AND s.section = ?";
    $params[] = $filterSection;
}

$sql .= " ORDER BY s.section, u.last_name, u.first_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of sections for filter
$stmt = $db->prepare("
    SELECT DISTINCT ca.section
    FROM class_assignments ca
    WHERE ca.teacher_id = ? AND ca.school_year_id = ?
    ORDER BY ca.section
");
$stmt->execute([$teacherId, $schoolYearId]);
$sections = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Students List';
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
            <a href="generate-sf9.php">
                <i class="bi bi-file-earmark-text"></i> Generate SF9
            </a>
            <a href="generate-sf10.php">
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
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Search and Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Search:</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name or LRN..."
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Section:</label>
                            <select name="section" class="form-select">
                                <option value="">All Sections</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?php echo $sec; ?>" <?php echo $filterSection == $sec ? 'selected' : ''; ?>>
                                        Section <?php echo htmlspecialchars($sec); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Students Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill me-2"></i>
                    All Students (<?php echo count($students); ?>)
                </h5>
                <?php if ($searchTerm || $filterSection): ?>
                    <a href="students-list.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-success">
                            <tr>
                                <th>No.</th>
                                <th>LRN</th>
                                <th>Student Name</th>
                                <th>Section</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No students found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($student['lrn']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($student['section']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['birth_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="student-grades.php?student_id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-outline-success" title="View Grades">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="generate-sf9.php?student_id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Generate SF9" target="_blank">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                </a>
                                                <a href="generate-sf10.php?student_id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-outline-info" title="Generate SF10" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                            </div>
                                        </td>
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
