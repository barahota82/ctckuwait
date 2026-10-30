<?php
declare(strict_types=1);
require_once __DIR__.'/finance-history.php';
// One dated source in ep_records. Company-only rows never enter payroll.
function finance_kinds103():array {return ['contract_sales99'=>'sales_agent','contract_staff99'=>'staff_contract','contract_agents99'=>'delivery_contract','receipt_company99'=>'company_collection','receipt_click99'=>'click_collection','receipt_afaq99'=>'afaq_collection','receipt_agents99'=>'collection'];}
function finance_direct118(string $key):bool {return in_array($key,['receipt_company99','receipt_click99','receipt_afaq99','contract_staff99'],true);}
function finance_company118():string {static $code=null;if($code===null){$c=company_by_code('CMP-0001');if(!$c)fail('شركة كليك تكنولوجي غير موجودة؛ راجع بيانات الشركات.');$code=(string)$c['code'];}return $code;}
function finance_opted118(array $s,?string $date=null,string $type='staff'):bool {return (decode($s['profile_json']??'{}')[$type==='staff'?'contract_commission118':'contract_agent119']??false)===true;}
function finance_scope103(array $u,string $key,string $op='view'):array {
 if(!isset(finance_kinds103()[$key])||personal_mode34($u))fail('هذه العملية تخص مساحة العمل.',403);
 permission_require($u,$key,$op);return array_map('intval',permission_scope29($u,$key,$op));
}
function finance_number103(mixed $v,bool $integer=false):float {
 if(is_string($v)&&trim($v)==='')$v=0;
 if(!is_numeric($v)||!is_finite((float)$v)||(float)$v<0||(float)$v>($integer?1000000000:999999999.999)||($integer&&floor((float)$v)!==(float)$v))fail('قيمة غير صالحة.');
 if(!$integer&&abs((float)$v*1000-round((float)$v*1000))>0.00001)fail('المبلغ بحد أقصى ثلاث خانات عشرية.');return (float)$v;
}
function finance_body103(array $row):array {return decode($row['body_json']);}
function finance_rows103(string $key,string $from,string $to,?array $scope196=null):array {$where=$scope196===null?'':' AND user_id IN ('.implode(',',array_map('intval',array_unique([0,...$scope196]))).')';return query("SELECT * FROM ep_records WHERE module='finance_daily103' AND date_from>=? AND date_from<=?".$where." ORDER BY date_from,user_id,id",[$from,$to])->fetchAll();}
function finance_visible103(array $u,string $key,array $ids,array $r):bool {
 $v=finance_body103($r);if(($v['key']??'')!==$key)return false;
 if((int)$r['user_id']!==0)return in_array((int)$r['user_id'],$ids,true);
 return (!finance_direct118($key)||(string)($v['company_code']??'')===finance_company118())&&company_allowed30($u,$key,(string)($v['company_code']??''));
}
function finance_people103(array $ids,string $key,string $date):array {
 $staff=[];foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$staff=array_merge($staff,query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$chunk)->fetchAll());}if(function_exists('service_prefetch'))service_prefetch($staff);
 require_once __DIR__.'/employee-fields.php';
 $out=[];foreach($staff as $s){$id=(int)$s['id'];$state=service_state($s,$date);if(!$state['employed'])continue;
 $sales=!empty(employee_commission_at130($id,'sales_agent',$date)['enabled']);
 $rep=($state['staff_type']??$s['staff_type']??'')==='rep';
 if(($key==='contract_sales99'&&!$sales)||($key==='contract_staff99'&&!finance_opted118($s,$date))||($key==='contract_agents99'&&!finance_opted118($s,$date,'agent'))||($key==='receipt_agents99'&&!$rep))continue;
 $out[]=['user_id'=>$id,'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json']??'{}')['name_en']??'','company_code'=>$state['company_code']??''];
 }usort($out,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return $out;
}
function finance_legacy103(array $ids,string $key,string $from,string $to):array {
 $kind=finance_kinds103()[$key];if(!in_array($kind,['sales_agent','delivery_contract','collection'],true)||!$ids)return [];
 $records=[];foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$records=array_merge($records,query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module IN ('contracts_daily35','commission_month','payroll_input35') AND status='approved' AND date_from>=? AND date_from<=? ORDER BY id",[...$chunk,substr($from,0,7).'-01',$to])->fetchAll());}
 $out=[];$monthly=[];
 foreach($records as $r){if($r['module']!=='commission_month')continue;$v=decode($r['body_json']);$date=pay35_month(substr($r['date_from'],0,7));if($date<$from||$date>$to)continue;
 foreach($v['lines']??[]as $line)if(($line['type']??'')===$kind){$base=(float)($line['base']??0);$monthly[$r['user_id'].'|'.substr($date,0,7)]=true;$out[]=['legacy'=>true,'id'=>'month-'.$r['id'].'-'.$kind,'user_id'=>(int)$r['user_id'],'date'=>$date,'delivered'=>$kind==='collection'?0:(int)($line['historical_component106']['delivered']??max(0,(int)$base)),'returns'=>$line['historical_component106']['returns']??[],'amount'=>$kind==='collection'?$base:0];}}
 foreach($records as $r){$uid=(int)$r['user_id'];$v=decode($r['body_json']);$month=substr($r['date_from'],0,7);if(isset($monthly[$uid.'|'.$month]))continue;
 if($kind==='collection'&&$r['module']==='payroll_input35'&&!empty($v['collection_entered'])){$date=pay35_month($month);if($date>=$from&&$date<=$to)$out[]=['legacy'=>true,'id'=>'input-'.$r['id'],'user_id'=>$uid,'date'=>$date,'delivered'=>0,'returns'=>[],'amount'=>(float)$v['collection']];}
 elseif($kind!=='collection'&&$r['module']==='contracts_daily35'&&($v['type']??'')===$kind&&$r['date_from']>=$from)$out[]=['legacy'=>true,'id'=>'daily-'.$r['id'],'user_id'=>$uid,'date'=>$r['date_from'],'delivered'=>(int)$v['delivered'],'returns'=>[(string)($v['origin_month']??$month)=>(int)$v['returned']],'amount'=>0];
 }return $out;
}
function finance_day103(array $u,string $key,string $date,array $ids):array {
 $all=array_values(array_filter(finance_rows103($key,$date,$date),fn($r)=>finance_visible103($u,$key,$ids,$r)));$legacy=finance_legacy103($ids,$key,$date,$date);
 $people=str_starts_with($key,'receipt_')&&$key!=='receipt_agents99'?[]:finance_people103($ids,$key,$date);
 $newPeople118=$people;
 // Existing dated rows stay inspectable even after later changes to job/service.
 foreach([...$all,...$legacy]as $r){$id=(int)$r['user_id'];if(!$id||array_filter($people,fn($p)=>$p['user_id']===$id))continue;$s=employee_required($id);$people[]=['user_id'=>$id,'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json']??'{}')['name_en']??'','company_code'=>service_state($s,$date)['company_code']??''];}
 $companies=array_values(array_filter(company_choices30($u),fn($c)=>(!finance_direct118($key)||(string)$c['code']===finance_company118())&&company_allowed30($u,$key,(string)$c['code'])));
 $values=[];foreach($all as $r){$v=finance_body103($r);$values[]=$v+['id'=>(int)$r['id'],'user_id'=>(int)$r['user_id'],'status'=>$r['status']];}
 return ['date'=>$date,'new_people118'=>$newPeople118,'people'=>$people,'companies'=>$companies,'values'=>$values,'legacy'=>$legacy,'revision'=>hash('sha256',jencode([$all,$legacy])),'can_create'=>permission_has($u,$key,'create'),'can_edit'=>permission_has($u,$key,'edit')];
}
function finance_context119(array $u,string $key,string $date,array $ids):array {
 $companyOnly=str_starts_with($key,'receipt_')&&$key!=='receipt_agents99';$people=$companyOnly?[]:finance_people103($ids,$key,$date);
 $companies=array_values(array_filter(company_choices30($u),fn($c)=>(!finance_direct118($key)||(string)$c['code']===finance_company118())&&company_allowed30($u,$key,(string)$c['code'])));
 return ['people'=>$people,'new_people118'=>$people,'companies'=>$companies,'can_create'=>permission_has($u,$key,'create'),'can_edit'=>permission_has($u,$key,'edit'),'values'=>[],'legacy'=>[]];
}
function finance_action103(string $action,array $u,array $b):array {
 $key=(string)($b['key']??$_GET['key']??'');$ids=finance_scope103($u,$key);$kind=finance_kinds103()[$key];
 if($action==='finance103_info'){
  $from=valid_date($_GET['from']??null);$to=valid_date($_GET['to']??null);if(!$from||!$to||$from>$to||$from<'2017-01-01')fail('الفترة غير صالحة.');
  $all115=finance_rows103($key,$from,$to,in_array($key,['receipt_company99','receipt_click99','receipt_afaq99'],true)?null:$ids);$rows=[];foreach($all115 as $r)if($r['status']==='approved'&&finance_visible103($u,$key,$ids,$r))$rows[]=finance_body103($r)+['id'=>(int)$r['id'],'user_id'=>(int)$r['user_id'],'date'=>$r['date_from'],'legacy'=>false];
  $rows=array_merge($rows,finance_legacy103($ids,$key,$from,$to));$staffCache=[];foreach($rows as &$r){$s=$r['user_id']?($staffCache[(int)$r['user_id']]??=employee_required((int)$r['user_id'])):null;$r['code']=$s?employee_key($s):'—';$r['name_en']=$s?(decode($s['profile_json']??'{}')['name_en']??''):'';$r['name']=$s?$s['name']:($key==='contract_staff99'?'مبيعات مباشرة':(['receipt_click99'=>'تحصيل أونلاين كليك','receipt_afaq99'=>'تحصيل أونلاين آفاق','receipt_company99'=>'تحصيل الشركة'][$key]??'الشركة'));$r['company_name']=$s?'':(company_by_code((string)($r['company_code']??''))['name']??'');}unset($r);
  $online115=null;$period115=valid_date($_GET['period_from115']??$from);if(!$period115||$period115<$from||$period115>$to)fail('الفترة غير صالحة.');
  if(in_array($key,['receipt_company99','receipt_click99','receipt_afaq99'],true)&&permission_has($u,'receipt_click99')&&permission_has($u,'receipt_afaq99')){$online115=0;$scopes115=[];foreach(['receipt_click99','receipt_afaq99'] as $channel115)$scopes115[$channel115]=finance_scope103($u,$channel115);foreach($all115 as $r){$v=finance_body103($r);$k=$v['key']??'';if(isset($scopes115[$k])&&$r['status']==='approved'&&$r['date_from']>=$period115&&finance_visible103($u,$k,$scopes115[$k],$r))$online115+=(int)round((float)($v['amount']??0)*1000);}$online115/=1000;}
  $entry119=finance_context119($u,$key,local_time()->format('Y-m-d'),$ids);
  return ['companies119'=>$entry119['companies'],'entry_context119'=>$entry119,'people118'=>(str_starts_with($key,'receipt_')&&$key!=='receipt_agents99'?[]:($to===local_time()->format('Y-m-d')?$entry119['people']:finance_people103($ids,$key,$to))),'online_total115'=>$online115,'rows'=>$rows,'can_create'=>permission_has($u,$key,'create'),'can_edit'=>permission_has($u,$key,'edit')];
 }
 $date=valid_date($b['date']??$_GET['date']??null);if(!$date||$date<'2017-01-01'||$date>local_time()->format('Y-m-d'))fail('التاريخ غير صالح.');
 if($action==='finance103_day'){if(!empty($_GET['context119']))return finance_context119($u,$key,$date,$ids);return finance_day103($u,$key,$date,$ids);}
 if($action!=='finance103_save')fail('عملية غير صالحة.',404);
 $token=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$token))fail('معرف التسجيل غير صالح.');
 db()->beginTransaction();commission_rules_lock();
 $source=valid_date($b['source_date']??$date);if(!$source||$source<'2017-01-01'||$source>local_time()->format('Y-m-d'))fail('تاريخ المصدر غير صالح.');
 $requestHash=hash('sha256',jencode([$key,$date,$source,$b['rows']??[]]));
 foreach(query("SELECT * FROM ep_records WHERE module='finance_batch103' AND user_id=? AND date_from=?",[$u['id'],$date])->fetchAll()as $batch){$v=decode($batch['body_json']);if(($v['request_id']??'')===$token){if(($v['hash']??'')!==$requestHash)fail('معرف التسجيل مستخدم لبيانات مختلفة.',409);db()->commit();return ['ok'=>true,'duplicate'=>true];}}
 $day=finance_day103($u,$key,$source,$ids);if($source!==$date){$destination=finance_day103($u,$key,$date,$ids);if($destination['values']||$destination['legacy'])fail('يوجد تسجيل في التاريخ الجديد؛ استعلم عنه وعدله بدل دمج يومين.');}
 if(!empty($b['new_entry115'])&&($day['values']||$day['legacy']))fail('يوجد تسجيل بهذا التاريخ؛ اضغط استعلام لعرضه وتعديله',409);
 if(empty($b['new_entry115'])&&!hash_equals($day['revision'],(string)($b['revision']??'')))fail('تغيرت البيانات؛ أعد الاستعلام قبل الحفظ.',409);
 if($day['legacy']){if($source!==$date)fail('لا تنقل المصدر التاريخي إلى تاريخ آخر من نافذة التسجيل.');$legacyRows=$b['rows']??null;if(!is_array($legacyRows)||count($legacyRows)>1000)fail('صفوف التسجيل غير صالحة.');$result=finance_history_save114($u,$key,$date,$day,$legacyRows);v26_insert('finance_batch103',(int)$u['id'],$date,null,['key'=>$key,'request_id'=>$token,'hash'=>$requestHash,'changed'=>$result['changed']]);db()->commit();return $result;}
 $op=$day['values']?'edit':'create';
 $eligible=array_column($day['people'],null,'user_id');$newEligible118=array_column(finance_people103($ids,$key,$date),null,'user_id');$old=[];foreach($day['values']as $v)$old[(int)$v['user_id'].'|'.($v['company_code']??'')]=$v;
 $seen=[];$before=[];$after=[];$changed=[];$rows=$b['rows']??null;if(!is_array($rows)||count($rows)>1000)fail('صفوف التسجيل غير صالحة.');
 foreach($rows as $row){
  if(!is_array($row)||!isset($row['user_id'])||!is_numeric($row['user_id'])||(int)$row['user_id']!=(float)$row['user_id']||(int)$row['user_id']<0||!is_array($row['returns']??[]))fail('صف تسجيل غير صالح.');
  $uid=(int)($row['user_id']??-1);$company='';
  if($uid){if(!isset($eligible[$uid]))fail('الموظف خارج نطاق التسجيل.',403);$company=(string)(service_state(employee_required($uid),$date)['company_code']??'');}
  else {if(!in_array($key,['contract_staff99','receipt_company99','receipt_click99','receipt_afaq99'],true))fail('حدد الموظف.');$company=(string)($row['company_code']??'');if(finance_direct118($key)&&$company!==finance_company118())fail('هذه القناة تخص شركة كليك تكنولوجي فقط.',403);if(!$company||!company_by_code($company)||!company_allowed30($u,$key,$company,'view'))fail('الشركة خارج النطاق.',403);}
  $rowKey=$uid.'|'.$company;if(isset($seen[$rowKey]))fail('صف مكرر.');$seen[$rowKey]=true;
  $delivered=(int)finance_number103($row['delivered']??0,true);$amount=finance_number103($row['amount']??0);$returns=[];
  if(str_starts_with($key,'contract_')){foreach($row['returns']??[]as $month=>$qty){pay35_month((string)$month);if($month>substr($date,0,7)||$month<'2017-01')fail('شهر العقد الأصلي غير صالح.');$count=(int)finance_number103($qty,true);if($count)$returns[(string)$month]=$count;}$amount=0;}
  else {$delivered=0;}
  if(array_sum($returns)>1000000000)fail('إجمالي المرتجع أكبر من الحد المسموح.');ksort($returns);$values=['key'=>$key,'kind'=>$kind,'company_code'=>$company,'delivered'=>$delivered,'returns'=>$returns,'amount'=>$amount];
  $previous=$old[$rowKey]??null;if($uid&&!$previous)foreach($old as $oldKey=>$candidate)if((int)$candidate['user_id']===$uid){$previous=$candidate;$seen[$oldKey]=true;break;}$existing=$previous?array_intersect_key($previous,$values):null;
  if($source===$date&&$existing!==null&&$existing==$values)continue;
  $zero=!$delivered&&!array_sum($returns)&&!$amount;if(!$previous&&$zero)continue;
  if($uid&&in_array($key,['contract_staff99','contract_agents99'],true)&&!$previous&&!isset($newEligible118[$uid]))fail('الموظف غير محدد لاستحقاق عمولة مبيعات العقود.',403);$rowOp=$previous?'edit':'create';$writeIds=finance_scope103($u,$key,$rowOp);if(!company_allowed30($u,$key,$company,$rowOp))fail('الشركة خارج النطاق.',403);if($uid){if(!in_array($uid,$writeIds,true))fail('الموظف خارج نطاق التعديل.',403);if($source!==$date&&!array_filter(finance_people103([$uid],$key,$date),fn($p)=>$p['user_id']===$uid))fail('التاريخ الجديد خارج فترة خدمة الموظف أو دوره.');$sourceName201=payroll_source_name201($key);pay35_lock($uid,substr($source,0,7),$sourceName201);pay35_lock($uid,substr($date,0,7),$sourceName201);$legacy=finance_legacy103([$uid],$key,substr($date,0,7).'-01',pay35_month(substr($date,0,7)));if($legacy)fail('يوجد مصدر شهري أو يومي سابق؛ راجع المصدر قبل إضافة تسجيل جديد للشهر نفسه.');$changed[$uid]=true;}
  if($previous){$before[]=$previous;query('UPDATE ep_records SET body_json=?,amount=?,status=?,updated_at=?,date_from=? WHERE id=? AND module=\'finance_daily103\'',[jencode($values),$amount,$zero?'cancelled':'approved',utc_now(),$date,$previous['id']]);$id=$previous['id'];}
  else $id=v26_insert('finance_daily103',$uid,$date,null,$values,'approved',$amount);
  $after[]=$values+['id'=>$id,'user_id'=>$uid];
 }
 foreach($old as $rowKey=>$previous)if(!isset($seen[$rowKey])&&(int)$previous['user_id']!==0)fail('صف مسجل غير موجود في الطلب؛ أعد الاستعلام.',409);
 $sourceName201=payroll_source_name201($key);foreach(array_keys($changed)as $uid)financial_invalidate((int)$uid,min(substr($date,0,7),substr($source,0,7)).'-01',$sourceName201);
 if($key==='contract_staff99'&&$changed&&function_exists('contract_supervisors119'))foreach(contract_supervisors119()as $supervisor119)if(!isset($changed[$supervisor119]))financial_invalidate($supervisor119,min(substr($date,0,7),substr($source,0,7)).'-01',$sourceName201);
 v26_insert('finance_batch103',(int)$u['id'],$date,null,['key'=>$key,'request_id'=>$token,'hash'=>$requestHash,'changed'=>count($after)]);
 if($after)unset($GLOBALS['CONTRACT_MONTH119']);
 if($after)audit('finance103_save',$key.':'.$date,['from'=>$source,'to'=>$date,'before'=>$before,'after'=>$after]);db()->commit();return ['ok'=>true,'changed'=>count($after)];
}
function finance_bases103(int $uid,string $month):array {
 $bases=[];$flags=[];$totals=[];foreach(query("SELECT * FROM ep_records WHERE module='finance_daily103' AND user_id=? AND date_from>=? AND date_from<=? AND status='approved'",[$uid,$month.'-01',pay35_month($month)])->fetchAll()as $r){$v=decode($r['body_json']);$kind=$v['kind'];
 if($kind==='staff_contract'){if(!function_exists('contract_lines119')&&((int)$v['delivered']||array_sum($v['returns']??[])))$flags[]='قاعدة توزيع عمولة موظفي الشركة لم تحدد بعد.';continue;}
 if(!in_array($kind,['sales_agent','delivery_contract','collection'],true))continue;
 $totals[$kind]['delivered']=($totals[$kind]['delivered']??0)+(int)$v['delivered'];$totals[$kind]['returned']=($totals[$kind]['returned']??0)+array_sum($v['returns']??[]);
 $bases[$kind]=($bases[$kind]??0)+($kind==='collection'?(float)$v['amount']:(int)$v['delivered']-array_sum($v['returns']??[]));
 }return ['bases'=>$bases,'flags'=>$flags,'totals'=>$totals];
}
