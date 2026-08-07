<?php
require_once __DIR__ . "/_layout_top.php";
admin_header("Categories");

/** Check if a column exists */
function col_exists(PDO $pdo, string $table, string $col): bool {
  $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
  $st->execute([$col]);
  return (bool)$st->fetch();
}

$hasDescription = col_exists($pdo, 'categories', 'description');

/** Delete category (only if no books attached) */
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];

  // NEW (many-to-many) check
  $st = $pdo->prepare("SELECT COUNT(*) AS c FROM book_categories WHERE category_id=?");
  $st->execute([$id]);
  $pivotCount = (int)($st->fetch()['c'] ?? 0);

  // Legacy check (old single category_id)
  $st = $pdo->prepare("SELECT COUNT(*) AS c FROM books WHERE category_id=?");
  $st->execute([$id]);
  $legacyCount = (int)($st->fetch()['c'] ?? 0);

  if ($pivotCount === 0 && $legacyCount === 0) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
  }

  header("Location: categories.php");
  exit;
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<style>
  /* categories.php table styling like news.php */
  .col-actions-200{width:200px}

  .admin-table-wrap{
    border-radius: 14px;
    overflow: hidden; /* header radius */
  }

  .admin-table{
    margin: 0;
  }

  .admin-table thead th{
    background: #f3f4f6;        /* light header like news.php */
    color: #111827;
    font-weight: 800;
    border-bottom: 1px solid rgba(0,0,0,.08);
    white-space: nowrap;
  }

  .admin-table tbody td{
    vertical-align: middle;
  }

  .admin-table tbody tr:hover{
    background: rgba(0,0,0,.03);
  }
</style>

<div class="d-flex justify-content-between align-items-center">
  <h3 class="fw-bold">Kategoriyalar</h3>
  <a class="btn btn-success" href="category_edit.php">+ Qo'shish</a>
</div>

<div class="stats-box mt-3 admin-table-wrap">
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle admin-table">
      <thead>
        <tr>
          <th style="width:80px">ID</th>
          <th>Nomi</th>
          <th>Raqami</th>
          <?php if ($hasDescription): ?><th>Ta`rifi</th><?php endif; ?>
          <th class="col-actions-200">Amallar</th>
        </tr>
      </thead>

      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td><?= (int)$c['id'] ?></td>
          <td class="fw-bold"><?= e($c['name']) ?></td>
          <td class="muted"><?= e($c['slug']) ?></td>

          <?php if ($hasDescription): ?>
            <td class="muted"><?= e((string)($c['description'] ?? '')) ?></td>
          <?php endif; ?>

          <td>
            <a class="btn btn-sm btn-outline-secondary" href="category_edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger"
               href="categories.php?del=<?= (int)$c['id'] ?>"
               onclick="return confirm('Delete? Only works if category has no books.')">
               Delete
            </a>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$cats): ?>
        <tr>
          <td colspan="<?= $hasDescription ? 5 : 4 ?>" class="muted">No categories yet.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php admin_footer(); ?>
