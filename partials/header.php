<?php
require_once __DIR__ . "/../app/config.php";
require_once __DIR__ . "/../app/functions.php";

$site_title = trim(get_setting($pdo, 'site_title', ''));
if ($site_title === '' || mb_strtolower($site_title) === 'online library') {
  $site_title = 'Onlayn Kutubxona';
}
$logo_path  = get_setting($pdo, 'logo_path', 'assets/img/logo.png');
$navbar     = get_json_setting($pdo, 'navbar_json', [
  ['label'=>'Bosh sahifa','href'=>'index.php'],
  ['label'=>'Categoriyalar','href'=>'category_list.php'],
]);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= e($site_title) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="topbar py-3">
  <div class="container topbar-shell d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <div class="brand">
      <div class="brand-mark">
        <img class="img-hide-onerror" src="<?= e($logo_path) ?>" alt="logo" onerror="this.classList.add('is-hidden')">
      </div>
      <div class="brand-copy">
        <div class="title"><?= e($site_title) ?></div>
      </div>
    </div>

    <nav class="d-flex gap-2 nav-pill flex-wrap" aria-label="Main menu">
      <?php foreach ($navbar as $item): ?>
        <?php
          $href = (string)($item['href'] ?? '#');
          $current = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
          $target = basename(parse_url($href, PHP_URL_PATH) ?? '');
          $isActive = ($current !== '' && $target !== '' && $current === $target);
        ?>
        <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= e($href) ?>">
          <span class="menu-dot" aria-hidden="true"></span>
          <span><?= e($item['label'] ?? 'Link') ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>

<main class="container">
