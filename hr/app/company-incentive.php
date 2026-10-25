<?php
declare(strict_types=1);
// Independent company entitlement. Never feeds salary_at, leave valuation or EOS.
function incentive_rows114(array $u,string $month):array {
 pay35_month($month);$personal=personal_mode34($u);if($personal){personal_require34($u,'payroll');$ids=[(int)$u['id']];}else $ids=pay35_scope($u,'payroll');$out=[];
 foreach(array_chunk(array_map('intval',$ids),500) as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$records=query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module IN ('company_incentive114','company_incentive_month114') AND status<>'cancelled' ORDER BY date_from,id",$chunk)->fetchAll();$terms=[];$snapshots=[];
  foreach($records as $r){$v=decode($r['body_json']);$key=$r['user_id'].'|'.($v['company_code']??'');if($r['module']==='company_incentive_month114'){if(substr($r['date_from'],0,7)===$month)$snapshots[$key]=$r;}elseif($r['date_from']<=pay35_month($month)&&(!$r['date_to']||$r['date_to']>=$month.'-01'))$terms[$key]=$r;}
  foreach(array_unique([...array_keys($terms),...array_keys($snapshots)]) as $key){if($personal&&!isset($snapshots[$key]))continue;$r=$snapshots[$key]??$terms[$key];$v=decode($r['body_json']);$staff=employee_required((int)$r['user_id']);if(!isset($snapshots[$key])&&!pay37_membership($staff,$month)['during'])continue;$company=(string)$v['company_code'];if(!$personal&&!company_allowed30($u,'payroll',$company))continue;if(!isset($snapshots[$key])&&pay41_company_code($staff,$month)===$company)continue;
   $amount=(float)$r['amount'];$out[]=['user_id'=>(int)$r['user_id'],'code'=>employee_key($staff),'name'=>$staff['name'],'name_en'=>decode($staff['profile_json'])['name_en']??'','company_code'=>$company,'company_name'=>pay41_company_name($company),'term_id'=>(int)($v['term_id']??$r['id']),'record_id'=>isset($snapshots[$key])?(int)$r['id']:null,'amount'=>$amount,'net_amount123'=>(float)($v['net_amount123']??$amount),'deduction123'=>(float)($v['deduction123']??0),'loan_repayment123'=>(float)($v['loan_repayment123']??0),'status'=>isset($snapshots[$key])?$r['status']:'draft','paid'=>!empty($v['paid']),'payment_date'=>$v['payment_date']??null,'reference'=>$v['reference']??'','month'=>$month];
  }
 }return $out;
}
function incentive_action114(string $action,array $u,array $b):array {
 $month=(string)($b['month']??$_GET['month']??local_time()->format('Y-m'));pay35_month($month);
 if($action==='incentive114_info'&&isset($_GET['user_id'])){admin_only($u);$uid=(int)$_GET['user_id'];employee_required($uid);$terms=[];foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='company_incentive114' AND status='approved' ORDER BY date_from,id",[$uid])->fetchAll() as $r){$v=decode($r['body_json']);$code=(string)($v['company_code']??'');$terms[]=['id'=>(int)$r['id'],'company_code'=>$code,'company_name'=>pay41_company_name($code),'amount'=>(float)$r['amount'],'from'=>$r['date_from'],'to'=>$r['date_to']];}return !empty($_GET['editor140'])?['terms'=>$terms]:['terms'=>$terms,'companies'=>company_choices30($u)];}
 if($action==='incentive114_info')return ['rows'=>incentive_rows114($u,$month),'companies'=>personal_mode34($u)?[]:company_choices30($u)];
 admin_only($u);$uid=(int)($b['user_id']??0);$staff=employee_required($uid);$company=(string)($b['company_code']??'');if(!$company||!company_by_code($company))fail('حدد الشركة.');
 $owns134=!db()->inTransaction();if($owns134)db()->beginTransaction();try{commission_rules_lock();
 if($action==='incentive114_stop'){
  $term132=query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='company_incentive114' AND status='approved'",[(int)($b['id']??0),$uid])->fetch();$to132=valid_date($b['to']??null);if(!$term132||(decode($term132['body_json'])['company_code']??'')!==$company||!empty($term132['date_to'])||!$to132||$to132<$term132['date_from'])fail('فترة أو تاريخ إيقاف الحافز غير صالح.');
  incentive_future_guard132($uid,$company,(new DateTimeImmutable($to132))->modify('first day of next month')->format('Y-m-d'));
  query('UPDATE ep_records SET date_to=?,updated_at=? WHERE id=?',[$to132,utc_now(),$term132['id']]);audit($action,'record:'.$term132['id'],['before'=>$term132,'to'=>$to132]);
 }elseif($action==='incentive114_terms'){
  $from=valid_date($b['from']??null);$to=valid_date($b['to']??null);$amount=finance_number103($b['amount']??0);if(!$from||$from<'2017-01-01'||($to&&$to<$from)||$amount<=0)fail('تواريخ أو مبلغ الحافز غير صالحة.');
  $state=service_state($staff,$from);if(!$state['employed']||($state['company_code']??'')===$company)fail('الحافز لشركة أخرى خلال فترة خدمة الموظف.');
  if(!empty($b['resume_id135'])){
   if(!empty($b['id']))fail('إجراء الحافز غير صالح.');$resume135=query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='company_incentive114' AND status='approved'",[(int)$b['resume_id135'],$uid])->fetch();if(!$resume135||empty($resume135['date_to'])||(decode($resume135['body_json'])['company_code']??'')!==$company||$from<=$resume135['date_to'])fail('إعادة الصرف تبدأ بعد نهاية آخر فترة حافز للشركة.');
   foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='company_incentive114' AND status='approved'",[$uid])->fetchAll()as $other135)if((decode($other135['body_json'])['company_code']??'')===$company&&(empty($other135['date_to'])||$other135['date_from']>$resume135['date_from']||($other135['date_from']===$resume135['date_from']&&(int)$other135['id']>(int)$resume135['id'])))fail('اختر آخر فترة متوقفة؛ توجد فترة أحدث للشركة.');
   incentive_future_guard132($uid,$company,substr($from,0,7).'-01');
  }
  if(!empty($b['id'])){
   $previous132=query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='company_incentive114' AND status='approved'",[(int)$b['id'],$uid])->fetch();if(!$previous132||(decode($previous132['body_json'])['company_code']??'')!==$company||!empty($previous132['date_to'])||$from<=$previous132['date_from']||abs((float)$previous132['amount']-$amount)<.0005)fail('حدد مبلغًا جديدًا وتاريخًا لاحقًا للحافز المستمر.');
   incentive_future_guard132($uid,$company,substr($from,0,7).'-01');$end132=(new DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d');query('UPDATE ep_records SET date_to=?,updated_at=? WHERE id=?',[$end132,utc_now(),$previous132['id']]);audit('incentive132_change','record:'.$previous132['id'],['before'=>$previous132,'to'=>$end132,'new_amount'=>$amount,'new_from'=>$from]);
  }
  foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='company_incentive114' AND status='approved' AND date_from<=? AND (date_to IS NULL OR date_to>=?)",[$uid,$to??'9999-12-31',$from])->fetchAll() as $r)if((decode($r['body_json'])['company_code']??'')===$company)fail('توجد فترة حافز متداخلة لهذه الشركة.');
  $id=v26_insert('company_incentive114',$uid,$from,$to,['company_code'=>$company,'exclude_leave'=>true,'exclude_eos'=>true,'full_month'=>true],'approved',$amount);audit($action,'record:'.$id,['company'=>$company,'amount'=>$amount,'from'=>$from,'to'=>$to]);
 }else{
  $row=null;foreach(incentive_rows114($u,$month)as $candidate)if($candidate['user_id']===$uid&&$candidate['company_code']===$company)$row=$candidate;if(!$row)fail('الحافز غير متاح لهذا الشهر والشركة.');
  if($action==='incentive114_approve'){
   if(!$row['record_id']){$id=v26_insert('company_incentive_month114',$uid,$month.'-01',pay35_month($month),['company_code'=>$company,'term_id'=>$row['term_id'],'paid'=>false,'exclude_leave'=>true,'exclude_eos'=>true,'label'=>'حافز شهري من شركة أخرى'],'approved',$row['amount']);audit($action,'record:'.$id,['company'=>$company,'month'=>$month,'amount'=>$row['amount']]);}
  }elseif($action==='incentive114_pay'){
   if(!$row['record_id']||$row['status']!=='approved')fail('اعتمد حافز الشركة قبل تسجيل الصرف.');if(!$row['paid']){$date=valid_date($b['payment_date']??null);$reference=trim((string)($b['reference']??''));if(!$date||$date>local_time()->format('Y-m-d')||$reference==='')fail('تاريخ ومرجع الصرف مطلوبان.');$r=query("SELECT * FROM ep_records WHERE id=? AND module='company_incentive_month114'",[$row['record_id']])->fetch();$v=decode($r['body_json']);$v['paid']=true;$v['payment_date']=$date;$v['reference']=mb_substr($reference,0,200);$v['paid_amount']=(float)$r['amount'];query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($v),utc_now(),$r['id']]);audit($action,'record:'.$r['id'],['company'=>$company,'month'=>$month,'amount'=>$r['amount'],'payment_date'=>$date,'reference'=>$reference]);}
  }else fail('عملية غير صالحة.');
 }
 if($owns134)db()->commit();return ['ok'=>true];
 }catch(Throwable $e){if($owns134&&db()->inTransaction())db()->rollBack();throw $e;}
}
function incentive_grid_row114(array $r):array {
 return $r+['company_incentive114'=>true,'fixed_salary'=>false,'can_edit'=>false,'can_debt'=>false,'can_leave'=>false,'can_penalty'=>false,'errors'=>[],'inputs'=>['revision'=>0,'extra_days'=>0,'debt_choice'=>0],'pending'=>['leave_days'=>0,'penalty'=>0],'net'=>$r['net_amount123']??$r['amount'],'calculation'=>['salary'=>0,'addition'=>$r['amount'],'deduction'=>$r['deduction123']??0,'debt_deduction'=>$r['loan_repayment123']??0,'reference_salary35'=>0,'reference_allowances35'=>[],'working_days'=>0,'deduct_days'=>0,'friday'=>['dates'=>[],'amount'=>0],'commission_lines35'=>[],'leave_cash_items'=>[],'penalty35'=>0,'absence_deduction'=>0,'sick_deduction'=>0,'flags'=>[]]];
}

function incentive_future_guard132(int $id,string $company,string $from):void {
 foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='company_incentive_month114' AND status='approved' AND date_from>=?",[$id,$from])->fetchAll()as $r)if((decode($r['body_json'])['company_code']??'')===$company)fail('توجد أشهر حافز معتمدة تتأثر بالتغيير؛ راجعها أولًا.');
}
