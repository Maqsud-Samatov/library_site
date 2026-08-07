<?php
require_once __DIR__ . "/_layout_top.php";
admin_header("Media Section");

// Delete
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];

  // Optional: delete image file (if local)
  $st = $pdo->prepare("SELECT image_path FROM media_sections WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch();

  $pdo->prepare("DELETE FROM media_sections WHERE id=?")->execute([$id]);

  // if stored as relative path and exists, delete
  if ($row && !empty($row['image_path']) && strpos($row['image_path'], 'assets/') === 0) {
    $abs = realpath(__DIR__ . "/../" . $row['image_path']);
    if ($abs && is_file($abs)) @unlink($abs);
  }

  header("Location: media.php"); exit;
}

$items = $pdo->query("
  SELECT * FROM media_sections
  ORDER BY sort_order ASC, id DESC
")->fetchAll();
?>

<style>
  /* admin/media.php-only CSS (no inline styles) */
  .col-w-80{width:80px}
  .col-w-180{width:180px}
  .col-w-120{width:120px}
  .col-w-220{width:220px}
  .quote-maxw{max-width:520px}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h3 class="fw-bold mb-0">Xabarlar bo'limi</h3>
  <a class="btn btn-success" href="media_edit.php">+ Yangi xabar</a>
</div>

<div class="muted mt-2">
  Menyuda faqat aktiv habarlar ko'rinadi, <b>tartib raqam bo'yicha</b>.
</div>

<div class="stats-box mt-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th class="col-w-80">ID</th>
          <th>Quote</th>
          <th class="col-w-180">Muallif</th>
          <th class="col-w-120">Aktiv</th>
          <th class="col-w-120">Tartib</th>
          <th class="col-w-220">Amallar</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="6" class="muted">No media items yet.</td></tr>
      <?php endif; ?>

      <?php foreach ($items as $m): ?>
        <tr>
          <td><?= (int)$m['id'] ?></td>
          <td>
            <div class="fw-bold quote-maxw">
              <?= e(mb_strimwidth((string)$m['quote'], 0, 140, '...','UTF-8')) ?>
            </div>
            <?php if (!empty($m['image_path'])): ?>
              <div class="muted small mt-1"><?= e((string)$m['image_path']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= e((string)$m['author']) ?></td>
          <td><?= (int)$m['is_active'] ? '✅ Yes' : '—' ?></td>
          <td><?= (int)$m['sort_order'] ?></td>
          <td>
            <a class="btn btn-sm btn-outline-secondary" href="media_edit.php?id=<?= (int)$m['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger"
               href="media.php?del=<?= (int)$m['id'] ?>"
               onclick="return confirm('Delete this media item?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php admin_footer(); ?>
