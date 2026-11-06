/* InduRobotic · Paneles contraíbles y responsivos (escritorio, tablet y móvil)
   Lo inyecta sim.php en TODAS las simulaciones. No requiere editar los HTML de sims/:
   detecta en tiempo de ejecución los paneles flotantes (position fixed/absolute) y las barras laterales
   (aside, #inspector-panel, columnas del tablero de inspección) y les agrega una barra para contraerlos.
   - Flotante contraído  → queda como una pastilla pequeña en su misma esquina.
   - Acoplado contraído  → se oculta y aparece una pestaña; el 3D recupera el espacio.
   - Pantalla < 900 px   → todo arranca contraído, se abre un panel a la vez y el panel abierto
                           se adapta al ancho (los acoplados pasan a flotar sobre el 3D).
   - Pantallas táctiles  → controles más grandes y texto de 16 px en campos (evita el zoom de iOS). */
(function () {
  'use strict';
  var SMALL = 900;                                   // ancho (px) por debajo del cual se usa el modo compacto
  var CFG = window.INDU || {}, KEY = 'indu.panels.' + (CFG.sim || location.pathname.split('/').pop() || 'sim') + '.';
  var store = {
    get: function (k) { try { return localStorage.getItem(KEY + k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(KEY + k, v); } catch (e) {} }
  };
  var panels = [], seq = 0, small = false;

  /* ---------- Estilos ---------- */
  var css = document.createElement('style');
  css.textContent = [
    '.ip-bar{display:flex;align-items:center;gap:6px;width:100%;box-sizing:border-box;margin:0 0 6px;padding:5px 8px;border:1px solid rgba(100,116,139,.45);border-radius:8px;background:rgba(15,23,42,.94);color:#e2e8f0;font:700 11px/1.2 Inter,system-ui,sans-serif;letter-spacing:.03em;cursor:pointer;text-align:left;position:sticky;top:0;z-index:30;flex:0 0 auto;align-self:stretch}',
    '.ip-bar:hover{border-color:#38bdf8}.ip-bar .ip-a{color:#38bdf8;font-size:10px;width:10px;display:inline-block}',
    '.ip-bar .ip-t{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '[data-ip].ip-col{width:auto!important;min-width:0!important;height:auto!important;min-height:0!important;max-height:none!important;padding:0!important;overflow:visible!important;background:transparent!important;border:0!important;box-shadow:none!important;backdrop-filter:none!important}',
    '[data-ip].ip-col>:not(.ip-bar){display:none!important}',
    '[data-ip].ip-col .ip-bar{margin:0;width:auto;min-width:96px;max-width:210px}',
    '[data-ip].ip-hid{display:none!important}',
    /* si el panel contiene la cámara web (video), no se oculta con display:none: se saca de la vista para que el seguimiento de manos siga funcionando */
    '[data-ip].ip-hid.ip-vid{display:block!important;position:fixed!important;left:0!important;top:0!important;width:2px!important;height:2px!important;min-height:0!important;padding:0!important;margin:0!important;overflow:hidden!important;pointer-events:none!important;opacity:0!important}',
    '[data-ip].ip-col>.ip-keep{display:block!important;position:absolute!important;left:0!important;top:0!important;width:2px!important;height:2px!important;margin:0!important;padding:0!important;overflow:hidden!important;pointer-events:none!important;opacity:0!important}',
    /* pestañas de los paneles acoplados contraídos */
    '.ip-chip{position:fixed;z-index:2147482000;max-width:calc(100vw - 16px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:8px 11px;border:1px solid rgba(100,116,139,.55);border-radius:8px;background:rgba(15,23,42,.94);color:#e2e8f0;font:700 11px/1 Inter,system-ui,sans-serif;cursor:pointer}',
    '.ip-chip:hover{border-color:#38bdf8}.ip-chip b{color:#38bdf8;font-weight:700}',
    /* modo compacto (tablet vertical y móvil) */
    'body.ip-small [data-ip]:not(.ip-col){width:min(340px,calc(100vw - 24px))!important;max-width:calc(100vw - 24px)!important;max-height:calc(100vh - 24px)!important;max-height:calc(100dvh - 24px)!important;overflow-y:auto!important;z-index:2147481000!important;-webkit-overflow-scrolling:touch}',
    'body.ip-small [data-ip].ip-dk:not(.ip-col){position:fixed!important;top:var(--ip-top,56px)!important;bottom:auto!important;margin:0!important;max-height:calc(100vh - var(--ip-top,56px) - 12px)!important;max-height:calc(100dvh - var(--ip-top,56px) - 12px)!important;box-shadow:0 12px 40px rgba(0,0,0,.6)!important}',
    'body.ip-small [data-ip].ip-dk.ip-l{left:8px!important;right:auto!important}body.ip-small [data-ip].ip-dk.ip-r{right:8px!important;left:auto!important}',
    'body.ip-phone.ip-has-open [data-ip].ip-col,body.ip-phone.ip-has-open .ip-chip{display:none!important}',
    'body.ip-small header{flex-wrap:wrap!important;height:auto!important;min-height:3rem;padding:6px 12px!important;row-gap:6px;column-gap:8px}',
    'body.ip-small header *,body.ip-small footer *{flex-wrap:wrap;min-width:0}',
    'body.ip-small header,body.ip-small footer{max-width:100%}',
    'body.ip-small #hud{white-space:normal!important;text-align:center;max-width:calc(100vw - 24px);font-size:11px!important}',
    /* táctil */
    'body.ip-touch [data-ip] button,body.ip-touch [data-ip] select,body.ip-touch #imq button.b,body.ip-touch #imq select{min-height:36px}',
    'body.ip-touch [data-ip] input[type=text],body.ip-touch [data-ip] input[type=number],body.ip-touch [data-ip] select,body.ip-touch #imq input[type=text],body.ip-touch #imq select{font-size:16px!important}',
    'body.ip-touch [data-ip] input[type=range]{min-height:28px}',
    'body.ip-touch [data-ip] input[type=checkbox],body.ip-touch #imq input[type=checkbox]{width:20px;height:20px}'
  ].join('');
  document.head.appendChild(css);

  /* ---------- Utilidades ---------- */
  function vw() {   // ancho realmente visible (innerWidth se infla si el contenido desborda en móviles)
    var a = [window.innerWidth, document.documentElement.clientWidth, window.visualViewport && window.visualViewport.width].filter(function (x) { return x > 0; });
    return Math.min.apply(null, a);
  }
  function vh() { return window.innerHeight || document.documentElement.clientHeight; }
  function visible(el, cs) { cs = cs || getComputedStyle(el); return cs.display !== 'none' && cs.visibility !== 'hidden'; }
  var SKIP = /^(SCRIPT|STYLE|LINK|META|CANVAS|SVG|VIDEO|A-SCENE|NOSCRIPT|IMG|PRE|TEXTAREA|SELECT|INPUT|BUTTON)$/;
  var NAMES = { ui: 'Controles', leftpanel: 'Controles', recbox: 'Grabación', cam: 'Cámara', cambox: 'Cámara', camcard: 'Cámara', mapbox: 'Mapa', rightcol: 'Panel', l: 'Controles', r: 'Datos', 'inspector-panel': 'Inspector', 'scanner-hud-panel': 'Escáner' };
  function titleOf(el, side) {
    var id = (el.id || '').toLowerCase(); if (NAMES[id]) return NAMES[id];
    if (/(^|\s)lg:col-span-4(\s|$)/.test(el.className || '')) return 'Estación';
    var h = el.querySelector('h1,h2,h3,h4,.title,[class*=title]'), t = h && h.textContent.trim().replace(/\s+/g, ' ');
    if (t && t.length > 1) return t.length > 24 ? t.slice(0, 23) + '…' : t;
    return el.tagName === 'ASIDE' ? 'Panel de control' : (side === 'l' ? 'Panel izq.' : 'Panel der.');
  }

  /* ---------- Detección de paneles ---------- */
  function isDockSel(el) { return el.tagName === 'ASIDE' || el.id === 'inspector-panel' || el.id === 'L' || el.id === 'R' || /(^|\s)lg:col-span-4(\s|$)/.test(el.className || ''); }
  function candidate(el) {
    if (el.nodeType !== 1 || SKIP.test(el.tagName) || el.id === 'imq' || /^ip-/.test(el.className || '') || el.hasAttribute('data-ip-no')) return null;
    if (el.closest && el.closest('a-scene,#imq')) return null;
    var cs = getComputedStyle(el); if (!visible(el, cs)) return null;
    var r = el.getBoundingClientRect(), W = vw(), H = vh();
    var dockSel = isDockSel(el);
    if (r.width < 150 || r.height < (dockSel ? 60 : 90)) return null;
    if (!dockSel && r.width > W * .8 && r.height > H * .8) return null;            // lienzo / contenedor de pantalla completa
    var floating = cs.position === 'fixed' || cs.position === 'absolute';
    var docked = !floating && dockSel && r.width < W * 1.02;                       // en móvil una barra lateral puede ocupar todo el ancho
    if (!floating && !docked) return null;
    if (floating && !(el.querySelector('button,input,select,canvas,video') || (el.textContent || '').trim().length > 12)) return null;
    if (floating && (el.id === 'hud' || /^(top|status-overlay)$/.test(el.id))) return null;   // barras de estado, no paneles
    return { floating: floating, rect: r };
  }
  function scan() {
    // recorrido por niveles (máx. 5), sin entrar en paneles ya detectados ni en la escena 3D
    var level = Array.prototype.slice.call(document.body.children);
    for (var d = 0; d < 5 && level.length; d++) {
      var next = [];
      level.forEach(function (el) {
        if (el.hasAttribute && el.hasAttribute('data-ip')) { return; }
        var c = candidate(el);
        if (c) { register(el, c); return; }
        if (el.nodeType === 1 && !SKIP.test(el.tagName) && el.id !== 'imq') Array.prototype.push.apply(next, el.children);
      });
      level = next;
    }
    // reponer la barra si la simulación reescribió el contenido del panel
    panels.forEach(function (p) { if (!p.el.querySelector(':scope>.ip-bar')) p.el.insertBefore(p.bar, p.el.firstChild); });
  }

  /* ---------- Registro y control de cada panel ---------- */
  function register(el, c) {
    var r = c.rect, side = (!c.floating && r.width > vw() * .6) ? 'l' : ((r.left + r.width / 2) < vw() / 2 ? 'l' : 'r');
    var p = { el: el, id: el.id || ('p' + (++seq)), side: side, docked: !c.floating, top: Math.max(8, Math.round(r.top)), open: true, chip: null };
    el.setAttribute('data-ip', p.id); el.classList.add(side === 'l' ? 'ip-l' : 'ip-r'); if (p.docked) el.classList.add('ip-dk');
    var cs = getComputedStyle(el); if (cs.position === 'static') el.style.position = 'relative';
    // barra de título (primer hijo)
    var bar = document.createElement('button'); bar.type = 'button'; bar.className = 'ip-bar';
    bar.innerHTML = '<span class="ip-a"></span><span class="ip-t"></span>'; bar.querySelector('.ip-t').textContent = titleOf(el, side);
    bar.addEventListener('click', function (e) { e.stopPropagation(); toggle(p); });
    p.bar = bar; el.insertBefore(bar, el.firstChild);
    // si la simulación voltea el panel (efecto espejo de la cámara), se contrarresta en la barra para que el texto se lea bien
    var tr = cs.transform, mm = tr && tr !== 'none' && tr.match(/matrix\(([-\d.e]+)/); if (mm && parseFloat(mm[1]) < 0) bar.style.transform = 'scaleX(-1)';
    // pestaña para paneles acoplados
    if (p.docked) {
      var chip = document.createElement('button'); chip.type = 'button'; chip.className = 'ip-chip'; chip.hidden = true;
      chip.innerHTML = '<b>▸</b> ' + bar.querySelector('.ip-t').textContent.replace(/</g, '&lt;'); chip.addEventListener('click', function () { toggle(p); });
      document.body.appendChild(chip); p.chip = chip;
    }
    panels.push(p);
    var saved = store.get(p.id);
    set(p, small ? false : (saved === null ? true : saved === '1'), true);
    layoutChips();
  }
  function set(p, open, init) {
    p.open = open; var el = p.el;
    el.classList.toggle('ip-col', !open && !p.docked);        // flotante: pastilla en su esquina
    el.classList.toggle('ip-hid', !open && p.docked);         // acoplado: se oculta
    var vid = !!el.querySelector('video'); el.classList.toggle('ip-vid', !open && p.docked && vid);
    Array.prototype.forEach.call(el.children, function (c) { if (c !== p.bar) c.classList.toggle('ip-keep', !open && !p.docked && (c.tagName === 'VIDEO' || !!c.querySelector('video'))); });
    if (p.chip) p.chip.hidden = open;
    p.bar.querySelector('.ip-a').textContent = open ? '▾' : '▸';
    p.bar.title = open ? 'Contraer' : 'Expandir';
    if (!small && !init) store.set(p.id, open ? '1' : '0');
    document.body.classList.toggle('ip-has-open', small && panels.some(function (q) { return q.open; }));
    if (!init) { layoutChips(); setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 30); }   // el 3D recalcula su tamaño
  }
  function toggle(p) {
    var open = !p.open;
    if (open && small) panels.forEach(function (o) { if (o !== p && o.open) set(o, false, true); });     // acordeón: uno a la vez
    set(p, open);
    if (small) { var q = document.getElementById('imq'); if (open && q && q.classList.contains('open')) { var t = q.querySelector('#imqX'); if (t) t.click(); } }
  }
  function layoutChips() {                                      // apila las pestañas por lado
    ['l', 'r'].forEach(function (s) {
      var base = 1e9; panels.forEach(function (p) { if (p.chip && p.side === s) base = Math.min(base, p.top); }); base = Math.max(8, base === 1e9 ? 8 : base);
      var hb = document.querySelector('header,#top'); if (hb) { var hr = hb.getBoundingClientRect(); if (hr.top < 100 && hr.height < 220 && hr.bottom > 0) base = Math.max(base, Math.round(hr.bottom) + 8); }   // por debajo del encabezado
      var closed = panels.filter(function (p) { return p.chip && p.side === s && !p.open; }).length;
      panels.forEach(function (p) { if (p.docked && p.side === s) p.el.style.setProperty('--ip-top', (base + closed * 38) + 'px'); });   // el panel abierto queda debajo de las pestañas
      var n = 0; panels.forEach(function (p) {
        if (!p.chip || p.side !== s || p.open) return;
        p.chip.style.top = (base + n * 38) + 'px'; p.chip.style[s === 'l' ? 'left' : 'right'] = '8px'; p.chip.style[s === 'l' ? 'right' : 'left'] = 'auto'; n++;
      });
    });
  }

  /* ---------- Modo compacto / táctil ---------- */
  function mode() {
    var now = vw() < SMALL, touch = window.matchMedia && matchMedia('(pointer:coarse)').matches;
    document.body.classList.toggle('ip-touch', !!touch); document.body.classList.toggle('ip-phone', vw() < 640);
    if (now !== small) {
      small = now; document.body.classList.toggle('ip-small', small);
      panels.forEach(function (p) { var saved = store.get(p.id); set(p, small ? false : (saved === null ? true : saved === '1'), true); });
      layoutChips();
    }
  }

  /* ---------- Arranque ---------- */
  function start() {
    if (!document.body) return;
    mode(); scan();
    window.addEventListener('resize', function () { clearTimeout(start.t); start.t = setTimeout(function () { mode(); scan(); layoutChips(); }, 120); });
    window.addEventListener('orientationchange', function () { setTimeout(function () { mode(); scan(); }, 250); });
    var n = 0, iv = setInterval(function () { scan(); if (++n > 12) { clearInterval(iv); setInterval(function () { if (!document.hidden) scan(); }, 4000); } }, 700);   // paneles que aparecen tarde
  }
  if (document.readyState === 'complete') setTimeout(start, 60); else window.addEventListener('load', function () { setTimeout(start, 60); });
  window.InduPanels = { rescan: scan, panels: panels };
})();
