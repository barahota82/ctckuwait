<?php
declare(strict_types=1);
function permit_context89(array $staff,string $today):array {
 $state=service_state($staff,$today);$profile=$staff['profile']??decode($staff['profile_json']??'{}');
 $closed=!empty($profile['ended_on'])&&$profile['ended_on']<$today;
 foreach($GLOBALS['EP_SERVICE_CACHE'][(int)$staff['id']]['periods']??[] as $period)if(!empty($period['date_to'])&&$period['date_to']<$today)$closed=true;
 $transfer=$profile['sponsorship_transfer89']??[];if(!is_array($transfer))$transfer=[];
 return ['ended'=>empty($state['employed'])&&$closed,'transfer'=>$transfer,'transferred'=>($transfer['status']??'pending')==='transferred'&&!empty($transfer['date'])&&$transfer['date']<=$today];
}
function permit_bucket89(array $record,array $context,string $today):string {
 if($context['ended'])return $context['transferred']?'ended_transferred':'ended_company';
 return !empty($record['date_to'])&&$record['date_to']<$today?'expired':'active';
}
function permit_records89(array $records,string $tab,string $today):array {
 if(!in_array($tab,['active','expired','ended_company','ended_transferred'],true))fail('تبويب إذن العمل غير صالح');
 $contexts=[];$result=[];
 foreach($records as $record){$uid=(int)$record['user_id'];$context=$contexts[$uid]??=permit_context89(employee_required($uid),$today);$bucket=permit_bucket89($record,$context,$today);if($bucket!==$tab)continue;
  $record['permit_bucket89']=$bucket;$record['sponsorship_transfer89']=$context['transfer'];$record['permit_incomplete89']=empty($record['date_to']);$result[]=$record;
 }
 return $result;
}
function permit_transfer_save89(array $actor,array $body):array {
 v26_permission($actor,'work_permit','edit');admin_only($actor);$uid=(int)($body['user_id']??0);$status=(string)($body['status']??'');$today=local_time()->format('Y-m-d');
 if(!in_array($status,['pending','transferred'],true))fail('حدد حالة نقل الكفالة');
 $date=$status==='transferred'?valid_date($body['date']??null):null;if($status==='transferred'&&(!$date||$date>$today))fail('تاريخ نقل الكفالة مطلوب ولا يكون في المستقبل');
 db()->beginTransaction();$staff=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid])->fetch();if(!$staff)fail('الموظف غير موجود');
 if(!query("SELECT TOP 1 id FROM ep_records WHERE user_id=? AND module='work_permit' AND status<>'archived'",[$uid])->fetch())fail('لا يوجد إذن عمل للموظف');
 if(!permit_context89($staff,$today)['ended'])fail('حالة النقل مخصصة للمنتهية خدماتهم');
 $profile=decode($staff['profile_json']??'{}');$before=$profile['sponsorship_transfer89']??null;$after=['status'=>$status,'date'=>$date];$profile['sponsorship_transfer89']=$after;
 query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($profile),$uid]);audit('work_permit_transfer','user:'.$uid,['before'=>$before,'after'=>$after]);db()->commit();return ['ok'=>true];
}
