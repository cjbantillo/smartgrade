<?php
/**
 * School Years Management - Admin
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

// Handle set active
if (isset($_GET['set_active'])) {
    $yearId = $_GET['set_active'];
    try {
        $db->beginTransaction();
        
        // Deactivate all
        $db->query("UPDATE school_years SET is_active = 0");
        
        // Activate selected
        $stmt = $db->prepare("UPDATE school_years SET is_active = 1 WHERE id = ?");
        $stmt->execute([$yearId]);
        
        // Deactivate all grading periods
        $db->query("UPDATE grading_periods SET is_active = 0");
        
        $db->commit();
        setFlashMessage('School year activated successfully', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    header('Location: school-years.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $yearId = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM school_years WHERE id = ?");
        $stmt->execute([$yearId]);
        setFlashMessage('School year deleted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error deleting: ' . $e->getMessage(), 'danger');
    }
    header('Location: school-years.php');
    exit;
}

// Get all school years
$stmt = $db->query("SELECT * FROM school_years ORDER BY year_start DESC");
$schoolYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'School Years Management';
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
            <a href="subjects.php">
                <i class="bi bi-book-fill"></i> Subjects
            </a>
            <a href="school-years.php" class="active">
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
                <a href="school-year-add.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i> Add School Year
                </a>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- School Years Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-range me-2"></i>
                    All School Years (<?php echo count($schoolYears); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>School Year</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schoolYears)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No school years found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schoolYears as $year): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($year['year_code']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($year['year_start'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($year['year_end'])); ?></td>
                                        <td>
                                            <?php if ($year['is_active']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($year['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!$year['is_active']): ?>
                                                    <a href="?set_active=<?php echo $year['id']; ?>" 
                                                       class="btn btn-outline-success" 
                                                       onclick="return confirm('Set this as active school year?')"
                                                       title="Set Active">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="grading-periods.php?year_id=<?php echo $year['id']; ?>" 
                                                   class="btn btn-outline-info" title="Grading Periods">
                                                    <i class="bi bi-calendar2-week"></i>
                                                </a>
                                                <a href="school-year-edit.php?id=<?php echo $year['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (!$year['is_active']): ?>
                                                    <a href="?delete=<?php echo $year['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Delete this school year?')"
                                                       title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
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
