<?php
/**
 * Edit School Year - Admin
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

// Get year ID
$yearId = getParam('id');
if (!$yearId) {
    setFlashMessage('Invalid school year ID', 'danger');
    header('Location: school-years.php');
    exit;
}

// Get school year data
$stmt = $db->prepare("SELECT * FROM school_years WHERE id = ?");
$stmt->execute([$yearId]);
$year = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$year) {
    setFlashMessage('School year not found', 'danger');
    header('Location: school-years.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $yearCode = trim($_POST['year_code']);
    $yearStart = $_POST['year_start'];
    $yearEnd = $_POST['year_end'];
    
    $errors = [];
    
    // Validate required fields
    if (empty($yearCode)) $errors[] = 'Year code is required';
    if (empty($yearStart)) $errors[] = 'Start date is required';
    if (empty($yearEnd)) $errors[] = 'End date is required';
    
    // Validate date range
    if (!empty($yearStart) && !empty($yearEnd)) {
        if (strtotime($yearEnd) <= strtotime($yearStart)) {
            $errors[] = 'End date must be after start date';
        }
    }
    
    // Check year code uniqueness (excluding current year)
    if (!empty($yearCode)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM school_years WHERE year_code = ? AND id != ?");
        $stmt->execute([$yearCode, $yearId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'School year already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE school_years SET year_code = ?, year_start = ?, year_end = ? WHERE id = ?");
            $stmt->execute([$yearCode, $yearStart, $yearEnd, $yearId]);
            
            // Audit log
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'update', 'school_years', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $yearId, $_SERVER['REMOTE_ADDR']]);
            
            setFlashMessage('School year updated successfully', 'success');
            header('Location: school-years.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit School Year';
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
                <a href="school-years.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to School Years
                </a>
            </div>
        </nav>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-fill me-2"></i>
                    Edit School Year Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Year Code <span class="text-danger">*</span></label>
                                <input type="text" name="year_code" class="form-control" required value="<?php echo htmlspecialchars($year['year_code']); ?>" placeholder="e.g., 2024-2025">
                                <small class="form-text text-muted">Format: YYYY-YYYY</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-control-plaintext">
                                    <?php if ($year['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                        <small class="d-block mt-1 text-muted">Use "Set Active" button in the list to activate</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="year_start" class="form-control" required value="<?php echo htmlspecialchars($year['year_start']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="year_end" class="form-control" required value="<?php echo htmlspecialchars($year['year_end']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="school-years.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update School Year
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
