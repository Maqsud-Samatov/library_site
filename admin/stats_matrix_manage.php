<?php
// admin/stats_matrix_manage.php
require_once __DIR__ . "/_layout_top.php";

function i0($v){ $v = trim((string)$v); return ($v === '') ? null : (int)$v; }

$msg = '';
$err = '';

/* ===== load active set (or latest) ===== */
$set = $pdo->query("
  SELECT * FROM library_stats_sets
  ORDER BY is_active DESC, sort_order ASC, id DESC
  LIMIT 1
")->fetch();

if (!$set) {
  $pdo->prepare("INSERT INTO library_stats_sets(title, as_of_date, is_active, sort_order) VALUES(?,?,1,0)")
      ->execute(['ARM umumiy fondi', date('Y-m-d')]);
  $set = $pdo->query("SELECT * FROM library_stats_sets ORDER BY id DESC LIMIT 1")->fetch();
}
$setId = (int)$set['id'];

/* ===== handle actions ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  try {
    if ($action === 'save_set') {
      $title = trim((string)($_POST['title'] ?? ''));
      $date  = trim((string)($_POST['as_of_date'] ?? ''));
      $is_active = isset($_POST['is_active']) ? 1 : 0;
      if ($title === '') throw new Exception("Sarlavha kiritilmadi.");

      if ($is_active) $pdo->exec("UPDATE library_stats_sets SET is_active=0");
      $pdo->prepare("UPDATE library_stats_sets SET title=?, as_of_date=?, is_active=? WHERE id=?")
          ->execute([$title, ($date !== '' ? $date : null), $is_active, $setId]);
      $msg = "Saqlandi.";
    }

    elseif ($action === 'add_type') {
      $name = trim((string)($_POST['type_title'] ?? ''));
      if ($name === '') throw new Exception("Ustun nomi kiritilmadi.");
      $pdo->prepare("INSERT INTO library_stats_types(set_id,type_title,sort_order) VALUES(?,?,0)")
          ->execute([$setId, $name]);
      $msg = "Ustun qo‘shildi.";
    }

    elseif ($action === 'add_row') {
      $name = trim((string)($_POST['row_title'] ?? ''));
      if ($name === '') throw new Exception("Qator nomi kiritilmadi.");
      $pdo->prepare("INSERT INTO library_stats_rows(set_id,row_title,sort_order) VALUES(?,?,0)")
          ->execute([$setId, $name]);
      $msg = "Qator qo‘shildi.";
    }

    elseif ($action === 'delete_type') {
      $typeId = (int)($_POST['type_id'] ?? 0);
      if ($typeId <= 0) throw new Exception("Noto‘g‘ri ustun ID.");
      $pdo->prepare("DELETE FROM library_stats_types WHERE id=? AND set_id=?")->execute([$typeId, $setId]);
      $msg = "Ustun o‘chirildi.";
    }

    elseif ($action === 'delete_row') {
      $rowId = (int)($_POST['row_id'] ?? 0);
      if ($rowId <= 0) throw new Exception("Noto‘g‘ri qator ID.");
      $pdo->prepare("DELETE FROM library_stats_rows WHERE id=? AND set_id=?")->execute([$rowId, $setId]);
      $msg = "Qator o‘chirildi.";
    }

    elseif ($action === 'save_meta') {
      // rename + sort types/rows
      $types = $_POST['types'] ?? [];
      $rows  = $_POST['rows'] ?? [];

      $stT = $pdo->prepare("UPDATE library_stats_types SET type_title=?, sort_order=? WHERE id=? AND set_id=?");
      foreach ((array)$types as $id => $t) {
        $id = (int)$id;
        $title = trim((string)($t['title'] ?? ''));
        $sort  = (int)($t['sort'] ?? 0);
        if ($title === '') $title = '—';
        $stT->execute([$title, $sort, $id, $setId]);
      }

      $stR = $pdo->prepare("UPDATE library_stats_rows SET row_title=?, sort_order=? WHERE id=? AND set_id=?");
      foreach ((array)$rows as $id => $r) {
        $id = (int)$id;
        $title = trim((string)($r['title'] ?? ''));
        $sort  = (int)($r['sort'] ?? 0);
        if ($title === '') $title = '—';
        $stR->execute([$title, $sort, $id, $setId]);
      }

      $msg = "Nomlar va tartib saqlandi.";
    }

    elseif ($action === 'save_cells') {
      // cells[typeId][rowId] => ['exist'=>..., 'total'=>...]
      $cells = $_POST['cells'] ?? [];

      $pdo->beginTransaction();
      $up = $pdo->prepare("
        INSERT INTO library_stats_cells(set_id,type_id,row_id,exist_count,total_count)
        VALUES(?,?,?,?,?)
        ON DUPLICATE KEY UPDATE exist_count=VALUES(exist_count), total_count=VALUES(total_count)
      ");

      foreach ((array)$cells as $typeId => $rowsData) {
        $typeId = (int)$typeId;
        foreach ((array)$rowsData as $rowId => $v) {
          $rowId = (int)$rowId;
          $exist = i0($v['exist'] ?? '');
          $total = i0($v['total'] ?? '');
          $up->execute([$setId, $typeId, $rowId, $exist, $total]);
        }
      }

      $pdo->commit();
      $msg = "Raqamlar saqlandi.";
    }

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $err = $e->getMessage();
  }

  // reload set
  $set = $pdo->query("SELECT * FROM library_stats_sets WHERE id={$setId} LIMIT 1")->fetch();
}

/* ===== load types/rows/cells ===== */
$types = $pdo->prepare("SELECT * FROM library_stats_types WHERE set_id=? ORDER BY sort_order ASC, id ASC");
$types->execute([$setId]);
$types = $types->fetchAll();

$rows = $pdo->prepare("SELECT * FROM library_stats_rows WHERE set_id=? ORDER BY sort_order ASC, id ASC");
$rows->execute([$setId]);
$rows = $rows->fetchAll();

$cellMap = []; // [typeId][rowId] => {exist,total}
if ($types && $rows) {
  $typeIds = array_map(fn($t)=>(int)$t['id'], $types);
  $rowIds  = array_map(fn($r)=>(int)$r['id'], $rows);
  $inT = implode(',', array_fill(0, count($typeIds), '?'));
  $inR = implode(',', array_fill(0, count($rowIds), '?'));

  $st = $pdo->prepare("
    SELECT type_id,row_id,exist_count,total_count
    FROM library_stats_cells
    WHERE set_id=? AND type_id IN ($inT) AND row_id IN ($inR)
  ");
  $st->execute(array_merge([$setId], $typeIds, $rowIds));
  foreach ($st->fetchAll() as $c) {
    $tid=(int)$c['type_id']; $rid=(int)$c['row_id'];
    $cellMap[$tid][$rid] = [
      'exist' => $c['exist_count'],
      'total' => $c['total_count']
    ];
  }
}

admin_header("Statistika (Matritsa)");
?>

<h3 class="fw-bold">Statistika (Matritsa)</h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php elseif ($msg): ?>
  <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div style="display:flex; flex-direction: column;" class="row g-3">
  <!-- LEFT: set + add controls -->
  <div class="col-12 col-lg-4">
    <div class="stats-box p-3">
      <div class="fw-bold mb-2">Asosiy sozlamalar</div>
      <form method="post" class="d-grid gap-2">
        <input type="hidden" name="action" value="save_set">

        <label class="form-label muted">Sarlavha</label>
        <input class="form-control" name="title" value="<?= e((string)$set['title']) ?>" required>

        <label class="form-label muted">Holat sanasi (ixtiyoriy)</label>
        <input class="form-control" name="as_of_date" value="<?= e((string)($set['as_of_date'] ?? '')) ?>" placeholder="2023-01-01">

        <label class="d-flex align-items-center gap-2 mt-2">
          <input type="checkbox" name="is_active" value="1" <?= ((int)$set['is_active'] === 1 ? 'checked' : '') ?>>
          <span>Aktiv (saytda ko‘rinadi)</span>
        </label>

        <button class="btn btn-success mt-2">Saqlash</button>
      </form>
    </div>

    <div style="width: 1447px;" class="stats-box p-3 mt-3">
      <div class="fw-bold mb-2">Ustun qo‘shish</div>
      <form method="post" class="d-flex gap-2">
        <input type="hidden" name="action" value="add_type">
        <input class="form-control" name="type_title" placeholder="Masalan: Ijtimoiy siyosiy" required>
        <button class="btn btn-orange">Qo‘shish</button>
      </form>
    </div>

    <div style="width: 1447px;" class="stats-box p-3 mt-3">
      <div class="fw-bold mb-2">Qator qo‘shish</div>
      <form method="post" class="d-flex gap-2">
        <input type="hidden" name="action" value="add_row">
        <input class="form-control" name="row_title" placeholder="Masalan: O‘zbekcha" required>
        <button class="btn btn-orange">Qo‘shish</button>
      </form>
    </div>
  </div>

  <!-- RIGHT: matrix editor -->
  <div class="col-12 col-lg-8">
    <div style="width: 1447px;" class="stats-box p-3">
      <div class="fw-bold mb-2">Nomlar va tartib</div>

      <form method="post">
        <input type="hidden" name="action" value="save_meta">

        <div class="row g-3">
          <div class="col-12">
            <div class="fw-bold mb-2">Ustunlar</div>
            <?php if (!$types): ?>
              <div class="muted">Ustun yo‘q</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Nomi</th>
                      <th style="width:110px">Tartib</th>
                      <th style="width:110px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($types as $t): $tid=(int)$t['id']; ?>
                      <tr>
                        <td>
                          <input class="form-control form-control-sm" name="types[<?= $tid ?>][title]" value="<?= e((string)$t['type_title']) ?>">
                        </td>
                        <td>
                          <input class="form-control form-control-sm" name="types[<?= $tid ?>][sort]" value="<?= (int)$t['sort_order'] ?>">
                        </td>
                        <td class="text-end">
                          <form method="post" onsubmit="return confirm('Ustun o‘chirilsinmi?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_type">
                            <input type="hidden" name="type_id" value="<?= $tid ?>">
                            <button class="btn btn-sm btn-danger">O‘chirish</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <div class="col-12">
            <div class="fw-bold mb-2">Qatorlar</div>
            <?php if (!$rows): ?>
              <div class="muted">Qator yo‘q</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Nomi</th>
                      <th style="width:110px">Tartib</th>
                      <th style="width:110px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($rows as $r): $rid=(int)$r['id']; ?>
                      <tr>
                        <td>
                          <input class="form-control form-control-sm" name="rows[<?= $rid ?>][title]" value="<?= e((string)$r['row_title']) ?>">
                        </td>
                        <td>
                          <input class="form-control form-control-sm" name="rows[<?= $rid ?>][sort]" value="<?= (int)$r['sort_order'] ?>">
                        </td>
                        <td class="text-end">
                          <form method="post" onsubmit="return confirm('Qator o‘chirilsinmi?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_row">
                            <input type="hidden" name="row_id" value="<?= $rid ?>">
                            <button class="btn btn-sm btn-danger">O‘chirish</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <button class="btn btn-success mt-2">Nom va tartibni saqlash</button>
      </form>
    </div>

    <div style="width: 1447px;" class="stats-box p-3 mt-3">
      <div class="fw-bold mb-2">Raqamlarni tahrirlash (Mavjud / Jami)</div>

      <?php if (!$types || !$rows): ?>
        <div class="muted">Avval ustun va qatorlarni kiriting.</div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action" value="save_cells">

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th style="min-width:170px">Til \ Bo‘lim</th>
                  <?php foreach ($types as $t): ?>
                    <th class="text-center" style="min-width:220px"><?= e((string)$t['type_title']) ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): $rid=(int)$r['id']; ?>
                  <tr>
                    <td class="fw-bold"><?= e((string)$r['row_title']) ?></td>

                    <?php foreach ($types as $t): $tid=(int)$t['id'];
                      $v = $cellMap[$tid][$rid] ?? ['exist'=>null,'total'=>null];
                    ?>
                      <td>
                        <div class="d-flex gap-2">
                          <input class="form-control form-control-sm text-end"
                                 name="cells[<?= $tid ?>][<?= $rid ?>][exist]"
                                 value="<?= e((string)($v['exist'] ?? '')) ?>"
                                 placeholder="Mavjud">
                          <input class="form-control form-control-sm text-end"
                                 name="cells[<?= $tid ?>][<?= $rid ?>][total]"
                                 value="<?= e((string)($v['total'] ?? '')) ?>"
                                 placeholder="Jami">
                        </div>
                      </td>
                    <?php endforeach; ?>

                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <button class="btn btn-success">Raqamlarni saqlash</button>
        </form>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php admin_footer(); ?>
