<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$success = '';
$error   = '';

// Create student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name && $email && $password) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, role)
                    VALUES (?, ?, ?, 'student')
                ");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
                $success = "Student account created successfully!";
            } catch (PDOException $e) {
                $error = "Email already exists.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['user_id'];
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$id]);
        $success = "Student deleted.";
    }

    if ($_POST['action'] === 'reset_password') {
        $id       = (int)$_POST['user_id'];
        $password = $_POST['new_password'] ?? '';
        if ($password) {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
            $success = "Password reset successfully.";
        }
    }
}

$students = $pdo->query("
    SELECT u.*, COUNT(es.id) as total_exams
    FROM users u
    LEFT JOIN exam_sessions es ON es.user_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Students — FTRC</title>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="/assets/css/dashboard.css"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="section-title">Manage Students</h2>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Create Student Form -->
<div class="card" style="margin-bottom:2rem;">
  <h3 style="margin-bottom:1rem;color:#0257aa;">Create Student Account</h3>
  <form method="POST">
    <input type="hidden" name="action" value="create"/>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Juan dela Cruz" required/>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="juan@email.com" required/>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required/>
      </div>
    </div>
    <button type="submit" class="btn-primary" style="width:auto;padding:10px 24px;">
      Create Account
    </button>
  </form>
</div>

<!-- Students Table -->
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Email</th>
      <th>Exams Taken</th>
      <th>Registered</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($students)): ?>
    <tr><td colspan="6" style="text-align:center;color:#888">No students yet.</td></tr>
  <?php else: ?>
    <?php foreach ($students as $i => $s): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($s['name']) ?></td>
      <td><?= htmlspecialchars($s['email']) ?></td>
      <td><?= $s['total_exams'] ?></td>
      <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
      <td style="display:flex;gap:8px;">
        <!-- Reset Password -->
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="reset_password"/>
          <input type="hidden" name="user_id" value="<?= $s['id'] ?>"/>
          <input type="text" name="new_password" placeholder="New password"
                 style="width:120px;padding:4px 8px;font-size:12px;border:1px solid #ddd;border-radius:6px;"/>
          <button type="submit" style="background:#0257aa;color:#fff200;border:none;
                  border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;">
            Reset
          </button>
        </form>
        <!-- Delete -->
        <form method="POST" onsubmit="return confirm('Delete this student?')">
          <input type="hidden" name="action" value="delete"/>
          <input type="hidden" name="user_id" value="<?= $s['id'] ?>"/>
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