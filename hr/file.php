<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
require __DIR__.'/app/cms.php';
try{
 if(!secure_request())fail('استخدم HTTPS.',403);
 session_cache_limiter('');session_init();$id=(int)($_GET['id']??0);$cfg=settings();
 $file=query('SELECT * FROM ep_files WHERE id=?',[$id])->fetch();if(!$file)fail('الملف غير موجود.',404);
 $logo=$id===(int)($cfg['logo_id']??0);
 if(!$logo&&!cms_image_public($id)){
  $u=current_user();if(!empty(decode($u['profile_json'])['must_change_password']))fail('غيّر كلمة المرور المؤقتة أولًا.',428);$allowed=$u['role']==='admin';
  if(personal_mode34($u))personal_file34($u,$id);
  $isNotice=(bool)query("SELECT TOP (1) id FROM ep_records WHERE module='notification_meta' AND file_id=?",[$id])->fetch();
  if($isNotice){$allowed=$allowed||(bool)query("SELECT TOP (1) id FROM ep_records WHERE module='notification_meta' AND file_id=? AND (user_id=? OR reviewed_by=?)",[$id,$u['id'],$u['id']])->fetch();if(!$allowed)fail('ليس لديك صلاحية مشاهدة الملف.',403);}
  else {
  $r=query('SELECT user_id,status,module FROM ep_records WHERE file_id=?',[$id])->fetch();
  if($r&&$r['module']==='video_receipt'){if($r['status']==='draft'&&$u['role']!=='admin')fail('الفيديو غير متاح.',403);if(($_GET['download']??'')==='1'&&($u['role']!=='admin'||empty($u['full_supervisor31'])))fail('تنزيل الفيديو للمشرف العام فقط.',403);}
  if($r){$GLOBALS['EP_SCREEN']=$r['module'];if(in_array($r['module'],['company_file','corporate_document','attachment_archive'],true)&&$u['role']==='manager'&&permission_has($u,$r['module'])){$v=decode(query('SELECT body_json FROM ep_records WHERE file_id=?',[$id])->fetchColumn());$allowed=record_company_allowed30($u,$r['module'],['body_json'=>jencode($v)]);}$allowed=$allowed||($r['module']==='video_receipt'?(int)$r['user_id']===(int)$u['id']:can_view($u,(int)$r['user_id']));if((int)$r['user_id']===(int)$u['id']&&$u['role']!=='admin'&&in_array($r['module'],['discipline','adjustment'],true)&&$r['status']!=='approved')$allowed=false;}
  else{$event=query('SELECT user_id FROM ep_events WHERE photo_id=? OR evidence_id=?',[$id,$id])->fetch();$GLOBALS['EP_SCREEN']='report';$allowed=$allowed||($event&&can_view($u,(int)$event['user_id']))||((int)$file['user_id']===(int)$u['id']);}
  if($r&&$u['role']!=='admin'&&in_array($r['module'],['company_file','corporate_document','attachment_archive'],true))$allowed=record_company_allowed30($u,$r['module'],['body_json'=>query('SELECT body_json FROM ep_records WHERE file_id=?',[$id])->fetchColumn()]);
  if(!(($_SESSION['access_mode29']??'personal')==='personal'&&((int)($r['user_id']??0)===(int)$u['id']||(int)$file['user_id']===(int)$u['id']))&&permission_denied29($u,$GLOBALS['EP_SCREEN']??'document'))$allowed=false;
  if(!$allowed)fail('ليس لديك صلاحية مشاهدة الملف.',403);
  }
 }
 $path=EP_ROOT.'/private/files/'.$file['disk_name'];if(!is_file($path))fail('الملف غير متاح.',404);
 if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
 if($logo&&($_GET['variant']??'')==='brand32'){require_once __DIR__.'/app/brand-image.php';[$path,$file['mime']]=brand_display_file($path,$file['mime']);}
 if($logo){$etag='"'.hash_file('sha256',$path).'"';header('ETag: '.$etag);header('Cache-Control: public, max-age=86400, immutable');if(trim($_SERVER['HTTP_IF_NONE_MATCH']??'')===$etag){http_response_code(304);exit;}}
 header('Content-Length: '.filesize($path));
 header('Content-Type: '.$file['mime']);header('X-Content-Type-Options: nosniff');header($logo?'Cache-Control: public, max-age=86400, immutable':'Cache-Control: private, no-store');
 header("Content-Security-Policy: default-src 'none'; sandbox");
 if(($_GET['download']??'')==='1')header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($file['original_name']));
 elseif(!in_array($file['mime'],['image/jpeg','image/png','image/webp','application/pdf','video/mp4','video/webm','video/quicktime'],true))header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($file['original_name']));
 elseif(in_array($file['mime'],['image/jpeg','image/png','image/webp','application/pdf','video/mp4','video/webm','video/quicktime'],true))header('Content-Disposition: inline; filename="attachment'.($file['mime']==='application/pdf'?'.pdf':($file['mime']==='video/mp4'?'.mp4':($file['mime']==='video/webm'?'.webm':'.jpg'))).'"');
 readfile($path);
}catch(Throwable $e){if(http_response_code()<400)http_response_code(403);echo 'الملف غير متاح أو لا تملك صلاحية عرضه.';}
