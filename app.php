<?php require __DIR__.'/src/bootstrap.php'; require __DIR__.'/src/simulators.php';
$u = require_login(); page_head('Simulaciones','app.php');
if ($u['id_cliente']) { $l = license((int)$u['id_cliente']);
  echo '<div class="card"><span class="badge b-'.h($l['plan']).'">'.strtoupper(h($l['plan'])).'</span> <b>'.h($u['razon_social']).'</b>';
  if ($l['dias']!==null) echo ' · vence en <b class="'.($l['dias']<15?'b-warn':'').'">'.$l['dias'].' días</b>';
  echo ' · usuarios '.$l['usados'].' / '.($l['max']===PHP_INT_MAX?'ilimitado':$l['max']);
  if ($l['max']!==PHP_INT_MAX) echo '<div class="meter"><i style="width:'.min(100,round(100*$l['usados']/max(1,$l['max']))).'%"></i></div>'; echo '</div>'; }
$cats = sim_categories(); $sims = simulators();
echo '<div class="chips"><button type="button" class="chip on" data-cat="all">Todas · '.count($sims).'</button>';
foreach ($cats as $k=>$n) echo '<button type="button" class="chip" data-cat="'.$k.'">'.h($n).'</button>'; echo '</div>';
foreach ($cats as $k=>$n) { $in = array_filter($sims, fn($s)=>$s[4]===$k); if (!$in) continue;
  echo '<section data-sec="'.$k.'"><div class="sec-title">'.h($n).'<span>'.count($in).' simulaciones</span></div><div class="grid">';
  foreach ($in as $id=>[$t,$d,$f,$ic]) echo '<a class="card sim" href="sim.php?id='.$id.'"><div class="sim-ico"><i class="fa-solid '.$ic.'"></i></div><h3>'.h($t).'</h3><p>'.h($d).'</p><div class="tags"><span class="badge">'.h($n).'</span></div></a>';
  echo '</div></section>'; }
page_foot();
