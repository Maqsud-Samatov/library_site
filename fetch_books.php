<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/app/config.php";
require_once __DIR__ . "/app/functions.php";

$activeCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$q = mb_substr($q, 0, 120); // safety

try {
  $params = [];
  $sql = "
    SELECT DISTINCT b.*
    FROM books b
    LEFT JOIN book_categories bc ON bc.book_id = b.id
    WHERE 1
  ";

  if ($activeCat > 0) {
    $sql .= " AND bc.category_id = ? ";
    $params[] = $activeCat;
  }

  if ($q !== '') {
    $sql .= " AND b.book_name LIKE ? ";
    $params[] = '%' . $q . '%';
  }

  $sql .= " ORDER BY b.created_at DESC ";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $books = $st->fetchAll();

  ob_start();
  if ($books) {
    foreach ($books as $b) {
      ?>
      <div class="col-6 col-md-4 col-xl-3">
        <a class="card-book d-block" href="book.php?id=<?= (int)$b['id'] ?>">
          <div class="cover cover-h240">
            <?php if (!empty($b['book_cover_path'])): ?>
              <img src="<?= e($b['book_cover_path']) ?>" alt="cover">
            <?php else: ?>
              <span class="no-cover">NO COVER</span>
            <?php endif; ?>
          </div>
          <div class="p">
            <div class="fw-bold clamp-2"><?= e($b['book_name']) ?></div>
            <div class="muted small clamp-1"><?= e($b['book_author'] ?? 'Unknown') ?></div>
          </div>
        </a>
      </div>
      <?php
    }
  } else {
    ?>
    <div class="col-12">
      <div class="stats-box">Topilmadi.</div>
    </div>
    <?php
  }

  $html = ob_get_clean();

  echo json_encode([
    'success' => true,
    'count'   => is_array($books) ? count($books) : 0,
    'html'    => $html,
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
