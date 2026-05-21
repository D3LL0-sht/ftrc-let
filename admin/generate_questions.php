<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$topics = $pdo->query("SELECT * FROM topics ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AI Question Generator — FTRC</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
  <style>
    .gen-card {
      background: #fff;
      border: 1px solid #e5e4de;
      border-radius: 14px;
      padding: 2rem;
      margin-bottom: 1.5rem;
    }
    textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      resize: vertical;
      font-family: inherit;
      box-sizing: border-box;
    }
    select {
      width: 100%;
      padding: 9px 12px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    .q-preview {
      background: #f9f9f6;
      border: 1px solid #e5e4de;
      border-radius: 10px;
      padding: 1.25rem;
      margin-bottom: 1rem;
    }
    .q-preview p {
      margin: 4px 0;
      font-size: 0.9rem;
    }
    .q-preview .correct {
      color: #1d9e75;
      font-weight: 600;
    }
    .badge-correct {
      background: #EAF3DE;
      color: #27500A;
      padding: 2px 8px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 600;
    }
    .badge-wrong {
      background: #f0f0ec;
      color: #666;
      padding: 2px 8px;
      border-radius: 99px;
      font-size: 11px;
    }
    #status {
      font-size: 0.9rem;
      color: #0257aa;
      margin: 1rem 0;
      min-height: 24px;
    }
    #status.error { color: #c0392b; }
    #status.success { color: #1d9e75; }
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid #ddd;
      border-top-color: #0257aa;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      vertical-align: middle;
      margin-right: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    #save-btn { display: none; }
    .batch-note {
      font-size: 0.8rem;
      color: #999;
      margin-top: 4px;
    }
    .q-number-badge {
      display: inline-block;
      background: #0257aa;
      color: #fff;
      border-radius: 99px;
      padding: 1px 10px;
      font-size: 12px;
      font-weight: 700;
      margin-right: 6px;
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">🤖 AI Question Generator</h2>
<p style="color:#666;margin-bottom:1.5rem;">
  Paste any lesson, passage, or topic notes — Gemini will generate LET-style questions automatically.
</p>

<div class="gen-card">
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:1rem;">

    <div class="form-group">
      <label>Topic</label>
      <select id="topic-select">
        <option value="">-- Select Topic --</option>
        <?php foreach ($topics as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Difficulty</label>
      <select id="difficulty-select">
        <option value="easy">Easy</option>
        <option value="medium" selected>Medium</option>
        <option value="hard">Hard</option>
      </select>
    </div>

    <div class="form-group">
      <label>Number of Questions</label>
      <select id="count-select">
        <option value="5" selected>5 questions</option>
        <option value="10">10 questions</option>
        <option value="15">15 questions</option>
        <option value="20">20 questions</option>
      </select>
      <p class="batch-note">⚠️ Higher counts may be split into batches automatically.</p>
    </div>

  </div>

  <div class="form-group">
    <label>Lesson / Passage / Topic Notes</label>
    <textarea id="lesson-input" rows="8"
      placeholder="Paste your lesson here. Example:

Philippine Literature covers literary works produced by Filipino authors in various languages including Filipino, English, and regional languages. Major periods include Pre-Colonial, Spanish Colonial, American Colonial, Japanese Occupation, and Contemporary. Key authors include Jose Rizal (Noli Me Tangere, El Filibusterismo), Francisco Balagtas (Florante at Laura), and Nick Joaquin..."></textarea>
  </div>

  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <button class="btn-primary" style="width:auto;padding:10px 28px;"
            onclick="generateQuestions()">
      ✨ Generate Questions
    </button>
    <button id="save-btn" class="btn-primary"
            style="width:auto;padding:10px 28px;background:#1d9e75;"
            onclick="saveQuestions()">
      💾 Save All to Database
    </button>
  </div>

  <div id="status"></div>
</div>

<!-- Preview -->
<div id="preview-container" style="display:none;">
  <h3 class="section-title">Preview — Review before saving</h3>
  <div id="preview-list"></div>
</div>

<script>
let generatedQuestions = [];
const API_KEY = '<?= AI_API_KEY ?>';
const API_URL = '<?= AI_API_URL ?>';

// Max questions per single API call to avoid token cutoff
const MAX_PER_BATCH = 10;

function setStatus(msg, type = 'info') {
  const el = document.getElementById('status');
  el.innerHTML = msg;
  el.className = type; // 'info', 'error', 'success'
}

// ─── Robust JSON extractor ───────────────────────────────────────────────────
function extractJSONArray(text) {
  // 1. Strip markdown code fences
  let clean = text.replace(/```json[\s\S]*?```/gi, m =>
    m.replace(/```json|```/gi, '')
  ).replace(/```/g, '').trim();

  // 2. Find outermost [ ... ]
  const start = clean.indexOf('[');
  const end   = clean.lastIndexOf(']');

  if (start === -1 || end === -1 || end <= start) {
    throw new Error('No JSON array found in AI response.');
  }

  let jsonStr = clean.substring(start, end + 1);

  // 3. Try parsing as-is
  try {
    return JSON.parse(jsonStr);
  } catch (e) {
    // 4. Response was truncated — salvage complete items only
    // Find the last complete object ending with }
    const lastClose = jsonStr.lastIndexOf('}');
    if (lastClose === -1) throw new Error('AI response JSON is unrecoverable.');

    // Cut off anything after last complete }
    jsonStr = jsonStr.substring(0, lastClose + 1);

    // Wrap back into array if needed
    if (!jsonStr.startsWith('[')) jsonStr = '[' + jsonStr;
    if (!jsonStr.endsWith(']'))   jsonStr = jsonStr + ']';

    // Remove trailing comma before ]
    jsonStr = jsonStr.replace(/,\s*\]$/, ']');

    return JSON.parse(jsonStr); // throws naturally if still broken
  }
}

// ─── Single batch API call ───────────────────────────────────────────────────
async function callGemini(topicName, difficulty, count, lesson) {
  const prompt = `You are an expert LET (Licensure Examination for Teachers) question writer for the Philippines.

Generate exactly ${count} multiple choice questions based on the lesson below.
Topic: ${topicName}
Difficulty: ${difficulty}

Lesson:
${lesson}

STRICT RULES:
- Each question must be LET-style (professional, clear, academic)
- 4 choices labeled A, B, C, D
- Only ONE correct answer per question
- correct_answer must be exactly one of: A, B, C, or D (uppercase)
- Include a brief explanation (2-3 sentences) of why the answer is correct
- Questions must be directly based on the lesson content
- Vary the question types (recall, application, analysis)
- Return ONLY a valid JSON array. No markdown, no backticks, no extra text before or after.
- Complete ALL ${count} questions fully — do NOT truncate.

JSON format (strictly follow this):
[
  {
    "question": "What is the question?",
    "choice_a": "First choice",
    "choice_b": "Second choice",
    "choice_c": "Third choice",
    "choice_d": "Fourth choice",
    "correct_answer": "A",
    "explanation": "The answer is A because..."
  }
]`;

  const res = await fetch(API_URL + API_KEY, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      contents: [{ parts: [{ text: prompt }] }],
      generationConfig: {
        temperature: 0.7,
        maxOutputTokens: 8192
      }
    })
  });

  if (!res.ok) {
    throw new Error(`Gemini API error: ${res.status} ${res.statusText}`);
  }

  const data = await res.json();

  // Check for API-level errors
  if (data.error) {
    throw new Error(`Gemini: ${data.error.message}`);
  }

  const text = data?.candidates?.[0]?.content?.parts?.[0]?.text || '';

  if (!text) {
    throw new Error('Gemini returned an empty response. Try again.');
  }

  return extractJSONArray(text);
}

// ─── Main generate function (handles batching) ───────────────────────────────
async function generateQuestions() {
  const topic_id   = document.getElementById('topic-select').value;
  const difficulty = document.getElementById('difficulty-select').value;
  const count      = parseInt(document.getElementById('count-select').value);
  const lesson     = document.getElementById('lesson-input').value.trim();
  const topicName  = document.getElementById('topic-select').selectedOptions[0]?.text || '';

  if (!topic_id) { alert('Please select a topic!'); return; }
  if (!lesson)   { alert('Please paste a lesson or topic notes!'); return; }

  setStatus('<span class="spinner"></span> Generating questions with Gemini AI...');
  document.getElementById('preview-container').style.display = 'none';
  document.getElementById('save-btn').style.display = 'none';
  generatedQuestions = [];

  try {
    let allQuestions = [];

    if (count <= MAX_PER_BATCH) {
      // Single call
      allQuestions = await callGemini(topicName, difficulty, count, lesson);
    } else {
      // Split into batches of MAX_PER_BATCH
      let remaining = count;
      let batchNum  = 1;
      const totalBatches = Math.ceil(count / MAX_PER_BATCH);

      while (remaining > 0) {
        const batchSize = Math.min(remaining, MAX_PER_BATCH);
        setStatus(`<span class="spinner"></span> Generating batch ${batchNum} of ${totalBatches} (${batchSize} questions)...`);
        const batch = await callGemini(topicName, difficulty, batchSize, lesson);
        allQuestions = allQuestions.concat(batch);
        remaining -= batchSize;
        batchNum++;
      }
    }

    if (!Array.isArray(allQuestions) || allQuestions.length === 0) {
      setStatus('⚠️ AI returned no questions. Try again or use a shorter lesson.', 'error');
      return;
    }

    // Normalize correct_answer to uppercase A/B/C/D
    generatedQuestions = allQuestions.map(q => ({
      ...q,
      topic_id:       parseInt(topic_id),
      topic_name:     topicName,
      difficulty:     difficulty,
      correct_answer: String(q.correct_answer).toUpperCase().charAt(0)
    }));

    renderPreview(generatedQuestions);
    setStatus(`✅ ${generatedQuestions.length} questions generated! Review them below then click Save.`, 'success');
    document.getElementById('save-btn').style.display = 'inline-block';

  } catch (err) {
    setStatus('❌ Error: ' + err.message + ' — Try again or reduce question count.', 'error');
    console.error(err);
  }
}

// ─── Render preview ──────────────────────────────────────────────────────────
function renderPreview(questions) {
  const container = document.getElementById('preview-list');
  container.innerHTML = questions.map((q, i) => `
    <div class="q-preview">
      <p style="font-weight:600;color:#0257aa;margin-bottom:8px;">
        <span class="q-number-badge">${i + 1}</span>${q.question}
      </p>
      <p>${q.correct_answer === 'A' ? '✅' : '⬜'} <strong>A.</strong> ${q.choice_a}
         ${q.correct_answer === 'A' ? '<span class="badge-correct">CORRECT</span>' : ''}</p>
      <p>${q.correct_answer === 'B' ? '✅' : '⬜'} <strong>B.</strong> ${q.choice_b}
         ${q.correct_answer === 'B' ? '<span class="badge-correct">CORRECT</span>' : ''}</p>
      <p>${q.correct_answer === 'C' ? '✅' : '⬜'} <strong>C.</strong> ${q.choice_c}
         ${q.correct_answer === 'C' ? '<span class="badge-correct">CORRECT</span>' : ''}</p>
      <p>${q.correct_answer === 'D' ? '✅' : '⬜'} <strong>D.</strong> ${q.choice_d}
         ${q.correct_answer === 'D' ? '<span class="badge-correct">CORRECT</span>' : ''}</p>
      <p style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e4de;color:#555;font-size:0.85rem;">
        💡 <em>${q.explanation}</em>
      </p>
    </div>
  `).join('');

  document.getElementById('preview-container').style.display = 'block';
}

// ─── Save to database ────────────────────────────────────────────────────────
async function saveQuestions() {
  if (!generatedQuestions.length) return;

  setStatus('<span class="spinner"></span> Saving questions to database...');
  document.getElementById('save-btn').disabled = true;

  try {
    const res  = await fetch('/api/save_questions.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ questions: generatedQuestions }),
    });

    const data = await res.json();

    if (data.success) {
      setStatus(`✅ ${data.saved} questions saved to database successfully!`, 'success');
      document.getElementById('save-btn').style.display  = 'none';
      document.getElementById('preview-container').style.display = 'none';
      generatedQuestions = [];
      document.getElementById('lesson-input').value = '';
      document.getElementById('topic-select').value = '';
    } else {
      setStatus('❌ Save failed: ' + (data.error || 'Unknown error'), 'error');
      document.getElementById('save-btn').disabled = false;
    }
  } catch (err) {
    setStatus('❌ Network error while saving: ' + err.message, 'error');
    document.getElementById('save-btn').disabled = false;
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>