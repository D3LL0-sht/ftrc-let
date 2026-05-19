<?php
// ============================================================
// FTRC — One-time Admin Seeder. DELETE AFTER RUNNING!
// Run at: http://yourdomain.com/database/seed_admin.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';

$name     = 'FTRC Admin';
$email    = 'admin@ftrc.com';
$password = 'ftrc@admin2024';
$hash     = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role)
        VALUES (?, ?, ?, 'admin')
        ON DUPLICATE KEY UPDATE name = name
    ")->execute([$name, $email, $hash]);

    echo "<div style='font-family:sans-serif;padding:2rem;'>
        <h2 style='color:green'>✅ Admin account created!</h2>
        <p><strong>Email:</strong> admin@ftrc.com</p>
        <p><strong>Password:</strong> ftrc@admin2024</p>
        <p style='color:red'><strong>⚠️ DELETE this file now!</strong></p>
        <a href='/pages/login.php'>Go to Login →</a>
    </div>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}