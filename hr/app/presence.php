<?php
declare(strict_types=1);
// Scheduled presence proof and court visits. Saving a rule does not version the shift.

function presence_clock(): DateTimeImmutable {
 if(!empty($GLOBALS['PRESENCE_NOW']))return new DateTimeImmutable($GLOBALS['PRESENCE_NOW'],new DateTimeZone(settings()['timezone']));
 return local_time();
}
function presence_blank_penalty(): array {
 return ['enabled'=>false,'minutes'=>0,'escalation'=>false,'period'=>'month','mode'=>'replace','tier_unit'=>'minutes','tiers'=>[]];
}
function presence_normalize_penalty(array $r): array {
 if(!is_bool($r['enabled']??null)||!is_bool($r['escalation']??null)||!is_numeric($r['minutes']??null)||!is_finite((float)$r['minutes']))fail('إعداد خصم إثبات التواجد غير صالح.');
 $minutes=(int)$r['minutes'];if((float)$r['minutes']!==(float)$minutes||$minutes<0||$minutes>1440)fail('دقائق الخصم بين 0 و 1440.');
 if(!in_array($r['period']??'',['month','year'],true)||!in_array($r['tier_unit']??'',['minutes','days'],true))fail('فترة العد أو وحدة الشريحة غير صالحة.');
 if(!is_array($r['tiers']??null))fail('شرائح الخصم غير صالحة.');
 $tiers=[];foreach($r['tiers'] as $t){if(!is_array($t)||!is_numeric($t['from']??null))fail('صيغة الشريحة: رقم البداية ثم الخصم.');$from=(int)$t['from'];if((float)$t['from']!==(float)$from||$from<1)fail('بداية الشريحة رقم صحيح من 1.');if($r['tier_unit']==='minutes'){$value=(int)($t['minutes']??-1);if((float)($t['minutes']??-1)!==(float)$value||$value<0||$value>1440)fail('خصم الشريحة بالدقائق بين 0 و 1440.');$tiers[]=['from'=>$from,'minutes'=>$value];}else{$value=(float)($t['days']??-1);if(!is_finite($value)||$value<0||$value>31)fail('خصم الشريحة بالأيام بين 0 و 31.');$tiers[]=['from'=>$from,'days'=>$value];}}
 usort($tiers,fn($a,$b)=>$a['from']<=>$b['from']);
 return ['enabled'=>$r['enabled'],'minutes'=>$minutes,'escalation'=>$r['escalation'],'period'=>$r['period'],'mode'=>'replace','tier_unit'=>$r['tier_unit'],'tiers'=>$tiers];
}
function presence_normalize_rule(array $in): array {
 $mode=(string)($in['mode']??'off');if(!in_array($mode,['off','scheduled','court'],true))fail('نوع إثبات التواجد غير صالح.');
 $capture=(string)($in['capture']??'location');if(!in_array($capture,['location','photo'],true))fail('طريقة الإرسال: موقع فقط أو صورة وموقع.');
 $window=(int)($in['window_minutes']??15);if($window<1||$window>180)fail('مهلة الإرسال بين دقيقة و 180 دقيقة.');
 $times=[];if($mode==='scheduled'){foreach($in['times']??[] as $time){$time=trim((string)$time);if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$time))fail('اكتب كل موعد بالشكل 10:00.');$times[]=$time;}$times=array_values(array_unique($times));sort($times);if(!$times||count($times)>8)fail('حدد موعدًا واحدًا على الأقل وبحد 8 مواعيد.');}
 return ['mode'=>$mode,'capture'=>$capture,'window_minutes'=>$window,'times'=>$times,'penalty'=>$mode==='scheduled'?presence_normalize_penalty($in['penalty']??[]):presence_blank_penalty()];
}
function presence_rule_for(int $uid): ?array {
 if(isset($GLOBALS['PRESENCE_RULES'][$uid]))return $GLOBALS['PRESENCE_RULES'][$uid];
 $row=query("SELECT TOP (1) body_json FROM ep_records WHERE module='presence_rule' AND user_id=? AND status='active' ORDER BY id DESC",[$uid])->fetch();
 return $GLOBALS['PRESENCE_RULES'][$uid]=$row?decode($row['body_json']):null;
}
function presence_rules_map(): array {
 if(isset($GLOBALS['PRESENCE_RULES_MAP']))return $GLOBALS['PRESENCE_RULES_MAP'];
 $map=[];foreach(query("SELECT user_id,body_json FROM ep_records WHERE module='presence_rule' AND status='active' ORDER BY id")->fetchAll() as $r)$map[(int)$r['user_id']]=decode($r['body_json']);
 return $GLOBALS['PRESENCE_RULES_MAP']=$map;
}
function presence_forget_cache(int $uid): void {unset($GLOBALS['PRESENCE_RULES'][$uid],$GLOBALS['PRESENCE_RULES_MAP'],$GLOBALS['PRESENCE_SENDS'][$uid]);}
function presence_sends(int $uid,?string $from=null,?string $to=null): array {
 $sql="SELECT id,user_id,date_from,body_json,created_at FROM ep_records WHERE module='presence_send' AND user_id=?";$args=[$uid];
 if($from){$sql.=' AND date_from>=?';$args[]=$from;}if($to){$sql.=' AND date_from<=?';$args[]=$to;}
 $sql.=' ORDER BY id';$rows=query($sql,$args)->fetchAll();foreach($rows as &$r)$r['body']=decode($r['body_json']);unset($r);return $rows;
}
function presence_send_cache(int $uid): array {return $GLOBALS['PRESENCE_SENDS'][$uid]??=presence_sends($uid);}
function presence_public_send(array $row): array {
 $b=$row['body']??decode($row['body_json']??'{}');
 return ['id'=>(int)$row['id'],'sent_at'=>$b['sent_at']??$row['created_at'],'kind'=>$b['kind']??'','slot'=>$b['slot']??'','court_name'=>$b['court_name']??'','latitude'=>isset($b['latitude'])?(float)$b['latitude']:null,'longitude'=>isset($b['longitude'])?(float)$b['longitude']:null,'accuracy'=>isset($b['accuracy'])?(float)$b['accuracy']:null,'photo_id'=>isset($b['photo_id'])?(int)$b['photo_id']:null];
}
function presence_charge(array $rule,int $sequence): array {
 $minutes=0.0;$days=0.0;if(!empty($rule['enabled'])){$minutes=(float)$rule['minutes'];$tiers=!empty($rule['escalation'])?$rule['tiers']:[];usort($tiers,fn($a,$b)=>$a['from']<=>$b['from']);foreach($tiers as $t)if($sequence>=(int)$t['from']){if(($rule['tier_unit']??'minutes')==='minutes'){$minutes=(float)$t['minutes'];$days=0.0;}else{$days=(float)$t['days'];if(($rule['mode']??'replace')==='replace')$minutes=0.0;}}}
 return [$minutes,$days];
}
function presence_slots(array $rule,string $date,array $sends,DateTimeImmutable $now): array {
 if(($rule['mode']??'')!=='scheduled')return [];
 $tz=new DateTimeZone(settings()['timezone']);$out=[];
 foreach($rule['times'] as $time){$start=new DateTimeImmutable($date.' '.$time,$tz);$end=$start->modify('+'.(int)$rule['window_minutes'].' minutes');$key=$date.' '.$time;$sent=null;foreach($sends as $row)if(($row['body']['slot']??'')===$key){$sent=$row;break;}$status=$sent?'sent':($now<$start?'waiting':($now<=$end?'open':'missed'));$out[]=['key'=>$key,'time'=>$time,'date'=>$date,'opens'=>$start->format('H:i'),'closes'=>$end->format('H:i'),'status'=>$status,'send_id'=>$sent?(int)$sent['id']:null];}
 return $out;
}
function presence_slot_day(array $day): bool {
 if(empty($day['scheduled'])||!empty($day['leave'])||empty($day['configured'])||!empty($day['historical']))return false;
 $note=(string)($day['note']??'');return $note===''||(!str_contains($note,'قبل بدء')&&!str_contains($note,'خارج فترة')&&!str_contains($note,'معفى')&&!str_contains($note,'غير معتمد'));
}
function presence_apply_report(int $uid,array $days,string $reportFrom): array {
 $rule=presence_rule_for($uid);$sends=presence_send_cache($uid);$now=presence_clock();$counts=[];
 foreach($days as &$day){
  $day['presence_missed']=0;$day['presence_penalty_minutes']=0.0;$day['presence_penalty_days']=0.0;$day['presence_slots']=[];
  $day['presence_sends']=array_map('presence_public_send',array_values(array_filter($sends,fn($row)=>$row['date_from']===$day['date'])));
  if(!$rule||$rule['mode']!=='scheduled'||!presence_slot_day($day)||!empty($day['absence']))continue;
  $slots=presence_slots($rule,$day['date'],$sends,$now);$day['presence_slots']=$slots;$period=period_start($day['date'],$rule['penalty']['period']);
  if(!isset($counts[$period])){$counts[$period]=0;if($period<$reportFrom&&empty($GLOBALS['PRESENCE_PRIOR'])){$GLOBALS['PRESENCE_PRIOR']=true;try{$prior=attendance_report($uid,$period,(new DateTimeImmutable($reportFrom))->modify('-1 day')->format('Y-m-d'));foreach($prior as $prev)$counts[$period]+=(int)($prev['presence_missed']??0);}finally{unset($GLOBALS['PRESENCE_PRIOR']);}}}
  foreach($slots as $slot)if($slot['status']==='missed'){$counts[$period]++;[$minutes,$penaltyDays]=presence_charge($rule['penalty'],$counts[$period]);$day['presence_missed']++;$day['presence_penalty_minutes']+=$minutes;$day['presence_penalty_days']+=$penaltyDays;}
 }
 unset($day);return $days;
}
function presence_location_bounds(int $uid,string $date): array {
 $p=policy_for($uid,$date)['values']??[];$accuracy=(float)($p['max_accuracy']??0);$age=(int)($p['location_max_age']??0);$photo=(int)($p['photo_max_age']??0);
 return ['max_accuracy'=>$accuracy>0?$accuracy:200,'location_max_age'=>$age>0?$age:180,'photo_max_age'=>$photo>0?$photo:120];
}
function presence_court_match(float $lat,float $lon,float $accuracy,array $locations): ?array {
 $hit=null;$best=INF;foreach($locations as $site){if(($site['place']??'work')!=='court'||($site['active']??true)===false)continue;$distance=distance_m($lat,$lon,(float)$site['latitude'],(float)$site['longitude']);if($distance+$accuracy<=(float)$site['radius']&&$distance<$best){$best=$distance;$hit=$site;}}
 return $hit;
}
function presence_workday(int $uid,string $date): bool {
 $user=employee_required($uid);$p=policy_for($uid,$date)['values']??[];if(empty($p['configured']))return false;
 $service=service_state($user,$date);if(empty($service['employed'])||empty($service['attendance']))return false;
 if(!empty($user['hired_on'])&&$date<$user['hired_on'])return false;
 if(!in_array((int)(new DateTimeImmutable($date))->format('w'),array_map('intval',$p['weekdays']??[]),true))return false;
 if(in_array($date,$p['holiday_dates']??[],true))return false;
 return approved_day($uid,$date)===null;
}
function presence_edit_access(array $actor,int $target): void {
 if(personal_mode34($actor))fail('هذه العملية تخص مساحة العمل.',403);
 if((int)$actor['id']===$target)fail('لا يعدّل الموظف مواعيد إثبات تواجده.',403);
 employee_required($target);if($actor['role']==='admin')return;
 permission_require($actor,'policy_staff','edit');if(!in_array($target,permission_scope29($actor,'policy_staff','edit'),true))fail('الموظف خارج نطاق الصلاحية',403);
}
function presence_view_access(array $actor,int $target): void {
 employee_required($target);if(personal_mode34($actor)){if((int)$actor['id']!==$target)fail('الملف الشخصي يتيح بياناتك فقط.',403);return;}
 if($actor['role']==='admin')return;$staff=employee_required($target);if((int)($staff['manager_id']??0)===(int)$actor['id'])return;
 permission_require($actor,'report','view');if(!in_array($target,permission_scope29($actor,'report','view'),true))fail('الموظف خارج نطاق الصلاحية',403);
}
function presence_employee_status(array $actor): ?array {
 $rule=presence_rule_for((int)$actor['id']);if(!$rule||$rule['mode']==='off')return null;
 $now=presence_clock();$today=$now->format('Y-m-d');$sends=presence_send_cache((int)$actor['id']);
 if($rule['mode']==='court')return ['mode'=>'court','capture'=>$rule['capture'],'slots'=>[],'sends'=>array_map('presence_public_send',array_values(array_filter($sends,fn($row)=>$row['date_from']===$today)))];
 $slots=[];foreach([$now->modify('-1 day')->format('Y-m-d'),$today] as $date)foreach(presence_slots($rule,$date,$sends,$now) as $slot)if($slot['status']!=='waiting'||$date===$today)$slots[]=$slot;
 return ['mode'=>'scheduled','capture'=>$rule['capture'],'window_minutes'=>(int)$rule['window_minutes'],'slots'=>$slots];
}
function presence_notify_open(array $actor): void {
 $status=presence_employee_status($actor);if(!$status||$status['mode']!=='scheduled')return;
 foreach($status['slots'] as $slot){if($slot['status']!=='open')continue;$title='presence-open:'.$slot['key'];if(query("SELECT id FROM ep_records WHERE module='presence_notice' AND user_id=? AND title=?",[(int)$actor['id'],$title])->fetch())continue;notice((int)$actor['id'],'وقت إثبات التواجد الآن','أرسل إثبات التواجد قبل '.$slot['closes'].'.',false,['screen'=>'attendance','employee_id'=>(int)$actor['id']]);$id=v26_insert('presence_notice',(int)$actor['id'],substr($slot['key'],0,10),null,['slot'=>$slot['key']],'sent');query('UPDATE ep_records SET title=? WHERE id=?',[$title,$id]);}
}
function presence_save_rule(array $actor,array $b): array {
 $target=(int)($b['user_id']??0);presence_edit_access($actor,$target);$rule=presence_normalize_rule(is_array($b['rule']??null)?$b['rule']:[]);
 db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$target]);
 $current=query("SELECT TOP (1) * FROM ep_records WHERE module='presence_rule' AND user_id=? AND status='active' ORDER BY id DESC",[$target])->fetch();
 if($current){query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($rule),utc_now(),$current['id']]);$id=(int)$current['id'];}
 else $id=v26_insert('presence_rule',$target,presence_clock()->format('Y-m-d'),null,$rule,'active');
 query("UPDATE ep_records SET status='replaced' WHERE module='presence_rule' AND user_id=? AND id<>? AND status='active'",[$target,$id]);
 audit('presence_rule_save','user:'.$target,['before'=>$current?decode($current['body_json']):null,'after'=>$rule]);db()->commit();presence_forget_cache($target);return ['ok'=>true,'id'=>$id,'rule'=>$rule];
}
function presence_log(array $actor,array $b): array {
 $target=(int)($b['user_id']??$_GET['user_id']??0);presence_view_access($actor,$target);
 $from=valid_date($b['from']??$_GET['from']??null)?:presence_clock()->format('Y-m-01');$to=valid_date($b['to']??$_GET['to']??null)?:presence_clock()->format('Y-m-d');if($to<$from)fail('فترة السجل غير صالحة.');
 return ['rule'=>presence_rule_for($target),'sends'=>array_map('presence_public_send',presence_sends($target,$from,$to))];
}
function presence_ticket(array $actor): array {
 if(!personal_mode34($actor))fail('الإرسال من مساحة الموظف فقط.',403);
 $ticket=bin2hex(random_bytes(24));$_SESSION['presence_capture']=['token'=>$ticket,'at'=>time(),'user_id'=>(int)$actor['id']];return ['ticket'=>$ticket,'server_time'=>utc_now()];
}
function presence_send(array $actor,array $b): array {
 if(!personal_mode34($actor))fail('الإرسال من مساحة الموظف فقط.',403);
 $uid=(int)$actor['id'];$rule=presence_rule_for($uid);if(!$rule||$rule['mode']==='off')fail('إثبات التواجد غير مفعّل لهذا الحساب.');
 if(empty(service_state(employee_required($uid),presence_clock()->format('Y-m-d'))['employed']))fail('خارج فترة الخدمة.');
 $rid=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$rid))fail('رقم الطلب غير صالح.');
 $now=presence_clock();$workDate=$now->format('Y-m-d');$slotKey='';$court=null;
 if($rule['mode']==='scheduled'){$slotKey=trim((string)($b['slot']??''));if(!preg_match('/^\d{4}-\d{2}-\d{2} (?:[01]\d|2[0-3]):[0-5]\d$/',$slotKey))fail('حدد الموعد المفتوح.');$workDate=substr($slotKey,0,10);if(!presence_workday($uid,$workDate))fail('لا يوجد موعد إثبات في هذا اليوم.');}
 [$lat,$lon,$accuracy]=validate_location($b,presence_location_bounds($uid,$workDate));
 if($rule['mode']==='court'){$court=presence_court_match($lat,$lon,$accuracy,settings()['locations']??[]);if(!$court)fail('يجب أن تكون داخل نطاق محكمة محفوظة.');}
 $photoId=null;if($rule['capture']==='photo'){if(empty($_FILES['photo'])||!is_array($_FILES['photo']))fail('الصورة مطلوبة مع الموقع.');$bounds=presence_location_bounds($uid,$workDate);$cap=$_SESSION['presence_capture']??[];$age=time()-(int)($b['photo_at']??0);if((int)($cap['user_id']??0)!==$uid||!hash_equals((string)($cap['token']??''),(string)($b['capture_ticket']??''))||time()-(int)($cap['at']??0)>$bounds['photo_max_age']||$age<-30||$age>$bounds['photo_max_age'])fail('التقط صورة جديدة.');$photoId=upload_file($_FILES['photo'],$uid,true);unset($_SESSION['presence_capture']);}
 db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid]);presence_forget_cache($uid);$existing=presence_sends($uid);
 foreach($existing as $row)if(($row['body']['request_id']??'')===$rid){db()->commit();return ['ok'=>true,'id'=>(int)$row['id'],'sent_at'=>$row['body']['sent_at']??$row['created_at'],'duplicate'=>true];}
 if($rule['mode']==='scheduled'){$match=null;foreach(presence_slots($rule,$workDate,$existing,$now) as $slot)if($slot['key']===$slotKey)$match=$slot;if(!$match)fail('هذا الموعد غير مسجل لهذا الحساب.');if($match['status']==='sent'){db()->commit();$sentAt='';foreach($existing as $row)if((int)$row['id']===$match['send_id'])$sentAt=$row['body']['sent_at']??$row['created_at'];return ['ok'=>true,'id'=>(int)$match['send_id'],'sent_at'=>$sentAt,'duplicate'=>true];}if($match['status']==='waiting')fail('لم يحن موعد إثبات التواجد.');if($match['status']==='missed')fail('انتهت مهلة إثبات التواجد.');}
 $body=['kind'=>$rule['mode'],'request_id'=>$rid,'slot'=>$slotKey,'court_id'=>$court['id']??'','court_name'=>$court['name']??'','latitude'=>$lat,'longitude'=>$lon,'accuracy'=>$accuracy,'sent_at'=>utc_now(),'photo_id'=>$photoId];
 $id=v26_insert('presence_send',$uid,$workDate,$workDate,$body,'sent');audit('presence_send','record:'.$id,['kind'=>$rule['mode'],'slot'=>$slotKey,'court'=>$court['name']??'']);db()->commit();presence_forget_cache($uid);
 return ['ok'=>true,'id'=>$id,'sent_at'=>$body['sent_at'],'duplicate'=>false,'court_name'=>$body['court_name'],'slot'=>$slotKey];
}
function presence_action(string $action,array $actor,array $b): array {
 if(!in_array($action,['presence_rule_save','presence_log','presence_ticket','presence_send'],true))fail('العملية غير موجودة.',404);
 return match($action){'presence_rule_save'=>presence_save_rule($actor,$b),'presence_log'=>presence_log($actor,$b),'presence_ticket'=>presence_ticket($actor),'presence_send'=>presence_send($actor,$b)};
}
