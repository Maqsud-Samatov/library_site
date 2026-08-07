console.log("index-books-swiper.js loaded ✅");

document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.getElementById("booksSlides");     // swiper-wrapper
  const indicator = document.getElementById("booksIndicator");
  const btnPrev = document.querySelector(".books-prev");
  const btnNext = document.querySelector(".books-next");

  if (!wrapper) {
    console.error("booksSlides not found");
    return;
  }

  const BASE = "/";
  let page = 1;
  let totalPages = 1;
  let loading = false;

  // Optional Swiper instance (agar kerak bo'lsa)
  let sw = null;
  if (window.Swiper) {
    sw = new Swiper("#booksSwiper", {
      slidesPerView: 1,
      spaceBetween: 0,
      allowTouchMove: false, // pagination tugmalar bilan
    });
  }

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function renderGrid(books) {
    if (!books || books.length === 0) {
      return `<div class="stats-box">Hali kitob yo‘q</div>`;
    }

    let html = `<div class="row g-3 rec-books-grid">`;

    for (const b of books) {
      const cover = (b.book_cover_path || "").trim();
      const coverSrc = cover || "https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=900&q=80";
      const id = parseInt(b.id, 10) || 0;

      html += `
        <div class="col-6 col-md-4 col-lg-3">
          <a class="card-book d-block" href="book.php?id=${id}">
            <div class="cover">
              <img src="${esc(coverSrc)}" alt="cover">
            </div>
            <div class="p">
              <div class="fw-bold clamp-2">${esc(b.book_name)}</div>
              <div class="muted small clamp-1">
                ${esc(b.book_author || "Unknown")}${b.book_year ? " · " + (parseInt(b.book_year, 10) || "") : ""}
              </div>
              <div class="d-flex justify-content-between mt-2 small muted">
                <span>⭐ ${(Number(b.rating_avg || 0)).toFixed(2)} (${parseInt(b.rating_count || 0, 10) || 0})</span>
                <span>👁 ${parseInt(b.read_count || 0, 10) || 0}</span>
              </div>
            </div>
          </a>
        </div>
      `;
    }

    html += `</div>`;
    return html;
  }

  function setControls() {
    if (indicator) indicator.textContent = `${page} / ${totalPages}`;
    if (btnPrev) btnPrev.disabled = loading || page <= 1;
    if (btnNext) btnNext.disabled = loading || page >= totalPages;
  }

  async function load(p) {
    if (loading) return;

    // limit
    p = Math.max(1, Math.min(p, totalPages || 1));

    loading = true;
    setControls();

    wrapper.innerHTML = `
      <div class="swiper-slide">
        <div class="stats-box">Yuklanmoqda...</div>
      </div>
    `;
    if (sw) sw.update();

    try {
      const url = `${BASE}fetch_books_pages.php?p=${encodeURIComponent(p)}`;
      const res = await fetch(url, { cache: "no-store" });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const data = await res.json();

      if (!data || !data.success) {
        wrapper.innerHTML = `
          <div class="swiper-slide">
            <div class="stats-box">Xatolik: ${esc(data?.message || "unknown")}</div>
          </div>
        `;
        loading = false;
        setControls();
        if (sw) sw.update();
        return;
      }

      page = parseInt(data.page, 10) || p;
      totalPages = parseInt(data.total_pages, 10) || 1;

      wrapper.innerHTML = `
        <div class="swiper-slide">
          ${renderGrid(data.books)}
        </div>
      `;

      loading = false;
      setControls();
      if (sw) sw.update();
    } catch (err) {
      wrapper.innerHTML = `
        <div class="swiper-slide">
          <div class="stats-box">Xatolik: ${esc(err.message || err)}</div>
        </div>
      `;
      loading = false;
      setControls();
      if (sw) sw.update();
    }
  }

  if (btnPrev) btnPrev.addEventListener("click", () => load(page - 1));
  if (btnNext) btnNext.addEventListener("click", () => load(page + 1));

  load(1);
});
