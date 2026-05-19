<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

// Quick stats
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_sessions  = $pdo->query("SELECT COUNT(*) FROM exam_sessions")->fetchColumn();
$recent_sessions = $pdo->query("
    SELECT u.name, es.mode, es.submitted_at,
           COUNT(sq.id) as total_q,
           SUM(sq.is_correct) as correct
    FROM exam_sessions es
    JOIN users u ON u.id = es.user_id
    LEFT JOIN session_questions sq ON sq.session_id = es.id
    WHERE es.submitted_at IS NOT NULL
    GROUP BY es.id
    ORDER BY es.submitted_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard — FTRC</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">Admin Dashboard</h2>

<div class="stats-grid">
  <div class="stat-card">
    <p class="stat-number"><?= $total_students ?></p>
    <p class="stat-label">Total Students</p>
  </div>
  <div class="stat-card">
    <p class="stat-number"><?= $total_questions ?></p>
    <p class="stat-label">Questions</p>
  </div>
  <div class="stat-card">
    <p class="stat-number"><?= $total_sessions ?></p>
    <p class="stat-label">Exams Taken</p>
  </div>
</div>

<h2 class="section-title">Recent Exam Sessions</h2>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>Student</th>
      <th>Mode</th>
      <th>Score</th>
      <th>Percentage</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($recent_sessions)): ?>
    <tr><td colspan="5" style="text-align:center;color:#888">No exams taken yet.</td></tr>
  <?php else: ?>
    <?php foreach ($recent_sessions as $s): ?>
      <?php $pct = $s['total_q'] > 0 ? round(($s['correct'] / $s['total_q']) * 100, 1) : 0; ?>
      <tr>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= ucfirst($s['mode']) ?></td>
        <td><?= $s['correct'] ?>/<?= $s['total_q'] ?></td>
        <td>
          <span style="color:<?= $pct >= 75 ? '#1d9e75' : '#e24b4a' ?>; font-weight:600">
            <?= $pct ?>%
          </span>
        </td>
        <td><?= date('M d, Y h:i A', strtotime($s['submitted_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>