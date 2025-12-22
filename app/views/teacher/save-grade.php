<?php
/**
 * Save Grade Handler (AJAX)
 */

session_start();

require_once '../../config/config.php';
require_once '../../helpers/security.php';
require_once '../../models/Grade.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is a teacher
if (!isLoggedIn() || !hasRole('teacher')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields (IDs must be present)
$requiredIds = ['student_id', 'subject_id', 'teacher_id', 'school_year_id', 'grading_period_id', 'entered_by'];
foreach ($requiredIds as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Validate max scores
if (empty($input['written_work_total']) || empty($input['performance_task_total']) || empty($input['quarterly_assessment_total'])) {
    echo json_encode(['success' => false, 'message' => 'Please set MAX SCORES first']);
    exit;
}

// Validate that at least one score is provided
if (empty($input['written_work_score']) && empty($input['performance_task_score']) && empty($input['quarterly_assessment_score'])) {
    echo json_encode(['success' => false, 'message' => 'Please enter at least one score']);
    exit;
}

// Set default values for empty scores (0)
$input['written_work_score'] = $input['written_work_score'] !== '' ? $input['written_work_score'] : 0;
$input['performance_task_score'] = $input['performance_task_score'] !== '' ? $input['performance_task_score'] : 0;
$input['quarterly_assessment_score'] = $input['quarterly_assessment_score'] !== '' ? $input['quarterly_assessment_score'] : 0;

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create Grade model instance
    $gradeModel = new Grade($db);
    
    // Prepare data array for Grade model
    $gradeData = [
        'student_id' => $input['student_id'],
        'subject_id' => $input['subject_id'],
        'teacher_id' => $input['teacher_id'],
        'school_year_id' => $input['school_year_id'],
        'grading_period_id' => $input['grading_period_id'],
        'written_work_score' => $input['written_work_score'],
        'written_work_total' => $input['written_work_total'],
        'performance_task_score' => $input['performance_task_score'],
        'performance_task_total' => $input['performance_task_total'],
        'quarterly_assessment_score' => $input['quarterly_assessment_score'],
        'quarterly_assessment_total' => $input['quarterly_assessment_total'],
        'entered_by' => $input['entered_by']
    ];
    
    // Save grade
    $result = $gradeModel->saveGrade($gradeData);
    
    if ($result) {
        // Log to audit trail
        $auditDetails = json_encode([
            'student_id' => $input['student_id'],
            'subject_id' => $input['subject_id'],
            'grading_period_id' => $input['grading_period_id'],
            'quarterly_grade' => 'computed'
        ]);
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['entered_by'],
            'grade_updated',
            'grades',
            $input['student_id'],
            $auditDetails,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Grade saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save grade to database']);
    }
    
} catch (PDOException $e) {
    error_log('Grade Save Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Grade Save Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
