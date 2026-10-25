<?php
declare(strict_types=1);
function setting31(string $key,mixed $fallback=[]):mixed{
 $raw=query('SELECT value_json FROM ep_settings WHERE setting_key=?',[$key])->fetchColumn();return $raw===false?$fallback:json_decode($raw,true,512,JSON_THROW_ON_ERROR);
}
function put_setting31(string $key,mixed $value):void{
 query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',[$key,jencode($value)]);
}
function owner_id31():int{return (int)setting31('owner31',0)?: (int)query("SELECT TOP (1) id FROM ep_users WHERE role='admin' ORDER BY id")->fetchColumn();}
function owner_only31(array $u):void{if((int)$u['id']!==owner_id31()||$u['role']!=='admin')fail('هذا الإجراء لمالك النظام فقط.',403);}
function supervisor_ids31():array{return array_map('intval',setting31('supervisors31',[]));}
function supervisor_recipients32():array {
 $ids=supervisor_ids31();$owner=owner_id31();return array_values(array_filter(query('SELECT id,role FROM ep_users WHERE active=1')->fetchAll(),fn($r)=>$r['role']==='admin'||(int)$r['id']===$owner||in_array((int)$r['id'],$ids,true)));
}
function supervisor_project31(array $u):array{
 $u['is_owner31']=(int)$u['id']===owner_id31();$u['full_supervisor31']=$u['is_owner31']||$u['role']==='admin'||in_array((int)$u['id'],supervisor_ids31(),true);
 // Authority is projected on this authenticated request, never on the employment record.
 if($u['full_supervisor31'])$u['role']='admin';elseif(function_exists('delegation_is_substitute199')&&delegation_is_substitute199((int)$u['id']))$u['role']='manager';return $u;
}
function site_v31_ready():bool{return site_v30_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v31'")->fetch();}
function migrate31():void{
 if(site_v31_ready())return;
 $owner=owner_id31();if(!$owner)fail('تعذر تحديد مالك النظام.');put_setting31('owner31',$owner);
 if(setting31('app_name31',null)===null)put_setting31('app_name31','Click');
 translation_seed31();put_setting31('upgrade_v31',true);
}
function control_action31(string $action,array $u,array $b):array{
 admin_only($u);
 if($action==='control31_supervisors'){
  $ids=supervisor_ids31();$rows=query('SELECT id,name,username,role,active,profile_json FROM ep_users ORDER BY id')->fetchAll();usort($rows,fn($a,$b)=>(($a['role']==='admin'?0:1)<=>($b['role']==='admin'?0:1))?:strnatcasecmp(employee_key($a)?:$a['username'],employee_key($b)?:$b['username']));
  return ['revision'=>setting31('supervisor_revision31',0),'users'=>array_map(fn($r)=>['id'=>(int)$r['id'],'name'=>$r['name'],'username'=>$r['username'],'employee_code'=>employee_key($r),'active'=>(bool)$r['active'],'owner'=>(int)$r['id']===owner_id31(),'enabled'=>$r['role']==='admin'||in_array((int)$r['id'],$ids,true)],$rows)];
 }
 if($action==='control31_supervisor_save'){
  $id=(int)($b['user_id']??0);if($id===owner_id31())fail('لا يمكن إلغاء صلاحيات مالك النظام.');
  db()->beginTransaction();query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='owner31'")->fetch();
  $revision=(int)setting31('supervisor_revision31',0);if((int)($b['revision']??-1)!==$revision)fail('تغيرت الصلاحيات؛ أعد تحميل الشاشة قبل الحفظ',409);
  $staff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();if(!$staff)fail('المستخدم غير موجود.');if($staff['role']==='admin')fail('هذا حساب إدارة مستقل؛ لا يمكن تحويله إلى موظف.');if(!empty($b['enabled'])&&!$staff['active']){if(empty($b['activate']))fail('فعّل حساب المستخدم قبل تعيينه مشرفًا.');query('UPDATE ep_users SET active=1 WHERE id=?',[$id]);audit('account_activation','user:'.$id,['before'=>false,'after'=>true]);}
  $before=supervisor_ids31();$ids=array_values(array_diff($before,[$id]));if(!empty($b['enabled']))$ids[]=$id;
  put_setting31('supervisors31',$ids);put_setting31('supervisor_revision31',$revision+1);audit('supervisor_assignment','user:'.$id,['before'=>in_array($id,$before,true),'after'=>in_array($id,$ids,true)]);db()->commit();return ['ok'=>true,'enabled'=>in_array($id,supervisor_ids31(),true),'active'=>(bool)query('SELECT active FROM ep_users WHERE id=?',[$id])->fetchColumn(),'revision'=>$revision+1];
 }
 if($action==='control31_activate_all'){
  $ids=array_values(array_unique(array_map('intval',$b['ids']??[])));sort($ids);if(!$ids)fail('لا توجد حسابات للتفعيل.');
  db()->beginTransaction();query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='owner31'")->fetch();
  $current=array_map('intval',query("SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE role<>'admin' AND active=0 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));sort($current);
  if($current!==$ids)fail('تغيرت حالة الحسابات؛ أعد فتح النافذة.',409);
  foreach($ids as $id){query('UPDATE ep_users SET active=1 WHERE id=?',[$id]);audit('account_activation','user:'.$id,['before'=>false,'after'=>true,'batch'=>true]);}
  db()->commit();return ['ok'=>true,'count'=>count($ids)];
 }
 if($action==='control31_reset'){
  $id=(int)($b['user_id']??0);if($id===(int)$u['id'])fail('استخدم تغيير كلمة المرور لحسابك.');
  db()->beginTransaction();$staff=query('SELECT * FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$staff)fail('المستخدم غير موجود.');
  if($id===owner_id31()||$staff['role']==='admin'||in_array($id,supervisor_ids31(),true))owner_only31($u);
  $password='Aa7!'.bin2hex(random_bytes(6));$profile=decode($staff['profile_json']);$profile['must_change_password']=true;
  query('UPDATE ep_users SET password_hash=?,profile_json=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),jencode($profile),$id]);
  audit('password_reset','user:'.$id,['temporary'=>true,'must_change'=>true]);db()->commit();return ['password'=>$password,'login'=>$staff['role']==='admin'?$staff['username']:employee_key($staff)];
 }
 fail('عملية غير موجودة',404);
}

function request_notice_recipients32(int $target,?string $screen=null):array {
 $ids=array_map('intval',array_column(supervisor_recipients32(),'id'));
 $manager=query('SELECT m.id FROM ep_users u JOIN ep_users m ON m.id=u.manager_id WHERE u.id=? AND m.active=1',[$target])->fetchColumn();
 $candidates=$manager?[(int)$manager]:[];
 foreach(groups_config29()['groups']??[] as $group)if(!empty($group['manual_team'])&&($group['active']??true)&&in_array($target,array_map('intval',$group['team_users']??[]),true))$candidates[]=(int)($group['manager_user_id']??0);
 $old=$_SESSION['access_mode29']??null;$filter=$_SESSION['company_filter30']??null;$_SESSION['access_mode29']='team';$_SESSION['company_filter30']='';
 try{foreach(array_unique($candidates)as $id){$person=query('SELECT * FROM ep_users WHERE id=? AND active=1',[$id])->fetch();if(!$person)continue;if($screen!==null&&(!permission_has($person,$screen,'review')||!in_array($target,permission_scope29($person,$screen,'review'),true)))continue;$ids[]=$id;}}
 finally{if($old===null)unset($_SESSION['access_mode29']);else $_SESSION['access_mode29']=$old;if($filter===null)unset($_SESSION['company_filter30']);else $_SESSION['company_filter30']=$filter;}
 return array_values(array_unique($ids));
}
