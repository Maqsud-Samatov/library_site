<?php
declare(strict_types=1);

// Kitobni sayt ichida o'qish sahifasi. PDF brauzerning o'z ko'rsatgichida emas,
// PDF.js bilan sahifama-sahifa chiziladi: yuklab olish va chop etish tugmalari yo'q.
// Fayl view_pdf.php dan bir martalik chipta bilan, shifrlangan holda olinadi.

require_once __DIR__ . "/app/config.php";
require_once __DIR__ . "/app/functions.php";
require_once __DIR__ . "/app/pdf_guard.php";

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

$limited = pdf_rate_limited();
$ticket = $limited ? null : pdf_issue_ticket((int)$book['id']);

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex');
if ($limited) http_response_code(429);

require_once __DIR__ . "/partials/header.php";
?>

<style>
  /* Chop etib bo'lmasin */
  @media print { html, body { display: none !important; } }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-3">
  <a class="btn btn-success" href="book.php?id=<?= (int)$book['id'] ?>">← Orqaga</a>
  <div class="fw-bold"><?= e($book['book_name']) ?></div>
  <a class="btn btn-info" href="category_list.php">Kategoriyalar</a>
</div>

<?php if ($limited): ?>
  <div class="stats-box">Juda ko'p kitob ochildi. Iltimos, birozdan keyin qayta urinib ko'ring.</div>
<?php else: ?>
  <div class="pdf-reader" id="pdfReader"
       data-id="<?= (int)$book['id'] ?>"
       data-token="<?= e($ticket['token']) ?>"
       data-key="<?= e($ticket['key']) ?>">
    <div class="pdf-reader-bar">
      <div class="pdf-reader-pageinfo" data-page-info>— / —</div>
      <div class="pdf-reader-zoom">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-zoom="-1" aria-label="Kichiklashtirish">−</button>
        <span data-zoom-info>100%</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-zoom="1" aria-label="Kattalashtirish">+</button>
      </div>
    </div>
    <div class="pdf-reader-status" data-status>Yuklanmoqda…</div>
    <div class="pdf-reader-pages" data-pages></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"
          integrity="sha384-/1qUCSGwTur9vjf/z9lmu/eCUYbpOTgSjmpbMQZ1/CtX2v/WcAIKqRv+U1DUCG6e"
          crossorigin="anonymous"></script>
  <script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js";
  </script>
  <script src="assets/js/pdf_reader.js?v=<?= filemtime(__DIR__ . '/assets/js/pdf_reader.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
