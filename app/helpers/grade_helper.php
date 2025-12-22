<?php
/**
 * Grade Computation Helper Functions
 * Implements DepEd K-12 Grading System (DO 8, s. 2015)
 * 
 * Grading Components:
 * - Written Works: 30%
 * - Performance Tasks: 50%
 * - Quarterly Assessment: 20%
 * 
 * Grading Scale: 60-100
 * Passing Grade: 75
 */

/**
 * Compute quarterly grade from components
 * Formula: WW(30%) + PT(50%) + QA(20%)
 * 
 * @param float $wwScore Written Work score
 * @param float $wwTotal Written Work total
 * @param float $ptScore Performance Task score
 * @param float $ptTotal Performance Task total
 * @param float $qaScore Quarterly Assessment score
 * @param float $qaTotal Quarterly Assessment total
 * @return float Quarterly grade (0-100)
 */
function computeQuarterlyGrade($wwScore, $wwTotal, $ptScore, $ptTotal, $qaScore, $qaTotal) {
    // Validate inputs
    if ($wwTotal <= 0 || $ptTotal <= 0 || $qaTotal <= 0) {
        return 0;
    }

    // Weights (DepEd K-12)
    $wwWeight = 30;
    $ptWeight = 50;
    $qaWeight = 20;

    // Percentage scores
    $wwPercent = ($wwScore / $wwTotal) * 100;
    $ptPercent = ($ptScore / $ptTotal) * 100;
    $qaPercent = ($qaScore / $qaTotal) * 100;

    // Weighted scores
    $wwWeighted = $wwPercent * ($wwWeight / 100);
    $ptWeighted = $ptPercent * ($ptWeight / 100);
    $qaWeighted = $qaPercent * ($qaWeight / 100);

    $wwPercent * ($wwWeight - $wwScore) / $wwTotal;

    // Initial Quarterly Grade (0–100)
    $initialGrade = $wwWeighted + $ptWeighted + $qaWeighted;

        return (float)$initialGrade;
}

/**
 * Transmute percentage score to 60-100 grading scale
 * Based on DepEd Transmutation Table
 * 
 * @param float $percentage
 * @return float
 */
function transmute($percentage) {
    // Simplified transmutation formula
    // For more accurate implementation, use full DepEd transmutation table
    if ($percentage >= 96.00) return 100.00;
    if ($percentage >= 95.20) return 99.00;
    if ($percentage >= 94.40) return 98.00;
    if ($percentage >= 93.60) return 97.00;
    if ($percentage >= 92.80) return 96.00;
    if ($percentage >= 92.00) return 95.00;
    if ($percentage >= 91.20) return 94.00;
    if ($percentage >= 90.40) return 93.00;
    if ($percentage >= 89.60) return 92.00;
    if ($percentage >= 88.80) return 91.00;
    if ($percentage >= 88.00) return 90.00;
    if ($percentage >= 87.20) return 89.00;
    if ($percentage >= 86.40) return 88.00;
    if ($percentage >= 85.60) return 87.00;
    if ($percentage >= 84.80) return 86.00;
    if ($percentage >= 84.00) return 85.00;
    if ($percentage >= 83.20) return 84.00;
    if ($percentage >= 82.40) return 83.00;
    if ($percentage >= 81.60) return 82.00;
    if ($percentage >= 80.80) return 81.00;
    if ($percentage >= 80.00) return 80.00;
    
    // For scores below 80%, linear interpolation
    if ($percentage >= 60.00) {
        return 60 + (($percentage - 60) / 20) * 15;  // Maps 60-80% to 60-75
    }
    
    return 60.00; // Minimum grade
}

/**
 * Compute final grade (average of quarters)
 * @param array $quarterlyGrades Array of quarterly grades
 * @return float
 */
function computeFinalGrade($quarterlyGrades) {
    $quarterlyGrades = array_filter($quarterlyGrades, function($grade) {
        return $grade !== null && $grade > 0;
    });
    
    if (empty($quarterlyGrades)) {
        return 0;
    }
    
    $sum = array_sum($quarterlyGrades);
    $count = count($quarterlyGrades);
    
    return (float)($sum / $count);
}

/**
 * Compute general average across all subjects
 * @param array $finalGrades Array of final grades
 * @return float
 */
function computeGeneralAverage($finalGrades) {
    $finalGrades = array_filter($finalGrades, function($grade) {
        return $grade !== null && $grade > 0;
    });
    
    if (empty($finalGrades)) {
        return 0;
    }
    
    return round(array_sum($finalGrades) / count($finalGrades), 2);
}

/**
 * Get grade remarks
 * @param float $grade
 * @param int $passingGrade
 * @return string
 */
function getGradeRemarks($grade, $passingGrade = 75) {
    if ($grade === 0 || $grade === null) {
        return 'NO GRADE';
    }
    
    if ($grade >= $passingGrade) {
        return 'PASSED';
    }
    
    return 'FAILED';
}

/**
 * Determine honor status based on general average
 * @param float $generalAverage
 * @return string|null
 */
function getHonorStatus($generalAverage) {
    if ($generalAverage >= 98) {
        return 'With Highest Honors';
    } elseif ($generalAverage >= 95) {
        return 'With High Honors';
    } elseif ($generalAverage >= 90) {
        return 'With Honors';
    }
    
    return null;
}

/**
 * Validate grade input (must be between 60-100)
 * @param float $grade
 * @return bool
 */
function isValidGrade($grade) {
    return $grade >= 60 && $grade <= 100;
}

/**
 * Format grade for display
 * @param float $grade
 * @param int $decimals
 * @return string
 */
function formatGrade($grade, $decimals = 2) {
    if ($grade === 0 || $grade === null) {
        return '-';
    }
    return number_format($grade, $decimals);
}

/**
 * Get grade color class based on performance
 * @param float $grade
 * @return string Bootstrap color class
 */
function getGradeColorClass($grade) {
    if ($grade >= 90) return 'text-success';  // Green for excellent
    if ($grade >= 85) return 'text-primary';  // Blue for very good
    if ($grade >= 80) return 'text-info';     // Cyan for good
    if ($grade >= 75) return 'text-warning';  // Yellow for passing
    return 'text-danger';                     // Red for failing
}

/**
 * Check if grade is passing
 * @param float $grade
 * @param int $passingGrade
 * @return bool
 */
function isPassing($grade, $passingGrade = 75) {
    return $grade >= $passingGrade;
}
