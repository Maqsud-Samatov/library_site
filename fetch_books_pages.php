<?php

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/partials/api_db.php";

header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 12;

try {
  $total = (int)($pdo->query("SELECT COUNT(*) FROM books")->fetchColumn() ?? 0);
  $totalPages = max(1, (int)ceil($total / $perPage));
  if ($page > $totalPages) $page = $totalPages;

  $offset = ($page - 1) * $perPage;

  $st = $pdo->prepare("
    SELECT b.*, c.name AS category_name
    FROM books b
    JOIN categories c ON c.id=b.category_id
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT :lim OFFSET :off
  ");
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  echo json_encode([
    'success' => true,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => $totalPages,
    'books' => $st->fetchAll(),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'SERVER_ERROR',
    'message' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
