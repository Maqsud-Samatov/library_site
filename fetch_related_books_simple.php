<?php
declare(strict_types=1);

require_once __DIR__ . "/partials/api_db.php";
header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 6);
$perPage = max(1, min(60, $perPage));

$catId = (int)($_GET['category_id'] ?? 0);
$excludeId = (int)($_GET['exclude_id'] ?? 0);

if ($catId <= 0) {
  echo json_encode(['success'=>false,'error'=>'BAD_REQUEST','message'=>'category_id required'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $params = [':cat' => $catId];
  $whereEx = "";

  if ($excludeId > 0) {
    $whereEx = " AND b.id <> :ex ";
    $params[':ex'] = $excludeId;
  }

  $stTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM books b
    WHERE b.category_id = :cat
    $whereEx
  ");
  foreach ($params as $k => $v) $stTotal->bindValue($k, $v, PDO::PARAM_INT);
  $stTotal->execute();
  $total = (int)($stTotal->fetchColumn() ?? 0);

  $totalPages = max(1, (int)ceil($total / $perPage));
  if ($page > $totalPages) $page = $totalPages;
  $offset = ($page - 1) * $perPage;

  $st = $pdo->prepare("
    SELECT id, book_name, book_author, book_year, book_cover_path
    FROM books b
    WHERE b.category_id = :cat
    $whereEx
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT :lim OFFSET :off
  ");
  foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_INT);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  echo json_encode([
    'success' => true,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => $totalPages,
    'books' => $st->fetchAll(PDO::FETCH_ASSOC),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'SERVER_ERROR',
    'message' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
