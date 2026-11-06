<?php require __DIR__.'/../src/bootstrap.php';
$u = require_login([R_ADMIN]);
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  if (($_POST['a']??'')==='save') { $c=row('SELECT password FROM configuracion_correo WHERE id_config=1'); $pw=$_POST['password']!==''?enc($_POST['password']):($c['password']??null); $sg=in_array($_POST['seguridad'],['tls','ssl','ninguna'],true)?$_POST['seguridad']:'tls';
    q('INSERT INTO configuracion_correo (id_config,host,puerto,usuario,password,remitente_nombre,remitente_email,seguridad) VALUES (1,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE host=VALUES(host),puerto=VALUES(puerto),usuario=VALUES(usuario),password=VALUES(password),remitente_nombre=VALUES(remitente_nombre),remitente_email=VALUES(remitente_email),seguridad=VALUES(seguridad)',[$_POST['host'],(int)$_POST['puerto'],$_POST['usuario'],$pw,$_POST['rnombre'],$_POST['remail'],$sg]); flash('Configuración guardada'); }
  if (($_POST['a']??'')==='test') flash(send_mail($u['email'],'Prueba SMTP - InduRobotic','<p>El servidor de correo funciona.</p>') ? 'Correo de prueba enviado a '.$u['email'] : 'Falló el envío: revisa host, puerto, seguridad y credenciales');
  go('correo.php'); }
$c = row('SELECT * FROM configuracion_correo WHERE id_config=1') ?: []; page_head('Correo (SMTP)','admin/correo.php');
?>
<div class="card wide"><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="save"><div class="row"><div><label>Host</label><input name="host" required value="<?= h($c['host']??'') ?>"></div><div><label>Puerto</label><input type="number" name="puerto" value="<?= h($c['puerto']??587) ?>"></div>
<div><label>Seguridad</label><select name="seguridad"><?php foreach(['tls'=>'TLS (STARTTLS, 587)','ssl'=>'SSL (465)','ninguna'=>'Ninguna'] as $k=>$v) echo '<option value="'.$k.'"'.(($c['seguridad']??'tls')===$k?' selected':'').'>'.$v.'</option>'; ?></select></div>
<div><label>Usuario</label><input name="usuario" value="<?= h($c['usuario']??'') ?>"></div><div><label>Contraseña (vacío = conservar)</label><input type="password" name="password" autocomplete="new-password"></div>
<div><label>Nombre remitente</label><input name="rnombre" value="<?= h($c['remitente_nombre']??'InduRobotic') ?>"></div><div><label>Correo remitente</label><input type="email" name="remail" value="<?= h($c['remitente_email']??'') ?>"></div></div><p><button class="btn">Guardar</button></p></form>
<form method="post"><?= csrf_field() ?><button name="a" value="test" class="btn ghost">Enviar correo de prueba a mi cuenta</button></form></div><?php page_foot();
