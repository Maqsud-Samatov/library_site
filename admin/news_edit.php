<?php
require_once __DIR__ . "/_layout_top.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$item = [
  'title' => '',
  'body' => '',
  'image_path' => '',
  'link_url' => '',
  'is_active' => 1,
  'sort_order' => 0,
];

if ($id) {
  $st = $pdo->prepare("SELECT * FROM news WHERE id=?");
  $st->execute([$id]);
  $item = $st->fetch() ?: $item;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $body = trim((string)($_POST['body'] ?? ''));
  $link_url = trim((string)($_POST['link_url'] ?? ''));
  $is_active = isset($_POST['is_active']) ? 1 : 0;
  $sort_order = (int)($_POST['sort_order'] ?? 0);

  if ($title === '') $err = "Title is required.";
  elseif ($body === '') $err = "Body text is required.";

  $image_path = (string)($item['image_path'] ?? '');

  // upload image
  if (!$err && !empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      $err = "Image must be jpg/jpeg/png/webp.";
    } else {
      $dir = __DIR__ . "/../assets/uploads/news/";
      if (!is_dir($dir)) mkdir($dir, 0777, true);
      $fname = "news_" . time() . "_" . rand(1000,9999) . "." . $ext;
      $dest = $dir . $fname;

      if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        $err = "Image upload failed.";
      } else {
        $image_path = "assets/uploads/news/" . $fname;
      }
    }
  }

  // optional: validate link url lightly
  if (!$err && $link_url !== '' && !preg_match('~^https?://~i', $link_url)) {
    $err = "Link URL must start with http:// or https:// (or leave empty).";
  }

  if (!$err) {
    if ($id) {
      $st = $pdo->prepare("
        UPDATE news
        SET title=?, body=?, image_path=?, link_url=?, is_active=?, sort_order=?
        WHERE id=?
      ");
      $st->execute([$title, $body, $image_path, $link_url, $is_active, $sort_order, $id]);
    } else {
      $st = $pdo->prepare("
        INSERT INTO news(title, body, image_path, link_url, is_active, sort_order)
        VALUES(?,?,?,?,?,?)
      ");
      $st->execute([$title, $body, $image_path, $link_url, $is_active, $sort_order]);
    }

    header("Location: news.php"); exit;
  }

  // keep values after error
  $item['title'] = $title;
  $item['body'] = $body;
  $item['link_url'] = $link_url;
  $item['is_active'] = $is_active;
  $item['sort_order'] = $sort_order;
  $item['image_path'] = $image_path;
}

admin_header($id ? "Edit News" : "Add News");
?>

<style>
  /* admin/news_edit.php-only CSS (no inline styles) */
  .newsedit-form{max-width:900px}
  .preview-img{max-width:320px;border-radius:12px;border:1px solid #dee2e6;display:block}
</style>
<h3 class="fw-bold"><?= $id ? "Edit News" : "Add News" ?></h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3 newsedit-form">
  <div class="col-12">
    <label class="form-label muted">Title</label>
    <input class="form-control" name="title" value="<?= e((string)$item['title']) ?>" required>
  </div>

  <div class="col-12">
    <label class="form-label muted">Body</label>
    <textarea class="form-control" name="body" rows="5" required><?= e((string)$item['body']) ?></textarea>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label muted">Link URL (optional)</label>
    <input class="form-control" name="link_url" value="<?= e((string)$item['link_url']) ?>" placeholder="https://...">
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label muted">Sort order</label>
    <input class="form-control" type="number" name="sort_order" value="<?= (int)$item['sort_order'] ?>">
  </div>

  <div class="col-12 col-md-3 d-flex align-items-end">
    <label class="d-flex align-items-center gap-2">
      <input type="checkbox" name="is_active" <?= (int)$item['is_active'] ? 'checked' : '' ?>>
      <span class="muted">Active</span>
    </label>
  </div>

  <div class="col-12">
    <label class="form-label muted">Image (jpg/png/webp)</label>
    <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

    <?php if (!empty($item['image_path'])): ?>
      <div class="muted small mt-2">Current: <?= e((string)$item['image_path']) ?></div>
      <div class="mt-2">
        <img class="preview-img" src="../<?= e((string)$item['image_path']) ?>" alt="preview">
      </div>
    <?php endif; ?>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-success">Save</button>
    <a class="btn btn-danger" href="news.php">Cancel</a>
  </div>
</form>

<?php admin_footer(); ?>
