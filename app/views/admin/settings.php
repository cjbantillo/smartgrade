<?php
/**
 * Settings - Admin
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

// Get system statistics
$stats = [];

// Total counts
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
$stats['active_users'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM students WHERE is_archived = 0");
$stats['active_students'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM teachers");
$stats['active_teachers'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM subjects");
$stats['total_subjects'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM school_years");
$stats['total_years'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM audit_logs");
$stats['total_logs'] = $stmt->fetchColumn();

// Get active school year
$stmt = $db->query("SELECT year_code FROM school_years WHERE is_active = 1 LIMIT 1");
$stats['active_year'] = $stmt->fetchColumn() ?: 'None';

// Get active grading period
$stmt = $db->query("SELECT period_name FROM grading_periods WHERE is_active = 1 LIMIT 1");
$stats['active_period'] = $stmt->fetchColumn() ?: 'None';

// Database size
try {
    $stmt = $db->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
        FROM information_schema.TABLES 
        WHERE table_schema = DATABASE()");
    $stats['db_size'] = $stmt->fetchColumn() . ' MB';
} catch (Exception $e) {
    $stats['db_size'] = 'N/A';
}

$pageTitle = 'System Settings';
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
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card.primary { border-color: #667eea; }
        .stat-card.success { border-color: #28a745; }
        .stat-card.info { border-color: #17a2b8; }
        .stat-card.warning { border-color: #ffc107; }
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
            <a href="school-years.php">
                <i class="bi bi-calendar-range"></i> School Years
            </a>
            <a href="audit-logs.php">
                <i class="bi bi-clock-history"></i> Audit Logs
            </a>
            <a href="settings.php" class="active">
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
        
        <!-- System Statistics -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="bi bi-bar-chart-fill me-2"></i>System Statistics</h5>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Users</h6>
                                <h3 class="mb-0"><?php echo $stats['active_users']; ?></h3>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Students</h6>
                                <h3 class="mb-0"><?php echo $stats['active_students']; ?></h3>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-mortarboard-fill" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Teachers</h6>
                                <h3 class="mb-0"><?php echo $stats['active_teachers']; ?></h3>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-person-badge-fill" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Subjects</h6>
                                <h3 class="mb-0"><?php echo $stats['total_subjects']; ?></h3>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-book-fill" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Application Name</th>
                                <td><?php echo APP_NAME; ?></td>
                            </tr>
                            <tr>
                                <th>Active School Year</th>
                                <td><strong><?php echo htmlspecialchars($stats['active_year']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Active Grading Period</th>
                                <td><strong><?php echo htmlspecialchars($stats['active_period']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Total School Years</th>
                                <td><?php echo $stats['total_years']; ?></td>
                            </tr>
                            <tr>
                                <th>Database Size</th>
                                <td><?php echo $stats['db_size']; ?></td>
                            </tr>
                            <tr>
                                <th>Total Audit Logs</th>
                                <td><?php echo number_format($stats['total_logs']); ?> records</td>
                            </tr>
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <th>Server Software</th>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-tools me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="school-years.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-calendar-range me-2"></i>
                                Manage School Years
                                <small class="text-muted d-block">Set active year and grading periods</small>
                            </a>
                            <a href="users.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-people me-2"></i>
                                Manage Users
                                <small class="text-muted d-block">Add, edit, or remove user accounts</small>
                            </a>
                            <a href="subjects.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-book me-2"></i>
                                Manage Subjects
                                <small class="text-muted d-block">Configure subject offerings</small>
                            </a>
                            <a href="audit-logs.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-clock-history me-2"></i>
                                View Audit Logs
                                <small class="text-muted d-block">Monitor system activity</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grade Computation Settings -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-calculator me-2"></i>
                            Grade Computation Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>DepEd K-12 Grading System</h6>
                            <p class="mb-2">The system uses the official DepEd formula:</p>
                            <ul class="mb-2">
                                <li><strong>Written Work (WW):</strong> 30%</li>
                                <li><strong>Performance Task (PT):</strong> 50%</li>
                                <li><strong>Quarterly Assessment (QA):</strong> 20%</li>
                            </ul>
                            <p class="mb-0"><strong>Formula:</strong> Initial Grade = (WW × 0.30) + (PT × 0.50) + (QA × 0.20)</p>
                            <p class="mb-0"><strong>Transmutation:</strong> Converts initial grade to 60-100 scale</p>
                        </div>
                        
                        <h6 class="mt-3">Honors Recognition Thresholds:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6 class="text-warning">With Honors</h6>
                                        <p class="mb-0">Final Grade: <strong>90 - 94.99</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-primary">With High Honors</h6>
                                        <p class="mb-0">Final Grade: <strong>95 - 97.99</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6 class="text-success">With Highest Honors</h6>
                                        <p class="mb-0">Final Grade: <strong>98 - 100</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Maintenance -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            System Maintenance
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Important:</strong> These actions should be performed with caution.</p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-warning" onclick="alert('Database backup functionality would be implemented here')">
                                <i class="bi bi-download me-1"></i> Backup Database
                            </button>
                            <button class="btn btn-outline-danger" onclick="if(confirm('Are you sure? This will clear old log entries.')) alert('Clear old logs functionality would be implemented here')">
                                <i class="bi bi-trash me-1"></i> Clear Old Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
