<?php require __DIR__.'/src/bootstrap.php';
$u = require_login(); page_head('Mi perfil','perfil.php');
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  if (isset($_POST['email'])) { $em=trim($_POST['email']);
    if (!filter_var($em,FILTER_VALIDATE_EMAIL) || val('SELECT COUNT(*) FROM usuarios WHERE email=?',[$em])) flash('Correo inválido o ya en uso');
    else { send_verification((int)$u['id_usuario'],'cambio_email',$em); flash('Te enviamos un enlace de confirmación al nuevo correo. El cambio se aplica al confirmarlo.'); } }
  if (isset($_POST['nueva'])) { if (strlen($_POST['nueva'])>=8 && password_verify($_POST['actual'],$u['password_hash'])) { q('UPDATE usuarios SET password_hash=? WHERE id_usuario=?',[password_hash($_POST['nueva'],PASSWORD_DEFAULT),$u['id_usuario']]); flash('Contraseña actualizada'); } else flash('Contraseña actual incorrecta o nueva muy corta'); }
  go('perfil.php'); }
?>
<div class="card wide"><h2>Cambiar correo</h2><p>Actual: <b><?= h($u['email']) ?></b></p><form method="post"><?= csrf_field() ?><label>Nuevo correo</label><input type="email" name="email" required><p><button class="btn">Enviar confirmación</button></p></form></div>
<div class="card wide"><h2>Cambiar contraseña</h2><form method="post"><?= csrf_field() ?><label>Contraseña actual</label><input type="password" name="actual" required><label>Nueva (mín. 8)</label><input type="password" name="nueva" minlength="8" required><p><button class="btn">Actualizar</button></p></form></div>
<?php page_foot();
