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
  $company201=(string)(decode($row['calculation_json'])['work_company_code']??'');
  $set201=array_values(array_filter(query("SELECT * FROM ep_payroll WITH (UPDLOCK,HOLDLOCK) WHERE month_key=? AND status='approved'",[$row['month_key']])->fetchAll(),fn($sib)=>(string)(decode($sib['calculation_json'])['work_company_code']??'')===$company201));
  if(!$set201)$set201=[$row];$lockIds201=array_values(array_unique(array_map(fn($sib)=>(int)$sib['user_id'],$set201)));sort($lockIds201);foreach($lockIds201 as $lockId201)query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$lockId201]);$reread201=array_values(array_filter(query("SELECT * FROM ep_payroll WITH (UPDLOCK,HOLDLOCK) WHERE month_key=? AND status='approved'",[$row['month_key']])->fetchAll(),fn($sib)=>(string)(decode($sib['calculation_json'])['work_company_code']??'')===$company201));if(count($reread201)!==count($set201))fail('تغيرت حالة الكشف أثناء الانتظار.');$set201=$reread201;usort($set201,fn($a,$b)=>(int)$a['id']<=>(int)$b['id']);
  foreach($set201 as $sib){$sid=(int)$sib['id'];query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$sib['user_id']]);if(query("SELECT id FROM ep_bank_payroll WHERE user_id=? AND month_key=? AND status='approved'",[$sib['user_id'],$sib['month_key']])->fetch())fail('تراجع أولًا عن اعتماد كشف البنك المرتبط.');if(query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key>? AND status='approved'",[$sib['user_id'],$sib['month_key']])->fetch())fail('تراجع أولًا عن الشهور اللاحقة المرتبطة.');$hSib=financial_approval199('payroll',$sid);$hist=$hSib?decode($hSib['body_json']):[];if(!$hSib||(int)($hist['actor']??0)!==(int)$actor['id'])fail('التراجع لصاحب الاعتماد المسجل فقط.',403);v26_payroll_version($sib,$actor,'approval_undone',$reason);foreach(query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE user_id=? AND module='leave_cash' AND status='approved'",[$sib['user_id']])->fetchAll()as $leave){$body=decode($leave['body_json']);if((int)($body['_payroll201']['payroll_id']??0)!==$sid||($body['_payroll201']['phase']??'')!=='paid')continue;$body['_payroll201']['phase']='reserved';unset($body['_approved_on'],$body['_valuation']);query('UPDATE ep_records SET status=?,amount=0,body_json=?,updated_at=? WHERE id=?',['pending',jencode($body),utc_now(),$leave['id']]);}foreach(query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE user_id=? AND module='adjustment' AND status='approved'",[$sib['user_id']])->fetchAll()as $add201){$addBody201=decode($add201['body_json']);if(($addBody201['source198']??'')!=='payroll_entry'||($addBody201['payroll_month']??'')!==$sib['month_key'])continue;query("UPDATE ep_records SET status='pending',updated_at=? WHERE id=?",[utc_now(),$add201['id']]);}query('UPDATE ep_payroll SET status=?,approved_by=?,approved_at=? WHERE id=?',[$hist['before']['status'],$hist['before']['approved_by']??null,$hist['before']['approved_at']??null,$sid]);$hist['withdrawn']=true;$hist['withdrawn_at']=utc_now();$hist['withdrawn_by']=(int)$actor['id'];$hist['reason']=$reason;query("UPDATE ep_records SET status='cancelled',body_json=?,updated_at=? WHERE id=?",[jencode($hist),utc_now(),$hSib['id']]);} 
 }else{query('UPDATE ep_bank_payroll SET status=?,approved_at=? WHERE id=?',[$history['before']['status'],$history['before']['approved_at']??null,$id]);$history['withdrawn']=true;$history['withdrawn_at']=utc_now();$history['withdrawn_by']=(int)$actor['id'];$history['reason']=$reason;query("UPDATE ep_records SET status='cancelled',body_json=?,updated_at=? WHERE id=?",[jencode($history),utc_now(),$h['id']]);}
 unset($GLOBALS['APPROVAL_HISTORY199']);audit($action,$kind.':'.$id,['reason'=>$reason,'previous_status'=>$row['status'],'restored_status'=>$history['before']['status']]);db()->commit();return ['ok'=>true];
}
