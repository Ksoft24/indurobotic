<?php require __DIR__.'/../src/bootstrap.php';
$u = require_login([R_ADMIN,R_CREATOR]); $cid=(int)$u['id_cliente'];
if ($u['id_rol']==R_ADMIN) { $cid=(int)($_GET['c'] ?? $_SESSION['c_adm'] ?? val('SELECT MIN(id_cliente) FROM clientes')); $_SESSION['c_adm']=$cid; }
$l = license($cid);
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); $a=$_POST['a']??'';
  if ($a==='new') { $em=trim($_POST['email']); $us=trim($_POST['usuario']); $g=(int)$_POST['grupo']?:null;
    if (!$l['cupo']) flash('Límite de usuarios de la licencia alcanzado');
    elseif (!filter_var($em,FILTER_VALIDATE_EMAIL)||!$us||val('SELECT COUNT(*) FROM usuarios WHERE usuario=? OR email=?',[$us,$em])) flash('Datos inválidos o usuario/correo duplicado');
    elseif ($g && !val('SELECT COUNT(*) FROM grupos WHERE id=? AND client_id=?',[$g,$cid])) flash('Grupo inválido');
    else { create_user($cid,$g,R_VIEWER,trim($_POST['nombre']),$us,$em,null); flash('Usuario creado; recibirá un correo para confirmar y definir su contraseña'); } }
  $t=(int)($_POST['id']??0); $own = val('SELECT COUNT(*) FROM usuarios WHERE id_usuario=? AND id_cliente=? AND id_rol<>1 AND id_usuario<>?',[$t,$cid,$u['id_usuario']]);
  if ($a==='toggle' && $own) { $on=(int)val('SELECT estado FROM usuarios WHERE id_usuario=?',[$t]);
    if (!$on && !$l['cupo']) flash('Límite de usuarios alcanzado'); else q('UPDATE usuarios SET estado=?, session_token=NULL WHERE id_usuario=?',[$on?0:1,$t]); }
  if ($a==='resend' && $own) { send_verification($t,'activacion'); flash('Confirmación reenviada'); }
  go('usuarios.php'); }
page_head('Usuarios','admin/usuarios.php');
if ($u['id_rol']==R_ADMIN) { echo '<form class="card" method="get"><label>Institución</label><select name="c" onchange="this.form.submit()">'; foreach (rows('SELECT id_cliente,razon_social FROM clientes') as $c) echo '<option value="'.$c['id_cliente'].'"'.($c['id_cliente']==$cid?' selected':'').'>'.h($c['razon_social']).'</option>'; echo '</select></form>'; }
echo '<div class="card"><span class="badge b-'.h($l['plan']).'">'.strtoupper(h($l['plan'])).'</span> Usuarios activos: <b>'.$l['usados'].' / '.($l['max']===PHP_INT_MAX?'ilimitado':$l['max']).'</b>'.($l['dias']!==null?' · vence en '.$l['dias'].' días':'').($l['ok']?'':' · <span class="b-bad">'.h($l['motivo']).'</span>').'</div>';
?>
<div class="card"><h2>Nuevo usuario</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="new"><div class="row"><div><label>Nombre</label><input name="nombre" required></div><div><label>Usuario</label><input name="usuario" required></div><div><label>Correo</label><input type="email" name="email" required></div>
<div><label>Grupo</label><select name="grupo"><option value="0">— Sin grupo —</option><?php foreach (rows('SELECT id,nombre FROM grupos WHERE client_id=?',[$cid]) as $g) echo '<option value="'.$g['id'].'">'.h($g['nombre']).'</option>'; ?></select></div></div><p><button class="btn" <?= $l['cupo']?'':'disabled' ?>>Crear e invitar</button></p></form></div>
<div class="card tw"><table><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Grupo</th><th>Estado</th><th></th></tr>
<?php foreach (rows('SELECT u.*, g.nombre AS grupo FROM usuarios u LEFT JOIN grupos g ON g.id=u.grupo_id WHERE u.id_cliente=? ORDER BY u.id_usuario',[$cid]) as $x): ?>
<tr><td><?= h($x['nombre']) ?></td><td><?= h($x['usuario']) ?></td><td><?= h($x['email']) ?> <?= $x['email_verificado']?'<i class="fa-solid fa-circle-check b-ok"></i>':'<span class="badge b-warn">sin confirmar</span>' ?></td><td><?= h($x['grupo']?:'—') ?></td><td><?= $x['estado']?'<span class="b-ok">Activo</span>':'<span class="b-bad">Inactivo</span>' ?></td>
<td style="white-space:nowrap"><?php if ($x['id_usuario']!=$u['id_usuario'] && $x['id_rol']!=1): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id_usuario'] ?>"><button name="a" value="toggle" class="btn ghost sm"><?= $x['estado']?'Desactivar':'Activar' ?></button> <?php if(!$x['email_verificado']) echo '<button name="a" value="resend" class="btn ghost sm">Reenviar correo</button>'; ?></form><?php endif; ?></td></tr>
<?php endforeach; ?></table></div><?php page_foot();
