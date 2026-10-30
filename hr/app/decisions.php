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
 $safe=decode($r['body_json']);unset($safe['_resumption_effects199']);foreach($safe['_decisions199']??[]as $i=>$event)unset($safe['_decisions199'][$i]['before']);$r['body_json']=jencode($safe);$label=phase_label200($r);if($label!=='')$r['phase_label200']=$label;if(($r['module']??'')==='leave'){$r['can_confirm200']=leave_confirm_manager200($actor,$r,$safe);}return $r;
}
function decision_action199(string $action,array $actor,array $b):array {
 $id=(int)($b['id']??0);$r=query('SELECT * FROM ep_records WHERE id=?',[$id])->fetch();if(!$r||!decision_module199($r['module']))fail('السجل غير موجود.',404);
 db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$r['user_id']]);$r=query('SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$id])->fetch();
 if(empty($b['revision199'])||!hash_equals(record_revision173($r),(string)$b['revision199']))fail('تغير السجل؛ أعد فتحه قبل اتخاذ القرار.',409);
 $body=decode($r['body_json']);if($r['module']==='adjustment'&&($body['source198']??'')==='payroll_entry')fail('إضافة التسجيل تتبع كشف الشهر.');$note=trim((string)($b['note']??''));$status=$r['status'];$reviewed=$r['reviewed_by']??null;$reviewNote=$r['review_note']??null;$amount=$r['amount'];
 if($action==='record_undo199'){
  if($r['module']==='loan'&&!empty($body['_disburse200']['debt_id']))fail('الصرف مسجل؛ تغيير الحالة لا يمسح القيد المالي.');
  if($r['module']==='leave'&&($body['_start200']['phase']??'')==='confirmed')fail('الإجازة بدأت؛ التراجع عن الاعتماد لا يلغي التنفيذ.');
  $index=decision_undo_index199($r,$actor);if($index===null)fail('يمكن لصاحب الاعتماد فقط التراجع عن اعتماده.',403);if($note==='')fail('سبب التراجع عن الاعتماد مطلوب.');
  $history=$body['_decisions199'];$snapshot=$history[$index]['before'];if($r['module']==='resumption')resumption_undo_effects199($r,$body,$actor,$note);$history[$index]['withdrawn_at']=utc_now();$history[$index]['withdrawn_by']=(int)$actor['id'];$history[$index]['withdrawal_reason']=$note;
  for($i=$index+1;$i<count($history);$i++)if(empty($history[$i]['withdrawn_at'])&&empty($history[$i]['invalidated_at'])){$history[$i]['invalidated_at']=utc_now();$history[$i]['invalidated_by_decision']=$history[$index]['id'];}
  foreach(['_workflow199','_approved_days','_balance_at_approval','_excess_approved','expected_return','_sick_days','_approved_on','_valuation','_punch39','_resumption_effects199','_start200','_disburse200']as $field){if(array_key_exists($field,$snapshot['body']))$body[$field]=$snapshot['body'][$field];else unset($body[$field]);}
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
 query('UPDATE ep_records SET status=?,body_json=?,reviewed_by=?,review_note=?,amount=?,updated_at=? WHERE id=?',[$status,jencode($body),$reviewed,$reviewNote,$amount,utc_now(),$id]);if($r['module']==='sick'&&function_exists('sick_resequence199'))sick_resequence199((int)$r['user_id'],(string)$r['date_from'],(string)($r['date_to']?:$r['date_from']));
 $updated=array_replace($r,['status'=>$status,'body_json'=>jencode($body)]);audit($action,'record:'.$id,['before_status'=>$r['status'],'status'=>$status,'note'=>$note]);
 notice((int)$r['user_id'],$action==='record_undo199'?'تراجع عن الاعتماد':'إلغاء الطلب',$r['title'].($note!==''?' — '.$note:''),false,['screen'=>$r['module'],'record_id'=>$id]);
 if(in_array($status,['pending','revision'],true))workflow_notify199($updated,'طلب بانتظار المراجعة بعد التراجع');
 db()->commit();return ['ok'=>true,'id'=>$id];
}
function phase_label200(array $r): string {
 if(($r['status']??'')!=='approved')return '';
 $b=decode($r['body_json']??'{}');
 if(($r['module']??'')==='leave'&&(empty($b['returned_on'])&&empty($b['actual_end']))){
  if(($b['_start200']['phase']??'')==='awaiting')return 'معتمدة — لم تبدأ';
  if(($b['_start200']['phase']??'')==='confirmed')return 'إجازة جارية';
 }
 if(($r['module']??'')==='loan'&&($b['_disburse200']['phase']??'')==='awaiting')return 'معتمدة — بانتظار الصرف';
 if(($r['module']??'')==='loan'&&($b['_disburse200']['phase']??'')==='confirmed')return 'تم الصرف';
 return '';
}
function leave_due_notice200(array $r): void {
 if(($r['module']??'')!=='leave'||($r['status']??'')!=='approved')return;
 $seen=decode($r['body_json']??'{}');
 if(($seen['_start200']['phase']??'')!=='awaiting'||!empty($seen['_start200']['notice_at'])||($r['date_from']??'9999-12-31')>local_time()->format('Y-m-d')||db()->inTransaction())return;
 db()->beginTransaction();
 query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[(int)$r['user_id']]);
 $fresh=query('SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[(int)$r['id']])->fetch();
 if(!$fresh){db()->commit();return;}
 $body=decode($fresh['body_json']);
 if(($fresh['module']??'')!=='leave'||($fresh['status']??'')!=='approved'||($body['_start200']['phase']??'')!=='awaiting'||!empty($body['_start200']['notice_at'])||$fresh['date_from']>local_time()->format('Y-m-d')){db()->commit();return;}
 $flow=$body['_workflow199']??[];
 $ids=!empty($flow['submitted_as_manager'])||actual_manager199((int)$fresh['user_id'])?array_map('intval',array_column(supervisor_recipients32(),'id')):[(int)($flow['manager_id']??0)];
 $ids=array_values(array_unique(array_filter($ids,fn($id)=>$id>0&&$id!==(int)$fresh['user_id'])));
 if(!$ids)$ids=array_map('intval',array_column(supervisor_recipients32(),'id'));
 foreach($ids as $id)notice($id,'إجازة معتمدة لم يبدأ تأكيدها',$fresh['title'].' — '.$fresh['date_from'],false,['screen'=>'leave','record_id'=>(int)$fresh['id'],'employee_id'=>(int)$fresh['user_id']]);
 $body['_start200']['notice_at']=utc_now();
 query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($body),utc_now(),$fresh['id']]);
 db()->commit();
}
function leave_due_scan200(): void {
 if(db()->inTransaction())return;
 $today=local_time()->format('Y-m-d');
 foreach(query("SELECT * FROM ep_records WHERE module='leave' AND status='approved' AND date_from<=?",[$today])->fetchAll() as $row){
  $body=decode($row['body_json']);
  if(($body['_start200']['phase']??'')!=='awaiting'||!empty($body['_start200']['notice_at']))continue;
  leave_due_notice200($row);
 }
}
function leave_payroll_gate200(int $uid,string $month,bool $block=true): void {
 if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))return;
 $from=$month.'-01';$to=(new DateTimeImmutable($from))->format('Y-m-t');$today=local_time()->format('Y-m-d');
 $lock=db()->inTransaction()?' WITH (UPDLOCK, HOLDLOCK)':'';
 foreach(query("SELECT * FROM ep_records{$lock} WHERE user_id=? AND module='leave' AND status='approved' AND date_from<=? AND date_to>=?",[$uid,$to,$from])->fetchAll() as $row){
  if(!leave_effect_pending200($row)||$row['date_from']>$today)continue;
  if(!db()->inTransaction())leave_due_notice200($row);
  if($block)fail('إجازة هذا الموظف معتمدة ولم يُؤكد بدءها. سوِّ الحالة قبل اعتماد كشف الفترة المتأثرة.');
 }
}
function execution_scope200(array $actor,string $screen,string $op,int $target): void {
 if($actor['role']==='admin'&&!personal_mode34($actor))return;
 if(personal_mode34($actor)||(int)$actor['id']===$target)fail('لا تُنفذ هذه العملية على طلبك الشخصي.',403);
 permission_require($actor,$screen,$op);
 if(!in_array($target,permission_scope29($actor,$screen,$op),true))fail('الموظف خارج نطاق الصلاحية.',403);
}
function execution_lock200(array $r): array {
 db()->beginTransaction();
 query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[(int)$r['user_id']]);
 $fresh=query('SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[(int)$r['id']])->fetch();
 if(!$fresh)fail('السجل غير موجود.',404);
 return $fresh;
}
function execution_same200(array $fresh,string $module,string $status): void {
 if($fresh['module']!==$module||$fresh['status']!==$status)fail('تغير السجل؛ أعد فتحه قبل اتخاذ القرار.');
}
function execution_revision200(array $fresh,array $b): void {
 if(empty($b['revision199'])||!function_exists('record_revision173')||!hash_equals(record_revision173($fresh),(string)$b['revision199']))fail('تغير السجل؛ أعد فتحه قبل اتخاذ القرار.');
}
function execution_month200(mixed $value): string {
 if(!is_string($value)||!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/',$value,$m)||!checkdate((int)$m[2],1,(int)$m[1]))fail('شهر الاستقطاع غير صالح.');
 return $value;
}
function leave_confirm_manager200(array $actor,array $r,array $body): bool {
 if(($actor['role']??'')==='admin'&&!personal_mode34($actor))return true;
 $manager=(int)($body['_workflow199']['manager_id']??0);
 if($manager<=0)return false;
 $reviewers=function_exists('delegation_reviewers199')?delegation_reviewers199($manager,(int)$r['user_id'],'leave'):[$manager];
 return in_array((int)$actor['id'],array_map('intval',$reviewers),true);
}
function leave_dates_guard200(array $r,string $start,string $end): void {
 $staff=query('SELECT * FROM ep_users WHERE id=?',[(int)$r['user_id']])->fetch();
 foreach(days_between($start,$end) as $day)if(!service_state($staff,$day)['employed'])fail('الفترة خارج خدمة الموظف.');
 if(query("SELECT TOP (1) id FROM ep_records WHERE id<>? AND user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? AND date_to>=?",[(int)$r['id'],(int)$r['user_id'],$end,$start])->fetch())fail('الفترة تتداخل مع إجازة معتمدة.');
}
function execution_event200(array &$body,array $actor,string $decision,string $note,array $before): void {
 $body['_decisions199'][]=['id'=>bin2hex(random_bytes(12)),'actor'=>(int)$actor['id'],'actor_name'=>$actor['name']??'','at'=>utc_now(),'decision'=>$decision,'note'=>$note,'before'=>$before,'withdrawn_at'=>null];
}
function execution_action200(string $action,array $actor,array $b): array {
 $r=query('SELECT * FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetch();if(!$r)fail('السجل غير موجود.',404);
 return match($action){
  'leave_start200'=>leave_start_confirm200($actor,$b,$r),
  'leave_schedule200'=>leave_schedule200($actor,$b,$r),
  'leave_cancel200'=>leave_cancel_confirm200($actor,$b,$r),
  'loan_terms200'=>loan_terms_prepare200($actor,$b,$r),
  'loan_disburse200'=>loan_disburse_confirm200($actor,$b,$r),
  'loan_cancel200'=>loan_cancel_confirm200($actor,$b,$r),
  default=>fail('العملية غير موجودة.',404)
 };
}
function leave_request_dates200(array $b,string $from,string $to,string $return): array {
 $start=valid_date($b['date_from']??$from);$end=valid_date($b['date_to']??$to);$back=valid_date($b['expected_return']??$return);
 if(!$start||!$end||!$back||$end<$start||$back<=$end)fail('تواريخ الإجازة غير صالحة.');
 return [$start,$end,$back];
}
function leave_start_confirm200(array $actor,array $b,array $r): array {
 if($r['module']!=='leave'||$r['status']!=='approved')fail('الإجازة غير جاهزة لتأكيد البدء.');
 execution_scope200($actor,'leave','review',(int)$r['user_id']);
 $r=execution_lock200($r);execution_same200($r,'leave','approved');$body=decode($r['body_json']);
 if(($body['_start200']['phase']??'')==='confirmed'){db()->commit();return ['ok'=>true,'already'=>true,'id'=>(int)$r['id']];}
 if(($body['_start200']['phase']??'')!=='awaiting')fail('هذه الإجازة لا تنتظر تأكيد البدء.');
 execution_revision200($r,$b);
 $approvedFrom=(string)($body['_start200']['approved_from']??$r['date_from']);$approvedTo=(string)($body['_start200']['approved_to']??$r['date_to']);$approvedReturn=(string)($body['_start200']['expected_return']??($body['expected_return']??''));
 [$start,$end,$return]=leave_request_dates200($b,$approvedFrom,$approvedTo,$approvedReturn);
 $admin=$actor['role']==='admin'&&!personal_mode34($actor);$changed=$start!==$approvedFrom||$end!==$approvedTo||$return!==$approvedReturn;$reason=trim((string)($b['reason']??''));
 if(!$admin&&!leave_confirm_manager200($actor,$r,$body))fail('تأكيد البدء للمدير المختص فقط.',403);
 if(!$admin&&$changed)fail('تأكيد المدير المباشر يبقي التواريخ المعتمدة كما هي.');
 if($admin&&$changed&&$reason==='')fail('سبب تعديل التاريخ مطلوب.');
 if($start>local_time()->format('Y-m-d'))fail('لا يُؤكد بدء إجازة لم يحن تاريخها.');
 leave_dates_guard200($r,$start,$end);
 $before=['date_from'=>$r['date_from'],'date_to'=>$r['date_to'],'expected_return'=>$body['expected_return']??null];
 $body['expected_return']=$return;$body['_start200']['phase']='confirmed';$body['_start200']['actor']=(int)$actor['id'];$body['_start200']['at']=utc_now();$body['_start200']['before']=$before;$body['_start200']['after']=['date_from'=>$start,'date_to'=>$end,'expected_return'=>$return];if($reason!=='')$body['_start200']['reason']=$reason;
 execution_event200($body,$actor,'start_confirmed',$reason,$before);
 query('UPDATE ep_records SET date_from=?,date_to=?,body_json=?,updated_at=? WHERE id=?',[$start,$end,jencode($body),utc_now(),$r['id']]);
 if(function_exists('financial_invalidate'))financial_invalidate((int)$r['user_id'],$start);
 audit('leave_start200','record:'.$r['id'],['before'=>$before,'after'=>$body['_start200']['after']]);db()->commit();return ['ok'=>true,'id'=>(int)$r['id']];
}
function leave_schedule200(array $actor,array $b,array $r): array {
 admin_only($actor);if(personal_mode34($actor))fail('هذه الشاشة للمشرف العام في مساحة العمل.',403);
 if($r['module']!=='leave'||$r['status']!=='approved')fail('الإجازة غير جاهزة لتعديل الموعد.');
 $reason=trim((string)($b['reason']??''));if($reason==='')fail('سبب تأجيل الموعد مطلوب.');
 $r=execution_lock200($r);execution_same200($r,'leave','approved');$body=decode($r['body_json']);
 if(($body['_start200']['phase']??'')==='confirmed')fail('الإجازة بدأت؛ تأجيل الموعد قبل تأكيد البدء فقط.');
 if(($body['_start200']['phase']??'')!=='awaiting')fail('هذه الإجازة لا تنتظر تأكيد البدء.');
 execution_revision200($r,$b);
 $approvedFrom=(string)($body['_start200']['approved_from']??$r['date_from']);$approvedTo=(string)($body['_start200']['approved_to']??$r['date_to']);$approvedReturn=(string)($body['_start200']['expected_return']??($body['expected_return']??''));
 [$start,$end,$return]=leave_request_dates200($b,$approvedFrom,$approvedTo,$approvedReturn);
 leave_dates_guard200($r,$start,$end);
 if($start!==(string)$r['date_from'])unset($body['_start200']['notice_at']);
 $before=['date_from'=>$r['date_from'],'date_to'=>$r['date_to'],'expected_return'=>$body['expected_return']??null];
 $body['expected_return']=$return;$body['_start200']['phase']='awaiting';$body['_start200']['approved_from']=$start;$body['_start200']['approved_to']=$end;$body['_start200']['expected_return']=$return;$body['_start200']['postponed_by']=(int)$actor['id'];$body['_start200']['postponed_at']=utc_now();$body['_start200']['postpone_reason']=$reason;$body['_start200']['before_postpone']=$before;
 execution_event200($body,$actor,'postponed',$reason,$before);
 query('UPDATE ep_records SET date_from=?,date_to=?,body_json=?,updated_at=? WHERE id=?',[$start,$end,jencode($body),utc_now(),$r['id']]);
 audit('leave_schedule200','record:'.$r['id'],['before'=>$before,'after'=>['date_from'=>$start,'date_to'=>$end,'expected_return'=>$return]]);db()->commit();return ['ok'=>true,'id'=>(int)$r['id'],'phase'=>'awaiting'];
}
function leave_cancel_confirm200(array $actor,array $b,array $r): array {
 if(($actor['role']??'')!=='admin'||personal_mode34($actor))fail('إلغاء الطلب بعد أول اعتماد للمشرف العام فقط.',403);
 if($r['module']!=='leave'||$r['status']!=='approved')fail('الإجازة غير جاهزة للإلغاء.');
 $reason=trim((string)($b['reason']??''));if($reason==='')fail('سبب الإلغاء مطلوب.');
 $r=execution_lock200($r);execution_same200($r,'leave','approved');$body=decode($r['body_json']);
 if(($body['_start200']['phase']??'')==='confirmed')fail('الإجازة بدأت؛ الإلغاء قبل التنفيذ فقط.');
 if(($body['_start200']['phase']??'')!=='awaiting')fail('هذه الإجازة لا تنتظر تأكيد البدء.');
 execution_revision200($r,$b);
 $body['_cancellation199']=['at'=>utc_now(),'actor'=>(int)$actor['id'],'previous_status'=>$r['status'],'reason'=>$reason];
 execution_event200($body,$actor,'cancelled_before_start',$reason,['date_from'=>$r['date_from'],'date_to'=>$r['date_to']]);
 query('UPDATE ep_records SET status=?,body_json=?,updated_at=? WHERE id=?',['cancelled',jencode($body),utc_now(),$r['id']]);
 audit('leave_cancel200','record:'.$r['id'],['reason'=>$reason]);db()->commit();return ['ok'=>true,'id'=>(int)$r['id']];
}
function loan_same_amount200(array $b,string $key,float $stored): bool {return !array_key_exists($key,$b)||$b[$key]===''||$b[$key]===null||abs(round((float)$b[$key],3)-$stored)<0.0005;}
function loan_money200(mixed $value): float {
 if(is_string($value))$value=trim($value);
 if($value===''||$value===null||!is_numeric($value)||!is_finite((float)$value))fail('حدد المبلغ والقسط وشهر بدء الاستقطاع.');
 return round((float)$value,3);
}
function loan_debt_for200(int $uid,int $loanId): array|false {
 foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='debt_advance' AND status='approved'",[$uid])->fetchAll() as $candidate)if((int)(decode($candidate['body_json'])['loan_id']??0)===$loanId)return $candidate;
 return false;
}
function loan_terms_prepare200(array $actor,array $b,array $r): array {
 admin_only($actor);if(personal_mode34($actor))fail('هذه الشاشة للمشرف العام في مساحة العمل.',403);
 if($r['module']!=='loan'||$r['status']!=='approved')fail('الطلب غير جاهز لتجهيز شروط السلفة.');
 $reason=trim((string)($b['reason']??''));if($reason==='')fail('سبب تعديل مبلغ السلفة أو شروط الاستقطاع مطلوب.');
 $amount=loan_money200($b['amount']??null);$installment=loan_money200($b['installment']??null);$month=execution_month200($b['deduction_month']??null);
 if($amount<=0||$installment<=0)fail('حدد المبلغ والقسط وشهر بدء الاستقطاع.');
 $r=execution_lock200($r);execution_same200($r,'loan','approved');$body=decode($r['body_json']);
 if(($body['_disburse200']['phase']??'')==='confirmed'||loan_debt_for200((int)$r['user_id'],(int)$r['id']))fail('بعد الصرف لا تُعاد كتابة الشروط.');
 if(($body['_disburse200']['phase']??'')!=='awaiting')fail('هذا الطلب لا ينتظر الصرف.');
 execution_revision200($r,$b);
 $current=array_key_exists('amount',$body['_disburse200'])&&$body['_disburse200']['amount']!==null?round((float)$body['_disburse200']['amount'],3):round((float)($body['_disburse200']['approved_amount']??$r['amount']),3);
 $before=['amount'=>$current,'installment'=>$body['_disburse200']['installment']??null,'deduction_month'=>$body['_disburse200']['deduction_month']??null];
 if(!array_key_exists('approved_amount',$body['_disburse200'])||$body['_disburse200']['approved_amount']===null)$body['_disburse200']['approved_amount']=round((float)$r['amount'],3);
 $body['_disburse200']['phase']='awaiting';$body['_disburse200']['amount']=$amount;$body['_disburse200']['installment']=$installment;$body['_disburse200']['deduction_month']=$month;$body['_disburse200']['terms_reason']=$reason;$body['_disburse200']['terms_by']=(int)$actor['id'];$body['_disburse200']['terms_at']=utc_now();
 execution_event200($body,$actor,'terms_prepared',$reason,$before);
 query('UPDATE ep_records SET amount=?,body_json=?,updated_at=? WHERE id=?',[$amount,jencode($body),utc_now(),$r['id']]);
 audit('loan_terms200','record:'.$r['id'],['before'=>$before,'after'=>['amount'=>$amount,'installment'=>$installment,'deduction_month'=>$month]]);db()->commit();return ['ok'=>true,'id'=>(int)$r['id'],'phase'=>'awaiting'];
}
function loan_disburse_confirm200(array $actor,array $b,array $r): array {
 if($r['module']!=='loan'||$r['status']!=='approved')fail('الطلب غير جاهز لتأكيد الصرف.');
 $admin=$actor['role']==='admin'&&!personal_mode34($actor);
 if(!$admin)execution_scope200($actor,'loan','disburse',(int)$r['user_id']);
 $r=execution_lock200($r);execution_same200($r,'loan','approved');$body=decode($r['body_json']);
 if(($body['_disburse200']['phase']??'')==='confirmed'){db()->commit();return ['ok'=>true,'already'=>true,'id'=>(int)$r['id'],'debt_id'=>(int)($body['_disburse200']['debt_id']??0)];}
 $existing=loan_debt_for200((int)$r['user_id'],(int)$r['id']);
 if($existing){$body['_disburse200']['phase']='confirmed';$body['_disburse200']['debt_id']=(int)$existing['id'];query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($body),utc_now(),$r['id']]);db()->commit();return ['ok'=>true,'already'=>true,'id'=>(int)$r['id'],'debt_id'=>(int)$existing['id']];}
 if(($body['_disburse200']['phase']??'')!=='awaiting')fail('هذا الطلب لا ينتظر الصرف.');
 execution_revision200($r,$b);
 $amount=round((float)($body['_disburse200']['amount']??$body['_disburse200']['approved_amount']??$r['amount']),3);
 $installment=isset($body['_disburse200']['installment'])&&$body['_disburse200']['installment']!==null?round((float)$body['_disburse200']['installment'],3):null;
 $month=(string)($body['_disburse200']['deduction_month']??'');
 if(!loan_same_amount200($b,'amount',$amount)||($installment!==null&&!loan_same_amount200($b,'installment',$installment))||($month!==''&&array_key_exists('deduction_month',$b)&&$b['deduction_month']!==''&&$b['deduction_month']!==null&&(string)$b['deduction_month']!==$month))fail('تأكيد الصرف لا يغير المبلغ أو القسط أو شهر الاستقطاع.');
 if($installment===null||$installment<=0||$amount<=0)fail('المشرف العام يحدد مبلغ القسط وشهر بدء الاستقطاع قبل تأكيد الصرف.');
 $month=execution_month200($month);
 $paidOn=valid_date($b['paid_on']??null);$method=(string)($b['method']??'');$reference=trim((string)($b['reference']??''));
 if(!$paidOn||$paidOn>local_time()->format('Y-m-d'))fail('تاريخ الصرف الفعلي مطلوب ولا يكون بعد اليوم.');
 if(!in_array($method,['cash','bank','bank_salary','bank_separate'],true))fail('حدد وسيلة الصرف.');
 if(substr($paidOn,0,7)>$month)fail('تأخر الصرف عن شهر الاستقطاع؛ يقرر المشرف العام الشهر قبل التأكيد.');
 $fid=!empty($_FILES['file']['tmp_name'])?upload_file($_FILES['file'],(int)$actor['id'],false):null;
 $before=['amount'=>$amount,'installment'=>$installment,'deduction_month'=>$month];
 $debtBody=['loan_id'=>(int)$r['id'],'name'=>$r['title'],'reference'=>$reference,'method'=>$method,'installment'=>$installment,'deduction_month'=>$month,'paid_on'=>$paidOn,'actor'=>(int)$actor['id']];
 $debtId=v26_insert('debt_advance',(int)$r['user_id'],$paidOn,null,$debtBody,'approved',$amount);
 if($fid)query('UPDATE ep_records SET file_id=? WHERE id=?',[$fid,$debtId]);
 $body['_disburse200']['phase']='confirmed';$body['_disburse200']['amount']=$amount;$body['_disburse200']['installment']=$installment;$body['_disburse200']['deduction_month']=$month;$body['_disburse200']['paid_on']=$paidOn;$body['_disburse200']['method']=$method;$body['_disburse200']['reference']=$reference;$body['_disburse200']['actor']=(int)$actor['id'];$body['_disburse200']['at']=utc_now();$body['_disburse200']['debt_id']=$debtId;$body['_disburse200']['before']=$before;$body['_disburse200']['after']=['amount'=>$amount,'installment'=>$installment,'deduction_month'=>$month,'paid_on'=>$paidOn];
 execution_event200($body,$actor,'disbursed','',$before);
 query('UPDATE ep_records SET amount=?,body_json=?,updated_at=? WHERE id=?',[$amount,jencode($body),utc_now(),$r['id']]);
 audit('loan_disburse200','record:'.$r['id'],['debt_id'=>$debtId,'before'=>$before,'after'=>$body['_disburse200']['after']]);db()->commit();return ['ok'=>true,'id'=>(int)$r['id'],'debt_id'=>$debtId];
}
function loan_cancel_confirm200(array $actor,array $b,array $r): array {
 if(($actor['role']??'')!=='admin'||personal_mode34($actor))fail('إلغاء الطلب بعد أول اعتماد للمشرف العام فقط.',403);
 if($r['module']!=='loan'||$r['status']!=='approved')fail('الطلب غير جاهز لإلغاء الاعتماد.');
 $reason=trim((string)($b['reason']??''));if($reason==='')fail('سبب الإلغاء مطلوب.');
 $r=execution_lock200($r);execution_same200($r,'loan','approved');$body=decode($r['body_json']);
 if(!empty($body['_disburse200']['debt_id'])||($body['_disburse200']['phase']??'')==='confirmed'||loan_debt_for200((int)$r['user_id'],(int)$r['id']))fail('بعد الصرف لا يُمسح القيد المالي بتغيير الحالة.');
 if(($body['_disburse200']['phase']??'')!=='awaiting')fail('هذا الطلب لا ينتظر الصرف.');
 execution_revision200($r,$b);
 $body['_cancellation199']=['at'=>utc_now(),'actor'=>(int)$actor['id'],'previous_status'=>$r['status'],'reason'=>$reason];
 execution_event200($body,$actor,'cancelled_before_disbursement',$reason,['amount'=>(float)$r['amount']]);
 query('UPDATE ep_records SET status=?,body_json=?,updated_at=? WHERE id=?',['cancelled',jencode($body),utc_now(),$r['id']]);
 audit('loan_cancel200','record:'.$r['id'],['reason'=>$reason]);db()->commit();return ['ok'=>true,'id'=>(int)$r['id']];
}
