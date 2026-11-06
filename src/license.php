<?php
// DEMO: 1 usuario, sin vencimiento. STANDARD: max_usuarios + vencimiento. PREMIUM: ilimitado + vencimiento.
function license(int $cid): array {
  $c = row('SELECT * FROM clientes WHERE id_cliente=?', [$cid]);
  if (!$c) return ['ok'=>false,'motivo'=>'Institución no encontrada','max'=>0,'usados'=>0,'dias'=>null,'cupo'=>false,'plan'=>'-','cliente'=>null];
  $usados = (int)val('SELECT COUNT(*) FROM usuarios WHERE id_cliente=? AND estado=1', [$cid]);
  $max = match($c['plan_tipo']) { 'demo'=>1, 'standard'=>(int)$c['max_usuarios'], default=>PHP_INT_MAX };
  $dias = $c['plan_vence_en'] ? (int)floor((strtotime($c['plan_vence_en'])-strtotime('today'))/86400) : null;
  $motivo = null;
  if ($c['estado'] !== 'active') $motivo = 'Institución '.($c['estado']==='pending'?'pendiente de activación':'suspendida');
  elseif ($c['plan_tipo'] !== 'demo' && ($dias === null || $dias < 0)) $motivo = 'La licencia de la institución está vencida';
  return ['ok'=>$motivo===null,'motivo'=>$motivo,'plan'=>$c['plan_tipo'],'max'=>$max,'usados'=>$usados,'dias'=>$dias,'cupo'=>$usados<$max,'cliente'=>$c];
}
function normalize_plan(array $in): array {
  $plan = in_array($in['plan_tipo']??'', ['demo','standard','premium'], true) ? $in['plan_tipo'] : 'demo';
  $vence = ($in['plan_vence_en'] ?? '') ?: null;
  if ($plan==='demo') return [$plan,1,null];
  if (!$vence) $vence = date('Y-m-d', strtotime('+1 year'));
  return [$plan, $plan==='standard' ? max(1,(int)($in['max_usuarios']??1)) : 0, $vence]; // 0 = ilimitado
}
