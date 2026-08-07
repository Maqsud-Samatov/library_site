<?php
require_once __DIR__ . "/_layout_top.php";

/**
 * admin/book_edit.php
 * - Add/Edit book
 * - Categories are CHECKBOX (many-to-many via book_categories)
 * - Also writes books.category_id = first selected category (compat)
 */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load categories
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
if (!$cats) {
  admin_header("Books");
  echo '<div class="alert alert-danger">Create at least 1 category first.</div>';
  echo '<a class="btn btn-orange" href="category_edit.php">+ Add Category</a>';
  admin_footer();
  exit;
}

// Default book data
$book = [
  'book_name' => '',
  'book_year' => null,
  'book_author' => '',
  'description' => '',
  'book_cover_path' => '',
  'book_pdf_path' => '',
  'category_id' => (int)$cats[0]['id'], // legacy compat
];

// Load book if editing
if ($id > 0) {
  $st = $pdo->prepare("SELECT * FROM books WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $row = $st->fetch();
  if ($row) $book = array_merge($book, $row);
}

// Selected categories for this book (pivot)
$selectedCatIds = [];
if ($id > 0) {
  $st = $pdo->prepare("SELECT category_id FROM book_categories WHERE book_id=?");
  $st->execute([$id]);
  $selectedCatIds = array_map(fn($r) => (int)$r['category_id'], $st->fetchAll());
} else {
  // default: first category checked
  $selectedCatIds = [(int)$cats[0]['id']];
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Inputs
  $book_name   = trim((string)($_POST['book_name'] ?? ''));
  $book_author = trim((string)($_POST['book_author'] ?? ''));
  $book_year   = trim((string)($_POST['book_year'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));

  // Categories (checkbox)
  $category_ids = $_POST['category_ids'] ?? [];
  $category_ids = array_values(array_unique(array_filter(array_map('intval', (array)$category_ids))));

  if ($book_name === '') {
    $err = "Book name is required.";
  } elseif (!$category_ids) {
    $err = "Choose at least 1 category.";
  } else {
    // Normalize year to satisfy chk_book_year (NULL or 1..2100)
    $yearVal = null;
    if ($book_year !== '') {
      $yearInt = (int)$book_year;
      if ($yearInt > 0 && $yearInt <= 2100) {
        $yearVal = $yearInt;
      } else {
        $yearVal = null; // invalid -> NULL
      }
    }

    // Legacy compat: set books.category_id = first selected category
    $legacy_category_id = (int)$category_ids[0];

    // Keep existing upload paths if not uploading new ones
    $cover_path = (string)($book['book_cover_path'] ?? '');
    $pdf_path   = (string)($book['book_pdf_path'] ?? '');

    // Upload cover (png/jpg/webp)
    if (!empty($_FILES['cover']['name']) && is_uploaded_file($_FILES['cover']['tmp_name'])) {
      $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['png','jpg','jpeg','webp'], true)) {
        $err = "Cover must be png/jpg/jpeg/webp.";
      } else {
        $destDir = __DIR__ . "/../assets/uploads/covers/";
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        $file = "cover_" . time() . "_" . rand(1000,9999) . "." . $ext;
        $dest = $destDir . $file;

        if (!move_uploaded_file($_FILES['cover']['tmp_name'], $dest)) {
          $err = "Cover upload failed.";
        } else {
          $cover_path = "assets/uploads/covers/" . $file;
        }
      }
    }

    // Upload PDF (pdf only)
    if (!$err && !empty($_FILES['pdf']['name']) && is_uploaded_file($_FILES['pdf']['tmp_name'])) {
      $ext = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
      if ($ext !== 'pdf') {
        $err = "Book file must be PDF (.pdf).";
      } else {
        $destDir = __DIR__ . "/../assets/uploads/pdfs/";
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        $file = "book_" . time() . "_" . rand(1000,9999) . ".pdf";
        $dest = $destDir . $file;

        if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)) {
          $err = "PDF upload failed.";
        } else {
          $pdf_path = "assets/uploads/pdfs/" . $file;
        }
      }
    }

    if (!$err) {
      // Save book (transaction)
      $pdo->beginTransaction();
      try {
        if ($id > 0) {
          $st = $pdo->prepare("
            UPDATE books SET
              category_id=?,
              book_name=?,
              book_year=?,
              book_author=?,
              description=?,
              book_cover_path=?,
              book_pdf_path=?
            WHERE id=?
          ");
          $st->execute([
            $legacy_category_id,
            $book_name,
            $yearVal,
            $book_author,
            $description,
            $cover_path,
            $pdf_path,
            $id
          ]);
          $bookId = $id;
        } else {
          $st = $pdo->prepare("
            INSERT INTO books
              (category_id, book_name, book_year, book_author, description, book_cover_path, book_pdf_path)
            VALUES
              (?,?,?,?,?,?,?)
          ");
          $st->execute([
            $legacy_category_id,
            $book_name,
            $yearVal,
            $book_author,
            $description,
            $cover_path,
            $pdf_path
          ]);
          $bookId = (int)$pdo->lastInsertId();
        }

        // Update pivot (book_categories)
        $pdo->prepare("DELETE FROM book_categories WHERE book_id=?")->execute([$bookId]);

        $ins = $pdo->prepare("INSERT INTO book_categories(book_id, category_id) VALUES(?,?)");
        foreach ($category_ids as $cid) {
          $ins->execute([$bookId, (int)$cid]);
        }

        $pdo->commit();
        header("Location: books.php");
        exit;

      } catch (Throwable $e) {
        $pdo->rollBack();
        $err = "Save failed: " . $e->getMessage();
      }
    }
  }

  // keep checked categories after error
  $selectedCatIds = $category_ids ?: $selectedCatIds;

  // keep entered values after error
  $book['book_name'] = $book_name;
  $book['book_author'] = $book_author;
  $book['book_year'] = $book_year;
  $book['description'] = $description;
  $book['book_cover_path'] = $cover_path;
  $book['book_pdf_path'] = $pdf_path;
}

admin_header($id > 0 ? "Edit Book" : "Add Book");
?>

<style>
  /* admin/book_edit.php-only CSS (no inline styles) */
  .bookedit-form{max-width:980px}
  .stats-pad-12{padding:12px}
</style>
<h3 class="fw-bold"><?= $id > 0 ? "Edit Book" : "Add Book" ?></h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php elseif ($msg): ?>
  <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3 bookedit-form">
  <!-- Categories (checkbox) -->
  <div class="col-12">
    <label class="form-label muted">Categories (choose multiple)</label>
    <div class="stats-box stats-pad-12">
      <div class="row g-2">
        <?php foreach ($cats as $c): $cid=(int)$c['id']; ?>
          <div class="col-12 col-md-6">
            <label class="d-flex align-items-center gap-2">
              <input type="checkbox" name="category_ids[]" value="<?= $cid ?>"
                <?= in_array($cid, $selectedCatIds, true) ? 'checked' : '' ?>>
              <span><?= e($c['name']) ?></span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="muted small mt-2">Tip: one book can appear in multiple categories.</div>
    </div>
  </div>

  <div class="col-12 col-md-8">
    <label class="form-label muted">Book name</label>
    <input class="form-control" name="book_name" value="<?= e((string)$book['book_name']) ?>" required>
  </div>

  <div class="col-12 col-md-4">
    <label class="form-label muted">Year (1..2100)</label>
    <input class="form-control" name="book_year" value="<?= e((string)($book['book_year'] ?? '')) ?>" placeholder="e.g. 2023">
  </div>

  <div class="col-12">
    <label class="form-label muted">Author</label>
    <input class="form-control" name="book_author" value="<?= e((string)$book['book_author']) ?>">
  </div>

  <div class="col-12">
    <label class="form-label muted">Description</label>
    <textarea class="form-control" name="description" rows="4"><?= e((string)$book['description']) ?></textarea>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label muted">Cover (png/jpg/webp)</label>
    <input class="form-control" type="file" name="cover" accept=".png,.jpg,.jpeg,.webp">
    <?php if (!empty($book['book_cover_path'])): ?>
      <div class="muted small mt-2">Current: <?= e((string)$book['book_cover_path']) ?></div>
    <?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label muted">PDF (only .pdf)</label>
    <input class="form-control" type="file" name="pdf" accept=".pdf">
    <?php if (!empty($book['book_pdf_path'])): ?>
      <div class="muted small mt-2">Current: <?= e((string)$book['book_pdf_path']) ?></div>
    <?php endif; ?>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-success">Save</button>
    <a class="btn btn-danger" href="books.php">Cancel</a>
  </div>
</form>

<?php admin_footer(); ?>
