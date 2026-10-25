<?php
declare(strict_types=1);
// Inclusive permit periods; changing a form never rewrites existing historical dates.
function permit_end155(string $from,int $years):string {
 if(!valid_date($from)||!in_array($years,[1,2,3],true))fail('اختر مدة إذن العمل وبداية سريان صحيحة.');
 return (new DateTimeImmutable($from))->modify('+'.$years.' years')->modify('-1 day')->format('Y-m-d');
}
function permit_duration155(array $r):?int {
 foreach([1,2,3]as $years)if(!empty($r['date_from'])&&!empty($r['date_to'])&&permit_end155($r['date_from'],$years)===$r['date_to'])return $years;
 return null;
}
function permit_extra155(string $mode,?array $old,array $request,?string $from):array {
 $v=$request['values']??[];$duration=$v['duration_years155']??null;$to=valid_date($request['to']??null);$extra=[];
 if($duration!==null&&$duration!==''){
  if((!is_int($duration)&&!is_string($duration))||!in_array((string)$duration,['1','2','3'],true)||!$from)fail('اختر مدة إذن العمل وبداية سريان صحيحة.');
  $to=permit_end155($from,(int)$duration);$extra['duration_years155']=(int)$duration;
 }elseif($mode!=='correct'||!$old||$from!==$old['date_from']||$to!==$old['date_to'])fail('اختر مدة إذن العمل وبداية سريان صحيحة.');
 foreach(['passport_number155','passport_expiry155']as $key){
  if(!array_key_exists($key,$v))continue;
  if(!is_string($v[$key]))fail('بيانات جواز السفر غير صالحة.');$value=trim($v[$key]);
  if($key==='passport_number155'){if(strlen($value)>160)fail('رقم جواز السفر أطول من المسموح.');}
  elseif($value!==''&&!valid_date($value))fail('تاريخ انتهاء جواز السفر غير صالح.');
  $extra[$key]=$value;
 }
 return [$to,$extra];
}
function employee_birth155(array &$profile,array $previous):void {
 $value=$profile['birth_date155']??$previous['birth_date155']??'';
 if(!is_string($value))fail('تاريخ الميلاد غير صالح.');$value=trim($value);
 if($value!==''&&(!valid_date($value)||$value>local_time()->format('Y-m-d')))fail('تاريخ الميلاد غير صالح.');
 $profile['birth_date155']=$value;
}
