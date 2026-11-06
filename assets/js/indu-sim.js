/* InduRobotic · Piel de simuladores
   Lo inyecta sim.php dentro de cada archivo de sims/. Activa la piel CSS (assets/css/indurobotic.css) y
   garantiza el fondo/niebla unificados (--env-cielo) aunque una simulación nueva traiga otro color.
   Los colores de Tailwind ya vienen precompilados con la paleta (tools/build-tailwind.js): aquí no se toca Tailwind. */
(function () {
  var d = document.documentElement;
  d.classList.add('indu-skin');
  function cielo() { return (getComputedStyle(d).getPropertyValue('--env-cielo') || '#DBE1E7').trim(); }
  function unify() {
    var c = cielo();
    try {   // A-Frame
      var sc = document.querySelector('a-scene');
      if (sc) { var f = function () { sc.setAttribute('background', 'color', c); if (sc.getAttribute('fog')) sc.setAttribute('fog', 'color', c); }; sc.hasLoaded ? f() : sc.addEventListener('loaded', f); }
    } catch (e) {}
    try {   // three.js puro (escena global)
      if (typeof scene !== 'undefined' && scene && scene.isScene && window.THREE) { scene.background = new THREE.Color(c); if (scene.fog) scene.fog.color.set(c); }
    } catch (e) {}
  }
  window.addEventListener('load', unify);
})();
