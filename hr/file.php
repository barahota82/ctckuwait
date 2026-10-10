<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
require __DIR__.'/app/cms.php';
try{
 if(!secure_request())fail('استخدم HTTPS.',403);
 session_init();$id=(int)($_GET['id']??0);$cfg=settings();
 $file=query('SELECT * FROM ep_files WHERE id=?',[$id])->fetch();if(!$file)fail('الملف غير موجود.',404);
 $logo=$id===(int)($cfg['logo_id']??0);
 if(!$logo&&!cms_image_public($id)){
  $u=current_user();$allowed=$u['role']==='admin';
  $r=query('SELECT user_id,status,module FROM ep_records WHERE file_id=?',[$id])->fetch();
  if($r){$GLOBALS['EP_SCREEN']=$r['module'];$allowed=$allowed||can_view($u,(int)$r['user_id']);if((int)$r['user_id']===(int)$u['id']&&$u['role']!=='admin'&&in_array($r['module'],['discipline','adjustment'],true)&&$r['status']!=='approved')$allowed=false;}
  else{$event=query('SELECT user_id FROM ep_events WHERE photo_id=? OR evidence_id=?',[$id,$id])->fetch();$GLOBALS['EP_SCREEN']='report';$allowed=$allowed||($event&&can_view($u,(int)$event['user_id']))||((int)$file['user_id']===(int)$u['id']);}
  if(!$allowed)fail('ليس لديك صلاحية مشاهدة الملف.',403);
 }
 $path=EP_ROOT.'/private/files/'.$file['disk_name'];if(!is_file($path))fail('الملف غير متاح.',404);
 header('Content-Type: '.$file['mime']);header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
 header("Content-Security-Policy: default-src 'none'; sandbox");
 header('Content-Disposition: inline; filename="attachment'.($file['mime']==='application/pdf'?'.pdf':'.jpg').'"');
 readfile($path);
}catch(Throwable $e){if(http_response_code()<400)http_response_code(403);echo 'الملف غير متاح أو لا تملك صلاحية عرضه.';}
