<?php
const R_ADMIN=1, R_VIEWER=2, R_CREATOR=4;
function me(){
  static $u=false; if ($u!==false) return $u; $u=null;
  if (!empty($_SESSION['uid'])) {
    $r = row('SELECT u.*, c.razon_social, c.plan_tipo FROM usuarios u LEFT JOIN clientes c ON c.id_cliente=u.id_cliente WHERE u.id_usuario=? AND u.estado=1', [$_SESSION['uid']]);
    if ($r && hash_equals((string)$r['session_token'], (string)($_SESSION['tok'] ?? ''))) $u=$r;
  }
  return $u;
}
function require_login(array $roles=[]): array {
  $u = me(); if (!$u) go(base_url().'/login.php');
  if ($u['id_rol'] != R_ADMIN) { $l = license((int)$u['id_cliente']); if (!$l['ok']) { page_head('Acceso bloqueado'); echo '<div class="card narrow"><h2>Acceso bloqueado</h2><p>'.h($l['motivo']).'. Contacta al administrador de tu institución.</p><a class="btn" href="'.base_url().'/logout.php">Salir</a></div>'; page_foot(); exit; } }
  if ($roles && !in_array((int)$u['id_rol'], $roles, true)) { http_response_code(403); exit('Sin permiso'); }
  return $u;
}
function attempt_login(string $user, string $pass): ?string {
  $u = row('SELECT * FROM usuarios WHERE usuario=? OR email=?', [$user,$user]);
  if (!$u || !password_verify($pass, $u['password_hash'])) return 'Credenciales incorrectas';
  if (!$u['estado']) return 'Cuenta desactivada';
  if (!$u['email_verificado']) return 'Debes confirmar tu correo antes de ingresar';
  if ($u['id_rol'] != R_ADMIN) { $l = license((int)$u['id_cliente']); if (!$l['ok']) return $l['motivo']; }
  $tok = bin2hex(random_bytes(24)); // sesión única por usuario
  q('UPDATE usuarios SET session_token=?, ultimo_acceso=NOW() WHERE id_usuario=?', [$tok,$u['id_usuario']]);
  session_regenerate_id(true); $_SESSION['uid']=$u['id_usuario']; $_SESSION['tok']=$tok;
  return null;
}
function create_user(int $cid, ?int $gid, int $rol, string $nombre, string $usuario, string $email, ?string $pass): int {
  q('INSERT INTO usuarios (id_cliente,id_rol,nombre,usuario,password_hash,email,grupo_id,estado,email_verificado,token_publico) VALUES (?,?,?,?,?,?,?,1,0,?)',
    [$cid,$rol,$nombre,$usuario,password_hash($pass ?? bin2hex(random_bytes(12)), PASSWORD_DEFAULT),$email,$gid,bin2hex(random_bytes(20))]);
  $id=(int)db()->lastInsertId();
  send_verification($id, $pass===null ? 'activacion' : 'registro');
  return $id;
}
function send_verification(int $uid, string $tipo, ?string $nuevo=null): bool {
  $u = row('SELECT nombre,email FROM usuarios WHERE id_usuario=?',[$uid]); if(!$u) return false;
  $tok = bin2hex(random_bytes(32)); // se guarda solo el hash; un token nuevo invalida el anterior
  q('UPDATE usuarios SET verif_tipo=?, verif_token_hash=?, verif_expira=DATE_ADD(NOW(),INTERVAL 48 HOUR), email_pendiente=? WHERE id_usuario=?',[$tipo,hash('sha256',$tok),$nuevo,$uid]);
  $url = base_url().'/verificar.php?t='.$tok;
  $txt = $tipo==='activacion' ? 'Tu institución te creó una cuenta. Confirma tu correo y define tu contraseña' : 'Confirma tu correo';
  return send_mail($nuevo ?? $u['email'], 'Confirma tu correo - InduRobotic', '<p>Hola '.h($u['nombre']).',</p><p>'.$txt.': <a href="'.h($url).'">'.h($url).'</a></p><p>El enlace vence en 48 horas.</p>');
}
