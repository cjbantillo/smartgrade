<?php
/**
 * Grade Model
 * Handles all grade-related database operations
 */

class Grade {
    private $conn;
    private $table_name = "grades";
    
    // Grade properties
    public $id;
    public $student_id;
    public $subject_id;
    public $teacher_id;
    public $school_year_id;
    public $grading_period_id;
    public $written_work_score;
    public $written_work_total;
    public $performance_task_score;
    public $performance_task_total;
    public $quarterly_assessment_score;
    public $quarterly_assessment_total;
    public $quarterly_grade;
    public $remarks;
    public $entered_by;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all grades for a specific class (subject + section)
     */
    public function getGradesByClass($teacher_id, $subject_id, $section, $grading_period_id) {
        $query = "SELECT 
                    g.*,
                    s.id as student_id,
                    s.student_number,
                    s.lrn,
                    CONCAT(u.last_name, ', ', u.first_name, ' ', COALESCE(u.middle_name, '')) as student_name
                FROM students s
                INNER JOIN users u ON s.user_id = u.id
                LEFT JOIN " . $this->table_name . " g ON 
                    s.id = g.student_id AND 
                    g.subject_id = :subject_id AND 
                    g.grading_period_id = :grading_period_id
                WHERE s.section = :section 
                    AND s.is_archived = 0
                ORDER BY u.last_name, u.first_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':section', $section);
        $stmt->bindParam(':grading_period_id', $grading_period_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Save or update grade
     */
    public function saveGrade($data) {
        // Check if grade already exists
        $checkQuery = "SELECT id FROM " . $this->table_name . " 
                      WHERE student_id = :student_id 
                      AND subject_id = :subject_id 
                      AND grading_period_id = :grading_period_id";
        
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->bindParam(':subject_id', $data['subject_id']);
        $stmt->bindParam(':grading_period_id', $data['grading_period_id']);
        $stmt->execute();
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Compute quarterly grade
        require_once __DIR__ . '/../helpers/grade_helper.php';
        $quarterly_grade = computeQuarterlyGrade(
            $data['written_work_score'],
            $data['written_work_total'],
            $data['performance_task_score'],
            $data['performance_task_total'],
            $data['quarterly_assessment_score'],
            $data['quarterly_assessment_total']
        );
        
        if ($existing) {
            // Update existing grade
            return $this->updateGrade($existing['id'], $data, $quarterly_grade);
        } else {
            // Insert new grade
            return $this->insertGrade($data, $quarterly_grade);
        }
    }
    
    /**
     * Insert new grade
     */
    private function insertGrade($data, $quarterly_grade) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (student_id, subject_id, teacher_id, school_year_id, grading_period_id,
                   written_work_score, written_work_total, performance_task_score, performance_task_total,
                   quarterly_assessment_score, quarterly_assessment_total, quarterly_grade, 
                   remarks, entered_by)
                  VALUES 
                  (:student_id, :subject_id, :teacher_id, :school_year_id, :grading_period_id,
                   :ww_score, :ww_total, :pt_score, :pt_total,
                   :qa_score, :qa_total, :quarterly_grade,
                   :remarks, :entered_by)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->bindParam(':subject_id', $data['subject_id']);
        $stmt->bindParam(':teacher_id', $data['teacher_id']);
        $stmt->bindParam(':school_year_id', $data['school_year_id']);
        $stmt->bindParam(':grading_period_id', $data['grading_period_id']);
        $stmt->bindParam(':ww_score', $data['written_work_score']);
        $stmt->bindParam(':ww_total', $data['written_work_total']);
        $stmt->bindParam(':pt_score', $data['performance_task_score']);
        $stmt->bindParam(':pt_total', $data['performance_task_total']);
        $stmt->bindParam(':qa_score', $data['quarterly_assessment_score']);
        $stmt->bindParam(':qa_total', $data['quarterly_assessment_total']);
        $stmt->bindParam(':quarterly_grade', $quarterly_grade);
        $stmt->bindParam(':remarks', $data['remarks']);
        $stmt->bindParam(':entered_by', $data['entered_by']);
        
        return $stmt->execute();
    }
    
    /**
     * Update existing grade
     */
    private function updateGrade($grade_id, $data, $quarterly_grade) {
        $query = "UPDATE " . $this->table_name . " 
                  SET written_work_score = :ww_score,
                      written_work_total = :ww_total,
                      performance_task_score = :pt_score,
                      performance_task_total = :pt_total,
                      quarterly_assessment_score = :qa_score,
                      quarterly_assessment_total = :qa_total,
                      quarterly_grade = :quarterly_grade,
                      remarks = :remarks
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':ww_score', $data['written_work_score']);
        $stmt->bindParam(':ww_total', $data['written_work_total']);
        $stmt->bindParam(':pt_score', $data['performance_task_score']);
        $stmt->bindParam(':pt_total', $data['performance_task_total']);
        $stmt->bindParam(':qa_score', $data['quarterly_assessment_score']);
        $stmt->bindParam(':qa_total', $data['quarterly_assessment_total']);
        $stmt->bindParam(':quarterly_grade', $quarterly_grade);
        $stmt->bindParam(':remarks', $data['remarks']);
        $stmt->bindParam(':id', $grade_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get student's grades for a subject across all quarters
     */
    public function getStudentGrades($student_id, $subject_id, $school_year_id) {
        $query = "SELECT g.*, gp.period_name, gp.period_number
                  FROM " . $this->table_name . " g
                  JOIN grading_periods gp ON g.grading_period_id = gp.id
                  WHERE g.student_id = :student_id 
                    AND g.subject_id = :subject_id 
                    AND g.school_year_id = :school_year_id
                  ORDER BY gp.period_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':school_year_id', $school_year_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete grade
     */
    public function deleteGrade($grade_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $grade_id);
        return $stmt->execute();
    }
    
    /**
     * Get grades summary for a student
     */
    public function getStudentGradesSummary($student_id, $school_year_id) {
        $query = "SELECT 
                    s.subject_code,
                    s.subject_name,
                    g.quarterly_grade,
                    gp.period_name,
                    gp.period_number
                  FROM " . $this->table_name . " g
                  JOIN subjects s ON g.subject_id = s.id
                  JOIN grading_periods gp ON g.grading_period_id = gp.id
                  WHERE g.student_id = :student_id 
                    AND g.school_year_id = :school_year_id
                  ORDER BY s.subject_name, gp.period_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':school_year_id', $school_year_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
