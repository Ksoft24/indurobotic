<?php require __DIR__.'/../src/bootstrap.php';
$u = require_login([R_ADMIN,R_CREATOR]); $cid=(int)$u['id_cliente'];
if ($u['id_rol']==R_ADMIN) { $cid=(int)($_GET['c'] ?? $_SESSION['c_adm'] ?? val('SELECT MIN(id_cliente) FROM clientes')); $_SESSION['c_adm']=$cid; }
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); $a=$_POST['a']??''; $id=(int)($_POST['id']??0);
  if ($a==='save') { $pr = $_POST['protocolo']==='ws'?'ws':'wss'; $prev = $id ? row('SELECT password_mqtt FROM mqtt_brokers WHERE id_broker=? AND id_cliente=?',[$id,$cid]) : null;
    $pw = $_POST['password']!=='' ? enc($_POST['password']) : ($prev['password_mqtt']??null);
    $v = [trim($_POST['nombre']),trim($_POST['host']),(int)$_POST['puerto']?:8883,$pr,$_POST['usuario']?:null,$pw,isset($_POST['tls'])?1:0];
    if ($id) q('UPDATE mqtt_brokers SET nombre=?,host=?,puerto=?,protocolo=?,usuario_mqtt=?,password_mqtt=?,usa_tls=? WHERE id_broker=? AND id_cliente=?',[...$v,$id,$cid]);
    else q('INSERT INTO mqtt_brokers (nombre,host,puerto,protocolo,usuario_mqtt,password_mqtt,usa_tls,id_cliente,id_creado_por) VALUES (?,?,?,?,?,?,?,?,?)',[...$v,$cid,$u['id_usuario']]);
    flash('Broker guardado'); }
  if ($a==='toggle') q('UPDATE mqtt_brokers SET estado=1-estado WHERE id_broker=? AND id_cliente=?',[$id,$cid]);
  if ($a==='del') q('DELETE FROM mqtt_brokers WHERE id_broker=? AND id_cliente=?',[$id,$cid]);
  go('mqtt.php'); }
$e = isset($_GET['e']) ? row('SELECT * FROM mqtt_brokers WHERE id_broker=? AND id_cliente=?',[(int)$_GET['e'],$cid]) : null;
page_head('Brokers MQTT','admin/mqtt.php');
if ($u['id_rol']==R_ADMIN) { echo '<form class="card" method="get"><label>Institución</label><select name="c" onchange="this.form.submit()">'; foreach (rows('SELECT id_cliente,razon_social FROM clientes') as $c) echo '<option value="'.$c['id_cliente'].'"'.($c['id_cliente']==$cid?' selected':'').'>'.h($c['razon_social']).'</option>'; echo '</select></form>'; }
?>
<div class="card"><h2><?= $e?'Editar':'Nuevo' ?> broker</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= $e['id_broker']??0 ?>">
<div class="row"><div><label>Nombre</label><input name="nombre" required value="<?= h($e['nombre']??'') ?>"></div><div><label>Host</label><input name="host" required value="<?= h($e['host']??'') ?>"></div><div><label>Puerto (WebSocket)</label><input type="number" name="puerto" value="<?= h($e['puerto']??8884) ?>"></div>
<div><label>Protocolo</label><select name="protocolo"><option>wss</option><option<?= ($e['protocolo']??'')==='ws'?' selected':'' ?>>ws</option></select></div><div><label>Usuario MQTT</label><input name="usuario" value="<?= h($e['usuario_mqtt']??'') ?>"></div><div><label>Contraseña <?= $e?'(vacío = conservar)':'' ?></label><input type="password" name="password" autocomplete="new-password"></div></div>
<label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="tls" style="width:auto" <?= ($e['usa_tls']??1)?'checked':'' ?>> Usar TLS</label><p><button class="btn">Guardar</button> <?php if($e) echo '<a class="btn ghost" href="mqtt.php">Cancelar</a>'; ?></p></form></div>
<div class="card tw"><table><tr><th>Nombre</th><th>URL</th><th>Usuario</th><th>Estado</th><th></th></tr>
<?php foreach (rows('SELECT * FROM mqtt_brokers WHERE id_cliente=? ORDER BY id_broker',[$cid]) as $b): ?>
<tr><td><?= h($b['nombre']) ?></td><td><?= h($b['protocolo'].'://'.$b['host'].':'.$b['puerto']) ?></td><td><?= h($b['usuario_mqtt']?:'—') ?></td><td><?= $b['estado']?'<span class="b-ok">Activo</span>':'<span class="b-bad">Inactivo</span>' ?></td>
<td style="white-space:nowrap"><a class="btn ghost sm" href="?e=<?= $b['id_broker'] ?>">Editar</a> <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id_broker'] ?>"><button name="a" value="toggle" class="btn ghost sm">On/Off</button> <button name="a" value="del" class="btn danger sm" onclick="return confirm('¿Eliminar broker?')">Eliminar</button></form></td></tr>
<?php endforeach; ?></table></div><?php page_foot();
