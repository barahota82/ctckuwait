<?php
declare(strict_types=1);
function delegations199():array {
 return $GLOBALS['DELEGATIONS199']??=query("SELECT * FROM ep_records WHERE module='manager_delegation199' ORDER BY date_from DESC,id DESC")->fetchAll();
}
function delegation_active199(array $r,?string $date=null):bool {$date??=local_time()->format('Y-m-d');return $r['status']==='active'&&$r['date_from']<=$date&&(!$r['date_to']||$r['date_to']>=$date);}
function delegation_for199(int $manager):?array {
 foreach(delegations199()as $r)if((int)decode($r['body_json'])['manager_id']===$manager&&delegation_active199($r))return decode($r['body_json'])+['id'=>(int)$r['id']];return null;
}
function delegation_people199(int $manager,string $screen,string $op):array {
 if(!$manager)return array_map('intval',query("SELECT id FROM ep_users WHERE role<>'admin' AND active=1 AND (manager_id IS NULL OR manager_id=0)")->fetchAll(PDO::FETCH_COLUMN));
 $original=query('SELECT * FROM ep_users WHERE id=? AND active=1',[$manager])->fetch();if(!$original)return [];
 $guard=$GLOBALS['NO_DELEGATION199']??false;$GLOBALS['NO_DELEGATION199']=true;
 try{return array_values(array_diff(permission_scope29($original,$screen,$op),[$manager]));}finally{$GLOBALS['NO_DELEGATION199']=$guard;}
}
function delegation_rule199(array $actor,string $screen):array {
 if(!empty($GLOBALS['NO_DELEGATION199'])||!in_array($screen,['leave','sick','external','request','resumption'],true))return [];
 $ids=[];foreach(delegations199()as $r){$d=decode($r['body_json']);if(!delegation_active199($r)||($d['route']??'')!=='substitute'||(int)($d['substitute_id']??0)!==(int)$actor['id'])continue;$ids=array_merge($ids,delegation_people199((int)$d['manager_id'],$screen,'review'));}
 $ids=array_values(array_diff(array_unique($ids),[(int)$actor['id']]));return $ids?[['allow'=>['view','review'],'deny'=>[],'scope'=>'selected','users'=>$ids,'company_mode'=>'all','companies'=>[],'source'=>'تفويض مدير']]:[];
}
function delegation_is_substitute199(int $id):bool {
 foreach(delegations199()as $r){$d=decode($r['body_json']);if(delegation_active199($r)&&($d['route']??'')==='substitute'&&(int)($d['substitute_id']??0)===$id)return true;}return false;
}
function delegation_reviewers199(int $manager,int $target,string $screen):array {
 $ids=$manager?[$manager]:[];$d=delegation_for199($manager);if(!$d)return $ids;
 if($d['route']==='admin')return array_map('intval',array_column(supervisor_recipients32(),'id'));
 if(in_array($target,delegation_people199($manager,$screen,'review'),true))$ids[]=(int)$d['substitute_id'];
 return array_values(array_diff(array_unique($ids),[$target]));
}
function delegation_action199(string $action,array $actor,array $b):array {
 admin_only($actor);if(personal_mode34($actor))fail('هذه الشاشة للمشرف العام في مساحة العمل.',403);
 if($action==='delegation_list199'){
  $rows=delegations199();foreach($rows as &$r)$r['revision199']=record_revision173($r);unset($r);
  return ['records'=>$rows,'staff'=>array_map(fn($s)=>['id'=>(int)$s['id'],'name'=>$s['name'],'role'=>$s['role'],'manager_id'=>(int)$s['manager_id']],query("SELECT id,name,role,manager_id FROM ep_users WHERE role<>'admin' AND active=1 ORDER BY name")->fetchAll())];
 }
 db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) ORDER BY id');
 if($action==='delegation_end199'){
  $r=query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=? AND module='manager_delegation199'",[(int)($b['id']??0)])->fetch();if(!$r||$r['status']!=='active')fail('التكليف غير متاح.');
  if(!hash_equals(record_revision173($r),(string)($b['revision199']??'')))fail('تغير التكليف؛ أعد فتحه.',409);
  $to=valid_date($b['date_to']??null);if(!$to||$to<$r['date_from'])fail('حدد تاريخ انتهاء لا يسبق بداية التكليف.');$body=decode($r['body_json']);$body['end_history'][]=['previous_date'=>$r['date_to'],'date'=>$to,'actor'=>(int)$actor['id'],'at'=>utc_now()];
  query('UPDATE ep_records SET date_to=?,body_json=?,updated_at=? WHERE id=?',[$to,jencode($body),utc_now(),$r['id']]);$id=(int)$r['id'];audit('delegation_end','record:'.$id,['date_to'=>$to]);
 }else{
  $manager=(int)($b['manager_id']??0);$route=$b['route']??'';$sub=(int)($b['substitute_id']??0);$from=valid_date($b['date_from']??null);if(!$from||!in_array($route,['admin','substitute'],true))fail('حدد جهة التحويل وتاريخ البداية.');
  if($manager){$m=employee_required($manager);if(!$m['active']||($m['role']!=='manager'&&!permission_has_team29($m)))fail('اختر مديرًا أصليًا متاحًا.');}
  if($route==='substitute'){$person=employee_required($sub);if(!$person['active']||$sub===$manager)fail('اختر موظفًا بديلًا مختلفًا عن المدير الأصلي.');}else $sub=0;
  foreach(query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE module='manager_delegation199' AND status='active'")->fetchAll()as $prior)if((int)decode($prior['body_json'])['manager_id']===$manager&&(!$prior['date_to']||$prior['date_to']>=$from))fail('يوجد تكليف متداخل؛ سجّل تاريخ انتهائه أولًا.');
  $body=['manager_id'=>$manager,'route'=>$route,'substitute_id'=>$sub,'created_by'=>(int)$actor['id'],'created_at'=>utc_now(),'original_manager_retains_authority'=>true];$id=v26_insert('manager_delegation199',$manager?:$actor['id'],$from,null,$body,'active');audit('delegation_create','record:'.$id,$body);
  if($sub)notice($sub,'تكليف مدير بديل','بداية التكليف '.$from.'؛ يحتفظ المدير الأساسي بصلاحياته.',false,['screen'=>'leave','delegation_id199'=>$id]);
 }
 unset($GLOBALS['DELEGATIONS199']);db()->commit();return ['ok'=>true,'id'=>$id];
}

function permission_has_base_team199(array $u):bool {$guard=$GLOBALS['NO_DELEGATION199']??false;$GLOBALS['NO_DELEGATION199']=true;try{return permission_has_team29($u);}finally{$GLOBALS['NO_DELEGATION199']=$guard;}}
