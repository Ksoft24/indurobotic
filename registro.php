<?php require __DIR__.'/src/bootstrap.php';
$t = $_GET['t'] ?? $_POST['t'] ?? '';
$e = row('SELECT e.*, g.nombre AS grupo, c.razon_social FROM enlaces_registro e JOIN grupos g ON g.id=e.id_grupo JOIN clientes c ON c.id_cliente=e.id_cliente WHERE e.token=? AND e.activo=1 AND (e.vence_en IS NULL OR e.vence_en>=CURDATE()) AND (e.max_usos IS NULL OR e.usos<e.max_usos)', [$t]);
page_head('Inscripción');
if (!$e) { echo '<div class="card narrow"><h2>Enlace no válido</h2><p>El enlace venció, se agotó o fue desactivado.</p></div>'; page_foot(); exit; }
$l = license((int)$e['id_cliente']); $err = null;
if (!$l['ok']) $err = $l['motivo'];
elseif (!$l['cupo']) $err = 'La institución alcanzó el límite de usuarios de su licencia';
elseif ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_check(); $n=trim($_POST['nombre']); $us=trim($_POST['usuario']); $em=trim($_POST['email']); $pw=$_POST['password'];
  if (strlen($pw)<8 || !filter_var($em,FILTER_VALIDATE_EMAIL) || !$n || !$us) $err='Completa los datos (contraseña mínimo 8 caracteres)';
  elseif (val('SELECT COUNT(*) FROM usuarios WHERE usuario=? OR email=?',[$us,$em])) $err='Usuario o correo ya registrado';
  else { $nid=create_user((int)$e['id_cliente'],(int)$e['id_grupo'],R_VIEWER,$n,$us,$em,$pw); q('UPDATE usuarios SET id_enlace_registro=? WHERE id_usuario=?',[$e['id_enlace'],$nid]); q('UPDATE enlaces_registro SET usos=usos+1 WHERE id_enlace=?',[$e['id_enlace']]);
    flash('Cuenta creada. Revisa tu correo para confirmarla.'); go('login.php'); }
}
?>
<form method="post" class="card narrow"><h2>Inscripción</h2><p><b><?= h($e['razon_social']) ?></b> · <?= h($e['grupo']) ?></p>
<?php if ($err) echo '<div class="toast">'.h($err).'</div>'; ?>
<?= csrf_field() ?><input type="hidden" name="t" value="<?= h($t) ?>">
<label>Nombre completo</label><input name="nombre" required><label>Usuario</label><input name="usuario" required><label>Correo</label><input type="email" name="email" required><label>Contraseña</label><input type="password" name="password" minlength="8" required>
<p><button class="btn" style="width:100%;justify-content:center">Crear cuenta</button></p></form>
<?php page_foot();
