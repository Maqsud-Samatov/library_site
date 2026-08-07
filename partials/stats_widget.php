<?php
// partials/stats_widget.php (NEW MATRIX MODEL)
// - Jadval: ustunlar = type (tab), qatorlar = row (til)
// - Har tab: yuqorida jami exist/total, pastda qatorlar, oxirida jami qatori
// - Grafik: Pie + Gorizontal bar (50/50)
// - Hammasi o‘zbekcha
// - Xato bo‘lsa ham footer o‘lib qolmasligi uchun try/catch bor

if (!isset($pdo)) { return; }

try {
  $set = $pdo->query("
    SELECT * FROM library_stats_sets
    WHERE is_active=1
    ORDER BY sort_order ASC, id DESC
    LIMIT 1
  ")->fetch();

  if (!$set) {
    echo '<section class="mb-4"><div class="stats-box p-3">Statistika: ma’lumotlar mavjud emas</div></section>';
    return;
  }

  $setId = (int)$set['id'];

  $st = $pdo->prepare("
    SELECT * FROM library_stats_types
    WHERE set_id=?
    ORDER BY sort_order ASC, id ASC
  ");
  $st->execute([$setId]);
  $types = $st->fetchAll();

  $st = $pdo->prepare("
    SELECT * FROM library_stats_rows
    WHERE set_id=?
    ORDER BY sort_order ASC, id ASC
  ");
  $st->execute([$setId]);
  $rows = $st->fetchAll();

  if (!$types || !$rows) {
    echo '<section class="mb-4"><div class="stats-box p-3">Statistika: ustunlar yoki qatorlar mavjud emas</div></section>';
    return;
  }

  $typeIds = array_map(fn($t)=>(int)$t['id'], $types);
  $rowIds  = array_map(fn($r)=>(int)$r['id'], $rows);

  $inT = implode(',', array_fill(0, count($typeIds), '?'));
  $inR = implode(',', array_fill(0, count($rowIds), '?'));

  $st = $pdo->prepare("
    SELECT type_id, row_id, exist_count, total_count
    FROM library_stats_cells
    WHERE set_id=?
      AND type_id IN ($inT)
      AND row_id IN ($inR)
  ");
  $st->execute(array_merge([$setId], $typeIds, $rowIds));
  $cells = $st->fetchAll();

  $cellMap = [];
  foreach ($cells as $c) {
    $tid = (int)$c['type_id'];
    $rid = (int)$c['row_id'];
    $cellMap[$tid][$rid] = [
      'exist' => ($c['exist_count'] === null ? null : (int)$c['exist_count']),
      'total' => ($c['total_count'] === null ? null : (int)$c['total_count']),
    ];
  }

  $totals = []; // per type
  foreach ($types as $t) {
    $tid = (int)$t['id'];
    $existSum = 0; $totalSum = 0; $hasAny = false;
    foreach ($rows as $r) {
      $rid = (int)$r['id'];
      $v = $cellMap[$tid][$rid] ?? null;
      if ($v) {
        if (is_int($v['exist'])) { $existSum += $v['exist']; $hasAny = true; }
        if (is_int($v['total'])) { $totalSum += $v['total']; $hasAny = true; }
      }
    }
    $totals[$tid] = ['exist'=>$existSum, 'total'=>$totalSum, 'hasAny'=>$hasAny];
  }

} catch (Throwable $e) {
  echo '<section class="mb-4"><div class="stats-box p-3">Statistika xatosi: '.htmlspecialchars($e->getMessage(), ENT_QUOTES).'</div></section>';
  return;
}
?>

<section class="mb-4">
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
    <div>
      <div class="section-title"><?= e((string)$set['title']) ?></div>
      <div class="muted">Holat: <?= !empty($set['as_of_date']) ? e((string)$set['as_of_date']) : '—' ?></div>
    </div>
  </div>

  <!-- TABLAR (USTUNLAR) -->
  <ul class="nav nav-pills gap-2 mt-3" id="libStatsTabs" role="tablist">
    <?php foreach ($types as $i => $t): $tid=(int)$t['id']; ?>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link <?= $i===0 ? 'active' : '' ?>"
          data-bs-toggle="tab"
          data-bs-target="#libstat-pane-<?= $tid ?>"
          type="button"
          role="tab"
          aria-selected="<?= $i===0 ? 'true' : 'false' ?>">
          <?= e((string)$t['type_title']) ?>
        </button>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="tab-content mt-3">
    <?php foreach ($types as $i => $t): $tid=(int)$t['id']; ?>
      <div class="tab-pane fade <?= $i===0 ? 'show active' : '' ?>" id="libstat-pane-<?= $tid ?>" role="tabpanel">
        <div class="stats-box p-3">

          <!-- 2-qator: JAMI -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="fw-bold">Jami (<?= e((string)$t['type_title']) ?>)</div>
            <div class="muted small" id="libstat-jami-text-<?= $tid ?>">
              <?php if (!empty($totals[$tid]['hasAny'])): ?>
                <?= number_format((int)$totals[$tid]['exist']) ?> / <?= number_format((int)$totals[$tid]['total']) ?>
              <?php else: ?>
                Ma’lumotlar mavjud emas
              <?php endif; ?>
            </div>
          </div>

          <!-- Grafiklar: 50% Pie + 50% Gorizontal bar -->
          <div class="row g-3 mt-2">
            <div class="col-12 col-lg-6">
              <div class="stats-box p-3 h-100">
                <div class="fw-bold mb-2">Doira diagramma (Mavjud / Jami)</div>
                <div class="chart-wrap"><canvas id="libstat-pie-<?= $tid ?>" height="240"></canvas></div>
                <div class="muted small mt-2" id="libstat-empty-<?= $tid ?>" style="display:none;">Ma’lumotlar mavjud emas</div>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="stats-box p-3 h-100">
                <div class="fw-bold mb-2">Gorizontal diagramma (Til kesimida)</div>
                <div class="chart-wrap"><canvas id="libstat-hbar-<?= $tid ?>" height="240"></canvas></div>
              </div>
            </div>
          </div>

          <!-- Jadval: qatorlar + oxirida jami -->
          <div class="table-responsive mt-3">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="table-light">
                  <th>Til</th>
                  <th class="text-end">Mavjud / Jami</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): $rid=(int)$r['id'];
                  $v = $cellMap[$tid][$rid] ?? null;
                  $ex = $v['exist'] ?? null;
                  $tt = $v['total'] ?? null;

                  $exOk = is_int($ex);
                  $ttOk = is_int($tt);
                ?>
                  <tr>
                    <td class="fw-bold"><?= e((string)$r['row_title']) ?></td>
                    <td class="text-end">
                      <?php
                        if (!$exOk && !$ttOk) echo "Ma’lumotlar mavjud emas";
                        elseif (!$exOk && $ttOk) echo "Ma’lumotlar mavjud emas / ".number_format($tt);
                        elseif ($exOk && !$ttOk) echo number_format($ex)." / Ma’lumotlar mavjud emas";
                        else echo number_format($ex)." / ".number_format($tt);
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>

                <!-- JAMI QATORI (pastda) -->
                <tr class="table-light">
                  <td class="fw-bold">Jami</td>
                  <td class="text-end fw-bold">
                    <?php if (!empty($totals[$tid]['hasAny'])): ?>
                      <?= number_format((int)$totals[$tid]['exist']) ?> / <?= number_format((int)$totals[$tid]['total']) ?>
                    <?php else: ?>
                      Ma’lumotlar mavjud emas
                    <?php endif; ?>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<style>
  .chart-wrap{position:relative;width:100%}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
window.__LIB_STATS_MATRIX__ = <?= json_encode([
  'types' => array_map(function($t) use ($rows, $cellMap, $totals) {
    $tid = (int)$t['id'];
    return [
      'id' => $tid,
      'nom' => (string)$t['type_title'],
      'jami_mavjud' => (int)$totals[$tid]['exist'],
      'jami_jami' => (int)$totals[$tid]['total'],
      'bor' => (bool)$totals[$tid]['hasAny'],
      'qatorlar' => array_map(function($r) use ($tid, $cellMap) {
        $rid = (int)$r['id'];
        $v = $cellMap[$tid][$rid] ?? null;
        return [
          'til' => (string)$r['row_title'],
          'mavjud' => $v['exist'] ?? null,
          'jami' => $v['total'] ?? null,
        ];
      }, $rows),
    ];
  }, $types),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<script>
(function(){
  const D = window.__LIB_STATS_MATRIX__;
  if (!D || !Array.isArray(D.types)) return;

  const charts = {};

  function render(typeId){
    const t = D.types.find(x => x.id === typeId);
    if (!t) return;

    const emptyEl = document.getElementById('libstat-empty-'+typeId);

    if (!t.bor) {
      if (emptyEl) emptyEl.style.display = 'block';
      if (charts[typeId]) Object.values(charts[typeId]).forEach(ch => ch && ch.destroy && ch.destroy());
      return;
    }
    if (emptyEl) emptyEl.style.display = 'none';

    const labels = t.qatorlar.map(r => r.til);
    const mavjudVals = t.qatorlar.map(r => Number.isFinite(r.mavjud) ? r.mavjud : 0);
    const jamiVals   = t.qatorlar.map(r => Number.isFinite(r.jami) ? r.jami : 0);

    // destroy previous
    if (charts[typeId]) Object.values(charts[typeId]).forEach(ch => ch && ch.destroy && ch.destroy());
    charts[typeId] = {};

    // Pie: Mavjud vs Jami (2 rang)
    charts[typeId].pie = new Chart(document.getElementById('libstat-pie-'+typeId), {
      type: 'pie',
      data: { labels: ['Mavjud', 'Jami'], datasets: [{ data: [t.jami_mavjud, t.jami_jami] }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Horizontal bar: 2 dataset (Mavjud/Jami) til kesimida
    charts[typeId].hbar = new Chart(document.getElementById('libstat-hbar-'+typeId), {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label: 'Mavjud', data: mavjudVals },
          { label: 'Jami', data: jamiVals }
        ]
      },
      options: {
        responsive: true,
        indexAxis: 'y',
        scales: { x: { beginAtZero: true } }
      }
    });
  }

  // init first
  if (D.types[0]) render(D.types[0].id);

  // on tab change
  document.getElementById('libStatsTabs')?.addEventListener('shown.bs.tab', function(e){
    const target = e.target?.getAttribute('data-bs-target');
    if (!target) return;
    const id = parseInt(target.replace('#libstat-pane-',''), 10);
    if (Number.isFinite(id)) render(id);
  });
})();
</script>
