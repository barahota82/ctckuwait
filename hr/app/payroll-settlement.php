<?php
declare(strict_types=1);
// V35: one monthly settlement, dated settings, immutable approved snapshots.
function pay35_month(string $month):string {
 if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))fail('حدد الشهر والسنة.');
 return (new DateTimeImmutable($month.'-01'))->format('Y-m-t');
}
function pay35_number(mixed $v,bool $integer=false):float {
 if(!is_numeric($v)||!is_finite((float)$v)||(float)$v<0||($integer&&floor((float)$v)!==(float)$v))fail('قيمة غير صالحة.');return (float)$v;
}
function pay35_rules(string $date):array {
 $r=query("SELECT TOP (1) * FROM ep_records WHERE module='payroll_rules35' AND status='approved' AND date_from<=? ORDER BY date_from DESC,id DESC",[$date])->fetch();
 return ($r?decode($r['body_json']):[])+['friday_lateness'=>false,'contracts_lateness'=>false,'daily_visible'=>false,'effective_from'=>$r['date_from']??null];
}
function pay35_attendance(array $u,string $date):bool {
 $s=service_state($u,$date);if(!$s['employed'])return false;if($s['attendance'])return true;
 if((new DateTimeImmutable($date))->format('w')!=='5')return false;
 $r=query("SELECT TOP (1) body_json FROM ep_records WHERE module='friday_access35' AND user_id=? AND status='approved' AND date_from<=? ORDER BY date_from DESC,id DESC",[$u['id'],$date])->fetch();
 return $r&&!empty(decode($r['body_json'])['enabled']);
}
function pay35_inputs(int $uid,string $month):array {
 $r=query("SELECT TOP (1) * FROM ep_records WHERE module='payroll_input35' AND user_id=? AND date_from=? ORDER BY id DESC",[$uid,$month.'-01'])->fetch();
 return ($r?decode($r['body_json']):[])+['extra_days'=>0.0,'collection'=>0.0,'collection_entered'=>false,'revision'=>0,'sales_agent_deduction36'=>0.0,'collection_deduction36'=>0.0,'commission_deduction_note36'=>''];
}
function pay35_lock(int $uid,string $month):void {
 query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid]);
 if(query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key=? AND status IN ('approved','submitted')",[$uid,$month])->fetch())fail('الكشف معتمد أو قيد المراجعة؛ لا تعدل مصدره.');
 if(query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key>? AND status IN ('approved','submitted')",[$uid,$month])->fetch())fail('يوجد كشف لاحق معتمد أو قيد المراجعة؛ لا تغير مصادر فترة سابقة.');
 if(historical_for($uid,'payroll',$month,$month))fail('الشهر تاريخي محفوظ؛ لا يعاد حسابه.');
}
function pay35_invalidate(int $uid,string $month):void {
 foreach(query("SELECT * FROM ep_payroll WHERE user_id=? AND month_key=? AND status IN ('draft','returned')",[$uid,$month])->fetchAll()as $r){v26_payroll_version($r,current_user(),'source_changed','تغير مصدر حساب الشهر');$v=pay35_inputs($uid,$month);$v['debt_choice']=(float)(decode($r['calculation_json'])['debt_deduction']??0);$input=query("SELECT id FROM ep_records WHERE module='payroll_input35' AND user_id=? AND date_from=?",[$uid,$month.'-01'])->fetch();if($input)query('UPDATE ep_records SET body_json=? WHERE id=?',[jencode($v),$input['id']]);else v26_insert('payroll_input35',$uid,$month.'-01',null,$v,'approved');}
 query("DELETE FROM ep_payroll WHERE user_id=? AND month_key=? AND status IN ('draft','returned')",[$uid,$month]);
 query("DELETE FROM ep_bank_payroll WHERE user_id=? AND month_key=? AND status='draft'",[$uid,$month]);
}
function pay35_scope(array $u,string $screen,string $op='view',?int $uid=null):array {
 if(personal_mode34($u))fail('هذه العملية تخص مساحة العمل.',403);
 permission_require($u,$screen,$op);$ids=permission_scope29($u,$screen,$op);
 if($uid!==null&&!in_array($uid,$ids,true))fail('الموظف خارج نطاق الصلاحية.',403);return $ids;
}
function pay35_uniform_salary201(array $calendar):array {
 $latest=null;foreach($calendar as $d){if(empty($d['employed']))continue;if($latest===null||strcmp((string)$d['date'],(string)$latest['date'])>=0)$latest=$d;}
 if(!$latest)return $calendar;foreach($calendar as &$d){if(empty($d['employed']))continue;$d['salary']=(float)$latest['salary'];$d['effective_from']=$latest['effective_from']??null;}unset($d);return $calendar;
}
function pay35_company_shares201(array $calendar,float $basic):array {
 $days=count($calendar);if($days<1)return [];$by=[];$employed=0;foreach($calendar as $d){if(empty($d['employed']))continue;$employed++;$code=(string)($d['company']??'');$by[$code]=($by[$code]??0)+1;}
 if($employed!==$days||count($by)<2)return [];$basic=round($basic,3);$shares=[];$used=0.0;$i=0;$n=count($by);
 foreach($by as $code=>$count){$i++;$salary=$i===$n?round($basic-$used,3):round($basic*$count/$days,3);$used=round($used+$salary,3);$shares[]=['company'=>$code,'calendar_days'=>$count,'working_days'=>round(26*$count/$days,6),'salary'=>$salary];}
 return $shares;
}
function pay35_reserved_leave201(int $uid,string $month):?array {
 foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='leave_cash' AND status='pending' ORDER BY id DESC",[$uid])->fetchAll()as $r){$b=decode($r['body_json']);if(($b['payroll_month']??'')===$month&&($b['_payroll201']['phase']??'')==='reserved')return ['id'=>(int)$r['id'],'days'=>(float)($b['days']??0),'body'=>$b];}
 return null;
}
// Pure calculation: full uninterrupted month=26; partial month=actual non-Fridays.
function pay35_basis_compute(array $calendar,float $extra,array $extraRates):array {
 $full=true;$eligible=[];$segments=[];
 foreach($calendar as $d){if(!$d['employed']||$d['leave'])$full=false;if($d['employed']&&!$d['leave']&&!$d['friday'])$eligible[]=$d;}
 $count=count($eligible);$weight=$full&&$count?26/$count:1.0;$basic=0.0;$amounts=array_fill_keys(['telephone','transport','housing','other'],0.0);
 foreach($eligible as $d){$basic+=$d['salary']/26*$weight;foreach($amounts as $k=>$v)$amounts[$k]+=($d['allowances'][$k]??0)/26*$weight;$segments[]=$d+['paid_days'=>$weight];}
 $baseRate=(float)$extraRates['salary']/26;$extraParts=['basic'=>$extra*$baseRate];foreach(['telephone','transport','housing']as $k)$extraParts[$k]=$extra*($extraRates['allowances'][$k]??0)/26;
 return ['salary'=>round($basic,3),'allowances'=>array_map(fn($v)=>round($v,3),$amounts),'working_days'=>$full&&$count?26:$count,'extra_days'=>$extra,'extra_amount'=>round(array_sum($extraParts),3),'extra_parts'=>$extraParts,'segments'=>$segments,'full_month'=>$full,'day_rate'=>$baseRate+array_sum(array_intersect_key($extraRates['allowances'],array_flip(['telephone','transport','housing'])))/26];
}
function pay35_basis(array $u,string $month,float $extra):array {
 $to=pay35_month($month);$calendar=[];$last=null;$id35=(int)$u['id'];$GLOBALS['REPORT_LEAVE32'][$id35]=query("SELECT * FROM ep_records WHERE user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? ORDER BY id",[$id35,$to])->fetchAll();$GLOBALS['REPORT_HISTORY32'][$id35]['leave_history']=historical_for($id35,'leave_history','0000-01-01','9999-12-31');
 foreach(days_between($month.'-01',$to)as $date){$s=service_state($u,$date);if(!$s['employed']){$calendar[]=['date'=>$date,'employed'=>false,'leave'=>false,'friday'=>(new DateTimeImmutable($date))->format('w')==='5','salary'=>0,'allowances'=>[],'company'=>''];continue;}$salary=salary_at($u,$date);$a=allowance_at($u,$date);$exc=approved_day((int)$u['id'],$date);$d=['date'=>$date,'employed'=>$s['employed'],'leave'=>$exc&&$exc['module']==='leave','friday'=>(new DateTimeImmutable($date))->format('w')==='5','salary'=>$salary['fixed_salary']?(float)$salary['salary']:0.0,'allowances'=>$a,'effective_from'=>$salary['effective_from']??null,'company'=>(string)($s['company_code']??'')];if($s['employed']){$last=$d;if($s['period_id']&&$salary['effective_from']<$s['from'])fail('سجل راتب فترة التعيين الحالية بتاريخ صحيح.');}$calendar[]=$d;}
 $calendar=pay35_uniform_salary201($calendar);unset($GLOBALS['REPORT_LEAVE32'][$id35],$GLOBALS['REPORT_HISTORY32'][$id35]);$computed=pay35_basis_compute($calendar,$extra,$last??['salary'=>0,'allowances'=>[]]);$computed['company_shares201']=!empty($computed['full_month'])?pay35_company_shares201($calendar,(float)($last['salary']??0)):[];return $computed+['reference_salary'=>$last['salary']??0,'reference_allowances'=>array_intersect_key($last['allowances']??[],array_flip(['telephone','transport','housing','other'])),'effective_from'=>$last['effective_from']??null,'rule'=>'26_full_actual_non_friday_partial'];
}
function pay35_fridays(array $u,string $month):array {
 $uid=(int)$u['id'];$legacy=friday_month($uid,$month);$rows=query('SELECT * FROM ep_events WHERE user_id=? AND work_date>=? AND work_date<=? ORDER BY received_at,id',[$uid,$month.'-01',pay35_month($month)])->fetchAll();$groups=[];foreach($rows as $r)if((new DateTimeImmutable($r['work_date']))->format('w')==='5')$groups[$r['work_date']][]=$r;
 $lines=[];$dates=[];$total=0;$flags=[];
 foreach($groups as $date=>$events){$in=null;$out=null;$permission=null;$away=0;foreach($events as $e){$at=(new DateTimeImmutable($e['received_at'],new DateTimeZone('UTC')))->getTimestamp();if($e['event_type']==='attendance')$in=$at;if($e['event_type']==='checkout')$out=$at;if($e['event_type']==='permission_exit')$permission=$at;if($e['event_type']==='permission_return'&&$permission!==null){$away+=max(0,$at-$permission);$permission=null;}}
 $complete=$in!==null&&$out!==null&&$out>$in&&$permission===null;$eligible=service_state($u,$date)['employed']&&!approved_day($uid,$date);$rate=friday_rate($date);$amount=$complete&&$eligible?$rate['rate']:0.0;if(!$complete)$flags[]=$date.': بصمة الجمعة غير مكتملة';if($amount){$dates[]=$date;$total+=$amount;}$lines[]=['date'=>$date,'hours'=>$complete?round(max(0,$out-$in-$away)/3600,3):0,'complete'=>$complete,'amount'=>$amount,'rate'=>$rate['rate'],'rule_id'=>$rate['rule_id'],'event_ids'=>array_column($events,'id')];}
 if(!empty($legacy['id'])){if($groups)$flags[]='توجد جمعات يدوية تاريخية وبصمات في الشهر نفسه؛ راجع المصدر قبل الاعتماد.';return $legacy+['flags'=>$flags,'source'=>'legacy_manual','evidence'=>$lines];}
 return ['amount'=>round($total,3),'dates'=>$dates,'lines'=>$lines,'flags'=>$flags,'source'=>'attendance_pairs'];
}
function pay35_contract_rows(int $uid,string $month):array {return query("SELECT * FROM ep_records WHERE module='contracts_daily35' AND user_id=? AND date_from>=? AND date_from<=? AND status='approved' ORDER BY date_from,id",[$uid,$month.'-01',pay35_month($month)])->fetchAll();}
function pay35_commissions(int $uid,string $month):array {
 $daily=pay35_contract_rows($uid,$month);$input=pay35_inputs($uid,$month);$legacy=commission_month_items($uid,$month);$lines=[];$flags=[];$bases=[];$returns=[];
 foreach($daily as $r){$v=decode($r['body_json']);$type=$v['type'];$bases[$type]=($bases[$type]??0)+(int)$v['delivered'];$bases[$type]-=(int)$v['returned'];}
 if($input['collection_entered'])$bases['collection']=(float)$input['collection'];
 $daily103=finance_bases103($uid,$month);$flags=array_merge($flags,$daily103['flags']);foreach($daily103['bases']as $type=>$base){if(array_key_exists($type,$bases))$flags[]='توجد حركات يومية ومصدر سابق للعمولة نفسها؛ راجع المصدر لمنع التكرار.';$bases[$type]=$base;}
 if($legacy){if($daily||$input['collection_entered']||$daily103['bases'])$flags[]='ملخص عمولة قديم وبيانات جديدة للشهر نفسه؛ يمنع احتساب المصدرين معًا.';foreach($legacy as $r)foreach(decode($r['body_json'])['lines']??[]as $v)$lines[]=$v+['legacy_id'=>$r['id']];return ['items'=>$legacy,'lines'=>$lines,'flags'=>$flags,'total'=>array_sum(array_column($legacy,'amount')),'source'=>'legacy'];}
 $dated119=function_exists('contract_lines119')?contract_lines119($uid,$month):['lines'=>[],'flags'=>[],'managed_agent'=>false];if($dated119['managed_agent'])unset($bases['delivery_contract']);$lines=array_merge($lines,$dated119['lines']);$flags=array_merge($flags,$dated119['flags']);
 foreach($bases as $type=>$base){if($base<0){$flags[]='المرتجع يتجاوز العقود المسلمة: '.commission_types()[$type];continue;}require_once __DIR__.'/employee-fields.php';$settings130=in_array($type,['sales_agent','collection'],true)?employee_commission_at130($uid,$type,pay35_month($month)):null;if($settings130!==null){$lines[]=['type'=>$type,'base'=>$base,'amount'=>employee_commission_amount130($settings130,$base,$type),'source'=>'employee_commission130','settings130'=>$settings130];continue;}if($type==='delivery_contract'){$terms198=contract_at119(employee_required($uid),pay35_month($month))['agent'];if($base&&!$terms198['enabled'])$flags[]='استحقاق عقود المندوب غير مفعل في بيانات الموظف.';$lines[]=['type'=>$type,'base'=>$base,'amount'=>$terms198['enabled']?round($base*$terms198['rate'],3):0,'source'=>'contract_terms119'];continue;}if($base)$flags[]='راجع إعداد العمولة بملف الموظف أو مصدر الحركة القديم: '.(commission_types()[$type]??$type);}
 $total=round(array_sum(array_column($lines,'amount')),3);return ['items'=>[['amount'=>$total,'body_json'=>jencode(['lines'=>$lines,'source'=>'daily35'])]],'lines'=>$lines,'flags'=>$flags,'total'=>$total,'source'=>'daily35'];
}
function pay35_action(string $action,array $u,array $b):array {
 if($action==='pay35_contract_save'&&in_array($b['type']??'',['sales_referral','sales_check','sales_delivery','sales_full'],true))fail('تقسيم الأنصبة القديم ملغى؛ استخدم تسجيل العقود.');
 $screen=match($action){'pay35_contract_save','pay35_contract_cancel'=>'commissions','pay35_rules_save'=>'payroll_settings35','pay35_friday_access_save'=>'payroll_settings35','pay35_input_save'=>'payroll',default=>(string)($_GET['screen']??'payroll')};
 if(!in_array($screen,['payroll','commissions','fridays35','payroll_settings35'],true))fail('شاشة غير صالحة.');
 $personal=personal_mode34($u);$op=$action==='pay35_info'?'view':($action==='pay35_contract_cancel'?'edit':'create');$uid=(int)($b['user_id']??0);
 if($personal){if($action!=='pay35_info'||$screen!=='commissions')fail('هذه العملية تخص مساحة العمل.',403);personal_require34($u,'commissions');if(!pay35_rules(local_time()->format('Y-m-d'))['daily_visible'])return ['rows'=>[],'daily_visible'=>false];$ids=[(int)$u['id']];}else $ids=pay35_scope($u,$screen,$op,$uid?:null);
 if($action==='pay35_info'){$month=(string)($_GET['month']??local_time()->format('Y-m'));$end=pay35_month($month);$out=[];foreach($ids as $id){$s=query("SELECT * FROM ep_users WHERE id=? AND role<>'admin'",[$id])->fetch();if(!$s)continue;$r=['user_id'=>$id,'code'=>employee_key($s),'name'=>$s['name']];if($screen==='commissions'){$r['daily']=pay35_contract_rows($id,$month);$r['commission']=pay35_commissions($id,$month);}elseif($screen==='fridays35')$r['friday']=pay35_fridays($s,$month);elseif($screen==='payroll'){$r['inputs']=pay35_inputs($id,$month);$r['fixed_salary']=(bool)salary_at($s,$end)['fixed_salary'];}else{require_once __DIR__.'/benefits.php';$r['friday_access']=friday_access198((int)$s['id'],$end)['enabled'];$r['attendance_required']=service_state($s,$end)['attendance'];}$out[]=$r;}usort($out,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return ['rows'=>$out,'daily_visible'=>true,'rules'=>$screen==='payroll_settings35'?pay35_rules($end):null];}
 if($action==='pay35_rules_save'){admin_only($u);$date=valid_date($b['date']??null);if(!$date||substr($date,8)!=='01')fail('الإعداد يسري من أول الشهر.');db()->beginTransaction();commission_rules_lock();if(query("SELECT id FROM ep_payroll WHERE month_key>=? AND status IN ('approved','submitted')",[substr($date,0,7)])->fetch())fail('توجد كشوف معتمدة أو مرسلة؛ اختر فترة لاحقة.');if(query("SELECT id FROM ep_records WHERE module='payroll_rules35' AND date_from=?",[$date])->fetch())fail('إعداد مسجل بنفس التاريخ.');$v=['friday_lateness'=>false,'contracts_lateness'=>false,'daily_visible'=>!empty($b['daily_visible'])];v26_insert('payroll_rules35',0,$date,null,$v,'approved');foreach(query("SELECT DISTINCT user_id FROM ep_payroll WHERE month_key>=? AND status IN ('draft','returned')",[substr($date,0,7)])->fetchAll()as $row)financial_invalidate((int)$row['user_id'],$date);audit($action,'rules',['from'=>$date,'values'=>$v]);db()->commit();return ['ok'=>true];}
 if($action==='pay35_friday_access_save'){admin_only($u);employee_required($uid);$date=valid_date($b['date']??null);if(!$date)fail('حدد تاريخ السريان.');db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid]);if(query("SELECT id FROM ep_records WHERE module='friday_access35' AND user_id=? AND date_from=?",[$uid,$date])->fetch())fail('إعداد مسجل بنفس التاريخ.');v26_insert('friday_access35',$uid,$date,null,['enabled'=>!empty($b['enabled'])],'approved');audit($action,'user:'.$uid,['date'=>$date,'enabled'=>!empty($b['enabled'])]);db()->commit();return ['ok'=>true];}
 $s=employee_required($uid);$month=(string)($b['month']??substr((string)($b['date']??''),0,7));pay35_month($month);db()->beginTransaction();commission_rules_lock();pay35_lock($uid,$month);
 if($action==='pay35_input_save'){$old=pay35_inputs($uid,$month);if(array_key_exists('collection',finance_bases103($uid,$month)['bases'])&&((float)($b['collection']??0)!=(float)$old['collection']||!empty($b['collection_entered'])!==!empty($old['collection_entered'])))fail('استخدم شاشة التسجيل اليومية لتعديل مصدر هذا الشهر.');if((int)($b['revision']??-1)!==(int)$old['revision'])fail('تغيرت البيانات؛ أعد تحميلها.',409);$pay201=query('SELECT * FROM ep_payroll WHERE user_id=? AND month_key=?',[$uid,$month])->fetch();if($pay201&&$pay201['status']==='returned'){$allowed201=array_map('intval',decode($pay201['calculation_json'])['_return201']['users']??[]);if(!in_array($uid,$allowed201,true))fail('هذا الموظف خارج مجموعة الإعادة؛ لا يُعدَّل إلا بقرار المشرف.');}if(abs(pay35_number($b['extra_days']??($old['extra_days']??0))-(float)($old['extra_days']??0))>0.0005)fail('عدّل الأيام الإضافية من شاشة تسجيل الرواتب.');if(array_key_exists('debt_choice',$b)&&abs(pay35_number($b['debt_choice'])-(float)($old['debt_choice']??0))>0.0005)fail('عدّل خصم السلف من شاشة تسجيل الرواتب.');$v=$old;$v['collection']=round(pay35_number($b['collection']??0),3);$v['collection_entered']=!empty($b['collection_entered']);$v['revision']=(int)$old['revision']+1;$v['sales_agent_deduction36']=round(pay35_number($b['sales_agent_deduction36']??$old['sales_agent_deduction36']),3);$v['collection_deduction36']=round(pay35_number($b['collection_deduction36']??$old['collection_deduction36']),3);$v['commission_deduction_note36']=mb_substr(trim((string)($b['commission_deduction_note36']??$old['commission_deduction_note36'])),0,1000);if($v['collection_entered']&&commission_month_items($uid,$month))fail('للشهر ملخص عمولة معتمد؛ لا تكرر التحصيل.');$r=query("SELECT id FROM ep_records WHERE module='payroll_input35' AND user_id=? AND date_from=?",[$uid,$month.'-01'])->fetch();if($r)query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($v),utc_now(),$r['id']]);else v26_insert('payroll_input35',$uid,$month.'-01',null,$v,'approved');audit($action,'user:'.$uid,['month'=>$month,'before'=>$old,'after'=>$v]);}
 elseif($action==='pay35_contract_cancel'){$r=query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='contracts_daily35' AND status='approved'",[(int)($b['id']??0),$uid])->fetch();$reason=trim((string)($b['reason']??''));if(!$r||substr($r['date_from'],0,7)!==$month||!$reason)fail('حدد الحركة وسبب التصحيح.');$before=decode($r['body_json']);if(!empty($before['company_sale_id36']))fail('ألغِ الحركة من مبيعات الشركة لتحديث جميع الأنصبة معًا.');$v=$before+['cancel_reason'=>$reason,'cancelled_by'=>$u['id'],'cancelled_at'=>utc_now()];query("UPDATE ep_records SET status='cancelled',body_json=?,updated_at=? WHERE id=?",[jencode($v),utc_now(),$r['id']]);$months35=[$month=>true];foreach(query("SELECT date_from FROM ep_records WHERE module='contracts_daily35' AND user_id=? AND date_from>=? AND status='approved'",[$uid,$r['date_from']])->fetchAll()as $later)$months35[substr($later['date_from'],0,7)]=true;foreach(array_keys($months35)as $m){$check=pay35_commissions($uid,$m);if($check['flags'])fail(implode(' · ',$check['flags']));}financial_invalidate($uid,$month.'-01');audit($action,'record:'.$r['id'],['before'=>$before,'reason'=>$reason]);}
 elseif($action==='pay35_contract_save'){$saved37=pay37_contract_locked($u,$s,$b,$month);if(!empty($saved37['duplicate'])){db()->commit();return $saved37;}}
 else fail('عملية غير صالحة.',404);
 pay35_invalidate($uid,$month);if($action==='pay35_contract_save')financial_invalidate($uid,$month.'-01');db()->commit();return ['ok'=>true];
}

function pay35_rule_guard(int $uid,string $from):void {
 if(query("SELECT id FROM ep_payroll WHERE (?=0 OR user_id=?) AND month_key>=? AND status IN ('approved','submitted')",[$uid,$uid,substr($from,0,7)])->fetch())fail('قاعدة العمولة مرتبطة بكشف معتمد أو مرسل.');
 foreach(query("SELECT DISTINCT user_id FROM ep_payroll WHERE (?=0 OR user_id=?) AND month_key>=? AND status IN ('draft','returned')",[$uid,$uid,substr($from,0,7)])->fetchAll()as $r)financial_invalidate((int)$r['user_id'],$from);
}

function pay35_preserve_debt(array $p):void {
 $choice=(float)(decode($p['calculation_json'])['debt_deduction']??0);if(!$choice)return;$uid=(int)$p['user_id'];$month=$p['month_key'];$v=pay35_inputs($uid,$month);$v['debt_choice']=$choice;$r=query("SELECT id FROM ep_records WHERE module='payroll_input35' AND user_id=? AND date_from=?",[$uid,$month.'-01'])->fetch();if($r)query('UPDATE ep_records SET body_json=? WHERE id=?',[jencode($v),$r['id']]);else v26_insert('payroll_input35',$uid,$month.'-01',null,$v,'approved');
}

function pay35_approved_commissions(array $ids):array {
 $out=[];foreach($ids as $uid)foreach(query("SELECT * FROM ep_payroll WHERE user_id=? AND status='approved' ORDER BY month_key DESC",[$uid])->fetchAll()as $p){$c=decode($p['calculation_json']);if(($c['commission_source35']??'')!=='daily35')continue;$lines=$c['commission_lines35']??[];if(!$lines)continue;$out[]=['id'=>'payroll-'.$p['id'],'user_id'=>$uid,'date_from'=>$p['month_key'].'-01','date_to'=>pay35_month($p['month_key']),'amount'=>array_sum(array_column($lines,'amount')),'status'=>'approved','body_json'=>jencode(['month'=>$p['month_key'],'lines'=>$lines,'note'=>'','payroll_id'=>$p['id']])];}return $out;
}

function pay37_contract_locked(array $u,array $s,array $b,string $month):array {
 $uid=(int)$s['id'];$action='pay35_contract_save';
 if(array_key_exists((string)($b['type']??''),finance_bases103($uid,$month)['bases']))fail('استخدم شاشة التسجيل اليومية لتعديل مصدر هذا الشهر.');
$date=valid_date($b['date']??null);$type=(string)($b['type']??'');if(in_array($type,['staff_contract119','supervisor_contract119'],true))fail('استخدم شاشة تسجيل العقود وإعدادات الموظف.');if(in_array($type,['sales_referral','sales_check','sales_full','sales_delivery'],true))fail('سجل مبيعات الموظفين من مبيعات الشركة لتوزيع الأنصبة دون تكرار العقود.');if(!$date||$date>local_time()->format('Y-m-d')||$type==='collection'||!isset(commission_types()[$type]))fail('تاريخ أو نوع العقود غير صالح.');$delivered=(int)pay35_number($b['delivered']??0,true);$returned=(int)pay35_number($b['returned']??0,true);if(!$delivered&&!$returned)fail('سجل المسلّم أو المرتجع.');if($delivered&&!service_state($s,$date)['employed'])fail('التسليم خارج فترة الخدمة.');$origin=(string)($b['origin_month']??$month);pay35_month($origin);if($origin>$month)fail('شهر العقد الأصلي لا يلي شهر المرتجع.');if(commission_month_items($uid,$month))fail('للشهر ملخص عمولة معتمد؛ لا تكرر العقود.');if(!commission_rule($uid,$type,pay35_month($month))&&$delivered)fail('سجل قاعدة العمولة أولًا.');
 $key=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$key))fail('معرف التسجيل غير صالح.');foreach(pay35_contract_rows($uid,$month)as $r)if((decode($r['body_json'])['request_id']??'')===$key){return ['ok'=>true,'duplicate'=>true];}
 $v=['type'=>$type,'delivered'=>$delivered,'returned'=>$returned,'origin_month'=>$origin,'request_id'=>$key,'note'=>mb_substr((string)($b['note']??''),0,1000),'actor'=>(int)$u['id']];v26_insert('contracts_daily35',$uid,$date,null,$v,'approved');$months35=[$month=>true];foreach(query("SELECT date_from FROM ep_records WHERE module='contracts_daily35' AND user_id=? AND date_from>=? AND status='approved'",[$uid,$date])->fetchAll()as $later)$months35[substr($later['date_from'],0,7)]=true;foreach(array_keys($months35)as $checkMonth){$check=pay35_commissions($uid,$checkMonth);if($check['flags'])fail(implode(' · ',$check['flags']));}audit($action,'user:'.$uid,['date'=>$date,'values'=>$v]);
 return ['ok'=>true];
}
