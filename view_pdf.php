<?php
declare(strict_types=1);

// PDF faylni faqat sayt ichidagi o'quvchiga (pdf.php) beradi:
// - faqat POST + X-PDF-Reader sarlavhasi bilan (manzilni brauzerda ochib bo'lmaydi);
// - faqat pdf.php bergan bir martalik, 5 daqiqalik chipta bilan;
// - fayl chipta kaliti bilan shifrlangan holda uzatiladi (tutib olinsa ham PDF bo'lib ochilmaydi).

require_once __DIR__ . "/app/config.php";
require_once __DIR__ . "/app/pdf_guard.php";

function deny(int $code, string $message): void {
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  header('Cache-Control: no-store');
  exit($message);
}

$fetchDest = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'empty';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || ($_SERVER['HTTP_X_PDF_READER'] ?? '') !== '1'
    || $fetchDest !== 'empty') {
  deny(403, "Kitobni faqat sayt ichida o'qish mumkin.");
}

$id = (int)($_POST['id'] ?? 0);
$key = pdf_take_ticket((string)($_POST['t'] ?? ''), $id);
session_write_close(); // katta fayl uzatilayotganda sessiya boshqa so'rovlarni to'sib qo'ymasin
if ($key === null) {
  deny(403, "Havola eskirgan. Sahifani yangilang.");
}

$st = $pdo->prepare("SELECT book_pdf_path FROM books WHERE id=? LIMIT 1");
$st->execute([$id]);
$row = $st->fetch();
if (!$row || empty($row['book_pdf_path'])) {
  deny(404, 'Not found');
}

$path = ltrim((string)$row['book_pdf_path'], '/');
$baseDir = realpath(__DIR__ . "/assets/uploads/pdfs");
$file = realpath(__DIR__ . "/" . $path);
if (!$baseDir || !$file || strpos($file, $baseDir) !== 0 || !is_file($file)) {
  deny(403, 'Forbidden');
}

$size = filesize($file);
$fh = fopen($file, 'rb');
if ($size === false || !$fh) {
  deny(500, "Faylni o'qib bo'lmadi");
}

set_time_limit(0);
while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/octet-stream');
header('Content-Length: ' . $size);
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');

// Kalit bilan XOR — bo'laklab, har bo'lak kalitning to'g'ri joyidan davom etadi
$keyLen = strlen($key);
$keyStream = str_repeat($key, 17);
$chunkSize = $keyLen * 16;
$pos = 0;
while (!feof($fh)) {
  $chunk = fread($fh, $chunkSize);
  if ($chunk === false || $chunk === '') break;
  $len = strlen($chunk);
  echo $chunk ^ substr($keyStream, $pos % $keyLen, $len);
  $pos += $len;
  flush();
}
fclose($fh);
exit;
