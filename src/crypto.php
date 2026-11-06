<?php
// AES-256-CBC, formato base64(iv[16] . ciphertext). Ajustar si tu app anterior usaba otro esquema.
function app_key(): string { return (string)base64_decode((string)env('APP_KEY')); }
function enc(string $t): string { $iv=random_bytes(16); return base64_encode($iv.openssl_encrypt($t,'aes-256-cbc',app_key(),OPENSSL_RAW_DATA,$iv)); }
function dec(?string $b): string { if(!$b) return ''; $r=(string)base64_decode($b); if(strlen($r)<17) return ''; $p=openssl_decrypt(substr($r,16),'aes-256-cbc',app_key(),OPENSSL_RAW_DATA,substr($r,0,16)); return $p===false ? '' : $p; }
