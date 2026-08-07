<?php
// admin/users_list.php
require_once __DIR__ . "/_layout_top.php";
// optional search
$q = trim((string)($_GET['q'] ?? ''));

if ($q !== '') {
  $st = $pdo->prepare("
    SELECT id, full_name, phone, username, role, is_active, created_at
    FROM users
    WHERE full_name LIKE :q OR phone LIKE :q OR username LIKE :q
    ORDER BY id DESC
  ");
  $like = "%{$q}%";
  $st->execute([':q' => $like]);
  $users = $st->fetchAll();
} else {
  $users = $pdo->query("
    SELECT id, full_name, phone, username, role, is_active, created_at
    FROM users
    ORDER BY id DESC
  ")->fetchAll();
}

admin_header("Foydalanuvchilar");
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-0">Foydalanuvchilar</h4>
    <div class="text-muted small">Telefon, username, rol va status bo‘yicha ko‘rish</div>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <form class="d-flex gap-2" method="get" action="">
      <input type="search" name="q" class="form-control" style="min-width:260px"
             placeholder="Qidirish: ism / telefon / username" value="<?= e($q) ?>">
      <button class="btn btn-outline-secondary" type="submit">
        <i class="bi bi-search"></i>
      </button>
      <?php if ($q !== ''): ?>
        <a class="btn btn-outline-secondary" href="users_list.php">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </form>

    <a href="users_create.php" class="btn btn-success">
      <i class="bi bi-person-plus"></i> Yangi
    </a>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px">#</th>
            <th>Ism</th>
            <th>Telefon</th>
            <th>Username</th>
            <th style="width:120px">Rol</th>
            <th style="width:110px">Status</th>
            <th style="width:190px">Yaratilgan</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$users): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Foydalanuvchi topilmadi.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                <td><?= e($u['phone']) ?></td>
                <td><code><?= e($u['username']) ?></code></td>
                <td>
                  <?php if ($u['role'] === 'admin'): ?>
                    <span class="badge bg-danger">admin</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">editor</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((int)$u['is_active'] === 1): ?>
                    <span class="badge bg-success">faol</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">o‘chiq</span>
                  <?php endif; ?>
                </td>
                <td><?= e($u['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php admin_footer(); ?>
