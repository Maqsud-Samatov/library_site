<?php
require_once __DIR__ . "/../app/config.php";
require_once __DIR__ . "/../app/functions.php";
require_admin();

function admin_header(string $title): void { ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= e($title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
  <style>
    /* Admin layout-only CSS (no inline styles) */
    .admin-sidebar-fixed{width:260px}
  </style>
</head>
<body>
<div class="admin-shell d-flex">
  <aside class="admin-sidebar p-3 admin-sidebar-fixed">
    <div class="fw-bold mb-3 admin-title">Admin Oynasi</div>
    <div class="d-grid gap-1 admin-menu">
  <a href="dashboard.php" class="admin-link">
    <i class="bi bi-speedometer2"></i>
    <span>Asosiy</span>
  </a>

  <a href="settings.php" class="admin-link">
    <i class="bi bi-layout-text-window-reverse"></i>
    <span>Header / Footer</span>
  </a>

  <a href="categories.php" class="admin-link">
    <i class="bi bi-tags"></i>
    <span>Kategoriyalar</span>
  </a>

  <a href="books.php" class="admin-link">
    <i class="bi bi-book"></i>
    <span>Kitob qo‘shish</span>
  </a>

  <a href="media.php" class="admin-link">
    <i class="bi bi-image"></i>
    <span>Media fon qo‘shish</span>
  </a>

  <a href="news.php" class="admin-link">
    <i class="bi bi-newspaper"></i>
    <span>Yangiliklar</span>
  </a>
  <a href="stats_matrix_manage.php" class="admin-link">
    <i class="bi bi-bar-chart-line"></i>
    <span>Statistika</span>
  </a>

  <a href="logout.php" class="admin-link text-danger">
    <i class="bi bi-box-arrow-right"></i>
    <span>Chiqish</span>
  </a>
  
</div>

  </aside>
  <main class="flex-grow-1 p-4">
<?php }

function admin_footer(): void { ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }
