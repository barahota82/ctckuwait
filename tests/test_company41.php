<?php
declare(strict_types=1);
// Isolated CLI checks. No production connection, credentials or database writes.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=$argv[1]??(__DIR__.'/../site');
function decode(string $v):array{return json_decode($v,true,512,JSON_THROW_ON_ERROR);}
function pay35_month(string $m):string{return (new DateTimeImmutable($m.'-01'))->format('Y-m-t');}
function days_between(string $a,string $b):array{$out=[];for($d=new DateTimeImmutable($a);$d<=new DateTimeImmutable($b);$d=$d->modify('+1 day'))$out[]=$d->format('Y-m-d');return $out;}
function service_state(array $s,string $date):array{return ['employed'=>($date>=($s['from']??'2017-01-01')&&$date<=($s['until']??'9999-12-31')),'company_code'=>$date<'2026-09-15'?'A':'B'];}
function company_by_code(string $code):array{return ['name'=>'Company '.$code];}
require $root.'/hr/app/payroll-layout.php';
$count=0;function check41(bool $ok):void{global $count;if(!$ok)throw new RuntimeException('Check '.($count+1).' failed');$count++;}
$s=['profile_json'=>'{"company_code":"CURRENT"}'];
check41(pay41_company_code($s,'2026-09')==='B');
check41(pay41_company_code($s+['until'=>'2026-09-10'],'2026-09')==='A');
check41(pay41_company_code($s+['from'=>'2026-10-01'],'2026-09')==='');
check41(pay41_company_code($s,'2026-09',['work_company_code'=>'SAVED'])==='SAVED');
check41(pay41_company_code($s,'2026-09',['work_company_code'=>''])==='');
check41(pay41_build_matches($s,'2026-09','B'));
check41(!pay41_build_matches($s,'2026-09','A'));
check41(pay41_build_matches($s,'2026-09',null));
check41(pay41_company_key('')==='__unassigned41');
check41(pay41_status([['status'=>'approved'],['status'=>'draft']])==='الكشف لم يتم اعتماده');
check41(pay41_status([['status'=>'approved'],['status'=>'approved']])==='الكشف تم اعتماده');
check41(pay41_status([['status'=>'returned']])==='الكشف به ملاحظات للتعديل');
check41(pay41_status([['status'=>'approved','errors'=>['check']]])==='الكشف به ملاحظات للتعديل');
check41(pay41_search('أَحمَد')===pay41_search('احمد'));
echo 'PASS '.$count." isolated company/status checks\n";
