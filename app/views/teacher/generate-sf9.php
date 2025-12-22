<?php
/**
 * Generate SF9 (Report Card / Form 137)
 * DepEd School Form 9
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../helpers/utils.php';
require_once '../../helpers/grade_helper.php';

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

// Get selected student
$studentId = getParam('student_id');

if (!$studentId) {
    header('Location: students-list.php');
    exit;
}

// Get student details
$stmt = $db->prepare("
    SELECT 
        s.*,
        u.first_name,
        u.middle_name,
        u.last_name,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name,
        u.email
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students-list.php');
    exit;
}

// Get active school year
$stmt = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1");
$schoolYear = $stmt->fetch();

// Get all grading periods
$stmt = $db->query("SELECT * FROM grading_periods ORDER BY period_number");
$gradingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's grades for this school year
$stmt = $db->prepare("
    SELECT 
        g.*,
        s.subject_code,
        s.subject_name,
        gp.period_name,
        gp.period_number
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN grading_periods gp ON g.grading_period_id = gp.id
    WHERE g.student_id = ? AND g.school_year_id = ?
    ORDER BY s.subject_code, gp.period_number
");
$stmt->execute([$studentId, $schoolYear['id']]);
$gradesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize grades by subject
$gradesBySubject = [];
foreach ($gradesData as $grade) {
    $subjectId = $grade['subject_id'];
    if (!isset($gradesBySubject[$subjectId])) {
        $gradesBySubject[$subjectId] = [
            'subject_code' => $grade['subject_code'],
            'subject_name' => $grade['subject_name'],
            'quarters' => []
        ];
    }
    $gradesBySubject[$subjectId]['quarters'][$grade['period_number']] = $grade['quarterly_grade'];
}

// Calculate final grades
foreach ($gradesBySubject as $subjectId => &$subject) {
    $quarters = $subject['quarters'];
    if (count($quarters) > 0) {
        $subject['final_grade'] = computeFinalGrade($quarters);
        $subject['remarks'] = $subject['final_grade'] >= 75 ? 'PASSED' : 'FAILED';
    } else {
        $subject['final_grade'] = null;
        $subject['remarks'] = '';
    }
}

// Get honors
$stmt = $db->prepare("
    SELECT h.*, gp.period_name
    FROM honors h
    JOIN grading_periods gp ON h.grading_period_id = gp.id
    WHERE h.student_id = ? AND h.school_year_id = ?
    ORDER BY gp.period_number
");
$stmt->execute([$studentId, $schoolYear['id']]);
$honors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Generate SF9';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SF9 - <?php echo htmlspecialchars($student['full_name']); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .sf9-container { box-shadow: none; }
        }
        
        body { background: #f8f9fa; }
        .sf9-container {
            background: white;
            max-width: 21cm;
            margin: 2rem auto;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .sf9-header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .sf9-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        .info-table td {
            padding: 0.25rem 0.5rem;
            border: 1px solid #000;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .grades-table th,
        .grades-table td {
            border: 1px solid #000;
            padding: 0.5rem;
            text-align: center;
        }
        .grades-table th {
            background: #e9ecef;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 2rem;
            padding-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Control Panel -->
    <div class="container no-print py-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="students-list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Students
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-success">
                    <i class="bi bi-printer-fill me-1"></i> Print SF9
                </button>
                <button onclick="exportPDF()" class="btn btn-primary">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
                </button>
            </div>
        </div>
    </div>
    
    <!-- SF9 Document -->
    <div class="sf9-container">
        <!-- Header -->
        <div class="sf9-header">
            <div style="font-size: 0.9rem;">Republic of the Philippines</div>
            <div style="font-size: 1rem; font-weight: bold;">Department of Education</div>
            <div style="font-size: 0.9rem;">Region XI - Davao Region</div>
            <div style="font-size: 0.9rem;">Division of Davao City</div>
            <div style="font-size: 1.1rem; font-weight: bold; margin-top: 0.5rem;">AMPAYON SENIOR HIGH SCHOOL</div>
            <div class="sf9-title">SCHOOL FORM 9 (SF9)</div>
            <div style="font-size: 1rem;">LEARNER'S PERMANENT ACADEMIC RECORD (for SHS)</div>
        </div>
        
        <!-- Student Information -->
        <table class="info-table" style="width: 100%; margin-bottom: 1rem;">
            <tr>
                <td style="width: 15%;"><strong>LRN:</strong></td>
                <td style="width: 35%;"><?php echo htmlspecialchars($student['lrn']); ?></td>
                <td style="width: 15%;"><strong>Name:</strong></td>
                <td style="width: 35%;"><?php echo htmlspecialchars($student['full_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Sex:</strong></td>
                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                <td><strong>Date of Birth:</strong></td>
                <td><?php echo date('F d, Y', strtotime($student['date_of_birth'])); ?></td>
            </tr>
            <tr>
                <td><strong>Grade Level:</strong></td>
                <td><?php echo htmlspecialchars($student['grade_level'] ?? 'Grade 11'); ?></td>
                <td><strong>Section:</strong></td>
                <td><?php echo htmlspecialchars($student['section']); ?></td>
            </tr>
            <tr>
                <td><strong>School Year:</strong></td>
                <td><?php echo htmlspecialchars($schoolYear['school_year']); ?></td>
                <td><strong>Track/Strand:</strong></td>
                <td><?php echo htmlspecialchars($student['strand'] ?? 'STEM'); ?></td>
            </tr>
        </table>
        
        <!-- Grades Table -->
        <table class="grades-table">
            <thead>
                <tr>
                    <th rowspan="2">LEARNING AREAS</th>
                    <th colspan="4">QUARTERLY GRADES</th>
                    <th rowspan="2">FINAL<br>GRADE</th>
                    <th rowspan="2">REMARKS</th>
                </tr>
                <tr>
                    <th>1st</th>
                    <th>2nd</th>
                    <th>3rd</th>
                    <th>4th</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gradesBySubject)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No grades recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gradesBySubject as $subject): ?>
                        <tr>
                            <td style="text-align: left;">
                                <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong><br>
                                <small><?php echo htmlspecialchars($subject['subject_name']); ?></small>
                            </td>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <td>
                                    <?php 
                                    echo isset($subject['quarters'][$i]) 
                                        ? formatGrade($subject['quarters'][$i]) 
                                        : '-'; 
                                    ?>
                                </td>
                            <?php endfor; ?>
                            <td>
                                <strong>
                                    <?php 
                                    echo $subject['final_grade'] 
                                        ? formatGrade($subject['final_grade']) 
                                        : '-'; 
                                    ?>
                                </strong>
                            </td>
                            <td>
                                <strong><?php echo $subject['remarks']; ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- General Average -->
        <?php if (!empty($gradesBySubject)): ?>
            <?php
                $allFinalGrades = array_filter(array_column($gradesBySubject, 'final_grade'));
                $generalAverage = !empty($allFinalGrades) ? array_sum($allFinalGrades) / count($allFinalGrades) : 0;
            ?>
            <div style="text-align: right; font-size: 1.1rem; font-weight: bold; margin-top: 1rem;">
                GENERAL AVERAGE: <?php echo formatGrade($generalAverage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Honors and Awards -->
        <?php if (!empty($honors)): ?>
            <div style="margin-top: 1.5rem;">
                <h6 style="font-weight: bold; border-bottom: 2px solid #000; padding-bottom: 0.5rem;">
                    HONORS AND AWARDS
                </h6>
                <ul style="margin-top: 1rem;">
                    <?php foreach ($honors as $honor): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($honor['honor_type']); ?></strong> - 
                            <?php echo htmlspecialchars($honor['period_name']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <small>Prepared by:</small><br>
                    <strong><?php echo getCurrentUserName(); ?></strong><br>
                    <small>Adviser</small>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <small>Certified True and Correct:</small><br>
                    <strong>_____________________</strong><br>
                    <small>Principal / School Head</small>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <small>Date:</small><br>
                    <strong><?php echo date('F d, Y'); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="margin-top: 2rem; text-align: center; font-size: 0.8rem; color: #666;">
            <p>This is a system-generated document.</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportPDF() {
            const element = document.querySelector('.sf9-container');
            const opt = {
                margin: 0.5,
                filename: 'SF9_<?php echo $student['lrn']; ?>_<?php echo date('Y'); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
