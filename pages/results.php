<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();
require_role('student');

$session_id = (int)($_GET['session_id'] ?? 0);
if (!$session_id) {
    header('Location: /pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Results — FTRC LET Review</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
  <link rel="stylesheet" href="/assets/css/quiz.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div id="results-loading" style="text-align:center;padding:3rem;">
  <p style="color:#0257aa;font-size:1.1rem;">Loading results...</p>
</div>

<div id="results-container" style="display:none;">

  <!-- Score Summary -->
  <div class="card" style="text-align:center;margin-bottom:2rem;">
    <h2 class="section-title" style="text-align:center;">Exam Results</h2>
    <div id="score-display"></div>
    <div class="stats-grid" id="score-stats" style="margin-top:1.5rem;"></div>
  </div>

  <!-- Topic Breakdown -->
  <div class="card" style="margin-bottom:2rem;">
    <h3 class="section-title">Performance by Topic</h3>
    <div id="topic-breakdown"></div>
  </div>

  <!-- Answer Review -->
  <div class="card">
    <h3 class="section-title">Answer Review</h3>
    <div id="answer-review"></div>
  </div>

  <div style="text-align:center;margin:2rem 0;display:flex;gap:12px;justify-content:center;">
    <a href="/pages/quiz.php?mode=mock" class="btn-primary"
       style="text-decoration:none;padding:10px 24px;">
      Take Another Exam
    </a>
    <a href="/pages/analytics.php" class="btn-outline"
       style="text-decoration:none;padding:10px 24px;">
      View My Progress
    </a>
  </div>

</div>

<script>
const SESSION_ID = <?= $session_id ?>;
const API_BASE   = '/api';

async function loadResults() {
  const res  = await fetch(`${API_BASE}/get_results.php?session_id=${SESSION_ID}`);
  const data = await res.json();

  if (!data.success) {
    document.getElementById('results-loading').innerHTML =
      `<p style="color:red">Error: ${data.error}</p>`;
    return;
  }

  document.getElementById('results-loading').style.display    = 'none';
  document.getElementById('results-container').style.display  = 'block';

  const { score, topics, answers } = data;

  // Score display
  const passed = score.passed;
  document.getElementById('score-display').innerHTML = `
    <div style="font-size:4rem;font-weight:700;color:${passed ? '#1d9e75' : '#e24b4a'}">
      ${score.pct}%
    </div>
    <span class="pass-badge ${passed ? 'pass' : 'fail'}">
      ${passed ? '✅ PASSED' : '❌ BELOW PASSING'}
    </span>
    <p style="color:#666;font-size:0.9rem;margin-top:8px;">
      LET passing score is 75%
    </p>
  `;

  // Stats
  document.getElementById('score-stats').innerHTML = `
    <div class="stat-card">
      <p class="stat-number">${score.correct}</p>
      <p class="stat-label">Correct</p>
    </div>
    <div class="stat-card">
      <p class="stat-number" style="color:#e24b4a">${score.wrong}</p>
      <p class="stat-label">Wrong</p>
    </div>
    <div class="stat-card">
      <p class="stat-number">${score.total}</p>
      <p class="stat-label">Total Items</p>
    </div>
  `;

  // Topic breakdown
  const topicHtml = Object.entries(topics).map(([name, t]) => {
    const pct   = t.total > 0 ? Math.round((t.correct / t.total) * 100) : 0;
    const cls   = pct >= 75 ? 'success' : pct >= 50 ? 'warning' : 'danger';
    return `
      <div class="topic-bar">
        <div class="topic-bar-label">
          <span>${name}</span>
          <span>${t.correct}/${t.total} (${pct}%)</span>
        </div>
        <div class="topic-bar-track">
          <div class="topic-bar-fill ${cls}" style="width:${pct}%"></div>
        </div>
      </div>
    `;
  }).join('');
  document.getElementById('topic-breakdown').innerHTML = topicHtml;

  // Answer review
  const reviewHtml = answers.map((a, i) => `
    <div class="card" style="margin-bottom:1rem;border-left:4px solid
         ${a.is_correct ? '#1d9e75' : '#e24b4a'}">
      <p class="q-number">Question ${i + 1} — ${a.topic_name}</p>
      <p class="q-text">${a.question_text}</p>
      <div style="display:grid;gap:6px;margin-bottom:1rem;">
        ${['A','B','C','D'].map(l => {
          const key = `choice_${l.toLowerCase()}`;
          let cls = '';
          if (l === a.correct_answer) cls = 'correct';
          else if (l === a.user_answer && !a.is_correct) cls = 'wrong';
          return `
            <div class="choice-btn ${cls}" style="cursor:default;">
              <span class="choice-letter">${l}</span> ${a[key]}
            </div>
          `;
        }).join('')}
      </div>
      <div id="explain-${a.id}">
        ${a.explanation
          ? `<div class="alert" style="background:#f0f6ff;border:1px solid #c0d8f5;color:#1a1a1a">
               💡 ${a.explanation}
             </div>`
          : `<button class="btn-outline" style="font-size:0.85rem;"
                     onclick="getExplanation(${a.id}, this)">
               🤖 Ask AI to explain
             </button>`
        }
      </div>
    </div>
  `).join('');
  document.getElementById('answer-review').innerHTML = reviewHtml;
}

async function getExplanation(sessionQuestionId, btn) {
  btn.textContent = 'Getting explanation...';
  btn.disabled    = true;

  const res  = await fetch(`${API_BASE}/explain.php`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ session_question_id: sessionQuestionId }),
  });
  const data = await res.json();

  const container = document.getElementById(`explain-${sessionQuestionId}`);
  if (data.success) {
    container.innerHTML = `
      <div class="alert" style="background:#f0f6ff;border:1px solid #c0d8f5;color:#1a1a1a">
        🤖 <strong>AI Explanation:</strong><br/>${data.explanation}
      </div>
    `;
  } else {
    btn.textContent = '⚠️ Try again';
    btn.disabled    = false;
  }
}

loadResults();
</script>
</body>
</html>