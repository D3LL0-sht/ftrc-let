<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();
require_role('student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Progress — FTRC LET Review</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">My Progress</h2>

<div id="analytics-loading" style="text-align:center;padding:2rem;">
  <p style="color:#0257aa;">Loading your progress...</p>
</div>

<div id="analytics-container" style="display:none;">

  <!-- Pass Prediction -->
  <div class="card" style="text-align:center;margin-bottom:2rem;">
    <h3 class="section-title">LET Pass Prediction</h3>
    <div id="prediction-display"></div>
  </div>

  <!-- Topic Performance -->
  <div class="card" style="margin-bottom:2rem;">
    <h3 class="section-title">Performance by Topic</h3>
    <div id="topic-performance"></div>
  </div>

  <!-- Exam History -->
  <div class="card">
    <h3 class="section-title">Exam History</h3>
    <div class="table-wrap">
    <table id="history-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Mode</th>
          <th>Score</th>
          <th>Percentage</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="history-body"></tbody>
    </table>
    </div>
  </div>

  <!-- Start Quiz Buttons -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:2rem;">
    <a href="/pages/quiz.php?mode=mock" class="btn-primary"
       style="text-decoration:none;padding:10px 24px;">
      📝 Mock Exam
    </a>
    <a href="/pages/quiz.php?mode=drill" class="btn-outline"
       style="text-decoration:none;padding:10px 24px;">
      🔁 Drill Mode
    </a>
  </div>

</div>

<script>
const API_BASE = '/api';

async function loadAnalytics() {
  const res  = await fetch(`${API_BASE}/get_analytics.php`);
  const data = await res.json();

  if (!data.success) {
    document.getElementById('analytics-loading').innerHTML =
      `<p style="color:red">Error: ${data.error}</p>`;
    return;
  }

  document.getElementById('analytics-loading').style.display    = 'none';
  document.getElementById('analytics-container').style.display  = 'block';

  const { topics, history, prediction } = data;

  // Pass prediction
  const avg    = prediction.average;
  const passed = prediction.will_pass;
  document.getElementById('prediction-display').innerHTML = `
    <div style="font-size:3.5rem;font-weight:700;
         color:${passed ? '#1d9e75' : '#e24b4a'}">
      ${avg}%
    </div>
    <span class="pass-badge ${passed ? 'pass' : 'fail'}">
      ${passed ? '✅ On track to PASS' : '⚠️ Needs improvement — aim for 75%'}
    </span>
    <p style="color:#666;font-size:0.85rem;margin-top:8px;">
      Based on your average across all topics
    </p>
  `;

  // Topic performance
  if (!topics.length) {
    document.getElementById('topic-performance').innerHTML =
      '<p style="color:#888;text-align:center;">No data yet — take a quiz first!</p>';
  } else {
    document.getElementById('topic-performance').innerHTML =
      topics.map(t => {
        const pct = parseFloat(t.accuracy_pct) || 0;
        const cls = pct >= 75 ? 'success' : pct >= 50 ? 'warning' : 'danger';
        return `
          <div class="topic-bar">
            <div class="topic-bar-label">
              <span>${t.name}</span>
              <span>${t.correct_count}/${t.total_attempts} (${pct}%)</span>
            </div>
            <div class="topic-bar-track">
              <div class="topic-bar-fill ${cls}" style="width:${pct}%"></div>
            </div>
          </div>
        `;
      }).join('');
  }

  // Exam history
  if (!history.length) {
    document.getElementById('history-body').innerHTML =
      '<tr><td colspan="6" style="text-align:center;color:#888">No exams yet.</td></tr>';
  } else {
    document.getElementById('history-body').innerHTML =
      history.map((h, i) => {
        const pct = h.total > 0 ? Math.round((h.correct / h.total) * 100) : 0;
        return `
          <tr>
            <td>${i + 1}</td>
            <td>${h.mode.charAt(0).toUpperCase() + h.mode.slice(1)}</td>
            <td>${h.correct}/${h.total}</td>
            <td style="color:${pct >= 75 ? '#1d9e75' : '#e24b4a'};font-weight:600">
              ${pct}%
            </td>
            <td>${new Date(h.submitted_at).toLocaleDateString('en-PH',
              {month:'short',day:'numeric',year:'numeric'})}</td>
            <td>
              <a href="/pages/results.php?session_id=${h.id}"
                 style="color:#0257aa;font-size:0.85rem;">View →</a>
            </td>
          </tr>
        `;
      }).join('');
  }
}

loadAnalytics();
</script>
</body>
</html>