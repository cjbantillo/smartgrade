<?php
/**
 * Logout Page
 * Destroys user session and redirects to login
 */

session_start();

// Include configuration
require_once '../app/config/config.php';
require_once '../app/helpers/security.php';

// Log logout action before destroying session
if (isLoggedIn()) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $logQuery = "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                         VALUES (:user_id, 'user_logout', 'users', :record_id, :ip, :user_agent)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':record_id' => $_SESSION['user_id'],
                ':ip' => getClientIP(),
                ':user_agent' => getUserAgent()
            ]);
        }
    } catch (PDOException $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
