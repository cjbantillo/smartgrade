<?php
/**
 * Student Honors & Awards
 * View all honors and awards received
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';

// Require student role
requireRole('student');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get student ID
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([getCurrentUserId()]);
$student = $stmt->fetch();
$studentId = $student['id'];

// Get all honors and awards
$stmt = $db->prepare("
    SELECT 
        h.*,
        sy.year_code,
        sy.year_start,
        sy.year_end
    FROM honors h
    JOIN school_years sy ON h.school_year_id = sy.id
    WHERE h.student_id = ?
    ORDER BY sy.year_start DESC, h.semester DESC
");
$stmt->execute([$studentId]);
$honors = $stmt->fetchAll();

// Get current general average for this school year
$stmt = $db->prepare("
    SELECT AVG(fg.final_grade) as general_average
    FROM final_grades fg
    JOIN school_years sy ON fg.school_year_id = sy.id
    WHERE fg.student_id = ? AND sy.is_active = 1 AND fg.final_grade IS NOT NULL
");
$stmt->execute([$studentId]);
$currentAvg = $stmt->fetch();
$generalAverage = $currentAvg['general_average'] ?? 0;
$currentHonorStatus = getHonorStatus($generalAverage);

$pageTitle = 'Honors & Awards';
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
            background: linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 52%, #2BFF88 90%);
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
        .honor-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .honor-card.gold {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
        }
        .honor-card.silver {
            background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
        }
        .honor-card.bronze {
            background: linear-gradient(135deg, #cd7f32 0%, #b87333 100%);
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
            <a href="my-grades.php">
                <i class="bi bi-clipboard-data"></i> My Grades
            </a>
            <a href="honors.php" class="active">
                <i class="bi bi-award-fill"></i> Honors & Awards
            </a>
            <a href="profile.php">
                <i class="bi bi-person-circle"></i> My Profile
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
        
        <!-- Current Status -->
        <?php if ($currentHonorStatus): ?>
        <div class="alert alert-success mb-4">
            <h5 class="alert-heading">
                <i class="bi bi-trophy-fill me-2"></i>
                Current Standing: <?php echo $currentHonorStatus; ?>
            </h5>
            <p class="mb-0">
                Your current general average is <strong><?php echo formatGrade($generalAverage); ?></strong>. Keep up the excellent work!
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Honors History -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-star-fill me-2"></i>
                    Academic Honors History
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($honors)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No honors received yet. Maintain a general average of 90 or higher to receive honors recognition!
                        <ul class="mt-2 mb-0">
                            <li><strong>With Honors:</strong> 90.00 - 94.99</li>
                            <li><strong>With High Honors:</strong> 95.00 - 97.99</li>
                            <li><strong>With Highest Honors:</strong> 98.00 - 100.00</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($honors as $honor): ?>
                            <?php
                            $cardClass = '';
                            if (strpos($honor['honor_type'], 'Highest') !== false) {
                                $cardClass = 'gold';
                            } elseif (strpos($honor['honor_type'], 'High') !== false) {
                                $cardClass = 'silver';
                            } else {
                                $cardClass = 'bronze';
                            }
                            ?>
                            <div class="col-md-6">
                                <div class="honor-card <?php echo $cardClass; ?>">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-award-fill" style="font-size: 3rem; margin-right: 1rem;"></i>
                                        <div>
                                            <h4 class="mb-0"><?php echo htmlspecialchars($honor['honor_type']); ?></h4>
                                            <p class="mb-0 opacity-75">
                                                <?php echo htmlspecialchars($honor['year_code']); ?> - Semester <?php echo $honor['semester']; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <hr style="border-color: rgba(255,255,255,0.3);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="opacity-75">General Average</small>
                                            <h3 class="mb-0"><?php echo formatGrade($honor['general_average']); ?></h3>
                                        </div>
                                        <div class="text-end">
                                            <small class="opacity-75">Awarded</small>
                                            <p class="mb-0"><?php echo date('M d, Y', strtotime($honor['awarded_date'])); ?></p>
                                        </div>
                                    </div>
                                    <?php if ($honor['remarks']): ?>
                                        <div class="mt-3">
                                            <small class="opacity-75">Remarks:</small>
                                            <p class="mb-0"><?php echo htmlspecialchars($honor['remarks']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Honor Requirements -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Honor Requirements
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3">
                            <i class="bi bi-award text-warning" style="font-size: 3rem;"></i>
                            <h5 class="mt-2">With Honors</h5>
                            <p class="text-muted">General Average</p>
                            <h4 class="text-warning">90.00 - 94.99</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3">
                            <i class="bi bi-award-fill text-info" style="font-size: 3rem;"></i>
                            <h5 class="mt-2">With High Honors</h5>
                            <p class="text-muted">General Average</p>
                            <h4 class="text-info">95.00 - 97.99</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3">
                            <i class="bi bi-trophy-fill text-success" style="font-size: 3rem;"></i>
                            <h5 class="mt-2">With Highest Honors</h5>
                            <p class="text-muted">General Average</p>
                            <h4 class="text-success">98.00 - 100.00</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
