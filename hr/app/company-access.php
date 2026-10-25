<?php
declare(strict_types=1);
// Company limits belong to each grant; never combine companies from one role
// with actions or people scope from another role.
function site_v30_ready():bool{return $GLOBALS['READY30']??=site_v29_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v30'")->fetch();}
function access_migrate30():void{
 if(site_v30_ready())return;
 query("INSERT INTO ep_settings(setting_key,value_json) VALUES('upgrade_v30','true')");$GLOBALS['READY30']=true;
}
function company_codes30():array{return $GLOBALS['COMPANY_CODES30']??=array_map('strval',query('SELECT code FROM ep_companies ORDER BY code')->fetchAll(PDO::FETCH_COLUMN));}
function company_limit_clean30(mixed $mode,mixed $codes):array{
 if(!in_array($mode,['all','selected'],true)||!is_array($codes))fail('حدد نطاق الشركات بصورة صحيحة');
 $codes=array_values(array_unique(array_map('strval',$codes)));
 foreach($codes as $code)if(!in_array($code,company_codes30(),true))fail('شركة غير موجودة');
 if($mode==='selected'&&!$codes)fail('اختر شركة واحدة على الأقل');
 return ['company_mode'=>$mode,'companies'=>$mode==='all'?[]:$codes];
}
function staff_company30(array $staff):string{
 $state=$staff['service']??service_state($staff,local_time()->format('Y-m-d'));
 $profile=$staff['profile']??decode($staff['profile_json']??'{}');
 return (string)($state['company_code']??$profile['company_code']??'');
}
function rule_company30(array $rule,string $code,bool $filter=true):bool{
 if(($rule['company_mode']??'all')==='selected'&&!in_array($code,$rule['companies']??[],true))return false;
 $selected=$filter?(string)($_SESSION['company_filter30']??''):'';
 return $selected===''||$selected===$code;
}
function company_allowed30(array $u,string $screen,string $code,string $op='view',bool $filter=true):bool{
 if($u['role']==='admin')return true;
 if(($_SESSION['access_mode29']??'personal')!=='team'||permission_denied29($u,$screen,$op))return false;
 foreach(permission_rules29($u,$screen)as $r)if(in_array('view',$r['allow'],true)&&in_array($op,$r['allow'],true)&&rule_company30($r,$code,$filter))return true;
 return false;
}
function company_choices30(array $u,?string $screen=null,bool $filter=true):array{
 $rows=query('SELECT id,code,name,active FROM ep_companies ORDER BY code,id')->fetchAll();
 if($u['role']==='admin')return $rows;
 $screens=$screen===null?array_keys(permission_catalog()):[$screen];
 return array_values(array_filter($rows,function($c)use($u,$screens,$filter){foreach($screens as $s)if(company_allowed30($u,$s,(string)$c['code'],'view',$filter))return true;return false;}));
}
function record_company_allowed30(array $u,string $screen,array $row,string $op='view'):bool{
 if($u['role']==='admin')return true;$v=decode($row['body_json']??'{}');
 if(!company_allowed30($u,$screen,(string)($v['company_code']??''),$op))return false;
 $target=(int)($v['linked_user_id']??0);
 if($target)return in_array($target,permission_scope29($u,$screen,$op),true);
 // Unlinked records are company records. Limited staff scopes must not expose them.
 foreach(permission_rules29($u,$screen)as $r)if(($r['scope']??'team')==='all'&&in_array('view',$r['allow'],true)&&in_array($op,$r['allow'],true)&&rule_company30($r,(string)($v['company_code']??'')))return true;
 return false;
}
function access_request30(string $action,array $u,array $b):void{
 if($u['role']==='admin')return;
 // Member-only screens must not silently bypass grants through the generic dashboard route.
 if(in_array($action,['groups30_filter','groups29_list','groups29_save','groups29_member','groups30_meta','groups30_members'],true))return;
 if($action==='company_save'){
  $op=empty($b['id'])?'create':'edit';permission_require($u,'companies',$op);
  if(empty($b['id'])){foreach(permission_rules29($u,'companies')as $r)if(($r['company_mode']??'all')==='all'&&($r['scope']??'team')==='all'&&in_array('create',$r['allow'],true))return;fail('إنشاء شركة يتطلب السماح بكل الشركات',403);}
  $code=query('SELECT code FROM ep_companies WHERE id=?',[(int)$b['id']])->fetchColumn();
  if(!$code||!company_allowed30($u,'companies',(string)$code,'edit'))fail('الشركة خارج نطاق الصلاحية',403);
 }
 if(in_array($action,['workforce_save','workforce_terms'],true)||($action==='v26_save'&&($b['kind']??'')==='work_permit')){
  [$screen,$op]=permission_route29($action,$b);$v=$b['values']??[];if(is_string($v))$v=decode($v);
  if(!empty($v['company_code'])&&!company_allowed30($u,$screen,(string)$v['company_code'],$op))fail('الشركة المستهدفة خارج نطاق الصلاحية',403);
 }
 if($action==='v28_record_save'&&in_array($b['kind']??'',['company_file','corporate_document','attachment_archive'],true)){
  $screen=$b['kind'];permission_require($u,$screen,'create');$v=$b['values']??[];if(is_string($v))$v=decode($v);$v['linked_user_id']=(int)($b['user_id']??0);
  if(!record_company_allowed30($u,$screen,['body_json'=>jencode($v)],'create'))fail('المستند خارج نطاق الشركة أو الموظفين',403);
  if(!empty($b['previous_id'])){$r=query('SELECT * FROM ep_records WHERE id=? AND module=?',[(int)$b['previous_id'],$screen])->fetch();if(!$r||!record_company_allowed30($u,$screen,$r,'create'))fail('النسخة السابقة خارج النطاق',403);}
 }
}
function groups_action30(string $action,array $u,array $b):array{
 if($action==='groups30_filter'){
  $code=(string)($b['company']??'');if($code!==''&&!in_array($code,array_column(company_choices30($u,null,false),'code'),true))fail('الشركة خارج الصلاحيات',403);
  $_SESSION['company_filter30']=$code;return ['ok'=>true];
 }
 admin_only($u);db()->beginTransaction();
 // Read under the lock, not from the earlier bootstrap/settings request cache.
 $row=query("SELECT value_json FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key='access_groups29'")->fetch();
 $v=$row?decode($row['value_json']):['groups'=>[],'members'=>[],'exceptions'=>[]];
 if((int)($b['revision']??-1)!==(int)($v['revision']??0))fail('تغيرت الصلاحيات؛ أعد تحميل الشاشة قبل الحفظ',409);
 $before=$v;$id=(string)($b['id']??'');
 if($action==='groups30_meta'){
  if($id!==''&&!isset($v['groups'][$id]))fail('المجموعة غير موجودة');
  $name=trim((string)($b['name']??''));if($name===''||mb_strlen($name)>100)fail('اسم المجموعة مطلوب ولا يتجاوز 100 حرف');
  foreach($v['groups']as $key=>$g)if($key!==$id&&mb_strtolower($g['name'])===mb_strtolower($name))fail('اسم المجموعة مستخدم');
  if($id==='')$id=bin2hex(random_bytes(8));$g=$v['groups'][$id]??['grants'=>[],'company_mode'=>'selected','companies'=>[],'scope'=>'all','users'=>[],'manual_team'=>true,'manager_user_id'=>0,'team_users'=>[]];
  $g['name']=$name;$g['description']=mb_substr(trim((string)($b['description']??'')),0,500);$g['active']=!empty($b['active']);$v['groups'][$id]=$g;
 }elseif($action==='groups30_delete'){
  if(!isset($v['groups'][$id]))fail('المجموعة غير موجودة');
  if(!empty($v['groups'][$id]['manual_team'])&&(!empty($v['groups'][$id]['manager_user_id'])||!empty($v['groups'][$id]['team_users'])))fail('أزل مدير المجموعة وأعضاءها قبل الحذف.');foreach($v['members']??[] as $memberGroups)if(in_array($id,$memberGroups,true))fail('انقل أعضاء المجموعة أو أزل عضويتهم قبل الحذف.');
  if(!empty($v['groups'][$id]['personal_users']))fail('أزل أعضاء صلاحيات الملف الشخصي قبل حذف المجموعة.');
  unset($v['groups'][$id]);
 }elseif($action==='groups30_personal'){
  $clean=personal_clean34($b['grants']??[]);
  if($id==='__default34')$v['personal_default34']=$clean;
  else{if(!isset($v['groups'][$id]))fail('اختر مجموعة موجودة');$ids=array_values(array_unique(array_map('intval',$b['users']??[])));foreach($ids as $person)employee_required($person);$v['groups'][$id]['personal_grants']=$clean;$v['groups'][$id]['personal_users']=$ids;}
 }elseif($action==='groups30_permissions'){
  if(!isset($v['groups'][$id]))fail('اختر مجموعة موجودة');
  $limits=company_limit_clean30($b['company_mode']??'selected',$b['companies']??[]);
  $manual=!empty($v['groups'][$id]['manual_team']);$scope=$manual?'all':(string)($b['scope']??'team');if(!in_array($scope,['self','team','department','selected','all'],true))fail('نطاق موظفين غير صالح');
  $ids=array_values(array_unique(array_map('intval',$b['users']??[])));foreach($ids as $uid)employee_required($uid);if($scope==='selected'&&!$ids)fail('اختر الموظفين المحددين');
  $case=$b['grants']['customer_cases']??[];if(!empty($case['allow'])&&(($case['company_mode']??'inherit')==='inherit'?$limits['company_mode']:$case['company_mode'])==='selected')fail('حالات العملاء مرتبطة بأقسام خدمة العملاء؛ لا تدعم فصل الشركات حاليًا.');
  $grants=grants_clean29($b['grants']??[]);if($manual)foreach($grants as &$grant){$grant['scope']='inherit';$grant['users']=[];}unset($grant);$v['groups'][$id]=array_replace($v['groups'][$id],$limits,['scope'=>$scope,'users'=>$ids,'grants'=>$grants]);
 }elseif($action==='groups30_members'){
  if(!isset($v['groups'][$id]))fail('اختر مجموعة موجودة');
  $ids=array_values(array_unique(array_map('intval',$b['users']??[])));foreach($ids as $uid)employee_required($uid);
  $manual=!empty($b['manual_team'])||!empty($v['groups'][$id]['manual_team']);
  if($manual){$manager=(int)($b['manager_user_id']??0);if($manager)employee_required($manager);if($ids&&!$manager)fail('حدد مدير المجموعة قبل اختيار الأعضاء.');$ids=array_values(array_diff($ids,[$manager]));$v['groups'][$id]['manual_team']=true;$v['groups'][$id]['manager_user_id']=$manager;$v['groups'][$id]['team_users']=$ids;$v['groups'][$id]['scope']='all';$v['groups'][$id]['users']=[];foreach($v['groups'][$id]['grants']as &$grant){$grant['scope']='inherit';$grant['users']=[];}unset($grant);}
  foreach(query("SELECT id FROM ep_users WHERE role<>'admin'")->fetchAll(PDO::FETCH_COLUMN)as $uid){$key=(string)$uid;$current=array_values(array_diff($v['members'][$key]??[],[$id]));if(!$manual&&in_array((int)$uid,$ids,true))$current[]=$id;$v['members'][$key]=$current;}
 }else fail('عملية غير موجودة',404);
 $v['revision']=(int)($v['revision']??0)+1;groups_store29($v);
 foreach(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll()as $staff)query('UPDATE ep_users SET role=? WHERE id=?',[permission_has_team29($staff)?'manager':'employee',$staff['id']]);
 audit($action,'access_group:'.$id,['before'=>$before,'after'=>$v]);db()->commit();return ['ok'=>true,'id'=>$id,'revision'=>$v['revision']];
}
