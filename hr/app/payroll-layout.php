<?php
declare(strict_types=1);
// A company's payroll follows the last employed day of the month, as payroll_calculate37 does.
function pay41_company_code(array $staff,string $month,?array $snapshot=null):string {
 if($snapshot!==null&&array_key_exists('work_company_code',$snapshot))return (string)$snapshot['work_company_code'];
 $profile=decode($staff['profile_json']);
 foreach(array_reverse(days_between($month.'-01',pay35_month($month)))as $date){$state=service_state($staff,$date);if($state['employed'])return (string)($state['company_code']??$profile['company_code']??'');}
 return '';
}
function pay41_company_key(string $code):string {return $code===''?'__unassigned41':$code;}
function pay41_company_name(string $code):string {return $code===''?'شركة العمل غير محددة':(company_by_code($code)['name']??$code);}
function pay41_status(array $rows):string {
 if(!$rows)return 'الكشف لم يتم اعتماده';
 foreach($rows as $r)if(($r['status']??'')==='returned'||!empty($r['errors']))return 'الكشف به ملاحظات للتعديل';
 foreach($rows as $r)if(($r['status']??'')!=='approved')return 'الكشف لم يتم اعتماده';
 return 'الكشف تم اعتماده';
}
function pay41_search(string $value):string {
 $value=preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u','',$value);
 return mb_strtolower(trim(strtr($value,['أ'=>'ا','إ'=>'ا','آ'=>'ا','ٱ'=>'ا','ى'=>'ي','ة'=>'ه'])));
}
function pay41_build_matches(array $staff,string $month,?string $company):bool {
 return $company===null||pay41_company_key(pay41_company_code($staff,$month))===$company;
}
