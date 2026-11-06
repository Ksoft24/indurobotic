<?php require __DIR__.'/src/bootstrap.php';
$u = require_login([R_ADMIN,R_CREATOR]); page_head('Guía de estilo','estilos.php');
$fam = ['acero'=>['Acero','Neutros: fondos, paneles, bordes y textos'],'naranja'=>['Naranja Industrial','Marca: acción principal, estado activo y acentos'],
        'verde'=>['Verde Operación','Correcto / en marcha'],'ambar'=>['Ámbar Alerta','Aviso / en espera'],'rojo'=>['Rojo Paro','Peligro / paro de emergencia'],'azul'=>['Azul Datos','Información / foco / datos']];
$steps = [50,100,200,300,400,500,600,700,800,900,950];
?>
<p>Todo el proyecto (plataforma y simuladores) usa una sola hoja de estilo: <b>assets/css/indurobotic.css</b>. Para cambiar un color en todo el sitio, edite solo la sección «Paleta y tokens».</p>

<div class="card"><h2>1. Paleta</h2>
<?php foreach ($fam as $k=>[$n,$uso]): ?>
  <div class="fam"><h3><?= h($n) ?> <small><?= h($uso) ?></small></h3><div class="swatches">
  <?php foreach ($steps as $s): ?><div class="swatch"><i style="background:var(--<?= $k ?>-<?= $s ?>)"></i><span><b><?= $k ?>-<?= $s ?></b><em style="font-style:normal" data-hex="--<?= $k ?>-<?= $s ?>"></em></span></div><?php endforeach; ?>
  </div></div>
<?php endforeach; ?>
<div class="fam"><h3>Entorno 3D <small>Fondo, piso y grilla de los simuladores</small></h3><div class="tokens">
  <?php foreach ([['--env-cielo','Gris Niebla','Fondo / cielo'],['--env-piso','Gris Acero','Piso'],['--env-grilla','Gris Grilla','Líneas de la cuadrícula']] as [$v,$n,$u2]): ?>
    <div class="token"><i style="background:var(<?= $v ?>)"></i><div><b><?= $n ?></b><small><span data-hex="<?= $v ?>"></span> · <?= $u2 ?></small></div></div>
  <?php endforeach; ?></div></div></div>

<div class="card"><h2>2. Colores semánticos</h2><div class="tokens">
<?php foreach ([['--bg','Fondo de página'],['--surface','Panel / tarjeta'],['--surface-2','Panel elevado / campos'],['--line','Bordes'],['--brand','Marca / acción principal'],['--on-brand','Texto sobre marca'],
  ['--ok','Correcto'],['--warn','Aviso'],['--danger','Peligro'],['--info','Información / foco']] as [$v,$n]): ?>
  <div class="token"><i style="background:var(<?= $v ?>)"></i><div><b><?= $v ?></b><small><span data-hex="<?= $v ?>"></span> · <?= $n ?></small></div></div>
<?php endforeach; ?></div></div>

<div class="card"><h2>3. Colores de texto</h2><div class="type-sample">
  <div style="color:var(--tx)"><b style="color:inherit">--tx</b> · Texto principal <small>(<span data-hex="--tx"></span>)</small></div>
  <div style="color:var(--tx-2)"><b style="color:inherit">--tx-2</b> · Texto secundario <small>(<span data-hex="--tx-2"></span>)</small></div>
  <div style="color:var(--tx-3)"><b style="color:inherit">--tx-3</b> · Ayudas y datos atenuados <small>(<span data-hex="--tx-3"></span>)</small></div>
  <div style="color:var(--tx-brand)"><b style="color:inherit">--tx-brand</b> · Enlaces y énfasis de marca <small>(<span data-hex="--tx-brand"></span>)</small></div>
  <div><span style="color:var(--ok)">● Correcto</span> &nbsp; <span style="color:var(--warn)">● Aviso</span> &nbsp; <span style="color:var(--danger)">● Peligro</span> &nbsp; <span style="color:var(--info)">● Información</span></div>
</div></div>

<div class="card"><h2>4. Tipografía</h2><div class="type-sample">
  <div style="font-size:var(--fs-2xl);font-weight:700">Inter · Título 24 / 700</div>
  <div style="font-size:var(--fs-xl);font-weight:700">Subtítulo 18 / 700</div>
  <div style="font-size:var(--fs-lg);font-weight:600">Encabezado de sección 15 / 600</div>
  <div style="font-size:var(--fs-md)">Texto base 13 / 400 · Cifras tabulares: 0123456789 · 12.4 m/s · -85.4 kPa</div>
  <div style="font-size:var(--fs-sm);color:var(--tx-3)">Ayuda 12 / 400</div>
  <div style="font-size:var(--fs-xs);color:var(--tx-3);text-transform:uppercase;letter-spacing:.06em;font-weight:600">Etiqueta 11 / 600</div>
</div></div>

<div class="card"><h2>5. Botones</h2><div class="btns">
  <button class="btn">Principal</button><button class="btn ghost">Secundario</button><button class="btn ok">Confirmar</button><button class="btn danger">Peligro</button>
  <button class="btn" disabled>Deshabilitado</button><button class="btn sm">Pequeño</button><button class="btn ghost sm">Pequeño secundario</button>
</div>
<p class="muted" style="margin-top:12px">En los simuladores los botones siguen el mismo sistema: secundario por defecto; <b>naranja</b> = activo/principal; verde = confirmar; rojo = paro/reiniciar.</p></div>

<div class="card"><h2>6. Campos, etiquetas y estados</h2>
<div class="row"><div><label>Campo de texto</label><input placeholder="Ejemplo"></div><div><label>Lista</label><select><option>Opción A</option><option>Opción B</option></select></div></div>
<p style="margin-top:14px"><span class="badge b-ok">ACTIVO</span> <span class="badge b-warn">POR VENCER</span> <span class="badge b-bad">VENCIDA</span> <span class="badge b-standard">STANDARD</span> <span class="badge b-premium">PREMIUM</span> <span class="badge b-demo">DEMO</span></p>
<div class="toast">Mensaje informativo con borde de marca.</div></div>
<?php page_foot();
