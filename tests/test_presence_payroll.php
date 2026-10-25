<?php
declare(strict_types=1);
// The production payroll calculator must deduct presence minutes the same way as forgotten-punch minutes.
function pay35_month($m){return $m.'-01';}function days_between($a,$b){return [$a];}function service_state($s,$d){return ['employed'=>true,'payroll'=>true];}
function pay35_inputs($id,$m){return ['extra_days'=>0,'sales_agent_deduction36'=>0,'collection_deduction36'=>0,'commission_deduction_note36'=>''];}
function allowance_keys(){return ['telephone'=>'','transport'=>'','housing'=>'','other'=>''];}function allowance_at($s,$d){return ['telephone'=>10,'transport'=>20,'housing'=>30,'other'=>0];}
function pay35_basis($s,$m,$extra){return ['salary'=>300,'effective_from'=>'2026-01-01','segments'=>[['date'=>$m.'-01']],'rule'=>'fixture','allowances'=>allowance_at($s,$m),'extra_amount'=>0,'extra_parts'=>[],'reference_salary'=>300,'reference_allowances'=>allowance_at($s,$m),'working_days'=>26,'extra_days'=>0];}
function pay35_rules($d){return [];}function pay35_fridays($s,$m){return ['flags'=>[],'amount'=>0];}function salary_at($s,$d){return ['fixed_salary'=>true,'salary'=>300];}
function attendance_report($id,$from,$to){return [['date'=>$from,'configured'=>true,'scheduled_hours'=>8,'unclosed_permission'=>false,'absence'=>false,'sick_paid'=>null,'penalty_days'=>0,'late_minutes'=>0,'early_minutes'=>0,'penalty_minutes'=>0,'presence_penalty_minutes'=>$GLOBALS['presence_minutes'],'presence_penalty_days'=>$GLOBALS['presence_days'],'overtime_minutes'=>0]];}
function query($s,$a=[]){return new class{function fetchAll(){return [];}};}function decode($s){return json_decode($s,true);}
function v26_additions_total($id,$m){return 25;}function pay35_commissions($id,$m){return ['items'=>[],'flags'=>[],'lines'=>[],'source'=>'fixture'];}function payout_held($s){return false;}function payment_snapshot($s,$d){return ['method'=>'cash','profile'=>[]];}function v28_debt_balance($id,$d){return 0;}
$source=file_get_contents(__DIR__.'/../hr/app/payroll.php');$start=strpos($source,'function payroll_calculate37(');eval(substr($source,$start));
$staff=['id'=>3,'job_title'=>'مندوب','hired_on'=>'2026-01-01','department'=>'A'];$cfg=['work_hours'=>8,'overtime_enabled'=>true,'overtime_multiplier'=>3,'salary_divisor'=>26];
$GLOBALS['presence_minutes']=0;$GLOBALS['presence_days']=0;$plain=payroll_calculate37($staff,'2026-10',$cfg);
$GLOBALS['presence_minutes']=60;$deducted=payroll_calculate37($staff,'2026-10',$cfg);
$expected=60/8/60*(300+60)/26;
if($plain['net']!==385.0||abs(($plain['net']-$deducted['net'])-$expected)>0.001)throw new RuntimeException('Presence minutes must reduce that day salary and allowances, and nothing else.');
echo "PASS presence payroll: 60 minutes deduct ".$expected." from salary and allowances.\n";
