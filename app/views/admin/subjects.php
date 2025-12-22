<?php
/**
 * Subjects Management - Admin
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

// Handle delete subject
if (isset($_GET['delete'])) {
    $subjectId = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        setFlashMessage('Subject deleted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error deleting subject: ' . $e->getMessage(), 'danger');
    }
    header('Location: subjects.php');
    exit;
}

// Get all subjects
$search = getParam('search', '');
$gradeFilter = getParam('grade_level', '');
$categoryFilter = getParam('category', '');

$sql = "SELECT * FROM subjects WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (subject_code LIKE ? OR subject_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($gradeFilter) {
    $sql .= " AND grade_level = ?";
    $params[] = $gradeFilter;
}

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY grade_level, subject_type, subject_code";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Subjects Management';
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
                <span class="badge bg-primary">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo getCurrentUserName(); ?>
                </span>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search subjects..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="grade_level" class="form-select">
                            <option value="">All Grades</option>
                            <option value="11" <?php echo $gradeFilter == '11' ? 'selected' : ''; ?>>Grade 11</option>
                            <option value="12" <?php echo $gradeFilter == '12' ? 'selected' : ''; ?>>Grade 12</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-select">
                            <option value="">All Types</option>
                            <option value="Core" <?php echo $categoryFilter == 'Core' ? 'selected' : ''; ?>>Core</option>
                            <option value="Applied" <?php echo $categoryFilter == 'Applied' ? 'selected' : ''; ?>>Applied</option>
                            <option value="Specialized" <?php echo $categoryFilter == 'Specialized' ? 'selected' : ''; ?>>Specialized</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="subject-add.php" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle me-1"></i> Add Subject
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Subjects Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-book-fill me-2"></i>
                    All Subjects (<?php echo count($subjects); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Subject Name</th>
                                <th>Grade</th>
                                <th>Track</th>
                                <th>Strand</th>
                                <th>Semester</th>
                                <th>Category</th>
                                <th>Units</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No subjects found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['grade_level']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['track'] ?? 'All'); ?></td>
                                        <td><?php echo htmlspecialchars($subject['strand'] ?? 'All'); ?></td>
                                        <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                                        <td>
                                            <?php
                                            $categoryColors = [
                                                'Core' => 'primary',
                                                'Applied' => 'success',
                                                'Specialized' => 'warning'
                                            ];
                                            $color = $categoryColors[$subject['subject_type']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars($subject['subject_type']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($subject['units']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="subject-edit.php?id=<?php echo $subject['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $subject['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this subject?')"
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
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
