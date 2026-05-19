<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();
require_role('student');

$user_id = $_SESSION['user_id'];
$mode    = $_GET['mode']     ?? 'mock';
$topic_id = (int)($_GET['topic_id'] ?? 0);

// Time limits per mode
$time_limits = ['mock' => 7200, 'drill' => 0, 'topic' => 0];
$time_limit  = $time_limits[$mode] ?? 7200;

// Create exam session
$stmt = $pdo->prepare("
    INSERT INTO exam_sessions (user_id, mode, time_limit_sec, started_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$user_id, $mode, $time_limit ?: null]);
$session_id = $pdo->lastInsertId();

$topics = $pdo->query("SELECT * FROM topics ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Quiz — FTRC LET Review</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/quiz.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div id="quiz-container">

  <!-- Loading -->
  <div id="quiz-loading" style="text-align:center;padding:3rem;">
    <p style="font-size:1.1rem;color:#0257aa;">Loading questions...</p>
  </div>

  <!-- Quiz Header -->
  <div id="quiz-header" style="display:none;">
    <div class="quiz-topbar">
      <div>
        <span id="quiz-mode-label" class="mode-badge"></span>
        <span id="quiz-progress"></span>
      </div>
      <div id="timer-wrap" style="display:none;">
        ⏱ <span id="timer" style="font-weight:600;color:#0257aa;font-size:1.1rem;"></span>
      </div>
    </div>
    <div class="progress-track">
      <div id="progress-fill" class="progress-fill"></div>
    </div>
  </div>

  <!-- Question Card -->
  <div id="question-card" style="display:none;" class="card">
    <p id="question-topic" class="q-topic"></p>
    <p id="question-number" class="q-number"></p>
    <p id="question-text" class="q-text"></p>
    <div id="choices" class="choices-grid"></div>
    <div class="quiz-nav">
      <button id="btn-prev" class="btn-outline" onclick="prevQuestion()">← Previous</button>
      <button id="btn-next" class="btn-primary-sm" onclick="nextQuestion()">Next →</button>
      <button id="btn-submit" class="btn-submit" onclick="submitQuiz()" style="display:none;">
        Submit Exam
      </button>
    </div>
  </div>

  <!-- Submitting -->
  <div id="quiz-submitting" style="display:none;text-align:center;padding:3rem;">
    <p style="font-size:1.1rem;color:#0257aa;">Submitting your answers...</p>
  </div>

</div>

<script>
const SESSION_ID  = <?= $session_id ?>;
const MODE        = '<?= $mode ?>';
const TOPIC_ID    = <?= $topic_id ?>;
const TIME_LIMIT  = <?= $time_limit ?>;
const API_BASE    = '/api';

let questions   = [];
let answers     = {};
let startTimes  = {};
let currentIdx  = 0;
let timerInterval;
let timeLeft    = TIME_LIMIT;

// ── Load questions ──────────────────────────────────────────
async function loadQuestions() {
  let url = `${API_BASE}/get_questions.php?mode=${MODE}&limit=100`;
  if (MODE === 'topic' && TOPIC_ID) url += `&topic_id=${TOPIC_ID}`;

  const res  = await fetch(url);
  const data = await res.json();

  if (!data.success || !data.questions.length) {
    document.getElementById('quiz-loading').innerHTML =
      '<p style="color:red">No questions found. Please ask your admin to add questions.</p>';
    return;
  }

  questions = data.questions;
  document.getElementById('quiz-loading').style.display  = 'none';
  document.getElementById('quiz-header').style.display   = 'block';
  document.getElementById('question-card').style.display = 'block';

  // Mode label
  const labels = { mock: '📝 Mock Exam', drill: '🔁 Drill', topic: '📚 Topic Quiz' };
  document.getElementById('quiz-mode-label').textContent = labels[MODE] || MODE;

  // Timer
  if (TIME_LIMIT > 0) {
    document.getElementById('timer-wrap').style.display = 'inline-flex';
    startTimer();
  }

  renderQuestion(0);
}

// ── Timer ───────────────────────────────────────────────────
function startTimer() {
  updateTimerDisplay();
  timerInterval = setInterval(() => {
    timeLeft--;
    updateTimerDisplay();
    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      submitQuiz(true);
    }
  }, 1000);
}

function updateTimerDisplay() {
  const h = Math.floor(timeLeft / 3600);
  const m = Math.floor((timeLeft % 3600) / 60);
  const s = timeLeft % 60;
  document.getElementById('timer').textContent =
    `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  if (timeLeft <= 300) {
    document.getElementById('timer').style.color = '#e24b4a';
  }
}

// ── Render question ──────────────────────────────────────────
function renderQuestion(idx) {
  currentIdx = idx;
  const q    = questions[idx];
  startTimes[q.id] = Date.now();

  document.getElementById('question-number').textContent =
    `Question ${idx + 1} of ${questions.length}`;
  document.getElementById('question-text').textContent   = q.question_text;

  // Progress bar
  const pct = ((idx + 1) / questions.length) * 100;
  document.getElementById('progress-fill').style.width   = pct + '%';
  document.getElementById('quiz-progress').textContent   =
    ` ${idx + 1}/${questions.length} answered: ${Object.keys(answers).length}`;

  // Choices
  const choicesEl = document.getElementById('choices');
  choicesEl.innerHTML = '';
  ['A','B','C','D'].forEach(letter => {
    const key  = `choice_${letter.toLowerCase()}`;
    const btn  = document.createElement('button');
    btn.className = 'choice-btn' + (answers[q.id] === letter ? ' selected' : '');
    btn.innerHTML = `<span class="choice-letter">${letter}</span> ${q[key]}`;
    btn.onclick   = () => selectAnswer(q.id, letter);
    choicesEl.appendChild(btn);
  });

  // Nav buttons
  document.getElementById('btn-prev').style.display =
    idx === 0 ? 'none' : 'inline-block';
  document.getElementById('btn-next').style.display =
    idx === questions.length - 1 ? 'none' : 'inline-block';
  document.getElementById('btn-submit').style.display =
    idx === questions.length - 1 ? 'inline-block' : 'none';
}

// ── Select answer ────────────────────────────────────────────
function selectAnswer(questionId, letter) {
  const elapsed = Math.round((Date.now() - (startTimes[questionId] || Date.now())) / 1000);
  answers[questionId] = { answer: letter, time_spent: elapsed };
  renderQuestion(currentIdx);
}

function prevQuestion() {
  if (currentIdx > 0) renderQuestion(currentIdx - 1);
}

function nextQuestion() {
  if (currentIdx < questions.length - 1) renderQuestion(currentIdx + 1);
}

// ── Submit ───────────────────────────────────────────────────
async function submitQuiz(autoSubmit = false) {
  const answered = Object.keys(answers).length;
  const total    = questions.length;

  if (!autoSubmit && answered < total) {
    if (!confirm(`You have ${total - answered} unanswered questions. Submit anyway?`)) return;
  }

  clearInterval(timerInterval);
  document.getElementById('question-card').style.display   = 'none';
  document.getElementById('quiz-header').style.display     = 'none';
  document.getElementById('quiz-submitting').style.display = 'block';

  const timeUsed = TIME_LIMIT > 0 ? TIME_LIMIT - timeLeft : 0;

  const payload = {
    session_id: SESSION_ID,
    time_used:  timeUsed,
    answers: Object.entries(answers).map(([qid, val]) => ({
      question_id: parseInt(qid),
      answer:      val.answer,
      time_spent:  val.time_spent,
    }))
  };

  const res  = await fetch(`${API_BASE}/submit_quiz.php`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload),
  });
  const data = await res.json();

  if (data.success) {
    window.location.href = `/pages/results.php?session_id=${SESSION_ID}`;
  } else {
    alert('Submission failed: ' + data.error);
    document.getElementById('quiz-submitting').style.display = 'none';
    document.getElementById('question-card').style.display   = 'block';
  }
}

loadQuestions();
</script>
</body>
</html>