<?php
require_once __DIR__ . "/partials/header.php";

/* ================= CATEGORIES ================= */
$cats = $pdo->query("
  SELECT c.id, c.name, c.slug, c.description,
    (SELECT COUNT(*) FROM book_categories bc WHERE bc.category_id=c.id) AS cnt
  FROM categories c
  ORDER BY c.name ASC
")->fetchAll();

$firstCatId = $cats ? (int)$cats[0]['id'] : 0;
$firstCatName = $cats[0]['name'] ?? '';
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
  <div>
    <div class="section-title">Categoriyalar</div>
    <div class="muted">Kategoriyani tanlang — kitoblar zamonaviy kartalarda chiqadi</div>
  </div>
  <a class="btn btn-success" href="index.php">← Orqaga</a>
</div>

<?php if (!$cats): ?>
  <div class="stats-box mt-3">Hali category yo‘q.</div>
<?php else: ?>

  <div class="row g-3 mt-2 category-layout">
    <!-- LEFT: CATEGORIES -->
    <div class="col-12 col-lg-4 col-xl-3">
      <aside class="category-sidebar">
        <div class="category-sidebar-head">
          <span class="fw-bold">Kategoriyalar</span>
          <span class="small opacity-75">Tanlang</span>
        </div>

        <div class="category-sidebar-body">
          <div class="nav flex-column nav-pills gap-2" id="catTabs" role="tablist" aria-orientation="vertical">
            <?php foreach ($cats as $i => $c):
              $cid = (int)$c['id'];
              $active = ($i === 0) ? 'active' : '';
              $sel = ($i === 0) ? 'true' : 'false';
            ?>
              <button class="nav-link text-start cat-pill <?= $active ?>"
                      id="tab-<?= $cid ?>"
                      data-cat="<?= $cid ?>"
                      type="button"
                      role="tab"
                      aria-selected="<?= $sel ?>">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold"><?= e($c['name']) ?></span>
                  <span class="badge text-bg-light"><?= (int)$c['cnt'] ?></span>
                </div>

                <?php if (!empty($c['description'])): ?>
                  <div class="small muted mt-1 clamp-2"><?= e($c['description']) ?></div>
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </div>

    <!-- RIGHT: BOOKS -->
    <div class="col-12 col-lg-8 col-xl-9">
      <div class="stats-box category-books-panel">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
          <div>
            <div class="fw-bold fs-5" id="activeCatTitle"><?= e($firstCatName) ?></div>
            <div class="muted">Ushbu kategoriyadagi kitoblar</div>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="input-wrap" style="min-width:260px; max-width:420px;">
              <input id="bookSearch" type="search" class="form-control" placeholder="Kitob nomi yoki muallif bo‘yicha qidirish...">
            </div>
            <div class="small muted">
              <span id="bookCount">0</span> ta
            </div>
          </div>
        </div>

        <!-- GRID -->
        <div class="row g-3 mt-2" id="booksGrid" aria-live="polite">
          <div class="col-12">
            <div class="stats-box">Yuklanmoqda...</div>
          </div>
        </div>

        <!-- PAGINATION CONTROLS -->
        <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
          <button type="button" class="btn btn-success" id="catPrev">oldingi</button>

          <div class="muted small">
            <span id="catIndicator">1 / 1</span>
          </div>

          <button type="button" class="btn btn-success" id="catNext">keyingi</button>
        </div>

      </div>
    </div>
  </div>

  <!-- JS needs these globals -->
  <script>
    window.__LIB_FIRST_CAT__ = <?= (int)$firstCatId ?>;
    window.__LIB_FIRST_CAT_NAME__ = <?= json_encode($firstCatName, JSON_UNESCAPED_UNICODE) ?>;
  </script>

  <!-- AJAX + pagination + global search -->
  <script src="/assets/js/category_list.js"></script>

<?php endif; ?>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
