// ============================================================
// Shell 2026 — comportamientos del layout (portado de Adminator)
//  - toggle de tema (icono + persistencia)
//  - dropdowns abrir/cerrar
//  - grupos del sidebar expandir/colapsar (con flyout en modo rail)
//  - fecha del hero
//  - drawer móvil off-canvas
// El script early-paint en cada layout fija el data-theme inicial
// para evitar el flash; este módulo solo maneja los toggles en runtime.
// ============================================================

const STORE_KEY = 'wally-theme';

const SUN_ICON  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>';
const MOON_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';

function initThemeToggle() {
  const root = document.documentElement;
  const toggle = document.getElementById('themeToggle');
  if (!toggle) return;

  const update = () => {
    toggle.innerHTML = root.getAttribute('data-theme') === 'dark' ? SUN_ICON : MOON_ICON;
  };
  update();

  toggle.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem(STORE_KEY, next); } catch { /* localStorage puede no estar disponible */ }
    update();
  });
}

function initHeroDate() {
  const el = document.getElementById('heroDate');
  if (!el) return;
  const fmt = new Intl.DateTimeFormat('es-ES', {
    weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
  }).format(new Date());
  const parts = fmt.replace(/,/g, '').split(' ');
  el.textContent = `${parts[0]} · ${parts[1]} ${parts[2]} · ${parts[3]}`;
}

function initNavGroups() {
  const railMQ = window.matchMedia('(min-width: 721px) and (max-width: 1100px)');

  const setOpen = (group, open) => {
    group.classList.toggle('is-open', open);
    group.querySelector('[data-nav-toggle]')?.setAttribute('aria-expanded', String(open));
  };

  const positionFlyout = (group) => {
    const trigger = group.querySelector('[data-nav-toggle]');
    const submenu = group.querySelector('.nav-submenu');
    if (!trigger || !submenu) return;
    const r = trigger.getBoundingClientRect();
    const h = submenu.offsetHeight;
    let top = Math.round(r.top);
    const maxTop = window.innerHeight - h - 8;
    if (top > maxTop) top = Math.max(8, maxTop);
    submenu.style.left = `${Math.round(r.right + 10)}px`;
    submenu.style.top = `${top}px`;
  };

  document.querySelectorAll('[data-nav-toggle]').forEach((a) => {
    a.addEventListener('click', (e) => {
      const group = a.closest('[data-nav-group]');
      if (!group) return;
      e.stopPropagation();
      const willOpen = !group.classList.contains('is-open');
      if (railMQ.matches) {
        document.querySelectorAll('[data-nav-group].is-open').forEach((g) => {
          if (g !== group) setOpen(g, false);
        });
      }
      setOpen(group, willOpen);
      if (willOpen && railMQ.matches) positionFlyout(group);
    });

    a.addEventListener('keydown', (e) => {
      const group = a.closest('[data-nav-group]');
      if (!group) return;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setOpen(group, true);
        group.querySelector('.nav-submenu a')?.focus();
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setOpen(group, false);
      }
    });
  });

  document.addEventListener('click', (e) => {
    if (!railMQ.matches) return;
    if (e.target.closest('[data-nav-group]')) return;
    document.querySelectorAll('[data-nav-group].is-open').forEach((g) => setOpen(g, false));
  });

  document.querySelectorAll('[data-nav-group]').forEach((group) => {
    group.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      setOpen(group, false);
      group.querySelector('[data-nav-toggle]')?.focus();
    });
  });

  const reposition = () => {
    if (!railMQ.matches) return;
    document.querySelectorAll('[data-nav-group].is-open').forEach(positionFlyout);
  };
  const sidebar = document.querySelector('.d-sidebar');
  if (sidebar) sidebar.addEventListener('scroll', reposition, { passive: true });
  window.addEventListener('resize', reposition);
}

function initDropdowns() {
  const closeAll = (except) => {
    document.querySelectorAll('.dd-wrap.is-open').forEach((w) => {
      if (w !== except) w.classList.remove('is-open');
    });
  };

  document.querySelectorAll('[data-dropdown]').forEach((trigger) => {
    const wrap = trigger.closest('.dd-wrap');
    if (!wrap) return;
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const willOpen = !wrap.classList.contains('is-open');
      closeAll(wrap);
      wrap.classList.toggle('is-open', willOpen);
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.dd-wrap')) closeAll();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll();
  });
}

function initTodos() {
  document.querySelectorAll('.todo-check').forEach((cb) => {
    cb.addEventListener('change', () => {
      const item = cb.closest('.todo-item');
      if (item) item.classList.toggle('is-done', cb.checked);
    });
  });
}

function initMobileDrawer() {
  const body = document.body;
  if (!body) return;

  if (!document.querySelector('.drawer-backdrop')) {
    const backdrop = document.createElement('div');
    backdrop.className = 'drawer-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    body.appendChild(backdrop);
    backdrop.addEventListener('click', closeDrawer);
  }

  function openDrawer() {
    body.classList.add('has-drawer-open');
  }
  function closeDrawer() {
    body.classList.remove('has-drawer-open');
  }

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-drawer-open]');
    if (trigger) { e.preventDefault(); openDrawer(); return; }
    const linkInDrawer = e.target.closest('.d-sidebar a[href]:not([href^="#"]):not([href="javascript:void(0)"])');
    if (body.classList.contains('has-drawer-open') && linkInDrawer) {
      closeDrawer();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && body.classList.contains('has-drawer-open')) closeDrawer();
  });
}

export function initShellBehaviors() {
  initThemeToggle();
  initHeroDate();
  initNavGroups();
  initDropdowns();
  initTodos();
  initMobileDrawer();
}
