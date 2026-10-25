<?php
declare(strict_types=1);
// Login principal remains in SESSION.uid; employment identity is request-local only.
function identity_status32(array $actor):array{
 $full=!empty($actor['full_supervisor31'])||$actor['role']==='admin';
 $raw=query('SELECT id,role FROM ep_users WHERE id=?',[$actor['id']])->fetch();
 $id=$raw&&$raw['role']!=='admin'?(int)$actor['id']:0;
 $employee=$id?(query("SELECT * FROM ep_users WHERE id=? AND role<>'admin' AND active=1",[$id])->fetch()?:null):null;
 $workspace=$full||permission_has_team29($actor);
 $requested=$_SESSION['identity_mode32']??($full?'team':($_SESSION['access_mode29']??'personal'));
 if($requested==='admin')$requested=$full?'team':($_SESSION['access_mode29']??'personal');
 $mode=$workspace&&($requested!=='personal'||!$employee)?'team':'personal';
 $effective=$mode==='personal'&&$employee?(int)$employee['id']:(int)$actor['id'];
 $context=hash('sha256',jencode([(int)$actor['id'],$effective,$mode,(int)($_SESSION['identity_epoch32']??0),'personal-permissions-v34',$full,(int)(groups_config29()['revision']??0)]));
 return ['supervisor'=>$full,'has_workspace'=>$workspace,'has_employee'=>(bool)$employee,'employee_id'=>$employee?(int)$employee['id']:null,'employee_name'=>$employee['name']??null,'mode'=>$mode,'auth_id'=>(int)$actor['id'],'context'=>$context,'_employee'=>$employee];
}
function identity_project32(array $actor):array{
 unset($GLOBALS['EP_EFFECTIVE_EMPLOYEE32']);
 $status=identity_status32($actor);$GLOBALS['EP_IDENTITY32']=array_diff_key($status,['_employee'=>true]);
 $_SESSION['access_mode29']=$status['mode'];
 if($status['mode']!=='personal')return $actor;
 $employee=$status['_employee'];if(!$employee)fail('ملف الموظف غير متاح.',403);
 $employee['role']='employee';$employee['_personal_identity32']=true;
 $profile=decode($employee['profile_json']);$profile['must_change_password']=!empty(decode($actor['profile_json'])['must_change_password']);$employee['profile_json']=jencode($profile);
 $GLOBALS['EP_EFFECTIVE_EMPLOYEE32']=(int)$employee['id'];return $employee;
}
function identity_guard32():void{
 $sent=(string)($_SERVER['HTTP_X_EP_CONTEXT32']??'');
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'&&$sent==='')return;
 $expected=(string)($GLOBALS['EP_IDENTITY32']['context']??'');
 if($expected===''||!hash_equals($expected,$sent))fail('تغير وضع الحساب؛ أعد تحميل الصفحة قبل تنفيذ العملية.',419);
}
function identity_action32(string $action,array $actor,array $b):array{
 $status=identity_status32($actor);
 if($action==='identity32_mode'){
  
  $mode=(string)($b['mode']??'');if(!in_array($mode,['team','personal'],true)||($mode==='personal'&&!$status['has_employee'])||($mode==='team'&&!$status['has_workspace']))fail('عرض غير مسموح',403);
  if(!empty(decode($actor['profile_json'])['must_change_password']))fail('غيّر كلمة المرور المؤقتة أولًا.',428);
  $_SESSION['identity_mode32']=$mode;$_SESSION['access_mode29']=$mode;$_SESSION['identity_epoch32']=(int)($_SESSION['identity_epoch32']??0)+1;$_SESSION['company_filter30']='';unset($_SESSION['capture_ticket'],$_SESSION['video_ticket']);
  audit('identity_mode','user:'.$actor['id'],['mode'=>$mode,'employee_id'=>$status['employee_id']]);return ['ok'=>true];
 }
 fail('طلب غير صالح',404);
}
