<?php
declare(strict_types=1);
require_once __DIR__.'/export-language.php';
function pay41_export(array $u,string $table,string $month,string $company,string $search=''):array {
 $screen=pay37_screen($table);$exportIds=pay35_scope($u,$screen,'export');
 $sheet=pay37_sheet($u,$table,$month,$company);$q=pay41_search($search);
 $rows=array_values(array_filter($sheet['rows'],fn($r)=>in_array($r['user_id'],$exportIds,true)&&($q===''||str_contains(pay41_search($r['code'].' '.$r['name'].' '.$r['name_en']),$q))));
 if(!$rows)fail('لا توجد بيانات للتصدير.');
 $companyName='';foreach($sheet['companies']as $c)if($c['code']===$company)$companyName=$c['name'];
 $title=$companyName.' — '.$month.' — '.pay41_status($rows);$sheets=[];
 if($table==='payroll'){
  require_once __DIR__.'/payroll-export.php';$data=[];
  foreach($rows as $r){if(!$r['calculation'])continue;$data[]=['staff'=>employee_required($r['user_id']),'p'=>['net_amount'=>$r['net'],'status'=>$r['status']],'c'=>$r['calculation']];}
  $sheets=payroll_export_data41($data,$title);
  $details=[[$title],['الكود','الموظف','أيام العمل','الأيام الإضافية','خصم السلفة','المديونية','أيام الإجازة المعتمدة للصرف','قيمة أيام الإجازة المصروفة','أيام إجازة تنتظر الاعتماد','جزاءات تنتظر الاعتماد','الحالة','ملاحظات','سبب خصم العمولة المحفوظ','عدد الجمعات','خصم الغياب','خصم المرضيات']];
  foreach($rows as $r){$c=$r['calculation']??[];$details[]=[$r['code'],export_name143($r),$c['working_days']??null,$r['inputs']['extra_days']??0,$r['inputs']['debt_choice']??0,$r['debt_balance']??null,array_sum(array_column($c['leave_cash_items']??[],'days')),array_sum(array_column($c['leave_cash_items']??[],'amount')),$r['pending']['leave_days'],$r['pending']['penalty'],export_text143($r['status']),implode(' · ',$r['errors']),export_data143('record_text:payroll_input35:commission_deduction_note36',(string)($c['input35']['commission_deduction_note36']??'')),count($c['friday']['dates']??[]),$c['absence_deduction']??null,$c['sick_deduction']??null];}
  $sheets[]=['name'=>'Payroll Details','rows'=>$details,'merge_rows'=>[0],'header_row'=>1,'freeze_rows'=>2,'integer_columns'=>[2,13],'precision_columns'=>[3,6,8],'column_widths'=>[0=>14,1=>30]];
  return $sheets;
 }
 $sales=$table==='sales';
 $headers=$sales?['الكود','الموظف','العقود المسلّمة بالشهر','العقود المرتجعة بالشهر','صافي العقود','قيمة العمولة','خصم عمولة المبيعات','خصم السلفة','المديونية','صافي عمولة المبيعات','الحالة','سبب خصم العمولة المحفوظ','ملاحظات']:['الكود','الموظف','تم تسجيل التحصيل','المبلغ المحصل','نسبة العمولة %','قيمة العمولة','خصم عمولة التحصيل','صافي عمولة التحصيل','الحالة','سبب خصم العمولة المحفوظ','ملاحظات'];
 $out=[[$title],$headers];
 foreach($rows as $r){$v=$r['inputs'];$out[]=$sales?[$r['code'],export_name143($r),$r['delivered'],$r['returned'],$r['base'],$r['commission'],$r['deduction'],$v['debt_choice']??0,$r['debt_balance']??null,$r['component_net'],export_text143($r['status']),export_data143('record_text:payroll_input35:commission_deduction_note36',(string)($v['commission_deduction_note36']??'')),implode(' · ',$r['errors'])]:[$r['code'],export_name143($r),!empty($v['collection_entered'])?'نعم':'لا',!empty($v['collection_entered'])?$v['collection']:null,$r['rule']['rate']??null,$r['commission'],$r['deduction'],$r['component_net'],export_text143($r['status']),export_data143('record_text:payroll_input35:commission_deduction_note36',(string)($v['commission_deduction_note36']??'')),implode(' · ',$r['errors'])];}
 $total=array_fill(0,count($headers),'');$total[1]='الإجمالي';foreach($sales?[2,3,4,5,6,7,8,9]:[3,5,6,7]as $col)$total[$col]=round(array_sum(array_map(fn($r)=>(float)($r[$col]??0),array_slice($out,2))),3);$out[]=$total;
 $sheets[]=['name'=>$sales?'Sales Commission':'Collection Commission','rows'=>$out,'merge_rows'=>[0],'header_row'=>1,'freeze_rows'=>2,'integer_columns'=>$sales?[2,3,4]:[],'column_widths'=>[0=>14,1=>30]];
 // Original months remain in the complete export even though their permanent UI column is removed.
 $movements=[[$title],['الكود','الموظف','التاريخ','نوع العمولة','مسلّم','مرتجع','شهر العقد الأصلي','ملاحظات']];
 if($sales)foreach($rows as $r)foreach(pay35_contract_rows($r['user_id'],$month)as $d){$v=decode($d['body_json']);if(($v['type']??'')!=='sales_agent')continue;$movements[]=[$r['code'],export_name143($r),$d['date_from'],$v['type'],$v['delivered'],$v['returned'],$v['origin_month']??'', export_data143('record_text:contracts_daily35:note',(string)($v['note']??''))];}
 if(count($movements)>2)$sheets[]=['name'=>'Contract Movements','rows'=>$movements,'merge_rows'=>[0],'header_row'=>1,'freeze_rows'=>2,'integer_columns'=>[4,5]];
 $lines41=[[$title],['الكود','الموظف','النوع','أساس العمولة','قيمة العمولة','طريقة الحساب','المعدل','الحد','المعدل الأعلى','شهر العقد الأصلي للمرتجع']];
 foreach($rows as $r)foreach($r['commission_lines41']??[]as $line41){$rule41=$line41['rule']??[];$lines41[]=[$r['code'],export_name143($r),$line41['type'],$line41['base']??null,$line41['amount']??null,$rule41['mode']??'',$rule41['rate']??null,$rule41['threshold']??null,$rule41['upper_rate']??null,$line41['return_origin']??''];}
 if(count($lines41)>2)$sheets[]=['name'=>'Commission Details','rows'=>$lines41,'merge_rows'=>[0],'header_row'=>1,'freeze_rows'=>2];
 return $sheets;
}
