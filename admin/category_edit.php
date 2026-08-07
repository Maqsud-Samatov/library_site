<?php
require_once __DIR__ . "/_layout_top.php";

/** Check if a column exists */
function col_exists(PDO $pdo, string $table, string $col): bool {
  $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
  $st->execute([$col]);
  return (bool)$st->fetch();
}

$hasDescription = col_exists($pdo, 'categories', 'description');

/** Make slug unique by appending -2, -3... */
function unique_slug(PDO $pdo, string $slug, int $ignoreId = 0): string {
  $base = $slug;
  $i = 1;

  while (true) {
    if ($ignoreId > 0) {
      $st = $pdo->prepare("SELECT id FROM categories WHERE slug=? AND id<>? LIMIT 1");
      $st->execute([$slug, $ignoreId]);
    } else {
      $st = $pdo->prepare("SELECT id FROM categories WHERE slug=? LIMIT 1");
      $st->execute([$slug]);
    }
    if (!$st->fetch()) return $slug;

    $i++;
    $slug = $base . "-" . $i;
  }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$cat = ['name'=>'','slug'=>'','description'=>''];
if ($id) {
  $st = $pdo->prepare("SELECT * FROM categories WHERE id=?");
  $st->execute([$id]);
  $cat = $st->fetch() ?: $cat;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string)($_POST['name'] ?? ''));
  $slug = trim((string)($_POST['slug'] ?? ''));
  $desc = trim((string)($_POST['description'] ?? ''));

  if ($name === '') {
    $error = "Name required.";
  } else {
    if ($slug === '') $slug = slugify($name);
    $slug = unique_slug($pdo, $slug, $id);

    try {
      if ($id) {
        if ($hasDescription) {
          $st = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=? WHERE id=?");
          $st->execute([$name, $slug, $desc, $id]);
        } else {
          $st = $pdo->prepare("UPDATE categories SET name=?, slug=? WHERE id=?");
          $st->execute([$name, $slug, $id]);
        }
      } else {
        if ($hasDescription) {
          $st = $pdo->prepare("INSERT INTO categories(name, slug, description) VALUES(?,?,?)");
          $st->execute([$name, $slug, $desc]);
        } else {
          $st = $pdo->prepare("INSERT INTO categories(name, slug) VALUES(?,?)");
          $st->execute([$name, $slug]);
        }
        $id = (int)$pdo->lastInsertId();
      }

      header("Location: categories.php");
      exit;

    } catch (Throwable $e) {
      // Show real DB error (so you immediately know what's wrong)
      $error = "DB error: " . $e->getMessage();
    }
  }

  // keep values after error
  $cat['name'] = $name;
  $cat['slug'] = $slug;
  $cat['description'] = $desc;
}

admin_header($id ? "Edit Category" : "Add Category");
?>

<style>
  /* admin/category_edit.php-only CSS (no inline styles) */
  .catedit-form{max-width:800px}
</style>
<h3 class="fw-bold"><?= $id ? "Edit Category" : "Add Category" ?></h3>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php elseif ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<form method="post" class="row g-3 catedit-form">
  <div class="col-12">
    <label class="form-label muted">Name</label>
    <input class="form-control" name="name" value="<?= e((string)$cat['name']) ?>" required>
  </div>

  <div class="col-12">
    <label class="form-label muted">Slug (auto if empty)</label>
    <input class="form-control" name="slug" value="<?= e((string)$cat['slug']) ?>">
    <div class="muted small mt-1">Slug must be unique. If duplicate, we auto add -2, -3...</div>
  </div>

  <?php if ($hasDescription): ?>
    <div class="col-12">
      <label class="form-label muted">Description</label>
      <textarea class="form-control" name="description" rows="4"><?= e((string)($cat['description'] ?? '')) ?></textarea>
    </div>
  <?php else: ?>
    <div class="col-12">
      <div class="stats-box">
        <div class="muted">
          Note: Your <code>categories</code> table has no <code>description</code> column, so description is disabled.
          If you want it, run:
          <div class="mt-2"><code>ALTER TABLE categories ADD COLUMN description TEXT NULL;</code></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-success">Save</button>
    <a class="btn btn-danger" href="categories.php">Cancel</a>
  </div>
</form>

<?php admin_footer(); ?>
