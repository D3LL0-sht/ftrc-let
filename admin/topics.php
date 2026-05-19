<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name) {
        try {
            $pdo->prepare("INSERT INTO topics (name, description) VALUES (?,?)")
                ->execute([$name, $description]);
            $success = "Topic added!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$topics = $pdo->query("
    SELECT t.*, COUNT(q.id) as question_count
    FROM topics t
    LEFT JOIN questions q ON q.topic_id = t.id
    GROUP BY t.id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Topics — FTRC</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">Manage Topics</h2>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:2rem;">
  <h3 style="margin-bottom:1rem;color:#0257aa;">Add Topic</h3>
  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
      <div class="form-group">
        <label>Topic Name</label>
        <input type="text" name="name" placeholder="e.g. Literature" required/>
      </div>
      <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" placeholder="Brief description"/>
      </div>
    </div>
    <button type="submit" class="btn-primary" style="width:auto;padding:10px 24px;">
      Add Topic
    </button>
  </form>
</div>

<div class="table-wrap">
<table>
  <thead>
    <tr><th>#</th><th>Topic</th><th>Description</th><th>Questions</th></tr>
  </thead>
  <tbody>
  <?php foreach ($topics as $i => $t): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($t['name']) ?></td>
      <td><?= htmlspecialchars($t['description'] ?? '—') ?></td>
      <td><?= $t['question_count'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>