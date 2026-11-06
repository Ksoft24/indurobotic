/* InduRobotic · Consola MQTT para simulaciones con grabación
   Lo inyecta sim.php. Se activa solo si la simulación trae consola de grabación (#rRec).
   - Conexión: lista de brokers de /api/mqtt_config.php (Paho sobre WebSocket).
   - Publicar: con el check activo se publica el JSON de la grabación al guardarla (■ Guardar),
     al reproducirla (▶) y con el botón «Publicar seleccionada».
   - Escuchar: con el check activo el robot reproduce el movimiento (JSON) que llegue al tópico.
   Usa las funciones/variables de la propia simulación (LIB, PB, REC, toJSON, fromJSON, addRec, pbStart, finishRec...). */
(function () {
  'use strict';
  var CFG = window.INDU || {}, PAHO = 'https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js';
  var MAX_IN = 5 * 1024 * 1024;                       // tamaño máximo aceptado por mensaje entrante
  var SRC = 'indu_' + Math.random().toString(16).slice(2, 10);   // id de este navegador (evita oír los propios mensajes)

  if (document.readyState === 'complete') setTimeout(init, 0); else window.addEventListener('load', function () { setTimeout(init, 0); });

  var started = false;
  function init() {
    if (started) return; if (!document.getElementById('rRec')) return;   // sin grabación → sin consola MQTT
    started = true;
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var key = 'indu.mqtt.' + (CFG.sim || 'sim') + '.';
    var ls = { get: function (k, d) { try { var v = localStorage.getItem(key + k); return v === null ? d : v; } catch (e) { return d; } },
               set: function (k, v) { try { localStorage.setItem(key + k, v); } catch (e) {} } };
    var defTopic = 'indurobotic/' + (CFG.cliente || 0) + '/' + (CFG.sim || 'sim');

    /* ---------- Interfaz ---------- */
    var css = document.createElement('style');
    css.textContent = [
      '#imq{position:fixed;left:0;top:0;bottom:0;z-index:2147483000;display:flex;pointer-events:none;font:12px/1.35 Inter,system-ui,sans-serif}',
      '#imq *{box-sizing:border-box}',
      '#imq .imq-tab{pointer-events:auto;align-self:center;order:2;writing-mode:vertical-rl;transform:rotate(180deg);background:#1e293b;color:#e2e8f0;border:1px solid #475569;border-left:0;border-radius:0 8px 8px 0;padding:10px 3px;cursor:pointer;font-weight:700;font-size:11px;letter-spacing:.04em;line-height:1}',
      '#imq .imq-tab b{color:#64748b;font-size:10px}',
      '#imq.ok .imq-tab b{color:#2dbe6c}#imq.err .imq-tab b{color:#e5484d}#imq.wait .imq-tab b{color:#f5a623}',
      '#imq .imq-box{pointer-events:auto;order:1;width:310px;max-width:calc(100vw - 40px);height:100%;overflow-y:auto;background:rgba(15,23,42,.97);color:#e2e8f0;border-right:1px solid #475569;padding:12px;transform:translateX(-100%);display:none}',
      '#imq.open .imq-box{display:block;transform:none}',
      '#imq h3{margin:0 0 8px;font-size:13px;font-weight:700;color:#38bdf8;display:flex;justify-content:space-between;align-items:center}',
      '#imq h3 button{background:none;border:0;color:#94a3b8;cursor:pointer;font-size:16px;padding:0 4px}',
      '#imq label.l{display:block;margin:8px 0 3px;color:#94a3b8;font-size:11px}',
      '#imq select,#imq input[type=text]{width:100%;background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:6px;padding:6px;font-size:12px}',
      '#imq .sec{border:1px solid #334155;border-radius:8px;padding:8px;margin-top:10px}',
      '#imq .sec.off{opacity:.55}',
      '#imq .chk{display:flex;gap:6px;align-items:center;font-weight:700;cursor:pointer}',
      '#imq .chk input{width:auto;margin:0;accent-color:#ff6a13}',
      '#imq button.b{width:100%;margin-top:6px;padding:6px;background:#1e293b;color:#e2e8f0;border:1px solid #334155;border-radius:6px;cursor:pointer;font-size:11px}',
      '#imq button.b:hover:not(:disabled){border-color:#38bdf8}#imq button.b:disabled{opacity:.45;cursor:not-allowed}',
      '#imq #imqSt{margin-top:6px;font-size:11px;color:#fbbf24}',
      '#imq #imqLog{margin-top:10px;height:150px;overflow-y:auto;background:#020617;border:1px solid #1e293b;border-radius:6px;padding:6px;font:10.5px/1.4 ui-monospace,Menlo,monospace;color:#94a3b8;white-space:pre-wrap;word-break:break-word}',
      '#imq #imqLog .tx{color:#38bdf8}#imq #imqLog .rx{color:#2dbe6c}#imq #imqLog .er{color:#f87171}',
      '#imq small{display:block;margin-top:6px;color:#64748b;font-size:10px}'
    ].join('');
    document.head.appendChild(css);

    var box = document.createElement('div'); box.id = 'imq';
    box.innerHTML =
      '<button class="imq-tab" id="imqTab" title="Consola MQTT"><b>●</b> MQTT</button>' +
      '<div class="imq-box">' +
        '<h3><span>📡 Consola MQTT</span><button id="imqX" title="Cerrar">✕</button></h3>' +
        '<label class="l" for="imqBr">Conexión</label>' +
        '<select id="imqBr"><option value="">— Sin conexión —</option></select>' +
        '<div id="imqSt">Desconectado</div>' +
        '<div class="sec off" id="imqPubS"><label class="chk"><input type="checkbox" id="imqPub" disabled> Publicar grabación</label>' +
          '<label class="l" for="imqPT">Tópico a publicar</label><input type="text" id="imqPT" spellcheck="false" autocomplete="off">' +
          '<button class="b" id="imqSend" disabled>📤 Publicar seleccionada</button></div>' +
        '<div class="sec off" id="imqLiveS"><label class="chk"><input type="checkbox" id="imqLive" disabled> Enviar movimiento en vivo</label>' +
          '<label class="l" for="imqLT">Tópico en vivo</label><input type="text" id="imqLT" spellcheck="false" autocomplete="off">' +
          '<label class="l" for="imqHz">Frecuencia (cámara, teclas, reproducción)</label><select id="imqHz"><option value="5">5 por segundo</option><option value="10">10 por segundo</option><option value="15" selected>15 por segundo</option><option value="20">20 por segundo</option></select></div>' +
        '<div class="sec off" id="imqSubS"><label class="chk"><input type="checkbox" id="imqSub" disabled> Escuchar MQTT</label>' +
          '<label class="l" for="imqST">Tópico a escuchar</label><input type="text" id="imqST" spellcheck="false" autocomplete="off"></div>' +
        '<div id="imqLog"></div>' +
        '<small>Publicar: envía el JSON de la grabación al guardarla (■), al reproducirla (▶) o con el botón. «En vivo» es independiente: transmite la pose del robot en tiempo real (por ejemplo la que captura la cámara), con o sin publicar grabaciones. Escuchar: el robot ejecuta el movimiento JSON que llegue al tópico (grabación completa o una pose {"q":{"J1":30,…}}).</small>' +
      '</div>';
    document.body.appendChild(box);

    var el = { lt: $('#imqLT'), live: $('#imqLive'), hz: $('#imqHz'), br: $('#imqBr'), st: $('#imqSt'), pub: $('#imqPub'), sub: $('#imqSub'), pt: $('#imqPT'), stp: $('#imqST'), send: $('#imqSend'), log: $('#imqLog') };
    el.pt.value = ls.get('pt', defTopic); el.lt.value = ls.get('lt', defTopic); el.stp.value = ls.get('st', defTopic);
    box.classList.toggle('open', ls.get('open', '0') === '1');
    function setOpen(o) { box.classList.toggle('open', o); ls.set('open', o ? '1' : '0'); }
    $('#imqTab').onclick = function () { setOpen(!box.classList.contains('open')); }; $('#imqX').onclick = function () { setOpen(false); };

    function log(msg, cls) {
      var d = document.createElement('div'); if (cls) d.className = cls;
      d.textContent = new Date().toLocaleTimeString() + ' ' + msg; el.log.appendChild(d);
      while (el.log.childNodes.length > 80) el.log.removeChild(el.log.firstChild); el.log.scrollTop = el.log.scrollHeight;
    }
    function status(txt, cls) { el.st.textContent = txt; box.classList.remove('ok', 'err', 'wait'); if (cls) box.classList.add(cls); }
    function gating() {
      var on = !!(client && client.isConnected());
      el.pub.disabled = el.sub.disabled = !on;
      $('#imqPubS').classList.toggle('off', !on); $('#imqSubS').classList.toggle('off', !on);
      el.send.disabled = !on || !el.pub.checked;
      $('#imqLiveS').classList.toggle('off', !on); el.live.disabled = el.lt.disabled = el.hz.disabled = !on;   // si se pierde la red, queda marcado y reanuda al reconectar
    }

    /* ---------- Conexión (Paho) ---------- */
    var brokers = [], client = null, wantId = '', retryT = null, subscribed = '';
    function loadPaho(cb) {
      if (window.Paho && Paho.MQTT) return cb();
      var s = document.createElement('script'); s.src = PAHO; s.onload = cb; s.onerror = function () { status('No se pudo cargar la librería MQTT', 'err'); log('No se pudo cargar Paho desde cdnjs', 'er'); };
      document.head.appendChild(s);
    }
    function fillBrokers(list) {
      brokers = list; while (el.br.options.length > 1) el.br.remove(1);
      brokers.forEach(function (b) { var o = document.createElement('option'); o.value = b.id; o.textContent = b.nombre; el.br.appendChild(o); });
      el.br.options[0].textContent = brokers.length ? '— Sin conexión —' : '— No hay brokers activos —';
      if (!brokers.length) log('No hay brokers activos para tu usuario: revisa Administración → MQTT (estado Activo e institución)', 'er');
    }
    function loadBrokers(cb) {   // primero la ruta relativa a esta página (conserva la cookie aunque APP_URL difiera del host usado), luego la absoluta
      var urls = [new URL('api/mqtt_config.php', location.href).href]; if (CFG.mqttConfigUrl && urls.indexOf(CFG.mqttConfigUrl) < 0) urls.push(CFG.mqttConfigUrl);
      (function next(i, why) {
        if (i >= urls.length) { status('No se pudo cargar la lista de brokers', 'err'); log('Brokers: ' + why, 'er'); el.br.options[0].textContent = '— Error al cargar (reintentar) —'; return; }
        fetch(urls[i], { credentials: 'same-origin', cache: 'no-store' }).then(function (r) {
          return r.text().then(function (t) {
            var j; try { j = JSON.parse(t); } catch (e) { throw new Error('HTTP ' + r.status + ' · la respuesta no es JSON (¿sesión caducada o URL distinta a APP_URL?) · ' + urls[i]); }
            if (!r.ok || !Array.isArray(j)) throw new Error((j && j.error) || ('HTTP ' + r.status));
            return j;
          });
        }).then(function (j) { fillBrokers(j); if (cb) cb(); }).catch(function (e) { next(i + 1, e.message); });
      })(0, '');
    }
    loadBrokers();
    el.br.addEventListener('focus', function () { if (!brokers.length) loadBrokers(); });   // si estaba vacío, reintenta al abrir el combo

    function disconnect(silent) {
      clearTimeout(retryT); subscribed = ''; liveStop();
      if (client) { try { client.onConnectionLost = function () {}; if (client.isConnected()) client.disconnect(); } catch (e) {} client = null; }
      if (!silent) { status('Desconectado'); log('Desconectado'); }
      gating();
    }
    function connect(id) {
      disconnect(true);
      var b = brokers.filter(function (x) { return String(x.id) === String(id); })[0]; if (!b) { status('Desconectado'); return; }
      status('Conectando a ' + b.nombre + '…', 'wait');
      loadPaho(function () {
        var u; try { u = new URL(b.url.replace(/^ws/, 'http')); } catch (e) { status('URL de broker inválida', 'err'); return; }
        var tls = /^wss/.test(b.url), port = Number(u.port) || (tls ? 443 : 80), cid = SRC + '_' + Math.random().toString(16).slice(2, 6);
        var c = new Paho.MQTT.Client(u.hostname, port, u.pathname || '/mqtt', cid); client = c;
        c.onConnectionLost = function (r) {
          if (client !== c) return; subscribed = ''; gating();
          status('Conexión perdida' + (r && r.errorMessage ? ': ' + r.errorMessage : ''), 'err'); log('Conexión perdida', 'er');
          if (String(wantId) === String(id)) retryT = setTimeout(function () { if (String(wantId) === String(id)) connect(id); }, 5000);
        };
        c.onMessageArrived = onMsg;
        var opt = { useSSL: tls, timeout: 8, keepAliveInterval: 30, cleanSession: true,
          onSuccess: function () { if (client !== c) return; status('Conectado a ' + b.nombre, 'ok'); log('Conectado a ' + b.nombre); gating(); syncSub(); },
          onFailure: function (e) { if (client !== c) return; status('No se pudo conectar', 'err'); log('Fallo de conexión: ' + (e && e.errorMessage || 'desconocido'), 'er'); gating();
            if (String(wantId) === String(id)) retryT = setTimeout(function () { if (String(wantId) === String(id)) connect(id); }, 8000); } };
        if (b.usuario) { opt.userName = b.usuario; if (b.password) opt.password = b.password; }
        try { c.connect(opt); } catch (e) { status('Error al conectar', 'err'); log(String(e), 'er'); }
      });
    }
    el.br.onchange = function () { wantId = el.br.value; ls.set('br', wantId); if (!wantId) { el.pub.checked = el.sub.checked = false; disconnect(); } else connect(wantId); };
    // el broker recordado se restablece cuando llega la lista
    var tries = 0, rt = setInterval(function () { if (brokers.length || ++tries > 40) { clearInterval(rt); var last = ls.get('br', ''); if (last && brokers.some(function (b) { return String(b.id) === last; })) { el.br.value = last; el.br.onchange(); } } }, 150);

    /* ---------- Publicar ---------- */
    function pubTopic() { return el.pt.value.trim(); }
    function badPub(t) { return !t || /[+#]/.test(t) || t.length > 250; }
    function ownInfo() {   // formato/robot de esta simulación, para rechazar movimientos de otro robot
      try { if (typeof toJSON === 'function') { var d = toJSON({ name: '', created: '', duration: 0, frames: [] }); return { format: d.format, robot: d.robot }; } } catch (e) {}
      return { format: null, robot: typeof toJSON === 'function' ? null : 'InduRobotic Duck J15' };
    }
    function serialize(r) {
      var o = (typeof toJSON === 'function') ? toJSON(r) : Object.assign({ robot: 'InduRobotic Duck J15', axes: 15 }, r);
      o.mqtt = { src: SRC, ts: Date.now() }; return o;
    }
    var muted = false;   // true mientras se ejecuta un movimiento recibido (evita reenviarlo)
    function publish(rec, why) {
      if (muted || !el.pub.checked || !client || !client.isConnected() || !rec) return;
      var t = pubTopic(); if (badPub(t)) { log('Tópico de publicación inválido (sin + ni #)', 'er'); return; }
      try {
        var s = JSON.stringify(serialize(rec)), m = new Paho.MQTT.Message(s); m.destinationName = t; m.qos = 0; m.retained = false; client.send(m);
        var kb = (s.length / 1024).toFixed(1); log('↑ ' + t + ' · ' + (rec.name || 'grabación') + ' · ' + kb + ' KB (' + why + ')', 'tx');
        if (s.length > 256 * 1024) log('Aviso: mensaje grande; verifica el límite del broker', 'er');
      } catch (e) { log('No se pudo publicar: ' + e.message, 'er'); }
    }
    el.pub.onchange = function () { if (el.pub.checked && badPub(pubTopic())) { el.pub.checked = false; log('Escribe un tópico válido para publicar (sin + ni #)', 'er'); } ls.set('pt', pubTopic()); gating(); };
    el.pt.onchange = function () { ls.set('pt', pubTopic()); };
    el.send.onclick = function () { if (typeof PB === 'undefined' || !PB.rec) { log('Selecciona o guarda una grabación primero', 'er'); return; } publish(PB.rec, 'manual'); };

    // al guardar una grabación nueva
    if (typeof finishRec === 'function') {
      var oF = window.finishRec;
      window.finishRec = function () { var n = LIB.length, r = oF.apply(this, arguments); if (LIB.length > n) publish(LIB[LIB.length - 1], 'guardada'); return r; };
      var bs = document.getElementById('rStop'); if (bs) bs.onclick = window.finishRec;   // el botón ya apuntaba a la función original
    }
    // al reproducir una grabación
    if (typeof pbStart === 'function') {
      var oP = window.pbStart;
      window.pbStart = function () { var r = oP.apply(this, arguments); if (!muted && PB.rec) publish(PB.rec, 'reproducción'); return r; };
    }


    /* ---------- Movimiento en vivo: emisor ---------- */
    var liveT = null, lastLive = '', seq = 0;
    function liveTopic() { return el.lt.value.trim(); }
    function liveFrame() {        // el cuadro lo arma la propia simulación (pushFrame), así el formato siempre coincide
      if (typeof REC === 'undefined') return null;
      if (typeof pushFrame === 'function') {
        var saved = REC.frames, f = null; REC.frames = [];
        try { pushFrame(0); f = REC.frames[0]; } catch (e) {} finally { REC.frames = saved; }
        if (f) return f;
      }
      return (typeof snap === 'function') ? { t: 0, v: snap() } : null;
    }
    function liveSend() {
      if (!client || !client.isConnected() || !el.live.checked) return;
      if (typeof PB !== 'undefined' && live && PB.rec === live.rec) return;   // este navegador está recibiendo: no reenvía
      var t = liveTopic(); if (badPub(t)) return;
      var f = liveFrame(); if (!f) return;
      var sig = JSON.stringify(f); if (sig === lastLive) return; lastLive = sig;     // solo si cambió algo
      try {
        var o = serialize({ name: 'en vivo', created: new Date().toISOString(), duration: 0, frames: [f] });
        o.live = true; o.mqtt.seq = ++seq; delete o.joints;
        var m = new Paho.MQTT.Message(JSON.stringify(o)); m.destinationName = t; m.qos = 0; m.retained = false; client.send(m);
      } catch (e) { log('Error en vivo: ' + e.message, 'er'); }
    }
    function liveTx() {
      clearInterval(liveT); liveT = null; lastLive = '';
      if (el.live.checked) { liveT = setInterval(liveSend, Math.round(1000 / Number(el.hz.value || 15))); log('Transmitiendo en vivo a ' + liveTopic() + ' (' + el.hz.value + '/s)', 'tx'); }
      else if (seq) log('Transmisión en vivo detenida');
    }
    el.live.onchange = function () { if (el.live.checked && badPub(liveTopic())) { el.live.checked = false; log('Escribe un tópico en vivo válido (sin + ni #)', 'er'); return; } ls.set('lt', liveTopic()); liveTx(); };
    el.lt.onchange = function () { ls.set('lt', liveTopic()); if (el.live.checked) { if (badPub(liveTopic())) { el.live.checked = false; liveTx(); log('Tópico en vivo inválido', 'er'); } else liveTx(); } };
    el.hz.onchange = function () { if (el.live.checked) liveTx(); };

    /* ---------- Movimiento en vivo: receptor (suaviza entre mensajes y mantiene la pose) ---------- */
    var live = null, raf = 0, lastRaf = 0;
    function lerpFrame(cur, tgt, k) {
      Object.keys(tgt).forEach(function (key) {
        if (key === 't') return; var a = cur[key], b = tgt[key];
        if (Array.isArray(b) && Array.isArray(a) && a.length === b.length && b.every(function (x) { return typeof x === 'number'; })) {
          for (var i = 0; i < b.length; i++) { var d = b[i] - a[i]; a[i] = (key === 'b' && i === 2 && Math.abs(d) > 3.1) ? b[i] : a[i] + d * k; }
        } else cur[key] = b;
      });
    }
    function liveLoop(now) {
      if (!live) { raf = 0; return; }
      var dt = Math.min(((now || 0) - lastRaf) / 1000, .1) || .016; lastRaf = now || 0;
      if (typeof PB !== 'undefined' && PB.rec === live.rec) {
        lerpFrame(live.rec.frames[0], live.tgt, 1 - Math.exp(-dt * 18));
        if (!PB.active) { PB.active = true; PB.playing = false; PB.t = 0; if (typeof recUI === 'function') recUI(); }
      } else { live = null; raf = 0; return; }   // el usuario cargó otra grabación: se suelta el modo en vivo
      raf = requestAnimationFrame(liveLoop);
    }
    function liveStop() {
      if (live && typeof PB !== 'undefined' && PB.rec === live.rec && typeof pbStop === 'function') { pbStop(); if (typeof recUI === 'function') recUI(); }
      live = null;
    }
    function runLive(o, topic) {
      var me = ownInfo(), fmt = function (s) { return String(s || '').replace(/s$/, ''); };
      if (o.format && me.format && fmt(o.format) !== fmt(me.format)) return;
      if (!o.format && o.robot && me.robot && o.robot !== me.robot) return;
      if (busy()) return;
      try {
        var rec = typeof fromJSON === 'function' ? fromJSON(o, 'MQTT') : importDuck({ recordings: [o] })[0];
        if (!rec || !rec.frames || !rec.frames.length) return;
        var tgt = rec.frames[0];
        if (live && typeof PB !== 'undefined' && PB.rec === live.rec) { live.tgt = tgt; }
        else {
          rec.duration = 0; rec.name = 'MQTT en vivo';
          live = { rec: rec, tgt: tgt }; rec.frames = [JSON.parse(JSON.stringify(tgt))];
          muted = true; try { PB.rec = rec; PB.t = 0; PB.active = true; PB.playing = false; } finally { muted = false; }
          log('↓ recibiendo movimiento en vivo (' + topic + ')', 'rx'); if (typeof recUI === 'function') recUI();
        }
        if (!raf) { lastRaf = performance.now(); raf = requestAnimationFrame(liveLoop); }
      } catch (e) { /* mensaje en vivo no válido: se descarta sin ruido */ }
    }

    /* ---------- Escuchar ---------- */
    function listenTopic() { return el.stp.value.trim(); }
    function syncSub() {
      if (!client || !client.isConnected()) return;
      var want = el.sub.checked ? listenTopic() : ''; if (!want) liveStop();
      if (subscribed && subscribed !== want) { try { client.unsubscribe(subscribed); log('Dejó de escuchar ' + subscribed); } catch (e) {} subscribed = ''; }
      if (want && subscribed !== want) { try { client.subscribe(want, { qos: 0, onSuccess: function () { log('Escuchando ' + want); }, onFailure: function (e) { log('No se pudo suscribir: ' + (e && e.errorMessage || ''), 'er'); } }); subscribed = want; } catch (e) { log(String(e), 'er'); } }
    }
    el.sub.onchange = function () { if (el.sub.checked && !listenTopic()) { el.sub.checked = false; log('Escribe un tópico para escuchar', 'er'); return; } ls.set('st', listenTopic()); syncSub(); };
    el.stp.onchange = function () { ls.set('st', listenTopic()); if (el.sub.checked) syncSub(); };

    function busy() { return (typeof REC !== 'undefined' && REC.on); }
    function run(o, topic) {
      var me = ownInfo(), fmt = function (s) { return String(s || '').replace(/s$/, ''); };
      if (o.format && me.format && fmt(o.format) !== fmt(me.format)) { log('Ignorado: es de otro robot (' + o.format + ')', 'er'); return; }
      if (!o.format && o.robot && me.robot && o.robot !== me.robot) { log('Ignorado: es de otro robot (' + o.robot + ')', 'er'); return; }
      if (busy()) { log('Ignorado: este robot está grabando', 'er'); return; }
      var single = !Array.isArray(o) && !o.recordings && !Array.isArray(o.frames) && (o.q || o.joints_deg);
      muted = true;
      try {
        if (single && typeof fromJSON !== 'function') throw new Error('esta simulación no admite poses sueltas');
        if (single) {                                   // una sola pose en vivo: se aplica sin llenar la lista
          var pose = { frames: [{ t: 0, q: o.q || o.joints_deg, base: o.base }], name: 'MQTT en vivo' };
          var rec = fromJSON(pose, 'MQTT'); PB.rec = rec; PB.t = 0; if (typeof pbStart === 'function') pbStart(); log('↓ pose recibida (' + topic + ')', 'rx');
        } else {
          var list = typeof fromJSONList === 'function' ? fromJSONList(o, 'MQTT') : importDuck(o);
          if (!list.length) throw new Error('sin cuadros');
          list.forEach(function (r) { r.name = 'MQTT · ' + String(r.name || 'movimiento').slice(0, 40); });
          list.forEach(function (r) { if (typeof addRec === 'function') addRec(r); else { LIB.push(r); PB.rec = r; if (typeof renderList === 'function') renderList(); } });
          PB.t = 0; pbStart(); log('↓ ' + list.length + ' grabación(es) recibida(s) · ' + list[list.length - 1].frames.length + ' cuadros (' + topic + ')', 'rx');
        }
        if (typeof recUI === 'function') recUI();
      } catch (e) { log('JSON no válido: ' + e.message, 'er'); }
      muted = false;
    }
    function importDuck(o) {   // Duck J15 (formato propio: cuadros {t, v:[22]})
      var arr = Array.isArray(o) ? o : (o.recordings || [o]), out = [];
      arr.forEach(function (r) {
        if (r && r.frames && r.frames[0] && r.frames[0].v && r.frames[0].v.length === 22) {
          r.id = ++rid; r.duration = r.duration || r.frames[r.frames.length - 1].t; r.created = r.created || new Date().toISOString(); out.push(r);
        }
      });
      return out;
    }
    function onMsg(m) {
      var s = m.payloadString; if (!s || s.length > MAX_IN) { log('Mensaje descartado (vacío o demasiado grande)', 'er'); return; }
      var o; try { o = JSON.parse(s); } catch (e) { log('Mensaje no es JSON', 'er'); return; }
      if (!o || typeof o !== 'object') return;
      if (o.mqtt && o.mqtt.src === SRC) return;        // eco de lo que publicó este mismo navegador
      if (o.live) { runLive(o, m.destinationName); return; }
      liveStop(); run(o, m.destinationName);
    }

    // API mínima para pruebas desde la consola del navegador
    window.InduMQTT = { publish: publish, run: run, src: SRC, liveSend: liveSend };
    gating();
  }
})();
