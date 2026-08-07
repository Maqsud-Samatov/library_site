<?php
declare(strict_types=1);

require_once __DIR__ . "/partials/api_db.php";
header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 8);         // ✅ 2 row (lg=4 col) = 8
$perPage = max(1, min(40, $perPage));

$catId = (int)($_GET['cat_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));

if ($catId <= 0) {
  echo json_encode([
    'success' => false,
    'error' => 'BAD_REQUEST',
    'message' => 'cat_id is required',
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // ✅ pivot: book_categories (book_id, category_id)
  $whereQ = "";
  $params = [':cat' => $catId];

  if ($q !== '') {
    $whereQ = " AND (b.book_name LIKE :q OR b.book_author LIKE :q) ";
    $params[':q'] = '%' . $q . '%';
  }

  // TOTAL
  $stTotal = $pdo->prepare("
    SELECT COUNT(DISTINCT b.id)
    FROM books b
    INNER JOIN book_categories bc ON bc.book_id = b.id
    WHERE bc.category_id = :cat
    $whereQ
  ");
  $stTotal->execute($params);
  $total = (int)($stTotal->fetchColumn() ?? 0);

  $totalPages = max(1, (int)ceil($total / $perPage));
  if ($page > $totalPages) $page = $totalPages;

  $offset = ($page - 1) * $perPage;

  // DATA
  $st = $pdo->prepare("
    SELECT
      b.id,
      b.book_name,
      b.book_author,
      b.book_year,
      b.book_cover_path,
      COALESCE(b.rating_avg, 0) AS rating_avg,
      COALESCE(b.rating_count, 0) AS rating_count,
      COALESCE(b.read_count, 0) AS read_count
    FROM books b
    INNER JOIN book_categories bc ON bc.book_id = b.id
    WHERE bc.category_id = :cat
    $whereQ
    GROUP BY b.id
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT :lim OFFSET :off
  ");

  // bind params
  foreach ($params as $k => $v) {
    $st->bindValue($k, $v);
  }
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);

  $st->execute();
  $books = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => $totalPages,
    'books' => $books,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'SERVER_ERROR',
    'message' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
