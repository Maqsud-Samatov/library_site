console.log("category_list.js loaded ✅");

document.addEventListener("DOMContentLoaded", () => {
  const tabsWrap = document.getElementById("catTabs");
  const grid = document.getElementById("booksGrid");
  const titleEl = document.getElementById("activeCatTitle");
  const searchEl = document.getElementById("bookSearch");

  const btnPrev = document.getElementById("catPrev");
  const btnNext = document.getElementById("catNext");
  const indicator = document.getElementById("catIndicator");
  const countEl = document.getElementById("bookCount");

  if (!grid) return;

  const BASE = "/";
  const PER_PAGE = 8; // ✅ LG(4col) => 2 row

  let catId = Number(window.__LIB_FIRST_CAT__ || 0);
  let page = 1;
  let totalPages = 1;
  let total = 0;
  let q = "";
  let loading = false;

  let ac = null; // AbortController for fast search

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
    if (countEl) countEl.textContent = String(total || 0);
    if (btnPrev) btnPrev.disabled = loading || page <= 1;
    if (btnNext) btnNext.disabled = loading || page >= totalPages;
  }

  function setActiveTab(btn) {
    // ✅ active class va aria-selected ni qo'lda boshqaramiz
    document.querySelectorAll("#catTabs .cat-pill").forEach((b) => {
      b.classList.remove("active");
      b.setAttribute("aria-selected", "false");
    });
    btn.classList.add("active");
    btn.setAttribute("aria-selected", "true");
  }

  function render(books) {
    if (!books || books.length === 0) {
      return `<div class="col-12"><div class="stats-box">Hech narsa topilmadi.</div></div>`;
    }

    let html = "";
    for (const b of books) {
      const cover = (b.book_cover_path || "").trim();
      const coverSrc = cover || "https://images.unsplash.com/photo-1476275466078-4007374efbbe?auto=format&fit=crop&w=900&q=80";
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
    return html;
  }

  async function load(nextPage) {
    if (loading) return;
    if (!catId) return;

    nextPage = Math.max(1, nextPage || 1);

    if (ac) ac.abort();
    ac = new AbortController();

    loading = true;
    setControls();

    grid.innerHTML = `<div class="col-12"><div class="stats-box">Yuklanmoqda...</div></div>`;

    try {
      const url =
        `${BASE}fetch_category_books.php` +
        `?cat_id=${encodeURIComponent(catId)}` +
        `&p=${encodeURIComponent(nextPage)}` +
        `&per_page=${encodeURIComponent(PER_PAGE)}` +
        `&q=${encodeURIComponent(q || "")}`;

      const res = await fetch(url, { cache: "no-store", signal: ac.signal });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();
      if (!data || !data.success) throw new Error(data?.message || "unknown error");

      page = parseInt(data.page, 10) || nextPage;
      totalPages = parseInt(data.total_pages, 10) || 1;
      total = parseInt(data.total, 10) || 0;

      grid.innerHTML = render(data.books);

      loading = false;
      setControls();
    } catch (e) {
      if (String(e?.name) === "AbortError") return;
      grid.innerHTML = `<div class="col-12"><div class="stats-box">Xatolik: ${esc(e.message || e)}</div></div>`;
      loading = false;
      setControls();
    }
  }

  // ✅ Category click: active + title + page=1 + load
  if (tabsWrap) {
    tabsWrap.addEventListener("click", (ev) => {
      const btn = ev.target.closest(".cat-pill");
      if (!btn) return;

      const newCat = parseInt(btn.getAttribute("data-cat"), 10) || 0;
      if (!newCat) return;

      catId = newCat;
      page = 1;

      setActiveTab(btn);

      const name = btn.querySelector(".fw-bold")?.textContent?.trim() || "";
      if (titleEl) titleEl.textContent = name;

      load(1);
    });
  }

  // ✅ Search: server-side (global, barcha page bo'yicha)
  let t = null;
  if (searchEl) {
    searchEl.addEventListener("input", () => {
      clearTimeout(t);
      t = setTimeout(() => {
        q = (searchEl.value || "").trim();
        page = 1;
        load(1);
      }, 250);
    });
  }

  // Pagination
  if (btnPrev) btnPrev.addEventListener("click", () => load(page - 1));
  if (btnNext) btnNext.addEventListener("click", () => load(page + 1));

  // Initial load
  load(1);
});
