<?php
/**
 * Teachers Management - Admin
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

// Handle delete teacher
if (isset($_GET['delete'])) {
    $teacherId = $_GET['delete'];
    try {
        $db->beginTransaction();
        
        // Get user_id
        $stmt = $db->prepare("SELECT user_id FROM teachers WHERE id = ?");
        $stmt->execute([$teacherId]);
        $teacher = $stmt->fetch();
        
        if ($teacher) {
            $stmt = $db->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->execute([$teacherId]);
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$teacher['user_id']]);
        }
        
        $db->commit();
        setFlashMessage('Teacher deleted successfully', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage('Error deleting teacher: ' . $e->getMessage(), 'danger');
    }
    header('Location: teachers.php');
    exit;
}

// Get all teachers
$search = getParam('search', '');
$deptFilter = getParam('department', '');

$sql = "SELECT t.*, u.email, u.is_active,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (t.employee_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($deptFilter) {
    $sql .= " AND t.department = ?";
    $params[] = $deptFilter;
}

$sql .= " ORDER BY u.last_name, u.first_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments
$departments = $db->query("SELECT DISTINCT department FROM teachers WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Teachers Management';
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
            <a href="teachers.php" class="active">
                <i class="bi bi-person-badge-fill"></i> Teachers
            </a>
            <a href="subjects.php">
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
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or employee number..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" <?php echo $deptFilter == $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="teacher-add.php" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle me-1"></i> Add Teacher
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Teachers Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-person-badge-fill me-2"></i>
                    All Teachers (<?php echo count($teachers); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Employee #</th>
                                <th>Teacher Name</th>
                                <th>Department</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No teachers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($teacher['employee_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($teacher['department'] ?? '-'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($teacher['specialization'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['contact_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td>
                                            <?php if ($teacher['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="teacher-view.php?id=<?php echo $teacher['id']; ?>" 
                                                   class="btn btn-outline-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="teacher-edit.php?id=<?php echo $teacher['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $teacher['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this teacher?')"
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
