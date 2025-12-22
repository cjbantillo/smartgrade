<?php
/**
 * Grading Periods Management - Admin
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

// Get school year ID
$yearId = getParam('year_id');
if (!$yearId) {
    setFlashMessage('Invalid school year ID', 'danger');
    header('Location: school-years.php');
    exit;
}

// Get school year data
$stmt = $db->prepare("SELECT * FROM school_years WHERE id = ?");
$stmt->execute([$yearId]);
$schoolYear = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schoolYear) {
    setFlashMessage('School year not found', 'danger');
    header('Location: school-years.php');
    exit;
}

// Handle set active period
if (isset($_GET['set_active'])) {
    $periodId = $_GET['set_active'];
    try {
        $db->beginTransaction();
        
        // Deactivate all periods
        $db->query("UPDATE grading_periods SET is_active = 0");
        
        // Activate selected period
        $stmt = $db->prepare("UPDATE grading_periods SET is_active = 1 WHERE id = ?");
        $stmt->execute([$periodId]);
        
        $db->commit();
        setFlashMessage('Grading period activated successfully', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    header('Location: grading-periods.php?year_id=' . $yearId);
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $periodId = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM grading_periods WHERE id = ?");
        $stmt->execute([$periodId]);
        setFlashMessage('Grading period deleted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error deleting: ' . $e->getMessage(), 'danger');
    }
    header('Location: grading-periods.php?year_id=' . $yearId);
    exit;
}

// Handle add period
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_period'])) {
    $periodName = trim($_POST['period_name']);
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $setActive = isset($_POST['set_active']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($periodName)) $errors[] = 'Period name is required';
    if (empty($startDate)) $errors[] = 'Start date is required';
    if (empty($endDate)) $errors[] = 'End date is required';
    
    if (!empty($startDate) && !empty($endDate)) {
        if (strtotime($endDate) <= strtotime($startDate)) {
            $errors[] = 'End date must be after start date';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // If set as active, deactivate all others
            if ($setActive) {
                $db->query("UPDATE grading_periods SET is_active = 0");
            }
            
            // Insert new period
            $stmt = $db->prepare("INSERT INTO grading_periods (school_year_id, period_name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$yearId, $periodName, $startDate, $endDate, $setActive]);
            
            // Audit log
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'create', 'grading_periods', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $db->lastInsertId(), $_SERVER['REMOTE_ADDR']]);
            
            $db->commit();
            setFlashMessage('Grading period added successfully', 'success');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setFlashMessage($error, 'danger');
        }
    }
    
    header('Location: grading-periods.php?year_id=' . $yearId);
    exit;
}

// Get grading periods for this school year
$stmt = $db->prepare("SELECT * FROM grading_periods WHERE school_year_id = ? ORDER BY start_date ASC");
$stmt->execute([$yearId]);
$periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Grading Periods';
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
                <div>
                    <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                    <small class="text-muted">School Year: <?php echo htmlspecialchars($schoolYear['year_name']); ?></small>
                </div>
                <a href="school-years.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to School Years
                </a>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Add Period Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Grading Period
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" name="period_name" class="form-control" placeholder="Period Name (e.g., 1st Quarter)" required>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <div class="form-check">
                                    <input type="checkbox" name="set_active" class="form-check-input" id="setActiveNew">
                                    <label class="form-check-label" for="setActiveNew">
                                        Set Active
                                    </label>
                                </div>
                                <button type="submit" name="add_period" class="btn btn-primary ms-auto">
                                    <i class="bi bi-plus-circle me-1"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Periods Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-calendar2-week me-2"></i>
                    Grading Periods (<?php echo count($periods); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Period Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($periods)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No grading periods found. Add one above.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($period['period_name']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($period['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($period['end_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $start = new DateTime($period['start_date']);
                                            $end = new DateTime($period['end_date']);
                                            $days = $end->diff($start)->days;
                                            echo $days . ' days';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($period['is_active']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!$period['is_active']): ?>
                                                    <a href="?year_id=<?php echo $yearId; ?>&set_active=<?php echo $period['id']; ?>" 
                                                       class="btn btn-outline-success" 
                                                       onclick="return confirm('Set this as active grading period?')"
                                                       title="Set Active">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?year_id=<?php echo $yearId; ?>&delete=<?php echo $period['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Delete this grading period?')"
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
