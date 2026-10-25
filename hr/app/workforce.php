<?php
declare(strict_types=1);
require_once __DIR__.'/employee-source.php';
/* Existing relational records own dated service periods; settings own locked sequences. */
function employee_key(array $u):string { if(($u['role']??'')==='admin')return ''; $p=$u['profile']??decode($u['profile_json']??'{}');return (string)($p['employee_code']??$u['username']); }
function next_code(string $kind):string {
 if(!db()->inTransaction())throw new LogicException('Code allocation requires transaction');
 $key='sequence_'.$kind;$r=query('SELECT value_json FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key=?',[$key])->fetch();$n=$r?(int)json_decode($r['value_json'],true):0;
 do{$code=($kind==='company'?'CMP':'EMP').'-'.str_pad((string)++$n,4,'0',STR_PAD_LEFT);$exists=$kind==='company'?company_by_code($code):(employee_by_code($code)??(query('SELECT id FROM ep_users WHERE username=?',[$code])->fetch()?:null));}while($exists);
 if($r)query('UPDATE ep_settings SET value_json=? WHERE setting_key=?',[jencode($n),$key]);else query('INSERT INTO ep_settings(setting_key,value_json) VALUES(?,?)',[$key,jencode($n)]);return $code;
}
function employee_by_code(string $code):?array {foreach(query('SELECT * FROM ep_users')->fetchAll()as $u)if(strcasecmp(employee_key($u),$code)===0)return $u;return null;}
function payment_snapshot(array $u,string $date):array {
 $p=$u['profile']??decode($u['profile_json']);$row=false;foreach(query("SELECT body_json FROM ep_history WHERE kind='bank_terms' AND employee_code=? AND period_key<=? ORDER BY period_key DESC,id DESC",[employee_key($u),$date])->fetchAll()as $candidate){$end=decode($candidate['body_json'])['effective_to']??null;if(!$end||$end>=$date){$row=$candidate;break;}}if($row)$p=array_replace($p,decode($row['body_json']));elseif(query("SELECT TOP (1) id FROM ep_history WHERE kind='bank_terms' AND employee_code=?",[employee_key($u)])->fetch())foreach(['bank_name','bank_account','iban']as $k)$p[$k]='';
 $residence134=employee_residence134((int)$u['id'],$date);if($residence134)$p['residence_company_code']=$residence134['company_code'];
 $any=false;foreach(['bank_name','bank_account','iban']as $k)if(trim((string)($p[$k]??''))!=='')$any=true;
 $missing=[];if($any)foreach(['bank_name','bank_account','iban','civil_id','name_en']as $k)if(trim((string)($p[$k]??''))==='')$missing[]=$k;
 return ['method'=>!$any?'cash':($missing?'incomplete':'bank'),'missing'=>$missing,'profile'=>$p,'terms_record'=>$row!==false,'date'=>$date];
}
function salary_month_basis(array $u,string $month,array $cfg):array {
 $from=$month.'-01';$to=(new DateTimeImmutable($from))->format('Y-m-t');$n=(int)substr($to,8);$total=0.0;$segments=[];$last=null;
 foreach(days_between($from,$to)as $date){$state=service_state($u,$date);if(!$state['employed'])continue;$rate=salary_at($u,$date);if($state['period_id']&&$rate['effective_from']<$state['from'])fail('سجل راتب فترة التعيين الحالية بتاريخ سريان صحيح للموظف '.employee_key($u));if(isset($rate['fixed_salary'])&&!$rate['fixed_salary'])$rate['salary']=0.0;$total+=$rate['salary'];$last=$rate;$segments[]=['date'=>$date,'salary'=>$rate['salary'],'effective_from'=>$rate['effective_from'],'period_id'=>$state['period_id']];}
 $divisor=($cfg['salary_proration']??'calendar')==='calendar'?$n:max(1,(float)$cfg['salary_divisor']);if(count($segments)===$n&&count(array_unique(array_column($segments,'salary')))===1)$total=$segments[0]['salary']*$divisor;return ['salary'=>round($total/$divisor,3),'effective_from'=>$last['effective_from']??null,'segments'=>$segments,'rule'=>($cfg['salary_proration']??'calendar')==='calendar'?'calendar_day_proration':'configured_divisor_proration','has_service'=>(bool)$segments];
}
// Request-local bulk preload; existing mutation invalidation remains authoritative.
function service_prefetch(array $staff):void {
 if(($_SERVER['REQUEST_METHOD']??'')==='GET'&&!db()->inTransaction())foreach($staff as $u)if(($u['role']??'')!=='admin'&&isset($u['profile_json'])&&array_key_exists('salary',$u)&&array_key_exists('hired_on',$u))$GLOBALS['EP_EMPLOYEE_REQUEST119'][(int)$u['id']]=$u;
 $missing=[];foreach($staff as $u){$id=(int)$u['id'];if(($u['role']??'')!=='admin'&&!isset($GLOBALS['EP_SERVICE_CACHE'][$id]))$missing[$id]=$id;}
 foreach(array_chunk(array_values($missing),500) as $ids){
  $marks=implode(',',array_fill(0,count($ids),'?'));
  $rows=query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module IN ('service_period','service_terms','work_permit','residence_file') AND status='approved' ORDER BY user_id,date_from,id",$ids)->fetchAll();
  foreach($ids as $id){$GLOBALS['EP_SERVICE_CACHE'][$id]=['periods'=>[],'terms'=>[]];$GLOBALS['EMP_RESIDENCE134'][$id]=[];$GLOBALS['EMP_RESIDENCE_FILES134'][$id]=[];}
  foreach($rows as $r){if($r['module']==='residence_file')array_unshift($GLOBALS['EMP_RESIDENCE_FILES134'][(int)$r['user_id']],$r);elseif($r['module']==='work_permit')array_unshift($GLOBALS['EMP_RESIDENCE134'][(int)$r['user_id']],$r);else $GLOBALS['EP_SERVICE_CACHE'][(int)$r['user_id']][$r['module']==='service_period'?'periods':'terms'][]=$r;}
 }
}
function service_state(array $u,string $date):array {
 $uid=(int)$u['id'];if(($u['role']??'')==='admin')return ['employed'=>false,'attendance'=>false,'leave'=>false,'payroll'=>false,'bank'=>false,'period_id'=>null,'from'=>null,'to'=>null];if(!isset($GLOBALS['EP_SERVICE_CACHE'][$uid]))$GLOBALS['EP_SERVICE_CACHE'][$uid]=['periods'=>query("SELECT * FROM ep_records WHERE user_id=? AND module='service_period' AND status='approved' ORDER BY date_from,id",[$uid])->fetchAll(),'terms'=>query("SELECT * FROM ep_records WHERE user_id=? AND module='service_terms' AND status='approved' ORDER BY date_from,id",[$uid])->fetchAll()];$rows=$GLOBALS['EP_SERVICE_CACHE'][$uid]['periods'];
 if(!$rows){$p=$u['profile']??decode($u['profile_json']);return ['employed'=>(empty($u['hired_on'])||$u['hired_on']<=$date)&&(empty($p['ended_on'])||$p['ended_on']>=$date),'attendance'=>(bool)($p['attendance_required']??true),'leave'=>(bool)($p['leave_eligible']??true),'payroll'=>(bool)($p['payroll_visible']??true),'bank'=>(bool)($p['bank_visible']??true),'period_id'=>null,'from'=>$u['hired_on']??null,'to'=>$p['ended_on']??null];}
 foreach($rows as $r)if($r['date_from']<=$date&&(!$r['date_to']||$date<=$r['date_to'])){$v=decode($r['body_json']);$terms=$GLOBALS['EP_SERVICE_CACHE'][$uid]['terms'];foreach($terms as $term)if($term['date_from']>=$r['date_from']&&$term['date_from']<=$date&&(!$term['date_to']||$term['date_to']>=$date))$v=array_replace($v,decode($term['body_json']));$residence134=employee_residence134($uid,$date);if($residence134)$v['residence_company_code']=$residence134['company_code'];return $v+['employed'=>true,'attendance'=>true,'leave'=>true,'payroll'=>true,'bank'=>true,'period_id'=>(int)$r['id'],'from'=>$r['date_from'],'to'=>$r['date_to']];}
 return ['employed'=>false,'attendance'=>false,'leave'=>false,'payroll'=>false,'bank'=>false,'period_id'=>null,'from'=>null,'to'=>null];
}
function service_save(array $u,array $b):array {
 admin_only($u);$uid=(int)($b['user_id']??0);$staff=employee_required($uid);$from=valid_date($b['from']??null);$to=valid_date($b['to']??null);if(!$from||($to&&$to<$from))fail('تواريخ فترة الخدمة غير صالحة.');
 $values=array_intersect_key($b['values']??[],array_flip(['company_code','residence_company_code','department','job_title','staff_type','attendance','leave','payroll','bank','note']));foreach(['company_code','residence_company_code']as $k)if(!empty($values[$k])&&!company_by_code((string)$values[$k]))fail('شركة غير موجودة.');if(empty($values['company_code']))fail('شركة العمل مطلوبة.');
 if(!in_array($values['staff_type']??'', ['office','rep','remote','partner'],true))fail('طبيعة العمل غير صالحة.');
 foreach(['attendance','leave','payroll','bank']as $k)$values[$k]=(bool)($values[$k]??false);
 $owns143=!db()->inTransaction();if($owns143)db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid]);
 $id=(int)($b['id']??0);$old=$id?query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='service_period'",[$id,$uid])->fetch():null;if($id&&!$old)fail('الفترة غير موجودة.');
 if($old&&!array_key_exists('residence_company_code',decode($old['body_json'])))unset($values['residence_company_code']);
 if($old&&($old['date_from']!==$from||decode($old['body_json'])!==$values))fail('استخدم تغيير شروط بتاريخ سريان؛ لا تعدّل تاريخ الفترة أو شروطها السابقة.');
 foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='service_period' AND status='approved'",[$uid])->fetchAll()as $r)if((int)$r['id']!==$id&&$r['date_from']<=($to??'9999-12-31')&&($r['date_to']??'9999-12-31')>=$from)fail('فترات الخدمة لا تتداخل.');
 if($old){if(query("SELECT id FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>=?",[$uid,substr($to??'9999-12-31',0,7)])->fetch())fail('راجع الكشف المعتمد قبل تغيير نهاية الخدمة.');if($old['date_to']&&$old['date_to']!==$to)fail('الفترة المغلقة ثابتة.');if(!$to)fail('حدد تاريخ انتهاء الخدمة.');if(query('SELECT id FROM ep_events WHERE user_id=? AND work_date>?',[$uid,$to])->fetch())fail('توجد بصمات بعد تاريخ الانتهاء؛ راجع التاريخ.');query('UPDATE ep_records SET date_to=?,updated_at=? WHERE id=?',[$to,utc_now(),$id]);}
 else{unset($values['residence_company_code']);query("INSERT INTO ep_records(module,user_id,title,date_from,date_to,status,body_json,created_at,updated_at) VALUES('service_period',?,?,?,?, 'approved',?,?,?)",[$uid,'فترة خدمة',$from,$to,jencode($values),utc_now(),utc_now()]);$id=(int)db()->lastInsertId();}
 if(!$old&&(!$staff['hired_on']||$from<$staff['hired_on']))query('UPDATE ep_users SET hired_on=? WHERE id=?',[$from,$uid]);
 if(function_exists('schedule_validate_employee38'))schedule_validate_employee38($uid);audit('service_period','user:'.$uid,['id'=>$id,'from'=>$from,'to'=>$to]);if($owns143)db()->commit();unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);return ['id'=>$id];
}
function workforce_action(string $action,array $u,array $b):array {
 admin_only($u);
 if($action==='workforce_list'){$rows=query("SELECT r.*,u.name FROM ep_records r JOIN ep_users u ON u.id=r.user_id WHERE u.role<>'admin' AND r.module IN ('service_period','service_terms') AND r.status='approved' ORDER BY r.user_id,r.date_from,r.id")->fetchAll();foreach($rows as &$row)if($row['module']==='service_period'){$staff=query('SELECT * FROM ep_users WHERE id=?',[$row['user_id']])->fetch();$row['current_terms']=service_state($staff,min($row['date_to']??'9999-12-31',max($row['date_from'],local_time()->format('Y-m-d'))));}unset($row);return sorted_records32($rows,'service_period');}
 if($action==='workforce_save')return service_save($u,$b);
 if($action==='workforce_terms'){
 $uid=(int)($b['user_id']??0);$staff=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();$date=valid_date($b['date']??null);if(!$staff||$staff['role']==='admin'||!$date)fail('الموظف والتاريخ مطلوبان.');$st=service_state($staff,$date);if(!$st['employed']||!$st['period_id'])fail('التغيير داخل فترة خدمة مسجلة فقط.');$v=$b['values']??[];if(array_key_exists('residence_company_code',$v))fail('سجل شركة الإقامة من أذونات العمل.');$clean=[];foreach(['attendance','leave','payroll','bank']as $k)if(array_key_exists($k,$v))$clean[$k]=(bool)$v[$k];foreach(['company_code','residence_company_code']as $k)if(array_key_exists($k,$v)){if($v[$k]!==''&&!company_by_code($v[$k]))fail('شركة غير موجودة.');$clean[$k]=$v[$k];}if(isset($clean['company_code'])&&$clean['company_code']==='')fail('شركة العمل مطلوبة.');$changed134=false;foreach($clean as $key134=>$value134)if(!array_key_exists($key134,$st)||$st[$key134]!==$value134)$changed134=true;if(!$changed134)return ['ok'=>true,'unchanged'=>true];
 $owns143=!db()->inTransaction();if($owns143)db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid]);if(query("SELECT id FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>=?",[$uid,substr($date,0,7)])->fetch())fail('شروط هذه الفترة مرتبطة بكشف معتمد.');if(preparation_entry40()&&$date===$st['from']){
 $period40=query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=? AND module='service_period' AND user_id=?",[$st['period_id'],$uid])->fetch();if(!$period40)fail('الفترة غير موجودة.');
 if(query("SELECT id FROM ep_records WHERE module='service_terms' AND user_id=? AND date_from=? AND status='approved'",[$uid,$date])->fetch())fail('يوجد تغيير بهذا التاريخ.');
 $values40=array_replace(decode($period40['body_json']),$clean);query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($values40),utc_now(),$period40['id']]);unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);if(function_exists('schedule_validate_employee38'))schedule_validate_employee38($uid);audit('initial_service_corrected','record:'.$period40['id'],['before'=>$period40,'after'=>$values40]);if($owns143)db()->commit();return ['ok'=>true];
 }
 $same134=query("SELECT * FROM ep_records WHERE module='service_terms' AND user_id=? AND date_from=? AND status='approved'",[$uid,$date])->fetch();if($same134){$prior134=decode($same134['body_json']);foreach($clean as $key134=>$value134)if(array_key_exists($key134,$prior134)&&$prior134[$key134]!==$value134)fail('يوجد تغيير مختلف لنفس البيانات بهذا التاريخ.');query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode(array_replace($prior134,$clean)),utc_now(),$same134['id']]);unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);if(function_exists('schedule_validate_employee38'))schedule_validate_employee38($uid);audit('service_terms_merge134','user:'.$uid);if($owns143)db()->commit();return ['ok'=>true];}query("INSERT INTO ep_records(module,user_id,title,date_from,status,body_json,created_at,updated_at) VALUES('service_terms',?,?,?,'approved',?,?,?)",[$uid,'تغيير شروط الخدمة',$date,jencode($clean),utc_now(),utc_now()]);query("DELETE FROM ep_payroll WHERE user_id=? AND status='draft' AND month_key>=?",[$uid,substr($date,0,7)]);if(function_exists('schedule_validate_employee38'))schedule_validate_employee38($uid);audit('service_terms','user:'.$uid);if($owns143)db()->commit();unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);return ['ok'=>true];
 }
 fail('العملية غير موجودة.',404);
}

function leave_service_window(array $u,string $date):array {
 $rows=query("SELECT * FROM ep_records WHERE module='service_period' AND user_id=? AND status='approved' AND date_from<=? ORDER BY date_from DESC,id DESC",[$u['id'],$date])->fetchAll();return $rows?['from'=>$rows[0]['date_from'],'to'=>$rows[0]['date_to'],'id'=>$rows[0]['id']]:['from'=>$u['hired_on']??null,'to'=>null,'id'=>null];
}
function eligible_leave_days(array $u,string $from,string $to,array $cfg):int {
 if($to<=$from)return 0;$end=new DateTimeImmutable($to);$n=0;for($d=(new DateTimeImmutable($from))->modify('+1 day');$d<=$end;$d=$d->modify('+1 day')){$date=$d->format('Y-m-d');$st=service_state($u,$date);if($st['employed']&&$st['leave']&&(!$cfg['leave_exclude_approved_days']||(approved_day((int)$u['id'],$date)['module']??'')!=='leave'))$n++;}return $n;
}

function bank_identity_record(array $s,array $profile,string $date,int $actor,?string $until=null):void {
 $date=valid_date($date)??preparation_effective40($s,'bank_terms');if(!$date||!service_state($s,$date)['employed'])fail('حدد تاريخ سريان بيانات البنك داخل فترة الخدمة.');
 $until=valid_date($until);if($until&&$until<$date)fail('نهاية السريان قبل البداية.');$body=['code'=>employee_key($s),'effective_from'=>$date,'effective_to'=>$until,'name'=>$s['name']]+array_intersect_key($profile,array_flip(['name_en','bank_name','bank_account','iban','civil_id','residence_company_code']));
 $old=query("SELECT id,body_json FROM ep_history WHERE kind='bank_terms' AND employee_code=? AND period_key=?",[employee_key($s),$date])->fetch();if($old){$prior=decode($old['body_json']);$prior['effective_to']??=null;if(!array_diff_assoc($body,$prior))return;if(!preparation_entry40())fail('بيانات بنك مسجلة بهذا التاريخ؛ سجل تغييرًا بتاريخ جديد.');financial_invalidate((int)$s['id'],$date);query('UPDATE ep_history SET body_json=? WHERE id=?',[jencode(array_replace($prior,$body)),$old['id']]);audit('initial_bank_corrected','history:'.$old['id'],['before'=>$prior,'after'=>$body]);return;}financial_invalidate((int)$s['id'],$date);query('INSERT INTO ep_history(kind,employee_code,period_key,body_json,imported_at,imported_by) VALUES(?,?,?,?,?,?)',['bank_terms',employee_key($s),$date,jencode($body),utc_now(),$actor]);
}
