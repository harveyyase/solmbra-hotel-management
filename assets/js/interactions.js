/* =============================================================
   interactions.js — the interactive half of animations.css.
   No dependencies. Include once per page with:
     <script src="interactions.js" defer></script>
   It watches the DOM, so content your own scripts render later
   (tables, stats, toasts) gets the same treatment.
   ============================================================= */
(() => {
  'use strict';

  const RM = window.matchMedia('(prefers-reduced-motion: reduce)');
  const root = document.documentElement;
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const t0 = performance.now();

  /* ---------- selectors ---------- */
  const SPOT   = '.stat-card,.contact-card,.profile-summary,.profile-details,.dash-card,.report-card';
  const RIPPLE = '.primary-btn,.secondary-btn,.btn-glass,.btn-submit,.btn-cancel,.submit-btn,.github-btn,' +
                 '.edit-btn,.save-btn,.cancel-btn,.mini-btn,.timer-preset,.back-link,.logout-btn,.folder-tab';
  // Containers whose children get a --i index used for staggered delays
  const STAGGER = '.stats,.dash-grid,.report-grid,.contact-grid,.quick-actions,.fields,.form-grid,' +
                  '.folder-tabs,.bar-chart,.inbox-list,tbody,.folder-body,.auth-container,.hero-actions';

  const replay = (el, cls, cleanupMs) => {
    el.classList.remove(cls);
    void el.offsetWidth; // force reflow so the animation restarts
    el.classList.add(cls);
    if (cleanupMs) {
      clearTimeout(el._fxTimer);
      el._fxTimer = setTimeout(() => el.classList.remove(cls), cleanupMs);
    }
  };

  /* ---------- 1. Cursor glow + card tilt / spotlight ---------- */
  let frame = 0, px = 0, py = 0, pTarget = null, pType = 'mouse', lastCard = null;

  const resetCard = (el) => {
    el.style.removeProperty('--rx');
    el.style.removeProperty('--ry');
  };

  const tick = () => {
    frame = 0;
    root.style.setProperty('--cx', px + 'px');
    root.style.setProperty('--cy', py + 'px');

    const card = pTarget && pTarget.closest ? pTarget.closest(SPOT) : null;
    if (lastCard && lastCard !== card) resetCard(lastCard);
    lastCard = card;
    if (!card) return;

    const r = card.getBoundingClientRect();
    const x = (px - r.left) / r.width;
    const y = (py - r.top) / r.height;
    card.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
    card.style.setProperty('--my', (y * 100).toFixed(1) + '%');

    // Tilt everything except the big details panel
    if (pType === 'mouse' && !card.classList.contains('profile-details')) {
      const max = card.matches('.dash-card,.report-card') ? 2 : 4;
      card.style.setProperty('--ry', ((x - 0.5) * 2 * max).toFixed(2) + 'deg');
      card.style.setProperty('--rx', ((0.5 - y) * 2 * max).toFixed(2) + 'deg');
    }
  };

  document.addEventListener('pointermove', (e) => {
    if (RM.matches) return;
    px = e.clientX; py = e.clientY; pTarget = e.target; pType = e.pointerType;
    if (!frame) frame = requestAnimationFrame(tick);
  }, { passive: true });

  root.addEventListener('mouseleave', () => {
    if (lastCard) resetCard(lastCard);
    lastCard = null;
  });

  /* ---------- 2. Button ripple ---------- */
  document.addEventListener('pointerdown', (e) => {
    if (RM.matches || e.button) return;
    const btn = e.target.closest && e.target.closest(RIPPLE);
    if (!btn || btn.disabled) return;
    const r = btn.getBoundingClientRect();
    const d = Math.max(r.width, r.height) * 2;
    const s = document.createElement('span');
    s.className = 'fx-ripple';
    s.style.cssText = `width:${d}px;height:${d}px;left:${e.clientX - r.left - d / 2}px;top:${e.clientY - r.top - d / 2}px`;
    btn.appendChild(s);
    s.addEventListener('animationend', () => s.remove());
  }, { passive: true });

  /* ---------- 3. Count-up numbers ---------- */
  const NUM = /^(\D*?)(-?\d[\d,]*(?:\.\d+)?)([\s\S]*)$/;
  const lastValues = new Map();

  const keyFor = (el) => {
    const card = el.closest('.stat-card,.report-card');
    const label = card && card.querySelector('.stat-label,h4');
    return label ? label.textContent.trim() : el.className + '|' + $$('.stat-value,.report-amount').indexOf(el);
  };

  const countUp = (el) => {
    if (el.dataset.fxBusy || el.children.length) return;
    const text = el.textContent.trim();
    if (el.dataset.fxFinal === text) return;
    el.dataset.fxFinal = text;

    const m = !/[:\/]/.test(text) && NUM.exec(text);
    if (!m) return;
    const raw = m[2];
    const target = parseFloat(raw.replace(/,/g, ''));
    if (!isFinite(target)) return;

    const dec = (raw.split('.')[1] || '').length;
    const group = raw.includes(',');
    const key = keyFor(el);
    const from = lastValues.has(key) ? lastValues.get(key) : 0;
    lastValues.set(key, target);
    if (RM.matches || from === target) return;

    const fmt = (v) => v.toLocaleString('en-US', {
      minimumFractionDigits: dec, maximumFractionDigits: dec, useGrouping: group
    });
    const start = performance.now(), dur = 900;
    el.dataset.fxBusy = '1';
    const step = (now) => {
      const p = Math.min(1, (now - start) / dur);
      const eased = 1 - Math.pow(1 - p, 3);
      if (p < 1) {
        el.textContent = m[1] + fmt(from + (target - from) * eased) + m[3];
        requestAnimationFrame(step);
      } else {
        el.textContent = text;
        delete el.dataset.fxBusy;
      }
    };
    requestAnimationFrame(step);
  };

  /* ---------- 4. Dashboard tabs: replay the folder animation ---------- */
  let lastTabClick = 0, tabWasActive = false;

  document.addEventListener('click', (e) => {
    const tab = e.target.closest && e.target.closest('.folder-tab');
    if (tab) tabWasActive = tab.classList.contains('active');
  }, true); // capture: runs before the app's own handler changes .active

  document.addEventListener('click', (e) => {
    const tab = e.target.closest && e.target.closest('.folder-tab');
    if (!tab || tabWasActive || RM.matches) return;
    lastTabClick = Date.now();
    const body = document.querySelector('.folder-body');
    if (body) { stagger(); replay(body, 'fx-swap', 1400); }
  });

  /* ---------- 5. Staggered delays + scan ---------- */
  function stagger() {
    $$(STAGGER).forEach((parent) => {
      Array.from(parent.children).forEach((child, i) => {
        child.style.setProperty('--i', Math.min(i, 12));
      });
    });
  }

  function scan() {
    stagger();
    $$('.stat-value,.report-amount').forEach(countUp);

    // A folder-body that just appeared (first load, or right after a tab click) gets the cascade
    $$('.folder-body').forEach((body) => {
      if (body.dataset.fx) return;
      body.dataset.fx = '1';
      const booting = performance.now() - t0 < 2500;
      if (!RM.matches && (booting || Date.now() - lastTabClick < 800)) replay(body, 'fx-swap', 1400);
    });
  }

  /* ---------- 6. Login: shake the card when an error appears ---------- */
  let lastShake = 0;
  function maybeShake(node) {
    const el = node.nodeType === 3 ? node.parentElement : node;
    if (!el || !el.closest) return;
    const msg = el.closest('.form-message');
    if (!msg || !msg.matches('.error.show') || !msg.textContent.trim()) return;
    if (Date.now() - lastShake < 400 || RM.matches) return;
    lastShake = Date.now();
    const card = msg.closest('.auth-container');
    if (card) replay(card, 'fx-shake', 600);
  }

  /* ---------- 7. Watch the DOM so late-rendered content is handled ---------- */
  let scheduled = false;
  const schedule = () => {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(() => { scheduled = false; scan(); });
  };

  const isNoise = (m) => {
    const t = m.target.nodeType === 3 ? m.target.parentElement : m.target;
    if (t && t.closest && t.closest('[data-fx-busy]')) return true;           // count-up ticking
    const nodes = [...m.addedNodes, ...m.removedNodes];
    return nodes.length > 0 && nodes.every((n) => n.classList && n.classList.contains('fx-ripple'));
  };

  new MutationObserver((mutations) => {
    let work = false;
    for (const m of mutations) {
      if (m.type === 'attributes') { maybeShake(m.target); continue; }
      if (isNoise(m)) continue;
      maybeShake(m.target);
      work = true;
    }
    if (work) schedule();
  }).observe(document.documentElement, {
    childList: true, subtree: true, characterData: true,
    attributes: true, attributeFilter: ['class']
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan);
  else scan();
})();