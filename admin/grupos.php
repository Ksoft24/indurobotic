<?php require __DIR__.'/../src/bootstrap.php';
$u = require_login([R_ADMIN,R_CREATOR]); $cid = (int)$u['id_cliente'];
if ($u['id_rol']==R_ADMIN) { $cid = (int)($_GET['c'] ?? $_SESSION['c_adm'] ?? val('SELECT MIN(id_cliente) FROM clientes')); $_SESSION['c_adm']=$cid; }
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); $a=$_POST['a']??'';
  if ($a==='new' && trim($_POST['nombre'])) { q('INSERT INTO grupos (client_id,nombre,descripcion,docente) VALUES (?,?,?,?)',[$cid,trim($_POST['nombre']),$_POST['descripcion']?:null,$_POST['docente']?:null]); flash('Grupo creado'); }
  if ($a==='del') { q('DELETE FROM grupos WHERE id=? AND client_id=?',[(int)$_POST['id'],$cid]); flash('Grupo eliminado'); }
  if ($a==='link') { $g=(int)$_POST['id']; if (val('SELECT COUNT(*) FROM grupos WHERE id=? AND client_id=?',[$g,$cid])) { q('INSERT INTO enlaces_registro (id_cliente,id_grupo,token,vence_en,max_usos) VALUES (?,?,?,?,?)',[$cid,$g,bin2hex(random_bytes(24)),$_POST['vence']?:date('Y-m-d',strtotime('+30 days')),$_POST['max']?:null]); flash('Enlace de inscripción generado'); } }
  if ($a==='off') { q('UPDATE enlaces_registro SET activo=0 WHERE id_enlace=? AND id_cliente=?',[(int)$_POST['id'],$cid]); }
  go('grupos.php'); }
page_head('Grupos','admin/grupos.php');
if ($u['id_rol']==R_ADMIN) { echo '<form class="card" method="get"><label>Institución</label><select name="c" onchange="this.form.submit()">'; foreach (rows('SELECT id_cliente,razon_social FROM clientes') as $c) echo '<option value="'.$c['id_cliente'].'"'.($c['id_cliente']==$cid?' selected':'').'>'.h($c['razon_social']).'</option>'; echo '</select></form>'; }
?>
<div class="card"><h2>Nuevo grupo</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="new"><div class="row"><div><label>Nombre</label><input name="nombre" required></div><div><label>Descripción</label><input name="descripcion"></div><div><label>Docente</label><input name="docente"></div></div><p><button class="btn">Crear</button></p></form></div>
<?php foreach (rows('SELECT g.*, (SELECT COUNT(*) FROM usuarios u WHERE u.grupo_id=g.id) AS n FROM grupos g WHERE client_id=? ORDER BY id',[$cid]) as $g): ?>
<div class="card"><div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><div><h3 style="margin:0"><?= h($g['nombre']) ?></h3><small><?= h($g['descripcion']) ?> · Docente: <?= h($g['docente']?:'—') ?> · <?= $g['n'] ?> inscritos</small></div>
<form method="post" class="inline" data-confirm="¿Eliminar el grupo? Los usuarios quedan sin grupo."><?= csrf_field() ?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= $g['id'] ?>"><button class="btn danger sm">Eliminar</button></form></div>
<form method="post" class="row" style="margin-top:12px;align-items:end"><?= csrf_field() ?><input type="hidden" name="a" value="link"><input type="hidden" name="id" value="<?= $g['id'] ?>"><div><label>Vence (def. +30 días)</label><input type="date" name="vence"></div><div><label>Máx. inscripciones</label><input type="number" name="max" min="1"></div><div><button class="btn ghost">Generar enlace</button></div></form>
<?php foreach (rows('SELECT * FROM enlaces_registro WHERE id_grupo=? AND activo=1 ORDER BY id_enlace DESC',[$g['id']]) as $e): $url=base_url().'/registro.php?t='.$e['token']; ?>
<form method="post" style="display:flex;gap:8px;margin-top:8px"><?= csrf_field() ?><input type="hidden" name="a" value="off"><input type="hidden" name="id" value="<?= $e['id_enlace'] ?>"><input readonly value="<?= h($url) ?>" onclick="this.select()"><small style="white-space:nowrap"><?= $e['usos'] ?>/<?= $e['max_usos']?:'∞' ?> · <?= h($e['vence_en']) ?></small><button class="btn ghost sm">Desactivar</button></form>
<?php endforeach; ?></div><?php endforeach; page_foot();
