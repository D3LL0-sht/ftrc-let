<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (is_logged_in()) {
    header('Location: /FTRC/pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT id, name, role, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: /FTRC/admin/index.php');
            } else {
                header('Location: /FTRC/pages/dashboard.php');
            }
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — FTRC LET Review</title>
  <link rel="stylesheet" href="/FTRC/assets/css/style.css" />
</head>
<body class="auth-page">

  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-icon-wrap">
        <span class="auth-icon">🎓</span>
      </div>
      <h1>FTRC LET Review</h1>
      <p>English Specialization</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email"
               placeholder="you@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••"
               required />
      </div>

      <button type="submit" class="btn-primary">Sign in</button>
    </form>

    <p class="auth-footer">
      No account? <a href="/FTRC/pages/register.php">Register here</a>
    </p>
  </div>

</body>
</html>