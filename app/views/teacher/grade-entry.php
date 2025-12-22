<?php
/**
 * Grade Entry Interface
 * Teachers can enter and update student grades
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';
require_once '../../models/Grade.php';

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

// Get active grading period
$stmt = $db->query("SELECT id, period_name FROM grading_periods WHERE is_active = 1 LIMIT 1");
$activePeriod = $stmt->fetch();
$gradingPeriodId = $activePeriod['id'];
$periodName = $activePeriod['period_name'];

// Get teacher's assigned classes
$stmt = $db->prepare("
    SELECT DISTINCT 
        ca.subject_id,
        s.subject_code,
        s.subject_name,
        ca.section
    FROM class_assignments ca
    JOIN subjects s ON ca.subject_id = s.id
    WHERE ca.teacher_id = ? AND ca.school_year_id = ?
    ORDER BY ca.section, s.subject_name
");
$stmt->execute([$teacherId, $schoolYearId]);
$assignedClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected class details
$selectedSubjectId = getParam('subject_id');
$selectedSection = getParam('section');

// Handle combined subject_id format from dropdown
if (isset($_GET['subject_id']) && strpos($_GET['subject_id'], '_') !== false) {
    list($selectedSubjectId, $selectedSection) = explode('_', $_GET['subject_id'], 2);
}

$students = [];
$selectedSubject = null;

if ($selectedSubjectId && $selectedSection) {
    // Get subject details
    $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$selectedSubjectId]);
    $selectedSubject = $stmt->fetch();
    
    // Get students with their grades
    $gradeModel = new Grade($db);
    $students = $gradeModel->getGradesByClass($teacherId, $selectedSubjectId, $selectedSection, $gradingPeriodId);
}

$pageTitle = 'Grade Entry';
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
        .grade-input {
            width: 80px;
            padding: 0.25rem;
            text-align: center;
        }
        .computed-grade {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0.5rem;
            border-radius: 5px;
        }
        .save-btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
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
            <a href="grade-entry.php" class="active">
                <i class="bi bi-pencil-square"></i> Enter Grades
            </a>
            <a href="students-list.php">
                <i class="bi bi-people-fill"></i> Students
            </a>
            <a href="students-list.php" title="Select student to generate SF9">
                <i class="bi bi-file-earmark-text"></i> Generate SF9
            </a>
            <a href="students-list.php" title="Select student to generate SF10">
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
                <h4 class="mb-0"><?php echo $pageTitle; ?> - <?php echo $periodName; ?></h4>
                <span class="badge bg-success">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo getCurrentUserName(); ?>
                </span>
            </div>
        </nav>
        
        <?php echo displayFlashMessage(); ?>
        
        <!-- Class Selection -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-filter-circle me-2"></i>Select Class</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Subject:</label>
                            <select name="subject_id" class="form-select" required onchange="this.form.submit()">
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($assignedClasses as $class): ?>
                                    <option value="<?php echo $class['subject_id']; ?>_<?php echo $class['section']; ?>"
                                            <?php echo ($selectedSubjectId == $class['subject_id'] && $selectedSection == $class['section']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['subject_code']) . ' - ' . htmlspecialchars($class['subject_name']) . ' (Section ' . htmlspecialchars($class['section']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selectedSubject && !empty($students)): ?>
        <!-- Grade Entry Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2"></i>
                    <?php echo htmlspecialchars($selectedSubject['subject_code']); ?> - 
                    <?php echo htmlspecialchars($selectedSubject['subject_name']); ?> 
                    (Section <?php echo htmlspecialchars($selectedSection); ?>)
                </h5>
                <small class="text-muted">Grading Period: <?php echo $periodName; ?></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-success">
                            <tr>
                                <th rowspan="2" class="align-middle">No.</th>
                                <th rowspan="2" class="align-middle">Student Name</th>
                                <th rowspan="2" class="align-middle">LRN</th>
                                <th class="text-center">Written Work (30%)</th>
                                <th class="text-center">Performance Task (50%)</th>
                                <th class="text-center">Quarterly Assessment (20%)</th>
                                <th rowspan="2" class="align-middle text-center">Grade</th>
                                <th rowspan="2" class="align-middle text-center">Action</th>
                            </tr>
                            <tr>
                                <th class="text-center bg-warning">
                                    <small>MAX SCORE:</small>
                                    <input type="number" step="0.01" class="form-control grade-input" 
                                           id="ww_max_score" 
                                           value="<?php echo $students[0]['written_work_total'] ?? 100; ?>"
                                           onchange="recalculateAllGrades()"
                                           placeholder="e.g. 100">
                                </th>
                                <th class="text-center bg-warning">
                                    <small>MAX SCORE:</small>
                                    <input type="number" step="0.01" class="form-control grade-input"
                                           id="pt_max_score"
                                           value="<?php echo $students[0]['performance_task_total'] ?? 100; ?>"
                                           onchange="recalculateAllGrades()"
                                           placeholder="e.g. 100">
                                </th>
                                <th class="text-center bg-warning">
                                    <small>MAX SCORE:</small>
                                    <input type="number" step="0.01" class="form-control grade-input"
                                           id="qa_max_score"
                                           value="<?php echo $students[0]['quarterly_assessment_total'] ?? 100; ?>"
                                           onchange="recalculateAllGrades()"
                                           placeholder="e.g. 100">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <tr id="row-<?php echo $student['student_id']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                <td>
                                    <input type="number" step="0.01" class="form-control grade-input" 
                                           id="ww_score_<?php echo $student['student_id']; ?>"
                                           value="<?php echo $student['written_work_score'] ?? ''; ?>"
                                           onchange="calculateGrade(<?php echo $student['student_id']; ?>)"
                                           placeholder="Score">
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control grade-input"
                                           id="pt_score_<?php echo $student['student_id']; ?>"
                                           value="<?php echo $student['performance_task_score'] ?? ''; ?>"
                                           onchange="calculateGrade(<?php echo $student['student_id']; ?>)"
                                           placeholder="Score">
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control grade-input"
                                           id="qa_score_<?php echo $student['student_id']; ?>"
                                           value="<?php echo $student['quarterly_assessment_score'] ?? ''; ?>"
                                           onchange="calculateGrade(<?php echo $student['student_id']; ?>)"
                                           placeholder="Score">
                                </td>
                                <td class="text-center">
                                    <span class="computed-grade" id="computed_grade_<?php echo $student['student_id']; ?>">
                                        <?php echo $student['quarterly_grade'] ? formatGrade($student['quarterly_grade']) : '-'; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary save-btn" 
                                                onclick="saveGrade(<?php echo $student['student_id']; ?>)">
                                            <i class="bi bi-save"></i> Save
                                        </button>
                                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="generateReportCard(<?php echo $student['student_id']; ?>); return false;">
                                                    <i class="bi bi-file-earmark-text me-2"></i>Report Card
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="generateSF10(<?php echo $student['student_id']; ?>); return false;">
                                                    <i class="bi bi-file-earmark-pdf me-2"></i>SF10
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-success" onclick="saveAllGrades()">
                        <i class="bi bi-save-fill me-1"></i> Save All Grades
                    </button>
                </div>
            </div>
        </div>
        <?php elseif ($selectedSubject): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No students found in this class.
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateGrade(studentId) {
            const wwScore = parseFloat(document.getElementById(`ww_score_${studentId}`).value) || 0;
            const ptScore = parseFloat(document.getElementById(`pt_score_${studentId}`).value) || 0;
            const qaScore = parseFloat(document.getElementById(`qa_score_${studentId}`).value) || 0;
            
            // Get MAX SCORES from header row
            const wwTotal = parseFloat(document.getElementById('ww_max_score').value) || 0;
            const ptTotal = parseFloat(document.getElementById('pt_max_score').value) || 0;
            const qaTotal = parseFloat(document.getElementById('qa_max_score').value) || 0;
            
            if (wwTotal > 0 && ptTotal > 0 && qaTotal > 0) {
                // Calculate percentages
                const wwPercent = (wwScore / wwTotal) * 100;
                const ptPercent = (ptScore / ptTotal) * 100;
                const qaPercent = (qaScore / qaTotal) * 100;
                
                // Apply weights (30%, 50%, 20%)
                const rawGrade = (wwPercent * 0.30) + (ptPercent * 0.50) + (qaPercent * 0.20);
                
                // Transmute to 60-100 scale
                let finalGrade;
                if (rawGrade >= 96.5) {
                    finalGrade = 100;
                } else if (rawGrade >= 80) {
                    finalGrade = 75 + ((rawGrade - 80) / 16.5) * 25;
                } else if (rawGrade >= 60) {
                    finalGrade = 60 + ((rawGrade - 60) / 20) * 15;
                } else {
                    finalGrade = 60;
                }
                
                document.getElementById(`computed_grade_${studentId}`).textContent = finalGrade.toFixed(2);
                document.getElementById(`computed_grade_${studentId}`).className = 'computed-grade ' + 
                    (finalGrade >= 90 ? 'bg-success text-white' : 
                     finalGrade >= 85 ? 'bg-primary text-white' :
                     finalGrade >= 80 ? 'bg-info text-white' :
                     finalGrade >= 75 ? 'bg-warning' : 'bg-danger text-white');
            }
        }
        
        function recalculateAllGrades() {
            const students = <?php echo json_encode(array_column($students, 'student_id')); ?>;
            students.forEach(studentId => {
                calculateGrade(studentId);
            });
        }
        
        function saveGrade(studentId) {
            // Get MAX SCORES from header row
            const wwTotal = document.getElementById('ww_max_score').value;
            const ptTotal = document.getElementById('pt_max_score').value;
            const qaTotal = document.getElementById('qa_max_score').value;
            
            if (!wwTotal || !ptTotal || !qaTotal) {
                alert('Please set MAX SCORES first (in the yellow row).');
                return;
            }
            
            const wwScore = document.getElementById(`ww_score_${studentId}`).value;
            const ptScore = document.getElementById(`pt_score_${studentId}`).value;
            const qaScore = document.getElementById(`qa_score_${studentId}`).value;
            
            // Check if at least one score is entered
            if (!wwScore && !ptScore && !qaScore) {
                alert('Please enter at least one score for this student.');
                return;
            }
            
            const data = {
                student_id: studentId,
                subject_id: <?php echo $selectedSubjectId ?? 0; ?>,
                teacher_id: <?php echo $teacherId; ?>,
                school_year_id: <?php echo $schoolYearId; ?>,
                grading_period_id: <?php echo $gradingPeriodId; ?>,
                written_work_score: wwScore || '',
                written_work_total: wwTotal,
                performance_task_score: ptScore || '',
                performance_task_total: ptTotal,
                quarterly_assessment_score: qaScore || '',
                quarterly_assessment_total: qaTotal,
                entered_by: <?php echo getCurrentUserId(); ?>
            };
            
            // Disable button during save
            const btn = document.querySelector(`#row-${studentId} button`);
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
            
            fetch('save-grade.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                btn.disabled = false;
                if (result.success) {
                    // Show success indicator
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Saved';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                    
                    setTimeout(() => {
                        btn.innerHTML = '<i class="bi bi-save"></i> Save';
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-primary');
                    }, 2000);
                } else {
                    btn.innerHTML = '<i class="bi bi-save"></i> Save';
                    alert('Error saving grade: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save"></i> Save';
                console.error('Error:', error);
                alert('Network error: Could not save grade. Please check your connection.');
            });
        }
        
        function saveAllGrades() {
            if (!confirm('Save all grades? This will save grades for all students with entered scores.')) {
                return;
            }
            
            const students = <?php echo json_encode(array_column($students, 'student_id')); ?>;
            let saved = 0;
            let promises = [];
            
            students.forEach(studentId => {
                const wwScore = document.getElementById(`ww_score_${studentId}`).value;
                if (wwScore) { // Only save if at least one score is entered
                    promises.push(
                        new Promise((resolve) => {
                            saveGrade(studentId);
                            setTimeout(resolve, 100); // Small delay between saves
                        })
                    );
                    saved++;
                }
            });
            
            if (saved === 0) {
                alert('No grades to save. Please enter scores first.');
            } else {
                Promise.all(promises).then(() => {
                    setTimeout(() => {
                        alert(`Successfully saved ${saved} grade(s)!`);
                    }, 500);
                });
            }
        }
        
        function generateReportCard(studentId) {
            // Open report card in new window
            window.open(`generate-report-card.php?student_id=${studentId}&subject_id=<?php echo $selectedSubjectId ?? 0; ?>&grading_period_id=<?php echo $gradingPeriodId; ?>`, '_blank');
        }
        
        function generateSF10(studentId) {
            // Open SF10 in new window
            window.open(`generate-sf10.php?student_id=${studentId}`, '_blank');
        }
    </script>
</body>
</html>
