<?php
require_once __DIR__ . "/partials/header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); die("Not found"); }

$st = $pdo->prepare("
  SELECT b.id, b.book_name, b.book_pdf_path
  FROM books b
  WHERE b.id=?
  LIMIT 1
");
$st->execute([$id]);
$book = $st->fetch();
if (!$book || empty($book['book_pdf_path'])) { http_response_code(404); die("PDF topilmadi"); }
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <a class="btn btn-success" href="book.php?id=<?= (int)$book['id'] ?>">← Orqaga</a>
  <div class="fw-bold"><?= e($book['book_name']) ?></div>
  <a class="btn btn-info" href="category_list.php">Kategoriyalar</a>
</div>

<div class="pdf-fullwrap">
  <iframe
    class="pdf-full"
    title="PDF o‘quvchi"
    src="view_pdf.php?id=<?= (int)$book['id'] ?>#toolbar=0&navpanes=0&scrollbar=1"
    loading="lazy"
  ></iframe>
</div>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
