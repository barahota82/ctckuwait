<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$authorization=(string)($_SERVER['HTTP_AUTHORIZATION']??'');
if($authorization===''){echo "OK\n";exit;} // PHP liveness only; no database inspection.
require __DIR__.'/hr/app/core.php';
if(!secure_request()){http_response_code(403);exit;}
try{
 $key=(string)(environment_values()['HEALTH_CHECK_KEY']??'');
 if(strlen($key)<32||!hash_equals('Bearer '.$key,$authorization)){http_response_code(404);exit;}
 if(!app_installed()){http_response_code(503);echo "NOT READY\n";exit;}
 $statement=query('SELECT 1');$ok=(int)$statement->fetchColumn()===1;$statement->closeCursor();
 http_response_code($ok?200:503);echo $ok?"READY\n":"NOT READY\n";
}catch(Throwable $e){http_response_code(503);echo "NOT READY\n";}
