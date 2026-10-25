<?php
declare(strict_types=1);
// Read-only roster for the future payroll-entry form. No financial writes or estimates.
function payroll_entry127(array $u):array {
 if(personal_mode34($u))fail('هذه العملية تخص مساحة العمل.',403);
 $key=(string)($_GET['key']??'');$company=match($key){'payroll_entry_click126'=>'CMP-0001','payroll_entry_afaq126'=>'CMP-0003',default=>''};if(!$company)fail('شاشة غير صالحة.',404);permission_require($u,$key);
 $section=(string)($_GET['section']??'salary');if(!in_array($section,$company==='CMP-0001'?['salary','sales','collection']:['salary'],true))fail('قسم غير صالح.');
 $month=(string)($_GET['month']??'');$end=pay35_month($month);if($month<'2017-01')fail('الشهر غير صالح.');$start=$month.'-01';$out=[];
 if(!company_allowed30($u,$key,$company))return ['rows'=>[],'month'=>$month,'section'=>$section,'company_code'=>$company];
 $ids=array_map('intval',permission_scope29($u,$key));$staff=[];foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$staff=array_merge($staff,query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$chunk)->fetchAll());}if(function_exists('service_prefetch'))service_prefetch($staff);
 require_once __DIR__.'/employee-fields.php';require_once __DIR__.'/benefits.php';foreach($ids as $id198)$GLOBALS['ADDITIONS198'][$id198]=[];foreach(array_chunk($ids,500) as $chunk198){$marks198=implode(',',array_fill(0,count($chunk198),'?'));foreach(query("SELECT * FROM ep_records WHERE user_id IN ($marks198) AND module IN ('addition','adjustment') AND status NOT IN ('cancelled','rejected','archived') ORDER BY id",$chunk198)->fetchAll() as $r198)$GLOBALS['ADDITIONS198'][(int)$r198['user_id']][]=$r198;}
 if($section==='salary'){$codes=array_values(array_unique(array_map('employee_key',$staff)));foreach($codes as $code)$GLOBALS['EP_SALARY_CACHE'][$code]=[];foreach(array_chunk($codes,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));foreach(query("SELECT employee_code,period_key,body_json FROM ep_history WHERE kind='salary_history' AND employee_code IN ($marks) ORDER BY period_key DESC",$chunk)->fetchAll()as $r)$GLOBALS['EP_SALARY_CACHE'][$r['employee_code']][]=$r;}}
 foreach($staff as $s){$last=null;$fixed=false;foreach(array_reverse(days_between($start,$end))as $date){$state=service_state($s,$date);if(!$state['employed'])continue;if($last===null)$last=['date'=>$date,'state'=>$state];if($section==='salary')try{$fixed=$fixed||(bool)salary_at($s,$date)['fixed_salary'];}catch(RuntimeException $e){$fixed=$fixed||!empty(decode($s['profile_json']??'{}')['fixed_salary']);}else break;}
  if(!$last||(string)($last['state']['company_code']??'')!==$company)continue;$id=(int)$s['id'];$state=$last['state'];if($section==='salary'&&!$fixed)continue;
  if($section==='collection'&&($state['staff_type']??$s['staff_type']??'')!=='rep')continue;
  if($section==='sales'&&empty(employee_commission_at130($id,'sales_agent',$last['date'])['enabled']))continue;
  $out[]=['user_id'=>$id,'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json']??'{}')['name_en']??'','additions198'=>($u['role']==='admin'||(permission_has($u,'addition')&&in_array($id,permission_scope29($u,'addition'),true))||(permission_has($u,'adjustment')&&in_array($id,permission_scope29($u,'adjustment'),true)))?addition_rows198($id,$month):[],'addition_create198'=>$u['role']==='admin'||((int)$u['id']!==$id&&((permission_has($u,'addition','create')&&in_array($id,permission_scope29($u,'addition','create'),true))||(permission_has($u,'adjustment','create')&&in_array($id,permission_scope29($u,'adjustment','create'),true))))];
 }usort($out,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return ['rows'=>$out,'month'=>$month,'section'=>$section,'company_code'=>$company];
}
