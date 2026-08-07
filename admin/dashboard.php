<?php
require_once __DIR__ . "/_layout_top.php";
admin_header("Dashboard");

$counts = $pdo->query("
  SELECT
    (SELECT COUNT(*) FROM categories) AS categories_cnt,
    (SELECT COUNT(*) FROM books) AS books_cnt
")->fetch();
?>
<link href="../assets/css/style.css" rel="stylesheet">
<h3 class="fw-bold">Dashboard</h3>

<div class="row g-3 mt-1">
  <div class="col-12 col-md-4">
    <div class="stats-box d-flex justify-content-between">
      <div class="fw-bold">Kategoriyalar</div>
      <div class="orange fw-bold"><?= (int)$counts['categories_cnt'] ?></div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stats-box d-flex justify-content-between">
      <div class="fw-bold">Kitoblar</div>
      <div class="orange fw-bold"><?= (int)$counts['books_cnt'] ?></div>
    </div>
  </div>
</div>

<div class="stats-box mt-3">
  Yondagi menyudan foydalanib kategoriyalar qo'shing, kitoblar qo'shing va hokazo....
</div>

<?php admin_footer(); ?>
