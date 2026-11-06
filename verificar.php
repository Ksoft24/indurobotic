<?php require __DIR__.'/src/bootstrap.php';
$t = $_GET['t'] ?? $_POST['t'] ?? '';
$v = $t ? row('SELECT id_usuario,verif_tipo,email_pendiente FROM usuarios WHERE verif_token_hash=? AND verif_expira>NOW()', [hash('sha256',$t)]) : null;
page_head('Confirmación de correo'); $msg = null;
if (!$v) $msg = 'El enlace no es válido o ya venció. Pide al administrador que reenvíe la confirmación.';
elseif ($v['verif_tipo']==='activacion' && $_SERVER['REQUEST_METHOD']!=='POST') {
  echo '<form method="post" class="card narrow"><h2>Define tu contraseña</h2>'.csrf_field().'<input type="hidden" name="t" value="'.h($t).'"><label>Nueva contraseña (mín. 8)</label><input type="password" name="password" minlength="8" required><p><button class="btn">Confirmar correo</button></p></form>'; page_foot(); exit;
} else {
  if ($_SERVER['REQUEST_METHOD']==='POST') csrf_check();
  if ($v['verif_tipo']==='activacion') { $pw=$_POST['password']??''; if (strlen($pw)<8) { echo '<div class="card narrow">Contraseña muy corta.</div>'; page_foot(); exit; } q('UPDATE usuarios SET password_hash=? WHERE id_usuario=?',[password_hash($pw,PASSWORD_DEFAULT),$v['id_usuario']]); }
  if ($v['verif_tipo']==='cambio_email') q('UPDATE usuarios SET email=?, email_verificado=1 WHERE id_usuario=?',[$v['email_pendiente'],$v['id_usuario']]);
  else q('UPDATE usuarios SET email_verificado=1 WHERE id_usuario=?',[$v['id_usuario']]);
  q('UPDATE usuarios SET verif_tipo=NULL, verif_token_hash=NULL, verif_expira=NULL, email_pendiente=NULL WHERE id_usuario=?',[$v['id_usuario']]);
  $msg='Correo confirmado. Ya puedes ingresar.';
}
echo '<div class="card narrow"><h2>Confirmación</h2><p>'.h($msg).'</p><a class="btn" href="login.php">Ir a ingresar</a></div>'; page_foot();
