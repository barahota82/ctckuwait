<?php
declare(strict_types=1);
function v26_reports_action(string $action,array $u,array $b):array {
 if($action==='v26_report_detail'){v26_permission($u,'analytics');$target=(int)($_GET['user_id']??0);if(!can_view($u,$target))fail('الموظف خارج نطاقك',403);$from=valid_date($_GET['from']??null);$to=valid_date($_GET['to']??null);if(!$from||!$to||$to<$from||$to>(new DateTimeImmutable($from))->modify('+1 year')->format('Y-m-d'))fail('اختر فترة لا تزيد عن سنة');$staff=employee_required($target);$details=['attendance'=>attendance_report($target,$from,$to),'leave_history'=>[],'cash_history'=>[],'leave_requests'=>[],'sick_history'=>[]];foreach(query("SELECT body_json FROM ep_history WHERE employee_code=? AND kind='sick_summary'",[employee_key($staff)])->fetchAll()as $h)foreach(decode($h['body_json'])['periods']??[]as $period)if($period['from']<=$to&&$period['to']>=$from)$details['sick_history'][]=$period;foreach(historical_for($target,'leave_history','0000-01-01','9999-12-31')as $h){$v=decode($h['body_json']);if($v['from']<=$to&&$v['to']>=$from)$details['leave_history'][]=$v;}foreach(historical_for($target,'leave_settlement',$from,$to)as $h){$v=decode($h['body_json']);if($u['role']!=='admin'&&!permission_has($u,'payroll','view'))unset($v['amount']);$details['cash_history'][]=$v;}foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='leave' AND date_from<=? AND date_to>=? ORDER BY date_from",[$target,$to,$from])->fetchAll()as $rec)$details['leave_requests'][]=['from'=>$rec['date_from'],'to'=>$rec['date_to'],'status'=>$rec['status'],'details'=>decode($rec['body_json'])];return ['name'=>$staff['name'],'code'=>employee_key($staff),'details'=>$details];}
 if($action==='v26_audit'){admin_only($u);$from=valid_date($_GET['from']??null)??'2000-01-01';$to=valid_date($_GET['to']??null)??local_time()->format('Y-m-d');$rows=query('SELECT TOP (2000) a.*,u.name AS actor_name FROM ep_audit a LEFT JOIN ep_users u ON u.id=a.user_id WHERE a.created_at>=? AND a.created_at<? AND (?=0 OR a.user_id=?) AND (?=\'\' OR a.action=?) ORDER BY a.id DESC',[(new DateTimeImmutable($from,new DateTimeZone('Asia/Kuwait')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),(new DateTimeImmutable($to,new DateTimeZone('Asia/Kuwait')))->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),(int)($_GET['actor']??0),(int)($_GET['actor']??0),(string)($_GET['action_type']??''),(string)($_GET['action_type']??'')])->fetchAll();return array_values(array_filter($rows,fn($r)=>(empty($_GET['actor'])||(int)$r['user_id']===(int)$_GET['actor'])&&(empty($_GET['action_type'])||$r['action']===$_GET['action_type'])));}
 v26_permission($u,'analytics');$from=valid_date($_GET['from']??null)??substr(local_time()->format('Y-m-d'),0,4).'-01-01';$to=valid_date($_GET['to']??null)??local_time()->format('Y-m-d');if($to<$from||$to>(new DateTimeImmutable($from))->modify('+1 year')->format('Y-m-d'))fail('اختر فترة لا تزيد عن سنة');$GLOBALS['EP_SCREEN']='analytics';$ids=scope_ids($u);$rows=[];
 $scopeMap=array_fill_keys(array_map('intval',$ids),true);$reportStaff=array_values(array_filter(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll(),fn($s)=>isset($scopeMap[(int)$s['id']])&&(empty($_GET['user_id'])||(int)$s['id']===(int)$_GET['user_id'])));service_prefetch($reportStaff);
 foreach($reportStaff as $s){$st=service_state($s,$to);
  $balance=v26_leave_balance((int)$s['id'],settings(),$to);$report=attendance_report((int)$s['id'],$from,$to);$data=['user_id'=>$s['id'],'name'=>$s['name'],'code'=>employee_key($s),'department'=>($_GET['department']??'')?:($st['department']??$s['department']),'company'=>($_GET['company']??'')?:($st['company_code']??''),'residence_company'=>$st['residence_company_code']??'','service_days'=>0,'unconfigured_days'=>0,'recovery_received'=>0.0,'bank_paid'=>0.0,'leave_cash_paid'=>0.0,'historic_cash_days'=>0.0,'historic_cash_amount'=>0.0,'scheduled_days'=>0,'presence_days'=>0,'absence_days'=>0,'sick_days'=>0,'late_minutes'=>0,'early_minutes'=>0,'permission_minutes'=>0,'permission_deduct_minutes39'=>0,'permission_exempt_minutes39'=>0,'missing_punches'=>0,'leave_days'=>0,'remaining_days'=>$balance['remaining'],'cash_days'=>$balance['cash_days'],'cash_total_days'=>$balance['cash_total_days'],'cash_reserved_days'=>$balance['cash_reserved_days'],'cash_value'=>$balance['cash_value'],'excess_days'=>0,'late_return_days'=>0,'salary_net_approved'=>0.0,'bank_approved'=>0.0,'recovery_due'=>0.0,'payments_recorded'=>0.0,'cash_shortfall'=>$balance['cash_shortfall'],'financial_accounts_unreconciled'=>(int)empty($balance['cash_ledger_valid']),'unconfirmed_bank'=>!empty(decode($s['profile_json'])['payment_unconfirmed'])];
  foreach($report as $d){$dated=service_state($s,$d['date']);if((!empty($_GET['company'])&&($dated['company_code']??'')!==$_GET['company'])||(!empty($_GET['department'])&&($dated['department']??$s['department'])!==$_GET['department']))continue;if(!$d['configured'])$data['unconfigured_days']++;if($dated['employed'])$data['service_days']++;$data['scheduled_days']+=(int)$d['scheduled'];$data['absence_days']+=(int)$d['absence'];$data['sick_days']+=(int)($d['leave']==='sick');foreach(['late_minutes','early_minutes','permission_minutes']as $k)$data[$k]+=$d[$k];$data['permission_deduct_minutes39']+=$d['permission_deduct_minutes39']??0;$data['permission_exempt_minutes39']+=$d['permission_exempt_minutes39']??0;$data['missing_punches']+=$d['missing'];if(array_filter($d['events'],fn($e)=>$e['event_type']==='attendance'))$data['presence_days']++;}
  foreach($balance['leave_details']as $l){$a=max($from,$l['from']);$z=min($to,$l['actual_end']);if($z>=$a)foreach(days_between($a,$z)as $day){$dated=service_state($s,$day);if((!empty($_GET['company'])&&($dated['company_code']??'')!==$_GET['company'])||(!empty($_GET['department'])&&($dated['department']??$s['department'])!==$_GET['department']))continue;$data['leave_days']++;$data['excess_days']+=(float)($l['excess_by_date'][$day]??0);if($day>$l['planned_end'])$data['late_return_days']++;}}
  foreach(query("SELECT net_amount,calculation_json,month_key FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>=? AND month_key<=?",[$s['id'],substr($from,0,7),substr($to,0,7)])->fetchAll()as $r){$dated=service_state($s,(new DateTimeImmutable($r['month_key'].'-01'))->format('Y-m-t'));$calc=decode($r['calculation_json']);if((!empty($_GET['company'])&&($calc['work_company_code']??$dated['company_code']??'')!==$_GET['company'])||(!empty($_GET['department'])&&($calc['department']??$dated['department']??$s['department'])!==$_GET['department']))continue;$data['salary_net_approved']+=(float)$r['net_amount'];}foreach(query("SELECT * FROM ep_bank_payroll WHERE user_id=? AND status='approved' AND month_key>=? AND month_key<=?",[$s['id'],substr($from,0,7),substr($to,0,7)])->fetchAll()as $r){$snap=decode($r['snapshot_json']);$dated=service_state($s,(new DateTimeImmutable($r['month_key'].'-01'))->format('Y-m-t'));if((!empty($_GET['company'])&&($snap['calculation']['work_company_code']??$dated['company_code']??'')!==$_GET['company'])||(!empty($_GET['department'])&&($snap['calculation']['department']??$dated['department']??$s['department'])!==$_GET['department']))continue;$data['bank_approved']+=(float)$r['amount'];$data['recovery_due']+=(float)(decode($r['snapshot_json'])['recovery_due']??0);}
  foreach(query("SELECT amount,body_json,date_from FROM ep_records WHERE user_id=? AND module='payment' AND status='approved' AND date_from>=? AND date_from<=?",[$s['id'],$from,$to])->fetchAll()as $r){$dated=service_state($s,$r['date_from']);if((!empty($_GET['company'])&&($dated['company_code']??'')!==$_GET['company'])||(!empty($_GET['department'])&&($dated['department']??$s['department'])!==$_GET['department']))continue;$kind=decode($r['body_json'])['kind'];if($kind==='recovery')$data['recovery_received']+=(float)$r['amount'];else $data['payments_recorded']+=(float)$r['amount'];if($kind==='bank')$data['bank_paid']+=(float)$r['amount'];if($kind==='leave_cash')$data['leave_cash_paid']+=(float)$r['amount'];else foreach(decode($r['body_json'])['leave_allocations']??[]as $allocation)$data['leave_cash_paid']+=(float)$allocation['amount'];}foreach(historical_for((int)$s['id'],'leave_settlement',$from,$to)as $history){$cash=decode($history['body_json']);$dated=service_state($s,$cash['date']);if((!empty($_GET['company'])&&($dated['company_code']??'')!==$_GET['company'])||(!empty($_GET['department'])&&($dated['department']??$s['department'])!==$_GET['department']))continue;$data['historic_cash_days']+=(float)$cash['days'];$data['historic_cash_amount']+=(float)$cash['amount'];}$data=array_replace($data,bank_balances32($s,$from,$to,(string)($_GET['company']??''),(string)($_GET['department']??'')));$data['monthly_presence_average']=$data['presence_days']/max(1,((int)substr($to,0,4)-(int)substr($from,0,4))*12+(int)substr($to,5,2)-(int)substr($from,5,2)+1);$data['alert']=$balance['remaining']>=settings()['leave_alert_high']?'high':($balance['remaining']>=settings()['leave_alert_first']?'due':'none');$data['evidence_note']=$st['attendance']?'الأيام الفعلية مثبتة ببصمة حضور فقط':'معفى من البصمة؛ الخدمة لا تعني إثبات أيام العمل الفعلي';if($u['role']!=='admin'&&!permission_has($u,'payroll','view'))foreach(['cash_value','salary_net_approved','bank_approved','recovery_due','payments_recorded','recovery_received','bank_paid','leave_cash_paid','historic_cash_amount','recovery_outstanding','bank_outstanding','bank_opening','recovery_opening']as $private)unset($data[$private]);if((!empty($_GET['company'])||!empty($_GET['department']))&&!$data['service_days'])continue;$rows[]=$data;
 }
 usort($rows,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));$totals=[];$groups=['companies'=>[],'departments'=>[]];foreach($rows as $r)foreach($r as $k=>$v)if(is_numeric($v)&&!in_array($k,['user_id','monthly_presence_average'],true)){$totals[$k]=($totals[$k]??0)+(float)$v;foreach(['companies'=>'company','departments'=>'department']as $group=>$field){$name=$r[$field]?:'غير محدد';$groups[$group][$name][$k]=($groups[$group][$name][$k]??0)+(float)$v;}}foreach($rows as $row)if($row['cash_days']===null){$totals['cash_days']=null;$totals['cash_total_days']=null;if(array_key_exists('cash_value',$totals))$totals['cash_value']=null;foreach(['companies'=>'company','departments'=>'department']as $group=>$field){$name=$row[$field]?:'غير محدد';$groups[$group][$name]['cash_days']=null;$groups[$group][$name]['cash_total_days']=null;if(array_key_exists('cash_value',$groups[$group][$name]))$groups[$group][$name]['cash_value']=null;}}return ['from'=>$from,'to'=>$to,'rows'=>$rows,'totals'=>$totals,'groups'=>$groups,'note'=>'الأرصدة حتى نهاية الفترة؛ الحضور داخل الفترة. الأرقام لا تثبت دفعًا لم يسجل أو عملًا ميدانيًا مستمرًا.'];
}

function v26_report_labels():array{return ['bank_opening'=>'رصيد التحويل الافتتاحي د.ك','recovery_opening'=>'رصيد الاسترداد الافتتاحي د.ك','financial_accounts_unreconciled'=>'حسابات مالية غير محسومة','user_id'=>'معرف السجل','code'=>'كود الموظف','name'=>'الموظف','department'=>'القسم','company'=>'شركة العمل','residence_company'=>'شركة الإقامة','service_days'=>'أيام الخدمة','unconfigured_days'=>'أيام بدون جدول دوام معتمد','scheduled_days'=>'أيام الدوام المجدولة','presence_days'=>'أيام الحضور المثبت','absence_days'=>'أيام الغياب المثبت','sick_days'=>'أيام المرضية','late_minutes'=>'دقائق التأخير','early_minutes'=>'دقائق الانصراف المبكر','permission_minutes'=>'دقائق الأذونات','permission_deduct_minutes39'=>'دقائق الخروج المخصومة','permission_exempt_minutes39'=>'دقائق الخروج المعفاة','missing_punches'=>'البصمات المنسية','leave_days'=>'أيام الإجازات الفعلية','remaining_days'=>'رصيد أيام الإجازة','cash_days'=>'المتاح للصرف أيام','cash_total_days'=>'الرصيد المالي الكلي أيام','cash_reserved_days'=>'المحجوز للصرف أيام','cash_value'=>'قيمة الرصيد المالي د.ك','excess_days'=>'أيام الزيادة عن الرصيد','late_return_days'=>'أيام التخلف عن العودة','salary_net_approved'=>'صافي الرواتب المعتمدة د.ك','bank_approved'=>'التحويلات المعتمدة د.ك','recovery_due'=>'فرق التحويل المطلوب استرداده د.ك','payments_recorded'=>'المدفوعات المثبتة د.ك','cash_shortfall'=>'صرف تاريخي يحتاج مراجعة بالأيام','unconfirmed_bank'=>'طريقة استلام غير محددة','recovery_received'=>'الفرق المسترد فعليًا د.ك','bank_paid'=>'التحويل المنفذ فعليًا د.ك','leave_cash_paid'=>'صرف الإجازات المنفذ د.ك','historic_cash_days'=>'أيام الرصيد المالي المصروفة تاريخيًا','historic_cash_amount'=>'مبالغ الإجازات المصروفة تاريخيًا د.ك','recovery_outstanding'=>'فرق تحويل لم يسترد د.ك','bank_outstanding'=>'تحويل معتمد غير مثبت دفعه د.ك','monthly_presence_average'=>'متوسط أيام الحضور المثبت شهريًا','alert'=>'تنبيه رصيد الإجازات','evidence_note'=>'دلالة بيانات الحضور'];}

function attendance_today_required200(array $day): bool {
 $note=(string)($day['note']??'');
 if(str_contains($note,'خارج فترة')||str_contains($note,'معفى')||str_contains($note,'غير خاضع')||str_contains($note,'قبل بدء'))return false;
 if(empty($day['configured'])||str_contains($note,'غير معتمد'))return false;
 if(!empty($day['leave']))return false;
 return !empty($day['scheduled']);
}
function attendance_today_status200(array $day): string {
 $note=(string)($day['note']??'');$state=event_state($day['events']??[]);
 if($state==='working'||$state==='away')return $state;
 if($state==='closed')return 'left';
 $leave=(string)($day['leave']??'');
 if($leave==='sick'||$leave==='مرضي')return 'sick';
 if($leave!=='')return 'leave';
 if(str_contains($note,'خارج فترة'))return 'service';
 if(str_contains($note,'معفى')||str_contains($note,'غير خاضع'))return 'exempt';
 if(str_contains($note,'قبل بدء'))return 'before';
 if(empty($day['configured'])||str_contains($note,'غير معتمد'))return 'unconfigured';
 if(empty($day['scheduled']))return 'rest';
 return 'missing';
}
function attendance_today_exit_clock200(string $stamp): ?DateTimeImmutable {
 if(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',$stamp))return null;
 $value=strlen($stamp)===16?$stamp.':00':$stamp;
 $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s',$value,new DateTimeZone(settings()['timezone']));
 return $date&&$date->format('Y-m-d\TH:i:s')===$value?$date:null;
}
function attendance_today_open_exit200(int $uid,array $day): ?array {
 if(event_state($day['events']??[])!=='away')return null;
 $open=null;
 foreach($day['events']??[] as $event){
  $type=(string)($event['event_type']??'');
  if($type==='permission_exit')$open=$event;
  elseif($type==='permission_return')$open=null;
 }
 if(!$open||(string)($open['event_type']??'')!=='permission_exit')return null;
 if(isset($open['user_id'])&&(int)$open['user_id']!==$uid)return null;
 $date=(string)($day['date']??'');
 if($date!==''&&isset($open['work_date'])&&(string)$open['work_date']!==$date)return null;
 return $open;
}
function attendance_today_same_exit200(?DateTimeImmutable $exit,array $event): bool {
 if(!$exit||empty($event['received_at']))return false;
 $local=(new DateTimeImmutable((string)$event['received_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(settings()['timezone']));
 return $exit->format('Y-m-d\TH:i')===$local->format('Y-m-d\TH:i');
}
function attendance_today_permission_agree200(array $rows): ?array {
 if(!$rows)return null;
 $until=null;
 foreach($rows as $body){
  $stamp=(string)($body['ends_at']??'');
  if($until===null)$until=$stamp;
  elseif($until!==$stamp)return null;
 }
 return $rows[0];
}
function attendance_today_permission_for200(int $uid,array $day,array $candidates): ?array {
 $open=attendance_today_open_exit200($uid,$day);if(!$open)return null;
 $eventId=(int)($open['id']??0);$linked=[];$timed=[];
 foreach($candidates as $body){
  if(!empty($body['cancelled_at'])||empty($body['exited_at'])||!empty($body['returned_at'])||($body['decision']??'approved')==='rejected')continue;
  if(isset($body['user_id'])&&(int)$body['user_id']!==$uid)continue;
  $id=(int)($body['exit_event_id']??0);
  if($id>0){if($eventId>0&&$id===$eventId)$linked[]=$body;continue;}
  if(attendance_today_same_exit200(attendance_today_exit_clock200((string)$body['exited_at']),$open))$timed[]=$body;
 }
 if($linked)return attendance_today_permission_agree200($linked);
 return attendance_today_permission_agree200($timed);
}
function attendance_today_permission200(array $day,?array $permission): array {
 if(event_state($day['events']??[])!=='away')return ['open'=>false,'until'=>''];
 $until='';
 if($permission&&empty($permission['returned_at'])&&empty($permission['cancelled_at'])&&!empty($permission['exited_at'])){
  if(preg_match('/T(\d{2}:\d{2})/',(string)($permission['ends_at']??''),$match))$until=$match[1];
 }
 return ['open'=>true,'until'=>$until];
}
function attendance_today200(array $actor): array {
 admin_only($actor);
 if(personal_mode34($actor))fail('هذه العملية تخص مساحة العمل.',403);
 $now=local_time();$today=$now->format('Y-m-d');
 $people=query("SELECT * FROM ep_users WHERE role<>'admin' ORDER BY id")->fetchAll();
 service_prefetch($people);departure_prefetch199($people,$now);
 try{
 $byDate=[];
 foreach($people as $person)$byDate[departure_context199($person,$now)['work_date']][]=$person;
 $days=[];
 foreach($byDate as $date=>$group){
  attendance_prefetch113($group,$date,$date);
  foreach($group as $person){
   $report=attendance_report((int)$person['id'],$date,$date);
   $days[(int)$person['id']]=$report[0]??['date'=>$date,'events'=>[],'configured'=>false,'scheduled'=>false,'note'=>'جدول الدوام غير معتمد'];
  }
 }
 $candidates=[];
 $ids=array_map(fn($person)=>(int)$person['id'],$people);
 foreach(array_chunk($ids,500) as $chunk){
  if(!$chunk)continue;
  $marks=implode(',',array_fill(0,count($chunk),'?'));
  foreach(query("SELECT user_id,body_json FROM ep_records WHERE module='exit_permission194' AND status='approved' AND user_id IN ($marks)",$chunk)->fetchAll() as $row){
   $body=decode($row['body_json']);
   if(!empty($body['cancelled_at'])||empty($body['exited_at'])||!empty($body['returned_at'])||($body['decision']??'approved')==='rejected')continue;
   $candidates[(int)$row['user_id']][]=$body;
  }
 }
 $rows=[];
 foreach($people as $person){
  $id=(int)$person['id'];$day=$days[$id];$profile=decode($person['profile_json']??'{}');
  $permission=attendance_today_permission200($day,attendance_today_permission_for200($id,$day,$candidates[$id]??[]));
  $english=trim((string)($profile['name_en']??''));
  $rows[]=['user_id'=>$id,'id'=>$id,'name'=>$person['name'],'employee_name'=>$person['name'],'name_en'=>$english,'profile'=>['name_en'=>$english],'employee_code'=>employee_key($person),'staff_type'=>$person['staff_type']??'office','manager_id'=>(int)($person['manager_id']??0),'required'=>attendance_today_required200($day),'attended'=>(bool)array_filter($day['events']??[],fn($event)=>($event['event_type']??'')==='attendance'),'status_key'=>attendance_today_status200($day),'permission_open'=>$permission['open'],'permission_until'=>$permission['until'],'work_date'=>$day['date']??$today,'day'=>$day];
 }
 usort($rows,fn($a,$b)=>strnatcasecmp($a['employee_code'],$b['employee_code'])?:($a['user_id']<=>$b['user_id']));
 $requiredRows=array_values(array_filter($rows,fn($row)=>!empty($row['required'])));
 $count=fn($predicate)=>count(array_filter($requiredRows,$predicate));
 $summary=['required'=>count($requiredRows),'present'=>$count(fn($row)=>!empty($row['attended'])),'missing'=>$count(fn($row)=>empty($row['attended'])),'working'=>$count(fn($row)=>$row['status_key']==='working'),'away'=>$count(fn($row)=>$row['status_key']==='away'),'left'=>$count(fn($row)=>$row['status_key']==='left')];
 $request=page_request195()??['size'=>'50','page'=>0,'filters'=>[]];
 $filters=$request['filters'];$employee=(int)($filters['employee']??0);$others=(string)($filters['others']??'')==='1';
 $visible=array_values(array_filter($rows,fn($row)=>$employee>0?(int)$row['user_id']===$employee:($others||!empty($row['required']))));
 $window=page_rows195($visible,$request);
 $peopleOut=array_map(fn($row)=>['id'=>$row['user_id'],'user_id'=>$row['user_id'],'name'=>$row['name'],'name_en'=>$row['name_en'],'employee_code'=>$row['employee_code'],'profile'=>['name_en'=>$row['name_en']]],$rows);
 return ['date'=>$today,'server_time'=>utc_now(),'summary'=>$summary,'people'=>$peopleOut,'rows'=>$window['rows195'],'page195'=>$window['page195']];
 }finally{unset($GLOBALS['DEPARTURE_INPUT199']);foreach($people as $person)unset($GLOBALS['REPORT_POLICIES32'][(int)$person['id']]);}
}
