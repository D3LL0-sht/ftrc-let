<?php
require_once __DIR__ . '/../config/config.php';
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
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>

<nav class="navbar">
  <div class="nav-brand">🎓 FTRC LET Review</div>

  <!-- Hamburger for mobile -->
  <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle menu">☰</button>

  <div class="nav-links" id="nav-links">
    <?php if ($user['role'] === 'admin'): ?>
      <a href="/admin/index.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'admin/index') ? 'class="active"' : '' ?>>
        📊 Dashboard
      </a>
      <a href="/admin/questions.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'questions') ? 'class="active"' : '' ?>>
        📝 Questions
      </a>
      <a href="/admin/generate_questions.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'generate') ? 'class="active"' : '' ?>>
        🤖 AI Generate
      </a>
      <a href="/admin/students.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'students') ? 'class="active"' : '' ?>>
        👥 Students
      </a>
      <a href="/admin/topics.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'topics') ? 'class="active"' : '' ?>>
        📚 Topics
      </a>
    <?php else: ?>
      <a href="/pages/dashboard.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'dashboard') ? 'class="active"' : '' ?>>
        🏠 Dashboard
      </a>
      <a href="/pages/quiz.php?mode=mock"
         <?= str_contains($_SERVER['PHP_SELF'], 'quiz') ? 'class="active"' : '' ?>>
        📝 Mock Exam
      </a>
      <a href="/pages/analytics.php"
         <?= str_contains($_SERVER['PHP_SELF'], 'analytics') ? 'class="active"' : '' ?>>
        📊 My Progress
      </a>
    <?php endif; ?>

    <div class="nav-user">
      <span class="nav-username">👤 <?= htmlspecialchars($user['name']) ?></span>
      <a href="/pages/logout.php" class="nav-logout">Logout</a>
    </div>
  </div>
</nav>

<div class="main-content">