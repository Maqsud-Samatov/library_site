<?php
require_once __DIR__ . "/partials/header.php";

/* ================= MEDIA ================= */
$media = $pdo->query("
  SELECT *
  FROM media_sections
  WHERE is_active=1
  ORDER BY sort_order ASC, id DESC
  LIMIT 1
")->fetch();

/* ================= NEWS ================= */
$news = $pdo->query("
  SELECT *
  FROM news
  WHERE is_active=1
  ORDER BY sort_order ASC, id DESC
  LIMIT 10
")->fetchAll();

$heroQuote = trim((string)($media['quote'] ?? ''));
if ($heroQuote === '') {
  $heroQuote = 'Shunday kitoblarni izlangki, ular bilan suhbat sizning tafakkuringizni kengaytirsin.';
}

$heroAuthor = trim((string)($media['author'] ?? ''));
if ($heroAuthor === '' || mb_strtolower($heroAuthor) === 'online library') {
  $heroAuthor = 'Onlayn Kutubxona';
}
?>

</main>

<!-- ================= MEDIA SECTION (FULL WIDTH OUTSIDE MAIN) ================= -->
<section class="mb-4 full-bleed">
  <div class="media-hero media-hero--full">

    <?php if (!empty($media['image_path'])): ?>
      <img class="media-hero-img" src="<?= e($media['image_path']) ?>" alt="media">
    <?php else: ?>
      <img class="media-hero-img" src="https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?auto=format&fit=crop&w=1800&q=82" alt="Modern library hero">
    <?php endif; ?>

    <div class="media-hero-grad"></div>

    <div class="media-hero-content">
      <div class="container media-hero-maxw">
        <div class="hero-panel">
          <div class="hero-panel-top">
            <div class="hero-chip">
              <i class="bi bi-book" aria-hidden="true"></i>
            </div>
            <div class="hero-line" aria-hidden="true"></div>
          </div>

          <div class="hero-kicker">Featured Collection</div>
          <div class="media-quote quote-maxw">“<?= e($heroQuote) ?>”</div>
          <div class="hero-author">— <?= e($heroAuthor) ?></div>

          <div class="hero-actions">
            <a class="hero-cta" href="category_list.php">
              <span class="hero-cta-copy">
                <i class="bi bi-book-half" aria-hidden="true"></i>
                <span>Katalogni ko‘rish</span>
              </span>
              <i class="bi bi-arrow-right-short hero-cta-arrow" aria-hidden="true"></i>
            </a>
          </div>

          <div class="hero-sketch" aria-hidden="true">
            <i class="bi bi-journal-bookmark"></i>
            <i class="bi bi-journal-richtext"></i>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<main class="container py-4">

<!-- ================= NEWS SECTION ================= -->
<section class="mb-4">
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
    <div>
      <div class="section-title">So'ngi yangiliklar</div>
      <div class="muted">Doimo habardor bo'ling</div>
    </div>
  </div>

  <div class="swiper" id="newsSwiper">
    <div class="swiper-wrapper">
      <?php foreach ($news as $n): ?>
        <div class="swiper-slide">
          <div class="stats-box news-card w-100">
            <?php if (!empty($n['image_path'])): ?>
              <img class="news-thumb" src="<?= e($n['image_path']) ?>" alt="news">
            <?php else: ?>
              <img class="news-thumb" src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=1200&q=80" alt="Library news">
            <?php endif; ?>

            <div class="news-body mt-3">
              <div class="fw-bold fs-6 clamp-2"><?= e($n['title']) ?></div>
              <div class="muted mt-2 clamp-3 news-snippet-maxw"><?= e($n['body']) ?></div>
            </div>

            <?php if (!empty($n['link_url'])): ?>
              <div class="mt-3">
                <a href="<?= e($n['link_url']) ?>" target="_blank" rel="noopener" class="btn btn-orange w-100">
                  Read more
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (!$news): ?>
        <div class="swiper-slide">
          <div class="stats-box">No news yet.</div>
        </div>
      <?php endif; ?>
    </div>

    <div class="swiper-pagination"></div>

    <div class="news-nav">
      <div class="swiper-button-prev news-prev"></div>
      <div class="swiper-button-next news-next"></div>
    </div>
  </div>
</section>

<!-- ================= RECOMMENDED BOOKS (SWIPER + AJAX PAGES) ================= -->
<section class="mb-4">
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
    <div>
      <div class="section-title">Sizga tavsiya qilingan kitoblar</div>
      <div class="muted">Kutubxonadagi kitoblardan tavsiyalar</div>
    </div>
    <a class="btn btn-success" href="category_list.php">Kategoriyalar bo‘yicha qidirish</a>
  </div>

  <div class="swiper mt-3" id="booksSwiper">
    <div class="swiper-wrapper" id="booksSlides">
      <div class="swiper-slide">
        <div class="stats-box">Yuklanmoqda...</div>
      </div>
    </div>
  </div>

  <div class="books-sw-nav">
    <button type="button" class="btn btn-outline-secondary books-prev">Oldingi</button>
    <div class="books-page-indicator muted small" id="booksIndicator"></div>
    <button type="button" class="btn btn-outline-secondary books-next">Keyingi</button>
  </div>
</section>

<!-- STATS_WIDGET_START -->
<?php require_once __DIR__ . "/partials/stats_widget.php"; ?>
<!-- STATS_WIDGET_end -->

<script>
document.addEventListener("DOMContentLoaded", function () {
  if (window.Swiper) {
    new Swiper("#newsSwiper", {
      loop: true,
      slidesPerView: 3,
      spaceBetween: 16,
      autoplay: { delay: 3500, disableOnInteraction: false },
      pagination: { el: "#newsSwiper .swiper-pagination", clickable: true },
      navigation: { nextEl: "#newsSwiper .news-next", prevEl: "#newsSwiper .news-prev" },
      breakpoints: {
        0: { slidesPerView: 1 },
        768: { slidesPerView: 2 },
        992: { slidesPerView: 3 }
      }
    });
  }
});
</script>

<script src="/assets/js/index-books-swiper.js"></script>

<?php require_once __DIR__ . "/partials/footer.php"; ?>
