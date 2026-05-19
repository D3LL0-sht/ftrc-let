<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();
require_role('student');

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['user_name'];

// Quick stats
$total_exams = $pdo->prepare("
    SELECT COUNT(*) FROM exam_sessions
    WHERE user_id = ? AND submitted_at IS NOT NULL
");
$total_exams->execute([$user_id]);
$total_exams = $total_exams->fetchColumn();

$best_score = $pdo->prepare("
    SELECT MAX(ROUND(SUM(sq.is_correct) / COUNT(sq.id) * 100, 1))
    FROM exam_sessions es
    JOIN session_questions sq ON sq.session_id = es.id
    WHERE es.user_id = ? AND es.submitted_at IS NOT NULL
    GROUP BY es.id
");
$best_score->execute([$user_id]);
$best_score = $best_score->fetchColumn() ?? 0;

$topics = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
$questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — FTRC LET Review</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">Welcome, <?= htmlspecialchars($name) ?>! 👋</h2>
<p style="color:#666;margin-bottom:2rem;">
  Ready to review? Choose a mode below to start practicing.
</p>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <p class="stat-number"><?= $total_exams ?></p>
    <p class="stat-label">Exams Taken</p>
  </div>
  <div class="stat-card">
    <p class="stat-number"><?= $best_score ?>%</p>
    <p class="stat-label">Best Score</p>
  </div>
  <div class="stat-card">
    <p class="stat-number"><?= $questions ?></p>
    <p class="stat-label">Questions Available</p>
  </div>
  <div class="stat-card">
    <p class="stat-number"><?= $topics ?></p>
    <p class="stat-label">Topics</p>
  </div>
</div>

<!-- Quiz Modes -->
<h3 class="section-title">Start Reviewing</h3>
<div class="stats-grid">

  <div class="card" style="text-align:center;cursor:pointer;"
       onclick="location.href='/pages/quiz.php?mode=mock'">
    <div style="font-size:2.5rem;">📝</div>
    <h3 style="color:#0257aa;margin:8px 0 4px;">Mock Exam</h3>
    <p style="color:#666;font-size:0.85rem;">
      100 items · 2 hours · Full simulation
    </p>
    <button class="btn-primary" style="margin-top:1rem;">Start</button>
  </div>

  <div class="card" style="text-align:center;cursor:pointer;"
       onclick="location.href='/pages/quiz.php?mode=drill'">
    <div style="font-size:2.5rem;">🔁</div>
    <h3 style="color:#0257aa;margin:8px 0 4px;">Drill Mode</h3>
    <p style="color:#666;font-size:0.85rem;">
      50 items · No timer · Mixed topics
    </p>
    <button class="btn-primary" style="margin-top:1rem;">Start</button>
  </div>

  <div class="card" style="text-align:center;">
    <div style="font-size:2.5rem;">📚</div>
    <h3 style="color:#0257aa;margin:8px 0 4px;">Topic Quiz</h3>
    <p style="color:#666;font-size:0.85rem;">
      Focus on one topic at a time
    </p>
    <select id="topic-select"
            style="width:100%;padding:8px;border:1.5px solid #ddd;
            border-radius:8px;margin-top:8px;font-size:0.9rem;">
      <option value="">-- Pick a topic --</option>
      <?php
      $topics_list = $pdo->query("SELECT * FROM topics ORDER BY name")->fetchAll();
      foreach ($topics_list as $t):
      ?>
        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn-primary" style="margin-top:1rem;"
            onclick="startTopicQuiz()">Start</button>
  </div>

</div>

<div style="margin-top:1rem;">
  <a href="/pages/analytics.php" class="btn-outline"
     style="text-decoration:none;padding:10px 24px;">
    📊 View My Progress
  </a>
</div>

<script>
function startTopicQuiz() {
  const id = document.getElementById('topic-select').value;
  if (!id) { alert('Please select a topic first!'); return; }
  window.location.href = `/pages/quiz.php?mode=topic&topic_id=${id}`;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>