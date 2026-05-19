<?php
// ============================================================
// FTRC LET — One-time Admin Account Seeder
// DELETE THIS FILE after running it!
// ============================================================
require_once __DIR__ . '/../includes/db.php';

$name     = 'FTRC Admin';
$email    = 'admin@ftrc.com';
$password = 'ftrc@admin2024';  // change this to your preferred password
$role     = 'admin';

// Hash the password
$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role)
        VALUES (?, ?, ?, ?)
        ON CONFLICT (email) DO NOTHING
    ");
    $stmt->execute([$name, $email, $hash, $role]);

    echo "
    <h2 style='font-family:sans-serif;color:green'>
        ✅ Admin account created successfully!
    </h2>
    <p style='font-family:sans-serif'>
        <strong>Email:</strong> admin@ftrc.com <br>
        <strong>Password:</strong> ftrc@admin2024 <br><br>
        <strong style='color:red'>⚠️ DELETE this file now! (database/seed_admin.php)</strong>
    </p>
    ";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}