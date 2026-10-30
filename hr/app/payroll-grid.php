<?php
declare(strict_types=1);
require_once __DIR__.'/payroll-layout.php';
require_once __DIR__.'/company-incentive.php';
require_once __DIR__.'/payroll-history.php';
function pay37_screen(string $table):string {
 return match($table){'payroll'=>'payroll','sales'=>'commissions','collection'=>'collection37',default=>throw new RuntimeException('جدول غير صالح.')};
}
function pay37_membership(array $s,string $month):array {
 $end=pay35_month($month);$during=false;$fixed=false;$salaryError=null;$state=[];$reference=$end;
 foreach(days_between($month.'-01',$end)as $d){$st=service_state($s,$d);if(!$st['employed'])continue;$during=true;$state=$st;$reference=$d;try{$fixed=$fixed||(bool)salary_at($s,$d)['fixed_salary'];}catch(RuntimeException $e){$salaryError=$e->getMessage();}}
 require_once __DIR__.'/employee-fields.php';$agent=!empty(employee_commission_at130((int)$s['id'],'sales_agent',$reference)['enabled']);
 return ['during'=>$during,'fixed'=>$fixed,'salary_error'=>$salaryError,'sales'=>$agent,'payroll'=>!$agent||$fixed||$salaryError!==null,'collection'=>($state['staff_type']??$s['staff_type']??'')==='rep','state'=>$state,'reference'=>$reference];
}
function pay37_permission(array $u,string $screen,string $op,int $uid):bool {
 return permission_has($u,$screen,$op)&&in_array($uid,permission_scope29($u,$screen,$op),true);
}
function pay37_pending(int $uid,string $month):array {
 $out=['leave_days'=>0.0,'penalty'=>0.0];foreach(query("SELECT * FROM ep_records WHERE user_id=? AND status='pending' AND module IN ('leave_cash','discipline')",[$uid])->fetchAll()as $r){$v=decode($r['body_json']);if(($v['payroll_month']??'')!==$month)continue;if($r['module']==='leave_cash')$out['leave_days']+=(float)($v['days']??0);else $out['penalty']+=(float)$r['amount'];}return $out;
}
function pay37_part_nets201(array $calc,float $full):array {
 $parts=$calc['debt_parts201']??[];$lines=$calc['commission_lines35']??[];$ded=$calc['commission_deductions36']??[];$sales=0.0;$collection=0.0;
 foreach($lines as $line){if(($line['type']??'')==='sales_agent')$sales+=(float)($line['amount']??0);if(($line['type']??'')==='collection')$collection+=(float)($line['amount']??0);}
 $salesNet=round($sales-(float)($ded['sales_agent']??0)-(float)($parts['sales']??0),3);
 $collectionNet=round($collection-(float)($ded['collection']??0)-(float)($parts['collection']??0),3);
 $afaqNet=round((float)($calc['afaq_commission123']??0)+(float)($calc['afaq_extra_commission123']??0)-(float)($parts['afaq']??0),3);
 return ['salary'=>round($full-$salesNet-$collectionNet-$afaqNet,3),'sales'=>$salesNet,'collection'=>$collectionNet,'afaq'=>$afaqNet,'statement'=>round($full,3)];
}
function pay37_sheet(array $u,string $table,string $month,?string $company=null):array {
 $screen=pay37_screen($table);$ids=pay35_scope($u,$screen);$edit=permission_has($u,$screen,'create')?permission_scope29($u,$screen,'create'):[];$end=pay35_month($month);$rows=[];
 require_once __DIR__.'/payroll.php';
 $contexts41=[];$companies41=[];
 foreach($ids as $uid){$s=employee_required((int)$uid);$m=pay37_membership($s,$month);$history123=$table==='payroll'?historical_for((int)$uid,'payroll',$month,$month):[];if(!$history123&&(!$m['during']||!$m[$table]))continue;
  $old41=query('SELECT * FROM ep_payroll WHERE user_id=? AND month_key=?',[$uid,$month])->fetch();
  $code41=$history123?(string)(decode($history123[0]['body_json'])['work_company_code']??''):pay41_company_code($s,$month,$old41&&in_array($old41['status'],['approved','submitted'],true)?decode($old41['calculation_json']):null);$key41=pay41_company_key($code41);
  $companies41[$key41]=['code'=>$key41,'name'=>pay41_company_name($code41)];$contexts41[]=['s'=>$s,'m'=>$m,'company'=>$key41,'old'=>$old41,'history123'=>$history123];
 }
 $extra114=$table==='payroll'&&function_exists('incentive_rows114')?incentive_rows114($u,$month):[];foreach($extra114 as $extra)$companies41[$extra['company_code']]=['code'=>$extra['company_code'],'name'=>$extra['company_name']];
 ksort($companies41,SORT_NATURAL);if($company===null)$company=$companies41?(string)array_key_first($companies41):'';
 if($companies41&&!isset($companies41[$company]))fail('شركة الكشف غير متاحة في الشهر المحدد؛ أعد تحميل الشاشة.',409);
 foreach($contexts41 as $context41){if($context41['company']!==$company)continue;$s=$context41['s'];$uid=(int)$s['id'];$m=$context41['m'];
  $input=pay35_inputs((int)$uid,$month);$r=['user_id'=>(int)$uid,'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json'])['name_en']??'','fixed_salary'=>$m['fixed'],'can_edit'=>in_array((int)$uid,$edit,true),'errors'=>[]];
  $r['company_code']=$company;$old=$context41['old'];$r['status']=$old['status']??'estimate';$protected=query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key>=? AND status IN ('approved','submitted')",[$uid,$month])->fetch();if($protected||historical_for((int)$uid,'payroll',$month,$month))$r['can_edit']=false;
  $fields=match($table){'payroll'=>['extra_days','debt_choice'],'sales'=>['sales_agent_deduction36','commission_deduction_note36'],'collection'=>['collection','collection_entered','collection_deduction36','commission_deduction_note36']};
  if($table==='sales'&&!$m['fixed']&&pay37_permission($u,'payroll','view',(int)$uid))$fields[]='debt_choice';
  $source103=finance_bases103($uid,$month);$sourceType103=$table==='sales'?'sales_agent':($table==='collection'?'collection':'');$r['daily_source103']=$sourceType103!==''&&array_key_exists($sourceType103,$source103['bases']);
  $r['inputs']=array_intersect_key($input,array_flip([...$fields,'revision']));$r['inputs']['debt_choice']=$r['inputs']['debt_choice']??0;
  $r['can_debt']=false;
  if(in_array('debt_choice',$fields,true))$r['debt_balance']=v28_debt_balance((int)$uid,$end);
  if($old&&in_array('debt_choice',$fields,true))$r['inputs']['debt_choice']=(float)(decode($old['calculation_json'])['debt_deduction']??$r['inputs']['debt_choice']);
  if($table==='payroll'){
   $r['can_leave']=$r['can_edit']&&pay37_permission($u,'leave_cash','create',(int)$uid);$r['can_penalty']=$r['can_edit']&&pay37_permission($u,'discipline','create',(int)$uid);
   try{$history123=$context41['history123'];$hv123=$history123?decode($history123[0]['body_json']):null;if($hv123){$r['status']='approved';$r['historical123']=true;$r['inputs']['debt_choice']=(float)($hv123['loan_repayment']??0);}$preview=$hv123?['calculation'=>pay_history123($hv123),'net'=>(float)$hv123['net']]:($old?['calculation'=>decode($old['calculation_json']),'net'=>(float)$old['net_amount']]:payroll_calculate37($s,$month,settings()));$r['calculation']=$preview['calculation'];$r['net']=$preview['net'];$r['part_nets201']=pay37_part_nets201(is_array($preview['calculation']??null)?$preview['calculation']:[],(float)($preview['net']??0));$r['part_net201']=$company==='CMP-0003'?$r['part_nets201']['statement']:$r['part_nets201']['salary'];$r['errors']=array_values($preview['calculation']['flags']??[]);if($old&&!$hv123&&in_array($old['status'],['draft','returned','submitted'],true)){require_once __DIR__.'/payroll-entry.php';$diff=statement_diff_map201($old,$month);if($diff){$r['source_diff201']=$diff;$r['errors'][]='الكشف يحتاج تحديثًا قبل الإرسال أو الاعتماد. '.statement_diff_text201($diff);}}}catch(RuntimeException $e){$r['errors'][]=$e->getMessage();$r['calculation']=null;$r['net']=null;}
   if(is_array($r['calculation']??null)&&array_key_exists('extra_days',$r['calculation']))$r['inputs']['extra_days']=$r['calculation']['extra_days'];$r['pending']=pay37_pending((int)$uid,$month);if($old&&$old['status']==='approved'&&($u['role']??'')==='admin')$r['undo201']=['id'=>(int)$old['id'],'revision'=>financial_revision199($old)];
  }else{
   $type=$table==='sales'?'sales_agent':'collection';$partKey=$table==='sales'?'sales':'collection';$commission=pay35_commissions((int)$uid,$month);$calc=$old?decode($old['calculation_json']):[];$snap=$old&&in_array($old['status'],['draft','returned','submitted','approved'],true);
   $lines=$snap?($calc['commission_lines35']??[]):$commission['lines'];$lines=array_values(array_filter($lines,fn($l)=>($l['type']??'')===$type));$r['commission_lines41']=$lines;$r['commission']=round(array_sum(array_column($lines,'amount')),3);$r['base']=array_sum(array_column($lines,'base'));$r['errors']=$snap?array_values($calc['flags']??[]):$commission['flags'];
   if($snap&&isset($calc['commission_rules201'])&&array_key_exists($type,$calc['commission_rules201'])){$rule=$calc['commission_rules201'][$type];$sourceLabel201='statement';}elseif($snap){$rule=null;$sourceLabel201='missing';$r['errors'][]='نسخة قاعدة العمولة غير محفوظة في الكشف.';}else{$rule=employee_commission_at130((int)$uid,$type,$end);$sourceLabel201='employee';}$r['rule']=$rule?['type'=>$type,'mode'=>$type==='collection'?'percent':(!empty($rule['retro'])||($rule['mode']??'')==='retro'?'retro':'progressive'),'rate'=>$rule['rate']??0,'threshold'=>$rule['threshold']??0,'upper_rate'=>($rule['upper_rate']??(($rule['rate']??0)+($rule['bonus']??0))),'source'=>$sourceLabel201]:['type'=>$type,'source'=>$sourceLabel201,'rate'=>null];if(!$rule&&$sourceLabel201==='employee')$r['errors'][]='إعداد العمولة غير مسجل في بيانات الموظف.';
   $r['delivered']=null;$r['returned']=null;$r['contract_rows201']=null;if($snap&&!array_key_exists('delivered201',$calc)){$r['snapshot_gap201']=true;$r['errors'][]='أعداد العقود غير محفوظة في نسخة الكشف.';}elseif($snap){$r['delivered']=(int)$calc['delivered201'];$r['returned']=(int)$calc['returned201'];$r['contract_rows201']=$calc['contract_rows201']??[];}elseif($table==='sales'){$r['delivered']=0;$r['returned']=0;foreach(pay35_contract_rows((int)$uid,$month)as $d){$v=decode($d['body_json']);if(($v['type']??'')==='sales_agent'){$r['delivered']+=(int)$v['delivered'];$r['returned']+=(int)$v['returned'];}}}
   if(!$snap&&$r['daily_source103']&&$table==='sales'){$r['delivered']=$source103['totals']['sales_agent']['delivered']??0;$r['returned']=$source103['totals']['sales_agent']['returned']??0;}
   $dedKey=$table==='sales'?'sales_agent':'collection';$storedDed=$snap?(float)($calc['commission_deductions36'][$dedKey]??0):(float)($input[$table==='sales'?'sales_agent_deduction36':'collection_deduction36']??0);$r['deduction']=$storedDed;$parts=$snap?($calc['debt_parts201']??[]):($input['debt_parts201']??[]);$r['debt_part201']=round((float)($parts[$partKey]??0),3);$r['component_net']=round($r['commission']-$r['deduction']-$r['debt_part201'],3);$r['statement_net201']=$old?(float)$old['net_amount']:null;$r['part_net201']=$r['component_net'];
   if($snap){$live=round(array_sum(array_column(array_values(array_filter($commission['lines'],fn($l)=>($l['type']??'')===$type)),'amount')),3);$liveDed=(float)($input[$table==='sales'?'sales_agent_deduction36':'collection_deduction36']??0);$liveRule201=employee_commission_at130((int)$uid,$type,$end);$liveMove201=payroll_movement_rows201((int)$uid,$month);$liveDelivered201=(int)$liveMove201['delivered'];$liveReturned201=(int)$liveMove201['returned'];$diff201=[];if(abs($live-$r['commission'])>0.0005)$diff201['commissions']=['before'=>$r['commission'],'after'=>$live];if(abs($liveDed-$r['deduction'])>0.0005)$diff201['deduction']=['before'=>$r['deduction'],'after'=>$liveDed];if($rule&&$liveRule201&&abs((float)($rule['rate']??0)-(float)($liveRule201['rate']??0))>0.0005)$diff201['rate']=['before'=>$rule['rate']??0,'after'=>$liveRule201['rate']??0];if($r['delivered']!==null&&($liveDelivered201!==(int)$r['delivered']||$liveReturned201!==(int)$r['returned']))$diff201['quantities']=['before'=>['delivered'=>$r['delivered'],'returned'=>$r['returned']],'after'=>['delivered'=>$liveDelivered201,'returned'=>$liveReturned201]];$partLive201=round((float)(($input['debt_parts201'][$partKey]??0)),3);if(abs($partLive201-(float)$r['debt_part201'])>0.0005)$diff201['debt_part']=['before'=>$r['debt_part201'],'after'=>$partLive201];if($snap&&isset($calc['commission_deductions36']['note'])){$savedNote201=(string)$calc['commission_deductions36']['note'];$liveNote201=(string)($input['commission_deduction_note36']??'');$r['inputs']['commission_deduction_note36']=$savedNote201;if($savedNote201!==$liveNote201)$diff201['deduction_note']=['before'=>$savedNote201,'after'=>$liveNote201];}if($table==='collection'&&array_key_exists('collection_amount201',$calc)){$r['inputs']['collection']=$calc['collection_amount201'];$r['inputs']['collection_entered']=!empty($calc['collection_entered201']);$liveCollection201=$liveMove201['entered']?(float)$liveMove201['collection']:(float)($input['collection']??0);if(abs((float)($calc['collection_amount201']??0)-$liveCollection201)>0.0005||!empty($calc['collection_entered201'])!==$liveMove201['entered'])$diff201['collection']=['before'=>$calc['collection_amount201'],'after'=>$liveCollection201];}elseif($table==='collection'&&isset($calc['input35'])){$r['inputs']['collection']=$calc['input35']['collection']??0;$r['inputs']['collection_entered']=!empty($calc['input35']['collection_entered']);}$partView201=$table==='sales'?($calc['sales_view201']??$liveMove201['sales_view']??''):($calc['collection_view201']??$liveMove201['collection_view']??'');if($partView201==='')$partView201=$table==='sales'?'contract_sales99':'receipt_agents99';if($partView201!=='payroll_entry')$r['source_view201']=$partView201;if($diff201){$r['source_diff201']=$diff201;$r['errors'][]='الكشف يحتاج تحديثًا قبل الإرسال أو الاعتماد.';}}
  }
  $rows[]=$r;
 }
 foreach($extra114 as $extra)if($extra['company_code']===$company)$rows[]=incentive_grid_row114($extra);
 usort($rows,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return ['month'=>$month,'table'=>$table,'company'=>$company,'companies'=>array_values($companies41),'status'=>pay41_status($rows),'rows'=>$rows];
}
function pay37_field_number(array $row,string $key,bool $integer=false):float {
 try{$v=pay35_number($row[$key]??0,$integer);if($v>1000000000)fail('قيمة غير صالحة.');return $v;}catch(RuntimeException $e){throw new InvalidArgumentException($key);}
}
function pay37_request(array $u,int $uid,string $module,string $date,string $month,float $value,string $reason,string $batch):void {
 if(!pay37_permission($u,$module,'create',$uid))fail('لا توجد صلاحية لإنشاء الطلب لهذا الموظف.',403);
 if($module==='discipline'&&$u['role']!=='admin'&&$uid===(int)$u['id'])fail('لا يمكن إنشاء قرار إداري لنفسك.',403);
 $body=['payroll_month'=>$month,'reason'=>$reason,'_batch37'=>$batch];$amount=$value;
 if($module==='leave_cash'){$balance=leave_balance($uid,settings(),$date);$reserved=pay37_pending($uid,$month)['leave_days'];if($balance['cash_days']===null||$value+$reserved>(float)$balance['cash_days']+1e-9)throw new InvalidArgumentException('leave_cash_days');$body+=['days'=>$value,'payment_method'=>'bank_salary'];$amount=0;}
 $id=v26_insert($module,$uid,$date,$date,$body,'pending',$amount);audit('pay37_request',$module.':'.$id,['actor'=>$u['id'],'value'=>$value,'batch'=>$batch]);
 foreach(request_notice_recipients32($uid,$module)as $approver)notice((int)$approver,'طلب جديد: '.modules()[$module]['label'],employee_key(employee_required($uid)),false,['screen'=>$module,'record_id'=>$id]);
}
function pay37_action(string $action,array $u,array $b):array {
 if(personal_mode34($u))fail('هذه العملية تخص مساحة العمل.',403);
 if($action==='pay37_vouchers'){
  $ids=pay35_scope($u,'vouchers37');$month=(string)($_GET['month']??'');pay35_month($month);$rows=[];
  foreach($ids as $uid){$s=employee_required((int)$uid);if(!pay37_membership($s,$month)['during'])continue;$p=query("SELECT net_amount,status FROM ep_payroll WHERE user_id=? AND month_key=?",[$uid,$month])->fetch();$rows[]=['user_id'=>(int)$uid,'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json'])['name_en']??'','net'=>$p?(float)$p['net_amount']:null,'status'=>$p['status']??'none'];}
  usort($rows,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return ['rows'=>$rows,'template_ready'=>false];
 }
 $table=(string)($b['table']??$_GET['table']??'payroll');$screen=pay37_screen($table);$month=(string)($b['month']??$_GET['month']??'');pay35_month($month);
 if($action==='pay37_sheet')return pay37_sheet($u,$table,$month,isset($_GET['company'])?(string)$_GET['company']:null);
 if($action!=='pay37_save')fail('عملية غير صالحة.');
 $ids=pay35_scope($u,$screen,'create');$rows=$b['rows']??[];$key=(string)($b['request_id']??'');
 if(!is_array($rows)||!array_is_list($rows)||!$rows||count($rows)>200||!preg_match('/^[a-f0-9-]{36}$/i',$key))fail('بيانات الدفعة غير صالحة.');
 $seen=[];foreach($rows as $r){$uid=(int)($r['user_id']??0);if(!$uid||!in_array($uid,$ids,true))fail('الموظف خارج نطاق الصلاحية.',403);if(isset($seen[$uid]))fail('الموظف مكرر في الدفعة.');$seen[$uid]=true;}
 usort($rows,fn($a,$b)=>(int)$a['user_id']<=>(int)$b['user_id']);$hash=hash('sha256',jencode([$table,$month,$b['date']??null,$b['company']??null,$rows]));$errors=[];$current=0;
 db()->beginTransaction();try{
 commission_rules_lock();$receipt=query("SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE module='pay37_batch' AND title=? AND user_id=?",[$key,$u['id']])->fetch();
 if($receipt){$v=decode($receipt['body_json']);if(!hash_equals($v['hash'],$hash))fail('معرف الحفظ مستخدم ببيانات مختلفة.');db()->commit();return ['ok'=>true,'duplicate'=>true,'saved'=>array_keys($seen)];}
 foreach($rows as $row){$current=(int)$row['user_id'];$s=employee_required($current);pay35_lock($current,$month,'statement');$membership=pay37_membership($s,$month);if(isset($b['company'])&&!pay41_build_matches($s,$month,(string)$b['company']))fail('الموظف خارج شركة الكشف المحددة.',409);if(!$membership['during']||!$membership[$table])fail('الموظف لا ينتمي لهذا الجدول في الشهر المحدد.');$old=pay35_inputs($current,$month);if((int)($row['revision']??-1)!==(int)$old['revision'])throw new InvalidArgumentException('revision');
  $allowed=match($table){'payroll'=>['extra_days','debt_choice','leave_cash_days','penalty_amount','request_reason'],'sales'=>['sales_agent_deduction36','commission_deduction_note36','delivered','returned','origin_month','debt_choice'],'collection'=>['collection','collection_entered','collection_deduction36','commission_deduction_note36']};
  foreach(array_keys($row)as $field)if(!in_array($field,[...$allowed,'user_id','revision'],true))fail('خانة غير مسموحة في هذا الجدول.');
  if($table==='collection'&&array_key_exists('collection',finance_bases103($current,$month)['bases'])&&((array_key_exists('collection',$row)&&(float)$row['collection']!=(float)$old['collection'])||(isset($row['collection_entered'])&&(bool)$row['collection_entered']!==!empty($old['collection_entered']))))fail('استخدم شاشة التسجيل اليومية لتعديل مصدر هذا الشهر.');
  $v=$old;
  foreach(['sales_agent_deduction36','collection_deduction36']as $dedField201)if(array_key_exists($dedField201,$row)&&abs((float)$row[$dedField201]-(float)($old[$dedField201]??0))>0.0005)fail('خصم العمولة بلا مصدر معتمد؛ القيمة تبقى للقراءة حتى يصدر قرار.');foreach(['extra_days','collection','debt_choice']as $field)if(array_key_exists($field,$row))$v[$field]=round(pay37_field_number($row,$field),$field==='extra_days'?6:3);
  if(isset($row['collection_entered'])){if(!is_bool($row['collection_entered']))throw new InvalidArgumentException('collection_entered');$v['collection_entered']=$row['collection_entered'];}
  if(isset($row['commission_deduction_note36']))$v['commission_deduction_note36']=mb_substr(trim((string)$row['commission_deduction_note36']),0,1000);
  if(isset($row['extra_days'])&&$v['extra_days']&&!$membership['fixed'])throw new InvalidArgumentException('extra_days');
  if(isset($row['debt_choice'])&&abs((float)$row['debt_choice']-(float)($old['debt_choice']??0))>0.0005)fail('خصم السلف يُسجل من شاشة تسجيل لإعداد الرواتب.');
  if($table==='payroll'&&isset($row['extra_days'])&&abs((float)$row['extra_days']-(float)($old['extra_days']??0))>0.0005)fail('الأيام الإضافية تُسجل من شاشة تسجيل لإعداد الرواتب.');
  if($table==='payroll'&&isset($row['leave_cash_days'])&&(float)$row['leave_cash_days']>0)fail('صرف رصيد الإجازة يُسجل من شاشة تسجيل لإعداد الرواتب.');
  if(isset($row['debt_choice'])){if(!pay37_permission($u,'payroll','create',$current))fail('خصم السلفة يتطلب صلاحية إعداد الرواتب.',403);if($table==='sales'&&$membership['fixed'])fail('خصم السلفة لهذا الموظف يسجل في جدول الرواتب.');if($v['debt_choice']>v28_debt_balance($current,pay35_month($month))+.0005)throw new InvalidArgumentException('debt_choice');}
  if($table==='collection'&&$v['collection_entered']){if(commission_month_items($current,$month))fail('للشهر ملخص عمولة معتمد؛ لا تكرر التحصيل.');if(empty(employee_commission_at130($current,'collection',pay35_month($month))['enabled']))throw new InvalidArgumentException('collection');}
  $delivered=$table==='sales'?(int)pay37_field_number($row,'delivered',true):0;$returned=$table==='sales'?(int)pay37_field_number($row,'returned',true):0;
  $leave=$table==='payroll'?pay37_field_number($row,'leave_cash_days'):0;$penalty=$table==='payroll'?round(pay37_field_number($row,'penalty_amount'),3):0;
  $date=null;if($delivered||$returned||$leave||$penalty){$date=valid_date($b['date']??null);if(!$date||substr($date,0,7)!==$month||$date>local_time()->format('Y-m-d'))throw new InvalidArgumentException('date');}
  if($penalty>0)fail('الجزاءات تُسجل من شاشة الإنذارات والقرارات.');
  if($delivered||$returned)fail('العقود تُسجل من شاشة تسجيل العقود.');
  if($table==='collection'&&array_key_exists('collection',$row)&&abs((float)$row['collection']-(float)($old['collection']??0))>0.0005)fail('التحصيل يُسجل من شاشة تسجيل مبالغ التحصيل.');
  if($leave||$penalty){$reason=mb_substr(trim((string)($row['request_reason']??'')),0,1000);if($leave)pay37_request($u,$current,'leave_cash',$date,$month,$leave,$reason,$key);}
  pay35_invalidate($current,$month);$latest=pay35_inputs($current,$month);if(!array_key_exists('debt_choice',$row)&&isset($latest['debt_choice']))$v['debt_choice']=$latest['debt_choice'];$v['revision']=$old['revision']+1;
  $record=query("SELECT id FROM ep_records WHERE module='payroll_input35' AND user_id=? AND date_from=?",[$current,$month.'-01'])->fetch();if($record)query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($v),utc_now(),$record['id']]);else v26_insert('payroll_input35',$current,$month.'-01',null,$v,'approved');audit('pay37_input','user:'.$current,['month'=>$month,'table'=>$table,'before'=>$old,'after'=>$v,'batch'=>$key]);
 }
 v26_insert('pay37_batch',(int)$u['id'],$month.'-01',null,['hash'=>$hash,'table'=>$table,'month'=>$month,'users'=>array_keys($seen)],'approved');$receiptId=(int)db()->lastInsertId();query('UPDATE ep_records SET title=? WHERE id=?',[$key,$receiptId]);db()->commit();return ['ok'=>true,'saved'=>array_keys($seen)];
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();if($e instanceof PDOException)throw $e;http_response_code(200);return ['ok'=>false,'errors'=>[['user_id'=>$current,'field'=>$e instanceof InvalidArgumentException?$e->getMessage():'row','message'=>$e instanceof InvalidArgumentException?($e->getMessage()==='revision'?'تغيرت بيانات الموظف؛ أعد تحميل الجدول قبل الحفظ.':'راجع قيمة الخانة أو الصلاحية أو الرصيد المتاح.'):$e->getMessage()]]];}
}
