<?php
declare(strict_types=1);

// PDF ni faqat sayt ichidagi o'quvchida ko'rsatish uchun yordamchilar.
// pdf.php bir martalik chipta (token + kalit) beradi, view_pdf.php faylni faqat
// shu chipta bilan, kalit bilan shifrlangan holda yuboradi.

const PDF_TICKET_TTL  = 300;   // chipta amal qilish muddati, soniya
const PDF_KEY_BYTES   = 4096;  // shifrlash kaliti uzunligi, bayt
const PDF_RATE_LIMIT  = 60;    // bitta IP dan soatiga ochiladigan kitoblar soni
const PDF_RATE_WINDOW = 3600;  // cheklov oynasi, soniya

function pdf_issue_ticket(int $bookId): array {
  $now = time();
  $tickets = array_filter(
    $_SESSION['pdf_tickets'] ?? [],
    fn(array $t): bool => $t['exp'] > $now
  );

  $token = bin2hex(random_bytes(16));
  $key = base64_encode(random_bytes(PDF_KEY_BYTES));
  $tickets[$token] = ['book' => $bookId, 'key' => $key, 'exp' => $now + PDF_TICKET_TTL];
  $_SESSION['pdf_tickets'] = $tickets;

  return ['token' => $token, 'key' => $key];
}

// Chiptani ishlatadi (bir martalik) va kalitni qaytaradi; yaroqsiz bo'lsa null
function pdf_take_ticket(string $token, int $bookId): ?string {
  $ticket = $_SESSION['pdf_tickets'][$token] ?? null;
  unset($_SESSION['pdf_tickets'][$token]);

  if (!$ticket || $ticket['book'] !== $bookId || $ticket['exp'] < time()) {
    return null;
  }
  return base64_decode($ticket['key'], true) ?: null;
}

// Bitta IP dan juda ko'p kitob ochilsa true (skript bilan ommaviy yuklab olishga qarshi)
function pdf_rate_limited(): bool {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $file = sys_get_temp_dir() . '/pdf_rate_' . hash('sha256', $ip) . '.json';
  $fh = @fopen($file, 'c+');
  if (!$fh) return false;

  flock($fh, LOCK_EX);
  $now = time();
  $hits = json_decode((string)stream_get_contents($fh), true);
  $hits = array_values(array_filter(
    is_array($hits) ? $hits : [],
    fn($t): bool => is_int($t) && $t > $now - PDF_RATE_WINDOW
  ));

  $limited = count($hits) >= PDF_RATE_LIMIT;
  if (!$limited) $hits[] = $now;

  ftruncate($fh, 0);
  rewind($fh);
  fwrite($fh, json_encode($hits));
  flock($fh, LOCK_UN);
  fclose($fh);

  return $limited;
}
