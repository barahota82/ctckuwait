<?php
declare(strict_types=1);
require_once __DIR__.'/attendance-prefetch.php';
require_once __DIR__.'/departure.php';
function modules(): array {return [
 'leave'=>['label'=>'الإجازات','employee'=>true,'approval'=>true,'fields'=>['leave_type'=>'نوع الإجازة','reason'=>'السبب']],
 'sick'=>['label'=>'المرضيات','employee'=>true,'approval'=>true,'fields'=>['reason'=>'ملاحظات المرضية']],
 'external'=>['label'=>'انصراف من الخارج','employee'=>true,'approval'=>true,'fields'=>['reason'=>'السبب','customer_area'=>'منطقة المهمة']],
 'request'=>['label'=>'طلبات الموظفين','employee'=>true,'approval'=>true,'fields'=>['record_category49'=>'التصنيف','request_type'=>'نوع الطلب','details'=>'التفاصيل','salary_month'=>'شهر الراتب','recipient'=>'الجهة الموجه إليها','purpose'=>'الغرض','requested_item'=>'المطلوب','reason'=>'سبب الاحتياج']],
 'leave_cash'=>['label'=>'تسويات الإجازة النقدية','employee'=>false,'approval'=>true,'fields'=>['days'=>'عدد أيام التسوية','payroll_month'=>'شهر الإضافة للراتب','reason'=>'السبب','payment_method'=>'طريقة الصرف','leave_id'=>'الإجازة المرتبطة']],
 'loan'=>['label'=>'طلبات السلف','employee'=>true,'approval'=>true,'fields'=>['reason'=>'السبب']],
 'custody'=>['label'=>'العهد','employee'=>false,'approval'=>false,'fields'=>['record_category49'=>'التصنيف','custody_state49'=>'حالة العهدة','asset_type'=>'نوع العهدة','serial'=>'الرقم / اللوحة','handover'=>'بيان التسليم','return_date'=>'تاريخ الاسترجاع','return_notes'=>'ملاحظات الاسترجاع']],
 'document'=>['label'=>'المستندات والعقود','employee'=>false,'approval'=>true,'fields'=>['record_category49'=>'التصنيف','document_type'=>'نوع المستند','document_number'=>'الرقم','expiry'=>'تاريخ الانتهاء']],
 'discipline'=>['label'=>'الإنذارات والقرارات','employee'=>false,'approval'=>true,'fields'=>['record_category49'=>'التصنيف','related_record49'=>'رقم القرار الأصلي — مطلوب للتظلم','decision_type'=>'نوع القرار','reason'=>'السبب','employee_response'=>'رد الموظف','payroll_month'=>'شهر تطبيق المبلغ YYYY-MM']],
 'evaluation'=>['label'=>'التقييمات','employee'=>false,'approval'=>true,'fields'=>['record_category49'=>'التصنيف','related_record49'=>'رقم التقييم الأصلي — مطلوب لخطة التحسين','score'=>'التقييم من 100','criteria'=>'المعايير','strengths'=>'نقاط القوة','improvement'=>'خطة التحسين','source182'=>'مصدر التقييم','recommendation'=>'توصية المسؤول','results'=>'النتائج المحققة','cooperation'=>'التعاون','communication'=>'التواصل','followup_date'=>'موعد المتابعة','issues'=>'الجوانب المطلوب تحسينها','goals'=>'أهداف التحسين','actions'=>'الإجراءات','support'=>'الدعم المطلوب','related_reference185'=>'التقييم المرتبط']],
 'employment'=>['label'=>'إجراءات التعيين وإنهاء الخدمة','employee'=>false,'approval'=>false,'fields'=>['stage'=>'الإجراء','checklist'=>'قائمة الإجراءات','details'=>'التفاصيل']],
 'adjustment'=>['label'=>'إضافات تحتاج اعتمادًا','employee'=>false,'approval'=>true,'fields'=>['payroll_month'=>'شهر التطبيق YYYY-MM','reason'=>'السبب']],
 'proof'=>['label'=>'طلبات إثبات التواجد','employee'=>false,'approval'=>false,'fields'=>['reason'=>'سبب الطلب','deadline'=>'موعد الاستجابة']],
 ];}
function days_between(string $from,string $to): array {
 $a=new DateTimeImmutable($from);$b=new DateTimeImmutable($to);
 $n=(int)$a->diff($b)->format('%r%a');if($n<0||$n>366)fail('الفترة يجب أن تكون من يوم إلى 367 يومًا.');
 $out=[];for($i=0;$i<=$n;$i++)$out[]=$a->modify("+$i days")->format('Y-m-d');return $out;
}
function sick_year_bounds(string $date,array $user,array $cfg): array {
 if($cfg['sick_year']==='hire'){
  if(!$user['hired_on'])fail('حدد تاريخ تعيين الموظف لحساب السنة.');
  $md=substr($user['hired_on'],5);$year=(int)substr($date,0,4);
  $start=sprintf('%04d-%s',$year,$md);
  if($start>$date)$start=sprintf('%04d-%s',$year-1,$md);
  $a=new DateTimeImmutable($start);
 }else{$a=new DateTimeImmutable(substr($date,0,4).'-01-01');}
 return [$a->format('Y-m-d'),$a->modify('+1 year')->modify('-1 day')->format('Y-m-d')];
}
function leave_effect_pending200(array $r,?array $body=null): bool {
 if(($r['module']??'')!=='leave'||($r['status']??'')!=='approved')return false;
 if($body===null)$body=is_string($r['body_json']??null)?decode((string)$r['body_json']):(is_array($r['body_json']??null)?$r['body_json']:[]);
 return ($body['_start200']['phase']??'')==='awaiting';
}
function resumption_leave_ready200(?array $leave): void {
 if(!$leave||($leave['status']??'')!=='approved'||leave_effect_pending200($leave))fail('أكد بدء الإجازة قبل تسجيل المباشرة.');
}
function approved_day(int $uid,string $date): ?array {
 foreach(($GLOBALS['REPORT_LEAVE32'][$uid]??query("SELECT * FROM ep_records WHERE user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? ORDER BY id",[$uid,$date])->fetchAll())as $r){$v=decode($r['body_json']);if(leave_effect_pending200($r,$v)||$r['date_from']>$date)continue;$end=$r['date_to'];if($r['module']==='leave'){$end=!empty($v['late_open'])?'9999-12-31':($v['actual_end']??$end);if(empty($v['late_open'])&&empty($v['actual_end'])&&!empty($v['returned_on']))$end=(new DateTimeImmutable($v['returned_on']))->modify('-1 day')->format('Y-m-d');}if($end>=$date)return $r;}
 foreach(($GLOBALS['REPORT_HISTORY32'][$uid]['leave_history']??historical_for($uid,'leave_history','0000-01-01','9999-12-31'))as $h){$v=decode($h['body_json']);if($v['from']<=$date&&$v['to']>=$date)return ['module'=>'leave','body_json'=>'{}','historical'=>true];}return null;
}
function original_leave_balance(int $uid,array $cfg,?string $reference=null): array {
 $u=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();if(!$u)fail('الموظف غير موجود.');
 $copy=$cfg;$copy['sick_year']=$cfg['leave_year'];
 [$from,$to]=sick_year_bounds($reference??local_time()->format('Y-m-d'),$u,$copy);
 $window=leave_service_window($u,$reference??local_time()->format('Y-m-d'));if($window['id'])$from=max($from,$window['from']);$used=0;$pending=0;
 foreach(query("SELECT * FROM ep_records WHERE module='leave' AND user_id=? AND date_to>=? AND date_from<=? AND status IN ('approved','pending','revision')",[$uid,$from,$to])->fetchAll()as $r){
  if(leave_effect_pending200($r))continue;
  foreach(days_between($r['date_from'],$r['date_to'])as $d)if($d>=$from&&$d<=$to){if($r['status']==='approved')$used++;else$pending++;}
 }
 foreach(historical_for($uid,'leave_history','0000-01-01','9999-12-31')as $h){$v=decode($h['body_json']);foreach(days_between($v['from'],$v['to'])as $date)if($date>=$from&&$date<=$to)$used++;}
 $profile=decode($u['profile_json']);$entitlement=$profile['leave_entitlement']??$cfg['annual_leave_days'];$st=service_state($u,$reference??local_time()->format('Y-m-d'));if($window['id']&&$window['from']&&eligible_leave_days($u,(new DateTimeImmutable(max($from,$window['from'])))->modify('-1 day')->format('Y-m-d'),$reference??local_time()->format('Y-m-d'),array_replace($cfg,['leave_exclude_approved_days'=>false]))===0)$entitlement=0;
 return ['year_from'=>$from,'year_to'=>$to,'entitlement'=>$entitlement,'used'=>$used,'pending'=>$pending,'remaining'=>$entitlement===null?null:max(0,(float)$entitlement-$used)];
}
function leave_balance_days(int $uid,array $cfg,?string $reference=null):array {
 if(!empty($cfg['upgrade_v26']))return v26_leave_balance($uid,$cfg,$reference??local_time()->format('Y-m-d'));

 $reference??=local_time()->format('Y-m-d');$base=original_leave_balance($uid,$cfg,$reference);$u=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();$profile=decode($u['profile_json']);
 $h=query("SELECT TOP (1) * FROM ep_history WHERE kind='leave_balance' AND employee_code=? AND period_key<=? ORDER BY period_key DESC",[employee_key($u),$reference])->fetch();
 $window=leave_service_window($u,$reference);if($h&&$window['id']&&$h['period_key']<$window['from'])$h=false;
 if(!$h){if(($cfg['leave_accrual_mode']??'annual')==='daily'&&$base['entitlement']!==null&&$window['from']){$base['entitlement']=$profile['leave_entitlement']??$cfg['annual_leave_days'];$days=eligible_leave_days($u,(new DateTimeImmutable(max($window['from'],$base['year_from'])))->modify('-1 day')->format('Y-m-d'),$reference,$cfg);$earned=$days*(float)$base['entitlement']/max(1,(float)$cfg['leave_accrual_divisor']);$base['remaining']=max(0,round($earned-$base['used'],3));return $base+['cash_days'=>null,'opening_as_of'=>null,'accrued'=>round($earned,3)];}return $base+['cash_days'=>null,'opening_as_of'=>null,'accrued'=>0];}
 $b=decode($h['body_json']);$opening=$b['as_of'];$used=0;$pending=0;
 foreach(query("SELECT * FROM ep_records WHERE module='leave' AND user_id=? AND date_to>? AND date_from<=? AND status IN ('approved','pending','revision')",[$uid,$opening,$reference])->fetchAll()as $rec){if(leave_effect_pending200($rec))continue;foreach(days_between($rec['date_from'],$rec['date_to'])as $date)if($date>$opening&&$date<=$reference){if($rec['status']==='approved')$used++;else $pending++;}}
 $entitlement=$b['annual_entitlement']!==''?(float)$b['annual_entitlement']:($profile['leave_entitlement']??$cfg['annual_leave_days']);$accrued=0.0;
 if(($cfg['leave_accrual_mode']??'annual')==='daily'&&$entitlement!==null){$n=eligible_leave_days($u,$opening,$reference,$cfg);$accrued=$n*(float)$entitlement/max(1,(float)($cfg['leave_accrual_divisor']??365));}
 foreach(historical_for($uid,'leave_history','0000-01-01','9999-12-31')as $rec){$v=decode($rec['body_json']);foreach(days_between($v['from'],$v['to'])as $date)if($date>$opening&&$date<=$reference)$used++;}
 $cashUsed=0.0;foreach(query("SELECT body_json FROM ep_records WHERE module='leave_cash' AND status='approved' AND user_id=? AND date_from>? AND date_from<=?",[$uid,$opening,$reference])->fetchAll()as $rec)$cashUsed+=(float)(decode($rec['body_json'])['days']??0);foreach(historical_for($uid,'leave_settlement',$opening,$reference)as $settlement)if($settlement['period_key']>$opening)$cashUsed+=(float)decode($settlement['body_json'])['days'];
 return ['year_from'=>$base['year_from'],'year_to'=>$base['year_to'],'entitlement'=>$entitlement,'used'=>$used,'pending'=>$pending,'remaining'=>round((float)$b['days']+$accrued-$used,3),'cash_days'=>round((float)$b['cash_days']+$accrued-$cashUsed,3),'opening_as_of'=>$opening,'accrued'=>round($accrued,3),'opening_days'=>(float)$b['days']];
}
function sick_snapshot(array $record,array $user,array $cfg): array {
 if($cfg['sick_full_days']===null||$cfg['sick_half_days']===null)fail('حدد شرائح المرضيات السنوية قبل الاعتماد.');
 $out=[];
 foreach(days_between($record['date_from'],$record['date_to']) as $d){
  [$from,$to]=sick_year_bounds($d,$user,$cfg);
  $used=0;
  foreach(historical_for((int)$user['id'],'sick_summary',substr($from,0,4),substr($from,0,4))as $h){$v=decode($h['body_json']);$used+=(int)$v['full']+(int)$v['half']+(int)$v['unpaid'];}
  foreach(query("SELECT body_json FROM ep_records WHERE user_id=? AND module='sick' AND status='approved' AND date_to>=? AND date_from<=?",[$user['id'],$from,$to])->fetchAll() as $r)
   foreach((decode($r['body_json'])['_sick_days']??[])as $day)if($day['date']>=$from&&$day['date']<=$to)$used++;
  foreach($out as $day)if($day['date']>=$from&&$day['date']<=$to)$used++;
  $paid=$used<(int)$cfg['sick_full_days']?1.0:($used<(int)$cfg['sick_full_days']+(int)$cfg['sick_half_days']?0.5:0.0);
  $out[]=['date'=>$d,'paid'=>$paid,'year_from'=>$from,'year_to'=>$to,'sequence'=>$used+1];
 }
 return $out;
}
function attendance_evidence_start33():?string {
 if(!array_key_exists('EP_ATTENDANCE_EVIDENCE33',$GLOBALS))$GLOBALS['EP_ATTENDANCE_EVIDENCE33']=query('SELECT MIN(work_date) FROM ep_events')->fetchColumn()?:null;
 return $GLOBALS['EP_ATTENDANCE_EVIDENCE33'];
}
function attendance_report(int $uid,string $from,string $to): array {
 $requestedFrom397=$from;$from=substr($from,0,7).'-01';$graceUsed397=[];$input113=$GLOBALS['ATTENDANCE_INPUT113'][$uid.'|'.$from.'|'.$to]??null;
 $user=$input113['user']??query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();if(!$user||$user['role']==='admin')fail('المشرف العام لا يملك سجلًا وظيفيًا.');
 $cfg=settings();$tz=new DateTimeZone($cfg['timezone']);$out=[];$counts=[];$punchCounts39=[];$historicSick=[];foreach(($input113!==null?($input113['history']['sick_summary']??[]):query("SELECT body_json FROM ep_history WHERE employee_code=? AND kind='sick_summary'",[employee_key($user)])->fetchAll())as $h)foreach(decode($h['body_json'])['periods']??[]as $period)foreach(days_between($period['from'],$period['to'])as $day)$historicSick[$day]=true;
 $GLOBALS['REPORT_POLICIES32'][$uid]=$input113['policies']??query('SELECT * FROM ep_policies WHERE (user_id=? OR user_id IS NULL) AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC,effective_from DESC,id DESC',[$uid,$to,$from])->fetchAll();
 $GLOBALS['REPORT_LEAVE32'][$uid]=$input113['leave']??query("SELECT * FROM ep_records WHERE user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? ORDER BY id",[$uid,$to])->fetchAll();
 $history32=$input113['history']??[];if($input113===null)foreach(query("SELECT * FROM ep_history WHERE employee_code=? AND (kind IN ('leave_history','sick_summary') OR (kind='attendance' AND period_key>=? AND period_key<=?)) ORDER BY period_key",[employee_key($user),$from,$to])->fetchAll()as $h)$history32[$h['kind']][]=$h;
 $GLOBALS['REPORT_HISTORY32'][$uid]=$history32+['leave_history'=>[]];$events32=[];foreach(($input113['events']??query('SELECT * FROM ep_events WHERE user_id=? AND work_date>=? AND work_date<=? ORDER BY received_at,id',[$uid,$from,$to])->fetchAll())as $event32)$events32[$event32['work_date']][]=$event32;
 $policies32=$GLOBALS['REPORT_POLICIES32'][$uid];$releases199=function_exists('departure_report199')?departure_report199($uid,$from,$to):[];
 $decisions39=[];foreach(($input113['decisions']??query("SELECT id,date_from,body_json FROM ep_records WHERE user_id=? AND module='request' AND status='approved' AND date_from>=? AND date_from<=? ORDER BY id",[$uid,$from,$to])->fetchAll())as $r39){$v39=decode($r39['body_json'])['_punch39']??null;if($v39){$v39['record_id']=(int)$r39['id'];$decisions39[$r39['date_from']][]=$v39;}}
 foreach(days_between($from,$to)as $date){
  $GLOBALS['REPORT_POLICIES32'][$uid]=$policies32; $policy=policy_for($uid,$date);$p=$policy['values'];
  $events=$events32[$date]??[];
  $day=['date'=>$date,'policy_id'=>$policy['id'],'configured'=>(bool)$p['configured'],'events'=>$events,'late_minutes'=>0,'late_raw_minutes397'=>0,'grace_applied_minutes397'=>0,'early_minutes'=>0,'permission_minutes'=>0,'unclosed_permission'=>false,'missing'=>0,'penalty_minutes'=>0,'penalty_days'=>0,'absence'=>false,'sick_paid'=>null,'leave'=>null,'scheduled'=>false,'overtime_minutes'=>0];
  $day['scheduled_hours']=max(0.01,((strtotime($p['end'])-strtotime($p['start']))/60-(float)($p['break_minutes']??0))/60);$cutoff=$cfg['attendance_start']??attendance_evidence_start33();if(!$cutoff||$date<$cutoff){$day['configured']=true;$day['note']='قبل بدء تسجيل الحضور الفعلي';if(isset($historicSick[$date])){$day['leave']='sick';$day['note']='مرضية تاريخية فعلية؛ لا تعني حضورًا أو راتبًا تاريخيًا';}$out[]=$day;continue;}
  $service=service_state($user,$date);if(!$service['employed']||!$service['attendance']){$day['configured']=true;$day['note']=$service['employed']?'معفى من الحضور':'خارج فترة الخدمة';if($service['employed']&&($exc=approved_day($uid,$date))){$day['leave']=$exc['module'];if($exc['module']==='sick')foreach(decode($exc['body_json'])['_sick_days']??[]as $sd)if($sd['date']===$date)$day['sick_paid']=$sd['paid'];}$out[]=$day;continue;}
  $hist=array_values(array_filter($history32['attendance']??[],fn($h)=>$h['period_key']===$date));
  if($hist){$v=decode($hist[0]['body_json']);foreach(['late_minutes','early_minutes','permission_minutes','missing','penalty_minutes','penalty_days']as $k)$day[$k]=(float)($v[$k]?:0);$day['configured']=true;$day['scheduled']=$v['status']!=='راحة';$day['absence']=$v['status']==='غياب';$day['leave']=in_array($v['status'],['إجازة','مرضي'],true)?$v['status']:null;if(!$day['absence']&&$day['missing']&&$p['forget_enabled']){$period=period_start($date,$p['forget_period']);if(!isset($counts[$period])){$counts[$period]=0;if($period<$from)foreach(attendance_report($uid,$period,(new DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d'))as $prev)if(!$prev['absence'])$counts[$period]+=$p['forget_unit']==='day'?($prev['missing']?1:0):$prev['missing'];}$counts[$period]+=$p['forget_unit']==='day'?1:$day['missing'];}$day['historical']=true;$day['note']='تاريخي: '.$v['status'].' — '.$v['note'];foreach(['attendance','checkout']as $type)if($v[$type]){$time=(new DateTimeImmutable($date.' '.$v[$type],$tz))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$day['events'][]=['event_type'=>$type,'received_at'=>$time,'accuracy'=>null,'latitude'=>null,'longitude'=>null,'photo_id'=>null,'evidence_id'=>null];}$out[]=$day;continue;}
  if(!$p['configured']){$day['note']='جدول الدوام غير معتمد';$out[]=$day;continue;}
  $scheduled=in_array((int)(new DateTimeImmutable($date))->format('w'),array_map('intval',$p['weekdays']),true);
  if($user['hired_on'] && $date<$user['hired_on'])$scheduled=false;
  if(in_array($date,$p['holiday_dates']??[],true))$scheduled=false;
  $day['scheduled']=$scheduled;
  $exc=approved_day($uid,$date);
  if($exc){$day['leave']=$exc['module'];if($exc['module']==='sick')foreach(decode($exc['body_json'])['_sick_days']??[]as $sd)if($sd['date']===$date)$day['sick_paid']=$sd['paid'];$out[]=$day;continue;}
  $start=new DateTimeImmutable($date.' '.$p['start'],$tz);$end=new DateTimeImmutable($date.' '.$p['end'],$tz);
  if($end<=$start)$end=$end->modify('+1 day');
  $in=null;$exit=null;$permission=null;
  foreach($events as $e){
   $time=new DateTimeImmutable($e['received_at'],new DateTimeZone('UTC'));
   if($e['event_type']==='attendance')$in=$time;
   if($e['event_type']==='checkout')$exit=$time;
   if($e['event_type']==='permission_exit')$permission=$time;
   if($e['event_type']==='permission_return'&&$permission){$day['permission_minutes']+=(int)floor(($time->getTimestamp()-$permission->getTimestamp())/60);$permission=null;}
  }
  $day['unclosed_permission']=$permission!==null;
  if($in)$day['late_minutes']=max(0,(int)ceil(($in->getTimestamp()-$start->getTimestamp())/60));
  if($exit){$day['early_minutes']=max(0,(int)ceil(($end->getTimestamp()-$exit->getTimestamp())/60));$day['overtime_minutes']=max(0,(int)floor(($exit->getTimestamp()-$end->getTimestamp())/60));}
  if(function_exists('departure_apply199'))departure_apply199($day,$releases199[$date]??[],$exit);
  if(!$scheduled){$day['late_minutes']=0;$day['early_minutes']=0;}
  $month397=substr($date,0,7);$day['late_raw_minutes397']=$day['late_minutes'];$remaining397=max(0,(int)$p['grace_minutes']-($graceUsed397[$month397]??0));$day['grace_applied_minutes397']=min($day['late_minutes'],$remaining397);$graceUsed397[$month397]=($graceUsed397[$month397]??0)+$day['grace_applied_minutes397'];$day['late_minutes']-=$day['grace_applied_minutes397'];
  if($scheduled&&time()>$end->getTimestamp()){
   if(!$in&&!$exit){$day['absence']=true;$day['missing']=2;}
   else $day['missing']=($in?0:1)+($exit?0:1);
   if(!isset($p['punch_rules39'])&&$day['missing']&&!$day['absence']&&$p['forget_enabled']){
    $period=period_start($date,$p['forget_period']);
    if(!isset($counts[$period])){
     $counts[$period]=0;
     if($period<$from){
      foreach(attendance_report($uid,$period,(new DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d'))as $prev)
       if(!$prev['absence'])$counts[$period]+=$p['forget_unit']==='day'?($prev['missing']?1:0):$prev['missing'];
     }
    }
    $units=$p['forget_unit']==='day'?1:$day['missing'];
    for($i=0;$i<$units;$i++){
     $sequence=++$counts[$period];$minutes=(int)$p['missing_minutes'];$days=0.0;
     $tiers=($p['forget_escalation_enabled']??!empty($p['forget_tiers']))?$p['forget_tiers']:[];usort($tiers,fn($a,$b)=>(int)$a['from']<=>(int)$b['from']);
     foreach($tiers as $tier)if($sequence>=(int)$tier['from']){
      $days=(float)$tier['days'];if($p['penalty_mode']==='replace')$minutes=0;
     }
     $day['penalty_minutes']+=$minutes;$day['penalty_days']+=$days;
    }
   }
  }
  $day['missing_raw194']=$day['missing'];$day['absence_raw194']=$day['absence'];
  $day=punch_apply39($uid,$day,$p,$start->getTimestamp(),$end->getTimestamp(),$punchCounts39,$from,$out,$decisions39[$date]??[]);
  $out[]=$day;
 }
 if(function_exists('presence_apply_report'))$out=presence_apply_report($uid,$out,$from);
 unset($GLOBALS['REPORT_POLICIES32'][$uid],$GLOBALS['REPORT_LEAVE32'][$uid],$GLOBALS['REPORT_HISTORY32'][$uid]);return array_values(array_filter($out,fn($d)=>$d['date']>=$requestedFrom397));
}

function leave_balance(int $uid,array $cfg,?string $reference=null):array {
 $reference??=local_time()->format('Y-m-d');if(!empty($cfg['upgrade_v26']))return v26_leave_balance($uid,$cfg,$reference);$balance=leave_balance_days($uid,$cfg,$reference);
 require_once __DIR__.'/salary.php';$user=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();
 $row=false;foreach(query("SELECT period_key,body_json FROM ep_history WHERE kind='salary_history' AND employee_code=? AND period_key<=? ORDER BY period_key DESC,id DESC",[employee_key($user),$reference])->fetchAll()as $candidate){$until=decode($candidate['body_json'])['effective_to']??null;if(!$until||$until>=$reference){$row=$candidate;break;}}
 $salary=$row?(float)decode($row['body_json'])['salary']:null;$divisor=(float)($cfg['salary_divisor']??30);
 return $balance+['valuation_date'=>$reference,'valuation_salary'=>$salary,'salary_effective_from'=>$row['period_key']??null,'cash_value'=>$salary!==null&&$balance['cash_days']!==null&&$divisor>0?round($balance['cash_days']*$salary/$divisor,3):null];
}
