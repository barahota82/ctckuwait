<?php
declare(strict_types=1);
/** Historical salary is selected by effective date; approved payroll remains a snapshot. */
function salary_at(array $user,string $date): array {
 $code=employee_key($user);if(!isset($GLOBALS['EP_SALARY_CACHE'][$code]))$GLOBALS['EP_SALARY_CACHE'][$code]=query("SELECT period_key,body_json FROM ep_history WHERE kind='salary_history' AND employee_code=? ORDER BY period_key DESC",[$code])->fetchAll();$row=false;foreach($GLOBALS['EP_SALARY_CACHE'][$code]as $candidate)if($candidate['period_key']<=$date&&(!(decode($candidate['body_json'])['effective_to']??null)||(decode($candidate['body_json'])['effective_to']??'')>=$date)){$row=$candidate;break;}
 if(!$row)fail('سجل تاريخ الراتب غير مكتمل للموظف '.employee_key($user).' بتاريخ '.$date.'. أدخل راتبه وتاريخ سريانه قبل الحساب.');
 $b=decode($row['body_json']);return ['salary'=>(float)$b['salary'],'effective_from'=>$row['period_key'],'fixed_salary'=>(bool)($b['fixed_salary']??true)];
}
function salary_record(array $user,float $salary,string $date,string $note,int $actor,bool $fixed=true,?string $until=null):void {
 if(!is_finite($salary)||$salary<0)fail('الراتب غير صالح.');
 $date=valid_date($date)??preparation_effective40($user,'salary_history');if(!$date)fail('تاريخ سريان الراتب مطلوب.');
 $until=valid_date($until);if($until&&$until<$date)fail('نهاية السريان قبل البداية.');
 if(!empty($user['hired_on'])&&$date<$user['hired_on'])fail('تاريخ الراتب قبل التعيين.');
 if($existing=query("SELECT id,body_json FROM ep_history WHERE kind='salary_history' AND employee_code=? AND period_key=?",[employee_key($user),$date])->fetch()){$prior=decode($existing['body_json']);if(abs((float)$prior['salary']-($fixed?$salary:0))<.0001&&(bool)($prior['fixed_salary']??true)===$fixed&&($prior['effective_to']??null)===$until){
  if((string)($prior['note']??'')===$note)return;
  $body=array_replace($prior,['note'=>$note]);query('UPDATE ep_history SET body_json=? WHERE id=?',[jencode($body),$existing['id']]);audit('salary_note_corrected','history:'.$existing['id'],['before'=>$prior,'after'=>$body]);unset($GLOBALS['EP_SALARY_CACHE'][employee_key($user)]);return;
 }if(!preparation_entry40())fail('توجد قيمة راتب مسجلة لهذا التاريخ؛ لا تُستبدل تلقائيًا.');financial_invalidate((int)$user['id'],$date);$body=array_replace($prior,['salary'=>$fixed?$salary:0.0,'fixed_salary'=>$fixed,'effective_to'=>$until]);if($note!=='')$body['note']=$note;query('UPDATE ep_history SET body_json=? WHERE id=?',[jencode($body),$existing['id']]);audit('initial_salary_corrected','history:'.$existing['id'],['before'=>$prior,'after'=>$body]);unset($GLOBALS['EP_SALARY_CACHE'][employee_key($user)]);return;}
 financial_invalidate((int)$user['id'],$date);
 $body=['code'=>employee_key($user),'effective_from'=>$date,'effective_to'=>$until,'salary'=>$fixed?$salary:0.0,'fixed_salary'=>$fixed,'note'=>$note];
 query('INSERT INTO ep_history(kind,employee_code,period_key,body_json,imported_at,imported_by) VALUES(?,?,?,?,?,?)',['salary_history',employee_key($user),$date,jencode($body),utc_now(),$actor]);unset($GLOBALS['EP_SALARY_CACHE'][employee_key($user)]);
}
