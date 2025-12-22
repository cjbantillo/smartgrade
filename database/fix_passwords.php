<?php
/**
 * Password Hash Fixer
 * Updates all user passwords to the correct hashes
 */

require_once '../app/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Passwords - SmartGrade</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h1 { color: #667eea; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 SmartGrade Password Fixer</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed!");
    }
    
    echo "<div class='info'>✓ Database connected successfully</div>";
    
    // Generate correct password hashes
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $teacherHash = password_hash('teacher123', PASSWORD_DEFAULT);
    $studentHash = password_hash('student123', PASSWORD_DEFAULT);

    // Update admin password
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$adminHash]);
    echo "<div class='success'>✓ Updated admin password (admin123)</div>";
    
    // Update teacher passwords
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username IN ('jdelacruz', 'msantos')");
    $stmt->execute([$teacherHash]);
    echo "<div class='success'>✓ Updated teacher passwords (teacher123)</div>";
    
    // Update student passwords
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username IN ('2024001', '2024002', '2024003')");
    $stmt->execute([$studentHash]);
    echo "<div class='success'>✓ Updated student passwords (student123)</div>";
    
    // Display current users
    echo "<h2>Current Users:</h2>";
    $stmt = $db->query("SELECT username, role, email, is_active FROM users ORDER BY role, username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' cellspacing='0' style='width:100%; border-collapse: collapse;'>
            <tr style='background: #667eea; color: white;'>
                <th>Username</th>
                <th>Role</th>
                <th>Email</th>
                <th>Status</th>
                <th>Password</th>
            </tr>";
    
    foreach ($users as $user) {
        $status = $user['is_active'] ? '<span style="color:green">✓ Active</span>' : '<span style="color:red">✗ Inactive</span>';
        $password = match($user['role']) {
            'admin' => 'admin123',
            'teacher' => 'teacher123',
            'student' => 'student123',
            default => 'N/A'
        };
        
        echo "<tr>
                <td><strong>{$user['username']}</strong></td>
                <td>" . ucfirst($user['role']) . "</td>
                <td>{$user['email']}</td>
                <td>{$status}</td>
                <td><code>{$password}</code></td>
              </tr>";
    }
    
    echo "</table>";
    
    echo "<div class='success' style='margin-top: 20px;'>
            <h3>✓ All passwords have been fixed!</h3>
            <p><strong>You can now login at:</strong> <a href='../auth/login.php'>http://localhost/smartgrade-v/auth/login.php</a></p>
            <p><strong>Test Accounts:</strong></p>
            <ul>
                <li><strong>Admin:</strong> admin / admin123</li>
                <li><strong>Teacher:</strong> jdelacruz / teacher123</li>
                <li><strong>Student:</strong> 2024001 / student123</li>
            </ul>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "    </div>
</body>
</html>";
?>
