<?php
/**
 * Main Entry Point
 * Redirects to login if not authenticated, otherwise to appropriate dashboard
 */

session_start();

// Define base URL
define('BASE_URL', 'http://localhost/smartgrade-v/');

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../app/views/admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: ../app/views/teacher/dashboard.php');
            break;
        case 'student':
            header('Location: ../app/views/student/dashboard.php');
            break;
        default:
            header('Location: ../auth/login.php');
    }
} else {
    // Not logged in - redirect to login page
    header('Location: ../auth/login.php');
}
exit();
