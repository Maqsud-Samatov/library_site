<?php
require_once __DIR__ . "/../app/config.php";
require_once __DIR__ . "/../app/functions.php";

$site_title = trim(get_setting($pdo, 'site_title', ''));
if ($site_title === '' || mb_strtolower($site_title) === 'online library') {
  $site_title = 'Onlayn Kutubxona';
}
$logo_path  = get_setting($pdo, 'logo_path', 'assets/img/logo.png');
$navbarRaw  = get_json_setting($pdo, 'navbar_json', [
  ['label'=>'Bosh sahifa','href'=>'index.php'],
  ['label'=>'Kategoriyalar','href'=>'category_list.php'],
]);

$navIconMap = [
  'index.php' => 'bi-house-door',
  'category_list.php' => 'bi-grid-3x3-gap',
  'admin/login.php' => 'bi-shield-check',
];

$navbar = array_values(array_map(function (array $item): array {
  $href = trim((string)($item['href'] ?? '#'));
  $path = ltrim((string)(parse_url($href, PHP_URL_PATH) ?? ''), '/');
  $label = trim((string)($item['label'] ?? ''));

  if ($path === '' || $path === 'index.php') {
    $label = 'Bosh sahifa';
  } elseif ($path === 'category_list.php') {
    $label = 'Kategoriyalar';
  } elseif ($path === 'admin/login.php' || $path === 'login.php') {
    $label = 'Admin';
    $href = 'admin/login.php';
  }

  return [
    'label' => $label !== '' ? $label : 'Link',
    'href' => $href !== '' ? $href : '#',
  ];
}, $navbarRaw));

$hasAdminLink = false;
foreach ($navbar as $item) {
  $path = ltrim((string)(parse_url((string)$item['href'], PHP_URL_PATH) ?? ''), '/');
  if ($path === 'admin/login.php' || $path === 'login.php') {
    $hasAdminLink = true;
    break;
  }
}

if (!$hasAdminLink) {
  $navbar[] = ['label' => 'Admin', 'href' => 'admin/login.php'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= e($site_title) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="topbar py-3">
  <div class="container topbar-shell d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <a class="brand" href="/" aria-label="<?= e($site_title) ?> — Bosh sahifa">
      <div class="brand-mark">
        <img class="img-hide-onerror" src="<?= e($logo_path) ?>" alt="logo" onerror="this.classList.add('is-hidden')">
      </div>
      <div class="brand-copy">
        <div class="title"><?= e($site_title) ?></div>
      </div>
    </a>

    <nav class="d-flex gap-2 nav-pill flex-wrap" aria-label="Main menu">
      <?php foreach ($navbar as $item): ?>
        <?php
          $href = (string)($item['href'] ?? '#');
          $path = ltrim((string)(parse_url($href, PHP_URL_PATH) ?? ''), '/');
          $currentPath = ltrim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''), '/');
          $current = basename($currentPath);
          $target = basename($path);
          $isActive = ($current !== '' && $target !== '' && $current === $target);
          $icon = $navIconMap[$path] ?? $navIconMap[$target] ?? 'bi-circle';
        ?>
        <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= e($href) ?>">
          <i class="bi <?= e($icon) ?> nav-icon" aria-hidden="true"></i>
          <span><?= e($item['label'] ?? 'Link') ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>

<main class="container">
