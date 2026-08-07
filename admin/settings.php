<?php
require_once __DIR__ . "/_layout_top.php";
admin_header("Settings");

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $site_title = trim((string)($_POST['site_title'] ?? 'Online Library'));
  $navbar_json = (string)($_POST['navbar_json'] ?? '[]');
  $footer_contacts = (string)($_POST['footer_contacts'] ?? '');
  $footer_links_json = (string)($_POST['footer_links_json'] ?? '[]');

  // logo upload optional
  $logo_path = get_setting($pdo, 'logo_path', 'assets/img/logo.png');
  if (!empty($_FILES['logo']['name'])) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['png','jpg','jpeg','webp'], true)) {
      $destDir = __DIR__ . "/../assets/uploads/";
      if (!is_dir($destDir)) mkdir($destDir, 0777, true);
      $file = "logo_" . time() . "." . $ext;
      $dest = $destDir . $file;
      if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
        $logo_path = "assets/uploads/" . $file;
      }
    }
  }

  set_setting($pdo, 'site_title', $site_title);
  set_setting($pdo, 'logo_path', $logo_path);
  set_setting($pdo, 'navbar_json', $navbar_json);
  set_setting($pdo, 'footer_contacts', $footer_contacts);
  set_setting($pdo, 'footer_links_json', $footer_links_json);

  $msg = "Saved.";
}

$site_title = get_setting($pdo,'site_title','Online Library');
$logo_path = get_setting($pdo,'logo_path','assets/img/logo.png');
$navbar_json = get_setting($pdo,'navbar_json','[]');
$footer_contacts = get_setting($pdo,'footer_contacts','');
$footer_links_json = get_setting($pdo,'footer_links_json','[]');
?>

<h3 class="fw-bold">Header / Footer Settings</h3>
<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="stats-box">
      <div class="fw-bold mb-2">Header</div>
      <label class="form-label muted">Site title</label>
      <input class="form-control" name="site_title" value="<?= e($site_title) ?>">

      <label class="form-label muted mt-3">Logo (png/jpg/webp)</label>
      <input class="form-control" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp">
      <div class="muted small mt-2">Current: <?= e($logo_path) ?></div>

      <label class="form-label muted mt-3">Navbar JSON</label>
      <textarea class="form-control" name="navbar_json" rows="6"><?= e($navbar_json) ?></textarea>
      <div class="muted small mt-2">Example: [{"label":"Home","href":"index.php"}]</div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="stats-box">
      <div class="fw-bold mb-2">Footer</div>
      <label class="form-label muted">Contacts (HTML allowed)</label>
      <textarea class="form-control" name="footer_contacts" rows="5"><?= e($footer_contacts) ?></textarea>

      <label class="form-label muted mt-3">Footer links JSON</label>
      <textarea class="form-control" name="footer_links_json" rows="6"><?= e($footer_links_json) ?></textarea>
    </div>
  </div>

  <div class="col-12">
    <button style="padding: 10px 30px;" class="btn btn-success">Saqlash</button>
  </div>
</form>

<?php admin_footer(); ?>
