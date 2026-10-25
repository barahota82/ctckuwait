<?php
declare(strict_types=1);
require_once __DIR__.'/decisions.php';
require_once __DIR__.'/approval-effects.php';
require_once __DIR__.'/departure.php';
require_once __DIR__.'/presence.php';
// Production contracts for the approved V18 interface. Existing authentication,
// CSRF, identity and company gates still run before these handlers.
function workflow_route194(string $action,array $b):?array {
 if(in_array($action,['record_undo199','record_cancel199'],true))return [(string)query('SELECT module FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetchColumn(),$action==='record_undo199'?'review':'view'];
 return match($action){
 'payroll_undo199'=>['payroll','review'],
 'bank_undo199'=>['bank','review'],
 'delegation_list199'=>['permissions','view'],
 'delegation_save199','delegation_end199'=>['permissions','edit'],
 'departure_grant199','departure_withdraw199'=>['report','view'],
 'presence_rule_save'=>['policy_staff','edit'],
 'presence_log'=>['report','view'],
 'presence_send','presence_ticket'=>['attendance','create'],
 'permissions182','permission_get182'=>['request','view'],
 'permission_request183'=>['request','create'],
 'permission_cancel_request199'=>['request','view'],
 'permission_undo199','permission_review183','permission_cancel182'=>['request','review'],
 'permission_punch182'=>['attendance','create'],
 'evaluation_parents185'=>['evaluation','view'],
 'evaluation_followup185'=>['evaluation','create'],
 'evaluation_settings182'=>['evaluation_settings182','view'],
 'evaluation_draft182'=>['evaluation_settings182','create'],
 'review193_types'=>['corporate_document','view'],
 'review193_type_save','review193_type_delete'=>['corporate_document','create'],
 'review193_video_sources','review193_video_download'=>['video_custom','view'],
 default=>null};
}
function workflow_reads194():array{return ['permissions182','permission_get182','evaluation_parents185','evaluation_settings182','review193_types','review193_video_sources','review193_video_download','delegation_list199','presence_log'];}
function permission_clock194(string $stamp):DateTimeImmutable {
 if(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',$stamp))fail('وقت الإذن غير صالح.');
 $v=strlen($stamp)===16?$stamp.':00':$stamp;$date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s',$v,new DateTimeZone(settings()['timezone']));
 if(!$date||$date->format('Y-m-d\TH:i:s')!==$v)fail('وقت الإذن غير صالح.');return $date;
}
function permission_timing194(array $b):array {
 $start=permission_clock194((string)($b['starts_at']??''));$duration=filter_var($b['duration']??null,FILTER_VALIDATE_INT);
 if($duration===false||$duration<1||$duration>1440)fail('حدد مدة الإذن من دقيقة إلى 1440 دقيقة.');
 $end=$start->modify('+'.$duration.' minutes');if($end->getTimestamp()<=time())fail('موعد الإذن انتهى.');
 return ['starts_at'=>$start->format('Y-m-d\TH:i:s'),'ends_at'=>$end->format('Y-m-d\TH:i:s')];
}
function permission_state194(array $p,?int $now=null):string {
 $now??=time();if(!empty($p['returned_at']))return 'completed';if(!empty($p['exited_at']))return $now>permission_clock194($p['ends_at'])->getTimestamp()?'overdue':'open';if($p['decision']==='cancelled')return 'cancelled';if(in_array($p['decision'],['pending','rejected'],true))return $p['decision'];if(!empty($p['cancelled_at']))return 'unused';
 $over=$now>permission_clock194($p['ends_at'])->getTimestamp();return !empty($p['exited_at'])?($over?'overdue':'open'):($over?'review':'approved');
}
function permission_describe194(array $r,array $actor):array {
 $p=decode($r['body_json']);$p['id']=(int)$r['id'];$p['user_id']=(int)$r['user_id'];$p['state']=permission_state194($p);$p['allowed_minutes']=(permission_clock194($p['ends_at'])->getTimestamp()-permission_clock194($p['starts_at'])->getTimestamp())/60;
 $p['actual_minutes']=!empty($p['exited_at'])&&!empty($p['returned_at'])?(permission_clock194($p['returned_at'])->getTimestamp()-permission_clock194($p['exited_at'])->getTimestamp())/60:null;
 $p['late_minutes']=!empty($p['exited_at'])?max(0,((!empty($p['returned_at'])?permission_clock194($p['returned_at'])->getTimestamp():time())-permission_clock194($p['ends_at'])->getTimestamp())/60):null;
 $staff=employee_required((int)$r['user_id']);$p['employee_name']=$staff['name'];$p['name']=$staff['name'];$p['name_en']=decode($staff['profile_json'])['name_en']??'';$p['employee_code']=employee_key($staff);$p['can_review']=permission_can_review194($actor,$r);$p['can_undo199']=!personal_mode34($actor)&&(int)($p['reviewed_by']??0)===(int)$actor['id']&&$p['decision']==='approved'&&isset($p['before_approval199'])&&empty($p['cancelled_at']);$p['can_cancel199']=$p['decision']==='pending'&&(empty($p['ever_approved199'])&&personal_mode34($actor)&&(int)$r['user_id']===(int)$actor['id']||$actor['role']==='admin'&&!personal_mode34($actor));
 foreach(['exit','return']as $kind)if(!empty($p[$kind.'_file_id']))$p[$kind.'_photo']=['data'=>'file.php?id='.(int)$p[$kind.'_file_id']];
 return $p;
}
function permission_can_review194(array $actor,array $r):bool {
 if(personal_mode34($actor)||(int)$r['user_id']===(int)$actor['id'])return false;
 if($actor['role']==='admin')return true;
 if(actual_manager199((int)$r['user_id']))return false;
 $staff=employee_required((int)$r['user_id']);return in_array((int)$actor['id'],function_exists('delegation_reviewers199')?delegation_reviewers199((int)$staff['manager_id'],(int)$staff['id'],'request'):[(int)$staff['manager_id']],true)&&permission_has($actor,'request','review')&&in_array((int)$r['user_id'],permission_scope29($actor,'request','review'),true);
}
function permission_access194(array $actor,array $r,bool $review=false):void {
 $own=personal_mode34($actor)&&(int)$r['user_id']===(int)$actor['id'];if($review?!permission_can_review194($actor,$r):!$own&&!permission_can_review194($actor,$r))fail('الإذن خارج نطاق صلاحياتك.',403);
}
function permission_row194(int $id,bool $lock=false):array {
 $r=query("SELECT * FROM ep_records".($lock?' WITH (UPDLOCK,HOLDLOCK)':'')." WHERE id=? AND module='exit_permission194'",[$id])->fetch();if(!$r)fail('الإذن غير موجود.',404);return $r;
}
function permission_overlap194(int $uid,array $times,int $except=0):void {
 foreach(query("SELECT * FROM ep_records WHERE module='exit_permission194' AND user_id=? AND status<>'rejected'",[$uid])->fetchAll()as $r){$p=decode($r['body_json']);if((int)$r['id']===$except||!empty($p['cancelled_at'])||!empty($p['returned_at']))continue;if($times['starts_at']<$p['ends_at']&&$times['ends_at']>$p['starts_at'])fail('يوجد إذن متداخل لهذا الموظف.');}
}
function permission_store194(array $r,array $p):array {query('UPDATE ep_records SET body_json=?,status=?,date_from=?,date_to=?,updated_at=? WHERE id=?',[jencode($p),$p['decision'],substr($p['starts_at'],0,10),substr($p['ends_at'],0,10),utc_now(),$r['id']]);return array_replace($r,['body_json'=>jencode($p)]);}
function permission_notice194(int $target,array $r,string $title,string $message):void {notice($target,$title,$message,false,['category'=>'attendance','type'=>'exit_permission182','screen'=>'exit_permissions183','permission_id182'=>(int)$r['id'],'record_id'=>(int)$r['id'],'employee_id'=>(int)$r['user_id']]);}
function permission_ticket194(array $actor,array $b):array {
 if(!personal_mode34($actor))fail('البصمة من مساحتك الشخصية فقط.',403);personal_require34($actor,'attendance','create');personal_require34($actor,'request');
 $r=permission_row194((int)($b['permission_id']??0));if((int)$r['user_id']!==(int)$actor['id'])fail('الإذن ليس لك.',403);$p=decode($r['body_json']);permission_punch_allowed194($p,(string)($b['kind']??''));
 $token=bin2hex(random_bytes(32));$_SESSION['permission_capture194']=['token'=>$token,'at'=>time(),'user_id'=>(int)$actor['id'],'id'=>(int)$r['id'],'kind'=>$b['kind']];return ['ticket'=>$token,'server_time'=>utc_now()];
}
function permission_punch_allowed194(array $p,string $kind):void {
 $state=permission_state194($p);if($kind==='exit'){if($state!=='approved'||time()<permission_clock194($p['starts_at'])->getTimestamp())fail('بصمة الخروج خارج الوقت المعتمد.');}
 elseif($kind==='return'){if(!in_array($state,['open','overdue'],true))fail('لا يوجد خروج مفتوح لهذا الإذن.');}else fail('حركة غير صالحة.');
}
function permission_punch194(array $actor,array $b):array {
 if(!personal_mode34($actor))fail('البصمة من مساحتك الشخصية فقط.',403);personal_require34($actor,'attendance','create');personal_require34($actor,'request');$uid=(int)$actor['id'];$rid=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$rid))fail('رقم الطلب غير صالح.');
 db()->beginTransaction();$staff=query('SELECT * FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid])->fetch();$r=permission_row194((int)($b['id']??0),true);if((int)$r['user_id']!==$uid)fail('الإذن ليس لك.',403);$p=decode($r['body_json']);$kind=(string)($b['kind']??'');
 $old=query('SELECT * FROM ep_events WHERE request_id=?',[$rid])->fetch();if($old){if((int)$old['user_id']!==$uid||!in_array((int)$old['id'],[(int)($p['exit_event_id']??0),(int)($p['return_event_id']??0)],true))fail('رقم الطلب مستخدم.',409);db()->commit();return permission_describe194($r,$actor);}
 permission_punch_allowed194($p,$kind);$date=departure_context199($staff)['work_date'];if(!pay35_attendance($staff,$date))fail('هذا الحساب لا يخضع للحضور في هذا اليوم.');$policy=policy_for($uid,$date);$v=$policy['values'];if(!$v['configured'])fail('جدول الدوام غير معتمد.');
 $events=query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$date])->fetchAll();$type='permission_'.$kind;if(!valid_event_transition(event_state($events),$type))fail('الحركة غير متاحة حسب آخر بصمة.');if(approved_day($uid,$date))fail('اليوم إجازة معتمدة.');if(historical_for($uid,'attendance',$date,$date))fail('اليوم مؤرشف.');
 [$lat,$lon,$acc]=validate_location($b,$v);$inside=false;foreach(settings()['locations']as $loc)if(($loc['active']??true)&&(!isset($v['location_id'])||$v['location_id']===''||($loc['id']??'')===$v['location_id'])&&distance_m($lat,$lon,(float)$loc['latitude'],(float)$loc['longitude'])+$acc<=(float)$loc['radius'])$inside=true;if(!$inside)fail('يجب التسجيل من نطاق موقع العمل المحدد.');
 $ticket=$_SESSION['permission_capture194']??[];$age=time()-(int)($b['photo_at']??0);$max=min(120,max(1,(int)$v['photo_max_age']));if(($ticket['id']??0)!==(int)$r['id']||($ticket['user_id']??0)!==$uid||($ticket['kind']??'')!==$kind||!hash_equals($ticket['token']??'',(string)($b['capture_ticket']??''))||time()-($ticket['at']??0)>$max||$age< -30||$age>$max)fail('التقط صورة جديدة من شاشة الإذن.');
 $GLOBALS['EDITOR_FILES143']=[];$fid=upload_file($_FILES['photo']??[],$uid,true);
 $now=utc_now();query('INSERT INTO ep_events(request_id,user_id,event_type,received_at,work_date,latitude,longitude,accuracy,photo_id,policy_id) VALUES(?,?,?,?,?,?,?,?,?,?)',[$rid,$uid,$type,$now,$date,$lat,$lon,$acc,$fid,$policy['id']]);$event=(int)db()->lastInsertId();
 $p[$kind==='exit'?'exited_at':'returned_at']=local_time()->format('Y-m-d\TH:i:s');$p[$kind.'_event_id']=$event;$p[$kind.'_file_id']=$fid;$p[$kind.'_location']=['latitude'=>$lat,'longitude'=>$lon,'accuracy'=>$acc,'received_at'=>$now];$p['revision']++;$r=permission_store194($r,$p);audit('permission_punch','record:'.$r['id'],['event_id'=>$event,'kind'=>$kind,'approved_end'=>$p['ends_at']]);db()->commit();unset($_SESSION['permission_capture194'],$GLOBALS['EDITOR_FILES143']);return permission_describe194($r,$actor);
}
function permission_action194(string $action,array $actor,array $b):array {
 if($action==='permission_punch182')return permission_punch194($actor,$b);
 if($action==='permissions182')return permission_list196($actor);
 if($action==='permission_get182'){$r=permission_row194((int)($_GET['id']??0));permission_access194($actor,$r);return permission_describe194($r,$actor);}
 if($action==='permission_request183'){
  if(!personal_mode34($actor))fail('قدّم الطلب من مساحتك الشخصية.',403);personal_require34($actor,'request','create');personal_require34($actor,'attendance','create');personal_require34($actor,'request');$uid=(int)$actor['id'];if(isset($b['user_id'])&&(int)$b['user_id']!==$uid)fail('الطلب لصاحب الحساب فقط.',403);
  $t=permission_timing194($b);$reason=trim((string)($b['reason']??''));if(!$reason||mb_strlen($reason)>1000)fail('سبب الإذن مطلوب وفي حدود ألف حرف.');db()->beginTransaction();$staff=query('SELECT * FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid])->fetch();$manager=(int)$staff['manager_id'];$managerRequest199=actual_manager199($uid);if(!$managerRequest199&&(!function_exists('delegation_for199')||!delegation_for199($manager))&&(!$manager||$manager===$uid||!query('SELECT id FROM ep_users WHERE id=? AND active=1',[$manager])->fetch()))fail('لم يحدد مدير مباشر متاح لهذا الحساب.');permission_overlap194($uid,$t);
  $p=$t+['user_id'=>$uid,'manager_id'=>$manager,'manager_request199'=>$managerRequest199,'reason'=>$reason,'requested_starts_at'=>$t['starts_at'],'requested_ends_at'=>$t['ends_at'],'requested_at'=>local_time()->format('Y-m-d\TH:i:s'),'decision'=>'pending','revision'=>1,'exited_at'=>null,'returned_at'=>null,'cancelled_at'=>null];$id=v26_insert('exit_permission194',$uid,substr($t['starts_at'],0,10),substr($t['ends_at'],0,10),$p,'pending');$r=permission_row194($id);foreach($managerRequest199?array_column(supervisor_recipients32(),'id'):(function_exists('delegation_reviewers199')?delegation_reviewers199($manager,$uid,'request'):[$manager])as $reviewer199)if((int)$reviewer199!==$uid)permission_notice194((int)$reviewer199,$r,'طلب إذن خروج بانتظار المراجعة',$staff['name'].' — '.$t['starts_at']);audit('permission_request','record:'.$id);db()->commit();return permission_describe194($r,$actor);
 }
 if(in_array($action,['permission_undo199','permission_cancel_request199'],true))return permission_decision199($action,$actor,$b);
 $r=permission_row194((int)($b['id']??0));permission_access194($actor,$r,true);db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$r['user_id']]);$r=permission_row194((int)$r['id'],true);permission_access194($actor,$r,true);$p=decode($r['body_json']);if((int)($b['revision']??0)!==(int)$p['revision'])fail('تغير الطلب؛ أعد فتحه للمراجعة.',409);
 if($action==='permission_cancel182'){if($p['decision']!=='approved'||!empty($p['exited_at'])||!empty($p['cancelled_at'])||!trim((string)($b['reason']??'')))fail('لا يمكن تسجيل هذا الإذن كغير مستخدم.');$p['cancelled_at']=local_time()->format('Y-m-d\TH:i:s');$p['cancel_reason']=mb_substr((string)$b['reason'],0,1000);}
 else{$decision=(string)($b['decision']??'');if($p['decision']!=='pending'||!in_array($decision,['save','approve','reject'],true))fail('القرار غير متاح.');if($decision==='reject'&&!trim((string)($b['note']??'')))fail('سبب الرفض مطلوب.');if($decision==='approve'){$snapshot199=$p;unset($snapshot199['before_approval199'],$snapshot199['decision_history199']);$p['before_approval199']=$snapshot199;$p['ever_approved199']=true;}if($decision!=='reject'){$t=permission_timing194($b);permission_overlap194((int)$r['user_id'],$t,(int)$r['id']);$p=array_replace($p,$t);}if($decision!=='save'){$p['decision']=$decision==='approve'?'approved':'rejected';$p['decided_at']=local_time()->format('Y-m-d\TH:i:s');$p['reviewed_by']=(int)$actor['id'];$p['rejection_reason']=$decision==='reject'?mb_substr(trim((string)($b['note']??'')),0,1000):'';if($decision==='approve')$p['approved_at']=$p['decided_at'];permission_notice194((int)$r['user_id'],$r,$decision==='approve'?'إذن خروج معتمد':'طلب إذن خروج مرفوض',$decision==='approve'?'الخروج: '.$p['starts_at'].' — العودة: '.$p['ends_at']:$p['rejection_reason']);}}
 $p['revision']++;$r=permission_store194($r,$p);audit('permission_review','record:'.$r['id'],['decision'=>$p['decision'],'revision'=>$p['revision']]);db()->commit();return permission_describe194($r,$actor);
}
require_once __DIR__.'/evaluation.php';
require_once __DIR__.'/company-documents.php';
function workflow_action194(string $action,array $actor,array $b):array {
 if(in_array($action,['payroll_undo199','bank_undo199'],true))return financial_approval_undo199($action,$actor,$b);
 if(str_starts_with($action,'delegation_'))return delegation_action199($action,$actor,$b);
 if(in_array($action,['departure_grant199','departure_withdraw199'],true))return departure_action199($action,$actor,$b);
 if(str_starts_with($action,'presence_'))return presence_action($action,$actor,$b);
 if(in_array($action,['record_undo199','record_cancel199'],true))return decision_action199($action,$actor,$b);
 if(str_starts_with($action,'permission'))return permission_action194($action,$actor,$b);
 if(str_starts_with($action,'evaluation_'))return evaluation_action194($action,$actor,$b);
 if(in_array($action,['review193_types','review193_type_save','review193_type_delete'],true))return company_types_action194($action,$actor,$b);
 if($action==='review193_video_sources')return video_sources194($actor);
 if($action==='review193_video_download'){if(empty($actor['full_supervisor31'])||$actor['role']!=='admin')fail('التنزيل للمشرف العام فقط.',403);$r=v26_video_action('v26_video_detail',$actor,['id'=>(int)($_GET['id']??0)]);if(empty($r['file_id']))fail('الفيديو غير متاح.',404);return ['url'=>'file.php?id='.(int)$r['file_id'].'&download=1','name'=>'receipt-'.(int)$r['id'].'.webm'];}
 fail('العملية غير موجودة.',404);
}

function permission_decision199(string $action,array $actor,array $b):array {
 $r=permission_row194((int)($b['id']??0));permission_access194($actor,$r);db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$r['user_id']]);$r=permission_row194((int)$r['id'],true);$p=decode($r['body_json']);if((int)($b['revision']??0)!==(int)$p['revision'])fail('تغير الطلب؛ أعد فتحه.',409);
 $described=permission_describe194($r,$actor);$reason=trim((string)($b['reason']??''));
 if($action==='permission_undo199'){
  if(!$described['can_undo199'])fail('التراجع لصاحب الاعتماد فقط.',403);if(!$reason)fail('سبب التراجع مطلوب.');
  $history=$p['decision_history199']??[];$history[]=['actor'=>(int)$actor['id'],'at'=>utc_now(),'reason'=>$reason,'approval'=>$p['approved_at']??null,'before'=>$p['before_approval199']];
  if(empty($p['exited_at']))foreach(['starts_at','ends_at','duration','reason','review_note']as $field){if(array_key_exists($field,$p['before_approval199']))$p[$field]=$p['before_approval199'][$field];else unset($p[$field]);}
  $p['decision']='pending';$p['decision_history199']=$history;$p['ever_approved199']=true;unset($p['approved_at'],$p['decided_at'],$p['reviewed_by'],$p['before_approval199']);
 }else{if(!$described['can_cancel199'])fail('إلغاء الطلب متاح قبل أول اعتماد فقط؛ بعده للمشرف العام.',403);$p['decision']='cancelled';$p['cancelled_at']=local_time()->format('Y-m-d\TH:i:s');$p['cancelled_by']=(int)$actor['id'];}
 $p['revision']++;$r=permission_store194($r,$p);permission_notice194((int)$r['user_id'],$r,$action==='permission_undo199'?'تراجع عن اعتماد الإذن':'إلغاء طلب الإذن',$reason);audit($action,'record:'.$r['id'],['reason'=>$reason]);db()->commit();return permission_describe194($r,$actor);
}
