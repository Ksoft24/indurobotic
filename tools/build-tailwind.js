#!/usr/bin/env node
/* InduRobotic · Compila el CSS de Tailwind de cada simulación (reemplaza al script CDN en tiempo de ejecución).
   Uso:   npm i tailwindcss@3     y luego     node tools/build-tailwind.js
   - Lee la paleta de assets/css/indurobotic.css (única fuente de verdad) y la mapea a los nombres de Tailwind.
   - Respeta la configuración `tailwind.config` que traiga cada simulación.
   - Salida: assets/css/tw/<simulacion>.css (sim.php lo usa automáticamente si existe).
   Ejecútelo cada vez que agregue o cambie clases de Tailwind en una simulación o cambie la paleta. */
const fs = require('fs'), path = require('path'), vm = require('vm'), cp = require('child_process'), os = require('os');
const root = path.join(__dirname, '..'), simsDir = path.join(root, 'sims'), outDir = path.join(root, 'assets/css/tw');
const css = fs.readFileSync(path.join(root, 'assets/css/indurobotic.css'), 'utf8');
const vars = {}; for (const m of css.matchAll(/(--[a-z0-9-]+):\s*([^;]+);/g)) if (!(m[1] in vars)) vars[m[1]] = m[2].trim();
const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
const fam = n => { const o = {}; steps.forEach(s => { o[s] = vars['--' + n + '-' + s]; }); o.DEFAULT = o[500]; return o; };
const P = { acero: fam('acero'), naranja: fam('naranja'), verde: fam('verde'), ambar: fam('ambar'), rojo: fam('rojo'), azul: fam('azul') };
const map = { slate: 'acero', gray: 'acero', zinc: 'acero', neutral: 'acero', stone: 'acero', orange: 'naranja', emerald: 'verde', green: 'verde', teal: 'verde', lime: 'verde',
  amber: 'ambar', yellow: 'ambar', red: 'rojo', rose: 'rojo', pink: 'rojo', sky: 'azul', blue: 'azul', cyan: 'azul', indigo: 'azul', violet: 'azul', purple: 'azul', fuchsia: 'azul' };
const colors = {}; Object.keys(map).forEach(k => { colors[k] = P[map[k]]; });
Object.assign(colors, { kuka: P.naranja, kukaDark: P.naranja[700], brand: P.naranja, darkBg: P.acero[950], panelBg: 'rgba(19,24,32,.90)', panelBorder: vars['--sim-line'] || '#343C47' });
fs.mkdirSync(outDir, { recursive: true });
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'indu-tw-'));
let ok = 0;
for (const f of fs.readdirSync(simsDir).filter(f => f.endsWith('.html'))) {
  const html = fs.readFileSync(path.join(simsDir, f), 'utf8');
  if (!/cdn\.tailwindcss\.com/.test(html)) continue;
  // configuración propia de la simulación (si la hay)
  let simCfg = {};
  for (const m of html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/gi)) if (/tailwind\.config/.test(m[1])) {
    const ctx = { tailwind: {} }; try { vm.runInNewContext(m[1], ctx); simCfg = ctx.tailwind.config || {}; } catch (e) { console.warn('  aviso: no se pudo leer tailwind.config de', f, e.message); }
  }
  const ext = Object.assign({}, (simCfg.theme && simCfg.theme.extend) || {});
  ext.colors = Object.assign({}, ext.colors || {}, colors);
  ext.fontFamily = Object.assign({}, ext.fontFamily || {}, { sans: ['Inter', 'system-ui', 'sans-serif'], mono: ['Inter', 'system-ui', 'sans-serif'] });
  const theme = Object.assign({}, simCfg.theme || {}, { extend: ext });
  const cfg = Object.assign({}, simCfg, { content: [path.join(simsDir, f)], theme, corePlugins: Object.assign({}, simCfg.corePlugins || {}) });
  const cfgFile = path.join(tmp, 'tailwind.config.js'), inFile = path.join(tmp, 'in.css'), outFile = path.join(outDir, f.replace(/\.html$/, '.css'));
  fs.writeFileSync(cfgFile, 'module.exports=' + JSON.stringify(cfg)); fs.writeFileSync(inFile, '@tailwind base;\n@tailwind components;\n@tailwind utilities;\n');
  cp.execFileSync(process.execPath, [require.resolve('tailwindcss/lib/cli.js'), '-c', cfgFile, '-i', inFile, '-o', outFile, '--minify'], { stdio: ['ignore', 'ignore', 'pipe'] });
  const dyn = (html.match(/(?:bg|text|border|ring|from|to|via)-\$\{/g) || []).length;
  console.log(f.padEnd(44), (fs.statSync(outFile).size / 1024).toFixed(1) + ' KB', dyn ? `  ⚠ ${dyn} clases construidas dinámicamente (revíselas)` : '');
  ok++;
}
console.log(`\n${ok} hojas generadas en assets/css/tw/`);
