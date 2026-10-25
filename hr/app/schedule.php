<?php
declare(strict_types=1);
function schedule_meta38(array $r):array{return decode($r['value_json']??'{}')['_schedule38']??['kind'=>'legacy'];}
function schedule_temp38(array $r):bool{return (schedule_meta38($r)['kind']??'')==='temporary';}
function schedule_select38(array $rows,array $staff,string $date,array $state):?array{
 if(empty($state['employed']))return null;$matches=[];$uid=(int)$staff['id'];
 foreach($rows as $r){if($r['effective_from']>$date||(!empty($r['effective_to'])&&$r['effective_to']<$date))continue;if(!empty($r['user_id'])&&(int)$r['user_id']!==$uid)continue;$m=schedule_meta38($r);
  if(!empty($m['company_code'])&&$m['company_code']!==($state['company_code']??decode($staff['profile_json']??'{}')['company_code']??''))continue;
  if(!empty($r['user_id'])&&!empty($m['service_from'])&&$m['service_from']!==($state['from']??null))continue;
  if(!empty($r['user_id'])&&!empty($state['from'])&&$r['effective_from']<$state['from'])continue;
  $matches[]=$r;
 }
 usort($matches,fn($a,$b)=>(int)schedule_temp38($b)<=>(int)schedule_temp38($a)?:((int)!empty($b['user_id'])<=>(int)!empty($a['user_id']))?:strcmp($b['effective_from'],$a['effective_from'])?:((int)$b['id']<=>(int)$a['id']));return $matches[0]??null;
}
function schedule_policy38(int $uid,string $date,array $rows):?array{
 // Boot also requests policy context for the standalone system administrator.
 // That account has no employment period and must not be treated as an employee.
 $staff=$GLOBALS['SCHEDULE_STAFF38'][$uid]??=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();
 if(!$staff||($staff['role']??'')==='admin')return null;
 $state=service_state($staff,$date);$selected=schedule_select38($rows,$staff,$date,$state);
 if($selected&&schedule_temp38($selected)){$base=schedule_select38(array_values(array_filter($rows,fn($r)=>!schedule_temp38($r))),$staff,$date,$state);if($base){$override=decode($selected['value_json']);$merged=array_replace(decode($base['value_json']),array_intersect_key($override,array_flip(['start','end','weekly','weekly_mode','weekdays','grace_minutes','break_minutes','holiday_dates','location_id','configured','_schedule38'])));$selected['value_json']=jencode($merged);}}
 return $selected;
}
function schedule_service_display38(array $staff,string $today):array{
 $state=service_state($staff,$today);if($state['employed'])return $state;
 $r=query("SELECT * FROM ep_records WHERE module='service_period' AND user_id=? AND status='approved' AND date_from<=? ORDER BY date_from DESC,id DESC",[$staff['id'],$today])->fetch();
 if($r){$state['from']=$r['date_from'];$state['to']=$r['date_to'];}else{$p=decode($staff['profile_json']??'{}');$state['from']=$staff['hired_on']??null;$state['to']=$p['ended_on']??null;}return $state;
}
function schedule_conflicts38(int $uid,?string $company,string $from,string $to,int $exclude=0):void{
 foreach(query('SELECT * FROM ep_policies WITH (UPDLOCK,HOLDLOCK) WHERE id<>? AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?)',[$exclude,$to,$from])->fetchAll()as $r){if(!schedule_temp38($r))continue;$otherUid=(int)($r['user_id']??0);$otherCompany=schedule_meta38($r)['company_code']??null;
  if($uid&&$otherUid&&$uid!==$otherUid)continue;if($company&&$otherCompany&&$company!==$otherCompany)continue;
  // Employee/company intersections are evaluated over dated service/company changes.
  $person=$uid?:$otherUid;
  if($person){$staff=employee_required($person);$start=max($from,$r['effective_from']);$end=min($to,$r['effective_to']??$to);$stateAtStart=service_state($staff,$from);$candidate=['id'=>0,'user_id'=>$uid?:null,'effective_from'=>$from,'effective_to'=>$to,'value_json'=>jencode(['_schedule38'=>['kind'=>'temporary','company_code'=>$company,'service_from'=>$uid?($stateAtStart['from']??null):null]])];$hit=false;foreach(days_between($start,$end)as $date){$state=service_state($staff,$date);if(schedule_select38([$candidate],$staff,$date,$state)&&schedule_select38([$r],$staff,$date,$state)){$hit=true;break;}}if(!$hit)continue;}
  fail(policy_conflict_message371($r),409);
 }
}
function schedule_temporary_save38(array $actor,array $b,array $values,string $from,?string $to):array{
 if(!$to||$to<$from)fail('الجدول المؤقت يحتاج تاريخ بداية ونهاية.');if((new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days>730)fail('مدة الجدول المؤقت لا تتجاوز سنتين.');
 $id=(int)($b['id']??0);$targets=array_values(array_unique(array_map('intval',$b['schedule_users']??[])));$uid=(int)($b['user_id']??0);$company=trim((string)($b['schedule_company']??''));
 if(($b['schedule_scope']??'')==='people'&&!$targets)fail('اختر موظفًا واحدًا على الأقل.');if(($b['schedule_scope']??'')==='company'&&!$company)fail('اختر الشركة.');if($uid)$targets=[$uid];if(!$targets)$targets=[0];if(count($targets)>200||min($targets)<0||($company&&$targets!==[0]))fail('نطاق الجدول المؤقت غير صالح.');if($company&&!company_by_code($company))fail('شركة غير موجودة.');if($id&&count($targets)!==1)fail('عدّل كل فترة مسجلة بصورة مستقلة.');
 foreach($targets as $target)policy_access32($actor,$target,$id?'edit':'create');
 db()->beginTransaction();policy_lock_scope32(0);if($uid&&!empty($values['_text170']))text_pairs_index170($values['_text170']);$old=$id?policy_locked32($id):null;
 if($old){if(!schedule_temp38($old))fail('لا تحول سياسة سابقة إلى مؤقتة؛ أضف جدولًا مؤقتًا جديدًا.');if((int)($old['user_id']??0)!==$targets[0]||(schedule_meta38($old)['company_code']??'')!==$company)fail('لا تغير نطاق الفترة المسجلة.');policy_assert32($old,$b);}
 $saved=[];foreach($targets as $target){$state=$target?service_state(employee_required($target),$from):[];if($target&&!$state['employed'])fail('بداية الجدول يجب أن تكون داخل فترة خدمة الموظف.');$candidate=['id'=>0,'user_id'=>$target,'effective_from'=>$from,'effective_to'=>$to];if(policy_impact32($candidate)['used'])fail('الفترة مرتبطة بتشغيل أو كشوف رواتب؛ اختر تاريخ سريان قادمًا لا يغير الحسابات السابقة.',409);
  schedule_conflicts38($target,$company?:null,$from,$to,$id);$v=$values;$v['_schedule38']=['kind'=>'temporary','company_code'=>$company,'service_from'=>$state['from']??null];
  if($old){query('UPDATE ep_policies SET effective_from=?,effective_to=?,label=?,value_json=? WHERE id=?',[$from,$to,mb_substr((string)$b['label'],0,150),jencode($v),$id]);$saved[]=$id;}
  else{query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(?,?,?,?,?,?)',[$target?:null,$from,$to,mb_substr((string)($b['label']??'جدول مؤقت'),0,150),jencode($v),utc_now()]);$saved[]=(int)db()->lastInsertId();}
  audit('schedule_temporary','policy:'.end($saved),['before'=>$old,'from'=>$from,'to'=>$to,'values'=>$v,'reason'=>$b['reason']??'']);
 }
 db()->commit();unset($GLOBALS['REPORT_POLICIES32']);return ['ok'=>true,'ids'=>$saved];
}
function schedule_validate_employee38(int $uid):void{
 $staff=employee_required($uid);unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);$today=local_time()->format('Y-m-d');$rows=array_values(array_filter(query('SELECT * FROM ep_policies WHERE (user_id=? OR user_id IS NULL) AND effective_to>=?',[$uid,$today])->fetchAll(),'schedule_temp38'));
 for($i=0;$i<count($rows);$i++)for($j=$i+1;$j<count($rows);$j++){$a=$rows[$i];$b=$rows[$j];$from=max($today,$a['effective_from'],$b['effective_from']);$to=min($a['effective_to'],$b['effective_to']);if($from>$to)continue;foreach(days_between($from,$to)as $date){$state=service_state($staff,$date);if(schedule_select38([$a],$staff,$date,$state)&&schedule_select38([$b],$staff,$date,$state))fail('تغيير الخدمة يسبب تداخل جدولين مؤقتين؛ راجع سياسات الموظف أولًا.',409);}}
}
