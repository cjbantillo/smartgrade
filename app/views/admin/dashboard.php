<?php
/**
 * Admin Dashboard
 * ICT Coordinator main dashboard
 */

session_start();

// Include configuration and helpers
require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';

// Require admin role
requireRole('admin');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

try {
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Total students
    $stmt = $db->query("SELECT COUNT(*) as total FROM students WHERE is_archived = 0");
    $stats['total_students'] = $stmt->fetch()['total'];
    
    // Total teachers
    $stmt = $db->query("SELECT COUNT(*) as total FROM teachers");
    $stats['total_teachers'] = $stmt->fetch()['total'];
    
    // Total subjects
    $stmt = $db->query("SELECT COUNT(*) as total FROM subjects WHERE is_active = 1");
    $stats['total_subjects'] = $stmt->fetch()['total'];
    
    // Active school year
    $stmt = $db->query("SELECT year_code FROM school_years WHERE is_active = 1 LIMIT 1");
    $activeYear = $stmt->fetch();
    $stats['active_year'] = $activeYear ? $activeYear['year_code'] : 'No active year';
    
    // Recent audit logs
    $stmt = $db->query("
        SELECT al.*, u.first_name, u.last_name, u.role 
        FROM audit_logs al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $recentLogs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats = [
        'total_users' => 0,
        'total_students' => 0,
        'total_teachers' => 0,
        'total_subjects' => 0,
        'active_year' => 'Error loading'
    ];
    $recentLogs = [];
}

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: #f8f9fa;
        }
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
        .sidebar-menu {
            padding: 1rem 0;
        }
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
        .sidebar-menu a i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
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
            <a href="dashboard.php" class="active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="users.php">
                <i class="bi bi-people-fill"></i> Users
            </a>
            <a href="students.php">
                <i class="bi bi-person-badge"></i> Students
            </a>
            <a href="teachers.php">
                <i class="bi bi-person-workspace"></i> Teachers
            </a>
            <a href="subjects.php">
                <i class="bi bi-book-fill"></i> Subjects
            </a>
            <a href="school-years.php">
                <i class="bi bi-calendar3"></i> School Years
            </a>
            <a href="audit-logs.php">
                <i class="bi bi-journal-text"></i> Audit Logs
            </a>
            <a href="archived-students.php">
                <i class="bi bi-archive-fill"></i> Archived Students
            </a>
            <a href="settings.php">
                <i class="bi bi-gear-fill"></i> System Settings
            </a>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <a href="../../../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <div>
                    <span class="me-3">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php echo $stats['active_year']; ?>
                    </span>
                    <span class="badge bg-primary">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo getCurrentUserName(); ?>
                    </span>
                </div>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Students</h6>
                            <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Teachers</h6>
                            <h3 class="mb-0"><?php echo $stats['total_teachers']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Subjects</h6>
                            <h3 class="mb-0"><?php echo $stats['total_subjects']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Users</h6>
                            <h3 class="mb-0"><?php echo $stats['total_users']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentLogs)): ?>
                            <p class="text-muted">No recent activity</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Action</th>
                                            <th>Table</th>
                                            <th>IP Address</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentLogs as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $log['role'] === 'admin' ? 'danger' : ($log['role'] === 'teacher' ? 'success' : 'primary'); ?>">
                                                        <?php echo ucfirst($log['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                                <td><?php echo formatDateTime($log['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
