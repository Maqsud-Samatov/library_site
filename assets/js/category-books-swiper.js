(function () {
  const swiperEl = document.getElementById("catBooksSwiper");
  const slidesEl = document.getElementById("catBooksSlides");
  const indicatorEl = document.getElementById("catBooksIndicator");

  if (!swiperEl || !slidesEl || !indicatorEl) return;

  const btnPrev = document.querySelector(".cat-books-prev");
  const btnNext = document.querySelector(".cat-books-next");

  let activeCat = parseInt(swiperEl.dataset.catId || "0", 10);
  if (!activeCat || activeCat < 0) activeCat = 0;

  const PER_PAGE = 6; // 3x2 = 6
  let totalPages = 1;
  let currentPage = 1;
  let sw = null;

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function cardHTML(b) {
    const cover = b.book_cover_path
      ? `<img src="${esc(b.book_cover_path)}" alt="cover">`
      : `NO COVER`;

    const authorYear = [
      b.book_author ? esc(b.book_author) : "Unknown",
      b.book_year ? ` · ${parseInt(b.book_year,10)}` : ""
    ].join("");

    const rating = Number(b.rating_avg || 0).toFixed(2);
    const rc = parseInt(b.rating_count || 0, 10);
    const reads = parseInt(b.read_count || 0, 10);

    return `
      <div class="col-12 col-md-4">
        <a class="card-book d-block" href="book.php?id=${parseInt(b.id,10)}">
          <div class="cover cover-h240">${cover}</div>
          <div class="p">
            <div class="fw-bold">${esc(b.book_name)}</div>
            <div class="muted small">${authorYear}</div>
            <div class="d-flex justify-content-between mt-2 small muted">
              <span>⭐ ${rating} (${rc})</span>
              <span>👁 ${reads}</span>
            </div>
          </div>
        </a>
      </div>
    `;
  }

  function slideHTML(books) {
    const items = (books || []).map(cardHTML).join("");
    const empty = `<div class="col-12"><div class="muted">Kitob topilmadi.</div></div>`;
    return `
      <div class="swiper-slide">
        <div class="rec-books-grid">
          <div class="row g-3">
            ${items || empty}
          </div>
        </div>
      </div>
    `;
  }

  async function loadPage(page) {
    slidesEl.innerHTML = `
      <div class="swiper-slide">
        <div class="stats-box">Yuklanmoqda...</div>
      </div>
    `;

    const url = `fetch_books.php?cat=${encodeURIComponent(activeCat)}&page=${encodeURIComponent(page)}&per_page=${PER_PAGE}`;
    const res = await fetch(url, { headers: { "Accept": "application/json" } });
    const data = await res.json();

    if (!data || data.success !== true) {
      slidesEl.innerHTML = `
        <div class="swiper-slide">
          <div class="stats-box">Xatolik: ${esc(data?.message || "Unknown")}</div>
        </div>
      `;
      totalPages = 1;
      currentPage = 1;
      indicatorEl.textContent = "";
      return;
    }

    totalPages = parseInt(data.total_pages || 1, 10);
    currentPage = parseInt(data.page || 1, 10);

    // ✅ bitta slide ichida 6 ta kitob
    slidesEl.innerHTML = slideHTML(data.books);

    // swiper init/update
    if (sw) {
      sw.update();
      sw.slideTo(0, 0);
    } else if (window.Swiper) {
      sw = new Swiper("#catBooksSwiper", {
        slidesPerView: 1,
        spaceBetween: 16,
        allowTouchMove: true,
        loop: false
      });
    }

    indicatorEl.textContent = `${currentPage} / ${totalPages}`;

    // buttons enable/disable
    if (btnPrev) btnPrev.disabled = currentPage <= 1;
    if (btnNext) btnNext.disabled = currentPage >= totalPages;
  }

  function goPrev() {
    if (currentPage > 1) loadPage(currentPage - 1);
  }
  function goNext() {
    if (currentPage < totalPages) loadPage(currentPage + 1);
  }

  if (btnPrev) btnPrev.addEventListener("click", goPrev);
  if (btnNext) btnNext.addEventListener("click", goNext);

  // 🔥 Category bosilganda activeCat o‘zgarishi kerak bo‘lsa:
  // Siz category linklarni bosganda sahifa reload bo‘lyapti (GET cat=..).
  // Shunda PHP `data-cat-id` ni yangilab beradi. JS ham qaytadan ishlaydi.

  // start
  loadPage(1);
})();
