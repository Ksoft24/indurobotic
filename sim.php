<?php require __DIR__.'/src/bootstrap.php'; require __DIR__.'/src/simulators.php';
/* Quita el script CDN de Tailwind y su configuración en línea (sin expresiones regulares sobre scripts grandes) */
function strip_tailwind_runtime(string $html): string {
  while (($i = stripos($html, 'cdn.tailwindcss.com')) !== false) {
    $st = strripos(substr($html, 0, $i), '<script'); $en = stripos($html, '</script>', $i); if ($st === false || $en === false) break;
    $html = substr($html, 0, $st).substr($html, $en + 9);
  }
  $off = 0;
  while (($i = stripos($html, 'tailwind.config', $off)) !== false) {
    $st = strripos(substr($html, 0, $i), '<script'); $en = stripos($html, '</script>', $i);
    if ($st === false || $en === false) break;
    $tag = substr($html, $st, strpos($html, '>', $st) - $st);
    if (stripos($tag, ' src=') !== false) { $off = $i + 15; continue; }
    $html = substr($html, 0, $st).substr($html, $en + 9); $off = $st;
  }
  return $html;
}

$u = require_login(); $s = simulators()[$_GET['id'] ?? ''] ?? null; if (!$s) { http_response_code(404); exit('No existe'); }
if (isset($_GET['raw'])) { // sims/ está bloqueada al público: solo se sirve con sesión y licencia vigentes
  $html = (string)file_get_contents(__DIR__.'/sims/'.$s[2]); $b = base_url();
  $inj = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
       . '<link rel="stylesheet" href="'.$b.'/assets/css/indurobotic.css">'
       . '<script>window.INDU={mqttConfigUrl:'.json_encode($b.'/api/mqtt_config.php').',sim:'.json_encode($_GET['id']).',cliente:'.(int)$u['id_cliente'].'};</script>'
       . '<script src="'.$b.'/assets/js/indu-sim.js"></script>';
  // Paneles contraíbles y responsivos (tablet/móvil): en todas las simulaciones
  $inj .= '<script src="'.$b.'/assets/js/indu-panels.js?v='.filemtime(__DIR__.'/assets/js/indu-panels.js').'"></script>';
  // Consola MQTT: solo en simulaciones que traen consola de grabación (#rRec)
  if (strpos($html, 'id="rRec"') !== false) $inj .= '<script src="'.$b.'/assets/js/indu-mqtt.js?v='.filemtime(__DIR__.'/assets/js/indu-mqtt.js').'"></script>';
  // Tailwind: si existe el CSS precompilado (tools/build-tailwind.js) se usa en lugar del script CDN, que compila en el navegador y pesa mucho
  $tw = __DIR__.'/assets/css/tw/'.preg_replace('/\.html$/', '.css', $s[2]);
  if (is_file($tw)) {
    $html = strip_tailwind_runtime($html);
    $inj = '<link rel="stylesheet" href="'.$b.'/assets/css/tw/'.basename($tw).'?v='.filemtime($tw).'">'.$inj;
  }
  $p = stripos($html, '</head>'); $html = $p === false ? $inj.$html : substr($html,0,$p).$inj.substr($html,$p);
  header('Content-Type: text/html; charset=utf-8'); echo $html; exit;
}
$b = base_url();
?><!DOCTYPE html><html lang="es" data-theme="<?= h($u['tema']) ?>"><head><meta charset="utf-8"><title><?= h($s[0]) ?> · InduRobotic</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= $b ?>/assets/css/indurobotic.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head>
<body class="indu-app"><div class="simwrap"><div class="simbar"><a class="btn ghost sm" href="app.php"><i class="fa-solid fa-arrow-left"></i> Simulaciones</a><b><?= h($s[0]) ?></b></div>
<iframe src="sim.php?id=<?= h($_GET['id']) ?>&raw=1" allow="camera; microphone; fullscreen; xr-spatial-tracking"></iframe></div></body></html>