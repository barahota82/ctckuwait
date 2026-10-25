<?php
declare(strict_types=1);

// Decisions are stored beside the original record. An approval never deletes its predecessor.
function decision_module199(string $module):bool{return isset(modules()[$module])||$module==='resumption';}
function decision_employee_module199(string $module):bool{return $module==='resumption'||!empty(modules()[$module]['employee']);}
function actual_manager199(int $id):bool {
 $raw=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();
 return $raw&&($raw['role']==='manager'||$raw['role']==='admin'||(!empty($GLOBALS['EP_IDENTITY32']['has_workspace'])&&(int)($GLOBALS['EP_IDENTITY32']['auth_id']??0)===$id));
}
function workflow_new199(array $actor,int $target,string $module,array $body):array {
 $personal=personal_mode34($actor);$manager=actual_manager199((int)$actor['id']);
 $two=$module==='leave'||($module==='request'&&($body['record_category49']??'')==='assets');
 $direct=($personal&&$manager)||$module==='loan'||($module==='request'&&($body['record_category49']??'')==='assets'&&$manager);
 $stage=$direct?'admin':($two?'manager':'legacy');$staff=employee_required($target);$mid=(int)($staff['manager_id']??0);
 if($stage==='manager'&&function_exists('delegation_for199')&&($assignment199=delegation_for199($mid))&&$assignment199['route']==='admin')$stage='admin';
 if($stage==='manager'&&(!function_exists('delegation_for199')||!delegation_for199($mid))&&(!$mid||$mid===$target||!query('SELECT id FROM ep_users WHERE id=? AND active=1',[$mid])->fetch()))fail('حدد جهة مراجعة الطلب عند عدم وجود مدير مباشر من شاشة تفويض المديرين.');
 return ['stage'=>$stage,'two_stage'=>$two&&!$direct,'manager_id'=>$mid,'submitted_by'=>(int)$actor['id'],'submitted_as_manager'=>$manager,'ever_approved'=>false];
}
function workflow_can_review199(array $actor,array $r):bool {
 if(personal_mode34($actor)||(int)$actor['id']===(int)$r['user_id'])return false;
 $body=decode($r['body_json']);$flow=$body['_workflow199']??[];$stage=$flow['stage']??'legacy';
 if($stage==='admin')return $actor['role']==='admin';
 if($stage==='manager')return in_array((int)$actor['id'],function_exists('delegation_reviewers199')?delegation_reviewers199((int)($flow['manager_id']??0),(int)$r['user_id'],$r['module']):[(int)($flow['manager_id']??0)],true)&&permission_has($actor,$r['module'],'review')&&in_array((int)$r['user_id'],permission_scope29($actor,$r['module'],'review'),true);
 // Historical manager personal requests must not become subordinate-manager decisions.
 if(actual_manager199((int)$r['user_id'])&& decision_employee_module199($r['module']))return $actor['role']==='admin';
 if($r['module']==='loan')return $actor['role']==='admin';
 return $actor['role']==='admin'||(permission_has($actor,$r['module'],'review')&&in_array((int)$r['user_id'],permission_scope29($actor,$r['module'],'review'),true));
}
function workflow_targets199(array $r):array {
 $flow=decode($r['body_json'])['_workflow199']??[];
 if(($flow['stage']??'')==='manager')return function_exists('delegation_reviewers199')?delegation_reviewers199((int)$flow['manager_id'],(int)$r['user_id'],$r['module']):[(int)$flow['manager_id']];
 if(($flow['stage']??'')==='admin'||$r['module']==='loan'||actual_manager199((int)$r['user_id']))return array_map('intval',array_column(supervisor_recipients32(),'id'));
 return request_notice_recipients32((int)$r['user_id'],$r['module']);
}
function workflow_notify199(array $r,string $title):void {
 foreach(workflow_targets199($r)as $id)if($id!==(int)$r['user_id'])notice($id,$title,$r['title'],false,['screen'=>$r['module'],'record_id'=>(int)$r['id'],'employee_id'=>(int)$r['user_id']]);
}
function decision_snapshot199(array $r):array {
 $body=decode($r['body_json']);unset($body['_decisions199']);
 return ['status'=>$r['status'],'body'=>$body,'reviewed_by'=>$r['reviewed_by']??null,'review_note'=>$r['review_note']??null,'amount'=>$r['amount']??0];
}
function decision_push199(array &$body,array $before,array $actor,string $decision,string $note):void {
 $body['_decisions199'][]=['id'=>bin2hex(random_bytes(12)),'actor'=>(int)$actor['id'],'actor_name'=>$actor['name']??'','at'=>utc_now(),'decision'=>$decision,'note'=>$note,'before'=>decision_snapshot199($before),'withdrawn_at'=>null];
 if($decision==='approved')$body['_workflow199']['ever_approved']=true;
}
function workflow_review199(array $actor,array $r,array &$body,string &$status,string $note,?string $revision):bool {
 if($revision!==null&&!hash_equals(record_revision173($r),$revision))fail('تغير السجل؛ أعد فتحه قبل اتخاذ القرار.',409);
 if(!workflow_can_review199($actor,$r))fail('الطلب ليس في مرحلة مراجعتك أو خارج نطاقك.',403);
 if(in_array($status,['rejected','revision'],true)&&trim($note)==='')fail('سبب الرفض أو طلب التعديل مطلوب.');
 $decision=$status;decision_push199($body,$r,$actor,$decision,$note);
 if($decision==='approved'&&($body['_workflow199']['stage']??'')==='manager'&&!empty($body['_workflow199']['two_stage'])){
  $body['_workflow199']['stage']='admin';$status='pending';return true;
 }
 return false;
}
function decision_undo_index199(array $r,array $actor):?int {
 if(personal_mode34($actor)||(int)$r['user_id']===(int)$actor['id'])return null;
 if($actor['role']!=='admin'&&(!permission_has($actor,$r['module'],'review')||!in_array((int)$r['user_id'],permission_scope29($actor,$r['module'],'review'),true)))return null;
 $history=decode($r['body_json'])['_decisions199']??[];
 for($i=count($history)-1;$i>=0;$i--)if($history[$i]['decision']==='approved'&&empty($history[$i]['withdrawn_at'])&&empty($history[$i]['invalidated_at'])&&(int)$history[$i]['actor']===(int)$actor['id'])return $i;
 return null;
}
function decision_can_cancel199(array $r,array $actor):bool {
 if(!in_array($r['status'],['pending','revision'],true)||!decision_employee_module199($r['module']))return false;
 if($actor['role']==='admin'&&!personal_mode34($actor))return true;
 $b=decode($r['body_json']);return (int)$r['user_id']===(int)$actor['id']&&personal_mode34($actor)&&empty($b['_workflow199']['ever_approved'])&&personal_has34($actor,$r['module'],'create');
}
function decision_describe199(array $r,array $actor):array {
 if(!decision_module199($r['module']??''))return $r;
 $r['revision199']=$r['revision199']??record_revision173($r);$flow=decode($r['body_json'])['_workflow199']??[];
 $r['workflow199']=['stage'=>$flow['stage']??'legacy','can_review'=>workflow_can_review199($actor,$r),'can_undo'=>decision_undo_index199($r,$actor)!==null,'can_cancel'=>decision_can_cancel199($r,$actor)];
 $safe=decode($r['body_json']);unset($safe['_resumption_effects199']);foreach($safe['_decisions199']??[]as $i=>$event)unset($safe['_decisions199'][$i]['before']);$r['body_json']=jencode($safe);return $r;
}
function decision_action199(string $action,array $actor,array $b):array {
 $id=(int)($b['id']??0);$r=query('SELECT * FROM ep_records WHERE id=?',[$id])->fetch();if(!$r||!decision_module199($r['module']))fail('السجل غير موجود.',404);
 db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$r['user_id']]);$r=query('SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$id])->fetch();
 if(empty($b['revision199'])||!hash_equals(record_revision173($r),(string)$b['revision199']))fail('تغير السجل؛ أعد فتحه قبل اتخاذ القرار.',409);
 $body=decode($r['body_json']);$note=trim((string)($b['note']??''));$status=$r['status'];$reviewed=$r['reviewed_by']??null;$reviewNote=$r['review_note']??null;$amount=$r['amount'];
 if($action==='record_undo199'){
  $index=decision_undo_index199($r,$actor);if($index===null)fail('يمكن لصاحب الاعتماد فقط التراجع عن اعتماده.',403);if($note==='')fail('سبب التراجع عن الاعتماد مطلوب.');
  $history=$body['_decisions199'];$snapshot=$history[$index]['before'];if($r['module']==='resumption')resumption_undo_effects199($r,$body,$actor,$note);$history[$index]['withdrawn_at']=utc_now();$history[$index]['withdrawn_by']=(int)$actor['id'];$history[$index]['withdrawal_reason']=$note;
  for($i=$index+1;$i<count($history);$i++)if(empty($history[$i]['withdrawn_at'])&&empty($history[$i]['invalidated_at'])){$history[$i]['invalidated_at']=utc_now();$history[$i]['invalidated_by_decision']=$history[$index]['id'];}
  foreach(['_workflow199','_approved_days','_balance_at_approval','_excess_approved','expected_return','_sick_days','_approved_on','_valuation','_punch39','_resumption_effects199']as $field){if(array_key_exists($field,$snapshot['body']))$body[$field]=$snapshot['body'][$field];else unset($body[$field]);}
  if(isset($snapshot['body']['_text170']['review_note']))$body['_text170']['review_note']=$snapshot['body']['_text170']['review_note'];else unset($body['_text170']['review_note']);
  $body['_decisions199']=$history;$body['_workflow199']['ever_approved']=true;
  $status=$snapshot['status'];$reviewed=$snapshot['reviewed_by'];$reviewNote=$snapshot['review_note'];$amount=$snapshot['amount'];
  if(in_array($r['module'],['leave','sick','discipline','adjustment','leave_cash'],true)){
   $from=in_array($r['module'],['leave','sick'],true)?$r['date_from']:(($body['payroll_month']??'')?($body['payroll_month'].'-01'):$r['date_from']);if($from)financial_invalidate((int)$r['user_id'],$from);
  }
 }else{
  if(!decision_can_cancel199($r,$actor))fail('إلغاء الطلب بعد أول اعتماد للمشرف العام فقط.',403);
  $body['_cancellation199']=['at'=>utc_now(),'actor'=>(int)$actor['id'],'previous_status'=>$status];$status='cancelled';
 }
 query('UPDATE ep_records SET status=?,body_json=?,reviewed_by=?,review_note=?,amount=?,updated_at=? WHERE id=?',[$status,jencode($body),$reviewed,$reviewNote,$amount,utc_now(),$id]);
 $updated=array_replace($r,['status'=>$status,'body_json'=>jencode($body)]);audit($action,'record:'.$id,['before_status'=>$r['status'],'status'=>$status,'note'=>$note]);
 notice((int)$r['user_id'],$action==='record_undo199'?'تراجع عن الاعتماد':'إلغاء الطلب',$r['title'].($note!==''?' — '.$note:''),false,['screen'=>$r['module'],'record_id'=>$id]);
 if(in_array($status,['pending','revision'],true))workflow_notify199($updated,'طلب بانتظار المراجعة بعد التراجع');
 db()->commit();return ['ok'=>true,'id'=>$id];
}
