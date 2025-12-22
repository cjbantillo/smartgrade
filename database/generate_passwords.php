<?php
/**
 * Password Hash Generator
 * Generates correct password hashes for the system
 */

echo "<h2>SmartGrade Password Hashes</h2>";
echo "<pre>";

$passwords = [
    'admin123' => 'For admin account',
    'teacher123' => 'For teacher accounts',
    'student123' => 'For student accounts'
];

foreach ($passwords as $password => $description) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "\n{$description}:\n";
    echo "Password: {$password}\n";
    echo "Hash: {$hash}\n";
    echo str_repeat("-", 80) . "\n";
}

echo "</pre>";

echo "<h3>SQL Update Statements:</h3>";
echo "<textarea style='width:100%; height:300px; font-family:monospace;'>";

// Generate SQL updates
$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$teacherHash = password_hash('teacher123', PASSWORD_DEFAULT);
$studentHash = password_hash('student123', PASSWORD_DEFAULT);

echo "-- Update password hashes\n\n";
echo "-- Admin password\n";
echo "UPDATE users SET password_hash = '{$adminHash}' WHERE username = 'admin';\n\n";
echo "-- Teacher passwords\n";
echo "UPDATE users SET password_hash = '{$teacherHash}' WHERE username = 'jdelacruz';\n";
echo "UPDATE users SET password_hash = '{$teacherHash}' WHERE username = 'msantos';\n\n";
echo "-- Student passwords\n";
echo "UPDATE users SET password_hash = '{$studentHash}' WHERE username = '2024001';\n";
echo "UPDATE users SET password_hash = '{$studentHash}' WHERE username = '2024002';\n";
echo "UPDATE users SET password_hash = '{$studentHash}' WHERE username = '2024003';\n";

echo "</textarea>";
?>
