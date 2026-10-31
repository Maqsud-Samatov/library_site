<?php
require_once __DIR__ . "/../app/config.php";
require_once __DIR__ . "/../app/functions.php";

/**
 * Debug logger (only when ?dbg=1)
 * writes to PHP error_log -> nginx error.log (php-fpm stderr)
 */
function admin_login_log(string $msg, array $ctx = []): void {
  if (!isset($_GET['dbg']) || (string)$_GET['dbg'] !== '1') return;
  $base = [
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
    'sid' => session_id() ?: null,
  ];
  $ctx = array_merge($base, $ctx);
  error_log('[ADMIN_LOGIN] ' . $msg . ' | ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

admin_login_log('Page hit');

try {
  // DB sanity checks
  admin_login_log('PDO ok', [
    'dsn' => defined('DB_DSN') ? DB_DSN : null,
    'db_const' => defined('DB_NAME') ? DB_NAME : null,
  ]);

  try {
    $dbNow = $pdo->query("SELECT DATABASE()")->fetchColumn();
    admin_login_log('SELECT DATABASE()', ['db_now' => $dbNow]);
  } catch (Throwable $e) {
    admin_login_log('SELECT DATABASE() failed', ['err' => $e->getMessage()]);
  }

} catch (Throwable $e) {
  admin_login_log('Bootstrap failed', ['err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
  http_response_code(500);
  echo "Server error (bootstrap).";
  exit;
}

if (!empty($_SESSION['admin_id'])) {
  admin_login_log('Already authed -> redirect', ['admin_id' => $_SESSION['admin_id']]);
  header("Location: dashboard.php"); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  admin_login_log('POST received', [
    'username' => $username,
    'pass_len' => strlen($password),
  ]);

  try {
    $st = $pdo->prepare("SELECT * FROM admins WHERE username=? AND is_active=1 LIMIT 1");
    admin_login_log('Preparing query ok');

    $st->execute([$username]);
    admin_login_log('Query executed', ['rowCount' => $st->rowCount()]);

    $admin = $st->fetch();

    if (!$admin) {
      admin_login_log('Admin not found or inactive', ['username' => $username]);
      $error = "Wrong username or password.";
    } else {
      admin_login_log('Admin found', [
        'id' => $admin['id'] ?? null,
        'role' => $admin['role'] ?? null,
        'has_hash' => !empty($admin['password_hash']),
      ]);

      $ok = false;
      try {
        $ok = password_verify($password, (string)$admin['password_hash']);
      } catch (Throwable $e) {
        admin_login_log('password_verify exception', ['err' => $e->getMessage()]);
        $ok = false;
      }

      admin_login_log('password_verify result', ['ok' => $ok]);

      if ($ok) {
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_role'] = (string)$admin['role'];

        admin_login_log('Session set', [
          'admin_id' => $_SESSION['admin_id'],
          'admin_role' => $_SESSION['admin_role'],
        ]);

        try {
          $pdo->prepare("UPDATE admins SET last_login_at=NOW(), last_login_ip=? WHERE id=?")
            ->execute([($_SERVER['REMOTE_ADDR'] ?? null), (int)$admin['id']]);
          admin_login_log('Updated last_login fields');
        } catch (Throwable $e) {
          admin_login_log('Failed updating last_login', ['err' => $e->getMessage()]);
          // login works anyway
        }

        admin_login_log('Redirecting to dashboard');
        header("Location: dashboard.php"); exit;
      } else {
        $error = "Wrong username or password.";
      }
    }

  } catch (Throwable $e) {
    admin_login_log('DB error in POST flow', ['err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    $error = "Server error. Please try again.";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
  <style>
    .login-wrap{max-width:520px}
  </style>
</head>
<body>
<div class="container py-5 login-wrap">
  <div class="stats-box">
    <div class="h4 fw-bold mb-3">Admin Login</div>
    <div class="muted mb-3">Boshqaruv paneliga xavfsiz kirish</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="d-grid gap-3">
      <div>
        <label class="form-label muted">Login</label>
        <input class="form-control" name="username" required>
      </div>
      <div>
        <label class="form-label muted">Parol</label>
        <input class="form-control" name="password" type="password" required>
      </div>
      <button class="btn btn-success">Login</button>
      <a class="btn btn-outline-secondary" href="../index.php">Saytga qaytish</a>
    </form>

    <?php if (isset($_GET['dbg']) && (string)$_GET['dbg'] === '1'): ?>
      <div class="mt-3 small text-muted">
        Debug yoqilgan: loglar nginx error.log ga yoziladi.
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
