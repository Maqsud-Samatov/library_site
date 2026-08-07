<?php
declare(strict_types=1);

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function get_setting(PDO $pdo, string $key, string $default = ''): string {
  $st = $pdo->prepare("SELECT value FROM settings WHERE `key`=? LIMIT 1");
  $st->execute([$key]);
  $row = $st->fetch();
  return $row ? (string)$row['value'] : $default;
}

function set_setting(PDO $pdo, string $key, string $value): void {
  $st = $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?)
    ON DUPLICATE KEY UPDATE value=VALUES(value)");
  $st->execute([$key, $value]);
}

function get_json_setting(PDO $pdo, string $key, array $default = []): array {
  $raw = get_setting($pdo, $key, '');
  if (!$raw) return $default;
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : $default;
}

function slugify(string $s): string {
  $s = trim(mb_strtolower($s));
  $s = preg_replace('/[^a-z0-9\p{L}]+/u', '-', $s);
  $s = trim($s, '-');
  return $s ?: 'item';
}

function require_admin(): void {
  if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
  }
}
function admin_user(): ?array {
  return $_SESSION['admin_user'] ?? null; // sizda sessiya qanday saqlansa shunga moslang
}

function is_admin_role(string $role): bool {
  $u = admin_user();
  return $u && ($u['role'] ?? '') === $role;
}

function require_role(string $role): void {
  if (!is_admin_role($role)) {
    http_response_code(403);
    die("403 Forbidden");
  }
}

function visitor_hash(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  return hash('sha256', $ip . '|' . $ua);
}
