<?php
// Run with PHP 8.2+: php tests/test_permit89.php. Isolated tests, no application DB.
declare(strict_types=1);
function decode(string $s):array{return json_decode($s,true,512,JSON_THROW_ON_ERROR);}
function service_state(array $staff,string $date):array{return ['employed'=>$staff['employed']];}
function employee_required(int $id):array{return $GLOBALS['staff89'][$id];}
function fail(string $message):never{throw new RuntimeException($message);}
require __DIR__.'/../hr/app/permit.php';
$n=0;function check89(bool $ok):void{global $n;if(!$ok)throw new RuntimeException('Failed assertion '.($n+1));$n++;}
$today='2026-10-18';
foreach([true,false]as $employed)foreach(['pending','transferred']as $transfer){
 $staff=['id'=>4,'employed'=>$employed,'profile_json'=>json_encode(['ended_on'=>'2026-09-30','sponsorship_transfer89'=>['status'=>$transfer,'date'=>$transfer==='transferred'?'2026-10-01':null]])];
 $context=permit_context89($staff,$today);
 foreach(['2026-10-17','2026-10-18','2027-01-01',null]as $end){$expected=$employed?($end&&$end<$today?'expired':'active'):($transfer==='transferred'?'ended_transferred':'ended_company');check89(permit_bucket89(['date_to'=>$end],$context,$today)===$expected);}
}
// Rehired employees use current service state; missing end/transfer does not invent a transfer.
$staff=['id'=>4,'employed'=>true,'profile_json'=>'{"ended_on":"2020-01-01"}'];check89(!permit_context89($staff,$today)['ended']);
$staff['employed']=false;$staff['profile_json']='{}';$GLOBALS['EP_SERVICE_CACHE'][4]['periods']=[['date_to'=>'2026-09-30']];$GLOBALS['staff89'][4]=$staff;check89(permit_context89($staff,$today)['ended']);
$rows=[];for($i=1;$i<=120;$i++)$rows[]=['id'=>$i,'user_id'=>4,'date_to'=>$i%2?'2026-01-01':null];
check89(count(permit_records89($rows,'ended_company',$today))===120);check89(count(permit_records89($rows,'ended_transferred',$today))===0);
try{permit_records89($rows,'invalid',$today);throw new RuntimeException('Expected validation');}catch(RuntimeException $e){check89($e->getMessage()==='تبويب إذن العمل غير صالح');}
echo "PASS $n permit classification and filtering checks\n";
