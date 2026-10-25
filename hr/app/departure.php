<?php
declare(strict_types=1);
function departure_bounds199(string $date,array $policy):array {
 $tz=new DateTimeZone(settings()['timezone']);$start=new DateTimeImmutable($date.' '.$policy['start'],$tz);$end=new DateTimeImmutable($date.' '.$policy['end'],$tz);
 if($end<=$start)$end=$end->modify('+1 day');return [$start,$end];
}
function departure_context199(array $staff,?DateTimeImmutable $now=null):array {
 $now??=local_time();$today=$now->format('Y-m-d');$date=$today;$uid=(int)$staff['id'];
 $latest=query("SELECT TOP (1) * FROM ep_events WHERE user_id=? AND event_type='attendance' AND work_date<=? ORDER BY received_at DESC,id DESC",[$uid,$today])->fetch();
 if($latest&&$latest['work_date']<$today){
  $previous=$latest['work_date'];$next=(new DateTimeImmutable($previous))->modify('+1 day')->format('Y-m-d');
  // The following scheduled start separates a current night/late shift from an old missing checkout.
  if($next===$today){$nextPolicy=policy_for($uid,$today)['values'];[$nextStart]=departure_bounds199($today,$nextPolicy);$events=query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$previous])->fetchAll();
   $todayEvents=query('SELECT id FROM ep_events WHERE user_id=? AND work_date=?',[$uid,$today])->fetchAll();
   if(!$todayEvents&&in_array(event_state($events),['working','away'],true)&&$now<$nextStart)$date=$previous;
  }
 }
 $policy=policy_for($uid,$date);[$start,$end]=departure_bounds199($date,$policy['values']);
 $release=query("SELECT * FROM ep_records WHERE module='departure_release199' AND user_id=? AND date_from=? AND status='approved' ORDER BY id DESC",[$uid,$date])->fetch();
 return ['work_date'=>$date,'policy'=>$policy,'end_at'=>$end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'release'=>$release?['id'=>(int)$release['id'],'at'=>decode($release['body_json'])['granted_at']]:null];
}
function departure_public199(array $context):array{return array_intersect_key($context,array_flip(['work_date','end_at','release']));}
function departure_check_context199(array $b,array $context):void {
 if(isset($b['work_date199'])&&$b['work_date199']!==$context['work_date'])fail('تغير يوم الدوام؛ افتح شاشة البصمة مجددًا قبل الإرسال.',409);
}
function departure_authorize199(array $actor,array $staff):void {
 if(personal_mode34($actor)||(int)$actor['id']===(int)$staff['id'])fail('السماح بالانصراف من مساحة العمل وللموظف التابع فقط.',403);
 if($actor['role']!=='admin'&&((int)($staff['manager_id']??0)!==(int)$actor['id']||!permission_has($actor,'report','view')||!in_array((int)$staff['id'],permission_scope29($actor,'report','view'),true)))fail('السماح للمدير المباشر داخل نطاقه فقط.',403);
}
function departure_action199(string $action,array $actor,array $b):array {
 $uid=(int)($b['user_id']??0);$staff=employee_required($uid);departure_authorize199($actor,$staff);
 db()->beginTransaction();$staff=query('SELECT * FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid])->fetch();departure_authorize199($actor,$staff);$context=departure_context199($staff);$date=$context['work_date'];
 if(service_state($staff,$date)['staff_type']!=='rep')fail('السماح مخصص للمناديب.');
 if($action==='departure_grant199'){
  departure_check_context199($b,$context);if(!$context['policy']['values']['configured']||!pay35_attendance($staff,$date)||approved_day($uid,$date))fail('لا يوجد دوام متاح لهذا الموظف.');
  if(utc_now()>=$context['end_at'])fail('وصل موعد نهاية الدوام؛ الانصراف العادي متاح بالفعل.');
  $events=query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$date])->fetchAll();if(event_state($events)==='closed')fail('الموظف سجل انصرافه بالفعل.');
  if($context['release']){db()->commit();return ['ok'=>true,'id'=>$context['release']['id'],'duplicate'=>true];}
  $reason=trim((string)($b['reason']??''));$body=['granted_by'=>(int)$actor['id'],'manager_name'=>$actor['name'],'granted_at'=>utc_now(),'scheduled_end'=>$context['end_at'],'reason'=>$reason!==''?mb_substr($reason,0,1000):'انتهاء مهام التحصيل / التوصيل'];
  $id=v26_insert('departure_release199',$uid,$date,$date,$body,'approved');
  foreach(supervisor_recipients32()as $admin)notice((int)$admin['id'],'السماح بانصراف مندوب قبل نهاية الدوام',$actor['name'].' — '.$staff['name'].' — موعد نهاية الدوام '.$context['policy']['values']['end'],false,['screen'=>'report','employee_id'=>$uid,'from'=>$date,'to'=>$date,'release_id199'=>$id]);
  notice($uid,'السماح بالانصراف','وافق مديرك على الانصراف؛ سجّل بصمتك من نطاق موقع العمل بالصورة والموقع.',false,['screen'=>'attendance','employee_id'=>$uid]);audit('departure_grant','record:'.$id,$body);db()->commit();return ['ok'=>true,'id'=>$id];
 }
 $r=query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=? AND module='departure_release199' AND user_id=?",[(int)($b['id']??0),$uid])->fetch();if(!$r||$r['status']!=='approved')fail('السماح غير متاح للتراجع.');$body=decode($r['body_json']);if((int)$body['granted_by']!==(int)$actor['id'])fail('يمكن لصاحب السماح فقط التراجع عنه.',403);
 $reason=trim((string)($b['reason']??''));if(!$reason)fail('سبب التراجع مطلوب.');financial_invalidate($uid,$r['date_from']);$body['withdrawn_at']=utc_now();$body['withdrawn_by']=(int)$actor['id'];$body['withdrawal_reason']=mb_substr($reason,0,1000);
 query("UPDATE ep_records SET status='cancelled',body_json=?,updated_at=? WHERE id=?",[jencode($body),utc_now(),$r['id']]);audit('departure_withdraw','record:'.$r['id'],$body);notice($uid,'تراجع عن السماح بالانصراف',$reason,false,['screen'=>'attendance','employee_id'=>$uid]);db()->commit();return ['ok'=>true];
}
function departure_report199(int $uid,string $from,string $to):array {
 $out=[];foreach(query("SELECT * FROM ep_records WHERE module='departure_release199' AND user_id=? AND date_from>=? AND date_from<=? ORDER BY id",[$uid,$from,$to])->fetchAll()as $r)$out[$r['date_from']][]=decode($r['body_json'])+['id'=>(int)$r['id'],'status'=>$r['status']];return $out;
}
function departure_apply199(array &$day,array $releases,?DateTimeImmutable $exit):void {
 $day['departure_releases199']=$releases;$day['early_raw_minutes199']=$day['early_minutes'];
 foreach($releases as $release)if($release['status']==='approved'&&$exit&&$exit->getTimestamp()>=(new DateTimeImmutable($release['granted_at'],new DateTimeZone('UTC')))->getTimestamp()){$day['early_minutes']=0;$day['departure_allowed199']=true;break;}
}
