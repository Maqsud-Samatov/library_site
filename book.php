<?php
require_once __DIR__ . "/partials/header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); die("Not found"); }
//saasdawdasdadawd
$st = $pdo->prepare("
  SELECT b.*, c.name AS category_name
  FROM books b
  JOIN categories c ON c.id=b.category_id
  WHERE b.id=?
  LIMIT 1
");
$st->execute([$id]);
$book = $st->fetch();
if (!$book) { http_response_code(404); die("Not found"); }

$bookCover = !empty($book['book_cover_path'])
  ? $book['book_cover_path']
  : 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=1000&q=80';


$vh = visitor_hash();
// unique read per visitor
try {
  $ins = $pdo->prepare("INSERT INTO book_reads(book_id, visitor_hash) VALUES(?,?)");
  $ins->execute([$id, $vh]);

  $pdo->prepare("UPDATE books SET read_count = read_count + 1 WHERE id=?")->execute([$id]);
  $book['read_count'] = (int)$book['read_count'] + 1;
} catch (Throwable $e) {
  // duplicate unique read -> ignore
}
?>


<div style="margin-top: 10px;" class="row g-4">
  <div class="col-12 col-md-4">
    <div class="card-book">
      <div class="cover">
        <img src="<?= e($bookCover) ?>" alt="cover">
      </div>
    </div>
  </div>

  <div class="col-12 col-md-8">
    <div class="badge-cat mb-2"><?= e($book['category_name']) ?></div>
    <h2 class="fw-bold mb-1"><?= e($book['book_name']) ?></h2>
    <div class="muted">
      <?= e($book['book_author'] ?? 'Unknown') ?>
      <?php if (!empty($book['book_year'])): ?> · <?= (int)$book['book_year'] ?><?php endif; ?>
    </div>

    <div class="d-flex gap-3 mt-3 small muted">
      <div>⭐ <?= number_format((float)$book['rating_avg'], 2) ?> (<?= (int)$book['rating_count'] ?>)</div>
      <div>👁 <?= (int)$book['read_count'] ?> reads</div>
    </div>

    <?php if (!empty($book['description'])): ?>
      <div class="mt-3"><?= nl2br(e($book['description'])) ?></div>
    <?php endif; ?>

    <div class="mt-4 d-flex gap-2 flex-wrap">
      <?php if (!empty($book['book_pdf_path'])): ?>
        <a class="btn btn-success"
           href="pdf.php?id=<?= (int)$book['id'] ?>"
   	   target="_blank" rel="noopener">
           O‘qish
        </a>

      <?php else: ?>
        <div class="stats-box">PDF yuklanmagan.</div>
      <?php endif; ?>
      <a class="btn btn-info" href="category_list.php">Kategoriyalarga qaytish</a>
    </div>

    <!-- ✅ RECOMMENDATIONS (AJAX + pagination) -->
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mt-4">
      <div class="section-title m-0">Shu kategoriyadan boshqa kitoblar</div>
      <!-- category_list dagidek -->
      <a class="btn btn-success" href="category_list.php">← Orqaga</a>
    </div>

    <div class="row g-3 mt-2" id="recGrid" aria-live="polite">
      <div class="col-12">
        <div class="stats-box">Yuklanmoqda...</div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
      <button type="button" class="btn btn-success" id="recPrev">oldingi</button>
      <div class="muted small"><span id="recIndicator">1 / 1</span></div>
      <button type="button" class="btn btn-success" id="recNext">keyingi</button>
    </div>

  </div>
</div>

<script>
  window.__BOOK_ID__ = <?= (int)$book['id'] ?>;
  window.__BOOK_CAT_ID__ = <?= (int)$book['category_id'] ?>;
</script>
<script src="assets/js/book_recs.js?v=<?= filemtime(__DIR__ . '/assets/js/book_recs.js') ?>"></script>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
