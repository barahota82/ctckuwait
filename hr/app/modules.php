<?php
declare(strict_types=1);
function modules(): array {return [
 'leave'=>['label'=>'الإجازات','employee'=>true,'approval'=>true,'fields'=>['leave_type'=>'نوع الإجازة','reason'=>'السبب']],
 'sick'=>['label'=>'المرضيات','employee'=>true,'approval'=>true,'fields'=>['reason'=>'ملاحظات المرضية']],
 'external'=>['label'=>'انصراف من الخارج','employee'=>true,'approval'=>true,'fields'=>['reason'=>'السبب','customer_area'=>'منطقة المهمة']],
 'request'=>['label'=>'طلبات الموظفين','employee'=>true,'approval'=>true,'fields'=>['request_type'=>'نوع الطلب','details'=>'التفاصيل']],
 'leave_cash'=>['label'=>'تسويات الإجازة النقدية','employee'=>false,'approval'=>true,'fields'=>['days'=>'عدد أيام التسوية','payroll_month'=>'شهر الإضافة للراتب YYYY-MM','reason'=>'السبب']],
 'loan'=>['label'=>'السلف والقروض','employee'=>true,'approval'=>true,'fields'=>['installment'=>'القسط الشهري','payroll_month'=>'أول شهر قسط YYYY-MM','reason'=>'السبب']],
 'custody'=>['label'=>'العهد','employee'=>false,'approval'=>false,'fields'=>['asset_type'=>'نوع العهدة','serial'=>'الرقم / اللوحة','handover'=>'بيان التسليم','return_date'=>'تاريخ الاسترجاع']],
 'document'=>['label'=>'المستندات والعقود','employee'=>false,'approval'=>false,'fields'=>['document_type'=>'نوع المستند','document_number'=>'الرقم','expiry'=>'تاريخ الانتهاء']],
 'discipline'=>['label'=>'الإنذارات والقرارات','employee'=>false,'approval'=>true,'fields'=>['decision_type'=>'نوع القرار','reason'=>'السبب','employee_response'=>'رد الموظف','payroll_month'=>'شهر تطبيق المبلغ YYYY-MM']],
 'evaluation'=>['label'=>'التقييمات','employee'=>false,'approval'=>false,'fields'=>['score'=>'التقييم من 100','criteria'=>'المعايير','strengths'=>'نقاط القوة','improvement'=>'خطة التحسين']],
 'employment'=>['label'=>'إجراءات التوظيف والخروج','employee'=>false,'approval'=>false,'fields'=>['stage'=>'الإجراء','checklist'=>'قائمة الإجراءات','details'=>'التفاصيل']],
 'adjustment'=>['label'=>'بدلات ومكافآت الرواتب','employee'=>false,'approval'=>true,'fields'=>['payroll_month'=>'شهر التطبيق YYYY-MM','reason'=>'السبب']],
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
function approved_day(int $uid,string $date): ?array {
 return query("SELECT TOP (1) * FROM ep_records WHERE user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? AND date_to>=? ORDER BY id",[$uid,$date,$date])->fetch()?:null;
}
function original_leave_balance(int $uid,array $cfg,?string $reference=null): array {
 $u=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();if(!$u)fail('الموظف غير موجود.');
 $copy=$cfg;$copy['sick_year']=$cfg['leave_year'];
 [$from,$to]=sick_year_bounds($reference??local_time()->format('Y-m-d'),$u,$copy);
 $used=0;$pending=0;
 foreach(query("SELECT * FROM ep_records WHERE module='leave' AND user_id=? AND date_to>=? AND date_from<=? AND status IN ('approved','pending','revision')",[$uid,$from,$to])->fetchAll()as $r){
  foreach(days_between($r['date_from'],$r['date_to'])as $d)if($d>=$from&&$d<=$to){if($r['status']==='approved')$used++;else$pending++;}
 }
 $profile=decode($u['profile_json']);$entitlement=$profile['leave_entitlement']??$cfg['annual_leave_days'];
 return ['year_from'=>$from,'year_to'=>$to,'entitlement'=>$entitlement,'used'=>$used,'pending'=>$pending,'remaining'=>$entitlement===null?null:max(0,(float)$entitlement-$used)];
}
function leave_balance(int $uid,array $cfg,?string $reference=null):array {
 $reference??=local_time()->format('Y-m-d');$base=original_leave_balance($uid,$cfg,$reference);$u=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();$profile=decode($u['profile_json']);
 $h=query("SELECT TOP (1) * FROM ep_history WHERE kind='leave_balance' AND employee_code=? AND period_key<=? ORDER BY period_key DESC",[$u['username'],$reference])->fetch();
 if(!$h)return $base+['cash_days'=>null,'opening_as_of'=>null,'accrued'=>0];
 $b=decode($h['body_json']);$opening=$b['as_of'];$used=0;$pending=0;
 foreach(query("SELECT * FROM ep_records WHERE module='leave' AND user_id=? AND date_to>? AND date_from<=? AND status IN ('approved','pending','revision')",[$uid,$opening,$reference])->fetchAll()as $rec)foreach(days_between($rec['date_from'],$rec['date_to'])as $date)if($date>$opening&&$date<=$reference){if($rec['status']==='approved')$used++;else $pending++;}
 $entitlement=$b['annual_entitlement']!==''?(float)$b['annual_entitlement']:($profile['leave_entitlement']??$cfg['annual_leave_days']);$accrued=0.0;
 if(($cfg['leave_accrual_mode']??'annual')==='daily'&&$entitlement!==null){$last=$profile['ended_on']??'';$end=$last&&$last<$reference?$last:$reference;$first=max($opening,$u['hired_on']??$opening);$n=max(0,(int)(new DateTimeImmutable($first))->diff(new DateTimeImmutable($end))->format('%r%a'));if(!empty($cfg['leave_exclude_approved_days']))$n=max(0,$n-$used);$accrued=$n*(float)$entitlement/max(1,(float)($cfg['leave_accrual_divisor']??365));}
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
function attendance_report(int $uid,string $from,string $to): array {
 $user=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();if(!$user)fail('الموظف غير موجود.');
 $cfg=settings();$tz=new DateTimeZone($cfg['timezone']);$out=[];$counts=[];
 foreach(days_between($from,$to)as $date){
  $policy=policy_for($uid,$date);$p=$policy['values'];
  $events=query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$date])->fetchAll();
  $day=['date'=>$date,'policy_id'=>$policy['id'],'configured'=>(bool)$p['configured'],'events'=>$events,'late_minutes'=>0,'early_minutes'=>0,'permission_minutes'=>0,'unclosed_permission'=>false,'missing'=>0,'penalty_minutes'=>0,'penalty_days'=>0,'absence'=>false,'sick_paid'=>null,'leave'=>null,'scheduled'=>false,'overtime_minutes'=>0];
  $hist=historical_for($uid,'attendance',$date,$date);
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
  if($in)$day['late_minutes']=max(0,(int)ceil(($in->getTimestamp()-$start->getTimestamp())/60)-(int)$p['grace_minutes']);
  if($exit){$day['early_minutes']=max(0,(int)ceil(($end->getTimestamp()-$exit->getTimestamp())/60));$day['overtime_minutes']=max(0,(int)floor(($exit->getTimestamp()-$end->getTimestamp())/60));}
  if(!$scheduled){$day['late_minutes']=0;$day['early_minutes']=0;}
  if($scheduled&&time()>$end->getTimestamp()){
   if(!$in&&!$exit){$day['absence']=true;$day['missing']=2;}
   else $day['missing']=($in?0:1)+($exit?0:1);
   if($day['missing']&&!$day['absence']&&$p['forget_enabled']){
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
     $tiers=$p['forget_tiers'];usort($tiers,fn($a,$b)=>(int)$a['from']<=>(int)$b['from']);
     foreach($tiers as $tier)if($sequence>=(int)$tier['from']){
      $days=(float)$tier['days'];if($p['penalty_mode']==='replace')$minutes=0;
     }
     $day['penalty_minutes']+=$minutes;$day['penalty_days']+=$days;
    }
   }
  }
  $out[]=$day;
 }
 return $out;
}
