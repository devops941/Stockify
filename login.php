<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        redirect('/dashboard.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= h(SHOP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-11 col-sm-8 col-md-5 col-lg-4">
      <div class="card p-4 shadow-sm">
        <div class="text-center mb-3">
          <i class="bi bi-receipt-cutoff" style="font-size:2.5rem;"></i>
          <h4 class="mt-2 mb-0"><?= h(SHOP_NAME) ?></h4>
          <small class="text-muted">GST Billing &amp; Stock Management</small>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus value="<?= h($_POST['username'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-muted small mt-3 mb-0 text-center">
          Demo: admin / admin123 &middot; cashier / cashier123
        </p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
