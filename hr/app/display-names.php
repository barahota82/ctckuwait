<?php
declare(strict_types=1);
function display_names143(string $action,mixed $result):mixed {
 $reads=['bootstrap','users','user_choices29','policy_staff_list','policy_editor_context','policy_manage_preview','dashboard','list','report','attendance_today200','policies','workforce_list','commission_info','allowance_info','leave_overview','sick_overview','locations','audit','payroll_list','bank_list','v26_info','v28_info','v26_audit','v26_historical','v26_payments','v26_report','v26_report_detail','v26_video_list','v26_video_detail','pay35_info','pay37_sheet','pay37_vouchers','sales36_report','finance103_info','finance103_day','payroll_entry127','incentive114_info','profile29','groups29_list','permissions_list','customer_departments','customer_case_detail'];
 if(!in_array($action,$reads,true)||!is_array($result))return $result;
 $direct=in_array($action,['users','user_choices29','policy_staff_list','leave_overview','sick_overview','locations'],true);$needed=[];
 $identity=function(array $row,string $key,bool $root)use($action,$direct):?array {
  $name=$row['employee_name']??$row['actor_name']??$row['name']??null;
  $id=(int)($row['user_id']??0);
  if(!$id&&(($root&&$direct)||in_array($key,['staff','people','assignees','user'],true)||isset($row['profile'],$row['role'])))$id=(int)($row['id']??0);
  if($action==='bank_list'&&$root&&isset($row['snapshot_json']))$name=decode($row['snapshot_json'])['employee_name']??$name;
  if(!$id||!is_string($name)||trim($name)==='')return null;
  $profile=is_array($row['profile']??null)?$row['profile']:[];
  $en=$row['employee_name_en']??$row['name_en']??$profile['name_en']??null;
  return ['id'=>$id,'ar'=>$name,'en'=>$en];
 };
 $collect=function(mixed $node,string $key='',bool $root=false)use(&$collect,$identity,&$needed):void {
  if(!is_array($node))return;if(array_is_list($node)){foreach($node as $r)$collect($r,$key,$root);return;}
  $person=$identity($node,$key,$root);if($person&&$person['en']===null)$needed[$person['id']]=true;
  foreach($node as $k=>$child)if(is_array($child)&&$k!=='profile')$collect($child,(string)$k,false);
 };
 $collect($result,'',true);$english=[];
 foreach(array_chunk(array_keys($needed),500)as $ids){$marks=implode(',',array_fill(0,count($ids),'?'));foreach(query("SELECT id,profile_json FROM ep_users WHERE id IN ($marks)",$ids)->fetchAll()as $row)$english[(int)$row['id']]=trim((string)(decode($row['profile_json'])['name_en']??''));}
 $bind=function(mixed $node,string $key='',bool $root=false)use(&$bind,$identity,$english):mixed {
  if(!is_array($node))return $node;if(array_is_list($node))return array_map(fn($row)=>$bind($row,$key,$root),$node);
  $person=$identity($node,$key,$root);if($person){$person['en']=trim((string)($person['en']??$english[$person['id']]??''));$node['employee_locale143']=$person;}
  foreach($node as $k=>$child)if(is_array($child)&&!in_array($k,['profile','employee_locale143'],true))$node[$k]=$bind($child,(string)$k,false);
  return $node;
 };
 return $bind($result,'',true);
}
