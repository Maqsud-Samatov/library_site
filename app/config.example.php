<?php
declare(strict_types=1);

$DB_HOST = "localhost";
$DB_NAME = "library";
$DB_USER = "";
$DB_PASS = "";       
$DB_PORT = "3306";    

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  die("DB connection failed: " . $e->getMessage());
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
