console.log("book_recs.js loaded ✅");

document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("recGrid");
  const btnPrev = document.getElementById("recPrev");
  const btnNext = document.getElementById("recNext");
  const indicator = document.getElementById("recIndicator");

  const bookId = Number(window.__BOOK_ID__ || 0);
  const catId = Number(window.__BOOK_CAT_ID__ || 0);

  if (!grid || !bookId || !catId) return;

  const BASE = "/";
  const PER_PAGE = 6; // ✅ 3 per row × 2 rows

  let page = 1;
  let totalPages = 1;
  let loading = false;

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function setControls() {
    if (indicator) indicator.textContent = `${page} / ${totalPages}`;
    if (btnPrev) btnPrev.disabled = loading || page <= 1;
    if (btnNext) btnNext.disabled = loading || page >= totalPages;
  }

  function render(books) {
    if (!books || books.length === 0) {
      return `<div class="col-12"><div class="stats-box">Boshqa kitob topilmadi.</div></div>`;
    }

    let html = "";
    for (const b of books) {
      const cover = (b.book_cover_path || "").trim();
      const coverSrc = cover || "https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?auto=format&fit=crop&w=900&q=80";
      const id = parseInt(b.id, 10) || 0;

      html += `
        <div class="col-6 col-md-4 col-lg-4">
          <a class="card-book d-block" href="book.php?id=${id}">
            <div class="cover cover-h200">
              <img src="${esc(coverSrc)}" alt="cover">
            </div>
            <div class="p">
              <div class="fw-bold clamp-2">${esc(b.book_name)}</div>
              <div class="muted small clamp-1">${esc(b.book_author || "Unknown")}</div>
            </div>
          </a>
        </div>
      `;
    }
    return html;
  }

  async function load(p) {
    if (loading) return;
    loading = true;
    setControls();

    grid.innerHTML = `<div class="col-12"><div class="stats-box">Yuklanmoqda...</div></div>`;

    try {
      const url =
        `${BASE}fetch_related_books_simple.php` +
        `?category_id=${encodeURIComponent(catId)}` +
        `&exclude_id=${encodeURIComponent(bookId)}` +
        `&p=${encodeURIComponent(p)}` +
        `&per_page=${encodeURIComponent(PER_PAGE)}`;

      const res = await fetch(url, { cache: "no-store" });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();
      if (!data || !data.success) throw new Error(data?.message || "unknown error");

      page = parseInt(data.page, 10) || p;
      totalPages = parseInt(data.total_pages, 10) || 1;

      grid.innerHTML = render(data.books);

      loading = false;
      setControls();
    } catch (e) {
      grid.innerHTML = `<div class="col-12"><div class="stats-box">Xatolik: ${esc(e.message || e)}</div></div>`;
      loading = false;
      setControls();
    }
  }

  if (btnPrev) btnPrev.addEventListener("click", () => load(page - 1));
  if (btnNext) btnNext.addEventListener("click", () => load(page + 1));

  load(1);
});
