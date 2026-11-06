<?php
declare(strict_types=1);
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();
$envFile = __DIR__.'/../.env';
if (!is_file($envFile)) { http_response_code(500); exit('Falta el archivo .env en la raíz del proyecto (copia .env.example y renómbralo a .env; en Windows verifica que no se llame .env.txt).'); }
$GLOBALS['env'] = parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [];
function env(string $k, $d=null){ $v = $GLOBALS['env'][$k] ?? null; return $v === null ? $d : trim((string)$v, " \t\"'"); }
function db(): PDO { static $p; return $p ??= new PDO('mysql:host='.env('DB_HOST','localhost').';dbname='.env('DB_NAME','bd_kunturvr').';charset=utf8mb4', env('DB_USER','root'), env('DB_PASS',''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
function q(string $s, array $a=[]): PDOStatement { $st=db()->prepare($s); $st->execute($a); return $st; }
function row(string $s, array $a=[]){ return q($s,$a)->fetch() ?: null; }
function rows(string $s, array $a=[]): array { return q($s,$a)->fetchAll(); }
function val(string $s, array $a=[]){ return q($s,$a)->fetchColumn(); }
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.csrf().'">'; }
function csrf_check(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals(csrf(), $_POST['_csrf'] ?? '')) { http_response_code(419); exit('CSRF'); } }
function go(string $u): never { header("Location: $u"); exit; }
function flash(?string $m=null){ if($m!==null){ $_SESSION['f']=$m; return null; } $x=$_SESSION['f']??null; unset($_SESSION['f']); return $x; }
function base_url(): string { return rtrim((string)env('APP_URL',''),'/'); }
require __DIR__.'/crypto.php'; require __DIR__.'/license.php'; require __DIR__.'/auth.php';
require __DIR__.'/mailer.php'; require __DIR__.'/layout.php';
