<?php
function page_head(string $title, string $active=''): void {
  $u = me(); $b = base_url(); $tema = $u['tema'] ?? 'oscuro';
  echo '<!DOCTYPE html><html lang="es" data-theme="'.h($tema).'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' · InduRobotic</title>'
    .'<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    .'<link rel="stylesheet" href="'.$b.'/assets/css/indurobotic.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">'
    .'<script>window.BASE='.json_encode($b).';window.CSRF='.json_encode(csrf()).';</script><script defer src="'.$b.'/assets/js/app.js"></script></head><body class="indu-app">';
  if (!$u) { echo '<main class="center">'; return; }
  $all = [R_ADMIN,R_CREATOR,R_VIEWER,3];
  $nav = [
    ['app.php','fa-cubes','Simulaciones',$all],
    ['admin/clientes.php','fa-building','Instituciones',[R_ADMIN]],
    ['admin/grupos.php','fa-people-group','Grupos',[R_ADMIN,R_CREATOR]],
    ['admin/usuarios.php','fa-users','Usuarios',[R_ADMIN,R_CREATOR]],
    ['admin/mqtt.php','fa-tower-broadcast','Brokers MQTT',[R_ADMIN,R_CREATOR]],
    ['admin/correo.php','fa-envelope','Correo (SMTP)',[R_ADMIN]],
    ['estilos.php','fa-palette','Guía de estilo',[R_ADMIN,R_CREATOR]],
    ['perfil.php','fa-user-gear','Mi perfil',$all],
  ];
  echo '<aside class="side"><div class="brand"><i class="fa-solid fa-robot"></i>Indu<b>Robotic</b></div><nav>';
  foreach ($nav as [$href,$ic,$lbl,$roles]) if (in_array((int)$u['id_rol'],$roles,true))
    echo '<a href="'.$b.'/'.$href.'" class="'.($active===$href?'on':'').'"><i class="fa-solid '.$ic.'"></i> '.$lbl.'</a>';
  echo '</nav><div class="side-foot"><div class="who">'.h($u['nombre']).'<small>'.h($u['razon_social'] ?? 'Plataforma').'</small></div>'
    .'<button class="btn ghost sm" id="themeBtn" title="Cambiar tema"><i class="fa-solid fa-circle-half-stroke"></i> Tema</button> <a class="btn ghost sm" href="'.$b.'/logout.php" title="Salir"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></div></aside><main class="main"><div class="page-head"><h1>'.h($title).'</h1></div>';
  if ($m = flash()) echo '<div class="toast">'.h($m).'</div>';
}
function page_foot(): void { echo '</main></body></html>'; }
