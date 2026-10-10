<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';require __DIR__.'/app/backups.php';
try {
 if(!secure_request()||!app_installed())fail('الطلب غير متاح.',403);
 session_init();$u=current_user();admin_only($u);$id=(string)($_GET['id']??'');
 if(!preg_match('/^[a-f0-9]{32}$/D',$id))fail('النسخة غير موجودة.',404);
 $file=backup_dir().'/'.$id.'.epbackup';$meta=backup_dir().'/'.$id.'.meta.json';if(!is_file($file)||!is_file($meta))fail('النسخة غير موجودة.',404);
 audit('backup_download','backup:'.$id);session_write_close();
 header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="company-'.$id.'.epbackup"');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($file));readfile($file);
}catch(Throwable $e){security_log($e,'EP backup download');if(http_response_code()<400)http_response_code(500);header('Content-Type: text/plain; charset=utf-8');echo $e instanceof RuntimeException?$e->getMessage():'تعذر تنزيل النسخة.';}
