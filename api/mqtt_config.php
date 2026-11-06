<?php require __DIR__.'/../src/bootstrap.php';
// Brokers para la consola MQTT de las simulaciones.
// Institución del usuario + brokers globales (id_cliente NULL). El administrador general ve todos los activos.
// Las credenciales llegan al navegador porque Paho/WebSocket se conecta desde allí.
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
$u = me();
if (!$u) { http_response_code(401); echo json_encode(['error'=>'Sesión no válida: vuelve a iniciar sesión']); exit; }
try {
  $out = [];
  $sql = 'SELECT b.*, c.razon_social FROM mqtt_brokers b LEFT JOIN clientes c ON c.id_cliente=b.id_cliente WHERE b.estado=1';
  $rs = ((int)$u['id_rol'] === R_ADMIN)
    ? rows($sql.' ORDER BY b.id_broker')
    : rows($sql.' AND (b.id_cliente IS NULL OR b.id_cliente=?) ORDER BY b.id_broker', [$u['id_cliente']]);
  foreach ($rs as $b) {
    $nom = $b['nombre'].((int)$u['id_rol'] === R_ADMIN && $b['razon_social'] ? ' ('.$b['razon_social'].')' : '');
    $out[] = ['id'=>(int)$b['id_broker'],'nombre'=>$nom,'url'=>$b['protocolo'].'://'.$b['host'].':'.$b['puerto'].'/mqtt','usuario'=>$b['usuario_mqtt'],'password'=>dec($b['password_mqtt']),'tls'=>(bool)$b['usa_tls']];
  }
  echo json_encode($out);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['error'=>'Error al leer los brokers (revisa la tabla mqtt_brokers)']); }
