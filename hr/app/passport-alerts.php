<?php
declare(strict_types=1);
require_once __DIR__.'/permit-fields.php';
// Calendar months are clamped to the final day of the target month, never rolled into another month.
function passport_due155(string $expiry,int $months):string {
 $d=new DateTimeImmutable($expiry);$target=$d->modify('first day of this month')->modify('-'.$months.' months');
 return $target->setDate((int)$target->format('Y'),(int)$target->format('m'),min((int)$d->format('d'),(int)$target->format('t')))->format('Y-m-d');
}
function passport_stage155(string $expiry,string $today):?int {
 if(!valid_date($expiry)||$expiry<$today)return null;
 foreach([1,2,3,6,9]as $months)if(passport_due155($expiry,$months)<=$today)return $months;
 return null;
}
function passport_alerts155():array {
 $today=local_time()->format('Y-m-d');$sent=0;$failed=0;$people=query("SELECT * FROM ep_users WHERE role<>'admin' AND active=1")->fetchAll();service_prefetch($people);
 foreach($people as $person){
  $uid=(int)$person['id'];$profile=decode($person['profile_json']);if(!empty($profile['permit_exempt150'])||!service_state($person,$today)['employed'])continue;
  try{
   db()->beginTransaction();$locked=query('SELECT * FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid])->fetch();
   if(!$locked||empty($locked['active'])||!empty(decode($locked['profile_json'])['permit_exempt150'])){db()->commit();continue;}
   unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);if(!service_state($locked,$today)['employed']){db()->commit();continue;}
   $r=query("SELECT TOP (1) * FROM ep_records WHERE module='work_permit' AND user_id=? AND status='approved' ORDER BY date_from DESC,id DESC",[$uid])->fetch();
   $b=$r?decode($r['body_json']):[];$expiry=(string)($b['passport_expiry155']??'');$stage=passport_stage155($expiry,$today);
   if($stage===null){db()->commit();continue;}
   $key='passport155_'.$uid.'_'.substr(hash('sha256',(string)($b['passport_number155']??'').'|'.$expiry),0,32).'_'.$stage;
   if(query('SELECT setting_key FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key=?',[$key])->fetch()){db()->commit();continue;}
   notice($uid,'تنبيه انتهاء جواز السفر','يرجى تجديد جواز سفرك قبل تاريخ الانتهاء — '.$expiry,false,['category'=>'documents','type'=>'message','employee_id'=>$uid,'due'=>$expiry,'screen'=>null,'passport_months155'=>$stage]);
   query('INSERT INTO ep_settings(setting_key,value_json) VALUES(?,?)',[$key,jencode(['at'=>utc_now(),'expiry'=>$expiry,'months'=>$stage])]);
   audit('passport_expiry_notice','user:'.$uid,['expiry'=>$expiry,'months'=>$stage]);db()->commit();$sent++;
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();security_log($e,'passport_alert155');$failed++;}
 }
 return ['sent'=>$sent,'failed'=>$failed,'date'=>$today];
}
