<?php
require_once __DIR__ . "/_layout_top.php";
admin_header("News");

// Delete
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];

  $st = $pdo->prepare("SELECT image_path FROM news WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch();

  $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$id]);

  if ($row && !empty($row['image_path']) && strpos($row['image_path'], 'assets/') === 0) {
    $abs = realpath(__DIR__ . "/../" . $row['image_path']);
    if ($abs && is_file($abs)) @unlink($abs);
  }

  header("Location: news.php"); exit;
}

$items = $pdo->query("
  SELECT * FROM news
  ORDER BY sort_order ASC, id DESC
")->fetchAll();
?>

<style>
  /* admin/news.php-only CSS (no inline styles) */
  .col-w-80{width:80px}
  .col-w-120{width:120px}
  .col-w-220{width:220px}
  .news-snippet-maxw{max-width:720px}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h3 class="fw-bold mb-0">News (Swiper)</h3>
  <a class="btn btn-success" href="news_edit.php">+ Qo'shish</a>
</div>
<div class="stats-box mt-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th class="col-w-80">ID</th>
          <th>Nomi</th>
          <th class="col-w-120">Faol</th>
          <th class="col-w-120">Raqam</th>
          <th class="col-w-220">Amallar</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="5" class="muted">Hozircha habarlar yo'q.</td></tr>
      <?php endif; ?>

      <?php foreach ($items as $n): ?>
        <tr>
          <td><?= (int)$n['id'] ?></td>
          <td>
            <div class="fw-bold"><?= e((string)$n['title']) ?></div>
            <div class="muted small news-snippet-maxw">
              <?= e(mb_strimwidth((string)$n['body'], 0, 160, '...','UTF-8')) ?>
            </div>
          </td>
          <td><?= (int)$n['is_active'] ? '✅ Yes' : '—' ?></td>
          <td><?= (int)$n['sort_order'] ?></td>
          <td>
            <a class="btn btn-sm btn-outline-secondary" href="news_edit.php?id=<?= (int)$n['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger"
               href="news.php?del=<?= (int)$n['id'] ?>"
               onclick="return confirm('Delete this news item?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php admin_footer(); ?>
