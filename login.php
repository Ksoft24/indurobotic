<?php require __DIR__.'/src/bootstrap.php';
if (me()) go('app.php');
$err = null;
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); $err = attempt_login(trim($_POST['usuario']??''), $_POST['password']??''); if (!$err) go('app.php'); }
page_head('Ingresar');
?>
<form method="post" class="card narrow"><h2><i class="fa-solid fa-robot" style="color:var(--brand)"></i> Indu<span style="color:var(--brand)">Robotic</span></h2>
<?php if ($err) echo '<div class="toast">'.h($err).'</div>'; if ($m=flash()) echo '<div class="toast">'.h($m).'</div>'; ?>
<?= csrf_field() ?><label>Usuario o correo</label><input name="usuario" required autofocus><label>Contraseña</label><input type="password" name="password" required>
<p><button class="btn" style="width:100%;justify-content:center">Ingresar</button></p></form>
<?php page_foot();
