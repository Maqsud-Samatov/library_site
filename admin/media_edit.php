<?php
require_once __DIR__ . "/_layout_top.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$item = [
  'title' => '',
  'quote' => '',
  'author' => '',
  'image_path' => '',
  'is_active' => 1,
  'sort_order' => 0,
];

if ($id) {
  $st = $pdo->prepare("SELECT * FROM media_sections WHERE id=?");
  $st->execute([$id]);
  $item = $st->fetch() ?: $item;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $quote = trim((string)($_POST['quote'] ?? ''));
  $author = trim((string)($_POST['author'] ?? ''));
  $is_active = isset($_POST['is_active']) ? 1 : 0;
  $sort_order = (int)($_POST['sort_order'] ?? 0);

  if ($quote === '') $err = "Quote is required.";
  elseif ($author === '') $err = "Author is required.";

  $image_path = (string)($item['image_path'] ?? '');

  // upload image
  if (!$err && !empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      $err = "Image must be jpg/jpeg/png/webp.";
    } else {
      $dir = __DIR__ . "/../assets/uploads/media/";
      if (!is_dir($dir)) mkdir($dir, 0777, true);
      $fname = "media_" . time() . "_" . rand(1000,9999) . "." . $ext;
      $dest = $dir . $fname;

      if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        $err = "Image upload failed.";
      } else {
        $image_path = "assets/uploads/media/" . $fname;
      }
    }
  }

  if (!$err) {
    if ($id) {
      $st = $pdo->prepare("
        UPDATE media_sections
        SET title=?, quote=?, author=?, image_path=?, is_active=?, sort_order=?
        WHERE id=?
      ");
      $st->execute([$title, $quote, $author, $image_path, $is_active, $sort_order, $id]);
    } else {
      $st = $pdo->prepare("
        INSERT INTO media_sections(title, quote, author, image_path, is_active, sort_order)
        VALUES(?,?,?,?,?,?)
      ");
      $st->execute([$title, $quote, $author, $image_path, $is_active, $sort_order]);
    }

    header("Location: media.php"); exit;
  }

  // keep values after error
  $item['title'] = $title;
  $item['quote'] = $quote;
  $item['author'] = $author;
  $item['is_active'] = $is_active;
  $item['sort_order'] = $sort_order;
  $item['image_path'] = $image_path;
}

admin_header($id ? "Edit Media" : "Add Media");
?>

<style>
  /* admin/media_edit.php-only CSS (no inline styles) */
  .mediaedit-form{max-width:900px}
  .preview-img{max-width:320px;border-radius:12px;border:1px solid #dee2e6;display:block}
</style>
<h3 class="fw-bold"><?= $id ? "Edit Media" : "Add Media" ?></h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3 mediaedit-form">
  <div class="col-12">
    <label class="form-label muted">Title (optional)</label>
    <input class="form-control" name="title" value="<?= e((string)$item['title']) ?>">
  </div>

  <div class="col-12">
    <label class="form-label muted">Quote</label>
    <textarea class="form-control" name="quote" rows="4" required><?= e((string)$item['quote']) ?></textarea>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label muted">Author</label>
    <input class="form-control" name="author" value="<?= e((string)$item['author']) ?>" required>
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
    <a class="btn btn-danger" href="media.php">Cancel</a>
  </div>
</form>

<?php admin_footer(); ?>
