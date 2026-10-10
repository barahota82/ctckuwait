<?php
declare(strict_types=1);
/** Historical salary is selected by effective date; approved payroll remains a snapshot. */
function salary_at(array $user,string $date): array {
 $row=query("SELECT TOP (1) period_key,body_json FROM ep_history WHERE kind='salary_history' AND employee_code=? AND period_key<=? ORDER BY period_key DESC",[$user['username'],$date])->fetch();
 if(!$row)fail('سجل تاريخ الراتب غير مكتمل للموظف '.$user['username'].' بتاريخ '.$date.'. أدخل راتبه وتاريخ سريانه قبل الحساب.');
 $b=decode($row['body_json']);return ['salary'=>(float)$b['salary'],'effective_from'=>$row['period_key']];
}
function salary_record(array $user,float $salary,string $date,string $note,int $actor):void {
 if(!is_finite($salary)||$salary<0)fail('الراتب غير صالح.');
 $date=valid_date($date);if(!$date)fail('تاريخ سريان الراتب مطلوب.');
 if(!empty($user['hired_on'])&&$date<$user['hired_on'])fail('تاريخ الراتب قبل التعيين.');
 if(query("SELECT id FROM ep_history WHERE kind='salary_history' AND employee_code=? AND period_key=?",[$user['username'],$date])->fetch())fail('توجد قيمة راتب مسجلة لهذا التاريخ؛ لا تُستبدل تلقائيًا.');
 $body=['code'=>$user['username'],'effective_from'=>$date,'salary'=>$salary,'note'=>$note];
 query('INSERT INTO ep_history(kind,employee_code,period_key,body_json,imported_at,imported_by) VALUES(?,?,?,?,?,?)',['salary_history',$user['username'],$date,jencode($body),utc_now(),$actor]);
}
