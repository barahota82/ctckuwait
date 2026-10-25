<?php
declare(strict_types=1);

// Called after the original admin-only debt authorization. Select employee pages
// before reading movements; each chosen ledger still includes its full history.
function debt_workspace196():array {
 $paging=page_request195();$staff=query("SELECT * FROM ep_users WHERE role<>'admin' ORDER BY id")->fetchAll();
 $people=array_map(fn($s)=>['user_id'=>(int)$s['id'],'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json']??'{}')['name_en']??''],$staff);
 usort($people,fn($a,$b)=>strnatcasecmp($a['code'],$b['code'])?:($a['user_id']<=>$b['user_id']));
 $companies=query('SELECT code,name FROM ep_companies ORDER BY code')->fetchAll();
 if(!$paging)return ['companies'=>$companies,'debt_ledger'=>array_map(fn($p)=>v28_debt_ledger($p['user_id'],true)+['name_en'=>$p['name_en']],$people)];
 $employee=(string)($paging['filters']['employee']??'');$selected=array_values(array_filter($people,fn($p)=>!$employee||$employee==='0'||(string)$p['user_id']===$employee));
 $query=record_search_normalize32((string)($paging['filters']['query']??''));
 if($query===''){
  $window=page_rows195($selected,$paging,count($people));
  $ledgers=array_map(fn($p)=>v28_debt_ledger($p['user_id'],true)+['name_en'=>$p['name_en']],$window['rows195']);
 }else{
  // Text may refer to a movement or its reference. Keep that complete search.
  $ledgers=array_map(fn($p)=>v28_debt_ledger($p['user_id'],true)+['name_en'=>$p['name_en']],$selected);
  $window=page_rows195(array_values(array_filter($ledgers,fn($r)=>page_matches195($r,$paging['filters']))),$paging,count($people));$ledgers=$window['rows195'];
 }
 return ['companies'=>$companies,'people195'=>$people,'debt_ledger'=>$ledgers,'page195'=>$window['page195']];
}

function permission_people196(array $actor):array {
 if(personal_mode34($actor))$staff=[employee_required((int)$actor['id'])];
 else{
  if($actor['role']!=='admin'&&!permission_has($actor,'request','review'))return [];
  $allowed=$actor['role']==='admin'?null:array_fill_keys(permission_scope29($actor,'request','review'),true);
  $staff=array_values(array_filter(query("SELECT * FROM ep_users WHERE role<>'admin' ORDER BY id")->fetchAll(),fn($s)=>(int)$s['id']!==(int)$actor['id']&&($allowed===null||(in_array((int)$actor['id'],function_exists('delegation_reviewers199')?delegation_reviewers199((int)$s['manager_id'],(int)$s['id'],'request'):[(int)$s['manager_id']],true)&&isset($allowed[(int)$s['id']])))));
 }
 return array_map(fn($s)=>['id'=>(int)$s['id'],'name'=>$s['name'],'name_en'=>decode($s['profile_json']??'{}')['name_en']??'','employee_code'=>employee_key($s)],$staff);
}
function permission_list196(array $actor):array {
 $people=permission_people196($actor);$ids=array_column($people,'id');$paging=page_request195();$filters=$paging['filters']??[];
 $staff=[];foreach($people as $person)$staff[$person['id']]=$person;
 $rows=$ids?query("SELECT * FROM ep_records WHERE module='exit_permission194' AND user_id IN (".implode(',',array_map('intval',$ids)).") ORDER BY id DESC")->fetchAll():[];
 $total=count($rows);$q=record_search_normalize32((string)($filters['query']??''));$employee=(string)($filters['employee']??'');$status=(string)($filters['status']??'');$reminder=!empty($_GET['reminder196']);
 $rows=array_values(array_filter($rows,function($r)use($staff,$q,$employee,$status,$reminder){
  if($employee&&$employee!=='0'&&(string)$r['user_id']!==$employee)return false;
  $p=decode($r['body_json']);$state=permission_state194($p);
  if($status&&$status!=='all'&&$status!==$state)return false;
  if($reminder&&!in_array($state,['approved','open','overdue','review'],true))return false;
  return page_query195([$staff[(int)$r['user_id']],$p['reason']??'',$p['starts_at']??'',$p['ends_at']??''],$q);
 }));
 if($paging){$window=page_rows195($rows,$paging,$total);$rows=$window['rows195'];}
 $described=array_map(fn($r)=>permission_describe194($r,$actor),$rows);
 return $paging?['rows195'=>$described,'people195'=>$people,'page195'=>$window['page195']]:$described;
}
