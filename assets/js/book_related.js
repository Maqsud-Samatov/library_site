console.log("book_related.js loaded ✅");

document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("relGrid");
  const btnPrev = document.getElementById("relPrev");
  const btnNext = document.getElementById("relNext");
  const indicator = document.getElementById("relIndicator");

  const bookId = Number(window.__BOOK_ID__ || 0);
  const catId = Number(window.__BOOK_FIRST_CAT__ || 0);

  if (!grid || !bookId) return;

  const BASE = "/library3/";
  const PER_PAGE = 6; // ✅ 3x2

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
      const id = parseInt(b.id, 10) || 0;

      html += `
        <div class="col-12 col-md-6 col-lg-4">
          <a class="card-book d-block" href="book.php?id=${id}">
            <div class="cover">
              ${cover ? `<img src="${esc(cover)}" alt="cover">` : `<div class="no-cover">NO COVER</div>`}
            </div>
            <div class="p">
              <div class="fw-bold clamp-2">${esc(b.book_name)}</div>
              <div class="muted small clamp-1">
                ${esc(b.book_author || "Unknown")}${b.book_year ? " · " + (parseInt(b.book_year, 10) || "") : ""}
              </div>
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
        `${BASE}fetch_related_books.php?book_id=${encodeURIComponent(bookId)}` +
        `&cat_id=${encodeURIComponent(catId || 0)}` +
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
