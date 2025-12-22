<?php
/**
 * View Teacher - Admin
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

// Get teacher ID
$teacherId = getParam('id');
if (!$teacherId) {
    setFlashMessage('Invalid teacher ID', 'danger');
    header('Location: teachers.php');
    exit;
}

// Get teacher data with user info
$stmt = $db->prepare("SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.middle_name, 
                      u.is_active, u.created_at as user_created_at,
                      CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name
                      FROM teachers t
                      JOIN users u ON t.user_id = u.id
                      WHERE t.id = ?");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    setFlashMessage('Teacher not found', 'danger');
    header('Location: teachers.php');
    exit;
}

// Get assigned subjects (if teacher_subjects table exists)
$subjects = [];
try {
    $stmt = $db->prepare("SELECT s.subject_code, s.subject_name, s.grade_level, s.semester, s.track, s.strand
                          FROM teacher_subjects ts
                          JOIN subjects s ON ts.subject_id = s.id
                          WHERE ts.teacher_id = ?
                          ORDER BY s.grade_level, s.semester, s.subject_name");
    $stmt->execute([$teacherId]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet, ignore
    $subjects = [];
}

$pageTitle = 'View Teacher';
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
        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .info-value {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
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
                <div>
                    <a href="teacher-edit.php?id=<?php echo $teacherId; ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="teachers.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Teachers
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Teacher Profile Card -->
        <div class="card shadow-sm mb-4">
            <div class="profile-header text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                </div>
                <h3 class="mb-1"><?php echo htmlspecialchars($teacher['full_name']); ?></h3>
                <p class="mb-2">
                    <i class="bi bi-person-badge me-2"></i>
                    Employee #: <?php echo htmlspecialchars($teacher['employee_number']); ?>
                </p>
                <div>
                    <?php if ($teacher['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body p-4">
                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-md-6">
                        <h5 class="mb-3">
                            <i class="bi bi-person-fill me-2"></i>
                            Personal Information
                        </h5>
                        
                        <div class="info-label">Full Name</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($teacher['first_name']) . ' ' . 
                                      htmlspecialchars($teacher['middle_name'] ? $teacher['middle_name'] . ' ' : '') . 
                                      htmlspecialchars($teacher['last_name']); ?>
                        </div>
                        
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <?php echo $teacher['email'] ? '<a href="mailto:' . htmlspecialchars($teacher['email']) . '">' . htmlspecialchars($teacher['email']) . '</a>' : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                        
                        <div class="info-label">Contact Number</div>
                        <div class="info-value">
                            <?php echo $teacher['contact_number'] ? htmlspecialchars($teacher['contact_number']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                    
                    <!-- Professional Information -->
                    <div class="col-md-6">
                        <h5 class="mb-3">
                            <i class="bi bi-briefcase-fill me-2"></i>
                            Professional Information
                        </h5>
                        
                        <div class="info-label">Department</div>
                        <div class="info-value">
                            <span class="badge bg-primary" style="font-size: 1rem;">
                                <?php echo htmlspecialchars($teacher['department']); ?>
                            </span>
                        </div>
                        
                        <div class="info-label">Specialization</div>
                        <div class="info-value">
                            <?php echo $teacher['specialization'] ? htmlspecialchars($teacher['specialization']) : '<span class="text-muted">Not specified</span>'; ?>
                        </div>
                        
                        <div class="info-label">Account Created</div>
                        <div class="info-value">
                            <?php echo date('F d, Y', strtotime($teacher['user_created_at'])); ?>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Account Information -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">
                            <i class="bi bi-key-fill me-2"></i>
                            Account Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-label">Username</div>
                                <div class="info-value">
                                    <code><?php echo htmlspecialchars($teacher['username']); ?></code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Account Status</div>
                                <div class="info-value">
                                    <?php if ($teacher['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-x-circle me-1"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assigned Subjects -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-book-fill me-2"></i>
                    Assigned Subjects (<?php echo count($subjects); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($subjects)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">No subjects assigned yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Grade Level</th>
                                    <th>Track</th>
                                    <th>Strand</th>
                                    <th>Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td>Grade <?php echo htmlspecialchars($subject['grade_level']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['track']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['strand'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                Semester <?php echo htmlspecialchars($subject['semester']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
