<?php require __DIR__.'/src/bootstrap.php';
if ($u = me()) q('UPDATE usuarios SET session_token=NULL WHERE id_usuario=?', [$u['id_usuario']]);
$_SESSION = []; session_destroy(); go('login.php');
