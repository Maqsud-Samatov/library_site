<?php
declare(strict_types=1);

require_once __DIR__ . "/app/config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$st = $pdo->prepare("SELECT book_pdf_path FROM books WHERE id=? LIMIT 1");
$st->execute([$id]);
$row = $st->fetch();
if (!$row || empty($row['book_pdf_path'])) { http_response_code(404); exit('Not found'); }

$path = (string)$row['book_pdf_path'];
$path = ltrim($path, '/'); // normalize

$baseDir = realpath(__DIR__ . "/assets/uploads/pdfs");
$file = realpath(__DIR__ . "/" . $path);

if (!$baseDir || !$file || strpos($file, $baseDir) !== 0 || !is_file($file)) {
  http_response_code(403);
  exit('Forbidden');
}

// Stream inline (no "download" response header)
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="book.pdf"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$size = filesize($file);
if ($size !== false) header('Content-Length: ' . $size);

readfile($file);
exit;

