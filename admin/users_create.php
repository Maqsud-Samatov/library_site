<?php
// admin/users_create.php
require_once __DIR__ . "/_layout_top.php";

$err = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full  = trim((string)($_POST['full_name'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $user  = trim((string)($_POST['username'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');
  $role  = (string)($_POST['role'] ?? 'editor');

  // basic validation
  if ($full === '' || $phone === '' || $user === '' || $pass === '') {
    $err = "Barcha maydonlarni to‘ldiring";
  } elseif (!preg_match('/^\+998\d{9}$/', $phone)) {
    $err = "Telefon raqam +998XXXXXXXXX formatda bo‘lishi kerak";
  } elseif (!in_array($role, ['admin','editor'], true)) {
    $err = "Noto‘g‘ri rol tanlandi";
  } else {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $st = $pdo->prepare("
      INSERT INTO users(full_name, phone, username, password, role)
      VALUES(?,?,?,?,?)
    ");

    try {
      $st->execute([$full, $phone, $user, $hash, $role]);
      $ok = "Foydalanuvchi muvaffaqiyatli yaratildi";
      // clear form values after success
      $_POST = [];
    } catch (Throwable $e) {
      // likely duplicate username/phone
      $err = "Username yoki telefon raqam mavjud (takror bo‘lmasligi kerak)";
    }
  }
}

admin_header("Yangi foydalanuvchi");
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-0">Yangi foydalanuvchi</h4>
    <div class="text-muted small">Admin yoki Editor rolida foydalanuvchi yarating</div>
  </div>
  <a href="users_list.php" class="btn btn-outline-secondary">
    <i class="bi bi-people"></i> Foydalanuvchilar
  </a>
</div>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif; ?>

<?php if ($ok): ?>
  <div class="alert alert-success"><?= e($ok) ?></div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:560px;">
  <div class="card-body">
    <form method="post" class="row g-3" autocomplete="off">
      <div class="col-12">
        <label class="form-label">To‘liq ism</label>
        <input name="full_name" class="form-control" required
               value="<?= e((string)($_POST['full_name'] ?? '')) ?>"
               placeholder="Masalan: Abdulla Abdullayev">
      </div>

      <div class="col-12">
        <label class="form-label">Telefon raqam</label>
        <input name="phone" class="form-control" required
               value="<?= e((string)($_POST['phone'] ?? '')) ?>"
               placeholder="+998901234567">
        <div class="form-text">Format: <code>+998XXXXXXXXX</code></div>
      </div>

      <div class="col-12">
        <label class="form-label">Username</label>
        <input name="username" class="form-control" required
               value="<?= e((string)($_POST['username'] ?? '')) ?>"
               placeholder="Masalan: admin1">
      </div>

      <div class="col-12">
        <label class="form-label">Parol</label>
        <input type="password" name="password" class="form-control" required
               placeholder="Kamida 6-8 belgi tavsiya">
      </div>

      <div class="col-12">
        <label class="form-label">Rol</label>
        <select name="role" class="form-select">
          <?php $r = (string)($_POST['role'] ?? 'editor'); ?>
          <option value="editor" <?= $r==='editor'?'selected':'' ?>>Editor</option>
          <option value="admin" <?= $r==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>

      <div class="col-12 d-flex gap-2 flex-wrap">
        <button class="btn btn-success">
          <i class="bi bi-person-plus"></i> Saqlash
        </button>
        <a href="users_list.php" class="btn btn-outline-secondary">
          <i class="bi bi-people"></i> Ro‘yxat
        </a>
      </div>
    </form>
  </div>
</div>

<?php admin_footer(); ?>
