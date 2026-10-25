<?php
declare(strict_types=1);
// Run the production calculator with controlled payroll inputs, including a stale enabled setting.
function pay35_month($m){return $m.'-01';}function days_between($a,$b){return [$a];}function service_state($s,$d){return ['employed'=>true,'payroll'=>true];}
function pay35_inputs($id,$m){return ['extra_days'=>0,'sales_agent_deduction36'=>0,'collection_deduction36'=>0,'commission_deduction_note36'=>''];}
function allowance_keys(){return ['telephone'=>'','transport'=>'','housing'=>'','other'=>''];}function allowance_at($s,$d){return ['telephone'=>10,'transport'=>20,'housing'=>30,'other'=>0];}
function pay35_basis($s,$m,$extra){return ['salary'=>300,'effective_from'=>'2026-01-01','segments'=>[['date'=>$m.'-01']],'rule'=>'fixture','allowances'=>allowance_at($s,$m),'extra_amount'=>0,'extra_parts'=>[],'reference_salary'=>300,'reference_allowances'=>allowance_at($s,$m),'working_days'=>26,'extra_days'=>0];}
function pay35_rules($d){return [];}function pay35_fridays($s,$m){return ['flags'=>[],'amount'=>0];}function salary_at($s,$d){return ['fixed_salary'=>true,'salary'=>300];}
function attendance_report($id,$from,$to){return [['date'=>$from,'configured'=>true,'unclosed_permission'=>false,'absence'=>false,'sick_paid'=>null,'penalty_days'=>0,'late_minutes'=>0,'early_minutes'=>0,'penalty_minutes'=>0,'overtime_minutes'=>$GLOBALS['extra_minutes']]];}
function query($s,$a=[]){return new class{function fetchAll(){return [];}};}function decode($s){return json_decode($s,true);}
function v26_additions_total($id,$m){return 25;}function pay35_commissions($id,$m){return ['items'=>[],'flags'=>[],'lines'=>[],'source'=>'fixture'];}function payout_held($s){return false;}function payment_snapshot($s,$d){return ['method'=>'cash','profile'=>[]];}function v28_debt_balance($id,$d){return 0;}
$source=file_get_contents(__DIR__.'/../hr/app/payroll.php');$start=strpos($source,'function payroll_calculate37(');eval(substr($source,$start));
$staff=['id'=>3,'job_title'=>'مندوب','hired_on'=>'2026-01-01','department'=>'A'];$cfg=['work_hours'=>8,'overtime_enabled'=>true,'overtime_multiplier'=>3,'salary_divisor'=>26];
$GLOBALS['extra_minutes']=0;$normal=payroll_calculate37($staff,'2026-10',$cfg);$GLOBALS['extra_minutes']=180;$late=payroll_calculate37($staff,'2026-10',$cfg);
if($normal['net']!==385.0||$late['net']!==385.0||$late['calculation']['addition']!==85.0)throw new RuntimeException('Staying late must not create pay; existing allowances and manual additions must remain.');
echo "PASS production payroll calculator: 180 late minutes with stale enabled overtime setting create no extra pay; 60 allowances and 25 manual addition retained.\n";
