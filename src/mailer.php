<?php
function send_mail(string $to, string $subject, string $html): bool {
  $c = row('SELECT * FROM configuracion_correo WHERE id_config=1'); if (!$c || !$c['host']) return false;
  $f = @stream_socket_client(($c['seguridad']==='ssl'?'ssl://':'tcp://').$c['host'].':'.$c['puerto'], $en, $es, 15);
  if (!$f) return false; stream_set_timeout($f, 15);
  $rd = function() use ($f){ $r=''; while(($l=fgets($f,515))!==false){ $r.=$l; if(($l[3]??' ')===' ') break; } return $r; };
  $cmd = function(?string $s, int $ok) use ($f,$rd){ if($s!==null) fwrite($f,$s."\r\n"); return str_starts_with($rd(), (string)$ok); };
  $rd(); $cmd('EHLO indurobotic',250);
  if ($c['seguridad']==='tls') { $cmd('STARTTLS',220); stream_socket_enable_crypto($f,true,STREAM_CRYPTO_METHOD_TLS_CLIENT); $cmd('EHLO indurobotic',250); }
  $cmd('AUTH LOGIN',334); $cmd(base64_encode($c['usuario']),334);
  if (!$cmd(base64_encode(dec($c['password'])),235)) { fclose($f); return false; }
  $from = $c['remitente_email'] ?: $c['usuario'];
  $cmd("MAIL FROM:<$from>",250); $cmd("RCPT TO:<$to>",250); $cmd('DATA',354);
  $msg = "From: =?UTF-8?B?".base64_encode((string)$c['remitente_nombre'])."?= <$from>\r\nTo: <$to>\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($html));
  fwrite($f, $msg."\r\n.\r\n"); $ok = $cmd(null,250); $cmd('QUIT',221); fclose($f); return $ok;
}
