<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= APP_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
</head>
<body>
<nav class="navbar">
  <div class="nav-brand">🎓 FTRC LET Review</div>
  <div class="nav-links">
    <?php if ($user['role'] === 'admin'): ?>
      <a href="/admin/index.php">Dashboard</a>
      <a href="/admin/questions.php">Questions</a>
      <a href="/admin/students.php">Students</a>
    <?php else: ?>
      <a href="/pages/dashboard.php">Dashboard</a>
      <a href="/pages/quiz.php">Take Quiz</a>
      <a href="/pages/analytics.php">My Progress</a>
    <?php endif; ?>
    <a href="/pages/logout.php" class="nav-logout">Logout (<?= htmlspecialchars($user['name']) ?>)</a>
  </div>
</nav>
<div class="main-content">