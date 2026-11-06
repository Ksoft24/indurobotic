/* Plataforma InduRobotic */
document.getElementById('themeBtn')?.addEventListener('click', () => {
  const r = document.documentElement, t = r.dataset.theme === 'claro' ? 'oscuro' : 'claro';
  r.dataset.theme = t;
  fetch(BASE + '/api/tema.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'tema=' + t + '&_csrf=' + encodeURIComponent(CSRF)});
  paintHex();
});
document.querySelectorAll('[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

/* Filtro por categoría (dashboard) */
document.querySelectorAll('.chip[data-cat]').forEach(ch => ch.addEventListener('click', () => {
  document.querySelectorAll('.chip[data-cat]').forEach(c => c.classList.toggle('on', c === ch));
  document.querySelectorAll('[data-sec]').forEach(s => { s.hidden = ch.dataset.cat !== 'all' && s.dataset.sec !== ch.dataset.cat; });
}));

/* Guía de estilo: muestra el HEX real de cada variable CSS */
function paintHex() {
  const toHex = c => { const m = c.match(/\d+(\.\d+)?/g); if (!m) return c; return '#' + m.slice(0, 3).map(n => (+n).toString(16).padStart(2, '0')).join('').toUpperCase(); };
  document.querySelectorAll('[data-hex]').forEach(el => {
    const p = document.createElement('i'); p.style.background = 'var(' + el.dataset.hex + ')'; document.body.appendChild(p);
    el.textContent = toHex(getComputedStyle(p).backgroundColor); p.remove();
  });
}
paintHex();
