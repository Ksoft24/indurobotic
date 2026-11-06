<?php
require __DIR__.'/../src/bootstrap.php'; csrf_check();
if ($u = me()) { $t = ($_POST['tema'] ?? '') === 'claro' ? 'claro' : 'oscuro'; q('UPDATE usuarios SET tema=? WHERE id_usuario=?', [$t, $u['id_usuario']]); }
http_response_code(204);
