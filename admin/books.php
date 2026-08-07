<?php
require_once __DIR__ . "/_layout_top.php";
admin_header("Books");

/* ===== Delete ===== */
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $pdo->prepare("DELETE FROM books WHERE id=?")->execute([$id]);

  // preserve filters
  $redir = "books.php";
  $qs = [];
  if (isset($_GET['cat'])) $qs[] = "cat=".(int)$_GET['cat'];
  if (isset($_GET['q']) && trim((string)$_GET['q']) !== "") $qs[] = "q=".urlencode(trim((string)$_GET['q']));
  if ($qs) $redir .= "?".implode("&", $qs);

  header("Location: $redir");
  exit;
}

/* ===== Filters ===== */
$activeCat = isset($_GET['cat']) ? max(0, (int)$_GET['cat']) : 0;
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : "";

/* ===== Categories + counts (pivot + legacy, unique books) ===== */
$cats = $pdo->query("
  SELECT
    c.id,
    c.name,
    (
      SELECT COUNT(DISTINCT b1.id)
      FROM books b1
      LEFT JOIN book_categories bc1 ON bc1.book_id = b1.id
      WHERE (b1.category_id = c.id OR bc1.category_id = c.id)
    ) AS cnt
  FROM categories c
  ORDER BY c.name ASC
")->fetchAll();

/* ALL count */
$allCnt = (int)($pdo->query("
  SELECT COUNT(DISTINCT b.id)
  FROM books b
")->fetchColumn() ?? 0);

/* ===== Books query (supports legacy + pivot + search) ===== */
$sql = "
  SELECT DISTINCT
    b.*,
    c.name AS category_name
  FROM books b
  JOIN categories c ON c.id = b.category_id
  LEFT JOIN book_categories bc ON bc.book_id = b.id
  WHERE 1
";
$params = [];

if ($activeCat > 0) {
  $sql .= " AND (b.category_id = ? OR bc.category_id = ?) ";
  $params[] = $activeCat;
  $params[] = $activeCat;
}

if ($q !== "") {
  $sql .= " AND (b.book_name LIKE ? OR b.book_author LIKE ?) ";
  $params[] = "%".$q."%";
  $params[] = "%".$q."%";
}

$sql .= " ORDER BY b.created_at DESC, b.id DESC ";

$st = $pdo->prepare($sql);
$st->execute($params);
$books = $st->fetchAll();

/* helper: build url keeping q */
function books_url(array $override = []): string {
  $get = $_GET;
  foreach ($override as $k => $v) {
    if ($v === null) unset($get[$k]);
    else $get[$k] = $v;
  }
  $qs = http_build_query($get);
  return $qs ? ("books.php?".$qs) : "books.php";
}
?>

<style>
  .col-actions-220{width:220px}

  .admin-table-wrap{ border-radius:14px; overflow:hidden; }
  .admin-table{ margin:0; }
  .admin-table thead th{
    background:#f3f4f6;
    color:#111827;
    font-weight:800;
    border-bottom:1px solid rgba(0,0,0,.08);
    white-space:nowrap;
  }
  .admin-table tbody tr:hover{ background: rgba(0,0,0,.03); }

  .cat-chips{ display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
  .cat-chip{
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 12px; border:1px solid rgba(0,0,0,.12);
    border-radius:999px; text-decoration:none; color:inherit;
    background:#fff; font-weight:700; font-size:13px;
  }
  .cat-chip:hover{ background: rgba(0,0,0,.03); }
  .cat-chip.is-active{ border-color:#111827; box-shadow:0 0 0 2px rgba(17,24,39,.10) inset; }
  .cat-chip .cnt{ font-weight:800; font-size:12px; opacity:.65; }

  .books-tools{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:12px; }
  .books-search{
    flex:1 1 320px;
    max-width: 520px;
    display:flex; gap:8px;
  }
  .books-search input{
    flex:1;
    padding:10px 12px;
    border-radius:12px;
    border:1px solid rgba(0,0,0,.12);
    outline:none;
  }
  main {
    overflow: scroll;
  }
</style>

<div class="d-flex justify-content-between align-items-center">
  <div>
    <h3 class="fw-bold mb-0">Kitoblar jadvali</h3>
  </div>

  <a class="btn btn-success" href="book_edit.php">+ Q'oshish</a>
</div>

<!-- Category chips -->
<div class="cat-chips">
  <a class="cat-chip <?= $activeCat===0 ? 'is-active' : '' ?>" href="<?= e(books_url(['cat'=>null])) ?>">
    Hammasi <span class="cnt"><?= (int)$allCnt ?></span>
  </a>

  <?php foreach ($cats as $c): ?>
    <a class="cat-chip <?= $activeCat===(int)$c['id'] ? 'is-active' : '' ?>"
       href="<?= e(books_url(['cat'=>(int)$c['id']])) ?>">
      <?= e($c['name']) ?>
      <span class="cnt"><?= (int)$c['cnt'] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Search (inside current category) -->
<div class="books-tools">
  <form class="books-search" method="get" action="books.php">
    <?php if ($activeCat > 0): ?>
      <input type="hidden" name="cat" value="<?= (int)$activeCat ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Kitob nomi yoki avtori ..." autocomplete="off">
    <button class="btn btn-outline-secondary" type="submit">Qidirish</button>
    <?php if ($q !== ""): ?>
      <a class="btn btn-outline-danger" href="<?= e(books_url(['q'=>null])) ?>">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="stats-box mt-3 admin-table-wrap">
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle admin-table">
      <thead>
        <tr>
          <th style="width:80px">ID</th>
          <th>Kitob</th>
          <th>Kategoriya</th>
          <th style="width:110px">O'qilganlar</th>
          <th style="width:170px">Rating</th>
          <th class="col-actions-220">Amallar</th>
        </tr>
      </thead>

      <tbody>
      <?php foreach ($books as $b): ?>
        <tr>
          <td><?= (int)$b['id'] ?></td>

          <td>
            <div class="fw-bold"><?= e($b['book_name']) ?></div>
            
          </td>

          <td class="fw-bold"><?= e($b['category_name']) ?></td>

          <td><?= (int)$b['read_count'] ?></td>

          <td>⭐ <?= number_format((float)$b['rating_avg'], 2) ?> (<?= (int)$b['rating_count'] ?>)</td>

          <td>
            <a class="btn btn-sm btn-outline-secondary" href="book_edit.php?id=<?= (int)$b['id'] ?>">Tahrirlash</a>
            <a class="btn btn-sm btn-outline-danger"
               href="<?= e(books_url(['del'=>(int)$b['id']])) ?>"
               onclick="return confirm('Delete book?')">O'chirish</a>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$books): ?>
        <tr><td colspan="6" class="muted">Kitoblar topilmadi.</td></tr>
      <?php endif; ?>
      </tbody>

    </table>
  </div>
</div>

<?php admin_footer(); ?>
