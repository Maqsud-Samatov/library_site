<?php
require_once __DIR__ . "/partials/header.php";

$cats = $pdo->query("
  SELECT c.*, COUNT(b.id) AS cnt
  FROM categories c
  LEFT JOIN books b ON b.category_id=c.id
  GROUP BY c.id
  ORDER BY c.name ASC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
  <div>
    <div class="section-title">Kategoriyalar</div>
    <div class="muted">Kerakli yo‘nalishni tanlang va kitoblarni ko‘ring</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php">Bosh sahifa</a>
</div>

<div class="row g-3">
  <?php foreach ($cats as $c): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <a class="stats-box d-block" href="categories.php?cat=<?= (int)$c['id'] ?>">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-bold"><?= e($c['name']) ?></div>
          <div class="badge text-bg-light"><?= (int)$c['cnt'] ?> ta</div>
        </div>
        <?php if (!empty($c['description'])): ?>
          <div class="muted small mt-2 clamp-2"><?= e($c['description']) ?></div>
        <?php endif; ?>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php
$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
if ($cat_id > 0) {
  $st = $pdo->prepare("SELECT * FROM categories WHERE id=?");
  $st->execute([$cat_id]);
  $cat = $st->fetch();

  $st = $pdo->prepare("
    SELECT b.*, c.name AS category_name
    FROM books b
    JOIN categories c ON c.id=b.category_id
    WHERE b.category_id=?
    ORDER BY b.created_at DESC
  ");
  $st->execute([$cat_id]);
  $books = $st->fetchAll();
  ?>
  <div class="section-title mt-4">Kategoriyadagi kitoblar: <?= e($cat['name'] ?? 'Category') ?></div>
  <div class="row g-3">
    <?php foreach ($books as $b): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a class="card-book d-block" href="book.php?id=<?= (int)$b['id'] ?>">
          <div class="cover">
            <?php if (!empty($b['book_cover_path'])): ?>
              <img src="<?= e($b['book_cover_path']) ?>" alt="cover">
            <?php else: ?>
              <img src="https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=700&q=80" alt="Book cover placeholder">
            <?php endif; ?>
          </div>
          <div class="p">
            <div class="fw-bold"><?= e($b['book_name']) ?></div>
            <div class="muted small"><?= e($b['book_author'] ?? 'Unknown') ?></div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
    <?php if (!$books): ?>
      <div class="col-12"><div class="stats-box">Bu kategoriyada hozircha kitob mavjud emas.</div></div>
    <?php endif; ?>
  </div>
<?php } ?>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
