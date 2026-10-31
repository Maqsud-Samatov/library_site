// Kitob o'quvchi: PDF ni PDF.js bilan canvas larga chizadi (yuklab olish / chop etish tugmalari yo'q).
// Fayl view_pdf.php dan bir martalik chipta bilan, XOR bilan shifrlangan holda keladi.
(() => {
  const root = document.getElementById('pdfReader');
  if (!root || !window.pdfjsLib) return;

  const bookId = root.dataset.id;
  const token = root.dataset.token;
  const key = Uint8Array.from(atob(root.dataset.key), (c) => c.charCodeAt(0));
  // Kalit va chiptani sahifadan darhol olib tashlaymiz
  root.removeAttribute('data-key');
  root.removeAttribute('data-token');

  const pagesEl = root.querySelector('[data-pages]');
  const statusEl = root.querySelector('[data-status]');
  const pageInfoEl = root.querySelector('[data-page-info]');
  const zoomInfoEl = root.querySelector('[data-zoom-info]');

  const ZOOMS = [0.6, 0.75, 0.9, 1, 1.25, 1.5, 1.75, 2, 2.5];
  let zoomIndex = ZOOMS.indexOf(1);
  let pdf = null;
  let pages = [];   // { num, wrap, canvas, ratio, visible, rendered, busy, task }
  let current = 1;

  // ---------- Yuklab olish / chop etishni to'sish ----------
  document.addEventListener('keydown', (e) => {
    const k = (e.key || '').toLowerCase();
    const mod = e.ctrlKey || e.metaKey;
    if ((mod && ['s', 'p', 'u'].includes(k)) ||
        k === 'f12' ||
        (mod && e.shiftKey && ['i', 'j', 'c', 's'].includes(k))) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);
  root.addEventListener('contextmenu', (e) => e.preventDefault());
  root.addEventListener('dragstart', (e) => e.preventDefault());

  // ---------- Holat ----------
  function setStatus(text, isError) {
    statusEl.textContent = text || '';
    statusEl.hidden = !text;
    statusEl.classList.toggle('is-error', !!isError);
  }

  function pageWidth() {
    const avail = pagesEl.clientWidth - 24;
    return Math.max(200, Math.min(avail, 900) * ZOOMS[zoomIndex]);
  }

  // ---------- Faylni olish va shifrini ochish ----------
  async function fetchBook() {
    const body = new FormData();
    body.append('id', bookId);
    body.append('t', token);
    const res = await fetch('view_pdf.php', {
      method: 'POST',
      body,
      headers: { 'X-PDF-Reader': '1' },
      credentials: 'same-origin',
      cache: 'no-store',
    });
    if (!res.ok) throw new Error((await res.text()) || 'Kitobni yuklab bo\'lmadi');

    const total = Number(res.headers.get('Content-Length')) || 0;
    const reader = res.body.getReader();
    const chunks = [];
    let received = 0;
    for (;;) {
      const { done, value } = await reader.read();
      if (done) break;
      chunks.push(value);
      received += value.length;
      if (total) setStatus(`Yuklanmoqda… ${Math.floor((received / total) * 100)}%`);
    }

    const data = new Uint8Array(received);
    let offset = 0;
    for (const chunk of chunks) {
      data.set(chunk, offset);
      offset += chunk.length;
    }
    const keyLen = key.length;
    for (let i = 0; i < data.length; i++) data[i] ^= key[i % keyLen];
    key.fill(0);
    return data;
  }

  // ---------- Sahifalarni chizish ----------
  async function render(p) {
    if (p.rendered || p.busy || !p.visible) return;
    p.busy = true; // bitta canvas ga bir vaqtda ikki marta chizilmasin
    p.stale = false;
    try {
      await draw(p);
    } finally {
      p.busy = false;
    }
    if (!p.visible) {
      release(p);
    } else if (p.stale) {
      // chizish paytida o'lcham o'zgardi — yangi o'lchamda qayta chizamiz
      p.stale = false;
      render(p);
    }
  }

  async function draw(p) {
    const page = await pdf.getPage(p.num);
    if (!p.visible) return;

    const base = page.getViewport({ scale: 1 });
    p.ratio = base.height / base.width;
    const cssW = pageWidth();
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const viewport = page.getViewport({ scale: (cssW / base.width) * dpr });

    p.wrap.style.height = `${cssW * p.ratio}px`;
    p.canvas.width = Math.floor(viewport.width);
    p.canvas.height = Math.floor(viewport.height);
    p.task = page.render({ canvasContext: p.canvas.getContext('2d'), viewport });
    try {
      await p.task.promise;
      p.rendered = true;
    } catch (e) {
      // ekrandan chiqib ketgani uchun bekor qilingan
    } finally {
      p.task = null;
    }
  }

  // Ekrandan uzoqlashgan sahifani xotiradan bo'shatamiz
  function release(p) {
    if (p.busy) p.stale = true;
    if (p.task) p.task.cancel();
    p.canvas.width = 0;
    p.canvas.height = 0;
    p.rendered = false;
  }

  function layout() {
    const w = pageWidth();
    for (const p of pages) {
      p.wrap.style.width = `${w}px`;
      p.wrap.style.height = `${w * p.ratio}px`;
      release(p);
    }
    for (const p of pages) render(p);
    zoomInfoEl.textContent = `${Math.round(ZOOMS[zoomIndex] * 100)}%`;
  }

  function buildPages(firstRatio) {
    const near = new IntersectionObserver((entries) => {
      for (const entry of entries) {
        const p = entry.target.__page;
        p.visible = entry.isIntersecting;
        if (p.visible) render(p);
        else if (p.rendered || p.task) release(p);
      }
    }, { root: null, rootMargin: '1500px 0px' });

    const centre = new IntersectionObserver((entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          current = entry.target.__page.num;
          pageInfoEl.textContent = `${current} / ${pdf.numPages}`;
        }
      }
    }, { root: null, rootMargin: '-50% 0px -50% 0px' });

    const frag = document.createDocumentFragment();
    pages = [];
    for (let num = 1; num <= pdf.numPages; num++) {
      const wrap = document.createElement('div');
      wrap.className = 'pdf-reader-page';
      const canvas = document.createElement('canvas');
      wrap.appendChild(canvas);
      const p = { num, wrap, canvas, ratio: firstRatio, visible: false, rendered: false, busy: false, stale: false, task: null };
      wrap.__page = p;
      pages.push(p);
      frag.appendChild(wrap);
    }
    pagesEl.appendChild(frag);
    layout();
    for (const p of pages) {
      near.observe(p.wrap);
      centre.observe(p.wrap);
    }
    pageInfoEl.textContent = `1 / ${pdf.numPages}`;
  }

  root.querySelectorAll('[data-zoom]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const next = zoomIndex + Number(btn.dataset.zoom);
      if (next < 0 || next >= ZOOMS.length || !pdf) return;
      zoomIndex = next;
      const keep = current;
      layout();
      pages[keep - 1].wrap.scrollIntoView({ block: 'start' });
    });
  });

  let resizeTimer = 0;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { if (pdf) layout(); }, 200);
  });

  (async () => {
    try {
      const data = await fetchBook();
      setStatus('Ochilmoqda…');
      pdf = await pdfjsLib.getDocument({ data, isEvalSupported: false }).promise;
      const first = (await pdf.getPage(1)).getViewport({ scale: 1 });
      setStatus('');
      buildPages(first.height / first.width);
    } catch (err) {
      console.error(err);
      setStatus(err && err.message ? err.message : 'Kitobni ochib bo\'lmadi', true);
    }
  })();
})();
