<?php
/**
 * Database Configuration
 * PDO-based MySQL connection for SmartGrade System
 * 
 * Security Features:
 * - Uses PDO with prepared statements
 * - Error handling with try-catch
 * - Connection reuse (singleton pattern)
 */

class Database {
    // Database credentials for XAMPP (default MySQL setup)
    private $host = 'localhost';
    private $db_name = 'smartgrade_db';
    private $username = 'root';
    private $password = ''; // Default XAMPP has no password
    private $charset = 'utf8mb4';
    
    // PDO connection object
    public $conn = null;
    
    /**
     * Get database connection
     * @return PDO connection object or null on failure
     */
    public function getConnection() {
        // If connection already exists, return it
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            // Create DSN (Data Source Name)
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            // PDO options for security and performance
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
                PDO::ATTR_PERSISTENT         => true,                   // Use persistent connections
            ];
            
            // Create PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // Log error (in production, log to file instead of displaying)
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Display user-friendly error in development
            if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
                echo "<strong>Database Connection Error:</strong><br>";
                echo htmlspecialchars($e->getMessage());
                echo "<br><br><strong>Troubleshooting:</strong>";
                echo "<ol>";
                echo "<li>Make sure XAMPP MySQL is running</li>";
                echo "<li>Check if database 'smartgrade_db' exists</li>";
                echo "<li>Verify database credentials in config/database.php</li>";
                echo "<li>Run the smartgrade_db.sql file to create the database</li>";
                echo "</ol>";
                echo "</div>";
            }
            
            return null;
        }
        
        return $this->conn;
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
