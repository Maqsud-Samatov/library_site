<?php
$footer_contacts = get_setting($pdo, 'footer_contacts', '');
$footer_links    = get_json_setting($pdo, 'footer_links_json', [
  ['label'=>'Home','href'=>'index.php'],
  ['label'=>'Admin','href'=>'admin/login.php'],
]);
?>
</main>

<footer class="footer">
  <div class="container">
    <div class="row g-3 align-items-start">
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-center gap-2">
          <img class="footer-logo img-hide-onerror" src="<?= e(get_setting($pdo,'logo_path','assets/img/logo.png')) ?>" onerror="this.classList.add('is-hidden')" alt="logo">
          <div class="fw-bold"><?= e(get_setting($pdo,'site_title','Online Library')) ?></div>
        </div>
        <div class="mt-2 small d-flex flex-column gap-1"><?= $footer_contacts ?: '<span>Bilim sari raqamli kutubxona</span><span>24/7 onlayn foydalanish imkoniyati</span>' ?></div>
      </div>

      <div class="col-12 col-md-4">
        <div class="fw-bold mb-2">Quick links</div>
        <div class="d-flex flex-column gap-1">
          <?php foreach ($footer_links as $l): ?>
            <a href="<?= e($l['href'] ?? '#') ?>"><?= e($l['label'] ?? 'Link') ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-12 col-md-4 text-md-end">
        <div class="small">© <?= date('Y') ?> <span class="orange">Library Platform</span></div>
        <div class="small text-white-50 mt-1">Modern digital learning experience</div>
      </div>
    </div>
  </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
</body>
</html>
