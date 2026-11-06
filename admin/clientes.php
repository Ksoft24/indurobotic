<?php require __DIR__.'/../src/bootstrap.php';
$u = require_login([R_ADMIN]);
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); $id=(int)($_POST['id']??0);
  [$plan,$max,$vence] = normalize_plan($_POST); $est = in_array($_POST['estado'],['pending','active','suspended'],true) ? $_POST['estado'] : 'pending';
  if ($id) { q('UPDATE clientes SET razon_social=?,ruc=?,email=?,plan_tipo=?,max_usuarios=?,plan_vence_en=?,estado=? WHERE id_cliente=?',[trim($_POST['razon_social']),$_POST['ruc']?:null,$_POST['email']?:null,$plan,$max,$vence,$est,$id]); flash('Institución actualizada'); }
  else { q('INSERT INTO clientes (razon_social,ruc,email,plan_tipo,max_usuarios,plan_vence_en,estado,registro_token) VALUES (?,?,?,?,?,?,?,?)',[trim($_POST['razon_social']),$_POST['ruc']?:null,$_POST['email']?:null,$plan,$max,$vence,$est,bin2hex(random_bytes(16))]);
    $cid=(int)db()->lastInsertId(); $ae=trim($_POST['admin_email']??'');
    if (filter_var($ae,FILTER_VALIDATE_EMAIL)) { create_user($cid,null,R_CREATOR,trim($_POST['admin_nombre'])?:'Administrador',explode('@',$ae)[0].'.'.$cid,$ae,null); flash('Institución creada; se envió la invitación al administrador'); } else flash('Institución creada'); }
  go('clientes.php'); }
$edit = isset($_GET['e']) ? row('SELECT * FROM clientes WHERE id_cliente=?',[(int)$_GET['e']]) : null;
page_head('Instituciones','admin/clientes.php');
?>
<div class="card"><h2><?= $edit?'Editar':'Nueva' ?> institución</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit['id_cliente']??0 ?>">
<div class="row"><div><label>Razón social</label><input name="razon_social" required value="<?= h($edit['razon_social']??'') ?>"></div><div><label>RUC</label><input name="ruc" value="<?= h($edit['ruc']??'') ?>"></div><div><label>Correo institucional</label><input type="email" name="email" value="<?= h($edit['email']??'') ?>"></div></div>
<div class="row"><div><label>Licencia</label><select name="plan_tipo"><?php foreach(['demo'=>'DEMO (1 usuario)','standard'=>'STANDARD (usuarios limitados + vencimiento)','premium'=>'PREMIUM (ilimitado + vencimiento)'] as $k=>$v) echo '<option value="'.$k.'"'.(($edit['plan_tipo']??'demo')===$k?' selected':'').'>'.$v.'</option>'; ?></select></div>
<div><label>Máx. usuarios (STANDARD)</label><input type="number" min="1" name="max_usuarios" value="<?= h($edit['max_usuarios']??10) ?>"></div><div><label>Vence el</label><input type="date" name="plan_vence_en" value="<?= h($edit['plan_vence_en']??'') ?>"></div>
<div><label>Estado</label><select name="estado"><?php foreach(['active'=>'Activa','pending'=>'Pendiente','suspended'=>'Suspendida'] as $k=>$v) echo '<option value="'.$k.'"'.(($edit['estado']??'active')===$k?' selected':'').'>'.$v.'</option>'; ?></select></div></div>
<?php if (!$edit): ?><div class="row"><div><label>Admin de la institución (nombre)</label><input name="admin_nombre"></div><div><label>Correo del admin (recibe invitación)</label><input type="email" name="admin_email"></div></div><?php endif; ?>
<p><button class="btn">Guardar</button> <?php if($edit) echo '<a class="btn ghost" href="clientes.php">Cancelar</a>'; ?></p></form></div>
<div class="card tw"><table><tr><th>Institución</th><th>Licencia</th><th>Usuarios</th><th>Vence</th><th>Estado</th><th></th></tr>
<?php foreach (rows('SELECT * FROM clientes ORDER BY id_cliente') as $c) { $l=license((int)$c['id_cliente']);
 echo '<tr><td>'.h($c['razon_social']).'</td><td><span class="badge b-'.h($c['plan_tipo']).'">'.strtoupper(h($c['plan_tipo'])).'</span></td><td>'.$l['usados'].' / '.($l['max']===PHP_INT_MAX?'∞':$l['max']).'</td><td>'.h($c['plan_vence_en']?:'—').($l['dias']!==null&&$l['dias']<0?' <span class="b-bad">vencida</span>':'').'</td><td>'.h($c['estado']).'</td><td><a class="btn ghost sm" href="?e='.$c['id_cliente'].'">Editar</a></td></tr>'; } ?></table></div>
<?php page_foot();
