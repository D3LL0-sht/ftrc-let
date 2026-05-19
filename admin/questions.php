<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $topic_id      = (int)$_POST['topic_id'];
        $question_text = trim($_POST['question_text'] ?? '');
        $choice_a      = trim($_POST['choice_a'] ?? '');
        $choice_b      = trim($_POST['choice_b'] ?? '');
        $choice_c      = trim($_POST['choice_c'] ?? '');
        $choice_d      = trim($_POST['choice_d'] ?? '');
        $correct       = $_POST['correct_answer'] ?? '';
        $difficulty    = $_POST['difficulty'] ?? 'medium';
        $explanation   = trim($_POST['explanation'] ?? '');

        if ($topic_id && $question_text && $choice_a && $choice_b && $choice_c && $choice_d && $correct) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO questions
                    (topic_id, question_text, choice_a, choice_b, choice_c, choice_d,
                     correct_answer, difficulty, explanation)
                    VALUES (?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                    $topic_id, $question_text, $choice_a, $choice_b,
                    $choice_c, $choice_d, $correct, $difficulty, $explanation
                ]);
                $success = "Question added successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['question_id'];
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
        $success = "Question deleted.";
    }
}

$topics    = $pdo->query("SELECT * FROM topics ORDER BY name")->fetchAll();
$questions = $pdo->query("
    SELECT q.*, t.name as topic_name
    FROM questions q
    JOIN topics t ON t.id = q.topic_id
    ORDER BY q.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Questions — FTRC</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">Manage Questions</h2>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Add Question Form -->
<div class="card" style="margin-bottom:2rem;">
  <h3 style="margin-bottom:1rem;color:#0257aa;">Add New Question</h3>
  <form method="POST">
    <input type="hidden" name="action" value="create"/>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
      <div class="form-group">
        <label>Topic</label>
        <select name="topic_id" required style="width:100%;padding:9px 12px;
                border:1.5px solid #ddd;border-radius:8px;font-size:0.95rem;">
          <option value="">-- Select Topic --</option>
          <?php foreach ($topics as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Difficulty</label>
        <select name="difficulty" style="width:100%;padding:9px 12px;
                border:1.5px solid #ddd;border-radius:8px;font-size:0.95rem;">
          <option value="easy">Easy</option>
          <option value="medium" selected>Medium</option>
          <option value="hard">Hard</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Question Text</label>
      <textarea name="question_text" rows="3" required
                style="width:100%;padding:9px 12px;border:1.5px solid #ddd;
                border-radius:8px;font-size:0.95rem;resize:vertical;"></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <?php foreach (['A','B','C','D'] as $letter): ?>
      <div class="form-group">
        <label>Choice <?= $letter ?></label>
        <input type="text" name="choice_<?= strtolower($letter) ?>" required/>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="form-group">
      <label>Correct Answer</label>
      <select name="correct_answer" required style="width:100%;padding:9px 12px;
              border:1.5px solid #ddd;border-radius:8px;font-size:0.95rem;">
        <option value="">-- Select --</option>
        <?php foreach (['A','B','C','D'] as $letter): ?>
          <option value="<?= $letter ?>">Choice <?= $letter ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Explanation (shown as fallback if AI is offline)</label>
      <textarea name="explanation" rows="2"
                style="width:100%;padding:9px 12px;border:1.5px solid #ddd;
                border-radius:8px;font-size:0.95rem;resize:vertical;"></textarea>
    </div>

    <button type="submit" class="btn-primary" style="width:auto;padding:10px 24px;">
      Add Question
    </button>
  </form>
</div>

<!-- Questions Table -->
<p style="color:#666;font-size:0.9rem;margin-bottom:1rem;">
  Total: <strong><?= count($questions) ?></strong> questions
</p>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Topic</th>
      <th>Question</th>
      <th>Difficulty</th>
      <th>Answer</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($questions)): ?>
    <tr><td colspan="6" style="text-align:center;color:#888">No questions yet.</td></tr>
  <?php else: ?>
    <?php foreach ($questions as $i => $q): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($q['topic_name']) ?></td>
      <td style="max-width:300px;"><?= htmlspecialchars(substr($q['question_text'], 0, 80)) ?>...</td>
      <td><?= ucfirst($q['difficulty']) ?></td>
      <td><strong><?= $q['correct_answer'] ?></strong></td>
      <td>
        <form method="POST" onsubmit="return confirm('Delete this question?')">
          <input type="hidden" name="action" value="delete"/>
          <input type="hidden" name="question_id" value="<?= $q['id'] ?>"/>
          <button type="submit" style="background:#e24b4a;color:#fff;border:none;
                  border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;">
            Delete
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>