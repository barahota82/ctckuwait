<?php
declare(strict_types=1);

function resumption_undo_effects199(array $r,array $body,array $actor,string $reason):void {
 $effect=$body['_resumption_effects199']??null;if(!$effect)fail('لا توجد نسخة موثقة للحالة السابقة لهذا الاعتماد.');
 financial_invalidate((int)$r['user_id'],$effect['from']);
 $linked=query('SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$effect['record_id']])->fetch();if(!$linked)fail('السجل المرتبط بالمباشرة غير موجود.');
 $current=decode($linked['body_json']);
 foreach($effect['after']as $key=>$value)if(($current[$key]??null)!==$value)fail('تغير أثر المباشرة بقرار لاحق؛ راجع السجل المرتبط قبل التراجع.',409);
 foreach($effect['before']as $key=>$prior){if($prior['exists'])$current[$key]=$prior['value'];else unset($current[$key]);}
 $from=$linked['date_from'];if(isset($effect['record_date_before'])){if($from!==$r['date_from'])fail('تغيرت بداية فترة الخدمة بعد الاعتماد.',409);$from=$effect['record_date_before'];}
 query('UPDATE ep_records SET date_from=?,body_json=?,updated_at=? WHERE id=?',[$from,jencode($current),utc_now(),$linked['id']]);
 if(isset($effect['hired_before'])){if(employee_required((int)$r['user_id'])['hired_on']!==$r['date_from'])fail('تغير تاريخ التعيين بعد المباشرة.',409);query('UPDATE ep_users SET hired_on=? WHERE id=?',[$effect['hired_before'],$r['user_id']]);}
 if(!empty($effect['salary_history_id'])){
  $h=query('SELECT * FROM ep_history WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$effect['salary_history_id']])->fetch();
  if(!$h||hash('sha256',$h['body_json'])!==$effect['salary_history_hash'])fail('تغير سجل بداية الراتب بعد المباشرة.',409);
  v26_insert('history_version',(int)$r['user_id'],local_time()->format('Y-m-d'),null,['history_id'=>$h['id'],'before'=>$h,'reason'=>$reason,'actor'=>(int)$actor['id'],'source'=>'resumption_undo199']);
  query('DELETE FROM ep_history WHERE id=?',[$h['id']]);
 }
 audit('resumption_effects_undo','record:'.$r['id'],['reason'=>$reason,'linked_record'=>$linked['id']]);
 unset($GLOBALS['EP_SERVICE_CACHE'][(int)$r['user_id']],$GLOBALS['EP_SALARY_CACHE'][employee_key(employee_required((int)$r['user_id']))]);
}
function resumption_effect199(array $record,array $before,array $after):array {
 $values=[];foreach($after as $key=>$value)$values[$key]=['exists'=>array_key_exists($key,$before),'value'=>$before[$key]??null];
 return ['record_id'=>(int)$record['id'],'from'=>$record['date_from'],'before'=>$values,'after'=>$after];
}

function financial_revision199(array $r):string{$v=[];foreach(['id','user_id','month_key','status','approved_by','approved_at','amount','net_amount','calculation_json','snapshot_json']as $k)$v[$k]=$r[$k]??null;return hash('sha256',jencode($v));}
function financial_approval_snapshot199(string $kind,array $row,array $actor):void {
 $historyId=v26_insert('approval_history199',(int)$row['user_id'],local_time()->format('Y-m-d'),null,['kind'=>$kind,'record_id'=>(int)$row['id'],'actor'=>(int)$actor['id'],'actor_name'=>$actor['name'],'at'=>utc_now(),'before'=>$row,'withdrawn'=>false]);query('UPDATE ep_records SET title=? WHERE id=?',[$kind.':'.$row['id'],$historyId]);unset($GLOBALS['APPROVAL_HISTORY199']);
}
function financial_approvals_prefetch199(string $kind,array $rows):void {
 $titles=[];foreach($rows as $row){$key=$kind.':'.$row['id'];$GLOBALS['APPROVAL_HISTORY199'][$key]=null;if($row['status']==='approved')$titles[]=$key;}
 foreach(array_chunk($titles,400)as $batch){$marks=implode(',',array_fill(0,count($batch),'?'));foreach(query("SELECT * FROM ep_records WHERE module='approval_history199' AND status='approved' AND title IN ($marks) ORDER BY id DESC",$batch)->fetchAll()as $r)if(empty($GLOBALS['APPROVAL_HISTORY199'][$r['title']]))$GLOBALS['APPROVAL_HISTORY199'][$r['title']]=$r;}
}
function financial_approval199(string $kind,int $id):?array {
 $key=$kind.':'.$id;if(!array_key_exists($key,$GLOBALS['APPROVAL_HISTORY199']??[]))$GLOBALS['APPROVAL_HISTORY199'][$key]=query("SELECT TOP (1) * FROM ep_records WHERE module='approval_history199' AND status='approved' AND title=? ORDER BY id DESC",[$key])->fetch()?:null;return $GLOBALS['APPROVAL_HISTORY199'][$key];
}
function financial_approval_describe199(string $kind,array $r,array $actor):array {
 if($actor['role']!=='admin'||personal_mode34($actor))return $r;
 $h=$r['status']==='approved'?financial_approval199($kind,(int)$r['id']):null;
 $r['approval199']=['can_undo'=>$h&&(int)decode($h['body_json'])['actor']===(int)$actor['id'],'revision'=>financial_revision199($r)];return $r;
}
function financial_approval_undo199(string $action,array $actor,array $b):array {
 admin_only($actor);if(personal_mode34($actor))fail('الإجراء متاح في مساحة العمل.',403);
 $kind=$action==='payroll_undo199'?'payroll':'bank';$table=$kind==='payroll'?'ep_payroll':'ep_bank_payroll';$id=(int)($b['id']??0);$reason=trim((string)($b['note']??''));if(!$reason)fail('سبب التراجع عن الاعتماد مطلوب.');
 db()->beginTransaction();$row=query('SELECT * FROM '.$table.' WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$row||$row['status']!=='approved')fail('السجل غير معتمد.');
 query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$row['user_id']]);
 if(!hash_equals(financial_revision199($row),(string)($b['revision199']??'')))fail('تغير الكشف؛ أعد فتحه.',409);
 $h=financial_approval199($kind,$id);$history=$h?decode($h['body_json']):[];if(!$h||(int)$history['actor']!==(int)$actor['id'])fail('التراجع لصاحب الاعتماد المسجل فقط.',403);
 if($kind==='payroll'){
  if(query("SELECT id FROM ep_bank_payroll WHERE user_id=? AND month_key=? AND status='approved'",[$row['user_id'],$row['month_key']])->fetch())fail('تراجع أولًا عن اعتماد كشف البنك المرتبط.');
  if(query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key>? AND status='approved'",[$row['user_id'],$row['month_key']])->fetch())fail('تراجع أولًا عن الشهور اللاحقة المرتبطة.');
  v26_payroll_version($row,$actor,'approval_undone',$reason);
  query('UPDATE ep_payroll SET status=?,approved_by=?,approved_at=? WHERE id=?',[$history['before']['status'],$history['before']['approved_by']??null,$history['before']['approved_at']??null,$id]);
 }else query('UPDATE ep_bank_payroll SET status=?,approved_at=? WHERE id=?',[$history['before']['status'],$history['before']['approved_at']??null,$id]);
 $history['withdrawn']=true;$history['withdrawn_at']=utc_now();$history['withdrawn_by']=(int)$actor['id'];$history['reason']=$reason;
 query("UPDATE ep_records SET status='cancelled',body_json=?,updated_at=? WHERE id=?",[jencode($history),utc_now(),$h['id']]);
 unset($GLOBALS['APPROVAL_HISTORY199']);audit($action,$kind.':'.$id,['reason'=>$reason,'previous_status'=>$row['status'],'restored_status'=>$history['before']['status']]);db()->commit();return ['ok'=>true];
}
